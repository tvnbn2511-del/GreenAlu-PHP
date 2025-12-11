<?php
// Thông tin kết nối CSDL
$servername = "localhost"; // Hoặc IP của server MySQL nếu khác
$username = "root";        // Username của bạn
$password = "";            // Password của bạn (để trống nếu dùng XAMPP mặc định)
$dbname = "quanlykho_nhom"; // Tên cơ sở dữ liệu đã tạo

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    // Trong môi trường production, không nên die() với lỗi chi tiết cho người dùng.
    // Ghi log lỗi và trả về thông báo chung hoặc JSON lỗi.
    error_log("DB Connection Error: " . $conn->connect_error . " (Error Code: " . $conn->connect_errno . ")");
    // Trả về JSON lỗi để client có thể xử lý
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi kết nối cơ sở dữ liệu. Vui lòng thử lại sau. (Mã lỗi: DBCONN_' . $conn->connect_errno . ')'
    ]);
    exit; // Dừng script sau khi trả về JSON
}

// Thiết lập bộ ký tự UTF-8 để làm việc với tiếng Việt
if (!$conn->set_charset("utf8mb4")) {
    error_log("Lỗi khi thiết lập utf8mb4: " . $conn->error);
    // Có thể không cần die ở đây, nhưng cần ghi nhận lỗi.
    // Nếu lỗi này nghiêm trọng, có thể xem xét việc dừng script.
}

// Không cần đóng kết nối $conn ở đây nếu tệp này được include vào các tệp khác sẽ sử dụng nó.
// Kết nối sẽ tự động đóng khi script PHP kết thúc.
// Nếu bạn muốn đóng kết nối một cách tường minh sau khi sử dụng, hãy gọi $conn->close(); ở cuối script sử dụng nó.
?>
