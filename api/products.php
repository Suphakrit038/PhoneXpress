<?php
/**
 * Products API
 * จัดการข้อมูลสินค้า iPhone
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// จัดการ CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/auth.php'; // สำหรับ authentication functions

// รับข้อมูลจาก request
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $input['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// สร้าง instance ของ Product model
$productModel = new Product();

try {
    switch ($action) {
        case 'get_all':
            handleGetAllProducts($productModel);
            break;
            
        case 'get_product':
            handleGetProduct($productModel);
            break;
            
        case 'get_featured':
            handleGetFeaturedProducts($productModel);
            break;
            
        case 'get_latest':
            handleGetLatestProducts($productModel);
            break;
            
        case 'get_related':
            handleGetRelatedProducts($productModel);
            break;
            
        case 'search':
            handleSearchProducts($productModel);
            break;
            
        case 'get_categories':
            handleGetCategories($productModel);
            break;
            
        case 'get_price_range':
            handleGetPriceRange($productModel);
            break;
            
        case 'check_stock':
            handleCheckStock($productModel);
            break;
            
        case 'get_stats':
            handleGetProductStats($productModel);
            break;
            
        // Admin functions
        case 'add_product':
            handleAddProduct($productModel, $input);
            break;
            
        case 'update_product':
            handleUpdateProduct($productModel, $input);
            break;
            
        case 'delete_product':
            handleDeleteProduct($productModel);
            break;
            
        case 'update_stock':
            handleUpdateStock($productModel, $input);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'API_ERROR'
    ]);
}

/**
 * ดึงสินค้าทั้งหมดพร้อมการกรองและแบ่งหน้า
 */
function handleGetAllProducts($productModel) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 12;
    
    // สร้างตัวกรอง
    $filters = [];
    
    if (!empty($_GET['category'])) {
        $filters['category'] = $_GET['category'];
    }
    
    if (!empty($_GET['min_price'])) {
        $filters['min_price'] = (float)$_GET['min_price'];
    }
    
    if (!empty($_GET['max_price'])) {
        $filters['max_price'] = (float)$_GET['max_price'];
    }
    
    if (!empty($_GET['search'])) {
        $filters['search'] = $_GET['search'];
    }
    
    if (!empty($_GET['sort'])) {
        $filters['sort'] = $_GET['sort'];
    }
    
    $result = $productModel->getAllProducts($page, $perPage, $filters);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * ดึงข้อมูลสินค้าตาม ID
 */
function handleGetProduct($productModel) {
    $productId = $_GET['id'] ?? null;
    
    if (!$productId) {
        throw new Exception('กรุณาระบุ ID สินค้า');
    }
    
    $result = $productModel->getProduct($productId);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(404);
    }
    
    echo json_encode($result);
}

/**
 * ดึงสินค้าแนะนำ
 */
