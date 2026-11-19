# IT Announcement & System Change Request (TLP IT Portal)

ระบบสารสนเทศแจ้งเตือนประกาศงาน IT และแบบคำขอเปลี่ยนแปลงระบบเทคโนโลยีสารสนเทศ (FM-IT-029) โรงพยาบาลสัตว์ทองหล่อ (TLP)  
พัฒนาด้วย **PHP 8.3 / Laravel 13** เชื่อมต่อฐานข้อมูล **PostgreSQL** (Schema `intra_tl`)

---

## 🚀 Quick Start (การเริ่มต้นใช้งาน)

### วิธีที่ 1: รันบน Windows
รันคำสั่งผ่านสคริปต์ [serve.bat](file:///c:/IT%20Annoucement%20-%20PHP/serve.bat) หรือรันใน PowerShell:
```powershell
php artisan serve --port=5897
```
เข้าใช้งานผ่านเบราว์เซอร์: [http://localhost:5897](http://localhost:5897)

### วิธีที่ 2: รันผ่าน Docker
```bash
docker build -t it-announcement-tlp:latest .
docker run -d --name it-announcement-app -p 5897:5897 it-announcement-tlp:latest
```

---

## 📌 สถาปัตยกรรมและการทำงานของระบบ (System Architecture)

เอกสารอธิบายการทำงานของระบบอย่างละเอียดทุกโมดูล จัดทำไว้ในไฟล์:  
👉 **[SYSTEM_ARCHITECTURE.md](file:///c:/IT%20Annoucement%20-%20PHP/SYSTEM_ARCHITECTURE.md)**

ครอบคลุม:
1. **ภาพรวมระบบ**: ระบบประกาศ IT & ระบบคำขอเปลี่ยนแปลง FM-IT-029 (Zero-Login Architecture)
2. **โครงสร้างฐานข้อมูล**: ตาราง PostgreSQL 13 ตารางที่มีคำนำหน้า `change_request_`
3. **ระบบ Routing & Dual API Contract**: รองรับทั้ง Modern REST v1 และ Legacy Aliases
4. **Backend Architecture**: Controller, Form Request, Resources, และ FileService
5. **Frontend Architecture**: Blade Views, In-Place Editing, html2canvas, jsPDF
6. **ระบบพรีวิวไฟล์แนบ (Preview Engine)**: Native Streaming PDF, docx-preview, SheetJS, Images
7. **Best Practice & Stability**: Memory-Friendly CSV Streaming, Root-Relative URLs, Sequence Resynchronization
8. **Automated Tests**: ชุดทดสอบอัตโนมัติ 19 Tests, 282 Assertions ผ่าน 100%

---

## 🧪 การทดสอบระบบ (Automated Tests)
รันคำสั่งทดสอบระบบทั้งหมด:
```powershell
php artisan test
```

---

## 🛠️ รายการคำสั่ง Artisan พิเศษ
* **นำเข้าข้อมูลจาก SQLite เข้าสู่ PostgreSQL**:
  ```powershell
  php artisan app:import-django-data
  ```
* **ตรวจสอบความแตกต่างของข้อมูล (Diff)**:
  ```powershell
  php artisan app:import-django-data --diff
  ```
* **แสดงรายชื่อตาราง Change Request ในฐานข้อมูล**:
  ```powershell
  php artisan app:import-django-data --list-cr-tables
  ```
