<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

// 1. Kiểm tra đăng nhập
checkLogin();
$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// 2. XỬ LÝ GỬI YÊU CẦU NẠP TIỀN
if (isset($_POST['btn_gui_yeu_cau'])) {
    $amount = str_replace([',', '.'], '', $_POST['amount']); // Loại bỏ dấu phẩy/chấm nếu có
    $note = trim($_POST['note']);
    
    // Validate số tiền
    if (!is_numeric($amount) || $amount < 10000) {
        $error_msg = "Số tiền nạp tối thiểu là 10.000đ.";
    } 
    // Validate ảnh
    elseif (!isset($_FILES['proof_img']) || $_FILES['proof_img']['error'] != 0) {
        $error_msg = "Vui lòng tải lên ảnh chụp màn hình chuyển khoản.";
    } else {
        // Xử lý upload ảnh
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['proof_img']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_name = "nap_" . $user_id . "_" . time() . "." . $ext;
            $dest = 'assets/uploads/' . $new_name;
            
            if (move_uploaded_file($_FILES['proof_img']['tmp_name'], $dest)) {
                // Insert vào DB
                try {
                    $sql = "INSERT INTO yeu_cau_nap_tien (nguoi_dung_id, so_tien, anh_chung_minh, ghi_chu) 
                            VALUES (:uid, :tien, :anh, :note)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        ':uid' => $user_id,
                        ':tien' => $amount,
                        ':anh' => $new_name,
                        ':note' => $note
                    ]);
                    
                    $success_msg = "Gửi yêu cầu thành công! Admin sẽ duyệt trong 5-10 phút.";
                } catch (PDOException $e) {
                    $error_msg = "Lỗi hệ thống: " . $e->getMessage();
                }
            } else {
                $error_msg = "Lỗi khi tải ảnh lên server.";
            }
        } else {
            $error_msg = "Định dạng ảnh không hợp lệ (Chỉ hỗ trợ JPG, PNG).";
        }
    }
}

// 3. LẤY LỊCH SỬ NẠP TIỀN
$stmt_his = $conn->prepare("SELECT * FROM yeu_cau_nap_tien WHERE nguoi_dung_id = :uid ORDER BY id DESC");
$stmt_his->execute([':uid' => $user_id]);
$history = $stmt_his->fetchAll();

include 'includes/header.php';
?>

<div class="container" style="margin-top: 30px; margin-bottom: 50px;">
    
    <div class="deposit-layout">
        <div class="deposit-form-box">
            <h2 class="box-title">Nạp Tiền Vào Tài Khoản</h2>
            
            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert alert-error"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div class="bank-info">
                <h4><i class="fa-solid fa-building-columns"></i> Thông tin chuyển khoản</h4>
                <p>Ngân hàng: <strong>MB Bank (Quân Đội)</strong></p>
                <p>Số tài khoản: <strong class="text-highlight" style="font-size: 18px;">0905123456</strong></p>
                <p>Chủ tài khoản: <strong>NGUYEN VAN A</strong></p>
                <p>Nội dung CK: <strong class="text-highlight">NAP <?php echo $user_id; ?></strong></p>
            </div>

            <hr>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Số tiền đã chuyển (VNĐ) <span class="required">*</span></label>
                    <input type="number" name="amount" class="form-control" placeholder="VD: 50000" required min="10000">
                </div>

                <div class="form-group">
                    <label>Ảnh chụp màn hình (Bill) <span class="required">*</span></label>
                    <input type="file" name="proof_img" class="form-control" required accept="image/*">
                </div>

                <div class="form-group">
                    <label>Ghi chú (Nếu có)</label>
                    <textarea name="note" class="form-control" placeholder="Nhập mã giao dịch hoặc ghi chú thêm..."></textarea>
                </div>

                <button type="submit" name="btn_gui_yeu_cau" class="btn-deposit-submit">
                    <i class="fa-solid fa-paper-plane"></i> Gửi Yêu Cầu
                </button>
            </form>
        </div>

        <div class="deposit-history-box">
            <h3 class="history-title">Lịch Sử Nạp Tiền</h3>
            
            <?php if (count($history) > 0): ?>
                <div class="history-list">
                    <?php foreach ($history as $item): ?>
                        <div class="history-item">
                            <div class="his-left">
                                <span class="his-amount">+ <?php echo number_format($item['so_tien']); ?> đ</span>
                                <span class="his-time"><?php echo date("d/m/Y H:i", strtotime($item['ngay_tao'])); ?></span>
                            </div>
                            <div class="his-right">
                                <?php if ($item['trang_thai'] == 'cho_duyet'): ?>
                                    <span class="badge-status pending">Đang xử lý</span>
                                <?php elseif ($item['trang_thai'] == 'thanh_cong'): ?>
                                    <span class="badge-status success">Thành công</span>
                                <?php else: ?>
                                    <span class="badge-status failed">Đã hủy</span>
                                <?php endif; ?>
                                <a href="assets/uploads/<?php echo $item['anh_chung_minh']; ?>" target="_blank" class="view-bill">Xem Bill</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #777; margin-top: 20px;">Chưa có giao dịch nào.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>