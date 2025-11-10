# 데이터베이스 설정 가이드

## Hostinger MySQL 데이터베이스 설정

### 1단계: Hostinger phpMyAdmin 접속

1. Hostinger 계정 로그인
2. **호스팅** → **웹사이트** → 해당 도메인 선택
3. **데이터베이스** → **phpMyAdmin 관리** 클릭

### 2단계: 데이터베이스 생성 (이미 생성되어 있다면 Skip)

1. phpMyAdmin 왼쪽 메뉴에서 **새로 만들기** 클릭
2. 데이터베이스 이름 입력: `swissturn_db` (또는 원하는 이름)
3. 문자 집합: `utf8mb4_unicode_ci` 선택
4. **만들기** 클릭

### 3단계: SQL 스키마 실행

1. 생성한 데이터베이스 선택 (왼쪽 메뉴)
2. 상단 메뉴에서 **SQL** 탭 클릭
3. `schema.sql` 파일의 내용을 복사하여 붙여넣기
   - **주의**: 맨 위의 `CREATE DATABASE` 줄은 주석 처리되어 있습니다
   - 데이터베이스는 이미 생성했으므로 그대로 실행하면 됩니다
4. **실행** 버튼 클릭

### 4단계: API 설정 파일 업데이트

`api/config/database.php` 파일을 열어 데이터베이스 정보를 업데이트합니다:

```php
class Database {
    // Hostinger 데이터베이스 정보로 변경
    private $host = "localhost";
    private $db_name = "u123456789_swissturn";  // Hostinger 데이터베이스 이름
    private $username = "u123456789_user";       // Hostinger 데이터베이스 사용자명
    private $password = "your_password_here";    // Hostinger 데이터베이스 비밀번호
    private $charset = "utf8mb4";
    ...
}
```

**Hostinger 데이터베이스 정보 확인 방법:**
1. Hostinger 대시보드 → **데이터베이스**
2. 데이터베이스 이름, 사용자명 확인
3. 비밀번호는 데이터베이스 생성 시 설정한 비밀번호

### 5단계: 기본 계정 확인

스키마 실행 후 자동으로 생성되는 기본 계정:

| 사용자명 | 비밀번호 | 역할 |
|---------|---------|------|
| admin | admin123 | 관리자 |
| operator | admin123 | 작업자 |

⚠️ **보안 경고**: 실제 운영 환경에서는 반드시 비밀번호를 변경하세요!

### 비밀번호 변경 방법:

1. phpMyAdmin에서 `users` 테이블 선택
2. 변경할 사용자 행에서 **편집** 클릭
3. `password` 필드를 다음과 같이 업데이트:

```sql
UPDATE users
SET password = '$2y$10$새로운해시값'
WHERE username = 'admin';
```

PHP에서 비밀번호 해시 생성:
```php
<?php
echo password_hash('새비밀번호', PASSWORD_DEFAULT);
?>
```

## 테이블 구조

### users - 사용자
- id, username, password, name, role

### machines - 장비
- id, name, status, current_job, runtime, downtime, oee

### tools - 공구
- id, code, name, category, size, supplier, lifespan_limit, current_usage, status, machine_id

### used_tools - 사용 완료 공구
- id, tool_id, code, name, final_usage, replaced_at

### production_records - 생산 기록
- id, date, model_name, sku, cnc, worker, unit, achievement_*

### tool_usage_history - 공구 사용 이력
- id, tool_id, machine_id, usage_hours, date

### tool_replacement_history - 공구 교체 이력
- id, old_tool_id, new_tool_id, machine_id, replaced_at

## 문제 해결

### 데이터베이스 연결 오류
1. `api/config/database.php`의 정보가 정확한지 확인
2. Hostinger 데이터베이스가 활성화되어 있는지 확인
3. phpMyAdmin에서 직접 로그인 테스트

### 권한 오류
- Hostinger에서 생성한 데이터베이스 사용자에게 모든 권한이 있는지 확인
- phpMyAdmin → 사용자 계정 → 권한 확인

### API 테스트
브라우저에서 다음 URL 접속하여 API 테스트:
```
https://yourdomain.com/api/machines
```

정상 응답 예시:
```json
{
  "success": true,
  "data": [...]
}
```

## 백업

정기적으로 데이터베이스 백업:
1. phpMyAdmin → 해당 데이터베이스 선택
2. **내보내기** 탭
3. **빠른** 또는 **사용자 정의** 선택
4. **실행** 클릭하여 SQL 파일 다운로드

**자동 백업**: Hostinger 대시보드에서 자동 백업 설정 가능
