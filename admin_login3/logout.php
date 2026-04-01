<?php
session_start();

// Xóa session
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Xóa cookie đăng nhập
setcookie("admin_login", "", time() - 3600, "/");

session_destroy();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Đang đăng xuất...</title>
</head>

<body>

<p style="text-align:center; margin-top:100px;">Đang đăng xuất...</p>

<script>
localStorage.setItem('force-logout-timestamp', Date.now().toString());
window.location.replace('login.php');
</script>

</body>
</html>

<?php exit; ?>