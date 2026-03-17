<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ứng Dụng Quản Lý Kho Nhôm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Thêm ?v=1.4 để làm mới file CSS nếu cần -->
    <link rel="stylesheet" href="css/style.css?v=1.4"> 
</head>
<body>
    <div class="container">
        <!-- === HEADER VÀ TABS === -->
        <header class="header-with-tabs">
            <div class="header-left">
                <h1><i class="fas fa-warehouse"></i> GreenAlu</h1>
            </div>
            <nav class="tabs-container">
                <!-- Tab 1: Tổng Quan (MỚI) - Mặc định active -->
                <a href="#tongquan" class="tab-link active" data-tab="tab-content-tongquan">
                    <i class="fas fa-chart-pie"></i> Tổng Quan Tồn Kho
                </a>
                <!-- Tab Kho Hùng Phát -->
                <a href="#khoHungPhat" class="tab-link" data-tab="tab-content-chitiet" data-kho="1">
                    <i class="fas fa-tasks"></i> Kho Hùng Phát
                </a>
                <!-- Tab KHO 277 -->
                <a href="#kho277" class="tab-link" data-tab="tab-content-chitiet" data-kho="2">
                    <i class="fas fa-tasks"></i> KHO 277
                </a>
            </nav>
        </header>

        <main>
            <!-- === NỘI DUNG TAB 1: TỔNG QUAN === -->
            <div id="tab-content-tongquan" class="tab-pane active">
                <!-- PHẦN 1: TỔNG HỢP SỐ LƯỢNG LỚN -->
                <section class="controls-section">
                    <div id="summary-container" class="summary-grid">
                        <div class="summary-loading">
                            <i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu tổng hợp...
                        </div>
                    </div>
                </section>

                <!-- PHẦN 2: DANH SÁCH LOT CÒN TỒN -->
                <section class="data-section">
                    <div id="lot-list-container">
                        <div class="summary-loading">
                            <i class="fas fa-spinner fa-spin"></i> Đang tải danh sách lot...
                        </div>
                    </div>
                </section>
            </div>

            <!-- === NỘI DUNG TAB 2: QUẢN LÝ CHI TIẾT === -->
            <div id="tab-content-chitiet" class="tab-pane">
                <section class="controls-section">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="lotno-input">Lot No:</label>
                            <input type="text" id="lotno-input" placeholder="VD: 0102 hoặc 0102,0103">
                        </div>
                        <div class="form-group">
                            <label for="loainhom-select">Loại Nhôm:</label>
                            <div class="select-with-button">
                                <select id="loainhom-select">
                                    <option value="">-- Chọn loại nhôm --</option>
                                    </select>
                                <button id="btn-them-loainhom-modal" title="Thêm loại nhôm mới"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="nsx-input">Ngày Sản Xuất:</label>
                            <input type="date" id="nsx-input">
                        </div>
                        <div class="form-group">
                            <label for="soluong-input">Số Lượng Kiện (Khi nhập hàng):</label>
                            <input type="number" id="soluong-input" min="1" placeholder="Số kiện">
                        </div>
                        <div class="form-group">
                            <label for="trangthai-select">Trạng Thái (Tìm kiếm):</label>
                            <select id="trangthai-select">
                                <option value="tat_ca">Tất cả</option>
                                <option value="ton_kho" selected>Tồn kho</option>
                                <option value="da_xuat">Đã xuất</option>
                            </select>
                        </div>
                       <div class="form-group">
                            <label for="loaihang-select-main">Loại Hàng (cho nhập/tìm):</label>
                            <div class="select-with-button">
                                <select id="loaihang-select-main">
                                    <option value="">-- Chọn loại hàng --</option>
                                    </select>
                                <button id="btn-them-loaihang-modal" title="Thêm loại hàng mới"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                    </div> <!-- .form-grid -->
                    
                    <div class="action-buttons">
                        <button id="btn-timkiem"><i class="fas fa-search"></i> Tìm Kiếm</button>
                        <button id="btn-nhaphang-modal"><i class="fas fa-dolly-flatbed"></i> Nhập Hàng</button>
                        <button id="btn-xuathang"><i class="fas fa-truck-loading"></i> Xuất Hàng</button>
                        <button id="btn-chuyenkho" style="background-color: #f39c12; color: white;"><i class="fas fa-exchange-alt"></i> Chuyển Kho</button>
                        <button id="btn-ghep-kien" style="background-color: #6f42c1; color: white;"><i class="fas fa-random"></i> Ghép Kiện</button>
                        <button id="btn-intem"><i class="fas fa-print"></i> In Tem</button>
                        <button id="btn-xuat-excel-moi" style="background-color: #17a2b8;"><i class="fas fa-file-excel"></i> Xuất Excel</button> 
                    </div>
                </section>

                <section class="data-section">
                    <div class="total-weight-display">
                        <strong>Tổng khối lượng chọn: <span id="total-selected-weight">0 Kg</span></strong>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="header-checkbox" title="Chọn/Bỏ chọn tất cả trên trang"></th>
                                    <th>STT</th>
                                    <th>Lot No</th>
                                    <th>Loại Nhôm</th>
                                    <!-- === SỬA LỖI LỆCH CỘT: Thêm class="th-loaihang" === -->
                                    <th class="th-loaihang">Loại Hàng</th> 
                                    <th>Kiện Số</th>
                                    <th>KL (Kg)</th>
                                    <th>NSX</th>
                                    <th>Trạng Thái</th>
                                </tr>
                            </thead>
                            <tbody id="kienhang-tbody">
                                <tr><td colspan="9" class="no-data">Đang tải dữ liệu...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div> <!-- #tab-content-chitiet -->
        </main>
    </div> <!-- .container -->

    <!-- === CÁC MODAL (Giữ nguyên) === -->

    <!-- Modal Thêm Loại Nhôm -->
    <div id="modal-them-loainhom" class="modal">
        <div class="modal-content">
            <span class="close-button" data-modal-id="modal-them-loainhom">&times;</span>
            <h3><i class="fas fa-plus-circle"></i> Thêm Loại Nhôm Mới</h3>
            <div class="form-group">
                <label for="ten-loainhom-moi">Tên loại nhôm:</label>
                <input type="text" id="ten-loainhom-moi" placeholder="VD: ADC12, AL6061">
            </div>
            <button id="btn-luu-loainhom" class="action-buttons"><i class="fas fa-save"></i> Lưu</button>
        </div>
    </div>

    <!-- Modal Nhập Khối Lượng -->
    <div id="modal-nhap-khoiluong" class="modal">
        <div class="modal-content modal-lg">
            <span class="close-button" data-modal-id="modal-nhap-khoiluong">&times;</span>
            <h3><i class="fas fa-weight-hanging"></i> Nhập Khối Lượng </h3>
            <input type="hidden" id="nhap-hang-mode" value="nhap_hang"> 
            <input type="hidden" id="ghep-kien-old-ids" value="">
            <p>Lot No: <strong id="modal-lotno-display"></strong> - Loại: <strong id="modal-loainhom-display"></strong> (<span id="modal-loaihang-display"></span>) - NSX: <strong id="modal-nsx-display"></strong></p>
            
            <!-- Thành Phần Hóa Học -->
            <h4 class="thanhphan-header"><i class="fas fa-flask"></i> Thành Phần Hóa Học (%)</h4>
            <div id="thanhphan-grid-container">
                <div class="modal-form-group-small">
                    <label for="tp-si">Si:</label>
                    <input type="number" id="tp-si" name="Si" class="thanhphan-input" step="0.001" min="0" value="0">
                </div>
                <div class="modal-form-group-small">
                    <label for="tp-fe">Fe:</label>
                    <input type="number" id="tp-fe" name="Fe" class="thanhphan-input" step="0.001" min="0" value="0">
                </div>
                <div class="modal-form-group-small">
                    <label for="tp-cu">Cu:</label>
                    <input type="number" id="tp-cu" name="Cu" class="thanhphan-input" step="0.001" min="0" value="0">
                </div>
                <div class="modal-form-group-small">
                    <label for="tp-mn">Mn:</label>
                    <input type="number" id="tp-mn" name="Mn" class="thanhphan-input" step="0.001" min="0" value="0">
                </div>
                <div class="modal-form-group-small">
                    <label for="tp-mg">Mg:</label>
                    <input type="number" id="tp-mg" name="Mg" class="thanhphan-input" step="0.001" min="0" value="0">
                </div>
                <div class="modal-form-group-small">
                    <label for="tp-zn">Zn:</label>
                    <input type="number" id="tp-zn" name="Zn" class="thanhphan-input" step="0.001" min="0" value="0">
                </div>
                <div class="modal-form-group-small">
                    <label for="tp-pb">Pb:</label>
                    <input type="number" id="tp-pb" name="Pb" class="thanhphan-input" step="0.001" min="0" value="0">
                </div>
                <div class="modal-form-group-small">
                    <label for="tp-ni">Ni:</label>
                    <input type="number" id="tp-ni" name="Ni" class="thanhphan-input" step="0.001" min="0" value="0">
                </div>
                <div class="modal-form-group-small">
                    <label for="tp-cr">Cr:</label>
                    <input type="number" id="tp-cr" name="Cr" class="thanhphan-input" step="0.001" min="0" value="0">
                </div>
                <div class="modal-form-group-small">
                    <label for="tp-sn">Sn:</label>
                    <input type="number" id="tp-sn" name="Sn" class="thanhphan-input" step="0.001" min="0" value="0">
                </div>
                <div class="modal-form-group-small">
                    <label for="tp-ti">Ti:</label>
                    <input type="number" id="tp-ti" name="Ti" class="thanhphan-input" step="0.001" min="0" value="0">
                </div>
                <div class="modal-form-group-small">
                    <label for="tp-cd">Cd:</label>
                    <input type="number" id="tp-cd" name="Cd" class="thanhphan-input" step="0.001" min="0" value="0">
                </div>
                <div class="modal-form-group-small">
                    <label for="tp-ca">Ca:</label>
                    <input type="number" id="tp-ca" name="Ca" class="thanhphan-input" step="0.001" min="0" value="0">
                </div>
            </div>
            <div style="display: flex; gap: 20px; align-items: flex-end; margin-bottom: 15px;">
            <div class="modal-form-group modal-form-group-kien-bat-dau" style="flex: 1;">
                <label for="modal-kien-batdau-input">Kiện Bắt Đầu:</label>
                <input type="number" id="modal-kien-batdau-input" min="1" value="1" placeholder="Số thứ tự kiện đầu">
            </div>
            
            <div class="modal-form-group" style="flex: 1;">
                <label for="modal-po-number-input" style="color: #007bff; font-weight: bold;">PO Number:</label>
                <input type="text" id="modal-po-number-input" placeholder="Nhập PO Number (nếu có)">
            </div>
        </div>

            <!-- Form Nhập Khối Lượng (ẩn) -->
            <form id="form-nhap-khoiluong">
                <!-- JS sẽ điền các kiện vào đây -->
            </form>
            <button id="btn-xacnhan-nhaphang" class="action-buttons"><i class="fas fa-check-circle"></i> Xác Nhận </button>
        </div>
    </div>

    <!-- Modal Chọn Mẫu Tem -->
    <div id="modal-chon-mau-tem" class="modal">
        <div class="modal-content">
            <span class="close-button" data-modal-id="modal-chon-mau-tem">&times;</span>
            <h3><i class="fas fa-print"></i> Chọn Mẫu Tem để In</h3>
            <div class="modal-form-group">
                <label for="select-mau-tem">Loại khách hàng / Mẫu tem:</label>
                <select id="select-mau-tem">
                    <option value="mau1">Tem 1 (ADC12 - INNOCO)</option>
                    <option value="mau2">Tem 2 (PHÚC MINH KHANG)</option>
                    <option value="mau3_A5">Tem 3 (A5 - M&C Electronics)</option>
                    <option value="mau4_A5">Tem 4 (A5 - HONDA TRADING)</option>
                    <option value="mau5">Tem 5 (GreenAlu)</option>
                    <option value="mau6">Tem 6 (Thuan Thanh)</option>
                </select>
            </div>
            <button id="btn-xacnhan-in-tem" class="action-buttons"><i class="fas fa-check"></i> Xác Nhận </button>
        </div>
    </div>
    
    <!-- Modal Thêm Loại Hàng -->
    <div id="modal-them-loaihang" class="modal">
        <div class="modal-content">
            <span class="close-button" data-modal-id="modal-them-loaihang">&times;</span>
            <h3><i class="fas fa-tags"></i> Thêm Loại Hàng Mới</h3>
            <div class="form-group">
                <label for="ten-loaihang-moi">Tên loại hàng:</label>
                <input type="text" id="ten-loaihang-moi" placeholder="VD: Hàng A, Hàng B">
            </div>
            <button id="btn-luu-loaihang" class="action-buttons"><i class="fas fa-save"></i> Lưu</button>
        </div>
    </div>
    
    <!-- Modal Sửa Kiện Hàng -->
    <div id="modal-sua-kienhang" class="modal">
        <div class="modal-content modal-lg"> <span class="close-button" data-modal-id="modal-sua-kienhang" title="Đóng">&times;</span>
            <h3><i class="fas fa-edit"></i> Chỉnh Sửa Thông Tin Kiện Hàng</h3>
            <form id="form-sua-kienhang">
                <input type="hidden" id="edit-kienhang-id" name="id">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit-lotno">Lot No:</label>
                        <input type="text" id="edit-lotno" name="lot_no" required>
                        <small>Thông tin gốc: <span id="original-lotno">N/A</span></small>
                    </div>
                    <div class="form-group">
                        <label for="edit-loainhom">Loại Nhôm:</label>
                        <select id="edit-loainhom" name="loai_nhom_id" required>
                            <option value="">-- Chọn Loại Nhôm --</option>
                        </select>
                        <small>Thông tin gốc: <span id="original-loainhom">N/A</span></small>
                    </div>
                    <div class="form-group">
                        <label for="edit-loaihang">Loại Hàng:</label>
                        <select id="edit-loaihang" name="loai_hang_id">
                            <option value="">-- Chọn Loại Hàng --</option>
                        </select>
                        <small>Thông tin gốc: <span id="original-loaihang">N/A</span></small>
                    </div>
                    <div class="form-group">
                        <label for="edit-kienso">Kiện Số:</label>
                        <input type="text" id="edit-kienso" name="kien_so" required>
                        <small>Thông tin gốc: <span id="original-kienso">N/A</span></small>
                    </div>
                    <div class="form-group">
                        <label for="edit-khoiluong">Khối Lượng (Kg):</label>
                        <input type="number" step="0.01" id="edit-khoiluong" name="khoi_luong_kg" required>
                        <small>Thông tin gốc: <span id="original-khoiluong">N/A</span></small>
                    </div>
                    <div class="form-group">
                        <label for="edit-nsx">Ngày Sản Xuất:</label>
                        <input type="date" id="edit-nsx" name="ngay_san_xuat" required>
                        <small>Thông tin gốc: <span id="original-nsx">N/A</span></small>
                    </div>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="edit-ghichu">Chú thích (cho lần sửa này):</label>
                    <textarea id="edit-ghichu" name="ghi_chu" rows="3" placeholder="Nhập chú thích nếu có thay đổi..."></textarea>
                    <small>Chú thích đã lưu: <span id="original-ghichu">Không có</span></small>
                </div>

                <button type="submit" id="btn-luu-thaydoi-kienhang" class="action-buttons"><i class="fas fa-save"></i> Lưu Thay Đổi</button>
            </form>
        </div>
    </div>

    <!-- === SỬA LỖI: Thêm DIV cho Tooltip === -->
    <div id="composition-tooltip"></div>
    
    <!-- === SỬA LỖI CACHE: Thêm ?v=1.4 === -->
    <script src="js/app.js?v=1.4"></script>
    <script src="js/tongquan.js?v=1.4"></script>

</body>
</html>