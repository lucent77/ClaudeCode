# Phase 6: Deployment Guide (Hostinger PHP)

## 1. Hostinger File Manager 업로드 규칙

### 1.1 파일 구조
```
public_html/
├── index.php
├── route-view.php
├── live-tracking.php
├── import_json_customers.php
├── config/
│   ├── db.php          (DB 연결 설정)
│   └── config.php      (API Keys)
├── functions/
│   ├── google.php
│   ├── route.php
│   └── customer_import.php
├── api/
│   ├── customers.php
│   ├── get-route.php
│   ├── recalculate.php
│   └── add-customer.php
├── assets/
│   ├── css/style.css
│   └── js/app.js
├── data/
│   └── (JSON 파일들)
└── database/
    └── schema.sql
```

### 1.2 업로드 방법

#### 방법 1: Hostinger File Manager
1. Hostinger hPanel에 로그인
2. **파일** → **File Manager** 클릭
3. `public_html` 폴더로 이동
4. **업로드** 버튼 클릭
5. 전체 프로젝트 폴더를 드래그 앤 드롭
6. 기존 파일 덮어쓰기 확인

#### 방법 2: FTP 클라이언트 (FileZilla)
```
Host: ftp.yourdomain.com
Username: u123456789
Password: your_password
Port: 21
```

#### 방법 3: SSH/SFTP (Business Plan)
```bash
sftp u123456789@yourdomain.com
cd public_html
put -r delivery-route-optimizer/*
```

### 1.3 파일 권한 설정
```
디렉토리: 755 (drwxr-xr-x)
PHP 파일: 644 (-rw-r--r--)
config 파일: 600 (-rw-------)  # 보안 강화
```

---

## 2. 환경 설정 (config.php)

### 2.1 Google API Key 설정

**config/config.php** 파일 수정:
```php
// Google Maps API Configuration
define('GOOGLE_API_KEY', 'AIzaSy...실제_API_키');  // 실제 키로 교체
```

### 2.2 Google Cloud Console에서 API Key 생성

