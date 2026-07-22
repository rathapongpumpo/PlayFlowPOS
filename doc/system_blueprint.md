# System Blueprint: PlayFlowPOS
**ระบบบริหารจัดการร้านนวดและสปา (Multi-Branch Spa & Massage Management System)**

---

## 1. ภาพรวมระบบ (System Overview)

PlayFlowPOS เป็นระบบ Web Application สำหรับบริหารจัดการธุรกิจร้านนวดและสปา รองรับการทำงานแบบหลายสาขา (Multi-Branch / Multi-Shop) ออกแบบโครงสร้างสถาปัตยกรรมด้วย **Service Layer Architecture** บน **Laravel Framework** ที่แยกส่วน Logic ออกจาก Controller เพื่อความง่ายในการดูแลและขยายระบบในอนาคต

### Technology Stack
* **Backend Framework:** Laravel 6.x (baseline) / PHP 8.5 Runtime (พร้อม Auto Compatibility Patch)
* **Frontend:** Laravel Blade Templates + Bootstrap 5 + FontAwesome (Server-side rendering)
* **Database:** MySQL / MariaDB (ใช้ `Illuminate\Support\Facades\DB` Query Builder ร่วมกับ Database Transactions)
* **Authentication & Authorization:** Custom Session Auth + Role-Based Access Control (RBAC) Middleware

---

## 2. โครงสร้างซอฟต์แวร์ (Software Architecture)

ระบบใช้สถาปัตยกรรม **Controller -> Service Layer -> Database (Query Builder)**

```
┌─────────────────────────────────────────────────────────────┐
│                       HTTP Request                          │
└──────────────────────────────┬──────────────────────────────┘
                               │
                        [ Middleware ]
             (Auth, ShopAccess, RoleAllowed, AdminOnly)
                               │
                               ▼
                        [ Controllers ]
           (รับ Request, Validate Input, คืนค่า View/JSON)
                               │
                               ▼
                         [ Service Layer ]
           (Business Logic, Transaction, Multi-branch context)
                               │
                               ▼
                        [ Database (DB) ]
                 (MySQL Tables & Query Builder)
```

### โครงสร้างโฟลเดอร์หลัก (Directory Structure)

```
PlayFlowPOS/
├── app/
│   ├── Http/
│   │   ├── Controllers/         # จัดการ HTTP Requests & UI Routing
│   │   └── Middleware/          # ตรวจสอบ Auth, Branch Context, และ Roles
│   └── Services/                # ศูนย์รวม Business Logic ทั้งหมดของระบบ (24+ Services)
├── config/                      # ไฟล์ตั้งค่าระบบ (app, database, auth ฯลฯ)
├── database/
│   ├── migrations/              # Database Schema Definitions
│   └── seeds/                   # Initial Data Seeders
├── doc/                         # เอกสารประกอบระบบ (Blueprint & Documentation)
├── resources/
│   └── views/                   # Blade View Templates แยกตามโมดูล
├── routes/
│   └── web.php                  # Web Routes & Middleware Assignment
└── scripts/
    └── apply_php85_compat.php   # Script รักษาสภาพแวดล้อม PHP 8.5 Compatibility
```

---

## 3. สิทธิ์การใช้งาน (Role-Based Access Control - RBAC)

ระบบควบคุมสิทธิ์ตามบทบาทผ่าน Middleware `EnsureRoleAllowed` และ `EnsureAdminOnly` ดังนี้:

| Role | สิทธิ์การใช้งานหลัก |
| :--- | :--- |
| **Super Admin** | จัดการทุกร้านค้า (`system/shops`), สลับสาขา, จัดการการตั้งค่าระดับระบบ |
| **Shop Owner** | บริหารจัดการร้านค้าของตนเอง, ดูรายงานภาพรวม, จัดการพนักงาน/หมอนวด, ตั้งค่าคอมมิชชัน |
| **Branch Manager**| จัดการสาขาที่สังกัด, ดูดูแลคิว/การจอง, เปิด-ปิดร้านประจำวัน, สรุปยอดประจำสาขา |
| **Cashier / Reception**| ทำรายการ POS (ขาย/คิดเงิน), จัดการคิว Walk-in และการจอง, เติมเงินกระเป๋า/ตัดแต้ม |
| **Masseuse (หมอนวด)**| เข้าถึง Dashboard ส่วนตัว (`/my-commission`) ดูคิวงานและยอดคอมมิชชันของตนเอง |

