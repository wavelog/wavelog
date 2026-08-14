<?php $numWeeks = count($weeks); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= __("QSOs per Year"); ?> <?= htmlspecialchars($yr, ENT_QUOTES); ?></title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: Menlo, Consolas, monospace;
  background-color: <?php echo $mode === 'dark' ? '#1e1e1e' : '#fff'; ?>;
  color: <?php echo $mode === 'dark' ? '#ddd' : '#333'; ?>;
  padding: 16px;
}
.pc-container {
  max-width: 760px;
  margin: 0 auto;
  border: 1px solid <?php echo $mode === 'dark' ? '#333' : '#D8D8D8'; ?>;
  border-radius: 5px;
  background-color: <?php echo $mode === 'dark' ? '#1e1e1e' : '#fff'; ?>;
  overflow: hidden;
}
.pc-header {
  position: relative;
  background-color: <?php echo $mode === 'dark' ? '#2c2c2c' : '#F5F5F5'; ?>;
  border-bottom: 1px solid <?php echo $mode === 'dark' ? '#444' : '#D8D8D8'; ?>;
  color: <?php echo $mode === 'dark' ? '#fff' : '#000'; ?>;
  font-size: 15px;
  font-weight: bold;
  padding: 7px 10px;
}
.pc-quantity {
  position: absolute;
  right: 10px;
  top: 10px;
  color: <?php echo $mode === 'dark' ? '#777' : '#aaa'; ?>;
  font-size: 10px;
  font-weight: normal;
}
.pc-body { padding: 10px; overflow-x: auto; }

table.pc {
  border-collapse: separate; border-spacing: 3px;
  table-layout: fixed;
  width: <?php echo (16 + 10 * $numWeeks + 3 * ($numWeeks + 2)); ?>px;
}
table.pc th, table.pc td {
  width: 10px; height: 10px;
  padding: 0; font-size: 9px; font-weight: normal;
  text-align: center; vertical-align: middle;
  shape-rendering: crispedges;
}
table.pc td.lbl {
  width: auto; min-width: 12px;
  color: <?php echo $mode === 'dark' ? '#777' : '#999'; ?>;
  text-align: right; padding-right: 4px;
}
table.pc th.mlbl {
  color: <?php echo $mode === 'dark' ? '#777' : '#AAA'; ?>;
  text-align: left; padding-bottom: 3px;
  font-weight: normal;
  white-space: nowrap; overflow: visible;
}

/* color buckets - light palette */
td.day { background-color: #eee; }
td.day[data-col="3"]  { background-color: #c3dbda; }
td.day[data-col="6"]  { background-color: #5caeaa; }
td.day[data-col="12"] { background-color: #277672; }
td.day[data-col="24"] { background-color: #075652; }
td.day[data-col="48"] { background-color: #004642; }
td.day.empty { background-color: transparent; }

body.dark td.day { background-color: #333; }
body.dark td.day[data-col="3"]  { background-color: #8bd8d1; }
body.dark td.day[data-col="6"]  { background-color: #76beb7; }
body.dark td.day[data-col="12"] { background-color: #62a39c; }
body.dark td.day[data-col="24"] { background-color: #4b837e; }
body.dark td.day[data-col="48"] { background-color: #395b58; }
body.dark td.day.empty { background-color: transparent; }

.pc-summary {
  color: <?php echo $mode === 'dark' ? '#aaa' : '#aaa'; ?>;
  font-size: 12px;
  margin: 8px 10px 10px;
  display: flex; justify-content: space-between; align-items: center;
  flex-wrap: wrap; gap: 6px;
}
.pc-legend { font-size: 12px; }
.pc-legend span.swatch {
  display: inline-block; width: 10px; height: 10px;
  margin: 0 2px; vertical-align: middle; shape-rendering: crispedges;
}
.pc-legend .s0 { background-color: <?php echo $mode === 'dark' ? '#333' : '#eee'; ?>; }
.pc-legend .s1 { background-color: <?php echo $mode === 'dark' ? '#8bd8d1' : '#c3dbda'; ?>; }
.pc-legend .s2 { background-color: <?php echo $mode === 'dark' ? '#76beb7' : '#5caeaa'; ?>; }
.pc-legend .s3 { background-color: <?php echo $mode === 'dark' ? '#62a39c' : '#277672'; ?>; }
.pc-legend .s4 { background-color: <?php echo $mode === 'dark' ? '#4b837e' : '#075652'; ?>; }
.pc-legend .s5 { background-color: <?php echo $mode === 'dark' ? '#395b58' : '#004642'; ?>; }
</style>
</head>
<body class="<?php echo $mode === 'dark' ? 'dark' : ''; ?>">
<div class="pc-container">
  <div class="pc-header">
    <?= __("QSOs per Year"); ?> <?php echo htmlspecialchars($yr, ENT_QUOTES); ?>
    <span class="pc-quantity"><?php echo (int) $total; ?> <?= _ngettext("QSO", "QSOs", $total); ?></span>
  </div>
  <div class="pc-body">
    <table class="pc">
      <colgroup>
        <col style="width:16px">
        <?php for ($w = 0; $w < $numWeeks; $w++): ?>
          <col style="width:10px">
        <?php endfor; ?>
      </colgroup>
      <?php
      $monthNames = [1=>'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      $weekdayLabels = [1=>'M',2=>'',3=>'W',4=>'',5=>'F',6=>'',7=>'S'];
      ?>
      <thead>
        <tr>
          <th class="mlbl"></th>
          <?php for ($w = 0; $w < $numWeeks; $w++): ?>
            <th class="mlbl">
              <?php if (isset($monthLabels[$w])): ?>
                <?php echo htmlspecialchars($monthNames[$monthLabels[$w]], ENT_QUOTES); ?>
              <?php endif; ?>
            </th>
          <?php endfor; ?>
        </tr>
      </thead>
      <tbody>
        <?php for ($d = 1; $d <= 7; $d++): ?>
          <tr>
            <td class="lbl"><?php echo $weekdayLabels[$d] ?? ''; ?></td>
            <?php for ($w = 0; $w < $numWeeks; $w++): ?>
              <?php $cell = $weeks[$w][$d] ?? null; ?>
              <?php if ($cell === null): ?>
                <td class="day empty"></td>
              <?php else: ?>
                <?php
                $col = (int) $cell['col'];
                $titleDate = htmlspecialchars($cell['date'], ENT_QUOTES);
                $titleCount = (int) $cell['n'];
                $titleText = $titleCount === 0
                    ? $titleDate . ': ' . __("No QSOs")
                    : $titleDate . ': ' . $titleCount . ' ' . _ngettext("QSO", "QSOs", $titleCount);
                ?>
                <td class="day"<?php echo $col > 0 ? ' data-col="' . $col . '"' : ''; ?> title="<?php echo $titleText; ?>"></td>
              <?php endif; ?>
            <?php endfor; ?>
          </tr>
        <?php endfor; ?>
      </tbody>
    </table>
  </div>
  <div class="pc-summary">
    <div class="pc-legend">
      <?= __("Less"); ?>
      <span class="swatch s0"></span>
      <span class="swatch s1"></span>
      <span class="swatch s2"></span>
      <span class="swatch s3"></span>
      <span class="swatch s4"></span>
      <span class="swatch s5"></span>
      <?= __("More"); ?>
    </div>
    <div><?= __("Calendar with QSOs"); ?></div>
  </div>
</div>
</body>
</html>
