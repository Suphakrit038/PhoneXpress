/**
 * Authentication Management System
 * จัดการระบบ Login/Register/Logout พร้อม UI
 * อัปเดตเพื่อใช้งาน MySQL API แทน IndexedDB
 */

// ตัวแปรสำหรับเก็บสถานะ
let currentUser = null;
let isAuthenticating = false;

// รอให้ API พร้อม
document.addEventListener('DOMContentLoaded', async function() {
    // รอให้ phonezoneAPI พร้อม
    if (typeof phonezoneAPI === 'undefined') {
        console.error('PhonezoneAPI not loaded');
        return;
    }
    
    // ตรวจสอบการเข้าสู่ระบบเมื่อโหลดหน้า
    await checkAuthenticationStatus();
    
    // ผูก event listeners
    bindAuthenticationEvents();
    
    // ตรวจสอบ URL สำหรับ auto-redirect
    checkAuthRedirect();
});

/**
 * ผูก event listeners สำหรับระบบ authentication
 */
function bindAuthenticationEvents() {
    // Login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    // Register form
    const registerForm = document.getElementById('registerForm'); 
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }

    // Logout buttons
    const logoutButtons = document.querySelectorAll('.logout-btn, .btn-logout');
    logoutButtons.forEach(btn => {
        btn.addEventListener('click', handleLogout);
    });

    // Switch between login/register
    const switchToRegister = document.getElementById('switchToRegister');
    const switchToLogin = document.getElementById('switchToLogin');
    
    if (switchToRegister) {
        switchToRegister.addEventListener('click', (e) => {
            e.preventDefault();
            showRegisterModal();
        });
    }
    
    if (switchToLogin) {
        switchToLogin.addEventListener('click', (e) => {
            e.preventDefault();
            showLoginModal();
        });
    }

    // Modal management
    bindModalEvents();
    
    // Profile management
    bindProfileEvents();
}

/**
 * ผูก events สำหรับ modals
 */
function bindModalEvents() {
    // Close buttons
    const closeButtons = document.querySelectorAll('.modal .close, .modal-close');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', closeModals);
    });

    // Click outside modal to close
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModals();
            }
        });
    });

    // Auth modal switches
    const authTabs = document.querySelectorAll('.auth-tab');
    authTabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            const action = tab.dataset.action;
            if (action === 'login') {
                showLoginModal();
            } else if (action === 'register') {
                showRegisterModal();
            }
        });
    });
}

/**
 * ผูก events สำหรับ profile management
 */
function bindProfileEvents() {
    // Profile button
    const profileButton = document.getElementById('profileButton');
    if (profileButton) {
        profileButton.addEventListener('click', showProfileModal);
    }

    // User menu toggle
    const userAvatar = document.querySelector('.user-avatar');
    if (userAvatar) {
        userAvatar.addEventListener('click', toggleUserMenu);
    }

    // Profile form
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', handleProfileUpdate);
    }

    // Change password form
    const passwordForm = document.getElementById('changePasswordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', handlePasswordChange);
    }

    // Profile tabs
    const profileTabs = document.querySelectorAll('.profile-tab');
    profileTabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            switchProfileTab(tab.dataset.tab);
        });
    });

    // Settings toggles
    const settingToggles = document.querySelectorAll('.toggle-switch');
    settingToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            toggle.classList.toggle('active');
            const setting = toggle.dataset.setting;
            const isActive = toggle.classList.contains('active');
            updateSetting(setting, isActive);
        });
    });
}

/**
 * ตรวจสอบสถานะผู้ใช้เมื่อโหลดหน้า
 */
async function checkAuthenticationStatus() {
    try {
        const result = await phonezoneAPI.checkSession();
        if (result.success && result.authenticated) {
            currentUser = result.user;
            updateUIForAuthenticatedUser(result.user);
        } else {
            currentUser = null;
            updateUIForGuestUser();
        }
    } catch (error) {
        console.error('Error checking authentication:', error);
        currentUser = null;
        updateUIForGuestUser();
    }
}

/**
 * จัดการการเข้าสู่ระบบ
 */
