# Scanbody File Manager

Hostinger PHP 웹서비스를 이용한 임플란트 Scanbody 파일 관리 시스템입니다. 제조사별 ID Code와 STL 파일을 효율적으로 관리할 수 있습니다.

## 주요 기능

- ✅ 임플란트 시스템 관리 (System, Size, SKU)
- ✅ 제조사별 Scanbody 관리 (3Shape, exocad 등)
- ✅ ID Code 입력 및 관리
- ✅ STL 파일 업로드/다운로드
- ✅ CSV 데이터 가져오기
- ✅ 검색 및 필터링
- ✅ 세련된 모던 UI (Tailwind CSS)

## 설치 방법

### 1. 파일 업로드

Hostinger 파일 매니저 또는 FTP를 통해 다음 파일들을 웹 루트 디렉토리에 업로드합니다:

```
/public_html/
├── index.php
├── api.php
├── config.php
├── database.php
├── schema.sql
├── sample_data.csv
├── .htaccess
└── uploads/ (디렉토리)
```

### 2. 데이터베이스 설정

#### 2.1 데이터베이스 생성

Hostinger의 phpMyAdmin 또는 데이터베이스 관리 도구에서:

1. 새 데이터베이스 생성 (예: `scanbody_manager`)
2. `schema.sql` 파일의 내용을 실행하여 테이블 생성

#### 2.2 config.php 수정

`config.php` 파일을 열어 데이터베이스 정보를 수정합니다:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');      // 데이터베이스 이름
define('DB_USER', 'your_database_user');      // 데이터베이스 사용자명
define('DB_PASS', 'your_database_password');  // 데이터베이스 비밀번호
```

**BASE_URL도 실제 도메인으로 변경:**

```php
define('BASE_URL', 'https://yourdomain.com');  // 실제 도메인으로 변경
```

### 3. 디렉토리 권한 설정

`uploads/` 디렉토리에 쓰기 권한을 부여합니다:

```bash
chmod 755 uploads/
```

Hostinger 파일 매니저에서도 디렉토리 권한을 변경할 수 있습니다.

### 4. 초기 데이터 가져오기

1. 웹 브라우저에서 사이트 접속
2. "CSV 가져오기" 버튼 클릭
3. `sample_data.csv` 파일 선택
4. "가져오기" 버튼 클릭

## 사용 방법

### 시스템 추가

1. "시스템 추가" 버튼 클릭
2. 시스템 이름, Size, SKU 입력
3. "저장" 버튼 클릭

### 제조사 추가

1. "제조사 추가" 버튼 클릭
2. 제조사 이름과 타입(CAD 소프트웨어) 입력
   - 예: 이름 = "3Shape", 타입 = "3Shape"
   - 예: 이름 = "exocad", 타입 = "exocad"
3. "저장" 버튼 클릭

### Scanbody 데이터 입력

1. 테이블에서 셀 클릭 (+ 아이콘 또는 기존 데이터)
2. ID Code 입력
3. STL 파일 선택 (선택사항)
4. "저장" 버튼 클릭

### STL 파일 다운로드

테이블에서 "STL" 링크를 클릭하면 파일이 다운로드됩니다.

### 검색

상단의 검색창에 키워드를 입력하면 실시간으로 필터링됩니다:
- 시스템 이름
- SKU
- ID Code
- 제조사 이름

## CSV 파일 형식

CSV 파일은 다음 형식을 따라야 합니다:

```csv
System,Size,SKU,제조사1 [타입1],제조사1 [타입2],제조사2 [타입1],...
시스템명,크기,제품코드,ID Code,ID Code,ID Code,...
```

**예시:**

```csv
System,Size,SKU,ARGEN [3Shape],ARGEN [exocad],ASTRA [3Shape]
AstraTech (OsseoSpeed TX),3.0 mm,AS30,,,O-LAB
AstraTech (OsseoSpeed TX),3.5/4.0 mm,AS40,O,O,
```

## 기술 스택

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**: HTML5, JavaScript (Vanilla)
- **CSS Framework**: Tailwind CSS 3.x
- **Icons**: Font Awesome 6.x

## 보안 권장사항

1. **프로덕션 환경에서는 에러 표시 비활성화**

   `config.php`에서:
   ```php
   error_reporting(0);
   ini_set('display_errors', 0);
   ```

2. **HTTPS 사용**

   Hostinger에서 무료 SSL 인증서를 활성화하고, `config.php`에서:
   ```php
   ini_set('session.cookie_secure', 1);  // HTTPS 전용 쿠키
   ```

3. **데이터베이스 백업**

   정기적으로 데이터베이스를 백업합니다.

4. **파일 업로드 크기 제한**

   `config.php`에서 MAX_FILE_SIZE를 적절히 설정합니다.

## 문제 해결

### "Database connection failed" 오류

- `config.php`의 데이터베이스 정보를 확인합니다
- Hostinger 컨트롤 패널에서 데이터베이스가 활성화되어 있는지 확인합니다

### 파일 업로드 실패

- `uploads/` 디렉토리의 권한을 확인합니다 (755 권한 필요)
- PHP 설정의 `upload_max_filesize` 및 `post_max_size`를 확인합니다

### 데이터가 표시되지 않음

- 브라우저 개발자 도구의 콘솔에서 JavaScript 오류를 확인합니다
- `api.php?action=get_data_matrix`를 직접 호출하여 응답을 확인합니다

## 라이선스

이 프로젝트는 MIT 라이선스 하에 배포됩니다.

## 지원

문의사항이 있으시면 이슈를 등록해주세요.

---

**제작**: Claude AI Assistant
**버전**: 1.0.0
**최종 업데이트**: 2025-11-18
