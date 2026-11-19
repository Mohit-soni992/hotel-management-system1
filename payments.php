<?php
// payments.php - who has paid / who is pending for a month + payment history.
$title = 'Rent';
include 'header.php';

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) { $month = date('Y-m'); }

// For every active tenant: rent (from room) - paid so far = balance
$stmt = $pdo->prepare("SELECT t.id, t.name, r.room_number, r.rent, COALESCE(p.paid,0) AS paid, r.rent - COALESCE(p.paid,0) AS balance
    FROM tenants t JOIN rooms r ON r.id = t.room_id
    LEFT JOIN (SELECT tenant_id, SUM(amount) AS paid FROM payments WHERE month = ? GROUP BY tenant_id) p ON p.tenant_id = t.id
    WHERE t.status = 'Active' ORDER BY t.name");
$stmt->execute([$month]);
$dues = $stmt->fetchAll();

// Payment history (latest first)
$stmt = $pdo->prepare('SELECT p.*, t.name FROM payments p JOIN tenants t ON t.id = p.tenant_id WHERE p.month = ? ORDER BY p.id DESC');
$stmt->execute([$month]);
$history = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between mb-3">
  <h3>Rent - <?= e(date('F Y', strtotime($month . '-01'))) ?></h3>
  <a class="btn btn-dark" href="payment_add.php?month=<?= e($month) ?>">+ Record payment</a>
</div>
<form class="row g-2 mb-3"><div class="col-auto"><input type="month" name="month" value="<?= e($month) ?>" class="form-control"></div>
  <div class="col-auto"><button class="btn btn-secondary">Show</button></div></form>

<h5>Rent status</h5>
<?php if (!$dues): ?><div class="alert alert-info">No active tenants.</div><?php else: ?>
<div class="table-responsive"><table class="table table-bordered bg-white align-middle">
  <tr class="table-light"><th>Tenant</th><th>Room</th><th>Rent</th><th>Paid</th><th>Balance</th><th>Status</th><th></th></tr>
  <?php foreach ($dues as $d): ?>
  <tr>
    <td><?= e($d['name']) ?></td><td><?= e($d['room_number']) ?></td><td>₹<?= number_format($d['rent'], 2) ?></td>
    <td>₹<?= number_format($d['paid'], 2) ?></td><td>₹<?= number_format(max(0, $d['balance']), 2) ?></td>
    <td><?php if ($d['balance'] <= 0): ?><span class="badge bg-success">Paid</span>
        <?php elseif ($d['paid'] > 0): ?><span class="badge bg-warning text-dark">Partly paid</span>
        <?php else: ?><span class="badge bg-danger">Unpaid</span><?php endif; ?></td>
    <td><?php if ($d['balance'] > 0): ?><a class="btn btn-sm btn-outline-dark" href="payment_add.php?tenant_id=<?= $d['id'] ?>&month=<?= e($month) ?>">Add payment</a><?php endif; ?></td>
  </tr>
  <?php endforeach; ?>
</table></div>
<?php endif; ?>

<h5 class="mt-4">Payments received</h5>
<?php if (!$history): ?><div class="alert alert-info">No payments for this month.</div><?php else: ?>
<div class="table-responsive"><table class="table table-bordered bg-white">
  <tr class="table-light"><th>#</th><th>Tenant</th><th>Amount</th><th>Date</th><th>Method</th></tr>
  <?php foreach ($history as $h): ?>
  <tr><td><?= $h['id'] ?></td><td><?= e($h['name']) ?></td><td>₹<?= number_format($h['amount'], 2) ?></td><td><?= e($h['payment_date']) ?></td><td><?= e($h['method']) ?></td></tr>
  <?php endforeach; ?>
</table></div>
<?php endif; include 'footer.php'; ?>
