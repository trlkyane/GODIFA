/**
 * GHN Address Selector with 3-level Cascading Dropdowns
 * File: public/js/ghn-address.js
 * 
 * Usage: Include this in checkout.php
 * <script src="/public/js/ghn-address.js"></script>
 */

class GHNAddressSelector {
    constructor() {
        this.provinceSelect = document.getElementById('province');
        this.districtSelect = document.getElementById('district');
        this.wardSelect = document.getElementById('ward');
        this.shippingFeeDisplay = document.getElementById('shipping-fee');
        this.totalAmountDisplay = document.getElementById('total-amount');
        
        this.selectedProvince = null;
        this.selectedDistrict = null;
        this.selectedWard = null;
        this.baseTotal = 0;
        
        this.init();
    }
    
    init() {
        // Lấy giá trị total ban đầu
        const totalText = this.totalAmountDisplay?.textContent.replace(/[^\d]/g, '');
        this.baseTotal = parseInt(totalText) || 0;
        
        // Load provinces khi trang load
        this.loadProvinces();
        
        // Event listeners
        this.provinceSelect?.addEventListener('change', () => this.onProvinceChange());
        this.districtSelect?.addEventListener('change', () => this.onDistrictChange());
        this.wardSelect?.addEventListener('change', () => this.onWardChange());
    }
    
    async loadProvinces() {
        try {
            const response = await fetch('/GODIFA/api/ghn/provinces.php');
            const result = await response.json();
            
            if (result.success) {
                this.populateProvince(result.data);
            } else {
                console.error('Failed to load provinces:', result.error);
                this.showError('Không thể tải danh sách tỉnh/thành phố');
            }
        } catch (error) {
            console.error('Error loading provinces:', error);
            this.showError('Lỗi kết nối API');
        }
    }
    
    populateProvince(provinces) {
        this.provinceSelect.innerHTML = '<option value="">-- Chọn Tỉnh/Thành phố --</option>';
        
        provinces.forEach(province => {
            const option = document.createElement('option');
            option.value = province.ProvinceID;
            option.textContent = province.ProvinceName;
            option.dataset.provinceId = province.ProvinceID;
            option.dataset.provinceName = province.ProvinceName;
            this.provinceSelect.appendChild(option);
        });
    }
    
    async onProvinceChange() {
        const selectedOption = this.provinceSelect.options[this.provinceSelect.selectedIndex];
        
        if (!selectedOption.value) {
            this.resetDistrict();
            this.resetWard();
            return;
        }
        
        this.selectedProvince = {
            id: parseInt(selectedOption.value),
            name: selectedOption.dataset.provinceName
        };
        
        // Reset district và ward
        this.resetDistrict();
        this.resetWard();
        
        // Load districts
        this.districtSelect.innerHTML = '<option value="">Đang tải...</option>';
        
        try {
            const response = await fetch(`/GODIFA/api/ghn/districts.php?provinceId=${this.selectedProvince.id}`);
            const result = await response.json();
            
            if (result.success) {
                this.populateDistrict(result.data);
            } else {
                this.showError('Không thể tải danh sách quận/huyện');
            }
        } catch (error) {
            console.error('Error loading districts:', error);
            this.showError('Lỗi kết nối API');
        }
    }
    
    populateDistrict(districts) {
        this.districtSelect.innerHTML = '<option value="">-- Chọn Quận/Huyện --</option>';
        
        districts.forEach(district => {
            const option = document.createElement('option');
            option.value = district.DistrictID;
            option.textContent = district.DistrictName;
            option.dataset.districtId = district.DistrictID;
            option.dataset.districtName = district.DistrictName;
            this.districtSelect.appendChild(option);
        });
        
        this.districtSelect.disabled = false;
    }
    
