<div class="container">
<br>
    <h2><?php echo __("KML Export"); ?></h2>

    <div class="card">
        <div class="card-header">
            <?php echo __("Export your logbook to a KML file for use in Google Earth."); ?>
        </div>

        <div class="alert alert-warning" role="alert">
            <?php echo __("Only QSOs with a gridsquare defined will be exported!"); ?>
        </div>

        <div class="card-body">

            <form class="form" action="<?php echo site_url('kmlexport/export'); ?>" method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="mb-3 col-md-3">
                        <label for="band"><?php echo __("Band"); ?></label>
                            <select id="band" name="band" class="form-select">
                                <option value="All" <?php if ($this->input->post('band') == "All" || $this->input->method() !== 'post') echo ' selected'; ?> ><?php echo __("All"); ?></option>
                                <?php foreach($worked_bands as $band) {
                                    echo '<option value="' . $band . '"';
                                    if ($this->input->post('band') == $band) echo ' selected';
                                    echo '>' . $band . '</option>'."\n";
                                } ?>
                            </select>
                    </div>

                    <div class="mb-3 col-md-3">
                    <label for="mode"><?php echo __("Mode"); ?></label>
                        <select id="mode" name="mode" class="form-select">
                            <option value="All"><?php echo __("All"); ?></option>
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
                        <label for="dxcc_id"><?php echo __("DXCC"); ?></label>
                        <select class="form-select" id="dxcc_id" name="dxcc_id">
                            <option value="All"><?php echo __("All"); ?></option>
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
                        <label for="cqz"><?php echo __("CQ Zone"); ?></label>
                        <select class="form-select" id="cqz" name="cqz">
                            <option value="All"><?php echo __("All"); ?></option>
                            <?php
                            for ($i = 1; $i<=40; $i++) {
                                echo '<option value="'. $i . '">'. $i .'</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3 col-md-5">
                        <label for="selectPropagation"><?php echo __("Propagation Mode"); ?></label>
                        <select class="form-select" id="selectPropagation" name="prop_mode">
                            <option value="All"><?php echo __("All"); ?></option>
                            <option value="AS">Aircraft Scatter</option>
                            <option value="AUR">Aurora</option>
                            <option value="AUE">Aurora-E</option>
                            <option value="BS">Back scatter</option>
                            <option value="ECH">EchoLink</option>
                            <option value="EME">Earth-Moon-Earth</option>
                            <option value="ES">Sporadic E</option>
                            <option value="FAI">Field Aligned Irregularities</option>
                            <option value="F2">F2 Reflection</option>
                            <option value="INTERNET">Internet-assisted</option>
                            <option value="ION">Ionoscatter</option>
                            <option value="IRL">IRLP</option>
                            <option value="MS">Meteor scatter</option>
                            <option value="RPT">Terrestrial or atmospheric repeater or transponder</option>
                            <option value="RS">Rain scatter</option>
                            <option value="SAT">Satellite</option>
                            <option value="TEP">Trans-equatorial</option>
                            <option value="TR">Tropospheric ducting</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-3 col-md-3">
                        <label for="from"><?php echo __("From date") . ": " ?></label>
                        <input name="from" id="from" type="date" class="form-control w-auto">
                    </div>

                    <div class="mb-3 col-md-3">
                        <label for="to"><?php echo __("To date") . ": " ?></label>
                        <input name="to" id="to" type="date" class="form-control w-auto">
                    </div>
                </div>    
                <br>
                <button type="submit" class="btn btn-primary mb-2" value="Export"><?php echo __("Export"); ?></button>
            </form>
        </div>
    </div>
</div>
