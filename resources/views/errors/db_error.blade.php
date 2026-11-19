<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Error - IT Announcement</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            padding: 32px;
            text-align: center;
        }
        .icon {
            font-size: 56px;
            margin-bottom: 16px;
        }
        h1 {
            font-size: 22px;
            color: #e11d48;
            margin-bottom: 12px;
            font-weight: 700;
        }
        p {
            color: #64748b;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .error-box {
            background-color: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 8px;
            padding: 16px;
            text-align: left;
            font-family: monospace;
            font-size: 13px;
            color: #9f1239;
            word-break: break-all;
            margin-bottom: 24px;
            max-height: 180px;
            overflow-y: auto;
        }
        .btn {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: background 0.2s;
        }
        .btn:hover {
            background-color: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">⚠️</div>
        <h1>ไม่สามารถเชื่อมต่อฐานข้อมูลได้</h1>
        <p>ระบบไม่สามารถติดต่อ PostgreSQL Database ได้ กรุณาตรวจสอบการตั้งค่า Network, IP Host, Port หรือสิทธิ์ในฐานข้อมูล</p>
        
        <div class="error-box">
            <strong>Error Message:</strong><br>
            {{ $errorMessage ?? 'Unknown Database Error' }}
        </div>

        <a href="/diagnose" class="btn">เปิดดูรายงานวิเคราะห์ระบบ (/diagnose)</a>
    </div>
</body>
</html>
