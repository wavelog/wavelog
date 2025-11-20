<!--

This is a DYNAMIC On-Air widget to place in your QRZ.com Bio or somewhere else.

For normal use with JavaScript:
<iframe name="iframe" src="[YOUR WAVELOG URL]/widgets/on_air/[PUBLIC SLUG]" height="240" width="640" frameborder="0" align="top"></iframe>

For QRZ.com compatibility (using QRZ.com auto-refresh):
<iframe name="iframe" src="[YOUR WAVELOG URL]/widgets/on_air/[PUBLIC SLUG]?nojs=1" height="140" width="640" frameborder="0" align="top" data-refresh="60"></iframe>

The widget automatically detects the nojs=1 parameter and serves a JavaScript-free version that works in QRZ.com's sandbox environment. QRZ.com's lazyLoader will automatically refresh every 60 seconds using the data-refresh attribute.
-->

<!DOCTYPE html>
<html lang="<?php echo $language['code']; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php if (isset($nojs) && $nojs): ?>
    <!-- No auto-refresh for QRZ.com compatibility - blocked by sandbox -->
    <?php endif; ?>

    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/<?php echo $theme; ?>/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/<?php echo $theme; ?>/overrides.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/general.css">

    <title><?= "Wavelog Dynamic On-Air widget"; ?></title>
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

        .refresh-indicator {
            opacity: 0.3;
            transition: opacity 0.3s ease;
        }

        <?php if (!isset($nojs) || !$nojs): ?>
        .refresh-indicator.updating {
            opacity: 1;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        <?php endif; ?>

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
            animation: blink 2s infinite;
        }

        .status-indicator.on-air {
            background-color: #28a745;
        }

        .status-indicator.off-air {
            background-color: #6c757d;
            animation: none;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            25%, 75% { opacity: 0.3; }
        }

        .frequency-display {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #007bff;
        }

        .mode-band {
            font-size: 0.9em;
            color: #6c757d;
            margin-left: 10px;
        }

        .last-updated {
            font-size: 0.8em;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>

<body>
    <?php if (isset($nojs) && $nojs): ?>
    <!-- No-JavaScript mode for QRZ.com compatibility -->
    <div class="widget container d-flex">
        <div class="left-column">
            <img class="widgetLogo" src="<?php echo base_url(); ?>assets/logo/<?php echo $this->optionslib->get_logo('header_logo', $theme); ?>.png" alt="Logo" />
        </div>
        <?php if (!isset($error)) { ?>
        <div class="right-column">
            <div class="top-right">
                <div>
                    <span class="status-indicator <?php echo $is_on_air ? 'on-air' : 'off-air'; ?>"></span>
                    <span class="<?= $text_size_class ?>">
                        <?php
                            if ($is_on_air === true) {
                                printf("%s ON-AIR", $user_callsign);
                            } else {
                                printf("%s OFF-AIR", $user_callsign);
                            }
                        ?>
                    </span>
                </div>
            </div>
            <div class="bottom-right mt-2">
                <?php if ($is_on_air === true) { ?>
                    <?php foreach ($radios_online as $radio_data) { ?>
                        <div class="frequency-info mb-2">
                            <span class="frequency-display <?= $text_size_class ?>">
                                <?= htmlspecialchars($radio_data->frequency_string); ?>
                            </span>
                        </div>
                    <?php } ?>
                <?php } else if ($last_seen_text !== null) { ?>
                    <div class="<?= $text_size_class ?> text-muted">
                        <?= htmlspecialchars($last_seen_text); ?>
                    </div>
                <?php } ?>
                <div class="last-updated mt-2">
                    <small>Updated: <?= date('H:i:s'); ?> (auto-refreshed every 60s)</small>
                </div>
            </div>
        </div>
     <?php } else { ?>
        <div class="right-column">
            <div class="top-right">
                <div>
                    <span class="status-indicator off-air"></span>
                    <span class="<?= $text_size_class ?>"><?= __("Error") ?></span>
                </div>
            </div>
            <div class="bottom-right mt-2">
                <div class="<?= $text_size_class ?> text-danger">
                    <?= $error ?>
                </div>
                <div class="last-updated mt-2">
                    <small>Updated: <?= date('H:i:s'); ?> (auto-refreshed by QRZ.com every 60s)</small>
                </div>
            </div>
        </div>
     <?php } ?>
    </div>

    <?php else: ?>
    <!-- Normal JavaScript-enabled mode -->
    <div class="widget container d-flex">
        <div class="left-column">
            <img class="widgetLogo" src="<?php echo base_url(); ?>assets/logo/<?php echo $this->optionslib->get_logo('header_logo', $theme); ?>.png" alt="Logo" />
        </div>
        <?php if (!isset($error)) { ?>
        <div class="right-column">
            <div class="top-right d-flex justify-content-between align-items-center">
                <div>
                    <span class="status-indicator <?php echo $is_on_air ? 'on-air' : 'off-air'; ?>"></span>
                    <span class="<?= $text_size_class ?>" id="status-text">
                        <?php
                            if ($is_on_air === true) {
                                printf("%s is ON-AIR", $user_callsign);
                            } else {
                                printf("%s is OFF-AIR", $user_callsign);
                            }
                        ?>
                    </span>
                </div>
                <div class="refresh-indicator" id="refresh-indicator">
                    <small>🔄</small>
                </div>
            </div>
            <div class="bottom-right mt-3" id="frequency-container">
                <?php if ($is_on_air === true) { ?>
                    <?php foreach ($radios_online as $radio_data) { ?>
                        <div class="frequency-info mb-2">
                            <span class="frequency-display <?= $text_size_class ?>" id="frequency-display">
                                <?= htmlspecialchars($radio_data->frequency_string); ?>
                            </span>
                        </div>
                    <?php } // end foreach ?>
                <?php } else if ($last_seen_text !== null) { ?>
                    <p class="<?= $text_size_class ?>" id="last-seen-text">
                        <?= htmlspecialchars($last_seen_text); ?>
                    </p>
                <?php } ?>
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
                <p class="<?= $text_size_class ?>"><?= htmlspecialchars($error) ?></p>
           </div>
        </div>
     <?php } ?>
    </div>
    <?php endif; ?>

    <?php if (!isset($nojs) || !$nojs): ?>
    <script>
        (function() {
            const userSlug = '<?php echo $user_slug ?? ''; ?>';
            let updateInterval;

            function updateWidget() {
                if (!userSlug) return;

                const refreshIndicator = document.getElementById('refresh-indicator');
                const statusText = document.getElementById('status-text');
                const statusIndicator = document.querySelector('.status-indicator');
                const frequencyContainer = document.getElementById('frequency-container');
                const lastUpdatedText = document.getElementById('last-updated-text');

                // Show loading state
                if (refreshIndicator) {
                    refreshIndicator.classList.add('updating');
                }

                fetch(`${window.location.origin}/index.php/widgets/on_air_ajax/${userSlug}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error('Widget error:', data.error);
                            return;
                        }

                        if (statusText) {
                            statusText.textContent = `${data.user_callsign} is ${data.is_on_air ? 'ON-AIR' : 'OFF-AIR'}`;
                        }

                        if (statusIndicator) {
                            statusIndicator.className = `status-indicator ${data.is_on_air ? 'on-air' : 'off-air'}`;
                        }

                        if (frequencyContainer && data.radios_online) {
                            let html = '';

                            if (data.radios_online.length > 0) {
                                data.radios_online.forEach(radio => {
                                    html += `
                                        <div class="frequency-info mb-2">
                                            <span class="frequency-display ${window.textSizeClass || ''}">
                                                ${radio.frequency_string}
                                            </span>
                                        </div>
                                    `;
                                });
                                html += '<div class="last-updated mt-2"><small id="last-updated-text">Last updated: ' +
                                       new Date().toLocaleTimeString() + '</small></div>';
                            } else if (data.last_seen_text) {
                                html = `<p class="${window.textSizeClass || ''}" id="last-seen-text">${data.last_seen_text}</p>`;
                                html += '<div class="last-updated mt-2"><small id="last-updated-text">Last updated: ' +
                                       new Date().toLocaleTimeString() + '</small></div>';
                            } else {
                                html = '<div class="last-updated mt-2"><small id="last-updated-text">Last updated: ' +
                                       new Date().toLocaleTimeString() + '</small></div>';
                            }

                            frequencyContainer.innerHTML = html;
                        }

                        if (lastUpdatedText) {
                            lastUpdatedText.textContent = 'Last updated: ' + new Date().toLocaleTimeString();
                        }
                    })
                    .catch(error => {
                        console.error('Widget update error:', error);
                    })
                    .finally(() => {
                        if (refreshIndicator) {
                            refreshIndicator.classList.remove('updating');
                        }
                    });
            }

            window.textSizeClass = '<?php echo $text_size_class ?? ''; ?>';

            setTimeout(() => {
                updateWidget(); 
                updateInterval = setInterval(updateWidget, 30000); 
            }, 2000); 

            window.addEventListener('beforeunload', () => {
                if (updateInterval) {
                    clearInterval(updateInterval);
                }
            });
        })();
    </script>
    <?php endif; ?>
</body>

</html>
