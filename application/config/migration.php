<?php defined('BASEPATH') OR exit('No direct script access allowed');
/*
|--------------------------------------------------------------------------
| Enable/Disable Migrations
|--------------------------------------------------------------------------
|
| Migrations are disabled by default but should be enabled
| whenever you intend to do a schema migration.
|
*/
$config['migration_enabled'] = TRUE;


/*
|--------------------------------------------------------------------------
| Migrations version
|--------------------------------------------------------------------------
|
| This is used to set migration version that the file system should be on.
| If you run $this->migration->latest() this is the version that schema will
| be upgraded / downgraded to.
|
*/

$config['migration_version'] = 225;

/*
|--------------------------------------------------------------------------
| Migrations Path
|--------------------------------------------------------------------------
|
| Path to your migrations folder.
| Typically, it will be within your application path.
| Also, writing permission is required within the migrations path.
|
*/
$config['migration_path'] = APPPATH . 'migrations/';


/*
|--------------------------------------------------------------------------
| Migration Lockfile
|--------------------------------------------------------------------------
|
| Path to the migration lockfile.
| This lockfile prevents migrations from running twice
|
*/
$config['migration_lockfile'] = sys_get_temp_dir() . '/.migration_running';

// the maximum age of the lockfile in seconds
$config['migration_lf_maxage'] = 300;


/* End of file migration.php */
/* Location: ./application/config/migration.php */