async function handleLogin(event) {
    event.preventDefault();
    
    if (isAuthenticating) return;
    
    const form = event.target;
    const formData = new FormData(form);
    
    const email = formData.get('email');
    const password = formData.get('password');
    const rememberMe = formData.get('remember_me') || false;
    
    // ตรวจสอบข้อมูล
    if (!email || !password) {
        showError('กรุณาใส่อีเมลและรหัสผ่าน');
        return;
    }
    
    isAuthenticating = true;
    showLoading('กำลังเข้าสู่ระบบ...');
    
    try {
        const result = await phonezoneAPI.login(email, password, rememberMe);
        
        if (result.success) {
            currentUser = result.user;
            showMessage('เข้าสู่ระบบสำเร็จ!', 'success');
            closeModals();
            updateUIForAuthenticatedUser(result.user);
            
            // Redirect ถ้ามี
            const redirectUrl = getRedirectUrl();
            if (redirectUrl) {
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 1000);
            }
        } else {
            showError(result.message || 'ไม่สามารถเข้าสู่ระบบได้');
        }
    } catch (error) {
        console.error('Login error:', error);
        showError('เกิดข้อผิดพลาดในการเข้าสู่ระบบ');
    } finally {
        isAuthenticating = false;
        hideLoading();
    }
}

/**
 * จัดการการสมัครสมาชิก
 */
async function handleRegister(event) {
    event.preventDefault();
    
    if (isAuthenticating) return;
    
    const form = event.target;
    const formData = new FormData(form);
    
    const userData = {
        username: formData.get('username'),
        email: formData.get('email'),
        password: formData.get('password'),
        confirm_password: formData.get('confirm_password'),
        name: formData.get('name'),
        surname: formData.get('surname'),
        tel: formData.get('tel')
    };
    
    // ตรวจสอบข้อมูล
    const validation = validateRegistrationData(userData);
    if (!validation.valid) {
        showError(validation.message);
        return;
    }
    
    isAuthenticating = true;
    showLoading('กำลังสมัครสมาชิก...');
    
    try {
        const result = await phonezoneAPI.register(userData);
        
        if (result.success) {
            showMessage('สমัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ', 'success');
            showLoginModal();
            
            // ใส่อีเมลที่สมัครไว้ในฟอร์ม login
            const emailInput = document.querySelector('#loginForm input[name="email"]');
            if (emailInput) {
                emailInput.value = userData.email;
            }
        } else {
            showError(result.message || 'ไม่สามารถสมัครสมาชิกได้');
        }
    } catch (error) {
        console.error('Registration error:', error);
        showError('เกิดข้อผิดพลาดในการสมัครสมาชิก');
    } finally {
        isAuthenticating = false;
        hideLoading();
    }
}

/**
 * จัดการการออกจากระบบ
 */
async function handleLogout(event) {
    if (event) {
        event.preventDefault();
    }
    
    try {
        showLoading('กำลังออกจากระบบ...');
        
        const result = await phonezoneAPI.logout();
        
        currentUser = null;
        updateUIForGuestUser();
        showMessage('ออกจากระบบเรียบร้อย', 'success');
        
        // รีโหลดหน้าหลังจาก logout
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 1000);
        
    } catch (error) {
        console.error('Logout error:', error);
        showError('เกิดข้อผิดพลาดในการออกจากระบบ');
    } finally {
        hideLoading();
    }
}

// === Modal Management ===

/**
 * แสดง modal เข้าสู่ระบบ
 */
function showLoginModal() {
    const modal = document.getElementById('authModal');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    if (modal && loginForm) {
        // แสดง login form และซ่อน register form
        loginForm.style.display = 'block';
        if (registerForm) {
            registerForm.style.display = 'none';
        }
        
        // แสดง modal
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // focus ที่ input แรก
        const firstInput = loginForm.querySelector('input[type="email"]');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
}

/**
 * แสดง modal สมัครสมาชิก
 */
function showRegisterModal() {
    const modal = document.getElementById('authModal');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    if (modal && registerForm) {
        // แสดง register form และซ่อน login form
        registerForm.style.display = 'block';
        if (loginForm) {
            loginForm.style.display = 'none';
        }
        
        // แสดง modal
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // focus ที่ input แรก
        const firstInput = registerForm.querySelector('input[type="email"]');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
}

/**
 * ปิด modals ทั้งหมด
 */
function closeModals() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.classList.remove('show');
    });
    
    document.body.style.overflow = 'auto';
    clearErrors();
}

// === UI Updates ===

/**
 * อัปเดต UI สำหรับผู้ใช้ที่เข้าสู่ระบบแล้ว
 */
function updateUIForAuthenticatedUser(user) {
    // ซ่อนปุ่ม login/register
    const authButtons = document.querySelectorAll('.auth-btn, .login-btn, .register-btn');
    authButtons.forEach(btn => {
        btn.style.display = 'none';
    });
    
    // แสดงข้อมูลผู้ใช้
    const userElements = document.querySelectorAll('.user-info, .user-menu');
    userElements.forEach(el => {
        el.style.display = 'block';
    });
    
    // อัปเดตชื่อผู้ใช้
    const userNameElements = document.querySelectorAll('.user-name');
    userNameElements.forEach(el => {
        el.textContent = `${user.name} ${user.surname}`;
    });
    
    // อัปเดตอีเมล
    const userEmailElements = document.querySelectorAll('.user-email');
    userEmailElements.forEach(el => {
        el.textContent = user.email;
    });
}

