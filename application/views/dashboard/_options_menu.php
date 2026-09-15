<?php
$__kpi_on  = !empty($dashboard_show_kpi_stats);
$__kpi_val = $__kpi_on ? '0' : '1';
$__dxp_on  = !empty($dashboard_show_dxpeditions);
$__dxp_val = $__dxp_on ? '0' : '1';
$__cont_on = !empty($dashboard_show_contests);
$__cont_val = $__cont_on ? '0' : '1';
$__solar_cur = ($dashboard_solar ?? 'N') === 'Y' ? 'bottom' : ($dashboard_solar ?? 'N');
if (!in_array($__solar_cur, ['top','bottom','N'], true)) { $__solar_cur = 'N'; }
$__solar_opts = [['v'=>'top','l'=>__("Top")],['v'=>'bottom','l'=>__("Bottom")],['v'=>'N','l'=>__("Off")]];
$__map_cur = $dashboard_map ?? 'Y';
if (!in_array($__map_cur, ['Y','map_at_left','map_at_right','N'], true)) { $__map_cur = 'Y'; }
$__map_opts = [['v'=>'Y','l'=>__("Map on top")],['v'=>'map_at_left','l'=>__("Map at left")],['v'=>'map_at_right','l'=>__("Map at right")],['v'=>'N','l'=>__("Off")]];
$__card_toggles = [
	['pref'=>'dxcc',     'on'=>!empty($dashboard_show_dxcc),     'label'=>__("DXCCs Breakdown")],
	['pref'=>'vucc',     'on'=>!empty($dashboard_show_vucc),     'label'=>__("VUCC-Grids")],
	['pref'=>'qslcards', 'on'=>!empty($dashboard_show_qslcards), 'label'=>__("QSL Cards")],
	['pref'=>'eqsl',     'on'=>!empty($dashboard_show_eqsl),     'label'=>__("eQSL Cards")],
	['pref'=>'qrz',      'on'=>!empty($dashboard_show_qrz),      'label'=>'QRZ.com'],
	['pref'=>'clublog',  'on'=>!empty($dashboard_show_clublog),  'label'=>__("Club Log")],
	['pref'=>'lotw',     'on'=>!empty($dashboard_show_lotw),     'label'=>__("LoTW")],
];
$__save_url = site_url('user_options/save_dashboard_pref');
?>
<div id="<?php echo $menu_id; ?>" class="dropdown-menu shadow" style="min-width:14rem;display:none;z-index:1080;">
	<h6 class="dropdown-header"><?= __("Dashboard options"); ?></h6>
	<button class="dropdown-item d-flex align-items-center gap-2" type="button" data-kpi-pref="<?php echo $__kpi_val; ?>">
		<i class="fas fa-check" style="width:1rem;<?= $__kpi_on ? '' : 'visibility:hidden;'; ?>"></i> <?= __("KPI statistics"); ?>
	</button>
	<button class="dropdown-item d-flex align-items-center gap-2" type="button" data-dxpeditions-pref="<?php echo $__dxp_val; ?>">
		<i class="fas fa-check" style="width:1rem;<?= $__dxp_on ? '' : 'visibility:hidden;'; ?>"></i> <?= __("Active Expeditions"); ?>
	</button>
	<button class="dropdown-item d-flex align-items-center gap-2" type="button" data-contests-pref="<?php echo $__cont_val; ?>">
		<i class="fas fa-check" style="width:1rem;<?= $__cont_on ? '' : 'visibility:hidden;'; ?>"></i> <?= __("Active Contests"); ?>
	</button>
	<div class="dropdown-divider"></div>
	<?php foreach ($__card_toggles as $__t): ?>
	<button class="dropdown-item d-flex align-items-center gap-2" type="button" data-<?php echo $__t['pref']; ?>-pref="<?php echo $__t['on'] ? '0' : '1'; ?>">
		<i class="fas fa-check" style="width:1rem;<?= $__t['on'] ? '' : 'visibility:hidden;'; ?>"></i> <?php echo $__t['label']; ?>
	</button>
	<?php endforeach; ?>
	<div class="dropdown-divider"></div>
	<h6 class="dropdown-header"><?= __("Solar data"); ?></h6>
	<?php foreach ($__solar_opts as $__o): ?>
	<button class="dropdown-item d-flex align-items-center gap-2" type="button" data-solar-pref="<?php echo $__o['v']; ?>">
		<i class="fas fa-check" style="width:1rem;<?= $__solar_cur === $__o['v'] ? '' : 'visibility:hidden;'; ?>"></i> <?php echo $__o['l']; ?>
	</button>
	<?php endforeach; ?>
	<div class="dropdown-divider"></div>
	<h6 class="dropdown-header"><?= __("Map"); ?></h6>
	<?php foreach ($__map_opts as $__o): ?>
	<button class="dropdown-item d-flex align-items-center gap-2" type="button" data-map-pref="<?php echo $__o['v']; ?>">
		<i class="fas fa-check" style="width:1rem;<?= $__map_cur === $__o['v'] ? '' : 'visibility:hidden;'; ?>"></i> <?php echo $__o['l']; ?>
	</button>
	<?php endforeach; ?>
