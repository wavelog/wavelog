<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
        Move the global OQRS options to user options
*/

class Migration_move_oqrs_global_to_user extends CI_Migration {

	public function up()
    {
        $this->db->query("insert into user_options (user_id, option_type, option_name, option_key, option_value)
			select users.user_id, 'oqrs', 'global_oqrs_text', 'text', options.option_value
			from users
			join options on options.option_name = 'global_oqrs_text'
			where not exists (select 1 from user_options where user_id = users.user_id and option_key = 'global_oqrs_text');");

		$this->db->query("insert into user_options (user_id, option_type, option_name, option_key, option_value)
			select users.user_id, 'oqrs', 'oqrs_grouped_search', 'boolean', options.option_value
			from users
			join options on options.option_name = 'groupedSearch'
			where not exists (select 1 from user_options where user_id = users.user_id and option_key = 'groupedSearch');");

		$this->db->query("insert into user_options (user_id, option_type, option_name, option_key, option_value)
			select users.user_id, 'oqrs', 'oqrs_grouped_search_show_station_name', 'boolean', options.option_value
			from users
			join options on options.option_name = 'groupedSearchShowStationName'
			where not exists (select 1 from user_options where user_id = users.user_id and option_key = 'groupedSearchShowStationName');");

		$this->db->query("delete from options where option_name = 'global_oqrs_text';");
		$this->db->query("delete from options where option_name = 'groupedSearch';");
		$this->db->query("delete from options where option_name = 'groupedSearchShowStationName';");
    }

    public function down()
    {
		// $this->db->query("insert into options (option_name, option_value)
		// 	select option_name, option_value from user_options where option_type = 'oqrs' and option_name = 'global_oqrs_text';");

		// $this->db->query("insert into options (option_name, option_value)
		// 	select 'groupedSearch', option_value from user_options where option_type = 'oqrs' and option_name = 'oqrs_grouped_search';");

		// $this->db->query("insert into options (option_name, option_value)
		// 	select 'groupedSearchShowStationName', option_value from user_options where option_type = 'oqrs' and option_name = 'oqrs_grouped_search_show_station_name';");

		$this->db->query("delete from user_options where option_type = 'oqrs' and option_name in ('global_oqrs_text', 'oqrs_grouped_search', 'oqrs_grouped_search_show_station_name');");
    }
}
