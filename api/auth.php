<?php
/**
 * Authentication API
 * จัดการการ Login, Register, Logout และ Session
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// จัดการ CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../models/User.php';

// รับข้อมูลจาก request
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $input['action'] ?? '';

// สร้าง instance ของ User model
$userModel = new User();

try {
    switch ($action) {
        case 'login':
            handleLogin($userModel, $input);
            break;
            
        case 'register':
            handleRegister($userModel, $input);
            break;
            
        case 'logout':
            handleLogout($userModel, $input);
            break;
            
        case 'check_session':
            handleCheckSession($userModel, $input);
            break;
            
        case 'update_profile':
            handleUpdateProfile($userModel, $input);
            break;
            
        case 'change_password':
            handleChangePassword($userModel, $input);
            break;
            
        case 'get_user_activity':
            handleGetUserActivity($userModel, $input);
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
 * จัดการการเข้าสู่ระบบ
 */
function handleLogin($userModel, $input) {
    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($input['email']) || empty($input['password'])) {
        throw new Exception('กรุณาใส่อีเมลและรหัสผ่าน');
    }

    // ตรวจสอบรูปแบบอีเมล
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('รูปแบบอีเมลไม่ถูกต้อง');
    }

    $email = trim($input['email']);
    $password = $input['password'];
    $rememberMe = $input['remember_me'] ?? false;

    $result = $userModel->login($email, $password, $rememberMe);

    if ($result['success']) {
        // ส่ง session token กลับไป
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'user' => $result['user'],
            'session_token' => $result['session_token']
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $result['message'],
            'error_code' => 'LOGIN_FAILED'
        ]);
    }
}

/**
 * จัดการการสมัครสมาชิก
 */
function handleRegister($userModel, $input) {
    // ตรวจสอบข้อมูลที่จำเป็น
    $required = ['email', 'password', 'confirm_password', 'first_name', 'last_name'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("กรุณาใส่ข้อมูล: {$field}");
        }
    }

    // ตรวจสอบรูปแบบอีเมล
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('รูปแบบอีเมลไม่ถูกต้อง');
    }

    // ตรวจสอบรหัสผ่านตรงกัน
    if ($input['password'] !== $input['confirm_password']) {
        throw new Exception('รหัสผ่านไม่ตรงกัน');
    }

    $email = trim($input['email']);
    $password = $input['password'];
    $firstName = trim($input['first_name']);
    $lastName = trim($input['last_name']);
    $phone = !empty($input['phone']) ? trim($input['phone']) : null;

    $result = $userModel->register($email, $password, $firstName, $lastName, $phone);

    if ($result['success']) {
        http_response_code(201);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);
}

/**
 * จัดการการออกจากระบบ
 */
function handleLogout($userModel, $input) {
    $sessionToken = getSessionTokenFromRequest();
    
    if (!$sessionToken) {
        throw new Exception('ไม่พบ session token');
    }

    $result = $userModel->logout($sessionToken);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);
}

/**
 * ตรวจสอบ session ปัจจุบัน
 */
function handleCheckSession($userModel, $input) {
    $sessionToken = getSessionTokenFromRequest();
    
    if (!$sessionToken) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบ session token',
            'error_code' => 'NO_SESSION'
        ]);
        return;
    }

    $result = $userModel->getUserFromSession($sessionToken);
    
    if ($result['success']) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'user' => $result['user'],
            'authenticated' => true
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $result['message'],
            'authenticated' => false,
            'error_code' => 'SESSION_EXPIRED'
        ]);
    }
}

/**
 * อัปเดตข้อมูลโปรไฟล์
 */
function handleUpdateProfile($userModel, $input) {
    $sessionToken = getSessionTokenFromRequest();
    
    if (!$sessionToken) {
        throw new Exception('กรุณาเข้าสู่ระบบ');
    }

    // ตรวจสอบ session และดึงข้อมูลผู้ใช้
    $sessionResult = $userModel->getUserFromSession($sessionToken);
    if (!$sessionResult['success']) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Session หมดอายุ กรุณาเข้าสู่ระบบใหม่',
            'error_code' => 'SESSION_EXPIRED'
        ]);
        return;
    }

    $userId = $sessionResult['user']['id'];

    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($input['first_name']) || empty($input['last_name'])) {
        throw new Exception('กรุณาใส่ชื่อและนามสกุล');
    }

    $firstName = trim($input['first_name']);
    $lastName = trim($input['last_name']);
    $phone = !empty($input['phone']) ? trim($input['phone']) : null;

    $result = $userModel->updateProfile($userId, $firstName, $lastName, $phone);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);
}

/**
 * เปลี่ยนรหัสผ่าน
 */
