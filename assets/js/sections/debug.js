function reassign(call, target_profile_id) {
	let qsoids = [];
	let elements = document.getElementsByName("cBox[]");
	elements.forEach((item) => {
		if (item.checked) {
			qsoids.push(item.value);
		}
	});
	$.ajax({
		url: base_url + "index.php/debug/reassign",
		type: "post",
		data: { call: call, station_id: target_profile_id, qsoids: qsoids },
		success: function (resu) {
			if (resu.status) {
				location.reload();
			}
		},
	});
}

function toggleAll(source) {
	if (source.checked) {
		let elements = document.getElementsByName("cBox[]");
		elements.forEach((item) => {
			item.checked = true;
		});
		source.checked = true;
	}
	if (!source.checked) {
		let elements = document.getElementsByName("cBox[]");
		elements.forEach((item) => {
			item.checked = false;
		});
		source.checked = false;
	}
}

function updateCallsign(item) {
	let text = item.options[item.selectedIndex].text;
	let call = text.substr(
		text.lastIndexOf("(") + 1,
		text.lastIndexOf(")") - text.lastIndexOf("(") - 1
	);
	document.getElementById("station_call").innerHTML = call;
}

function version_check(callback) {
	var latest_tag; 
	$('#version_check_button').prop("disabled", true).addClass("running");
	$.ajax({
		url: base_url + 'index.php/debug/wavelog_version',	// get latest commit hash from current version
		success: function(last_local_commit) {
			$.ajax({
				url: base_url + 'index.php/debug/wavelog_fetch',	// Fetch Repo (don't merge!)-Head and get latest hash from there
				type: 'GET',
				success: function(data) {
					// Extract the latest tag
					last_repo_commit=data.latest_commit_hash;

					// Compare database version with the latest tag
					var is_latest_version = (last_local_commit === last_repo_commit);

					// Call the callback function with the result
					callback(is_latest_version, last_repo_commit); 
					$('#version_check_button').prop("disabled", false).removeClass("running");
				},
				error: function(xhr, status, error) {
					console.error('ERROR fetching Git tags:', error);
					callback(null, null);
					$('#version_check_button').prop("disabled", false).removeClass("running");
				}
			});
		},
		error: function(xhr, status, error) {
			console.error('ERROR fetching database version:', error);
			callback(null, null);
		}
	});
}

function update_version_check(local_branch) {
	version_check(function(is_latest_version, last_repo_commit) {
		$('#version_check_result').removeClass('alert alert-success alert-warning alert-danger').text('');
		$('#version_update_button').hide();
		var timestamp = Date.now();

		if (is_latest_version !== null && last_repo_commit != '') {
			if (is_latest_version) {
				$('#version_check_result').addClass('alert alert-success');
				$('#version_check_result').html(lang_git_is_uptodate);
			} else {
				$('#version_check_result').addClass('alert alert-warning');
				$('#version_check_result').html(lang_git_new_update_available.replace("%s", last_repo_commit));
				$('#version_update_button').show();
			}
		} else {
			$('#version_check_result').addClass('alert alert-warning');
			$('#version_check_result').html(lang_git_remote_doesnt_know_branch);
		}

		$('#last_version_check').html(lang_git_last_version_check.replace("%s", new Date(timestamp).toUTCString()));
	});
}

$(document).ready(function () {
	$('#clear_cache_button').on('click', function () {
		if (!confirm(decodeHtml(lang_cache_clean_confirm))) {
			return;
		}

		$.ajax({
			url: base_url + 'index.php/debug/clear_cache',
			type: 'post',
			success: function (resu) {
				if (resu && resu.status) {
					location.reload();
				} else {
					alert(decodeHtml(lang_cache_clear_failure));
				}
			},
			error: function () {
				alert(decodeHtml(lang_cache_clear_failure));
			}
		});
	});
});