---

## 4. สรุปโมดูลหลักของระบบ (Core System Modules)

### 1. ระบบจัดการบริบทสาขา (Multi-Branch & Context Management)
* **[ShopContextService](file:///c:/Projects/PlayFlowPOS/app/Services/ShopContextService.php) / [BranchContextService](file:///c:/Projects/PlayFlowPOS/app/Services/BranchContextService.php):** จัดการ Session และการจำกัด Scope ข้อมูลของ User แต่ละสาขา ป้องกันข้อมูลรั่วไหลข้ามสาขา

### 2. ระบบขายหน้าร้าน (POS Engine)
* **[PosController](file:///c:/Projects/PlayFlowPOS/app/Http/Controllers/PosController.php) / [PosService](file:///c:/Projects/PlayFlowPOS/app/Services/PosService.php):** 
  * รองรับการคิดเงินค่าบริการนวด สินค้าขายปลีก แพ็กเกจ
  * คำนวณส่วนลดท้ายบิล, ตัดเงินผ่าน Wallet / Points / เงินสด / QR
  * สร้าง Order & Receipt พร้อมคำนวณค่าคอมมิชชันหมอนวดให้อัตโนมัติใน Transaction เดียวกัน

### 3. ระบบจองคิวและห้องนวด (Booking & Room Operations)
* **[BookingService](file:///c:/Projects/PlayFlowPOS/app/Services/BookingService.php) / [MassageRoomService](file:///c:/Projects/PlayFlowPOS/app/Services/MassageRoomService.php):**
  * จัดการคิว Walk-in และ Advance Booking
  * ตรวจสอบเวลาซ้ำซ้อน (Time Conflict Validation) ของทั้งหมอนวดและห้อง/เตียงนวด
  * แสดงผลสถานะคิว (Waiting, In-service, Completed, Cancelled)

### 4. ระบบจัดการหมอนวดและคอมมิชชัน (Masseuse & Commission)
* **[MasseuseService](file:///c:/Projects/PlayFlowPOS/app/Services/MasseuseService.php) / [CommissionService](file:///c:/Projects/PlayFlowPOS/app/Services/CommissionService.php):**
  * บันทึกการลงเวลาเข้า-ออกงาน (Attendance & Shifts)
  * คิดค่าคอมมิชชันทั้งแบบ % และแบบ Fix Rate (บาท/ชั่วโมง)
  * คำนวณค่าประกันรายได้ (Guarantee Amount) ของหมอนวด

### 5. ระบบลูกค้าสัมพันธ์และสมาชิก (CRM, Points & Wallet)
* **[CustomerService](file:///c:/Projects/PlayFlowPOS/app/Services/CustomerService.php) / [WalletService](file:///c:/Projects/PlayFlowPOS/app/Services/WalletService.php) / [PointService](file:///c:/Projects/PlayFlowPOS/app/Services/PointService.php):**
  * ประวัติการรับบริการ ข้อควรระวังในการนวด (Medical Notes/Preferences)
  * กระเป๋าเงินอิเล็กทรอนิกส์ (Prepaid Wallet) และระบบคะแนนสะสม (Loyalty Points)
  * ระดับสมาชิก (Membership Tiers: Silver, Gold, Platinum)

### 6. ระบบคลังสินค้าและวัตถุดิบ (Inventory & Store Assets)
* **[ProductService](file:///c:/Projects/PlayFlowPOS/app/Services/ProductService.php) / [StoreAssetService](file:///c:/Projects/PlayFlowPOS/app/Services/StoreAssetController.php):**
  * ตัดสต็อกสินค้าขายปลีกอัตโนมัติเมื่อมีการทำรายการ POS
  * จัดการวัสดุอุปกรณ์ใช้ภายในร้าน (Internal Store Assets) และบันทึกการปรับยอด (Stock Adjustment)

### 7. ระบบเปิด-ปิดร้านประจำวัน (Store Operations)
* **[StoreOperationsController](file:///c:/Projects/PlayFlowPOS/app/Http/Controllers/StoreOperationsController.php):** บันทึกเงินทอนเริ่มต้น (Drawer Opening Balance) สรุปยอดเงินสดปิดกล่อง และตรวจสอบความถูกต้องของลิ้นชักเก็บเงิน

---

## 5. ตารางฐานข้อมูลหลัก (Core Database Tables)

* **`users` / `staff`:** ข้อมูลผู้ใช้งาน พนักงาน และสิทธิ์เข้าถึง
* **`masseuses` / `masseuse_shifts` / `masseuse_attendances`:** ตารางข้อมูลหมอนวด กะการทำงาน และการลงเวลา
* **`customers` / `customer_wallets` / `customer_points`:** ฐานข้อมูลลูกค้า กระเป๋าเงิน และแต้มสะสม
* **`services` / `service_categories`:** รายการบริการนวดและสปา
* **`products` / `product_categories` / `store_assets`:** สินค้าขายปลีกและวัตถุดิบในร้าน
* **`massage_rooms` / `massage_beds`:** ห้องนวดและเตียงนวด
* **`bookings` / `booking_services`:** ข้อมูลการจองคิว
* **`orders` / `order_items` / `receipts`:** บิลสั่งซื้อ รายการสินค้า/บริการ และใบเสร็จ
* **`commissions` / `commission_configs`:** รายการค่าคอมมิชชันที่หมอนวดได้รับและการตั้งค่าสูตร
* **`store_operations`:** บันทึกการเปิด-ปิดลิ้นชักเงินประจำวัน

---

## 6. คำแนะนำสำหรับการพัฒนาต่อ (Developer Onboarding & Next Steps)

สำหรับนักพัฒนาที่เข้ามาสานงานต่อ ให้ดำเนินการตามขั้นตอนดังนี้:

### 1. การเตรียมสภาพแวดล้อม (Local Setup)
```bash
# 1. ติดตั้ง Composer Dependencies
composer install

# 2. คัดลอกและตั้งค่า Environment
cp .env.example .env
php artisan key:generate

# 3. ตั้งค่า Database ใน .env แล้ว Run Migrations
php artisan migrate

# 4. เริ่มต้น Local Dev Server
php artisan serve
```

### 2. ข้อควรระวังในการเขียนโค้ด (Coding Conventions & Rules)
1. **ตรวจสอบโครงสร้างระบบอย่างละเอียดก่อนแก้ไขโค้ดทุกครั้ง:** ต้องตรวจสอบคอลัมน์ในตาราง (Schema) และความสัมพันธ์ของไฟล์ที่เกี่ยวข้องทั้งหมดก่อนเขียนหรือแก้ไขโค้ดทุกครั้ง เพื่อป้องกันข้อผิดพลาด 500 Server Error
2. **อย่าเขียน Logic ใน Controller:** ให้สร้างหรือเพิ่ม Business Logic ลงใน `App\Services\*` เสมอ
3. **ใช้ Database Transactions:** การทำรายการที่ส่งผลต่อหลายตาราง (เช่น POS Checkout ที่กระทบ Order, Stock, Wallet, Points, Commission) ต้องหุ้มด้วย `DB::transaction(function() { ... })` เสมอ
4. **ตรวจสอบ Branch Context:** ต้องกรองข้อมูลด้วย `branch_id` หรือใช้ `BranchContextService` ในทุก Query ของระดับสาขา
5. **PHP 8.5 Compatibility Guard:** หากมีการแก้ไข Composer Script ห้ามลบ `@php scripts/apply_php85_compat.php` ออกจาก `composer.json`
