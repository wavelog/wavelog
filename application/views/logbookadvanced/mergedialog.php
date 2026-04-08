<form id="mergeForm">
    <input type="hidden" name="qsoIds[]" value="<?php echo $qsoIds[0] ?>">
    <input type="hidden" name="qsoIds[]" value="<?php echo $qsoIds[1] ?>">
    <input type="hidden" name="mergeData[primaryQso]" id="primaryQso" value="<?php echo $qsoIds[0] ?>">

    <div class="container-fluid">
        <div class="alert alert-info">
            <strong><?= __("Which QSO should be kept?"); ?></strong><br>
            <?= __("Select which QSO will remain after the merge. The other will be deleted."); ?>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <strong><?= __("QSO"); ?> #<?php echo $qso1->COL_PRIMARY_KEY ?></strong>
                        <label class="float-end">
                            <input type="radio" name="primaryQsoRadio" value="<?php echo $qsoIds[0] ?>"
								onclick="selectAllQso1Fields()"
								data-qso-id="<?php echo $qsoIds[0] ?>"
								<?php echo $qsoIds[0] == $qsoIds[0] ? 'checked' : '' ?>>
                            <?= __("Keep this QSO"); ?>
                        </label>
                    </div>
                    <div class="card-body">
                        <small>
                            <strong><?= __("Date/Time"); ?>:</strong> <?php echo $qso1->COL_TIME_ON ?><br>
                            <strong><?= __("Call"); ?>:</strong> <?php echo $qso1->COL_CALL ?><br>
                            <strong><?= __("Mode"); ?>:</strong> <?php echo $qso1->COL_MODE ?> <?php echo $qso1->COL_SUBMODE ?? '' ?><br>
                            <strong><?= __("Band"); ?>:</strong> <?php echo $qso1->COL_BAND ?><br>
                            <strong><?= __("Station"); ?>:</strong> <?php echo $qso1->COL_STATION_CALLSIGN ?>
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <strong><?= __("QSO"); ?> #<?php echo $qso2->COL_PRIMARY_KEY ?></strong>
                        <label class="float-end">
                            <input type="radio" name="secondaryQsoRadio" value="<?php echo $qsoIds[1] ?>"
								onclick="selectAllQso2Fields()"
								data-qso-id="<?php echo $qsoIds[1] ?>"
								<?php echo $qsoIds[0] == $qsoIds[1] ? 'checked' : '' ?>>
                            <?= __("Keep this QSO"); ?>
                        </label>
                    </div>
                    <div class="card-body">
                        <small>
                            <strong><?= __("Date/Time"); ?>:</strong> <?php echo $qso2->COL_TIME_ON ?><br>
                            <strong><?= __("Call"); ?>:</strong> <?php echo $qso2->COL_CALL ?><br>
                            <strong><?= __("Mode"); ?>:</strong> <?php echo $qso2->COL_MODE ?> <?php echo $qso2->COL_SUBMODE ?? '' ?><br>
                            <strong><?= __("Band"); ?>:</strong> <?php echo $qso2->COL_BAND ?><br>
                            <strong><?= __("Station"); ?>:</strong> <?php echo $qso2->COL_STATION_CALLSIGN ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            <strong><?= __("Select which fields to keep from each QSO:"); ?></strong><br>
            <?= __("The selected fields will be merged into the primary QSO. You can select different fields from each QSO."); ?>
        </div>

        <div class="row">
            <div class="col-md-12">
                <table class="table-sm table table-bordered table-hover table-striped table-condensed">
                    <thead>
                        <tr>
                            <th style="width: 30%"><?= __("Field"); ?></th>
                            <th style="width: 35%"><?= __("QSO"); ?> #<?php echo $qso1->COL_PRIMARY_KEY; ?></th>
                            <th style="width: 35%"><?= __("QSO"); ?> #<?php echo $qso2->COL_PRIMARY_KEY; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $fields = [
                            // Basic QSO Info
                            'RST Sent' => 'COL_RST_SENT',
                            'RST Received' => 'COL_RST_RCVD',
                            'Name' => 'COL_NAME',
                            'QTH' => 'COL_QTH',
                            'Gridsquare' => 'COL_GRID',
                            'State' => 'COL_STATE',
                            'County' => 'COL_CNTY',
                            'Country' => 'COL_COUNTRY',
                            'DXCC' => 'COL_DXCC',
                            'CQ Zone' => 'COL_CQZ',
                            'ITU Zone' => 'COL_ITUZ',
                            'Address' => 'COL_ADDRESS',
                            'Age' => 'COL_AGE',
                            'ARRL Section' => 'COL_ARRL_SECT',
                            'Biography' => 'COL_BIOGRAPHY',
                            'Email' => 'COL_EMAIL',
                            // Awards Programs
                            'IOTA' => 'COL_IOTA',
                            'POTA' => 'COL_POTA',
                            'SOTA' => 'COL_SOTA',
                            'WWFF' => 'COL_WWFF',
                            'DOK' => 'COL_DARC_DOK',
                            'SIG' => 'COL_SIG',
                            'SIG Info' => 'COL_SIG_INFO',
                            'FISTS' => 'COL_FISTS',
                            'FISTS CC' => 'COL_FISTS_CC',
                            'SKCC' => 'COL_SKCC',
                            'Ten Ten' => 'COL_TEN_TEN',
                            'UKSMG' => 'COL_UKSMG',
                            // VUCC Grids
                            'VUCC Grids' => 'COL_VUCC_GRIDS',
                            'USACA Counties' => 'COL_USACA_COUNTIES',
                            // QSL Details
                            'QSL Via' => 'COL_QSL_VIA',
                            'QSL Message' => 'COL_QSLMSG',
                            'QSL Message Received' => 'COL_QSLMSG_RCVD',
                            'QSL Sent' => 'COL_QSL_SENT',
                            'QSL Received' => 'COL_QSL_RCVD',
                            'QSL Sent Date' => 'COL_QSLSDATE',
                            'QSL Received Date' => 'COL_QSLRDATE',
                            'QSL Sent Method' => 'COL_QSL_SENT_VIA',
                            'QSL Received Method' => 'COL_QSL_RCVD_VIA',
                            // LoTW
                            'LoTW Sent' => 'COL_LOTW_QSL_SENT',
                            'LoTW Received' => 'COL_LOTW_QSL_RCVD',
                            'LoTW Sent Date' => 'COL_LOTW_QSLSDATE',
                            'LoTW Received Date' => 'COL_LOTW_QSLRDATE',
                            'LoTW Status' => 'COL_LOTW_STATUS',
                            // eQSL
                            'eQSL Sent' => 'COL_EQSL_QSL_SENT',
                            'eQSL Received' => 'COL_EQSL_QSL_RCVD',
                            'eQSL Sent Date' => 'COL_EQSL_QSLSDATE',
                            'eQSL Received Date' => 'COL_EQSL_QSLRDATE',
                            'eQSL AG' => 'COL_EQSL_AG',
                            'eQSL Status' => 'COL_EQSL_STATUS',
                            // Clublog
                            'Clublog Status' => 'COL_CLUBLOG_QSO_UPLOAD_STATUS',
                            'Clublog Upload Date' => 'COL_CLUBLOG_QSO_UPLOAD_DATE',
                            'Clublog Download Date' => 'COL_CLUBLOG_QSO_DOWNLOAD_DATE',
                            'Clublog Download Status' => 'COL_CLUBLOG_QSO_DOWNLOAD_STATUS',
                            // QRZ
                            'QRZ Status' => 'COL_QRZCOM_QSO_UPLOAD_STATUS',
                            'QRZ Upload Date' => 'COL_QRZCOM_QSO_UPLOAD_DATE',
                            'QRZ Download Date' => 'COL_QRZCOM_QSO_DOWNLOAD_DATE',
                            'QRZ Download Status' => 'COL_QRZCOM_QSO_DOWNLOAD_STATUS',
                            // HRDLog
                            'HRDLog Status' => 'COL_HRDLOG_QSO_UPLOAD_STATUS',
                            'HRDLog Upload Date' => 'COL_HRDLOG_QSO_UPLOAD_DATE',
                            // DCL
                            'DCL Sent' => 'COL_DCL_QSL_SENT',
                            'DCL Received' => 'COL_DCL_QSL_RCVD',
                            'DCL Sent Date' => 'COL_DCL_QSLSDATE',
                            'DCL Received Date' => 'COL_DCL_QSLRDATE',
                            // Station & Operator
                            'Operator' => 'COL_OPERATOR',
                            'Owner Callsign' => 'COL_OWNER_CALLSIGN',
                            'Station Callsign' => 'COL_STATION_CALLSIGN',
                            'My DXCC' => 'COL_MY_DXCC',
                            'My Country' => 'COL_MY_COUNTRY',
                            'My State' => 'COL_MY_STATE',
                            'My County' => 'COL_MY_CNTY',
                            'My County Alt' => 'COL_MY_CNTY_ALT',
                            'My Grid' => 'COL_MY_GRIDSQUARE',
                            'My CQ Zone' => 'COL_MY_CQ_ZONE',
                            'My ITU Zone' => 'COL_MY_ITU_ZONE',
                            'My IOTA' => 'COL_MY_IOTA',
                            'My IOTA Island ID' => 'COL_MY_IOTA_ISLAND_ID',
                            'My SOTA' => 'COL_MY_SOTA_REF',
                            'My POTA' => 'COL_MY_POTA_REF',
                            'My WWFF' => 'COL_MY_WWFF_REF',
                            'My VUCC Grids' => 'COL_MY_VUCC_GRIDS',
                            'My DOK' => 'COL_MY_DARC_DOK',
                            'My Name' => 'COL_MY_NAME',
                            'My City' => 'COL_MY_CITY',
                            'My Postal Code' => 'COL_MY_POSTAL_CODE',
                            'My Street' => 'COL_MY_STREET',
                            'My Antenna' => 'COL_MY_ANTENNA',
                            'My Rig' => 'COL_MY_RIG',
                            'My SIG' => 'COL_MY_SIG',
                            'My SIG Info' => 'COL_MY_SIG_INFO',
                            'My FISTS' => 'COL_MY_FISTS',
                            // Technical Details
                            'Frequency' => 'COL_FREQ',
                            'RX Frequency' => 'COL_FREQ_RX',
                            'Band RX' => 'COL_BAND_RX',
                            'Propagation Mode' => 'COL_PROP_MODE',
                            'Satellite Name' => 'COL_SAT_NAME',
                            'Satellite Mode' => 'COL_SAT_MODE',
                            'Antenna Azimuth' => 'COL_ANT_AZ',
                            'Antenna Elevation' => 'COL_ANT_EL',
                            'Antenna Path' => 'COL_ANT_PATH',
                            'TX Power' => 'COL_TX_PWR',
                            'RX Power' => 'COL_RX_PWR',
                            'A Index' => 'COL_A_INDEX',
                            'K Index' => 'COL_K_INDEX',
                            'SFI' => 'COL_SFI',
                            'STX' => 'COL_STX',
                            'STX String' => 'COL_STX_STRING',
                            'SRX' => 'COL_SRX',
                            'SRX String' => 'COL_SRX_STRING',
                            'Contest ID' => 'COL_CONTEST_ID',
                            'Contest Exchange Sent' => 'COL_PRECEDENCE',
                            'Morse Key Type' => 'COL_MORSE_KEY_TYPE',
                            'Morse Key Info' => 'COL_MORSE_KEY_INFO',
                            'Silent Key' => 'COL_SILENT_KEY',
                            'SWL' => 'COL_SWL',
                            'Web' => 'COL_WEB',
                            'Distance' => 'COL_DISTANCE',
                            'Region' => 'COL_REGION',
                            'Rig' => 'COL_RIG',
                            'Notes' => 'COL_NOTES',
                            'QSO Complete' => 'COL_QSO_COMPLETE',
                            // User Defined Fields
                            'User Defined 0' => 'COL_USER_DEFINED_0',
                            'User Defined 1' => 'COL_USER_DEFINED_1',
                            'User Defined 2' => 'COL_USER_DEFINED_2',
                            'User Defined 3' => 'COL_USER_DEFINED_3',
                            'User Defined 4' => 'COL_USER_DEFINED_4',
                            'User Defined 5' => 'COL_USER_DEFINED_5',
                            'User Defined 6' => 'COL_USER_DEFINED_6',
                            'User Defined 7' => 'COL_USER_DEFINED_7',
                            'User Defined 8' => 'COL_USER_DEFINED_8',
                            'User Defined 9' => 'COL_USER_DEFINED_9',
                            // Comment
                            'Comment' => 'COL_COMMENT',
                        ];

                        foreach ($fields as $label => $field) {
                            $val1 = $qso1->$field ?? '';
                            $val2 = $qso2->$field ?? '';

                            $diff = false;
                            if (!empty($val1) && !empty($val2) && strtolower($val1) != strtolower($val2)) {
                                $diff = true;
                            }

                            // Don't skip empty fields - show all fields
                            $fieldName = str_replace('COL_', '', $field);

                            echo '<tr style="">';
                            echo '<td><strong>' . $label . '</strong></td>';

                            // QSO 1 - Default to checked (QSO 1 is primary by default)
                            echo '<td>';
                            echo '<label class="d-block">';
                            $checked1 = 'checked';
                            echo '<input type="radio" name="mergeData[' . $fieldName . ']" value="qso1" ' . $checked1 . '> ';
                            if (!empty($val1)) {
                                if ($diff) {
                                    echo "<strong>".htmlspecialchars($val1)."</strong>";
                                } else {
                                    echo htmlspecialchars($val1);
                                }
                            } else {
                                echo '<em class="text-muted">(empty)</em>';
                            }
                            echo '</label>';
                            echo '</td>';

                            // QSO 2 - Not checked by default
                            echo '<td>';
                            echo '<label class="d-block">';
                            $checked2 = '';
                            echo '<input type="radio" name="mergeData[' . $fieldName . ']" value="qso2" ' . $checked2 . '> ';
                            if (!empty($val2)) {
                                if ($diff) {
                                    echo "<strong>".htmlspecialchars($val2)."</strong>";
                                } else {
                                    echo htmlspecialchars($val2);
                                }
                            } else {
                                echo '<em class="text-muted">(empty)</em>';
                            }
                            echo '</label>';
                            echo '</td>';

                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>

<script>

// Initialize on load
$(document).ready(function() {
    selectAllQso1Fields();
});
</script>

