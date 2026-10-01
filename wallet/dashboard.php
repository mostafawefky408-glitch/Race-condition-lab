<?php
require '../config/db.php';
require '../includes/session.php';
requireLogin();

$stmt = $pdo->prepare("SELECT username, points, wallet_balance FROM users WHERE id = ?");
$stmt->execute([currentUserId()]);
$user = $stmt->fetch();    
$pointsPerPound = 10;


$historyStmt = $pdo->prepare("SELECT * FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC");
$historyStmt->execute([currentUserId()]);
$withdrawals = $historyStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>المحفظة - RewardLoop</title>
</head>
<body>
    <h2>محفظة <?= htmlspecialchars($user['username']) ?></h2>
    <p>رصيد النقط: <strong id="points-balance"><?= $user['points'] ?></strong> نقطة</p>
    <p>رصيد الفلوس: <strong id="cash-balance"><?= number_format($user['wallet_balance'], 2) ?></strong> جنيه</p>
    <a href="../feed/index.php">رجوع للفيد</a>

    <hr>

    <h3>تحويل نقط لفلوس (كل <?= $pointsPerPound ?> نقطة = 1 جنيه)</h3>
    <form id="withdraw-form">
        <input type="number" id="points-input" name="points" min="1" placeholder="عدد النقط" required>
        <button type="submit">حوّل دلوقتي</button>
    </form>
    <p id="withdraw-message"></p>

    <hr>

    <h3>سجل عمليات التحويل</h3>
    <ul>
        <?php foreach ($withdrawals as $w): ?>
            <li>
                <?= $w['points_spent'] ?> نقطة → <?= number_format($w['cash_amount'], 2) ?> جنيه
                (<?= $w['created_at'] ?>)
            </li>
        <?php endforeach; ?>
    </ul>

    <script>
    document.getElementById('withdraw-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const points = document.getElementById('points-input').value;

        fetch('withdraw.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'points=' + points
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('withdraw-message').innerText = data.message;
            if (data.success) {
                document.getElementById('points-balance').innerText = data.new_points;
                document.getElementById('cash-balance').innerText = data.new_balance;
            }
        });
    });
    </script>
</body>
</html>