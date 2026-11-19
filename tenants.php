<?php
// tenants.php - list of tenants with search (name/phone) and status filter.
$title = 'Tenants';
include 'header.php';

$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'Active';          // default: show current tenants

$sql    = 'SELECT t.*, r.room_number FROM tenants t JOIN rooms r ON r.id = t.room_id WHERE (t.name LIKE ? OR t.phone LIKE ?)';
$params = ["%$q%", "%$q%"];
if ($status === 'Active' || $status === 'Left') { $sql .= ' AND t.status = ?'; $params[] = $status; }
$sql .= ' ORDER BY t.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tenants = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between mb-3">
  <h3>Tenants</h3><a class="btn btn-dark" href="tenant_form.php">+ Add tenant</a>
</div>
<form class="row g-2 mb-3">
  <div class="col-md-4"><input name="q" class="form-control" placeholder="Search name or phone" value="<?= e($q) ?>"></div>
  <div class="col-md-3"><select name="status" class="form-select">
    <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
    <option value="Left"   <?= $status === 'Left' ? 'selected' : '' ?>>Left</option>
    <option value="All"    <?= $status === 'All' ? 'selected' : '' ?>>All</option></select></div>
  <div class="col-md-3"><button class="btn btn-secondary">Search</button> <a class="btn btn-light" href="tenants.php">Reset</a></div>
</form>

<?php if (!$tenants): ?><div class="alert alert-info">No tenants found.</div><?php else: ?>
<div class="table-responsive"><table class="table table-bordered bg-white align-middle">
  <tr class="table-light"><th>Name</th><th>Phone</th><th>Room</th><th>Joined</th><th>Guardian</th><th>Status</th><th>Actions</th></tr>
  <?php foreach ($tenants as $t): ?>
  <tr>
    <td><?= e($t['name']) ?></td><td><?= e($t['phone']) ?></td><td><?= e($t['room_number']) ?></td>
    <td><?= e($t['joining_date']) ?></td><td><?= e($t['guardian_name']) ?></td>
    <td><?= $t['status'] === 'Active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Left ' . e($t['leaving_date']) . '</span>' ?></td>
    <td class="text-nowrap">
      <a class="btn btn-sm btn-outline-primary" href="tenant_form.php?id=<?= $t['id'] ?>">Edit</a>
      <?php if ($t['status'] === 'Active'): ?>
      <form method="post" action="tenant_checkout.php" class="d-inline" onsubmit="return confirm('Check out <?= e($t['name']) ?>?')">
        <input type="hidden" name="id" value="<?= $t['id'] ?>"><button class="btn btn-sm btn-outline-danger">Check out</button>
      </form>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table></div>
<?php endif; include 'footer.php'; ?>
