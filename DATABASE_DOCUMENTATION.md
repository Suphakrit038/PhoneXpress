# 🗄️ PhoneXpress Database System Documentation

## ภาพรวมระบบฐานข้อมูล

ระบบฐานข้อมูลของ PhoneXpress ใช้ **IndexedDB** ซึ่งเป็นฐานข้อมูลแบบ NoSQL ที่ทำงานในเบราว์เซอร์ สามารถจัดเก็บข้อมูลได้มากกว่า localStorage และมีประสิทธิภาพสูงกว่า

## 🏗️ โครงสร้างฐานข้อมูล

### Database Info
- **ชื่อฐานข้อมูล**: `PhoneXpressDB`
- **เวอร์ชัน**: `1`
- **ประเภท**: IndexedDB (HTML5 Web Database)

### 📊 ตารางในฐานข้อมูล

#### 1. ตาราง `users` - ข้อมูลผู้ใช้
```javascript
{
  id: number (Primary Key, Auto Increment),
  name: string,           // ชื่อ-นามสกุล
  email: string,          // อีเมล (Unique Index)
  password: string,       // รหัสผ่าน (Hashed)
  phone: string,          // เบอร์โทรศัพท์
  address: string,        // ที่อยู่
  createdAt: string,      // วันที่สมัคร (ISO Date)
  lastLogin: string,      // เข้าสู่ระบบล่าสุด (ISO Date)
  updatedAt: string,      // อัพเดทล่าสุด (ISO Date)
  isActive: boolean,      // สถานะการใช้งาน
  avatar: string,         // รูปโปรไฟล์
  preferences: {          // การตั้งค่าผู้ใช้
    newsletter: boolean,
    notifications: boolean,
    theme: string
  }
}
```

#### 2. ตาราง `sessions` - Session การเข้าสู่ระบบ
```javascript
{
  sessionId: string (Primary Key),  // ID ของ session
  userId: number,                   // ID ผู้ใช้ (Index)
  createdAt: string,               // วันที่สร้าง session
  expiresAt: string,               // วันที่หมดอายุ
  isActive: boolean                // สถานะ session
}
```

#### 3. ตาราง `cart` - ตะกร้าสินค้า
```javascript
{
  id: number (Primary Key, Auto Increment),
  userId: number,          // ID ผู้ใช้ (Index)
  productModel: string,    // รหัสรุ่นสินค้า (Index)
  productName: string,     // ชื่อสินค้า
  productPrice: number,    // ราคาสินค้า
  productImage: string,    // รูปภาพสินค้า
  quantity: number,        // จำนวน
  addedAt: string         // วันที่เพิ่มลงตะกร้า
}
```

#### 4. ตาราง `orders` - คำสั่งซื้อ
```javascript
{
  id: number (Primary Key, Auto Increment),
  userId: number,              // ID ผู้ใช้ (Index)
  orderNumber: string,         // เลขที่คำสั่งซื้อ
  items: array,               // รายการสินค้าที่สั่งซื้อ
  totalAmount: number,        // ยอดรวม
  shippingAddress: object,    // ที่อยู่จัดส่ง
  paymentMethod: string,      // วิธีการชำระเงิน
  orderStatus: string,        // สถานะคำสั่งซื้อ (Index)
  createdAt: string,          // วันที่สั่งซื้อ (Index)
  estimatedDelivery: string   // วันที่คาดว่าจะได้รับสินค้า
}
```

#### 5. ตาราง `userPreferences` - การตั้งค่าผู้ใช้
```javascript
{
  userId: number (Primary Key),    // ID ผู้ใช้
  theme: string,                   // ธีมที่ใช้
  language: string,                // ภาษา
  notifications: boolean,          // การแจ้งเตือน
  newsletter: boolean,             // จดหมายข่าว
  privacy: object                  // การตั้งค่าความเป็นส่วนตัว
}
```

## 🔧 ฟีเจอร์หลักของระบบ

### 1. การจัดการผู้ใช้ (User Management)
- **สมัครสมาชิก**: สร้างบัญชีใหม่พร้อม validation
- **เข้าสู่ระบบ**: ตรวจสอบอีเมลและรหัสผ่าน
- **จัดการ Session**: สร้างและตรวจสอบ session
- **อัพเดทข้อมูล**: แก้ไขข้อมูลส่วนตัว
- **รีเซ็ตรหัสผ่าน**: (พร้อมสำหรับการพัฒนาต่อ)

### 2. การจัดการตะกร้าสินค้า (Cart Management)
- **เพิ่มสินค้า**: เพิ่มสินค้าลงตะกร้าผู้ใช้
- **อัพเดทจำนวน**: แก้ไขจำนวนสินค้า
- **ลบสินค้า**: ลบสินค้าออกจากตะกร้า
- **ล้างตะกร้า**: ลบสินค้าทั้งหมด

### 3. การจัดการคำสั่งซื้อ (Order Management)
- **สร้างคำสั่งซื้อ**: บันทึกการสั่งซื้อ
- **ติดตามคำสั่งซื้อ**: ดูประวัติการสั่งซื้อ
- **อัพเดทสถานะ**: เปลี่ยนสถานะคำสั่งซื้อ

### 4. สถิติและรายงาน (Analytics)
- **สถิติผู้ใช้**: จำนวนผู้ใช้ทั้งหมด, ผู้ใช้ใหม่
- **สถิติการขาย**: ยอดขาย, จำนวนคำสั่งซื้อ
- **รายงานรายได้**: คำนวณรายได้ทั้งหมด