/**
 * อัปเดต UI สำหรับ guest user
 */
function updateUIForGuestUser() {
    // แสดงปุ่ม login/register
    const authButtons = document.querySelectorAll('.auth-btn, .login-btn, .register-btn');
    authButtons.forEach(btn => {
        btn.style.display = 'inline-block';
    });
    
    // ซ่อนข้อมูลผู้ใช้
    const userElements = document.querySelectorAll('.user-info, .user-menu');
    userElements.forEach(el => {
        el.style.display = 'none';
    });
}

// === Validation & Utilities ===

/**
 * ตรวจสอบข้อมูลการสมัครสมาชิก
 */
function validateRegistrationData(data) {
    if (!data.username || !data.email || !data.password || !data.name || !data.surname) {
        return { valid: false, message: 'กรุณาใส่ข้อมูลให้ครบถ้วน' };
    }
    
    if (!isValidEmail(data.email)) {
        return { valid: false, message: 'รูปแบบอีเมลไม่ถูกต้อง' };
    }
    
    if (data.password.length < 6) {
        return { valid: false, message: 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร' };
    }
    
    if (data.password !== data.confirm_password) {
        return { valid: false, message: 'รหัสผ่านไม่ตรงกัน' };
    }
    
    return { valid: true };
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showMessage(message, type = 'info') {
    console.log(`${type.toUpperCase()}: ${message}`);
    // อาจเพิ่มระบบแสดงข้อความจริงในอนาคต
}

function showError(message) {
    showMessage(message, 'error');
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(el => {
        el.textContent = message;
        el.style.display = 'block';
    });
}

function clearErrors() {
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(el => {
        el.textContent = '';
        el.style.display = 'none';
    });
}

function showLoading(message = 'กำลังโหลด...') {
    console.log('Loading:', message);
}

function hideLoading() {
    console.log('Loading finished');
}

function checkAuthRedirect() {
    const urlParams = new URLSearchParams(window.location.search);
    const action = urlParams.get('action');
    
    if (action === 'login') {
        showLoginModal();
    } else if (action === 'register') {
        showRegisterModal();
    }
}

function getRedirectUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('redirect') || null;
}

// Global functions for compatibility
window.openLoginModal = showLoginModal;
window.openSignupModal = showRegisterModal;
window.closeAuthModal = closeModals;
window.handleLogout = handleLogout;
window.getCurrentUser = () => currentUser;
window.isUserAuthenticated = () => !!currentUser;

// === Profile & Settings Functions ===

/**
 * แสดง modal โปรไฟล์
 */
function showProfileModal() {
    const modal = document.getElementById('profileModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // โหลดข้อมูลผู้ใช้
        loadUserProfile();
        
        // แสดง tab แรก
        switchProfileTab('profile');
    }
}

/**
 * สลับ tab ในหน้าโปรไฟล์
 */
function switchProfileTab(tabName) {
    // ซ่อน content ทั้งหมด
    const contents = document.querySelectorAll('.profile-content');
    contents.forEach(content => {
        content.classList.remove('active');
    });
    
    // ยกเลิก active ของ tab ทั้งหมด
    const tabs = document.querySelectorAll('.profile-tab');
    tabs.forEach(tab => {
        tab.classList.remove('active');
    });
    
    // แสดง content และ tab ที่เลือก
    const selectedContent = document.getElementById(`${tabName}Content`);
    const selectedTab = document.querySelector(`[data-tab="${tabName}"]`);
    
    if (selectedContent) selectedContent.classList.add('active');
    if (selectedTab) selectedTab.classList.add('active');
}

/**
 * โหลดข้อมูลผู้ใช้สำหรับแสดงในโปรไฟล์
 */
async function loadUserProfile() {
    if (!currentUser) return;
    
    try {
        // อัปเดตข้อมูลในฟอร์มโปรไฟล์
        const fields = {
            'profileUsername': currentUser.username || '',
            'profileName': currentUser.name || '',
            'profileSurname': currentUser.surname || '',
            'profileEmail': currentUser.email || '',
            'profileTel': currentUser.tel || ''
        };
        
        Object.keys(fields).forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) field.value = fields[fieldId];
        });
        
        // อัปเดตข้อมูลในส่วนแสดงผล
        const infoElements = {
            'userInfoName': `${currentUser.name} ${currentUser.surname}`,
            'userInfoUsername': currentUser.username,
            'userInfoEmail': currentUser.email,
            'userInfoTel': currentUser.tel || '-',
            'userInfoJoined': new Date(currentUser.created_at).toLocaleDateString('th-TH')
        };
        
        Object.keys(infoElements).forEach(elementId => {
            const element = document.getElementById(elementId);
            if (element) element.textContent = infoElements[elementId];
        });
        
    } catch (error) {
        console.error('Error loading user profile:', error);
    }
}

