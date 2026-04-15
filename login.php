<?php
// login.php
// Requirement 4: Login page with single button, checks blocked users and validates credentials

session_start();
require_once 'dp.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "يرجى تعبئة جميع الحقول";
    } else {

        // Requirement 4: Check if user is blocked first
        $stmtBlocked = $pdo->prepare("SELECT id FROM blockeduser WHERE emailAddress = ?");
        $stmtBlocked->execute([$email]);
        if ($stmtBlocked->fetch()) {
            $error = "هذا الحساب محظور. لا يمكنك تسجيل الدخول.";
        } else {

            // Fetch user by email
            $stmtUser = $pdo->prepare("SELECT * FROM user WHERE emailAddress = ?");
            $stmtUser->execute([$email]);
            $user = $stmtUser->fetch();

            if ($user && password_verify($password, $user['password'])) {

                // Requirement 4: Store user id and type in session variables
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_type'] = $user['userType'];

                // Redirect based on user type (Requirement 4)
                if ($user['userType'] === 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: user.php");
                }
                exit();

            } else {
                $error = "البريد الإلكتروني أو كلمة المرور غير صحيحة";
            }
        }
    }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>سُفره | تسجيل الدخول</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <header class="site-header">
    <div class="container header-inner">
      <div id="logo">
        <img src="images/logo.png" alt="Logo">
        <span>سُفــــرة</span>
      </div>
      <nav class="nav">
        <a class="nav-chip" href="index.php">الرئيسية</a>
      </nav>
    </div>
  </header>

  <div class="container">
    <main class="form-container">
      <h1>تسجيل الدخول</h1>
      <p class="form-subtitle">يرجى إدخال البريد الإلكتروني وكلمة المرور.</p>

      <?php if ($error !== ""): ?>
        <p style="color:red; margin-bottom:15px; text-align:center;"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <!-- Show error passed from other pages via query string -->
      <?php if (isset($_GET['error']) && $_GET['error'] !== ""): ?>
        <p style="color:red; margin-bottom:15px; text-align:center;"><?= htmlspecialchars($_GET['error']) ?></p>
      <?php endif; ?>

      <!-- Requirement 4: Single login button (not two buttons like Phase 1) -->
      <form class="login-form" action="login.php" method="POST">

        <div class="form-group">
          <label for="loginEmail">البريد الإلكتروني</label>
          <input id="loginEmail" name="email" type="email"
                 placeholder="example@email.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 required>
        </div>

        <div class="form-group">
          <label for="loginPass">كلمة المرور</label>
          <input id="loginPass" name="password" type="password"
                 placeholder="••••••••" required>
        </div>

        <div class="actions">
          <button class="btn btn-primary" type="submit">تسجيل الدخول</button>
        </div>
      </form>

      <p class="note-text">
        لا يوجد حساب؟ <a class="link" href="signup.php">إنشاء حساب</a>
      </p>
    </main>
  </div>

  <footer class="site-footer" role="contentinfo">
    <div class="container footer-inner">
      <div class="footer-col footer-about">
        <div class="footer-brand">
          <img src="images/logo.png" alt="شعار سُفرة" class="footer-logo">
          <h3 class="footer-title">سُفرة</h3>
        </div>
        <p class="footer-text">منصة وصفات رمضانية تساعدك توصل لوصفات الإفطار والسحور بطريقة مرتبة وبسيطة.</p>
      </div>
      <div class="footer-col">
        <h4 class="footer-heading">استكشاف</h4>
        <ul class="footer-links">
          <li><a href="index.php">الرئيسية</a></li>
          <li><a href="signup.php">إنشاء حساب</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4 class="footer-heading">تواصل معنا</h4>
        <div class="footer-social">
          <a class="social-btn" href="#" aria-label="انستقرام">IG</a>
          <a class="social-btn" href="#" aria-label="إكس">X</a>
          <a class="social-btn" href="#" aria-label="فيسبوك">f</a>
          <a class="social-btn" href="#" aria-label="يوتيوب">▶</a>
        </div>
        <p class="footer-mini">البريد: <a href="mailto:sufrah@example.com">sufrah@example.com</a></p>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="container footer-bottom-inner">
        <small>© 2026 سُفرة .</small>
        <small>صُنع بـ <span aria-hidden="true">♥</span> لرمضان</small>
      </div>
    </div>
  </footer>

</body>
</html>