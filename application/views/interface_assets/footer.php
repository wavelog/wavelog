<script>
    /*
    Global used Variables
    */
    var option_map_tile_server = '<?php echo $this->optionslib->get_option('option_map_tile_server');?>';
    var option_map_tile_server_copyright = '<?php echo $this->optionslib->get_option('option_map_tile_server_copyright');?>';

    var base_url = "<?php echo base_url(); ?>"; // Base URL
    var site_url = "<?php echo site_url(); ?>"; // Site URL
	let measurement_base = 'K';

	<?php
	if ($this->session->userdata('user_measurement_base') == NULL) {
		?>
		measurement_base = '<?php echo $this->config->item('measurement_base'); ?>';
	<?php }
	else { ?>
		measurement_base = '<?php echo $this->session->userdata('user_measurement_base'); ?>';
	<?php }
	?>

    var icon_dot_url = "<?php echo base_url();?>assets/images/dot.png";

    // get the user_callsign from session
    var my_call = "<?php echo $this->session->userdata('user_callsign'); ?>".toUpperCase();

    /*
    General Language
    */
    var lang_general_word_qso_data = "<?= __("QSO Data"); ?>";
    var lang_general_edit_qso = "<?= __("Edit QSO"); ?>";
    var lang_general_word_danger = "<?= __("DANGER"); ?>";
    var lang_general_word_error = "<?= __("ERROR"); ?>";
    var lang_general_word_attention = "<?= __("Attention"); ?>";
    var lang_general_word_warning = "<?= __("Warning"); ?>";
    var lang_general_word_cancel = "<?= __("Cancel"); ?>";
    var lang_general_word_ok = "<?= __("OK"); ?>";
    var lang_general_word_search = "<?= __("Search"); ?>";
    var lang_qso_delete_warning = "<?= __("Warning! Are you sure you want delete QSO with "); ?>";
    var lang_general_word_colors = "<?= __("Colors"); ?>";
    var lang_general_word_confirmed = "<?= __("Confirmed"); ?>";
    var lang_general_word_worked_not_confirmed = "<?= __("Worked not confirmed"); ?>";
    var lang_general_word_not_worked = "<?= __("Not worked"); ?>";
    var lang_general_gridsquares = "<?= __("Gridsquares"); ?>";
    var lang_admin_close = "<?= __("Close"); ?>";
    var lang_admin_save = "<?= __("Save"); ?>";
    var lang_admin_clear = "<?= __("Clear"); ?>";
    var lang_lotw_propmode_hint = "<?= __("Propagation mode is not supported by LoTW. LoTW QSL fields disabled."); ?>";
    var lang_no_states_for_dxcc_available = "<?= html_entity_decode(__("No states for this DXCC available")); ?>";
    var lang_qrbcalc_title = '<?= __("Compute QRB and QTF"); ?>';
    var lang_qrbcalc_errmsg = '<?= __("Error in locators. Please check."); ?>';
    var lang_general_refresh_list = '<?= __("Refresh List"); ?>';
    var lang_general_word_please_wait = "<?= __("Please Wait ..."); ?>"
</script>

<!-- General JS Files used across Wavelog -->
<script src="<?php echo base_url(); ?>assets/js/jquery-3.3.1.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/jquery.fancybox.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/leaflet/leaflet.js"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/leaflet/Control.FullScreen.js"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/leaflet/L.Maidenhead.qrb.js"></script>
<?php if ($this->uri->segment(1) == "activators") { ?>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/leaflet/L.Maidenhead.activators.js"></script>
<?php } ?>
<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/leaflet/leaflet.geodesic.js"></script>
<script type="text/javascript" src="<?php echo base_url() ;?>assets/js/radiohelpers.js"></script>
<script type="text/javascript" src="<?php echo base_url() ;?>assets/js/darkmodehelpers.js"></script>
<script src="<?php echo base_url(); ?>assets/js/bootstrapdialog/js/bootstrap-dialog.min.js"></script>
<script type="text/javascript" src="<?php echo base_url() ;?>assets/js/easyprint.js"></script>
<script type="text/javascript" src="<?php echo base_url() ;?>assets/js/sections/common.js"></script>
<script type="text/javascript" src="<?php echo base_url() ;?>assets/js/sections/eqslcharcounter.js"></script>
<script type="text/javascript" src="<?php echo base_url() ;?>assets/js/sections/version_dialog.js"></script>
<script type="text/javascript" src="<?php echo base_url() ;?>assets/js/showdown.min.js"></script>

<script type="module" defer>
  		import { polyfillCountryFlagEmojis } from "<?php echo base_url() ;?>assets/js/country-flag-emoji-polyfill.js";
		polyfillCountryFlagEmojis();
</script>

<script src="<?php echo base_url(); ?>assets/js/htmx.min.js"></script>

<script>
    // Reinitialize tooltips after new content has been loaded
    document.addEventListener('htmx:afterSwap', function(event) {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
</script>


<!-- DATATABLES LANGUAGE -->
<?php
$local_code = $language['locale'];
$lang_code = $language['code'];
$file_path = base_url() . "assets/json/datatables_languages/" . $local_code . ".json";

// Check if the file exists
if ($lang_code != 'en' && !file_exists(FCPATH . "assets/json/datatables_languages/" . $local_code . ".json")) {
    $datatables_language_url = '';
} else {
    $datatables_language_url = $file_path;
}
?>

<script type="text/javascript">
    function getDataTablesLanguageUrl() {
        locale = "<?php echo $local_code ?>";
        lang_code = "<?php echo $lang_code; ?>";
        datatables_language_url = "<?php echo $datatables_language_url; ?>";

        // if language is set to english we don't need to load any language files
        if (lang_code != 'en') {
            if (datatables_language_url !== '') {
                return datatables_language_url;
            } else {
                console.error("Datatables language file does not exist for locale: " + locale);
                return null;
            }
        }
    }
</script>
<!-- DATATABLES LANGUAGE END -->

<!-- Version Dialog START -->

<?php
if($this->session->userdata('user_id') != null) {
    $versionDialog = $this->optionslib->get_option('version_dialog');
    if (empty($versionDialog)) {
        $this->optionslib->update('version_dialog', 'release_notes', 'yes');
    }
    $versionDialogHeader = $this->optionslib->get_option('version_dialog_header');
    if (empty($versionDialogHeader)) {
        $this->optionslib->update('version_dialog_header', __("Version Info"), 'yes');
    }
    if($versionDialog != "disabled") {
        $confirmed = $this->user_options_model->get_options('version_dialog', array('option_name'=>'confirmed'))->result();
        $confirmation_value = (isset($confirmed[0]->option_value))?$confirmed[0]->option_value:'false';
        if ($confirmation_value != 'true') {
            $this->user_options_model->set_option('version_dialog', 'confirmed', array('boolean' => $confirmation_value));
            ?><script>
                displayVersionDialog();
            </script><?php
        }
    }
}
?>

<!-- Version Dialog END -->

<!-- SPECIAL CALLSIGN OPERATOR FEATURE -->
<?php if ($this->config->item('special_callsign') == true && $this->uri->segment(1) == "dashboard") { ?>
<script type="text/javascript" src="<?php echo base_url() ;?>assets/js/sections/operator.js"></script>
<script>
	<?php
	# Set some variables for better readability
    $op_call = $this->session->userdata('operator_callsign');
	$account_call = $this->session->userdata('user_callsign');
    $user_type = $this->session->userdata('user_type'); ?>

    // JS variable which is used in operator.js
    let sc_account_call = '<?php echo $account_call; ?>'

	<?php
    # if the operator call and the account call is the same we show the dialog (except for admins!)
    if ($op_call == $account_call && $user_type != '99') { ?>

        // load the dialog with javascript
        displayOperatorDialog();

    <?php } ?>
</script>
<?php } ?>
<!-- SPECIAL CALLSIGN OPERATOR FEATURE END -->

<script>
    // Replace all Ø in the searchbar
    $('#nav-bar-search-input').on('input', function () {
        $(this).val($(this).val().replace(/0/g, 'Ø'));
    });
</script>

<script>
    var current_active_location = "<?php echo $this->stations->find_active(); ?>";
    quickswitcher_show_activebadge(current_active_location);
</script>

<?php if ($this->uri->segment(1) == "oqrs") { ?>
    <script src="<?php echo base_url() ;?>assets/js/sections/oqrs.js"></script>
<?php } ?>

<!-- JS library to convert cron format to human readable -->
<?php if ($this->uri->segment(1) == "cron") { ?>
    <script src="<?php echo base_url() ;?>assets/js/cronstrue.min.js"async></script>
<?php } ?>

<?php if ($this->uri->segment(1) == "options") { ?>
    <script>
        $('#sendTestMailButton').click(function() {
            $.ajax({
                url: base_url + 'index.php/options/sendTestMail',
                type: 'POST',
            });
        });

    </script>
<?php } ?>

<?php if ($this->uri->segment(1) == "awards" && ($this->uri->segment(2) == "iota") ) { ?>
    <script id="iotamapjs" type="text/javascript" src="<?php echo base_url(); ?>assets/js/sections/iotamap.js" tileUrl="<?php echo $this->optionslib->get_option('option_map_tile_server');?>"></script>
<?php } ?>

<?php if ($this->uri->segment(1) == "awards" && ($this->uri->segment(2) == "dxcc") ) { ?>
    <script id="dxccmapjs" type="text/javascript" src="<?php echo base_url(); ?>assets/js/sections/dxccmap.js" tileUrl="<?php echo $this->optionslib->get_option('option_map_tile_server');?>"></script>
<?php } ?>

<?php if ($this->uri->segment(1) == "statistics" && $this->uri->segment(2) == "") { ?>
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/chart.js"></script>
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/chartjs-plugin-piechart-outlabels.js"></script>
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/sections/statistics.js"></script>
<?php } ?>

<?php if ($this->uri->segment(1) == "continents") { ?>
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/chart.js"></script>
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/chartjs-plugin-piechart-outlabels.js"></script>
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/sections/continents.js"></script>
<?php } ?>

<?php if ($this->uri->segment(1) == "adif" || $this->uri->segment(1) == "qrz" || $this->uri->segment(1) == "hrdlog" || $this->uri->segment(1) == "webadif" || $this->uri->segment(1) == "sattimers") { ?>
    <!-- Javascript used for ADIF Import and Export Areas -->
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/moment.min.js"></script>
<?php } ?>

<?php if ($this->uri->segment(1) == "adif" ) { ?>
    <script src="<?php echo base_url() ;?>assets/js/sections/adif.js"></script>
    <script src="<?php echo base_url() ;?>assets/js/jszip.min.js"></script>
<?php } ?>

<?php if ($this->uri->segment(1) == "notes" && ($this->uri->segment(2) == "add" || $this->uri->segment(2) == "edit") ) { ?>
    <!-- Javascript used for Notes Area -->
    <script src="<?php echo base_url() ;?>assets/plugins/quill/quill.min.js"></script>
    <script src="<?php echo base_url() ;?>assets/js/sections/notes.js"></script>
<?php } ?>

<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/datatables.min.js"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/selectize.js"></script>

<?php if ($this->uri->segment(1) == "station") { ?>
    <script language="javascript" src="<?php echo base_url() ;?>assets/js/HamGridSquare.js"></script>
    <script src="<?php echo base_url() ;?>assets/js/sections/station_locations.js"></script>
    <script src="<?php echo base_url() ;?>assets/js/bootstrap-multiselect.js"></script>
    <script>
        var position;
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition);
            } else {
                console.log('Geolocation is not supported by this browser.');
            }
        }

        function showPosition(position) {
            gridsquare = latLonToGridSquare(position.coords.latitude,position.coords.longitude);
            document.getElementById("stationGridsquareInput").value = gridsquare;
  }
    </script>
<?php } ?>

<?php if ($this->uri->segment(1) == "logbooks") { ?>
    <script src="<?php echo base_url() ;?>assets/js/sections/station_logbooks.js"></script>
<?php } ?>

<?php if ($this->uri->segment(1) == "debug") { ?>
<script type="text/javascript">
function copyURL(url) {
   var urlField = $('#baseUrl');
   navigator.clipboard.writeText(url).then(function() {
   });
   urlField.addClass('flash-copy')
      .delay('1000').queue(function() {
         urlField.removeClass('flash-copy').dequeue();
      });
}

$(function () {
   $('[data-bs-toggle="tooltip"]').tooltip({'delay': { show: 500, hide: 0 }, 'placement': 'right'});
});
</script>
<?php } ?>

