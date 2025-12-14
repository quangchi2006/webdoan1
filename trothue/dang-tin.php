<?php
// 1. Khởi động Session và Kết nối
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

// 2. Kiểm tra đăng nhập (Bắt buộc)
checkLogin();

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// CẤU HÌNH PHÍ ĐĂNG TIN
$PHI_DANG_TIN = 20000; // 20.000 VNĐ

// 3. Lấy dữ liệu danh mục để đổ vào Form
try {
    $quan_huyen = $conn->query("SELECT * FROM quan_huyen ORDER BY ten_quan ASC")->fetchAll();
    $loai_phong = $conn->query("SELECT * FROM loai_phong")->fetchAll();
    
    // Lấy số dư hiện tại của user
    $stmt_bal = $conn->prepare("SELECT so_du FROM nguoidung WHERE id = :id");
    $stmt_bal->execute([':id' => $user_id]);
    $current_balance = $stmt_bal->fetchColumn();
} catch (PDOException $e) {
    die("Lỗi kết nối dữ liệu: " . $e->getMessage());
}

// 4. XỬ LÝ KHI NGƯỜI DÙNG SUBMIT FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // A. Kiểm tra số dư
    if ($current_balance < $PHI_DANG_TIN) {
        $error_msg = "Số dư không đủ. Phí đăng tin là " . number_format($PHI_DANG_TIN) . "đ. Số dư hiện tại: " . number_format($current_balance) . "đ. <br><a href='nap-tien.php' style='color: #007bff; font-weight: bold;'>Nạp tiền ngay</a>";
    } else {
        // B. Lấy dữ liệu từ Form
        $tieu_de = trim($_POST['tieu_de']);
        $loai_phong_id = $_POST['loai_phong_id'];
        $quan_huyen_id = $_POST['quan_huyen_id'];
        $dia_chi = trim($_POST['dia_chi']);
        $gia_thue = $_POST['gia_thue'];
        $dien_tich = $_POST['dien_tich'];
        $mo_ta = $_POST['mo_ta'];
        
        // Tiện ích (Checkbox)
        $wifi = isset($_POST['wifi']) ? 1 : 0;
        $may_lanh = isset($_POST['may_lanh']) ? 1 : 0;
        $tu_lanh = isset($_POST['tu_lanh']) ? 1 : 0;
        $wc_rieng = isset($_POST['wc_rieng']) ? 1 : 0;
        $may_giat = isset($_POST['may_giat']) ? 1 : 0;
        $gio_tu_do = isset($_POST['gio_tu_do']) ? 1 : 0;

        // C. Xử lý Upload Ảnh
        $image_files = [];
        if (isset($_FILES['anh_phong']) && count($_FILES['anh_phong']['name']) > 0) {
            $total_files = count($_FILES['anh_phong']['name']);
            for ($i = 0; $i < $total_files; $i++) {
                $file_name = $_FILES['anh_phong']['name'][$i];
                $tmp_name = $_FILES['anh_phong']['tmp_name'][$i];
                $error = $_FILES['anh_phong']['error'][$i];
                $file_size = $_FILES['anh_phong']['size'][$i];

                if ($error === 0) {
                    // Kiểm tra định dạng ảnh
                    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                    
                    if (in_array($ext, $allowed) && $file_size < 5000000) { // < 5MB
                        $new_name = time() . '_' . $i . '_' . uniqid() . '.' . $ext;
                        $dest = 'assets/uploads/' . $new_name;
                        if (move_uploaded_file($tmp_name, $dest)) {
                            $image_files[] = $new_name;
                        }
                    }
                }
            }
        }

        if (empty($image_files)) {
            $error_msg = "Vui lòng chọn ít nhất 1 hình ảnh hợp lệ!";
        } else {
            $anh_phong_json = json_encode($image_files);

            // D. Bắt đầu Giao Dịch (Transaction) - Trừ tiền & Thêm tin
            try {
                $conn->beginTransaction(); 

                // 1. Trừ tiền
                $sql_tru_tien = "UPDATE nguoidung SET so_du = so_du - :phi WHERE id = :uid";
                $stmt_tru = $conn->prepare($sql_tru_tien);
                $stmt_tru->execute([':phi' => $PHI_DANG_TIN, ':uid' => $user_id]);

                // 2. Insert tin
                $sql_insert = "INSERT INTO phongtro 
                        (tieu_de, chu_tro_id, loai_phong_id, quan_huyen_id, dia_chi_cu_the, gia_thue, dien_tich, mo_ta, anh_phong, wifi, may_lanh, tu_lanh, wc_rieng, may_giat, gio_tu_do, trang_thai) 
                        VALUES 
                        (:tieu_de, :uid, :lid, :qid, :dc, :gia, :dt, :mota, :anh, :wifi, :ml, :tl, :wc, :mg, :gtd, 'cho_duyet')";
                
                $stmt = $conn->prepare($sql_insert);
                $stmt->execute([
                    ':tieu_de' => $tieu_de, ':uid' => $user_id, ':lid' => $loai_phong_id, ':qid' => $quan_huyen_id,
                    ':dc' => $dia_chi, ':gia' => $gia_thue, ':dt' => $dien_tich, ':mota' => $mo_ta,
                    ':anh' => $anh_phong_json, ':wifi' => $wifi, ':ml' => $may_lanh, ':tl' => $tu_lanh,
                    ':wc' => $wc_rieng, ':mg' => $may_giat, ':gtd' => $gio_tu_do
                ]);

                $conn->commit(); // Hoàn tất giao dịch
                
                // Cập nhật lại số dư hiển thị ngay lập tức
                $current_balance -= $PHI_DANG_TIN;
                $success_msg = "Đăng tin thành công! Tài khoản đã bị trừ " . number_format($PHI_DANG_TIN) . "đ. Tin của bạn đang chờ duyệt.";
            
            } catch (Exception $e) {
                $conn->rollBack(); // Hoàn tác nếu lỗi
                $error_msg = "Lỗi hệ thống: " . $e->getMessage();
            }
        }
    }
}

