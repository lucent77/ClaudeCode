# Hostinger 설치 가이드 (간편 버전)

## 구조 변경 완료

프로젝트가 Hostinger 호스팅 서비스에 최적화되도록 재구성되었습니다.

### 변경 사항
- ✅ **index.php가 루트 디렉토리에 위치** (Hostinger 요구사항)
- ✅ 모든 PHP 파일이 루트에 배치
- ✅ 모든 경로 수정 완료 (`/public/` → `/`)
- ✅ `.htaccess` 루트 레벨 보안 설정

## 새로운 디렉토리 구조

```
task-management-system/
├── index.php              ← 메인 대시보드 (루트에 위치!)
├── login.php              ← 로그인 페이지
├── logout.php             ← 로그아웃
├── front-desk.php         ← Front Desk 페이지
├── solidex.php            ← Solidex 부서
├── cocr.php               ← COCR 부서
├── 3d-print.php           ← 3D Print 부서
├── admin.php              ← 관리자 대시보드
├── api/                   ← API 엔드포인트
│   ├── tasks.php
│   ├── users.php
│   └── import.php
├── includes/              ← PHP 클래스
├── config/                ← 설정 파일
├── database/              ← DB 스키마
├── assets/                ← CSS, JS
├── views/                 ← 템플릿
├── uploads/               ← 업로드 파일
└── logs/                  ← 로그 파일
```

## Hostinger 설치 방법

### 1단계: 파일 업로드
1. Hostinger hPanel에 로그인
2. **파일 관리자** 또는 **FTP**로 접속
3. `public_html/` 폴더로 이동
4. `task-management-system` 폴더 전체를 업로드

**결과:** `public_html/task-management-system/index.php`

### 2단계: 데이터베이스 생성
1. hPanel → **데이터베이스** → **MySQL 데이터베이스**
2. **새 데이터베이스 생성**
   - 이름: `u123456789_tasks` (예시)
3. **새 사용자 생성**
   - 사용자: `u123456789_taskuser`
   - 비밀번호: 강력한 비밀번호 생성 및 저장
4. **사용자를 데이터베이스에 추가**
   - 모든 권한 부여

### 3단계: 데이터베이스 스키마 가져오기
1. hPanel → **phpMyAdmin** 실행
2. 왼쪽에서 생성한 데이터베이스 선택
3. **가져오기** 탭 클릭
4. `database/schema.sql` 파일 선택
5. **실행** 버튼 클릭
6. (선택사항) 샘플 데이터: `database/seed_data.sql` 가져오기

### 4단계: 데이터베이스 설정
1. 파일 관리자에서 `config/database.php` 편집
2. 데이터베이스 정보 입력:

```php
return [
    'host' => 'localhost',
    'database' => 'u123456789_tasks',        // 생성한 DB 이름
    'username' => 'u123456789_taskuser',     // 생성한 사용자명
    'password' => 'your_password_here',      // 생성한 비밀번호
    // 나머지는 그대로 유지
];
```

3. **저장 후 닫기**

### 5단계: 도메인/하위도메인 설정

#### 옵션 A: 하위도메인 사용 (권장)
1. hPanel → **도메인** → **하위도메인**
2. **하위도메인 생성**
   - 하위도메인: `tasks` (예: tasks.yourdomain.com)
   - 문서 루트: `public_html/task-management-system`
3. **생성** 클릭

#### 옵션 B: 메인 도메인 사용
1. hPanel → **도메인** → **관리**
2. 도메인 클릭
3. **문서 루트** 변경: `public_html/task-management-system`
4. **저장**

### 6단계: 파일 권한 설정
파일 관리자에서:
1. `uploads/` 폴더 → 마우스 오른쪽 버튼 → **권한** → **755**
2. `logs/` 폴더 → 마우스 오른쪽 버튼 → **권한** → **755**

### 7단계: 접속 테스트
1. 브라우저에서 접속: `https://tasks.yourdomain.com`
2. 로그인 페이지가 표시되어야 함

**기본 로그인 정보:**
- 이메일: `admin@example.com`
- 비밀번호: `admin123`

⚠️ **중요:** 첫 로그인 후 즉시 비밀번호 변경!

## 빠른 체크리스트

- [ ] 파일 업로드 완료 (`public_html/task-management-system/`)
- [ ] MySQL 데이터베이스 생성
- [ ] MySQL 사용자 생성 및 권한 부여
- [ ] phpMyAdmin에서 `schema.sql` 가져오기
- [ ] `config/database.php` 수정 (DB 정보)
- [ ] 하위도메인 설정 (문서 루트 지정)
- [ ] `uploads/`, `logs/` 폴더 권한 755 설정
- [ ] 브라우저 접속 테스트
- [ ] 기본 계정으로 로그인 테스트
- [ ] 관리자 비밀번호 변경

## 주요 기능 확인

로그인 후 다음 기능들이 작동하는지 확인:

1. **대시보드**: 통계 표시
2. **Front Desk**: 모든 작업 보기, 새 작업 생성
3. **부서 페이지**: Solidex, COCR, 3D Print 접근
4. **관리자**: 사용자 관리, 시스템 통계
5. **실시간 업데이트**: 다른 브라우저에서 작업 수정 시 자동 반영 (5초마다)

## 문제 해결

### "데이터베이스 연결 실패" 오류
- `config/database.php`의 정보가 정확한지 확인
- phpMyAdmin에서 직접 로그인 테스트
- 사용자 권한이 올바른지 확인

### "500 Internal Server Error" 오류
- hPanel → **파일** → **오류 로그** 확인
- `.htaccess` 파일 문법 오류 확인
- PHP 버전 확인 (7.4 이상 필요)

### 페이지가 표시되지 않음
- 문서 루트 설정 확인
- `index.php` 파일이 루트에 있는지 확인
- 파일 권한 확인 (644 for files, 755 for directories)

### 파일 업로드 안 됨
- `uploads/` 폴더 권한 755 확인
- PHP 설정: `upload_max_filesize` 확인
- `.htaccess`에 업로드 제한 설정 확인

## 추가 설정 (선택사항)

### SSL 인증서 (HTTPS)
1. hPanel → **고급** → **SSL**
2. 무료 SSL 인증서 활성화
3. HTTPS 강제 전환 활성화

### 이메일 알림 (향후 추가 기능)
`config/app.php`에서 SMTP 설정 구성 가능

### 백업 설정
1. hPanel → **백업** 기능 사용
2. 또는 phpMyAdmin에서 수동 백업 (내보내기)

## 도움말

더 자세한 설치 가이드는 `INSTALL.md` 참조
전체 기능 문서는 `README.md` 참조

문제 발생 시:
1. `logs/` 디렉토리 확인
2. hPanel 오류 로그 확인
3. 브라우저 개발자 도구 콘솔 확인

---

## 요약

이제 Hostinger에 최적화된 구조로 변경되었습니다:
- ✅ **index.php가 루트에 위치** - Hostinger 요구사항 충족
- ✅ 모든 경로 수정 완료 - 즉시 사용 가능
- ✅ 간편한 설치 - 7단계로 완료

**업로드만 하면 바로 작동합니다!**
