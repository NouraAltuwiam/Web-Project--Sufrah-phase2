
<?php
session_start();
require 'dp.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // فحص إذا المستخدم محظور
    $blockedStmt = $pdo->prepare("SELECT * FROM blockeduser WHERE emailAddress = ?");
    $blockedStmt->execute([$email]);
    $blockedUser = $blockedStmt->fetch(PDO::FETCH_ASSOC);

    if ($blockedUser) {
        $error = "هذا الحساب محظور.";
    } else {
        // جلب المستخدم
        $stmt = $pdo->prepare("SELECT * FROM user WHERE emailAddress = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['userType'];

                if ($user['userType'] === 'admin') {
                    header("Location: admin.php");
                    exit();
                } else {
                    header("Location: user.php");
                    exit();
                }

            } else {
                $error = "كلمة المرور غير صحيحة";
            }
        } else {
            $error = "المستخدم غير موجود";
        }
    }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>سُفره | تسجيل الدخول</title>
  <link rel="stylesheet" href="style.css" />
</head>

<body>

   <!-- Header Full Width -->
  <header class="site-header">
  <div class="container header-inner">

    <!-- الشعار أقصى اليمين -->
    <div id="logo">
      <img src="images/logo.png" alt="Logo">
      <span>سُفــــرة</span>
    </div>

    
    <nav class="nav">
      <a class="nav-chip" href="index.php">الرئيسية</a>
      <a class="nav-chip" href="index.php">مقاطع قصيرة</a>
    </nav>
  </div>
</header>

  <div class="container">

    <main class="form-container">
      <h1>تسجيل الدخول</h1>
      <p class="form-subtitle">يرجى إدخال البريد الإلكتروني وكلمة المرور، ثم اختيار نوع الدخول.</p>

           <form class="login-form" action="login.php" method="post">
               <?php if($error != ""){ ?>
                <p style="color:red; margin-bottom:15px;"><?php echo $error; ?></p>
                <?php } ?>
               <div class="form-group">
          <label for="loginEmail">البريد الإلكتروني</label>
          <input id="loginEmail" name="email" type="email" placeholder="example@email.com" required />
        </div>

        <div class="form-group">
          <label for="loginPass">كلمة المرور</label>
          <input id="loginPass" name="password" type="password" placeholder="••••••••" required />
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

  <script src="js.js" defer></script>
  
  <footer class="site-footer" role="contentinfo">
  <div class="container footer-inner">

    <!-- عمود: نبذة -->
    <div class="footer-col footer-about">
      <div class="footer-brand">
        <img src="images/logo.png" alt="شعار سُفرة" class="footer-logo">
        <h3 class="footer-title">سُفرة</h3>
      </div>
      <p class="footer-text">
        منصة وصفات رمضانية تساعدك توصل لوصفات الإفطار والسحور بطريقة مرتبة وبسيطة.
      </p>
    </div>

    

    <!-- عمود: استكشاف -->
    <div class="footer-col">
      <h4 class="footer-heading">استكشاف</h4>
      <ul class="footer-links">
        <li><a href="index.php">الرئيسية</a></li>
        <li><a href="my-recipes.php">وصفاتي</a></li>
        <li><a href="add-recipe.php">إضافة وصفة</a></li>
        <li><a href="login.php">تسجيل الدخول</a></li>
      </ul>
    </div>
	
	<!-- عمود: التصنيفات -->
    <div class="footer-col">
      <h4 class="footer-heading">التصنيفات</h4>
      <ul class="footer-links">
        <li><a href="my-recipes.php">إفطار</a></li>
        <li><a href="my-recipes.php">سحور</a></li>
        <li><a href="my-recipes.php">حلويات</a></li>
      </ul>
    </div>

    <!-- عمود: تواصل -->
    <div class="footer-col">
      <h4 class="footer-heading">تواصل معنا</h4>

      <div class="footer-social">
        <a class="social-btn" href="#" aria-label="انستقرام">IG</a>
        <a class="social-btn" href="#" aria-label="إكس">X</a>
        <a class="social-btn" href="#" aria-label="فيسبوك">f</a>
        <a class="social-btn" href="#" aria-label="يوتيوب">▶</a>
      </div>

      <p class="footer-mini">
        البريد: <a href="mailto:sufrah@example.com">sufrah@example.com</a>
      </p>
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
