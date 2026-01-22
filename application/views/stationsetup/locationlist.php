<div class="container-fluid pt-3 ps-4 pe-4">
<h2><?= $page_title ?></h2>

        <div class="table-responsive mt-3">
			<table style="width:100%" class="table-sm table table-hover table-striped table-bordered table-condensed" id="qsoList">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Active</th>
                        <th>QSO Count</th>
                        <th>Name</th>
                        <th>Callsign</th>
                        <th>Grid</th>
                        <th>City</th>
                        <th>DXCC</th>
                        <th>IOTA</th>
                        <th>SOTA</th>
                        <th>Power</th>
                        <th>County</th>
                        <th>CQ Zone</th>
                        <th>ITU Zone</th>
                        <th>State</th>
                        <th>WWFF</th>
                        <th>POTA</th>
                        <th>SIG</th>
                        <th>SIG Info</th>
                        <th>eQSL Nickname</th>
                        <th>eQSL default QSLmsg</th>
                        <th>QRZ upload</th>
                        <th>OQRS enabled</th>
                        <th>OQRS Text</th>
                        <th>OQRS Email alert</th>
                        <th>QO-100 DX Club realtime upload</th>
                        <th>ClubLog realtime upload</th>
                        <th>ClubLog Ignore</th>
                        <th>HRDLog realtime upload</th>
                        <th>HRDLog username</th>
                        <th>Created</th>
                        <th>Last Modified</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($locations as $loc): 
				$qrzr=($loc->qrzrealtime ?? -1);
				if ($qrzr == -1) {
					$qrzr=__("No");
				} else if ($qrzr == 0) {
					$qrzr=__("Yes");
				} else {
					$qrzr=__("Realtime");
				}
			?>
                        <tr>
                            <td><?php echo $loc->station_id; ?></td>
                            <td><?php echo $loc->station_active ? 'Yes' : 'No' ?></td>
                            <td><?php echo $loc->qso_total; ?></td>
			<?php if (!($cd_p_level == 3) && !($cd_p_level == 6)) { // ClubOfficer (9) and normal User can click on a link, while ClubOfficer (ADIF) (3,6) can only see. ?>
                            <td><a href="<?php echo site_url('station/edit')."/".$loc->station_id; ?>"><?php echo $loc->station_profile_name; ?></a></td>
			<?php } else { ?>
                            <td><?php echo $loc->station_profile_name; ?></td>
			<?php } ?>
                            <td><?php echo $loc->station_callsign; ?></td>
                            <td><?php echo $loc->station_gridsquare; ?></td>
                            <td><?php echo $loc->station_city; ?></td>
                            <td><?php echo ucwords(strtolower($loc->dxccname), "- (/") . ($loc->dxccprefix ? ' (' . $loc->dxccprefix . ') ' : ''); ?>
                            <?php if (isset($loc->end)) {
                               echo '<span class="badge text-bg-danger">'.__("Deleted DXCC").'</span>';
                            } ?>
                            </td>
                            <td><?php echo $loc->station_iota; ?></td>
                            <td><?php echo $loc->station_sota; ?></td>
                            <td><?php echo $loc->station_power; ?></td>
                            <td><?php echo $loc->station_cnty; ?></td>
                            <td><?php echo $loc->station_cq; ?></td>
                            <td><?php echo $loc->station_itu; ?></td>
                            <td><?php echo $loc->state; ?></td>
                            <td><?php echo $loc->station_wwff; ?></td>
                            <td><?php echo $loc->station_pota; ?></td>
                            <td><?php echo $loc->station_sig; ?></td>
                            <td><?php echo $loc->station_sig_info; ?></td>
                            <td><?php echo $loc->eqslqthnickname; ?></td>
                            <td><?php echo $loc->eqsl_default_qslmsg; ?></td>
                            <td><?php echo $qrzr; ?></td>
                            <td><?php echo $loc->oqrs ? 'Yes' : 'No'; ?></td>
                            <td><?php echo $loc->oqrs_text; ?></td>
                            <td><?php echo $loc->oqrs_email ? 'Yes' : 'No' ?></td>
                            <td><?php echo $loc->webadifrealtime ? 'Yes' : 'No' ?></td>
                            <td><?php echo $loc->clublogrealtime ? 'Yes' : 'No' ?></td>
                            <td><?php echo $loc->clublogignore ? 'Yes' : 'No' ?></td>
                            <td><?php echo $loc->hrdlogrealtime ? 'Yes' : 'No' ?></td>
                            <td><?php echo $loc->hrdlog_username; ?></td>
                            <td><?php echo $loc->creation_date; ?></td>
                            <td><?php echo $loc->last_modified; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
