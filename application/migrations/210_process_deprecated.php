<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_process_deprecated extends CI_Migration
{
   public function up() {

      if (file_exists('.git')) {

         try {

            exec('git reset assets/json/dok.txt');
				exec('git reset assets/json/pota.txt');
            exec('git reset assets/resources/sota.txt');
            exec('git reset assets/resources/wwff.txt');

            exec('rm assets/json/dok.txt');
            exec('rm assets/json/pota.txt');
            exec('git restore assets/resources/sota.txt');
            exec('git restore assets/resources/wwff.txt');

         } catch (\Throwable $th) {

            $branch = trim(exec('git rev-parse --abbrev-ref HEAD'));
				log_message("Error","Mig: Error at Migrate txt files. Run manually: git reset --hard origin/$branch");

			}
      }

   }

   public function down() {
      // no way back here
   }
}
