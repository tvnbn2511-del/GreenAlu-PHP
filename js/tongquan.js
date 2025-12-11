// --- LOGIC CHO TAB TỔNG QUAN ---

/**
 * Hàm này được gọi bởi app.js khi người dùng nhấp vào tab "Tổng Quan"
 * Nó chỉ chạy 1 lần để tải dữ liệu.
 */
let dashboardDataLoaded = false;
function loadDashboardData() {
    // Chỉ tải 1 lần duy nhất
    if (dashboardDataLoaded) return; 

    const summaryContainer = document.getElementById('summary-container');
    const lotListContainer = document.getElementById('lot-list-container');
    const AJAX_URL_TONGQUAN = 'php/ajax_tongquan_handler.php'; // Đường dẫn tới handler mới

    if (!summaryContainer || !lotListContainer) {
        console.error("Không tìm thấy container của tab Tổng Quan!");
        return;
    }

    // Hiển thị loading (dù đã có sẵn trong HTML)
    summaryContainer.innerHTML = `<div class="summary-loading"><i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu tổng hợp...</div>`;
    lotListContainer.innerHTML = `<div class="summary-loading"><i class="fas fa-spinner fa-spin"></i> Đang tải danh sách lot...</div>`;

    fetch(AJAX_URL_TONGQUAN, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_inventory_summary'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Lỗi mạng: ${response.status} ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Đánh dấu đã tải thành công
            dashboardDataLoaded = true; 
            
            // 1. Vẽ các thẻ tổng hợp
            renderSummary(data.summary_data || []);
            
            // 2. Vẽ bảng chi tiết lot
            renderLotList(data.lot_data || [], data.composition_data || {});

        } else {
            throw new Error(data.message || 'Không thể lấy dữ liệu từ server.');
        }
    })
    .catch(error => {
        console.error('Lỗi khi tải dữ liệu tổng quan:', error);
        summaryContainer.innerHTML = `<div class="error-msg">Lỗi: ${error.message}</div>`;
        lotListContainer.innerHTML = `<div class="error-msg">Lỗi: ${error.message}</div>`;
    });
}

// Đẩy hàm này ra global scope để app.js có thể gọi được
window.loadDashboardData = loadDashboardData;


/**
 * Hiển thị các thẻ tổng hợp
 * @param {Array} summaryData - Dữ liệu từ Query 1 (đã join)
 */
