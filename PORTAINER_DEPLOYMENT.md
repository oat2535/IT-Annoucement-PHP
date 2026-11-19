# 🚀 Portainer Deployment Guide (Git Repository Stack)

คู่มือการติดตั้งและ Deploy ระบบ **IT Announcement (Laravel 13 + PostgreSQL)** ผ่าน **Portainer Stack** โดยเชื่อมต่อกับ **GitHub Repository** โดยตรงตามหลัก Best Practice, Performance และ Stability

---

## 📌 ข้อมูลสำคัญสำหรับการ Deploy

| รายการ | ค่าที่กำหนด |
| :--- | :--- |
| **Git Repository URL** | `https://github.com/oat2535/IT-Annoucement-PHP.git` |
| **Branch** | `main` (หรือ `refs/heads/main`) |
| **Compose path** | `docker-compose.yml` |
| **Service Port** | `5897` |
| **Healthcheck Endpoint** | `http://127.0.0.1:5897/up` |
| **Database** | PostgreSQL (Schema: `intra_tl`) |

---

## 🏗️ สถาปัตยกรรมการทำงานใน Docker Container

```
                    ┌──────────────────────────────────────────────┐
                    │               Portainer Stack                │
                    │   (Cloned from GitHub oat2535/IT-...-PHP)    │
                    └──────────────────────┬───────────────────────┘
                                           │ Builds & Runs
                                           ▼
┌──────────────────────────────────────────────────────────────────────────────────┐
│ Docker Container: it_announcement_laravel_web                                    │
│ Port: 5897                                                                       │
│                                                                                  │
│  ┌───────────────────────┐   ┌────────────────────────┐   ┌───────────────────┐  │
│  │ docker-entrypoint.sh  │──▶│ PHP 8.3 CLI + OPcache  │──▶│ Laravel 13 App    │  │
│  │ - Symlink storage     │   │ - Upload limit: 50MB   │   │ - Health: /up     │  │
│  │ - Permissions (775)   │   │ - Memory: 256MB        │   │ - RESTful API     │  │
│  │ - Config/Route Cache  │   └────────────────────────┘   └───────────────────┘  │
│  └───────────────────────┘                                                       │
└───────────────────────┬──────────────────────────────────┬───────────────────────┘
                        │                                  │
         ┌──────────────┴──────────────┐    ┌──────────────┴──────────────┐
         ▼                             ▼    ▼                             ▼
  [Named Volume]                 [Named Volume]                 [External Database]
  it_announcement_storage_public it_announcement_logs           PostgreSQL Server
  (/app/storage/app/public)      (/app/storage/logs)            (Host: 192.168.98.254:5434)
```

---

## 🛠️ ขั้นตอนการสร้าง Stack ใน Portainer

### ขั้นตอนที่ 1: เข้าสู่ Portainer Web UI
1. เปิดบราวเซอร์เข้าสู่ Portainer ขององค์กร
2. เลือก Environment (เช่น `local` หรือ Docker Host ที่ต้องการ Deploy)
3. เมนูด้านซ้ายคลิกที่ **Stacks** แล้วกดปุ่ม **+ Add stack**

---

### ขั้นตอนที่ 2: ตั้งค่า Repository
1. **Name**: กรอกชื่อ Stack เช่น `it-announcement`
2. **Build method**: เลือกแท็บ **Repository**
3. **Repository URL**:
   ```text
   https://github.com/oat2535/IT-Annoucement-PHP.git
   ```
4. **Repository reference**:
   ```text
   refs/heads/main
   ```
5. **Compose path**:
   ```text
   docker-compose.yml
   ```

---

### ขั้นตอนที่ 3: เปิดระบบ Automatic Redeployment (CI/CD)
เพื่อให้ Stack อัปเดตอัตโนมัติเมื่อมีการ `git push` ไปยัง GitHub:

- **วิธีที่ 1 (แนะนำ - Webhook)**:
  1. เปิดสวิตช์ **Automatic updates**
  2. เลือกกลไก **Webhook**
  3. Portainer จะสร้าง Webhook URL ให้ (เช่น `https://portainer.domain.com/api/stacks/webhooks/...`)
  4. นำ URL นี้ไปใส่ใน GitHub Repository: **Settings** > **Webhooks** > **Add webhook** (Content type: `application/json`)
- **วิธีที่ 2 (Polling)**:
  1. เปิดสวิตช์ **Automatic updates**
  2. เลือกกลไก **Poll**
  3. กำหนดรอบตรวจสอบ เช่น `5m` (ทุก 5 นาที)

---

