<?php
/**
 * Product Model
 * จัดการข้อมูลสินค้า iPhone
 */

require_once __DIR__ . '/../config/database.php';

class Product {
    private $db;
    private $table = 'products';

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * ดึงสินค้าทั้งหมด
     */
    public function getAllProducts($page = 1, $perPage = 12, $filters = []) {
        try {
            $whereConditions = ['status = "active"'];
            $params = [];

            // ฟิลเตอร์ตามหมวดหมู่
            if (!empty($filters['category'])) {
                $whereConditions[] = 'category = :category';
                $params[':category'] = $filters['category'];
            }

            // ฟิลเตอร์ตามช่วงราคา
            if (!empty($filters['min_price'])) {
                $whereConditions[] = 'price >= :min_price';
                $params[':min_price'] = $filters['min_price'];
            }

            if (!empty($filters['max_price'])) {
                $whereConditions[] = 'price <= :max_price';
                $params[':max_price'] = $filters['max_price'];
            }

            // ค้นหาตามชื่อสินค้า
            if (!empty($filters['search'])) {
                $whereConditions[] = '(name LIKE :search OR description LIKE :search)';
                $params[':search'] = '%' . $filters['search'] . '%';
            }

            // สร้าง WHERE clause
            $whereClause = implode(' AND ', $whereConditions);

            // กำหนดการเรียงลำดับ
            $orderBy = 'created_at DESC';
            if (!empty($filters['sort'])) {
                switch ($filters['sort']) {
                    case 'price_low':
                        $orderBy = 'price ASC';
                        break;
                    case 'price_high':
                        $orderBy = 'price DESC';
                        break;
                    case 'name':
                        $orderBy = 'name ASC';
                        break;
                    case 'popular':
                        $orderBy = 'view_count DESC, created_at DESC';
                        break;
                }
            }

            $baseQuery = "SELECT id, name, category, price, image, storage, color, 
                                 stock_quantity, view_count, created_at
                          FROM {$this->table} 
                          WHERE {$whereClause} 
                          ORDER BY {$orderBy}";

            $result = $this->db->paginate($baseQuery, $params, $page, $perPage);

            return [
                'success' => true,
                'products' => $result['data'],
                'pagination' => $result['pagination']
            ];

        } catch (Exception $e) {
            error_log("Get all products failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลสินค้า'
            ];
        }
    }

