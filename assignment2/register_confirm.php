<?php
session_start();

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// 入力をセッションに保存（完了画面に渡す用）
$_SESSION['register'] = [
    'name' => $name,
    'email' => $email,
    'password' => password_hash($password, PASSWORD_DEFAULT)
];
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>登録内容の確認</title>
</head>
<body>
    <h1>登録内容の確認</h1>

    <p>名前: <?= htmlspecialchars($name) ?></p>
    <p>メール: <?= htmlspecialchars($email) ?></p>

    <form method="post" action="register_done.php">
        <button type="submit">この内容で登録する</button>
    </form>
    <p><a href="register.php">戻って修正する</a></p>
</body>
</html>
