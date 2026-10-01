<?php
require '../config/db.php';
require '../includes/session.php';
requireLogin();

$stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
$posts = $stmt->fetchAll();

$likedStmt = $pdo->prepare("SELECT post_id FROM likes WHERE user_id = ?");
$likedStmt->execute([currentUserId()]);
$likedPostIds = $likedStmt->fetchAll(PDO::FETCH_COLUMN);

$userStmt = $pdo->prepare("SELECT username, points FROM users WHERE id = ?");
$userStmt->execute([currentUserId()]);
$user = $userStmt->fetch();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الفيد - RewardLoop</title>
</head>
<body>
    <h2>أهلاً، <?= htmlspecialchars($user['username']) ?> — رصيدك: <?= $user['points'] ?> نقطة</h2>
    <a href="../wallet/dashboard.php">المحفظة</a>
    <a href="../auth/logout.php">تسجيل خروج</a>

    <hr>

    <?php foreach ($posts as $post):
        $isLiked = in_array($post['id'], $likedPostIds);
    ?>
        <div>
            <h3><?= htmlspecialchars($post['title']) ?></h3>
            <p><?= htmlspecialchars($post['content']) ?></p>
            <p>عدد اللايكات: <span id="likes-<?= $post['id'] ?>"><?= $post['likes_count'] ?></span></p>
            <button id="btn-<?= $post['id'] ?>" onclick="likePost(<?= $post['id'] ?>)">
                <?= $isLiked ? 'إلغاء اللايك 💔' : 'لايك 👍' ?>
            </button>
        </div>
        <hr>
    <?php endforeach; ?>

    <script>
    function likePost(postId) {
        fetch('like.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'post_id=' + postId
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('likes-' + postId).innerText = data.likes_count;
                document.getElementById('btn-' + postId).innerText = data.liked ? 'إلغاء اللايك 💔' : 'لايك 👍';
            } else {
                alert(data.message);
            }
        });
    }
    </script>
</body>
</html>