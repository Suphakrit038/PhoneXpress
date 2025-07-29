/**
 * MySQL API Client
 * จัดการการเชื่อมต่อกับ MySQL ผ่าน PHP API
 * แทนที่ระบบ IndexedDB เดิม
 */

class PhonezoneAPI {
    constructor() {
        this.baseURL = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
        this.sessionToken = this.getSessionToken();
        this.user = null;
        
        // ตรวจสอบ session เมื่อโหลดหน้า
        this.checkSession();
    }

    /**
     * ดึง session token จาก localStorage
     */
    getSessionToken() {
        return localStorage.getItem('phonezone_session_token');
    }

    /**
     * บันทึก session token
     */
    setSessionToken(token) {
        if (token) {
            localStorage.setItem('phonezone_session_token', token);
            this.sessionToken = token;
        } else {
            localStorage.removeItem('phonezone_session_token');
            this.sessionToken = null;
        }
    }

    /**
     * สร้าง headers สำหรับ API request
     */
    getHeaders() {
        const headers = {
            'Content-Type': 'application/json',
        };

        if (this.sessionToken) {
            headers['Authorization'] = `Bearer ${this.sessionToken}`;
        }

        return headers;
    }

    /**
     * ทำ API request
     */
    async makeRequest(endpoint, options = {}) {
        try {
            const url = `${this.baseURL}/${endpoint}`;
            const config = {
                ...options,
                headers: {
                    ...this.getHeaders(),
                    ...options.headers
                }
            };

            const response = await fetch(url, config);
            const data = await response.json();

            // ตรวจสอบ session หมดอายุ
            if (data.error_code === 'SESSION_EXPIRED') {
                this.handleSessionExpired();
                throw new Error('Session หมดอายุ กรุณาเข้าสู่ระบบใหม่');
            }

            return data;
        } catch (error) {
            console.error('API Request failed:', error);
            throw error;
        }
    }

    /**
     * จัดการเมื่อ session หมดอายุ
     */
    handleSessionExpired() {
        this.setSessionToken(null);
        this.user = null;
        
        // แสดงแจ้งเตือนและไปหน้าหลัก
        if (typeof showMessage === 'function') {
            showMessage('Session หมดอายุ กรุณาเข้าสู่ระบบใหม่', 'warning');
        }
        
        // รีโหลดหน้าเพื่อแสดง UI ที่ถูกต้อง
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    }

    // === Authentication Methods ===

    /**
     * เข้าสู่ระบบ
     */
    async login(email, password, rememberMe = false) {
        try {
            const data = await this.makeRequest('api/auth.php?action=login', {
                method: 'POST',
                body: JSON.stringify({
                    email,
                    password,
                    remember_me: rememberMe
                })
            });

            if (data.success) {
                this.setSessionToken(data.session_token);
                this.user = data.user;
            }

            return data;
        } catch (error) {
            return {
                success: false,
                message: error.message || 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ'
            };
        }
    }

    /**
     * สมัครสมาชิก
     */
    async register(userData) {
        try {
            const data = await this.makeRequest('api/auth.php?action=register', {
                method: 'POST',
                body: JSON.stringify(userData)
            });

            return data;
        } catch (error) {
            return {
                success: false,
                message: error.message || 'เกิดข้อผิดพลาดในการสมัครสมาชิก'
            };
        }
    }

    /**
     * ออกจากระบบ
     */
    async logout() {
        try {
            const data = await this.makeRequest('api/auth.php?action=logout', {
                method: 'POST',
                body: JSON.stringify({
                    session_token: this.sessionToken
                })
            });

            this.setSessionToken(null);
            this.user = null;

            return data;
        } catch (error) {
            // แม้จะเกิดข้อผิดพลาดก็ให้ logout local
            this.setSessionToken(null);
            this.user = null;
            
            return {
                success: true,
                message: 'ออกจากระบบเรียบร้อย'
            };
        }
    }

    /**
     * ตรวจสอบ session
     */
    async checkSession() {
        if (!this.sessionToken) {
            return { success: false, authenticated: false };
        }

        try {
            const data = await this.makeRequest('api/auth.php?action=check_session', {
                method: 'POST',
                body: JSON.stringify({
                    session_token: this.sessionToken
                })
            });

            if (data.success) {
                this.user = data.user;
            } else {
                this.setSessionToken(null);
                this.user = null;
            }

            return data;
        } catch (error) {
            this.setSessionToken(null);
            this.user = null;
            return { success: false, authenticated: false };
        }
    }

