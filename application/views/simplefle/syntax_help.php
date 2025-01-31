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
<p><?= __("Additional informations can be submitted in the following way:") ?></p>
<p><?= __("Notes:") ?></p>
<pre>
    2112 dj3ce &lt; comment &gt;
</pre>
<p><?= __("Operator Name:") ?></p>
<pre>
  2112 dj3ce @Cedric
</pre>
<p><?= __("QSL-message (Caution! Not visible in wavelog currently!):") ?></p>
<pre>
  2112 dj3ce [tnx qso]
</pre>
<p><?= __("Contest exchange; serials or other exchange - or even both:") ?></p>
<pre>
    2112 dj3ce ,1.12
    2113 dj3ce ,LA.DL
    2114 dj3ce ,12,LA.14.DL
    2114 dj3ce .14 ,12 .DL ,LA
</pre>
<p><?= __("Received exchange has to be prefixed with a dot '.', sent exchange with a comma ','. The last two lines are equivalent - i.e. spaces don't matter as the order doesn't as well. Exchange you have sent will automatically be included in the next QSO, if it contains received exchange, or if you use a single comma ','. To automatically increment the sent serial, use ',++' and give an initial sent exchange. To deactivate, use ',+0':") ?></p>
<pre>
    ,++
    2112 dj3ce ,1.12
    2113 dk0mm .105
</pre>
<p><?= __("Here, the first qso uses the set serial 1, and the second will use 2 as the serial. If you want to wipe your sent exchange, use ',-':") ?></p>
<pre>
    2115 dj3ce ,15,D23.1.F39
    2116 dk0mm ,-,16.1015
</pre>
<p><?= __("First, all previous exchange is wiped, then only a serial is set. Otherwise the previous exchange 'D23' would have been set also.") ?></p>
<p><?= __("You may use the comment syntax, to fill adif-fields supported by the Wavelog-Import:") ?></p>
<pre>
    2119 dj3ce &lt;tx_pwr:50&gt; &lt;rx_pwr:750&gt; &lt;darc_dok:F39&gt; &lt;sfi:210&gt; &lt;rig:QCX&gt; &lt;...&gt;
</pre>
<p><?= sprintf(__("A full summary of all commands and the necessary syntax can be found in %sthis article%s of our Wiki."), '<a href="https://github.com/wavelog/wavelog/wiki/SimpleFLE" target="_blank">', '</a>'); ?></p>

