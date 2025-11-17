# 🎁 경품 추첨 프로그램

전시회에서 사용할 수 있는 실시간 경품 추첨 시스템입니다. SVG 룰렛 애니메이션과 함께 확률 기반 추첨을 제공합니다.

## ✨ 주요 기능

- 🎰 **SVG 룰렛 애니메이션**: 디테일한 SVG 기반 룰렛이 회전하며 추첨
- 📊 **확률 기반 추첨**: 각 상품별 확률을 설정하여 공정한 추첨
- 📧 **이메일 수집**: 참여자 이메일 자동 수집 및 저장
- 💾 **데이터베이스 저장**: 모든 당첨 내역 자동 저장
- 📱 **반응형 디자인**: Tailwind CSS로 제작된 모던하고 세련된 UI
- 👨‍💼 **관리자 페이지**: 상품 관리, 당첨자 목록, 통계 확인
- 📥 **CSV 내보내기**: 당첨자 목록을 CSV 파일로 다운로드

## 🛠️ 기술 스택

- **프론트엔드**: HTML5, Tailwind CSS, Vanilla JavaScript
- **백엔드**: PHP 7.4+
- **데이터베이스**: MySQL 5.7+ / MariaDB 10.3+
- **호스팅**: Hostinger PHP 웹호스팅

## 📦 설치 방법

### 1. 파일 업로드

Hostinger 파일 관리자 또는 FTP를 통해 모든 파일을 웹 루트 디렉토리에 업로드합니다.

```
/public_html/
├── admin/
│   └── index.php
├── api/
│   ├── config.php
│   ├── draw.php
│   ├── get_prizes.php
│   └── get_winners.php
├── assets/
│   └── js/
│       └── raffle.js
├── database/
│   └── schema.sql
├── .htaccess
├── index.php
└── README.md
```

### 2. 데이터베이스 설정

#### 2.1 데이터베이스 생성

Hostinger의 phpMyAdmin 또는 MySQL 데이터베이스 관리 도구에서:

1. 새 데이터베이스 생성 (예: `raffle_db`)
2. 데이터베이스 사용자 생성 및 권한 부여

#### 2.2 테이블 생성

`database/schema.sql` 파일의 내용을 phpMyAdmin에서 실행:

1. phpMyAdmin에 접속
2. 생성한 데이터베이스 선택
3. "SQL" 탭 클릭
4. `schema.sql` 파일의 내용 복사 후 실행

#### 2.3 연결 설정

`api/config.php` 파일을 열어 데이터베이스 정보를 입력:

```php
define('DB_HOST', 'localhost');              // 보통 localhost
define('DB_NAME', 'raffle_db');              // 생성한 데이터베이스 이름
define('DB_USER', 'your_db_username');       // 데이터베이스 사용자명
define('DB_PASS', 'your_db_password');       // 데이터베이스 비밀번호
```

### 3. 권한 설정

파일 권한이 올바르게 설정되었는지 확인:

```bash
# 디렉토리: 755
# PHP 파일: 644
```

### 4. 테스트

브라우저에서 사이트에 접속하여 정상 작동 확인:

- 메인 페이지: `https://yourdomain.com/`
- 관리자 페이지: `https://yourdomain.com/admin/`

## 🎨 상품 설정

### 데이터베이스에서 직접 수정

phpMyAdmin에서 `prizes` 테이블을 직접 수정할 수 있습니다:

```sql
-- 상품 추가
INSERT INTO prizes (name, probability, color, stock)
VALUES ('1등 - MacBook Pro', 0.5, '#EF4444', 1);

-- 상품 수정
UPDATE prizes
SET probability = 5.0, stock = 10
WHERE id = 1;

-- 상품 비활성화
UPDATE prizes
SET is_active = 0
WHERE id = 1;
```

### 확률 설정 주의사항

⚠️ **중요**: 모든 활성화된 상품의 확률 합계는 **반드시 100%**여야 합니다!

```sql
-- 확률 합계 확인
SELECT SUM(probability) as total FROM prizes WHERE is_active = 1;
-- 결과가 100.00이어야 합니다
```

## 🎯 사용 방법

### 참여자 입장

1. 메인 페이지 접속
2. 이메일 주소 입력
3. "추첨 시작!" 버튼 클릭
4. 룰렛이 회전하며 당첨 결과 표시

### 관리자 입장

