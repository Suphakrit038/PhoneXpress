# 📱 PhoneZone - ระบบ MySQL Database

## 🎉 การอัปเกรดเสร็จสิ้น!

เว็บไซต์ PhoneZone ได้รับการอัปเกรดจากระบบ **IndexedDB** เป็น **MySQL Database** เรียบร้อยแล้ว! 

### ✨ คุณสมบัติใหม่

#### 🔐 ระบบ Authentication
- **เข้าสู่ระบบ/สมัครสมาชิก** ด้วย MySQL
- **Session Management** ที่ปลอดภัย
- **การจัดการโปรไฟล์** ผู้ใช้
- **ระบบสิทธิ์** Admin/User

#### 🗄️ ฐานข้อมูล MySQL
- **ตารางผู้ใช้ (users)** - จัดเก็บข้อมูลสมาชิก
- **ตาราง sessions** - จัดการการเข้าสู่ระบบ
- **ตารางสินค้า (products)** - ข้อมูล iPhone ทั้งหมด
- **ตารางตะกร้า (cart)** - จัดเก็บรายการสั่งซื้อ
- **ตารางคำสั่งซื้อ (orders)** - ประวัติการซื้อ
- **ตาราง logs** - บันทึกการใช้งาน

#### 🔧 API Backend
- **REST API** สำหรับการจัดการข้อมูล
- **PHP Backend** ที่มีประสิทธิภาพ
- **ระบบ Validation** ข้อมูล
- **Error Handling** ที่ครบถ้วน

---

## 📁 โครงสร้างไฟล์ใหม่

```
PhoneZone/
├── 📁 api/                    # PHP API Endpoints
│   ├── auth.php              # ระบบ Authentication
│   └── products.php          # จัดการสินค้า
├── 📁 assets/
│   ├── 📁 css/               # ไฟล์ CSS เดิม + ใหม่
│   ├── 📁 image/             # รูปภาพ
│   └── 📁 js/
│       ├── api.js            # 🆕 MySQL API Client
│       └── auth.js           # 🔄 อัปเดตใหม่
├── 📁 config/
│   └── database.php          # 🆕 การเชื่อมต่อ MySQL
├── 📁 database/
│   └── schema.sql            # 🆕 โครงสร้างฐานข้อมูล
├── 📁 models/                # 🆕 PHP Models
│   ├── User.php              # Model ผู้ใช้
│   └── Product.php           # Model สินค้า
├── 📁 temp/                  # 🆕 ไฟล์ชั่วคราว
├── 📄 *.html                 # หน้าเว็บทั้งหมด (อัปเดตแล้ว)
├── 📄 MYSQL_SETUP.md         # 🆕 คู่มือติดตั้ง MySQL
└── 📄 .gitignore            # 🆕 ไฟล์ที่ไม่ต้อง commit
```

---

## 🚀 วิธีการเริ่มใช้งาน

### 1. ติดตั้ง XAMPP
```bash
# ดาวน์โหลดและติดตั้ง XAMPP
# จาก https://www.apachefriends.org/
```

### 2. สำคัญ! คัดลอกโฟลเดอร์ไปใน htdocs
```bash
# คัดลอกโฟลเดอร์ PhoneZone ไปวางที่
C:\xampp\htdocs\PhoneZone\
```

### 3. เริ่ม Apache และ MySQL
- เปิด XAMPP Control Panel
- กด Start ที่ Apache และ MySQL

### 4. สร้างฐานข้อมูล
1. เข้า http://localhost/phpmyadmin
2. สร้างฐานข้อมูลชื่อ `phonexpress`
3. นำเข้าไฟล์ `database/schema.sql`

### 5. ทดสอบการเชื่อมต่อ
```bash
# เข้าไปที่
http://localhost/PhoneZone/config/database.php?test_connection
```

### 6. เข้าใช้งานเว็บไซต์
```bash
http://localhost/PhoneZone/
```

---

## 🎯 วิธีการใช้งานใหม่

### 🔐 สำหรับผู้ใช้ทั่วไป

#### สมัครสมาชิก
1. คลิกปุ่ม "สมัครสมาชิก"
2. กรอกข้อมูล: อีเมล, รหัสผ่าน, ชื่อ-นามสกุล
3. ระบบจะเก็บข้อมูลใน MySQL

#### เข้าสู่ระบบ
1. คลิกปุ่ม "เข้าสู่ระบบ"
2. ใส่อีเมลและรหัสผ่าน
3. ระบบจะสร้าง session ใน MySQL

