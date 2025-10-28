<h1><?= $page_title ?></h1>

    <?php if (!empty($locations)): ?>
        <div class="table-responsive mt-3">
			<table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed text-center">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
						<th>Active</th>
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
                        <th>County</th>
                        <th>WWFF</th>
                        <th>POTA</th>
                        <th>SIG</th>
                        <th>SIG Info</th>
                        <th>eQSL Nick</th>
                        <th>QRZ Live</th>
                        <th>OQRS</th>
                        <th>OQRS Text</th>
                        <th>OQRS Email</th>
                        <th>Web ADIF Live</th>
                        <th>ClubLog Live</th>
                        <th>ClubLog Ignore</th>
                        <th>HRDLog Live</th>
                        <th>Created</th>
                        <th>Last Modified</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($locations as $loc): ?>
                        <tr>
							<td><?php echo $loc->station_id; ?></td>
                            <td><?php echo $loc->station_active ? 'Yes' : 'No' ?></td>
                            <td><a href="http://localhost/wavelog/station/edit/<?php echo $loc->station_id; ?>"><?php echo $loc->station_profile_name; ?></a></td>
                            <td><?php echo $loc->station_callsign; ?></td>
                            <td><?php echo $loc->station_gridsquare; ?></td>
                            <td><?php echo $loc->station_city; ?></td>
                            <td><?php echo ucwords(strtolower($loc->dxccname), "- (/") . ' (' . $loc->dxccprefix . ') '; ?>
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
                            <td><?php echo $loc->county; ?></td>
                            <td><?php echo $loc->station_wwff; ?></td>
                            <td><?php echo $loc->station_pota; ?></td>
                            <td><?php echo $loc->station_sig; ?></td>
                            <td><?php echo $loc->station_sig_info; ?></td>
                            <td><?php echo $loc->eqslqthnickname; ?></td>
                            <td><?php echo $loc->qrzrealtime ? 'Yes' : 'No'; ?></td>
                            <td><?php echo $loc->oqrs ? 'Yes' : 'No'; ?></td>
                            <td><?php echo $loc->oqrs_text; ?></td>
                            <td><?php echo $loc->oqrs_email; ?></td>
                            <td><?php echo $loc->webadifrealtime ? 'Yes' : 'No' ?></td>
                            <td><?php echo $loc->clublogrealtime ? 'Yes' : 'No' ?></td>
                            <td><?php echo $loc->clublogignore ? 'Yes' : 'No' ?></td>
                            <td><?php echo $loc->hrdlogrealtime ? 'Yes' : 'No' ?></td>
                            <td><?php echo $loc->creation_date; ?></td>
                            <td><?php echo $loc->last_modified; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info mt-3">No station locations found.</div>
    <?php endif; ?>
</div>