1. 관리자 페이지 접속 (`/admin/`)
2. **상품 관리** 탭: 등록된 상품 및 확률 확인
3. **당첨자 목록** 탭: 당첨자 내역 확인 및 CSV 다운로드
4. **통계** 탭: 실시간 참여 통계 확인

## 📊 데이터베이스 구조

### prizes (상품 테이블)

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT | 상품 ID (Primary Key) |
| name | VARCHAR(255) | 상품명 |
| probability | DECIMAL(5,2) | 당첨 확률 (%) |
| color | VARCHAR(7) | 룰렛 색상 (Hex) |
| stock | INT | 재고 (-1: 무제한) |
| is_active | TINYINT(1) | 활성화 여부 |
| created_at | TIMESTAMP | 생성일시 |

### winners (당첨자 테이블)

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT | 당첨 ID (Primary Key) |
| email | VARCHAR(255) | 참여자 이메일 |
| prize_id | INT | 당첨 상품 ID (Foreign Key) |
| prize_name | VARCHAR(255) | 당첨 상품명 (기록용) |
| ip_address | VARCHAR(45) | IP 주소 |
| user_agent | TEXT | User Agent |
| drawn_at | TIMESTAMP | 추첨 일시 |

## 🔒 보안 고려사항

### 구현된 보안 기능

- ✅ SQL Injection 방지 (PDO Prepared Statements)
- ✅ XSS 방지 (htmlspecialchars)
- ✅ 이메일 유효성 검사
- ✅ 민감한 파일 접근 차단 (.htaccess)
- ✅ 에러 로그 기록

### 추가 보안 권장사항

1. **관리자 페이지 보호**: 기본 인증 또는 로그인 시스템 추가
2. **HTTPS 사용**: SSL 인증서 설치 (Hostinger에서 무료 제공)
3. **Rate Limiting**: 동일 IP의 과도한 요청 제한
4. **CAPTCHA**: 봇 방지를 위한 reCAPTCHA 추가

### 관리자 페이지 보호 (.htaccess)

`/admin/.htaccess` 파일 추가:

```apache
AuthType Basic
AuthName "관리자 전용"
AuthUserFile /path/to/.htpasswd
Require valid-user
```

## 🎨 커스터마이징

### 색상 변경

`index.php`의 `<style>` 섹션에서 그라디언트 색상 변경:

```css
.gradient-bg {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
```

### 룰렛 크기 조정

`assets/js/raffle.js`에서:

```javascript
this.radius = 250;  // 룰렛 반지름
this.centerX = 300; // 중심 X 좌표
this.centerY = 300; // 중심 Y 좌표
```

### 회전 시간 조정

```javascript
const duration = 5000; // 5초 (밀리초 단위)
```

## 🐛 문제 해결

### 데이터베이스 연결 오류

- `api/config.php`의 DB 정보 확인
- Hostinger에서 원격 DB 접속 허용 확인
- 호스트명이 `localhost`인지 확인

### 상품 목록이 안 보임

- 브라우저 콘솔(F12)에서 에러 확인
- `/api/get_prizes.php`를 직접 접속하여 JSON 응답 확인
- PHP 에러 로그 확인

### 룰렛이 안 돌아감

- 브라우저 콘솔에서 JavaScript 에러 확인
- `/assets/js/raffle.js` 파일 경로 확인
- 캐시 삭제 후 재시도 (Ctrl+F5)

### 확률이 정확하지 않음

- 모든 상품의 확률 합계가 100%인지 확인:
  ```sql
  SELECT SUM(probability) FROM prizes WHERE is_active = 1;
  ```

## 📈 성능 최적화

1. **이미지 최적화**: 상품 이미지 사용 시 WebP 형식 사용
2. **캐싱 활용**: `.htaccess`에 캐시 설정 적용
3. **CDN 사용**: Tailwind CSS를 로컬에 다운로드하여 사용
4. **DB 인덱싱**: 대량 데이터 처리 시 인덱스 최적화

## 📄 라이선스

이 프로젝트는 MIT 라이선스로 배포됩니다. 자유롭게 사용 및 수정 가능합니다.

## 🙏 지원

문제가 발생하거나 추가 기능이 필요한 경우:

- 데이터베이스 백업을 먼저 수행
- 에러 로그 확인
- Hostinger 지원팀 문의

## 📞 연락처

프로젝트 관련 문의: [your-email@example.com]

---

**즐거운 전시회 되세요! 🎉**
