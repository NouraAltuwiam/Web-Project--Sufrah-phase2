<?php
session_start();
require_once("db.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php?msg=" . urlencode("You must login as a regular user"));
    exit();
}

$user_id = (int) $_SESSION['user_id'];

/* 1) Retrieve user info*/
$sql_user = "SELECT id, firstName, lastName, emailAddress, photoFileName 
             FROM user 
             WHERE id = ? AND userType = 'user'";
$stmt_user = mysqli_prepare($conn, $sql_user);

if (!$stmt_user) {
    die("Query preparation failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$user = mysqli_fetch_assoc($result_user);

if (!$user) {
    header("Location: login.php?msg=" . urlencode("User not found"));
    exit();
}

$full_name = trim($user['firstName'] . " " . $user['lastName']);
$photo_file = !empty($user['photoFileName']) ? $user['photoFileName'] : "default.png";
$photo_path = "images/" . $photo_file;

/*  2) Total recipes for this user*/
$sql_total_recipes = "SELECT COUNT(*) AS total_recipes 
                      FROM recipe 
                      WHERE userID = ?";
$stmt_total_recipes = mysqli_prepare($conn, $sql_total_recipes);

if (!$stmt_total_recipes) {
    die("Query preparation failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt_total_recipes, "i", $user_id);
mysqli_stmt_execute($stmt_total_recipes);
$result_total_recipes = mysqli_stmt_get_result($stmt_total_recipes);
$row_total_recipes = mysqli_fetch_assoc($result_total_recipes);
$total_recipes = (int) $row_total_recipes['total_recipes'];

/* 3) Total likes for all user's recipes */
$sql_total_likes = "SELECT COUNT(l.recipeID) AS total_likes
                    FROM recipe r
                    LEFT JOIN likes l ON r.id = l.recipeID
                    WHERE r.userID = ?";
$stmt_total_likes = mysqli_prepare($conn, $sql_total_likes);

if (!$stmt_total_likes) {
    die("Query preparation failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt_total_likes, "i", $user_id);
mysqli_stmt_execute($stmt_total_likes);
$result_total_likes = mysqli_stmt_get_result($stmt_total_likes);
$row_total_likes = mysqli_fetch_assoc($result_total_likes);
$total_likes = (int) $row_total_likes['total_likes'];

/* 4) Retrieve categories for the filter form */
$sql_categories = "SELECT id, categoryName 
                   FROM recipecategory 
                   ORDER BY categoryName";
$result_categories = mysqli_query($conn, $sql_categories);

if (!$result_categories) {
    die("Failed to retrieve categories: " . mysqli_error($conn));
}

/* Save categories in array because result set is consumed in loop */
$categories = [];
while ($category_row = mysqli_fetch_assoc($result_categories)) {
    $categories[] = $category_row;
}

/*5) Recipes list (GET = all, POST = filtered by category) */
$selected_category_id = "";
$recipes_result = null;
$no_recipes_message = "";

$base_sql_recipes = "
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
    LEFT JOIN user u ON r.userID = u.id
    LEFT JOIN recipecategory c ON r.categoryID = c.id
    LEFT JOIN likes l ON r.id = l.recipeID
";

$group_by_sql = "
    GROUP BY 
        r.id, r.name, r.photoFileName,
        u.firstName, u.lastName, u.photoFileName,
        c.categoryName
    ORDER BY r.id DESC
";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['category_id'])) {
        $selected_category_id = trim($_POST['category_id']);
    }

    if ($selected_category_id === "") {
        $sql_recipes = $base_sql_recipes . $group_by_sql;
        $recipes_result = mysqli_query($conn, $sql_recipes);
    } else {
        $selected_category_id = (int) $selected_category_id;

        $sql_recipes = $base_sql_recipes . "
            WHERE r.categoryID = ?
        " . $group_by_sql;

        $stmt_recipes = mysqli_prepare($conn, $sql_recipes);

        if (!$stmt_recipes) {
            die("Query preparation failed: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt_recipes, "i", $selected_category_id);
        mysqli_stmt_execute($stmt_recipes);
        $recipes_result = mysqli_stmt_get_result($stmt_recipes);
    }
} else {
    $sql_recipes = $base_sql_recipes . $group_by_sql;
    $recipes_result = mysqli_query($conn, $sql_recipes);
}

if (!$recipes_result) {
    die("Failed to retrieve recipes: " . mysqli_error($conn));
}

if (mysqli_num_rows($recipes_result) === 0) {
    $no_recipes_message = "لا توجد وصفات في هذه الفئة.";
}

/* 
   6) Retrieve favourite recipes of this user
 */
$sql_favourites = "
    SELECT 
        r.id,
        r.name,
        r.photoFileName
    FROM favourites f
    INNER JOIN recipe r ON f.recipeID = r.id
    WHERE f.userID = ?
    ORDER BY r.id DESC
";

$stmt_favourites = mysqli_prepare($conn, $sql_favourites);

if (!$stmt_favourites) {
    die("Query preparation failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt_favourites, "i", $user_id);
mysqli_stmt_execute($stmt_favourites);
$result_favourites = mysqli_stmt_get_result($stmt_favourites);

if (!$result_favourites) {
    die("Failed to retrieve favourite recipes.");
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>سفرة</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .fav-item:hover {
      color: #8FAE9E;
      text-decoration: underline;
    }

    .filter-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      margin: 16px 0 20px;
    }

    .filter-form {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      width: 100%;
    }

    .remove-link {
      display: inline-block;
      text-decoration: none;
      color: inherit;
      width: 100%;
      height: 100%;
    }

    .block-btn {
      cursor: pointer;
    }
  </style>
</head>
<body>

  <header class="site-header">
    <div class="container header-inner">
      <div id="logo">
        <img src="images/logo.png" alt="Logo">
        <span>سُفــــرة</span>
      </div>

      <a href="login.php" class="sign-out">تسجيل الخروج</a>
    </div>
  </header>

  <div class="container">

    <section class="welcome">
      <h1>مرحبًا <span><?php echo htmlspecialchars($user['firstName']); ?></span> !</h1>
    </section>

    <div class="main-grid">

      <section class="user-info-card">
        <div class="user-header">
          <div class="user-photo">
            <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="User Avatar" style="width: 100%; height: 100%; object-fit: cover;">
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

      <section class="my-recipes-card">
        <h3>وصفاتي</h3>

        <a href="my-recipes.php" class="recipes-link">
          عرض جميع وصفاتي
          <img src="images/arrow.svg" class="icon-sm" alt="سهم">
        </a>

        <div class="stats-boxes">
          <div class="stat-box">
            <div class="stat-number" id="totalRecipes"><?php echo $total_recipes; ?></div>
            <div class="stat-label">إجمالي الوصفات</div>
          </div>

          <div class="stat-box">
            <div class="stat-number">
              <img src="images/heart.svg" class="heart-icon" alt="قلب">
              <span id="totalLikes"><?php echo $total_likes; ?></span>
            </div>
            <div class="stat-label">إجمالي الإعجابات</div>
          </div>
        </div>
      </section>

    </div>

    <section class="all-recipes-section">
      <h2 class="section-title">جميع الوصفات المتاحة</h2>

      <div class="filter-bar">
        <form method="POST" action="user.php" class="filter-form">
          <img src="images/filter.svg" class="icon-sm" alt="تصفية">

          <label for="categoryFilter" class="filter-label">تصفية حسب الفئة:</label>

          <select name="category_id" id="categoryFilter" class="category-select">
            <option value="">جميع الفئات</option>
            <?php foreach ($categories as $category) { ?>
              <option value="<?php echo (int) $category['id']; ?>"
                <?php echo ((string)$selected_category_id === (string)$category['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($category['categoryName']); ?>
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
          <tbody id="allRecipesBody">
            <?php while ($recipe = mysqli_fetch_assoc($recipes_result)) { ?>
              <tr>
                <td>
                  <a href="view-recipe.php?id=<?php echo (int) $recipe['id']; ?>" class="recipe-name-link">
                    <?php echo htmlspecialchars($recipe['name']); ?>
                  </a>
                </td>

                <td>
                  <img class="thumb-img"
                       src="images/<?php echo htmlspecialchars($recipe['photoFileName']); ?>"
                       alt="<?php echo htmlspecialchars($recipe['name']); ?>">
                </td>

                <td>
                  <div class="creator-info">
                    <img src="images/<?php echo htmlspecialchars(!empty($recipe['userPhoto']) ? $recipe['userPhoto'] : 'default.png'); ?>"
                         class="creator-photo"
                         alt="<?php echo htmlspecialchars($recipe['firstName']); ?>">

                    <span class="creator-name">
                      <?php echo htmlspecialchars(trim($recipe['firstName'] . " " . $recipe['lastName'])); ?>
                    </span>
                  </div>
                </td>

                <td>
                  <img src="images/heart.svg" class="likes-heart" alt="إعجاب">
                  <?php echo (int) $recipe['totalLikes']; ?>
                </td>

                <td>
                  <span class="reel-category">
                    <?php echo htmlspecialchars($recipe['categoryName']); ?>
                  </span>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      <?php } ?>
    </section>

    <section class="favorites-section">
      <h2 class="section-title">
        <img src="images/heart.svg" class="heart-title" alt="قلب">
        وصفاتي المفضلة
      </h2>

      <?php if (mysqli_num_rows($result_favourites) > 0) { ?>
        <table class="favorites-table">
          <thead>
            <tr>
              <th>اسم الوصفة</th>
              <th>صورة الوصفة</th>
              <th>إجراء</th>
            </tr>
          </thead>

          <tbody id="favRecipesBody">
            <?php while ($fav = mysqli_fetch_assoc($result_favourites)) { ?>
              <tr>
                <td>
                  <div class="creator-info">
                    <a href="view-recipe.php?id=<?php echo (int) $fav['id']; ?>" style="text-decoration: underline; text-decoration-color:#333;">
                      <span class="creator-name fav-item">
                        <?php echo htmlspecialchars($fav['name']); ?>
                      </span>
                    </a>
                  </div>
                </td>

                <td>
                  <img class="thumb-img"
                       src="images/<?php echo htmlspecialchars($fav['photoFileName']); ?>"
                       alt="<?php echo htmlspecialchars($fav['name']); ?>">
                </td>

                <td>
                  <a class="remove-link" href="remove-favourite.php?recipe_id=<?php echo (int) $fav['id']; ?>" onclick="return confirm('هل تريد حذف هذه الوصفة من المفضلة؟');">
                    <button class="block-btn" type="button">حذف</button>
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
        <p class="footer-text">
          منصة وصفات رمضانية تساعدك توصل لوصفات الإفطار والسحور بطريقة مرتبة وبسيطة.
        </p>
      </div>

      <div class="footer-col">
        <h4 class="footer-heading">استكشاف</h4>
        <ul class="footer-links">
          <li><a href="index.html">الرئيسية</a></li>
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