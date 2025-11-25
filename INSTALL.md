# Magic Rx Scanner - 설치 가이드

## 빠른 시작 (Hostinger)

### 1단계: 파일 업로드

1. Hostinger 파일 매니저 또는 FTP로 모든 파일을 `public_html` 디렉토리에 업로드

### 2단계: 데이터베이스 설정

1. **Hostinger cPanel** → **MySQL Databases** 접속
2. **Create a New Database** 클릭
   - Database Name: `magic_rx_scanner`
3. **Create a New User** 클릭
   - Username: 원하는 사용자명
   - Password: 강력한 비밀번호 생성
4. **Add User to Database**에서 새로 만든 사용자에게 **ALL PRIVILEGES** 부여
5. **phpMyAdmin** 접속
6. 생성한 데이터베이스 선택 → **Import** 탭
7. `database/schema.sql` 파일 업로드 및 실행

### 3단계: Google Cloud 설정

#### 3.1 Google Cloud 프로젝트 생성

1. https://console.cloud.google.com 접속
2. **프로젝트 만들기** 클릭
3. 프로젝트 이름 입력 (예: magic-rx-scanner)

#### 3.2 API 활성화

1. **API 및 서비스** → **라이브러리**
2. 다음 API 검색 및 활성화:
   - **Cloud Document AI API**
   - **Cloud Vision API**

#### 3.3 Document AI Processor 생성

1. **Document AI** 메뉴 접속
2. **Processor 만들기** 클릭
3. **Form Parser** 선택
4. Processor 이름 입력
5. **Region**: US 선택 (또는 원하는 지역)
6. 생성 후 **Processor ID** 복사 (나중에 사용)

#### 3.4 Service Account 생성

1. **IAM 및 관리자** → **서비스 계정**
2. **서비스 계정 만들기** 클릭
3. 서비스 계정 세부정보 입력:
   - 이름: rx-scanner-sa
   - 설명: Magic Rx Scanner Service Account
4. **역할 선택**에서 다음 역할 추가:
   - **Cloud Document AI API 사용자**
   - **Cloud Vision API 사용자**
5. **완료** 클릭
6. 생성된 서비스 계정 클릭 → **키** 탭
7. **키 추가** → **새 키 만들기** → **JSON** 선택
8. JSON 키 파일 다운로드 (안전하게 보관!)

#### 3.5 키 파일 업로드

1. 다운로드한 JSON 파일을 `google-service-account.json`으로 이름 변경
2. Hostinger 파일 매니저로 `credentials/` 디렉토리에 업로드
3. 파일 권한을 **600**으로 설정 (보안을 위해)

### 4단계: 설정 파일 수정

1. Hostinger 파일 매니저에서 `includes/config.php` 편집
2. 다음 값 수정:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_magic_rx');  // cPanel에서 생성한 DB 이름
define('DB_USER', 'u123456789_rxuser');    // cPanel에서 생성한 사용자명
define('DB_PASS', 'your_secure_password'); // 설정한 비밀번호

// Google Cloud Configuration
define('GOOGLE_PROJECT_ID', 'magic-rx-scanner-123456');  // Google Cloud 프로젝트 ID
define('GOOGLE_LOCATION', 'us');  // Processor를 생성한 지역
define('GOOGLE_PROCESSOR_ID', '1234567890abcdef');  // Document AI Processor ID
```

3. 저장

### 5단계: PHP 설정 확인

1. **Hostinger cPanel** → **Select PHP Version**
2. PHP 버전을 **8.0 이상** 선택
3. **Extensions** 탭에서 다음 확장 활성화:
   - ✓ gd
   - ✓ pdo
   - ✓ pdo_mysql
   - ✓ curl
   - ✓ openssl
   - ✓ json
   - ✓ fileinfo

### 6단계: 디렉토리 권한 설정

Hostinger 파일 매니저에서 다음 디렉토리 권한을 **755**로 설정:

- `uploads/`
- `uploads/documents/`
- `uploads/templates/`
- `uploads/processed/`
- `logs/`

### 7단계: HTTPS 활성화 (권장)

1. **Hostinger cPanel** → **SSL/TLS**
2. **무료 SSL 인증서** (Let's Encrypt) 활성화
3. `/.htaccess` 파일 편집
4. HTTPS 리다이렉트 주석 해제:

```apache
# Force HTTPS (uncomment in production)
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
```

### 8단계: 설치 확인

1. 터미널 또는 SSH로 접속 (Hostinger는 일부 플랜에서 SSH 제공)
2. 다음 명령 실행:

```bash
php utils/install.php
```

**또는** 웹 브라우저로 직접 테스트:

3. `http://your-domain.com/` 접속
4. 처방전 이미지 업로드 테스트

