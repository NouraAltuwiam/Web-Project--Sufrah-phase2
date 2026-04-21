<?php
// admin.php
// Requirement: Admin dashboard - shows welcome info, reports table, and blocked users table.

session_start();
require_once 'dp.php';

// Requirement: Check that the logged-in user is an admin - redirect if not
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php?error=" . urlencode("يجب تسجيل الدخول كمسؤول للوصول لهذه الصفحة"));
    exit();
}

// Requirement: Retrieve admin info from the database using session ID
$adminID    = (int) $_SESSION['user_id'];
$stmtAdmin  = $pdo->prepare("SELECT firstName, lastName, emailAddress FROM user WHERE id = ? AND userType = 'admin'");
$stmtAdmin->execute([$adminID]);
$admin = $stmtAdmin->fetch();

if (!$admin) {
    session_destroy();
    header("Location: login.php?error=" . urlencode("لم يتم العثور على حساب المسؤول"));
    exit();
}

// System stats for the stats boxes
$totalUsers   = $pdo->query("SELECT COUNT(*) FROM user WHERE userType = 'user'")->fetchColumn();
$totalRecipes = $pdo->query("SELECT COUNT(*) FROM recipe")->fetchColumn();
$totalReports = $pdo->query("SELECT COUNT(*) FROM report")->fetchColumn();