#### ซื้อสินค้า
1. เลือกสินค้าที่ต้องการ
2. กดเพิ่มลงตะกร้า (ข้อมูลจัดเก็บใน MySQL)
3. ดูตะกร้าสินค้าและสั่งซื้อ

### 👑 สำหรับ Admin

#### เข้าสู่ระบบ Admin
```
อีเมล: admin@phonezone.com
รหัสผ่าน: admin123
```

#### จัดการสินค้า
- เพิ่ม/แก้ไข/ลบสินค้า
- อัปเดตสต็อกสินค้า
- ดูสถิติการขาย

#### จัดการผู้ใช้
- ดูรายชื่อสมาชิก
- จัดการสถานะผู้ใช้
- ดูประวัติการใช้งาน

---

## 🔧 การ Customize

### เพิ่มสินค้าใหม่
```sql
INSERT INTO products (name, category, price, image, storage, color, stock_quantity) 
VALUES ('iPhone 16 Pro Max', 'iPhone 16 Pro', 49900, 'iphone16promax.jpg', '512GB', 'Desert Titanium', 5);
```

### แก้ไขการตั้งค่าฐานข้อมูล
```php
// ใน config/database.php
private $host = 'localhost';
private $database = 'phonexpress';
private $username = 'root';
private $password = '';
```

### เพิ่ม API Endpoint ใหม่
```php
// ใน api/products.php
case 'new_function':
    handleNewFunction($productModel);
    break;
```

---

## 📊 การดูข้อมูลใน phpMyAdmin

### ดูข้อมูลผู้ใช้
```sql
SELECT id, email, first_name, last_name, created_at FROM users;
```

### ดูข้อมูลสินค้า
```sql
SELECT name, category, price, stock_quantity FROM products;
```

### ดูประวัติการสั่งซื้อ
```sql
SELECT o.id, u.email, o.total_amount, o.created_at 
FROM orders o 
JOIN users u ON o.user_id = u.id;
```

---

## 🔒 ความปลอดภัย

### การเข้ารหัสรหัสผ่าน
- ใช้ `password_hash()` และ `password_verify()`
- รหัสผ่านไม่เก็บแบบ plain text

### Session Security
- Session Token แบบสุ่ม 64 characters
- มีเวลาหมดอายุ
- ตรวจสอบ IP Address

### SQL Injection Protection
- ใช้ Prepared Statements ทุกครั้ง
- Validate และ Sanitize ข้อมูล input

---

## 🐛 การแก้ไขปัญหา

### ปัญหาเชื่อมต่อฐานข้อมูลไม่ได้
1. ตรวจสอบ MySQL service รันหรือไม่
2. ตรวจสอบ username/password ใน config
3. ตรวจสอบชื่อฐานข้อมูลถูกต้องหรือไม่

### ปัญหา API ไม่ทำงาน
1. ตรวจสอบไฟล์ error.log
2. เปิด developer tools ดู network tab
3. ตรวจสอบ path ไฟล์ถูกต้องหรือไม่

### ปัญหา Permission Denied
1. ตรวจสอบสิทธิ์โฟลเดอร์ htdocs
2. รัน XAMPP ในฐานะ Administrator
3. ตรวจสอบ firewall blocking

---

## 📈 การพัฒนาต่อ

### คุณสมบัติที่สามารถเพิ่มได้
- ระบบการชำระเงิน
- ระบบแจ้งเตือน Email
- ระบบรีวิวสินค้า
- ระบบคูปองส่วนลด
- ระบบติดตามการส่ง

### การปรับปรุงประสิทธิภาพ
- เพิ่มระบบ Cache
- ปรับแต่ง MySQL Indexing
- ใช้ CDN สำหรับรูปภาพ
- ปรับแต่ง Apache/PHP

---

## 🎯 สรุป

ระบบใหม่นี้จะทำให้:
- **ข้อมูลปลอดภัยมากขึ้น** ด้วย MySQL
- **ดูข้อมูลเป็นตารางได้ง่าย** ผ่าน phpMyAdmin
- **ระบบเสถียรกว่า** IndexedDB
- **รองรับผู้ใช้หลายคนพร้อมกัน**
- **ง่ายต่อการ backup และ restore**

🎉 **ตอนนี้เว็บไซต์พร้อมใช้งานแล้ว!** 

สามารถเริ่มใช้งานได้ทันทีหลังจากติดตั้ง XAMPP และสร้างฐานข้อมูลเรียบร้อยแล้ว
