<?php
require_once __DIR__ . '/config.php';
session_destroy();          // forget the logged-in admin
header('Location: login.php');