<?php if ($this->uri->segment(1) == "api"  && $this->uri->segment(2) == "help") { ?>
<script type="text/javascript">
function copyApiKey(apiKey) {
   var apiKeyField = $('#'+apiKey);
   navigator.clipboard.writeText(apiKey).then(function() {
   });
   apiKeyField.addClass('flash-copy')
      .delay('1000').queue(function() {
         apiKeyField.removeClass('flash-copy').dequeue();
      });
}

function copyApiUrl() {
   var apiUrlField = $('#apiUrl');
   navigator.clipboard.writeText("<?php echo base_url(); ?>").then(function() {
   });
   apiUrlField.addClass('flash-copy')
      .delay('1000').queue(function() {
         apiUrlField.removeClass('flash-copy').dequeue();
      });
}

$(function () {
   $('[data-bs-toggle="tooltip"]').tooltip({'delay': { show: 500, hide: 0 }, 'placement': 'right'});
});
</script>
<?php } ?>

<?php if ($this->uri->segment(1) == "search" && $this->uri->segment(2) == "filter") { ?>

<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/query-builder.standalone.min.js"></script>

<script type="text/javascript">
    $(".search-results-box").hide();

    $('#builder').queryBuilder({
        filters: [
            <?php foreach ($get_table_names->result() as $row) {
                $value_name = str_replace("COL_", "", $row->Field);
                if ($value_name != "PRIMARY_KEY" && strpos($value_name, 'MY_') === false && strpos($value_name, '_INTL') == false) { ?> {
                        id: '<?php echo $row->Field; ?>',
                        label: '<?php echo $value_name; ?>',
                        <?php if (strpos($row->Type, 'int(') !== false) { ?>
                            type: 'integer',
                            operators: ['equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal']
                        <?php } elseif (strpos($row->Type, 'double') !== false) { ?>
                            type: 'double',
                            operators: ['equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal']
                        <?php } elseif (strpos($row->Type, 'datetime') !== false) { ?>
                            type: 'datetime',
                            operators: ['equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal']
                        <?php } else { ?>
                            type: 'string',
                            operators: ['equal', 'not_equal', 'begins_with', 'contains', 'ends_with', 'is_empty', 'is_not_empty', 'is_null', 'is_not_null']
                        <?php } ?>
                    },
                <?php } ?>
            <?php } ?>
        ]
    });


    function export_search_result() {
        var result = $('#builder').queryBuilder('getRules');
        if (!$.isEmptyObject(result)) {
            xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                var a;
                if (xhttp.readyState === 4 && xhttp.status === 200) {
                    // Trick for making downloadable link
                    a = document.createElement('a');
                    a.href = window.URL.createObjectURL(xhttp.response);
                    // Give filename you wish to download
                    a.download = "advanced_search_export.adi";
                    a.style.display = 'none';
                    document.body.appendChild(a);
                    a.click();
                }
            };
            // Post data to URL which handles post request
            xhttp.open("POST", "<?php echo site_url('search/export_to_adif'); ?>", true);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            // You should set responseType as blob for binary responses
            xhttp.responseType = 'blob';
            xhttp.send("search=" + JSON.stringify(result, null, 2));
        }
    }

    function export_stored_query(id) {
        xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            var a;
            if (xhttp.readyState === 4 && xhttp.status === 200) {
                // Trick for making downloadable link
                a = document.createElement('a');
                a.href = window.URL.createObjectURL(xhttp.response);
                // Give filename you wish to download
                a.download = "advanced_search_export.adi";
                a.style.display = 'none';
                document.body.appendChild(a);
                a.click();
            }
        };
        // Post data to URL which handles post request
        xhttp.open("POST", "<?php echo site_url('search/export_stored_query_to_adif'); ?>", true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        // You should set responseType as blob for binary responses
        xhttp.responseType = 'blob';
        xhttp.send("id=" + id);
    }

    $('#btn-save').on('click', function() {
        var resultquery = $('#builder').queryBuilder('getRules');
        if (!$.isEmptyObject(resultquery)) {
            let message = "<?= __("Description:"); ?>" + '<input class="form-control input-group-sm getqueryname">'

            BootstrapDialog.confirm({
                title: "<?= __("Query description"); ?>",
                size: BootstrapDialog.SIZE_NORMAL,
                cssClass: 'description-dialog',
                closable: true,
                nl2br: false,
                message: message,
                btnCancelLabel: lang_general_word_cancel,
                btnOKLabel: lang_admin_save,
                callback: function(result) {
                    if (result) {
                        $.post("<?php echo site_url('search/save_query'); ?>", {
                                search: JSON.stringify(resultquery, null, 2),
                                description: $(".getqueryname").val()
                            })
                            .done(function(data) {
                                $(".alert").remove();
                                $(".card-body.main").append('<div class="alert alert-success">'+"<?= __("Your query has been saved!"); ?>"+'</div>');
                                if ($("#querydropdown option").length == 0) {
                                    var dropdowninfo = ' <button class="btn btn-sm btn-primary" onclick="edit_stored_query_dialog()" id="btn-edit">'+"<?= __("Edit queries"); ?>"+'</button></p>' +
                                    '<div class="mb-3 row querydropdownform">' +
                                        '<label class="col-md-2 control-label" for="querydropdown">  '+"<?= __("Stored queries:"); ?>"+'</label>' +
                                        '<div class="col-md-3">' +
                                            '<select id="querydropdown" name="querydropdown" class="form-select form-select-sm">' +
                                            '</select>' +
                                        '</div>' +
                                        '<button class="btn btn-sm btn-primary ld-ext-right runbutton" onclick="run_query()">'+"<?= __("Run Query"); ?>"+'<div class="ld ld-ring ld-spin"></div></button>' +
                                    '</div>';
                                    $("#btn-save").after(dropdowninfo);
                                }
                                $('#querydropdown').append(new Option(data.description, data.id)); // We add the saved query to the dropdown
                            });
                    }
                },
            });

        } else {
            BootstrapDialog.show({
                title: "<?= __("Stored Queries"); ?>",
                type: BootstrapDialog.TYPE_WARNING,
                size: BootstrapDialog.SIZE_NORMAL,
                cssClass: 'queries-dialog',
                nl2br: false,
                message: "<?= __("You need to make a query before you search!"); ?>",
                buttons: [{
                    label: lang_admin_close,
                    action: function(dialogItself) {
                        dialogItself.close();
                    }
                }]
            });
        }
    });

    function run_query() {
        $(".alert").remove();
        $(".runbutton").addClass('running');
        $(".runbutton").prop('disabled', true);
        let id = $('#querydropdown').val();
        $.post("<?php echo site_url('search/run_query'); ?>", {
                id: id
            })
            .done(function(data) {

                $('.exportbutton').html('<button class="btn btn-sm btn-primary" onclick="export_stored_query(' + id + ')">'+"<?= __("Export to ADIF"); ?>"+'</button>');
                $('.card-body.result').empty();
                $(".search-results-box").show();

                $('.card-body.result').append(data);
                $('.table').DataTable({
                    "pageLength": 25,
                    responsive: false,
                    ordering: false,
                    "scrollY": "400px",
                    "scrollCollapse": true,
                    "paging": false,
                    "scrollX": true,
                    "language": {
                        url: getDataTablesLanguageUrl(),
                    },
                    dom: 'Bfrtip',
                    buttons: [
                        'csv'
                    ]
                });
                // change color of csv-button if dark mode is chosen
                if (isDarkModeTheme()) {
                    $(".buttons-csv").css("color", "white");
                }
                $('[data-bs-toggle="tooltip"]').tooltip();
                $(".runbutton").removeClass('running');
                $(".runbutton").prop('disabled', false);

				$('.table-responsive .dropdown-toggle').off('mouseenter').on('mouseenter', function () {
                        showQsoActionsMenu($(this).closest('.dropdown'));
                    });
            });
    }

    function delete_stored_query(id) {
        BootstrapDialog.confirm({
            title: "<?= __("DANGER"); ?>",
            message: "<?= __("Warning! Are you sure you want delete this stored query?"); ?>",
            type: BootstrapDialog.TYPE_DANGER,
            closable: true,
            draggable: true,
            btnOKClass: 'btn-danger',
            callback: function(result) {
                if (result) {
                    $.ajax({
                        url: base_url + 'index.php/search/delete_query',
                        type: 'post',
                        data: {
                            'id': id
                        },
                        success: function(data) {
                            $(".bootstrap-dialog-message").prepend('<div class="alert alert-danger">'+"<?= __("The stored query has been deleted!"); ?>"+'</div>');
                            $("#query_" + id).remove(); // removes query from table in dialog
                            $("#querydropdown option[value='" + id + "']").remove(); // removes query from dropdown
                            if ($("#querydropdown option").length == 0) {
                                $("#btn-edit").remove();
                                $('.querydropdownform').remove();
                            };
                        },
                        error: function() {
                            $(".bootstrap-dialog-message").prepend('<div class="alert alert-danger">'+"<?= __("The stored query could not be deleted. Please try again!"); ?>"+'</div>');
                        },
                    });
                }
            }
        });
    }

    function edit_stored_query(id) {
        $('#description_' + id).attr('contenteditable', 'true');
        $('#description_' + id).focus();
        $('#edit_' + id).html('<a class="btn btn-primary btn-sm" href="javascript:save_edited_query(' + id + ');">'+"<?= __("Save"); ?>"+'</a>'); // Change to save button
    }

    function save_edited_query(id) {
        $('#description_' + id).attr('contenteditable', 'false');
        $('#edit_' + id).html('<a class="btn btn-outline-primary btn-sm" href="javascript:edit_stored_query(' + id + ');">'+"<?= __("Edit"); ?>"+'</a>');
        $.ajax({
            url: base_url + 'index.php/search/save_edited_query',
            type: 'post',
            data: {
                id: id,
                description: $('#description_' + id).html(),
            },
            success: function(html) {
                $('#edit_' + id).html('<a class="btn btn-outline-primary btn-sm" href="javascript:edit_stored_query(' + id + ');">'+"<?= __("Edit"); ?>"+'</a>'); // Change to edit button
                $(".bootstrap-dialog-message").prepend('<div class="alert alert-success">'+"<?= __("The query description has been updated!"); ?>"+'</div>');
                $("#querydropdown option[value='" + id + "']").text($('#description_' + id).html()); // Change text in dropdown
            },
            error: function() {
                $(".bootstrap-dialog-message").prepend('<div class="alert alert-danger">'+"<?= __("Something went wrong with the save. Please try again!"); ?>"+'</div>');
            },
        });
    }

    function edit_stored_query_dialog() {
        $(".alert").remove();
        $.ajax({
            url: base_url + 'index.php/search/get_stored_queries',
            type: 'post',
            success: function(html) {
                BootstrapDialog.show({
                    title: "<?= __("Stored Queries"); ?>",
                    size: BootstrapDialog.SIZE_WIDE,
                    cssClass: 'queries-dialog',
                    nl2br: false,
                    message: html,
                    buttons: [{
                        label: lang_admin_close,
                        action: function(dialogItself) {
                            dialogItself.close();
                        }
                    }]
                });
            }
        });
    }

    $('#btn-get').on('click', function() {
        $(".alert").remove();
        var result = $('#builder').queryBuilder('getRules');
        if (!$.isEmptyObject(result)) {
            $(".searchbutton").addClass('running');
            $(".searchbutton").prop('disabled', true);

            $.post("<?php echo site_url('search/search_result'); ?>", {
                    search: JSON.stringify(result, null, 2),
                    temp: "testvar"
                })
                .done(function(data) {
                    $('.exportbutton').html('<button class="btn btn-sm btn-primary" onclick="export_search_result();">'+"<?= __("Export to ADIF"); ?>"+'</button>');

                    $('.card-body.result').empty();
                    $(".search-results-box").show();

                    $('.card-body.result').append(data);
                    $('.table').DataTable({
                        "pageLength": 25,
                        responsive: false,
                        ordering: false,
                        "scrollY": "400px",
                        "scrollCollapse": true,
                        "paging": false,
                        "scrollX": true,
                        "language": {
                            url: getDataTablesLanguageUrl(),
                        },
                        dom: 'Bfrtip',
                        buttons: [
                            'csv'
                        ]
                    });
                    // change color of csv-button if dark mode is chosen
                    if (isDarkModeTheme()) {
                        $(".buttons-csv").css("color", "white");
                    }
                    $('[data-bs-toggle="tooltip"]').tooltip();
                    $(".searchbutton").removeClass('running');
                    $(".searchbutton").prop('disabled', false);
                    $("#btn-save").show();
                    $('.table-responsive .dropdown-toggle').off('mouseenter').on('mouseenter', function () {
                        showQsoActionsMenu($(this).closest('.dropdown'));
                    });
                });
        } else {
            BootstrapDialog.show({
                title: "<?= __("Stored Queries"); ?>",
                type: BootstrapDialog.TYPE_WARNING,
                size: BootstrapDialog.SIZE_NORMAL,
                cssClass: 'queries-dialog',
                nl2br: false,
                message: "<?= __("You need to make a query before you search!"); ?>",
                buttons: [{
                    label: lang_admin_close,
                    action: function(dialogItself) {
                        dialogItself.close();
                    }
                }]
            });
        }
    });

    $('#btn-set').on('click', function() {
        //$('#builder').queryBuilder('setRules', rules_basic);
        var result = $('#builder').queryBuilder('getRules');
        if (!$.isEmptyObject(result)) {
            rules_basic = result;
        }
    });

    //When rules changed :
    $('#builder').on('getRules.queryBuilder.filter', function(e) {
        //$log.info(e.value);
    });
