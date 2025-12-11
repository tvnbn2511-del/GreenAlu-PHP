document.addEventListener('DOMContentLoaded', function() {
    
    // === BẮT ĐẦU LOGIC CHUYỂN TAB MỚI ===
    
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabPanes = document.querySelectorAll('.tab-pane');
    let isTongQuanLoaded = false; // Cờ để biết tab Tổng Quan đã tải chưa
    let isChiTietLoaded = false;  // Cờ để biết tab Chi Tiết đã tải chưa
    
    // Lắng nghe sự kiện click trên từng link tab
    tabLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault(); // Ngăn trình duyệt nhảy đến (ví dụ: #tongquan)
            
            const tabId = this.getAttribute('data-tab');
            
            // 1. Xóa class 'active' khỏi tất cả các link và pane
            tabLinks.forEach(lnk => lnk.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // 2. Thêm class 'active' cho link và pane được chọn
            this.classList.add('active');
            const activePane = document.getElementById(tabId);
            if (activePane) {
                activePane.classList.add('active');
            }

          // Tải dữ liệu cho tab "Tổng Quan"
            if (tabId === 'tab-content-tongquan' && !isTongQuanLoaded) {
            if (typeof loadDashboardData === 'function') {
                loadDashboardData(); 
                isTongQuanLoaded = true; // Đánh dấu đã tải
            }
            }

         // Tải dữ liệu cho tab "Chi Tiết"
            if (tabId === 'tab-content-chitiet' && !isChiTietLoaded) {
            if (typeof loadInitialData === 'function') {
                loadInitialData(); // Hàm này đã có sẵn trong app.js
                isChiTietLoaded = true; // Đánh dấu đã tải
                }
            }
                    });
                });
    
    // (Tùy chọn): Tự động mở tab dựa trên URL hash (nếu có)
    if (window.location.hash) {
        const hash = window.location.hash; // Ví dụ: #tongquan
        const targetTabLink = document.querySelector(`.tab-link[href="${hash}"]`);
        if (targetTabLink) {
            targetTabLink.click(); // Kích hoạt sự kiện click
        }
    }
    
    // === KẾT THÚC LOGIC CHUYỂN TAB MỚI ===

    
    // === TOÀN BỘ LOGIC CŨ CỦA BẠN BẮT ĐẦU TỪ ĐÂY ===

    // --- Biến toàn cục cho Tooltip và Cache ---
    let compositionDataCache = {};
    let tooltipElement = null;

    // --- DOM Elements - Main Form & Inputs ---
    const lotnoInput = document.getElementById('lotno-input');
    const loainhomSelect = document.getElementById('loainhom-select');
    const loaihangSelectMain = document.getElementById('loaihang-select-main');
    const nsxInput = document.getElementById('nsx-input');
    const soluongInput = document.getElementById('soluong-input'); // Số lượng kiện khi nhập hàng
    const trangthaiSelect = document.getElementById('trangthai-select'); // Trạng thái tìm kiếm

    // --- DOM Elements - Buttons ---
    const btnThemLoainhomModal = document.getElementById('btn-them-loainhom-modal');
    const btnThemLoaihangModal = document.getElementById('btn-them-loaihang-modal');
    const btnTimkiem = document.getElementById('btn-timkiem');
    const btnNhaphangModal = document.getElementById('btn-nhaphang-modal');
    const btnXuathang = document.getElementById('btn-xuathang'); // Nút "Xuất Hàng" (cập nhật DB + xuất Excel)
    const btnXuatExcelMoi = document.getElementById('btn-xuat-excel-moi'); // Nút "Xuất Excel" mới (chỉ xuất Excel)
    const btnIntem = document.getElementById('btn-intem');

    // --- DOM Elements - Table & Total Weight Display ---
    const kienhangTbody = document.getElementById('kienhang-tbody');
    const headerCheckbox = document.getElementById('header-checkbox');
    const totalSelectedWeightSpan = document.getElementById('total-selected-weight');

    // --- DOM Elements - Modal Thêm Loại Nhôm ---
    const modalThemLoaiNhom = document.getElementById('modal-them-loainhom');
    const tenLoainhomMoiInput = document.getElementById('ten-loainhom-moi');
    const btnLuuLoainhom = document.getElementById('btn-luu-loainhom');
    const btnDongModalThemLoaiNhom = document.querySelector('#modal-them-loainhom .close-button');

    // --- DOM Elements - Modal Thêm Loại Hàng ---
    const modalThemLoaiHang = document.getElementById('modal-them-loaihang');
    const tenLoaihangMoiInput = document.getElementById('ten-loaihang-moi');
    const btnLuuLoaihang = document.getElementById('btn-luu-loaihang');
    const btnDongModalThemLoaiHang = document.querySelector('#modal-them-loaihang .close-button');

    // --- DOM Elements - Modal Nhập Khối Lượng (cho Nhập Hàng) ---
    const modalNhapKhoiLuong = document.getElementById('modal-nhap-khoiluong');
    const modalKienBatdauInput = document.getElementById('modal-kien-batdau-input');
    const formNhapKhoiluong = document.getElementById('form-nhap-khoiluong');
    const btnXacnhanNhaphang = document.getElementById('btn-xacnhan-nhaphang');
    const modalLotnoDisplay = document.getElementById('modal-lotno-display');
    const modalLoainhomDisplay = document.getElementById('modal-loainhom-display');
    const modalLoaihangDisplay = document.getElementById('modal-loaihang-display');
    const modalNsxDisplay = document.getElementById('modal-nsx-display');
    const btnDongModalNhapKhoiLuong = document.querySelector('#modal-nhap-khoiluong .close-button');
    const modalNhapHangTitle = document.getElementById('modal-nhaphang-title'); // Thêm ID này vào <h3> trong index.php

    // --- DOM Elements - Modal Chọn Mẫu Tem ---
    const modalChonMauTem = document.getElementById('modal-chon-mau-tem');
    const selectMauTem = document.getElementById('select-mau-tem');
    const btnXacNhanInTem = document.getElementById('btn-xacnhan-in-tem');
    const btnDongModalChonMauTem = document.querySelector('#modal-chon-mau-tem .close-button');

    // --- DOM Elements - Modal Sửa Kiện Hàng ---
    const modalSuaKienHang = document.getElementById('modal-sua-kienhang');
    const btnDongModalSuaKienHang = document.querySelector('#modal-sua-kienhang .close-button');
    const formSuaKienHang = document.getElementById('form-sua-kienhang');
    const editKienHangIdInput = document.getElementById('edit-kienhang-id');
    const editLotnoInput = document.getElementById('edit-lotno');
    const editLoainhomSelect = document.getElementById('edit-loainhom');
    const editLoaihangSelect = document.getElementById('edit-loaihang');
    const editKiensoInput = document.getElementById('edit-kienso');
    const editKhoiluongInput = document.getElementById('edit-khoiluong');
    const editNsxInput = document.getElementById('edit-nsx');
    const editGhichuTextarea = document.getElementById('edit-ghichu');
    const btnLuuThaydoiKienHang = document.getElementById('btn-luu-thaydoi-kienhang');
    // Spans để hiển thị thông tin gốc trong modal sửa
    const originalLotnoSpan = document.getElementById('original-lotno');
    const originalLoainhomSpan = document.getElementById('original-loainhom');
    const originalLoaihangSpan = document.getElementById('original-loaihang');
    const originalKiensoSpan = document.getElementById('original-kienso');
    const originalKhoiluongSpan = document.getElementById('original-khoiluong');
    const originalNsxSpan = document.getElementById('original-nsx');
    const originalGhichuSpan = document.getElementById('original-ghichu');
    
    let originalItemDataForEdit = {}; // Lưu dữ liệu gốc khi mở modal sửa
    window.cachedLoaiNhomOptions = []; // Cache options để dùng trong modal sửa
    window.cachedLoaiHangOptions = [];

    const AJAX_URL = 'php/ajax_handler.php';

    // --- LOGIC TOOLTIP (Khởi tạo) ---
    initializeTooltipLogic();

    // --- SỰ KIỆN CLICK VÀO Ô LOT NO ĐỂ SỬA ---
    if (kienhangTbody) {
        kienhangTbody.addEventListener('click', function(event) {
            const targetCell = event.target.closest('td'); // Tìm ô <td> được click

            // Kiểm tra xem ô được click có class 'lotno-clickable' không
            if (targetCell && targetCell.classList.contains('lotno-clickable')) {
                const kienhangId = targetCell.dataset.id; // Lấy ID từ data-id của ô <td>
                if (!kienhangId) {
                    showCustomAlert('Không tìm thấy ID của kiện hàng.', 'error');
                    return;
                }

                // Gọi AJAX để lấy chi tiết kiện hàng từ Server
                fetch(AJAX_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=get_kien_hang_detail_for_edit&id=${kienhangId}`
                })
                .then(response => handleFetchJsonResponse(response, "lấy chi tiết kiện hàng"))
                .then(data => {
                    if (data.success && data.item) {
                        originalItemDataForEdit = data.item; // Lưu dữ liệu gốc

                        // Điền dữ liệu vào form sửa
                        editKienHangIdInput.value = originalItemDataForEdit.id;
                        editLotnoInput.value = originalItemDataForEdit.lot_no || '';
                        
                        // Populate và chọn đúng cho select Loại Nhôm và Loại Hàng
                        updateGenericSelect(editLoainhomSelect, window.cachedLoaiNhomOptions, '-- Sửa Loại Nhôm --', 'id', 'ten_loai_nhom', originalItemDataForEdit.loai_nhom_id);
                        updateGenericSelect(editLoaihangSelect, window.cachedLoaiHangOptions, '-- Sửa Loại Hàng --', 'id', 'ten_loai_hang', originalItemDataForEdit.loai_hang_id);

                        editKiensoInput.value = originalItemDataForEdit.kien_so || '';
                        editKhoiluongInput.value = originalItemDataForEdit.khoi_luong_kg || '';
                        editNsxInput.value = originalItemDataForEdit.ngay_san_xuat_raw || ''; 
                        editGhichuTextarea.value = originalItemDataForEdit.ghi_chu || '';

                        // Hiển thị thông tin gốc
                        originalLotnoSpan.textContent = originalItemDataForEdit.lot_no || 'N/A';
                        originalLoainhomSpan.textContent = originalItemDataForEdit.ten_loai_nhom || 'N/A'; 
                        originalLoaihangSpan.textContent = originalItemDataForEdit.ten_loai_hang || 'N/A'; 
                        originalKiensoSpan.textContent = originalItemDataForEdit.kien_so || 'N/A';
                        originalKhoiluongSpan.textContent = (originalItemDataForEdit.khoi_luong_kg !== null ? formatKhoiLuong(originalItemDataForEdit.khoi_luong_kg) : '0') + ' Kg';
                        originalNsxSpan.textContent = originalItemDataForEdit.ngay_san_xuat_f || 'N/A'; 
                        originalGhichuSpan.textContent = originalItemDataForEdit.ghi_chu || 'Không có';
                        
                        showModal('modal-sua-kienhang'); // Hàm showModal của bạn
                    } else {
                        showCustomAlert('Lỗi: ' + (data.message || 'Không thể lấy chi tiết kiện hàng.'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Lỗi AJAX khi lấy chi tiết kiện hàng:', error); // Log lỗi ra console để debug
                    showCustomAlert('Lỗi AJAX khi lấy chi tiết kiện hàng: ' + error.message, 'error');
                });
            }
        });
    }

    // --- XỬ LÝ SUBMIT FORM SỬA KIỆN HÀNG ---
    if (formSuaKienHang && btnLuuThaydoiKienHang) { // Kiểm tra cả nút lưu
        formSuaKienHang.addEventListener('submit', function(event) {
            event.preventDefault(); // Ngăn form submit theo cách truyền thống

            const formData = new FormData(formSuaKienHang);
            formData.append('action', 'sua_kien_hang');

            fetch(AJAX_URL, {
                method: 'POST',
                body: formData
            })
            .then(response => handleFetchJsonResponse(response, "lưu thay đổi kiện hàng"))
            .then(data => {
                if (data.success) {
                    showCustomAlert(data.message || 'Cập nhật thành công!', 'success');
                    closeModal('modal-sua-kienhang');
                    
                    // Làm mới bảng để hiển thị thay đổi
                    if (btnTimkiem) {
                        btnTimkiem.click(); // Ưu tiên tìm kiếm lại với filter hiện tại
                    } else {
                        loadInitialData(); // Hoặc tải lại dữ liệu ban đầu
                    }
                } else {
                    showCustomAlert('Lỗi cập nhật: ' + (data.message || 'Không rõ nguyên nhân.'), 'error');
                }
            })
            .catch(error => {
                console.error('Lỗi AJAX khi lưu thay đổi:', error); // Log lỗi ra console để debug
                showCustomAlert('Lỗi AJAX khi lưu thay đổi: ' + error.message, 'error');
            });
        });
    }

    // --- CÁC HÀM TIỆN ÍCH ---
    function showCustomAlert(message, type = 'info') {
        console.log(`Alert (${type}): ${message}`);
        // Tạm thời dùng alert, bạn có thể thay thế bằng modal đẹp hơn
        alert(message); 
    }

    function showCustomConfirm(message, callback) {
        if (confirm(message)) {
            callback();
        }
    }

    function showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = 'block';
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = 'none';
    }

    function formatKhoiLuong(number) {
        const num = parseFloat(number);
        if (isNaN(num) || !Number.isFinite(num)) return '0';
        if (num % 1 === 0) {
            return num.toString(); // Là số nguyên
        } else {
            return num.toFixed(2); // Có phần thập phân
        }
    }
    
    // Hàm chung để cập nhật select options
    function updateGenericSelect(selectElement, options, defaultOptionText, valueField = 'id', textField = 'name', selectedValue = null) {
        if (!selectElement) return;
        selectElement.innerHTML = `<option value="">${defaultOptionText}</option>`;
        if (options && Array.isArray(options)) {
            options.forEach(opt => {
                const optionEl = document.createElement('option');
                optionEl.value = opt[valueField];
                optionEl.textContent = opt[textField];
                if (selectedValue && String(opt[valueField]) === String(selectedValue)) {
                    optionEl.selected = true;
                }
                selectElement.appendChild(optionEl);
            });
        }
    }

    function updateLoaiNhomSelect(options, selectedId = null) {
        updateGenericSelect(loainhomSelect, options, '-- Chọn loại nhôm --', 'id', 'ten_loai_nhom', selectedId);
        if (editLoainhomSelect) {
            updateGenericSelect(editLoainhomSelect, options, '-- Sửa Loại Nhôm --', 'id', 'ten_loai_nhom', originalItemDataForEdit.loai_nhom_id);
        }
    }
    
    function updateLoaiHangSelect(options, selectedId = null) {
        updateGenericSelect(loaihangSelectMain, options, '-- Chọn loại hàng --', 'id', 'ten_loai_hang', selectedId);
         if (editLoaihangSelect) {
            updateGenericSelect(editLoaihangSelect, options, '-- Sửa Loại Hàng --', 'id', 'ten_loai_hang', originalItemDataForEdit.loai_hang_id);
        }
    }

    // --- RENDER BẢNG DỮ LIỆU ---
    function renderKienHangTable(data) {
        if (!kienhangTbody) return;
        kienhangTbody.innerHTML = '';

        const tableHead = document.querySelector('#tab-content-chitiet table thead tr'); // Nhắm mục tiêu chính xác hơn
        let hasLoaiHangColumnInHtml = !!(tableHead && tableHead.querySelector('.th-loaihang')); 
        const dataHasLoaiHang = data.length > 0 && data[0] && data[0].hasOwnProperty('ten_loai_hang');
        
        let currentEffectiveColspan = 9; // Giá trị mặc định cho bảng của bạn

        if (tableHead) { 
            // Xử lý cột Loại Hàng (thêm/bớt tự động)
            if (dataHasLoaiHang && !hasLoaiHangColumnInHtml) {
                const thLoaiHang = document.createElement('th');
                thLoaiHang.textContent = 'Loại Hàng';
                thLoaiHang.classList.add('th-loaihang');
                const loaiNhomHeader = Array.from(tableHead.children).find(th => th.textContent.trim() === 'Loại Nhôm');
                if (loaiNhomHeader && loaiNhomHeader.nextSibling) {
                    tableHead.insertBefore(thLoaiHang, loaiNhomHeader.nextSibling);
                } else { 
                    tableHead.insertBefore(thLoaiHang, tableHead.children[4] || null);
                }
                hasLoaiHangColumnInHtml = true;
            } else if (!dataHasLoaiHang && hasLoaiHangColumnInHtml) {
                const thToRemove = tableHead.querySelector('.th-loaihang');
                if(thToRemove) { tableHead.removeChild(thToRemove); hasLoaiHangColumnInHtml = false; }
            }
            currentEffectiveColspan = tableHead.children.length;
        }


        if (!data || data.length === 0) {
            kienhangTbody.innerHTML = `<tr><td colspan="${currentEffectiveColspan}" class="no-data">Không có dữ liệu kiện hàng.</td></tr>`;
            if (headerCheckbox) headerCheckbox.checked = false;
            updateCheckboxStates();
            return;
        }
        
        data.forEach((item, index) => {
            const row = kienhangTbody.insertRow();
            const khoiLuongNum = (item.khoi_luong_kg !== null && item.khoi_luong_kg !== undefined) ? parseFloat(item.khoi_luong_kg) : 0;
            const khoiLuongDisplay = `${formatKhoiLuong(khoiLuongNum)} Kg`;
            
            let loaiHangCellHtml = '';
            if (hasLoaiHangColumnInHtml) { 
                loaiHangCellHtml = `<td>${item.ten_loai_hang || ''}</td>`; 
            }

            // === TÍCH HỢP LOGIC TOOLTIP VÀ SỬA LỖI ===
            let lotNoCellClasses = ['lotno-clickable', 'lotno-hover-trigger']; // Thêm class cho tooltip
            let lotNoTitle = "Nhấp để sửa thông tin"; // Tooltip mặc định

            if (item.ghi_chu && item.ghi_chu.trim() !== "") {
                lotNoCellClasses.push("lotno-edited"); 
                lotNoTitle = item.ghi_chu.replace(/"/g, '&quot;').replace(/\n/g, '&#xA;');
            }
            
            // Thêm data-lotno cho tooltip
            let lotNoCellContent = `<td class="${lotNoCellClasses.join(' ')}" data-id="${item.id}" data-lotno="${item.lot_no}" title="${lotNoTitle}">${item.lot_no || ''}</td>`;
            // === KẾT THÚC TÍCH HỢP ===


            let cellsHtml = `
                <td><input type="checkbox" class="row-checkbox" data-id="${item.id}" data-weight="${khoiLuongNum}"></td>
                <td>${index + 1}</td>
                ${lotNoCellContent}
                <td>${item.ten_loai_nhom || ''}</td>
                ${loaiHangCellHtml}
                <td>${item.kien_so || ''}</td>
                <td>${khoiLuongDisplay}</td>
                <td>${item.ngay_san_xuat_f || ''}</td>
                <td class="status-${item.trang_thai}">${item.trang_thai === 'ton_kho' ? 'Tồn kho' : (item.trang_thai === 'da_xuat' ? 'Đã xuất' : item.trang_thai)}</td>
            `;
            row.innerHTML = cellsHtml;
            row.dataset.itemId = item.id; 
        });
        updateCheckboxStates();
    }

    // --- HÀM XỬ LÝ FETCH JSON ---
    async function handleFetchJsonResponse(response, actionDescription) {
        if (!response.ok) {
            let errorText = `Lỗi HTTP ${response.status}: ${response.statusText}`;
            try { 
                const serverError = await response.text();
                errorText = serverError || errorText; 
            } catch (e) { /* Bỏ qua */ }
            console.error(`Lỗi HTTP khi ${actionDescription}:`, errorText);
            throw new Error(`Lỗi HTTP khi ${actionDescription}. Chi tiết: ${errorText}`);
        }
        try {
            return await response.json();
        } catch (jsonError) {
            console.error(`Lỗi phân tích JSON khi ${actionDescription}:`, jsonError);
            const rawResponse = await response.text().catch(() => "Không thể đọc raw response.");
            console.error(`Server raw response (${actionDescription}):`, rawResponse);
            throw new Error(`Server không trả về JSON hợp lệ khi ${actionDescription}. Raw: ${rawResponse}`);
        }
    }

    // --- TẢI DỮ LIỆU BAN ĐẦU ---
    function loadInitialData() {
        const colspan = document.querySelector('#tab-content-chitiet table thead tr')?.children.length || 9;
        if (kienhangTbody) kienhangTbody.innerHTML = `<tr><td colspan="${colspan}" class="no-data">Đang tải dữ liệu...</td></tr>`;

        fetch(`${AJAX_URL}?action=load_initial_data`)
            .then(response => handleFetchJsonResponse(response, "tải dữ liệu ban đầu"))
            .then(data => {
                if (data.success) {
                    updateLoaiNhomSelect(data.loai_nhom_options);
                    updateLoaiHangSelect(data.loai_hang_options);
                    renderKienHangTable(data.kien_hang || []); 
                    window.cachedLoaiNhomOptions = data.loai_nhom_options || [];
                    window.cachedLoaiHangOptions = data.loai_hang_options || [];
                    
                    // THÊM: Cache dữ liệu thành phần
                    cacheCompositionData(data.thanh_phan); 

                } else {
                    showCustomAlert('Lỗi tải dữ liệu ban đầu: ' + (data.message || 'Không rõ nguyên nhân'), 'error');
                    updateLoaiNhomSelect(data.loai_nhom_options || []); 
                    updateLoaiHangSelect(data.loai_hang_options || []);
                    renderKienHangTable([]); 
                }
            })
            .catch(error => {
                showCustomAlert('Lỗi nghiêm trọng khi tải dữ liệu: ' + error.message, 'error');
                renderKienHangTable([]); 
            })
            .finally(() => {
                updateCheckboxStates();
            });
    }
    
    // --- XỬ LÝ CÁC MODAL (Đóng) ---
    if (btnDongModalThemLoaiNhom) btnDongModalThemLoaiNhom.addEventListener('click', () => closeModal('modal-them-loainhom'));
    if (btnDongModalThemLoaiHang) btnDongModalThemLoaiHang.addEventListener('click', () => closeModal('modal-them-loaihang'));
    if (btnDongModalNhapKhoiLuong) btnDongModalNhapKhoiLuong.addEventListener('click', () => closeModal('modal-nhap-khoiluong'));
    if (btnDongModalChonMauTem) btnDongModalChonMauTem.addEventListener('click', () => closeModal('modal-chon-mau-tem'));
    if (btnDongModalSuaKienHang) btnDongModalSuaKienHang.addEventListener('click', () => closeModal('modal-sua-kienhang'));

    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target.id);
        }
    });

    // --- THÊM LOẠI NHÔM ---
    if (btnThemLoainhomModal && tenLoainhomMoiInput && btnLuuLoainhom) {
        btnThemLoainhomModal.addEventListener('click', () => { tenLoainhomMoiInput.value = ''; showModal('modal-them-loainhom'); tenLoainhomMoiInput.focus(); });
        btnLuuLoainhom.addEventListener('click', () => {
            const tenMoi = tenLoainhomMoiInput.value.trim();
            if (!tenMoi) { showCustomAlert('Tên loại nhôm không được để trống.', 'error'); return; }
            const formData = new FormData(); formData.append('action', 'them_loai_nhom'); formData.append('ten_loai_nhom', tenMoi);
            fetch(AJAX_URL, { method: 'POST', body: formData })
                .then(response => handleFetchJsonResponse(response, "thêm loại nhôm"))
                .then(data => {
                    showCustomAlert(data.message || (data.success ? 'Thành công' : 'Lỗi'), data.success ? 'success' : 'error');
                    if (data.success) { 
                        updateLoaiNhomSelect(data.loai_nhom_options, data.new_loai_nhom?.id); 
                        window.cachedLoaiNhomOptions = data.loai_nhom_options || []; // Cập nhật cache
                        closeModal('modal-them-loainhom'); 
                    }
                })
                .catch(error => { showCustomAlert('Lỗi AJAX thêm loại nhôm: ' + error.message, 'error'); });
        });
    }

    // --- THÊM LOẠI HÀNG ---
    if (btnThemLoaihangModal && tenLoaihangMoiInput && btnLuuLoaihang) {
        btnThemLoaihangModal.addEventListener('click', () => { tenLoaihangMoiInput.value = ''; showModal('modal-them-loaihang'); tenLoaihangMoiInput.focus(); });
        btnLuuLoaihang.addEventListener('click', () => {
            const tenMoi = tenLoaihangMoiInput.value.trim();
            if (!tenMoi) { showCustomAlert('Tên loại hàng không được để trống.', 'error'); return; }
            const formData = new FormData(); formData.append('action', 'them_loai_hang'); formData.append('ten_loai_hang', tenMoi);
            fetch(AJAX_URL, { method: 'POST', body: formData })
                .then(response => handleFetchJsonResponse(response, "thêm loại hàng"))
                .then(data => {
                    showCustomAlert(data.message || (data.success ? 'Thành công' : 'Lỗi'), data.success ? 'success' : 'error');
                    if (data.success) { 
                        updateLoaiHangSelect(data.loai_hang_options, data.new_loai_hang?.id); 
                        window.cachedLoaiHangOptions = data.loai_hang_options || []; // Cập nhật cache
                        closeModal('modal-them-loaihang'); 
                    }
                })
                .catch(error => { showCustomAlert('Lỗi AJAX thêm loại hàng: ' + error.message, 'error'); });
        });
    }
    
    // --- TÌM KIẾM KIỆN HÀNG ---
    if (btnTimkiem) {
        btnTimkiem.addEventListener('click', () => {
            const formData = new FormData(); formData.append('action', 'tim_kiem_kien_hang');
            if (lotnoInput) formData.append('lotno', lotnoInput.value.trim());
            if (loainhomSelect) formData.append('loai_nhom_id', loainhomSelect.value);
            if (loaihangSelectMain) formData.append('loai_hang_id', loaihangSelectMain.value);
            if (nsxInput) formData.append('nsx', nsxInput.value);
            if (trangthaiSelect) formData.append('trang_thai', trangthaiSelect.value);
            
            const colspan = document.querySelector('#tab-content-chitiet table thead tr')?.children.length || 9;
            if (kienhangTbody) kienhangTbody.innerHTML = `<tr><td colspan="${colspan}" class="no-data">Đang tìm kiếm...</td></tr>`;
            
            fetch(AJAX_URL, { method: 'POST', body: formData })
                .then(response => handleFetchJsonResponse(response, "tìm kiếm kiện hàng"))
                .then(data => {
                    if (data.success) { 
                        renderKienHangTable(data.kien_hang || []); 
                        
                        // THÊM: Cache dữ liệu thành phần
                        cacheCompositionData(data.thanh_phan); 

                    } else { 
                        showCustomAlert('Lỗi tìm kiếm: ' + (data.message || 'Không rõ lỗi'), 'error'); 
                        renderKienHangTable([]); 
                    }
                })
                .catch(error => { showCustomAlert('Lỗi AJAX tìm kiếm: ' + error.message, 'error'); renderKienHangTable([]); });
        });
    }

    // --- NHẬP HÀNG (Mở Modal Nhập Khối Lượng) ---
    if (btnNhaphangModal && modalKienBatdauInput && formNhapKhoiluong && soluongInput) {
        let generateWeightInputsScoped = null; 
        btnNhaphangModal.addEventListener('click', () => {
            const lotno = lotnoInput.value.trim(); 
            const loainhomId = loainhomSelect.value;
            const loainhomText = loainhomSelect.options[loainhomSelect.selectedIndex]?.text || 'N/A';
            const loaihangId = loaihangSelectMain.value; 
            const loaihangText = loaihangSelectMain.options[loaihangSelectMain.selectedIndex]?.text || 'N/A';
            const nsx = nsxInput.value; 
            const soluongGoc = parseInt(soluongInput.value);

            // Chế độ 1: Chỉ nhập Lot No (Chỉ nhập thành phần)
            if (lotno && !soluongGoc) { 
                if (modalNhapHangTitle) modalNhapHangTitle.innerHTML = '<i class="fas fa-flask"></i> Nhập/Sửa Thành Phần Hóa Học';
                if (modalLotnoDisplay) modalLotnoDisplay.textContent = lotno;
                if (modalLoainhomDisplay) modalLoainhomDisplay.textContent = 'N/A';
                if (modalLoaihangDisplay) modalLoaihangDisplay.textContent = 'N/A';
                if (modalNsxDisplay) modalNsxDisplay.textContent = 'N/A';
                
                formNhapKhoiluong.innerHTML = ''; // Xóa form
                modalNhapKhoiLuong.classList.add('composition-only'); // Thêm class để ẩn
                
                // TODO: Tải thành phần hiện tại của Lot No nếu có? (Nâng cao)
                // Tạm thời reset
                document.querySelectorAll('#modal-nhap-khoiluong .thanhphan-input').forEach(input => input.value = 0);
                
                showModal('modal-nhap-khoiluong');
                return; // Dừng
            }

            // Chế độ 2: Nhập hàng đầy đủ
            if (!lotno || !loainhomId || !loaihangId || !nsx || isNaN(soluongGoc) || soluongGoc <= 0) {
                showCustomAlert('Vui lòng điền đầy đủ thông tin (Lot No, Loại Nhôm, Loại Hàng, NSX, Số Lượng Kiện) để nhập hàng.', 'error');
                return;
            }
            
            if (modalNhapHangTitle) modalNhapHangTitle.innerHTML = '<i class="fas fa-weight-hanging"></i> Nhập Khối Lượng & Thành Phần';
            if (modalLotnoDisplay) modalLotnoDisplay.textContent = lotno;
            if (modalLoainhomDisplay) modalLoainhomDisplay.textContent = loainhomText;
            if (modalLoaihangDisplay) modalLoaihangDisplay.textContent = loaihangText; 
            if (modalNsxDisplay) { try { const d = new Date(nsx); modalNsxDisplay.textContent = `${d.getDate().toString().padStart(2,'0')}/${(d.getMonth()+1).toString().padStart(2,'0')}/${d.getFullYear()}`; } catch (e) { modalNsxDisplay.textContent = nsx; } }
            
            modalNhapKhoiLuong.classList.remove('composition-only'); // Xóa class
            modalKienBatdauInput.value = '1'; 
            
            generateWeightInputsScoped = function() { 
                const kienBatDauVal = parseInt(modalKienBatdauInput.value); 
                formNhapKhoiluong.innerHTML = '';
                if (isNaN(soluongGoc) || soluongGoc <= 0) { formNhapKhoiluong.innerHTML = '<p style="color:red; grid-column: 1 / -1;">Số lượng kiện không hợp lệ.</p>'; return; }
                if (isNaN(kienBatDauVal) || kienBatDauVal <= 0) { formNhapKhoiluong.innerHTML = '<p style="color:red; grid-column: 1 / -1;">Số kiện bắt đầu không hợp lệ.</p>'; return; }
                for (let i = 0; i < soluongGoc; i++) {
                    const kienSoFormatted = String(kienBatDauVal + i).padStart(2, '0');
                    const itemDiv = document.createElement('div'); itemDiv.classList.add('kien-item');
                    itemDiv.innerHTML = `<label for="kl-kien-${kienSoFormatted}">Kiện ${kienSoFormatted}:</label><input type="number" step="0.01" id="kl-kien-${kienSoFormatted}" data-kien-so="${kienSoFormatted}" placeholder="Nhập KL (Kg)" required>`;
                    formNhapKhoiluong.appendChild(itemDiv);
                }
            };
            generateWeightInputsScoped(); 
            modalKienBatdauInput.oninput = generateWeightInputsScoped; 
            
            // Reset thành phần
            document.querySelectorAll('#modal-nhap-khoiluong .thanhphan-input').forEach(input => input.value = 0);
            
            showModal('modal-nhap-khoiluong');
        });
    }

    // --- XÁC NHẬN NHẬP HÀNG (Từ Modal Nhập Khối Lượng) ---
    if (btnXacnhanNhaphang && formNhapKhoiluong) {
        btnXacnhanNhaphang.addEventListener('click', () => {
            const lotno = document.getElementById('lotno-input').value.trim(); 
            const loainhomId = document.getElementById('loainhom-select').value;
            const loaihangId = document.getElementById('loaihang-select-main').value;
            const nsx = document.getElementById('nsx-input').value;
            
            // === Thu thập dữ liệu thành phần (LUÔN LUÔN THU THẬP) ===
            const compositionData = {};
            const inputsThanhPhan = document.querySelectorAll('#modal-nhap-khoiluong .thanhphan-input');
            inputsThanhPhan.forEach(input => {
                const name = input.name; 
                const value = parseFloat(input.value) || 0;
                if (name) { 
                    compositionData[name] = value;
                }
            });
            const thanhPhanJson = JSON.stringify(compositionData);

            // === KIỂM TRA CHẾ ĐỘ ===
            const isCompositionOnly = modalNhapKhoiLuong.classList.contains('composition-only');
            
            const formData = new FormData();
            formData.append('lotno', lotno);
            formData.append('thanh_phan', thanhPhanJson);

            if (isCompositionOnly) {
                // --- CHẾ ĐỘ 1: CHỈ LƯU THÀNH PHẦN ---
                if (!lotno) {
                    showCustomAlert('Vui lòng nhập Lot No.', 'error');
                    return;
                }
                formData.append('action', 'luu_thanh_phan'); 
                
            } else {
                // --- CHẾ ĐỘ 2: LƯU HÀNG ĐẦY ĐỦ ---
                const dsKhoiLuong = []; 
                const inputFields = formNhapKhoiluong.querySelectorAll('input[type="number"]');
                let isValid = true;
                
                if (inputFields.length === 0) { showCustomAlert('Không có thông tin kiện hàng để nhập.', 'error'); return; }
                
                inputFields.forEach(input => {
                    if (!isValid) return; 
                    const kienSo = input.dataset.kienSo; const khoiLuong = parseFloat(input.value);
                    if (isNaN(khoiLuong) || khoiLuong <= 0) { showCustomAlert(`Khối lượng kiện ${kienSo} không hợp lệ.`, 'error'); input.focus(); isValid = false; } 
                    else { dsKhoiLuong.push({ kien_so: kienSo, khoi_luong: khoiLuong }); }
                });
                if (!isValid) return;
                if (dsKhoiLuong.length === 0 && inputFields.length > 0 ) { showCustomAlert('Vui lòng nhập khối lượng hợp lệ cho các kiện.', 'error'); return; }

                // Thêm các trường còn lại
                formData.append('action', 'nhap_hang');
                formData.append('loai_nhom_id', loainhomId); 
                formData.append('loai_hang_id', loaihangId);
                formData.append('nsx', nsx); 
                formData.append('ds_khoi_luong', JSON.stringify(dsKhoiLuong));
            }
            
            // --- GỬI FETCH (Dùng chung cho cả 2 chế độ) ---
            fetch(AJAX_URL, { method: 'POST', body: formData })
                .then(response => handleFetchJsonResponse(response, "xác nhận nhập hàng/thành phần"))
                .then(data => {
                    showCustomAlert(data.message || (data.success ? 'Thành công' : 'Lỗi'), data.success ? 'success' : 'error');
                    if (data.success) { 
                        closeModal('modal-nhap-khoiluong'); 
                        if (btnTimkiem) btnTimkiem.click(); else loadInitialData(); 
                        if (soluongInput) soluongInput.value = ''; // Reset số lượng kiện
                    }
                })
                .catch(error => { showCustomAlert('Lỗi AJAX: ' + error.message, 'error'); });
        });
    }
    
    // --- XUẤT HÀNG (Cập nhật DB + Xuất Excel) ---
    if (btnXuathang && kienhangTbody) {
        btnXuathang.addEventListener('click', () => {
            const selectedIds = Array.from(kienhangTbody.querySelectorAll('.row-checkbox:checked')).map(cb => cb.dataset.id);
            if (selectedIds.length === 0) { showCustomAlert('Vui lòng chọn ít nhất một kiện hàng để xuất.', 'info'); return; }

            showCustomConfirm(`Bạn có chắc chắn muốn XUẤT ${selectedIds.length} kiện hàng (trạng thái sẽ đổi) và tải file Excel không?`, () => {
                const formData = new FormData();
                formData.append('action', 'xuat_hang_excel'); 
                formData.append('kien_ids', JSON.stringify(selectedIds));

                fetch(AJAX_URL, { method: 'POST', body: formData })
                .then(async response => {
                    if (!response.ok) {
                        let errorData = { message: `Lỗi HTTP ${response.status}` };
                        try { const text = await response.text(); errorData = JSON.parse(text); } 
                        catch (e) { const text = await response.text(); errorData.message = `Lỗi HTTP ${response.status}: ${text||response.statusText}`; }
                        throw new Error(errorData.message);
                    }
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet") !== -1) {
                        return response.blob().then(blob => ({ blob, headers: response.headers, success: true, isFile: true, message: `Đã xuất ${selectedIds.length} kiện và tải file Excel.` }));
                    } else {
                        return response.json().then(data => {
                            if (!data.success) { throw new Error(data.message || "Lỗi không xác định."); }
                            return { success: false, message: "Phản hồi không mong đợi.", isFile: false};
                        });
                    }
                })
                .then(result => {
                    if (result.isFile && result.blob) {
                        const url = window.URL.createObjectURL(result.blob);
                        const a = document.createElement('a'); a.style.display = 'none'; a.href = url;
                        let filename = "XuatHang.xlsx";
                        const disposition = result.headers.get('Content-Disposition');
                        if (disposition && disposition.indexOf('attachment') !== -1) {
                            const fnRegex = /filename[^;=\n]*=(?:(?:"((?:[^"]|\\")*)")|([^;\n]*))/;
                            const matches = fnRegex.exec(disposition);
                            if (matches != null && (matches[1] || matches[2])) { filename = matches[1] ? matches[1].replace(/\\"/g, '"') : matches[2];}
                        }
                        a.download = filename; document.body.appendChild(a); a.click();
                        window.URL.revokeObjectURL(url); a.remove();
                    }
                    showCustomAlert(result.message, result.success ? 'success' : 'error');
                    if (result.success && result.isFile) { if (btnTimkiem) btnTimkiem.click(); else loadInitialData(); }
                })
                .catch(error => { showCustomAlert('Lỗi khi xuất hàng & Excel: ' + error.message, 'error'); });
            });
        });
    }

    // --- NÚT XUẤT EXCEL MỚI (Chỉ xuất Excel, không đổi trạng thái) ---
    if (btnXuatExcelMoi && kienhangTbody) {
        btnXuatExcelMoi.addEventListener('click', () => {
            const selectedIds = Array.from(kienhangTbody.querySelectorAll('.row-checkbox:checked')).map(cb => cb.dataset.id);
            if (selectedIds.length === 0) {
                showCustomAlert('Vui lòng chọn ít nhất một kiện hàng để xuất ra Excel.', 'info');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'export_selected_to_excel'); // << ACTION MỚI
            formData.append('kien_ids', JSON.stringify(selectedIds));

            fetch(AJAX_URL, {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                if (!response.ok) {
                    let errorData = { message: `Lỗi HTTP ${response.status}: ${response.statusText}` };
                    try {
                        const text = await response.text();
                        try { errorData = JSON.parse(text); } catch (e) { errorData.message = `Lỗi HTTP ${response.status}: ${text || response.statusText}`; }
                    } catch (e) { /* ignore */ }
                    throw new Error(errorData.message || `Lỗi HTTP ${response.status}`);
                }
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet") !== -1) {
                    return response.blob().then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = url;
                        let filename = "DuLieuKienHang.xlsx";
                        const disposition = response.headers.get('Content-Disposition');
                        if (disposition && disposition.indexOf('attachment') !== -1) {
                            const filenameRegex = /filename[^;=\n]*=(?:(?:"((?:[^"]|\\")*)")|([^;\n]*))/;
                            const matches = filenameRegex.exec(disposition);
                            if (matches != null && (matches[1] || matches[2])) {
                                filename = matches[1] ? matches[1].replace(/\\"/g, '"') : matches[2];
                            }
                        }
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        a.remove();
                        return { success: true, message: `Đã tải file Excel cho ${selectedIds.length} kiện.`, isFile: true };
                    });
                } else {
                    return response.json().then(data => {
                        if (!data.success) { throw new Error(data.message || "Lỗi không xác định từ server."); }
                        return { success: false, message: "Phản hồi không mong đợi từ server.", isFile: false };
                    });
                }
            })
            .then(result => { 
                showCustomAlert(result.message, result.success ? 'success' : 'error');
            })
            .catch(error => {
                console.error('Lỗi khi xuất Excel (chỉ xuất):', error);
                showCustomAlert('Lỗi khi xuất Excel (chỉ xuất): ' + error.message, 'error');
            });
        });
    }

    // --- IN TEM ---
    if (btnIntem && kienhangTbody && modalChonMauTem && selectMauTem && btnXacNhanInTem) {
        btnIntem.addEventListener('click', () => {
            const selectedIds = Array.from(kienhangTbody.querySelectorAll('.row-checkbox:checked')).map(cb => cb.dataset.id);
            if (selectedIds.length === 0) { showCustomAlert('Vui lòng chọn ít nhất một kiện hàng để in tem.', 'info'); return; }
            selectMauTem.value = 'mau1'; showModal('modal-chon-mau-tem');
        });
        btnXacNhanInTem.addEventListener('click', () => {
            const selectedIds = Array.from(kienhangTbody.querySelectorAll('.row-checkbox:checked')).map(cb => cb.dataset.id);
            const mauTemDaChon = selectMauTem.value;
            if (selectedIds.length === 0) { showCustomAlert('Vui lòng chọn lại kiện hàng để in tem.', 'info'); closeModal('modal-chon-mau-tem'); return; }
            if (!mauTemDaChon) { showCustomAlert('Vui lòng chọn một mẫu tem.', 'error'); return; }
            
            closeModal('modal-chon-mau-tem');
            fetchKienHangDetailsAndPrint(selectedIds, mauTemDaChon); // Chỉ cần gọi hàm này
        });
    }

    function fetchKienHangDetailsAndPrint(selectedIds, mauTem) {
        const formData = new FormData(); formData.append('action', 'get_kien_hang_details_for_printing'); 
        formData.append('kien_ids', JSON.stringify(selectedIds)); formData.append('mau_tem', mauTem);
        fetch(AJAX_URL, { method: 'POST', body: formData })
            .then(response => handleFetchJsonResponse(response, `lấy chi tiết kiện hàng cho mẫu ${mauTem}`))
            .then(data => {
                if (data.success && data.items && data.items.length > 0) {
                    
                    // Sửa lỗi: Truyền data.thanh_phan vào hàm in
                    if (mauTem === 'mau1') { generateAndPrintHtmlForTem1(data.items); } 
                    else if (mauTem === 'mau2') { generateAndPrintHtmlForTem2(data.items); }
                    else if (mauTem === 'mau3_A5') { generateAndPrintHtmlForTem3(data.items); }
                    else if (mauTem === 'mau4_A5') { 
                        generateAndPrintHtmlForTem4(data.items, data.thanh_phan); // Sửa ở đây
                    }
                } else { showCustomAlert('Lỗi lấy dữ liệu in: ' + (data.message || 'Không có dữ liệu'), 'error'); }
            })
            .catch(error => { showCustomAlert(error.message, 'error'); });
    } // Đóng hàm fetchKienHangDetailsAndPrint

    
    // --- CÁC HÀM IN TEM (1, 2, 3) ---
    function generateAndPrintHtmlForTem1(itemsData) {
        let printHtml = `<html><head><title>In Tem Kiện Hàng - ADC12</title><style>
        @page {
            size: A4 portrait;
            margin: 10mm; /* Giữ nguyên lề 10mm A4 */
        }
        body {
            margin: 0; /* Bỏ lề mặc định của body */
            font-family: Arial, sans-serif;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .page {
            width: 190mm; /* (210mm - 2*10mm lề) */
            height: 277mm; /* (297mm - 2*10mm lề) */
            display: grid;
            grid-template-columns: 93mm 93mm; /* 2 cột, mỗi cột rộng 93mm */
            grid-template-rows: 65mm 65mm 65mm 65mm; /* 4 hàng, mỗi hàng cao 65mm */
            gap: 0mm; 
            justify-content: flex-start;
            align-content: flex-start;
            page-break-after: always;
            box-sizing: border-box;
        }
        .page:last-child {
            page-break-after: avoid;
        }
        .label-item {
            width: 93mm; 
            height: 65mm; 
            border: 0.5px solid #ccc !important; /* Thêm !important */
            padding: 4mm; 
            box-sizing: border-box; 
            overflow: hidden;
            display: flex;
            flex-direction: column;
            font-size: 10pt; 
        }
        .label-item div { line-height: 1.3; }
        .label-item .spec,
        .label-item .lot-no,
        .label-item .bundle-no,
        .label-item .color-code,
        .label-item .net-weight,
        .label-item .supplier {
            font-size: 16.5pt; 
            margin-bottom: 2.3mm; 
            text-align: left;
        }
        .label-item .spec { font-weight: bold; }
        .label-item .supplier { margin-bottom: 0; }
        .label-item.empty-placeholder { border: none !important; }
        </style></head><body>`;
        const itemsPerPage = 8;
        for (let i = 0; i < itemsData.length; i += itemsPerPage) {
            printHtml += '<div class="page">';
            const pageItems = itemsData.slice(i, i + itemsPerPage);
            pageItems.forEach(item => {
                const netWeightFormatted = formatKhoiLuong(item.khoi_luong_kg);
                printHtml += `<div class="label-item"><div class="spec">SPEC: ADC12</div><div class="lot-no">LOT No: ${item.lot_no || ''}</div><div class="bundle-no">Bundle No: ${item.kien_so || ''}</div><div class="color-code">Color code: Màu đỏ</div><div class="net-weight">Net Weight: ${netWeightFormatted} kg</div><div class="supplier">Supplier: INNOCO</div></div>`;
            });
            if (pageItems.length < itemsPerPage) { for (let j = 0; j < (itemsPerPage - pageItems.length); j++) { printHtml += '<div class="label-item empty-placeholder"></div>'; } }
            printHtml += '</div>';
        }
        printHtml += `</body></html>`;
        const printWindow = window.open('', '_blank', 'height=800,width=800');
        if (printWindow) { printWindow.document.write(printHtml); printWindow.document.close(); printWindow.focus(); setTimeout(() => { printWindow.print(); }, 750); } 
        else { showCustomAlert('Không thể mở cửa sổ in. Vui lòng kiểm tra cài đặt chặn popup của trình duyệt.', 'error'); }
    } // Đóng hàm generateAndPrintHtmlForTem1

    function generateAndPrintHtmlForTem2(itemsData) {
        let printHtml = `<html><head><title>In Tem Kiện Hàng - Mẫu 2</title><style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .page {
            width: 190mm; 
            height: 277mm; 
            display: grid;
            grid-template-columns: 93mm 93mm; 
            grid-template-rows: 65mm 65mm 65mm 65mm; 
            gap: 0mm; 
            justify-content: flex-start; 
            align-content: flex-start; 
            page-break-after: always;
            box-sizing: border-box;
        }
        .page:last-child {
            page-break-after: avoid;
        }
        .label-item {
            width: 93mm; 
            height: 65mm; 
            border: 0.5px solid #666 !important; /* Thêm !important */
            padding: 3mm 4mm; 
            box-sizing: border-box;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: flex-start; 
            font-size: 9pt; 
        }
        .label-item .model,
        .label-item .lot-no,
        .label-item .bundle-no,
        .label-item .so-thoi,
        .label-item .net-weight,
        .label-item .ngay-sx {
            font-size: 15pt; 
            border-bottom: 1px solid #333 !important; /* Thêm !important */
            padding-bottom: 1mm; 
            margin-bottom: 1.5mm; 
            line-height: 1.2; 
        }
        .label-item .model { margin-bottom: 2mm; }
        .label-item .ngay-sx { margin-bottom: 0; }
        .label-item .label-title {
            display: inline-block;
            width: 100px; 
            font-weight: normal;
            font-size: 0.85em; 
            padding-right: 4px;
        }
        .label-item .label-value {
            font-weight: bold;
            font-size: 1em; 
        }
        .label-item.empty-placeholder {
            border: none !important;
        }
        </style></head><body>`;
        const itemsPerPage = 8;
        for (let i = 0; i < itemsData.length; i += itemsPerPage) {
            printHtml += '<div class="page">';
            const pageItems = itemsData.slice(i, i + itemsPerPage);
            pageItems.forEach(item => {
                const netWeightFormatted = formatKhoiLuong(item.khoi_luong_kg);
                let ngaySanXuatFormatted = item.ngay_san_xuat_f || 'N/A';
                if (item.ngay_san_xuat && !item.ngay_san_xuat_f) {
                    try { const parts = item.ngay_san_xuat.split('-'); if (parts.length === 3) { ngaySanXuatFormatted = `${parts[2]}/${parts[1]}/${parts[0]}`; } else { ngaySanXuatFormatted = new Date(item.ngay_san_xuat.replace(/-/g, '/')).toLocaleDateString('vi-VN', {day: '2-digit', month: '2-digit', year: 'numeric'});}} catch(e) { console.error("Lỗi định dạng NSX cho tem 2: ", e); }
                }

                printHtml += '<div class="label-item">';
                printHtml += `<div class="model"><span class="label-title">Model:</span><span class="label-value">${item.ten_loai_nhom || 'N/A'}</span></div>`;
                printHtml += `<div class="lot-no"><span class="label-title">Lotno:</span><span class="label-value">${item.lot_no || item.LOTNO || 'N/A'}</span></div>`;
                printHtml += `<div class="bundle-no"><span class="label-title">Số kiện:</span><span class="label-value">${item.kien_so || 'N/A'}</span></div>`;
                printHtml += `<div class="so-thoi"><span class="label-title">Số thỏi:</span><span class="label-value">${item.so_thoi !== null && item.so_thoi !== undefined ? item.so_thoi : 'N/A'}</span></div>`;
                printHtml += `<div class="net-weight"><span class="label-title">Khối lượng:</span><span class="label-value">${netWeightFormatted} ${netWeightFormatted !== 'N/A' ? 'Kg' : ''}</span></div>`;
                printHtml += `<div class="ngay-sx"><span class="label-title">NSX:</span><span class="label-value">${ngaySanXuatFormatted}</span></div>`;
                printHtml += '</div>'; 
            });
            if (pageItems.length < itemsPerPage) {
                for (let j = 0; j < (itemsPerPage - pageItems.length); j++) {
                    printHtml += '<div class="label-item empty-placeholder"></div>';
                }
            }
            printHtml += '</div>'; // Kết thúc một trang .page
        }
        printHtml += `</body></html>`;
        const printWindow = window.open('', '_blank', 'height=800,width=800');
        if (printWindow) { printWindow.document.write(printHtml); printWindow.document.close(); printWindow.focus(); setTimeout(() => { printWindow.print(); }, 750); } 
        else { showCustomAlert('Không thể mở cửa sổ in. Vui lòng kiểm tra cài đặt chặn popup của trình duyệt.', 'error'); }
    } // Đóng hàm generateAndPrintHtmlForTem2

    function generateAndPrintHtmlForTem3(itemsData) {
        let printHtml = `<html><head><title>In Tem A5 - Landscape</title><style>
        body { 
            margin: 0; 
            font-family: Arial, sans-serif; 
            -webkit-print-color-adjust: exact !important; 
            print-color-adjust: exact !important; 
        }
        @page { 
            size: A5 landscape; 
            margin: 5mm;
            
        }
        .label-page-a5 {
            width: 100%;
            height: auto; 
            box-sizing: border-box;
            font-size: 16.5pt; 
            line-height: 1.6; 
            page-break-after: always; 
        }
        .label-page-a5:last-child { 
            page-break-after: avoid; 
        }
        .label-page-a5 p { 
            margin: 3mm 0;
            display: flex; 
            align-items: flex-start;
        }
        .data-label {
            width: 205px; 
            font-weight: normal; 
            flex-shrink: 0; 
            padding-right: 5px;
        }
        .data-value {
            font-weight: normal; 
            flex: 1; 
        }
        .item-details { 
            margin-top: 10mm; 
        }
        .separator { 
            border: none; 
            border-top: 0.5px solid #555 !important; /* Thêm !important */
            margin: 5mm 0; 
        }
        </style></head><body>`;

        itemsData.forEach(item => {
            const companyName = "CÔNG TY TNHH INNOCO VINA";
            const companyAddress = "Số nhà 402, đường Tôn Đức Thắng, Phường Hồng Tiến, Thành phố Phổ Yên, Tỉnh Thái Nguyên, Việt Nam";
            const customerName = "M&C Electronics Vina";
            const quantity = item.kien_so || 'N/A';
            const alumiumType = `AL ${item.ten_loai_nhom || 'N/A'}`; 
            const weight = formatKhoiLuong(item.khoi_luong_kg); 
            const lotNumber = item.lot_no || item.LOTNO || 'N/A'; 
            const manufacturingDate = item.ngay_san_xuat_f || 'N/A'; 

            printHtml += `
            <div class="label-page-a5">
                <p><span class="data-label">Company Name :</span> <span class="data-value company-name-value">${companyName}</span></p>
                <p><span class="data-label">Address :</span> <span class="data-value">${companyAddress}</span></p>
                <p><span class="data-label">Customer :</span> <span class="data-value customer-name-value">${customerName}</span></p> 
                <div class="item-details">
                    <p><span class="data-label">Quantity :</span> <span class="data-value">${quantity}</span></p>
                    <p><span class="data-label">Alumium type :</span> <span class="data-value">${alumiumType}</span></p>
                    <p><span class="data-label">Weight :</span> <span class="data-value">${weight} ${weight !== 'N/A' && weight !== '0' ? 'Kg' : ''}</span></p>
                    <p><span class="data-label">Lot number :</span> <span class="data-value">${lotNumber}</span></p>
                    <p><span class="data-label">Manufacturing date :</span> <span class="data-value">${manufacturingDate}</span></p>
                </div>
            </div>`;
        });
        printHtml += `</body></html>`;
        
        const printWindow = window.open('', '_blank', 'width=800,height=600,resizable=yes,scrollbars=yes');
        if (printWindow) {
            printWindow.document.open();
            printWindow.document.write(printHtml);
            printWindow.document.close();

            let printCalled = false;
            const doPrint = () => {
                if (!printCalled) {
                    printCalled = true;
                    printWindow.focus();
                    printWindow.print();
                }
            };
            
            if (printWindow.document.readyState === "complete") {
                doPrint();
            } else {
                printWindow.onload = doPrint;
            }
             setTimeout(() => {
                if (printWindow.document.readyState === "complete" && !printCalled) {
                    doPrint();
                }
            }, 750); 
        } else {
            showCustomAlert('Không thể mở cửa sổ in. Vui lòng kiểm tra cài đặt chặn popup của trình duyệt.', 'error');
        }
    } // Đóng hàm generateAndPrintHtmlForTem3

    // --- HÀM IN TEM 4 (Tem Honda - Đã sửa lỗi) ---
    function formatCompositionString(composition) {
        if (!composition) {
            return "Không có dữ liệu thành phần";
        }
        const parts = [];
        const elements = ['Si', 'Fe', 'Cu', 'Mn', 'Mg', 'Zn', 'Pb', 'Ni', 'Cr', 'Sn', 'Ti', 'Cd', 'Ca'];
        elements.forEach(key => {
            const value = parseFloat(composition[key]);
            if (!isNaN(value) && value > 0) {
                parts.push(`${key}: ${value}`);
            }
        });
        if (parts.length === 0) {
            return "Không có thành phần > 0%";
        }
        return parts.join(', ');
    }

    function generateAndPrintHtmlForTem4(itemsData, thanhPhanData) { // Sửa 1: Nhận 2 tham số
        let printHtml = `<html><head><title>In Tem Kiện Hàng - Bảng Kẻ Ô</title><style>
        body { 
            margin: 0; 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 11pt;
            -webkit-print-color-adjust: exact !important; 
            print-color-adjust: exact !important; 
        }
        @page { 
            size: A5 landscape; 
            margin: 4mm; 
        }
        .label-container {
            width: 100%;
            height: 140mm; 
            box-sizing: border-box;
            page-break-after: always; 
        }
        .label-container:last-child { 
            page-break-after: avoid; 
        }
        .tem-table {
            width: 100%;
            height: 100%; 
            border-collapse: collapse; 
            table-layout: fixed;
            border: 2px solid #000 !important; /* SỬA: Thêm !important */
        }
        .tem-table tr {
            height: calc(100% / 9); 
        }
        .tem-table td {
            border: 1px solid #000 !important; /* SỬA: Thêm !important */
            padding: 2px 5px;
            vertical-align: middle;
            box-sizing: border-box;
        }
        .data-cell-label {
            width: 25%; 
            font-weight: normal;
            text-align: center;
            font-size: 12pt;
        }
        .data-cell-value {
            width: 75%; 
            font-weight: normal;
            padding-left: 10px !important;
            text-align: left;
            font-size: 12pt; 
        }
        .composition-cell-value {
            font-size: 12pt; /* SỬA: Tăng font-size */
            vertical-align: top;
            padding-top: 5px !important;
            word-wrap: break-word; /* Thêm để tự xuống dòng nếu quá dài */
        }
        .producer-left {
            display: inline-block;
            width: 60%;
            font-weight: normal;
        }
        .producer-right {
            display: inline-block;
            width: 35%;
            font-weight:normal;
            padding-left: 5mm;
            border-left: 1px solid #ccc !important; /* SỬA: Thêm !important */
        }
        .customer-name-value {
             font-weight: bold;
        }
        </style></head><body>`;

        // Lặp qua từng kiện hàng để tạo tem
        itemsData.forEach(item => {
            const customerName = "CÔNG TY TNHH Honda Trading Việt Nam";
            const customerAddress = "Tầng 8, Tòa nhà Mặt trời sông Hồng, số 23 Phan Chu Trinh, Phường Cửa Nam, Thành phố Hà Nội";
            const supplierName = "CÔNG TY TNHH Greenalu"; // Giả định
            const origin = "Việt Nam"; // Giả định
            
            const lotNumber = item.lot_no || 'N/A'; 
            
            // Sửa 2: Sử dụng 'thanhPhanData' được truyền vào
            const compositionObject = thanhPhanData ? thanhPhanData[lotNumber] : null;
            const composition = formatCompositionString(compositionObject);
            
            const itemName = 'Nhôm dạng thỏi chưa gia công: ' + (item.ten_loai_nhom || 'N/A'); 
            const ingotCount = item.so_thoi || 'N/A'; // Số thỏi
            const bundleCount = item.kien_so || 'N/A'; // Số kiện
            const netWeight = formatKhoiLuong(item.khoi_luong_kg); // Trọng lượng

            printHtml += `
            <div class="label-container">
                <table class="tem-table">
                    <!-- Hàng 1/9 -->
                    <tr>
                        <td class="data-cell-label">Nhà cung cấp</td>
                        <td class="data-cell-value customer-name-value">${customerName}</td>
                    </tr>
                    <!-- Hàng 2/9 -->
                    <tr>
                        <td class="data-cell-label">Địa chỉ</td>
                        <td class="data-cell-value">${customerAddress}</td>
                    </tr>
                    <!-- Hàng 3/9 -->
                    <tr>
                        <td class="data-cell-label">Tên hàng</td>
                        <td class="data-cell-value">${itemName}</td>
                    </tr>
                    <!-- Hàng 4/9 -->
                    <tr>
                        <td class="data-cell-label">Nhà sản xuất</td>
                        <td class="data-cell-value">
                            <span class="producer-left">Công ty : ${supplierName}</span>
                            <span class="producer-right">Xuất xứ : ${origin}</span>
                        </td>
                    </tr>
                    <!-- Hàng 5/9 -->
                    <tr>
                        <td class="data-cell-label">Lotno</td>
                        <td class="data-cell-value">${lotNumber}</td>
                    </tr>
                    <!-- Hàng 6/9 -->
                    <tr>
                        <td class="data-cell-label">Số thỏi</td>
                        <td class="data-cell-value">${ingotCount}</td>
                    </tr>
                    <!-- Hàng 7/9 -->
                    <tr>
                        <td class="data-cell-label">Số kiện</td>
                        <td class="data-cell-value">${bundleCount}</td>
                    </tr>
                    <!-- Hàng 8/9 -->
                    <tr>
                        <td class="data-cell-label">Trọng lượng tịnh</td>
                        <td class="data-cell-value">${netWeight} Kg</td>
                    </tr>
                    <!-- Hàng 9/9 -->
                    <tr>
                        <td class="data-cell-label">Thành phần</td>
                        <td class="data-cell-value composition-cell-value">${composition}</td>
                    </tr>
                </table>
            </div>`;
        });
        
        printHtml += `</body></html>`;
        
        const printWindow = window.open('', '_blank', 'width=1000,height=800,resizable=yes,scrollbars=yes');
        if (!printWindow) {
            if (typeof showCustomAlert === 'function') {
                 showCustomAlert('Không thể mở cửa sổ in. Vui lòng kiểm tra cài đặt chặn popup của trình duyệt.', 'error');
            }
            return;
        }

        printWindow.document.open();
        printWindow.document.write(printHtml);
        printWindow.document.close();
        
        let printCalled = false;
        const doPrint = () => {
            if (!printCalled) {
                printCalled = true;
                printWindow.focus();
                printWindow.print();
            }
        };
        
        if (printWindow.document.readyState === "complete") {
            doPrint();
        } else {
            printWindow.onload = doPrint;
        }
    } // Đóng hàm generateAndPrintHtmlForTem4


    // --- LOGIC TOOLTIP (Các hàm xử lý) ---
    function initializeTooltipLogic() {
        tooltipElement = document.getElementById('composition-tooltip');
        // const kienhangTbody = document.getElementById('kienhang-tbody'); // Đã khai báo ở trên
        if (!tooltipElement || !kienhangTbody) { console.error("Không tìm thấy tooltip hoặc tbody!"); return; }
        kienhangTbody.addEventListener('mouseover', handleMouseOver);
        kienhangTbody.addEventListener('mouseout', handleMouseOut);
        kienhangTbody.addEventListener('mousemove', handleMouseMove);
    }
    function cacheCompositionData(thanhPhanData) {
        if (thanhPhanData) {
            compositionDataCache = thanhPhanData;
        }
    }
    function handleMouseOver(e) {
        const targetCell = e.target.closest('.lotno-hover-trigger');
        if (!targetCell) return; 
        const lotno = targetCell.dataset.lotno;
        const composition = compositionDataCache[lotno]; 
        updateTooltipContent(composition);
        if(tooltipElement) tooltipElement.style.display = 'block';
        handleMouseMove(e); 
    }
    function handleMouseOut(e) {
        const targetCell = e.target.closest('.lotno-hover-trigger');
        if (!targetCell) return;
        if(tooltipElement) tooltipElement.style.display = 'none';
    }
    function handleMouseMove(e) {
        if (tooltipElement && tooltipElement.style.display === 'block') {
            tooltipElement.style.left = (e.pageX + 15) + 'px';
            tooltipElement.style.top = (e.pageY + 10) + 'px';
        }
    }
    function updateTooltipContent(composition) {
        if (!tooltipElement) return;
        if (!composition) {
            tooltipElement.innerHTML = '<em>Không có dữ liệu thành phần.</em>';
            return;
        }
        let html = '<h4>Thành Phần Lot</h4><div class="tooltip-grid">';
        let hasData = false;
        const keys = Object.keys(composition).filter(k => k !== 'lot_no');
        keys.forEach(key => {
            const value = parseFloat(composition[key]);
            if (value > 0) {
                hasData = true;
                html += `<div><strong>${key}:</strong> ${value}%</div>`;
            }
        });
        if (!hasData) {
            html = '<em>Không có dữ liệu thành phần (Tất cả = 0).</em>';
        } else {
            html += '</div>';
        }
        tooltipElement.innerHTML = html;
    }
    // --- KẾT THÚC LOGIC TOOLTIP ---


    // --- CẬP NHẬT CHECKBOX ---
    function updateTotalSelectedWeight() {
        if (!totalSelectedWeightSpan) return;
        if (!kienhangTbody) { totalSelectedWeightSpan.textContent = '0 Kg'; return; }
        let totalWeight = 0;
        const checkedCheckboxes = kienhangTbody.querySelectorAll('.row-checkbox:checked');
        checkedCheckboxes.forEach(checkbox => {
            const weightAttr = checkbox.dataset.weight; const weight = parseFloat(weightAttr);
            if (!isNaN(weight) && Number.isFinite(weight)) { totalWeight += weight; } 
            else { console.warn(`data-weight không hợp lệ: "${weightAttr}" cho ID ${checkbox.dataset.id}`); }
        });
        totalSelectedWeightSpan.textContent = `${formatKhoiLuong(totalWeight)} Kg`;
    }

    function updateCheckboxStates() {
        if (headerCheckbox && kienhangTbody) {
            const rowCheckboxes = kienhangTbody.querySelectorAll('.row-checkbox');
            const checkedCount = kienhangTbody.querySelectorAll('.row-checkbox:checked').length;
            if (rowCheckboxes.length === 0) { headerCheckbox.checked = false; headerCheckbox.indeterminate = false; } 
            else if (checkedCount === 0) { headerCheckbox.checked = false; headerCheckbox.indeterminate = false; } 
            else if (checkedCount === rowCheckboxes.length) { headerCheckbox.checked = true; headerCheckbox.indeterminate = false; } 
            else { headerCheckbox.checked = false; headerCheckbox.indeterminate = true; }
        }
        updateTotalSelectedWeight();
    }

    if (headerCheckbox) {
        headerCheckbox.addEventListener('change', function() {
            if (kienhangTbody) { kienhangTbody.querySelectorAll('.row-checkbox').forEach(cb => { cb.checked = this.checked; }); }
            updateCheckboxStates();
        });
    }
    if (kienhangTbody) {
        kienhangTbody.addEventListener('change', function(event) {
            if (event.target.classList.contains('row-checkbox')) { updateCheckboxStates(); }
        });
    }

    // --- KHỞI CHẠY LẦN ĐẦU ---
   updateCheckboxStates();

// Tải dữ liệu cho tab "Tổng Quan" (vì đây là tab mặc định)
if (typeof loadDashboardData === 'function') {
    loadDashboardData();
    isTongQuanLoaded = true; // Đánh dấu đã tải ngay
}

}); // Đóng DOMContentLoaded