</div>
<script>
(function(){
	var menu = document.getElementById('<?php echo $menu_id; ?>');
	var target = document.getElementById('<?php echo $target_id; ?>');
	if(!menu || !target) return;
	function show(x,y){
		if(menu.parentNode !== document.body){ document.body.appendChild(menu); }
		menu.style.position = 'absolute';
		menu.style.display = 'block';
		var r = menu.getBoundingClientRect();
		var vw = window.innerWidth, vh = window.innerHeight, sx = window.pageXOffset, sy = window.pageYOffset;
		if(x + r.width  > sx + vw - 8) x = sx + vw - r.width  - 8;
		if(y + r.height > sy + vh - 8) y = sy + vh - r.height - 8;
		menu.style.left = x + 'px';
		menu.style.top  = y + 'px';
	}
	function hide(){ menu.style.display='none'; }
	target.addEventListener('contextmenu', function(e){ e.preventDefault(); show(e.pageX, e.pageY); });
	document.addEventListener('click',      function(e){ if(!menu.contains(e.target)) hide(); });
	document.addEventListener('contextmenu',function(e){ if(!target.contains(e.target)) hide(); });
	function save(pref, value){
		fetch('<?php echo $__save_url; ?>', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ pref: pref, value: value })
		}).then(function(r){ if(r.ok){ location.reload(); } else { hide(); } }).catch(function(){ hide(); });
	}
	menu.querySelectorAll('[data-kpi-pref]').forEach(function(b){
		b.addEventListener('click', function(){ save('kpi', b.getAttribute('data-kpi-pref')); });
	});
	menu.querySelectorAll('[data-solar-pref]').forEach(function(b){
		b.addEventListener('click', function(){ save('solar', b.getAttribute('data-solar-pref')); });
	});
	menu.querySelectorAll('[data-map-pref]').forEach(function(b){
		b.addEventListener('click', function(){ save('map', b.getAttribute('data-map-pref')); });
	});
	menu.querySelectorAll('[data-dxpeditions-pref]').forEach(function(b){
		b.addEventListener('click', function(){ save('dxpeditions', b.getAttribute('data-dxpeditions-pref')); });
	});
	menu.querySelectorAll('[data-contests-pref]').forEach(function(b){
		b.addEventListener('click', function(){ save('contests', b.getAttribute('data-contests-pref')); });
	});
	['dxcc','vucc','qslcards','eqsl','qrz','clublog','lotw'].forEach(function(p){
		menu.querySelectorAll('[data-'+p+'-pref]').forEach(function(b){
			b.addEventListener('click', function(){ save(p, b.getAttribute('data-'+p+'-pref')); });
		});
	});
})();
</script>
