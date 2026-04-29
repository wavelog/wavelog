<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logbook</title>
<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}
body {
  font-family: Menlo, Consolas, monospace;
  background-color: #fff;
  color: #333;
}
.mini-container {
  max-width: 800px;
  margin: 0 auto;
  overflow: hidden;
}
.mini-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 15px 20px;
  background-color: #fff;
}
.mini-logo {
  font-size: 20px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 10px;
}
.mini-logo svg {
  height: 24px;
  width: 24px;
}
.search-box {
  position: relative;
}
.search-box input {
  padding: 8px 30px 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  width: 200px;
  background-color: #f5f5f5;
  outline: none;
}
.search-box input:focus {
  border-color: #999;
}
.search-box button {
  position: absolute;
  right: 5px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: #666;
  padding: 0;
  display: flex;
}
.table-wrapper {
  min-height: 400px;
  overflow-y: hidden;
  border: 1px solid #e0e0e0;
}
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}
thead {
  position: sticky;
  top: 0;
  z-index: 1;
  background-color: #f8f8f8;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
th {
  text-align: left;
  padding: 10px 20px;
  font-weight: bold;
  color: #333;
  border-bottom: 1px solid #e0e0e0;
  white-space: nowrap;
}
td {
  padding: 8px 20px;
  border-bottom: 1px solid #f0f0f0;
  color: #333;
  height: 36px;
  box-sizing: border-box;
  white-space: nowrap;
}
tr:hover td {
  background-color: #fafafa;
}
.status-icons {
  display: flex;
  gap: 4px;
}
.triangle-up {
  width: 0;
  height: 0;
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
  border-bottom: 8px solid #ccc;
}
.triangle-down {
  width: 0;
  height: 0;
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
  border-top: 8px solid #ccc;
}
.red {
  border-bottom-color: #ff4d4f !important;
  border-top-color: #ff4d4f !important;
}
.green {
  border-bottom-color: #52c41a !important;
  border-top-color: #52c41a !important;
}
.no-data {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #666;
  font-style: italic;
}

@media (max-width: 540px) {
  th, td {
    padding-left: 10px;
    padding-right: 10px;
  }
  table {
    font-size: 12px;
  }
  .search-box input {
    width: 120px;
  }
}

@media (max-width: 380px) {
  th:nth-child(5), td:nth-child(5),
  th:nth-child(6), td:nth-child(6) {
    display: none;
  }
}
</style>
</head>
<body>
<div class="mini-container">
  <div class="mini-header">
    <div class="mini-logo">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
      Logbook
    </div>
    <form class="search-box" method="POST" action="<?php echo site_url('visitor/'.$slug.'/mini'); ?>">
      <input type="text" name="callsign" placeholder="Your CallSign" value="<?php echo isset($search_callsign) ? htmlspecialchars($search_callsign, ENT_QUOTES) : ''; ?>">
      <button type="submit">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
      </button>
    </form>
  </div>
  <?php
  $hasData = !empty($results) && count($results) > 0;
  ?>
  <div class="table-wrapper">
    <?php if ($hasData): ?>
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>CallSign</th>
          <th>Band</th>
          <th>Mode</th>
          <th>LOTW</th>
          <th>DIRECT</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($results as $row): ?>
        <tr>
          <td><?php echo date('Y-m-d', strtotime($row->COL_TIME_ON)); ?></td>
          <td><?php echo str_replace("0", "&Oslash;", strtoupper($row->COL_CALL)); ?></td>
          <td><?php echo strtolower($row->COL_BAND); ?></td>
          <td><?php echo $row->COL_SUBMODE ?? $row->COL_MODE; ?></td>
          <td>
            <div class="status-icons">
              <div class="triangle-up <?php echo ($row->COL_LOTW_QSL_SENT == 'Y') ? 'green' : 'red'; ?>"></div>
              <div class="triangle-down <?php echo ($row->COL_LOTW_QSL_RCVD == 'Y') ? 'green' : 'red'; ?>"></div>
            </div>
          </td>
          <td>
            <div class="status-icons">
              <div class="triangle-up <?php echo ($row->COL_QSL_SENT == 'Y') ? 'green' : 'red'; ?>"></div>
              <div class="triangle-down <?php echo ($row->COL_QSL_RCVD == 'Y') ? 'green' : 'red'; ?>"></div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="no-data">no QSOs yet</div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
