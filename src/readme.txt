=== LINE Bridge WP ===
Contributors: linebridge
Tags: line, line login, woocommerce, messaging, notification, thai
Requires at least: 6.9.4
Tested up to: 6.9.4
Requires PHP: 8.1
Stable tag: 1.0.0
WC requires at least: 10.7.0
WC tested up to: 10.7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

เชื่อมต่อ WordPress + WooCommerce กับ LINE Login และ LINE Messaging API

== Description ==

**LINE Bridge WP** ให้คุณ:

**LINE Login**
* ล็อกอินหรือสมัครสมาชิกด้วยบัญชี LINE
* เชื่อมต่อ LINE กับบัญชี WordPress ที่มีอยู่
* แสดงปุ่ม "เข้าสู่ระบบด้วย LINE" ในทุกฟอร์ม login

**การแจ้งเตือนลูกค้าผ่าน LINE Bot**
* ข้อความต้อนรับเมื่อ login ครั้งแรก
* ยืนยันคำสั่งซื้อ (Flex Message)
* อัปเดตสถานะออเดอร์ (กำลังดำเนินการ / จัดส่ง / สำเร็จ / ยกเลิก / คืนเงิน)

**การแจ้งเตือน Admin**
* ผู้ใช้ใหม่ลงทะเบียน
* ออเดอร์ใหม่
* สินค้าเหลือน้อย / หมด
* สลิปชำระเงิน (integration hook)
* รองรับส่งทั้งแชทส่วนตัวและกลุ่ม

**LIFF (LINE Front-end Framework)**
* Mini-app ดูสถานะออเดอร์ใน LINE โดยตรง
* ยืนยันตัวตนด้วย LINE User ID

== Installation ==

1. อัปโหลดโฟลเดอร์ `line-bridge-wp` ไปที่ `/wp-content/plugins/`
2. เปิดใช้งาน plugin ผ่านเมนู Plugins
3. ไปที่ Settings → LINE Bridge WP
4. ตั้งค่า LINE Login Channel ID/Secret และ Messaging API Channel Access Token

== Configuration ==

**LINE Login Setup:**
1. สร้าง LINE Login Channel ที่ [LINE Developers Console](https://developers.line.biz/)
2. คัดลอก Channel ID และ Channel Secret ไปใส่ใน Settings → LINE Login
3. คัดลอก Callback URL จากหน้า Settings ไปวางใน LINE Developers Console

**Messaging API Setup:**
1. สร้าง Messaging API Channel
2. คัดลอก Channel Access Token และ Channel Secret
3. คัดลอก Webhook URL จากหน้า Settings → Messaging API ไปวางใน LINE Developers Console
4. เปิดใช้งาน Webhook ใน LINE Developers Console

**Payment Slip Integration:**
```php
// ใช้ในปลั๊กอินแจ้งชำระเงิน
do_action('line_bridge_payment_slip_received', $order_id, $slip_image_url, [
    'amount' => $amount,
]);
```

== Changelog ==

= 1.0.0 =
* Initial release
* LINE Login (OAuth 2.0) with auto-register
* LINE Messaging API push notifications
* WooCommerce order notifications (Flex Message)
* Admin notifications (new user, new order, low stock, out of stock)
* Payment slip notification hook
* LIFF order tracker mini-app
* Notification log management
* Message template editor
