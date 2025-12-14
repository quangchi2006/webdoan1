<?php
session_start();
require_once 'includes/db.php'; // Kết nối Database

// 1. Nếu đã đăng nhập thì đá về trang chủ hoặc trang quản trị
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = '';

// 2. Xử lý Logic Đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    } else {
        // Truy vấn tìm user theo email
        $stmt = $conn->prepare("SELECT * FROM nguoidung WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user) {
            // Check khóa tài khoản
            if ($user['trang_thai'] == 0) {
                $error = "Tài khoản này đã bị khóa.";
            } 
            // Check mật khẩu (dùng password_verify để so khớp với hash trong DB)
            elseif (password_verify($password, $user['mat_khau'])) {
                // Đăng nhập thành công -> Lưu Session
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['ho_ten'];
                $_SESSION['role'] = $user['vai_tro'];
                $_SESSION['avatar'] = $user['avatar'];

                // Chuyển hướng
                if ($user['vai_tro'] === 'admin') {
                    header("Location: admin/index.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error = "Mật khẩu không chính xác.";
            }
        } else {
            $error = "Email này chưa được đăng ký.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập - Hệ thống Trọ Tốt</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); /* Màu nền Gradient đẹp */
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-card {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-header h2 { color: #333; margin-bottom: 10px; }
        .login-header p { color: #666; font-size: 14px; margin-bottom: 30px; }

        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #444; }
        
        .input-box {
            position: relative;
        }
        
        .input-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }

        .input-box input {
            width: 100%;
            padding: 12px 12px 12px 40px; /* Chừa chỗ cho icon */
            border: 1px solid #ddd;
            border-radius: 5px;
            outline: none;
            transition: 0.3s;
        }

        .input-box input:focus { border-color: #764ba2; }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: #764ba2;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-login:hover { background: #5a367f; }

        .error-msg {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: left;
            border-left: 4px solid #c62828;
        }

        .links { margin-top: 20px; font-size: 14px; display: flex; justify-content: space-between; }
        .links a { text-decoration: none; color: #764ba2; }
        .links a:hover { text-decoration: underline; }

        .back-home { margin-top: 30px; display: block; font-size: 13px; color: #888; text-decoration: none; }
        .back-home:hover { color: #333; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <h2>Chào mừng trở lại!</h2>
            <p>Vui lòng đăng nhập để tiếp tục</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-msg">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Email</label>
                <div class="input-box">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" placeholder="Nhập email..." required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Mật khẩu</label>
                <div class="input-box">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Nhập mật khẩu..." required>
                </div>
            </div>

            <button type="submit" class="btn-login">ĐĂNG NHẬP</button>

            <div class="links">
                <a href="dang-ky.php">Đăng ký mới</a>
                <a href="#">Quên mật khẩu?</a>
            </div>
        </form>

        <a href="index.php" class="back-home">
            <i class="fa-solid fa-arrow-left"></i> Quay lại trang chủ
        </a>
    </div>

</body>
</html> 