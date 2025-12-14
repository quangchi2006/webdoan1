<?php
// Bắt đầu Session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gọi file kết nối và auth (Dùng __DIR__ để đi ra khỏi thư mục includes -> admin -> root)
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Kiểm tra quyền Admin ngay tại đây
checkAdmin();

// Lấy tên file hiện tại để tô màu menu (Active)
$current_page = basename($_SERVER['PHP_SELF']);

// Đếm số yêu cầu nạp tiền đang chờ (để hiện thông báo đỏ)
$cnt_deposit_wait = 0;
if (isset($conn)) {
    $cnt_deposit_wait = $conn->query("SELECT COUNT(*) FROM yeu_cau_nap_tien WHERE trang_thai = 'cho_duyet'")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Quản Trị - Trọ Tốt</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        .admin-content-padding { padding: 30px; }
        .badge-count { 
            background: red; color: white; font-size: 10px; 
            padding: 2px 6px; border-radius: 10px; margin-left: 5px; 
            vertical-align: middle;
        }
    </style>
</head>
<body>

<div class="admin-wrapper">
    
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fa-solid fa-shield-cat"></i> ADMIN CP</h3>
        </div>
        <ul class="sidebar-menu">
            <li class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <a href="index.php"><i class="fa-solid fa-gauge"></i> Tổng quan</a>
            </li>

            <li class="<?php echo ($current_page == 'quan-ly-tin.php') ? 'active' : ''; ?>">
                <a href="quan-ly-bai-dang.php"><i class="fa-solid fa-list-check"></i> Duyệt tin đăng</a>
            </li>

            <li class="<?php echo ($current_page == 'quan-ly-nap.php') ? 'active' : ''; ?>">
                <a href="quan-ly-nap.php">
                    <i class="fa-solid fa-money-bill-transfer"></i> Duyệt nạp tiền
                    <?php if($cnt_deposit_wait > 0): ?>
                        <span class="badge-count"><?php echo $cnt_deposit_wait; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="<?php echo ($current_page == 'quan-ly-nguoi-dung.php') ? 'active' : ''; ?>">
             <a href="quan-ly-nguoi-dung.php"><i class="fa-solid fa-users"></i> Quản lý người dùng</a>
            </li>

            <li style="border-top: 1px solid #34495e; margin-top: 10px;"></li>

            <li>
                <a href="../index.php" target="_blank"><i class="fa-solid fa-house"></i> Xem trang chủ</a>
            </li>
            
            <li>
                <a href="../dang-xuat.php" onclick="return confirm('Đăng xuất khỏi Admin?');">
                    <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        
        <div class="header-bar">
            <div class="page-title">
                <h4>Quản Trị Hệ Thống</h4>
            </div>
            <div class="user-info">
                <span>Xin chào, <strong><?php echo $_SESSION['fullname']; ?></strong></span>
                <img src="<?php echo !empty($_SESSION['avatar']) ? '../'.$_SESSION['avatar'] : '../assets/img/default-user.png'; ?>" class="admin-avatar">
            </div>
        </div>

        <div class="admin-content-padding"></div>