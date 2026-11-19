<?php
// header.php - top of every page: login check + navigation bar.
require_once __DIR__ . '/config.php';
if (empty($public)) { login_required(); }
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'StayEase') ?> - StayEase</title>
<!-- Local Bootstrap first. If the file is missing, load it from the internet instead. -->
<link rel="stylesheet" href="assets/bootstrap.min.css"
      onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'">
</head>
<body class="bg-light">
<?php if (!file_exists(__DIR__ . '/assets/bootstrap.min.css')): ?>
<div style="background:#fff3cd;color:#664d03;padding:8px 12px;font:14px sans-serif;border-bottom:1px solid #ffecb5">
  Warning: <b>assets/bootstrap.min.css</b> is missing, so the design is loaded from the internet.
  Copy the <b>assets</b> folder from the original zip to fix this permanently.
</div>
<?php endif; ?>
<?php if (!empty($_SESSION['admin'])): ?>
<nav class="navbar navbar-expand-md navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php">StayEase</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="rooms.php">Rooms</a></li>
        <li class="nav-item"><a class="nav-link" href="tenants.php">Tenants</a></li>
        <li class="nav-item"><a class="nav-link" href="payments.php">Rent</a></li>
        <li class="nav-item"><a class="nav-link" href="complaints.php">Complaints</a></li>
      </ul>
      <span class="navbar-text me-3"><?= e($_SESSION['admin']['name']) ?></span>
      <a class="btn btn-outline-light btn-sm" href="logout.php">Logout</a>
    </div>
  </div>
</nav>
<?php endif; ?>
<div class="container pb-5">
<?php if (!empty($_SESSION['msg'])): [$type, $text] = $_SESSION['msg']; unset($_SESSION['msg']); ?>
  <div class="alert alert-<?= $type ?>"><?= e($text) ?></div>
<?php endif; ?>