</script>

<?php } ?>

<script>
$(document).ready(function() {
	$('#create_station_profile #country').val($("#dxcc_id option:selected").text());
	$("#create_station_profile #dxcc_id" ).change(function() {
	$('#country').val($("#dxcc_id option:selected").text());

	});
});
</script>

<script>
function printWarning() {
    if ($("#dxcc_id option:selected").text().includes("<?= __("Deleted DXCC"); ?>")) {
        $('#warningMessageDXCC').show();
        $('#dxcc_id').css('border', '2px solid rgb(217, 83, 79)');
        $('#warningMessageDXCC').text("<?= __("Stop here for a Moment. Your chosen DXCC is outdated and not valid anymore. Check which DXCC for this particular location is the correct one. If you are sure, ignore this warning."); ?>");
    } else {
        $('#dxcc_id').css('border', '');
        $('#warningMessageDXCC').hide();
    }
}
$('#dxcc_id').ready(function() {
    printWarning();
});

$('#dxcc_id').on('change', function() {
    printWarning();
    <?php if (isset($dxcc_list) && $dxcc_list->result() > 0) { ?>
        let dxccadif = $('#dxcc_id').val();
        let dxccinfo = dxccarray.filter(function(dxcc) {
            return dxcc.adif == dxccadif;
        });
        $("#stationCQZoneInput").val(dxccinfo[0].cq);
        if (dxccadif == 0) {
            $("#stationITUZoneInput").val(dxccinfo[0].itu); // Only set ITU zone to none if DXCC none is selected. We don't have ITU data for other DXCCs.
        }
    <?php } ?>
});
</script>

<script>
var $= jQuery.noConflict();
$('[data-fancybox]').fancybox({
    toolbar  : false,
    smallBtn : true,
    iframe : {
        preload : false
    }
});

// Here we capture ALT-L to invoke the Quick lookup
document.onkeyup = function(e) {
	if (e.altKey && e.which == 76) {
		spawnLookupModal();
	}
    if (e.altKey && e.which == 81) {
		spawnQrbCalculator();
	}
};



function showActivatorsMap(call, count, grids) {

    let re = /,/g;
    grids = grids.replace(re, ', ');

    var result = '<?= __("Callsign: "); ?>'+call.replace('0', '&Oslash;')+"<br />";
    result +=    '<?= __("Count: "); ?>'+count+"<br/>";
    result +=    '<?= __("Grids: "); ?>'+grids+"<br/><br />";

    $(".activatorsmapResult").html(result);

    // If map is already initialized
    var container = L.DomUtil.get('mapactivators');

    if(container != null){
        container._leaflet_id = null;
    }

    const map = new L.map('mapactivators').setView([30, 0], 1.5);

    var grid_four = grids.split(', ');

    var maidenhead = new L.maidenheadactivators(grid_four).addTo(map);

    var osmUrl = '<?php echo $this->optionslib->get_option('option_map_tile_server');?>';
    var osmAttrib = option_map_tile_server_copyright;
    var osm = new L.TileLayer(osmUrl, {minZoom: 1, maxZoom: 12, attribution: osmAttrib});

    map.addLayer(osm);
}

</script>

<?php if ($this->uri->segment(1) == "" || $this->uri->segment(1) == "dashboard" ) { ?>
    <script type="text/javascript" src="<?php echo base_url();?>assets/js/leaflet/L.Maidenhead.js"></script>
    <script id="leafembed" type="text/javascript" src="<?php echo base_url();?>assets/js/leaflet/leafembed.js" tileUrl="<?php echo $this->optionslib->get_option('option_map_tile_server');?>"></script>

    <script type="text/javascript">
      $(function () {
        $('[data-bs-toggle="tooltip"]').tooltip()
      });

        <?php if($qra == "set") { ?>
        var q_lat = <?php echo $qra_lat; ?>;
        var q_lng = <?php echo $qra_lng; ?>;
        <?php } else { ?>
        var q_lat = 40.313043;
        var q_lng = -32.695312;
        <?php } ?>

        var qso_loc = '<?php echo site_url('map/map_plot_json');?>';
        var q_zoom = 3;

      $(document).ready(function(){
            <?php if ($this->config->item('map_gridsquares') != FALSE) { ?>
              var grid = "Yes";
            <?php } else { ?>
              var grid = "No";
            <?php } ?>
            initmap(grid,'map',{'dataPost':{'nb_qso':'18'}});

      });
    </script>
<?php } ?>


<script type="text/javascript">
  $(function () {
     $('[data-bs-toggle="tooltip"]').tooltip()
  });
</script>

<?php if ($this->uri->segment(1) == "search") { ?>
<script type="text/javascript">
i=0;

function findlotwunconfirmed(){
    event.preventDefault();
    $('#partial_view').load(base_url+"index.php/logbook/search_lotw_unconfirmed/"+$("#station_id").val(), function() {
        $('.qsolist').DataTable({
            "pageLength": 25,
            responsive: false,
            ordering: false,
            "scrollY":        "500px",
            "scrollCollapse": true,
            "paging":         false,
            "scrollX": true,
            "language": {
                url: getDataTablesLanguageUrl(),
            },
            dom: 'Bfrtip',
            buttons: [
                'csv'
            ]
        });
        // change color of csv-button if dark mode is chosen
        if (isDarkModeTheme()) {
            $(".buttons-csv").css("color", "white");
        }
    });
}

function findincorrectcqzones() {
    event.preventDefault();
    $('#partial_view').load(base_url+"index.php/logbook/search_incorrect_cq_zones/"+$("#station_id").val(), function() {
        $('.qsolist').DataTable({
            "pageLength": 25,
            responsive: false,
            ordering: false,
            "scrollY":        "500px",
            "scrollCollapse": true,
            "paging":         false,
            "scrollX": true,
            "language": {
                url: getDataTablesLanguageUrl(),
            },
            dom: 'Bfrtip',
            buttons: [
                'csv'
            ]
        });
        // change color of csv-button if dark mode is chosen
        if (isDarkModeTheme()) {
            $(".buttons-csv").css("color", "white");
        }
    });
}

function findincorrectituzones() {
    event.preventDefault();
    $('#partial_view').load(base_url+"index.php/logbook/search_incorrect_itu_zones/"+$("#station_id").val(), function() {
        $('.qsolist').DataTable({
            "pageLength": 25,
            responsive: false,
            ordering: false,
            "scrollY":        "500px",
            "scrollCollapse": true,
            "paging":         false,
            "scrollX": true,
            "language": {
                url: getDataTablesLanguageUrl(),
            },
            dom: 'Bfrtip',
            buttons: [
                'csv'
            ]
        });
        // change color of csv-button if dark mode is chosen
        if (isDarkModeTheme()) {
            $(".buttons-csv").css("color", "white");
        }
    });
}

function searchButtonPress() {
    if (event) { event.preventDefault(); }
    if ($('#callsign').val()) {
        let fixedcall = $('#callsign').val().trim();
        $('#partial_view').load("logbook/search_result/" + fixedcall.replace('Ø', '0'), function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
            $('.table-responsive .dropdown-toggle').off('mouseenter').on('mouseenter', function() {
                showQsoActionsMenu($(this).closest('.dropdown'));
            });
        });
    }
}

$(document).ready(function(){
    <?php if($this->input->post('callsign') != "") { ?>
        $('#callsign').val('<?php echo $this->input->post('callsign'); ?>');
        searchButtonPress();
    <?php } ?>

$($('#callsign')).on('keypress',function(e) {
    if(e.which == 13) {
        searchButtonPress();
        event.preventDefault();
        return false;
    }
});


});
</script>
<?php } ?>

<?php if ($this->uri->segment(1) == "logbook" && $this->uri->segment(2) != "view") { ?>
    <script type="text/javascript" src="<?php echo base_url();?>assets/js/leaflet/L.Maidenhead.js"></script>
    <script id="leafembed" type="text/javascript" src="<?php echo base_url();?>assets/js/leaflet/leafembed.js" tileUrl="<?php echo $this->optionslib->get_option('option_map_tile_server');?>"></script>
    <script type="text/javascript">
      $(function () {
         $('[data-bs-toggle="tooltip"]').tooltip()
      });
    </script>
    <script type="text/javascript">
        <?php if($qra == "set") { ?>
        var q_lat = <?php echo $qra_lat; ?>;
        var q_lng = <?php echo $qra_lng; ?>;
        <?php } else { ?>
        var q_lat = 40.313043;
        var q_lng = -32.695312;
        <?php } ?>

        var qso_loc = '<?php echo site_url('map/map_plot_json'); ?>';
        var q_zoom = 3;

        <?php if ($this->config->item('map_gridsquares') != FALSE) { ?>
              var grid = "Yes";
        <?php } else { ?>
              var grid = "No";
        <?php } ?>
            initmap(grid,'map',{'dataPost':{'nb_qso':'25','offset':'<?php echo $this->uri->segment(3); ?>'}});

    </script>
<?php } ?>

