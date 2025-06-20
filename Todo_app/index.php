<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);


// DB接続
$pdo = new PDO('mysql:host=localhost;port=8889;dbname=mydb;charset=utf8', 'root', 'root');

// エラーメッセージ用変数
$errors = [];

// CREATE処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {
    // タスク追加（バリデーション付き）
    if ($_POST['type'] === 'create' && isset($_POST['title'])) {
        $title = trim($_POST['title']);

        // バリデーション：空欄チェック
        if (empty($title)) {
            $errors[] = 'タイトルは必須です。'; 
        }
        // バリデーション：文字数制限
        elseif (mb_strlen($title) > 50) {
            $errors[] = 'タイトルは50文字以内で入力してください。';
        }

        // エラーがなければ登録
        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO tasks (title) VALUES (:title)");
            $stmt->execute([':title' => $title]);
        }
    }

    // 完了状態切り替え処理
    if ($_POST['type'] === 'toggle_done' && isset($_POST['id'])) {
        $id = $_POST['id'];
        $is_done = isset($_POST['is_done']) ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE tasks SET is_done = :is_done WHERE id = :id");
        $stmt->execute([':is_done' => $is_done, ':id' => $id]);
    }
}
?>

<!-- タスク追加フォーム -->
<form method="post">
    <input type="text" name="title" placeholder="新しいタスクを入力">
    <input type="submit" name="type" value="create">
</form>

<!-- エラーメッセージ表示 -->
<?php if (!empty($errors)): ?>
    <ul style="color: red;">
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<hr>

<!-- タスクリスト表示 -->
<h3>タスクリスト</h3>

<ul>
<?php
$stmt = $pdo->query("SELECT * FROM tasks ORDER BY created_at DESC");
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($tasks)) {
    echo "<p>タスクはまだありません。</p>";
} else {
    foreach ($tasks as $task):
        $title = htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8');
        $is_done = $task['is_done'] ? 'checked' : '';
        $status_text = $task['is_done'] ? '✅ 完了' : '🕒 未完了';
        $status_style = $task['is_done'] ? 'style=\"margin-left: 20px;\"' : 'style=\"color:red; margin-left: 20px;\"';
?>
    <li>
        <form method="post" style="display:inline;">
            <input type="hidden" name="type" value="toggle_done">
            <input type="hidden" name="id" value="<?= $task['id'] ?>">
            <label style="font-size: 18px;">
                <input type="checkbox" name="is_done" onchange="this.form.submit()" <?= $is_done ?>>
                <?= $title ?>
            </label>
            <span <?= $status_style ?>><?= $status_text ?></span>
        </form>
    </li>
<?php
    endforeach;
}
?>
</ul>
