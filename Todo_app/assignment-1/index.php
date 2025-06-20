<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = '127.0.0.1';
$port = '8889';
$dbname = 'laravel';
$username = 'root';
$password = 'root';

$debug = [];
$errors = [];
$edit_task = null;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $debug[] = "DB接続成功";
} catch (PDOException $e) {
    $errors[] = 'DB接続エラー: ' . $e->getMessage();
    $debug[] = 'DB接続エラー: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debug[] = 'フォームが送信されました（POST受信）';

    // 編集・削除・追加の分岐
    $type = $_POST['type'] ?? '';
    $debug[] = 'リクエストtype: ' . $type;

    // タスク追加
    if ($type === 'create') {
        $title = trim($_POST['title'] ?? '');
        $debug[] = '追加タイトル: ' . $title;

        if ($title === '') {
            $errors[] = 'タイトルを入力してください';
            $debug[] = 'バリデーション: タイトルが空';
        } elseif (mb_strlen($title) > 50) {
            $errors[] = 'タイトルは50文字以内で入力してください';
            $debug[] = 'バリデーション: タイトル長すぎ';
        }
        if (empty($errors) && isset($pdo)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO tasks (title) VALUES (:title)");
                $stmt->execute([':title' => $title]);
                $debug[] = 'DBへのINSERT成功';

               // echo "<h2>デバッグ情報</h2><ul>";
                //foreach ($debug as $d) echo "<li>" . htmlspecialchars($d, ENT_QUOTES, 'UTF-8') . "</li>";
                //echo "</ul><p>1秒後にリダイレクトします。</p>";
                //header("Refresh:1; URL=" . $_SERVER['PHP_SELF']);
                //exit;
            } catch (PDOException $e) {
                $errors[] = '保存時にエラー: ' . $e->getMessage();
                $debug[] = 'PDOエラー: ' . $e->getMessage();
            }
        }
    }

    // タスク編集（編集フォーム表示）
    if ($type === 'edit' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $debug[] = "編集フォーム表示リクエスト: id={$id}";
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $edit_task = $stmt->fetch(PDO::FETCH_ASSOC);
        $debug[] = '編集タスク取得: ' . print_r($edit_task, true);
    }

    // タスク更新
    if ($type === 'update' && isset($_POST['id'], $_POST['title'])) {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title']);
        $debug[] = "更新タイトル: {$title} (id={$id})";

        if ($title === '') {
            $errors[] = 'タイトルを入力してください';
            $debug[] = 'バリデーション: タイトルが空';
        } elseif (mb_strlen($title) > 50) {
            $errors[] = 'タイトルは50文字以内で入力してください';
            $debug[] = 'バリデーション: タイトル長すぎ';
        }
        if (empty($errors) && isset($pdo)) {
            try {
                $stmt = $pdo->prepare("UPDATE tasks SET title = :title WHERE id = :id");
                $stmt->execute([':title' => $title, ':id' => $id]);
                $debug[] = 'DBへのUPDATE成功';

                //echo "<h2>デバッグ情報</h2><ul>";
                //foreach ($debug as $d) echo "<li>" . htmlspecialchars($d, ENT_QUOTES, 'UTF-8') . "</li>";
                //echo "</ul><p>1秒後にリダイレクトします。</p>";
                //header("Refresh:1; URL=" . $_SERVER['PHP_SELF']);
                //exit;
            } catch (PDOException $e) {
                $errors[] = '保存時にエラー: ' . $e->getMessage();
                $debug[] = 'PDOエラー: ' . $e->getMessage();
            }
        } else {
            $edit_task = ['id' => $id, 'title' => $title];
        }
    }

    // タスク削除
    if ($type === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $debug[] = "削除リクエスト: id={$id}";
        try {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $debug[] = 'DBへのDELETE成功';

           //echo "<h2>デバッグ情報</h2><ul>";
           // foreach ($debug as $d) echo "<li>" . htmlspecialchars($d, ENT_QUOTES, 'UTF-8') . "</li>";
            //echo "</ul><p>1秒後にリダイレクトします。</p>";
            //header("Refresh:1; URL=" . $_SERVER['PHP_SELF']);
            //exit;
        } catch (PDOException $e) {
            $errors[] = '削除時にエラー: ' . $e->getMessage();
            $debug[] = 'PDOエラー: ' . $e->getMessage();
        }
    }
}

// タスク一覧
$stmt = $pdo->query("SELECT * FROM tasks ORDER BY created_at DESC");
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Todo List</title>
    <style>
        body { font-family: sans-serif; margin: 2em; }
        .error { color: red; }
        .debug { color: #444; font-size: 0.95em; background: #f8f8f8; border: 1px solid #ddd; padding: 10px; margin-top: 1em;}
    </style>
</head>
<body>
    <h1>Todo List</h1>

    <!-- タスク追加フォーム または 編集フォーム -->
    <?php if ($edit_task): ?>
        <form method="post">
            <input type="hidden" name="type" value="update">
            <input type="hidden" name="id" value="<?= htmlspecialchars($edit_task['id'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="title" value="<?= htmlspecialchars($edit_task['title'], ENT_QUOTES, 'UTF-8') ?>" maxlength="50" required>
            <button type="submit">更新</button>
            <a href="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">キャンセル</a>
        </form>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="type" value="create">
            <input type="text" name="title" placeholder="新しいタスク" maxlength="50" required>
            <button type="submit">追加</button>
        </form>
    <?php endif; ?>

    <!-- エラーメッセージ表示 -->
    <?php if ($errors): ?>
        <ul class="error">
            <?php foreach ($errors as $msg): ?>
                <li><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- タスク一覧 -->
    <ul>
        <?php foreach ($tasks as $task): ?>
            <li>
                <?= htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8') ?>
                <!-- 編集ボタン -->
                <form method="post" style="display:inline;">
                    <input type="hidden" name="type" value="edit">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($task['id'], ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit">編集</button>
                </form>
                <!-- 削除ボタン -->
                <form method="post" style="display:inline;">
                    <input type="hidden" name="type" value="delete">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($task['id'], ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" onclick="return confirm('削除してよいですか？');">削除</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- デバッグ情報 -->
    <?php if ($debug): ?>
        <div class="debug">
            <strong>情報:</strong>
            <ul>
                <?php foreach ($debug as $d): ?>
                    <li><?= htmlspecialchars($d, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</body>
</html>
