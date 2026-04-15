<?php
session_start();
require 'dp.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName = trim($_POST['firstName']);
    $lastName  = trim($_POST['lastName']);
    $email     = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password  = $_POST['password'];

    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    }

    if ($error == "") {
        // Check if email already exists in the users table
        $stmt = $pdo->prepare("SELECT id FROM user WHERE emailAddress = ?");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if email exists in the blocked users table
        $stmt2 = $pdo->prepare("SELECT id FROM blockeduser WHERE emailAddress = ?");
        $stmt2->execute([$email]);
        $blockedUser = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($existingUser || $blockedUser) {
            $error = "This email address is already registered.";
        }
    }

    // Default profile photo
    $photoFileName = "default-user.png";

    // Handle optional profile photo upload
    if ($error == "" && isset($_FILES['profileImg']) && $_FILES['profileImg']['error'] === 0) {
        $uploadDir = "images/users/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $originalName = basename($_FILES['profileImg']['name']);
        $ext          = pathinfo($originalName, PATHINFO_EXTENSION);
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array(strtolower($ext), $allowedTypes)) {
            $error = "Unsupported image type. Please upload jpg, jpeg, png, or gif.";
        } else {
            // Use a unique name to avoid collisions
            $uniqueName = time() . "_" . uniqid() . "." . $ext;
            $targetFile = $uploadDir . $uniqueName;
            move_uploaded_file($_FILES['profileImg']['tmp_name'], $targetFile);
            $photoFileName = $uniqueName;
        }
    }

    if ($error == "") {
        // Hash the password before storing (never store plain text)
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert the new user into the database
        $insert = $pdo->prepare("
            INSERT INTO user (userType, firstName, lastName, emailAddress, password, photoFileName)
            VALUES ('user', ?, ?, ?, ?, ?)
        ");
        $insert->execute([$firstName, $lastName, $email, $hashedPassword, $photoFileName]);

        $newUserId = $pdo->lastInsertId();

        // Store user id and type in session (Req 3)
        $_SESSION['user_id']    = $newUserId;
        $_SESSION['user_type']  = 'user';
        $_SESSION['first_name'] = $firstName;

        header("Location: user.php");
        exit();
    }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>سُفره | إنشاء حساب</title>
  <link rel="stylesheet" href="style.css" />
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
      <a class="nav-chip" href="login.php">تسجيل الدخول</a>
    </nav>
  </div>
</header>
<div class="container">
  <main class="form-container">
    <h1>إنشاء حساب</h1>
    <p class="form-subtitle">
      يرجى تعبئة البيانات المطلوبة. صورة الملف اختيارية، وفي حال عدم الرفع يتم استخدام صورة افتراضية.
    </p>
    <?php if ($error != "") { ?>
      <p style="color:red; margin-bottom:15px;"><?php echo htmlspecialchars($error); ?></p>
    <?php } ?>
    <form class="signup-form" action="signup.php" method="post" enctype="multipart/form-data">
      <div class="form-group">
        <label for="firstName">الاسم الأول</label>
        <input id="firstName" name="firstName" type="text" placeholder="الاسم" required>
      </div>
      <div class="form-group">
        <label for="lastName">اسم العائلة</label>
        <input id="lastName" name="lastName" type="text" placeholder="اسم العائلة" required>
      </div>
      <div class="form-group">
        <label for="signupEmail">البريد الإلكتروني</label>
        <input id="signupEmail" name="email" type="email" placeholder="example@email.com" required>
      </div>
      <div class="form-group">
        <label for="signupPass">كلمة المرور</label>
        <input id="signupPass" name="password" type="password" placeholder="••••••••" required>
      </div>
      <div class="form-group">
        <label for="profileImg">صورة الملف (اختياري)</label>
        <input id="profileImg" name="profileImg" type="file" accept="image/*">
      </div>
      <div class="actions">
        <button type="submit" class="btn btn-primary">إنشاء الحساب</button>
      </div>
    </form>
    <p class="note-text">
      يوجد حساب مسبق؟ <a class="link" href="login.php">تسجيل الدخول</a>
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
        <li><a href="login.php">تسجيل الدخول</a></li>
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