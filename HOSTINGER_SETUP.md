# Hostinger 전용 설치 가이드

이 프로젝트는 **Hostinger PHP 웹호스팅**에 최적화되어 있습니다.
`index.php`가 루트 디렉토리에 위치하여 Hostinger에서 바로 사용 가능합니다.

## 📁 프로젝트 구조 (Hostinger 최적화)

```
public_html/                    ← Hostinger 웹 루트
├── index.php                   ← Laravel 진입점 (ROOT에 위치)
├── .htaccess                   ← Apache 설정
├── .env                        ← 환경 설정 (복사 후 수정 필요)
├── js/                         ← JavaScript 파일
│   └── app.js
├── css/                        ← CSS 파일
├── uploads/                    ← 업로드 파일 저장소
├── app/                        ← Laravel 애플리케이션 코드
├── bootstrap/                  ← Laravel 부트스트랩
├── config/                     ← 설정 파일
├── database/                   ← 마이그레이션 & 시더
├── resources/                  ← 뷰, 에셋
│   └── views/
│       └── dashboard.blade.php
├── routes/                     ← 라우트 정의
├── storage/                    ← 로그, 캐시
├── vendor/                     ← Composer 패키지
├── artisan                     ← Laravel CLI
└── composer.json               ← PHP 의존성
```

## 🚀 빠른 설치 (5분)

### 1단계: 파일 업로드

#### 방법 A: Git 사용 (권장)
```bash
# Hostinger SSH 접속
ssh u123456789@yourdomain.com

# public_html로 이동
cd public_html

# 저장소 클론
git clone <repository-url> .
```

#### 방법 B: FTP/SFTP 사용
1. FileZilla 또는 Hostinger File Manager 사용
2. 모든 파일을 `public_html` 폴더에 업로드
3. 파일 구조가 위와 같은지 확인

### 2단계: Composer 설치

```bash
# Hostinger SSH에서 실행
cd ~/public_html

# Composer 의존성 설치
composer install --no-dev --optimize-autoloader
```

> **Composer가 없는 경우:**
> ```bash
> # Composer 설치
> curl -sS https://getcomposer.org/installer | php
> php composer.phar install --no-dev --optimize-autoloader
> ```

### 3단계: 환경 설정

```bash
# .env 파일 생성
cp .env.example .env

# .env 파일 수정
nano .env  # 또는 Hostinger File Manager 사용
```

**필수 설정 항목:**
```env
APP_NAME="Creodent AoX Dashboard"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# 데이터베이스 (Hostinger MySQL)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_aox
DB_USERNAME=u123456789_admin
DB_PASSWORD=your_db_password

# Slack API
SLACK_BOT_TOKEN=xoxb-your-slack-token
SLACK_WORKSPACE_ID=T01234567
SLACK_CANVAS_ID=F01234567

# 파일 저장 전략
FILE_STORAGE_STRATEGY=hybrid
```

### 4단계: 애플리케이션 키 생성

```bash
php artisan key:generate
```

### 5단계: 파일 권한 설정

```bash
# 스토리지 및 캐시 폴더 쓰기 권한
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod -R 777 storage/logs
chmod -R 777 uploads

# .env 파일 보안
chmod 600 .env
```

### 6단계: 데이터베이스 마이그레이션

```bash
# 테이블 생성
php artisan migrate

# 성공 메시지 확인
# Migration table created successfully.
```

### 7단계: 관리자 계정 생성

```bash
php artisan tinker
```

Tinker 콘솔에서:
```php
$user = new App\Models\User();
$user->name = 'Admin';
$user->email = 'admin@yourdomain.com';
$user->password = bcrypt('your-secure-password');
$user->role = 'admin';
$user->is_active = true;
$user->save();
exit
```

### 8단계: Cron Job 설정 (자동 동기화)

1. Hostinger 패널 → **Advanced** → **Cron Jobs**
2. 새 Cron Job 추가:

**명령어:**
```bash
/usr/bin/php /home/u123456789/public_html/cron/sync.php >> /home/u123456789/public_html/storage/logs/cron.log 2>&1
```

**스케줄:** 30분마다 실행
```
*/30 * * * *
```

**또는:**
- 매 시간: `0 * * * *`
- 매 15분: `*/15 * * * *`

### 9단계: 첫 로그인 테스트

1. 브라우저에서 `https://yourdomain.com` 접속
2. 7단계에서 만든 계정으로 로그인
3. "Sync" 버튼 클릭하여 Slack 데이터 동기화
4. 케이스가 표시되는지 확인

## ✅ 완료!

대시보드가 정상적으로 작동하면 설치 완료입니다.

---

## 🔧 Hostinger 특화 설정

### PHP 버전 확인

Hostinger 패널에서 **PHP 8.0 이상** 설정:
1. **Advanced** → **PHP Configuration**
2. PHP 버전: **8.0** 이상 선택
3. 저장

### PHP 설정 최적화

