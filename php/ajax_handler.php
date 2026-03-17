<?php

// 1. KHAI BÁO NAMESPACE VÀ AUTOLOAD (ĐẦU FILE)
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
// use PhpOffice\PhpSpreadsheet\Style\Font;
// use PhpOffice\PhpSpreadsheet\Style\Alignment;

// !!! KIỂM TRA KỸ ĐƯỜNG DẪN NÀY !!!
require_once __DIR__ . '/../vendor/autoload.php';

// 2. THIẾT LẬP XỬ LÝ LỖI VÀ MÔI TRƯỜNG
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_errors.log');

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    error_log("PHP Error: [$errno] $errstr in $errfile on line $errline");
    return true;
});

// 3. KẾT NỐI DATABASE
include 'db_connect.php';

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $errorMessage = 'Lỗi kết nối CSDL nghiêm trọng.';
    if (isset($conn) && $conn->connect_error) { $errorMessage .= ' Chi tiết: ' . $conn->connect_error; }
    error_log($errorMessage . " (ajax_handler.php)");
    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.']);
    exit;
}
$conn->set_charset("utf8mb4");

// 4. LẤY ACTION VÀ KHỞI TẠO RESPONSE MẶC ĐỊNH
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
$response = ['success' => false, 'message' => "Hành động '" . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . "' không hợp lệ."];
// error_log("AJAX Action: '" . $action . "' POST: " . print_r($_POST, true) . " GET: " . print_r($_GET, true));


// --- CÁC HÀM HỖ TRỢ ---
function getLoaiNhomOptions($db_conn) {
    $options = [];
    $sql = "SELECT id, ten_loai_nhom FROM loai_nhom ORDER BY ten_loai_nhom ASC";
    $result = $db_conn->query($sql);
    if ($result) {
        while($row = $result->fetch_assoc()) { $options[] = $row; }
        $result->close();
    } else { error_log("Lỗi SQL [getLoaiNhomOptions]: " . $db_conn->error); }
    return $options;
}

function getLoaiHangOptions($db_conn) {
    $options = [];
    $sql = "SELECT id, ten_loai_hang FROM loai_hang ORDER BY ten_loai_hang ASC";
    $result = $db_conn->query($sql);
    if ($result) {
        while($row = $result->fetch_assoc()) { $options[] = $row; }
        $result->close();
    } else { error_log("Lỗi SQL [getLoaiHangOptions]: " . $db_conn->error); }
    return $options;
}
// --- KẾT THÚC HÀM HỖ TRỢ ---


