<?php
// complaint_add.php - register a complaint for an active tenant.
$title = 'New complaint';
include 'header.php';

$c = ['tenant_id' => 0, 'title' => '', 'description' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $c = ['tenant_id' => (int)($_POST['tenant_id'] ?? 0), 'title' => trim($_POST['title'] ?? ''), 'description' => trim($_POST['description'] ?? '')];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tenants WHERE id = ? AND status = 'Active'");
    $stmt->execute([$c['tenant_id']]);
    if (!$stmt->fetchColumn())                         $errors[] = 'Choose an active tenant.';
    if ($c['title'] === '' || strlen($c['title']) > 150) $errors[] = 'Title is required (max 150 characters).';
    if (strlen($c['description']) < 5)                 $errors[] = 'Please describe the problem (at least 5 characters).';

    if (!$errors) {
        $pdo->prepare('INSERT INTO complaints (tenant_id, title, description, created_at) VALUES (?,?,?,CURDATE())')
            ->execute([$c['tenant_id'], $c['title'], $c['description']]);
        message('Complaint saved.');
        redirect('complaints.php');
    }
}
$tenants = $pdo->query("SELECT t.id, t.name, r.room_number FROM tenants t JOIN rooms r ON r.id = t.room_id WHERE t.status='Active' ORDER BY t.name")->fetchAll();
?>
<h3>New complaint</h3>
<?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
<form method="post" class="card card-body" style="max-width:560px">
  <label class="form-label">Tenant</label>
  <select name="tenant_id" class="form-select mb-3" required><option value="">Choose a tenant</option>
    <?php foreach ($tenants as $t): ?><option value="<?= $t['id'] ?>" <?= $c['tenant_id'] == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?> (Room <?= e($t['room_number']) ?>)</option><?php endforeach; ?>
  </select>
  <label class="form-label">Title</label>
  <input name="title" class="form-control mb-3" maxlength="150" value="<?= e($c['title']) ?>" required>
  <label class="form-label">Details</label>
  <textarea name="description" class="form-control mb-3" rows="3" minlength="5" required><?= e($c['description']) ?></textarea>
  <div><button class="btn btn-dark">Save</button> <a class="btn btn-light" href="complaints.php">Cancel</a></div>
</form>
<?php include 'footer.php'; ?>
