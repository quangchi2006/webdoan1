<?php
session_start();
require_once 'includes/db.php';

// Nếu đã đăng nhập thì không cho vào trang đăng ký
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Xử lý khi bấm nút Đăng Ký
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role']; // 'nguoi_thue' hoặc 'chu_tro'

    // 1. Validate dữ liệu
    if (empty($fullname) || empty($email) || empty($phone) || empty($password)) {
        $error = "Vui lòng điền đầy đủ thông tin.";
    } elseif ($password !== $confirm_password) {
        $error = "Mật khẩu nhập lại không khớp.";
    } elseif (strlen($password) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự.";
    } else {
        // 2. Kiểm tra xem Email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT id FROM nguoidung WHERE email = :email");
        $stmt->execute([':email' => $email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Email này đã được sử dụng. Vui lòng chọn email khác.";
        } else {
            // 3. Thêm người dùng mới
            // Mã hóa mật khẩu
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $avatar_default = 'assets/img/default-user.png';

            try {
                $sql = "INSERT INTO nguoidung (ho_ten, email, sdt, mat_khau, vai_tro, avatar, trang_thai) 
                        VALUES (:name, :email, :phone, :pass, :role, :avatar, 1)";
                $stmt_insert = $conn->prepare($sql);
                $stmt_insert->execute([
                    ':name' => $fullname,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':pass' => $hashed_password,
                    ':role' => $role,
                    ':avatar' => $avatar_default
                ]);

                $success = "Đăng ký thành công! Đang chuyển hướng...";
                
                // Tự động chuyển qua trang đăng nhập sau 2 giây
                echo "<script>
                        setTimeout(function(){
                            window.location.href = 'dang-nhap.php';
                        }, 2000);
                      </script>";

            } catch (PDOException $e) {
                $error = "Lỗi hệ thống: " . $e->getMessage();
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
    <title>Đăng Ký Tài Khoản - Trọ Tốt</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .register-card {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 500px; /* Rộng hơn form login một chút */
        }

        .header h2 { text-align: center; color: #333; margin-bottom: 5px; }
        .header p { text-align: center; color: #666; font-size: 14px; margin-bottom: 25px; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #444; font-size: 14px; }
        
        .input-box { position: relative; }
        .input-box i {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #888;
        }
        .input-box input, .input-box select {
            width: 100%;
            padding: 10px 10px 10px 40px;
            border: 1px solid #ddd;
            border-radius: 5px;
            outline: none;
            font-size: 14px;
        }
        .input-box input:focus, .input-box select:focus { border-color: #764ba2; }

        .btn-register {
            width: 100%;
            padding: 12px;
            background: #764ba2;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
        }
        .btn-register:hover { background: #5a367f; }

        .alert {
            padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 14px; text-align: center;
        }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }

        .login-link { text-align: center; margin-top: 20px; font-size: 14px; }
        .login-link a { color: #764ba2; text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="register-card">
        <div class="header">
            <h2>Tạo tài khoản mới</h2>
            <p>Tham gia cộng đồng Trọ Tốt ngay hôm nay</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Họ và tên</label>
                <div class="input-box">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="fullname" placeholder="VD: Nguyễn Văn A" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email đăng nhập</label>
                <div class="input-box">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" placeholder="VD: email@example.com" required>
                </div>
            </div>

            <div class="form-group">
                <label>Số điện thoại</label>
                <div class="input-box">
                    <i class="fa-solid fa-phone"></i>
                    <input type="text" name="phone" placeholder="VD: 0905123456" required>
                </div>
            </div>

            <div class="form-group">
                <label>Bạn là ai?</label>
                <div class="input-box">
                    <i class="fa-solid fa-users"></i>
                    <select name="role">
                        <option value="nguoi_thue">Người tìm trọ (Khách thuê)</option>
                        <option value="chu_tro">Chủ nhà trọ (Đăng tin)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Mật khẩu</label>
                <div class="input-box">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Nhập mật khẩu (tối thiểu 6 ký tự)" required>
                </div>
            </div>

            <div class="form-group">
                <label>Xác nhận mật khẩu</label>
                <div class="input-box">
                    <i class="fa-solid fa-shield-halved"></i>
                    <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
                </div>
            </div>

            <button type="submit" class="btn-register">ĐĂNG KÝ NGAY</button>
        </form>

        <div class="login-link">
            Đã có tài khoản? <a href="dang-nhap.php">Đăng nhập tại đây</a>
            <br><br>
            <a href="index.php" style="color: #666; font-size: 13px;"><i class="fa-solid fa-arrow-left"></i> Về trang chủ</a>
        </div>
    </div>

</body>
</html>