<?php
$__kpi_on  = !empty($dashboard_show_kpi_stats);
$__kpi_val = $__kpi_on ? '0' : '1';
$__solar_cur = ($dashboard_solar ?? 'N') === 'Y' ? 'bottom' : ($dashboard_solar ?? 'N');
if (!in_array($__solar_cur, ['top','bottom','N'], true)) { $__solar_cur = 'N'; }
$__solar_opts = [['v'=>'top','l'=>__("Top")],['v'=>'bottom','l'=>__("Bottom")],['v'=>'N','l'=>__("Off")]];
$__save_url = site_url('user_options/save_dashboard_pref');
?>
<div id="<?php echo $menu_id; ?>" class="dropdown-menu shadow" style="min-width:14rem;display:none;z-index:1080;">
	<h6 class="dropdown-header"><?= __("Dashboard options"); ?></h6>
	<button class="dropdown-item d-flex align-items-center gap-2" type="button" data-kpi-pref="<?php echo $__kpi_val; ?>">
		<i class="fas fa-check" style="width:1rem;<?= $__kpi_on ? '' : 'visibility:hidden;'; ?>"></i> <?= __("KPI statistics"); ?>
	</button>
	<div class="dropdown-divider"></div>
	<h6 class="dropdown-header"><?= __("Solar data"); ?></h6>
	<?php foreach ($__solar_opts as $__o): ?>
	<button class="dropdown-item d-flex align-items-center gap-2" type="button" data-solar-pref="<?php echo $__o['v']; ?>">
		<i class="fas fa-check" style="width:1rem;<?= $__solar_cur === $__o['v'] ? '' : 'visibility:hidden;'; ?>"></i> <?php echo $__o['l']; ?>
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
})();
</script>
