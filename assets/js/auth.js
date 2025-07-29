// Authentication System for PhoneXpress
// ระบบ Authentication ที่ใช้ localStorage สำหรับการจำลอง

// Authentication Functions
function openLoginModal() {
  const modal = document.getElementById('authModal');
  showLoginForm();
  modal.classList.add('show');
  document.body.style.overflow = 'hidden';
}

function openSignupModal() {
  const modal = document.getElementById('authModal');
  showSignupForm();
  modal.classList.add('show');
  document.body.style.overflow = 'hidden';
}

function closeAuthModal() {
  const modal = document.getElementById('authModal');
  modal.classList.remove('show');
  document.body.style.overflow = 'auto';
  
  // Reset forms
  document.getElementById('loginForm').querySelector('form').reset();
  document.getElementById('signupForm').querySelector('form').reset();
  clearErrors();
}

function showLoginForm() {
  document.getElementById('loginForm').style.display = 'block';
  document.getElementById('signupForm').style.display = 'none';
}

function showSignupForm() {
  document.getElementById('loginForm').style.display = 'none';
  document.getElementById('signupForm').style.display = 'block';
}

function handleLogin(event) {
  event.preventDefault();
  
  const email = document.getElementById('loginEmail').value;
  const password = document.getElementById('loginPassword').value;
  const submitBtn = document.getElementById('loginSubmitBtn');
  
  // Clear previous errors
  clearErrors();
  
  // Basic validation
  if (!validateEmail(email)) {
    showError('loginEmailError', 'กรุณากรอกอีเมลที่ถูกต้อง');
    return false;
  }
  
  if (password.length < 6) {
    showError('loginPasswordError', 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
    return false;
  }
  
  // Simulate login process
  submitBtn.classList.add('loading');
  submitBtn.textContent = 'กำลังเข้าสู่ระบบ...';
  
  setTimeout(() => {
    // Check if user exists in localStorage (for demo purposes)
    const existingUsers = JSON.parse(localStorage.getItem('users')) || [];
    const user = existingUsers.find(u => u.email === email && u.password === password);
    
    if (user) {
      const userData = {
        name: user.name,
        email: user.email
      };
      
      loginUser(userData);
      closeAuthModal();
      showSuccessNotification('เข้าสู่ระบบสำเร็จ!');
    } else {
      // For demo purposes, create a temporary user
      const userData = {
        name: 'ผู้ใช้ทดสอบ',
        email: email
      };
      
      loginUser(userData);
      closeAuthModal();
      showSuccessNotification('เข้าสู่ระบบสำเร็จ!');
    }
    
    submitBtn.classList.remove('loading');
    submitBtn.textContent = 'เข้าสู่ระบบ';
  }, 1500);
  
  return false;
}

function handleSignup(event) {
  event.preventDefault();
  
  const name = document.getElementById('signupName').value;
  const email = document.getElementById('signupEmail').value;
  const password = document.getElementById('signupPassword').value;
  const confirmPassword = document.getElementById('signupConfirmPassword').value;
  const submitBtn = document.getElementById('signupSubmitBtn');
  
  // Clear previous errors
  clearErrors();
  
  // Validation
  let hasError = false;
  
  if (name.length < 2) {
    showError('signupNameError', 'กรุณากรอกชื่อ-นามสกุล');
    hasError = true;
  }
  
  if (!validateEmail(email)) {
    showError('signupEmailError', 'กรุณากรอกอีเมลที่ถูกต้อง');
    hasError = true;
  }
  
  if (password.length < 6) {
    showError('signupPasswordError', 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
    hasError = true;
  }
  
  if (password !== confirmPassword) {
    showError('signupConfirmPasswordError', 'รหัสผ่านไม่ตรงกัน');
    hasError = true;
  }
  
  if (hasError) return false;
  
  // Check if email already exists
  const existingUsers = JSON.parse(localStorage.getItem('users')) || [];
  if (existingUsers.find(u => u.email === email)) {
    showError('signupEmailError', 'อีเมลนี้ถูกใช้งานแล้ว');
    return false;
  }
  
  // Simulate signup process
  submitBtn.classList.add('loading');
  submitBtn.textContent = 'กำลังสมัครสมาชิก...';
  
  setTimeout(() => {
    // Save new user to localStorage
    const newUser = {
      name: name,
      email: email,
      password: password,
      registeredAt: new Date().toISOString()
    };
    
    existingUsers.push(newUser);
    localStorage.setItem('users', JSON.stringify(existingUsers));
    
    const userData = {
      name: name,
      email: email
    };
    
    loginUser(userData);
    closeAuthModal();
    showSuccessNotification('สมัครสมาชิกสำเร็จ!');
    
    submitBtn.classList.remove('loading');
    submitBtn.textContent = 'สมัครสมาชิก';
  }, 2000);
  
  return false;
}

function loginUser(userData) {
  // Store current user data in localStorage
  localStorage.setItem('currentUser', JSON.stringify(userData));
  
  // Update UI
  const authSection = document.getElementById('authSection');
  const userMenu = document.getElementById('userMenu');
  const userAvatar = document.getElementById('userAvatar');
  
  if (authSection) authSection.style.display = 'none';
  if (userMenu) userMenu.style.display = 'block';
  if (userAvatar) userAvatar.textContent = userData.name.charAt(0).toUpperCase();
}

function logout() {
  localStorage.removeItem('currentUser');
  
  const authSection = document.getElementById('authSection');
  const userMenu = document.getElementById('userMenu');
  
  if (authSection) authSection.style.display = 'block';
  if (userMenu) userMenu.style.display = 'none';
  
  showSuccessNotification('ออกจากระบบสำเร็จ!');
}

function validateEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}

function showError(elementId, message) {
  const errorElement = document.getElementById(elementId);
  if (errorElement) {
    errorElement.textContent = message;
    errorElement.parentElement.classList.add('error');
  }
}

function clearErrors() {
  const errorElements = document.querySelectorAll('.error-message');
  const formGroups = document.querySelectorAll('.form-group');
  
  errorElements.forEach(element => element.textContent = '');
  formGroups.forEach(group => group.classList.remove('error'));
}

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

// Initialize authentication state on page load
document.addEventListener('DOMContentLoaded', function() {
  const userData = localStorage.getItem('currentUser');
  if (userData) {
    const user = JSON.parse(userData);
    loginUser(user);
  }
  
  // Close modal when clicking outside
  const authModal = document.getElementById('authModal');
  if (authModal) {
    authModal.addEventListener('click', function(event) {
      if (event.target === this) {
        closeAuthModal();
      }
    });
  }
  
  // Close modal with Escape key
  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      closeAuthModal();
    }
  });
});

// Additional utility functions
function getCurrentUser() {
  const userData = localStorage.getItem('currentUser');
  return userData ? JSON.parse(userData) : null;
}

function isUserLoggedIn() {
  return getCurrentUser() !== null;
}

// Export functions for use in other files
if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    openLoginModal,
    openSignupModal,
    closeAuthModal,
    logout,
    getCurrentUser,
    isUserLoggedIn
  };
}