    /**
     * อัปเดตข้อมูลโปรไฟล์
     */
    async updateProfile(profileData) {
        try {
            const data = await this.makeRequest('api/auth.php?action=update_profile', {
                method: 'POST',
                body: JSON.stringify(profileData)
            });

            return data;
        } catch (error) {
            return {
                success: false,
                message: error.message || 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล'
            };
        }
    }

    /**
     * เปลี่ยนรหัสผ่าน
     */
    async changePassword(currentPassword, newPassword, confirmPassword) {
        try {
            const data = await this.makeRequest('api/auth.php?action=change_password', {
                method: 'POST',
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword,
                    confirm_password: confirmPassword
                })
            });

            return data;
        } catch (error) {
            return {
                success: false,
                message: error.message || 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน'
            };
        }
    }

    // === Product Methods ===

    /**
     * ดึงสินค้าทั้งหมด
     */
    async getAllProducts(filters = {}) {
        try {
            const params = new URLSearchParams(filters);
            const data = await this.makeRequest(`api/products.php?action=get_all&${params}`, {
                method: 'GET'
            });

            return data;
        } catch (error) {
            return {
                success: false,
                message: error.message || 'เกิดข้อผิดพลาดในการดึงข้อมูลสินค้า'
            };
        }
    }

    /**
     * ดึงข้อมูลสินค้าตาม ID
     */
    async getProduct(productId) {
        try {
            const data = await this.makeRequest(`api/products.php?action=get_product&id=${productId}`, {
                method: 'GET'
            });

            return data;
        } catch (error) {
            return {
                success: false,
                message: error.message || 'เกิดข้อผิดพลาดในการดึงข้อมูลสินค้า'
            };
        }
    }

    /**
     * ดึงสินค้าแนะนำ
     */
    async getFeaturedProducts(limit = 8) {
        try {
            const data = await this.makeRequest(`api/products.php?action=get_featured&limit=${limit}`, {
                method: 'GET'
            });

            return data;
        } catch (error) {
            return {
                success: false,
                message: error.message || 'เกิดข้อผิดพลาดในการดึงสินค้าแนะนำ'
            };
        }
    }

    /**
     * ดึงสินค้าใหม่ล่าสุด
     */
    async getLatestProducts(limit = 6) {
        try {
            const data = await this.makeRequest(`api/products.php?action=get_latest&limit=${limit}`, {
                method: 'GET'
            });

            return data;
        } catch (error) {
            return {
                success: false,
                message: error.message || 'เกิดข้อผิดพลาดในการดึงสินค้าใหม่'
            };
        }
    }

    /**
     * ค้นหาสินค้า
     */
    async searchProducts(query, page = 1, perPage = 12) {
        try {
            const params = new URLSearchParams({
                action: 'search',
                q: query,
                page: page,
                per_page: perPage
            });

            const data = await this.makeRequest(`api/products.php?${params}`, {
                method: 'GET'
            });

            return data;
        } catch (error) {
            return {
                success: false,
                message: error.message || 'เกิดข้อผิดพลาดในการค้นหาสินค้า'
            };
        }
    }

    /**
     * ดึงหมวดหมู่สินค้า
     */
    async getCategories() {
        try {
            const data = await this.makeRequest('api/products.php?action=get_categories', {
                method: 'GET'
            });

            return data;
        } catch (error) {
            return {
                success: false,
                message: error.message || 'เกิดข้อผิดพลาดในการดึงหมวดหมู่'
            };
        }
    }

    /**
     * ตรวจสอบสต็อกสินค้า
     */
    async checkStock(productId, quantity = 1) {
        try {
            const data = await this.makeRequest(`api/products.php?action=check_stock&product_id=${productId}&quantity=${quantity}`, {
                method: 'GET'
            });

            return data;
        } catch (error) {
            return {
                success: false,
                message: error.message || 'เกิดข้อผิดพลาดในการตรวจสอบสต็อก'
            };
        }
    }

    // === Cart Methods (ใช้ localStorage ชั่วคราว) ===

    /**
     * ดึงข้อมูลตะกร้าสินค้า
     */
    getCart() {
        try {
            const cartData = localStorage.getItem('phonezone_cart');
            return cartData ? JSON.parse(cartData) : [];
        } catch (error) {
            console.error('Error getting cart:', error);
            return [];
        }
    }

    /**
     * บันทึกข้อมูลตะกร้าสินค้า
     */
    saveCart(cartItems) {
        try {
            localStorage.setItem('phonezone_cart', JSON.stringify(cartItems));
            
            // ส่ง event เพื่อแจ้งการเปลี่ยนแปลง
            window.dispatchEvent(new CustomEvent('cartUpdated', {
                detail: { items: cartItems }
            }));
            
            return true;
        } catch (error) {
            console.error('Error saving cart:', error);
            return false;
        }
    }

    /**
     * เพิ่มสินค้าในตะกร้า
     */
    async addToCart(productId, quantity = 1) {
        try {
            // ตรวจสอบสต็อกก่อน
            const stockCheck = await this.checkStock(productId, quantity);
            if (!stockCheck.success || !stockCheck.available) {
                return {
                    success: false,
                    message: 'สินค้าไม่มีในสต็อกเพียงพอ'
                };
            }

            // ดึงข้อมูลสินค้า
            const productResult = await this.getProduct(productId);
            if (!productResult.success) {
                return {
                    success: false,
                    message: 'ไม่พบข้อมูลสินค้า'
                };
            }

            const product = productResult.product;
            const cart = this.getCart();
            
            // ตรวจสอบว่ามีสินค้านี้ในตะกร้าแล้วหรือไม่
            const existingItemIndex = cart.findIndex(item => item.product_id == productId);
            
            if (existingItemIndex >= 0) {
                // เพิ่มจำนวนสินค้าที่มีอยู่
                cart[existingItemIndex].quantity += quantity;
            } else {
                // เพิ่มสินค้าใหม่
                cart.push({
                    product_id: productId,
                    name: product.name,
                    price: product.price,
                    image: product.image,
                    quantity: quantity,
                    category: product.category,
                    storage: product.storage,
                    color: product.color
                });
            }

            this.saveCart(cart);

            return {
                success: true,
                message: 'เพิ่มสินค้าในตะกร้าเรียบร้อย',
                cart_count: cart.reduce((total, item) => total + item.quantity, 0)
            };
        } catch (error) {
            return {
                success: false,
                message: error.message || 'เกิดข้อผิดพลาดในการเพิ่มสินค้า'
            };
        }
    }

    /**
     * อัปเดตจำนวนสินค้าในตะกร้า
     */
    updateCartQuantity(productId, quantity) {
        try {
            const cart = this.getCart();
            const itemIndex = cart.findIndex(item => item.product_id == productId);
            
            if (itemIndex >= 0) {
                if (quantity <= 0) {
                    // ลบสินค้าออกจากตะกร้า
                    cart.splice(itemIndex, 1);
                } else {
                    // อัปเดตจำนวน
                    cart[itemIndex].quantity = quantity;
                }
                
                this.saveCart(cart);
                
                return {
                    success: true,
                    message: 'อัปเดตตะกร้าเรียบร้อย',
                    cart_count: cart.reduce((total, item) => total + item.quantity, 0)
                };
            }
            
            return {
                success: false,
                message: 'ไม่พบสินค้าในตะกร้า'
            };
        } catch (error) {
            return {
                success: false,
                message: 'เกิดข้อผิดพลาดในการอัปเดตตะกร้า'
            };
        }
    }

    /**
     * ลบสินค้าออกจากตะกร้า
     */
    removeFromCart(productId) {
        return this.updateCartQuantity(productId, 0);
    }

    /**
     * ล้างตะกร้าสินค้า
     */
    clearCart() {
        try {
            localStorage.removeItem('phonezone_cart');
            
            window.dispatchEvent(new CustomEvent('cartUpdated', {
                detail: { items: [] }
            }));
            
            return {
                success: true,
                message: 'ล้างตะกร้าเรียบร้อย'
            };
        } catch (error) {
            return {
                success: false,
                message: 'เกิดข้อผิดพลาดในการล้างตะกร้า'
            };
        }
    }

    /**
     * คำนวณราคารวมในตะกร้า
     */
    getCartTotal() {
        const cart = this.getCart();
        return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    // === Utility Methods ===

    /**
     * ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
     */
    isAuthenticated() {
        return this.sessionToken !== null && this.user !== null;
    }

    /**
     * ดึงข้อมูลผู้ใช้ปัจจุบัน
     */
    getCurrentUser() {
        return this.user;
    }

    /**
     * แสดงข้อความแจ้งเตือน
     */
    showMessage(message, type = 'info') {
        if (typeof showMessage === 'function') {
            showMessage(message, type);
        } else {
            console.log(`${type.toUpperCase()}: ${message}`);
        }
    }

    /**
     * จัดรูปแบบราคา
     */
    formatPrice(price) {
        return new Intl.NumberFormat('th-TH', {
            style: 'currency',
            currency: 'THB'
        }).format(price);
    }

    /**
     * จัดรูปแบบวันที่
     */
    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('th-TH', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}

// สร้าง instance หลักของ API
window.phonezoneAPI = new PhonezoneAPI();

// Export สำหรับใช้ใน modules อื่น
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PhonezoneAPI;
}
