<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
$success = '';
$error = '';

// 最初はDBから最新情報を取得：ブラウザでページを開いたときや、リンクをクリックしたとき 主に「データを取り出す・表示する」目的
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch();
    $name = $user['name'];
    $email = $user['email'];
} else {
    // POST: フォーム送信時
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // バリデーション
    if ($name === '' || $email === '') {
        $error = '名前とメールアドレスは必須です。';
    } else {
        if ($password) {
            // パスワードも変更
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET name = :name, email = :email, password = :password WHERE id = :id');
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => $hash,
                ':id' => $user_id
            ]);
        } else {
            // パスワード変更なし
            $stmt = $pdo->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id');
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':id' => $user_id
            ]);
        }
        // セッション情報も更新
        $_SESSION['user']['name'] = $name;
        $success = 'ユーザー情報を更新しました。';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ユーザー情報編集</title>
</head>
<body>
    <h1>会員情報編集</h1>

    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color: green;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="post">
        <label>名前: <input type="text" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required></label><br>
        <label>メールアドレス: <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required></label><br>
        <label>新しいパスワード: <input type="password" name="password"></label>（空欄なら変更なし）<br>
        <button type="submit" onclick="return confirm('この内容で更新しますか？')">更新する</button>
    </form>
    <p><a href="index.php">掲示板トップへ戻る</a></p>
</body>
</html>