/**
 * จัดการการอัปเดตโปรไฟล์
 */
async function handleProfileUpdate(event) {
    event.preventDefault();
    
    if (isAuthenticating) return;
    
    const form = event.target;
    const formData = new FormData(form);
    
    const profileData = {
        username: formData.get('username'),
        name: formData.get('name'),
        surname: formData.get('surname'),
        email: formData.get('email'),
        tel: formData.get('tel')
    };
    
    // ตรวจสอบข้อมูล
    if (!profileData.username || !profileData.name || !profileData.surname || !profileData.email) {
        showError('กรุณาใส่ข้อมูลให้ครบถ้วน');
        return;
    }
    
    isAuthenticating = true;
    showLoading('กำลังอัปเดตข้อมูล...');
    
    try {
        const result = await phonezoneAPI.updateProfile(profileData);
        
        if (result.success) {
            currentUser = { ...currentUser, ...profileData };
            showMessage('อัปเดตข้อมูลเรียบร้อย!', 'success');
            updateUIForAuthenticatedUser(currentUser);
            loadUserProfile(); // รีเฟรชข้อมูล
        } else {
            showError(result.message || 'ไม่สามารถอัปเดตข้อมูลได้');
        }
    } catch (error) {
        console.error('Profile update error:', error);
        showError('เกิดข้อผิดพลาดในการอัปเดตข้อมูล');
    } finally {
        isAuthenticating = false;
        hideLoading();
    }
}

/**
 * จัดการการเปลี่ยนรหัสผ่าน
 */
async function handlePasswordChange(event) {
    event.preventDefault();
    
    if (isAuthenticating) return;
    
    const form = event.target;
    const formData = new FormData(form);
    
    const passwordData = {
        current_password: formData.get('current_password'),
        new_password: formData.get('new_password'),
        confirm_password: formData.get('confirm_password')
    };
    
    // ตรวจสอบข้อมูล
    if (!passwordData.current_password || !passwordData.new_password || !passwordData.confirm_password) {
        showError('กรุณาใส่ข้อมูลให้ครบถ้วน');
        return;
    }
    
    if (passwordData.new_password.length < 6) {
        showError('รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร');
        return;
    }
    
    if (passwordData.new_password !== passwordData.confirm_password) {
        showError('รหัสผ่านใหม่ไม่ตรงกัน');
        return;
    }
    
    isAuthenticating = true;
    showLoading('กำลังเปลี่ยนรหัสผ่าน...');
    
    try {
        const result = await phonezoneAPI.changePassword(passwordData);
        
        if (result.success) {
            showMessage('เปลี่ยนรหัสผ่านเรียบร้อย!', 'success');
            form.reset();
        } else {
            showError(result.message || 'ไม่สามารถเปลี่ยนรหัสผ่านได้');
        }
    } catch (error) {
        console.error('Password change error:', error);
        showError('เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน');
    } finally {
        isAuthenticating = false;
        hideLoading();
    }
}

/**
 * อัปเดตการตั้งค่า
 */
async function updateSetting(setting, value) {
    try {
        const result = await phonezoneAPI.updateUserPreference(setting, value);
        
        if (result.success) {
            showMessage('บันทึกการตั้งค่าเรียบร้อย', 'success');
        } else {
            showError('ไม่สามารถบันทึกการตั้งค่าได้');
        }
    } catch (error) {
        console.error('Setting update error:', error);
        showError('เกิดข้อผิดพลาดในการบันทึกการตั้งค่า');
    }
}

/**
 * toggle user menu
 */
function toggleUserMenu() {
    const dropdownMenu = document.querySelector('.dropdown-menu');
    if (dropdownMenu) {
        const isVisible = dropdownMenu.style.opacity === '1';
        dropdownMenu.style.opacity = isVisible ? '0' : '1';
        dropdownMenu.style.visibility = isVisible ? 'hidden' : 'visible';
        dropdownMenu.style.transform = isVisible ? 'translateY(-10px)' : 'translateY(0)';
    }
}

/**
 * แสดงข้อความสำเร็จ
 */
function showSuccessNotification(message) {
    const notification = document.getElementById('successNotification');
    const messageElement = document.getElementById('successMessage');
    
    if (notification && messageElement) {
        messageElement.textContent = message;
        notification.classList.add('show');
        
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }
}
