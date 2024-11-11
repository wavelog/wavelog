<?php

$message['subject'] = __("Wavelog Test-Mail");

$message['body'] = __("Hi,

This is a test email from your Wavelog instance.

If you received this email, your mail settings are correct.

Regards,

Wavelog");

echo json_encode($message);