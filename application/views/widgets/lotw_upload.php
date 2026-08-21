<!--

This is a widget to show the last LoTW upload in your QRZ.com Bio or somewhere else.

<iframe name="iframe" src="[YOUR WAVELOG URL]/widgets/lotw_upload/[PUBLIC SLUG]" height="240" width="640" frameborder="0" align="top"></iframe>

-->

<!DOCTYPE html>
<html lang="<?php echo $language['code']; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="<?php echo $this->paths->cache_buster('/assets/css/' . $theme . '/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo $this->paths->cache_buster('/assets/css/' . $theme . '/overrides.css'); ?>">
    <link rel="stylesheet" href="<?php echo $this->paths->cache_buster('/assets/css/general.css'); ?>">

    <title><?= "Wavelog Last LoTW-Upload widget"; ?></title>
    <style>
        .widget.container {
            max-width: none;
        }

        .left-column {
            width: 150px;
            display: flex;
            justify-content: center;
            align-items: top;
            border-right: 1px solid #444;
            padding: 10px;
        }

        .right-column {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 10px;
        }

        .top-right {
            height: 60px;
            display: flex;
        }

        .top-right,
        .bottom-right {
            border-bottom: 1px solid #444;
            padding: 10px;
        }

        .bottom-right {
            flex: 1;
        }

        .widgetLogo {
            width: 150px;
            height: 150px;
        }

        .lotw-upload-label {
            font-size: 0.9em;
            margin-right: 10px;
            padding-right: 10px;
        }

        .last-updated {
            font-size: 0.8em;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>

<body>
    <!-- Normal JavaScript-enabled mode -->
    <div class="widget container d-flex">
        <div class="left-column">
            <img class="widgetLogo" src="<?php echo $this->paths->cache_buster('/assets/logo/' . $this->optionslib->get_logo('header_logo', $theme) . '.png'); ?>" alt="Logo" />
        </div>
        <?php if (!isset($error)) { ?>
        <div class="right-column">
            <div class="top-right d-flex justify-content-between align-items-center">
                <div>
                    <span class="<?= $text_size_class ?>" id="status-text">
                      <strong><?= __("Last LoTW Uploads"); ?></strong>
                    </span>
                </div>
            </div>
            <div class="bottom-right mt-3" id="lotw_uploads-container">
                <div class="mb-2">
                <?php if (!empty($lotw_uploads)) { ?>
                    <table>
                    <tr>
                        <th><?= __("Callsign"); ?></th>
                        <th><?= __("Last Upload (UTC)"); ?></th>
                    </tr>
                    <?php foreach ($lotw_uploads as $upload) { ?>
                        <tr>
                            <td><span class="lotw-upload-label"><?php echo $upload->callsign.":"; ?></span></td>
                            <?php if ($upload->last_upload) { ?>
                                <td><span class="lotw-upload-label"><?php echo $upload->last_upload; ?>Z</span></td>
                            <?php } else { ?>
                                <td><span class="lotw-upload-label"><?= __("n/a"); ?></span></td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                    </table>
                <?php } else { ?>
                    <?= __("No data available"); ?>
                <?php } ?>
                </div>
                <div class="last-updated mt-2">
                    <small id="last-updated-text">Last updated: <?= date('H:i:s'); ?></small>
                </div>
            </div>
        </div>
     <?php } else { ?>
        <div class="right-column">
            <div class="top-right">
                <p class="<?= $text_size_class ?>"><?= __("Error") ?></p>
            </div>
            <div class="bottom-right mt-3">
                <p class="<?= $text_size_class ?>"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8', false) ?></p>
           </div>
        </div>
     <?php } ?>
    </div>

</body>

</html>