</div>
<script>
	document.addEventListener("DOMContentLoaded", function() {
		$('.table').DataTable({
			"pageLength": 25,
			responsive: false,
			ordering: true,
			"scrollY": window.innerHeight - 250,
			"scrollCollapse": true,
			"paging": false,
			"scrollX": true,
			"language": {
				url: getDataTablesLanguageUrl(),
			},
			dom: 'Bfrtip',
			buttons: [
				{
					extend: 'csv',
					className: 'mb-1 btn btn-sm btn-primary',
					init: function(api, node, config) {
						$(node).removeClass('dt-button').addClass('btn btn-primary');
					},
				},
				{
					text: 'Export All Locations',
					className: 'mb-1 btn btn-sm btn-primary', // same Bootstrap style
					action: function(e, dt, node, config) {
						exportAllLocations();
					},
					init: function(api, node, config) {
						$(node).removeClass('dt-button').addClass('btn btn-primary');
					}
				},
				<?php if (!($cd_p_level == 3) && !($cd_p_level == 6)) { // ClubOfficer (9) and normal User can import, while ClubOfficer (ADIF) (3,6) can only see. ?>
				{
				text: 'Import Locations',
				className: 'mb-1 btn btn-sm btn-primary',
				action: function(e, dt, node, config) {
					// Create a hidden file input (accept JSON)
					const input = document.createElement('input');
					input.type = 'file';
					input.accept = 'application/json';
					input.style.display = 'none';

					input.addEventListener('change', function(event) {
						const file = event.target.files[0];
						if (!file) return;

						const formData = new FormData();
						formData.append('file', file);

						const url = base_url + 'index.php/stationsetup/import_locations';

						fetch(url, {
							method: 'POST',
							body: formData
						})
						.then(response => response.json())
						.then(result => {
							console.log("Import result:", result);
							showToast('Info', result.message || "Import completed successfully!", 'bg-info text-dark', 4000);
						})
						.catch(error => {
							console.error("Import failed:", error);
							showToast('Error', 'Import failed. Check console for details.', 'bg-danger text-white', 5000);
						});
					});

					document.body.appendChild(input);
					input.click(); // Trigger file chooser
					document.body.removeChild(input);
				},
				init: function(api, node, config) {
					$(node).removeClass('dt-button').addClass('btn btn-primary');
				}
			}
			<?php } ?>

			]
		});
	});

	function exportAllLocations() {
		const url = base_url + 'index.php/stationsetup/export_locations';

		fetch(url)
			.then(response => {
				if (!response.ok) {
					showToast('Error', 'Network response was not ok (${response.status})', 'bg-danger text-white', 5000);
				}
				return response.json();
			})
			.then(data => {
				// Convert JSON to string
				const jsonStr = JSON.stringify(data, null, 2);

				// Create a downloadable blob
				const blob = new Blob([jsonStr], { type: "application/json" });
				const link = document.createElement("a");
				link.href = URL.createObjectURL(blob);
				link.download = "locations.json";

				// Trigger download
				document.body.appendChild(link);
				link.click();
				document.body.removeChild(link);
			})
			.catch(error => {
				showToast('Error', 'Failed to export locations. Check console for details.', 'bg-danger text-white', 5000);
			});
	}



</script>
</div>
