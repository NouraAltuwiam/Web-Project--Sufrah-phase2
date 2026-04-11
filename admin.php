<?php
// ===================================================
// admin.php
// نفس شكل admin.html بالضبط + PHP للداتابيس
// ===================================================

session_start();

// تحقق أن المستخدم أدمن
if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'admin') {
    header("Location: login.php?error=يجب تسجيل الدخول كمسؤول للوصول لهذه الصفحة");
    exit();
}

// اتصال بالداتابيس
$conn = new mysqli("localhost", "root", "", "sufrah_db");
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// جلب معلومات الأدمن
$adminID = $_SESSION['userID'];
$stmt = $conn->prepare("SELECT firstName, lastName, emailAddress FROM user WHERE id = ?");
$stmt->bind_param("i", $adminID);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// إحصائيات النظام
$totalUsers   = $conn->query("SELECT COUNT(*) AS c FROM user WHERE userType = 'user'")->fetch_assoc()['c'];
$totalRecipes = $conn->query("SELECT COUNT(*) AS c FROM recipe")->fetch_assoc()['c'];
$totalReports = $conn->query("SELECT COUNT(*) AS c FROM report")->fetch_assoc()['c'];

// جلب البلاغات مع معلومات الوصفة وصاحبها
$reportsResult = $conn->query("
    SELECT 
        report.id            AS reportID,
        recipe.id            AS recipeID,
        recipe.name          AS recipeName,
        recipe.photoFileName AS recipePhoto,
        user.id              AS ownerID,
        user.firstName       AS ownerFirst,
        user.lastName        AS ownerLast
    FROM report
    JOIN recipe ON report.recipeID = recipe.id
    JOIN user   ON recipe.userID   = user.id
");

// جلب المستخدمين المحظورين
$blockedResult = $conn->query("SELECT id, firstName, lastName, emailAddress FROM blockeduser");
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

  <!-- Header Full Width -->
  <header>
    <div class="container">
      <!-- header (logo + sign out) -->
      <div id="logo">
        <img src="images/logo.png" alt="Logo">
        <span>سُفرة</span>
      </div>
      <!-- رابط تسجيل الخروج يروح لـ signout.php -->
      <a href="signout.php" class="sign-out">تسجيل الخروج</a>
    </div>
  </header>

  <div class="container">

    <!-- welcome message -->
    <section class="welcome">
      <!-- اسم الأدمن من الداتابيس -->
      <h1>مرحبا <span><?= htmlspecialchars($admin['firstName']) ?></span> !</h1>
    </section>

    <!-- Main Content Grid -->
    <div class="main-grid">

      <!-- Admin Information -->
      <section class="user-info-card">
        <div class="user-header">
          <div class="user-photo">
            <!-- الحرف الأول من اسم الأدمن -->
            <div class="user-photo-fallback">
              <?= htmlspecialchars(mb_substr($admin['firstName'], 0, 1)) ?>
            </div>
          </div>

          <div class="user-title">
            <!-- الاسم الكامل من الداتابيس -->
            <h2><?= htmlspecialchars($admin['firstName'] . ' ' . $admin['lastName']) ?></h2>
          </div>
        </div>

        <!-- admin information -->
        <div class="info-grid">
          <div class="info-item">
            <div class="info-icon">👤</div>
            <div class="info-content">
              <div class="info-label">الاسم</div>
              <!-- الاسم من الداتابيس -->
              <div class="info-value"><?= htmlspecialchars($admin['firstName'] . ' ' . $admin['lastName']) ?></div>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon">📧</div>
            <div class="info-content">
              <div class="info-label">البريد الإلكتروني</div>
              <!-- الإيميل من الداتابيس -->
              <div class="info-value"><?= htmlspecialchars($admin['emailAddress']) ?></div>
            </div>
          </div>
        </div>
      </section>

      <!-- Admin Statistics -->
      <section class="my-recipes-card">
        <h3>إحصائيات النظام</h3>

        <div class="stats-boxes">
          <div class="stat-box">
            <!-- عدد المستخدمين من الداتابيس -->
            <div class="stat-number"><?= $totalUsers ?></div>
            <div class="stat-label">إجمالي المستخدمين</div>
          </div>

          <div class="stat-box">
            <!-- عدد الوصفات من الداتابيس -->
            <div class="stat-number"><?= $totalRecipes ?></div>
            <div class="stat-label">إجمالي الوصفات</div>
          </div>

          <div class="stat-box">
            <!-- عدد البلاغات من الداتابيس -->
            <div class="stat-number"><?= $totalReports ?></div>
            <div class="stat-label">البلاغات المعلقة</div>
          </div>
        </div>
      </section>

    </div>

    <!-- Pending Reports Section -->
    <section class="all-recipes-section">
      <h2 class="section-title">البلاغات المعلقة</h2>

      <?php if ($reportsResult->num_rows > 0): ?>
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

          <?php while ($report = $reportsResult->fetch_assoc()): ?>
          <tr>
            <td>
              <!-- رابط لصفحة عرض الوصفة -->
              <a href="view-recipe.php?id=<?= $report['recipeID'] ?>" class="recipe-name-link">
                <?= htmlspecialchars($report['recipeName']) ?>
              </a>
            </td>
            <td>
              <!-- صورة الوصفة - نفس مسار images من فيز 1 -->
              <img src="images/<?= htmlspecialchars($report['recipePhoto']) ?>" class="recipe-image" alt="<?= htmlspecialchars($report['recipeName']) ?>">
            </td>
            <td>
              <div class="creator-info">
                <!-- صورة صاحب الوصفة -->
                <img src="images/user<?= $report['ownerID'] ?>.jpg" class="creator-photo" alt="<?= htmlspecialchars($report['ownerFirst']) ?>">
                <span class="creator-name"><?= htmlspecialchars($report['ownerFirst'] . ' ' . $report['ownerLast']) ?></span>
              </div>
            </td>
            <td>
              <!-- فورم الإجراء: حظر أو رفض -->
              <form action="handle_report.php" method="POST">
                <input type="hidden" name="reportID" value="<?= $report['reportID'] ?>">
                <input type="hidden" name="recipeID" value="<?= $report['recipeID'] ?>">
                <input type="hidden" name="ownerID"  value="<?= $report['ownerID'] ?>">
                <div class="action-buttons">
                  <button type="submit" name="action" value="block"   class="block-btn">حظر المستخدم</button>
                  <button type="submit" name="action" value="dismiss" class="dismiss-btn">رفض البلاغ</button>
                </div>
              </form>
            </td>
          </tr>
          <?php endwhile; ?>

        </tbody>
      </table>

      <?php else: ?>
        <p>لا توجد بلاغات معلقة حالياً</p>
      <?php endif; ?>
    </section>

    <!-- Blocked Users Section -->
    <section class="favorites-section">
      <h2 class="section-title">المستخدمون المحظورون</h2>

      <?php if ($blockedResult->num_rows > 0): ?>
      <table class="favorites-table">
        <thead>
          <tr>
            <th>اسم المستخدم</th>
            <th>البريد الإلكتروني</th>
            <th>إجراء</th>
          </tr>
        </thead>
        <tbody>

          <?php while ($blocked = $blockedResult->fetch_assoc()): ?>
          <tr>
            <td>
              <div class="creator-info">
                <!-- المحظورون ما عندهم صور، نستخدم صورة افتراضية -->
                <img src="images/default.png" class="creator-photo" alt="<?= htmlspecialchars($blocked['firstName']) ?>">
                <span class="creator-name"><?= htmlspecialchars($blocked['firstName'] . ' ' . $blocked['lastName']) ?></span>
              </div>
            </td>
            <td><?= htmlspecialchars($blocked['emailAddress']) ?></td>
            <td>
              <!-- رابط إلغاء الحظر -->
              <a href="unblock_user.php?id=<?= $blocked['id'] ?>" class="unblock-btn">إلغاء الحظر</a>
            </td>
          </tr>
          <?php endwhile; ?>

        </tbody>
      </table>

      <?php else: ?>
        <p>لا يوجد مستخدمون محظورون حالياً</p>
      <?php endif; ?>
    </section>

  </div>

<?php $conn->close(); ?>
</body>
</html>