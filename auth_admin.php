<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$admins = [
    'tachfin213@gmail.com',
    'mouhandbedjou687@gmail.com'
];

$email = strtolower($_SESSION['email'] ?? '');

if (!$email || !in_array($email, array_map('strtolower', $admins))) {
    header('Location: index.php');
    exit;
}
?>