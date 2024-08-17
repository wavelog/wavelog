<p><?= __("Before starting to log a QSO, please note the basic rules.") ?>:</p>
<p><?= __("- Each new QSO should be on a new line.") ?></p>
<p><?= __("- On each new line, only write data that has changed from the previous QSO.") ?></p>
<p><?= __("To begin, ensure you have already filled in the form on the left with the date, station call, and operator's call. The main data includes the band (or QRG in MHz, e.g., '7.145'), mode, and time. After the time, you provide the first QSO, which is essentially the callsign.") ?></p>
<pre>
    20m ssb
    2134 2m0sql
</pre>
<p><?= __("For example, a QSO that started at 21:34 (UTC) with 4W7EST on 20m SSB.") ?><p>
<p><?= __("If you don't provide any RST information, the syntax will use 59 (599 for data). Our next QSO wasn't 59 on both sides, so we provide the information with the sent RST first. It was 2 minutes later than the first QSO.") ?></p>
<pre>
    20m ssb
    2134 2m0sql
    6 la8aja 47 46
</pre>
<p><?= __("The first QSO was at 21:34, and the second one 2 minutes later at 21:36. We write down 6 because this is the only data that changed here. The information about band and mode didn't change, so this data is omitted.") ?></p>
<p><?= __("For our next QSO at 21:40 on 14th May, 2021, we changed the band to 40m but still on SSB. If no RST information is given, the syntax will use 59 for every new QSO. Therefore we can add another QSO which took place at the exact same time two days later. The date must be in format YYYY-MM-DD.") ?></p>
<pre>
    20m ssb
    2134 2m0sql
    6 la8aja 47 46
    date 2021-05-14
    40m 
    40 dj7nt
    day ++
    df3et
</pre>
<p><?= sprintf(__("A full summary of all commands and the necessary syntax can be found in %sthis article%s of our Wiki."), '<a href="https://github.com/wavelog/wavelog/wiki/SimpleFLE" target="_blank">', '</a>'); ?></p>

