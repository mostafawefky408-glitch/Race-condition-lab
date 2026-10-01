<?php
require '../config/db.php';
session_start();

$error = '';
$maxAttempts = 3;

if (!isset($_SESSION['pending_user_id'], $_SESSION['otp_attempt_id'])) {
    header('Location: login.php');
    exit;
}

$attemptId = $_SESSION['otp_attempt_id'];


session_write_close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOtp = trim($_POST['otp'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM otp_attempts WHERE id = ?");
    $stmt->execute([$attemptId]);
    $attempt = $stmt->fetch();

    if (!$attempt || $attempt['is_locked']) {
        $error = 'الحساب مقفول أو الجلسة منتهية، سجل دخول من جديد';
    } elseif ($attempt['attempts_used'] >= $maxAttempts) {
        $pdo->prepare("UPDATE otp_attempts SET is_locked = 1 WHERE id = ?")->execute([$attemptId]);
        $error = 'تعديت الحد المسموح من المحاولات';
    } elseif ($enteredOtp === $attempt['otp_code']) {
        session_start();
        $_SESSION['user_id'] = $attempt['user_id'];
        unset($_SESSION['pending_user_id'], $_SESSION['otp_attempt_id']);
        header('Location: ../feed/index.php');
        exit;
    } else {
        // === the vulnability is here ===
        $newAttempts = $attempt['attempts_used'] + 1;
        $pdo->prepare("UPDATE otp_attempts SET attempts_used = ? WHERE id = ?")
            ->execute([$newAttempts, $attemptId]);

        $error = 'كود خاطء حاول تاني';
    }
}

$demoOtp = $_GET['demo_otp'] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تحقق OTP - RewardLoop</title>
</head>
<body>
    <h2>أدخل كود التحقق</h2>
    <?php if ($demoOtp): ?>
        <p>(Demo) الكود المرسل: <?= htmlspecialchars($demoOtp) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST">
        <input type="text" name="otp" placeholder="الكود المكون من 4 أرقام" maxlength="4" required><br>
        <button type="submit">تحقق</button>
    </form>
</body>
</html>