<?php
session_start();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>新規登録</title>
</head>
<body>
    <h1>新規会員登録</h1>

    <form method="post" action="register_confirm.php">
        <label>名前: <input type="text" name="name" required></label><br>
        <label>メールアドレス: <input type="email" name="email" required></label><br>
        <label>パスワード: <input type="password" name="password" required></label><br>
        <button type="submit">確認画面へ</button>
    </form>

    <p><a href="login.php">ログインはこちら</a></p>
</body>
</html>
