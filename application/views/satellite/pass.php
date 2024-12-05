<div class="container container-fluid">
<h2><?= __("Satellite passes"); ?></h2>
<div class="card">
	<div class="card-body">
		<form class="d-flex align-items-center">
		<div class="row">
			<div class="mb-3 w-auto">
				<label class="my-1 me-sm-2 w-auto" id="satslabel" for="satslist"><?= __("Min. Satellite Elevation"); ?></label>
				<input class="my-1 me-sm-2 w-auto form-control" id="minelevation" type="number" min="0" max="90" name="minelevation" value="0" />
			</div>
			<div class="mb-3 w-auto">
				<label class="my-1 me-sm-2 w-auto" for="minazimuth"><?= __("Min. Azimuth"); ?></label>
                <select class="my-1 me-sm-2 w-auto form-select" id="minazimuth" name="minazimuth">
				<?php for ($i = 0; $i <= 350; $i += 10): ?>
					<option value="<?= $i ?>" <?= $i === 0 ? 'selected' : '' ?>><?= $i ?> &deg;</option>
				<?php endfor; ?>
			</select>
			</div>
			<div class="mb-3 w-auto">
				<label class="my-1 me-sm-2 w-auto" for="maxazimuth"><?= __("Max. Azimuth"); ?></label>
				<select class="my-1 me-sm-2 w-auto form-select" id="maxazimuth" name="maxazimuth">
					<?php for ($i = 10; $i <= 360; $i += 10): ?>
						<option value="<?= $i ?>" <?= $i === 360 ? 'selected' : '' ?>><?= $i ?> &deg;</option>
					<?php endfor; ?>
				</select>
			</div>
			<div class="mb-3 w-auto">
					<label class="my-1 me-sm-2 w-auto" for="yourgrid"><?= __("Gridsquare"); ?></label>
                    <input class="my-1 me-sm-2 w-auto form-control"  id="yourgrid" type="text" name="gridsquare" value="<?php echo $activegrid; ?>"/>
			</div>
			<div class="mb-3 w-auto">
					<label class="my-1 me-sm-2 w-auto" for="altitude"><?= __("Altitude (meters)"); ?></label>
                    <input class="my-1 me-sm-2 w-auto form-control"  id="altitude" type="number" name="altitude" value="0" />
			</div>
			<div class="mb-3 w-auto">
					<label class="my-1 me-sm-2 w-auto" for="timezone"><?= __("Timezone"); ?></label>
					<select class="my-1 me-sm-2 w-auto form-select" id="timezone" name="timezone">
                            <option>Africa/Abidjan</option>
                            <option>Africa/Accra</option>
                            <option>Africa/Addis_Ababa</option>
                            <option>Africa/Algiers</option>
                            <option>Africa/Asmara</option>
                            <option>Africa/Bamako</option>
                            <option>Africa/Bangui</option>
                            <option>Africa/Banjul</option>
                            <option>Africa/Bissau</option>
                            <option>Africa/Blantyre</option>
                            <option>Africa/Brazzaville</option>
                            <option>Africa/Bujumbura</option>
                            <option>Africa/Cairo</option>
                            <option>Africa/Casablanca</option>
                            <option>Africa/Ceuta</option>
                            <option>Africa/Conakry</option>
                            <option>Africa/Dakar</option>
                            <option>Africa/Dar_es_Salaam</option>
                            <option>Africa/Djibouti</option>
                            <option>Africa/Douala</option>
                            <option>Africa/El_Aaiun</option>
                            <option>Africa/Freetown</option>
                            <option>Africa/Gaborone</option>
                            <option>Africa/Harare</option>
                            <option>Africa/Johannesburg</option>
                            <option>Africa/Juba</option>
                            <option>Africa/Kampala</option>
                            <option>Africa/Khartoum</option>
                            <option>Africa/Kigali</option>
                            <option>Africa/Kinshasa</option>
                            <option>Africa/Lagos</option>
                            <option>Africa/Libreville</option>
                            <option>Africa/Lome</option>
                            <option>Africa/Luanda</option>
                            <option>Africa/Lubumbashi</option>
                            <option>Africa/Lusaka</option>
                            <option>Africa/Malabo</option>
                            <option>Africa/Maputo</option>
                            <option>Africa/Maseru</option>
                            <option>Africa/Mbabane</option>
                            <option>Africa/Mogadishu</option>
                            <option>Africa/Monrovia</option>
                            <option>Africa/Nairobi</option>
                            <option>Africa/Ndjamena</option>
                            <option>Africa/Niamey</option>
                            <option>Africa/Nouakchott</option>
                            <option>Africa/Ouagadougou</option>
                            <option>Africa/Porto-Novo</option>
                            <option>Africa/Sao_Tome</option>
                            <option>Africa/Tripoli</option>
                            <option>Africa/Tunis</option>
                            <option>Africa/Windhoek</option>
                            <option>America/Adak</option>
                            <option>America/Anchorage</option>
                            <option>America/Anguilla</option>
                            <option>America/Antigua</option>
                            <option>America/Araguaina</option>
                            <option>America/Argentina/Buenos_Aires</option>
                            <option>America/Argentina/Catamarca</option>
                            <option>America/Argentina/Cordoba</option>
                            <option>America/Argentina/Jujuy</option>
                            <option>America/Argentina/La_Rioja</option>
                            <option>America/Argentina/Mendoza</option>
                            <option>America/Argentina/Rio_Gallegos</option>
                            <option>America/Argentina/Salta</option>
                            <option>America/Argentina/San_Juan</option>
                            <option>America/Argentina/San_Luis</option>
                            <option>America/Argentina/Tucuman</option>
                            <option>America/Argentina/Ushuaia</option>
                            <option>America/Aruba</option>
                            <option>America/Asuncion</option>
                            <option>America/Atikokan</option>
                            <option>America/Bahia</option>
                            <option>America/Bahia_Banderas</option>
                            <option>America/Barbados</option>
                            <option>America/Belem</option>
                            <option>America/Belize</option>
                            <option>America/Blanc-Sablon</option>
                            <option>America/Boa_Vista</option>
                            <option>America/Bogota</option>
                            <option>America/Boise</option>
                            <option>America/Cambridge_Bay</option>
                            <option>America/Campo_Grande</option>
                            <option>America/Cancun</option>
                            <option>America/Caracas</option>
                            <option>America/Cayenne</option>
                            <option>America/Cayman</option>
                            <option>America/Chicago</option>
                            <option>America/Chihuahua</option>
                            <option>America/Ciudad_Juarez</option>
                            <option>America/Costa_Rica</option>
                            <option>America/Creston</option>
                            <option>America/Cuiaba</option>
                            <option>America/Curacao</option>
                            <option>America/Danmarkshavn</option>
                            <option>America/Dawson</option>
                            <option>America/Dawson_Creek</option>
                            <option>America/Denver</option>
                            <option>America/Detroit</option>
                            <option>America/Dominica</option>
                            <option>America/Edmonton</option>
                            <option>America/Eirunepe</option>
                            <option>America/El_Salvador</option>
                            <option>America/Fort_Nelson</option>
                            <option>America/Fortaleza</option>
                            <option>America/Glace_Bay</option>
                            <option>America/Goose_Bay</option>
                            <option>America/Grand_Turk</option>
                            <option>America/Grenada</option>
                            <option>America/Guadeloupe</option>
                            <option>America/Guatemala</option>
                            <option>America/Guayaquil</option>
                            <option>America/Guyana</option>
                            <option>America/Halifax</option>
                            <option>America/Havana</option>
                            <option>America/Hermosillo</option>
                            <option>America/Indiana/Indianapolis</option>
                            <option>America/Indiana/Knox</option>
                            <option>America/Indiana/Marengo</option>
                            <option>America/Indiana/Petersburg</option>
                            <option>America/Indiana/Tell_City</option>
                            <option>America/Indiana/Vevay</option>
                            <option>America/Indiana/Vincennes</option>
                            <option>America/Indiana/Winamac</option>
                            <option>America/Inuvik</option>
                            <option>America/Iqaluit</option>
                            <option>America/Jamaica</option>
                            <option>America/Juneau</option>
                            <option>America/Kentucky/Louisville</option>
                            <option>America/Kentucky/Monticello</option>
                            <option>America/Kralendijk</option>
                            <option>America/La_Paz</option>
                            <option>America/Lima</option>
                            <option>America/Los_Angeles</option>
                            <option>America/Lower_Princes</option>
                            <option>America/Maceio</option>
                            <option>America/Managua</option>
                            <option>America/Manaus</option>
                            <option>America/Marigot</option>
                            <option>America/Martinique</option>
                            <option>America/Matamoros</option>
                            <option>America/Mazatlan</option>
                            <option>America/Menominee</option>
                            <option>America/Merida</option>
                            <option>America/Metlakatla</option>
                            <option>America/Mexico_City</option>
                            <option>America/Miquelon</option>
                            <option>America/Moncton</option>
                            <option>America/Monterrey</option>
                            <option>America/Montevideo</option>
                            <option>America/Montserrat</option>
                            <option>America/Nassau</option>
                            <option>America/New_York</option>
                            <option>America/Nome</option>
                            <option>America/Noronha</option>
                            <option>America/North_Dakota/Beulah</option>
                            <option>America/North_Dakota/Center</option>
                            <option>America/North_Dakota/New_Salem</option>
                            <option>America/Nuuk</option>
                            <option>America/Ojinaga</option>
                            <option>America/Panama</option>
                            <option>America/Paramaribo</option>
                            <option>America/Phoenix</option>
                            <option>America/Port-au-Prince</option>
                            <option>America/Port_of_Spain</option>
                            <option>America/Porto_Velho</option>
                            <option>America/Puerto_Rico</option>
                            <option>America/Punta_Arenas</option>
                            <option>America/Rankin_Inlet</option>
                            <option>America/Recife</option>
                            <option>America/Regina</option>
                            <option>America/Resolute</option>
                            <option>America/Rio_Branco</option>
                            <option>America/Santarem</option>
                            <option>America/Santiago</option>
                            <option>America/Santo_Domingo</option>
                            <option>America/Sao_Paulo</option>
                            <option>America/Scoresbysund</option>
                            <option>America/Sitka</option>
                            <option>America/St_Barthelemy</option>
                            <option>America/St_Johns</option>
                            <option>America/St_Kitts</option>
                            <option>America/St_Lucia</option>
                            <option>America/St_Thomas</option>
                            <option>America/St_Vincent</option>
                            <option>America/Swift_Current</option>
                            <option>America/Tegucigalpa</option>
                            <option>America/Thule</option>
                            <option>America/Tijuana</option>
                            <option>America/Toronto</option>
                            <option>America/Tortola</option>
                            <option>America/Vancouver</option>
                            <option>America/Whitehorse</option>
                            <option>America/Winnipeg</option>
                            <option>America/Yakutat</option>
                            <option>Antarctica/Casey</option>
                            <option>Antarctica/Davis</option>
                            <option>Antarctica/DumontDUrville</option>
                            <option>Antarctica/Macquarie</option>
                            <option>Antarctica/Mawson</option>
                            <option>Antarctica/McMurdo</option>
                            <option>Antarctica/Palmer</option>
                            <option>Antarctica/Rothera</option>
                            <option>Antarctica/Syowa</option>
                            <option>Antarctica/Troll</option>
                            <option>Antarctica/Vostok</option>
                            <option>Arctic/Longyearbyen</option>
                            <option>Asia/Aden</option>
                            <option>Asia/Almaty</option>
                            <option>Asia/Amman</option>
                            <option>Asia/Anadyr</option>
                            <option>Asia/Aqtau</option>
                            <option>Asia/Aqtobe</option>
                            <option>Asia/Ashgabat</option>
                            <option>Asia/Atyrau</option>
                            <option>Asia/Baghdad</option>
                            <option>Asia/Bahrain</option>
                            <option>Asia/Baku</option>
                            <option>Asia/Bangkok</option>
                            <option>Asia/Barnaul</option>
                            <option>Asia/Beirut</option>
                            <option>Asia/Bishkek</option>
                            <option>Asia/Brunei</option>
                            <option>Asia/Chita</option>
                            <option>Asia/Choibalsan</option>
                            <option>Asia/Colombo</option>
                            <option>Asia/Damascus</option>
                            <option>Asia/Dhaka</option>
                            <option>Asia/Dili</option>
                            <option>Asia/Dubai</option>
                            <option>Asia/Dushanbe</option>
                            <option>Asia/Famagusta</option>
                            <option>Asia/Gaza</option>
                            <option>Asia/Hebron</option>
                            <option>Asia/Ho_Chi_Minh</option>
                            <option>Asia/Hong_Kong</option>
                            <option>Asia/Hovd</option>
                            <option>Asia/Irkutsk</option>
                            <option>Asia/Jakarta</option>
                            <option>Asia/Jayapura</option>
                            <option>Asia/Jerusalem</option>
                            <option>Asia/Kabul</option>
                            <option>Asia/Kamchatka</option>
                            <option>Asia/Karachi</option>
                            <option>Asia/Kathmandu</option>
                            <option>Asia/Khandyga</option>
                            <option>Asia/Kolkata</option>
                            <option>Asia/Krasnoyarsk</option>
                            <option>Asia/Kuala_Lumpur</option>
                            <option>Asia/Kuching</option>
                            <option>Asia/Kuwait</option>
                            <option>Asia/Macau</option>
                            <option>Asia/Magadan</option>
                            <option>Asia/Makassar</option>
                            <option>Asia/Manila</option>
                            <option>Asia/Muscat</option>
                            <option>Asia/Nicosia</option>
                            <option>Asia/Novokuznetsk</option>
                            <option>Asia/Novosibirsk</option>
                            <option>Asia/Omsk</option>
                            <option>Asia/Oral</option>
                            <option>Asia/Phnom_Penh</option>
                            <option>Asia/Pontianak</option>
                            <option>Asia/Pyongyang</option>
                            <option>Asia/Qatar</option>
                            <option>Asia/Qostanay</option>
                            <option>Asia/Qyzylorda</option>
                            <option>Asia/Riyadh</option>
                            <option>Asia/Sakhalin</option>
                            <option>Asia/Samarkand</option>
                            <option>Asia/Seoul</option>
                            <option>Asia/Shanghai</option>
                            <option>Asia/Singapore</option>
                            <option>Asia/Srednekolymsk</option>
                            <option>Asia/Taipei</option>
                            <option>Asia/Tashkent</option>
                            <option>Asia/Tbilisi</option>
                            <option>Asia/Tehran</option>
                            <option>Asia/Thimphu</option>
                            <option>Asia/Tokyo</option>
                            <option>Asia/Tomsk</option>
                            <option>Asia/Ulaanbaatar</option>
                            <option>Asia/Urumqi</option>
                            <option>Asia/Ust-Nera</option>
                            <option>Asia/Vientiane</option>
                            <option>Asia/Vladivostok</option>
                            <option>Asia/Yakutsk</option>
                            <option>Asia/Yangon</option>
                            <option>Asia/Yekaterinburg</option>
                            <option>Asia/Yerevan</option>
                            <option>Atlantic/Azores</option>
                            <option>Atlantic/Bermuda</option>
                            <option>Atlantic/Canary</option>
                            <option>Atlantic/Cape_Verde</option>
                            <option>Atlantic/Faroe</option>
                            <option>Atlantic/Madeira</option>
                            <option>Atlantic/Reykjavik</option>
                            <option>Atlantic/South_Georgia</option>
                            <option>Atlantic/St_Helena</option>
                            <option>Atlantic/Stanley</option>
                            <option>Australia/Adelaide</option>
                            <option>Australia/Brisbane</option>
                            <option>Australia/Broken_Hill</option>
                            <option>Australia/Darwin</option>
                            <option>Australia/Eucla</option>
                            <option>Australia/Hobart</option>
                            <option>Australia/Lindeman</option>
                            <option>Australia/Lord_Howe</option>
                            <option>Australia/Melbourne</option>
                            <option>Australia/Perth</option>
                            <option>Australia/Sydney</option>
                            <option>Europe/Amsterdam</option>
                            <option>Europe/Andorra</option>
                            <option>Europe/Astrakhan</option>
                            <option>Europe/Athens</option>
                            <option>Europe/Belgrade</option>
                            <option>Europe/Berlin</option>
                            <option>Europe/Bratislava</option>
                            <option>Europe/Brussels</option>
                            <option>Europe/Bucharest</option>
                            <option>Europe/Budapest</option>
                            <option>Europe/Busingen</option>
                            <option>Europe/Chisinau</option>
                            <option>Europe/Copenhagen</option>
                            <option>Europe/Dublin</option>
                            <option>Europe/Gibraltar</option>
                            <option>Europe/Guernsey</option>
                            <option>Europe/Helsinki</option>
                            <option>Europe/Isle_of_Man</option>
                            <option>Europe/Istanbul</option>
                            <option>Europe/Jersey</option>
                            <option>Europe/Kaliningrad</option>
                            <option>Europe/Kirov</option>
                            <option>Europe/Kyiv</option>
                            <option>Europe/Lisbon</option>
                            <option>Europe/Ljubljana</option>
                            <option>Europe/London</option>
                            <option>Europe/Luxembourg</option>
                            <option>Europe/Madrid</option>
                            <option>Europe/Malta</option>
                            <option>Europe/Mariehamn</option>
                            <option>Europe/Minsk</option>
                            <option>Europe/Monaco</option>
                            <option>Europe/Moscow</option>
                            <option>Europe/Oslo</option>
                            <option>Europe/Paris</option>
                            <option>Europe/Podgorica</option>
                            <option>Europe/Prague</option>
                            <option>Europe/Riga</option>
                            <option>Europe/Rome</option>
                            <option>Europe/Samara</option>
                            <option>Europe/San_Marino</option>
                            <option>Europe/Sarajevo</option>
                            <option>Europe/Saratov</option>
                            <option>Europe/Simferopol</option>
                            <option>Europe/Skopje</option>
                            <option>Europe/Sofia</option>
                            <option>Europe/Stockholm</option>
                            <option>Europe/Tallinn</option>
                            <option>Europe/Tirane</option>
                            <option>Europe/Ulyanovsk</option>
                            <option>Europe/Vaduz</option>
                            <option>Europe/Vatican</option>
                            <option>Europe/Vienna</option>
                            <option>Europe/Vilnius</option>
                            <option>Europe/Volgograd</option>
                            <option>Europe/Warsaw</option>
                            <option>Europe/Zagreb</option>
                            <option>Europe/Zurich</option>
                            <option>Indian/Antananarivo</option>
                            <option>Indian/Chagos</option>
                            <option>Indian/Christmas</option>
                            <option>Indian/Cocos</option>
                            <option>Indian/Comoro</option>
                            <option>Indian/Kerguelen</option>
                            <option>Indian/Mahe</option>
                            <option>Indian/Maldives</option>
                            <option>Indian/Mauritius</option>
                            <option>Indian/Mayotte</option>
                            <option>Indian/Reunion</option>
                            <option>Pacific/Apia</option>
                            <option>Pacific/Auckland</option>
                            <option>Pacific/Bougainville</option>
                            <option>Pacific/Chatham</option>
                            <option>Pacific/Chuuk</option>
                            <option>Pacific/Easter</option>
                            <option>Pacific/Efate</option>
                            <option>Pacific/Fakaofo</option>
                            <option>Pacific/Fiji</option>
                            <option>Pacific/Funafuti</option>
                            <option>Pacific/Galapagos</option>
                            <option>Pacific/Gambier</option>
                            <option>Pacific/Guadalcanal</option>
                            <option>Pacific/Guam</option>
                            <option>Pacific/Honolulu</option>
                            <option>Pacific/Kanton</option>
                            <option>Pacific/Kiritimati</option>
                            <option>Pacific/Kosrae</option>
                            <option>Pacific/Kwajalein</option>
                            <option>Pacific/Majuro</option>
                            <option>Pacific/Marquesas</option>
                            <option>Pacific/Midway</option>
                            <option>Pacific/Nauru</option>
                            <option>Pacific/Niue</option>
                            <option>Pacific/Norfolk</option>
                            <option>Pacific/Noumea</option>
                            <option>Pacific/Pago_Pago</option>
                            <option>Pacific/Palau</option>
                            <option>Pacific/Pitcairn</option>
                            <option>Pacific/Pohnpei</option>
                            <option>Pacific/Port_Moresby</option>
                            <option>Pacific/Rarotonga</option>
                            <option>Pacific/Saipan</option>
                            <option>Pacific/Tahiti</option>
                            <option>Pacific/Tarawa</option>
                            <option>Pacific/Tongatapu</option>
                            <option>Pacific/Wake</option>
                            <option>Pacific/Wallis</option>
                            <option selected>UTC</option>
                            <option ">Etc/GMT-12</option><option ">Etc/GMT-11</option>
                            <option ">Etc/GMT-10</option><option ">Etc/GMT-9</option>
                            <option ">Etc/GMT-8</option><option ">Etc/GMT-7</option>
                            <option ">Etc/GMT-6</option><option ">Etc/GMT-5</option>
                            <option ">Etc/GMT-4</option><option ">Etc/GMT-3</option>
                            <option ">Etc/GMT-2</option><option ">Etc/GMT-1</option>
                            <option ">Etc/GMT+0</option><option ">Etc/GMT+1</option>
                            <option ">Etc/GMT+2</option><option ">Etc/GMT+3</option>
                            <option ">Etc/GMT+4</option><option ">Etc/GMT+5</option>
                            <option ">Etc/GMT+6</option><option ">Etc/GMT+7</option>
                            <option ">Etc/GMT+8</option><option ">Etc/GMT+9</option>
                            <option ">Etc/GMT+10</option><option ">Etc/GMT+11</option>
                            <option ">Etc/GMT+12</option>
					</select>
					</div>
					<!-- <div class="mb-3 w-auto">
						<label class="my-1 me-sm-2 w-auto" for="date"><?= __("Date"); ?></label>
						<select class="my-1 me-sm-2 w-auto form-select" id="date" name="start">
							<option selected value="0"><?= __("Today"); ?></option>
						</select>
					</div> -->
					<div class="mb-3 w-auto">
						<label class="my-1 me-sm-2 w-auto" for="mintime"><?= __("Min. time"); ?></label>
						<select class="my-1 me-sm-2 w-auto form-select" id="mintime" name="mintime">
                        <?php for ($i = 0; $i <= 24; $i += 1): ?>
                            <option value="<?= $i ?>" <?= $i === 8 ? 'selected' : '' ?>><?= $i ?>:00</option>
                        <?php endfor; ?>
						</select>
				</div>
				<div class="mb-3 w-auto">
						<label class="my-1 me-sm-2 w-auto" for="maxtime"><?= __("Max. time"); ?></label>
						<select class="my-1 me-sm-2 w-auto form-select" id="maxtime" name="maxtime">
                        <?php for ($i = 0; $i <= 24; $i += 1): ?>
                            <option value="<?= $i ?>" <?= $i === 22 ? 'selected' : '' ?>><?= $i ?>:00</option>
                        <?php endfor; ?>
						</select>
                </div>
                <div class="mb-3 w-auto">
					<label class="my-1 me-sm-2 w-auto" id="satslabel" for="satlist"><?= __("Satellites"); ?></label>
					<select class="my-1 me-sm-2 w-auto form-select"  id="satlist">
						<?php foreach($satellites as $sat) {
							echo '<option value="' . $sat->satname . '"' . '>' . $sat->satname . '</option>'."\n";
						} ?>
					</select>
				</div>
        </form>
				</div>
		<button id="plot" type="button" name="searchpass" class="btn-sm btn btn-primary me-1 ld-ext-right ld-ext-right-plot" onclick="searchpasses()"><?= __("Load predictions"); ?><div class="ld ld-ring ld-spin"></div></button>
	</div>
    <div id="resultpasses">

    </div>
</div>
</div>
</div>
