// Home.jsx
import React from 'react';

export default function Home() {
  return (
    <div className="container bg-white min-h-screen flex flex-col font-sans">
      {/* Navbar */}
      <header className="navbar flex justify-between items-center py-4 px-6 border-b shadow-sm bg-white">
        <div className="logo text-2xl font-bold text-black">iPhoneZone</div>
        <nav className="menu flex space-x-6">
          <a href="/shop" className="text-black hover:text-yellow-600">สินค้า</a>
          <a href="/cart" className="text-black hover:text-yellow-600">ตะกร้า</a>
          <a href="/login" className="text-black hover:text-yellow-600">เข้าสู่ระบบ</a>
        </nav>
      </header>

      {/* Hero */}
      <section className="hero text-center py-16 bg-gradient-to-b from-white to-yellow-50">
        <h1 className="text-4xl md:text-5xl font-bold text-black mb-4">
          ซื้อ iPhone แท้ ง่าย ปลอดภัย และเหนือระดับ
        </h1>
        <p className="text-lg text-gray-700 mb-6">
          เปรียบเทียบรุ่น สี ความจุ พร้อมชำระเงินในคลิกเดียว
        </p>
        <button className="btn px-6 py-2 rounded-full bg-black text-white hover:bg-yellow-600 transition">
          ดูสินค้าทั้งหมด
        </button>
      </section>

      {/* About Section */}
      <section className="about max-w-3xl mx-auto py-10 px-4 text-center">
        <h2 className="text-2xl font-semibold text-black mb-3">เกี่ยวกับเว็บไซต์</h2>
        <p className="text-gray-800 mb-2">
          แพลตฟอร์มออนไลน์ที่รวบรวม iPhone แต่ละรุ่นพร้อมภาพสวยคมชัดและข้อมูลสเปกครบครัน ให้คุณเปรียบเทียบรุ่น สี และความจุได้อย่างใจต้องการ
        </p>
        <p className="text-gray-800 mb-2">
          เพียงคลิกเดียวก็สามารถเพิ่มสินค้าลงตะกร้า กรอกที่อยู่ และชำระเงินพร้อมระบบติดตามสถานะคำสั่งซื้อและฟอร์มติดต่อสอบถามภายในเว็บ
        </p>
        <p className="text-gray-800">
          ดีไซน์เรียบหรูด้วยโทนสีขาว–ทอง–ดำ รองรับทั้งคอมพิวเตอร์และมือถือ มอบประสบการณ์ซื้อ iPhone ออนไลน์ที่ง่าย ปลอดภัย และเหนือระดับตั้งแต่ก้าวแรกจนถึงมือลูกค้า
        </p>
      </section>

      {/* Footer */}
      <footer className="footer bg-black py-8 text-center text-white text-sm mt-auto">
        <p>&copy; 2025 iPhoneZone. All rights reserved.</p>
        <p>ติดตามสถานะคำสั่งซื้อ | ติดต่อผ่าน LINE | บริการจัดส่งถึงบ้าน</p>
      </footer>
    </div>
  );
}
