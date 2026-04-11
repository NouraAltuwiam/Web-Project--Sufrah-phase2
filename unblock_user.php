<?php
// ===================================================
// unblock_user.php - إلغاء حظر مستخدم
// يحذف المستخدم من جدول blockeduser فقط
// ===================================================

session_start();

// تحقق أن المستخدم أدمن
if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'admin') {
    header("Location: login.php?error=غير مصرح لك");
    exit();
}

// تحقق أن ID المستخدم المحظور موجود في الرابط
if (!isset($_GET['id'])) {
    header("Location: admin.php");
    exit();
}

$blockedID = (int) $_GET['id'];

// اتصال بالداتابيس
$conn = new mysqli("localhost", "root", "", "sufrah_db");
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// حذف المستخدم من جدول blockeduser
$stmt = $conn->prepare("DELETE FROM blockeduser WHERE id = ?");
$stmt->bind_param("i", $blockedID);
$stmt->execute();
$stmt->close();

$conn->close();

// الرجوع لصفحة الأدمن
header("Location: admin.php");
exit();
?>