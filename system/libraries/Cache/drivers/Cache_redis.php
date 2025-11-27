<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2019, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 3.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter Redis Caching Class
 *
 * @package	   CodeIgniter
 * @subpackage Libraries
 * @category   Core
 * @author	   Anton Lindqvist <anton@qvister.se>
 * @link
 */
class CI_Cache_redis extends CI_Driver
{
	/**
	 * Default config
	 *
	 * @static
	 * @var	array
	 */
	protected static $_default_config = array(
		'host' => '127.0.0.1',
		'password' => NULL,
		'port' => 6379,
		'timeout' => 0,
		'database' => 0
	);

	/**
	 * Redis connection
	 *
	 * @var	Redis
	 */
	protected $_redis;

	/**
	 * Connection status flag
	 *
	 * @var	bool
	 */
	protected $_connected = FALSE;


	/**
	 * del()/delete() method name depending on phpRedis version
	 *
	 * @var	string
	 */
	protected static $_delete_name;

	/**
	 * sRem()/sRemove() method name depending on phpRedis version
	 *
	 * @var	string
	 */
	protected static $_sRemove_name;

	// ------------------------------------------------------------------------

	/**
	 * Class constructor
	 *
	 * Setup Redis
	 *
	 * Loads Redis config file if present. Will halt execution
	 * if a Redis connection can't be established.
	 *
	 * @return	void
	 * @see		Redis::connect()
	 */
	public function __construct()
	{
		if ( ! $this->is_supported())
		{
			log_message('error', 'Cache: Failed to create Redis object; extension not loaded?');
			return;
		}

		if ( ! isset(static::$_delete_name, static::$_sRemove_name))
		{
			if (version_compare(phpversion('redis'), '5', '>='))
			{
				static::$_delete_name  = 'del';
				static::$_sRemove_name = 'sRem';
			}
			else
			{
				static::$_delete_name  = 'delete';
				static::$_sRemove_name = 'sRemove';
			}
		}

		$CI =& get_instance();

		if ($CI->config->load('redis', TRUE, TRUE))
		{
			$redis_config = $CI->config->item('redis');

			// Handle nested array structure from config file
			if (is_array($redis_config) && isset($redis_config['redis']))
			{
				$redis_config = $redis_config['redis'];
			}

			if (is_array($redis_config))
			{
				$config = array_merge(self::$_default_config, $redis_config);
			}
			else
			{
				$config = self::$_default_config;
			}
		}
		else
		{
			$config = self::$_default_config;
		}

		$this->_redis = new Redis();

		try
		{
			$connected = $this->_redis->connect($config['host'], ($config['host'][0] === '/' ? 0 : $config['port']), $config['timeout']);

			if ($connected)
			{
				$this->_connected = TRUE;

				if (isset($config['password']) && $config['password'] !== NULL && $config['password'] !== '' && ! $this->_redis->auth($config['password']))
				{
					log_message('error', 'Cache: Redis authentication failed.');
					$this->_connected = FALSE;
				}

				if ($this->_connected && isset($config['database']) && $config['database'] > 0 && ! $this->_redis->select($config['database']))
				{
					log_message('error', 'Cache: Redis select database failed.');
					$this->_connected = FALSE;
				}

				if ($this->_connected)
				{
					log_message('info', 'Cache: Redis server is being used ('.$config['host'].':'.$config['port'].')');
				}
			}
			else
			{
				log_message('error', 'Cache: Redis connection failed. Check your configuration.');
				$this->_connected = FALSE;
			}
		}
		catch (RedisException $e)
		{
			log_message('error', 'Cache: Redis connection failed - '.$e->getMessage());
			$this->_connected = FALSE;
		}
		catch (Exception $e)
		{
			log_message('error', 'Cache: Redis connection error - '.$e->getMessage());
			$this->_connected = FALSE;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Get cache
	 *
	 * @param	string	$key	Cache ID
	 * @return	mixed
	 */
	public function get($key)
	{
		if ( ! $this->_connected)
		{
			return FALSE;
		}

		try
		{
			$data = $this->_redis->hMGet($key, array('__ci_type', '__ci_value'));

			if ( ! isset($data['__ci_type'], $data['__ci_value']) OR $data['__ci_type'] === FALSE)
			{
				return FALSE;
			}

			switch ($data['__ci_type'])
			{
				case 'array':
				case 'object':
					return unserialize($data['__ci_value']);
				case 'boolean':
					return (bool) $data['__ci_value'];
				case 'integer':
					return (int) $data['__ci_value'];
				case 'double':
					return (float) $data['__ci_value'];
				case 'string':
					return (string) $data['__ci_value'];
				case 'NULL':
					return NULL;
				case 'resource':
				default:
					return FALSE;
			}
		}
		catch (RedisException $e)
		{
			log_message('error', 'Cache: Redis get operation failed - ' . $e->getMessage());
			return FALSE;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Save cache
	 *
	 * @param	string	$id	Cache ID
	 * @param	mixed	$data	Data to save
	 * @param	int	$ttl	Time to live in seconds
	 * @param	bool	$raw	Whether to store the raw value (unused)
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function save($id, $data, $ttl = 60, $raw = FALSE)
	{
		if ( ! $this->_connected)
		{
			return FALSE;
		}

		try
		{
			switch ($data_type = gettype($data))
			{
				case 'array':
				case 'object':
					$data = serialize($data);
					break;
				case 'boolean':
				case 'integer':
				case 'double': // Yes, 'double' is returned and NOT 'float'
				case 'string':
				case 'NULL':
					break;
				case 'resource':
				default:
					return FALSE;
			}

			if ( ! $this->_redis->hMSet($id, array('__ci_type' => $data_type, '__ci_value' => $data)))
			{
				return FALSE;
			}
			else
			{
				$this->_redis->{static::$_sRemove_name}('_ci_redis_serialized', $id);
			}

			if ($ttl > 0)
			{
				$this->_redis->expire($id, $ttl);
			}

			return TRUE;
		}
		catch (RedisException $e)
		{
			log_message('error', 'Cache: Redis save operation failed - ' . $e->getMessage());
			return FALSE;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete from cache
	 *
	 * @param	string	$key	Cache key
	 * @return	bool
	 */
	public function delete($key)
	{
		if ( ! $this->_connected)
		{
			return FALSE;
		}

		try
		{
			if ($this->_redis->{static::$_delete_name}($key) !== 1)
			{
				return FALSE;
			}

			$this->_redis->{static::$_sRemove_name}('_ci_redis_serialized', $key);

			return TRUE;
		}
		catch (RedisException $e)
		{
			log_message('error', 'Cache: Redis delete operation failed - ' . $e->getMessage());
			return FALSE;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Increment a raw value
	 *
	 * @param	string	$id	Cache ID
	 * @param	int	$offset	Step/value to add
	 * @return	mixed	New value on success or FALSE on failure
	 */
	public function increment($id, $offset = 1)
	{
		if ( ! $this->_connected)
		{
			return FALSE;
		}

		try
		{
			return $this->_redis->incrBy($id, $offset);
		}
		catch (RedisException $e)
		{
			log_message('error', 'Cache: Redis increment failed - ' . $e->getMessage());
			return FALSE;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Decrement a raw value
	 *
	 * @param	string	$id	Cache ID
	 * @param	int	$offset	Step/value to reduce by
	 * @return	mixed	New value on success or FALSE on failure
	 */
	public function decrement($id, $offset = 1)
	{
		if ( ! $this->_connected)
		{
			return FALSE;
		}

		try
		{
			return $this->_redis->decrBy($id, $offset);
		}
		catch (RedisException $e)
		{
			log_message('error', 'Cache: Redis decrement failed - ' . $e->getMessage());
			return FALSE;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Clean cache
	 *
	 * @return	bool
	 * @see		Redis::flushDB()
	 */
	public function clean()
	{
		if ( ! $this->_connected)
		{
			return FALSE;
		}

		try
		{
			return $this->_redis->flushDB();
		}
		catch (RedisException $e)
		{
			log_message('error', 'Cache: Redis clean failed - ' . $e->getMessage());
			return FALSE;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Get cache driver info
	 *
	 * @param	string	$type	Not supported in Redis.
	 *				Only included in order to offer a
	 *				consistent cache API.
	 * @return	array
	 * @see		Redis::info()
	 */
	public function cache_info($type = NULL)
	{
		if ( ! $this->_connected)
		{
			return FALSE;
		}

		try
		{
			return $this->_redis->info();
		}
		catch (RedisException $e)
		{
			log_message('error', 'Cache: Redis info failed - ' . $e->getMessage());
			return FALSE;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Get cache metadata
	 *
	 * @param	string	$key	Cache key
	 * @return	array
	 */
	public function get_metadata($key)
	{
		if ( ! $this->_connected)
		{
			return FALSE;
		}

		$value = $this->get($key);

		if ($value !== FALSE)
		{
			return array(
				'expire' => time() + $this->_redis->ttl($key),
				'data' => $value
			);
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Check if Redis driver is supported
	 *
	 * @return	bool
	 */
	public function is_supported()
	{
		return extension_loaded('redis');
	}

	// ------------------------------------------------------------------------

	/**
	 * Class destructor
	 *
	 * Closes the connection to Redis if present.
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		if ($this->_redis)
		{
			$this->_redis->close();
		}
	}
}
