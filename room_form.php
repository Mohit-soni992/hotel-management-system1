<?php
// room_form.php - CREATE (no id) and UPDATE (with ?id=) of a room, in one file.
$title = 'Room';
include 'header.php';

$id   = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$room = ['room_number' => '', 'room_type' => 'Double', 'capacity' => 2, 'rent' => ''];
$errors = [];

if ($id) {                                   // editing: load the existing room
    $stmt = $pdo->prepare('SELECT * FROM rooms WHERE id = ?');
    $stmt->execute([$id]);
    $room = $stmt->fetch();
    if (!$room) { message('Room not found.', 'danger'); redirect('rooms.php'); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room = [
        'room_number' => trim($_POST['room_number'] ?? ''),
        'room_type'   => $_POST['room_type'] ?? '',
        'capacity'    => (int)($_POST['capacity'] ?? 0),
        'rent'        => trim($_POST['rent'] ?? ''),
    ];

    // ----- server-side validation -----
    if ($room['room_number'] === '' || strlen($room['room_number']) > 20) $errors[] = 'Room number is required.';
    if (!in_array($room['room_type'], ['Single', 'Double', 'Triple', 'Dormitory'])) $errors[] = 'Choose a room type.';
    if ($room['capacity'] < 1 || $room['capacity'] > 20) $errors[] = 'Beds must be between 1 and 20.';
    if (!is_numeric($room['rent']) || $room['rent'] <= 0) $errors[] = 'Rent must be more than 0.';

    // duplicate room number?
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM rooms WHERE room_number = ? AND id <> ?');
    $stmt->execute([$room['room_number'], $id]);
    if ($stmt->fetchColumn() > 0) $errors[] = 'This room number already exists.';

    // cannot reduce beds below the people already living there
    if ($id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tenants WHERE room_id = ? AND status = 'Active'");
        $stmt->execute([$id]);
        if ($room['capacity'] < $stmt->fetchColumn()) $errors[] = 'Beds cannot be less than current tenants in this room.';
    }

    if (!$errors) {
        if ($id) {
            $pdo->prepare('UPDATE rooms SET room_number=?, room_type=?, capacity=?, rent=? WHERE id=?')
                ->execute([$room['room_number'], $room['room_type'], $room['capacity'], $room['rent'], $id]);
            message('Room updated.');
        } else {
            $pdo->prepare('INSERT INTO rooms (room_number, room_type, capacity, rent) VALUES (?,?,?,?)')
                ->execute([$room['room_number'], $room['room_type'], $room['capacity'], $room['rent']]);
            message('Room added.');
        }
        redirect('rooms.php');
    }
}
?>
<h3><?= $id ? 'Edit' : 'Add' ?> room</h3>
<?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
<form method="post" class="card card-body" style="max-width:520px">
  <input type="hidden" name="id" value="<?= $id ?>">
  <label class="form-label">Room number</label>
  <input name="room_number" class="form-control mb-3" value="<?= e($room['room_number']) ?>" required maxlength="20">
  <label class="form-label">Room type</label>
  <select name="room_type" class="form-select mb-3">
    <?php foreach (['Single', 'Double', 'Triple', 'Dormitory'] as $t): ?>
      <option <?= $room['room_type'] === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
  </select>
  <label class="form-label">Number of beds</label>
  <input type="number" name="capacity" min="1" max="20" class="form-control mb-3" value="<?= e($room['capacity']) ?>" required>
  <label class="form-label">Monthly rent per bed (₹)</label>
  <input type="number" step="0.01" min="1" name="rent" class="form-control mb-3" value="<?= e($room['rent']) ?>" required>
  <div><button class="btn btn-dark">Save</button> <a class="btn btn-light" href="rooms.php">Cancel</a></div>
</form>
<?php include 'footer.php'; ?>
