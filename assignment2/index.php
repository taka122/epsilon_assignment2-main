<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once('db.php');

// 投稿処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_SESSION['user']['name'];
    $userId = $_SESSION['user']['id'];
    $message = $_POST['message'] ?? '';
    $title = $_POST['title'] ?? '';
    $type = $_POST['type'] ?? '';

    // 新規投稿処理
    if ($type === 'create' && $message !== '') {
        $stmt = $pdo->prepare('INSERT INTO posts (user_id, name, message, title) VALUES (:user_id, :name, :message, :title)');
        $stmt->execute([
            ':user_id' => $userId,
            ':name' => $name,
            ':message' => $message,
            ':title' => $title//連想配列
        ]);
        header('Location: index.php');  // 投稿後にリダイレクト
        exit;
    }

    // 投稿更新処理
    if ($type === 'update' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $title = $_POST['title'];
        $message = $_POST['message'];

        if ($title !== '' && $message !== '') {
            $stmt = $pdo->prepare('UPDATE posts SET title = :title, message = :message WHERE id = :id AND user_id = :user_id');
            $stmt->execute([
                ':title' => $title,
                ':message' => $message,
                ':id' => $id,
                ':user_id' => $_SESSION['user']['id']  // 投稿者のみ更新できる
            ]);
            header('Location: index.php');  // 更新後にリダイレクト
            exit;
        }
    }
}
//投稿を編集する処理
$type = $_POST['type'] ?? '';
$editTask = null;  // 最初に初期化
if ($type === 'edit' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $editTask = $stmt->fetch(PDO::FETCH_ASSOC);
}
// 投稿を削除する処理
if ($type === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $_SESSION['user']['id']  // 投稿者のみ削除できる
        ]);
        header('Location: index.php');  // 削除後にリダイレクト
        exit;
    }

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // 1ページあたりの表示件数
$offset = ($page - 1) * $limit; // オフセット計算（何件目から取得するか）


// 投稿一覧取得（LIMITとOFFSETを使ってページネーション＋論理削除除外）
//論理削除修正：WHERE deleted_at IS NULL
$stmt = $pdo->prepare('SELECT * FROM posts WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);//bindParamは「SQLの:limitや:offsetに値をセットする」ための命令
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);// fetchAll：「全部まとめて取ってくる。PDO::FETCH_ASSOC：連想配列の形で

// 総投稿数を取得して総ページ数を計算
//論理削除修正
$stmt = $pdo->query('SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL');
//  <div class="pagination">から作られたtotalposts
$totalPosts = $stmt->fetchColumn(); 
$totalPages = ceil($totalPosts / $limit); // 総ページ数（ceil＝切り上げ）
?>



<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>掲示板</title>
</head>
<body>
    <p>ようこそ、<?= htmlspecialchars($_SESSION['user']['name']) ?> さん！<a href="logout.php">ログアウト</a></p>

    <h1>掲示板</h1>

    <!-- 新規投稿フォーム -->

     <?php if ($editTask): ?>
        <form method="post">
            <input type="hidden" name="type" value="update">
            <input type="hidden" name="id" value="<?= htmlspecialchars($editTask['id'], ENT_QUOTES, 'UTF-8') ?>">
            <h3>タイトル:</h3>
            <textarea name="title" rows="1" cols="20" required><?= htmlspecialchars($editTask['title'], ENT_QUOTES, 'UTF-8') ?></textarea><br>
            <h3>メッセージ:</h3>
            <textarea name="message" rows="4" cols="40" required><?= htmlspecialchars($editTask['message'], ENT_QUOTES, 'UTF-8') ?></textarea><br>
            <button type="submit">更新</button>
        </form>
    <?php else: ?>
    <form method="post">
        <input type="hidden" name="type" value="create">
        <div style="display: flex; align-items: center; gap: 0.5em;">
            <span>名前:</span>
            <span><?= htmlspecialchars($_SESSION['user']['name']) ?></span>
            <span><a href="user_edit.php">会員情報編集</a></span>
        </div>
        <input type="hidden" name="name" value="<?= htmlspecialchars($_SESSION['user']['name']) ?>">
        <h3>タイトル:</h3>
        <textarea name="title" rows="1" cols="20" required></textarea><br>
        <h3>メッセージ:</h3>
        <textarea name="message" rows="4" cols="40" required></textarea><br>
        <button type="submit">投稿</button>
    </form>
     <?php endif; ?>

    <hr>

    <h2>投稿一覧</h2>
    <?php foreach ($posts as $post): ?>
         <!--deleted_atがないやつだけ-->
        <?php if (is_null($post['deleted_at'])): ?>
        <p><strong><?= htmlspecialchars($post['name']) ?></strong> (<?= $post['created_at'] ?>)</p>
        <p><?= nl2br(htmlspecialchars($post['title'])) ?></p>
        <p><?= nl2br(htmlspecialchars($post['message'])) ?></p>
       
        <!-- 投稿者のみが編集できるリンク -->
        <?php if ($_SESSION['user']['id'] === $post['user_id']): ?>
            <form method="post" style="display:inline;">
                <input type="hidden" name="type" value="edit">
                <input type="hidden" name="id" value="<?= $post['id'] ?>">
                <button type="submit">編集</button>
            </form>
            <form method="post" style="display:inline;">
                <input type="hidden" name="type" value="delete">
                <input type="hidden" name="id" value="<?= $post['id'] ?>">
                <button type="submit" onclick="return confirm('本当に削除しますか？');">削除</button>
            </form>

            <?php endif; ?>


         
        <?php endif; ?>

<?php endforeach; ?>
        <!-- ページネーションリンク -->
  <div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>



</body>
</html>
        