## 💻 การใช้งาน API

### การเริ่มต้นฐานข้อมูล
```javascript
const phoneXpressDB = new PhoneXpressDB();
await phoneXpressDB.init();
```

### การสมัครสมาชิก
```javascript
const newUser = await phoneXpressDB.addUser({
  name: 'John Doe',
  email: 'john@example.com',
  password: 'securepassword'
});
```

### การเข้าสู่ระบบ
```javascript
const authResult = await phoneXpressDB.authenticateUser(
  'john@example.com', 
  'securepassword'
);
```

### การเพิ่มสินค้าลงตะกร้า
```javascript
const cartItem = await phoneXpressDB.addToCart(userId, {
  model: 'iphone14pro',
  name: 'iPhone 14 Pro',
  price: 35900,
  image: 'path/to/image.jpg',
  quantity: 1
});
```

### การสร้างคำสั่งซื้อ
```javascript
const order = await phoneXpressDB.createOrder(userId, {
  items: cartItems,
  totalAmount: 35900,
  shippingAddress: addressData,
  paymentMethod: 'credit_card'
});
```

## 🛡️ ความปลอดภัย (Security Features)

### 1. การเข้ารหัสรหัสผ่าน
- ใช้ Hash Function สำหรับเข้ารหัสรหัสผ่าน
- ไม่เก็บรหัสผ่านแบบ plain text

### 2. Session Management
- สร้าง Session ID แบบ unique
- ตรวจสอบอายุของ Session
- ลบ Session เมื่อ logout

### 3. Data Validation
- ตรวจสอบรูปแบบอีเมล
- ตรวจสอบความยาวรหัสผ่าน
- ป้องกัน SQL Injection (แม้ว่าจะเป็น NoSQL)

## 📱 Admin Panel Features

### หน้า Admin (`admin.html`)
- **Dashboard**: แสดงสถิติแบบ real-time
- **User Management**: จัดการข้อมูลผู้ใช้
- **Statistics**: ดูสถิติการใช้งาน
- **Data Export**: ส่งออกข้อมูลเป็น JSON
- **Database Control**: ล้างข้อมูล, รีเซ็ตระบบ

### การเข้าถึง Admin Panel
```
http://localhost/admin.html
```

## 🔄 การ Backup และ Restore

### Export Data
```javascript
// ส่งออกข้อมูลทั้งหมด
const data = await phoneXpressDB.exportAllData();
```

### Clear Database
```javascript
// ล้างข้อมูลทั้งหมด (ใช้ระวัง!)
await phoneXpressDB.clearDatabase();
```

## 📊 ตัวอย่างการใช้งาน

### 1. การสมัครสมาชิกและเข้าสู่ระบบ
```javascript
// สมัครสมาชิก
try {
  const user = await phoneXpressDB.addUser({
    name: 'สมชาย ใจดี',
    email: 'somchai@example.com',
    password: 'mypassword123'
  });
  console.log('สมัครสมาชิกสำเร็จ:', user);
} catch (error) {
  console.error('สมัครสมาชิกไม่สำเร็จ:', error.message);
}

// เข้าสู่ระบบ
try {
  const authResult = await phoneXpressDB.authenticateUser(
    'somchai@example.com', 
    'mypassword123'
  );
  console.log('เข้าสู่ระบบสำเร็จ:', authResult.user);
  console.log('Session:', authResult.session);
} catch (error) {
  console.error('เข้าสู่ระบบไม่สำเร็จ:', error.message);
}
```

### 2. การจัดการตะกร้าสินค้า
```javascript
// เพิ่มสินค้าลงตะกร้า
const cartItem = await phoneXpressDB.addToCart(userId, {
  model: 'iphone15pro',
  name: 'iPhone 15 Pro Max',
  price: 45900,
  image: 'assets/image/iphone/iphone15promax.jpg',
  quantity: 1
});

// ดูตะกร้าสินค้า
const cartItems = await phoneXpressDB.getUserCart(userId);
console.log('สินค้าในตะกร้า:', cartItems);
```

## 🚀 ข้อดีของระบบ

### 1. ประสิทธิภาพสูง
- ใช้ IndexedDB ที่เร็วกว่า localStorage
- รองรับข้อมูลขนาดใหญ่
- ทำงานแบบ Asynchronous

### 2. ความปลอดภัย
- เข้ารหัสรหัสผ่าน
- Session Management
- Data Validation

### 3. ความยืดหยุ่น
- สามารถขยายเพิ่มฟีเจอร์ได้ง่าย
- รองรับการ Index หลายแบบ
- API ที่ใช้งานง่าย

### 4. การจัดการข้อมูล
- Backup และ Restore
- Export ข้อมูล
- Admin Panel สำหรับจัดการ

## 🔮 การพัฒนาต่อในอนาคต

### 1. ฟีเจอร์เพิ่มเติม
- Push Notifications
- Offline Support
- Data Synchronization with Server
- Advanced Analytics

### 2. การปรับปรุงประสิทธิภาพ
- Index Optimization
- Query Performance
- Memory Management
- Caching Strategy

### 3. ความปลอดภัยเพิ่มเติม
- Two-Factor Authentication
- Advanced Encryption
- Access Control
- Audit Logging

---

**หมายเหตุ**: ระบบนี้เป็นการจำลองฐานข้อมูลในเบราว์เซอร์ เหมาะสำหรับการพัฒนา Prototype หรือ Demo ในการใช้งานจริงควรใช้ฐานข้อมูลฝั่ง Server เช่น MySQL, PostgreSQL หรือ MongoDB
