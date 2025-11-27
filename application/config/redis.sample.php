<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Redis Configuration
|--------------------------------------------------------------------------
|
| Configuration for Redis cache adapter used by CodeIgniter's Cache driver.
|
| Copy this file to redis.php and configure your Redis connection settings.
|
| For setup instructions and documentation, visit:
| https://github.com/wavelog/wavelog/wiki/Redis
|
*/

$config['redis'] = array(
    'host'     => '127.0.0.1',    // Redis server hostname/IP
    'password' => NULL,            // Redis password (NULL if no password)
    'port'     => 6379,           // Redis server port
    'timeout'  => 0,              // Connection timeout (0 = no timeout)
    'database' => 0,              // Redis database number (0-15)
);
