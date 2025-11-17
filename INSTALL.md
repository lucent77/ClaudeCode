# 📦 Hostinger 설치 가이드

이 문서는 Hostinger PHP 웹호스팅에서 경품 추첨 프로그램을 설치하는 단계별 가이드입니다.

## 전제 조건

- Hostinger 웹호스팅 계정
- FTP 클라이언트 (FileZilla 권장) 또는 Hostinger 파일 관리자
- phpMyAdmin 접근 권한

## 1단계: 파일 업로드

### 방법 A: Hostinger 파일 관리자 사용

1. Hostinger hPanel에 로그인
2. **파일** → **파일 관리자** 클릭
3. `public_html` 디렉토리로 이동
4. 모든 프로젝트 파일을 업로드 (ZIP으로 압축 후 업로드 가능)
5. 압축 해제

### 방법 B: FTP 클라이언트 사용

1. FTP 접속 정보 확인 (hPanel에서 확인)
   - 호스트: `ftpXXX.hostinger.com`
   - 사용자명: `u123456789`
   - 비밀번호: (설정한 비밀번호)
   - 포트: 21

2. FileZilla 등 FTP 클라이언트로 접속

3. 로컬에서 `public_html` 디렉토리로 모든 파일 업로드

## 2단계: MySQL 데이터베이스 생성

1. Hostinger hPanel에 로그인

2. **데이터베이스** → **MySQL 데이터베이스** 클릭

3. **새 데이터베이스 만들기** 클릭
   - 데이터베이스 이름: `raffle_db` (원하는 이름)
   - 생성 버튼 클릭

4. 생성된 정보를 메모:
   ```
   데이터베이스 이름: u123456789_raffle
   사용자명: u123456789_user
   비밀번호: (자동 생성된 비밀번호)
   호스트: localhost
   ```

## 3단계: 데이터베이스 테이블 생성

1. **phpMyAdmin** 클릭 (또는 hPanel에서 직접 접근)

2. 좌측에서 방금 생성한 데이터베이스 선택

3. 상단 **SQL** 탭 클릭

4. FTP로 업로드한 `/database/schema.sql` 파일의 내용을 복사

5. SQL 쿼리 창에 붙여넣기

6. **실행** 버튼 클릭

7. 성공 메시지 확인 및 테이블 생성 확인:
   - `prizes` 테이블
   - `winners` 테이블
   - 샘플 데이터 6개 삽입됨

## 4단계: 데이터베이스 연결 설정

1. 파일 관리자에서 `/api/config.php` 파일 열기

2. 다음 부분을 2단계에서 메모한 정보로 수정:

```php
define('DB_HOST', 'localhost');                    // 그대로 유지
define('DB_NAME', 'u123456789_raffle');           // 실제 DB 이름
define('DB_USER', 'u123456789_user');             // 실제 사용자명
define('DB_PASS', 'your_actual_password');        // 실제 비밀번호
```

3. 파일 저장

## 5단계: 테스트

### 5.1 데이터베이스 연결 테스트

브라우저에서 다음 URL에 접속:

```
https://yourdomain.com/api/get_prizes.php
```

정상 응답 예시:
```json
{
  "success": true,
  "prizes": [...],
  "total_probability": 100,
  "count": 6
}
```

### 5.2 메인 페이지 테스트

```
https://yourdomain.com/
```

- 룰렛이 정상적으로 표시되는지 확인
- 상품 목록이 보이는지 확인
- 이메일 입력 폼이 있는지 확인

### 5.3 추첨 기능 테스트

1. 테스트 이메일 입력 (예: `test@example.com`)
2. "추첨 시작!" 버튼 클릭
3. 룰렛이 회전하는지 확인
4. 당첨 결과가 표시되는지 확인

### 5.4 관리자 페이지 테스트

```
https://yourdomain.com/admin/
```

- 상품 목록이 표시되는지 확인
- 당첨자 목록에 방금 테스트한 데이터가 있는지 확인
- 통계가 정상적으로 표시되는지 확인

## 6단계: 보안 설정 (권장)

### 6.1 관리자 페이지 비밀번호 보호

1. `.htpasswd` 파일 생성:

온라인 도구 사용: https://hostingcanada.org/htpasswd-generator/

예시 출력:
```
admin:$apr1$abc12345$xyz...
```

2. Hostinger 파일 관리자에서 홈 디렉토리에 `.htpasswd` 파일 생성

3. 위 출력 내용 붙여넣기

4. `/admin/.htaccess` 파일 편집:

```apache
AuthType Basic
AuthName "관리자 전용"
AuthUserFile /home/u123456789/.htpasswd
Require valid-user
```

5. 파일 경로 확인:
   - 파일 관리자에서 홈 경로 확인 (보통 `/home/uXXXXXXXXX`)
   - `AuthUserFile` 경로를 실제 경로로 수정

### 6.2 SSL 인증서 설치 (HTTPS)

1. Hostinger hPanel에서 **SSL** 메뉴 클릭

2. 무료 Let's Encrypt SSL 설치

3. HTTPS 강제 리디렉션 설정

## 7단계: 커스터마이징

### 상품 수정

phpMyAdmin에서 `prizes` 테이블 수정:

```sql
-- 상품 추가
INSERT INTO prizes (name, probability, color, stock)
VALUES ('신규 상품', 5.0, '#3B82F6', 10);

-- 상품 수정
UPDATE prizes SET name = '수정된 상품명' WHERE id = 1;

-- 상품 삭제 (비활성화 권장)
UPDATE prizes SET is_active = 0 WHERE id = 1;
```

⚠️ **중요**: 확률 합계가 100%가 되도록 조정!

```sql
-- 확률 합계 확인
SELECT SUM(probability) FROM prizes WHERE is_active = 1;
```

### 룰렛 색상 변경

`index.php`에서 CSS 수정:

```css
.gradient-bg {
    background: linear-gradient(135deg, #YOUR_COLOR1 0%, #YOUR_COLOR2 100%);
}
```

## 문제 해결

### 에러: "데이터베이스 연결 실패"

- `/api/config.php`의 DB 정보 재확인
- phpMyAdmin에서 접속 테스트
- 데이터베이스 사용자 권한 확인

### 에러: "상품 목록을 불러올 수 없습니다"

```bash
# PHP 에러 로그 확인
# Hostinger hPanel → 웹사이트 → 고급 → 에러 로그
```

### 룰렛이 안 보임

1. 브라우저 콘솔(F12) 열기
2. 에러 메시지 확인
3. `/assets/js/raffle.js` 파일 경로 확인
4. 캐시 삭제 (Ctrl+Shift+R)

### 확률이 맞지 않음

```sql
-- DB에서 확률 합계 확인
SELECT SUM(probability) as total FROM prizes WHERE is_active = 1;
-- 결과가 정확히 100.00이어야 합니다
```

### 파일 권한 문제

Hostinger 파일 관리자에서:
- 디렉토리: 755
- PHP 파일: 644

## 유지보수

### 정기 백업

1. **데이터베이스 백업**:
   - phpMyAdmin → 내보내기 → SQL 형식

2. **파일 백업**:
   - FTP로 전체 파일 다운로드

### 로그 모니터링

- Hostinger hPanel → 에러 로그 정기 확인
- 비정상적인 접근 패턴 모니터링

## 추가 리소스

- Hostinger 고객 지원: https://www.hostinger.com/support
- PHP 문서: https://www.php.net/manual/
- MySQL 문서: https://dev.mysql.com/doc/

## 완료!

이제 경품 추첨 프로그램을 사용할 준비가 완료되었습니다! 🎉

전시회에서 즐겁게 사용하세요!
