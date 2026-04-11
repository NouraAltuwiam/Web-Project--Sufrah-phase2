<?php
// ===================================================
// handle_report.php - معالجة البلاغات (حظر أو رفض)
// هذه صفحة منفصلة للمعالجة فقط، لا تعرض واجهة
// ===================================================

session_start();

// تحقق أن المستخدم أدمن
if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'admin') {
    header("Location: login.php?error=غير مصرح لك");
    exit();
}

// تحقق أن البيانات المطلوبة وصلت
if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['action'], $_POST['reportID'], $_POST['recipeID'], $_POST['ownerID'])) {
    header("Location: admin.php");
    exit();
}

// اتصال بالداتابيس
$conn = new mysqli("localhost", "root", "", "sufrah_db");
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

$action   = $_POST['action'];
$reportID = (int) $_POST['reportID'];
$recipeID = (int) $_POST['recipeID'];
$ownerID  = (int) $_POST['ownerID'];

// ===== إذا الإجراء هو حظر المستخدم =====
if ($action === 'block') {

    // 1) جلب معلومات المستخدم قبل حذفه (لإضافته لجدول blockeduser)
    $stmt = $conn->prepare("SELECT firstName, lastName, emailAddress FROM user WHERE id = ?");
    $stmt->bind_param("i", $ownerID);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $userData   = $userResult->fetch_assoc();
    $stmt->close();

    if ($userData) {

        // 2) جلب كل وصفات هذا المستخدم لحذف ملفاتها
        $recipesStmt = $conn->prepare("SELECT id, photoFileName, videoFilePath FROM recipe WHERE userID = ?");
        $recipesStmt->bind_param("i", $ownerID);
        $recipesStmt->execute();
        $recipesResult = $recipesStmt->get_result();

        while ($rec = $recipesResult->fetch_assoc()) {
            $rID = $rec['id'];

            // حذف ملف صورة الوصفة من السيرفر
            if (!empty($rec['photoFileName'])) {
                $photoPath = "uploads/recipes/" . $rec['photoFileName'];
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }

            // حذف ملف فيديو الوصفة من السيرفر
            if (!empty($rec['videoFilePath'])) {
                $videoPath = "uploads/videos/" . $rec['videoFilePath'];
                if (file_exists($videoPath)) {
                    unlink($videoPath);
                }
            }

            // حذف البيانات المرتبطة بكل وصفة (الترتيب مهم بسبب foreign keys)
            $conn->query("DELETE FROM comment     WHERE recipeID = $rID");
            $conn->query("DELETE FROM likes       WHERE recipeID = $rID");
            $conn->query("DELETE FROM favourites  WHERE recipeID = $rID");
            $conn->query("DELETE FROM report      WHERE recipeID = $rID");
            $conn->query("DELETE FROM ingredients WHERE recipeID = $rID");
            $conn->query("DELETE FROM instructions WHERE recipeID = $rID");
        }
        $recipesStmt->close();

        // 3) حذف كل وصفات المستخدم من جدول recipe
        $delRecipes = $conn->prepare("DELETE FROM recipe WHERE userID = ?");
        $delRecipes->bind_param("i", $ownerID);
        $delRecipes->execute();
        $delRecipes->close();

        // 4) إضافة المستخدم لجدول blockeduser
        $blockStmt = $conn->prepare(
            "INSERT INTO blockeduser (firstName, lastName, emailAddress) VALUES (?, ?, ?)"
        );
        $blockStmt->bind_param("sss", $userData['firstName'], $userData['lastName'], $userData['emailAddress']);
        $blockStmt->execute();
        $blockStmt->close();

        // 5) حذف المستخدم من جدول user
        $delUser = $conn->prepare("DELETE FROM user WHERE id = ?");
        $delUser->bind_param("i", $ownerID);
        $delUser->execute();
        $delUser->close();
    }
}

// ===== حذف البلاغ في كلتا الحالتين (حظر أو رفض) =====
$delReport = $conn->prepare("DELETE FROM report WHERE id = ?");
$delReport->bind_param("i", $reportID);
$delReport->execute();
$delReport->close();

$conn->close();

// الرجوع لصفحة الأدمن
header("Location: admin.php");
exit();
?>