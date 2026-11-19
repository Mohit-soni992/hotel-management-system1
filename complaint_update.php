<?php
// complaint_update.php - mark a complaint as Resolved.
require_once __DIR__ . '/config.php';
login_required();
post_only();

$pdo->prepare("UPDATE complaints SET status = 'Resolved' WHERE id = ?")->execute([(int)($_POST['id'] ?? 0)]);
message('Complaint marked as resolved.');
redirect('complaints.php');
