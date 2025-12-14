<?php
// Nhúng Header Admin
include 'includes/header.php';

// --- XỬ LÝ DUYỆT / HỦY YÊU CẦU (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $req_id = $_POST['req_id'];
    $action = $_POST['action']; // 'duyet' hoặc 'huy'

    // Lấy thông tin yêu cầu để kiểm tra
    $stmt = $conn->prepare("SELECT * FROM yeu_cau_nap_tien WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $req_id]);
    $request = $stmt->fetch();

    if ($request && $request['trang_thai'] == 'cho_duyet') {
        
        // TRƯỜNG HỢP 1: DUYỆT ĐƠN (Cộng tiền)
        if ($action == 'duyet') {
            try {
                $conn->beginTransaction(); // Bắt đầu giao dịch an toàn

                // 1. Cộng tiền vào ví User
                $sql_cong = "UPDATE nguoidung SET so_du = so_du + :tien WHERE id = :uid";
                $stmt_cong = $conn->prepare($sql_cong);
                $stmt_cong->execute([':tien' => $request['so_tien'], ':uid' => $request['nguoi_dung_id']]);

                // 2. Cập nhật trạng thái đơn nạp thành 'thanh_cong'
                $sql_update = "UPDATE yeu_cau_nap_tien SET trang_thai = 'thanh_cong' WHERE id = :id";
                $conn->prepare($sql_update)->execute([':id' => $req_id]);

                // 3. Ghi vào lịch sử giao dịch (Để user xem được trong trang cá nhân)
                $sql_log = "INSERT INTO lich_su_giao_dich (nguoi_dung_id, loai_giao_dich, so_tien, noi_dung) 
                            VALUES (:uid, 'nap_tien', :tien, 'Nạp tiền vào tài khoản')";
                $stmt_log = $conn->prepare($sql_log);
                $stmt_log->execute([
                    ':uid' => $request['nguoi_dung_id'],
                    ':tien' => $request['so_tien']
                ]);

                $conn->commit(); // Xác nhận thành công
                echo "<script>alert('Đã duyệt thành công! Cộng ".number_format($request['so_tien'])."đ cho user.');</script>";
            
            } catch (Exception $e) {
                $conn->rollBack(); // Hoàn tác nếu lỗi
                echo "<script>alert('Lỗi hệ thống: " . $e->getMessage() . "');</script>";
            }
        } 
        
        // TRƯỜNG HỢP 2: HỦY ĐƠN
        elseif ($action == 'huy') {
            $stmt_huy = $conn->prepare("UPDATE yeu_cau_nap_tien SET trang_thai = 'da_huy' WHERE id = :id");
            $stmt_huy->execute([':id' => $req_id]);
            echo "<script>alert('Đã hủy yêu cầu nạp tiền này.');</script>";
        }
    }
}

// --- LẤY DỮ LIỆU & BỘ LỌC ---
$status = isset($_GET['status']) ? $_GET['status'] : 'cho_duyet'; // Mặc định hiện chờ duyệt

$sql = "SELECT y.*, u.ho_ten, u.email 
        FROM yeu_cau_nap_tien y 
        JOIN nguoidung u ON y.nguoi_dung_id = u.id ";

if ($status != 'all') {
    $sql .= " WHERE y.trang_thai = :st ";
}

$sql .= " ORDER BY y.id DESC";

$stmt = $conn->prepare($sql);
if ($status != 'all') {
    $stmt->execute([':st' => $status]);
} else {
    $stmt->execute();
}
$requests = $stmt->fetchAll();
?>

<div class="table-section">
    <div class="section-head" style="display: flex; justify-content: space-between; align-items: center;">
        <h3>Quản Lý Nạp Tiền</h3>
        
        <div class="filter-tabs">
            <a href="quan-ly-nap.php?status=cho_duyet" class="tab-btn <?php echo ($status=='cho_duyet')?'active':''; ?>">
                Chờ xử lý
            </a>
            <a href="quan-ly-nap.php?status=thanh_cong" class="tab-btn <?php echo ($status=='thanh_cong')?'active':''; ?>">
                Thành công
            </a>
            <a href="quan-ly-nap.php?status=da_huy" class="tab-btn <?php echo ($status=='da_huy')?'active':''; ?>">
                Đã hủy
            </a>
            <a href="quan-ly-nap.php?status=all" class="tab-btn <?php echo ($status=='all')?'active':''; ?>">
                Tất cả
            </a>
        </div>
    </div>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Người nạp</th>
                <th>Số tiền</th>
                <th>Ảnh chứng minh</th>
                <th>Ghi chú</th>
                <th>Ngày tạo</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($requests) > 0): ?>
                <?php foreach ($requests as $item): ?>
                    <tr>
                        <td>#<?php echo $item['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($item['ho_ten']); ?></strong><br>
                            <small style="color: #777;"><?php echo $item['email']; ?></small>
                        </td>
                        <td style="color: #27ae60; font-weight: bold; font-size: 15px;">
                            + <?php echo number_format($item['so_tien']); ?> đ
                        </td>
                        <td>
                            <a href="../assets/uploads/<?php echo $item['anh_chung_minh']; ?>" target="_blank" title="Bấm để xem ảnh lớn">
                                <img src="../assets/uploads/<?php echo $item['anh_chung_minh']; ?>" class="table-thumb" style="width: 60px; height: auto;">
                            </a>
                        </td>
                        <td style="font-size: 13px; color: #555; max-width: 200px;">
                            <?php echo !empty($item['ghi_chu']) ? $item['ghi_chu'] : '---'; ?>
                        </td>
                        <td><?php echo date("d/m/Y H:i", strtotime($item['ngay_tao'])); ?></td>
                        <td>
                            <?php if ($item['trang_thai'] == 'cho_duyet'): ?>
                                <span class="badge" style="background: #f1c40f; color: #000;">Chờ xử lý</span>
                            <?php elseif ($item['trang_thai'] == 'thanh_cong'): ?>
                                <span class="badge" style="background: #27ae60;">Thành công</span>
                            <?php else: ?>
                                <span class="badge" style="background: #e74c3c;">Đã hủy</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item['trang_thai'] == 'cho_duyet'): ?>
                                <div style="display: flex; gap: 5px;">
                                    <form method="POST">
                                        <input type="hidden" name="req_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="action" value="duyet">
                                        <button type="submit" class="btn-action btn-ok" onclick="return confirm('Xác nhận đã nhận được tiền và muốn DUYỆT?')" title="Duyệt đơn">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    </form>

                                    <form method="POST">
                                        <input type="hidden" name="req_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="action" value="huy">
                                        <button type="submit" class="btn-action btn-del" onclick="return confirm('Xác nhận HỦY yêu cầu này?')" title="Hủy đơn">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span style="color: #999; font-size: 12px; font-style: italic;">Đã hoàn tất</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" style="text-align: center; padding: 20px;">Không có yêu cầu nào.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>