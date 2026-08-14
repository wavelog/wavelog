<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_process_deprecated extends CI_Migration
{
   public function up() {

      if (file_exists('.git')) {

         try {
            if (function_usable('exec')) {
               exec('git reset assets/json/dok.txt');
               exec('git reset assets/json/pota.txt');
               exec('git reset assets/resources/sota.txt');
               exec('git reset assets/resources/wwff.txt');

               exec('rm assets/json/dok.txt');
               exec('rm assets/json/pota.txt');
               exec('git restore assets/resources/sota.txt');
               exec('git restore assets/resources/wwff.txt');
            }
         } catch (\Throwable $th) {

				log_message("error","Mig: Error at Mig 210 for txt files. Run manually a git reset.");

			}
      } else {
			log_message("info","Mig 210: No .git found. Skipping processing of txt files.");
		}

   }

   public function down() {
      // no way back here
   }
}
