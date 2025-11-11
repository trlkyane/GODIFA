<?php
/**
 * Giao Hàng Nhanh (GHN) Model
 * File: model/mGHN.php
 * Xử lý tích hợp API GHN
 */

class GHN {
    private $config;
    private $apiUrl;
    private $token;
    private $shopId;
    
    public function __construct() {
        $this->config = include __DIR__ . '/../config/ghn.php';
        $this->apiUrl = $this->config['api_url'];
        $this->token = $this->config['token'];
        $this->shopId = $this->config['shop_id'];
    }
    
    /**
     * Gọi API GHN
     */
    private function callAPI($endpoint, $method = 'POST', $data = []) {
        $url = $this->apiUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Token: ' . $this->token,
            'ShopId: ' . $this->shopId
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bỏ qua SSL verify cho localhost
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'GET') {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("GHN CURL Error: " . $error);
            return ['success' => false, 'error' => $error];
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode !== 200 || !isset($result['code']) || $result['code'] !== 200) {
            $errorMsg = $result['message'] ?? 'Unknown error';
            error_log("GHN API Error ($httpCode): $errorMsg - Response: $response");
            return ['success' => false, 'error' => $errorMsg, 'response' => $result];
        }
        
        return ['success' => true, 'data' => $result['data']];
    }
    
    /**
     * Lấy danh sách tỉnh/thành phố
     */
    public function getProvinces() {
        return $this->callAPI('/master-data/province', 'GET');
    }
    
    /**
     * Lấy danh sách quận/huyện theo tỉnh
     */
    public function getDistricts($provinceId) {
        return $this->callAPI('/master-data/district', 'POST', [
            'province_id' => $provinceId
        ]);
    }
    
    /**
     * Lấy danh sách phường/xã theo quận
     */
    public function getWards($districtId) {
        return $this->callAPI('/master-data/ward', 'POST', [
            'district_id' => $districtId
        ]);
    }
    
    /**
     * Tính phí vận chuyển
     */
    public function calculateFee($toDistrictId, $toWardCode, $weight = null, $insurance = 0, $serviceTypeId = null) {
        $weight = $weight ?? $this->config['default_weight'];
        $serviceTypeId = $serviceTypeId ?? $this->config['service_type_id'];
        
        return $this->callAPI('/v2/shipping-order/fee', 'POST', [
            'from_district_id' => $this->config['from_district_id'],
            'service_type_id' => (int)$serviceTypeId,
            'to_district_id' => $toDistrictId,
            'to_ward_code' => $toWardCode,
            'height' => $this->config['default_height'],
            'length' => $this->config['default_length'],
            'weight' => $weight,
            'width' => $this->config['default_width'],
            'insurance_value' => $insurance
        ]);
    }
    
    /**
     * Tính thời gian giao hàng dự kiến
     */
    public function getLeadTime($toDistrictId, $toWardCode) {
        return $this->callAPI('/v2/shipping-order/leadtime', 'POST', [
            'from_district_id' => $this->config['from_district_id'],
            'from_ward_code' => $this->config['from_ward_code'],
            'to_district_id' => $toDistrictId,
            'to_ward_code' => $toWardCode,
            'service_id' => $this->config['service_type_id']
        ]);
    }
}
