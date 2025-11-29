# ⚙️ DentalFlow - 기술 사양서 (TRD)

> **문서 버전**: 1.0
> **작성일**: 2025-11-29
> **프로젝트명**: DentalFlow

---

## 1. 권장 기술 스택

### 1.1 핵심 스택 요약

| 계층 | 기술 | 버전 | 비고 |
|------|------|------|------|
| **Framework** | Next.js (App Router) | 15.x | React 19 기반 |
| **Language** | TypeScript | 5.x | 타입 안정성 |
| **Styling** | Tailwind CSS | 4.x | CSS-first 설정 |
| **UI Components** | shadcn/ui | latest | Tailwind 기반 컴포넌트 |
| **Database** | PostgreSQL | 16.x | 관계형 DB |
| **ORM** | Prisma | 6.x | 안정 버전 권장 |
| **Authentication** | Auth.js (NextAuth v5) | 5.x | 역할 기반 인증 |
| **Real-time** | Socket.io | 4.x | WebSocket 통신 |
| **State Management** | Zustand | 5.x | 경량 상태 관리 |
| **Charts** | Recharts | 2.x | 대시보드 차트 |
| **Hosting** | Hostinger VPS | - | Ubuntu 22.04 LTS |

---

## 2. 선정 이유

### 2.1 Next.js 15 선정 근거

| 장점 | 설명 |
|------|------|
| **AI 코딩 최적화** | 가장 많은 학습 데이터, AI가 실수할 확률 최저 |
| **풀스택** | API Routes로 백엔드 분리 불필요 |
| **성능** | Turbopack으로 개발 속도 76% 향상 (공식 발표) |
| **React 19** | 최신 React 기능 네이티브 지원 |
| **App Router** | 파일 기반 라우팅으로 구조 명확 |

