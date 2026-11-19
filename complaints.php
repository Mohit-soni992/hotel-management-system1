<?php
// complaints.php - list + filter by status. "Mark resolved" button on each row.
$title = 'Complaints';
include 'header.php';

$status = $_GET['status'] ?? '';
$sql = 'SELECT c.*, t.name FROM complaints c JOIN tenants t ON t.id = c.tenant_id';
$params = [];
if ($status === 'Pending' || $status === 'Resolved') { $sql .= ' WHERE c.status = ?'; $params[] = $status; }
$stmt = $pdo->prepare($sql . ' ORDER BY c.status, c.id DESC');
$stmt->execute($params);
$list = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between mb-3">
  <h3>Complaints</h3><a class="btn btn-dark" href="complaint_add.php">+ New complaint</a>
</div>
<form class="row g-2 mb-3"><div class="col-auto"><select name="status" class="form-select" onchange="this.form.submit()">
  <option value="">All</option>
  <option <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
  <option <?= $status === 'Resolved' ? 'selected' : '' ?>>Resolved</option></select></div></form>

<?php if (!$list): ?><div class="alert alert-info">No complaints found.</div><?php else: ?>
<div class="table-responsive"><table class="table table-bordered bg-white align-middle">
  <tr class="table-light"><th>Date</th><th>Tenant</th><th>Title</th><th>Details</th><th>Status</th><th></th></tr>
  <?php foreach ($list as $c): ?>
  <tr>
    <td><?= e($c['created_at']) ?></td><td><?= e($c['name']) ?></td><td><?= e($c['title']) ?></td><td><?= e($c['description']) ?></td>
    <td><?= $c['status'] === 'Pending' ? '<span class="badge bg-warning text-dark">Pending</span>' : '<span class="badge bg-success">Resolved</span>' ?></td>
    <td><?php if ($c['status'] === 'Pending'): ?>
      <form method="post" action="complaint_update.php"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn btn-sm btn-outline-success">Mark resolved</button></form>
    <?php endif; ?></td>
  </tr>
  <?php endforeach; ?>
</table></div>
<?php endif; include 'footer.php'; ?>
