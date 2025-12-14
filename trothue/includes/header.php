<?php
// 1. Khởi động Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Kết nối CSDL (Sử dụng __DIR__ để đường dẫn luôn đúng)
require_once __DIR__ . '/db.php'; 

// CẤU HÌNH ĐƯỜNG DẪN GỐC (Thay đổi dòng này nếu bạn đổi tên thư mục)
$base_url = '/DACS/web/trothue'; 

// 3. Lấy danh sách Loại phòng từ DB để tạo Menu
$ds_loai_phong = [];
if (isset($conn)) {
    try {
        $stmt_menu = $conn->prepare("SELECT * FROM loai_phong ORDER BY id ASC");
        $stmt_menu->execute();
        $ds_loai_phong = $stmt_menu->fetchAll();
    } catch (PDOException $e) {
        // Lỗi kết nối thì bỏ qua, không làm chết trang
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trọ Tốt - Kênh thông tin phòng trọ số 1</title>
    
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
    
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/detail.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/dang-tin.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/footer.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<header class="main-header">
    <div class="container">
        
        <div class="top-bar">
            <div class="logo">
                <a href="<?php echo $base_url; ?>/index.php">
                    <span class="logo-text">PHONGTRO<span class="highlight">TOT</span>.COM</span>
                    <span class="logo-slogan">Kênh thông tin phòng trọ số 1 Việt Nam</span>
                </a>
            </div>

            <div class="search-widget">
                <div class="location-picker">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Toàn quốc</span>
                </div>
                <div class="filter-btn">
                    <a href="<?php echo $base_url; ?>/tim-kiem.php">
                        <i class="fa-solid fa-filter"></i> Bộ lọc
                    </a>
                </div>
            </div>

            <div class="user-actions">
                <a href="#" class="action-item">
                    <i class="fa-regular fa-heart"></i> Tin đã lưu
                </a>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-dropdown">
                        <a href="#" class="action-item user-name" style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-regular fa-user" style="font-size: 20px;"></i>
                            
                            <div style="display: flex; flex-direction: column; line-height: 1.2;">
                                <span style="font-weight: 600;">
                                    <?php echo mb_substr($_SESSION['fullname'], 0, 10) . '...'; ?>
                                </span>
                                
                                <?php 
                                    $so_du = 0;
                                    if (isset($conn)) {
                                        $stmt_bal = $conn->prepare("SELECT so_du FROM nguoidung WHERE id = :id");
                                        $stmt_bal->execute([':id' => $_SESSION['user_id']]);
                                        $so_du = $stmt_bal->fetchColumn();
                                    }
                                ?>
                                <span style="font-size: 11px; color: #f73859; font-weight: bold;">
                                    Số dư: <?php echo number_format($so_du); ?>đ
                                </span>
                            </div>
                        </a>
                        
                        <ul class="dropdown-menu">
                            <li><a href="<?php echo $base_url; ?>/profile.php"><i class="fa-solid fa-id-card"></i> Tài khoản</a></li>
                            <li><a href="<?php echo $base_url; ?>/nap-tien.php"><i class="fa-solid fa-wallet"></i> Nạp tiền</a></li>
                            
                            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                <li><a href="<?php echo $base_url; ?>/admin/"><i class="fa-solid fa-gear"></i> Quản trị</a></li>
                            <?php endif; ?>
                            
                            <li><a href="<?php echo $base_url; ?>/dang-xuat.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>/dang-ky.php" class="action-item">
                        <i class="fa-solid fa-user-plus"></i> Đăng ký
                    </a>
                    <a href="<?php echo $base_url; ?>/dang-nhap.php" class="action-item">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Đăng nhập
                    </a>
                <?php endif; ?>

                <a href="<?php echo $base_url; ?>/dang-tin.php" class="btn-post-new">
                    <i class="fa-regular fa-pen-to-square"></i> Đăng tin
                </a>
            </div>
        </div>

        <nav class="main-nav">
            <div class="container">
                <ul>
                    <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' && !isset($_GET['loai'])) ? 'active' : ''; ?>">
                        <a href="<?php echo $base_url; ?>/index.php">Trang chủ</a>
                    </li>

                    <?php if (!empty($ds_loai_phong)): ?>
                        <?php foreach ($ds_loai_phong as $loai): ?>
                            <?php 
                                // Kiểm tra Active
                                $is_active = (isset($_GET['loai']) && $_GET['loai'] == $loai['id']) ? 'active' : '';
                            ?>
                            <li class="<?php echo $is_active; ?>">
                                <a href="<?php echo $base_url; ?>/index.php?loai=<?php echo $loai['id']; ?>">
                                    <?php echo htmlspecialchars($loai['ten_loai']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Bảng giá</a></li>
                    <li><a href="<?php echo $base_url; ?>/nap-tien.php">Nạp tiền</a></li>
                </ul>
            </div>
        </nav>
    </div>
</header>

<div style="height: 10px;"></div>