function renderSummary(summaryData) {
    const container = document.getElementById('summary-container');
    if (!container) return;

    if (summaryData.length === 0) {
        container.innerHTML = '<div class="summary-loading">Không có dữ liệu tồn kho.</div>';
        return;
    }

    // 1. Nhóm dữ liệu theo ten_loai_nhom (ADC12, AC2B...)
    const groupedData = {};
    let grandTotalWeight = 0;

    summaryData.forEach(item => {
        const loaiNhom = item.ten_loai_nhom;
        const khoiLuong = parseFloat(item.total_kg);
        grandTotalWeight += khoiLuong;

        if (!groupedData[loaiNhom]) {
            groupedData[loaiNhom] = {
                totalWeight: 0,
                subItems: [] // Lưu các loại hàng (Boramtek, Lioho...)
            };
        }
        
        groupedData[loaiNhom].totalWeight += khoiLuong;
        groupedData[loaiNhom].subItems.push(item);
    });

    // 2. Tạo HTML
    let html = '';
    
    // Sắp xếp các loại nhôm theo tên
    const sortedLoaiNhomKeys = Object.keys(groupedData).sort();

    sortedLoaiNhomKeys.forEach(loaiNhom => {
        const group = groupedData[loaiNhom];
        
        html += `
            <div class="summary-card">
                <h3 class="summary-card-header">${loaiNhom}</h3>
                <div class="summary-card-total">
                    Tổng cộng: <strong>${formatWeight(group.totalWeight)}</strong>
                </div>
                <ul class="summary-card-list">
        `;

        // Sắp xếp các loại hàng bên trong theo tên
        group.subItems.sort((a, b) => a.ten_loai_hang.localeCompare(b.ten_loai_hang));

        group.subItems.forEach(item => {
            html += `
                <li class="summary-sub-item">
                    <span>${item.ten_loai_hang || '(Không xác định)'}</span>
                    <strong>${formatNumber(item.total_kg)} Kg</strong>
                </li>
            `;
        });

        html += `
                </ul>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    // Bạn có thể thêm một thẻ tổng ở đâu đó nếu muốn
    // Ví dụ: console.log(`Tổng khối lượng tồn kho: ${grandTotalWeight}`);
}


/**
 * Hiển thị bảng chi tiết các Lot No
 * @param {Array} lotData - Dữ liệu từ Query 2
 * @param {Object} compositionData - Dữ liệu từ Query 3
 */
function renderLotList(lotData, compositionData) {
    const container = document.getElementById('lot-list-container');
    if (!container) return;

    if (lotData.length === 0) {
        container.innerHTML = '<div class="summary-loading">Không có Lot No nào tồn kho.</div>';
        return;
    }

    // 1. Nhóm dữ liệu theo ten_loai_nhom (ADC12, AC2B...)
    const groupedData = {};
    lotData.forEach(lot => {
        const loaiNhom = lot.ten_loai_nhom;
        if (!groupedData[loaiNhom]) {
            groupedData[loaiNhom] = [];
        }
        groupedData[loaiNhom].push(lot);
    });

    // 2. Tạo HTML
    let html = '';
    const sortedLoaiNhomKeys = Object.keys(groupedData).sort();

    sortedLoaiNhomKeys.forEach(loaiNhom => {
        const lotsInGroup = groupedData[loaiNhom];

        // Tiêu đề cho nhóm
        html += `<h3 class="lot-group-header">${loaiNhom}</h3>`;
        
        // Bảng cho nhóm
        html += `
            <div class="table-responsive">
                <table class="lot-summary-table">
                    <thead>
                        <tr>
                            <th>Lot No</th>
                            <th>Khách Hàng (Loại Hàng)</th>
                            <th>Tổng Khối Lượng</th>
                            <th>Thành Phần Hóa Học</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        // Sắp xếp các lot trong nhóm (ví dụ: theo NSX)
        lotsInGroup.sort((a, b) => a.ngay_san_xuat.localeCompare(b.ngay_san_xuat));

        lotsInGroup.forEach(lot => {
            const lotno = lot.lot_no;
            const compositionObject = compositionData[lotno];
            const compositionString = formatCompositionString(compositionObject); // Dùng hàm trợ giúp

            html += `
                <tr>
                    <td><strong>${lotno}</strong></td>
                    <td>${lot.ten_loai_hang || '(Không xác định)'}</td>
                    <td><strong>${formatWeight(lot.total_kg)}</strong></td>
                    <td class="composition-cell">${compositionString}</td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;
    });

    container.innerHTML = html;
}


// --- CÁC HÀM TRỢ GIÚP (Copy từ app.js hoặc định nghĩa mới) ---

/**
 * Định dạng khối lượng, tự động chuyển sang Tấn
 * @param {string|number} kgValue - Giá trị khối lượng (Kg)
 * @returns {string} - Chuỗi đã định dạng (ví dụ: "1500 Kg" hoặc "2.5 Tấn")
 */
function formatWeight(kgValue) {
    const num = parseFloat(kgValue);
    if (isNaN(num)) return "0 Kg";

    if (num >= 1000) {
        // Chuyển sang Tấn (T)
        return `${(num / 1000).toFixed(2)} Tấn`;
    }
    // Giữ nguyên Kg
    return `${formatNumber(num)} Kg`;
}

/**
 * Định dạng số, thêm dấu phẩy hàng nghìn
 * @param {string|number} num - Số cần định dạng
 * @returns {string} - Chuỗi đã định dạng (ví dụ: "12,345")
 */
function formatNumber(num) {
    // Đảm bảo là số, và xử lý cả số nguyên (bỏ .00)
    const parsedNum = parseFloat(num);
    if (isNaN(parsedNum)) return '0';
    
    return parsedNum.toLocaleString('vi-VN', {
        maximumFractionDigits: 2 
    });
}

/**
 * Chuyển đổi đối tượng thành phần thành chuỗi định dạng
 * @param {object | undefined} composition - Đối tượng thành phần.
 * @returns {string} - Chuỗi đã định dạng.
 */
function formatCompositionString(composition) {
    if (!composition) {
        return "N/A"; // Trả về N/A nếu không có dữ liệu
    }

    const parts = [];
    // Liệt kê các thành phần theo thứ tự
    const elements = ['Si', 'Fe', 'Cu', 'Mn', 'Mg', 'Zn', 'Pb', 'Ni', 'Cr', 'Sn', 'Ti', 'Cd', 'Ca'];

    elements.forEach(key => {
        const value = parseFloat(composition[key]);
        if (!isNaN(value) && value > 0) {
            parts.push(`${key}: ${value}`);
        }
    });

    if (parts.length === 0) {
        return "Tất cả = 0"; // Hoặc "N/A"
    }

    return parts.join(', ');
}