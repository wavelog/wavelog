<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// The session library only autoloads CI_Session_driver, so the parent has to be pulled in here.
require_once BASEPATH.'libraries/Session/drivers/Session_redis_driver.php';

/**
 * Redis session driver with a blocking lock.
 *
 * Alternative to CI3's built-in 'redis' driver, selected with:
 *
 *     $config['sess_driver'] = 'redis2';
 *
 * Two differences to the built-in driver, everything else is inherited:
 *
 * 1. Waiting for the lock uses BLPOP instead of a poll loop. A waiting request
 *    is woken by Redis the moment the holder releases it, instead of on the
 *    next poll tick. The built-in driver sleeps between attempts, so every
 *    waiting request pays that full interval even when the lock became free
 *    right after it started waiting.
 *
 * 2. The lock holds a unique token and is released through a Lua script that
 *    only deletes the key while the token still matches. The built-in driver
 *    deletes unconditionally: a request whose lock had already expired via TTL
 *    would delete a lock that meanwhile belongs to a different request, and
 *    both would consider themselves the holder.
 *
 * A crashed holder never sends a wakeup, so BLPOP is bounded and retried. That
 * case degrades to the same behaviour as the built-in driver and is ultimately
 * resolved by the lock's own TTL.
 */
class Session_redis2_driver extends CI_Session_redis_driver {

	/**
	 * Lock TTL in seconds, safety net for holders that die without releasing.
	 * Matches the value used by the built-in driver.
	 */
	const LOCK_TTL = 300;

	/**
	 * How long a single BLPOP waits before the acquire loop retries. Bounded so
	 * a crashed holder cannot block a waiter until the total timeout.
	 */
	const BLOCK_TIMEOUT = 1;

	/**
	 * Total time to wait for the lock before giving up, in seconds.
	 */
	const MAX_WAIT = 30;

	/**
	 * Lifetime of a wakeup token in milliseconds. Releasing while nobody waits
	 * would otherwise leave the token behind for the next unrelated request.
	 */
	const QUEUE_TTL = 1000;

	/**
	 * Releases the lock only if we still own it, then wakes exactly one waiter.
	 */
	const RELEASE_SCRIPT = <<<'LUA'
if redis.call('GET', KEYS[1]) == ARGV[1] then
	redis.call('DEL', KEYS[1])
	redis.call('LPUSH', KEYS[2], '1')
	redis.call('PEXPIRE', KEYS[2], ARGV[2])
	return 1
end
return 0
LUA;

	/**
	 * Unique token identifying our own hold on the lock
	 *
	 * @var	string
	 */
	protected $_lock_token;

	// ------------------------------------------------------------------------

	/**
	 * Open
	 *
	 * Connects through the parent, then makes sure the socket read timeout
	 * outlives a blocking BLPOP - otherwise phpRedis throws instead of waiting.
	 *
	 * @param	string	$save_path	Server path
	 * @param	string	$name		Session cookie name, unused
	 * @return	bool
	 */
	public function open($save_path, $name): bool
	{
		$result = parent::open($save_path, $name);

		if ($result === $this->_success && isset($this->_redis))
		{
			$this->_redis->setOption(Redis::OPT_READ_TIMEOUT, self::BLOCK_TIMEOUT + 2);
		}

		return $result;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get lock
	 *
	 * @param	string	$session_id	Session ID
	 * @return	bool
	 */
	protected function _get_lock($session_id)
	{
		$lock_key = $this->_key_prefix.$session_id.':lock';

		// PHP 7 reuses the SessionHandler object on regeneration, so the lock
		// may already be ours - just refresh its TTL (same as the parent does).
		if ($this->_lock_key === $lock_key)
		{
			return $this->_redis->{$this->_setTimeout_name}($this->_lock_key, self::LOCK_TTL);
		}

		$queue_key = $lock_key.':queue';
		$token = bin2hex(random_bytes(16));
		$deadline = microtime(TRUE) + self::MAX_WAIT;

		do
		{
			if ($this->_redis->set($lock_key, $token, array('nx', 'ex' => self::LOCK_TTL)))
			{
				$this->_lock_key = $lock_key;
				$this->_lock_token = $token;
				$this->_lock = TRUE;
				return TRUE;
			}

			// Someone else holds it. Wait to be woken by their release.
			try
			{
				$this->_redis->blPop(array($queue_key), self::BLOCK_TIMEOUT);
			}
			catch (RedisException $e)
			{
				// Never let a blocking read take the whole request down.
				log_message('error', 'Session: Got RedisException while waiting for lock '.$lock_key.': '.$e->getMessage());
				usleep(100000);
			}
		}
		while (microtime(TRUE) < $deadline);

		log_message('error', 'Session: Unable to obtain lock for '.$lock_key.' within '.self::MAX_WAIT.' seconds, aborting.');
		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Release lock
	 *
	 * @return	bool
	 */
	protected function _release_lock()
	{
		if ( ! isset($this->_redis, $this->_lock_key) OR ! $this->_lock)
		{
			return TRUE;
		}

		$released = $this->_redis->eval(
			self::RELEASE_SCRIPT,
			array($this->_lock_key, $this->_lock_key.':queue', $this->_lock_token, self::QUEUE_TTL),
			2
		);

		if ($released !== 1)
		{
			// Our lock had already expired and may belong to another request by
			// now. Deleting it would be exactly the bug this driver avoids, so
			// only drop our own state.
			log_message('error', 'Session: Lock for '.$this->_lock_key.' expired before it was released, session data may have been written concurrently.');
		}

		$this->_lock_key = NULL;
		$this->_lock_token = NULL;
		$this->_lock = FALSE;
		return TRUE;
	}

}
