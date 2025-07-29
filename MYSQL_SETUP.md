# คู่มือการติดตั้งและใช้งาน MySQL สำหรับ PhoneZone

## 📋 รายการที่ต้องติดตั้ง

### 1. XAMPP (แนะนำ - ง่ายที่สุด)
- ดาวน์โหลดจาก: https://www.apachefriends.org/download.html
- รวม Apache, MySQL, PHP, phpMyAdmin ในตัว
- เหมาะสำหรับการพัฒนาในเครื่อง

### 2. WampServer (ทางเลือก)
- ดาวน์โหลดจาก: https://www.wampserver.com/
- ใช้งานง่าย สำหรับ Windows เท่านั้น

### 3. MySQL แยกต่างหาก
- MySQL Server: https://dev.mysql.com/downloads/mysql/
- phpMyAdmin: https://www.phpmyadmin.net/downloads/

---

## 🚀 ขั้นตอนการติดตั้ง XAMPP

### 1. ดาวน์โหลดและติดตั้ง
1. ไปที่ https://www.apachefriends.org/download.html
2. เลือกเวอร์ชัน PHP 8.0+ 
3. ดาวน์โหลดและรันไฟล์ installer
4. ติดตั้งที่ `C:\xampp` (แนะนำ)

### 2. เริ่มต้นใช้งาน
1. เปิด XAMPP Control Panel
2. คลิก "Start" ที่ Apache และ MySQL
3. รอให้สถานะเป็นสีเขียว

### 3. ตรวจสอบการติดตั้ง
- เปิดเบราว์เซอร์ไปที่ `http://localhost`
- ควรเห็นหน้า XAMPP Dashboard
- คลิก phpMyAdmin หรือไปที่ `http://localhost/phpmyadmin`

---

## 🗄️ การสร้างฐานข้อมูล

### วิธีที่ 1: ใช้ phpMyAdmin (แนะนำ)

1. **เข้า phpMyAdmin**
   ```
   http://localhost/phpmyadmin
   ```

2. **สร้างฐานข้อมูล**
   - คลิก "New" หรือ "สร้างใหม่"
   - ชื่อฐานข้อมูล: `phonexpress`
   - Collation: `utf8mb4_unicode_ci`
   - คลิก "Create"

3. **นำเข้าโครงสร้างฐานข้อมูล**
   - เลือกฐานข้อมูล `phonexpress`
   - คลิกแท็บ "Import" หรือ "นำเข้า"
   - เลือกไฟล์ `database/schema.sql`
   - คลิก "Go" หรือ "ดำเนินการ"

### วิธีที่ 2: ใช้ Command Line

1. **เปิด Command Prompt**
   ```cmd
   cd C:\xampp\mysql\bin
   mysql -u root -p
   ```

2. **สร้างฐานข้อมูล**
   ```sql
   CREATE DATABASE phonexpress CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE phonexpress;
   SOURCE /path/to/database/schema.sql;
   ```

---

## ⚙️ การกำหนดค่า

### 1. แก้ไขไฟล์ config/database.php

```php
private $host = 'localhost';        // ที่อยู่เซิร์ฟเวอร์
private $database = 'phonexpress';  // ชื่อฐานข้อมูล
private $username = 'root';         // ชื่อผู้ใช้ (XAMPP default)
private $password = '';             // รหัสผ่าน (XAMPP default = ว่าง)
```

### 2. ตั้งรหัสผ่าน MySQL (แนะนำ)

1. **ใน phpMyAdmin:**
   - คลิก "User accounts"
   - เลือก user "root"
   - คลิก "Change password"
   - ใส่รหัสผ่านใหม่

2. **อัปเดตในไฟล์ config:**
   ```php
   private $password = 'รหัสผ่านใหม่';
   ```

---

## 🏃‍♂️ การรันเว็บไซต์

### 1. วางไฟล์โปรเจค
```
C:\xampp\htdocs\PhoneZone\
├── index.html
├── api/
├── assets/
├── config/
├── database/
└── models/
```

### 2. เข้าถึงเว็บไซต์
```
http://localhost/PhoneZone/
```

### 3. ทดสอบ API
```
http://localhost/PhoneZone/config/database.php?test_connection
```

---

## 🔧 การแก้ไขปัญหาที่พบบ่อย

### 1. MySQL ไม่สามารถเริ่มต้นได้
**ปัญหา:** Port 3306 ถูกใช้งานโดยโปรแกรมอื่น

**วิธีแก้:**
1. เปิด XAMPP Control Panel
2. คลิก "Config" ข้าง MySQL
3. เลือก "my.ini"
4. เปลี่ยน `port=3306` เป็น `port=3307`
5. อัปเดต `config/database.php`:
   ```php
   private $host = 'localhost:3307';
   ```

### 2. ไม่สามารถเชื่อมต่อฐานข้อมูล
**ตรวจสอบ:**
- MySQL service ทำงานหรือไม่
- ชื่อฐานข้อมูล username password ถูกต้องหรือไม่
- Firewall บล็อกการเชื่อมต่อหรือไม่

