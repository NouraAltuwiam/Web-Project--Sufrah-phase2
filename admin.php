<?php


session_start();

// --- 11a: تحقق أن المستخدم أدمن ---
if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'admin') {
    header("Location: login.php?error=يجب تسجيل الدخول كمسؤول للوصول لهذه الصفحة");
    exit();
}

// --- اتصال بالداتابيس ---
$conn = new mysqli("localhost", "root", "", "sufrah_db");
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// --- 11b: جلب معلومات الأدمن من الداتابيس ---
$adminID = $_SESSION['userID'];
$stmt = $conn->prepare("SELECT firstName, lastName, emailAddress, photoFileName FROM user WHERE id = ?");
$stmt->bind_param("i", $adminID);
$stmt->execute();
$adminResult = $stmt->get_result();
$admin = $adminResult->fetch_assoc();
$stmt->close();

// --- إحصائيات النظام ---
// عدد المستخدمين
$totalUsers = $conn->query("SELECT COUNT(*) AS count FROM user WHERE userType = 'user'")->fetch_assoc()['count'];

// عدد الوصفات
$totalRecipes = $conn->query("SELECT COUNT(*) AS count FROM recipe")->fetch_assoc()['count'];

// عدد البلاغات المعلقة
$totalReports = $conn->query("SELECT COUNT(*) AS count FROM report")->fetch_assoc()['count'];

// --- 11c: جلب البلاغات المعلقة مع معلومات الوصفة وصاحبها ---
$reportsQuery = "
    SELECT 
        report.id AS reportID,
        recipe.id AS recipeID,
        recipe.name AS recipeName,
        recipe.photoFileName AS recipePhoto,
        user.id AS ownerID,
        user.firstName AS ownerFirst,
        user.lastName AS ownerLast,
        user.photoFileName AS ownerPhoto
    FROM report
    JOIN recipe ON report.recipeID = recipe.id
    JOIN user ON recipe.userID = user.id
";
$reportsResult = $conn->query($reportsQuery);

// --- 11d: جلب المستخدمين المحظورين ---
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

<!-- Header -->
<header>
    <div class="container">
        <div id="logo">
            <img src="images/logo.png" alt="Logo">
            <span>سُفرة</span>
        </div>
        <!-- 12: رابط تسجيل الخروج -->
        <a href="signout.php" class="sign-out">تسجيل الخروج</a>
    </div>
</header>