    /**
     * ดึงข้อมูลสินค้าตาม ID
     */
    public function getProduct($productId) {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE id = :product_id AND status = 'active'";
            
            $product = $this->db->fetch($sql, [':product_id' => $productId]);

            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบสินค้าที่ต้องการ'
                ];
            }

            // เพิ่มจำนวนการดู
            $this->incrementViewCount($productId);

            return [
                'success' => true,
                'product' => $product
            ];

        } catch (Exception $e) {
            error_log("Get product failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลสินค้า'
            ];
        }
    }

    /**
     * ดึงสินค้าแนะนำ
     */
    public function getFeaturedProducts($limit = 8) {
        try {
            $sql = "SELECT id, name, category, price, image, storage, color, view_count
                    FROM {$this->table} 
                    WHERE status = 'active' AND is_featured = 1
                    ORDER BY view_count DESC, created_at DESC 
                    LIMIT :limit";

            $products = $this->db->fetchAll($sql, [':limit' => $limit]);

            return [
                'success' => true,
                'products' => $products
            ];

        } catch (Exception $e) {
            error_log("Get featured products failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงสินค้าแนะนำ'
            ];
        }
    }

    /**
     * ดึงสินค้าใหม่ล่าสุด
     */
    public function getLatestProducts($limit = 6) {
        try {
            $sql = "SELECT id, name, category, price, image, storage, color, created_at
                    FROM {$this->table} 
                    WHERE status = 'active'
                    ORDER BY created_at DESC 
                    LIMIT :limit";

            $products = $this->db->fetchAll($sql, [':limit' => $limit]);

            return [
                'success' => true,
                'products' => $products
            ];

        } catch (Exception $e) {
            error_log("Get latest products failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงสินค้าใหม่'
            ];
        }
    }

    /**
     * ดึงสินค้าที่เกี่ยวข้อง
     */
    public function getRelatedProducts($productId, $category = null, $limit = 4) {
        try {
            $sql = "SELECT id, name, category, price, image, storage, color
                    FROM {$this->table} 
                    WHERE status = 'active' 
                    AND id != :product_id";

            $params = [':product_id' => $productId];

            if ($category) {
                $sql .= " AND category = :category";
                $params[':category'] = $category;
            }

            $sql .= " ORDER BY RAND() LIMIT :limit";
            $params[':limit'] = $limit;

            $products = $this->db->fetchAll($sql, $params);

            return [
                'success' => true,
                'products' => $products
            ];

        } catch (Exception $e) {
            error_log("Get related products failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงสินค้าที่เกี่ยวข้อง'
            ];
        }
    }

    /**
     * ค้นหาสินค้า
     */
    public function searchProducts($query, $page = 1, $perPage = 12) {
        try {
            $searchQuery = "%{$query}%";
            
            $baseQuery = "SELECT id, name, category, price, image, storage, color, 
                                 stock_quantity, view_count
                          FROM {$this->table} 
                          WHERE status = 'active' 
                          AND (name LIKE :search OR description LIKE :search OR category LIKE :search)
                          ORDER BY 
                            CASE 
                              WHEN name LIKE :search THEN 1
                              WHEN description LIKE :search THEN 2
                              WHEN category LIKE :search THEN 3
                              ELSE 4
                            END,
                            view_count DESC";

            $params = [':search' => $searchQuery];
            $result = $this->db->paginate($baseQuery, $params, $page, $perPage);

            return [
                'success' => true,
                'products' => $result['data'],
                'pagination' => $result['pagination'],
                'query' => $query
            ];

        } catch (Exception $e) {
            error_log("Search products failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการค้นหาสินค้า'
            ];
        }
    }

    /**
     * ดึงหมวดหมู่สินค้าทั้งหมด
     */
    public function getCategories() {
        try {
            $sql = "SELECT category, COUNT(*) as product_count
                    FROM {$this->table} 
                    WHERE status = 'active'
                    GROUP BY category
                    ORDER BY category";

            $categories = $this->db->fetchAll($sql);

            return [
                'success' => true,
                'categories' => $categories
            ];

        } catch (Exception $e) {
            error_log("Get categories failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงหมวดหมู่'
            ];
        }
    }

    /**
     * ดึงช่วงราคาของสินค้า
     */
    public function getPriceRange() {
        try {
            $sql = "SELECT MIN(price) as min_price, MAX(price) as max_price
                    FROM {$this->table} 
                    WHERE status = 'active'";

            $range = $this->db->fetch($sql);

            return [
                'success' => true,
                'price_range' => $range
            ];

        } catch (Exception $e) {
            error_log("Get price range failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงช่วงราคา'
            ];
        }
    }

    /**
     * ตรวจสอบสต็อกสินค้า
     */
    public function checkStock($productId, $quantity = 1) {
        try {
            $sql = "SELECT stock_quantity FROM {$this->table} 
                    WHERE id = :product_id AND status = 'active'";
            
            $product = $this->db->fetch($sql, [':product_id' => $productId]);

            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบสินค้า'
                ];
            }

            $available = $product['stock_quantity'] >= $quantity;

            return [
                'success' => true,
                'available' => $available,
                'stock_quantity' => $product['stock_quantity'],
                'requested_quantity' => $quantity
            ];

        } catch (Exception $e) {
            error_log("Check stock failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการตรวจสอบสต็อก'
            ];
        }
    }

    /**
     * อัปเดตสต็อกสินค้า
     */
    public function updateStock($productId, $quantity, $operation = 'decrease') {
        try {
            $this->db->beginTransaction();

            // ตรวจสอบสต็อกปัจจุบัน
            $sql = "SELECT stock_quantity FROM {$this->table} 
                    WHERE id = :product_id AND status = 'active' FOR UPDATE";
            
            $product = $this->db->fetch($sql, [':product_id' => $productId]);

            if (!$product) {
                $this->db->rollback();
                return [
                    'success' => false,
                    'message' => 'ไม่พบสินค้า'
                ];
            }

            // คำนวณสต็อกใหม่
            $newStock = $operation === 'decrease' ? 
                        $product['stock_quantity'] - $quantity : 
                        $product['stock_quantity'] + $quantity;

            if ($newStock < 0) {
                $this->db->rollback();
                return [
                    'success' => false,
                    'message' => 'สต็อกสินค้าไม่เพียงพอ'
                ];
            }

            // อัปเดตสต็อก
            $sql = "UPDATE {$this->table} 
                    SET stock_quantity = :new_stock, updated_at = NOW() 
                    WHERE id = :product_id";

            $this->db->query($sql, [
                ':new_stock' => $newStock,
                ':product_id' => $productId
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'อัปเดตสต็อกเรียบร้อย',
                'old_stock' => $product['stock_quantity'],
                'new_stock' => $newStock
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Update stock failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตสต็อก'
            ];
        }
    }

    /**
     * เพิ่มจำนวนการดูสินค้า
     */
    private function incrementViewCount($productId) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET view_count = view_count + 1 
                    WHERE id = :product_id";
            
            $this->db->query($sql, [':product_id' => $productId]);
        } catch (Exception $e) {
            error_log("Increment view count failed: " . $e->getMessage());
        }
    }

    /**
     * ดึงสถิติสินค้า
     */
    public function getProductStats() {
        try {
            $stats = [];

            // จำนวนสินค้าทั้งหมด
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'active'";
            $stats['total_products'] = $this->db->count($sql);

            // จำนวนสินค้าในแต่ละหมวดหมู่
            $sql = "SELECT category, COUNT(*) as count 
                    FROM {$this->table} 
                    WHERE status = 'active' 
                    GROUP BY category";
            $stats['by_category'] = $this->db->fetchAll($sql);

            // สินค้าที่มียอดชมมากที่สุด
            $sql = "SELECT name, view_count 
                    FROM {$this->table} 
                    WHERE status = 'active' 
                    ORDER BY view_count DESC 
                    LIMIT 5";
            $stats['most_viewed'] = $this->db->fetchAll($sql);

            // สินค้าที่สต็อกน้อย
            $sql = "SELECT name, stock_quantity 
                    FROM {$this->table} 
                    WHERE status = 'active' AND stock_quantity <= 5
                    ORDER BY stock_quantity ASC";
            $stats['low_stock'] = $this->db->fetchAll($sql);

            return [
                'success' => true,
                'stats' => $stats
            ];

        } catch (Exception $e) {
            error_log("Get product stats failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงสถิติสินค้า'
            ];
        }
    }

    /**
     * เพิ่มสินค้าใหม่ (สำหรับ Admin)
     */
    public function addProduct($data) {
        try {
            $sql = "INSERT INTO {$this->table} 
                    (name, category, price, description, image, storage, color, 
                     stock_quantity, is_featured, status, created_at) 
                    VALUES (:name, :category, :price, :description, :image, :storage, :color, 
                            :stock_quantity, :is_featured, 'active', NOW())";

            $params = [
                ':name' => $data['name'],
                ':category' => $data['category'],
                ':price' => $data['price'],
                ':description' => $data['description'] ?? '',
                ':image' => $data['image'] ?? '',
                ':storage' => $data['storage'] ?? '',
                ':color' => $data['color'] ?? '',
                ':stock_quantity' => $data['stock_quantity'] ?? 0,
                ':is_featured' => $data['is_featured'] ?? 0
            ];

            $this->db->query($sql, $params);
            $productId = $this->db->lastInsertId();

            return [
                'success' => true,
                'message' => 'เพิ่มสินค้าเรียบร้อย',
                'product_id' => $productId
            ];

        } catch (Exception $e) {
            error_log("Add product failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเพิ่มสินค้า'
            ];
        }
    }

    /**
     * อัปเดตข้อมูลสินค้า (สำหรับ Admin)
     */
    public function updateProduct($productId, $data) {
        try {
            $fields = [];
            $params = [':product_id' => $productId];

            // สร้างคำสั่ง SQL แบบ dynamic
            foreach ($data as $field => $value) {
                if (in_array($field, ['name', 'category', 'price', 'description', 'image', 'storage', 'color', 'stock_quantity', 'is_featured', 'status'])) {
                    $fields[] = "{$field} = :{$field}";
                    $params[":{$field}"] = $value;
                }
            }

            if (empty($fields)) {
                return [
                    'success' => false,
                    'message' => 'ไม่มีข้อมูลที่ต้องอัปเดต'
                ];
            }

            $fields[] = "updated_at = NOW()";
            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :product_id";

            $this->db->query($sql, $params);

            return [
                'success' => true,
                'message' => 'อัปเดตสินค้าเรียบร้อย'
            ];

        } catch (Exception $e) {
            error_log("Update product failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตสินค้า'
            ];
        }
    }

    /**
     * ลบสินค้า (Soft Delete)
     */
    public function deleteProduct($productId) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET status = 'deleted', updated_at = NOW() 
                    WHERE id = :product_id";

            $this->db->query($sql, [':product_id' => $productId]);

            return [
                'success' => true,
                'message' => 'ลบสินค้าเรียบร้อย'
            ];

        } catch (Exception $e) {
            error_log("Delete product failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบสินค้า'
            ];
        }
    }
}
?>
