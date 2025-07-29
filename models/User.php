<?php
/**
 * User Model
 * จัดการข้อมูลผู้ใช้และการ Authentication
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    private $table = 'users';

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * สร้างบัญชีผู้ใช้ใหม่
     */
    public function register($email, $password, $firstName, $lastName, $phone = null) {
        try {
            // ตรวจสอบว่าอีเมลซ้ำหรือไม่
            if ($this->emailExists($email)) {
                return [
                    'success' => false,
                    'message' => 'อีเมลนี้ถูกใช้งานแล้ว'
                ];
            }

            // ตรวจสอบความแข็งแรงของรหัสผ่าน
            $passwordValidation = $this->validatePassword($password);
            if (!$passwordValidation['valid']) {
                return [
                    'success' => false,
                    'message' => $passwordValidation['message']
                ];
            }

            // เข้ารหัสรหัสผ่าน
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // สร้างบัญชีผู้ใช้
            $sql = "INSERT INTO {$this->table} (email, password, first_name, last_name, phone, status, created_at) 
                    VALUES (:email, :password, :first_name, :last_name, :phone, 'active', NOW())";
            
            $params = [
                ':email' => $email,
                ':password' => $hashedPassword,
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':phone' => $phone
            ];

            $this->db->query($sql, $params);
            $userId = $this->db->lastInsertId();

            // บันทึก log การสร้างบัญชี
            $this->logActivity($userId, 'register', 'สร้างบัญชีผู้ใช้ใหม่');

            return [
                'success' => true,
                'message' => 'สร้างบัญชีเรียบร้อยแล้ว',
                'user_id' => $userId
            ];

        } catch (Exception $e) {
            error_log("User registration failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสร้างบัญชี'
            ];
        }
    }

    /**
     * เข้าสู่ระบบ
     */
    public function login($email, $password, $rememberMe = false) {
        try {
            // ค้นหาผู้ใช้จากอีเมล
            $user = $this->findByEmail($email);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบบัญชีผู้ใช้นี้'
                ];
            }

            // ตรวจสอบสถานะบัญชี
            if ($user['status'] !== 'active') {
                return [
                    'success' => false,
                    'message' => 'บัญชีนี้ถูกระงับการใช้งาน'
                ];
            }

            // ตรวจสอบรหัสผ่าน
            if (!password_verify($password, $user['password'])) {
                // บันทึกความพยายามเข้าสู่ระบบที่ผิด
                $this->logActivity($user['id'], 'login_failed', 'พยายามเข้าสู่ระบบด้วยรหัสผ่านผิด');
                
                return [
                    'success' => false,
                    'message' => 'รหัสผ่านไม่ถูกต้อง'
                ];
            }

            // สร้าง session
            $sessionToken = $this->createSession($user['id'], $rememberMe);
            
            // อัปเดตเวลาเข้าสู่ระบบล่าสุด
            $this->updateLastLogin($user['id']);

            // บันทึก log การเข้าสู่ระบบ
            $this->logActivity($user['id'], 'login', 'เข้าสู่ระบบสำเร็จ');

            return [
                'success' => true,
                'message' => 'เข้าสู่ระบบสำเร็จ',
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'role' => $user['role'],
                    'avatar' => $user['avatar']
                ],
                'session_token' => $sessionToken
            ];

        } catch (Exception $e) {
            error_log("User login failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ'
            ];
        }
    }

    /**
     * ออกจากระบบ
     */
    public function logout($sessionToken) {
        try {
            // ลบ session
            $sql = "UPDATE sessions SET status = 'expired', updated_at = NOW() 
                    WHERE token = :token AND status = 'active'";
            
            $this->db->query($sql, [':token' => $sessionToken]);

            // ดึงข้อมูล user จาก session เพื่อบันทึก log
            $session = $this->getSessionByToken($sessionToken);
            if ($session) {
                $this->logActivity($session['user_id'], 'logout', 'ออกจากระบบ');
            }

            return [
                'success' => true,
                'message' => 'ออกจากระบบเรียบร้อย'
            ];

        } catch (Exception $e) {
            error_log("User logout failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการออกจากระบบ'
            ];
        }
    }

    /**
     * ตรวจสอบ session และดึงข้อมูลผู้ใช้
     */
    public function getUserFromSession($sessionToken) {
        try {
            $sql = "SELECT u.*, s.expires_at 
                    FROM {$this->table} u
                    JOIN sessions s ON u.id = s.user_id
                    WHERE s.token = :token 
                    AND s.status = 'active' 
                    AND s.expires_at > NOW()
                    AND u.status = 'active'";
            
            $user = $this->db->fetch($sql, [':token' => $sessionToken]);
            
            if ($user) {
                // อัปเดตเวลาใช้งานล่าสุด
                $this->updateSessionActivity($sessionToken);
                
                return [
                    'success' => true,
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'role' => $user['role'],
                        'avatar' => $user['avatar'],
                        'phone' => $user['phone']
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => 'Session หมดอายุหรือไม่ถูกต้อง'
            ];

        } catch (Exception $e) {
            error_log("Get user from session failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการตรวจสอบ session'
            ];
        }
    }

    /**
     * อัปเดตข้อมูลผู้ใช้
     */
    public function updateProfile($userId, $firstName, $lastName, $phone = null) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET first_name = :first_name, last_name = :last_name, phone = :phone, updated_at = NOW()
                    WHERE id = :user_id";
            
            $params = [
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':phone' => $phone,
                ':user_id' => $userId
            ];

            $this->db->query($sql, $params);

            // บันทึก log การแก้ไขข้อมูล
            $this->logActivity($userId, 'profile_update', 'แก้ไขข้อมูลโปรไฟล์');

            return [
                'success' => true,
                'message' => 'อัปเดตข้อมูลเรียบร้อย'
            ];

        } catch (Exception $e) {
            error_log("Profile update failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล'
            ];
        }
    }

    /**
     * เปลี่ยนรหัสผ่าน
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // ดึงข้อมูลผู้ใช้
            $user = $this->findById($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลผู้ใช้'
                ];
            }

            // ตรวจสอบรหัสผ่านปัจจุบัน
            if (!password_verify($currentPassword, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง'
                ];
            }

            // ตรวจสอบความแข็งแรงของรหัสผ่านใหม่
            $passwordValidation = $this->validatePassword($newPassword);
            if (!$passwordValidation['valid']) {
                return [
                    'success' => false,
                    'message' => $passwordValidation['message']
                ];
            }

            // เข้ารหัสรหัสผ่านใหม่
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // อัปเดตรหัสผ่าน
            $sql = "UPDATE {$this->table} SET password = :password, updated_at = NOW() WHERE id = :user_id";
            $this->db->query($sql, [':password' => $hashedPassword, ':user_id' => $userId]);

            // ยกเลิก session ทั้งหมดของผู้ใช้ (ยกเว้น session ปัจจุบัน)
            $sql = "UPDATE sessions SET status = 'expired' 
                    WHERE user_id = :user_id AND status = 'active'";
            $this->db->query($sql, [':user_id' => $userId]);

            // บันทึก log การเปลี่ยนรหัสผ่าน
            $this->logActivity($userId, 'password_change', 'เปลี่ยนรหัสผ่าน');

            return [
                'success' => true,
                'message' => 'เปลี่ยนรหัสผ่านเรียบร้อย'
            ];

        } catch (Exception $e) {
            error_log("Password change failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน'
            ];
        }
    }

    // ---- Private Methods ----

    private function emailExists($email) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = :email";
        return $this->db->count($sql, [':email' => $email]) > 0;
    }

    private function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email";
        return $this->db->fetch($sql, [':email' => $email]);
    }

    private function findById($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :user_id";
        return $this->db->fetch($sql, [':user_id' => $userId]);
    }

    private function validatePassword($password) {
        if (strlen($password) < 6) {
            return [
                'valid' => false,
                'message' => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร'
            ];
        }

        if (!preg_match('/[A-Za-z]/', $password)) {
            return [
                'valid' => false,
                'message' => 'รหัสผ่านต้องมีตัวอักษร'
            ];
        }

        if (!preg_match('/[0-9]/', $password)) {
            return [
                'valid' => false,
                'message' => 'รหัสผ่านต้องมีตัวเลข'
            ];
        }

        return ['valid' => true];
    }

    private function createSession($userId, $rememberMe = false) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = $rememberMe ? 
            date('Y-m-d H:i:s', strtotime('+30 days')) : 
            date('Y-m-d H:i:s', strtotime('+1 day'));

        $sql = "INSERT INTO sessions (user_id, token, expires_at, status, created_at) 
                VALUES (:user_id, :token, :expires_at, 'active', NOW())";

        $params = [
            ':user_id' => $userId,
            ':token' => $token,
            ':expires_at' => $expiresAt
        ];

        $this->db->query($sql, $params);
        return $token;
    }

    private function updateLastLogin($userId) {
        $sql = "UPDATE {$this->table} SET last_login = NOW() WHERE id = :user_id";
        $this->db->query($sql, [':user_id' => $userId]);
    }

    private function updateSessionActivity($sessionToken) {
        $sql = "UPDATE sessions SET updated_at = NOW() WHERE token = :token";
        $this->db->query($sql, [':token' => $sessionToken]);
    }

    private function getSessionByToken($token) {
        $sql = "SELECT * FROM sessions WHERE token = :token";
        return $this->db->fetch($sql, [':token' => $token]);
    }

    private function logActivity($userId, $action, $description) {
        try {
            $sql = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, created_at) 
                    VALUES (:user_id, :action, :description, :ip_address, :user_agent, NOW())";

            $params = [
                ':user_id' => $userId,
                ':action' => $action,
                ':description' => $description,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ];

            $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log("Activity logging failed: " . $e->getMessage());
        }
    }

    /**
     * ดึงประวัติการใช้งานของผู้ใช้
     */
    public function getUserActivity($userId, $page = 1, $perPage = 20) {
        $baseQuery = "SELECT action, description, ip_address, created_at 
                      FROM activity_logs 
                      WHERE user_id = :user_id 
                      ORDER BY created_at DESC";

        return $this->db->paginate($baseQuery, [':user_id' => $userId], $page, $perPage);
    }

    /**
     * ล้าง session หมดอายุ
     */
    public function cleanupExpiredSessions() {
        try {
            $sql = "UPDATE sessions SET status = 'expired' 
                    WHERE status = 'active' AND expires_at < NOW()";
            
            $this->db->query($sql);
            
            return [
                'success' => true,
                'message' => 'ล้าง session หมดอายุเรียบร้อย'
            ];

        } catch (Exception $e) {
            error_log("Session cleanup failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการล้าง session'
            ];
        }
    }
}
?>
