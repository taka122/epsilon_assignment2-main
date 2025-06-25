<?php
//エラーが出た時表示
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = '127.0.0.1';
$port = '8889';
$dbname = 'laravel';
$username = 'root';
$password = 'root';


$debug = [];
$errors = [];
$warnings = [];
$edit_task = null;

// DB接続（tryがエラーならcatchへジャンプ）
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $debug[] = "DB接続成功";
} catch (PDOException $e) {
    $errors[] = 'DB接続エラー: ' . $e->getMessage();
    $debug[] = 'DB接続エラー: ' . $e->getMessage();
}

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debug[] = 'フォームが送信されました（POST受信）';
    $type = $_POST['type'] ?? '';
    $debug[] = 'リクエストtype: ' . $type;

    // create
    if ($type === 'create') {
        $title = trim($_POST['title'] ?? '');
        $debug[] = '追加タイトル: ' . $title;

        if ($title === '') {
            $errors[] = 'タイトルを入力してください';
            $debug[] = 'バリデーション: タイトルが空';
        } elseif (mb_strlen($title) > 50) {
            $errors[] = 'タイトルは50文字以内が推奨です';
            $debug[] = 'バリデーション: タイトル長すぎ（警告）';
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO tasks (title) VALUES (:title)");
                $stmt->execute([':title' => $title]);
                $debug[] = 'DBへのINSERT成功';
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } catch (PDOException $e) {
                $errors[] = '保存時にエラー: ' . $e->getMessage();
                $debug[] = 'PDOエラー: ' . $e->getMessage();
            }
        }
    }

    // edit
    if ($type === 'edit' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $edit_task = $stmt->fetch(PDO::FETCH_ASSOC);//fetch:取り出す
        $debug[] = "編集フォーム表示: id={$id}";
    }

    // update
    if ($type === 'update' && isset($_POST['id'], $_POST['title'])) {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title']);
        $debug[] = "更新タイトル: {$title} (id={$id})";

        //バリデーション(update)
        if ($title === '') {
            $errors[] = 'タイトルを入力してください';
        } elseif (mb_strlen($title) > 50) {
            $errors[] = 'タイトルは50文字以内が推奨です';
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE tasks SET title = :title WHERE id = :id");
                $stmt->execute([':title' => $title, ':id' => $id]);
                $debug[] = 'DBへのUPDATE成功';
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } catch (PDOException $e) {
                $errors[] = '保存時にエラー: ' . $e->getMessage();
                $debug[] = 'PDOエラー: ' . $e->getMessage();
            }
        } else {
            $edit_task = ['id' => $id, 'title' => $title];
        }
    }

    // delete
    if ($type === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $debug[] = "削除成功: id={$id}";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } catch (PDOException $e) {
            $errors[] = '削除時にエラー: ' . $e->getMessage();
        }
    }

    // 完了/未完了切替
    if ($type === 'toggle_done' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $is_done = (int)$_POST['is_done'];
        $stmt = $pdo->prepare("UPDATE tasks SET is_done = :is_done WHERE id = :id");
        $stmt->execute([':is_done' => $is_done, ':id' => $id]);
        $debug[] = "完了状態切替: id={$id}, is_done={$is_done}";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// タスク一覧取得
$stmt = $pdo->query("SELECT * FROM tasks ORDER BY created_at DESC");
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
     <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
    <title>Todo List</title>
    <style>
        body { font-family: sans-serif; margin: 2em; }
        .error { color: red; }
        .warning { color: orange; }
        .debug { color: #444; font-size: 0.95em; background: #f8f8f8; border: 1px solid #ddd; padding: 10px; margin-top: 1em; }
        .done { text-decoration: line-through; color: gray; }
        .status-label { margin-left: 1em; font-size: 0.9em; color: #666; }
    </style>
</head>
<body>
    <h1>Todo List</h1>

    <!-- edit入力フォーム -->
    <?php if ($edit_task): ?>
        <form method="post">
            <input type="hidden" name="type" value="update">
            <input type="hidden" name="id" value="<?= htmlspecialchars($edit_task['id'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="title" value="<?= htmlspecialchars($edit_task['title'], ENT_QUOTES, 'UTF-8') ?>" maxlength="100">
            <button type="submit">更新</button>
            <a href="<?= $_SERVER['PHP_SELF'] ?>">キャンセル</a>
        </form>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="type" value="create">
            <input type="text" name="title" placeholder="新しいタスク" maxlength="100">
            <button type="submit">追加</button>
        </form>
    <?php endif; ?>

    <!-- メッセージ -->
    <?php if ($errors): ?>
        <ul class="error">
            <?php foreach ($errors as $msg): ?>
                <li><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
     
    <?php if ($warnings): ?>
        <ul class="warning">
            <?php foreach ($warnings as $msg): ?>
                <li><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- タスクリスト -->
   <ul>
    <?php 
    if (empty($tasks)) {
    echo "<p>タスクはまだありません。</p>";
} else foreach ($tasks as $task): ?>
        <li>
            <form method="post" style="display:inline;">
                <input type="hidden" name="type" value="toggle_done">
                <input type="hidden" name="id" value="<?= htmlspecialchars($task['id'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="is_done" value="<?= $task['is_done'] ? '0' : '1' ?>">
                <button type="submit"><?= $task['is_done'] ? '✅ 完了' : '⬜ 未完了' ?></button>
            </form>
            <span class="<?= $task['is_done'] ? 'done' : '' ?>">
                <?= htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8') ?>
            </span>
            <span class="status-label"><?= $task['is_done'] ? '完了' : '未完了' ?></span>
            
            <!--edit-->
            <form method="post" style="display:inline;">
                <input type="hidden" name="type" value="edit">
                <input type="hidden" name="id" value="<?= $task['id'] ?>">
                <button type="submit">編集</button>
            </form>

            <!--delete-->
            <form method="post" style="display:inline;">
                <input type="hidden" name="type" value="delete">
                <input type="hidden" name="id" value="<?= $task['id'] ?>">
                <button type="submit" onclick="return confirm('削除してよいですか？');">削除</button>
            </form>
        </li>
    <?php endforeach; ?>
</ul>
    <!-- デバッグ情報 -->
    <?php if ($debug): ?>
        <div class="debug">
            <strong>デバッグ情報:</strong>
            <ul>
                <?php foreach ($debug as $d): ?>
                    <li><?= htmlspecialchars($d, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</body>
</html>