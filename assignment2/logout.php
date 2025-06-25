<?php
session_start();
require_once('db.php');

if (isset($_SESSION['user']['id'])) {
    $userId = $_SESSION['user']['id'];
    $stmt = $pdo->prepare("UPDATE posts SET deleted_at = NOW() WHERE user_id = :id");
    $stmt->execute([':id' => $userId]);
}

$_SESSION = [];//セッションの「中身」を全部消す
session_destroy();//セッション自体も完全に消す
ob_clean();//PHPがバッファしてた画面出力内容をリセット

header('Location: login.php');
exit;
