<?php
// index.php - dashboard: 6 numbers, all calculated from the database.
$title = 'Dashboard';
include 'header.php';
$month = date('Y-m');

$rooms    = $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
$beds     = $pdo->query('SELECT COALESCE(SUM(capacity),0) FROM rooms')->fetchColumn();
$tenants  = $pdo->query("SELECT COUNT(*) FROM tenants WHERE status='Active'")->fetchColumn();
$freeBeds = $beds - $tenants;

$st = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE month = ?');
$st->execute([$month]);
$collected = $st->fetchColumn();

// pending rent this month = rent of each active tenant - what they already paid
$st = $pdo->prepare("SELECT COALESCE(SUM(GREATEST(r.rent - COALESCE(p.paid,0), 0)),0)
    FROM tenants t JOIN rooms r ON r.id = t.room_id
    LEFT JOIN (SELECT tenant_id, SUM(amount) AS paid FROM payments WHERE month = ? GROUP BY tenant_id) p ON p.tenant_id = t.id
    WHERE t.status = 'Active'");
$st->execute([$month]);
$pending = $st->fetchColumn();

$complaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status='Pending'")->fetchColumn();


// ---------- data for the graphs (all from the database) ----------
// 1) Rent collected in the last 6 months
$labels = []; $collectedByMonth = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months", strtotime(date('Y-m-01'))));
    $st = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE month = ?');
    $st->execute([$m]);
    $labels[] = date('M Y', strtotime($m . '-01'));
    $collectedByMonth[] = (float)$st->fetchColumn();
}