<?php if ($this->uri->segment(1) == "qso") { ?>

<script src="<?php echo base_url() ;?>assets/js/sections/qso.js"></script>
<script src="<?php echo base_url() ;?>assets/js/bootstrap-multiselect.js"></script>
<?php if ($this->session->userdata('isWinkeyEnabled')) { ?>
	<script src="<?php echo base_url() ;?>assets/js/winkey.js"></script>
<?php }	?>
	<script type="text/javascript">
		var dxcluster_provider = '<?php echo base_url(); ?>index.php/dxcluster';
	</script>

<?php
    $active_station_id = $this->stations->find_active();
    $station_profile = $this->stations->profile($active_station_id);
    $active_station_info = $station_profile->row();

    if (strpos(($active_station_info->station_gridsquare ?? ''), ',') !== false) {
        $gridsquareArray = explode(',', $active_station_info->station_gridsquare);
        $user_gridsquare = $gridsquareArray[0];
    } else {
        $user_gridsquare = ($active_station_info->station_gridsquare ?? '');
    }
?>

<script>
  var markers = L.layerGroup();
  var pos = [51.505, -0.09];
  var mymap = L.map('qsomap').setView(pos, 12);
  $.ajax({
     url: base_url + 'index.php/logbook/qralatlngjson',
     type: 'post',
     data: {
<?php if (($active_station_info->station_gridsquare ?? '') != "") { ?>
        qra: '<?php echo $user_gridsquare; ?>',
<?php } else if (null !== $this->config->item('locator')) { ?>
        qra: '<?php echo $this->config->item('locator'); ?>',
<?php } else { ?>
        // Fallback to London in case all else fails
        qra: 'IO91WM',
<?php } ?>
     },
     success: function(data) {
        result = JSON.parse(data);
        if (typeof result[0] !== "undefined" && typeof result[1] !== "undefined") {
           mymap.panTo([result[0], result[1]]);
           pos = result;
        }
     },
     error: function() {
     },
  });

  L.tileLayer('<?php echo $this->optionslib->get_option('option_map_tile_server');?>', {
    maxZoom: 18,
    attribution: '<?php echo $this->optionslib->get_option('option_map_tile_server_copyright');?>',
    id: 'mapbox.streets'
  }).addTo(mymap);

</script>

  <script type="text/javascript">

    var manual = <?php echo $manual_mode; ?>;

<?php if ($this->session->userdata('user_qso_end_times')  == 1) { ?>
    $('#callsign').focusout(function() {
      if (! manual && $('#callsign').val() != '') {
        clearInterval(handleStart);
        clearInterval(handleDate);
      }
    });
    $('#start_time').focusout(function() {
       if (manual && $('#start_time').val() != '') {
          $('#end_time').val($('#start_time').val());
       }
    });
<?php } ?>

<?php if ($this->session->userdata('user_sota_lookup') == 1) { ?>
	$('#sota_ref').change(function() {
		var sota = $('#sota_ref').val();
		if (sota.length > 0) {
			$.ajax({
				url: base_url+'index.php/qso/get_sota_info',
				type: 'post',
				data: {'sota': sota},
				success: function(res) {
					$('#qth').val(res.name);
					$('#locator').val(res.locator);
				},
				error: function() {
					$('#qth').val('');
					$('#locator').val('');
				},
			});
		}
	});
<?php } ?>

<?php if ($this->session->userdata('user_wwff_lookup') == 1) { ?>
	$('#wwff_ref').change(function() {
		var wwff = $('#wwff_ref').val();
		if (wwff.length > 0) {
			$.ajax({
				url: base_url+'index.php/qso/get_wwff_info',
				type: 'post',
				data: {'wwff': wwff},
				success: function(res) {
					$('#qth').val(res.name);
					$('#locator').val(res.locator);
				},
				error: function() {
					$('#qth').val('');
					$('#locator').val('');
				},
			});
		}
	});
<?php } ?>

<?php if ($this->session->userdata('user_pota_lookup') == 1) { ?>
	$('#pota_ref').change(function() {
		var pota = $('#pota_ref').val();
		if (pota.length > 0) {
			$.ajax({
				url: base_url+'index.php/qso/get_pota_info',
				type: 'post',
				data: {'pota': pota},
				success: function(res) {
					$('#qth').val(res.name);
					$('#locator').val(res.grid6);
				},
				error: function() {
					$('#qth').val('');
					$('#locator').val('');
				},
			});
		}
	});
<?php } ?>


<?php if ($this->session->userdata('user_qth_lookup') == 1) { ?>
    $('#qth').focusout(function() {
    	if ($('#locator').val() === '') {
			var lat = 0;
			var lon = 0;
			$.ajax({
				async: false,
				type: 'GET',
				dataType: "json",
				url: "https://nominatim.openstreetmap.org/?city=" + $(this).val() + "&format=json&addressdetails=1&limit=1",
				data: {},
				success: function (data) {
					if (typeof data[0].lat !== 'undefined') {
						lat = parseFloat(data[0].lat);
					}
					if (typeof data[0].lon !== 'undefined') {
						lon = parseFloat(data[0].lon);
					}
				},
			});
			if (lat !== 0 && lon !== 0) {
				var qthloc = LatLng2Loc(lat, lon, 10);
				if (qthloc.length > 0) {
					$('#locator').val(qthloc.substr(0, 6)).trigger('focusout');
				}
			}
		}
	});

	LatLng2Loc = function(y, x, num) {
		if (x < -180) {
			x = x + 360;
		}
		if (x > 180) {
			x = x - 360;
		}
		var yqth, yi, yk, ydiv, yres, ylp, y;
		var ycalc = new Array(0, 0, 0);
		var yn = new Array(0, 0, 0, 0, 0, 0, 0);

		var ydiv_arr = new Array(10, 1, 1 / 24, 1 / 240, 1 / 240 / 24);
		ycalc[0] = (x + 180) / 2;
		ycalc[1] = y + 90;

		for (yi = 0; yi < 2; yi++) {
			for (yk = 0; yk < 5; yk++) {
				ydiv = ydiv_arr[yk];
				yres = ycalc[yi] / ydiv;
				ycalc[yi] = yres;
				if (ycalc[yi] > 0) ylp = Math.floor(yres); else ylp = Math.ceil(yres);
				ycalc[yi] = (ycalc[yi] - ylp) * ydiv;
				yn[2 * yk + yi] = ylp;
			}
		}

		var qthloc = "";
		if (num >= 2) qthloc += String.fromCharCode(yn[0] + 0x41) + String.fromCharCode(yn[1] + 0x41);
		if (num >= 4) qthloc += String.fromCharCode(yn[2] + 0x30) + String.fromCharCode(yn[3] + 0x30);
		if (num >= 6) qthloc += String.fromCharCode(yn[4] + 0x41) + String.fromCharCode(yn[5] + 0x41);
		if (num >= 8) qthloc += ' ' + String.fromCharCode(yn[6] + 0x30) + String.fromCharCode(yn[7] + 0x30);
		if (num >= 10) qthloc += String.fromCharCode(yn[8] + 0x61) + String.fromCharCode(yn[9] + 0x61);
		return qthloc;
	}
	<?php } ?>

  </script>

<?php } ?>
<?php if ( $this->uri->segment(1) == "qso" || ($this->uri->segment(1) == "contesting" && $this->uri->segment(2) != "add")) { ?>
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/moment.min.js"></script>
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/datetime-moment.js"></script>

    <script>
    $( document ).ready(function() {
	    // Javascript for controlling rig frequency.
	    var updateFromCAT = function() {
		    var cat2UI = function(ui, cat, allow_empty, allow_zero, callback_on_update) {
			    // Check, if cat-data is available
			    if(cat == null) {
				    return;
			    } else if (typeof allow_empty !== 'undefined' && !allow_empty && cat == '') {
				    return;
			    } else if (typeof allow_zero !== 'undefined' && !allow_zero && cat == '0' ) {
				    return;
			    }
			    // Only update the ui-element, if cat-data has changed
			    if (ui.data('catValue') != cat) {
				    ui.val(cat);
				    ui.data('catValue',cat);
				    if (typeof callback_on_update === 'function') { callback_on_update(cat); }
			    }
		    }

		    if($('select.radios option:selected').val() != '0') {
			    radioID = $('select.radios option:selected').val();
			    if ((typeof radioID !== 'undefined') && (radioID !== null) && (radioID !== "")) {
				    $.getJSON( "radio/json/" + radioID, function( data ) {
	/* {
	"frequency": "2400210000",
	    "frequency_rx": "10489710000",
	    "mode": "SSB",
	    "satmode": "S/X",
	    "satname": "QO-100"
	    "power": "20"
	    "prop_mode": "SAT",
	    "error": "not_logged_id" // optional, reserved for errors
	}  */
					    if (data.error) {
						    if (data.error == 'not_logged_in') {
							    $(".radio_cat_state" ).remove();
							    if($('.radio_login_error').length == 0) {
								    $('.qso_panel').prepend('<div class="alert alert-danger radio_login_error" role="alert"><i class="fas fa-broadcast-tower"></i> ' + '<?= sprintf(__("You're not logged in. Please %slogin%s"), '<a href="' . base_url() . '">', '</a>'); ?>' + '</div>');
							    }
						    }
						    // Put future Errorhandling here
					    } else {
						    if($('.radio_login_error').length != 0) {
							    $(".radio_login_error" ).remove();
						    }
						    cat2UI($('#frequency'),data.frequency,false,true,function(d){
							    if ($("#band").val() != frequencyToBand(d)) {
								    $("#band").val(frequencyToBand(d)).trigger('change');	// Let's only change if we really have a different band!
							    }
						    });

						    cat2UI($('#frequency_rx'),data.frequency_rx,false,true,function(d){$("#band_rx").val(frequencyToBand(d))});
						    cat2UI($('.mode'),data.mode,false,false,function(d){setRst($(".mode").val())});
						    cat2UI($('#sat_name'),data.satname,false,false);
						    cat2UI($('#sat_mode'),data.satmode,false,false);
						    cat2UI($('#transmit_power'),data.power,false,false);
						    cat2UI($('#selectPropagation'),data.prop_mode,false,false);

						    // Display CAT Timeout warning based on the figure given in the config file
						    var minutes = Math.floor(<?php echo $this->optionslib->get_option('cat_timeout_interval'); ?> / 60);

						    if(data.updated_minutes_ago > minutes) {
							    $(".radio_cat_state" ).remove();
							    if($('.radio_timeout_error').length == 0) {
								    $('#radio_status').prepend('<div class="alert alert-danger radio_timeout_error" role="alert"><i class="fas fa-broadcast-tower"></i> Radio connection timed-out: ' + $('select.radios option:selected').text() + ' data is ' + data.updated_minutes_ago + ' minutes old.</div>');
							    } else {
								    $('.radio_timeout_error').html('Radio connection timed-out: ' + $('select.radios option:selected').text() + ' data is ' + data.updated_minutes_ago + ' minutes old.');
							    }
						    } else {
							    $(".radio_timeout_error" ).remove();
							    text = '<i class="fas fa-broadcast-tower"></i><span style="margin-left:10px;"></span><b>TX:</b> ' + data.frequency_formatted;
							    if(data.mode != null) {
								    text = text+'<span style="margin-left:10px"></span>'+data.mode;
							    }
							    if(data.power != null && data.power != 0) {
								    text = text+'<span style="margin-left:10px"></span>'+data.power+' W';
							    }
							    ptext = '';
							    if(data.prop_mode != null && data.prop_mode != '') {
								    ptext = ptext + data.prop_mode;
								    if (data.prop_mode == 'SAT') {
									    ptext = ptext + ' ' + data.satname;
								    }
							    }
							    if(data.frequency_rx != null && data.frequency_rx != 0) {
								    ptext = ptext + '<span style="margin-left:10px"></span><b>RX:</b> ' + data.frequency_rx_formatted;
							    }
							    if( ptext != '') { text = text + '<span style="margin-left:10px"></span>(' + ptext + ')';}
							    if (! $('#radio_cat_state').length) {
								    $('#radio_status').prepend('<div aria-hidden="true"><div id="radio_cat_state" class="alert alert-success radio_cat_state" role="alert">'+text+'</div></div>');
							    } else {
								    $('#radio_cat_state').html(text);
							    }
						    }
					    }
				    });
			    }
		    }
	    };

	    // Update frequency every three second
	    setInterval(updateFromCAT, 3000);

	    // If a radios selected from drop down select radio update.
	    $('.radios').change(updateFromCAT);

	    // If no radio is selected clear data
	    $( ".radios" ).change(function() {
		    if ($(".radios option:selected").val() == 0) {
			    $("#sat_name").val("");
			    $("#sat_mode").val("");
			    $("#frequency").val("");
			    $("#frequency_rx").val("");
			    $("#band_rx").val("");
			    $("#selectPropagation").val($("#selectPropagation option:first").val());
			    $(".radio_timeout_error" ).remove();
                $(".radio_cat_state" ).remove();
		    }
	    });
    });
  </script>

<?php } ?>

<?php if ($this->uri->segment(1) == "logbook" && $this->uri->segment(2) == "view") { ?>
<script>

  var mymap = L.map('map').setView([lat,long], 5);

  L.tileLayer('<?php echo $this->optionslib->get_option('option_map_tile_server');?>', {
    maxZoom: 18,
    attribution: '<?php echo $this->optionslib->get_option('option_map_tile_server_copyright');?>',
    id: 'mapbox.streets'
  }).addTo(mymap);



  var printer = L.easyPrint({
      		tileLayer: tiles,
      		sizeModes: ['Current', 'A4Landscape', 'A4Portrait'],
      		filename: 'myMap',
      		exportOnly: true,
      		hideControlContainer: true
		}).addTo(mymap);

  var redIcon = L.icon({
      iconUrl: icon_dot_url,
      iconSize:     [18, 18], // size of the icon
  });

  L.marker([lat,long], {icon: redIcon}).addTo(mymap)
    .bindPopup(callsign);

  mymap.on('click', onMapClick);

</script>
<?php } ?>

<?php if ($this->uri->segment(1) == "update") { ?>
<script>
$(document).ready(function(){
    $('#btn_update_dxcc').bind('click', function(){
		$("#btn_update_dxcc").addClass("running");
		$("#btn_update_dxcc").prop("disabled", true);
        $('#dxcc_update_status').show();
        $.ajax({
            url:"update/dxcc",
            success: function(response) {
                if (response == 'success') {
                    setTimeout(function() {
                        $("#btn_update_dxcc").removeClass("running");
                        $("#btn_update_dxcc").prop("disabled", false);
                    }, 2000);
                }
            }
        });
        setTimeout(update_stats,5000);
    });
    function update_stats(){
        $('#dxcc_update_status').load('<?php echo base_url()?>updates/status.html', function(val){
            $('#dxcc_update_staus').html(val);

            if ((val  === null) || (val.substring(0,4) !="DONE")){
                setTimeout(update_stats, 5000);
            } else {
				$("#btn_update_dxcc").removeClass("running");
				$("#btn_update_dxcc").prop("disabled", false);
			}
        });

    }

});
</script>

<?php } ?>

<?php if ($this->uri->segment(1) == "gridsquares" && !empty($this->uri->segment(2))) { ?>
<script>var gridsquaremap = true;</script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/leaflet/L.MaidenheadColoured.js"></script>

<script>

  var layer = L.tileLayer('<?php echo $this->optionslib->get_option('option_map_tile_server');?>', {
    maxZoom: 18,
    attribution: '<?php echo $this->optionslib->get_option('option_map_tile_server_copyright');?>',
    id: 'mapbox.streets'
  });

  var map = L.map('gridsquare_map', {
    layers: [layer],
    center: [19, 0],
    zoom: 2,
    minZoom: 1,
    fullscreenControl: true,
        fullscreenControlOptions: {
          position: 'topleft'
        },
  });

  var printer = L.easyPrint({
        tileLayer: layer,
        sizeModes: ['Current'],
        filename: 'myMap',
        exportOnly: true,
        hideControlContainer: true
    }).addTo(map);

  var grid_two = <?php echo $grid_2char; ?>;
  var grid_four = <?php echo $grid_4char; ?>;
  var grid_six = <?php echo $grid_6char; ?>;

  var grid_two_count = grid_two.length;
  var grid_four_count = grid_four.length;
  var grid_six_count = grid_six.length;

  var grid_two_confirmed = <?php echo $grid_2char_confirmed; ?>;
  var grid_four_confirmed = <?php echo $grid_4char_confirmed; ?>;
  var grid_six_confirmed = <?php echo $grid_6char_confirmed; ?>;

  var grid_two_confirmed_count = grid_two_confirmed.length;
  var grid_four_confirmed_count = grid_four_confirmed.length;
  var grid_six_confirmed_count = grid_six_confirmed.length;

  if (grid_four_confirmed_count > 0) {
     var span = document.getElementById('confirmed_grids');
     span.innerText = span.textContent = '('+grid_four_confirmed_count+" <?= __("grid square"); ?>"+(grid_four_confirmed_count != 1 ? 's' : '')+') ';
  }
  if ((grid_four_count-grid_four_confirmed_count) > 0) {
     var span = document.getElementById('worked_grids');
     span.innerText = span.textContent = '('+(grid_four_count-grid_four_confirmed_count)+" <?= __("grid square"); ?>"+(grid_four_count-grid_four_confirmed_count != 1 ? 's' : '')+') ';
  }
  var span = document.getElementById('sum_grids');
  span.innerText = span.textContent = " <?= __("Total count"); ?>"+': '+grid_four_count+" <?= __("grid square"); ?>"+(grid_four_count != 1 ? 's' : '');

  var maidenhead = L.maidenhead().addTo(map);

  map.on('click', onMapClick);

  function onMapClick(event) {
    var LatLng = event.latlng;
    var lat = LatLng.lat;
    var lng = LatLng.lng;
    var locator = LatLng2Loc(lat,lng, 10);
    var loc_4char = locator.substring(0, 4);

    if(map.getZoom() > 2) {
    	<?php if ($this->session->userdata('user_callsign')) { ?>
        spawnGridsquareModal(loc_4char);
		  <?php } ?>
    }
  };

  function spawnGridsquareModal(loc_4char) {
    var band = '';
      var search_type = "<?php echo $this->uri->segment(2); ?>";
      if(search_type == "satellites") {
		band = 'SAT';
      } else {
        band = "<?php echo $this->uri->segment(3); ?>";
      }
    $(".modal-body").empty();
		  $.ajax({
			  url: base_url + 'index.php/awards/qso_details_ajax',
			  type: 'post',
			  data: {
				  'Searchphrase': loc_4char,
				  'Band': band,
				  'Mode': 'All',
				  'Type': 'VUCC'
			  },
			  success: function (html) {
				$(".modal-body").html(html);
				  $(".modal-body table").addClass('table-sm');
				  $(".modal-body h5").empty();
				  var count = $('.table tr').length;
				  count = count - 1;
				  $('#qso_count').text(count);
				  if (count > 1) {
					  $('#gt1_qso').text("s");
				  } else {
					  $('#gt1_qso').text("");
				  }

				  if (count > 0) {
					  $('#square_number').text(loc_4char);
					  $('#exampleModal').modal('show');
					  $('[data-bs-toggle="tooltip"]').tooltip({ boundary: 'window' });
				  }
                    $('.table-responsive .dropdown-toggle').off('mouseenter').on('mouseenter', function () {
                        showQsoActionsMenu($(this).closest('.dropdown'));
                    });
			  }
		  });
  }

<?php if ($this->uri->segment(1) == "gridsquares" && $this->uri->segment(2) == "band") { ?>

  var bands_available = <?php echo $bands_available; ?>;
  $('#gridsquare_bands').append('<option value="All">'+"<?= __("All"); ?>"+'</option>')
  $.each(bands_available, function(key, value) {
     $('#gridsquare_bands')
         .append($("<option></option>")
                    .attr("value",value)
                    .text(value));
  });

  var num = "<?php echo $this->uri->segment(3);?>";
    $("#gridsquare_bands option").each(function(){
        if($(this).val()==num){ // EDITED THIS LINE
            $(this).attr("selected","selected");
        }
    });

  $(function(){
      // bind change event to select
      $('#gridsquare_bands').on('change', function () {
          var url = $(this).val(); // get selected value
          if (url) { // require a URL
              window.location = "<?php echo site_url('gridsquares/band/');?>" + url
          }
          return false;
      });
    });
<?php } ?>

</script>
<?php } ?>

<?php if ($this->uri->segment(1) == "activated_grids" && !empty($this->uri->segment(2))) { ?>

<script type="text/javascript" src="<?php echo base_url();?>assets/js/leaflet/L.MaidenheadColoured.js"></script>

<script>
  var layer = L.tileLayer('<?php echo $this->optionslib->get_option('option_map_tile_server');?>', {
    maxZoom: 18,
    attribution: '<?php echo $this->optionslib->get_option('option_map_tile_server_copyright');?>',
    id: 'mapbox.streets'
  });


  var map = L.map('gridsquare_map', {
    layers: [layer],
    center: [19, 0],
    zoom: 2,
    minZoom: 1,
    fullscreenControl: true,
        fullscreenControlOptions: {
          position: 'topleft'
        },
  });

  var grid_two = <?php echo $grid_2char; ?>;
  var grid_four = <?php echo $grid_4char; ?>;
  var grid_six = <?php echo $grid_6char; ?>;

  var grid_two_count = grid_two.length;
  var grid_four_count = grid_four.length;
  var grid_six_count = grid_six.length;

  var grid_two_confirmed = <?php echo $grid_2char_confirmed; ?>;
  var grid_four_confirmed = <?php echo $grid_4char_confirmed; ?>;
  var grid_six_confirmed = <?php echo $grid_6char_confirmed; ?>;

  var grid_two_confirmed_count = grid_two_confirmed.length;
  var grid_four_confirmed_count = grid_four_confirmed.length;
  var grid_six_confirmed_count = grid_six_confirmed.length;

  if (grid_four_confirmed_count > 0) {
     var span = document.getElementById('confirmed_grids');
     span.innerText = span.textContent = '('+grid_four_confirmed_count+" <?= __("grid square"); ?>"+(grid_four_confirmed_count != 1 ? 's' : '')+') ';
  }
  if ((grid_four_count-grid_four_confirmed_count) > 0) {
     var span = document.getElementById('activated_grids');
     span.innerText = span.textContent = '('+(grid_four_count-grid_four_confirmed_count)+" <?= __("grid square"); ?>"+(grid_four_count-grid_four_confirmed_count != 1 ? 's' : '')+') ';
  }
  var span = document.getElementById('sum_grids');
  span.innerText = span.textContent = " <?= __("Total count"); ?>"+': '+grid_four_count+" <?= __("grid square"); ?>"+(grid_four_count != 1 ? 's' : '');

  var maidenhead = L.maidenhead().addTo(map);

  map.on('click', onMapClick);

  function onMapClick(event) {
    var LatLng = event.latlng;
    var lat = LatLng.lat;
    var lng = LatLng.lng;
    var locator = LatLng2Loc(lat,lng, 10);
    var loc_4char = locator.substring(0, 4);

    if(map.getZoom() > 2) {
    	<?php if ($this->session->userdata('user_callsign')) { ?>
	  var band = '';
      var search_type = "<?php echo $this->uri->segment(2); ?>";
      if(search_type == "satellites") {
		band = 'SAT';
      } else {
        band = "<?php echo $this->uri->segment(3); ?>";
      }
		$(".modal-body").empty();
		  $.ajax({
			  url: base_url + 'index.php/activated_grids/qso_details_ajax',
			  type: 'post',
			  data: {
				  'Searchphrase': loc_4char,
				  'Band': band,
				  'Mode': 'All',
			  },
			  success: function (html) {
				$(".modal-body").html(html);
				  $(".modal-body table").addClass('table-sm');
				  $(".modal-body h5").empty();
				  var count = $('.table tr').length;
				  count = count - 1;
				  $('#qso_count').text(count);
				  if (count > 1) {
					  $('#gt1_qso').text("s");
				  } else {
					  $('#gt1_qso').text("");
				  }

				  if (count > 0) {
					  $('#square_number').text(loc_4char);
					  $('#exampleModal').modal('show');
					  $('[data-bs-toggle="tooltip"]').tooltip({ boundary: 'window' });
				  }
                    $('.table-responsive .dropdown-toggle').off('mouseenter').on('mouseenter', function () {
                        showQsoActionsMenu($(this).closest('.dropdown'));
                    });
			  }
		  });
		  <?php } ?>
    }
  };

<?php if ($this->uri->segment(1) == "activated_grids" && $this->uri->segment(2) == "band") { ?>

  var bands_available = <?php echo $bands_available; ?>;
  $('#gridsquare_bands').append('<option value="All">'+"<?= __("All"); ?>"+'</option>')
  $.each(bands_available, function(key, value) {
     $('#gridsquare_bands')
         .append($("<option></option>")
                    .attr("value",value)
                    .text(value));
  });

  var num = "<?php echo $this->uri->segment(3);?>";
    $("#gridsquare_bands option").each(function(){
        if($(this).val()==num){ // EDITED THIS LINE
            $(this).attr("selected","selected");
        }
    });

  $(function(){
      // bind change event to select
      $('#gridsquare_bands').on('change', function () {
          var url = $(this).val(); // get selected value
          if (url) { // require a URL
              window.location = "<?php echo site_url('activated_grids/band/');?>" + url
          }
          return false;
      });
    });
<?php } ?>

</script>
<?php } ?>

<?php if ($this->uri->segment(1) == "dayswithqso") { ?>
    <script src="<?php echo base_url(); ?>assets/js/chart.js"></script>
	<script src="<?php echo base_url(); ?>assets/js/sections/dayswithqso.js"></script>
<?php } ?>

<?php if ($this->uri->segment(1) == "distances") { ?>
    <script src="<?php echo base_url(); ?>assets/js/highstock.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/highstock/exporting.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/highstock/offline-exporting.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/highstock/export-data.js"></script>
	<script src="<?php echo base_url(); ?>assets/js/sections/distances.js"></script>
<?php } ?>


    <?php if ($this->uri->segment(1) == "hrdlog") { ?>
		<script src="<?php echo base_url(); ?>assets/js/sections/hrdlog.js"></script>
    <?php } ?>
    <?php if ($this->uri->segment(1) == "qrz") { ?>
		<script src="<?php echo base_url(); ?>assets/js/sections/qrzlogbook.js"></script>
    <?php } ?>
	<?php if ($this->uri->segment(1) == "webadif") { ?>
		<script src="<?php echo base_url(); ?>assets/js/sections/webadif.js"></script>
	<?php } ?>

<?php if ($this->uri->segment(2) == "dxcc") { ?>
<script>
    $('.tabledxcc').DataTable({
        "pageLength": 25,
        responsive: false,
        ordering: false,
        "scrollY":        "400px",
        "scrollCollapse": true,
        "paging":         false,
        "scrollX": true,
        "language": {
            url: getDataTablesLanguageUrl(),
        },
        dom: 'Bfrtip',
        buttons: [
            'csv'
        ]
    });

    $('.tablesummary').DataTable({
        info: false,
        searching: false,
        ordering: false,
        "paging":         false,
        "language": {
            url: getDataTablesLanguageUrl(),
        },
        dom: 'Bfrtip',
        "language": {
            url: getDataTablesLanguageUrl(),
        },
        buttons: [
            'csv'
        ]
    });

    // change color of csv-button if dark mode is chosen
    if (isDarkModeTheme()) {
        $(".buttons-csv").css("color", "white");
    }
 </script>
    <?php } ?>

<?php if ($this->uri->segment(2) == "waja") { ?>
<script>
    $('.tablewaja').DataTable({
        "pageLength": 25,
        responsive: false,
        ordering: false,
        "scrollY":        "400px",
        "scrollCollapse": true,
        "paging":         false,
        "scrollX": true,
        "language": {
            url: getDataTablesLanguageUrl(),
        },
        dom: 'Bfrtip',
        buttons: [
            'csv'
        ]
    });

    $('.tablesummary').DataTable({
        info: false,
        searching: false,
        ordering: false,
        "paging":         false,
        "language": {
            url: getDataTablesLanguageUrl(),
        },
        dom: 'Bfrtip',
        "language": {
            url: getDataTablesLanguageUrl(),
        },
        buttons: [
            'csv'
        ]
    });

    // change color of csv-button if dark mode is chosen
    if (isDarkModeTheme()) {
        $(".buttons-csv").css("color", "white");
    }
 </script>
<?php } ?>

<?php if ($this->uri->segment(2) == "helvetia") { ?>
<script>
    $('.tablehelvetia').DataTable({
        "pageLength": 25,
        responsive: false,
        ordering: false,
        "scrollY":        "400px",
        "scrollCollapse": true,
        "paging":         false,
        "scrollX": true,
        "language": {
            url: getDataTablesLanguageUrl(),
        },
        dom: 'Bfrtip',
        buttons: [
            'csv'
        ]
    });

    $('.tablesummary').DataTable({
        info: false,
        searching: false,
        ordering: false,
        "paging":         false,
        "language": {
            url: getDataTablesLanguageUrl(),
        },
        dom: 'Bfrtip',
        "language": {
            url: getDataTablesLanguageUrl(),
        },
        buttons: [
            'csv'
        ]
    });

    // change color of csv-button if dark mode is chosen
    if (isDarkModeTheme()) {
        $(".buttons-csv").css("color", "white");
    }
 </script>
<?php } ?>

<?php if ($this->uri->segment(2) == "vucc_band") { ?>
    <script>
    $('.tablevucc').DataTable({
        "pageLength": 25,
        responsive: false,
        ordering: false,
        "scrollY":        "400px",
        "scrollCollapse": true,
        "paging":         false,
        "scrollX": true,
        "language": {
            url: getDataTablesLanguageUrl(),
        },
        dom: 'Bfrtip',
        buttons: [
            'csv'
        ]
    });

    // change color of csv-button if dark mode is chosen
    if (isDarkModeTheme()) {
        $(".buttons-csv").css("color", "white");
    }
    </script>
<?php } ?>

<?php if ($this->uri->segment(2) == "iota") { ?>
    <script>

        $('.tableiota').DataTable({
            "pageLength": 25,
            responsive: false,
            ordering: false,
            "scrollY":        "400px",
            "scrollCollapse": true,
            "paging":         false,
            "scrollX": true,
            "language": {
                url: getDataTablesLanguageUrl(),
            },
            dom: 'Bfrtip',
            buttons: [
                'csv'
            ]
        });

        $('.tablesummary').DataTable({
            info: false,
            searching: false,
            ordering: false,
            "paging":         false,
            "language": {
                url: getDataTablesLanguageUrl(),
            },
            dom: 'Bfrtip',
            "language": {
                url: getDataTablesLanguageUrl(),
            },
            buttons: [
                'csv'
            ]
        });

        // change color of csv-button if dark mode is chosen
        if (isDarkModeTheme()) {
            $(".buttons-csv").css("color", "white");
        }
    </script>

<?php } ?>

<?php if ($this->uri->segment(2) == "cq") { ?>
    <script>
        $('.tablecq').DataTable({
            "pageLength": 25,
            responsive: false,
            ordering: false,
            "scrollY":        "400px",
            "scrollCollapse": true,
            "paging":         false,
            "scrollX": true,
            "language": {
                url: getDataTablesLanguageUrl(),
            },
            dom: 'Bfrtip',
            buttons: [
                'csv'
            ]
        });

        $('.tablesummary').DataTable({
            info: false,
            searching: false,
            ordering: false,
            "paging":         false,
            "language": {
                url: getDataTablesLanguageUrl(),
            },
            dom: 'Bfrtip',
            "language": {
                url: getDataTablesLanguageUrl(),
            },
            buttons: [
                'csv'
            ]
        });

        // change color of csv-button if dark mode is chosen
        if (isDarkModeTheme()) {
            $(".buttons-csv").css("color", "white");
        }
    </script>
<?php } ?>

<?php if ($this->uri->segment(2) == "was") { ?>
    <script>
        $('.tablewas').DataTable({
            "pageLength": 25,
            responsive: false,
            ordering: false,
            "scrollY":        "400px",
            "scrollCollapse": true,
            "paging":         false,
            "scrollX": true,
            "language": {
                url: getDataTablesLanguageUrl(),
            },
            dom: 'Bfrtip',
            buttons: [
                'csv'
            ]
        });

        $('.tablesummary').DataTable({
            info: false,
            searching: false,
            ordering: false,
            "paging":         false,
            "language": {
                url: getDataTablesLanguageUrl(),
            },
            dom: 'Bfrtip',
            "language": {
                url: getDataTablesLanguageUrl(),
            },
            buttons: [
                'csv'
            ]
        });

        // change color of csv-button if dark mode is chosen
        if (isDarkModeTheme()) {
            $(".buttons-csv").css("color", "white");
        }
    </script>
<?php } ?>


<script>
    var reload_after_qso_safe = false;
    <?php if (
	$this->uri->segment(1) != "search" &&
	$this->uri->segment(2) != "filter" &&
	$this->uri->segment(1) != "qso" &&
	$this->uri->segment(1) != "logbookadvanced") { ?>
		reload_after_qso_safe = true;
	<?php } ?>
</script>

    <?php if ($this->uri->segment(1) == "timeline") { ?>
        <script>
         $.fn.dataTable.ext.buttons.clear = {
               className: 'buttons-clear',
               action: function ( e, dt, node, config ) {
                  dt.search('');
                  dt.draw();
               }
            };
            $('.timelinetable').DataTable({
                "pageLength": 25,
                responsive: false,
                ordering: false,
                "scrollY":        "500px",
                "scrollCollapse": true,
                "paging":         false,
                "scrollX": true,
                "language": {
                    url: getDataTablesLanguageUrl(),
                },
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'csv'
                    },
                    {
                        extend: 'clear',
                        text: lang_admin_clear
                    }
                ]
            });

            // change color of csv-button if dark mode is chosen
            if (isDarkModeTheme()) {
                $(".buttons-csv").css("color", "white");
            }

            function displayTimelineContacts(querystring, band, mode, propmode, type) {
                var baseURL= "<?php echo base_url();?>";
                $.ajax({
                    url: baseURL + 'index.php/timeline/details',
                    type: 'post',
                    data: {'Querystring': querystring,
                        'Band': band,
                        'Mode': mode,
                        'Propmode': propmode,
                        'Type': type
                    },
                    success: function(html) {
                        BootstrapDialog.show({
                            title: lang_general_word_qso_data,
                            size: BootstrapDialog.SIZE_WIDE,
                            cssClass: 'qso-was-dialog',
                            nl2br: false,
                            message: html,
                            onshown: function(dialog) {
                               $('[data-bs-toggle="tooltip"]').tooltip();
                               $('.table-responsive .dropdown-toggle').off('mouseenter').on('mouseenter', function () {
                                    showQsoActionsMenu($(this).closest('.dropdown'));
                                });
                            },
                            buttons: [{
                                label: lang_admin_close,
                                action: function (dialogItself) {
                                    dialogItself.close();
                                }
                            }]
                        });
                    }
                });
            }
        </script>
        <?php } ?>


    <?php if ($this->uri->segment(1) == "mode") { ?>
		<script src="<?php echo base_url(); ?>assets/js/sections/mode.js"></script>
    <?php } ?>

    <?php if ($this->uri->segment(1) == "band") { ?>
		<script src="<?php echo base_url(); ?>assets/js/sections/bands.js"></script>
    <?php } ?>