function handleChangePassword($userModel, $input) {
    $sessionToken = getSessionTokenFromRequest();
    
    if (!$sessionToken) {
        throw new Exception('กรุณาเข้าสู่ระบบ');
    }

    // ตรวจสอบ session และดึงข้อมูลผู้ใช้
    $sessionResult = $userModel->getUserFromSession($sessionToken);
    if (!$sessionResult['success']) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Session หมดอายุ กรุณาเข้าสู่ระบบใหม่',
            'error_code' => 'SESSION_EXPIRED'
        ]);
        return;
    }

    $userId = $sessionResult['user']['id'];

    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($input['current_password']) || empty($input['new_password']) || empty($input['confirm_password'])) {
        throw new Exception('กรุณาใส่ข้อมูลให้ครบถ้วน');
    }

    // ตรวจสอบรหัสผ่านใหม่ตรงกัน
    if ($input['new_password'] !== $input['confirm_password']) {
        throw new Exception('รหัสผ่านใหม่ไม่ตรงกัน');
    }

    $currentPassword = $input['current_password'];
    $newPassword = $input['new_password'];

    $result = $userModel->changePassword($userId, $currentPassword, $newPassword);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);
}

/**
 * ดึงประวัติการใช้งานของผู้ใช้
 */
function handleGetUserActivity($userModel, $input) {
    $sessionToken = getSessionTokenFromRequest();
    
    if (!$sessionToken) {
        throw new Exception('กรุณาเข้าสู่ระบบ');
    }

    // ตรวจสอบ session และดึงข้อมูลผู้ใช้
    $sessionResult = $userModel->getUserFromSession($sessionToken);
    if (!$sessionResult['success']) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Session หมดอายุ กรุณาเข้าสู่ระบบใหม่',
            'error_code' => 'SESSION_EXPIRED'
        ]);
        return;
    }

    $userId = $sessionResult['user']['id'];
    $page = $input['page'] ?? 1;
    $perPage = $input['per_page'] ?? 20;

    $result = $userModel->getUserActivity($userId, $page, $perPage);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);
}

/**
 * ดึง session token จาก request
 */
function getSessionTokenFromRequest() {
    // ตรวจสอบใน Authorization header
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
    }

    // ตรวจสอบใน POST data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!empty($input['session_token'])) {
        return $input['session_token'];
    }

    // ตรวจสอบใน GET parameter (ไม่แนะนำ แต่เพื่อความยืดหยุน)
    if (!empty($_GET['session_token'])) {
        return $_GET['session_token'];
    }

    return null;
}

/**
 * Middleware สำหรับตรวจสอบการเข้าสู่ระบบ
 */
function requireAuthentication($userModel) {
    $sessionToken = getSessionTokenFromRequest();
    
    if (!$sessionToken) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'กรุณาเข้าสู่ระบบ',
            'error_code' => 'AUTHENTICATION_REQUIRED'
        ]);
        exit;
    }

    $result = $userModel->getUserFromSession($sessionToken);
    
    if (!$result['success']) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Session หมดอายุ กรุณาเข้าสู่ระบบใหม่',
            'error_code' => 'SESSION_EXPIRED'
        ]);
        exit;
    }

    return $result['user'];
}

/**
 * Middleware สำหรับตรวจสอบสิทธิ์ Admin
 */
function requireAdminRole($userModel) {
    $user = requireAuthentication($userModel);
    
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'ไม่มีสิทธิ์เข้าถึง',
            'error_code' => 'INSUFFICIENT_PERMISSIONS'
        ]);
        exit;
    }

    return $user;
}

/**
 * ฟังก์ชันสำหรับ validate ข้อมูล input
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    // ตรวจสอบเบอร์โทรศัพท์ไทย
    return preg_match('/^(\+66|0)[0-9]{8,9}$/', $phone);
}

function sanitizeInput($input) {
    if (is_string($input)) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
    return $input;
}

/**
 * จัดการข้อผิดพลาดและ logging
 */
function logError($message, $context = []) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    error_log("Auth API Error: " . json_encode($logData, JSON_UNESCAPED_UNICODE));
}

/**
 * ตรวจสอบ rate limiting (ป้องกันการโจมตี brute force)
 */
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    // สำหรับ production ควรใช้ Redis หรือ Memcached
    // ตัวอย่างนี้ใช้ไฟล์เก็บข้อมูล
    
    $rateLimitFile = __DIR__ . '/../temp/rate_limit.json';
    $rateLimits = [];
    
    if (file_exists($rateLimitFile)) {
        $rateLimits = json_decode(file_get_contents($rateLimitFile), true) ?: [];
    }
    
    $now = time();
    $key = md5($identifier);
    
    // ล้างข้อมูลเก่า
    foreach ($rateLimits as $k => $data) {
        if ($data['reset_time'] < $now) {
            unset($rateLimits[$k]);
        }
    }
    
    // ตรวจสอบ limit
    if (isset($rateLimits[$key])) {
        if ($rateLimits[$key]['attempts'] >= $maxAttempts) {
            return false;
        }
        $rateLimits[$key]['attempts']++;
    } else {
        $rateLimits[$key] = [
            'attempts' => 1,
            'reset_time' => $now + $timeWindow
        ];
    }
    
    // บันทึกข้อมูล
    if (!is_dir(dirname($rateLimitFile))) {
        mkdir(dirname($rateLimitFile), 0755, true);
    }
    file_put_contents($rateLimitFile, json_encode($rateLimits));
    
    return true;
}
?>
