<?php
// user.php
// Requirement 6: User dashboard page - shows profile, stats, all recipes, and favourites

session_start();
require_once 'dp.php';

// Requirement 5 & 6a: Check that the logged-in user is a regular user
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php?error=" . urlencode("You must be logged in as a regular user to access this page."));
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Requirement 6b: Retrieve user info from the database
$stmt_user = $pdo->prepare("SELECT id, firstName, lastName, emailAddress, photoFileName FROM user WHERE id = ? AND userType = 'user'");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: login.php?error=" . urlencode("User not found."));
    exit();
}

$full_name  = trim($user['firstName'] . " " . $user['lastName']);
$photo_file = !empty($user['photoFileName']) ? $user['photoFileName'] : "default-user.png";
$photo_path = "images/users/" . $photo_file;
// Fallback to images/ root if not in users subfolder
if (!file_exists($photo_path)) {
    $photo_path = "images/" . $photo_file;
}

// Requirement 6c: Total recipes for this user
$stmt_total_recipes = $pdo->prepare("SELECT COUNT(*) AS total_recipes FROM recipe WHERE userID = ?");
$stmt_total_recipes->execute([$user_id]);
$total_recipes = (int) $stmt_total_recipes->fetchColumn();

// Requirement 6c: Total likes across all user's recipes
$stmt_total_likes = $pdo->prepare("
    SELECT COUNT(l.recipeID) AS total_likes
    FROM recipe r
    LEFT JOIN likes l ON r.id = l.recipeID
    WHERE r.userID = ?
");
$stmt_total_likes->execute([$user_id]);
$total_likes = (int) $stmt_total_likes->fetchColumn();

// Requirement 6d: Retrieve categories from database for filter form
$result_categories = $pdo->query("SELECT id, categoryName FROM recipecategory ORDER BY categoryName");
$categories = $result_categories->fetchAll(PDO::FETCH_ASSOC);

// Requirement 6e: Recipes list - GET = all, POST = filtered by selected category
$selected_category_id = "";
$no_recipes_message   = "";

$base_sql = "
    SELECT
        r.id,
        r.name,
        r.photoFileName,
        u.firstName,
        u.lastName,
        u.photoFileName AS userPhoto,
        c.categoryName,
        COUNT(l.recipeID) AS totalLikes
    FROM recipe r
    LEFT JOIN user u            ON r.userID    = u.id
    LEFT JOIN recipecategory c  ON r.categoryID = c.id
    LEFT JOIN likes l           ON r.id         = l.recipeID
";

$group_sql = "
    GROUP BY r.id, r.name, r.photoFileName, u.firstName, u.lastName, u.photoFileName, c.categoryName
    ORDER BY r.id DESC
";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_id'])) {
    $selected_category_id = trim($_POST['category_id']);
    if ($selected_category_id === "") {
        // No category selected - show all
        $stmt_recipes = $pdo->query($base_sql . $group_sql);
        $recipes_rows = $stmt_recipes->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $selected_category_id = (int) $selected_category_id;
        $stmt_recipes = $pdo->prepare($base_sql . " WHERE r.categoryID = ? " . $group_sql);
        $stmt_recipes->execute([$selected_category_id]);
        $recipes_rows = $stmt_recipes->fetchAll(PDO::FETCH_ASSOC);
    }
} else {
    $stmt_recipes = $pdo->query($base_sql . $group_sql);
    $recipes_rows = $stmt_recipes->fetchAll(PDO::FETCH_ASSOC);
}

if (count($recipes_rows) === 0) {
    $no_recipes_message = "No recipes found in this category.";
}

// Requirement 6f: Retrieve favourite recipes of this user
$stmt_fav = $pdo->prepare("
    SELECT r.id, r.name, r.photoFileName
    FROM favourites f
    INNER JOIN recipe r ON f.recipeID = r.id
    WHERE f.userID = ?
    ORDER BY r.id DESC
");
$stmt_fav->execute([$user_id]);
$favourites = $stmt_fav->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>سفرة | صفحة المستخدم</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .filter-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin:16px 0 20px; }
    .filter-form { display:flex; align-items:center; gap:10px; flex-wrap:wrap; width:100%; }
  </style>
