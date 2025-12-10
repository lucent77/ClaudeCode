# Google Sheets 데이터 입력 Chrome 확장 프로그램

Google Sheets에 데이터를 입력하는 Chrome 확장 프로그램 테스트 프로젝트입니다.

## 목차

1. [프로젝트 구조](#프로젝트-구조)
2. [작동 원리](#작동-원리)
3. [Google Cloud Console 설정](#google-cloud-console-설정)
4. [확장 프로그램 설치](#확장-프로그램-설치)
5. [사용 방법](#사용-방법)
6. [코드 설명](#코드-설명)
7. [문제 해결](#문제-해결)

---

## 프로젝트 구조

```
google-sheets-extension/
├── manifest.json          # 확장 프로그램 설정 파일 (필수)
├── popup.html            # 팝업 UI
├── popup.js              # 팝업 로직
├── background.js         # 백그라운드 서비스 워커
├── icons/                # 아이콘 폴더
│   ├── generate-icons.html  # 아이콘 생성 도구
│   ├── icon16.png        # 16x16 아이콘
│   ├── icon48.png        # 48x48 아이콘
│   └── icon128.png       # 128x128 아이콘
└── README.md             # 이 파일
```

---

## 작동 원리

### 1. Chrome 확장 프로그램 구조

```
┌─────────────────────────────────────────────────────────────┐
│                    Chrome 브라우저                           │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐   │
│  │  manifest   │────▶│   popup     │────▶│ background  │   │
│  │   .json     │     │  .html/.js  │     │    .js      │   │
│  │  (설정)     │     │  (UI/로직)   │     │ (백그라운드) │   │
│  └─────────────┘     └──────┬──────┘     └─────────────┘   │
│                             │                               │
│                             ▼                               │
│                    ┌─────────────────┐                      │
│                    │ Chrome Identity │                      │
│                    │      API        │                      │
│                    │  (OAuth 인증)    │                      │
│                    └────────┬────────┘                      │
└─────────────────────────────┼───────────────────────────────┘
                              │
                              ▼
                    ┌─────────────────┐
                    │  Google Sheets  │
                    │      API        │
                    │   (데이터 R/W)   │
                    └─────────────────┘
```

### 2. 인증 플로우

```
사용자                  확장 프로그램              Google
  │                         │                      │
  │  1. 로그인 버튼 클릭     │                      │
  │ ──────────────────────▶ │                      │
  │                         │                      │
  │                         │  2. OAuth 토큰 요청   │
  │                         │ ────────────────────▶│
  │                         │                      │
  │  3. Google 로그인 팝업   │◀─────────────────────│
  │◀─────────────────────── │                      │
  │                         │                      │
  │  4. 권한 승인            │                      │
  │ ──────────────────────▶ │                      │
  │                         │                      │
  │                         │  5. Access Token     │
  │                         │◀─────────────────────│
  │                         │                      │
  │  6. 로그인 완료!         │                      │
  │◀─────────────────────── │                      │
```

### 3. 데이터 추가 플로우

```
사용자 입력:
"홍길동, 25, 서울
김철수, 30, 부산"
        │
        ▼
┌───────────────────┐
│   데이터 파싱      │
│   (줄→행, 쉼표→열) │
└─────────┬─────────┘
          │
          ▼
┌───────────────────┐
│   2차원 배열 생성   │
│ [                 │
│  ["홍길동","25","서울"], │
│  ["김철수","30","부산"]  │
│ ]                 │
└─────────┬─────────┘
          │
          ▼
┌───────────────────┐
│ Google Sheets API │
│    POST 요청      │
└─────────┬─────────┘
          │
          ▼
    스프레드시트에
    데이터 추가됨!
```

---

## Google Cloud Console 설정

### 단계 1: 프로젝트 생성

1. [Google Cloud Console](https://console.cloud.google.com/) 접속
2. 상단의 프로젝트 선택 버튼 클릭
3. **"새 프로젝트"** 클릭
4. 프로젝트 이름 입력 (예: `sheets-extension-test`)
5. **"만들기"** 클릭

### 단계 2: Google Sheets API 활성화

1. 좌측 메뉴에서 **"API 및 서비스"** → **"라이브러리"** 클릭
2. 검색창에 **"Google Sheets API"** 입력
3. **"Google Sheets API"** 선택
4. **"사용"** 버튼 클릭하여 API 활성화

### 단계 3: OAuth 동의 화면 설정

1. 좌측 메뉴에서 **"API 및 서비스"** → **"OAuth 동의 화면"** 클릭
2. User Type에서 **"외부"** 선택 후 **"만들기"**
3. 앱 정보 입력:
   - 앱 이름: `Google Sheets Extension`
   - 사용자 지원 이메일: 본인 이메일
   - 개발자 연락처 정보: 본인 이메일
4. **"저장 후 계속"** 클릭
5. 범위(Scopes) 페이지에서:
   - **"범위 추가 또는 삭제"** 클릭
   - `https://www.googleapis.com/auth/spreadsheets` 검색하여 선택
   - **"업데이트"** 클릭
6. **"저장 후 계속"** 클릭
7. 테스트 사용자 추가 (본인 Gmail 추가)
8. **"저장 후 계속"** 클릭

### 단계 4: OAuth 클라이언트 ID 생성

1. 좌측 메뉴에서 **"API 및 서비스"** → **"사용자 인증 정보"** 클릭
2. 상단 **"+ 사용자 인증 정보 만들기"** → **"OAuth 클라이언트 ID"** 선택
3. 애플리케이션 유형: **"Chrome 앱"** 선택
4. 이름 입력: `Sheets Extension Client`
5. 애플리케이션 ID 입력:

   **중요: 확장 프로그램 ID 찾는 방법**
   - Chrome에서 `chrome://extensions/` 접속
   - 우측 상단 **"개발자 모드"** 활성화
   - 확장 프로그램 로드 후 표시되는 ID 복사
   - (형식: `abcdefghijklmnopqrstuvwxyzabcdef`)

6. **"만들기"** 클릭
7. 생성된 **클라이언트 ID** 복사 (형식: `123456789-xxxxx.apps.googleusercontent.com`)

### 단계 5: manifest.json 수정

`manifest.json` 파일을 열고 `oauth2.client_id`를 수정:

```json
"oauth2": {
  "client_id": "여기에_복사한_클라이언트_ID_붙여넣기.apps.googleusercontent.com",
  "scopes": [
    "https://www.googleapis.com/auth/spreadsheets"
  ]
}
```

---

## 확장 프로그램 설치

### 단계 1: 아이콘 생성

1. `icons/generate-icons.html` 파일을 브라우저에서 엽니다
2. **"모든 아이콘 다운로드"** 버튼 클릭
3. 다운로드된 PNG 파일들을 `icons` 폴더에 저장
   - `icon16.png`
   - `icon48.png`
   - `icon128.png`

### 단계 2: Chrome에 설치

1. Chrome 브라우저에서 `chrome://extensions/` 접속
2. 우측 상단 **"개발자 모드"** 토글 활성화
3. **"압축해제된 확장 프로그램을 로드합니다"** 클릭
4. `google-sheets-extension` 폴더 선택
5. 확장 프로그램이 설치되고 ID가 표시됩니다

### 단계 3: 클라이언트 ID 업데이트 (최초 1회)

1. 설치된 확장 프로그램의 ID 복사
2. Google Cloud Console로 돌아가서 OAuth 클라이언트 ID의 애플리케이션 ID 수정
3. Chrome에서 확장 프로그램 새로고침 (리로드 버튼 클릭)

---

## 사용 방법

### 1. 테스트용 Google Sheets 만들기

1. [Google Sheets](https://sheets.google.com) 접속
2. **"새 스프레드시트"** 생성
3. URL에서 스프레드시트 ID 복사:
   ```
   https://docs.google.com/spreadsheets/d/[이_부분이_ID]/edit
   ```
   예: `1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms`

### 2. 확장 프로그램 사용

1. Chrome 툴바에서 확장 프로그램 아이콘 클릭
2. **"Google 계정으로 로그인"** 클릭
3. Google 계정 선택 및 권한 승인
4. 스프레드시트 ID 입력
5. 시트 이름 입력 (기본: Sheet1)
6. 데이터 입력:
   ```
   이름, 나이, 도시
   홍길동, 25, 서울
   김철수, 30, 부산
   ```
7. **"데이터 추가하기"** 클릭

### 3. 결과 확인

- Google Sheets로 돌아가서 데이터가 추가되었는지 확인
- 확장 프로그램 하단의 **"실행 로그"**에서 API 호출 결과 확인

---

## 코드 설명

### manifest.json 주요 항목

| 항목 | 설명 |
|------|------|
| `manifest_version` | Manifest 버전 (현재 3이 최신) |
| `permissions` | 필요한 Chrome API 권한 |
| `host_permissions` | 접근할 외부 URL 패턴 |
| `oauth2` | Google OAuth 설정 |
| `action` | 툴바 아이콘 및 팝업 설정 |
| `background` | 백그라운드 서비스 워커 |

### 주요 API

#### Chrome Identity API
```javascript
// 토큰 요청 (로그인)
chrome.identity.getAuthToken({ interactive: true }, (token) => {
  // token을 사용하여 API 호출
});

// 토큰 제거 (로그아웃)
chrome.identity.removeCachedAuthToken({ token: token }, () => {
  // 로그아웃 완료
});
```

#### Google Sheets API

**데이터 추가 (Append):**
```javascript
POST https://sheets.googleapis.com/v4/spreadsheets/{spreadsheetId}/values/{range}:append
Headers:
  Authorization: Bearer {accessToken}
  Content-Type: application/json
Body:
  { "values": [["데이터1", "데이터2"], ["데이터3", "데이터4"]] }
```

**데이터 읽기 (Get):**
```javascript
GET https://sheets.googleapis.com/v4/spreadsheets/{spreadsheetId}/values/{range}
Headers:
  Authorization: Bearer {accessToken}
```

---

## 문제 해결

### "OAuth2 not granted or revoked" 오류

1. Google Cloud Console에서 OAuth 동의 화면의 테스트 사용자에 본인 이메일 추가
2. Chrome에서 확장 프로그램 삭제 후 다시 설치

### "Invalid client_id" 오류

1. manifest.json의 client_id가 정확한지 확인
2. OAuth 클라이언트 ID의 애플리케이션 ID가 확장 프로그램 ID와 일치하는지 확인

### "Request had insufficient authentication scopes" 오류

1. Google Cloud Console에서 OAuth 동의 화면의 범위에 `spreadsheets` 추가
2. Chrome에서 로그아웃 후 다시 로그인

### 데이터가 추가되지 않는 경우

1. 스프레드시트 ID가 정확한지 확인
2. 시트 이름이 정확한지 확인 (대소문자 구분)
3. 스프레드시트가 본인 계정으로 접근 가능한지 확인

---

## 다음 단계

이 테스트 프로젝트를 기반으로 확장할 수 있는 기능들:

1. **Content Script 추가**: 웹 페이지의 데이터를 자동으로 수집
2. **Context Menu**: 우클릭 메뉴로 선택한 텍스트 저장
3. **자동 동기화**: 주기적으로 데이터 백업
4. **다중 스프레드시트**: 여러 스프레드시트 관리
5. **데이터 포맷팅**: 셀 스타일, 수식 지원

---

## 라이선스

MIT License - 자유롭게 수정하고 사용하세요!
