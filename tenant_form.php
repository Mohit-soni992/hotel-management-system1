<?php
// tenant_form.php - CREATE and UPDATE of a tenant.
$title = 'Tenant';
include 'header.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$t  = ['name' => '', 'phone' => '', 'guardian_name' => '', 'address' => '', 'joining_date' => date('Y-m-d'), 'room_id' => (int)($_GET['room_id'] ?? 0)];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM tenants WHERE id = ?');
    $stmt->execute([$id]);
    $t = $stmt->fetch();
    if (!$t) { message('Tenant not found.', 'danger'); redirect('tenants.php'); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $t = [
        'name' => trim($_POST['name'] ?? ''), 'phone' => trim($_POST['phone'] ?? ''),
        'guardian_name' => trim($_POST['guardian_name'] ?? ''), 'address' => trim($_POST['address'] ?? ''),
        'joining_date' => $_POST['joining_date'] ?? '', 'room_id' => (int)($_POST['room_id'] ?? 0),
    ];

    // ----- validation -----
    if ($t['name'] === '')          $errors[] = 'Name is required.';
    if (!valid_phone($t['phone']))  $errors[] = 'Enter a valid 10-digit mobile number.';
    if ($t['guardian_name'] === '') $errors[] = 'Guardian name is required.';
    if ($t['address'] === '')       $errors[] = 'Address is required.';
    if (!strtotime($t['joining_date']) || $t['joining_date'] > date('Y-m-d')) $errors[] = 'Choose a valid joining date (not in the future).';

    // the room must exist and must have a free bed
    $stmt = $pdo->prepare('SELECT capacity FROM rooms WHERE id = ?');
    $stmt->execute([$t['room_id']]);
    $capacity = $stmt->fetchColumn();
    if ($capacity === false) {
        $errors[] = 'Choose a room.';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tenants WHERE room_id = ? AND status = 'Active' AND id <> ?");
        $stmt->execute([$t['room_id'], $id]);
        if ($stmt->fetchColumn() >= $capacity) $errors[] = 'This room is full. Choose another room.';
    }

    if (!$errors) {
        if ($id) {
            $pdo->prepare('UPDATE tenants SET name=?, phone=?, guardian_name=?, address=?, joining_date=?, room_id=? WHERE id=?')
                ->execute([$t['name'], $t['phone'], $t['guardian_name'], $t['address'], $t['joining_date'], $t['room_id'], $id]);
            message('Tenant updated.');
        } else {
            $pdo->prepare('INSERT INTO tenants (name, phone, guardian_name, address, joining_date, room_id) VALUES (?,?,?,?,?,?)')
                ->execute([$t['name'], $t['phone'], $t['guardian_name'], $t['address'], $t['joining_date'], $t['room_id']]);
            message('Tenant added.');
        }
        redirect('tenants.php');
    }
}

// rooms for the drop-down, with how many beds are free
$rooms = $pdo->query("SELECT r.*, r.capacity - (SELECT COUNT(*) FROM tenants t WHERE t.room_id = r.id AND t.status='Active') AS free_beds
                      FROM rooms r ORDER BY room_number")->fetchAll();
?>
<h3><?= $id ? 'Edit' : 'Add' ?> tenant</h3>
<?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
<form method="post" class="card card-body" style="max-width:620px">
  <input type="hidden" name="id" value="<?= $id ?>">
  <label class="form-label">Full name</label>
  <input name="name" class="form-control mb-3" value="<?= e($t['name']) ?>" required>
  <label class="form-label">Phone (10 digits)</label>
  <input name="phone" class="form-control mb-3" value="<?= e($t['phone']) ?>" pattern="[6-9][0-9]{9}" maxlength="10" required>
  <label class="form-label">Guardian name</label>
  <input name="guardian_name" class="form-control mb-3" value="<?= e($t['guardian_name']) ?>" required>
  <label class="form-label">Address</label>
  <textarea name="address" class="form-control mb-3" rows="2" required><?= e($t['address']) ?></textarea>
  <label class="form-label">Joining date</label>
  <input type="date" name="joining_date" class="form-control mb-3" value="<?= e($t['joining_date']) ?>" max="<?= date('Y-m-d') ?>" required>
  <label class="form-label">Room</label>
  <select name="room_id" class="form-select mb-3" required>
    <option value="">Choose a room</option>
    <?php foreach ($rooms as $r): ?>
      <option value="<?= $r['id'] ?>" <?= (int)$t['room_id'] === (int)$r['id'] ? 'selected' : '' ?>>
        Room <?= e($r['room_number']) ?> (<?= e($r['room_type']) ?>) - <?= max(0, $r['free_beds']) ?> free - ₹<?= $r['rent'] ?>
      </option>
    <?php endforeach; ?>
  </select>
  <div><button class="btn btn-dark">Save</button> <a class="btn btn-light" href="tenants.php">Cancel</a></div>
</form>
<?php include 'footer.php'; ?>
