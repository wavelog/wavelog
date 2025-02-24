<!--

This is an On-Air widget to place in your QRZ.com Bio or somewhere else.

To use this widget insert this Element:

<iframe name="iframe" src="[YOUR WAVELOG URL]/widgets/on_air/[PUBLIC SLUG]" height="240" width="640" frameborder="0" align="top"></iframe> -->


<!DOCTYPE html>
<html lang="<?php echo $language['code']; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/<?php echo $theme; ?>/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/<?php echo $theme; ?>/overrides.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/general.css">

    <title><?= "Wavelog On-Air widget"; ?></title>
    <style>
        .widget.container {
            max-width: none;
        }

        .left-column {
            width: 150px;
            display: flex;
            justify-content: center;
            align-items: center;
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
            border-bottom: none;
        }

        .widgetLogo {
            width: 150px;
            height: 150px;
        }
    </style>
</head>

<body>
    <div class="widget container d-flex">
        <div class="left-column">
            <img class="widgetLogo" src="<?php echo base_url(); ?>assets/logo/<?php echo $this->optionslib->get_logo('header_logo', $theme); ?>.png" alt="Logo" />
        </div>
        <?php if (!isset($error)) { ?>
        <div class="right-column">
            <div class="top-right">
                <p class="<?= $text_size_class ?>">
                    <?php
                        if ($is_on_air === true) {
                            printf("%s is ON-AIR", $user_callsign);
                        } else {
                            printf("%s is OFF-AIR", $user_callsign);
                        }
                    ?>
                </p>
            </div>
            <div class="bottom-right mt-3">
                <?php if ($is_on_air === true) { ?>
                    <?php foreach ($radios_online as $radio_data) { ?>
                        <p class="<?= $text_size_class ?>">
                            <?= $radio_data->frequency_string; ?>
                        </p>
                    <?php } // end foreach ?>
                <?php } else if ($last_seen_text !== null) { ?>
                    <p class="<?= $text_size_class ?>">
                        <?= sprintf($last_seen_text); ?>
                    </p>
                <?php } ?>
            </div>
        </div>
     <?php } else { ?>
        <div class="right-column">
            <div class="top-right">
                <p class="<?= $text_size_class ?>"><?= __("Error") ?></p>
            </div>
            <div class="bottom-right mt-3">
                <p class="<?= $text_size_class ?>"><?= $error ?></p>
           </div>
        </div>
     <?php } ?>
    </div>
</body>

</html>
