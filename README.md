# Magic Rx Scanner - AI 처방전 스캐너

Google Cloud Document AI와 Vision API를 활용한 치과 처방전 자동 인식 시스템

## 📋 프로젝트 개요

Magic Rx Scanner는 치과 처방전 이미지를 업로드하여 AI가 자동으로 텍스트를 추출하고 분석하는 웹 애플리케이션입니다. 템플릿 기반 필기 추출 및 Document AI를 통한 폼 필드 인식을 지원합니다.

### 주요 기능

✅ **이미지 업로드**: 드래그 앤 드롭 또는 파일 선택으로 처방전 업로드
✅ **템플릿 기반 필기 추출**: 빈 템플릿과 작성본을 비교하여 필기만 추출
✅ **Document AI 분석**: Google Document AI를 통한 자동 폼 필드 추출
✅ **Vision API OCR**: 추출된 필기 이미지의 텍스트 인식
✅ **데이터 저장**: MySQL/MariaDB에 모든 결과 영구 저장
✅ **결과 내보내기**: CSV 및 JSON 형식으로 데이터 다운로드
✅ **모던한 UI**: Tailwind CSS 기반의 반응형 디자인

---

## 🛠 기술 스택

- **Backend**: PHP 8.0+
- **Database**: MySQL 8.0+ / MariaDB 10.5+
- **OCR Engine**: Google Cloud Document AI & Vision API
- **Image Processing**: PHP GD Extension
- **Frontend**: HTML5, Tailwind CSS, Vanilla JavaScript
- **Web Server**: Apache (Hostinger 호환)

---

## 📁 프로젝트 구조

```
magic-rx-scanner/
├── api/                        # API 엔드포인트
│   ├── upload.php             # 파일 업로드
│   ├── analyze.php            # OCR 분석
│   ├── results.php            # 결과 조회
│   └── download.php           # 결과 다운로드
├── includes/                   # 핵심 PHP 클래스
│   ├── config.php             # 설정 파일
│   ├── database.php           # DB 연결 클래스
│   ├── file_handler.php       # 파일 처리
│   ├── image_processor.php    # 이미지 처리
│   └── ocr_handler.php        # Google API 호출
├── assets/                     # 정적 리소스
│   ├── css/
│   └── js/
│       └── main.js            # 프론트엔드 로직
├── uploads/                    # 업로드된 파일
│   ├── documents/
│   ├── templates/
│   └── processed/
├── database/                   # 데이터베이스 스키마
│   └── schema.sql
├── credentials/                # Google 서비스 계정 키
├── logs/                       # 애플리케이션 로그
├── index.php                   # 메인 페이지
├── .htaccess                   # Apache 설정
└── README.md
```

---

## 🚀 설치 및 설정

### 1. 시스템 요구사항

- **PHP**: 8.0 이상
- **MySQL/MariaDB**: 8.0 / 10.5 이상
- **PHP Extensions**:
  - PDO
  - GD 또는 Imagick
  - cURL
  - OpenSSL
  - JSON
- **Apache Modules**:
  - mod_rewrite
  - mod_headers

### 2. 데이터베이스 설정

```bash
# MySQL/MariaDB 로그인
mysql -u your_username -p

# 데이터베이스 생성
CREATE DATABASE magic_rx_scanner CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 스키마 임포트
mysql -u your_username -p magic_rx_scanner < database/schema.sql
```

### 3. Google Cloud 설정

#### 3.1 Google Cloud 프로젝트 생성
1. [Google Cloud Console](https://console.cloud.google.com/) 접속
2. 새 프로젝트 생성
3. 프로젝트 ID 확인

#### 3.2 API 활성화
- Cloud Document AI API
- Cloud Vision API

#### 3.3 Service Account 생성
1. **IAM & Admin > Service Accounts**로 이동
2. **Create Service Account** 클릭
3. 역할 부여:
   - Document AI API User
   - Cloud Vision API User
4. **Create Key** → JSON 선택
5. 다운로드한 JSON 파일을 `credentials/google-service-account.json`에 저장

#### 3.4 Document AI Processor 생성
1. **Document AI > Processors** 메뉴
2. **Create Processor** 클릭
3. Processor 타입 선택 (Form Parser 권장)
4. Processor ID 확인

### 4. 설정 파일 수정

`includes/config.php` 파일을 수정하여 환경에 맞게 설정:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'magic_rx_scanner');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');

// Google Cloud Configuration
define('GOOGLE_PROJECT_ID', 'your-project-id');
define('GOOGLE_LOCATION', 'us');  // 또는 'eu', 'asia-northeast1' 등
define('GOOGLE_PROCESSOR_ID', 'your-processor-id');
```

### 5. 파일 권한 설정

```bash
# 업로드 디렉토리 권한
chmod 755 uploads/
chmod 755 uploads/documents/
chmod 755 uploads/templates/
chmod 755 uploads/processed/

# 로그 디렉토리 권한
chmod 755 logs/

# Credentials 보안 (웹 접근 차단)
chmod 600 credentials/google-service-account.json
```

### 6. PHP 설정 확인

`php.ini` 또는 `.htaccess`에서 다음 값 확인:

```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
```

---

## 📖 사용 방법

### 웹 인터페이스

1. 브라우저에서 `http://your-domain.com/` 접속
2. **작성된 처방전** 이미지 업로드
3. (선택사항) **빈 템플릿** 업로드 → 필기 추출 모드 활성화
4. **AI 분석 시작** 버튼 클릭
5. 결과 확인 및 CSV/JSON으로 다운로드

### API 사용 (프로그래밍 방식)

#### 1. 파일 업로드

```bash
curl -X POST http://your-domain.com/api/upload.php \
  -F "document=@prescription.jpg" \
  -F "template=@template.jpg"
```

