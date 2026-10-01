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
$pointsToSpend = intval($_POST['points'] ?? 0);
$pointsPerPound = 10;

if ($pointsToSpend <= 0) {
    echo json_encode(['success' => false, 'message' => 'عدد نقط غير صحيح']);
    exit;
}

// 1) هات رصيد النقط الحالي
$stmt = $pdo->prepare("SELECT points, wallet_balance FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($pointsToSpend > $user['points']) {
    echo json_encode(['success' => false, 'message' => 'رصيد النقط مش كافي']);
    exit;
}

// === the vulnability is here ===
// there's race window between reading the points and updating them

$cashAmount = $pointsToSpend / $pointsPerPound;

$updateStmt = $pdo->prepare(
    "UPDATE users SET points = points - ?, wallet_balance = wallet_balance + ? WHERE id = ?"
);
$updateStmt->execute([$pointsToSpend, $cashAmount, $userId]);

$logStmt = $pdo->prepare(
    "INSERT INTO withdrawals (user_id, points_spent, cash_amount) VALUES (?, ?, ?)"
);
$logStmt->execute([$userId, $pointsToSpend, $cashAmount]);


$newStmt = $pdo->prepare("SELECT points, wallet_balance FROM users WHERE id = ?");
$newStmt->execute([$userId]);
$newUser = $newStmt->fetch();

echo json_encode([
    'success' => true,
    'message' => 'تم التحويل بنجاح',
    'new_points' => $newUser['points'],
    'new_balance' => number_format($newUser['wallet_balance'], 2)
]);