include 'includes/header.php'; 
?>

<div class="container" style="margin-top: 30px; margin-bottom: 50px;">
    
    <div class="post-form-card">
        <h2 class="form-title">Đăng Tin Cho Thuê Mới</h2>

        <div class="fee-alert">
            <p>
                <i class="fa-solid fa-wallet"></i> Số dư của bạn: <strong><?php echo number_format($current_balance); ?> đ</strong>
            </p>
            <p>
                <i class="fa-solid fa-circle-info"></i> Phí đăng tin: <span style="color: #d35400;"><strong><?php echo number_format($PHI_DANG_TIN); ?> đ/tin</strong></span>
            </p>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-check-circle"></i> <?php echo $success_msg; ?>
                <br><br>
                <a href="index.php" class="btn-back">Về trang chủ</a>
            </div>
        <?php elseif ($error_msg): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($success_msg)): ?>
        <form action="" method="POST" enctype="multipart/form-data">
            
            <div class="form-section">
                <h3><i class="fa-solid fa-info-circle"></i> Thông tin cơ bản</h3>
                
                <div class="form-group">
                    <label>Tiêu đề tin đăng <span class="required">*</span></label>
                    <input type="text" name="tieu_de" class="form-control" required placeholder="VD: Phòng trọ giá rẻ, thoáng mát gần ĐH Bách Khoa...">
                </div>

                <div class="row-2-col">
                    <div class="form-group">
                        <label>Loại phòng</label>
                        <select name="loai_phong_id" class="form-control">
                            <?php foreach ($loai_phong as $lp): ?>
                                <option value="<?php echo $lp['id']; ?>"><?php echo $lp['ten_loai']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Khu vực (Quận/Huyện)</label>
                        <select name="quan_huyen_id" class="form-control">
                            <?php foreach ($quan_huyen as $qh): ?>
                                <option value="<?php echo $qh['id']; ?>"><?php echo $qh['ten_quan']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Địa chỉ chi tiết (Số nhà, tên đường) <span class="required">*</span></label>
                    <input type="text" name="dia_chi" class="form-control" required placeholder="VD: 54 Nguyễn Lương Bằng">
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fa-solid fa-list"></i> Thông tin chi tiết</h3>
                
                <div class="row-2-col">
                    <div class="form-group">
                        <label>Giá cho thuê (VNĐ) <span class="required">*</span></label>
                        <input type="number" name="gia_thue" class="form-control" required min="0" step="100000" placeholder="VD: 2500000">
                    </div>
                    <div class="form-group">
                        <label>Diện tích (m²) <span class="required">*</span></label>
                        <input type="number" name="dien_tich" class="form-control" required min="5" placeholder="VD: 25">
                    </div>
                </div>

                <div class="form-group">
                    <label>Tiện ích có sẵn</label>
                    <div class="amenities-grid">
                        <label class="checkbox-container"><input type="checkbox" name="wifi"> Wifi</label>
                        <label class="checkbox-container"><input type="checkbox" name="may_lanh"> Máy lạnh</label>
                        <label class="checkbox-container"><input type="checkbox" name="wc_rieng"> WC riêng</label>
                        <label class="checkbox-container"><input type="checkbox" name="tu_lanh"> Tủ lạnh</label>
                        <label class="checkbox-container"><input type="checkbox" name="may_giat"> Máy giặt</label>
                        <label class="checkbox-container"><input type="checkbox" name="gio_tu_do"> Giờ tự do</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Mô tả chi tiết</label>
                    <textarea name="mo_ta" class="form-control" rows="5" placeholder="Mô tả chi tiết về phòng trọ, môi trường xung quanh, điện nước..."></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fa-solid fa-images"></i> Hình ảnh</h3>
                <div class="form-group">
                    <label>Chọn ảnh (Nên chọn tối thiểu 3 ảnh rõ nét) <span class="required">*</span></label>
                    <div class="file-upload-wrapper">
                        <input type="file" name="anh_phong[]" class="form-control" multiple required accept="image/*">
                        <p class="note">Giữ phím <b>Ctrl</b> để chọn nhiều ảnh cùng lúc.</p>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit-post">
                ĐĂNG TIN NGAY (PHÍ 20.000Đ)
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>