---

## 수동 설치 (일반 서버)

### 1. 시스템 요구사항 확인

```bash
php -v  # PHP 8.0 이상
mysql --version  # MySQL 8.0 이상
```

### 2. PHP 확장 확인

```bash
php -m | grep -E 'pdo|gd|curl|openssl|json'
```

### 3. 파일 클론

```bash
git clone <repository-url>
cd magic-rx-scanner
```

### 4. 디렉토리 권한 설정

```bash
chmod 755 uploads/ uploads/documents/ uploads/templates/ uploads/processed/ logs/
chmod 600 credentials/google-service-account.json
```

### 5. 데이터베이스 생성

```bash
mysql -u root -p
```

```sql
CREATE DATABASE magic_rx_scanner CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

```bash
mysql -u root -p magic_rx_scanner < database/schema.sql
```

### 6. 설정 파일 수정

```bash
nano includes/config.php
```

### 7. 설치 확인

```bash
php utils/install.php
```

---

## 문제 해결

### 문제: "Database connection failed"

**해결:**
1. `includes/config.php`에서 DB 정보 확인
2. MySQL 서비스 실행 확인
3. Hostinger에서는 DB 호스트가 `localhost`인지 확인

### 문제: "Service account credentials file not found"

**해결:**
1. `credentials/google-service-account.json` 파일 존재 확인
2. 파일 권한 확인 (600)
3. `config.php`에서 경로 확인

### 문제: 파일 업로드 실패

**해결:**
1. `uploads/` 디렉토리 권한 755 확인
2. PHP 설정 확인:
   ```
   upload_max_filesize = 10M
   post_max_size = 10M
   ```
3. Hostinger cPanel → **Select PHP Options**에서 설정

### 문제: GD extension 없음

**해결:**
- Hostinger: cPanel → **Select PHP Version** → **Extensions** → **gd** 체크
- 수동 설치: `sudo apt-get install php-gd` (Ubuntu/Debian)

---

## 테스트

### 1. 기본 기능 테스트

1. 웹 브라우저에서 애플리케이션 접속
2. 샘플 이미지 업로드
3. AI 분석 실행
4. 결과 확인

### 2. API 테스트

```bash
# 파일 업로드
curl -X POST http://your-domain.com/api/upload.php \
  -F "document=@test-prescription.jpg"

# 결과 확인
curl http://your-domain.com/api/results.php?prescription_id=1
```

---

## 프로덕션 체크리스트

- [ ] HTTPS 활성화
- [ ] `config.php`에서 에러 표시 비활성화
- [ ] 강력한 데이터베이스 비밀번호 사용
- [ ] Google Service Account JSON 파일 권한 600
- [ ] 정기적인 데이터베이스 백업 설정
- [ ] 파일 정리 Cron job 설정
- [ ] 로그 파일 모니터링 설정
- [ ] `.htaccess` 보안 설정 확인

---

## 추가 지원

- GitHub Issues: 문제 보고
- Documentation: README.md 참조
- Logs: `logs/app.log` 확인

**설치 완료!** 🎉