`.htaccess`에 이미 포함되어 있습니다:
```apache
php_value upload_max_filesize 50M
php_value post_max_size 50M
php_value max_execution_time 300
php_value max_input_time 300
```

필요시 추가 설정:
```apache
php_value memory_limit 256M
```

### MySQL 데이터베이스 생성

1. Hostinger 패널 → **Databases** → **MySQL Databases**
2. **Create New Database**:
   - 데이터베이스 이름: `u123456789_aox`
   - 사용자 생성 및 권한 부여
3. 자격 증명을 `.env`에 입력

### SSL/HTTPS 활성화

1. Hostinger 패널 → **Security** → **SSL/TLS**
2. **Enable SSL** 클릭
3. `.htaccess`에서 HTTPS 리다이렉트 주석 해제:
```apache
# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 🐛 문제 해결

### 1. "500 Internal Server Error"

**원인:** 파일 권한 또는 `.env` 설정 문제

**해결:**
```bash
# 권한 재설정
chmod -R 755 storage bootstrap/cache
chmod 600 .env

# 로그 확인
tail -f storage/logs/laravel.log
```

### 2. "Database connection failed"

**원인:** 데이터베이스 자격 증명 오류

**해결:**
```bash
# .env 파일 확인
cat .env | grep DB_

# MySQL 연결 테스트
mysql -u u123456789_admin -p u123456789_aox
```

### 3. "index.php not found"

**원인:** 파일이 올바른 위치에 없음

**해결:**
```bash
# index.php가 public_html 루트에 있는지 확인
ls -la ~/public_html/index.php

# 있어야 함: /home/u123456789/public_html/index.php
```

### 4. "Composer dependencies not installed"

**원인:** vendor 폴더 누락

**해결:**
```bash
cd ~/public_html
composer install --no-dev
```

### 5. CSS/JS 파일이 로드되지 않음

**원인:** 경로 문제

**해결:**
```bash
# js, css 폴더가 루트에 있는지 확인
ls -la ~/public_html/js
ls -la ~/public_html/css

# .htaccess에서 정적 파일 규칙 확인
```

### 6. Slack 동기화 실패

**원인:** API 토큰 또는 권한 문제

**해결:**
1. `.env`에서 `SLACK_BOT_TOKEN` 확인
2. Slack 앱 권한 확인 (files:read, channels:read)
3. 대시보드에서 "Test Connection" 실행

### 7. 파일 업로드 실패

**원인:** 업로드 폴더 권한 문제

**해결:**
```bash
chmod -R 777 ~/public_html/uploads
ls -la ~/public_html/uploads  # drwxrwxrwx 확인
```

---

## 📊 성능 최적화

### OPcache 활성화

Hostinger 패널 → **PHP Configuration** → **OPcache: Enable**

또는 `.user.ini` 파일 생성:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
```

### 캐시 최적화

```bash
# 프로덕션 환경에서 캐시 생성
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**업데이트 후 캐시 클리어:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## 🔐 보안 체크리스트

- [x] `.env` 파일 권한 600
- [x] `APP_DEBUG=false` 설정
- [x] HTTPS 활성화
- [x] 강력한 `APP_KEY` 생성
- [x] 강력한 관리자 비밀번호
- [x] 정기적인 백업 설정
- [x] `storage` 폴더 웹 접근 차단 (자동)

---

## 💾 백업

### 데이터베이스 백업 스크립트

`backup-db.sh` 생성:
```bash
#!/bin/bash
BACKUP_DIR="/home/u123456789/backups"
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u u123456789_admin -p'password' u123456789_aox > $BACKUP_DIR/db_$DATE.sql
find $BACKUP_DIR -name "db_*.sql" -mtime +7 -delete
```

Cron Job 추가 (매일 새벽 2시):
```
0 2 * * * /home/u123456789/backup-db.sh
```

### 파일 백업

Hostinger 자동 백업 기능 사용 또는:
```bash
# 전체 프로젝트 압축
tar -czf ~/aox-backup-$(date +%Y%m%d).tar.gz ~/public_html
```

---

## 📞 지원

### 로그 확인
```bash
# Laravel 로그
tail -f ~/public_html/storage/logs/laravel.log

# Cron 로그
tail -f ~/public_html/storage/logs/cron.log

# Apache 에러 로그
tail -f ~/logs/error_log
```

### Hostinger 지원
- 라이브 챗: https://www.hostinger.com
- 헬프 센터: https://support.hostinger.com

### 프로젝트 문서
- README.md - 전체 프로젝트 문서
- DEPLOYMENT.md - 상세 배포 가이드
- QUICKSTART.md - 빠른 시작 가이드

---

## 🎉 완료!

Hostinger에서 Creodent AoX Elevate Dashboard가 성공적으로 설치되었습니다!

**다음 단계:**
1. 첫 케이스 동기화 테스트
2. 팀원 계정 생성
3. 정기 백업 설정 확인
4. 프로덕션 모니터링 시작

**접속 URL:** https://yourdomain.com
**관리자 이메일:** admin@yourdomain.com