**응답:**
```json
{
  "success": true,
  "message": "Files uploaded successfully",
  "data": {
    "prescription_id": 123,
    "analysis_mode": "handwriting_extraction"
  }
}
```

#### 2. OCR 분석

```bash
curl -X POST http://your-domain.com/api/analyze.php \
  -H "Content-Type: application/json" \
  -d '{"prescription_id": 123}'
```

**응답:**
```json
{
  "success": true,
  "data": {
    "prescription_id": 123,
    "mode": "handwriting_extraction",
    "handwriting": {
      "text": "추출된 텍스트...",
      "image": "data:image/png;base64,...",
      "confidence": 85.5
    }
  }
}
```

#### 3. 결과 조회

```bash
curl http://your-domain.com/api/results.php?prescription_id=123
```

#### 4. 결과 다운로드

```bash
# JSON 형식
curl http://your-domain.com/api/download.php?prescription_id=123&format=json > result.json

# CSV 형식
curl http://your-domain.com/api/download.php?prescription_id=123&format=csv > result.csv
```

---

## 🔒 보안 고려사항

### 구현된 보안 기능

✅ **SQL Injection 방지**: PDO Prepared Statements 사용
✅ **XSS 방지**: HTML 이스케이핑 및 CSP 헤더
✅ **파일 업로드 검증**: MIME 타입 + 확장자 + 실제 이미지 검증
✅ **파일 크기 제한**: 10MB 제한
✅ **디렉토리 접근 차단**: .htaccess로 민감한 디렉토리 보호
✅ **HTTPS 강제**: .htaccess에서 HTTPS 리다이렉트 (활성화 필요)
✅ **보안 헤더**: X-Frame-Options, X-XSS-Protection 등

### 추가 권장사항

1. **프로덕션 환경에서 에러 표시 비활성화**
   ```php
   // config.php
   error_reporting(0);
   ini_set('display_errors', 0);
   ```

2. **HTTPS 활성화**
   - Hostinger에서 Let's Encrypt SSL 인증서 활성화
   - `.htaccess`에서 HTTPS 리다이렉트 주석 해제

3. **정기적인 파일 정리**
   - Cron job으로 오래된 업로드 파일 삭제
   ```bash
   # Crontab 예시 (매일 자정 30일 이상 된 파일 삭제)
   0 0 * * * php /path/to/cleanup.php
   ```

4. **데이터베이스 백업**
   - 정기적인 MySQL 백업 설정

---

## 🐛 문제 해결

### 1. 파일 업로드 실패

**원인**: PHP 업로드 크기 제한
**해결**:
```bash
# .htaccess 또는 php.ini
upload_max_filesize = 10M
post_max_size = 10M
```

### 2. GD Extension 없음

**원인**: PHP GD 확장 미설치
**해결** (Hostinger):
1. cPanel → Select PHP Version
2. **gd** 체크박스 활성화

### 3. Google API 인증 실패

**원인**: Service Account JSON 경로 오류
**해결**:
- `credentials/google-service-account.json` 파일 존재 확인
- `config.php`에서 경로 확인
- 파일 권한 확인 (600)

### 4. Database Connection 실패

**원인**: 잘못된 DB 정보
**해결**:
- `config.php`에서 DB_HOST, DB_NAME, DB_USER, DB_PASS 확인
- MySQL 사용자 권한 확인

### 5. 이미지 처리 시간 초과

**원인**: 대용량 이미지 처리
**해결**:
```php
// config.php 또는 php.ini
max_execution_time = 300
memory_limit = 256M
```

---

## 📊 데이터베이스 스키마

### prescriptions (처방전)
| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT | Primary Key |
| original_filename | VARCHAR(255) | 원본 파일명 |
| stored_filename | VARCHAR(255) | 저장된 파일명 |
| template_filename | VARCHAR(255) | 템플릿 파일명 |
| file_path | VARCHAR(500) | 파일 경로 |
| template_path | VARCHAR(500) | 템플릿 경로 |
| analysis_mode | ENUM | 분석 모드 |
| status | ENUM | 처리 상태 |
| created_at | TIMESTAMP | 생성 시간 |
| updated_at | TIMESTAMP | 수정 시간 |

### extracted_data (추출 데이터)
| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT | Primary Key |
| prescription_id | INT | Foreign Key |
| data_type | ENUM | 데이터 타입 |
| field_name | VARCHAR(255) | 필드명 |
| field_value | TEXT | 필드 값 |
| image_base64 | LONGTEXT | Base64 이미지 |
| confidence_score | DECIMAL(5,2) | 신뢰도 |
| created_at | TIMESTAMP | 생성 시간 |

---

## 🔄 업데이트 및 유지보수

### 로그 확인
```bash
tail -f logs/app.log
```

### 데이터베이스 백업
```bash
mysqldump -u username -p magic_rx_scanner > backup_$(date +%Y%m%d).sql
```

### 오래된 파일 정리 (수동)
```php
// cleanup.php 생성
<?php
require_once 'includes/config.php';
require_once 'includes/file_handler.php';

$fileHandler = new FileHandler();
$deleted = $fileHandler->cleanupOldFiles(UPLOAD_DIR, 30); // 30일 이상 된 파일 삭제
echo "Deleted {$deleted} files\n";
```

---

## 📝 라이선스

이 프로젝트는 교육 및 연구 목적으로 제공됩니다.

---

## 🤝 기여

버그 리포트 및 기능 제안은 GitHub Issues를 통해 제출해주세요.

---

## 📞 지원

문제가 발생하면 다음을 확인하세요:
1. 로그 파일 (`logs/app.log`)
2. 데이터베이스 연결
3. Google Cloud API 할당량
4. PHP 에러 로그

---

**Powered by Google Cloud AI** 🚀