### ขั้นตอนที่ 4: ตั้งค่า Environment Variables (ถ้าต้องการ Custom)
ในส่วน **Environment variables** สามารถเพิ่ม Key-Value เพื่อ Override ค่าเริ่มต้นใน `docker-compose.yml` ได้ทันทีโดยไม่ต้องแก้โค้ด:

| Name | Example Value | Description |
| :--- | :--- | :--- |
| `PORT` | `5897` | Port ของ Web Application |
| `APP_KEY` | `base64:7K5gB0qJ72n0O22R1j0PjS6cR7mi04s3D76u4234xY0=` | Key ความปลอดภัยของ Laravel |
| `APP_URL` | `https://annouce.thonglorpet.com` | Full Domain URL |
| `DB_HOST` | `192.168.98.254` | IP Database PostgreSQL |
| `DB_PORT` | `5434` | Port Database |
| `DB_DATABASE` | `imedx_tlp_for_it` | ชื่อ Database |
| `DB_USERNAME` | `eservices` | ชื่อผู้ใช้ Database |
| `DB_PASSWORD` | `Tlp@2023` | รหัสผ่าน Database |
| `DB_SCHEMA` | `intra_tl` | Search Path Schema |
| `DB_AUTO_MIGRATE` | `false` | ตั้งเป็น `true` หากต้องการรัน migrate อัตโนมัติ |

---

### ขั้นตอนที่ 5: สั่ง Deploy Stack
1. เลื่อนลงมาด้านล่างสุด แล้วกดปุ่ม **Deploy the stack**
2. Portainer จะทำการ Clone โค้ดล่าสุดจาก GitHub, Build Docker Image ตาม `Dockerfile` และสตาร์ท Container อัตโนมัติ
3. เมื่อ Deploy สำเร็จ สถานะของ Container จะแสดงเป็น **healthy** 🟢

---

## 🔍 การตรวจสอบความเรียบร้อย (Verification & Diagnostics)

1. **ตรวจสอบสถานะ Container**:
   - Container Name: `it_announcement_laravel_web`
   - State: `running (healthy)`
2. **ทดสอบ Health Endpoint**:
   ```bash
   curl -I http://<SERVER_IP>:5897/up
   # ต้องได้สถานะ: HTTP/1.1 200 OK
   ```
3. **ระบบวิเคราะห์สถานะเชิงลึก (Self-Diagnostics Endpoint)**:
   - เปิดบราวเซอร์ไปที่: `http://<SERVER_IP>:5897/diagnose`
   - ระบบจะส่งกลับ JSON รายงานผลการเชื่อมต่อฐานข้อมูล PostgreSQL, Permission ของไดเรกทอรี Storage/Session/Cache และจำนวนข้อมูลในตารางทันที หากมีข้อผิดพลาดจะระบุสาเหตุอย่างละเอียด
4. **เปิดใช้งานหน้าเว็บ**:
   - หน้าหลัก Portal: `http://<SERVER_IP>:5897/`
   - ฟอร์ม Change Request: `http://<SERVER_IP>:5897/change-request`
   - หน้าประกาศ Announcement: `http://<SERVER_IP>:5897/announcement`

---

## 💡 Best Practices & จุดเด่นของระบบในการ Deploy

1. **Zero Secret Leakage**:
   - ไม่มีไฟล์ `.env` ที่มีข้อมูลจริงหลุดเข้าไปใน Git
   - ค่า Credentials ต่างๆ ควบคุมผ่าน Environment Variables ใน Portainer UI
2. **Named Volume Data Persistence**:
   - ไฟล์แนบและเอกสารที่ผู้ใช้อัปโหลดจะเก็บลงใน Docker Named Volume `it_announcement_storage_public` อย่างปลอดภัย แม้มีการ Redeploy หรือ Rebuild Image ไฟล์จะไม่หาย
   - ไฟล์ Template Word (`storage/app/templates/*.docx`) ถูกฝังไว้ใน Image ตั้งแต่ตอน Build ทำให้พร้อมใช้งานเสมอ
3. **High Performance**:
   - เปิดใช้งาน **OPcache** และเพิ่มขีดจำกัดหน่วยความจำ (`memory_limit=256M`, `upload_max_filesize=50M`)
   - รันคำสั่ง `config:cache`, `route:cache`, `view:cache` ผ่าน Entrypoint อัตโนมัติในโหมด Production
4. **Log Rotation**:
   - กำหนด Logging Driver เป็น `json-file` จำกัดขนาดไฟล์ละ `20MB` สูงสุด `5` ไฟล์ ป้องกันปัญหา Disk เต็มบน Server