// --- XỬ LÝ CÁC ACTION ---
try {
    switch ($action) {
        case 'load_initial_data':
            // ... (case 'load_initial_data' của bạn không đổi) ...
            $kho_id = isset($_GET['kho_id']) ? intval($_GET['kho_id']) : 1;
            $kien_hang_data = []; $latest_lot_no = null; $current_message = null;
            $sql_get_latest_lot = "SELECT lot_no FROM kien_hang ORDER BY ngay_san_xuat DESC, id DESC LIMIT 1";
            $result_latest_lot = $conn->query($sql_get_latest_lot);
            if ($result_latest_lot) {
                if ($result_latest_lot->num_rows > 0) { $latest_lot_no = $result_latest_lot->fetch_assoc()['lot_no']; }
                $result_latest_lot->close();
            } else { $current_message = 'Lỗi tìm lot_no mới nhất: ' . $conn->error; error_log($current_message); }
            
            if ($latest_lot_no !== null && $current_message === null) {
                 $sql_kien_hang = "SELECT kh.id, kh.lot_no, ln.ten_loai_nhom, lh.ten_loai_hang, kh.kien_so, kh.khoi_luong_kg,
                                       DATE_FORMAT(kh.ngay_san_xuat, '%d/%m/%Y') as ngay_san_xuat_f, kh.trang_thai,
                                       COALESCE(stc.so_thoi_mac_dinh, 'N/A') AS so_thoi,kh.ghi_chu
                                FROM kien_hang kh
                                JOIN loai_nhom ln ON kh.loai_nhom_id = ln.id
                                LEFT JOIN loai_hang lh ON kh.loai_hang_id = lh.id
                                LEFT JOIN so_thoi_config stc ON kh.loai_nhom_id = stc.loai_nhom_id AND kh.loai_hang_id = stc.loai_hang_id
                                WHERE kh.lot_no = ? AND kh.trang_thai = 'ton_kho' AND kh.kho_id = ? ORDER BY kh.kien_so ASC";
                $stmt_kien_hang = $conn->prepare($sql_kien_hang);
                if ($stmt_kien_hang) {
                    $stmt_kien_hang->bind_param("si", $latest_lot_no, $kho_id);
                    if ($stmt_kien_hang->execute()) {
                        $result_kien_hang = $stmt_kien_hang->get_result();
                        while($row_kh = $result_kien_hang->fetch_assoc()) { $kien_hang_data[] = $row_kh; }
                        if ($result_kien_hang) $result_kien_hang->close();
                    } else { $current_message = 'Lỗi thực thi lấy kiện hàng: ' . $stmt_kien_hang->error; error_log($current_message); }
                    $stmt_kien_hang->close();
                } else { $current_message = 'Lỗi chuẩn bị SQL lấy kiện hàng: ' . $conn->error; error_log($current_message); }
            }
            $response = [
                'success' => $current_message === null,
                'loai_nhom_options' => getLoaiNhomOptions($conn),
                'loai_hang_options' => getLoaiHangOptions($conn),
                'kien_hang' => $kien_hang_data,
                'message' => $current_message
            ];
            break;
        case 'luu_thanh_phan':
            $lotno = isset($_POST['lotno']) ? trim($_POST['lotno']) : '';
            $thanh_phan_json = isset($_POST['thanh_phan']) ? $_POST['thanh_phan'] : '{}';
            $thanh_phan = json_decode($thanh_phan_json, true);

            if (empty($lotno)) {
                $response['message'] = 'Lot No không được để trống.';
                break;
            }
            if (!is_array($thanh_phan)) {
                 $response['message'] = 'Dữ liệu thành phần hóa học không hợp lệ.';
                 break;
            }
            
            $conn->begin_transaction();
            try {
                // Sửa logic bảo toàn dữ liệu TP == 0
                $si = (float)($thanh_phan['Si'] ?? 0); $fe = (float)($thanh_phan['Fe'] ?? 0);
                $cu = (float)($thanh_phan['Cu'] ?? 0); $mn = (float)($thanh_phan['Mn'] ?? 0);
                $mg = (float)($thanh_phan['Mg'] ?? 0); $zn = (float)($thanh_phan['Zn'] ?? 0);
                $pb = (float)($thanh_phan['Pb'] ?? 0); $ni = (float)($thanh_phan['Ni'] ?? 0);
                $cr = (float)($thanh_phan['Cr'] ?? 0); $sn = (float)($thanh_phan['Sn'] ?? 0);
                $ti = (float)($thanh_phan['Ti'] ?? 0); $cd = (float)($thanh_phan['Cd'] ?? 0);
                $ca = (float)($thanh_phan['Ca'] ?? 0);
                
                $sum_tp = $si + $fe + $cu + $mn + $mg + $zn + $pb + $ni + $cr + $sn + $ti + $cd + $ca;
                
                if ($sum_tp > 0) {
                    $sql_thanh_phan = "INSERT INTO thanhphanlot ( 
                                        lot_no, Si, Fe, Cu, Mn, Mg, Zn, Pb, Ni, Cr, Sn, Ti, Cd, Ca
                                    ) VALUES (
                                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                                    ) ON DUPLICATE KEY UPDATE
                                        Si = VALUES(Si), Fe = VALUES(Fe), Cu = VALUES(Cu), Mn = VALUES(Mn),
                                        Mg = VALUES(Mg), Zn = VALUES(Zn), Pb = VALUES(Pb), Ni = VALUES(Ni),
                                        Cr = VALUES(Cr), Sn = VALUES(Sn), Ti = VALUES(Ti), Cd = VALUES(Cd), Ca = VALUES(Ca)";
                } else {
                    $sql_thanh_phan = "INSERT IGNORE INTO thanhphanlot ( 
                                        lot_no, Si, Fe, Cu, Mn, Mg, Zn, Pb, Ni, Cr, Sn, Ti, Cd, Ca
                                    ) VALUES (
                                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                                    )";
                }
                
                $stmt_thanh_phan = $conn->prepare($sql_thanh_phan);
                if ($stmt_thanh_phan === false) throw new Exception("Lỗi CSDL (TP_ONLY_01): " . $conn->error);
                
                $stmt_thanh_phan->bind_param("sddddddddddddd", 
                    $lotno, $si, $fe, $cu, $mn, $mg, $zn, $pb, $ni, $cr, $sn, $ti, $cd, $ca
                );
                
                if (!$stmt_thanh_phan->execute()) {
                    throw new Exception("Lỗi lưu thành phần hóa học: " . $stmt_thanh_phan->error);
                }
                $stmt_thanh_phan->close();
                
                $conn->commit();
                $response = ['success' => true, 'message' => 'Đã cập nhật thành phần hóa học cho Lot ' . htmlspecialchars($lotno) . '.'];

            } catch (Exception $e) { 
                $conn->rollback(); 
                $response['message'] = 'Lỗi lưu thành phần: ' . $e->getMessage(); 
                error_log('Lỗi Exception [luu_thanh_phan]: '.$response['message']); 
            }
            break;

        case 'them_loai_nhom':
            // ... (case 'them_loai_nhom' của bạn không đổi) ...
            $ten_moi = isset($_POST['ten_loai_nhom']) ? trim($_POST['ten_loai_nhom']) : '';
            if (empty($ten_moi)) { $response['message'] = 'Tên loại nhôm không được để trống.'; } 
            elseif (mb_strlen($ten_moi) > 255) { $response['message'] = 'Tên loại nhôm quá dài.'; } 
            else {
                $stmt_check = $conn->prepare("SELECT id FROM loai_nhom WHERE ten_loai_nhom = ?");
                if (!$stmt_check) { $response['message'] = 'Lỗi CSDL (LN01).'; error_log('LN01 Check: ' . $conn->error); } 
                else {
                    $stmt_check->bind_param("s", $ten_moi); $stmt_check->execute(); $result_check = $stmt_check->get_result();
                    if ($result_check->num_rows > 0) { $response['message'] = 'Loại nhôm này đã tồn tại.'; } 
                    else {
                        $stmt_insert = $conn->prepare("INSERT INTO loai_nhom (ten_loai_nhom) VALUES (?)");
                        if (!$stmt_insert) { $response['message'] = 'Lỗi CSDL (LN02).'; error_log('LN02 Insert: ' . $conn->error); } 
                        else {
                            $stmt_insert->bind_param("s", $ten_moi);
                            if ($stmt_insert->execute()) {
                                $new_id = $stmt_insert->insert_id;
                                $response = ['success' => true, 'message' => 'Thêm loại nhôm mới thành công!', 'new_loai_nhom' => ['id' => $new_id, 'ten_loai_nhom' => $ten_moi], 'loai_nhom_options' => getLoaiNhomOptions($conn)];
                            } else { $response['message'] = 'Lỗi khi thêm loại nhôm.'; error_log('LN02 Exec: ' . $stmt_insert->error); }
                            $stmt_insert->close();
                        }
                    }
                    if ($result_check) $result_check->close(); $stmt_check->close();
                }
            }
            break;

        case 'them_loai_hang':
            // ... (case 'them_loai_hang' của bạn không đổi) ...
            $ten_moi = isset($_POST['ten_loai_hang']) ? trim($_POST['ten_loai_hang']) : '';
            if (empty($ten_moi)) { $response['message'] = 'Tên loại hàng không được để trống.'; } 
            elseif (mb_strlen($ten_moi) > 255) { $response['message'] = 'Tên loại hàng quá dài.'; } 
            else {
                $stmt_check = $conn->prepare("SELECT id FROM loai_hang WHERE ten_loai_hang = ?");
                if (!$stmt_check) { $response['message'] = 'Lỗi CSDL (LH01).'; error_log('LH01 Check: ' . $conn->error); } 
                else {
                    $stmt_check->bind_param("s", $ten_moi); $stmt_check->execute(); $result_check = $stmt_check->get_result();
                    if ($result_check->num_rows > 0) { $response['message'] = 'Loại hàng này đã tồn tại.'; } 
                    else {
                        $stmt_insert = $conn->prepare("INSERT INTO loai_hang (ten_loai_hang) VALUES (?)");
                        if (!$stmt_insert) { $response['message'] = 'Lỗi CSDL (LH02).'; error_log('LH02 Insert: ' . $conn->error); } 
                        else {
                            $stmt_insert->bind_param("s", $ten_moi);
                            if ($stmt_insert->execute()) {
                                $new_id = $stmt_insert->insert_id;
                                $response = ['success' => true, 'message' => 'Thêm loại hàng mới thành công!', 'new_loai_hang' => ['id' => $new_id, 'ten_loai_hang' => $ten_moi], 'loai_hang_options' => getLoaiHangOptions($conn)];
                            } else { $response['message'] = 'Lỗi khi thêm loại hàng.'; error_log('LH02 Exec: ' . $stmt_insert->error); }
                            $stmt_insert->close();
                        }
                    }
                    if ($result_check) $result_check->close(); $stmt_check->close();
                }
            }
            break;

        case 'tim_kiem_kien_hang':
            // ... (case 'tim_kiem_kien_hang' của bạn không đổi) ...
            $kho_id = isset($_POST['kho_id']) ? intval($_POST['kho_id']) : 1;
            $lotno_str = isset($_POST['lotno']) ? trim($_POST['lotno']) : '';
            $loai_nhom_id_input = isset($_POST['loai_nhom_id']) ? $_POST['loai_nhom_id'] : '';
            $loai_hang_id_input = isset($_POST['loai_hang_id']) ? $_POST['loai_hang_id'] : '';
            $nsx_input = isset($_POST['nsx']) ? trim($_POST['nsx']) : '';
            $trang_thai_tk = isset($_POST['trang_thai']) ? trim($_POST['trang_thai']) : 'tat_ca';
            $kien_hang_data_search = [];
            
            $sql = "SELECT kh.id, kh.lot_no, ln.ten_loai_nhom, lh.ten_loai_hang, kh.kien_so, kh.khoi_luong_kg,
                           DATE_FORMAT(kh.ngay_san_xuat, '%d/%m/%Y') as ngay_san_xuat_f, kh.trang_thai,
                           COALESCE(stc.so_thoi_mac_dinh, 'N/A') AS so_thoi,kh.ghi_chu
                    FROM kien_hang kh
                    JOIN loai_nhom ln ON kh.loai_nhom_id = ln.id
                    LEFT JOIN loai_hang lh ON kh.loai_hang_id = lh.id
                    LEFT JOIN so_thoi_config stc ON kh.loai_nhom_id = stc.loai_nhom_id AND kh.loai_hang_id = stc.loai_hang_id
                    WHERE kh.kho_id = ?";
            $params = [$kho_id]; $types = "i";
            if (!empty($lotno_str)) {
                $lotnos = array_filter(array_map('trim', explode(',', $lotno_str)));
                if (!empty($lotnos)) { $placeholders_lot = implode(',', array_fill(0, count($lotnos), '?')); $sql .= " AND kh.lot_no IN ($placeholders_lot)"; foreach ($lotnos as $ln_item) { $params[] = $ln_item; $types .= "s"; } }
            }
            if (!empty($loai_nhom_id_input) && filter_var($loai_nhom_id_input, FILTER_VALIDATE_INT)) { $loai_nhom_id_val = (int)$loai_nhom_id_input; $sql .= " AND kh.loai_nhom_id = ?"; $params[] = $loai_nhom_id_val; $types .= "i"; }
            if (!empty($loai_hang_id_input) && filter_var($loai_hang_id_input, FILTER_VALIDATE_INT)) { $loai_hang_id_val = (int)$loai_hang_id_input; $sql .= " AND kh.loai_hang_id = ?"; $params[] = $loai_hang_id_val; $types .= "i"; }
            if (!empty($nsx_input) && preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $nsx_input)) { $sql .= " AND kh.ngay_san_xuat = ?"; $params[] = $nsx_input; $types .= "s"; }
            if ($trang_thai_tk !== 'tat_ca' && in_array($trang_thai_tk, ['ton_kho', 'da_xuat'])) { $sql .= " AND kh.trang_thai = ?"; $params[] = $trang_thai_tk; $types .= "s"; }
            $sql .= " ORDER BY kh.ngay_san_xuat ASC, kh.lot_no ASC, kh.kien_so ASC";
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) { $response['message'] = 'Lỗi CSDL (TK01).'; error_log('TK01 Prepare: ' . $conn->error . ' SQL: ' . $sql); } 
            else {
                if (!empty($params)) { $stmt->bind_param($types, ...$params); }
                if ($stmt->execute()) {
                    $result = $stmt->get_result(); while($row = $result->fetch_assoc()) { $kien_hang_data_search[] = $row; }
                    $response = ['success' => true, 'kien_hang' => $kien_hang_data_search];
                    if ($result) $result->close();
                } else { $response['message'] = 'Lỗi thực thi tìm kiếm.'; error_log('TK01 Exec: ' . $stmt->error); }
                $stmt->close();
            }
            break;

        // ===== CASE NHAP_HANG ĐÃ ĐƯỢC CẬP NHẬT =====
        case 'nhap_hang':
        // Code xử lý nhập hàng (Đã cập nhật để hỗ trợ Kiện Lẻ override)
        $kho_id = isset($_POST['kho_id']) ? intval($_POST['kho_id']) : 1;
        $lotno = isset($_POST['lotno']) ? trim($_POST['lotno']) : '';
        $loai_nhom_id_input = isset($_POST['loai_nhom_id']) ? $_POST['loai_nhom_id'] : '';
        $loai_hang_id_input = isset($_POST['loai_hang_id']) ? $_POST['loai_hang_id'] : '';
        $nsx_input = isset($_POST['nsx']) ? trim($_POST['nsx']) : '';
        $ds_khoi_luong = json_decode($_POST['ds_khoi_luong'] ?? '[]', true);
        $thanh_phan = json_decode($_POST['thanh_phan'] ?? '{}', true);
        $po_number = isset($_POST['po_number']) ? trim($_POST['po_number']) : '';

        // Validate cơ bản
        if (empty($lotno) || empty($loai_nhom_id_input) || empty($loai_hang_id_input) || empty($nsx_input)) {
            $response['message'] = 'Thiếu thông tin bắt buộc (Lot, Loại, NSX).';
            break;
        }

        $conn->begin_transaction();
        try {
            // 1. Lưu Thành Phần (Bảo tồn TP_OLD nếu nhập toàn 0)
            $si = (float)($thanh_phan['Si']??0); $fe = (float)($thanh_phan['Fe']??0); $cu = (float)($thanh_phan['Cu']??0); 
            $mn = (float)($thanh_phan['Mn']??0); $mg = (float)($thanh_phan['Mg']??0); $zn = (float)($thanh_phan['Zn']??0);
            $pb = (float)($thanh_phan['Pb']??0); $ni = (float)($thanh_phan['Ni']??0); $cr = (float)($thanh_phan['Cr']??0);
            $sn = (float)($thanh_phan['Sn']??0); $ti = (float)($thanh_phan['Ti']??0); $cd = (float)($thanh_phan['Cd']??0);
            $ca = (float)($thanh_phan['Ca']??0);
            $sum_tp = $si + $fe + $cu + $mn + $mg + $zn + $pb + $ni + $cr + $sn + $ti + $cd + $ca;
            
            if ($sum_tp > 0) {
                $sql_tp = "INSERT INTO thanhphanlot (lot_no, Si, Fe, Cu, Mn, Mg, Zn, Pb, Ni, Cr, Sn, Ti, Cd, Ca) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE Si=VALUES(Si), Fe=VALUES(Fe), Cu=VALUES(Cu), Mn=VALUES(Mn), Mg=VALUES(Mg), Zn=VALUES(Zn), Pb=VALUES(Pb), Ni=VALUES(Ni), Cr=VALUES(Cr), Sn=VALUES(Sn), Ti=VALUES(Ti), Cd=VALUES(Cd), Ca=VALUES(Ca)";
            } else {
                $sql_tp = "INSERT IGNORE INTO thanhphanlot (lot_no, Si, Fe, Cu, Mn, Mg, Zn, Pb, Ni, Cr, Sn, Ti, Cd, Ca) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            }
            $stmt_tp = $conn->prepare($sql_tp);
            $stmt_tp->bind_param("sddddddddddddd", $lotno, $si, $fe, $cu, $mn, $mg, $zn, $pb, $ni, $cr, $sn, $ti, $cd, $ca);
            $stmt_tp->execute();
            $stmt_tp->close();

            // 1.5 Lưu PO Number
            if (!empty($po_number)) {
                $sql_po = "INSERT INTO POnumber (lot_no, po_number) VALUES (?, ?) ON DUPLICATE KEY UPDATE po_number = VALUES(po_number)";
                $stmt_po = $conn->prepare($sql_po);
                $stmt_po->bind_param("ss", $lotno, $po_number);
                $stmt_po->execute();
                $stmt_po->close();
            }

            // 2. Lưu Kiện Hàng (Xử lý Override cho Kiện Lẻ)
            $stmt_insert = $conn->prepare("INSERT INTO kien_hang (lot_no, loai_nhom_id, loai_hang_id, kien_so, khoi_luong_kg, ngay_san_xuat, trang_thai, ngay_nhap_kho, kho_id) VALUES (?, ?, ?, ?, ?, ?, 'ton_kho', NOW(), ?)");
            
            $inserted_count = 0;
            foreach ($ds_khoi_luong as $kien) {
                // Kiểm tra xem có override không (cho Kiện Lẻ)
                $final_lot_no = !empty($kien['override_lot_no']) ? $kien['override_lot_no'] : $lotno;
                $final_kien_so = !empty($kien['override_kien_so']) ? $kien['override_kien_so'] : $kien['kien_so'];
                $khoi_luong = (float)$kien['khoi_luong'];

                $stmt_insert->bind_param("siisdsi", $final_lot_no, $loai_nhom_id_input, $loai_hang_id_input, $final_kien_so, $khoi_luong, $nsx_input, $kho_id);
                
                if (!$stmt_insert->execute()) {
                    // Nếu trùng (Duplicate) thì bỏ qua hoặc báo lỗi tùy bạn. Ở đây tôi throw lỗi để rollback cho an toàn.
                    throw new Exception("Lỗi nhập kiện '{$final_kien_so}' (Lot: {$final_lot_no}): " . $stmt_insert->error);
                }
                $inserted_count++;
            }
            $stmt_insert->close();

            $conn->commit();
            $response = ['success' => true, 'message' => "Đã nhập {$inserted_count} kiện hàng."];

        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = "Lỗi: " . $e->getMessage();
        }
        break;

    case 'ghep_kien':
        // NHẬN DỮ LIỆU NHƯ NHẬP HÀNG
        $old_ids_json = $_POST['old_ids'] ?? '[]';
        $old_ids = json_decode($old_ids_json, true);
        
        // (Dữ liệu kiện mới)
        $kho_id = isset($_POST['kho_id']) ? intval($_POST['kho_id']) : 1;
        $lotno = isset($_POST['lotno']) ? trim($_POST['lotno']) : '';
        $loai_nhom_id_input = isset($_POST['loai_nhom_id']) ? $_POST['loai_nhom_id'] : '';
        $loai_hang_id_input = isset($_POST['loai_hang_id']) ? $_POST['loai_hang_id'] : '';
        $nsx_input = isset($_POST['nsx']) ? trim($_POST['nsx']) : '';
        $ds_khoi_luong = json_decode($_POST['ds_khoi_luong'] ?? '[]', true);
        $thanh_phan = json_decode($_POST['thanh_phan'] ?? '{}', true);
        $po_number = isset($_POST['po_number']) ? trim($_POST['po_number']) : '';

        // Validate
        if (empty($old_ids)) { $response['message'] = 'Không có kiện hàng cũ nào được chọn để ghép.'; break; }
        if (empty($lotno) || empty($ds_khoi_luong)) { $response['message'] = 'Thiếu thông tin cho lô hàng mới.'; break; }

        $conn->begin_transaction();
        try {
            // 1. XÓA KIỆN CŨ
            $ids_clean = array_map('intval', $old_ids);
            $in_str = implode(',', $ids_clean);
            $sql_del = "DELETE FROM kien_hang WHERE id IN ($in_str)";
            if (!$conn->query($sql_del)) throw new Exception("Lỗi xóa kiện cũ: " . $conn->error);

            // 2. TẠO KIỆN MỚI
            // Lưu Thành Phần (Bảo tồn TP_OLD nếu nhập toàn 0)
            $si = (float)($thanh_phan['Si']??0); $fe = (float)($thanh_phan['Fe']??0); $cu = (float)($thanh_phan['Cu']??0); 
            $mn = (float)($thanh_phan['Mn']??0); $mg = (float)($thanh_phan['Mg']??0); $zn = (float)($thanh_phan['Zn']??0);
            $pb = (float)($thanh_phan['Pb']??0); $ni = (float)($thanh_phan['Ni']??0); $cr = (float)($thanh_phan['Cr']??0);
            $sn = (float)($thanh_phan['Sn']??0); $ti = (float)($thanh_phan['Ti']??0); $cd = (float)($thanh_phan['Cd']??0);
            $ca = (float)($thanh_phan['Ca']??0);
            $sum_tp = $si + $fe + $cu + $mn + $mg + $zn + $pb + $ni + $cr + $sn + $ti + $cd + $ca;
            
            if ($sum_tp > 0) {
                $sql_tp = "INSERT INTO thanhphanlot (lot_no, Si, Fe, Cu, Mn, Mg, Zn, Pb, Ni, Cr, Sn, Ti, Cd, Ca) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE Si=VALUES(Si), Fe=VALUES(Fe), Cu=VALUES(Cu), Mn=VALUES(Mn), Mg=VALUES(Mg), Zn=VALUES(Zn), Pb=VALUES(Pb), Ni=VALUES(Ni), Cr=VALUES(Cr), Sn=VALUES(Sn), Ti=VALUES(Ti), Cd=VALUES(Cd), Ca=VALUES(Ca)";
            } else {
                $sql_tp = "INSERT IGNORE INTO thanhphanlot (lot_no, Si, Fe, Cu, Mn, Mg, Zn, Pb, Ni, Cr, Sn, Ti, Cd, Ca) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            }
            $stmt_tp = $conn->prepare($sql_tp);
            $stmt_tp->bind_param("sddddddddddddd", $lotno, $si, $fe, $cu, $mn, $mg, $zn, $pb, $ni, $cr, $sn, $ti, $cd, $ca);
            $stmt_tp->execute();
            $stmt_tp->close();

            // Lưu PO Number
            if (!empty($po_number)) {
                $sql_po = "INSERT INTO POnumber (lot_no, po_number) VALUES (?, ?) ON DUPLICATE KEY UPDATE po_number = VALUES(po_number)";
                $stmt_po = $conn->prepare($sql_po);
                $stmt_po->bind_param("ss", $lotno, $po_number);
                $stmt_po->execute();
                $stmt_po->close();
            }

            // Insert Kiện
            $stmt_insert = $conn->prepare("INSERT INTO kien_hang (lot_no, loai_nhom_id, loai_hang_id, kien_so, khoi_luong_kg, ngay_san_xuat, trang_thai, ngay_nhap_kho, kho_id) VALUES (?, ?, ?, ?, ?, ?, 'ton_kho', NOW(), ?)");
            foreach ($ds_khoi_luong as $kien) {
                // Logic Override Kiện Lẻ
                $final_lot_no = !empty($kien['override_lot_no']) ? $kien['override_lot_no'] : $lotno;
                $final_kien_so = !empty($kien['override_kien_so']) ? $kien['override_kien_so'] : $kien['kien_so'];
                $khoi_luong = (float)$kien['khoi_luong'];

                $stmt_insert->bind_param("siisdsi", $final_lot_no, $loai_nhom_id_input, $loai_hang_id_input, $final_kien_so, $khoi_luong, $nsx_input, $kho_id);
                if (!$stmt_insert->execute()) throw new Exception("Lỗi tạo kiện mới: " . $stmt_insert->error);
            }
            $stmt_insert->close();

            $conn->commit();
            $response = ['success' => true, 'message' => "Đã ghép thành công! (Xóa " . count($ids_clean) . " kiện cũ, Tạo lô mới '$lotno')"];

        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = "Lỗi ghép kiện: " . $e->getMessage();
        }
        break;
        // ===============================================

        case 'chuyen_kho':
            $kien_ids_json = isset($_POST['kien_ids']) ? $_POST['kien_ids'] : '[]';
            $kien_ids = json_decode($kien_ids_json, true);
            $current_kho = isset($_POST['current_kho']) ? intval($_POST['current_kho']) : 1;
            
            if (empty($kien_ids) || !is_array($kien_ids) || !array_filter($kien_ids, 'is_numeric')) { 
                $response['message'] = 'ID kiện hàng không hợp lệ.'; 
                break;
            }
            
            $kien_ids_sanitized = array_map('intval', $kien_ids);
            if (empty($kien_ids_sanitized)) {
                $response['message'] = 'Không có ID kiện hợp lệ.'; 
                break;
            }
            
            $placeholders = implode(',', array_fill(0, count($kien_ids_sanitized), '?'));
            $types = str_repeat('i', count($kien_ids_sanitized));
            $target_kho = ($current_kho == 1) ? 2 : 1;
            
            $conn->begin_transaction();
            try {
                $sql_update = "UPDATE kien_hang SET kho_id = ? WHERE id IN ($placeholders) AND trang_thai = 'ton_kho'";
                $stmt_update = $conn->prepare($sql_update);
                if (!$stmt_update) throw new Exception("Lỗi CSDL (CK01): " . $conn->error);
                
                $update_params = array_merge([$target_kho], $kien_ids_sanitized);
                $update_types = "i" . $types;
                $stmt_update->bind_param($update_types, ...$update_params);
                
                if ($stmt_update->execute()) {
                    $updated_rows = $stmt_update->affected_rows;
                    if ($updated_rows > 0) { 
                        $conn->commit(); 
                        $response = ['success' => true, 'message' => "Chuyển thành công {$updated_rows} kiện hàng."]; 
                    } else { 
                        $conn->rollback(); 
                        $response['message'] = "Không có kiện hàng nào được chuyển (có thể kiện hàng đã xuất)."; 
                    }
                } else { 
                    throw new Exception("Lỗi khi chuyển kho: " . $stmt_update->error); 
                }
                $stmt_update->close();
            } catch (Exception $e) { 
                $conn->rollback(); 
                $response['message'] = 'Lỗi chuyển kho: ' . $e->getMessage(); 
                error_log($response['message']); 
            }
            break;

        case 'xuat_hang': // Case xuất hàng cũ (không Excel)
            // ... (case 'xuat_hang' của bạn không đổi) ...
            $kien_ids_json = isset($_POST['kien_ids']) ? $_POST['kien_ids'] : '[]';
            $kien_ids = json_decode($kien_ids_json, true);
            if (empty($kien_ids) || !is_array($kien_ids) || !array_filter($kien_ids, 'is_numeric')) { $response['message'] = 'ID kiện hàng không hợp lệ.'; } 
            else {
                $kien_ids_sanitized = array_map('intval', $kien_ids);
                if (empty($kien_ids_sanitized)) {$response['message'] = 'Không có ID kiện hợp lệ.'; break;}
                $placeholders = implode(',', array_fill(0, count($kien_ids_sanitized), '?'));
                $types = str_repeat('i', count($kien_ids_sanitized));
                $conn->begin_transaction();
                try {
                    // Không cần lấy $exported_data nếu đây là action cũ chỉ cập nhật trạng thái
                    $ngay_xuat = date('Y-m-d H:i:s');
                    $sql_update = "UPDATE kien_hang SET trang_thai = 'da_xuat', ngay_xuat_kho = ? WHERE id IN ($placeholders) AND trang_thai = 'ton_kho'";
                    $stmt_update = $conn->prepare($sql_update);
                    if (!$stmt_update) throw new Exception("Lỗi CSDL (XH02): " . $conn->error);
                    $update_params = array_merge([$ngay_xuat], $kien_ids_sanitized);
                    $update_types = "s" . $types;
                    $stmt_update->bind_param($update_types, ...$update_params);
                    if ($stmt_update->execute()) {
                        $updated_rows = $stmt_update->affected_rows;
                        if ($updated_rows > 0) { $conn->commit(); $response = ['success' => true, 'message' => "Xuất thành công {$updated_rows} kiện."]; } 
                        else { $conn->rollback(); $response['message'] = "Không có kiện hàng nào được cập nhật."; }
                    } else { throw new Exception("Lỗi khi cập nhật trạng thái xuất hàng: " . $stmt_update->error); }
                    $stmt_update->close();
                } catch (Exception $e) { $conn->rollback(); $response['message'] = 'Lỗi xuất hàng: ' . $e->getMessage(); error_log($response['message']); }
            }
            break;
        
        case 'get_kien_hang_detail_for_edit':
            // ... (case 'get_kien_hang_detail_for_edit' của bạn không đổi) ...
            // (Đảm bảo nó có 'exit;' ở cuối)
            $response_data_for_this_action = ['success' => false, 'message' => 'Lỗi không xác định.'];
            $kienhang_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

            if ($kienhang_id > 0) {
                $sql_detail = "SELECT 
                                    kh.id, kh.lot_no, kh.loai_nhom_id, kh.loai_hang_id, kh.kien_so, 
                                    kh.khoi_luong_kg, kh.ghi_chu,
                                    DATE_FORMAT(kh.ngay_san_xuat, '%Y-%m-%d') as ngay_san_xuat_raw, /* Cho input date */
                                    DATE_FORMAT(kh.ngay_san_xuat, '%d/%m/%Y') as ngay_san_xuat_f,  /* Cho hiển thị */
                                    ln.ten_loai_nhom, 
                                    lh.ten_loai_hang,
                                    COALESCE(stc.so_thoi_mac_dinh, 'N/A') AS so_thoi_mac_dinh 
                                FROM kien_hang kh
                                LEFT JOIN loai_nhom ln ON kh.loai_nhom_id = ln.id
                                LEFT JOIN loai_hang lh ON kh.loai_hang_id = lh.id
                                LEFT JOIN so_thoi_config stc ON kh.loai_nhom_id = stc.loai_nhom_id AND kh.loai_hang_id = stc.loai_hang_id
                                WHERE kh.id = ?";
                $stmt_detail = $conn->prepare($sql_detail);
                if($stmt_detail) {
                    $stmt_detail->bind_param("i", $kienhang_id);
                    if($stmt_detail->execute()){
                        $result_detail = $stmt_detail->get_result();
                        if ($item = $result_detail->fetch_assoc()) {
                            // Lấy thêm danh sách options cho select
                            $loai_nhom_options = getLoaiNhomOptions($conn); // Giả sử bạn có hàm này
                            $loai_hang_options = getLoaiHangOptions($conn); // Giả sử bạn có hàm này
                            $response_data_for_this_action = [
                                'success' => true, 
                                'item' => $item,
                                'loai_nhom_options' => $loai_nhom_options,
                                'loai_hang_options' => $loai_hang_options
                            ];
                        } else {
                            $response_data_for_this_action['message'] = 'Không tìm thấy kiện hàng với ID cung cấp.';
                        }
                        if($result_detail) $result_detail->close();
                    } else {
                        $response_data_for_this_action['message'] = 'Lỗi thực thi truy vấn chi tiết kiện hàng: ' . $stmt_detail->error;
                        error_log($response_data_for_this_action['message']);
                    }
                    $stmt_detail->close();
                } else {
                    $response_data_for_this_action['message'] = 'Lỗi chuẩn bị truy vấn chi tiết kiện hàng: ' . $conn->error;
                    error_log($response_data_for_this_action['message']);
                }
            } else {
                $response_data_for_this_action['message'] = 'ID kiện hàng không hợp lệ.';
            }
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode($response_data_for_this_action);
            exit; // Dừng script sau khi gửi JSON

        case 'sua_kien_hang':
            // ... (case 'sua_kien_hang' của bạn không đổi) ...
            // (Đảm bảo nó có 'exit;' ở cuối)
            $response_data_for_this_action = ['success' => false, 'message' => 'Lỗi không xác định khi cập nhật.'];
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $lot_no = isset($_POST['lot_no']) ? trim($_POST['lot_no']) : '';
            $loai_nhom_id = isset($_POST['loai_nhom_id']) ? intval($_POST['loai_nhom_id']) : 0;
            $loai_hang_id = isset($_POST['loai_hang_id']) ? (empty($_POST['loai_hang_id']) ? NULL : intval($_POST['loai_hang_id'])) : NULL;
            $kien_so = isset($_POST['kien_so']) ? trim($_POST['kien_so']) : '';
            $khoi_luong_kg = isset($_POST['khoi_luong_kg']) ? (is_numeric($_POST['khoi_luong_kg']) ? floatval($_POST['khoi_luong_kg']) : 0) : 0;
            $ngay_san_xuat = isset($_POST['ngay_san_xuat']) ? trim($_POST['ngay_san_xuat']) : ''; // Format YYYY-MM-DD từ input type="date"
            $ghi_chu = isset($_POST['ghi_chu']) ? trim($_POST['ghi_chu']) : NULL;

            if ($id <= 0) { $response_data_for_this_action['message'] = 'ID kiện hàng không hợp lệ.'; goto send_sua_kien_hang_response; }
            if (empty($lot_no)) { $response_data_for_this_action['message'] = 'Lot No không được để trống.'; goto send_sua_kien_hang_response; }
            if ($loai_nhom_id <= 0) { $response_data_for_this_action['message'] = 'Loại nhôm không hợp lệ.'; goto send_sua_kien_hang_response; }
            if (empty($kien_so)) { $response_data_for_this_action['message'] = 'Kiện số không được để trống.'; goto send_sua_kien_hang_response; }
            if ($khoi_luong_kg <= 0) { $response_data_for_this_action['message'] = 'Khối lượng không hợp lệ.'; goto send_sua_kien_hang_response; }
            if (empty($ngay_san_xuat) || !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $ngay_san_xuat)) {
                 $response_data_for_this_action['message'] = 'Ngày sản xuất không hợp lệ.'; goto send_sua_kien_hang_response;
            }

            $sql_update = "UPDATE kien_hang SET 
                                lot_no = ?, 
                                loai_nhom_id = ?, 
                                loai_hang_id = ?, 
                                kien_so = ?, 
                                khoi_luong_kg = ?, 
                                ngay_san_xuat = ?,
                                ghi_chu = ?
                            WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            if($stmt_update) {
                $stmt_update->bind_param("siisdssi", 
                    $lot_no, $loai_nhom_id, $loai_hang_id, 
                    $kien_so, $khoi_luong_kg, $ngay_san_xuat, 
                    $ghi_chu, $id
                );
                if ($stmt_update->execute()) {
                    $sql_updated_item = "SELECT kh.*, 
                                        DATE_FORMAT(kh.ngay_san_xuat, '%Y-%m-%d') as ngay_san_xuat_raw,
                                        DATE_FORMAT(kh.ngay_san_xuat, '%d/%m/%Y') as ngay_san_xuat_f,
                                        ln.ten_loai_nhom, lh.ten_loai_hang,
                                        COALESCE(stc.so_thoi_mac_dinh, 'N/A') AS so_thoi,
                                        kh.ghi_chu
                                     FROM kien_hang kh
                                     LEFT JOIN loai_nhom ln ON kh.loai_nhom_id = ln.id
                                     LEFT JOIN loai_hang lh ON kh.loai_hang_id = lh.id
                                     LEFT JOIN so_thoi_config stc ON kh.loai_nhom_id = stc.loai_nhom_id AND kh.loai_hang_id = stc.loai_hang_id
                                     WHERE kh.id = ?";
                    $stmt_get = $conn->prepare($sql_updated_item);
                    $stmt_get->bind_param("i", $id);
                    $stmt_get->execute();
                    $updated_item_result = $stmt_get->get_result();
                    $updated_item_data = $updated_item_result->fetch_assoc();
                    $stmt_get->close();

                    $response_data_for_this_action = ['success' => true, 'message' => 'Cập nhật thông tin kiện hàng thành công!', 'updated_item' => $updated_item_data];
                } else {
                    $response_data_for_this_action['message'] = 'Lỗi khi cập nhật kiện hàng: ' . $stmt_update->error;
                    error_log($response_data_for_this_action['message']);
                }
                $stmt_update->close();
            } else {
                $response_data_for_this_action['message'] = 'Lỗi chuẩn bị câu lệnh cập nhật: ' . $conn->error;
                error_log($response_data_for_this_action['message']);
            }

            send_sua_kien_hang_response: // Nhãn để goto
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode($response_data_for_this_action);
            exit; // Dừng script sau khi gửi JSON

        case 'get_kien_hang_details_for_printing':
            // ... (case 'get_kien_hang_details_for_printing' của bạn không đổi) ...
            $kien_ids_json = isset($_POST['kien_ids']) ? $_POST['kien_ids'] : '[]';
            $kien_ids = json_decode($kien_ids_json, true);
            $items_data = [];
            if (empty($kien_ids) || !is_array($kien_ids) || !array_filter($kien_ids, 'is_numeric')) { 
                $response = ['success' => false, 'message' => 'ID kiện hàng không hợp lệ cho việc in.']; 
            } else {
                $kien_ids_sanitized = array_map('intval', $kien_ids);
                if (empty($kien_ids_sanitized)) { 
                    $response = ['success' => false, 'message' => 'Danh sách ID rỗng sau xử lý cho việc in.']; 
                } else {
                    $placeholders_print = implode(',', array_fill(0, count($kien_ids_sanitized), '?')); 
                    $types_print = str_repeat('i', count($kien_ids_sanitized));
                    
                    $sql_print = "SELECT kh.id, kh.lot_no, ln.ten_loai_nhom, kh.kien_so,
                                        COALESCE(stc.so_thoi_mac_dinh, 'N/A') AS so_thoi,
                                        kh.khoi_luong_kg,
                                        DATE_FORMAT(kh.ngay_san_xuat, '%d/%m/%Y') AS ngay_san_xuat_f,
                                        lh.ten_loai_hang
                                 FROM kien_hang kh
                                 JOIN loai_nhom ln ON kh.loai_nhom_id = ln.id
                                 LEFT JOIN loai_hang lh ON kh.loai_hang_id = lh.id
                                 LEFT JOIN so_thoi_config stc
                                    ON kh.loai_nhom_id = stc.loai_nhom_id AND kh.loai_hang_id = stc.loai_hang_id
                                 WHERE kh.id IN ($placeholders_print)
                                 ORDER BY FIELD(kh.id, $placeholders_print)"; // Cần bind 2 lần
                    
                    $stmt_print = $conn->prepare($sql_print);
                    if ($stmt_print) {
                        $bind_params_print = array_merge($kien_ids_sanitized, $kien_ids_sanitized);
                        $bind_types_print = $types_print . $types_print;
                        $stmt_print->bind_param($bind_types_print, ...$bind_params_print);

                        if ($stmt_print->execute()) {
                            $result_print = $stmt_print->get_result(); 
                            while ($row_print = $result_print->fetch_assoc()) { $items_data[] = $row_print; }
                            // === BẮT ĐẦU LOGIC MỚI: LẤY THÀNH PHẦN CHO CÁC LOT NO ===

$thanh_phan_data_map = []; // Nơi lưu kết quả thành phần
$unique_lot_nos = []; // Nơi lưu các lot_no duy nhất

if (!empty($items_data)) {
    // 1. Lấy danh sách các lot_no duy nhất từ các kiện hàng đã chọn
    $unique_lot_nos = array_unique(array_column($items_data, 'lot_no'));

    if (!empty($unique_lot_nos)) {
        // 2. Chuẩn bị câu SQL để truy vấn bảng ThanhPhanLot
        $placeholders_lot = implode(',', array_fill(0, count($unique_lot_nos), '?'));
        $types_lot = str_repeat('s', count($unique_lot_nos));
        $sql_thanhphan = "SELECT * FROM ThanhPhanLot WHERE lot_no IN ($placeholders_lot)";
        
        $stmt_thanhphan = $conn->prepare($sql_thanhphan);
        if ($stmt_thanhphan) {
            $stmt_thanhphan->bind_param($types_lot, ...$unique_lot_nos);
            if ($stmt_thanhphan->execute()) {
                $result_thanhphan = $stmt_thanhphan->get_result();
                // 3. Lưu kết quả vào một map, với key là lot_no
                while ($row_tp = $result_thanhphan->fetch_assoc()) {
                    $thanh_phan_data_map[$row_tp['lot_no']] = $row_tp;
                }
                if ($result_thanhphan) $result_thanhphan->close();
            } else {
                // Không làm dừng, chỉ log lỗi (để vẫn in được tem dù thiếu thành phần)
                error_log('AJAX [get_kien_hang_details_for_printing] execute error: ' . $stmt_thanhphan->error);
            }
            $stmt_thanhphan->close();
        } else {
            error_log('AJAX [get_kien_hang_details_for_printing] prepare error: ' . $conn->error);
        }
    }
}

// 4. Gán $response cuối cùng, bao gồm cả 'items' và 'thanh_phan'
$response = [
    'success' => true, 
    'items' => $items_data,
    'thanh_phan' => $thanh_phan_data_map // Key mới mà JavaScript đang chờ
];

// === KẾT THÚC LOGIC MỚI ===
                            if ($result_print) $result_print->close();
                        } else { $response['message'] = 'Lỗi lấy chi tiết kiện hàng để in.'; error_log('Print fetch exec: ' . $stmt_print->error); }
                        $stmt_print->close();
                    } else { $response['message'] = 'Lỗi CSDL (PRINT01).'; error_log('Print prepare: ' . $conn->error . ' SQL: ' . $sql_print); }
                }
            }
            break;
        
        case 'xuat_hang_excel':
            // ... (case 'xuat_hang_excel' của bạn không đổi) ...
            // (Đảm bảo nó có 'exit;' ở cuối)
            $kien_ids_json = isset($_POST['kien_ids']) ? $_POST['kien_ids'] : '[]';
            $kien_ids = json_decode($kien_ids_json, true);
            $file_sent_successfully = false; 

            if (empty($kien_ids) || !is_array($kien_ids) || !array_filter($kien_ids, 'is_numeric')) {
                $response = ['success' => false, 'message' => 'ID kiện hàng không hợp lệ để xuất Excel.'];
                break; 
            }
            $kien_ids_sanitized = array_map('intval', $kien_ids);
            if (empty($kien_ids_sanitized)) {
                $response = ['success' => false, 'message' => 'Không có ID kiện hợp lệ sau khi xử lý để xuất Excel.'];
                break; 
            }

            $placeholders = implode(',', array_fill(0, count($kien_ids_sanitized), '?'));
            $types = str_repeat('i', count($kien_ids_sanitized));
            $ngay_xuat = date('Y-m-d H:i:s');
            $data_for_excel = [];

            $conn->begin_transaction();
            try {
                $sql_select_export_data = "SELECT
                                                kh.lot_no, DATE_FORMAT(kh.ngay_san_xuat, '%d/%m/%Y') AS nsx,
                                                kh.kien_so, COALESCE(stc.so_thoi_mac_dinh, 'N/A') AS so_thoi,
                                                kh.khoi_luong_kg, ln.ten_loai_nhom, lh.ten_loai_hang
                                            FROM kien_hang kh
                                            JOIN loai_nhom ln ON kh.loai_nhom_id = ln.id
                                            LEFT JOIN loai_hang lh ON kh.loai_hang_id = lh.id
                                            LEFT JOIN so_thoi_config stc
                                                ON kh.loai_nhom_id = stc.loai_nhom_id AND kh.loai_hang_id = stc.loai_hang_id
                                            WHERE kh.id IN ($placeholders) AND kh.trang_thai = 'ton_kho'
                                            ORDER BY kh.lot_no ASC, kh.kien_so ASC";

                $stmt_select = $conn->prepare($sql_select_export_data);
                if (!$stmt_select) throw new Exception("Lỗi CSDL (XHE01P): " . $conn->error);
                $stmt_select->bind_param($types, ...$kien_ids_sanitized);
                if (!$stmt_select->execute()) throw new Exception("Lỗi CSDL (XHE01E): " . $stmt_select->error);
                $result_export = $stmt_select->get_result();
                while ($row = $result_export->fetch_assoc()) { $data_for_excel[] = $row; }
                if ($result_export) $result_export->close();
                $stmt_select->close();

                if (empty($data_for_excel)) {
                    $conn->rollback();
                    $response = ['success' => false, 'message' => "Không tìm thấy kiện tồn kho hợp lệ nào để xuất Excel."];
                    break; 
                }

                $sql_update = "UPDATE kien_hang SET trang_thai = 'da_xuat', ngay_xuat_kho = ? WHERE id IN ($placeholders) AND trang_thai = 'ton_kho'";
                $stmt_update = $conn->prepare($sql_update);
                if (!$stmt_update) throw new Exception("Lỗi CSDL (XHE02P): " . $conn->error);
                $update_params = array_merge([$ngay_xuat], $kien_ids_sanitized);
                $update_types = "s" . $types;
                $stmt_update->bind_param($update_types, ...$update_params);
                if (!$stmt_update->execute()) throw new Exception("Lỗi cập nhật trạng thái: " . $stmt_update->error);
                
                $updated_rows = $stmt_update->affected_rows;
                if ($stmt_update) $stmt_update->close();

                if ($updated_rows > 0) {
                    $conn->commit();

                    $spreadsheet = new Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();
                    $sheet->setTitle("DanhSachXuatHang");
                    $headers = ["STT", "Lotno", "NSX", "Kiện Số", "Số thỏi", "Khối Lượng (Kg)", "Loại Nhôm", "Loại Hàng",];
                    $sheet->fromArray($headers, NULL, 'A1');
                    foreach (range('A', $sheet->getHighestDataColumn()) as $col) {
                        $sheet->getColumnDimension($col)->setAutoSize(true);
                        $sheet->getStyle($col . '1')->getFont()->setBold(true);
                        $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    }
                    
                    $stt = 1; $data_rows_for_excel_output = [];
                    foreach ($data_for_excel as $item_excel) {
                        $data_rows_for_excel_output[] = [
                            $stt++, $item_excel['lot_no'], $item_excel['nsx'], $item_excel['kien_so'],
                            $item_excel['so_thoi'], $item_excel['khoi_luong_kg'], $item_excel['ten_loai_nhom'], $item_excel['ten_loai_hang']
                        ];
                    }
                    $sheet->fromArray($data_rows_for_excel_output, NULL, 'A2');
                    
                    $filename = "XuatHang_" . date('Ymd_His') . ".xlsx";
                    if (ob_get_level()) ob_end_clean(); 

                    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                    header('Content-Disposition: attachment;filename="' . $filename . '"');
                    header('Cache-Control: max-age=0');
                    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                    header('Cache-Control: private, no-transform, no-store, must-revalidate');
                    header('Pragma: public');

                    $writer = new Xlsx($spreadsheet);
                    $writer->save('php://output');
                    $file_sent_successfully = true; 
                } else {
                    $conn->rollback();
                    $response = ['success' => false, 'message' => "Không có kiện hàng nào được cập nhật (có thể đã xuất hoặc không tồn kho)."];
                }
            } catch (Exception $e) {
                if ($conn->connect_errno === 0 && $conn->in_transaction) {
                     $conn->rollback();
                }
                error_log("Lỗi Exception xuất hàng Excel: " . $e->getMessage() . " tại " . $e->getFile() . ":" . $e->getLine());
                $response = ['success' => false, 'message' => 'Lỗi hệ thống khi xuất Excel: ' . $e->getMessage()];
            }
            
            if ($file_sent_successfully) {
                if (isset($conn) && $conn instanceof mysqli && $conn->connect_errno === 0) { $conn->close(); }
                exit; 
            }
            break; 
            
        case 'export_selected_to_excel': // Action mới chỉ để xuất Excel
            // ... (case 'export_selected_to_excel' của bạn không đổi) ...
            // (Đảm bảo nó có 'exit;' ở cuối)
            $kien_ids_json = isset($_POST['kien_ids']) ? $_POST['kien_ids'] : '[]';
            $kien_ids = json_decode($kien_ids_json, true);
            $file_sent_successfully_export_only = false; // Cờ riêng

            if (empty($kien_ids) || !is_array($kien_ids) || !array_filter($kien_ids, 'is_numeric')) {
                $response = ['success' => false, 'message' => 'ID kiện hàng không hợp lệ để xuất Excel (chỉ xuất).'];
                break; 
            }
            $kien_ids_sanitized = array_map('intval', $kien_ids);
            if (empty($kien_ids_sanitized)) {
                $response = ['success' => false, 'message' => 'Không có ID kiện hợp lệ để xuất Excel (chỉ xuất).'];
                break; 
            }

            $placeholders = implode(',', array_fill(0, count($kien_ids_sanitized), '?'));
            $types = str_repeat('i', count($kien_ids_sanitized));
            $data_for_excel = [];

            try {
                $sql_select_data = "SELECT
                                        kh.lot_no, DATE_FORMAT(kh.ngay_san_xuat, '%d/%m/%Y') AS nsx,
                                        kh.kien_so, COALESCE(stc.so_thoi_mac_dinh, 'N/A') AS so_thoi,
                                        DATE_FORMAT(kh.ngay_xuat_kho, '%d/%m/%Y %H:%i') AS ngay_xuat,
                                        kh.khoi_luong_kg, ln.ten_loai_nhom, lh.ten_loai_hang
                                    FROM kien_hang kh
                                    JOIN loai_nhom ln ON kh.loai_nhom_id = ln.id
                                    LEFT JOIN loai_hang lh ON kh.loai_hang_id = lh.id
                                    LEFT JOIN so_thoi_config stc
                                        ON kh.loai_nhom_id = stc.loai_nhom_id AND kh.loai_hang_id = stc.loai_hang_id
                                    WHERE kh.id IN ($placeholders) 
                                    ORDER BY kh.lot_no ASC, kh.kien_so ASC"; 

                $stmt_select = $conn->prepare($sql_select_data);
                if (!$stmt_select) throw new Exception("Lỗi CSDL (EXCEL_S01P): " . $conn->error);

                $stmt_select->bind_param($types, ...$kien_ids_sanitized);
                if (!$stmt_select->execute()) throw new Exception("Lỗi CSDL (EXCEL_S01E): " . $stmt_select->error);

                $result_export = $stmt_select->get_result();
                while ($row = $result_export->fetch_assoc()) { $data_for_excel[] = $row; }
                if ($result_export) $result_export->close();
                $stmt_select->close();

                if (empty($data_for_excel)) {
                    $response = ['success' => false, 'message' => "Không tìm thấy kiện hàng nào với các ID đã chọn để xuất Excel."];
                    break; 
                }

                $spreadsheet = new Spreadsheet(); 
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle("DuLieuKienHang");
                $headers = ["STT", "Lotno", "NSX", "Kiện Số", "Số thỏi", "Khối Lượng (Kg)", "Loại Nhôm", "Loại Hàng","Ngày Xuất"];
                $sheet->fromArray($headers, NULL, 'A1');
                foreach (range('A', $sheet->getHighestDataColumn()) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                    $sheet->getStyle($col . '1')->getFont()->setBold(true);
                    $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }

                $stt = 1; $data_rows_for_excel_output = [];
                foreach ($data_for_excel as $item_excel) {
                    $data_rows_for_excel_output[] = [
                        $stt++, $item_excel['lot_no'], $item_excel['nsx'], $item_excel['kien_so'],
                        $item_excel['so_thoi'], $item_excel['khoi_luong_kg'], $item_excel['ten_loai_nhom'], $item_excel['ten_loai_hang'],$item_excel['ngay_xuat']
                    ];
                }
                $sheet->fromArray($data_rows_for_excel_output, NULL, 'A2');

                $filename = "DuLieuXuat_" . date('Ymd_His') . ".xlsx";
                if (ob_get_level()) ob_end_clean(); 

                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                header('Cache-Control: max-age=0');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                header('Cache-Control: private, no-transform, no-store, must-revalidate');
                header('Pragma: public');

                $writer = new Xlsx($spreadsheet); 
                $writer->save('php://output');
                $file_sent_successfully_export_only = true; 

            } catch (Exception $e) {
                error_log("Lỗi Exception xuất Excel (Only): " . $e->getMessage() . " tại " . $e->getFile() . ":" . $e->getLine());
                $response = ['success' => false, 'message' => 'Lỗi hệ thống khi tạo file Excel (chỉ xuất): ' . $e->getMessage()];
            }

            if ($file_sent_successfully_export_only) {
                if (isset($conn) && $conn instanceof mysqli && $conn->connect_errno === 0) { $conn->close(); }
                exit; 
            }
            break; 
            
        default:
            // $response đã được gán giá trị mặc định
            break; 
    }
} catch (Throwable $e) { 
    error_log("Lỗi Throwable trong AJAX handler: " . $e->getMessage() . " tại " . $e->getFile() . ":" . $e->getLine() . "\nStack trace:\n" . $e->getTraceAsString());
    $response = ['success' => false, 'message' => 'Có lỗi hệ thống nghiêm trọng. Vui lòng kiểm tra log server.'];
}

// --- GỬI PHẢN HỒI JSON ---
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
}

if (isset($conn) && $conn instanceof mysqli && $conn->connect_errno === 0) {
    $conn->close();
}
exit; 

?>