// 2) Beds in every room: occupied vs free
$roomRows = $pdo->query("SELECT r.room_number, r.capacity, COUNT(t.id) AS occupied
    FROM rooms r LEFT JOIN tenants t ON t.room_id = r.id AND t.status = 'Active'
    GROUP BY r.id, r.room_number, r.capacity ORDER BY r.room_number")->fetchAll();
$roomNames = []; $roomOccupied = []; $roomFree = [];
foreach ($roomRows as $r) {
    $roomNames[]    = 'Room ' . $r['room_number'];
    $roomOccupied[] = (int)$r['occupied'];
    $roomFree[]     = max(0, $r['capacity'] - $r['occupied']);
}

// 3) This month: how many active tenants are Paid / Partly paid / Unpaid
$st = $pdo->prepare("SELECT r.rent, COALESCE(p.paid,0) AS paid
    FROM tenants t JOIN rooms r ON r.id = t.room_id
    LEFT JOIN (SELECT tenant_id, SUM(amount) AS paid FROM payments WHERE month = ? GROUP BY tenant_id) p ON p.tenant_id = t.id
    WHERE t.status = 'Active'");
$st->execute([$month]);
$paidN = $partN = $unpaidN = 0;
foreach ($st->fetchAll() as $row) {
    if ($row['paid'] >= $row['rent'])  $paidN++;
    elseif ($row['paid'] > 0)          $partN++;
    else                               $unpaidN++;
}

// 4) Complaints: pending vs resolved
$resolvedN = (int)$pdo->query("SELECT COUNT(*) FROM complaints WHERE status='Resolved'")->fetchColumn();
$pendingN  = (int)$complaints;

$cards = [
  ['Total rooms', $rooms], ['Active tenants', $tenants], ['Free beds', $freeBeds],
  ['Rent collected (' . date('M') . ')', '₹' . number_format($collected, 2)],
  ['Rent pending (' . date('M') . ')', '₹' . number_format($pending, 2)],
  ['Pending complaints', $complaints],
];
?>
<h3 class="mb-3">Dashboard</h3>
<div class="row g-3">
<?php foreach ($cards as [$label, $value]): ?>
  <div class="col-6 col-md-4"><div class="card card-body">
    <div class="text-muted small"><?= e($label) ?></div><div class="fs-3 fw-bold"><?= e($value) ?></div>
  </div></div>
<?php endforeach; ?>
</div>

<h5 class="mt-4">Graphs</h5>
<div class="row g-3">
  <div class="col-lg-7"><div class="card card-body">
    <div class="fw-semibold mb-2">Rent collected (last 6 months)</div>
    <div style="height:260px"><canvas id="rentChart"></canvas></div>
  </div></div>
  <div class="col-lg-5"><div class="card card-body">
    <div class="fw-semibold mb-2">Rent status this month (tenants)</div>
    <div style="height:260px"><canvas id="statusChart"></canvas></div>
  </div></div>
  <div class="col-lg-7"><div class="card card-body">
    <div class="fw-semibold mb-2">Beds in each room (occupied vs available)</div>
    <div style="height:260px"><canvas id="roomChart"></canvas></div>
  </div></div>
  <div class="col-lg-5"><div class="card card-body">
    <div class="fw-semibold mb-2">Complaints</div>
    <div style="height:260px"><canvas id="complaintChart"></canvas></div>
  </div></div>
</div>
<p id="chartError" class="text-danger small mt-2" style="display:none">Graphs could not load (assets/chart.umd.js is missing and there is no internet).</p>

<script src="assets/chart.umd.js"></script>
<script>
// Data comes from PHP (database). Chart.js only draws it.
var DATA = {
  months: <?= json_encode($labels) ?>,           collected: <?= json_encode($collectedByMonth) ?>,
  rooms: <?= json_encode($roomNames) ?>,         occupied: <?= json_encode($roomOccupied) ?>, free: <?= json_encode($roomFree) ?>,
  status: <?= json_encode([$paidN, $partN, $unpaidN]) ?>,
  complaints: <?= json_encode([$pendingN, $resolvedN]) ?>
};
function drawCharts() {
  var green = '#198754', yellow = '#ffc107', red = '#dc3545', grey = '#adb5bd', dark = '#212529';
  var noGrid = { grid: { display: false } };

  new Chart(document.getElementById('rentChart'), { type: 'bar',
    data: { labels: DATA.months, datasets: [{ label: 'Rent collected (₹)', data: DATA.collected, backgroundColor: dark }] },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } } });

  new Chart(document.getElementById('statusChart'), { type: 'doughnut',
    data: { labels: ['Paid', 'Partly paid', 'Unpaid'], datasets: [{ data: DATA.status, backgroundColor: [green, yellow, red] }] },
    options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });

  new Chart(document.getElementById('roomChart'), { type: 'bar',
    data: { labels: DATA.rooms, datasets: [
      { label: 'Occupied beds', data: DATA.occupied, backgroundColor: dark },
      { label: 'Available beds', data: DATA.free, backgroundColor: green } ] },
    options: { maintainAspectRatio: false, scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } },
               plugins: { legend: { position: 'bottom' } } } });

  new Chart(document.getElementById('complaintChart'), { type: 'doughnut',
    data: { labels: ['Pending', 'Resolved'], datasets: [{ data: DATA.complaints, backgroundColor: [yellow, green] }] },
    options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
}
// Use the local Chart.js; if it is missing, load it from the internet instead.
window.addEventListener('load', function () {
  if (typeof Chart !== 'undefined') { drawCharts(); return; }
  var s = document.createElement('script');
  s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.js';
  s.onload = drawCharts;
  s.onerror = function () { document.getElementById('chartError').style.display = 'block'; };
  document.body.appendChild(s);
});
</script>

<div class="mt-4 d-flex gap-2 flex-wrap">
  <a class="btn btn-dark" href="tenant_form.php">+ Add tenant</a>
  <a class="btn btn-dark" href="room_form.php">+ Add room</a>
  <a class="btn btn-dark" href="payment_add.php">+ Record payment</a>
  <a class="btn btn-outline-dark" href="complaints.php">View complaints</a>
</div>
<?php include 'footer.php'; ?>