<?php if ($this->uri->segment(1) == "accumulated") { ?>
    <script src="<?php echo base_url(); ?>assets/js/chart.js"></script>
	<script src="<?php echo base_url(); ?>assets/js/sections/accumulatedstatistics.js"></script>
<?php } ?>

<?php if ($this->uri->segment(1) == "timeplotter") { ?>
    <script src="<?php echo base_url(); ?>assets/js/highstock.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/highstock/exporting.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/highstock/offline-exporting.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/highstock/export-data.js"></script>
	<script src="<?php echo base_url(); ?>assets/js/sections/timeplot.js"></script>
<?php } ?>

<?php if ($this->uri->segment(1) == "qsl" || $this->uri->segment(1) == "eqsl") {
    	// Get Date format
	if($this->session->userdata('user_date_format')) {
		// If Logged in and session exists
		$custom_date_format = $this->session->userdata('user_date_format');
	} else {
		// Get Default date format from /config/wavelog.php
		$custom_date_format = $this->config->item('qso_date_format');
	}

    switch ($custom_date_format) {
        case 'd/m/y': $usethisformat = 'D/MM/YY';break;
        case 'd/m/Y': $usethisformat = 'D/MM/YYYY';break;
        case 'm/d/y': $usethisformat = 'MM/D/YY';break;
        case 'm/d/Y': $usethisformat = 'MM/D/YYYY';break;
        case 'd.m.Y': $usethisformat = 'D.MM.YYYY';break;
        case 'y/m/d': $usethisformat = 'YY/MM/D';break;
        case 'Y-m-d': $usethisformat = 'YYYY-MM-D';break;
        case 'M d, Y': $usethisformat = 'MMM D, YYYY';break;
        case 'M d, y': $usethisformat = 'MMM D, YY';break;
    }

    ?>
<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/moment.min.js"></script>
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/datetime-moment.js"></script>
    <script>
        $.fn.dataTable.moment('<?php echo $usethisformat ?>');
        $.fn.dataTable.ext.buttons.clear = {
            className: 'buttons-clear',
            action: function ( e, dt, node, config ) {
               dt.search('').draw();
            }
        };
    </script>
    <?php if ($this->uri->segment(1) == "qsl") {
        $qsl_eqsl_table = '.qsltable';
    } else if ($this->uri->segment(1) == "eqsl") {
        $qsl_eqsl_table = '.eqsltable';
    } ?>
    <script>
        $('<?php echo $qsl_eqsl_table ?>').DataTable({
            "pageLength": 25,
            responsive: false,
            ordering: true,
            "scrollY":        "500px",
            "scrollCollapse": true,
            "paging":         false,
            "scrollX": true,
            "language": {
                url: getDataTablesLanguageUrl(),
            },
            "order": [ 2, 'desc' ],
            dom: 'Bfrtip',
            buttons: [
               {
                  extend: 'clear',
                  text: lang_admin_clear
               }
            ]
        });
        // change color of csv-button if dark mode is chosen
        if (isDarkModeTheme()) {
            $('[class*="buttons"]').css("color", "white");
        }

    </script>
<?php } ?>


