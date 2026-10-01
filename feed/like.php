<?php
require '../config/db.php';
require '../includes/session.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'سجل دخول الأول']);
    exit;
}

$userId = currentUserId();
session_write_close();  
$postId = intval($_POST['post_id'] ?? 0);

if ($postId <= 0) {
    echo json_encode(['success' => false, 'message' => 'بوست غير صحيح']);
    exit;
}

// check if the user has already liked this post
$check = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
$check->execute([$userId, $postId]);
$existingLike = $check->fetch();

// === the vulnability is here ===
if ($existingLike) {
    // == Unlike ==
    $deleteLike = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
    $deleteLike->execute([$userId, $postId]);

    // Theres's a vulnerability here:
    //  the likes_count can go negative if a user unlikes a post they haven't liked yet. We should ensure that likes_count doesn't go below 0.
    $updatePost = $pdo->prepare("UPDATE posts SET likes_count = likes_count - 1 WHERE id = ?");
    $updatePost->execute([$postId]);

    $updateUser = $pdo->prepare("UPDATE users SET points = points - 1 WHERE id = ?");
    $updateUser->execute([$userId]);

    $liked = false;
} else {
    // == Like ==
    $insertLike = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
    $insertLike->execute([$userId, $postId]);

    $updatePost = $pdo->prepare("UPDATE posts SET likes_count = likes_count + 1 WHERE id = ?");
    $updatePost->execute([$postId]);

    $updateUser = $pdo->prepare("UPDATE users SET points = points + 1 WHERE id = ?");
    $updateUser->execute([$userId]);

    $liked = true;
}

$countStmt = $pdo->prepare("SELECT likes_count FROM posts WHERE id = ?");
$countStmt->execute([$postId]);
$newCount = $countStmt->fetchColumn();

echo json_encode(['success' => true, 'likes_count' => $newCount, 'liked' => $liked]);