<?php
// db.php の最初に session_start() を呼び出す前に確認
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// データベース接続やその他の処理


$dsn = 'mysql:host=localhost;dbname=bbs_app;charset=utf8mb4;port=8889';
$user = 'root';
$password = 'root';

try {
    $pdo = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage());
}
?>
