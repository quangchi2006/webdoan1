<?php
// Cấu hình thông tin kết nối
$host = 'localhost';      // Tên server (thường là localhost nếu dùng XAMPP)
$db   = 'trothue';        // Tên cơ sở dữ liệu bạn đã tạo trong phpMyAdmin
$user = 'root';           // Tên đăng nhập MySQL (Mặc định XAMPP là root)
$pass = '';               // Mật khẩu MySQL (Mặc định XAMPP để trống)
$charset = 'utf8mb4';     // Bảng mã hỗ trợ tiếng Việt đầy đủ (cả emoji)

// Thiết lập DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Các tùy chọn cho PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Báo lỗi dạng Exception để dễ bắt lỗi
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Mặc định trả về mảng kết hợp (key là tên cột)
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Tắt giả lập prepare để bảo mật tốt hơn
];

try {
    // Khởi tạo kết nối
    $conn = new PDO($dsn, $user, $pass, $options);
    
    // Nếu chạy đến đây mà không lỗi nghĩa là kết nối thành công!
    // echo "Kết nối thành công!"; // (Bỏ comment dòng này nếu muốn test)

} catch (\PDOException $e) {
    // Nếu lỗi thì dừng trang web và báo lỗi
    // Trong thực tế nên ghi log thay vì hiện lỗi ra màn hình
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
?>