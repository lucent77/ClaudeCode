# Hostinger 배포 가이드

이 가이드는 Swissturn 생산 관리 시스템을 Hostinger 웹 호스팅에 배포하는 방법을 설명합니다.

## 사전 준비

1. Hostinger 호스팅 계정
2. Node.js 18 이상이 설치된 로컬 개발 환경
3. FTP 클라이언트 (FileZilla 권장) 또는 Hostinger 파일 관리자 접근 권한

## 배포 단계

### 1단계: 프로젝트 빌드

로컬 개발 환경에서 프로젝트를 빌드합니다:

```bash
# 프로젝트 디렉토리로 이동
cd swissturn-management

# 의존성 설치 (처음 한 번만)
npm install

# 프로덕션 빌드
npm run build
```

빌드가 완료되면 `dist` 디렉토리가 생성됩니다.

### 2단계: Hostinger 파일 관리자로 업로드

#### 방법 A: Hostinger 파일 관리자 사용

1. Hostinger 계정에 로그인
2. **호스팅** → **웹사이트** → 해당 도메인 선택
3. **파일 관리자(File Manager)** 클릭
4. `public_html` 디렉토리로 이동
5. 기존 파일이 있다면 백업 후 삭제
6. `dist` 폴더 안의 **모든 내용**을 `public_html`에 업로드
   - `index.html`
   - `assets` 폴더
   - 기타 모든 파일

#### 방법 B: FTP 클라이언트 사용 (FileZilla)

1. Hostinger에서 FTP 정보 확인:
   - **호스팅** → **웹사이트** → **FTP 계정**
   - 호스트, 사용자명, 비밀번호 확인

2. FileZilla에서 연결:
   - 호스트: `ftp.yourdomain.com` (Hostinger 제공)
   - 사용자명: FTP 사용자명
   - 비밀번호: FTP 비밀번호
   - 포트: 21

3. 원격 사이트에서 `public_html` 디렉토리로 이동

4. 로컬 사이트에서 `dist` 폴더 열기

5. `dist` 폴더의 모든 내용을 `public_html`로 드래그 앤 드롭

### 3단계: .htaccess 설정 (중요!)

Single Page Application(SPA) 라우팅을 지원하기 위해 `.htaccess` 파일이 필요합니다.

`public_html` 디렉토리에 `.htaccess` 파일을 생성하고 다음 내용을 추가:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteCond %{REQUEST_FILENAME} !-l
  RewriteRule . /index.html [L]
</IfModule>

# Compression
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Browser Caching
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpg "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/gif "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/svg+xml "access plus 1 year"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
  ExpiresByType text/javascript "access plus 1 month"
  ExpiresByType text/html "access plus 0 seconds"
</IfModule>
```

### 4단계: 배포 확인

1. 웹 브라우저에서 도메인 접속 (예: `https://yourdomain.com`)
2. 대시보드가 정상적으로 로드되는지 확인
3. 네비게이션 메뉴를 통해 각 페이지 이동 테스트
   - 대시보드
   - 장비 관리
   - 공구 관리

### 5단계: SSL 인증서 설정 (권장)

1. Hostinger 대시보드에서 **SSL** 메뉴로 이동
2. 무료 Let's Encrypt SSL 인증서 활성화
3. HTTPS 강제 리디렉션 활성화

## 업데이트 배포

시스템을 업데이트해야 할 때:

1. 로컬에서 코드 수정
2. `npm run build` 실행
3. `dist` 폴더의 내용을 다시 `public_html`에 업로드
4. 브라우저 캐시 삭제 후 확인

## 문제 해결

### 페이지가 새로고침되면 404 오류 발생
- `.htaccess` 파일이 올바르게 설정되었는지 확인
- mod_rewrite가 활성화되어 있는지 Hostinger 지원팀에 문의

### CSS나 JavaScript 파일이 로드되지 않음
- `public_html`에 `assets` 폴더가 제대로 업로드되었는지 확인
- 브라우저 개발자 도구(F12)에서 네트워크 탭 확인

### 한글이 깨져서 보임
- 파일 업로드 시 UTF-8 인코딩 유지 확인
- FTP 전송 모드를 Binary로 설정

## 성능 최적화

### 1. Gzip 압축
위의 `.htaccess`에 이미 포함되어 있습니다.

### 2. 브라우저 캐싱
위의 `.htaccess`에 이미 포함되어 있습니다.

### 3. CDN 사용 (선택사항)
Hostinger의 CDN 서비스를 활성화하면 전 세계적으로 빠른 로딩 속도를 얻을 수 있습니다.

## 보안 권장사항

1. **정기적인 백업**: Hostinger의 자동 백업 기능 활성화
2. **SSL 인증서**: HTTPS 사용 (무료 Let's Encrypt)
3. **파일 권한**: 중요 파일은 읽기 전용으로 설정
4. **업데이트**: 정기적으로 시스템 업데이트

## 도메인 연결

### 커스텀 도메인 사용
1. Hostinger에서 도메인 구매 또는 외부 도메인 연결
2. DNS 설정에서 A 레코드가 Hostinger 서버 IP를 가리키도록 설정
3. 전파 시간 대기 (최대 48시간)

## 지원

문제가 발생하면:
1. Hostinger 실시간 채팅 지원
2. Hostinger 티켓 시스템
3. 시스템 관리자에게 문의

---

배포 날짜: 2024
버전: 1.0.0