<script>
function viewQsl(picture, callsign) {

            var webpath_qsl = "<?php echo $this->paths->getPathQsl(); ?>";
            var textAndPic = $('<div class="text-center"></div>');
                textAndPic.append('<img class="img-fluid w-qsl" style="height:auto;width:auto;"src="'+base_url+webpath_qsl+'/'+picture+'" />');
            var title = '';
            if (callsign == null) {
                title = "<?= __("QSL Card"); ?>";
            } else {
                title = "<?= __("QSL Card for "); ?>" + callsign.replace('0', '&Oslash;');
            }

            BootstrapDialog.show({
                title: title,
                size: BootstrapDialog.SIZE_WIDE,
                message: textAndPic,
                buttons: [{
                    label: lang_admin_close,
                    action: function(dialogRef){
                        dialogRef.close();
                    }
                }]
            });
        }
</script>
<script>
function deleteQsl(id) {
            BootstrapDialog.confirm({
                title: "<?= __("DANGER"); ?>",
                message: "<?= __("Warning! Are you sure you want to delete this QSL card?"); ?>"  ,
                type: BootstrapDialog.TYPE_DANGER,
                closable: true,
                draggable: true,
                btnOKClass: 'btn-danger',
                callback: function(result) {
                    if(result) {
                        $.ajax({
                            url: base_url + 'index.php/qsl/delete',
                            type: 'post',
                            data: {'id': id
                            },
                            success: function(data) {
                                $("#" + id).parent("tr:first").remove(); // removes qsl from table

                                // remove qsl from carousel
                                $(".carousel-indicators li:last-child").remove();
                                $(".carouselimageid_"+id).remove();
                                $('#carouselExampleIndicators').find('.carousel-item').first().addClass('active');

                                // remove table and hide tab if all qsls are deleted
                                if ($('.qsltable tr').length == 1) {
                                    $('.qsltable').remove();
                                    $('.qslcardtab').attr('hidden','');
                                }
                            }
                        });
                    }
                }
            });
        }
