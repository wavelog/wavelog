<div class="container">
<br>
    <h2><?= __("KML Export"); ?></h2>

    <div class="card">
        <div class="card-header">
            <?= __("Export your logbook to a KML file for use in Google Earth."); ?>
        </div>

        <div class="alert alert-warning" role="alert">
            <?= __("Only QSOs with a gridsquare defined will be exported!"); ?>
        </div>

        <div class="card-body">

            <form class="form" action="<?php echo site_url('kmlexport/export'); ?>" method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="mb-3 col-md-3">
                        <label for="band"><?= __("Band"); ?></label>
                            <select id="band" name="band" class="form-select">
                                <option value="All" <?php if ($this->input->post('band') == "All" || $this->input->method() !== 'post') echo ' selected'; ?> ><?= __("All"); ?></option>
                                <?php foreach($worked_bands as $band) {
                                    echo '<option value="' . $band . '"';
                                    if ($this->input->post('band') == $band) echo ' selected';
                                    echo '>' . $band . '</option>'."\n";
                                } ?>
                            </select>
                    </div>

                    <div class="mb-3 col-md-3">
                    <label for="mode"><?= __("Mode"); ?></label>
                        <select id="mode" name="mode" class="form-select">
                            <option value="All"><?= __("All"); ?></option>
                            <?php
                            foreach($modes->result() as $mode){
                                if ($mode->submode == null) {
                                    echo '<option value="' . $mode->mode . '">'. $mode->mode . '</option>'."\n";
                                } else {
                                    echo '<option value="' . $mode->submode . '">' . $mode->submode . '</option>'."\n";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3 col-md-4">
                        <label for="dxcc_id"><?= __("DXCC"); ?></label>
                        <select class="form-select" id="dxcc_id" name="dxcc_id">
                            <option value="All"><?= __("All"); ?></option>
                            <?php
                                foreach($dxcc as $d){
                                    echo '<option value=' . $d->adif . '>' . $d->prefix . ' - ' . ucwords(strtolower($d->name), "- (/");
                                    if ($d->Enddate != null) {
                                        echo ' ('.__("Deleted DXCC").')';
                                    }
                                    echo '</option>';
                                }
                            ?>

                        </select>
                    </div>
                </div>
                <div class="row">                
                    <div class="mb-3 col-md-3">
                        <label for="cqz"><?= __("CQ Zone"); ?></label>
                        <select class="form-select" id="cqz" name="cqz">
                            <option value="All"><?= __("All"); ?></option>
                            <?php
                            for ($i = 1; $i<=40; $i++) {
                                echo '<option value="'. $i . '">'. $i .'</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3 col-md-5">
                        <label for="selectPropagation"><?= __("Propagation Mode"); ?></label>
                        <select class="form-select" id="selectPropagation" name="prop_mode">
                        <option value="All"><?= __("All"); ?></option>
						<option value="AS"><?= _pgettext("Propagation Mode","Aircraft Scatter"); ?></option>
						<option value="AUR"><?= _pgettext("Propagation Mode","Aurora"); ?></option>
						<option value="AUE"><?= _pgettext("Propagation Mode","Aurora-E"); ?></option>
						<option value="BS"><?= _pgettext("Propagation Mode","Back scatter"); ?></option>
						<option value="ECH"><?= _pgettext("Propagation Mode","EchoLink"); ?></option>
						<option value="EME"><?= _pgettext("Propagation Mode","Earth-Moon-Earth"); ?></option>
						<option value="ES"><?= _pgettext("Propagation Mode","Sporadic E"); ?></option>
						<option value="FAI"><?= _pgettext("Propagation Mode","Field Aligned Irregularities"); ?></option>
						<option value="F2"><?= _pgettext("Propagation Mode","F2 Reflection"); ?></option>
						<option value="INTERNET"><?= _pgettext("Propagation Mode","Internet-assisted"); ?></option>
						<option value="ION"><?= _pgettext("Propagation Mode","Ionoscatter"); ?></option>
						<option value="IRL"><?= _pgettext("Propagation Mode","IRLP"); ?></option>
						<option value="MS"><?= _pgettext("Propagation Mode","Meteor scatter"); ?></option>
						<option value="RPT"><?= _pgettext("Propagation Mode","Terrestrial or atmospheric repeater or transponder"); ?></option>
						<option value="RS"><?= _pgettext("Propagation Mode","Rain scatter"); ?></option>
						<option value="SAT"><?= _pgettext("Propagation Mode","Satellite"); ?></option>
						<option value="TEP"><?= _pgettext("Propagation Mode","Trans-equatorial"); ?></option>
						<option value="TR"><?= _pgettext("Propagation Mode","Tropospheric ducting"); ?></option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-3 col-md-3">
                        <label for="from"><?= __("From date") . ": " ?></label>
                        <input name="from" id="from" type="date" class="form-control w-auto">
                    </div>

                    <div class="mb-3 col-md-3">
                        <label for="to"><?= __("To date") . ": " ?></label>
                        <input name="to" id="to" type="date" class="form-control w-auto">
                    </div>
                </div>    
                <br>
                <button type="submit" class="btn btn-primary mb-2" value="Export"><?= __("Export"); ?></button>
            </form>
        </div>
    </div>
</div>
