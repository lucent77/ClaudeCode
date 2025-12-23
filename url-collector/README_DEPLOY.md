# URL Collector - Deployment Guide

iPhone 단축어로 URL을 수집하고, Gemini AI로 자동 분석하여 대시보드에서 관리하는 웹 서비스입니다.

## 목차

1. [요구사항](#요구사항)
2. [파일 구조](#파일-구조)
3. [설치 방법](#설치-방법)
4. [설정](#설정)
5. [데이터베이스 설정](#데이터베이스-설정)
6. [Cron 설정](#cron-설정)
7. [iPhone 단축어 설정](#iphone-단축어-설정)
8. [대시보드 사용법](#대시보드-사용법)
9. [보안 체크리스트](#보안-체크리스트)
10. [문제 해결](#문제-해결)

---

## 요구사항

- PHP 8.0 이상
- MySQL 5.7+ 또는 MariaDB 10.3+
- cURL 확장 모듈
- PDO MySQL 확장 모듈
- Hostinger 공유호스팅/클라우드호스팅 (또는 호환 환경)

**필요 없음:** Composer, Node.js, Python, 추가 OS 패키지

---

## 파일 구조

```
url-collector/
├── config/
│   ├── config.php          # 메인 설정 파일 (수정 필요)
│   └── .htaccess           # 디렉토리 보호
├── lib/
│   ├── db.php              # 데이터베이스 연결
│   ├── http.php            # HTTP 클라이언트 (SSRF 방지)
│   ├── parser.php          # HTML 파싱
│   ├── gemini.php          # Gemini API 클라이언트
│   ├── security.php        # 보안 유틸리티
│   ├── util.php            # 헬퍼 함수
│   └── .htaccess           # 디렉토리 보호
├── api/
│   └── ingest.php          # URL 수집 API 엔드포인트
├── cron/
│   └── analyze.php         # 분석 크론 작업
├── dashboard/
│   ├── login.php           # 로그인 페이지
│   ├── logout.php          # 로그아웃
│   ├── index.php           # 메인 대시보드
│   ├── edit.php            # 상세 편집
│   └── update.php          # 업데이트 처리
├── public/
│   └── .htaccess           # 보안 헤더 및 규칙
├── logs/
│   └── .htaccess           # 로그 디렉토리 보호
├── sql/
│   ├── schema.sql          # 데이터베이스 스키마
│   └── .htaccess           # 디렉토리 보호
└── README_DEPLOY.md        # 이 문서
```

---

## 설치 방법

### 1. 파일 업로드

1. 모든 파일을 Hostinger의 `public_html` 디렉토리에 업로드합니다.
2. 또는 서브디렉토리(예: `public_html/collector`)에 업로드할 수 있습니다.

### 2. 디렉토리 권한 설정

```bash
# logs 디렉토리에 쓰기 권한 부여
chmod 755 logs/
chmod 644 logs/.htaccess
```

### 3. 설정 파일 수정

`config/config.php` 파일을 열어 다음 항목들을 수정합니다:

```php
// 데이터베이스 설정
'db' => [
    'host'     => 'localhost',           // Hostinger MySQL 호스트
    'name'     => 'u123456789_urlcol',   // 데이터베이스 이름
    'user'     => 'u123456789_admin',    // 데이터베이스 사용자
    'pass'     => 'YourSecurePassword',  // 데이터베이스 비밀번호
],

// API 키 설정 (강력한 랜덤 문자열 생성)
'app' => [
    'api_key' => 'your-32-character-random-string-here',
],

// 관리자 계정
'admin' => [
    'username' => 'admin',
    'password_hash' => '$2y$10$...', // 아래 방법으로 생성
],

// Gemini API 키
'gemini' => [
    'api_key' => 'YOUR_GEMINI_API_KEY',
],
```

### 4. 관리자 비밀번호 해시 생성

PHP에서 다음 명령어로 비밀번호 해시를 생성합니다:

```php
<?php
echo password_hash('your_secure_password', PASSWORD_DEFAULT);
// 출력: $2y$10$xxxxx...
```

또는 온라인 도구 사용: https://bcrypt-generator.com/

---

## 데이터베이스 설정

### Hostinger에서 데이터베이스 생성

1. Hostinger hPanel 로그인
2. **Databases** > **MySQL Databases** 이동
3. 새 데이터베이스 생성 (예: `u123456789_urlcol`)
4. 사용자 생성 및 권한 부여

### 스키마 적용

1. **phpMyAdmin** 접속
2. 생성한 데이터베이스 선택
3. **Import** 탭 클릭
4. `sql/schema.sql` 파일 업로드 및 실행

또는 SQL 탭에서 직접 실행:

```sql
-- sql/schema.sql 내용을 복사하여 실행
```

---

## Cron 설정

### Hostinger에서 Cron Job 설정

1. hPanel > **Advanced** > **Cron Jobs** 이동
2. 새 Cron Job 추가:

```
# 1분마다 실행 (권장)
* * * * * /usr/bin/php /home/u123456789/public_html/cron/analyze.php

# 또는 5분마다
*/5 * * * * /usr/bin/php /home/u123456789/public_html/cron/analyze.php
```

### PHP 경로 확인

Hostinger의 PHP 경로는 보통:
- `/usr/bin/php`
- `/usr/bin/php8.1`
- `/usr/bin/php8.2`

정확한 경로 확인:
```bash
which php
```

### Cron 로그 확인

```bash
tail -f /home/u123456789/public_html/logs/app.log
```

---

## iPhone 단축어 설정

### 1. 단축어 앱에서 새 단축어 생성

1. **단축어** 앱 열기
2. **+** 버튼으로 새 단축어 생성
3. 이름 지정: "URL 저장" 또는 원하는 이름

### 2. 액션 추가

#### 액션 1: "공유 시트 입력 받기" (선택)
- 유형: URL

#### 액션 2: "URL의 컨텐츠 가져오기" (필수)
```
URL: https://yourdomain.com/api/ingest.php
Method: POST
Headers:
  - X-APP-KEY: your-api-key-here
  - Content-Type: application/json
Request Body: JSON
  {
    "url": [단축어 입력],
    "source": "ios_shortcut",
    "note": ""
  }
```

#### 액션 3: "사전에서 값 가져오기" (선택)
- 키: `success` 또는 `message`

#### 액션 4: "알림 표시" (선택)
- 결과 메시지 표시

### 3. 공유 시트에 추가

1. 단축어 설정 (i 버튼)
2. **공유 시트에 표시** 활성화
3. **공유 시트 유형**: URL 선택

### 4. 사용 방법

1. Safari나 앱에서 URL 공유
2. "URL 저장" 단축어 선택
3. 완료 알림 확인

### 단축어 JSON 예시

```json
{
  "WFWorkflowActions": [
    {
      "WFWorkflowActionIdentifier": "is.workflow.actions.getcontentsofurl",
      "WFWorkflowActionParameters": {
        "WFHTTPMethod": "POST",
        "WFHTTPHeaders": {
          "X-APP-KEY": "your-api-key",
          "Content-Type": "application/json"
        },
        "WFHTTPBodyType": "Json",
        "WFJSONValues": {
          "url": "{{Input}}",
          "source": "ios_shortcut"
        },
        "WFURL": "https://yourdomain.com/api/ingest.php"
      }
    }
  ]
}
```

---

## 대시보드 사용법

### 접속

```
https://yourdomain.com/dashboard/login.php
```

### 기능

#### 탭 필터
- **Inbox**: 새로 수집된 항목 (리뷰 대기)
- **Keep**: 보관 표시된 항목
- **Archive**: 아카이브된 항목
- **Pending**: 분석 대기 중
- **Error**: 분석 실패
- **All**: 전체 항목

#### 정렬 옵션
- 기본 정렬 (탭별 최적화)
- 점수 높은 순 / 낮은 순
- 최신순 / 오래된 순
- 분석일 순

#### 빠른 액션
- **Keep**: 보관 표시
- **Archive**: 아카이브
- **Score**: 관심도 점수 (0-100) 입력

#### 상세 편집
- 리뷰 상태 변경
- 관심도 점수 조정
- 카테고리 수정
- 메모 추가
- 재분석 요청

---

## 보안 체크리스트

### 필수 확인 사항

- [ ] `config/config.php`의 API 키가 강력한 랜덤 문자열인지 확인
- [ ] 관리자 비밀번호가 안전한 해시로 저장되어 있는지 확인
- [ ] Gemini API 키가 올바르게 설정되어 있는지 확인
- [ ] 모든 `.htaccess` 파일이 제자리에 있는지 확인
- [ ] `logs/` 디렉토리가 웹에서 접근 불가한지 확인
- [ ] `config/`, `lib/`, `sql/` 디렉토리가 웹에서 접근 불가한지 확인
- [ ] HTTPS가 활성화되어 있는지 확인 (필수!)

### 테스트

```bash
# 보호된 디렉토리 접근 테스트 (403 에러가 나와야 함)
curl https://yourdomain.com/config/config.php
curl https://yourdomain.com/logs/app.log
curl https://yourdomain.com/lib/db.php

# API 인증 테스트 (401 에러가 나와야 함)
curl -X POST https://yourdomain.com/api/ingest.php

# 정상 API 호출 테스트
curl -X POST https://yourdomain.com/api/ingest.php \
  -H "X-APP-KEY: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{"url": "https://example.com"}'
```

### HTTPS 강제 (권장)

`public/.htaccess`에서 주석 해제:

```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 문제 해결

### API 호출 실패

1. **401 Unauthorized**
   - API 키가 올바른지 확인
   - 헤더가 `X-APP-KEY` 또는 `Authorization: Bearer`인지 확인

2. **429 Too Many Requests**
   - Rate limit 초과. 잠시 후 재시도
   - `config.php`에서 `rate_limit` 값 조정 가능

3. **500 Internal Server Error**
   - `logs/app.log` 확인
   - PHP 에러 로그 확인
   - 데이터베이스 연결 정보 확인

### Cron이 실행되지 않음

1. PHP 경로 확인
2. 파일 권한 확인 (`chmod +x cron/analyze.php`)
3. Cron 로그 확인
4. 수동 실행 테스트:
   ```bash
   /usr/bin/php /path/to/cron/analyze.php
   ```

### 분석이 실패하는 경우

1. Gemini API 키 확인
2. API 할당량 확인 (Google AI Studio)
3. 네트워크 연결 확인
4. `error_message` 필드에서 상세 오류 확인

### 대시보드 로그인 실패

1. 비밀번호 해시가 올바르게 생성되었는지 확인
2. 세션 디렉토리 권한 확인
3. 쿠키가 활성화되어 있는지 확인

---

## API 응답 형식

### 성공 응답

```json
{
  "success": true,
  "message": "URL queued for analysis",
  "id": 123,
  "url": "https://example.com",
  "status": "pending",
  "review_status": "inbox"
}
```

### 중복 URL

```json
{
  "success": true,
  "message": "URL already exists",
  "id": 45,
  "status": "done",
  "review_status": "inbox",
  "duplicate": true
}
```

### 오류 응답

```json
{
  "success": false,
  "error": "URL is required"
}
```

---

## 지원

문제가 발생하면:

1. `logs/app.log` 파일 확인
2. PHP 에러 로그 확인
3. 위 문제 해결 섹션 참조

---

## 라이선스

MIT License

---

## 변경 이력

- **v1.0.0** - 초기 릴리스
  - URL 수집 및 저장
  - Gemini AI 분석
  - 대시보드 (Inbox/Keep/Archive)
  - 관심도 점수
  - iPhone 단축어 지원