</script>
<script>
function viewEqsl(picture, callsign) {
            var webpath_eqsl = '<?php echo $this->paths->getPathEqsl(); ?>';
            var baseURL= "<?php echo base_url();?>";
            var $textAndPic = $('<div></div>');
                $textAndPic.append('<img class="img-fluid" style="height:auto;width:auto;"src="'+baseURL+webpath_eqsl+'/'+picture+'" />');
            var title = '';
            if (callsign == null) {
                title = "<?= __("eQSL Card"); ?>";
            } else {
                title = "<?= __("eQSL Card for "); ?>" + callsign.replace('0', '&Oslash;');
            }

            BootstrapDialog.show({
                title: title,
                size: BootstrapDialog.SIZE_WIDE,
                message: $textAndPic,
                buttons: [{
                    label: lang_admin_close,
                    action: function(dialogRef){
                        dialogRef.close();
                    }
                }]
            });
        }
</script>
<script>
    $('#displayAwardInfo').click(function (event) {
        var awardInfoLines = [
            lang_award_info_ln2,
            lang_award_info_ln3,
            lang_award_info_ln4
        ];
        var awardInfoContent = "";
        awardInfoLines.forEach(function (line) {
            awardInfoContent += line + "<br><br>";
        });
        BootstrapDialog.alert({
            title: "<h4>"+lang_award_info_ln1+"</h4>",
            message: awardInfoContent,
        });
    });
</script>
<script>
  /*
   * Used to fetch QSOs from the logbook in the awards
   */
    function displayContacts(searchphrase, band, sat, orbit, mode, type, qsl) {
        $.ajax({
            url: base_url + 'index.php/awards/qso_details_ajax',
            type: 'post',
            data: {
                'Searchphrase': searchphrase,
                'Band': band,
                'Sat': sat,
                'Orbit': orbit,
                'Mode': mode,
                'Type': type,
                'QSL' : qsl
            },
            success: function (html) {
                BootstrapDialog.show({
                    title: lang_general_word_qso_data,
                    size: BootstrapDialog.SIZE_WIDE,
                    cssClass: 'qso-dialog',
                    nl2br: false,
                    message: html,
                    onshown: function(dialog) {
                       $('[data-bs-toggle="tooltip"]').tooltip();
                       $('.contacttable').DataTable({
                            "pageLength": 7,
                            responsive: false,
                            ordering: false,
                            "scrollY":        "550px",
                            "scrollCollapse": true,
                            "paging":         true,
                            "scrollX": true,
                            "language": {
                                url: getDataTablesLanguageUrl(),
                            },
                            dom: 'Bfrtip',
                            buttons: [
                                'csv'
                            ]
                        });
                        $('.table-responsive .dropdown-toggle').off('mouseenter').on('mouseenter', function () {
                            showQsoActionsMenu($(this).closest('.dropdown'));
                        });
                    },
                    buttons: [{
                        label: lang_admin_close,
                        action: function (dialogItself) {
                            dialogItself.close();
                        }
                    }]
                });
            }
        });
    }

    function displayContactsOnMap(target, searchphrase, band, sat, orbit, mode, type, qsl) {
	    $.ajax({
	    url: base_url + 'index.php/awards/qso_details_ajax',
		    type: 'post',
		    data: {
		    'Searchphrase': searchphrase,
			    'Band': band,
			    'Sat': sat,
			    'Orbit': orbit,
			    'Mode': mode,
			    'Type': type,
			    'QSL' : qsl
        },
	    success: function (html) {
		    var dialog = new BootstrapDialog({
		    title: lang_general_word_qso_data,
			    size: BootstrapDialog.SIZE_WIDE,
			    cssClass: 'qso-dialog',
			    nl2br: false,
			    message: html,
			    onshown: function(dialog) {
				    $('[data-bs-toggle="tooltip"]').tooltip();
				    $('.contacttable').DataTable({
				    "pageLength": 25,
					    responsive: false,
					    ordering: false,
					    "scrollY":        "550px",
					    "scrollCollapse": true,
					    "paging":         false,
					    "scrollX": true,
                        "language": {
                            url: getDataTablesLanguageUrl(),
                        },
					    dom: 'Bfrtip',
					    buttons: [
						    'csv'
					    ]
				    });
                    $('.table-responsive .dropdown-toggle').off('mouseenter').on('mouseenter', function () {
                        showQsoActionsMenu($(this).closest('.dropdown'));
                    });
			    },
			    buttons: [{
			    label: lang_admin_close,
				    action: function (dialogItself) {
					    dialogItself.close();
				    }
				    }]
	    });
		    dialog.realize();
		    target.append(dialog.getModal());
		    dialog.open();
	    }
    });
    }

    function uploadQsl() {
        var webpath_qsl = "<?php echo $this->paths->getPathQsl(); ?>";
        var formdata = new FormData(document.getElementById("fileinfo"));

        $.ajax({
            url: base_url + 'index.php/qsl/uploadqsl',
            type: 'post',
            data: formdata,
            enctype: 'multipart/form-data',
            processData: false,
            contentType: false,
            success: function(data) {
                if (data.status.front.status == 'Success') {
                    if ($('.qsltable').length > 0) {
                        $('.qsltable tr:last').after('<tr><td style="text-align: center">'+data.status.front.filename+'</td>' +
                            '<td id="'+data.status.front.insertid+'"style="text-align: center"><button onclick="deleteQsl('+data.status.front.insertid+');" class="btn btn-sm btn-danger">'+"<?= __("Delete"); ?>"+'</button></td>' +
                            '<td style="text-align: center"><button onclick="viewQsl(\'' + data.status.front.filename + '\')" class="btn btn-sm btn-success">'+"<?= __("View"); ?>"+'</button></td>'+
                            '</tr>');
                        var quantity = $(".carousel-indicators li").length;
                        $(".carousel-indicators").append('<li data-bs-target="#carouselExampleIndicators" data-bs-slide-to="'+quantity+'"></li>');
                        $(".carousel-inner").append('<div class="text-center carousel-item carouselimageid_'+data.status.front.insertid+'"><img class="img-fluid w-qsl" src="'+base_url+'/'+webpath_qsl+'/'+data.status.front.filename+'" alt="QSL picture #'+(quantity+1)+'"></div>');
                        $("#qslcardfront").val(null);
                    }
                    else {
                        $("#qslupload").prepend('<table style="width:100%" class="qsltable table table-sm table-bordered table-hover table-striped table-condensed">'+
                            '<thead>'+
                               '<tr>'+
                            '<th style="text-align: center">'+"<?= __("QSL image file"); ?>"+'</th>'+
                            '<th style="text-align: center"></th>'+
                            '<th style="text-align: center"></th>'+
                            '</tr>'+
                            '</thead><tbody>'+
                                '<tr><td style="text-align: center">'+data.status.front.filename+'</td>' +
                            '<td id="'+data.status.front.insertid+'"style="text-align: center"><button onclick="deleteQsl('+data.status.front.insertid+');" class="btn btn-sm btn-danger">'+"<?= __("Delete"); ?>"+'</button></td>' +
                            '<td style="text-align: center"><button onclick="viewQsl(\'' + data.status.front.filename + '\')" class="btn btn-sm btn-success">'+"<?= __("View"); ?>"+'</button></td>'+
                            '</tr>'+
                        '</tbody></table>');
                        $('.qslcardtab').removeAttr('hidden');
                        var quantity = $(".carousel-indicators li").length;
                        $(".carousel-indicators").append('<li class="active" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="'+quantity+'"></li>');
                        $(".carousel-inner").append('<div class="text-center active carousel-item carouselimageid_'+data.status.front.insertid+'"><img class="img-fluid w-qsl" src="'+base_url+'/'+webpath_qsl+'/'+data.status.front.filename+'" alt="QSL picture #'+(quantity+1)+'"></div>');
                        $(".carouselExampleIndicators").carousel();
                        $("#qslcardfront").val(null);
                    }

                } else if (data.status.front.status != '') {
                    $("#qslupload").append('<div class="alert alert-danger">'+"<?= __("Front QSL Card:"); ?>  " +
                    data.status.front.error +
                        '</div>');
                }
                if (data.status.back.status == 'Success') {
                    var qsoid = $("#qsoid").text();
                    if ($('.qsltable').length > 0) {
                        $('.qsltable tr:last').after('<tr><td style="text-align: center">'+data.status.back.filename+'</td>' +
                            '<td id="'+data.status.back.insertid+'"style="text-align: center"><button onclick="deleteQsl('+data.status.back.insertid+');" class="btn btn-sm btn-danger">'+"<?= __("Delete"); ?>"+'</button></td>' +
                            '<td style="text-align: center"><button onclick="viewQsl(\'' + data.status.back.filename + '\')" class="btn btn-sm btn-success">'+"<?= __("View"); ?>"+'</button></td>'+
                            '</tr>');
                        var quantity = $(".carousel-indicators li").length;
                        $(".carousel-indicators").append('<li data-bs-target="#carouselExampleIndicators" data-bs-slide-to="'+quantity+'"></li>');
                        $(".carousel-inner").append('<div class="text-center carousel-item carouselimageid_'+data.status.back.insertid+'"><img class="img-fluid w-qsl" src="'+base_url+'/'+webpath_qsl+'/'+data.status.back.filename+'" alt="QSL picture #'+(quantity+1)+'"></div>');
                        $("#qslcardback").val(null);
                    }
                    else {
                        $("#qslupload").prepend('<table style="width:100%" class="qsltable table table-sm table-bordered table-hover table-striped table-condensed">'+
                            '<thead>'+
                            '<tr>'+
                            '<th style="text-align: center">'+"<?= __("QSL image file"); ?>"+'</th>'+
                            '<th style="text-align: center"></th>'+
                            '<th style="text-align: center"></th>'+
                            '</tr>'+
                            '</thead><tbody>'+
                            '<tr><td style="text-align: center">'+data.status.back.filename+'</td>' +
                            '<td id="'+data.status.back.insertid+'"style="text-align: center"><button onclick="deleteQsl('+data.status.back.insertid+');" class="btn btn-sm btn-danger">'+"<?= __("Delete"); ?>"+'</button></td>' +
                            '<td><button onclick="viewQsl(\'' + data.status.back.filename + '\')" class="btn btn-sm btn-success">'+"<?= __("View"); ?>"+'</button></td>'+
                            '</tr>'+
                            '</tbody></table>');
                        $('.qslcardtab').removeAttr('hidden');
                        var quantity = $(".carousel-indicators li").length;
                        $(".carousel-indicators").append('<li class="active" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="'+quantity+'"></li>');
                        $(".carousel-inner").append('<div class="text-center active carousel-item carouselimageid_'+data.status.back.insertid+'"><img class="img-fluid w-qsl" src="'+base_url+'/'+webpath_qsl+'/'+data.status.back.filename+'" alt="QSL picture #'+(quantity+1)+'"></div>');
                        $(".carouselExampleIndicators").carousel();
                        $("#qslcardback").val(null);
                    }
                } else if (data.status.back.status != '') {
                    $("#qslupload").append('<div class="alert alert-danger">\n'+"<?= __("Back QSL Card:"); ?>  " +
                    data.status.back.error +
                        '</div>');
                }
            }
        });
    }