    async onDistrictChange() {
        const selectedOption = this.districtSelect.options[this.districtSelect.selectedIndex];
        
        if (!selectedOption.value) {
            this.resetWard();
            return;
        }
        
        this.selectedDistrict = {
            id: parseInt(selectedOption.value),
            name: selectedOption.dataset.districtName
        };
        
        // Reset ward
        this.resetWard();
        
        // Load wards
        this.wardSelect.innerHTML = '<option value="">Đang tải...</option>';
        
        try {
            const response = await fetch(`/GODIFA/api/ghn/wards.php?districtId=${this.selectedDistrict.id}`);
            const result = await response.json();
            
            if (result.success) {
                this.populateWard(result.data);
            } else {
                this.showError('Không thể tải danh sách phường/xã');
            }
        } catch (error) {
            console.error('Error loading wards:', error);
            this.showError('Lỗi kết nối API');
        }
    }
    
    populateWard(wards) {
        this.wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
        
        wards.forEach(ward => {
            const option = document.createElement('option');
            option.value = ward.WardCode;
            option.textContent = ward.WardName;
            option.dataset.wardCode = ward.WardCode;
            option.dataset.wardName = ward.WardName;
            this.wardSelect.appendChild(option);
        });
        
        this.wardSelect.disabled = false;
    }
    
    async onWardChange() {
        const selectedOption = this.wardSelect.options[this.wardSelect.selectedIndex];
        
        if (!selectedOption.value) {
            this.updateShippingFee(0);
            return;
        }
        
        this.selectedWard = {
            code: selectedOption.value,
            name: selectedOption.dataset.wardName
        };
        
        // Calculate shipping fee
        await this.calculateShippingFee();
    }
    
    async calculateShippingFee() {
        if (!this.selectedDistrict || !this.selectedWard) {
            console.log('Missing district or ward');
            return;
        }
        
        this.shippingFeeDisplay.textContent = 'Đang tính...';
        
        // Always use Standard service (service_type_id = 2)
        // Express (5) is not available for all routes
        const serviceTypeId = 2;
        
        const requestData = {
            districtId: this.selectedDistrict.id,
            wardCode: this.selectedWard.code,
            weight: 500, // Default weight: 500g (GHN minimum: 200g)
            insurance: this.baseTotal,
            service_type_id: serviceTypeId
        };
        
        console.log('Request data:', requestData);
        
        try {
            const response = await fetch('/GODIFA/api/ghn/calculate_fee.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestData)
            });
            
            const result = await response.json();
            console.log('API Response:', result);
            
            if (result.success) {
                const shippingFee = result.data.total;
                this.updateShippingFee(shippingFee);
            } else {
                console.error('API Error:', result.error, result.details);
                this.showError('Không thể tính phí vận chuyển: ' + (result.error || 'Unknown error'));
                this.updateShippingFee(0);
            }
        } catch (error) {
            console.error('Error calculating fee:', error);
            this.showError('Lỗi kết nối API');
            this.updateShippingFee(0);
        }
    }
    
    updateShippingFee(fee) {
        // Update display
        this.shippingFeeDisplay.textContent = this.formatMoney(fee);
        
        // Update hidden input for form submission
        const hiddenInput = document.getElementById('shipping-fee-value');
        if (hiddenInput) {
            hiddenInput.value = fee;
        }
        
        // Call callback to update total in checkout.php (includes discount calculation)
        if (typeof window.onShippingFeeCalculated === 'function') {
            window.onShippingFeeCalculated(fee);
        }
    }
    
    resetDistrict() {
        this.districtSelect.innerHTML = '<option value="">-- Chọn Quận/Huyện --</option>';
        this.districtSelect.disabled = true;
        this.selectedDistrict = null;
    }
    
    resetWard() {
        this.wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
        this.wardSelect.disabled = true;
        this.selectedWard = null;
        this.updateShippingFee(0);
    }
    
    formatMoney(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    }
    
    showError(message) {
        alert(message);
    }
    
    // Get selected data for form submission
    getSelectedData() {
        return {
            provinceId: this.selectedProvince?.id,
            provinceName: this.selectedProvince?.name,
            districtId: this.selectedDistrict?.id,
            districtName: this.selectedDistrict?.name,
            wardCode: this.selectedWard?.code,
            wardName: this.selectedWard?.name
        };
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.ghnAddressSelector = new GHNAddressSelector();
});
