# Swissturn 시스템 - PHP + MySQL 백엔드 배포 가이드

이 가이드는 Hostinger에서 React 프론트엔드 + PHP 백엔드 + MySQL 데이터베이스를 배포하는 전체 과정을 설명합니다.

## 목차
1. [사전 준비](#사전-준비)
2. [데이터베이스 설정](#데이터베이스-설정)
3. [파일 업로드](#파일-업로드)
4. [API 설정](#api-설정)
5. [테스트](#테스트)
6. [문제 해결](#문제-해결)

---

## 사전 준비

### 필요한 것
- Hostinger 호스팅 계정
- FTP 클라이언트 (FileZilla 권장) 또는 Hostinger 파일 관리자
- 로컬 개발 환경 (Node.js 18+)

### 프로젝트 빌드

로컬에서 프로젝트를 빌드합니다:

```bash
cd swissturn-management
npm install
npm run build
```

빌드 결과물은 `dist/` 폴더에 생성됩니다.

---

## 데이터베이스 설정

### 1. MySQL 데이터베이스 생성

1. Hostinger 대시보드 → **데이터베이스** → **관리**
2. **MySQL 데이터베이스** 섹션에서 **새로 만들기**
3. 데이터베이스 이름: `swissturn_db` (또는 원하는 이름)
4. 생성된 정보 기록:
   - 데이터베이스 이름: `u123456789_swissturn`
   - 사용자명: `u123456789_user`
   - 비밀번호: `********`
   - 호스트: `localhost`

### 2. phpMyAdmin에서 스키마 실행

1. **phpMyAdmin 관리** 클릭
2. 왼쪽에서 생성한 데이터베이스 선택
3. 상단 **SQL** 탭 클릭
4. `database/schema.sql` 파일 내용 복사 → 붙여넣기
5. **실행** 클릭

### 3. 데이터 확인

다음 테이블들이 생성되었는지 확인:
- ✅ users
- ✅ machines (8개 장비 데이터)
- ✅ tools (샘플 공구 데이터)
- ✅ used_tools
- ✅ production_records
- ✅ tool_usage_history
- ✅ tool_replacement_history

---

## 파일 업로드

### 배포할 파일 구조

```
public_html/
├── api/                    # PHP 백엔드
│   ├── config/
│   │   ├── database.php   # ⚠️ 수정 필요
│   │   └── cors.php
│   ├── models/
│   │   ├── Machine.php
│   │   ├── Tool.php
│   │   ├── Production.php
│   │   └── User.php
│   ├── endpoints/
│   │   ├── machines.php
│   │   ├── tools.php
│   │   ├── production.php
│   │   └── auth.php
│   └── index.php
├── assets/                 # React 빌드 파일
├── index.html             # React 메인 파일
└── .htaccess              # URL 라우팅 설정
```

### 방법 1: Hostinger 파일 관리자

1. Hostinger → **파일 관리자**
2. `public_html` 폴더로 이동
3. 기존 파일 백업 후 삭제
4. 업로드:
   - `dist/` 폴더의 모든 내용 → `public_html/`
   - `api/` 폴더 전체 → `public_html/api/`
   - `database/` 폴더 → `public_html/database/` (참고용, 삭제 가능)

### 방법 2: FTP (FileZilla)

1. FTP 접속 정보 (Hostinger → FTP 계정)
2. FileZilla 연결
3. 로컬 `dist/` → 원격 `public_html/`
4. 로컬 `api/` → 원격 `public_html/api/`

---

## API 설정

### 1. 데이터베이스 연결 정보 수정

`public_html/api/config/database.php` 파일 수정:

```php
<?php
class Database {
    // Hostinger 데이터베이스 정보로 변경
    private $host = "localhost";
    private $db_name = "u123456789_swissturn";  // 실제 DB 이름
    private $username = "u123456789_user";       // 실제 사용자명
    private $password = "your_password_here";    // 실제 비밀번호
    private $charset = "utf8mb4";
    ...
}

class Config {
    public static $DB_HOST = "localhost";
    public static $DB_NAME = "u123456789_swissturn";
    public static $DB_USER = "u123456789_user";
    public static $DB_PASS = "your_password_here";

    // JWT 비밀키 변경 (보안)
    public static $JWT_SECRET = "CHANGE-THIS-TO-RANDOM-STRING-123456";

    // 허용 도메인
    public static $ALLOWED_ORIGINS = [
        "https://yourdomain.com"
    ];
}
?>
```

### 2. 파일 권한 설정

Hostinger 파일 관리자에서:
- `api/` 폴더: **755**
- `api/config/database.php`: **644**
- 모든 PHP 파일: **644**

---

## 테스트

### 1. 데이터베이스 연결 테스트

브라우저에서 접속:
```
https://yourdomain.com/api/machines
```

**정상 응답:**
```json
{
  "success": true,
  "data": [
    {
      "id": "MP1",
      "name": "MP1",
      "status": "idle",
      ...
    },
    ...
  ]
}
```

### 2. 각 API 엔드포인트 테스트

| 엔드포인트 | 메소드 | URL |
|----------|--------|-----|
| 장비 목록 | GET | `/api/machines` |
| 특정 장비 | GET | `/api/machines/MP1` |
| 장비 업데이트 | PUT | `/api/machines/MP1` |
| 공구 목록 | GET | `/api/tools` |
| 공구 생성 | POST | `/api/tools` |
| 공구 교체 | POST | `/api/tools/{id}/replace` |
| 사용 완료 공구 | GET | `/api/tools/used` |
| 생산 기록 | GET | `/api/production` |
| 로그인 | POST | `/api/auth` |

### 3. 프론트엔드 테스트

```
https://yourdomain.com
```

1. 대시보드 로드 확인
2. 장비 데이터 표시 확인
3. 공구 데이터 표시 확인
4. 장비 업데이트 테스트
5. 공구 추가 테스트

---

## 문제 해결

### API가 작동하지 않음 (500 오류)

**원인:** 데이터베이스 연결 실패

**해결:**
1. `api/config/database.php`의 정보가 정확한지 확인
2. phpMyAdmin에서 직접 로그인 테스트
3. PHP 오류 로그 확인 (Hostinger → 파일 관리자 → `error_log`)

### API가 404 오류

**원인:** .htaccess 라우팅 문제

**해결:**
1. `.htaccess` 파일이 `public_html/`에 있는지 확인
2. mod_rewrite가 활성화되어 있는지 Hostinger 지원팀에 문의
3. `.htaccess` 내용 확인:
```apache
RewriteRule ^api/(.*)$ api/index.php [QSA,L]
```

### CORS 오류

**원인:** Cross-Origin 요청 차단

**해결:**
`api/config/cors.php`에서 허용 도메인 확인:
```php
header("Access-Control-Allow-Origin: *");
```

### 데이터가 저장되지 않음

**원인:** 파일 권한 또는 데이터베이스 권한

**해결:**
1. phpMyAdmin에서 사용자 권한 확인
2. 파일 권한 확인 (api/ 폴더 755)
3. PHP 오류 로그 확인

### 한글이 깨짐

**원인:** 문자 인코딩 문제

**해결:**
1. 데이터베이스 문자 집합: `utf8mb4_unicode_ci`
2. PHP 파일 인코딩: UTF-8
3. `.htaccess`에 추가:
```apache
AddDefaultCharset UTF-8
```

---

## 보안 권장사항

### 1. 비밀번호 변경

기본 계정 비밀번호 즉시 변경:
```sql
UPDATE users
SET password = '$2y$10$새로운해시'
WHERE username = 'admin';
```

### 2. database.php 보호

`.htaccess`에 추가:
```apache
<Files "database.php">
    Require all denied
</Files>
```

### 3. JWT 비밀키 변경

`Config::$JWT_SECRET`을 랜덤한 긴 문자열로 변경

### 4. HTTPS 강제

Hostinger SSL 활성화 후 `.htaccess`에 추가:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 5. 정기 백업

- 데이터베이스: Hostinger 자동 백업 활성화
- 파일: 정기적으로 전체 다운로드

---

## 성능 최적화

### 1. PHP OPcache 활성화

Hostinger 대시보드에서 OPcache 활성화

### 2. 데이터베이스 인덱스

이미 스키마에 포함됨:
- machines.id
- tools.code, tools.status
- production_records.date, production_records.cnc

### 3. 캐싱

향후 Redis 또는 Memcached 고려

---

## 업데이트 배포

코드 수정 후 재배포:

1. 로컬에서 빌드: `npm run build`
2. `dist/` 내용 업로드
3. PHP 파일 수정 시 해당 파일만 업로드
4. 브라우저 캐시 삭제 후 테스트

---

## 지원

문제 발생 시:
1. PHP 오류 로그 확인 (`public_html/error_log`)
2. 브라우저 개발자 도구 Network 탭 확인
3. Hostinger 실시간 채팅 지원

---

배포 날짜: 2024
버전: 2.0.0 (PHP + MySQL 백엔드 포함)