// Requirement: Retrieve all reports from the database with recipe and owner info
$stmtReports = $pdo->query("
    SELECT
        report.id            AS reportID,
        recipe.id            AS recipeID,
        recipe.name          AS recipeName,
        recipe.photoFileName AS recipePhoto,
        user.id              AS ownerID,
        user.firstName       AS ownerFirst,
        user.lastName        AS ownerLast,
        user.photoFileName   AS ownerPhoto
    FROM report
    JOIN recipe ON report.recipeID = recipe.id
    JOIN user   ON recipe.userID   = user.id
    ORDER BY report.id DESC
");
$reports = $stmtReports->fetchAll();

// Requirement: Retrieve all blocked users from the database
$stmtBlocked = $pdo->query("SELECT id, firstName, lastName, emailAddress FROM blockeduser ORDER BY id DESC");
$blockedUsers = $stmtBlocked->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>سفرة - لوحة الإدارة</title>
  <link rel="stylesheet" href="style.css">
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
        <a class="nav-chip" href="index.php"> الرئيسية</a>
      </nav>
      <!-- Requirement: Sign-out clears session and redirects to homepage -->
      <a href="signout.php" class="sign-out">تسجيل الخروج</a>
    </div>
  </header>

  <div class="container">

    <!-- Requirement: Display welcome note with admin's name from database -->
    <section class="welcome">
      <h1>مرحبا <span><?= htmlspecialchars($admin['firstName']) ?></span> !</h1>
    </section>

    <div class="main-grid">

      <!-- Requirement: Display admin info retrieved from database -->
      <section class="user-info-card">
        <div class="user-header">
          <div class="user-photo">
            <div class="user-photo-fallback">
              <?= htmlspecialchars(mb_substr($admin['firstName'], 0, 1)) ?>
            </div>
          </div>
          <div class="user-title">
            <h2><?= htmlspecialchars($admin['firstName'] . ' ' . $admin['lastName']) ?></h2>
          </div>
        </div>

        <div class="info-grid">
          <div class="info-item">
            <div class="info-icon">👤</div>
            <div class="info-content">
              <div class="info-label">الاسم</div>
              <div class="info-value"><?= htmlspecialchars($admin['firstName'] . ' ' . $admin['lastName']) ?></div>
            </div>
          </div>
          <div class="info-item">
            <div class="info-icon">📧</div>
            <div class="info-content">
              <div class="info-label">البريد الإلكتروني</div>
              <div class="info-value"><?= htmlspecialchars($admin['emailAddress']) ?></div>
            </div>
          </div>
        </div>
      </section>

      <!-- System statistics -->
      <section class="my-recipes-card">
        <h3>إحصائيات النظام</h3>
        <div class="stats-boxes">
          <div class="stat-box">
            <div class="stat-number"><?= (int)$totalUsers ?></div>
            <div class="stat-label">إجمالي المستخدمين</div>
          </div>
          <div class="stat-box">
            <div class="stat-number"><?= (int)$totalRecipes ?></div>
            <div class="stat-label">إجمالي الوصفات</div>
          </div>
          <div class="stat-box">
            <div class="stat-number"><?= (int)$totalReports ?></div>
            <div class="stat-label">البلاغات المعلقة</div>
          </div>
        </div>
      </section>

    </div>

    <!-- Requirement: Reports table - all reports retrieved from database -->
    <section class="all-recipes-section">
      <h2 class="section-title">البلاغات المعلقة</h2>

      <?php if (count($reports) > 0): ?>
        <table class="recipes-table">
          <thead>
            <tr>
              <th>اسم الوصفة</th>
              <th>صورة الوصفة</th>
              <th>صاحب الوصفة</th>
              <th>الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reports as $report): ?>
            <tr>
              <td>
                <!-- Requirement: Recipe name is a generated link to view-recipe page -->
                <a href="view-recipe.php?id=<?= (int)$report['recipeID'] ?>" class="recipe-name-link">
                  <?= htmlspecialchars($report['recipeName']) ?>
                </a>
              </td>
              <td>
                <img src="images/<?= htmlspecialchars($report['recipePhoto']) ?>"
                     class="thumb-img"
                     alt="<?= htmlspecialchars($report['recipeName']) ?>"
                     onerror="this.src='images/default.png'">
              </td>
              <td>
                <div class="creator-info">
                  <img src="images/<?= htmlspecialchars($report['ownerPhoto'] ?: 'default.png') ?>"
                       class="creator-photo"
                       alt="<?= htmlspecialchars($report['ownerFirst']) ?>"
                       onerror="this.src='images/default.png'">
                  <span class="creator-name">
                    <?= htmlspecialchars($report['ownerFirst'] . ' ' . $report['ownerLast']) ?>
                  </span>
                </div>
              </td>
              <td>
                <!-- Requirement: Action form includes recipe ID as hidden input, submits to handle_report.php -->
                <!-- Requirement: Actions are block user or dismiss report -->
                <form action="handle_report.php" method="POST">
                  <input type="hidden" name="reportID" value="<?= (int)$report['reportID'] ?>">
                  <input type="hidden" name="recipeID" value="<?= (int)$report['recipeID'] ?>">
                  <input type="hidden" name="ownerID"  value="<?= (int)$report['ownerID'] ?>">
                  <div class="action-buttons">
                    <button type="submit" name="action" value="block"   class="block-btn">حظر المستخدم</button>
                    <button type="submit" name="action" value="dismiss" class="dismiss-btn">رفض البلاغ</button>
                  </div>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p style="color:#666; padding:16px 0;">لا توجد بلاغات معلقة حالياً ✅</p>
      <?php endif; ?>
    </section>

    <!-- Requirement: Blocked users table - all blocked users retrieved from database -->
    <section class="favorites-section">
      <h2 class="section-title">المستخدمون المحظورون</h2>

      <?php if (count($blockedUsers) > 0): ?>
        <table class="favorites-table">
          <thead>
            <tr>
              <th>اسم المستخدم</th>
              <th>البريد الإلكتروني</th>
              <th>إجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($blockedUsers as $blocked): ?>
            <tr>
              <td>
                <div class="creator-info">
                  <img src="images/default.png" class="creator-photo"
                       alt="<?= htmlspecialchars($blocked['firstName']) ?>"
                       onerror="this.src='images/default.png'">
                  <span class="creator-name">
                    <?= htmlspecialchars($blocked['firstName'] . ' ' . $blocked['lastName']) ?>
                  </span>
                </div>
              </td>
              <td><?= htmlspecialchars($blocked['emailAddress']) ?></td>
              <td>
                <a href="unblock_user.php?id=<?= (int)$blocked['id'] ?>" class="unblock-btn">إلغاء الحظر</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p style="padding:16px 0;">لا يوجد مستخدمون محظورون حالياً</p>
      <?php endif; ?>
    </section>

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
          <li><a href="admin.php">لوحة الإدارة</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4 class="footer-heading">التصنيفات</h4>
        <ul class="footer-links">
          <li><a href="admin.php">إفطار</a></li>
          <li><a href="admin.php">سحور</a></li>
          <li><a href="admin.php">حلويات</a></li>
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
