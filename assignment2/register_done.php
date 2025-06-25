<?php
session_start();
require_once('db.php');

$register = $_SESSION['register'] ?? null;

if (!$register) {
    header('Location: register.php');
    exit;
}

// ユーザー登録処理
$stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (:name, :email, :password)');
$stmt->execute([
    ':name' => $register['name'],
    ':email' => $register['email'],
    ':password' => $register['password']
]);

// 登録後に自動ログイン
$_SESSION['user'] = [
    'name' => $register['name'],
    'id' => $pdo->lastInsertId()
];

// 登録情報クリア
unset($_SESSION['register']);

header('Location: index.php');
exit;