**출처**: [Next.js 15 공식 블로그](https://nextjs.org/blog/next-15)

### 2.2 실시간 기능: Socket.io 선정 근거

| 비교 항목 | Socket.io | Supabase Realtime |
|-----------|-----------|-------------------|
| 저수준 제어 | ✅ 높음 | ⚠️ 제한적 |
| VPS 자체 호스팅 | ✅ 용이 | ❌ 복잡 |
| 데이터베이스 독립 | ✅ PostgreSQL과 분리 | ❌ PostgreSQL 종속 |
| 80명 동시 접속 | ✅ 검증됨 | ✅ 가능 |

**결론**: Hostinger VPS 환경에서 직접 운영하기에 Socket.io가 더 적합

**출처**: [Socket.IO vs Supabase 비교](https://ably.com/compare/socketio-vs-supabase)

### 2.3 Tailwind CSS v4 선정 근거

- 2025년 1월 정식 출시, 안정화 완료
- 빌드 속도 5배 향상 (Rust 기반 엔진)
- CSS-first 설정으로 구성 단순화
- shadcn/ui와 완벽 호환

**출처**: [Tailwind CSS v4.0](https://tailwindcss.com/blog/tailwindcss-v4)

---

## 3. 핵심 아키텍처

### 3.1 시스템 아키텍처 다이어그램

```
┌─────────────────────────────────────────────────────────────────┐
│                        Hostinger VPS                            │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                    Docker Compose                         │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐   │   │
│  │  │  Next.js    │  │  Socket.io  │  │   PostgreSQL    │   │   │
│  │  │  (Port 3000)│  │  (Port 3001)│  │   (Port 5432)   │   │   │
│  │  └──────┬──────┘  └──────┬──────┘  └────────┬────────┘   │   │
│  │         │                │                   │            │   │
│  │         └────────────────┼───────────────────┘            │   │
│  │                          │                                │   │
│  │                    ┌─────▼─────┐                          │   │
│  │                    │   Redis   │ (세션/캐시)               │   │
│  │                    │(Port 6379)│                          │   │
│  │                    └───────────┘                          │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─────────────┐                                                │
│  │    Nginx    │ (Reverse Proxy + SSL)                          │
│  │  (Port 80/443)                                               │
│  └─────────────┘                                                │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
                    ┌─────────────────┐
                    │   사용자 브라우저   │
                    │   (80명 동시접속)  │
                    └─────────────────┘
```

### 3.2 실시간 동기화 흐름

```
[사용자 A]                    [서버]                    [사용자 B]
    │                           │                           │
    │  1. 케이스 상태 변경        │                           │
    ├──────────────────────────►│                           │
    │                           │  2. DB 업데이트 (Prisma)   │
    │                           │                           │
    │                           │  3. Socket.io 브로드캐스트  │
    │                           ├──────────────────────────►│
    │                           │                           │
    │  4. 성공 응답              │  5. 실시간 화면 업데이트    │
    │◄──────────────────────────│                           │
```

---

## 4. 프로젝트 디렉토리 구조

```
dentalflow/
├── .env.local                    # 환경변수 (Git 제외)
├── .env.example                  # 환경변수 템플릿
├── docker-compose.yml            # Docker 구성
├── next.config.ts                # Next.js 설정
├── tailwind.config.ts            # Tailwind 설정
├── tsconfig.json                 # TypeScript 설정
├── package.json
│
├── prisma/
│   ├── schema.prisma             # DB 스키마 정의
│   ├── migrations/               # 마이그레이션 파일
│   └── seed.ts                   # 초기 데이터
│
├── src/
│   ├── app/                      # App Router (라우팅)
│   │   ├── layout.tsx            # 루트 레이아웃
│   │   ├── page.tsx              # 홈 (로그인 리다이렉트)
│   │   ├── globals.css           # 글로벌 스타일
│   │   │
│   │   ├── (auth)/               # 인증 그룹
│   │   │   ├── login/
│   │   │   │   └── page.tsx
│   │   │   └── layout.tsx
│   │   │
│   │   ├── (dashboard)/          # 대시보드 그룹 (인증 필요)
│   │   │   ├── layout.tsx        # 사이드바 포함 레이아웃
│   │   │   ├── dashboard/
│   │   │   │   └── page.tsx      # 관리자 대시보드
│   │   │   ├── cases/
│   │   │   │   ├── page.tsx      # 케이스 목록
│   │   │   │   ├── [id]/
│   │   │   │   │   └── page.tsx  # 케이스 상세
│   │   │   │   └── new/
│   │   │   │       └── page.tsx  # 케이스 생성
│   │   │   ├── users/
│   │   │   │   └── page.tsx      # 사용자 관리 (관리자)
│   │   │   └── settings/
│   │   │       └── page.tsx      # 설정
│   │   │
│   │   └── api/                   # API Routes
│   │       ├── auth/
│   │       │   └── [...nextauth]/
│   │       │       └── route.ts
│   │       ├── cases/
│   │       │   ├── route.ts       # GET, POST
│   │       │   └── [id]/
│   │       │       └── route.ts   # GET, PUT, DELETE
│   │       ├── users/
│   │       │   └── route.ts
│   │       └── stats/
│   │           └── route.ts       # 통계 API
│   │
│   ├── components/                # 재사용 컴포넌트
│   │   ├── ui/                    # shadcn/ui 컴포넌트
│   │   │   ├── button.tsx
│   │   │   ├── input.tsx
│   │   │   ├── select.tsx
│   │   │   ├── table.tsx
│   │   │   ├── card.tsx
│   │   │   ├── dialog.tsx
│   │   │   └── ...
│   │   ├── layout/                # 레이아웃 컴포넌트
│   │   │   ├── sidebar.tsx
│   │   │   ├── header.tsx
│   │   │   └── nav-user.tsx
│   │   ├── cases/                 # 케이스 관련 컴포넌트
│   │   │   ├── case-table.tsx
│   │   │   ├── case-form.tsx
│   │   │   ├── case-status-badge.tsx
│   │   │   └── case-filter.tsx
│   │   └── dashboard/             # 대시보드 컴포넌트
│   │       ├── stats-cards.tsx
│   │       ├── department-chart.tsx
│   │       └── delayed-cases-list.tsx
│   │
│   ├── lib/                       # 유틸리티 & 설정
│   │   ├── prisma.ts              # Prisma 클라이언트
│   │   ├── auth.ts                # Auth.js 설정
│   │   ├── auth.config.ts         # Auth 콜백 (미들웨어용)
│   │   ├── socket.ts              # Socket.io 클라이언트
│   │   └── utils.ts               # 공통 유틸
│   │
│   ├── hooks/                     # 커스텀 훅
│   │   ├── use-socket.ts          # Socket.io 훅
│   │   ├── use-cases.ts           # 케이스 데이터 훅
│   │   └── use-realtime.ts        # 실시간 업데이트 훅
│   │
│   ├── stores/                    # Zustand 스토어
│   │   ├── case-store.ts
│   │   └── user-store.ts
│   │
│   ├── types/                     # TypeScript 타입
│   │   ├── case.ts
│   │   ├── user.ts
│   │   └── index.ts
│   │
│   └── middleware.ts              # Next.js 미들웨어 (인증)
│
├── server/                        # Socket.io 서버 (별도)
│   ├── index.ts
│   └── handlers/
│       └── case-handlers.ts
│
└── public/
    └── logo.svg
```

**출처**: [Next.js 15 프로젝트 구조 가이드](https://dev.to/bajrayejoon/best-practices-for-organizing-your-nextjs-15-2025-53ji)

---

## 5. 데이터베이스 스키마 (Prisma)

```prisma
// prisma/schema.prisma

generator client {
  provider = "prisma-client-js"
}

datasource db {
  provider = "postgresql"
  url      = env("DATABASE_URL")
}

// ===== 사용자 관련 =====

enum Role {
  ADMIN
  WORKER
}

enum Department {
  FRONT_DESK
  PRE_CAD
  SOLIDEX_DESIGN
  COCR_DESIGN
  PRINT_3D_DESIGN
  PRE_CAM
  SOLIDEX
  COCR
  PRINT_3D
}

model User {
  id            String      @id @default(cuid())
  email         String      @unique
  password      String      // bcrypt 해시
  name          String
  role          Role        @default(WORKER)
  department    Department
  createdAt     DateTime    @default(now())
  updatedAt     DateTime    @updatedAt

  // Relations
  casesCreated  Case[]      @relation("CreatedBy")
  caseHistories CaseHistory[]
}

// ===== 케이스 관련 =====

enum CaseStatus {
  PENDING     // 대기중
  IN_PROGRESS // 진행중
  COMPLETED   // 완료
  ON_HOLD     // 보류
  CANCELLED   // 취소
}

model Case {
  id              String      @id @default(cuid())
  caseNumber      String      @unique // 자동생성: YYYYMMDD-XXXX

  // 기본 정보
  panNumber       String?
  labDoctorName   String
  patientName     String
  quantity        Int         @default(1)
  toothNumbers    String[]    // 치아 번호 배열
  toothColor      String?
  implantType     String?
  dueDate         DateTime

  // 작업 유형
  cocrType        String?
  solidexType     String?
  print3dType     String?

  // 노트 (다중 선택 + 자유 텍스트)
  noteOptions     String[]    // 선택된 옵션들
  noteText        String?     // 자유 텍스트

  // 상태 및 부서
  status          CaseStatus  @default(PENDING)
  currentDepartment Department @default(FRONT_DESK)

  // 워크플로우 (이 케이스가 거쳐야 할 부서들)
  workflow        Department[]

  // 낙관적 잠금
  version         Int         @default(1)

  // 타임스탬프
  createdAt       DateTime    @default(now())
  updatedAt       DateTime    @updatedAt

  // Relations
  createdBy       User        @relation("CreatedBy", fields: [createdById], references: [id])
  createdById     String
  histories       CaseHistory[]
  departmentStatuses DepartmentStatus[]

  @@index([caseNumber])
  @@index([status])
  @@index([currentDepartment])
  @@index([dueDate])
}

// 부서별 작업 상태 추적
model DepartmentStatus {
  id            String      @id @default(cuid())
  caseId        String
  department    Department
  status        CaseStatus  @default(PENDING)
  startedAt     DateTime?
  completedAt   DateTime?

  case          Case        @relation(fields: [caseId], references: [id], onDelete: Cascade)

  @@unique([caseId, department])
}

// 케이스 변경 이력
model CaseHistory {
  id          String      @id @default(cuid())
  caseId      String
  userId      String
  action      String      // CREATE, UPDATE, STATUS_CHANGE, DEPARTMENT_CHANGE
  changes     Json        // 변경 내역
  createdAt   DateTime    @default(now())

  case        Case        @relation(fields: [caseId], references: [id], onDelete: Cascade)
  user        User        @relation(fields: [userId], references: [id])

  @@index([caseId])
  @@index([createdAt])
}

// NOTE 옵션 마스터
model NoteOption {
  id        String   @id @default(cuid())
  label     String   @unique
  isActive  Boolean  @default(true)
  sortOrder Int      @default(0)
}
```

---

## 6. 필수 라이브러리 목록

### 6.1 package.json dependencies

```json
{
  "dependencies": {
    "next": "^15.0.0",
    "react": "^19.0.0",
    "react-dom": "^19.0.0",

    "@prisma/client": "^6.0.0",
    "next-auth": "^5.0.0-beta.25",
    "@auth/prisma-adapter": "^2.0.0",

    "socket.io-client": "^4.7.0",

    "zustand": "^5.0.0",
    "zod": "^3.23.0",
    "bcryptjs": "^2.4.3",

    "recharts": "^2.12.0",
    "date-fns": "^3.6.0",
    "clsx": "^2.1.0",
    "tailwind-merge": "^2.3.0",

    "@radix-ui/react-dialog": "^1.0.0",
    "@radix-ui/react-select": "^2.0.0",
    "@radix-ui/react-dropdown-menu": "^2.0.0",
    "lucide-react": "^0.400.0",
    "class-variance-authority": "^0.7.0"
  },
  "devDependencies": {
    "typescript": "^5.5.0",
    "@types/node": "^20.0.0",
    "@types/react": "^19.0.0",
    "@types/bcryptjs": "^2.4.0",

    "prisma": "^6.0.0",
    "tailwindcss": "^4.0.0",
    "@tailwindcss/postcss": "^4.0.0",

    "eslint": "^9.0.0",
    "eslint-config-next": "^15.0.0"
  }
}
```

### 6.2 Socket.io 서버 (별도 패키지)

```json
{
  "dependencies": {
    "socket.io": "^4.7.0",
    "ioredis": "^5.4.0"
  }
}
```

---

## 7. AI 코딩 주의사항 ⚠️

### 7.1 Next.js 15 App Router 관련

| 규칙 | 설명 |
|------|------|
| **'use client' 최소화** | 가능한 서버 컴포넌트 유지. 클라이언트 컴포넌트는 인터랙션 필요 시에만 |
| **비동기 API 변경** | `cookies()`, `headers()`, `params`가 이제 `async`/`await` 필요 |
| **캐싱 기본값 변경** | `fetch`는 기본적으로 캐싱 안 됨. 필요 시 `cache: 'force-cache'` 명시 |
| **Server Actions 활용** | 폼 제출은 API Route 대신 Server Actions 우선 검토 |

**출처**: [Next.js 15 마이그레이션 가이드](https://nextjs.org/blog/next-15)

### 7.2 Auth.js v5 역할 기반 인증

```typescript
// ❌ 잘못된 방식 - 미들웨어에서 role 접근 불가
// middleware.ts
export default auth((req) => {
  const role = req.auth?.user?.role; // undefined!
});

// ✅ 올바른 방식 - auth.config.ts에서 callbacks 정의
// lib/auth.config.ts
export const authConfig = {
  callbacks: {
    jwt({ token, user }) {
      if (user) {
        token.role = user.role;
        token.department = user.department;
      }
      return token;
    },
    session({ session, token }) {
      session.user.role = token.role;
      session.user.department = token.department;
      return session;
    }
  }
};
```

**출처**: [Auth.js RBAC 가이드](https://authjs.dev/guides/role-based-access-control)

### 7.3 Prisma 낙관적 잠금

```typescript
// 동시 수정 충돌 방지
async function updateCase(id: string, data: UpdateData, expectedVersion: number) {
  const result = await prisma.case.updateMany({
    where: {
      id,
      version: expectedVersion  // 버전 체크
    },
    data: {
      ...data,
      version: { increment: 1 }  // 버전 증가
    }
  });

  if (result.count === 0) {
    throw new Error('CONFLICT: 다른 사용자가 수정했습니다. 새로고침 후 다시 시도하세요.');
  }
}
```

### 7.4 Tailwind CSS v4 설정

```css
/* src/app/globals.css */
@import "tailwindcss";

/* CSS-first 설정 (tailwind.config.ts 불필요) */
@theme {
  --color-primary: #0066cc;
  --color-secondary: #6b7280;
  --font-family-sans: "Pretendard", "Inter", sans-serif;
}
```

**출처**: [Tailwind CSS v4 문서](https://tailwindcss.com/blog/tailwindcss-v4)

### 7.5 Socket.io 연결 관리

```typescript
// ❌ 잘못된 방식 - 컴포넌트마다 연결 생성
function CaseList() {
  const socket = io('http://localhost:3001'); // 매번 새 연결!
}

// ✅ 올바른 방식 - 싱글톤 패턴
// lib/socket.ts
let socket: Socket | null = null;

export function getSocket() {
  if (!socket) {
    socket = io(process.env.NEXT_PUBLIC_SOCKET_URL!, {
      autoConnect: false,
      reconnection: true,
      reconnectionAttempts: 5
    });
  }
  return socket;
}
```

---

## 8. 배포 환경 설정

### 8.1 Docker Compose

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "3000:3000"
    environment:
      - DATABASE_URL=postgresql://postgres:password@db:5432/dentalflow
      - NEXTAUTH_URL=https://your-domain.com
      - NEXTAUTH_SECRET=your-secret-key
    depends_on:
      - db
      - redis

  socket:
    build: ./server
    ports:
      - "3001:3001"
    environment:
      - REDIS_URL=redis://redis:6379
    depends_on:
      - redis

  db:
    image: postgres:16-alpine
    volumes:
      - postgres_data:/var/lib/postgresql/data
    environment:
      - POSTGRES_DB=dentalflow
      - POSTGRES_PASSWORD=password

  redis:
    image: redis:7-alpine
    volumes:
      - redis_data:/data

volumes:
  postgres_data:
  redis_data:
```

### 8.2 Nginx 설정

```nginx
# /etc/nginx/sites-available/dentalflow
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;

    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    # Next.js 앱
    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }

    # Socket.io
    location /socket.io/ {
        proxy_pass http://localhost:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }
}
```

---

## 9. 참고 자료 (Sources)

- [Next.js 15 공식 블로그](https://nextjs.org/blog/next-15)
- [Next.js 15.5 릴리즈](https://nextjs.org/blog/next-15-5)
- [Tailwind CSS v4.0](https://tailwindcss.com/blog/tailwindcss-v4)
- [Prisma ORM 공식 문서](https://www.prisma.io/docs)
- [Auth.js 역할 기반 접근 제어](https://authjs.dev/guides/role-based-access-control)
- [Socket.IO vs Supabase 비교](https://ably.com/compare/socketio-vs-supabase)
- [Next.js 15 프로젝트 구조 가이드](https://dev.to/bajrayejoon/best-practices-for-organizing-your-nextjs-15-2025-53ji)
- [shadcn/ui Tailwind v4](https://ui.shadcn.com/docs/tailwind-v4)

---

*이 문서는 AI 코더가 개발 시 엄격하게 준수해야 할 기술 사양을 정의합니다.*