</head>
<body>

  <header class="site-header">
    <div class="container header-inner">
      <div id="logo">
        <img src="images/logo.png" alt="Logo">
        <span>سُفــــرة</span>
      </div>
      <!-- Requirement 12: Sign-out link goes to signout.php -->
      <a href="signout.php" class="sign-out">تسجيل الخروج</a>
    </div>
  </header>

  <div class="container">

    <!-- Requirement 6b: Welcome note with user's first name -->
    <section class="welcome">
      <h1>مرحبًا <span><?php echo htmlspecialchars($user['firstName']); ?></span> !</h1>
    </section>

    <div class="main-grid">

      <!-- Requirement 6b: User info card -->
      <section class="user-info-card">
        <div class="user-header">
          <div class="user-photo">
            <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="User Avatar" style="width:100%;height:100%;object-fit:cover;">
          </div>
          <div class="user-title">
            <h2><?php echo htmlspecialchars($full_name); ?></h2>
          </div>
        </div>
        <div class="info-grid">
          <div class="info-item">
            <div class="info-icon">👤</div>
            <div class="info-content">
              <div class="info-label">الاسم</div>
              <div class="info-value"><?php echo htmlspecialchars($full_name); ?></div>
            </div>
          </div>
          <div class="info-item">
            <div class="info-icon">📧</div>
            <div class="info-content">
              <div class="info-label">البريد الإلكتروني</div>
              <div class="info-value"><?php echo htmlspecialchars($user['emailAddress']); ?></div>
            </div>
          </div>
        </div>
      </section>

      <!-- Requirement 6c: Stats card with link to my recipes page -->
      <section class="my-recipes-card">
        <h3>وصفاتي</h3>
        <!-- Requirement 6c: Link to my recipes page -->
        <a href="my-recipes.php" class="recipes-link">
          عرض جميع وصفاتي
          <img src="images/arrow.svg" class="icon-sm" alt="arrow">
        </a>
        <div class="stats-boxes">
          <div class="stat-box">
            <div class="stat-number"><?php echo $total_recipes; ?></div>
            <div class="stat-label">إجمالي الوصفات</div>
          </div>
          <div class="stat-box">
            <div class="stat-number">
              <img src="images/heart.svg" class="heart-icon" alt="heart">
              <span><?php echo $total_likes; ?></span>
            </div>
            <div class="stat-label">إجمالي الإعجابات</div>
          </div>
        </div>
      </section>

    </div>

    <!-- Requirement 6d & 6e: All recipes with category filter -->
    <section class="all-recipes-section">
      <h2 class="section-title">جميع الوصفات المتاحة</h2>

      <!-- Requirement 6d: Filter form using POST -->
      <div class="filter-bar">
        <form method="POST" action="user.php" class="filter-form">
          <label for="categoryFilter" class="filter-label">تصفية حسب الفئة:</label>
          <select name="category_id" id="categoryFilter" class="category-select">
            <option value="">جميع الفئات</option>
            <?php foreach ($categories as $cat) { ?>
              <option value="<?php echo (int)$cat['id']; ?>"
                <?php echo ((string)$selected_category_id === (string)$cat['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['categoryName']); ?>
              </option>
            <?php } ?>
          </select>
          <button class="filter-button" type="submit">تصفية</button>
        </form>
      </div>

      <?php if ($no_recipes_message !== "") { ?>
        <p><?php echo htmlspecialchars($no_recipes_message); ?></p>
      <?php } else { ?>
        <table class="recipes-table">
          <thead>
            <tr>
              <th>اسم الوصفة</th>
              <th>صورة الوصفة</th>
              <th>صاحب الوصفة</th>
              <th>عدد الإعجابات</th>
              <th>الفئة</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recipes_rows as $recipe) { ?>
              <tr>
                <td>
                  <!-- Requirement 6e: Recipe name is a link to view-recipe page -->
                  <a href="view-recipe.php?id=<?php echo (int)$recipe['id']; ?>" class="recipe-name-link">
                    <?php echo htmlspecialchars($recipe['name']); ?>
                  </a>
                </td>
                <td>
                  <img class="thumb-img"
                       src="images/<?php echo htmlspecialchars($recipe['photoFileName']); ?>"
                       alt="<?php echo htmlspecialchars($recipe['name']); ?>"
                       onerror="this.src='images/default.png'">
                </td>
                <td>
                  <div class="creator-info">
                    <img src="images/<?php echo htmlspecialchars(!empty($recipe['userPhoto']) ? $recipe['userPhoto'] : 'default-user.png'); ?>"
                         class="creator-photo"
                         alt="<?php echo htmlspecialchars($recipe['firstName']); ?>"
                         onerror="this.src='images/default-user.png'">
                    <span class="creator-name">
                      <?php echo htmlspecialchars(trim($recipe['firstName'] . " " . $recipe['lastName'])); ?>
                    </span>
                  </div>
                </td>
                <td>
                  <img src="images/heart.svg" class="likes-heart" alt="likes">
                  <?php echo (int)$recipe['totalLikes']; ?>
                </td>
                <td>
                  <span class="reel-category"><?php echo htmlspecialchars($recipe['categoryName']); ?></span>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      <?php } ?>
    </section>

    <!-- Requirement 6f: Favourites section -->
    <section class="favorites-section">
      <h2 class="section-title">
        <img src="images/heart.svg" class="heart-title" alt="heart">
        وصفاتي المفضلة
      </h2>

      <?php if (count($favourites) > 0) { ?>
        <table class="favorites-table">
          <thead>
            <tr>
              <th>اسم الوصفة</th>
              <th>صورة الوصفة</th>
              <th>إجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($favourites as $fav) { ?>
              <tr>
                <td>
                  <!-- Requirement 6f: Recipe name is a link to view-recipe page -->
                  <a href="view-recipe.php?id=<?php echo (int)$fav['id']; ?>" class="recipe-name-link">
                    <?php echo htmlspecialchars($fav['name']); ?>
                  </a>
                </td>
                <td>
                  <img class="thumb-img"
                       src="images/<?php echo htmlspecialchars($fav['photoFileName']); ?>"
                       alt="<?php echo htmlspecialchars($fav['name']); ?>"
                       onerror="this.src='images/default.png'">
                </td>
                <td>
                  <!-- Requirement 6f: Remove link goes to remove-favourite.php -->
                  <a href="remove-favourite.php?recipe_id=<?php echo (int)$fav['id']; ?>"
                     onclick="return confirm('Are you sure you want to remove this from favourites?');"
                     class="btn btn-outline" style="font-size:0.85rem;">
                    حذف
                  </a>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      <?php } else { ?>
        <p>لا توجد وصفات مفضلة لديك.</p>
      <?php } ?>
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
          <li><a href="my-recipes.php">وصفاتي</a></li>
          <li><a href="add-recipe.php">إضافة وصفة</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4 class="footer-heading">التصنيفات</h4>
        <ul class="footer-links">
          <li><a href="user.php">إفطار</a></li>
          <li><a href="user.php">سحور</a></li>
          <li><a href="user.php">حلويات</a></li>
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