$(document).ready(function () {
	var cfg = window.workerStatusLive;
	if (!cfg) { return; }

	var cluster = cfg.nodesTotal > 1;
	var uptimeBase = null, uptimeAt = 0, tickT = null;

	function val(x) { return x != null ? x : '—'; }

	function fmt(s) {
		s = Math.max(0, Math.floor(s));
		var d = Math.floor(s / 86400); s %= 86400;
		var h = Math.floor(s / 3600);  s %= 3600;
		var m = Math.floor(s / 60);    s %= 60;
		return (d ? d + 'd ' : '') + ((d || h) ? h + 'h ' : '') + m + 'm ' + s + 's';
	}
	function tick() { $('#ws-uptime').text(fmt(uptimeBase + (Date.now() - uptimeAt) / 1000)); }
	function startTick() { if (!tickT) { tickT = setInterval(tick, 250); } } // sub-second so no displayed second is skipped
	function stopTick()  { if (tickT) { clearInterval(tickT); tickT = null; } uptimeBase = null; }

	function showMessage(html) { stopTick(); $('#worker-status').hide(); $('#worker-status-container').html(html); }
	function showTable()       { $('#worker-status-container').empty(); $('#worker-status').show(); }

	function setBadge(degraded) {
		$('#ws-state').text(degraded ? cfg.msg.degraded : cfg.msg.online);
		$('#ws-badge').removeClass('text-bg-success text-bg-warning').addClass(degraded ? 'text-bg-warning' : 'text-bg-success');
	}

	// Live stats from a WS status frame. Owns the counter cells + dot + uptime.
	function renderStats(p) {
		showTable();
		$('#ws-live-dot').show();
		if (!cluster) { setBadge(false); } // single instance: always "Online"; cluster badge is owned by the fan-out
		$('#ws-url').text(WavelogWorker.url);
		$('#ws-version').text(val(p.version));
		$('#ws-topics').text(val(p.active_topics));
		$('#ws-clients').text(val(p.connected_clients));
		if (typeof p.uptime_seconds === 'number') {
			uptimeBase = p.uptime_seconds; uptimeAt = Date.now();
			startTick(); tick();
		} else {
			stopTick(); $('#ws-uptime').text(val(p.uptime));
		}
	}

	// Cluster node reachability from the backend fan-out. Owns #ws-cluster + badge.
	function renderCluster(data) {
		if (!data || !data.success || data.disabled) { return; }
		var workers = data.workers || [];
		if (!workers.length) { return; }
		var online = workers.filter(function (w) { return w.alive; }).length;
		$('#ws-cluster-head, #ws-cluster').show();
		$('#ws-cluster').text(online + '/' + workers.length);
		setBadge(online < workers.length);
	}
	function loadCluster() {
		fetch(cfg.snapshotUrl).then(function (r) { return r.json(); }).then(renderCluster).catch(function () {});
	}

	// No worker, or no browser WS URL / token → nothing live to show.
	if (!cfg.enabled || !cfg.token || !(window.WavelogWorker && WavelogWorker.isAvailable())) {
		showMessage(cfg.msg.disabled);
		return;
	}

	// Cluster node x/y comes from the backend (9001 reachability), polled.
	if (cluster) { loadCluster(); setInterval(loadCluster, 10000); }

	// WS status unavailable (never connected, or connected but silent) → ask the
	// backend (9001): worker reachable = too old for the status feed → update hint;
	// otherwise truly unreachable.
	function fallback() {
		fetch(cfg.snapshotUrl).then(function (r) { return r.json(); }).then(function (data) {
			if (data && data.disabled) { showMessage(cfg.msg.disabled); return; }
			var alive = ((data && data.workers) || []).some(function (w) { return w.alive; });
			showMessage(alive ? cfg.msg.update : cfg.msg.unreachable);
		}).catch(function () { showMessage(cfg.msg.unreachable); });
	}

	// Live stats over the generic worker WS client (handles auth + reconnect).
	var pollT, statusDeadline;
	var conn = WavelogWorker.subscribe({
		topic: cfg.topic,
		token: cfg.token,
		onOpen: function () {
			conn.send({ type: 'status' });
			clearInterval(pollT);
			pollT = setInterval(function () { conn.send({ type: 'status' }); }, 4000);
			// Connected but no status frame in time → worker too old for the status feed.
			statusDeadline = setTimeout(function () { clearInterval(pollT); conn.close(); fallback(); }, 3000);
		},
		onMessage: function (frame) {
			if (frame.type === 'status' && frame.payload) { clearTimeout(statusDeadline); renderStats(frame.payload); }
		},
		onClose: function () { clearInterval(pollT); clearTimeout(statusDeadline); stopTick(); }, // freeze; dot stays while worker.js reconnects
		onFailed: function () { clearInterval(pollT); fallback(); }
	});
});
