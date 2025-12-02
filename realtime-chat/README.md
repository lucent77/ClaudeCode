# Realtime Chat

Slack 스타일의 실시간 채팅 애플리케이션입니다. CyberPanel/AlmaLinux9 환경에 최적화되어 있습니다.

## 주요 기능

- **실시간 메시징**: Socket.io를 이용한 즉각적인 메시지 전송
- **채널**: 팀 채널 생성 및 관리
- **다이렉트 메시지**: 1:1 개인 대화
- **타이핑 표시**: 상대방이 입력 중일 때 표시
- **이모지 리액션**: 메시지에 이모지로 반응
- **온라인 상태**: 실시간 사용자 접속 상태 표시
- **메시지 수정/삭제**: 본인 메시지 편집 가능
- **반응형 UI**: Tailwind CSS 기반 모던 디자인

## 기술 스택

- **Backend**: Node.js, Express, Socket.io
- **Database**: SQLite (better-sqlite3, WAL 모드)
- **Frontend**: Vanilla JS, Tailwind CSS
- **Security**: bcrypt, helmet, rate-limiting, XSS 방지

## 시스템 요구사항

- Node.js 18+
- AlmaLinux 9 / CentOS 9 / RHEL 9
- 최소 512MB RAM
- CyberPanel (선택)

## 빠른 시작

### 1. 저장소 클론

```bash
cd /home
git clone <repository-url> realtime-chat
cd realtime-chat
```

### 2. 의존성 설치

```bash
npm install
```

### 3. CSS 빌드

```bash
npm run build:css
```

### 4. 환경 설정

```bash
cp .env.example .env
# .env 파일을 편집하여 설정 변경
```

### 5. 서버 실행

```bash
# 개발 모드
npm run dev

# 프로덕션 모드
npm start
```

서버가 http://localhost:3000 에서 실행됩니다.

## 프로덕션 배포 (CyberPanel)

### 자동 설치

```bash
sudo bash scripts/install.sh
```

### 수동 설치

1. **Node.js 설치**:
```bash
curl -fsSL https://rpm.nodesource.com/setup_20.x | sudo bash -
sudo dnf install -y nodejs
```

2. **PM2 설치**:
```bash
sudo npm install -g pm2
```

3. **애플리케이션 설정**:
```bash
cd /home/realtime-chat
npm install --production
npm run build:css
```

4. **PM2로 실행**:
```bash
pm2 start ecosystem.config.js --env production
pm2 save
pm2 startup
```

### 리버스 프록시 설정

CyberPanel은 OpenLiteSpeed를 사용합니다. `scripts/openlitespeed-proxy.conf` 파일을 참고하여 설정하세요.

#### CyberPanel에서 설정:

1. CyberPanel 관리자 패널 로그인
2. **Websites** > **List Websites** > 도메인 선택 > **Manage**
3. **vHost Conf** 섹션에 프록시 설정 추가
4. **Restart LiteSpeed** 클릭

#### WebSocket 지원 (필수):

Socket.io가 제대로 작동하려면 WebSocket 연결을 위한 프록시 설정이 필요합니다:

```
context /socket.io/ {
  type                    proxy
  handler                 ChatAppServer
  addDefaultCharset       off
}
```

### SSL 인증서

CyberPanel에서 Let's Encrypt SSL 발급:

1. **SSL** > **Issue SSL**
2. 도메인 선택 후 **Issue SSL** 클릭

## 프로젝트 구조

```
realtime-chat/
├── server/
│   ├── index.js          # Express 서버 진입점
│   ├── socket.js         # Socket.io 이벤트 핸들러
│   ├── db/
│   │   └── database.js   # SQLite 데이터베이스
│   ├── middleware/
│   │   └── auth.js       # 인증 미들웨어
│   └── routes/
│       ├── auth.js       # 인증 API
│       ├── channels.js   # 채널 API
│       ├── messages.js   # 메시지 API
│       └── users.js      # 사용자 API
├── public/
│   ├── index.html        # 메인 HTML
│   ├── css/
│   │   ├── input.css     # Tailwind 소스
│   │   └── styles.css    # 빌드된 CSS
│   └── js/
│       └── app.js        # 클라이언트 JavaScript
├── data/                 # SQLite 데이터베이스 파일
├── logs/                 # 로그 파일
├── scripts/              # 설치/배포 스크립트
├── package.json
├── tailwind.config.js
└── ecosystem.config.js   # PM2 설정
```

## API 엔드포인트

### 인증
- `POST /api/auth/register` - 회원가입
- `POST /api/auth/login` - 로그인
- `POST /api/auth/logout` - 로그아웃
- `GET /api/auth/me` - 현재 사용자 정보

### 채널
- `GET /api/channels` - 채널 목록
- `POST /api/channels` - 채널 생성
- `GET /api/channels/:id/messages` - 채널 메시지

### 사용자
- `GET /api/users` - 사용자 목록
- `GET /api/users/:id/messages` - DM 메시지

## Socket.io 이벤트

### 클라이언트 → 서버
- `message:send` - 메시지 전송
- `message:edit` - 메시지 수정
- `message:delete` - 메시지 삭제
- `reaction:add` - 리액션 추가
- `typing:start` / `typing:stop` - 타이핑 상태
- `dm:send` - DM 전송
- `channel:create` - 채널 생성

### 서버 → 클라이언트
- `message:new` - 새 메시지
- `message:edited` - 메시지 수정됨
- `message:deleted` - 메시지 삭제됨
- `reaction:updated` - 리액션 업데이트
- `typing:update` - 타이핑 상태 업데이트
- `user:online` / `user:offline` - 접속 상태

## PM2 명령어

```bash
# 상태 확인
pm2 status

# 로그 보기
pm2 logs realtime-chat

# 재시작
pm2 restart realtime-chat

# 모니터링
pm2 monit

# 메모리/CPU 정보
pm2 info realtime-chat
```

## 보안 고려사항

- 모든 입력값 XSS 필터링 (sanitize-html)
- bcrypt 비밀번호 해싱
- Rate limiting (API 요청 제한)
- CORS 설정
- Helmet 보안 헤더
- SQL Injection 방지 (Prepared statements)

## 성능 최적화

- SQLite WAL 모드로 동시 읽기/쓰기 성능 향상
- PM2 클러스터 모드로 멀티코어 활용
- Gzip 압축
- 정적 파일 캐싱
- WebSocket 연결 유지

## 라이선스

MIT License
