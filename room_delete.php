<?php
// room_delete.php - DELETE a room (only if nobody ever stayed in it).
require_once __DIR__ . '/config.php';
login_required();
post_only();

$id = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT COUNT(*) FROM tenants WHERE room_id = ?');
$stmt->execute([$id]);

if ($stmt->fetchColumn() > 0) {
    message('Cannot delete: tenants (current or old) are linked to this room.', 'danger');
} else {
    $pdo->prepare('DELETE FROM rooms WHERE id = ?')->execute([$id]);
    message('Room deleted.');
}
redirect('rooms.php');