**วิธีแก้:**
```php
// ทดสอบการเชื่อมต่อ
http://localhost/PhoneZone/config/database.php?test_connection
```

### 3. ข้อผิดพลาด UTF-8
**วิธีแก้:**
1. ตรวจสอบว่าฐานข้อมูลใช้ `utf8mb4_unicode_ci`
2. เพิ่มใน Apache config:
   ```apache
   AddDefaultCharset UTF-8
   ```

### 4. Permission denied
**วิธีแก้:**
- ตรวจสอบสิทธิ์โฟลเดอร์ htdocs
- รัน XAMPP ในฐานะ Administrator

---

## 📊 การจัดการฐานข้อมูลผ่าน phpMyAdmin

### 1. ดูข้อมูลตาราง
1. เลือกฐานข้อมูล `phonexpress`
2. คลิกชื่อตารางที่ต้องการดู
3. คลิกแท็บ "Browse" เพื่อดูข้อมูล

### 2. เพิ่มข้อมูลสินค้า
```sql
INSERT INTO products (name, category, price, image, storage, color, stock_quantity) 
VALUES ('iPhone 15 Pro Max', 'iPhone 15 Pro', 45900, 'iphone15promax.jpg', '256GB', 'Natural Titanium', 10);
```

### 3. ดูผู้ใช้ที่สมัคร
```sql
SELECT id, email, first_name, last_name, created_at FROM users;
```

### 4. รีเซ็ตข้อมูล
```sql
-- ลบข้อมูลทั้งหมด (ระวัง!)
TRUNCATE TABLE users;
TRUNCATE TABLE sessions;
TRUNCATE TABLE products;
```

---

## 🔒 การรักษาความปลอดภัย

### 1. ตั้งรหัสผ่าน MySQL
```sql
ALTER USER 'root'@'localhost' IDENTIFIED BY 'รหัสผ่านใหม่';
FLUSH PRIVILEGES;
```

### 2. สร้างผู้ใช้เฉพาะโปรเจค
```sql
CREATE USER 'phonezone'@'localhost' IDENTIFIED BY 'รหัสผ่าน';
GRANT ALL PRIVILEGES ON phonexpress.* TO 'phonezone'@'localhost';
FLUSH PRIVILEGES;
```

### 3. อัปเดต config
```php
private $username = 'phonezone';
private $password = 'รหัสผ่าน';
```

---

## 📈 การสำรองข้อมูล

### 1. Export ฐานข้อมูล
**ใน phpMyAdmin:**
1. เลือกฐานข้อมูล `phonexpress`
2. คลิก "Export"
3. เลือก "Custom" 
4. เลือกตารางที่ต้องการ
5. คลิก "Go"

**Command Line:**
```cmd
cd C:\xampp\mysql\bin
mysqldump -u root -p phonexpress > backup.sql
```

### 2. Import ฐานข้อมูล
```cmd
mysql -u root -p phonexpress < backup.sql
```

---

## 🌐 การนำขึ้น Production Server

### 1. เตรียมไฟล์
- อัปโหลดไฟล์ทั้งหมดยกเว้น `temp/` และ `database/`
- อัปเดต `config/database.php` ตามการตั้งค่าของ hosting

### 2. สร้างฐานข้อมูลใน cPanel/hosting
1. เข้า cPanel → MySQL Databases
2. สร้างฐานข้อมูลใหม่
3. สร้าง MySQL User
4. เพิ่ม User เข้าฐานข้อมูล
5. Import ไฟล์ `schema.sql`

### 3. อัปเดต config
```php
private $host = 'localhost';  // หรือตาม hosting
private $database = 'cpanel_user_phonexpress';
private $username = 'cpanel_user_phonezone';
private $password = 'รหัสผ่านจริง';
```

---

## 📞 การขอความช่วยเหลือ

หากพบปัญหาในการติดตั้งหรือใช้งาน:

1. **ตรวจสอบ Error Log:**
   ```
   C:\xampp\apache\logs\error.log
   C:\xampp\mysql\data\mysql_error.log
   ```

2. **ทดสอบการเชื่อมต่อ:**
   ```
   http://localhost/PhoneZone/config/database.php?test_connection
   ```

3. **ตรวจสอบ PHP Error:**
   - เปิด `display_errors` ใน `php.ini`
   - ดู error จาก Developer Tools ในเบราว์เซอร์

---

## ✅ Checklist การติดตั้ง

- [ ] ติดตั้ง XAMPP เรียบร้อย
- [ ] Apache และ MySQL รันได้
- [ ] เข้า phpMyAdmin ได้
- [ ] สร้างฐานข้อมูล `phonexpress`
- [ ] Import ไฟล์ `schema.sql`
- [ ] วางไฟล์โปรเจคใน `htdocs`
- [ ] แก้ไข `config/database.php`
- [ ] ทดสอบเข้าเว็บไซต์ได้
- [ ] ทดสอบการสมัครสมาชิก/เข้าสู่ระบบ
- [ ] ทดสอบระบบตะกร้าสินค้า

**🎉 เมื่อทำครบทุกข้อแล้ว เว็บไซต์พร้อมใช้งาน!**
