<?php
require '../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $error = 'بيانات الدخول غير صحيحة';
    } else {
        $otp = strval(random_int(1000, 9999));
        $expiresAt = date('Y-m-d H:i:s', time() + 300);

        $stmt = $pdo->prepare(
            "INSERT INTO otp_attempts (user_id, otp_code, attempts_used, is_locked, expires_at)
             VALUES (?, ?, 0, 0, ?)"
        );
        $stmt->execute([$user['id'], $otp, $expiresAt]);

        session_start();
        $_SESSION['pending_user_id'] = $user['id'];
        $_SESSION['otp_attempt_id'] = $pdo->lastInsertId();

        header('Location: verify_otp.php?demo_otp=' . $otp);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول - RewardLoop</title>
</head>
<body>
    <h2>تسجيل الدخول</h2>
    <?php if (isset($_GET['registered'])): ?>
        <p style="color:green;">تم إنشاء الحساب، سجل دخولك الآن</p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="اسم المستخدم" required><br>
        <input type="password" name="password" placeholder="كلمة المرور" required><br>
        <button type="submit">دخول</button>
        <a href="register.php">مش عندك حساب سجل دلوقتي</a>
    </form>
</body>
</html>