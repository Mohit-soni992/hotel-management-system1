<?php
// config.php - included by every page: starts the session and connects to MySQL.
session_start();

$pdo = new PDO(
    'mysql:host=localhost;dbname=stayease_mini;charset=utf8mb4',
    'root',          // XAMPP default user
    '',              // XAMPP default password (empty)
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // show errors as exceptions
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // rows come as arrays
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ]
);

// Safe output: stops users from injecting HTML/JavaScript (XSS).
function e($text) { return htmlspecialchars((string)$text); }

// Go to another page.
function redirect($page) { header("Location: $page"); exit; }

// Show a message on the next page (green = success, red = error).
function message($text, $type = 'success') { $_SESSION['msg'] = [$type, $text]; }

// Only logged-in admins may see a page.
function login_required() {
    if (empty($_SESSION['admin'])) { redirect('login.php'); }
}

// Delete/checkout buttons must be POST, never a plain link.
function post_only() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit('Use the button on the page.'); }
}

// Valid 10-digit Indian mobile number?
function valid_phone($p) { return preg_match('/^[6-9][0-9]{9}$/', $p) === 1; }
