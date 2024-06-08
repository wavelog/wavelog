<div class="container settings">

	<div class="row">
		<!-- Nav Start -->
		<?php $this->load->view('options/sidebar') ?>
		<!-- Nav End -->

		<!-- Content -->
		<div class="col-md-9">
            <div class="card">
                <div class="card-header"><h2><?php echo $page_title; ?> - <?php echo $sub_heading; ?></h2></div>

                <div class="card-body">
                    <?php if($this->session->flashdata('success')) { ?>
                        <!-- Display Success Message -->
                        <div class="alert alert-success">
                        <?php echo $this->session->flashdata('success'); ?>
                        </div>
                    <?php } ?>

                    <?php if($this->session->flashdata('message')) { ?>
                        <!-- Display Message -->
                        <div class="alert-message error">
                        <?php echo $this->session->flashdata('message'); ?>
                        </div>
                    <?php } ?>

                    <?php if(validation_errors()) { ?>
                    <div class="alert alert-danger">
                        <a class="btn-close" data-bs-dismiss="alert">x</a>
                        <?php echo validation_errors(); ?>
                    </div>
                    <?php } ?>

                    <?php echo form_open('options/dxcluster_save'); ?>

                        <div class="mb-3">
                            <label for="globalSearch"><?= __("Provider of DXClusterCache"); ?></label>
                            <p><?= sprintf(__("The Provider of the DXCluster-Cache. You can set up your own Cache with %s or use a public one"), "<a href='https://github.com/int2001/DXClusterAPI'>".__("DXClusterAPI")."</a>"); ?></p>
                            <input type="text" name="dxcache_url" class="form-control" id="dxcache_url" aria-describedby="dxcache_urlHelp" value="<?php echo $this->optionslib->get_option('dxcache_url'); ?>">
                            <small id="dxcache_urlHelp" class="form-text text-muted"><?= sprintf(__("URL of the DXCluster-Cache. e.g. %s"), "https://dxc.jo30.de/dxcache" ); ?></small>
                        </div>
                        <div class="mb-3">
                            <label for="maxAgeSelect"><?= __("Maximum Age of spots taken care of"); ?></label>
                            <select class="form-select" id="maxAgeSelect" name="dxcluster_maxage" aria-describedby="dxcluster_maxageHelp" required>
				<option value="120"<?php if ($this->optionslib->get_option('dxcluster_maxage') == '120') { echo " selected"; } ?>><?= __("2 Hours"); ?></option>
				<option value="60"<?php if ($this->optionslib->get_option('dxcluster_maxage') == '60') { echo " selected"; } ?>><?= __("60 Minutes"); ?></option>
				<option value="30"<?php if ($this->optionslib->get_option('dxcluster_maxage') == '30') { echo " selected"; } ?>><?= __("30 Minutes"); ?></option>
                                </select>
                            <small id="dxcluster_maxageHelp" class="form-text text-muted"><?= __("The Age in Minutes of spots, that will be taken care at bandplan/lookup"); ?></small>
                        </div>
			<div class="mb-3">
                            <label for="decontSelect"><?= __("Show spots which are spotted from following continent"); ?></label>
                            <select class="form-select" id="decontSelect" name="dxcluster_decont" aria-describedby="dxcluster_decontHelp" required>
				<option value="AF"<?php if ($this->optionslib->get_option('dxcluster_decont') == 'AF') { echo " selected"; } ?>><?= __("Africa"); ?></option>
				<option value="AN"<?php if ($this->optionslib->get_option('dxcluster_decont') == 'AN') { echo " selected"; } ?>><?= __("Antarctica"); ?></option>
				<option value="AS"<?php if ($this->optionslib->get_option('dxcluster_decont') == 'AS') { echo " selected"; } ?>><?= __("Asia"); ?></option>
				<option value="EU"<?php if ($this->optionslib->get_option('dxcluster_decont') == 'EU') { echo " selected"; } ?>><?= __("Europe"); ?></option>
				<option value="NA"<?php if ($this->optionslib->get_option('dxcluster_decont') == 'NA') { echo " selected"; } ?>><?= __("North America"); ?></option>
				<option value="OC"<?php if ($this->optionslib->get_option('dxcluster_decont') == 'OC') { echo " selected"; } ?>><?= __("Oceania"); ?></option>
				<option value="SA"<?php if ($this->optionslib->get_option('dxcluster_decont') == 'SA') { echo " selected"; } ?>><?= __("South America"); ?></option>
                                </select>
                            <small id="dxcluster_decontHelp" class="form-text text-muted"><?= __("Only spots by spotters from this continent are shown"); ?></small>
                        </div>
 
                        <!-- Save the Form -->
                        <input class="btn btn-primary" type="submit" value="<?= __("Save"); ?>" />
                    </form>
                </div>
            </div>
		</div>
	</div>

</div>
