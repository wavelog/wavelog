<style>
.sfle-syntax pre {
	background-color: rgba(127, 127, 127, .12);
	border: 1px solid rgba(127, 127, 127, .30);
	border-radius: .375rem;
	padding: .5rem .75rem;
}
</style>
<div class="sfle-syntax">
<h5 class="mt-2 mb-2 border-bottom pb-1"><?= __("Getting started") ?></h5>
<p><?= __("Before starting to log a QSO, please note the basic rules.") ?>:</p>
<ul class="mb-3">
	<li><?= ltrim(__("- Each new QSO should be on a new line."), "- ") ?></li>
	<li><?= ltrim(__("- On each new line, only write data that has changed from the previous QSO."), "- ") ?></li>
</ul>

<h5 class="mt-4 mb-2 border-bottom pb-1"><?= __("The first QSO") ?></h5>
<p><?= sprintf(__("To begin, ensure you have already filled in the form on the left with the date, station call, and operator's call. The main data includes the band (or QRG in MHz, e.g., %s), mode, and time. After the time, you provide the first QSO, which is essentially the callsign."), "'7.145'") ?></p>
<pre class="mb-3">
    20m ssb
    2134 4w7est
</pre>
<p><?= __("For example, a QSO that started at 21:34 (UTC) with 4W7EST on 20m SSB.") ?></p>

<h5 class="mt-4 mb-2 border-bottom pb-1"><?= __("Signal reports (RST)") ?></h5>
<p><?= __("If you don't provide any RST information, the syntax will use 59 for phone, 599 for CW and +0 dB for digital data modes. Our next QSO wasn't 59 on both sides, so we provide the information with the sent RST first. It was 2 minutes later than the first QSO.") ?></p>
<pre class="mb-3">
    20m ssb
    2134 4w7est
    6 la8aja 47 46
</pre>
<p><?= __("The first QSO was at 21:34, and the second one 2 minutes later at 21:36. We write down 6 because this is the only data that changed here. The information about band and mode didn't change, so this data is omitted.") ?></p>

<h5 class="mt-4 mb-2 border-bottom pb-1"><?= __("Date and time") ?></h5>
<p><?= __("For our next QSO at 21:40 on 14th May, 2021, we changed the band to 40m but still on SSB. If no RST information is given, the syntax will use 59 for every new QSO. Therefore we can add another QSO which took place at the exact same time two days later. The date must be in format YYYY-MM-DD.") ?></p>
<pre class="mb-3">
    20m ssb
    2134 4w7est
    6 la8aja 47 46
    date 2021-05-14
    40m
    40 dj7nt
    day ++
    df3et
</pre>

<h5 class="mt-4 mb-2 border-bottom pb-1"><?= __("Additional information") ?></h5>
<p><?= __("Additional informations can be submitted in the following way:") ?></p>
<p class="mb-1"><?= __("Notes:") ?></p>
<pre class="mb-3">
    2112 dj3ce &lt; comment &gt;
</pre>
<p class="mb-1"><?= __("Operator Name:") ?></p>
<pre class="mb-3">
    2112 dj3ce @Cedric
</pre>
<p class="mb-1"><?= __("QSL-message (Caution! Not visible in wavelog currently!):") ?></p>
<pre class="mb-3">
    2112 dj3ce [tnx qso]
</pre>
<p><?= __("You may use the comment syntax, to fill adif-fields supported by the Wavelog-Import:") ?></p>
<pre class="mb-3">
    2119 dj3ce &lt;tx_pwr:50&gt; &lt;rx_pwr:750&gt; &lt;darc_dok:F39&gt; &lt;sfi:210&gt; &lt;rig:QCX&gt; &lt;...&gt;
</pre>

<h5 class="mt-4 mb-2 border-bottom pb-1"><?= __("Contest exchange") ?></h5>
<p><?= __("Contest exchange; serials or other exchange - or even both:") ?></p>
<pre class="mb-3">
    2112 dj3ce ,1.12
    2113 dj3ce ,LA.DL
    2114 dj3ce ,12,LA.14.DL
    2114 dj3ce .14 ,12 .DL ,LA
</pre>
<p><?= sprintf(__("Received exchange has to be prefixed with a dot %s, sent exchange with a comma %s. The last two lines are equivalent - i.e. spaces don't matter as the order doesn't as well. Each of the four lines above simply illustrates one type of exchange (serial only, text only, or both); note that the sent exchange is 'sticky': once set, it is automatically carried into the following QSOs (whenever the next line contains a received exchange or a single comma %s) until you change or wipe it. To automatically increment the sent serial, add %s to a QSO line together with an initial sent exchange (it does not work on its own line). To deactivate, use %s:"), "'.'", "','", "','", "',++'", "',+0'") ?></p>
<pre class="mb-3">
    2112 dj3ce ,++,1.12
    2113 dk0mm .105
</pre>
<p><?= sprintf(__("Here, the first qso uses the set serial 1, and the second will use 2 as the serial. If you want to wipe your sent exchange, use %s:"), "',-'") ?></p>
<pre class="mb-3">
    2115 dj3ce ,15,D23.1.F39
    2116 dk0mm ,-,16.1015
</pre>
<p><?= sprintf(__("First, all previous exchange is wiped, then only a serial is set. Otherwise the previous exchange %s would have been set also."), "'D23'") ?></p>

<h5 class="mt-4 mb-2 border-bottom pb-1"><?= __("Time zone") ?></h5>
<p><?= sprintf(__("If you log in local time instead of UTC, declare your offset once with %s (or the short form %s) followed by the signed hour offset. All following QSO times are then converted to UTC until you change the offset again:"), "'TIMEZONE'", "'TZOFS'") ?></p>
<pre class="mb-3">
    TIMEZONE +2
    1400 dj3ce
</pre>
<p><?= __("Here, the QSO logged at 14:00 local time is stored as 12:00 UTC.") ?></p>

<hr class="my-3">
<p><?= sprintf(__("A full summary of all commands and the necessary syntax can be found in %sthis article%s of our Wiki."), '<a href="https://docs.wavelog.org/user-guide/logbook/simple-fle/" target="_blank">', '</a>'); ?></p>
</div>
