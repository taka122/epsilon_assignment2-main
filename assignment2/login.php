<?php
session_start();//PHPで「セッション（session）」という機能を使うための命令（関数）
require_once('db.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email !== '' && $password !== '') {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        
        //password_verify：入力パスワードをハッシュ化した値と比較するPHP関数
        //$_SESSION[‘user’]：ログインしたユーザーの情報を「セッション」に保存
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name']
            ];
            header('Location: index.php');
            exit;
        } else {
            $error = 'メールアドレスまたはパスワードが正しくありません';
        }
    } else {
        $error = 'メールアドレスとパスワードを入力してください';
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログイン</title>
</head>
<body>
    <h1>ログイン</h1>

    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <label>メールアドレス: <input type="email" name="email" required></label><br>
        <label>パスワード: <input type="password" name="password" required></label><br>
        <button type="submit">ログイン</button>
    </form>

    <p><a href="register.php">新規登録はこちら</a></p>
</body>
</html>
