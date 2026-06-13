# GadgetZone — เว็บไซต์ E-Commerce ขายสินค้าไอที (PHP + MySQL)

เว็บไซต์ขายสินค้ากลุ่ม Gadget แบบครบวงจร เขียนด้วย PHP 8 + MySQL/MariaDB ล้วน
ไม่พึ่ง framework ใหญ่ ๆ ไม่ใช้ Composer ติดตั้งง่ายบน XAMPP

มาพร้อมหน้าร้าน, ระบบสมาชิก, ตะกร้าสินค้า, ระบบสั่งซื้อ (COD / bKash / Nagad / บัตรเครดิตผ่าน Stripe),
หลังบ้านสำหรับผู้ดูแล (CRUD สินค้า, จัดการคำสั่งซื้อ, ผู้ใช้, การตั้งค่าหลายสกุลเงิน)

---

## สารบัญ

1. [ฟีเจอร์ทั้งหมด](#ฟีเจอร์ทั้งหมด)
2. [เทคโนโลยีที่ใช้](#เทคโนโลยีที่ใช้)
3. [โครงสร้างไฟล์](#โครงสร้างไฟล์)
4. [สิ่งที่ต้องมีก่อนติดตั้ง](#สิ่งที่ต้องมีก่อนติดตั้ง)
5. [ติดตั้งบนเครื่องตัวเอง (Local + XAMPP)](#ติดตั้งบนเครื่องตัวเอง-local--xampp)
6. [การตั้งค่าไฟล์ .env](#การตั้งค่าไฟล์-env)
7. [ทดลองเข้าใช้งาน](#ทดลองเข้าใช้งาน)
8. [การใช้ TiDB Cloud / MySQL บนคลาวด์](#การใช้-tidb-cloud--mysql-บนคลาวด์)
9. [Deploy ขึ้น Vercel](#deploy-ขึ้น-vercel)
10. [Deploy ขึ้นโฮสต์อื่น](#deploy-ขึ้นโฮสต์อื่น)
11. [การแก้ปัญหาที่พบบ่อย](#การแก้ปัญหาที่พบบ่อย)
12. [License](#license)

---

## ฟีเจอร์ทั้งหมด

### ฝั่งหน้าร้าน (Storefront)

| หน้า | รายละเอียด |
|------|-------------|
| Home | Hero, แถบฟีเจอร์, หมวดหมู่, สินค้าแนะนำ, Deal of the Day พร้อมนับถอยหลัง, สินค้าใหม่, รีวิว, สมัครรับข่าวสาร |
| Shop | ค้นหา, กรองตามหมวดหมู่/ราคา/badge, เรียงลำดับ, แบ่งหน้า (9 ต่อหน้า) |
| Product Detail | ภาพสินค้า, รายละเอียด, จำนวน, ปุ่มเพิ่มเข้าตะกร้า, สินค้าที่เกี่ยวข้อง |
| Cart | ตะกร้าสินค้าแบบ AJAX (เพิ่ม/ลบ/เปลี่ยนจำนวน) มี fallback แบบ form, แสดงความคืบหน้าค่าจัดส่งฟรี |
| Checkout | กรอกข้อมูลผู้รับ + เลือกวิธีชำระเงิน 4 แบบ (เงินสด, bKash, Nagad, บัตรเครดิต Stripe) |
| My Account | Dashboard, ประวัติคำสั่งซื้อ, แก้ไขโปรไฟล์ + อัปโหลดรูป, เปลี่ยนรหัสผ่าน |
| Login / Register | สมัคร, ล็อกอิน, ล็อกเอาท์ |

### ฝั่งหลังบ้าน (Admin Panel — `/admin`)

| หน้า | รายละเอียด |
|------|-------------|
| Dashboard | สถิติยอดขาย, จำนวนคำสั่งซื้อ/สินค้า/ผู้ใช้, แจ้งเตือนสินค้าใกล้หมด, รายการคำสั่งซื้อล่าสุด, สินค้าขายดี Top 5 |
| Products | CRUD สินค้า (เพิ่ม/แก้ไข/ลบ), อัปโหลดรูปหรือใส่ URL, ค้นหา, กรองตามหมวดหมู่, ป้องกันการลบสินค้าที่มีคำสั่งซื้อ |
| Orders | รายการคำสั่งซื้อ + ค้นหา + กรองตามสถานะ + แบ่งหน้า 15/หน้า, อัปเดตสถานะใน column, หน้ารายละเอียดผ่าน `?id=` |
| Users | จัดการผู้ใช้, สถิติตาม role, เพิ่มผู้ใช้ใหม่ผ่าน modal, เปลี่ยน role (super_admin เท่านั้น), ป้องกันลบผู้ใช้ที่มีคำสั่งซื้อ |
| Settings | เลือกสกุลเงินจาก 12 สกุลพร้อมตัวอย่างราคาสด, ใส่ Stripe API keys (รองรับโหมด Sandbox / LIVE / Not Set) |

### ฟีเจอร์ระดับ Platform

- เก็บราคาทุกอย่างใน BDT แล้วแปลงเป็นสกุลเงินอื่นทันทีตอนแสดงผล (รองรับ 12 สกุลเงิน)
- ใช้ Prepared Statement ทั้งหมดเพื่อป้องกัน SQL Injection
- เข้ารหัสรหัสผ่านด้วย `password_hash()` (bcrypt)
- ใช้ `htmlspecialchars()` ป้องกัน XSS
- ตะกร้าสินค้าเก็บใน Session แบบ `$_SESSION['cart']`
- ค่าคงที่ `BASE_URL` ตัวเดียวคุมทุก URL ในระบบ ใช้ได้ทั้งใน sub-folder และ root domain
- ไฟล์ `.env` แยก credential ออกจากโค้ด (ไม่ขึ้น Git)

---

## เทคโนโลยีที่ใช้

| ส่วน | เทคโนโลยี |
|------|-------------|
| Backend | PHP 8+ (mysqli แบบ OO) |
| Frontend | HTML5, CSS3 (variables, grid, flexbox), Vanilla JavaScript |
| Database | MySQL 5.7+ / MariaDB / TiDB (compatible) |
| ฟอนต์ | IBM Plex Sans (หัวข้อ), DM Sans (เนื้อหา) |
| Theme | Dark theme — background `#0a0a0f`, accent สีอำพัน `#f59e0b` |
| Payment | Cash on Delivery, bKash, Nagad, Stripe Checkout |

ไม่ต้องใช้ Node.js, npm, Composer หรือ build step ใด ๆ — แก้ไฟล์แล้วรีโหลดเบราว์เซอร์ได้เลย

---

## โครงสร้างไฟล์

```
gadgetzone/
├── index.php                      หน้า Home
├── database_setup.sql             สคริปต์สร้างตาราง + ข้อมูลตัวอย่าง
├── migration_stripe_currency.sql  Migration เพิ่มคอลัมน์ Stripe + ตาราง settings
├── vercel.json                    ตั้งค่า Deploy บน Vercel
├── .env                           ค่าเชื่อมต่อฐานข้อมูล (ไม่ถูก commit)
├── .gitignore
│
├── includes/
│   ├── db.php                     เชื่อมต่อ DB + load .env + เริ่ม session
│   ├── functions.php              ฟังก์ชันช่วย: auth, cart, formatPrice, sanitize
│   ├── currency.php               ระบบหลายสกุลเงิน + formatPrice()
│   ├── header.php                 Navbar + ค้นหา + cart badge
│   └── footer.php                 Footer + โหลด main.js
│
├── pages/
│   ├── shop.php                   หน้ารวมสินค้าพร้อมตัวกรอง
│   ├── product.php                หน้ารายละเอียดสินค้า
│   ├── cart.php                   ตะกร้าสินค้า
│   ├── cart_action.php            AJAX endpoint จัดการตะกร้า
│   ├── checkout.php               หน้าชำระเงิน
│   ├── stripe_checkout.php        สร้าง Stripe Checkout Session
│   ├── stripe_return.php          callback จาก Stripe
│   ├── order_success.php          หน้าหลังสั่งซื้อสำเร็จ
│   ├── myaccount.php              ศูนย์รวมบัญชีผู้ใช้
│   ├── login.php                  ล็อกอิน
│   ├── register.php               สมัครสมาชิก
│   └── logout.php                 ออกจากระบบ
│
├── admin/
│   ├── index.php                  Dashboard
│   ├── products.php               จัดการสินค้า
│   ├── orders.php                 จัดการคำสั่งซื้อ + detail view
│   ├── users.php                  จัดการผู้ใช้
│   ├── settings.php               ตั้งค่าสกุลเงิน + Stripe
│   ├── layout.php                 หัวข้อ + sidebar + auth guard
│   ├── footer.php                 ปิดแท็ก + โหลด admin.js
│   ├── admin.css                  สไตล์เฉพาะ admin
│   ├── admin.js                   Modal + sidebar + image preview
│   └── uploads/                   เก็บรูปสินค้าที่อัปโหลด
│
└── assets/
    ├── css/style.css              สไตล์หลักของ storefront
    ├── js/main.js                 JS หลัก (auto-detect BASE_URL)
    └── uploads/avatars/           เก็บรูปโปรไฟล์ผู้ใช้
```

---

## สิ่งที่ต้องมีก่อนติดตั้ง

| สิ่งที่ต้องมี | ใช้ทำอะไร |
|------------|------------|
| **XAMPP** หรือ **CAMPP** / WAMP / MAMP | ทำเซิร์ฟเวอร์ Apache + PHP + MySQL บนเครื่อง<br>(XAMPP ใช้พอร์ต **80**, CAMPP ใช้พอร์ต **8080**) |
| **PHP 8.0+** | ตัวแปลภาษา PHP (มากับ XAMPP) |
| **MySQL / MariaDB** | ฐานข้อมูล (มากับ XAMPP) — หรือใช้ TiDB Cloud แทนก็ได้ |
| **Git** | สำหรับ clone repo (ไม่บังคับถ้าดาวน์โหลด ZIP) |
| เบราว์เซอร์ | Chrome / Firefox / Edge เวอร์ชันใหม่ |

ดาวน์โหลด XAMPP ได้ที่ https://www.apachefriends.org/

---

## ติดตั้งบนเครื่องตัวเอง (Local + XAMPP)

### ขั้นที่ 1 — Clone หรือดาวน์โหลดโปรเจ็กต์

```bash
git clone https://github.com/thanakornleelalai/gadgetzone.git
```

หรือ Download ZIP แล้วแตกไฟล์

### ขั้นที่ 2 — ย้ายโฟลเดอร์เข้า htdocs

คัดลอกโฟลเดอร์ทั้งหมดไปไว้ใน `htdocs` ของ XAMPP โดย **ต้องตั้งชื่อโฟลเดอร์เป็น `gadgetzone`**

```
Windows : C:\xampp\htdocs\gadgetzone\
macOS   : /Applications/XAMPP/htdocs/gadgetzone/
Linux   : /opt/lampp/htdocs/gadgetzone/
```

> ถ้าตั้งชื่ออื่น ต้องไปแก้ค่า `GZ_BASE_URL` ใน `.env` ให้ตรงด้วย

### ขั้นที่ 3 — เปิด XAMPP

เปิด XAMPP Control Panel แล้วกด **Start** ทั้ง:
- Apache
- MySQL

### ขั้นที่ 4 — Import ฐานข้อมูล

1. เปิดเบราว์เซอร์ไปที่ phpMyAdmin
   - XAMPP: http://localhost/phpmyadmin/
   - CAMPP: http://127.0.0.1:8080/phpmyadmin/
2. คลิกเมนู **SQL** ด้านบน
3. เปิดไฟล์ `database_setup.sql` ในโปรเจ็กต์ คัดลอกเนื้อหาทั้งหมด
4. วางในช่อง SQL ของ phpMyAdmin แล้วกด **Go**
5. ระบบจะสร้าง database `gadgetzone` พร้อมตาราง 6 ตาราง + ข้อมูลตัวอย่าง (6 หมวดหมู่ + 15 สินค้า + admin 1 คน)

หรือสั่งผ่าน command line:

```bash
mysql -u root -p < database_setup.sql
```

### ขั้นที่ 5 — สร้างไฟล์ `.env`

ในโฟลเดอร์โปรเจ็กต์ สร้างไฟล์ชื่อ `.env` (เริ่มด้วยจุด) ใส่เนื้อหา:

```env
GZ_DB_HOST=127.0.0.1
GZ_DB_PORT=3306
GZ_DB_USER=root
GZ_DB_PASS=
GZ_DB_NAME=gadgetzone
GZ_BASE_URL=/gadgetzone
```

ถ้า MySQL ของ XAMPP ใช้พอร์ตอื่น (เช่น 3307) หรือมีรหัสผ่าน ให้แก้ตามจริง

> **หมายเหตุ:** ไฟล์ `.env` ถูกลิสต์ใน `.gitignore` แล้ว — จะไม่ถูก commit ขึ้น Git

### ขั้นที่ 6 — เข้าหน้าเว็บ

เปิดเบราว์เซอร์ไปที่ URL ตามชนิดเซิร์ฟเวอร์ที่ใช้:

| โปรแกรม | URL |
|---------|-----|
| **XAMPP** (default port 80) | http://localhost/gadgetzone/ |
| **CAMPP** (default port 8080) | http://127.0.0.1:8080/gadgetzone/ |
| **WAMP / MAMP** (port อื่น) | `http://localhost:<port>/gadgetzone/` |

> ถ้าไม่แน่ใจว่าใช้พอร์ตอะไร เปิด Control Panel ของโปรแกรมที่ใช้ ดูที่ช่อง Apache จะระบุพอร์ตไว้

ถ้าเห็นหน้า Home แสดงสินค้าและภาพต่าง ๆ ขึ้นมา = ติดตั้งสำเร็จ

---

## การตั้งค่าไฟล์ .env

ไฟล์ `.env` ใช้เก็บค่าที่เปลี่ยนไปตามสภาพแวดล้อม (local / production)

| Key | คำอธิบาย | ค่าตัวอย่าง |
|-----|-----------|--------------|
| `GZ_DB_HOST` | ที่อยู่ MySQL server | `127.0.0.1` |
| `GZ_DB_PORT` | พอร์ต MySQL | `3306` |
| `GZ_DB_USER` | ชื่อผู้ใช้ DB | `root` |
| `GZ_DB_PASS` | รหัสผ่าน DB | (ว่างถ้าไม่มี) |
| `GZ_DB_NAME` | ชื่อฐานข้อมูล | `gadgetzone` |
| `GZ_DB_SSL` | เปิดใช้ TLS (1 = เปิด) — ใช้ตอนต่อ TiDB / PlanetScale | `1` |
| `GZ_BASE_URL` | path ที่เว็บอยู่ — สำคัญมาก | `/gadgetzone` หรือเว้นว่าง |

### การกำหนด BASE_URL

| สภาพแวดล้อม | ค่าที่ตั้ง |
|---------------|-------------|
| XAMPP / CAMPP local (`.../gadgetzone/...`) | `GZ_BASE_URL=/gadgetzone` |
| Deploy ขึ้น domain ที่ root (`example.com/`) | ไม่ต้องตั้ง หรือเว้นว่าง |
| Deploy ใต้ path (`example.com/shop/`) | `GZ_BASE_URL=/shop` |

ถ้าตั้งผิด รูป CSS JS จะโหลดไม่ขึ้น ลิงก์จะ 404

---

## ทดลองเข้าใช้งาน

### บัญชีผู้ดูแลตัวอย่าง (Default Super Admin)

| Field | Value |
|-------|-------|
| Email | `admin@gadgetzone.com` |
| Password | `Admin@1234` |

เปิดหน้า Admin (เลือกตาม server ที่ใช้):
- XAMPP: http://localhost/gadgetzone/admin/
- CAMPP: http://127.0.0.1:8080/gadgetzone/admin/

ล็อกอินด้วยบัญชีนี้ จะเข้าสู่ Admin Dashboard

> รหัสผ่านถูก hash ด้วย bcrypt เก็บในคอลัมน์ `password` ของตาราง `users`

### ทดลองสั่งซื้อสินค้า

1. เปิดหน้า Home → คลิกสินค้า → กด **Add to Cart**
2. คลิกไอคอนตะกร้ามุมขวาบน → ปรับจำนวน → กด **Proceed to Checkout**
3. กรอกข้อมูลผู้รับ + เลือกวิธีชำระเงิน (เริ่มที่ Cash on Delivery ก่อน)
4. กด **Place Order** → จะได้หมายเลขคำสั่งซื้อ `GZ-XXXXX`
5. กลับเข้า Admin → Orders จะเห็นคำสั่งซื้อที่เพิ่งสร้าง

### ทดลองชำระผ่านบัตรเครดิต (Stripe)

ก่อนใช้งานต้องตั้ง Stripe key ก่อน:

1. ไปที่ https://dashboard.stripe.com → เปิด **Test Mode**
2. ไปที่ **Developers → API keys** คัดลอก:
   - Publishable key (`pk_test_...`)
   - Secret key (`sk_test_...`)
3. เข้า Admin → **Settings** → กรอก key ทั้งสอง → **Save**
4. กลับมาที่หน้า Checkout จะเห็นตัวเลือก **Credit/Debit Card**

บัตรทดสอบของ Stripe:

| ประเภท | หมายเลข | Exp | CVC |
|---------|----------|------|------|
| Visa สำเร็จ | `4242 4242 4242 4242` | `12/30` | `123` |
| Mastercard | `5555 5555 5555 4444` | `12/30` | `123` |
| ปฏิเสธ | `4000 0000 0000 0002` | `12/30` | `123` |

---

## การใช้ TiDB Cloud / MySQL บนคลาวด์

ถ้าต้องการให้ DB อยู่บนคลาวด์ (ไม่ต้องเปิด XAMPP) ใช้ TiDB Cloud (มี free tier) ได้:

### ขั้นตอน

1. สมัครที่ https://tidbcloud.com → สร้าง **Serverless Cluster** (ฟรี)
2. คลิก **Connect** → เลือก **Standard Connection** → คัดลอก credential
3. แก้ไฟล์ `.env` ในโปรเจ็กต์:

```env
GZ_DB_HOST=gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com
GZ_DB_PORT=4000
GZ_DB_USER=xxxxxxxxxxxxxxx.root
GZ_DB_PASS=xxxxxxxxxxxxxxxx
GZ_DB_NAME=gadgetzone
GZ_DB_SSL=1
GZ_BASE_URL=/gadgetzone
```

4. ใน TiDB Cloud Dashboard → คลิก **Chat2Query** หรือ SQL Editor → วาง content ของ `database_setup.sql` แล้วรัน
5. เปิดเว็บใน browser (XAMPP → http://localhost/gadgetzone/, CAMPP → http://127.0.0.1:8080/gadgetzone/) — ถ้าเห็นสินค้า = เชื่อม TiDB สำเร็จ

> TiDB เป็น MySQL-compatible สามารถใช้คำสั่ง MySQL ได้เกือบทั้งหมด สคริปต์ `database_setup.sql` ทำงานได้ปกติ
> `GZ_DB_SSL=1` จำเป็นเพราะ TiDB Cloud บังคับใช้ TLS

---

## Deploy ขึ้น Vercel

โปรเจ็กต์มีไฟล์ `vercel.json` ให้แล้ว ใช้ community runtime `vercel-php@0.7.3`

### ข้อจำกัดที่ต้องทราบก่อน Deploy

Vercel เป็นแพลตฟอร์มแบบ Serverless ทำให้ฟีเจอร์บางอย่างทำงานไม่เต็มที่:

| ฟีเจอร์ | สถานะบน Vercel |
|---------|------------------|
| หน้า Home / Shop / Product Detail | ทำงานปกติ |
| Login / Register | Session อาจหลุดบ่อย (cold start) |
| Cart | อาจรีเซ็ตเมื่อเปลี่ยนหน้า |
| Admin อัปโหลดรูปสินค้า | ใช้ไม่ได้ (filesystem read-only) |
| Currency Switcher | รีเซ็ตทุก request |

ถ้าต้องการให้ทำงานครบ ใช้ **Railway** หรือ **Render** หรือ **VPS** จะตรงไปตรงมากว่า

### ขั้นตอน Deploy บน Vercel

1. ไปที่ https://vercel.com/new
2. Login ด้วย GitHub → เลือก repo `gadgetzone`
3. ตั้งค่า:
   - **Framework Preset:** Other
   - **Root Directory:** `./`
   - **Build/Output:** ปล่อยว่าง
4. คลิก **Environment Variables** กรอก:

   ```
   GZ_DB_HOST  = (host ของ TiDB)
   GZ_DB_PORT  = 4000
   GZ_DB_USER  = (user ของ TiDB)
   GZ_DB_PASS  = (password ของ TiDB)
   GZ_DB_NAME  = gadgetzone
   GZ_DB_SSL   = 1
   ```

   > ไม่ต้องตั้ง `GZ_BASE_URL` เพราะ Vercel ใช้ root domain

5. กด **Deploy** รอประมาณ 1–3 นาที
6. ได้ URL กลับมา เช่น `gadgetzone-xxxx.vercel.app`

---

## Deploy ขึ้นโฮสต์อื่น

### Railway (แนะนำสำหรับ PHP)

1. สมัครที่ https://railway.app
2. New Project → Deploy from GitHub repo → เลือก `gadgetzone`
3. Add MySQL Plugin หรือใช้ TiDB ภายนอก
4. ตั้ง Environment Variables เหมือนของ Vercel
5. Deploy

ข้อดี: รองรับ filesystem ปกติ session ใช้งานได้ admin upload ใช้ได้

### Hostinger / Cloudways / Shared Hosting ทั่วไป

1. อัปโหลดไฟล์ทั้งหมดผ่าน FTP / File Manager ไปยัง `public_html/`
2. สร้าง database ผ่าน cPanel
3. Import `database_setup.sql` ผ่าน phpMyAdmin
4. สร้าง `.env` พร้อม credentials ของ shared hosting
5. แก้ `GZ_BASE_URL=` (เว้นว่างถ้าเว็บอยู่ที่ root domain)

### VPS (DigitalOcean / Vultr / Linode)

ติดตั้ง LAMP stack ปกติ:

```bash
sudo apt update
sudo apt install apache2 mysql-server php8.1 php8.1-mysql php8.1-mbstring
sudo mysql < database_setup.sql
# clone โปรเจ็กต์เข้า /var/www/html/
# สร้าง .env
```

---

## การแก้ปัญหาที่พบบ่อย

### ปัญหา: `Database connection failed`

**สาเหตุ:** เชื่อมต่อ DB ไม่ได้

**วิธีแก้:**
- ตรวจสอบว่า MySQL ของ XAMPP รันอยู่ (Apache + MySQL = Start)
- ตรวจสอบค่าใน `.env` — host, port, user, pass ถูกต้องหรือไม่
- ถ้าใช้ TiDB ต้องตั้ง `GZ_DB_SSL=1`

### ปัญหา: หน้าเว็บโหลด แต่ CSS / JS ไม่โหลด

**สาเหตุ:** `GZ_BASE_URL` ตั้งไม่ตรงกับ path จริง

**วิธีแก้:**
- ถ้าเข้าเว็บผ่าน `localhost/gadgetzone/` หรือ `127.0.0.1:8080/gadgetzone/` → ตั้ง `GZ_BASE_URL=/gadgetzone`
- ถ้าเข้าเว็บผ่าน `localhost/` (root) → ไม่ต้องตั้ง หรือเว้นว่าง

### ปัญหา: Login ไม่ได้ทั้ง ๆ ที่รหัสผ่านถูก

**สาเหตุ:** Hash ของรหัสผ่านอาจเสีย หรือเป็น MD5 เก่า

**วิธีแก้:** reset รหัสผ่าน admin ผ่าน phpMyAdmin:

```sql
UPDATE users
SET password = '$2y$10$IHDQwagf1JxAncjpyZj5oefo9utzJ7AQG0bez9kMXj2aEexbnIFBa'
WHERE email = 'admin@gadgetzone.com';
```

(รหัสนี้คือ `Admin@1234` แบบ bcrypt)

### ปัญหา: ตะกร้า / login หาย เมื่อเปลี่ยนหน้าบน Vercel

**สาเหตุ:** PHP Session แบบไฟล์ไม่ persistent บน Serverless

**วิธีแก้:** ย้ายไปใช้ Railway / Render แทน หรือเขียน Session handler ที่เก็บใน DB

### ปัญหา: รูปสินค้าใน Admin อัปโหลดไม่ได้บน Vercel

**สาเหตุ:** filesystem ของ Vercel เป็น read-only

**วิธีแก้:** ใช้ URL ของรูปจาก Unsplash หรือใช้บริการ image hosting (Cloudinary, S3) หรือย้ายไป host ที่มี persistent disk

### ปัญหา: Stripe ไม่ขึ้นในหน้า Checkout

**สาเหตุ:** ยังไม่ได้ตั้ง API key

**วิธีแก้:** เข้า Admin → Settings → กรอก key ทั้ง Publishable + Secret (ต้องขึ้นต้นด้วย `pk_` และ `sk_`) แล้วบันทึก

---

## License

โปรเจ็กต์นี้ใช้สำหรับการศึกษา ปรับปรุง และเผยแพร่ต่อได้อย่างอิสระ
แต่ไม่รับประกันการใช้งานในระดับ production

**Copyright 2026 — All rights reserved by Kitpapa.com**

---

## ติดต่อ / ขอความช่วยเหลือ

- Email: support@gadgetzone.com
- GitHub Issues: https://github.com/thanakornleelalai/gadgetzone/issues
