BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//wavelog/satscheduler//NONSGML v1.0//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
DTEND:<?php echo date("Ymd\THis\Z",strtotime($los))."\n"; ?>
UID:<?php echo uniqid()."\n"; ?>
DTSTAMP:<?php echo date("Ymd\THis\Z")."\n"; ?>
LOCATION:At the SAT-TRX
SUMMARY:<?php echo $sat."\n"; ?>
DTSTART:<?php echo date("Ymd\THis\Z",strtotime($aos))."\n"; ?>
END:VEVENT
END:VCALENDAR
