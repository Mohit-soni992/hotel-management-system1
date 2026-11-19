<?php
// tenant_checkout.php - tenant leaves. We do NOT delete: status becomes 'Left',
// so old payments stay in the records and the bed becomes free automatically.
require_once __DIR__ . '/config.php';
login_required();
post_only();

$id = (int)($_POST['id'] ?? 0);
$pdo->prepare("UPDATE tenants SET status = 'Left', leaving_date = CURDATE() WHERE id = ? AND status = 'Active'")->execute([$id]);
message('Tenant checked out. The bed is free now.');
redirect('tenants.php');
