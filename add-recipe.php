<?php
// add-recipe.php
// Requirement 8: Add recipe page - loads categories from DB and shows the form

session_start();
require 'dp.php';

// Requirement 5: Only logged-in regular users can add recipes
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php?error=" . urlencode("You must be logged in as a regular user."));
    exit();
}

// Requirement 8a: Retrieve categories from database for the drop-down menu
$categories = $pdo->query("SELECT id, categoryName FROM recipecategory ORDER BY categoryName")->fetchAll(PDO::FETCH_ASSOC);

$error = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>سُفرة | إضافة وصفة</title>
  <link href="style.css" rel="stylesheet">
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <div id="logo">
      <img src="images/logo.png" alt="Logo">
      <span>سُفــــرة</span>
    </div>
    <nav class="nav">
      <a class="nav-chip" href="user.php">صفحة المستخدم</a>
      <a class="nav-chip" href="my-recipes.php">وصفاتي</a>
    </nav>
    <a href="signout.php" class="sign-out">تسجيل الخروج</a>
  </div>
</header>

<div class="container">
  <div class="form-container">
    <h1>إضافة وصفة جديدة</h1>

    <?php if ($error !== ''): ?>
      <p style="color:red; margin-bottom:15px;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <!-- Requirement 8b: Form submits to add_recipe_action.php -->
    <form id="recipeForm" action="add_recipe_action.php" method="POST" enctype="multipart/form-data">

      <div class="form-group">
        <label>اسم الوصفة:</label>
        <input type="text" name="name" required>
      </div>

      <!-- Requirement 8a: Categories loaded from database -->
      <div class="form-group">
        <label>التصنيف:</label>
        <select name="categoryID" required>
          <option value="">اختر التصنيف</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo (int)$cat['id']; ?>">
              <?php echo htmlspecialchars($cat['categoryName']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>الوصف:</label>
        <textarea name="description" required></textarea>
      </div>

      <div class="form-group">
        <label>رفع صورة الوصفة:</label>
        <input type="file" name="recipePhoto" accept="image/*" required>
      </div>

      <div class="form-group">
        <label>رفع فيديو الوصفة (اختياري):</label>
        <input type="file" name="recipeVideo" accept="video/*">
      </div>

      <!-- Ingredients section -->
      <div class="section-title" style="margin-top:20px;">المكونات:</div>
      <div id="ingredientsContainer">
        <div class="ingredient-row">
          <input type="text" name="ing_name[]" placeholder="اسم المكون" required>
          <input type="text" name="ing_qty[]"  placeholder="الكمية" required>
          <button type="button" class="delete-row-btn" onclick="this.parentElement.remove()">حذف</button>
        </div>
      </div>
      <button type="button" class="btn btn-outline" style="margin-top:8px;" onclick="addIngredient()">+ إضافة مكون</button>

      <!-- Instructions section -->
      <div class="section-title" style="margin-top:20px;">خطوات التحضير:</div>
      <div id="stepsContainer">
        <div class="ingredient-row">
          <label style="min-width:60px;">الخطوة 1:</label>
          <input type="text" name="step[]" placeholder="وصف الخطوة" required>
          <button type="button" class="delete-row-btn" onclick="removeStep(this)">حذف</button>
        </div>
      </div>
      <button type="button" class="btn btn-outline" style="margin-top:8px;" onclick="addStep()">+ إضافة خطوة</button>

      <div class="actions" style="margin-top:24px;">
        <button type="submit" class="btn btn-primary">حفظ الوصفة</button>
        <a href="my-recipes.php" class="btn btn-outline">إلغاء</a>
      </div>

    </form>
  </div>
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

<script>
  function addIngredient() {
    const container = document.getElementById('ingredientsContainer');
    const row = document.createElement('div');
    row.className = 'ingredient-row';
    row.innerHTML = '<input type="text" name="ing_name[]" placeholder="اسم المكون" required>'
                  + '<input type="text" name="ing_qty[]" placeholder="الكمية" required>'
                  + '<button type="button" class="delete-row-btn" onclick="this.parentElement.remove()">حذف</button>';
    container.appendChild(row);
  }

  function addStep() {
    const container = document.getElementById('stepsContainer');
    const count = container.querySelectorAll('.ingredient-row').length + 1;
    const row = document.createElement('div');
    row.className = 'ingredient-row';
    row.innerHTML = '<label style="min-width:60px;">الخطوة ' + count + ':</label>'
                  + '<input type="text" name="step[]" placeholder="وصف الخطوة" required>'
                  + '<button type="button" class="delete-row-btn" onclick="removeStep(this)">حذف</button>';
    container.appendChild(row);
  }

  function removeStep(btn) {
    btn.parentElement.remove();
    // Re-label remaining steps
    const rows = document.querySelectorAll('#stepsContainer .ingredient-row');
    rows.forEach((row, i) => {
      const lbl = row.querySelector('label');
      if (lbl) lbl.textContent = 'الخطوة ' + (i + 1) + ':';
    });
  }
</script>

</body>
</html>