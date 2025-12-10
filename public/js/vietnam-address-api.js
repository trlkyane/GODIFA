/**
 * Vietnam Address API - Using provinces.open-api.vn/api/v2
 * Full data: 63 provinces + all districts + all wards
 */

class VietnamAddressAPI {
    constructor() {
        this.baseURL = 'https://provinces.open-api.vn/api';
        this.provinceSelect = document.getElementById('province');
        this.districtSelect = document.getElementById('district');
        this.wardSelect = document.getElementById('ward');
        
        // Cache data
        this.cachedProvinces = null;
        
        this.init();
    }
    
    init() {
        // Load provinces immediately
        this.loadProvinces();
        
        // Events
        this.provinceSelect.addEventListener('change', () => this.onProvinceChange());
        this.districtSelect.addEventListener('change', () => this.onDistrictChange());
    }
    
    async loadProvinces() {
        try {
            console.log('🌍 Loading provinces from API...');
            
            // Call API
            const response = await fetch(`${this.baseURL}/p/`);
            const provinces = await response.json();
            
            // Cache
            this.cachedProvinces = provinces;
            
            // Populate
            this.provinceSelect.innerHTML = '<option value="">-- Chọn Tỉnh/Thành phố --</option>';
            
            provinces.forEach(province => {
                const option = document.createElement('option');
                option.value = province.code;
                option.textContent = province.name;
                option.dataset.name = province.name;
                this.provinceSelect.appendChild(option);
            });
            
            console.log('✅ Loaded', provinces.length, 'provinces');
        } catch (error) {
            console.error('❌ Error loading provinces:', error);
            alert('Không thể tải danh sách tỉnh/thành phố. Vui lòng thử lại!');
        }
    }
    
    async onProvinceChange() {
        const provinceCode = this.provinceSelect.value;
        
        if (!provinceCode) {
            this.resetDistrict();
            return;
        }
        
        try {
            console.log('🏙️ Loading districts for province:', provinceCode);
            
            // Load province with districts (depth=2)
            const response = await fetch(`${this.baseURL}/p/${provinceCode}?depth=2`);
            const provinceData = await response.json();
            
            console.log('📦 Province data received:', provinceData);
            
            // Populate districts
            this.districtSelect.innerHTML = '<option value="">Đang tải...</option>';
            
            if (provinceData.districts && provinceData.districts.length > 0) {
                this.districtSelect.innerHTML = '<option value="">-- Chọn Quận/Huyện --</option>';
                
                provinceData.districts.forEach(district => {
                    const option = document.createElement('option');
                    option.value = district.code;
                    option.textContent = district.name;
                    option.dataset.name = district.name;
                    this.districtSelect.appendChild(option);
                });
                
                this.districtSelect.disabled = false;
                console.log('✅ Loaded', provinceData.districts.length, 'districts');
            } else {
                console.warn('⚠️ No districts found. Response:', provinceData);
                this.districtSelect.innerHTML = '<option value="">Không có dữ liệu quận/huyện</option>';
                this.districtSelect.disabled = true;
            }
            
            // Reset ward
            this.resetWard();
            
            // Update shipping fee
            this.updateShippingFee(provinceCode);
            
        } catch (error) {
            console.error('❌ Error loading districts:', error);
            alert('Không thể tải danh sách quận/huyện. Vui lòng thử lại!');
        }
    }
    
    async onDistrictChange() {
        const districtCode = this.districtSelect.value;
        
        if (!districtCode) {
            this.resetWard();
            return;
        }
        
        try {
            console.log('📍 Loading wards for district:', districtCode);
            
            // Load district with wards (depth=2)
            const response = await fetch(`${this.baseURL}/d/${districtCode}?depth=2`);
            const districtData = await response.json();
            
            console.log('📦 District data received:', districtData);
            
            // Populate wards
            this.wardSelect.innerHTML = '<option value="">Đang tải...</option>';
            
            if (districtData.wards && districtData.wards.length > 0) {
                this.wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
                
                districtData.wards.forEach(ward => {
                    const option = document.createElement('option');
                    option.value = ward.code;
                    option.textContent = ward.name;
                    option.dataset.name = ward.name;
                    this.wardSelect.appendChild(option);
                });
                
                this.wardSelect.disabled = false;
                console.log('✅ Loaded', districtData.wards.length, 'wards');
            } else {
                console.warn('⚠️ No wards found. Response:', districtData);
                this.wardSelect.innerHTML = '<option value="">Không có dữ liệu phường/xã</option>';
                this.wardSelect.disabled = true;
            }
            
        } catch (error) {
            console.error('❌ Error loading wards:', error);
            alert('Không thể tải danh sách phường/xã. Vui lòng thử lại!');
        }
    }
    
    updateShippingFee(provinceCode) {
        // Phí ship theo tỉnh
        const shippingFees = {
            '01': 35000,  // Hà Nội
            '79': 25000,  // Hồ Chí Minh
            '48': 30000,  // Đà Nẵng
            '31': 35000,  // Hải Phòng
            '92': 28000,  // Cần Thơ
        };
        
        const fee = shippingFees[provinceCode] || 30000; // Default 30k
        
        // Update global variable
        if (typeof shippingFee !== 'undefined') {
            shippingFee = fee;
        }
        
        // Update UI
        const shippingFeeElement = document.getElementById('shipping-fee');
        const shippingFeeValueElement = document.getElementById('shipping-fee-value');
        
        if (shippingFeeElement) {
            shippingFeeElement.textContent = this.formatMoney(fee) + '₫';
        }
        
        if (shippingFeeValueElement) {
            shippingFeeValueElement.value = fee;
        }
        
        // Update total
        if (typeof updateTotal === 'function') {
            updateTotal();
        }
        
        console.log('💰 Shipping fee:', fee, 'VND');
    }
    
    resetDistrict() {
        this.districtSelect.innerHTML = '<option value="">-- Chọn Quận/Huyện --</option>';
        this.districtSelect.disabled = true;
        this.resetWard();
        this.updateShippingFee(0);
    }
    
    resetWard() {
        this.wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
        this.wardSelect.disabled = true;
    }
    
    formatMoney(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount);
    }
}

// Initialize when DOM ready
document.addEventListener('DOMContentLoaded', () => {
    new VietnamAddressAPI();
});
