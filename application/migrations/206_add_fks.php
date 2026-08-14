<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_fks extends CI_Migration {

	public function up() {
			$this->dbtry("delete from api where not exists (select 1 from users where user_id = api.user_id);");
			$this->dbtry("delete from bandxuser where not exists (select 1 from users where user_id = bandxuser.userid);");
			$this->dbtry("delete from station_logbooks_relationship where not exists (select 1 from station_logbooks where station_logbook_id = station_logbooks_relationship.station_logbook_id);");
			$this->dbtry("delete from station_logbooks_relationship where not exists (select 1 from station_profile where station_id = station_logbooks_relationship.station_location_id);");
			$this->dbtry("delete from eQSL_images where not exists (select 1 from ".$this->config->item('table_name')." log where log.COL_PRIMARY_KEY=eQSL_images.qso_id);");
			$this->dbtry("delete from qsl_images where not exists (select 1 from ".$this->config->item('table_name')." log where log.COL_PRIMARY_KEY=qsl_images.qsoid);");
			$this->dbtry("delete from station_logbooks where not exists (select 1 from users where user_id = station_logbooks.user_id);");
			$this->dbtry("delete from oqrs where not exists (select 1 from station_profile where station_id = oqrs.station_id);");
			$this->dbtry("delete from lotw_certs where not exists (select 1 from users where user_id = lotw_certs.user_id);");
			$this->dbtry("delete from label_types where not exists (select 1 from users where user_id = label_types.user_id);");
			$this->dbtry("delete from notes where not exists (select 1 from users where user_id = notes.user_id);");
			$this->dbtry("delete from station_profile where not exists (select 1 from users where user_id = station_profile.user_id);");
			$this->dbtry("delete from cat where not exists (select 1 from users where user_id = cat.user_id);");
			$this->dbtry("delete from ".$this->config->item('table_name')." where not exists (select 1 from station_profile where station_id = ".$this->config->item('table_name').".station_id);");

			$this->dbtry("ALTER TABLE api ADD CONSTRAINT api_users_FK FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE RESTRICT;");
			$this->dbtry("ALTER TABLE bandxuser ADD CONSTRAINT bandxuser_users_FK FOREIGN KEY (userid) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE RESTRICT;");
			$this->dbtry("ALTER TABLE station_logbooks_relationship ADD CONSTRAINT station_logbooks_relationship_station_profile_FK FOREIGN KEY (station_location_id) REFERENCES station_profile (station_id) ON DELETE CASCADE ON UPDATE RESTRICT;");
			$this->dbtry("ALTER TABLE eQSL_images ADD CONSTRAINT eQSL_images_TABLE_HRD_CONTACTS_V01_FK FOREIGN KEY (qso_id) REFERENCES ".$this->config->item('table_name')." (COL_PRIMARY_KEY) ON DELETE CASCADE ON UPDATE RESTRICT;");
			$this->dbtry("ALTER TABLE qsl_images ADD CONSTRAINT qsl_images_TABLE_HRD_CONTACTS_V01_FK FOREIGN KEY (qsoid) REFERENCES ".$this->config->item('table_name')." (COL_PRIMARY_KEY) ON DELETE CASCADE ON UPDATE RESTRICT;");
			$this->dbtry("ALTER TABLE station_logbooks ADD CONSTRAINT station_logbooks_users_FK FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE RESTRICT;");
			$this->dbtry("ALTER TABLE oqrs ADD CONSTRAINT oqrs_station_profile_FK FOREIGN KEY (station_id) REFERENCES station_profile (station_id) ON DELETE CASCADE ON UPDATE RESTRICT;");
			$this->dbtry("ALTER TABLE lotw_certs ADD CONSTRAINT lotw_certs_users_FK FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE RESTRICT;");
			$this->dbtry("ALTER TABLE label_types ADD CONSTRAINT label_types_users_FK FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE RESTRICT;");
			$this->dbtry("ALTER TABLE notes ADD CONSTRAINT notes_users_FK FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE RESTRICT;");
			$this->dbtry("ALTER TABLE station_profile ADD CONSTRAINT station_profile_users_FK FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE RESTRICT;");
			$this->dbtry("ALTER TABLE cat ADD CONSTRAINT cat_users_FK FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE RESTRICT;");
			$this->dbtry("ALTER TABLE ".$this->config->item('table_name')." ADD CONSTRAINT TABLE_HRD_CONTACTS_V01_station_profile_FK FOREIGN KEY (station_id) REFERENCES station_profile (station_id) ON DELETE CASCADE ON UPDATE RESTRICT;");
	}

	public function down(){
			$this->dbtry("alter table station_logbooks drop foreign key station_logbooks_users_FK;");
			$this->dbtry("alter table oqrs drop foreign key oqrs_station_profile_FK;");
			$this->dbtry("alter table api drop foreign key api_users_FK;");
			$this->dbtry("alter table station_logbooks_relationship drop foreign key station_logbooks_relationship_station_profile_FK;");
			$this->dbtry("alter table lotw_certs drop foreign key lotw_certs_users_FK;");
			$this->dbtry("alter table qsl_images drop foreign key qsl_images_TABLE_HRD_CONTACTS_V01_FK;");
			$this->dbtry("alter table eQSL_images drop foreign key eQSL_images_TABLE_HRD_CONTACTS_V01_FK;");
			$this->dbtry("alter table label_types drop foreign key label_types_users_FK;");
			$this->dbtry("alter table notes drop foreign key notes_users_FK;");
			$this->dbtry("alter table station_profile drop foreign key station_profile_users_FK;");
			$this->dbtry("alter table cat drop foreign key cat_users_FK;");
			$this->dbtry("alter table bandxuser drop foreign key bandxuser_users_FK;");
			$this->dbtry("alter table ".$this->config->item('table_name')." drop foreign key TABLE_HRD_CONTACTS_V01_station_profile_FK;");
	}	
	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Something gone wrong while altering FKs: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
