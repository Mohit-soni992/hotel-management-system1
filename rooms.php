<?php
// rooms.php - READ: list of rooms with search + filter.
$title = 'Rooms';
include 'header.php';

$q      = trim($_GET['q'] ?? '');
$filter = $_GET['status'] ?? '';

// "occupied" = number of ACTIVE tenants living in the room (counted, never stored)
$sql = "SELECT * FROM (
          SELECT r.*, (SELECT COUNT(*) FROM tenants t WHERE t.room_id = r.id AND t.status = 'Active') AS occupied
          FROM rooms r) x
        WHERE room_number LIKE ?";
if ($filter === 'Available') { $sql .= ' AND occupied < capacity'; }
if ($filter === 'Full')      { $sql .= ' AND occupied >= capacity'; }
$sql .= ' ORDER BY room_number';

$stmt = $pdo->prepare($sql);
$stmt->execute(["%$q%"]);
$rooms = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between mb-3">
  <h3>Rooms</h3><a class="btn btn-dark" href="room_form.php">+ Add room</a>
</div>
<form class="row g-2 mb-3">
  <div class="col-md-4"><input name="q" class="form-control" placeholder="Search room number" value="<?= e($q) ?>"></div>
  <div class="col-md-3"><select name="status" class="form-select">
    <option value="">All rooms</option>
    <option <?= $filter === 'Available' ? 'selected' : '' ?>>Available</option>
    <option <?= $filter === 'Full' ? 'selected' : '' ?>>Full</option></select></div>
  <div class="col-md-3"><button class="btn btn-secondary">Search</button> <a class="btn btn-light" href="rooms.php">Reset</a></div>
</form>

<?php if (!$rooms): ?><div class="alert alert-info">No rooms found.</div><?php else: ?>
<div class="table-responsive"><table class="table table-bordered bg-white align-middle">
  <tr class="table-light"><th>Room</th><th>Type</th><th>Beds</th><th>Occupied</th><th>Free</th><th>Rent / bed</th><th>Status</th><th>Actions</th></tr>
  <?php foreach ($rooms as $r): $free = $r['capacity'] - $r['occupied']; ?>
  <tr>
    <td><?= e($r['room_number']) ?></td><td><?= e($r['room_type']) ?></td><td><?= $r['capacity'] ?></td>
    <td><?= $r['occupied'] ?></td><td><?= $free ?></td><td>₹<?= number_format($r['rent'], 2) ?></td>
    <td><?= $free > 0 ? '<span class="badge bg-success">Available</span>' : '<span class="badge bg-danger">Full</span>' ?></td>
    <td class="text-nowrap">
      <a class="btn btn-sm btn-outline-primary" href="room_form.php?id=<?= $r['id'] ?>">Edit</a>
      <form method="post" action="room_delete.php" class="d-inline" onsubmit="return confirm('Delete room <?= e($r['room_number']) ?>?')">
        <input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table></div>
<?php endif; include 'footer.php'; ?>
