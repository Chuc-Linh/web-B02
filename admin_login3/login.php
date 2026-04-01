<?php
session_start();

require_once 'db.php'; // Nếu chưa có file db.php thì comment dòng này

// Nếu đã đăng nhập rồi thì chuyển thẳng vào admin
if (isset($_SESSION['admin']) && !empty($_SESSION['admin'])) {
    header("Location: admin.php");
    exit;
}

// Nếu chưa có session nhưng có cookie thì tự login lại
if (!isset($_SESSION['admin']) && isset($_COOKIE['admin_login']) && !empty($_COOKIE['admin_login'])) {
    $_SESSION['admin'] = $_COOKIE['admin_login'];
    header("Location: admin.php");
    exit;
}

// Khởi tạo biến
$err = "";
$username = '';           // Mặc định rỗng khi vào trang lần đầu
$login_attempts = $_SESSION['login_attempts'] ?? 0;

// Chỉ lấy username từ POST khi form được submit và có lỗi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Chống brute-force: 5 lần sai thì khóa tạm 30 giây
    if ($login_attempts >= 5) {

        if (time() - ($_SESSION['last_attempt'] ?? 0) < 30) {
            $err = "Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau 30 giây!";
        } else {
            unset($_SESSION['login_attempts']);
            unset($_SESSION['last_attempt']);
            $login_attempts = 0;
        }
    }

    if (!$err) {

        if (empty($username) || empty($password)) {

            $err = "Vui lòng nhập đầy đủ tài khoản và mật khẩu!";

        } else {

            // Hardcode users (trong thực tế nên lưu vào database)
            $users = [
                'admin'  => password_hash('123456', PASSWORD_DEFAULT),
                'admin2' => password_hash('654321', PASSWORD_DEFAULT),
            ];

            if (isset($users[$username]) && password_verify($password, $users[$username])) {

                $_SESSION['admin'] = $username;

                // Cookie đăng nhập 7 ngày
                setcookie("admin_login", $username, time() + (86400 * 7), "/");

                unset($_SESSION['login_attempts']);
                unset($_SESSION['last_attempt']);

                // Xóa dấu vết logout cũ ở client nếu có
                echo "<script>localStorage.removeItem('force-logout-timestamp');</script>";

                header("Location: admin.php");
                exit;

            } else {

                $err = "Tài khoản hoặc mật khẩu không đúng!";
                $_SESSION['login_attempts'] = $login_attempts + 1;
                $_SESSION['last_attempt'] = time();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Quản trị</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            background: #f4f4f4;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            border: 1px solid #e0e0e0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header i {
            font-size: 3.5rem;
            color: #444;
            margin-bottom: 1rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1.25rem;
            border: 1px solid #ced4da;
            transition: all 0.3s;
            background: #fafafa;
        }

        .form-control:focus {
            border-color: #555;
            box-shadow: 0 0 0 0.25rem rgba(85,85,85,0.15);
            background: #ffffff;
        }

        .btn-login {
            background: #333;
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background: #222;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(34,34,34,0.3);
        }

        .error-msg {
            color: #dc3545;
            font-weight: 500;
            text-align: center;
            margin-top: 1rem;
            font-size: 0.95rem;
        }

        .input-group-text {
            border-radius: 10px 0 0 10px;
            background: #e9ecef;
            color: #555;
            border: 1px solid #ced4da;
            border-right: none;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="login-card mx-auto">
        <div class="login-header">
            <i class="fas fa-user-shield"></i>
            <h3>ĐĂNG NHẬP QUẢN TRỊ</h3>
        </div>

        <form method="post" autocomplete="off">
            <?php if ($err): ?>
                <div class="alert alert-danger text-center mb-4" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($err) ?>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label">Tài khoản</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="username" class="form-control <?= $err ? 'is-invalid' : '' ?>" 
                           placeholder="Tên đăng nhập" required autofocus 
                           value="<?= htmlspecialchars($username) ?>" autocomplete="off">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Mật khẩu</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control <?= $err ? 'is-invalid' : '' ?>" 
                           placeholder="Mật khẩu" required autocomplete="off">
                </div>
            </div>

            <button type="submit" name="login" class="btn btn-login w-100">
                <i class="fas fa-sign-in-alt me-2"></i> Đăng nhập
            </button>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>