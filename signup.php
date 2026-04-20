<?php
// signup.php
// Requirement: Sign-up page that checks if email already exists (in users or blocked tables),
//              hashes password, handles optional photo, adds user, stores session, redirects to user page.

session_start();
require 'dp.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $firstName = trim($_POST['firstName'] ?? '');
    $lastName  = trim($_POST['lastName']  ?? '');
    $email     = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password  = $_POST['password'] ?? '';

    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $error = "يرجى تعبئة جميع الحقول";
    }

    if ($error === "") {
        // Requirement: Check if email already exists in the users table
        $stmt = $pdo->prepare("SELECT id FROM user WHERE emailAddress = ?");
        $stmt->execute([$email]);

        // Requirement: Also check if email exists in the blocked users table
        $stmt2 = $pdo->prepare("SELECT id FROM blockeduser WHERE emailAddress = ?");
        $stmt2->execute([$email]);

        if ($stmt->fetch() || $stmt2->fetch()) {
            $error = "هذا البريد الإلكتروني مسجل مسبقًا.";
        }
    }

    // Requirement: Use default photo if user did not upload one
    $photoFileName = "default.png";

    if ($error === "" && isset($_FILES['profileImg']) && $_FILES['profileImg']['error'] === 0) {
        // Requirement: Handle correct photo upload and save it
        $uploadDir = "images/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext          = strtolower(pathinfo($_FILES['profileImg']['name'], PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowedTypes)) {
            $error = "نوع الصورة غير مدعوم";
        } else {
            $uniqueName = "user_" . time() . "_" . uniqid() . "." . $ext;
            move_uploaded_file($_FILES['profileImg']['tmp_name'], $uploadDir . $uniqueName);
            $photoFileName = $uniqueName;
        }
    }

    if ($error === "") {
        // Requirement: Hash the password before storing in the database
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Requirement: Insert the new user into the database
        $insert = $pdo->prepare("
            INSERT INTO user (userType, firstName, lastName, emailAddress, password, photoFileName)
            VALUES ('user', ?, ?, ?, ?, ?)
        ");
        $insert->execute([$firstName, $lastName, $email, $hashedPassword, $photoFileName]);

        $newUserId = $pdo->lastInsertId();

        // Requirement: Store user id and type in session after successful registration
        $_SESSION['user_id']   = $newUserId;
        $_SESSION['user_type'] = 'user';

        // Requirement: Redirect to user's page
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

  <!-- Header -->
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

      <?php if ($error !== ""): ?>
        <p style="color:red; margin-bottom:15px;"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form class="signup-form" action="signup.php" method="post" enctype="multipart/form-data">

        <div class="form-group">
          <label for="firstName">الاسم الأول</label>
          <input id="firstName" name="firstName" type="text" placeholder="الاسم"
                 value="<?= htmlspecialchars($_POST['firstName'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label for="lastName">اسم العائلة</label>
          <input id="lastName" name="lastName" type="text" placeholder="اسم العائلة"
                 value="<?= htmlspecialchars($_POST['lastName'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label for="signupEmail">البريد الإلكتروني</label>
          <input id="signupEmail" name="email" type="email" placeholder="example@email.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label for="signupPass">كلمة المرور</label>
          <input id="signupPass" name="password" type="password" placeholder="••••••••" required>
        </div>

        <!-- Requirement: Profile photo is optional - default used if not uploaded -->
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
        <p class="footer-text">
          منصة وصفات رمضانية تساعدك توصل لوصفات الإفطار والسحور بطريقة مرتبة وبسيطة.
        </p>
      </div>

      <div class="footer-col">
        <h4 class="footer-heading">استكشاف</h4>
        <ul class="footer-links">
          <li><a href="index.php">الرئيسية</a></li>
          <li><a href="my-recipes.php">وصفاتي</a></li>
          <li><a href="add-recipe.php">إضافة وصفة</a></li>
          <li><a href="login.php">تسجيل الدخول</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4 class="footer-heading">التصنيفات</h4>
        <ul class="footer-links">
          <li><a href="my-recipes.php">إفطار</a></li>
          <li><a href="my-recipes.php">سحور</a></li>
          <li><a href="my-recipes.php">حلويات</a></li>
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
