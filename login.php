<?php
// login.php - admin login using email + password.
require_once __DIR__ . '/config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1. find the admin by email (prepared statement = safe from SQL injection)
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ?');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    // 2. compare typed password with the stored hash
    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = ['id' => $admin['id'], 'name' => $admin['name']];
        redirect('index.php');
    }
    $error = 'Wrong email or password.';
}

$title = 'Login'; $public = true;
include 'header.php';
?>
<div class="row justify-content-center mt-5"><div class="col-md-4">
  <h3 class="text-center mb-3">StayEase Login</h3>
  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="card card-body">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control mb-3" required>
    <label class="form-label">Password</label>
    <input type="password" name="password" class="form-control mb-3" required>
    <button class="btn btn-dark">Login</button>
  </form>
  <p class="text-muted small text-center mt-3">Demo: admin@stayease.com / admin123</p>
</div></div>
<?php include 'footer.php'; ?>
