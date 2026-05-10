# LINE Bridge WP — คู่มือการติดตั้งและใช้งาน

**เวอร์ชัน:** 1.0.0  
**รองรับ:** WordPress 6.9.4+ · WooCommerce 10.7.0+ · PHP 8.1+

---

## สารบัญ

1. [ภาพรวม](#1-ภาพรวม)
2. [ความต้องการของระบบ](#2-ความต้องการของระบบ)
3. [การเตรียม LINE Developers Console](#3-การเตรียม-line-developers-console)
   - 3.1 สร้าง LINE Login Channel
   - 3.2 สร้าง Messaging API Channel
   - 3.3 สร้าง LIFF App (ถ้าต้องการ)
4. [การติดตั้ง Plugin](#4-การติดตั้ง-plugin)
5. [การตั้งค่า LINE Login](#5-การตั้งค่า-line-login)
6. [การตั้งค่า Messaging API](#6-การตั้งค่า-messaging-api)
7. [การตั้งค่าการแจ้งเตือน](#7-การตั้งค่าการแจ้งเตือน)
8. [การแก้ไข Message Template](#8-การแก้ไข-message-template)
9. [การตั้งค่า LIFF App](#9-การตั้งค่า-liff-app)
10. [การจัดการผู้ใช้ที่เชื่อมต่อ LINE](#10-การจัดการผู้ใช้ที่เชื่อมต่อ-line)
11. [การดู Notification Log](#11-การดู-notification-log)
12. [การผสาน Payment Slip (สำหรับ Developer)](#12-การผสาน-payment-slip-สำหรับ-developer)
13. [การทดสอบระบบ](#13-การทดสอบระบบ)
14. [การถอนการติดตั้ง](#14-การถอนการติดตั้ง)
15. [Troubleshooting](#15-troubleshooting)
16. [Placeholder Reference](#16-placeholder-reference)

---

## 1. ภาพรวม

**LINE Bridge WP** เชื่อมต่อ WordPress + WooCommerce กับ LINE Platform ในสามส่วนหลัก:

| ส่วน | ฟีเจอร์ |
|------|---------|
| **LINE Login** | ล็อกอิน / สมัครสมาชิกด้วยบัญชี LINE (Auto Register) |
| **LINE Messaging** | ส่งข้อความแจ้งเตือน Text / Flex Message / Image ถึงลูกค้าและ Admin |
| **LIFF App** | Mini-app ใน LINE สำหรับติดตามสถานะออเดอร์ |

### การแจ้งเตือนที่รองรับ

**ฝั่งลูกค้า (ต้องเชื่อมต่อ LINE)**
- ✅ ข้อความต้อนรับเมื่อล็อกอิน LINE ครั้งแรก
- ✅ ยืนยันคำสั่งซื้อ (Flex Message พร้อมรายการสินค้า)
- ✅ อัปเดตสถานะ: กำลังดำเนินการ / จัดส่งแล้ว / เสร็จสมบูรณ์ / ยกเลิก / คืนเงิน

**ฝั่ง Admin (ส่งไปยัง LINE ส่วนตัวหรือกลุ่ม)**
- ✅ มีผู้ใช้ลงทะเบียนใหม่
- ✅ มีออเดอร์ใหม่
- ✅ สินค้าเหลือน้อย / หมด
- ✅ ลูกค้าส่งสลิปชำระเงิน (พร้อมรูปภาพ)

---

## 2. ความต้องการของระบบ

| รายการ | ข้อกำหนด |
|--------|----------|
| WordPress | 6.9.4 ขึ้นไป |
| WooCommerce | 10.7.0 ขึ้นไป |
| PHP | 8.1 ขึ้นไป (ใช้ Constructor Promotion, Match Expression) |
| Extensions | `openssl`, `json`, `curl` |
| HTTPS | **จำเป็น** — LINE Platform ต้องการ HTTPS เท่านั้น |
| Permalink | ต้องไม่ใช้ Plain (แนะนำ Post name) |

> ⚠️ **หมายเหตุ:** ต้องติดตั้ง WooCommerce ก่อนเสมอ Plugin จะไม่โหลดหากไม่มี WooCommerce

---

## 3. การเตรียม LINE Developers Console

เข้าสู่ระบบที่ [LINE Developers Console](https://developers.line.biz/) และสร้าง Provider หากยังไม่มี

---

### 3.1 สร้าง LINE Login Channel

1. ใน Provider ของคุณ กด **Create a new channel**
2. เลือก **LINE Login**
3. กรอกข้อมูล:
   - **Channel name:** ชื่อเว็บไซต์ของคุณ
   - **Channel description:** คำอธิบายสั้นๆ
   - **App types:** เลือก **Web app**
4. กด **Create** → ยืนยันข้อตกลง
5. ไปที่แท็บ **Basic settings** → จด:
   - **Channel ID** (ตัวเลข เช่น `1234567890`)
   - **Channel secret** (ตัวอักษร 32 หลัก)
6. ไปที่แท็บ **LINE Login** → ช่อง **Callback URL** ให้ใส่ค่าชั่วคราวก่อน: `https://yoursite.com/` (จะแก้ทีหลังหลังติดตั้ง Plugin)
7. เปิดใช้งาน **Email address permission** หากต้องการรับอีเมลจาก LINE

---

### 3.2 สร้าง Messaging API Channel

1. กด **Create a new channel** → เลือก **Messaging API**
2. กรอกข้อมูลและกด **Create**
3. แท็บ **Basic settings** → จด **Channel secret**
4. แท็บ **Messaging API** → เลื่อนลงหา **Channel access token (long-lived)** → กด **Issue** → คัดลอก token
5. เปิด **Use webhooks** → On
6. ช่อง **Webhook URL** ให้ใส่ค่าชั่วคราวก่อน (จะแก้หลังติดตั้ง Plugin)

> 💡 **เพิ่ม LINE OA เป็นเพื่อน:** สแกน QR Code ใน LINE Official Account Manager เพื่อรับข้อความทดสอบ

---

### 3.3 สร้าง LIFF App (ถ้าต้องการ)

> ข้ามหัวข้อนี้ได้หากไม่ต้องการ Mini-app ติดตามออเดอร์ใน LINE

1. ใน LINE Login Channel → แท็บ **LIFF** → **Add**
2. กรอกข้อมูล:
   - **LIFF app name:** Order Tracker
   - **Size:** Full
   - **Endpoint URL:** `https://yoursite.com/liff-app/` (แก้ domain เป็นของคุณ)
   - **Scopes:** เลือก **profile** และ **openid**
   - **Bot link feature:** On (เลือก channel Messaging API ของคุณ)
3. กด **Add** → จด **LIFF ID** (เช่น `1234567890-XXXXXXXX`)

---

## 4. การติดตั้ง Plugin

### วิธีที่ 1 — อัปโหลดผ่าน WordPress Admin (แนะนำ)

1. ไปที่ **WordPress Admin → Plugins → Add New Plugin → Upload Plugin**
2. เลือกไฟล์ `line-bridge-wp-v1.0.0.zip`
3. กด **Install Now** → รอจนเสร็จ
4. กด **Activate Plugin**

### วิธีที่ 2 — อัปโหลดผ่าน FTP/cPanel

1. แตกไฟล์ `line-bridge-wp-v1.0.0.zip` → จะได้โฟลเดอร์ `line-bridge-wp/`
2. อัปโหลดโฟลเดอร์ทั้งหมดไปยัง `/wp-content/plugins/`
3. ไปที่ **WordPress Admin → Plugins** → เปิดใช้งาน **LINE Bridge WP**

### ตรวจสอบการติดตั้ง

หลัง Activate Plugin ระบบจะ:
- สร้างตารางฐานข้อมูล 3 ตาราง (`line_bridge_users`, `line_bridge_notifications`, `line_bridge_message_templates`)
- เพิ่มเมนู **LINE Bridge WP** ใน Settings
- Seed Template ข้อความเริ่มต้น 11 รายการ

---

## 5. การตั้งค่า LINE Login

ไปที่ **Settings → LINE Bridge WP → แท็บ LINE Login**

| ช่อง | ค่าที่ใส่ |
|------|-----------|
| **Channel ID** | Channel ID จาก LINE Developers (ตัวเลข) |
| **Channel Secret** | Channel Secret จาก LINE Developers |
| **Auto Register** | ✅ เปิดใช้งาน = สร้างบัญชี WP อัตโนมัติเมื่อล็อกอิน LINE ครั้งแรก |

**Callback URL (อ่านอย่างเดียว):** คัดลอก URL นี้

```
https://yoursite.com/wp-json/line-bridge/v1/oauth/callback
```

นำ URL นี้ไปวางใน LINE Developers Console → **LINE Login Channel → Callback URL** แล้วกด **Update**

กด **บันทึกการตั้งค่า**

---

## 6. การตั้งค่า Messaging API

ไปที่ **Settings → LINE Bridge WP → แท็บ Messaging API**

| ช่อง | ค่าที่ใส่ |
|------|-----------|
| **Channel Access Token** | Long-lived token จาก LINE Developers |
| **Channel Secret** | Channel Secret ของ Messaging API Channel |
| **Admin LINE ID** | LINE User ID ของ Admin (ขึ้นต้นด้วย `U`) หรือ Group ID (`C`) |
| **ประเภทผู้รับ Admin** | เลือก **ส่วนตัว** หรือ **กลุ่มแชท** |
| **ข้อความต้อนรับ** | ✅ เปิด = ส่งข้อความต้อนรับเมื่อล็อกอิน LINE ครั้งแรก |

**Webhook URL (อ่านอย่างเดียว):** คัดลอก URL นี้

```
https://yoursite.com/wp-json/line-bridge/v1/webhook
```

นำ URL นี้ไปวางใน LINE Developers Console → **Messaging API Channel → Webhook URL** แล้ว:
1. กด **Verify** — ต้องได้ ✅ Success
2. เปิด **Use webhook** → On

กด **บันทึกการตั้งค่า**

### หา LINE User ID ของตัวเอง

วิธีหา User ID (`U...`) ของ Admin:
1. เข้าไปที่ [LINE Messaging API Demo Bot](https://manager.line.biz/) หรือใช้ Webhook
2. ส่งข้อความหา LINE OA → Webhook จะรับ `userId` ของผู้ส่ง
3. ดูได้ใน **WordPress Admin → LINE Bridge WP → Notification Logs**

### ทดสอบการเชื่อมต่อ

กด **ส่งข้อความทดสอบ** → ถ้าตั้งค่าถูกต้องจะได้รับข้อความใน LINE ว่า "✅ ทดสอบการเชื่อมต่อ LINE Bridge WP สำเร็จ!"

---

## 7. การตั้งค่าการแจ้งเตือน

ไปที่ **Settings → LINE Bridge WP → แท็บ การแจ้งเตือน**

### การแจ้งเตือน Admin

| เหตุการณ์ | คำอธิบาย |
|-----------|----------|
| **ผู้ใช้ใหม่** | Admin ได้รับแจ้งทุกครั้งที่มีคนสมัครสมาชิก |
| **ออเดอร์ใหม่** | Admin ได้รับแจ้งทุกครั้งที่มีออเดอร์ใหม่ |
| **สินค้าเหลือน้อย** | Admin ได้รับแจ้งเมื่อสินค้าต่ำกว่า Low Stock Threshold |
| **สินค้าหมด** | Admin ได้รับแจ้งเมื่อสินค้าหมด (stock = 0) |
| **สลิปชำระเงิน** | Admin ได้รับแจ้งพร้อมรูปสลิปเมื่อลูกค้าส่งหลักฐาน |

**Low Stock Threshold:** จำนวนสินค้าที่ถือว่า "เหลือน้อย" (ค่าเริ่มต้น = 5) หากตั้งเป็น 0 จะใช้ค่าจาก WooCommerce

### การแจ้งเตือนลูกค้า

| เหตุการณ์ | ข้อกำหนด |
|-----------|---------|
| **ยืนยันออเดอร์** | ลูกค้าต้องเชื่อมต่อ LINE + ฟีเจอร์นี้เปิดอยู่ |
| **อัปเดตสถานะออเดอร์** | ลูกค้าต้องเชื่อมต่อ LINE + ฟีเจอร์นี้เปิดอยู่ |

กด **บันทึกการตั้งค่า**

---

## 8. การแก้ไข Message Template

ไปที่ **Settings → LINE Bridge WP → แท็บ ข้อความ Templates**

### Template ที่มีให้แก้ไข

| Template Key | ใช้งานเมื่อ | ประเภทข้อความ |
|-------------|------------|--------------|
| `welcome` | ล็อกอิน LINE ครั้งแรก | Text |
| `order_placed` | สั่งซื้อสำเร็จ | Flex Message |
| `order_processing` | สถานะ → กำลังดำเนินการ | Text |
| `order_shipped` | สถานะ → จัดส่งแล้ว | Text |
| `order_completed` | สถานะ → เสร็จสมบูรณ์ | Text |
| `order_cancelled` | สถานะ → ยกเลิก | Text |
| `order_refunded` | สถานะ → คืนเงิน | Text |
| `admin_new_order` | Admin: ออเดอร์ใหม่ | Text |
| `admin_new_user` | Admin: ผู้ใช้ใหม่ | Text |
| `admin_low_stock` | Admin: สินค้าเหลือน้อย | Text |
| `admin_out_of_stock` | Admin: สินค้าหมด | Text |
| `payment_slip` | Admin: สลิปชำระเงิน | Text |

### วิธีแก้ไข Template

1. เลือก Template ที่ต้องการจาก Dropdown
2. เลือก **ประเภทข้อความ:** Text หรือ Flex Message (JSON)
3. แก้ไขเนื้อหาในช่อง **เนื้อหา**
4. ใช้ Placeholder ตามตาราง (ดู [หัวข้อ 16](#16-placeholder-reference))
5. กด **บันทึก Template**

### ตัวอย่าง Text Template

```
🎉 ยินดีต้อนรับ {{CUSTOMER_NAME}}!

ขอบคุณที่ช้อปปิ้งกับ {{SITE_NAME}} 🛍️
ออเดอร์ #{{ORDER_NUMBER}} ของคุณได้รับการยืนยันแล้ว
ยอดรวม: {{ORDER_TOTAL}}

ดูรายละเอียด: {{ORDER_URL}}
```

### ตัวอย่าง Flex Message (JSON)

```json
{
  "type": "flex",
  "altText": "ยืนยันออเดอร์ #{{ORDER_NUMBER}}",
  "contents": {
    "type": "bubble",
    "header": {
      "type": "box",
      "layout": "vertical",
      "backgroundColor": "#06C755",
      "contents": [
        { "type": "text", "text": "{{SITE_NAME}}", "color": "#fff", "size": "sm" },
        { "type": "text", "text": "ยืนยันคำสั่งซื้อ", "color": "#fff", "size": "xl", "weight": "bold" }
      ]
    },
    "body": {
      "type": "box",
      "layout": "vertical",
      "contents": [
        { "type": "text", "text": "ออเดอร์: #{{ORDER_NUMBER}}" },
        { "type": "text", "text": "ยอดรวม: {{ORDER_TOTAL}}", "color": "#06C755", "weight": "bold" }
      ]
    },
    "footer": {
      "type": "box",
      "layout": "vertical",
      "contents": [
        {
          "type": "button",
          "style": "primary",
          "color": "#06C755",
          "action": { "type": "uri", "label": "ดูออเดอร์", "uri": "{{ORDER_URL}}" }
        }
      ]
    }
  }
}
```

---

## 9. การตั้งค่า LIFF App

> ข้ามได้หากไม่ต้องการ Mini-app ติดตามออเดอร์

ไปที่ **Settings → LINE Bridge WP → แท็บ LIFF App**

| ช่อง | ค่าที่ใส่ |
|------|-----------|
| **LIFF App ID** | LIFF ID จาก LINE Developers (เช่น `1234567890-XXXXXXXX`) |

กด **บันทึกการตั้งค่า**

หลังบันทึก ระบบจะแสดง **LIFF URL** และ **LIFF Endpoint URL**

นำ **LIFF Endpoint URL** ไปอัปเดตที่ LINE Developers Console → LINE Login Channel → LIFF → แก้ไข Endpoint URL:

```
https://yoursite.com/liff-app/
```

### การใช้งาน LIFF

ลูกค้าสามารถเปิด LIFF App ได้จาก:
- กดปุ่ม **ดูรายละเอียดออเดอร์** ใน Flex Message ที่ส่งให้
- URL โดยตรง: `https://liff.line.me/{LIFF_ID}?order={ORDER_ID}&api=https://yoursite.com/wp-json/`

LIFF App จะ:
1. ยืนยันตัวตนด้วย LINE User ID
2. ดึงข้อมูลออเดอร์ผ่าน REST API
3. แสดงสถานะ รายการสินค้า ยอดรวม และวันที่สั่งซื้อ

---

## 10. การจัดการผู้ใช้ที่เชื่อมต่อ LINE

ไปที่ **Settings → LINE ผู้ใช้**

ตารางแสดง:
- ผู้ใช้ WordPress (ชื่อ + อีเมล)
- LINE Profile (รูปภาพ + ชื่อแสดงใน LINE)
- LINE User ID
- วันที่เชื่อมต่อ

### การยกเลิกการเชื่อมต่อ (Admin)

กด **ยกเลิกเชื่อมต่อ** ที่แถวผู้ใช้ที่ต้องการ → ยืนยัน → ผู้ใช้จะไม่ได้รับการแจ้งเตือนผ่าน LINE อีก

### การยกเลิกการเชื่อมต่อ (ลูกค้า)

ลูกค้าไปที่ **My Account → เชื่อมต่อ LINE** → กด **ยกเลิกการเชื่อมต่อ LINE**

---

## 11. การดู Notification Log

ไปที่ **Settings → LINE บันทึก**

ตารางแสดง:
- **วันที่:** เวลาที่ส่ง
- **ประเภท:** เช่น `order_placed`, `admin_new_order`
- **ผู้รับ:** LINE User ID หรือ Group ID (ย่อ)
- **อ้างอิง:** ลิงก์ไปยังออเดอร์หรือผู้ใช้ที่เกี่ยวข้อง
- **สถานะ:** สำเร็จ / ล้มเหลว / รอ
- **ข้อผิดพลาด:** แสดงเมื่อส่งไม่สำเร็จ

### การกรอง Log

ใช้ Dropdown **ทุกประเภท** และ **ทุกสถานะ** เพื่อกรองรายการ → กด **กรอง**

### Retry อัตโนมัติ

การแจ้งเตือนที่ล้มเหลวจะถูก Retry อัตโนมัติทุก **15 นาที** (สูงสุด 3 ครั้ง) ผ่าน WP Cron

---

## 12. การผสาน Payment Slip (สำหรับ Developer)

Plugin รองรับ Custom Action Hook สำหรับรับการแจ้งชำระเงินจาก Plugin ภายนอก

### วิธีใช้งาน

เรียก Hook นี้จาก Plugin แจ้งชำระเงินของคุณ:

```php
do_action(
    'line_bridge_payment_slip_received',
    $order_id,       // int    — WooCommerce Order ID
    $slip_image_url, // string — URL ของรูปสลิป (ต้อง Public accessible)
    [
        'amount' => '฿1,500.00', // string — ยอดชำระ (optional)
    ]
);
```

### ผลลัพธ์

Admin จะได้รับข้อความ LINE 2 ข้อความ:
1. รูปสลิปชำระเงิน (Image Message)
2. ข้อความรายละเอียด: ออเดอร์ / ลูกค้า / ยอดเงิน / วันที่

### ตัวอย่างการใช้งาน

```php
// ใน Plugin ตรวจสอบสลิปของคุณ
add_action('your_plugin_slip_verified', function($order_id, $image_url, $amount) {
    do_action('line_bridge_payment_slip_received', $order_id, $image_url, [
        'amount' => $amount,
    ]);
}, 10, 3);
```

---

## 13. การทดสอบระบบ

### Checklist ทดสอบตามลำดับ

#### ✅ ทดสอบ LINE Login

1. เปิดหน้า `/wp-login.php` บนเบราว์เซอร์ใหม่ (Incognito)
2. ต้องเห็นปุ่ม **เข้าสู่ระบบด้วย LINE** (สีเขียว) ด้านล่างฟอร์ม
3. กดปุ่ม → เข้าสู่หน้า LINE Authorization
4. เลือกบัญชี LINE → กด **Allow**
5. ต้องถูก Redirect กลับมาที่เว็บ และล็อกอินสำเร็จ
6. ตรวจสอบ **Settings → LINE ผู้ใช้** ต้องมีชื่อผู้ใช้ใหม่ปรากฏ

#### ✅ ทดสอบ Welcome Message

1. ล็อกอิน LINE เป็นครั้งแรก (ยังไม่เคยล็อกอินมาก่อน)
2. ต้องได้รับข้อความต้อนรับใน LINE

#### ✅ ทดสอบออเดอร์ใหม่

1. ล็อกอินเข้าเว็บด้วยบัญชีที่เชื่อมต่อ LINE แล้ว
2. สั่งซื้อสินค้า → กด Place Order
3. ต้องได้รับ Flex Message ยืนยันออเดอร์ใน LINE
4. Admin ต้องได้รับแจ้งเตือนออเดอร์ใหม่ใน LINE (หากเปิดใช้งาน)

#### ✅ ทดสอบสถานะออเดอร์

1. ไปที่ WordPress Admin → WooCommerce → Orders
2. เลือกออเดอร์ → เปลี่ยนสถานะเป็น **Processing**
3. กด **Update** → ลูกค้าต้องได้รับข้อความใน LINE

#### ✅ ทดสอบ Webhook

1. ไปที่ LINE Developers Console → Messaging API Channel → Webhook settings
2. กด **Verify** → ต้องได้ ✅ Success

#### ✅ ทดสอบ Admin Notification

1. กดปุ่ม **ส่งข้อความทดสอบ** ในแท็บ Messaging API
2. ต้องได้รับข้อความ LINE ที่ Admin ID

#### ✅ ทดสอบ LIFF (ถ้าตั้งค่าไว้)

1. เปิด LIFF URL จากข้อความ LINE
2. ต้องแสดงสถานะออเดอร์ถูกต้อง

---

## 14. การถอนการติดตั้ง

### แบบที่ 1 — เก็บข้อมูลไว้ (ค่าเริ่มต้น)

ไปที่ **Plugins → Deactivate → Delete**  
ข้อมูลใน Database และ Options จะ **ยังคงอยู่**

### แบบที่ 2 — ลบข้อมูลทั้งหมด

1. ไปที่ **Settings → LINE Bridge WP → แท็บ ขั้นสูง**
2. ✅ เปิดใช้งาน **ลบข้อมูลเมื่อถอนการติดตั้ง**
3. กด **บันทึกการตั้งค่า**
4. ไปที่ **Plugins → Deactivate → Delete**

ระบบจะลบ:
- ตาราง `line_bridge_users`
- ตาราง `line_bridge_notifications`
- ตาราง `line_bridge_message_templates`
- ค่า Options ทั้งหมดใน wp_options
- WP Cron Jobs

> ⚠️ **คำเตือน:** ข้อมูลผู้ใช้ LINE และ Log ทั้งหมดจะหายถาวร

---

## 15. Troubleshooting

### ปัญหา: ไม่เห็นปุ่ม LINE Login

| สาเหตุที่เป็นไปได้ | วิธีแก้ |
|-------------------|---------|
| ยังไม่ได้ใส่ Channel ID | ไปที่ Settings → LINE Login → ใส่ Channel ID |
| WooCommerce ไม่ได้ติดตั้ง | ติดตั้งและเปิดใช้งาน WooCommerce |
| Theme Override ฟอร์ม Login | ตรวจสอบ Theme ว่ามี Hook `wp_login_form` หรือไม่ |

---

### ปัญหา: กด LINE Login แล้วได้รับ Error "Invalid state token"

**สาเหตุ:** State token หมดอายุ (15 นาที) หรือ Transient ถูกลบ  
**วิธีแก้:** กดปุ่ม LINE Login ใหม่อีกครั้ง

---

### ปัญหา: Webhook Verify ไม่ผ่าน (ได้ Error)

| สาเหตุที่เป็นไปได้ | วิธีแก้ |
|-------------------|---------|
| ยังไม่ได้ใส่ Channel Secret | ใส่ Channel Secret ใน Settings → Messaging API |
| เว็บไซต์ไม่ได้ใช้ HTTPS | ติดตั้ง SSL Certificate |
| WordPress REST API ถูกบล็อก | ตรวจสอบ Security Plugin (Wordfence, etc.) |
| Firewall บล็อก LINE IP | Whitelist LINE IP ranges |

**ทดสอบ Webhook URL ด้วย curl:**
```bash
curl -X POST https://yoursite.com/wp-json/line-bridge/v1/webhook \
  -H "Content-Type: application/json" \
  -d '{"events":[]}'
```
ควรได้รับ `{"error":"Forbidden"}` (403) — แสดงว่า endpoint ทำงานถูกต้อง

---

### ปัญหา: ส่งข้อความ LINE ไม่สำเร็จ

1. ตรวจสอบ **Channel Access Token** ใน Settings → Messaging API
2. ตรวจสอบ **Admin LINE ID** — ต้องขึ้นต้นด้วย `U` (Personal) หรือ `C` (Group)
3. ดู Error ใน **Settings → LINE บันทึก**
4. เปิด `WP_DEBUG_LOG` ใน `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```
   แล้วดู `/wp-content/debug.log`

---

### ปัญหา: ลูกค้าไม่ได้รับการแจ้งเตือนออเดอร์

| สาเหตุที่เป็นไปได้ | วิธีแก้ |
|-------------------|---------|
| ลูกค้าไม่ได้เชื่อมต่อ LINE | ลูกค้าต้องล็อกอินด้วย LINE หรือเชื่อมต่อที่ My Account |
| ฟีเจอร์แจ้งเตือนปิดอยู่ | Settings → การแจ้งเตือน → เปิดใช้งาน |
| ออเดอร์ถูกแจ้งเตือนไปแล้ว | ระบบป้องกัน Duplicate — ออเดอร์เดิมจะไม่ถูกส่งซ้ำ |

---

### ปัญหา: ลูกค้าสมัครสมาชิกด้วย LINE แล้วได้รับ Username แปลกๆ

**สาเหตุ:** ชื่อ LINE มีตัวอักษรพิเศษหรือภาษาไทย ระบบจะตัดออกและใช้ `line_` + ชื่อ  
**วิธีแก้:** ลูกค้าสามารถแก้ไข Display Name และ Username ได้เองใน **My Account → Account Details**

---

### ปัญหา: WP Cron ไม่ทำงาน (Retry ไม่ทำงาน)

บางโฮสต์ปิด WP Cron ค่าเริ่มต้น ให้ตั้งค่า Real Cron Job:

```bash
# เพิ่มใน crontab (cPanel → Cron Jobs)
*/15 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

แล้วเพิ่มใน `wp-config.php`:
```php
define('DISABLE_WP_CRON', true);
```

---

## 16. Placeholder Reference

ใช้ Placeholder เหล่านี้ใน Message Template

| Placeholder | ความหมาย | ใช้ได้ใน Template |
|-------------|----------|------------------|
| `{{SITE_NAME}}` | ชื่อเว็บไซต์ (ค่าจาก General Settings) | ทุก Template |
| `{{DATE_TIME}}` | วันที่และเวลาปัจจุบัน (รูปแบบ d/m/Y H:i) | ทุก Template |
| `{{CUSTOMER_NAME}}` | ชื่อ-นามสกุล Billing ของลูกค้า | Order Templates |
| `{{ORDER_NUMBER}}` | หมายเลขออเดอร์ | Order Templates |
| `{{ORDER_TOTAL}}` | ยอดรวมพร้อมสกุลเงิน | Order Templates |
| `{{ORDER_STATUS}}` | สถานะออเดอร์ (ภาษาไทย) | Order Templates |
| `{{ORDER_URL}}` | ลิงก์ดูรายละเอียดออเดอร์ | Order Templates |
| `{{TRACKING_URL}}` | ลิงก์ติดตามพัสดุ (ปัจจุบันใช้ ORDER_URL) | Shipped Template |
| `{{PRODUCT_NAME}}` | ชื่อสินค้า | Stock Templates |
| `{{STOCK_QTY}}` | จำนวนสินค้าคงเหลือ | Stock Templates |
| `{{USER_DISPLAY_NAME}}` | ชื่อแสดงของผู้ใช้ WordPress | User Templates |
| `{{USER_EMAIL}}` | อีเมลของผู้ใช้ WordPress | User Templates |
| `{{SLIP_IMAGE_URL}}` | URL รูปสลิปชำระเงิน | Payment Slip Template |
| `{{PAYMENT_AMOUNT}}` | ยอดชำระในสลิป | Payment Slip Template |

---

## Custom Hooks (สำหรับ Developer)

### Action Hooks ที่ Plugin ยิงออกมา

```php
// เมื่อผู้ใช้เชื่อมต่อ LINE สำเร็จ
add_action('line_bridge_user_linked', function(int $wp_user_id, array $line_profile) {
    // $line_profile มี: userId, displayName, pictureUrl, email
}, 10, 2);

// เมื่อผู้ใช้ยกเลิกการเชื่อมต่อ LINE
add_action('line_bridge_user_unlinked', function(int $wp_user_id) {
    // ทำอะไรก็ได้หลังยกเลิกเชื่อมต่อ
});

// เมื่อผู้ใช้สมัครสมาชิกใหม่ผ่าน LINE (Auto Register)
add_action('line_bridge_user_registered', function(int $wp_user_id, array $line_profile) {
    // สามารถตั้งค่า Meta เพิ่มเติมได้
}, 10, 2);
```

### Filter Hooks

```php
// แก้ไขข้อความก่อนส่ง
add_filter('line_bridge_before_send_message', function(array $messages, string $recipient_id, array $meta) {
    // $messages คือ array ของ LINE message objects
    return $messages;
}, 10, 3);

// เปลี่ยน URL Redirect หลังล็อกอิน LINE
add_filter('line_bridge_login_redirect', function(string $redirect_url, WP_User $user) {
    return home_url('/dashboard/');
}, 10, 2);
```

---

## โครงสร้างไฟล์ Plugin

```
line-bridge-wp/
├── line-bridge-wp.php          # จุดเริ่มต้น Plugin
├── uninstall.php               # ลบข้อมูลเมื่อถอนการติดตั้ง
├── includes/
│   ├── api/                    # REST API Endpoints
│   ├── auth/                   # LINE OAuth 2.0
│   ├── database/               # CRUD Database
│   ├── liff/                   # LIFF Manager
│   ├── messaging/              # LINE API + Message Builder
│   ├── notifications/          # ตัวจัดการการแจ้งเตือน
│   └── utilities/              # Crypto, Logger, Template Renderer
├── admin/                      # Admin Panel
│   ├── class-admin.php
│   ├── class-settings-page.php
│   ├── class-users-list-table.php
│   ├── class-logs-list-table.php
│   └── partials/               # หน้า Settings แต่ละแท็บ
├── public/                     # Frontend
├── liff-app/                   # LIFF Mini-app (HTML/JS/CSS)
├── assets/                     # CSS, JS, Images
└── languages/                  # ไฟล์แปลภาษา
```

---

*LINE Bridge WP v1.0.0 — สร้างสำหรับ WordPress 6.9.4+ / WooCommerce 10.7.0+ / PHP 8.1+*
