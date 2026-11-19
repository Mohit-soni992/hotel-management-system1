<?php
// payment_add.php - record a payment (full or partial) for one month.
$title = 'Record payment';
include 'header.php';

$p = ['tenant_id' => (int)($_GET['tenant_id'] ?? 0), 'month' => $_GET['month'] ?? date('Y-m'),
      'amount' => '', 'payment_date' => date('Y-m-d'), 'method' => 'Cash'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p = ['tenant_id' => (int)($_POST['tenant_id'] ?? 0), 'month' => $_POST['month'] ?? '',
          'amount' => trim($_POST['amount'] ?? ''), 'payment_date' => $_POST['payment_date'] ?? '', 'method' => $_POST['method'] ?? ''];

    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $p['month'])) $errors[] = 'Choose a valid month.';
    if (!in_array($p['method'], ['Cash', 'UPI']))              $errors[] = 'Choose Cash or UPI.';
    if (!is_numeric($p['amount']) || $p['amount'] <= 0)        $errors[] = 'Amount must be more than 0.';
    if (!strtotime($p['payment_date']) || $p['payment_date'] > date('Y-m-d')) $errors[] = 'Payment date cannot be in the future.';

    // find the tenant's rent and how much is already paid for that month
    $stmt = $pdo->prepare("SELECT r.rent FROM tenants t JOIN rooms r ON r.id = t.room_id WHERE t.id = ? AND t.status = 'Active'");
    $stmt->execute([$p['tenant_id']]);
    $rent = $stmt->fetchColumn();
    if ($rent === false) {
        $errors[] = 'Choose an active tenant.';
    } elseif (!$errors) {
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE tenant_id = ? AND month = ?');
        $stmt->execute([$p['tenant_id'], $p['month']]);
        $balance = $rent - $stmt->fetchColumn();
        if ($balance <= 0)              $errors[] = 'This month is already fully paid.';
        elseif ($p['amount'] > $balance) $errors[] = 'Amount is more than the balance (₹' . number_format($balance, 2) . ').';
    }

    if (!$errors) {
        $pdo->prepare('INSERT INTO payments (tenant_id, month, amount, payment_date, method) VALUES (?,?,?,?,?)')
            ->execute([$p['tenant_id'], $p['month'], $p['amount'], $p['payment_date'], $p['method']]);
        message('Payment of ₹' . number_format($p['amount'], 2) . ' saved.');
        redirect('payments.php?month=' . $p['month']);
    }
}

$tenants = $pdo->query("SELECT t.id, t.name, r.room_number, r.rent FROM tenants t JOIN rooms r ON r.id = t.room_id WHERE t.status='Active' ORDER BY t.name")->fetchAll();
?>
<h3>Record payment</h3>
<?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
<form method="post" class="card card-body" style="max-width:520px">
  <label class="form-label">Tenant</label>
  <select name="tenant_id" class="form-select mb-3" required>
    <option value="">Choose a tenant</option>
    <?php foreach ($tenants as $t): ?>
      <option value="<?= $t['id'] ?>" <?= $p['tenant_id'] == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?> (Room <?= e($t['room_number']) ?>, ₹<?= $t['rent'] ?>)</option>
    <?php endforeach; ?>
  </select>
  <label class="form-label">Rent month</label>
  <input type="month" name="month" class="form-control mb-3" value="<?= e($p['month']) ?>" required>
  <label class="form-label">Amount (₹)</label>
  <input type="number" step="0.01" min="1" name="amount" class="form-control mb-3" value="<?= e($p['amount']) ?>" required>
  <label class="form-label">Payment date</label>
  <input type="date" name="payment_date" class="form-control mb-3" value="<?= e($p['payment_date']) ?>" max="<?= date('Y-m-d') ?>" required>
  <label class="form-label">Method</label>
  <select name="method" class="form-select mb-3"><?php foreach (['Cash', 'UPI'] as $m): ?><option <?= $p['method'] === $m ? 'selected' : '' ?>><?= $m ?></option><?php endforeach; ?></select>
  <div><button class="btn btn-dark">Save payment</button> <a class="btn btn-light" href="payments.php">Cancel</a></div>
</form>
<?php include 'footer.php'; ?>
