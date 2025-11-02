# Hostinger 배포 가이드 (한국어)

## 📋 배포 전 체크리스트

- [ ] Hostinger 계정 및 호스팅 활성화
- [ ] FTP 또는 File Manager 접속 정보 확보
- [ ] MySQL 데이터베이스 생성 완료
- [ ] 데이터베이스 접속 정보 확인
- [ ] Evolution Web Portal 접속 정보 준비

---

## 🚀 Hostinger 배포 방법

### 방법 1: File Manager 사용 (권장)

#### 1단계: 파일 준비

모든 파일을 ZIP으로 압축:
```bash
# 로컬에서 실행
zip -r creodent.zip * .htaccess .gitignore
```

또는 이 폴더의 모든 파일을 선택하여 ZIP으로 압축하세요.

#### 2단계: Hostinger cPanel 접속

1. Hostinger에 로그인
2. 호스팅 계정 선택
3. "File Manager" 클릭

#### 3단계: 파일 업로드

1. File Manager에서 `public_html` 디렉토리로 이동
2. **기존 파일 백업** (있다면)
3. "Upload" 버튼 클릭
4. `creodent.zip` 파일 업로드
5. 업로드 완료 후 ZIP 파일 우클릭 → "Extract" 선택
6. 압축 해제 완료

#### 4단계: 파일 구조 확인

`public_html` 안에 다음과 같은 구조가 있어야 합니다:

```
public_html/
├── index.php          ← 이 파일이 루트에 있어야 함!
├── .htaccess          ← 이 파일도 루트에!
├── install.php
├── app/
├── config/
├── cron/
├── database/
├── storage/
├── vendor/
├── views/
└── ... (기타 파일들)
```

**중요**: `public/` 폴더는 무시하세요. 모든 파일이 `public_html` 루트에 직접 있어야 합니다.

#### 5단계: 권한 설정

File Manager에서 다음 폴더/파일 선택 → 우클릭 → "Permissions":

```
storage/        → 755
storage/logs/   → 755
storage/cache/  → 755
storage/uploads/→ 755
config/         → 755
config/config.php → 644
```

### 방법 2: FTP 사용

#### 1단계: FTP 클라이언트 설치

- FileZilla 다운로드: https://filezilla-project.org/
- 또는 WinSCP (Windows): https://winscp.net/

#### 2단계: FTP 접속 정보 확인

Hostinger cPanel → FTP Accounts:
- **Host**: ftp.yourdomain.com
- **Username**: your_ftp_username
- **Password**: your_ftp_password
- **Port**: 21

#### 3단계: 파일 업로드

1. FTP 클라이언트로 접속
2. 원격: `/public_html` 디렉토리로 이동
3. 로컬: ClaudeCode 폴더의 **모든 파일** 선택
   - `public/` 폴더는 **제외**
   - `.htaccess` 파일 **포함** (숨김 파일 표시 켜기)
4. 모든 파일을 `public_html`로 업로드
5. 업로드 완료 대기 (수 분 소요)

---

## ⚙️ 데이터베이스 설정

### 1단계: MySQL 데이터베이스 확인

Hostinger cPanel → MySQL Databases:

이미 생성된 데이터베이스 정보:
```
Host: 127.0.0.1:3306
Database: u359033001_TOOL
Username: u359033001_TOOL
Password: Creo$10001
```

### 2단계: config.php 확인

`public_html/config/config.php` 파일에 올바른 정보가 있는지 확인:

```php
'database' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'u359033001_TOOL',
    'username' => 'u359033001_TOOL',
    'password' => 'Creo$10001',
],
```

이미 올바르게 설정되어 있습니다!

---

## 🔧 설치 실행

### 1단계: 설치 페이지 접속

브라우저에서 다음 주소로 이동:
```
http://yourdomain.com/install.php
```

또는 IP 주소 사용:
```
http://your-ip-address/install.php
```

### 2단계: 설치 마법사 진행

1. **Welcome 화면**
   - "Start Installation" 클릭

2. **Requirements Check**
   - 모든 항목이 ✓ Passed 인지 확인
   - 실패 항목이 있으면 Hostinger 지원팀에 문의
   - "Continue to Installation" 클릭

