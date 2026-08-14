var modalloading = false;

function buildQslString() {
	var qsl = '';
	if (document.getElementById('lotw')?.checked) qsl += 'L';
	if (document.getElementById('qsl')?.checked) qsl += 'Q';
	return qsl || 'LQ';
}

function displayRoverGridQsos(grid) {
	if (modalloading) return;
	modalloading = true;
	$.ajax({
		url: base_url + 'index.php/awards/qso_details_ajax',
		type: 'post',
		data: {
			Searchphrase: grid,
			Band: 'SAT',
			Sat: 'All',
			Orbit: 'All',
			Mode: 'All',
			Propagation: 'SAT',
			Type: 'VUCC',
			searchmode: 'activated',
			QSL: buildQslString()
		},
		success: function(html) {
			BootstrapDialog.show({
				title: lang_general_word_qso_data,
				cssClass: 'qso-dialog',
				size: BootstrapDialog.SIZE_WIDE,
				nl2br: false,
				message: html,
				onshown: function(dialog) {
					modalloading = false;
					$('[data-bs-toggle="tooltip"]').tooltip();
					$('.displaycontactstable').DataTable({
						pageLength: 25,
						responsive: false,
						ordering: false,
						scrollY: "550px",
						scrollCollapse: true,
						paging: false,
						scrollX: true,
						language: { url: getDataTablesLanguageUrl() },
						dom: 'Bfrtip',
						buttons: ['csv']
					});
				}
			});
		}
	});
}

// AMSAT Rover Award - Bonus points & progress bar

var amsatRoverConfig = {};

function calculateBonus() {
	let bonus = 0;
	const map = { bonus_social: 5, bonus_photos: 5, bonus_mm: 10, bonus_journal: 15 };
	for (const [id, pts] of Object.entries(map)) {
		if (document.getElementById(id)?.checked) bonus += pts;
	}
	return bonus;
}

function updateTotals() {
	const total = (parseInt(amsatRoverConfig.basePoints) || 0) + calculateBonus();
	const ok = total >= 25;

	const el = (id) => document.getElementById(id);

	if (el('bonusPoints')) el('bonusPoints').textContent = calculateBonus();

	if (el('totalPoints')) {
		el('totalPoints').textContent = total;
		el('totalPoints').className = 'display-4 ' + (ok ? 'text-success' : 'text-warning');
	}
	if (el('statusText')) {
		el('statusText').textContent = ok ? amsatRoverConfig.textApproved : amsatRoverConfig.textInProgress;
		el('statusText').className = 'h4 ' + (ok ? 'text-success' : 'text-warning');
	}
	if (el('progressBar')) {
		el('progressBar').style.width = Math.min((total / 25) * 100, 100) + '%';
		el('progressBar').textContent = total + ' / 25';
		el('progressBar').className = 'progress-bar bg-' + (ok ? 'success' : 'warning');
	}
}

function initAmsatRover() {
	const c = document.getElementById('amsatRover');
	if (!c) return;
	amsatRoverConfig = {
		basePoints: c.dataset.basePoints || 0,
		textApproved: c.dataset.textApproved || 'APPROVED',
		textInProgress: c.dataset.textInProgress || 'IN PROGRESS',
		textGenerateFirst: c.dataset.textGenerateFirst || '',
		exportTextUrl: c.dataset.exportTextUrl || '',
		exportCsvUrl: c.dataset.exportCsvUrl || ''
	};
	document.querySelectorAll('[id^="bonus_"]').forEach(cb => cb.addEventListener('change', updateTotals));
	updateTotals();
}

function generateTextExport() {
	const form = document.getElementById('amsatRoverForm');
	if (!form) return;
	fetch(amsatRoverConfig.exportTextUrl, { method: 'POST', body: new FormData(form) })
		.then(r => r.text())
		.then(data => { document.getElementById('exportText').value = data; })
		.catch(e => { console.error('Error:', e); });
}

function copyToClipboard() {
	const ta = document.getElementById('exportText');
	if (!ta.value) { alert(amsatRoverConfig.textGenerateFirst); return; }
	navigator.clipboard.writeText(ta.value);
}

function downloadCsv() {
	const form = document.getElementById('amsatRoverForm');
	if (!form) return;
	fetch(amsatRoverConfig.exportCsvUrl, { method: 'POST', body: new FormData(form) })
		.then(r => r.blob())
		.then(blob => {
			const url = URL.createObjectURL(blob);
			const a = Object.assign(document.createElement('a'), { href: url, download: 'amsat_rover_activations.csv' });
			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);
			URL.revokeObjectURL(url);
		})
		.catch(e => { console.error('Error:', e); });
}

document.addEventListener('DOMContentLoaded', initAmsatRover);
