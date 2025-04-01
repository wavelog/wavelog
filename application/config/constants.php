<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');

/* Error return codes */
define('EEMAILEXISTS',							'E-mail address already exists');
define('EUSERNAMEEXISTS',						'Username already exists');
define('EPASSWORDINVALID',						'Invalid password');
define('ENOSUCHUSER',							'No such user');
define('EFORBIDDEN',							'Forbidden');

define('OK',									'OK');

define('DASHBOARD_DEFAULT_QSOS_COUNT',          20);
define('DASHBOARD_QSOS_COUNT_LIMIT',            50);

define('QSO_PAGE_DEFAULT_QSOS_COUNT',           5);
define('QSO_PAGE_QSOS_COUNT_LIMIT',             20);

define('USER_SLUG_LENGTH',                      10);

define('QSO_WIDGET_DEFAULT_QSO_LIMIT',          15);
define('QSO_WIDGET_MAX_QSO_LIMIT',              50);


/* End of file constants.php */
/* Location: ./application/config/constants.php */