3. **Installing...**
   - 자동으로 진행됩니다
   - 데이터베이스 테이블 생성
   - 기본 데이터 입력
   - 관리자 계정 생성

4. **완료!**
   - 기본 관리자 정보 확인:
     ```
     Username: admin
     Password: Admin@123
     ```
   - **"Go to Login"** 클릭

### 3단계: 첫 로그인 및 비밀번호 변경

1. 위 정보로 로그인
2. **즉시 비밀번호 변경!** (보안상 필수)
3. Dashboard 확인

### 4단계: install.php 삭제

보안을 위해 **반드시** 삭제:
- File Manager 또는 FTP에서 `install.php` 파일 삭제

---

## 🔗 Evolution Portal 연동 설정

### 1단계: config.php 수정

File Manager에서 `config/config.php` 파일 편집:

```php
'evolution' => [
    'base_url' => 'https://your-actual-evolution-portal-url.com',
    'username' => 'your_evolution_username',
    'password' => 'your_evolution_password',
    'timeout' => 30,
    'retry_count' => 3,
    'retry_delay' => 2,
],
```

**실제 정보로 변경하세요!**

### 2단계: 연결 테스트

1. 로그인 후 **Import** 메뉴로 이동
2. "Test Connection" 버튼 클릭
3. "Successfully connected" 메시지 확인

### 3단계: 첫 데이터 가져오기

1. Import 페이지에서 날짜 범위 선택 (예: 최근 7일)
2. "Import Now" 클릭
3. 완료 메시지 확인
4. Dashboard 또는 Cases 페이지에서 데이터 확인

---

## ⏰ Cron Job 설정 (자동 가져오기)

### Hostinger cPanel에서 설정

1. **cPanel → Advanced → Cron Jobs** 이동

2. **새 Cron Job 추가**:

   - **Type**: Custom
   - **Minute**: `0`
   - **Hour**: `*`
   - **Day**: `*`
   - **Month**: `*`
   - **Weekday**: `*`
   - **Command**:
     ```bash
     /usr/bin/php /home/u359033001/public_html/cron/import_evo.php >> /home/u359033001/public_html/storage/logs/cron.log 2>&1
     ```

   **주의**: `/home/u359033001/` 부분을 **실제 경로**로 변경!

   실제 경로 확인 방법:
   - File Manager에서 `public_html` 폴더 클릭
   - 상단에 표시되는 전체 경로 복사

3. **"Add New Cron Job"** 클릭

4. **테스트**:
   - 1시간 후 Import → History에서 자동 실행 확인

---

## ✅ 배포 후 확인사항

### 필수 체크리스트

- [ ] 메인 페이지 접속 가능 (`http://yourdomain.com`)
- [ ] 로그인 가능
- [ ] Dashboard 데이터 표시
- [ ] Cases 페이지 작동
- [ ] Evolution Portal 연결 성공
- [ ] 데이터 가져오기 성공
- [ ] 사용자 추가 가능 (Admin → Users)
- [ ] 케이스 생성 가능
- [ ] 케이스 할당 가능
- [ ] Audit Log 기록됨
- [ ] Cron Job 실행 확인 (1시간 후)
- [ ] install.php 삭제 완료
- [ ] 관리자 비밀번호 변경 완료

### 에러 발생 시 확인

#### "Database connection failed"
```bash
# config/config.php 확인
- Host가 127.0.0.1 인지
- Database 이름, 사용자명, 비밀번호 정확한지
- Hostinger cPanel → MySQL에서 데이터베이스 존재 확인
```

#### "500 Internal Server Error"
```bash
# .htaccess 문제 가능성
- File Manager에서 .htaccess 파일 존재 확인
- 권한이 644 인지 확인
- Error logs 확인: Hostinger cPanel → Error Logs
```

#### "404 Not Found" (모든 페이지)
```bash
# mod_rewrite 문제
- .htaccess 파일이 public_html 루트에 있는지 확인
- Hostinger 지원팀에 mod_rewrite 활성화 요청
```