function handleGetFeaturedProducts($productModel) {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8;
    
    $result = $productModel->getFeaturedProducts($limit);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * ดึงสินค้าใหม่ล่าสุด
 */
function handleGetLatestProducts($productModel) {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
    
    $result = $productModel->getLatestProducts($limit);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * ดึงสินค้าที่เกี่ยวข้อง
 */
function handleGetRelatedProducts($productModel) {
    $productId = $_GET['product_id'] ?? null;
    $category = $_GET['category'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 4;
    
    if (!$productId) {
        throw new Exception('กรุณาระบุ ID สินค้า');
    }
    
    $result = $productModel->getRelatedProducts($productId, $category, $limit);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * ค้นหาสินค้า
 */
function handleSearchProducts($productModel) {
    $query = $_GET['q'] ?? $_GET['query'] ?? '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 12;
    
    if (empty($query)) {
        throw new Exception('กรุณาใส่คำค้นหา');
    }
    
    $result = $productModel->searchProducts($query, $page, $perPage);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * ดึงหมวดหมู่สินค้าทั้งหมด
 */
function handleGetCategories($productModel) {
    $result = $productModel->getCategories();
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * ดึงช่วงราคาของสินค้า
 */
function handleGetPriceRange($productModel) {
    $result = $productModel->getPriceRange();
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * ตรวจสอบสต็อกสินค้า
 */
function handleCheckStock($productModel) {
    $productId = $_GET['product_id'] ?? null;
    $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;
    
    if (!$productId) {
        throw new Exception('กรุณาระบุ ID สินค้า');
    }
    
    $result = $productModel->checkStock($productId, $quantity);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * ดึงสถิติสินค้า
 */
function handleGetProductStats($productModel) {
    $result = $productModel->getProductStats();
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * เพิ่มสินค้าใหม่ (Admin only)
 */
function handleAddProduct($productModel, $input) {
    // ตรวจสอบสิทธิ์ Admin
    $userModel = new User();
    requireAdminRole($userModel);
    
    // ตรวจสอบข้อมูลที่จำเป็น
    $required = ['name', 'category', 'price'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("กรุณาใส่ข้อมูล: {$field}");
        }
    }
    
    // ตรวจสอบข้อมูล
    if (!is_numeric($input['price']) || $input['price'] <= 0) {
        throw new Exception('ราคาต้องเป็นตัวเลขที่มากกว่า 0');
    }
    
    if (isset($input['stock_quantity']) && (!is_numeric($input['stock_quantity']) || $input['stock_quantity'] < 0)) {
        throw new Exception('จำนวนสต็อกต้องเป็นตัวเลขที่มากกว่าหรือเท่ากับ 0');
    }
    
    $result = $productModel->addProduct($input);
    
    if ($result['success']) {
        http_response_code(201);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * อัปเดตข้อมูลสินค้า (Admin only)
 */
function handleUpdateProduct($productModel, $input) {
    // ตรวจสอบสิทธิ์ Admin
    $userModel = new User();
    requireAdminRole($userModel);
    
    $productId = $_GET['id'] ?? $input['id'] ?? null;
    
    if (!$productId) {
        throw new Exception('กรุณาระบุ ID สินค้า');
    }
    
    // ตรวจสอบข้อมูล
    if (isset($input['price']) && (!is_numeric($input['price']) || $input['price'] <= 0)) {
        throw new Exception('ราคาต้องเป็นตัวเลขที่มากกว่า 0');
    }
    
    if (isset($input['stock_quantity']) && (!is_numeric($input['stock_quantity']) || $input['stock_quantity'] < 0)) {
        throw new Exception('จำนวนสต็อกต้องเป็นตัวเลขที่มากกว่าหรือเท่ากับ 0');
    }
    
    $result = $productModel->updateProduct($productId, $input);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * ลบสินค้า (Admin only)
 */
function handleDeleteProduct($productModel) {
    // ตรวจสอบสิทธิ์ Admin
    $userModel = new User();
    requireAdminRole($userModel);
    
    $productId = $_GET['id'] ?? null;
    
    if (!$productId) {
        throw new Exception('กรุณาระบุ ID สินค้า');
    }
    
    $result = $productModel->deleteProduct($productId);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * อัปเดตสต็อกสินค้า (Admin only)
 */
function handleUpdateStock($productModel, $input) {
    // ตรวจสอบสิทธิ์ Admin
    $userModel = new User();
    requireAdminRole($userModel);
    
    $productId = $input['product_id'] ?? null;
    $quantity = $input['quantity'] ?? null;
    $operation = $input['operation'] ?? 'decrease';
    
    if (!$productId) {
        throw new Exception('กรุณาระบุ ID สินค้า');
    }
    
    if (!$quantity || !is_numeric($quantity) || $quantity <= 0) {
        throw new Exception('กรุณาระบุจำนวนที่ถูกต้อง');
    }
    
    if (!in_array($operation, ['increase', 'decrease'])) {
        throw new Exception('การดำเนินการไม่ถูกต้อง');
    }
    
    $result = $productModel->updateStock($productId, $quantity, $operation);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * ฟังก์ชันช่วยสำหรับ validate ข้อมูลสินค้า
 */
function validateProductData($data) {
    $errors = [];
    
    // ตรวจสอบชื่อสินค้า
    if (empty($data['name'])) {
        $errors[] = 'กรุณาใส่ชื่อสินค้า';
    }
    
    // ตรวจสอบหมวดหมู่
    $validCategories = ['iPhone 14', 'iPhone 15', 'iPhone 15 Pro', 'iPhone 16', 'iPhone 16 Pro', 'iPhone SE'];
    if (empty($data['category'])) {
        $errors[] = 'กรุณาเลือกหมวดหมู่';
    } elseif (!in_array($data['category'], $validCategories)) {
        $errors[] = 'หมวดหมู่ไม่ถูกต้อง';
    }
    
    // ตรวจสอบราคา
    if (empty($data['price'])) {
        $errors[] = 'กรุณาใส่ราคา';
    } elseif (!is_numeric($data['price']) || $data['price'] <= 0) {
        $errors[] = 'ราคาต้องเป็นตัวเลขที่มากกว่า 0';
    }
    
    // ตรวจสอบความจุ
    $validStorages = ['64GB', '128GB', '256GB', '512GB', '1TB'];
    if (!empty($data['storage']) && !in_array($data['storage'], $validStorages)) {
        $errors[] = 'ความจุไม่ถูกต้อง';
    }
    
    // ตรวจสอบสี
    $validColors = ['Black', 'White', 'Blue', 'Pink', 'Purple', 'Red', 'Green', 'Yellow', 'Silver', 'Gold', 'Space Gray', 'Midnight', 'Starlight', 'Product Red', 'Deep Purple', 'Dynamic Island', 'Natural Titanium', 'Blue Titanium', 'White Titanium', 'Black Titanium', 'Desert Titanium'];
    if (!empty($data['color']) && !in_array($data['color'], $validColors)) {
        $errors[] = 'สีไม่ถูกต้อง';
    }
    
    // ตรวจสอบสต็อก
    if (isset($data['stock_quantity']) && (!is_numeric($data['stock_quantity']) || $data['stock_quantity'] < 0)) {
        $errors[] = 'จำนวนสต็อกต้องเป็นตัวเลขที่มากกว่าหรือเท่ากับ 0';
    }
    
    return $errors;
}

/**
 * ฟังก์ชันสำหรับ sanitize ข้อมูลสินค้า
 */
function sanitizeProductData($data) {
    $sanitized = [];
    
    $stringFields = ['name', 'category', 'description', 'image', 'storage', 'color'];
    foreach ($stringFields as $field) {
        if (isset($data[$field])) {
            $sanitized[$field] = trim(htmlspecialchars($data[$field], ENT_QUOTES, 'UTF-8'));
        }
    }
    
    $numericFields = ['price', 'stock_quantity'];
    foreach ($numericFields as $field) {
        if (isset($data[$field])) {
            $sanitized[$field] = is_numeric($data[$field]) ? (float)$data[$field] : 0;
        }
    }
    
    $booleanFields = ['is_featured'];
    foreach ($booleanFields as $field) {
        if (isset($data[$field])) {
            $sanitized[$field] = (bool)$data[$field];
        }
    }
    
    return $sanitized;
}

/**
 * ฟังก์ชันสำหรับ log การเปลี่ยนแปลงข้อมูลสินค้า
 */
function logProductChange($action, $productId, $data = []) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'product_id' => $productId,
        'data' => $data,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    error_log("Product API Log: " . json_encode($logData, JSON_UNESCAPED_UNICODE));
}

/**
 * ฟังก์ชันสำหรับ cache ข้อมูลสินค้า
 */
function getCacheKey($action, $params = []) {
    $key = $action . '_' . md5(serialize($params));
    return $key;
}

function getCachedData($key, $expiry = 300) {
    $cacheFile = __DIR__ . '/../temp/cache/' . $key . '.json';
    
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if ($data && $data['timestamp'] + $expiry > time()) {
            return $data['content'];
        }
    }
    
    return null;
}

function setCachedData($key, $data) {
    $cacheDir = __DIR__ . '/../temp/cache/';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $cacheData = [
        'timestamp' => time(),
        'content' => $data
    ];
    
    file_put_contents($cacheDir . $key . '.json', json_encode($cacheData));
}
?>
