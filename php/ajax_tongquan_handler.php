<?php
// FILE NÀY CHỈ XỬ LÝ CÁC TÁC VỤ CHO TAB TỔNG QUAN

ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_errors.log');

// 1. KẾT NỐI DATABASE
// (Sửa đường dẫn include an toàn hơn, giả sử db_connect.php nằm CÙNG thư mục 'php/')
$db_connect_path = __DIR__ . '/db_connect.php'; 
if (!file_exists($db_connect_path)) {
    // Thử tìm ở thư mục cha nếu không thấy (tùy cấu trúc của bạn)
    $db_connect_path = __DIR__ . '/../db_connect.php'; 
}
include $db_connect_path;


if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $errorMessage = 'Lỗi kết nối CSDL (ajax_tongquan_handler.php).';
    if (isset($conn) && $conn->connect_error) { $errorMessage .= ' Chi tiết: ' . $conn->connect_error; }
    error_log($errorMessage);
    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu. (Kiểm tra đường dẫn include db_connect.php)']);
    exit;
}
$conn->set_charset("utf8mb4");

// 2. LẤY ACTION
// === SỬA LỖI: Đổi $_GET thành $_POST ===
$action = isset($_POST['action']) ? $_POST['action'] : '';
$response = ['success' => false, 'message' => "Hành động không hợp lệ."];

// 3. XỬ LÝ ACTION
try {
    switch ($action) {
        
        case 'get_inventory_summary':
            $summary_data = [];
            $lot_data = [];
            $composition_data = [];
            $error_message = null;

            // === Query 1: Lấy dữ liệu Tổng Hợp (cho các Card) ===
            // (Đổi tên cột AS cho nhất quán, ví dụ: total_kg)
            $sql_summary = "SELECT 
                                ln.ten_loai_nhom,
                                lh.ten_loai_hang,
                                SUM(kh.khoi_luong_kg) AS total_kg
                            FROM kien_hang kh
                            JOIN loai_nhom ln ON kh.loai_nhom_id = ln.id
                            LEFT JOIN loai_hang lh ON kh.loai_hang_id = lh.id
                            WHERE kh.trang_thai = 'ton_kho'
                            GROUP BY ln.ten_loai_nhom, lh.ten_loai_hang
                            ORDER BY ln.ten_loai_nhom, lh.ten_loai_hang";
            
            $result_summary = $conn->query($sql_summary);
            if ($result_summary) {
                while ($row = $result_summary->fetch_assoc()) {
                    $summary_data[] = $row;
                }
                $result_summary->close();
            } else {
                $error_message = "Lỗi khi lấy dữ liệu tổng hợp: " . $conn->error;
                error_log($error_message);
            }

            // === Query 2: Lấy dữ liệu Chi Tiết Lot (cho Bảng) ===
            if ($error_message === null) {
                $sql_lots = "SELECT 
                                kh.lot_no,
                                ln.ten_loai_nhom,
                                lh.ten_loai_hang,
                                DATE_FORMAT(kh.ngay_san_xuat, '%d/%m/%Y') as ngay_san_xuat_f,
                                kh.ngay_san_xuat,
                                SUM(kh.khoi_luong_kg) AS total_kg
                            FROM kien_hang kh
                            JOIN loai_nhom ln ON kh.loai_nhom_id = ln.id
                            LEFT JOIN loai_hang lh ON kh.loai_hang_id = lh.id
                            WHERE kh.trang_thai = 'ton_kho'
                            GROUP BY kh.lot_no, ln.ten_loai_nhom, lh.ten_loai_hang, kh.ngay_san_xuat
                            ORDER BY ln.ten_loai_nhom, kh.ngay_san_xuat, kh.lot_no";
                
                $result_lots = $conn->query($sql_lots);
                if ($result_lots) {
                    while ($row_lot = $result_lots->fetch_assoc()) {
                        $lot_data[] = $row_lot;
                    }
                    $result_lots->close();
                } else {
                    $error_message = "Lỗi khi lấy danh sách lot: " . $conn->error;
                    error_log($error_message);
                }
            }

            // === Query 3: Lấy Thành Phần cho các Lot No tìm được ===
            if ($error_message === null && !empty($lot_data)) {
                $unique_lot_nos = array_unique(array_column($lot_data, 'lot_no'));
                
                if (!empty($unique_lot_nos)) {
                    $placeholders_lot = implode(',', array_fill(0, count($unique_lot_nos), '?'));
                    $types_lot = str_repeat('s', count($unique_lot_nos));
                    // Đảm bảo tên bảng 'ThanhPhanLot' là chính xác
                    $sql_thanhphan = "SELECT * FROM ThanhPhanLot WHERE lot_no IN ($placeholders_lot)"; 
                    
                    $stmt_thanhphan = $conn->prepare($sql_thanhphan);
                    if ($stmt_thanhphan) {
                        $stmt_thanhphan->bind_param($types_lot, ...$unique_lot_nos);
                        if ($stmt_thanhphan->execute()) {
                            $result_thanhphan = $stmt_thanhphan->get_result();
                            while ($row_tp = $result_thanhphan->fetch_assoc()) {
                                $composition_data[$row_tp['lot_no']] = $row_tp;
                            }
                            if ($result_thanhphan) $result_thanhphan->close();
                        } else {
                            error_log('AJAX [get_inventory_summary] execute error: ' . $stmt_thanhphan->error);
                        }
                        $stmt_thanhphan->close();
                    } else {
                        error_log('AJAX [get_inventory_summary] prepare error: ' . $conn->error);
                    }
                }
            }

            // === Trả về kết quả ===
            if ($error_message === null) {
                $response = [
                    'success' => true,
                    'summary_data' => $summary_data,
                    'lot_data' => $lot_data,
                    'composition_data' => $composition_data
                ];
            } else {
                $response['message'] = $error_message;
            }
            break;

        default:
            $response['message'] = "Hành động '" . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . "' không hợp lệ cho ajax_tongquan_handler.";
            break;
    }
} catch (Throwable $e) { 
    error_log("Lỗi Throwable trong ajax_tongquan_handler: " . $e->getMessage() . " tại " . $e->getFile() . ":" . $e->getLine());
    $response = ['success' => false, 'message' => 'Có lỗi hệ thống nghiêm trọng. Vui lòng kiểm tra log server.'];
}

// 4. GỬI PHẢN HỒI JSON
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
}

if (isset($conn) && $conn instanceof mysqli && $conn->connect_errno === 0) {
    $conn->close();
}
exit;
?>