</script>
<script>

	function addQsosToQsl(filename) {
		var title = "<?= __("Add additional QSOs to a QSL Card"); ?>";

		$.ajax({
			url: base_url + 'index.php/qsl/loadSearchForm',
			type: 'post',
			data: {'filename': filename},
			success: function(html) {
				BootstrapDialog.show({
					title: title,
					size: BootstrapDialog.SIZE_WIDE,
					cssClass: 'qso-search_results',
					nl2br: false,
					message: html,
					buttons: [{
						label: lang_admin_close,
						action: function (dialogItself) {
							dialogItself.close();
						}
					}]
				});
			}
		});
	}

	function addQsoToQsl(qsoid, filename, id) {
		var title = "<?= __("Add additional QSOs to a QSL Card"); ?>";

		$.ajax({
			url: base_url + 'index.php/qsl/addQsoToQsl',
			type: 'post',
			data: {'filename': filename, 'qsoid': qsoid},
			success: function(html) {
				if (html.status == 'Success') {
					location.reload();
				} else {
					$(".alert").remove();
					$('#searchresult').prepend('<div class="alert alert-danger">'+"<?= __("Something went wrong. Please try again!"); ?>"+'</div>');
				}
			}
		});
	}

	function searchAdditionalQsos(filename) {
		$.ajax({
			url: base_url + 'index.php/qsl/searchQsos',
			type: 'post',
			data: {'callsign': $('#callsign').val(), 'filename': filename},
			success: function(html) {
				$('#searchresult').empty();
				$('#searchresult').append(html);
			}
		});
	}
</script>
<?php if ($this->uri->segment(1) == "contesting" && ($this->uri->segment(2) != "add" && $this->uri->segment(2) != "edit")) { ?>
    <script>
        var manual = <?php echo $manual_mode; ?>;
    </script>
<?php } ?>

<?php if ($this->uri->segment(2) == "counties" || $this->uri->segment(2) == "counties_details") { ?>
<script>
    $('.countiestable').DataTable({
        "pageLength": 25,
        responsive: false,
        ordering: false,
        "scrollY":        "390px",
        "scrollCollapse": true,
        "paging":         false,
        "scrollX": true,
        "language": {
            url: getDataTablesLanguageUrl(),
        },
        dom: 'Bfrtip',
        buttons: [
            'csv'
        ]
    });

    // change color of csv-button if dark mode is chosen
    if (isDarkModeTheme()) {
        $(".buttons-csv").css("color", "white");
    }

    function displayCountyContacts(state, county) {
        var baseURL= "<?php echo base_url();?>";
        $.ajax({
            url: baseURL + 'index.php/awards/counties_details_ajax',
            type: 'post',
            data: {'State': state, 'County': county },
            success: function(html) {
                BootstrapDialog.show({
                    title: lang_general_word_qso_data,
                    size: BootstrapDialog.SIZE_WIDE,
                    cssClass: 'qso-counties-dialog',
                    nl2br: false,
                    message: html,
                    onshown: function(dialog) {
                       $('[data-bs-toggle="tooltip"]').tooltip();
                    },
                    buttons: [{
                        label: lang_admin_close,
                        action: function (dialogItself) {
                            dialogItself.close();
                        }
                    }]
                });
            }
        });
    }
</script>
<?php } ?>

<?php if ($this->uri->segment(2) == "sig_details") { ?>
	<script>
		$('.tablesig').DataTable({
			"pageLength": 25,
			responsive: false,
			ordering: false,
			"scrollY":        "400px",
			"scrollCollapse": true,
			"paging":         false,
			"scrollX": true,
			"language": {
				url: getDataTablesLanguageUrl(),
			},
			dom: 'Bfrtip',
			buttons: [
				'csv'
			]
		});

		// change color of csv-button if dark mode is chosen
		if (isDarkModeTheme()) {
			$(".buttons-csv").css("color", "white");
		}
	</script>
<?php } ?>

<?php if ($this->uri->segment(1) == "contesting" && $this->uri->segment(2) == "add") { ?>
	<script src="<?php echo base_url() ;?>assets/js/sections/contestingnames.js"></script>
<?php } ?>

<?php if ($this->uri->segment(1) == "themes") { ?>
    <script src="<?php echo base_url() ;?>assets/js/sections/themes.js"></script>
<?php } ?>


<?php if ($this->uri->segment(1) == "eqsl") { ?>
	<script>
	$('.qsotable').DataTable({
		"stateSave": true,
		"pageLength": 25,
		responsive: false,
		"scrollY": "400px",
		"scrollCollapse": true,
		"paging": false,
		"scrollX": true,
        "language": {
            url: getDataTablesLanguageUrl(),
        },
		"ordering": true,
		"order": [ 0, 'desc' ],
	});
	</script>
<?php } ?>

<?php if ($this->uri->segment(1) == "distancerecords") { ?>
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/moment.min.js"></script>
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/datetime-moment.js"></script>
        <script>
            $.fn.dataTable.moment('<?php echo $usethisformat ?>');
            $.fn.dataTable.ext.buttons.clear = {
                className: 'buttons-clear',
                action: function ( e, dt, node, config ) {
                   dt.search('').draw();
                }
            };
            $.fn.dataTable.ext.type.order['distance-pre'] = function(data) {
               var num = parseFloat(data);
               return isNaN(num) ? 0 : num;
            };
            $('#distrectable').on('order.dt search.dt', function() {
               var disttable = $('#distrectable').DataTable();
               let i = 1;
               disttable
                  .cells(null, 0, { search: 'applied', order: 'applied' })
                  .every(function (cell) {
                     this.data(i++);
                  });
            });
            $('#distrectable').DataTable({
                "pageLength": 25,
                responsive: false,
                ordering: true,
                "columnDefs": [
                   {
                      2: 'num'
                   },
                   {
                      "targets": $(".distance-column-sort").index(),
                      "type": "distance",
                   }
                ],
                "scrollCollapse": true,
                "paging":         false,
                "scrollX": true,
                "language": {
                    url: getDataTablesLanguageUrl(),
                },
                "order": [ 2, 'desc' ],
                dom: 'Bfrtip',
                buttons: [
                   {
                      extend: 'csv'
                   },
                   {
                      extend: 'clear',
                      text: lang_admin_clear
                   }
                ]
            });
            // change color of csv-button if dark mode is chosen
            if (isDarkModeTheme()) {
               $('[class*="buttons"]').css("color", "white");
            }
        </script>
<?php } ?>

<?php if ($this->uri->segment(1) == "awards") {
	// Get Date format
	if($this->session->userdata('user_date_format')) {
		// If Logged in and session exists
		$custom_date_format = $this->session->userdata('user_date_format');
	} else {
		// Get Default date format from /config/wavelog.php
		$custom_date_format = $this->config->item('qso_date_format');
	}

    switch ($custom_date_format) {
        case 'd/m/y': $usethisformat = 'D/MM/YY';break;
        case 'd/m/Y': $usethisformat = 'D/MM/YYYY';break;
        case 'm/d/y': $usethisformat = 'MM/D/YY';break;
        case 'm/d/Y': $usethisformat = 'MM/D/YYYY';break;
        case 'd.m.Y': $usethisformat = 'D.MM.YYYY';break;
        case 'y/m/d': $usethisformat = 'YY/MM/D';break;
        case 'Y-m-d': $usethisformat = 'YYYY-MM-D';break;
        case 'M d, Y': $usethisformat = 'MMM D, YYYY';break;
        case 'M d, y': $usethisformat = 'MMM D, YY';break;
    }

    ?>
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/moment.min.js"></script>
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/js/datetime-moment.js"></script>
    <?php if ($this->uri->segment(2) == "wwff") { ?>
        <script>
            $.fn.dataTable.moment('<?php echo $usethisformat ?>');
            $.fn.dataTable.ext.buttons.clear = {
                className: 'buttons-clear',
                action: function ( e, dt, node, config ) {
                   dt.search('').draw();
                }
            };
            $('#wwfftable').DataTable({
                "pageLength": 25,
                responsive: false,
                ordering: true,
                "scrollY":        "500px",
                "scrollCollapse": true,
                "paging":         false,
                "scrollX": true,
                "language": {
                    url: getDataTablesLanguageUrl(),
                },
                "order": [ 0, 'asc' ],
                dom: 'Bfrtip',
                buttons: [
                   {
                      extend: 'csv'
                   },
                   {
                      extend: 'clear',
                      text: lang_admin_clear
                   }
                ]
            });
            // change color of csv-button if dark mode is chosen
            if (isDarkModeTheme()) {
               $('[class*="buttons"]').css("color", "white");
            }
        </script>
    <?php } else if ($this->uri->segment(2) == "pota") { ?>
        <script>
            $.fn.dataTable.moment('<?php echo $usethisformat ?>');
            $.fn.dataTable.ext.buttons.clear = {
                className: 'buttons-clear',
                action: function ( e, dt, node, config ) {
                   dt.search('').draw();
                }
            };
            $('#potatable').DataTable({
                "pageLength": 25,
                responsive: false,
                ordering: true,
                "scrollY":        "500px",
                "scrollCollapse": true,
                "paging":         false,
                "scrollX": true,
                "language": {
                    url: getDataTablesLanguageUrl(),
                },
                "order": [ 0, 'asc' ],
                dom: 'Bfrtip',
                buttons: [
                   {
                      extend: 'csv'
                   },
                   {
                      extend: 'clear',
                      text: lang_admin_clear
                   }
                ]
            });
            // change color of csv-button if dark mode is chosen
            if (isDarkModeTheme()) {
               $('[class*="buttons"]').css("color", "white");
            }
        </script>
    <?php } else if ($this->uri->segment(2) == "dok") { ?>
        <script>
            $.fn.dataTable.ext.buttons.clear = {
                className: 'buttons-clear',
                action: function ( e, dt, node, config ) {
                   dt.search('').draw();
                }
            };
            $('#doktable').DataTable({
                "pageLength": 25,
                responsive: false,
                ordering: false,
                "scrollY":        "500px",
                "scrollCollapse": true,
                "paging":         false,
                "scrollX": true,
                "language": {
                    url: getDataTablesLanguageUrl(),
                },
                dom: 'Bfrtip',
                buttons: [
                   {
                      extend: 'csv'
                   },
                   {
                      extend: 'clear',
                      text: lang_admin_clear
                   }
                ]
            });
            // change color of csv-button if dark mode is chosen
            if (isDarkModeTheme()) {
               $('[class*="buttons"]').css("color", "white");
            }
        </script>
    <?php } else if ($this->uri->segment(2) == "wac") { ?>
        <script>
            $('#band2').change(function(){
				var band = $("#band2 option:selected").text();
				if (band != "SAT") {
					$("#sats").val('All');
					$("#orbits").val('All');
					$("#satrow").hide();
					$("#orbitrow").hide();
				} else {
					$("#satrow").show();
					$("#orbitrow").show();
				}
			});

			$('#sats').change(function(){
				var sat = $("#sats option:selected").text();
				$("#band2").val('SAT');
				if (sat != "All") {
				}
			});
            // change color of csv-button if dark mode is chosen
            if (isDarkModeTheme()) {
            	$('[class*="buttons"]').css("color", "white");
            }
        </script>
    <?php } ?>
<?php } ?>

<?php if ($this->uri->segment(1) == "user") { ?>
    <script src="<?php echo base_url() ;?>assets/js/sections/user.js"></script>
<?php } ?>

<?php
if (isset($scripts) && is_array($scripts)){
	foreach($scripts as $script){
		?><script type="text/javascript" src="<?php echo base_url() . $script ;?>"></script>
		<?php
	}
}
?>

  </body>
</html>