<div class="container">

    <!-- رسالة ترحيب -->
    <section class="welcome">
        <h1>مرحبا <span><?= htmlspecialchars($admin['firstName']) ?></span> !</h1>
    </section>

    <!-- شبكة المحتوى الرئيسي -->
    <div class="main-grid">

        <!-- معلومات الأدمن -->
        <section class="user-info-card">
            <div class="user-header">
                <div class="user-photo">
                    <?php if (!empty($admin['photoFileName']) && $admin['photoFileName'] !== 'default.png'): ?>
                        <!-- إذا عنده صورة شخصية -->
                        <img src="uploads/users/<?= htmlspecialchars($admin['photoFileName']) ?>" alt="صورة الأدمن">
                    <?php else: ?>
                        <!-- الحرف الأول من الاسم كبديل للصورة -->
                        <div class="user-photo-fallback">
                            <?= htmlspecialchars(mb_substr($admin['firstName'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="user-title">
                    <h2><?= htmlspecialchars($admin['firstName'] . ' ' . $admin['lastName']) ?></h2>
                </div>
            </div>

            <!-- بيانات الأدمن -->
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon">👤</div>
                    <div class="info-content">
                        <div class="info-label">الاسم</div>
                        <div class="info-value">
                            <?= htmlspecialchars($admin['firstName'] . ' ' . $admin['lastName']) ?>
                        </div>
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

        <!-- إحصائيات النظام -->
        <section class="my-recipes-card">
            <h3>إحصائيات النظام</h3>
            <div class="stats-boxes">
                <div class="stat-box">
                    <div class="stat-number"><?= $totalUsers ?></div>
                    <div class="stat-label">إجمالي المستخدمين</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $totalRecipes ?></div>
                    <div class="stat-label">إجمالي الوصفات</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $totalReports ?></div>
                    <div class="stat-label">البلاغات المعلقة</div>
                </div>
            </div>
        </section>

    </div>

    <!-- ===== 11c: جدول البلاغات المعلقة ===== -->
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
                    <!-- اسم الوصفة كرابط لصفحة عرضها -->
                    <td>
                        <a href="view-recipe.php?id=<?= $report['recipeID'] ?>" class="recipe-name-link">
                            <?= htmlspecialchars($report['recipeName']) ?>
                        </a>
                    </td>

                    <!-- صورة الوصفة -->
                    <td>
                        <img src="uploads/recipes/<?= htmlspecialchars($report['recipePhoto']) ?>"
                             class="recipe-image"
                             alt="<?= htmlspecialchars($report['recipeName']) ?>">
                    </td>

                    <!-- معلومات صاحب الوصفة -->
                    <td>
                        <div class="creator-info">
                            <?php if (!empty($report['ownerPhoto']) && $report['ownerPhoto'] !== 'default.png'): ?>
                                <img src="uploads/users/<?= htmlspecialchars($report['ownerPhoto']) ?>"
                                     class="creator-photo"
                                     alt="<?= htmlspecialchars($report['ownerFirst']) ?>">
                            <?php else: ?>
                                <div class="user-photo-fallback small">
                                    <?= htmlspecialchars(mb_substr($report['ownerFirst'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <span class="creator-name">
                                <?= htmlspecialchars($report['ownerFirst'] . ' ' . $report['ownerLast']) ?>
                            </span>
                        </div>
                    </td>

                    <!-- فورم الإجراء: حظر أو رفض البلاغ -->
                    <!-- الفورم يرسل recipeID و ownerID لصفحة handle_report.php -->
                    <td>
                        <form action="handle_report.php" method="POST">
                            <!-- بيانات مخفية ضرورية -->
                            <input type="hidden" name="reportID" value="<?= $report['reportID'] ?>">
                            <input type="hidden" name="recipeID" value="<?= $report['recipeID'] ?>">
                            <input type="hidden" name="ownerID" value="<?= $report['ownerID'] ?>">

                            <div class="action-buttons">
                                <!-- زر حظر المستخدم -->
                                <button type="submit" name="action" value="block" class="block-btn">
                                    حظر المستخدم
                                </button>
                                <!-- زر رفض البلاغ -->
                                <button type="submit" name="action" value="dismiss" class="dismiss-btn">
                                    رفض البلاغ
                                </button>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <?php else: ?>
            <!-- لا توجد بلاغات -->
            <p class="no-data-msg">لا توجد بلاغات معلقة حالياً ✅</p>
        <?php endif; ?>
    </section>

    <!-- ===== 11d: جدول المستخدمين المحظورين ===== -->
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
                            <!-- لا توجد صورة للمحظورين، نعرض الحرف الأول -->
                            <div class="user-photo-fallback small">
                                <?= htmlspecialchars(mb_substr($blocked['firstName'], 0, 1)) ?>
                            </div>
                            <span class="creator-name">
                                <?= htmlspecialchars($blocked['firstName'] . ' ' . $blocked['lastName']) ?>
                            </span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($blocked['emailAddress']) ?></td>
                    <td>
                        <!-- رابط لإلغاء الحظر -->
                        <a href="unblock_user.php?id=<?= $blocked['id'] ?>" class="unblock-btn">
                            إلغاء الحظر
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <?php else: ?>
            <p class="no-data-msg">لا يوجد مستخدمون محظورون حالياً</p>
        <?php endif; ?>
    </section>

</div>

<?php $conn->close(); ?>
</body>
</html>