1. [Google Cloud Console](https://console.cloud.google.com) 접속
2. 프로젝트 선택 또는 새 프로젝트 생성
3. **API 및 서비스** → **라이브러리** 이동
4. 다음 API 활성화:
   - Maps JavaScript API
   - Geocoding API
   - Directions API
5. **API 및 서비스** → **사용자 인증 정보**
6. **+ 사용자 인증 정보 만들기** → **API 키**
7. 키 제한 설정:
   - HTTP 리퍼러 제한: `https://yourdomain.com/*`
   - API 제한: Maps JavaScript API, Geocoding API, Directions API

### 2.3 Database 설정

**config/db.php** 파일 수정:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_delivery');    // 실제 DB 이름
define('DB_USER', 'u123456789_user');        // 실제 사용자명
define('DB_PASS', 'your_secure_password');   // 실제 비밀번호
```

### 2.4 .htaccess 보안 설정

`public_html/.htaccess` 생성:
```apache
# Protect config files
<FilesMatch "^(db|config)\.php$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Protect data directory
<Directory "data">
    Order Allow,Deny
    Deny from all
</Directory>

# Error handling
ErrorDocument 404 /index.php
ErrorDocument 500 /index.php

# PHP settings
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300

# Enable CORS for API
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
</IfModule>
```

---

## 3. 데이터베이스 설정

### 3.1 MySQL 데이터베이스 생성

1. Hostinger hPanel → **데이터베이스** → **MySQL 데이터베이스**
2. 새 데이터베이스 생성:
   - 데이터베이스 이름: `delivery` (자동으로 접두사 추가됨)
   - 사용자 생성 및 권한 부여

### 3.2 테이블 생성

**방법 1: phpMyAdmin 사용**
1. hPanel → **데이터베이스** → **phpMyAdmin**
2. 생성한 데이터베이스 선택
3. **SQL** 탭 클릭
4. `database/schema.sql` 내용 붙여넣기
5. **실행** 클릭

**방법 2: 명령줄 (SSH)**
```bash
mysql -u username -p database_name < database/schema.sql
```

### 3.3 연결 테스트

브라우저에서 접속하여 테스트:
```
https://yourdomain.com/api/customers.php
```

정상 응답:
```json
{
  "success": true,
  "data": {
    "customers": [],
    "total": 0
  }
}
```

---

## 4. JSON 파일 업로드 및 Import

### 4.1 JSON 파일 업로드

1. File Manager에서 `public_html/data/` 폴더 생성
2. JSON 파일 업로드 (예: `customers.json`)

### 4.2 Import 스크립트 실행

**방법 1: 웹 브라우저**
```
https://yourdomain.com/import_json_customers.php
```
- JSON 파일 선택 또는 경로 입력
- Geocoding 옵션 선택
- Import 버튼 클릭

**방법 2: CLI (SSH 접속 후)**
```bash
cd /home/u123456789/public_html
php import_json_customers.php data/customers.json
```

### 4.3 Import 결과 확인
```
=== Import Results ===
Total records: 150
Imported: 148
Updated: 0
Skipped: 2
Geocoded: 145
```

---

## 5. PHP 에러 로그 확인

### 5.1 에러 로그 위치
```
/home/u123456789/logs/error.log
```

### 5.2 hPanel에서 확인
1. hPanel → **고급** → **오류 페이지**
2. 또는 **고급** → **Cron 작업** → **로그 보기**

### 5.3 실시간 로그 확인 (SSH)
```bash
tail -f ~/logs/error.log
```

### 5.4 디버그 모드 활성화 (개발 시에만)

**config/config.php**:
```php
define('APP_DEBUG', true);  // 운영 환경에서는 false로!
```

---

## 6. DB 백업 전략

### 6.1 자동 백업 (Hostinger 제공)
- Hostinger는 매일 자동 백업 제공
- hPanel → **파일** → **백업** 에서 복원 가능

### 6.2 수동 백업

**phpMyAdmin 사용:**
1. phpMyAdmin 접속
2. 데이터베이스 선택
3. **내보내기** 탭
4. 형식: SQL
5. **실행** 클릭하여 다운로드

**SSH/CLI:**
```bash
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

### 6.3 자동 백업 스크립트

`backup.sh` 생성:
```bash
#!/bin/bash
BACKUP_DIR="/home/u123456789/backups"
DATE=$(date +%Y%m%d_%H%M)
DB_NAME="u123456789_delivery"
DB_USER="u123456789_user"
DB_PASS="your_password"

mkdir -p $BACKUP_DIR
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/backup_$DATE.sql
gzip $BACKUP_DIR/backup_$DATE.sql

# 7일 이상 된 백업 삭제
find $BACKUP_DIR -name "*.gz" -mtime +7 -delete
```

Cron 작업 등록 (매일 새벽 3시):
```
0 3 * * * /home/u123456789/backup.sh
```

---

## 7. 성능 최적화

### 7.1 PHP OPcache 설정
```php
// .user.ini 파일 생성
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

### 7.2 API 응답 캐싱
Geocoding 결과는 `geocoding_cache` 테이블에 자동 저장됨.
동일 주소 재요청 시 캐시에서 반환.

### 7.3 CDN 사용 (선택)
- Hostinger Cloudflare 통합 활용
- 정적 파일 (CSS, JS) 캐싱

---

## 8. 보안 체크리스트

- [ ] config.php에서 APP_DEBUG = false 설정
- [ ] Google API Key 도메인 제한 설정
- [ ] 데이터베이스 비밀번호 강력하게 설정
- [ ] .htaccess로 config 파일 보호
- [ ] HTTPS 인증서 설치 (Hostinger 무료 SSL)
- [ ] SQL Injection 방지 (Prepared Statements 사용됨)
- [ ] XSS 방지 (htmlspecialchars 사용됨)
- [ ] 정기적인 백업 설정

---

## 9. 향후 확장 아이디어

### 9.1 드라이버 앱 연결
- React Native / Flutter 모바일 앱 개발
- WebSocket으로 실시간 위치 공유
- Push 알림 (Firebase Cloud Messaging)

### 9.2 실시간 GPS 업데이트
```javascript
// WebSocket 연결
const ws = new WebSocket('wss://yourdomain.com/ws');

ws.onmessage = (event) => {
    const data = JSON.parse(event.data);
    updateDriverLocation(data.lat, data.lng);
};
```

### 9.3 Multi-driver Optimization
- 여러 드라이버 동시 관리
- Vehicle Routing Problem (VRP) 알고리즘
- Google OR-Tools 활용

### 9.4 ETA 푸시 알림 시스템
- 고객에게 배송 예정 시간 알림
- SMS/Email 자동 발송
- 지연 알림 자동화

### 9.5 분석 대시보드
- 배송 효율성 리포트
- 드라이버 성과 분석
- 비용 최적화 인사이트

---

## 10. 문제 해결

### 일반적인 오류

**"Database connection failed"**
- DB 이름, 사용자명, 비밀번호 확인
- DB 서버가 localhost인지 확인

**"Geocoding failed"**
- Google API Key 유효성 확인
- API 결제 계정 활성화 확인
- 일일 할당량 초과 확인

**"CORS error"**
- .htaccess CORS 헤더 확인
- API 응답에 헤더 포함 확인

**"Map not loading"**
- Google Maps API Key 확인
- 브라우저 콘솔에서 오류 확인
- API 제한 설정 확인

---

**전체 6단계 빌드 완료 — JSON 기반 경로 최적화 시스템 개발 문서 및 코드 생성 완료.**