#### 로그 확인 방법
```bash
# File Manager에서:
1. storage/logs/app.log - 애플리케이션 에러
2. storage/logs/import_YYYY-MM-DD.log - 가져오기 로그

# Hostinger cPanel:
- Error Logs 메뉴에서 Apache 에러 확인
```

---

## 🎯 다음 단계

### 1. 사용자 추가
1. Admin → Users → "+ New User"
2. 정보 입력 (username, password, name, department, role)
3. Create

### 2. 부서 확인
1. Admin → Departments
2. 기본 부서 확인:
   - SOLIDEX
   - 3DPRINT
   - COCR
   - KOREA
   - QC
   - ADMIN

### 3. 데이터 마이그레이션 (Google Sheets)

기존 Google Sheets 데이터가 있다면:

**옵션 A: Evolution Portal 통해 가져오기**
- Import 메뉴에서 과거 날짜까지 범위 확대
- 한 번에 최대 30일 권장

**옵션 B: API를 통해 직접 입력**
- API 토큰 생성 (SQL 직접 실행 필요)
- VB.NET 또는 스크립트로 데이터 POST

**옵션 C: SQL 직접 입력**
- Google Sheets → CSV 내보내기
- CSV → SQL INSERT 변환
- phpMyAdmin에서 실행

### 4. 직원 교육

1. **작업자용 매뉴얼**
   - 로그인 방법
   - 할당된 케이스 확인
   - 상태 업데이트
   - 노트 추가

2. **관리자용 매뉴얼**
   - 케이스 생성
   - 작업자 할당
   - 진행상황 모니터링
   - 보고서 확인

### 5. 백업 설정

**데이터베이스 백업 (매주)**
```bash
# Hostinger cPanel → Backups
# 또는 phpMyAdmin에서 수동 Export
```

**파일 백업 (매월)**
```bash
# File Manager에서 전체 압축
# 다운로드하여 안전한 곳에 보관
```

---

## 🔐 보안 권장사항

### 즉시 실행

1. ✅ 기본 admin 비밀번호 변경
2. ✅ install.php 삭제
3. ✅ config.php 권한 644로 설정
4. ✅ storage/ 폴더 권한 755로 설정

### 가능하면 적용

1. **SSL 인증서 설치**
   - Hostinger에서 무료 SSL 제공
   - cPanel → SSL/TLS → Let's Encrypt
   - 설치 후 `.htaccess`에서 HTTPS 리다이렉트 활성화

2. **정기 비밀번호 변경**
   - 모든 사용자 3개월마다

3. **IP 제한** (선택사항)
   - Admin 페이지는 회사 IP만 접속
   - `.htaccess`에 IP 화이트리스트 추가

4. **보안 업데이트**
   - PHP 버전 최신 유지
   - MySQL 버전 최신 유지

---

## 📞 문제 해결 연락처

### Hostinger 지원
- **웹사이트**: https://www.hostinger.com/support
- **라이브 챗**: cPanel 내 채팅 버튼
- **이메일**: support@hostinger.com

### 기술 지원
- **이메일**: admin@creodent.com
- **문서**: README.md, QUICK_START.md 참조

---

## 📝 배포 체크리스트 (인쇄용)

```
□ 파일 업로드 완료 (public_html에 직접)
□ 권한 설정 완료 (storage: 755)
□ install.php 실행 및 삭제
□ 관리자 로그인 성공
□ 비밀번호 변경 완료
□ config.php에 Evolution 정보 입력
□ Evolution 연결 테스트 성공
□ 첫 데이터 가져오기 성공
□ Cron Job 설정 완료
□ SSL 인증서 설치 (선택)
□ 팀원들에게 계정 생성
□ 사용 교육 진행
□ 백업 일정 수립
□ 모든 기능 테스트 완료
```

---

## 🎉 배포 완료!

모든 단계를 완료했다면 시스템 사용 준비가 끝났습니다!

**접속 주소**: `http://yourdomain.com`

**다음 읽어볼 문서**:
- `README.md` - 전체 시스템 문서
- `QUICK_START.md` - 빠른 사용 가이드
- `FIELD_MAPPINGS.md` - 데이터 구조 참조

---

**마지막 업데이트**: 2025년 11월 2일
**버전**: 1.0.0
