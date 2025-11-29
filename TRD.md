# ⚙️ DentalFlow - 기술 사양서 (TRD)

> **문서 버전**: 1.1
> **작성일**: 2025-11-29
> **최종 수정**: 2025-11-29
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
| **Database** | MySQL | 8.x | Hostinger VPS 기본 제공 |
| **ORM** | Prisma | 6.x | 안정 버전 권장 |
| **Authentication** | Auth.js (NextAuth v5) | 5.x | 역할 기반 인증 |
| **Real-time** | Socket.io | 4.x | WebSocket 통신 |
| **State Management** | Zustand | 5.x | 경량 상태 관리 |
| **Charts** | Recharts | 2.x | 대시보드 차트 |
| **Process Manager** | PM2 | 5.x | Node.js 프로세스 관리 |
| **Web Server** | OpenLiteSpeed | 1.7.x | Hostinger 기본 제공 (Nginx 대비 4배 빠름) |
| **Hosting** | Hostinger VPS | KVM 2+ | Ubuntu 22.04 + Node.js 템플릿 |

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
| 데이터베이스 독립 | ✅ MySQL과 분리 | ❌ PostgreSQL 종속 |
| 80명 동시 접속 | ✅ 검증됨 | ✅ 가능 |

**결론**: Hostinger VPS 환경에서 직접 운영하기에 Socket.io가 더 적합

**출처**: [Socket.IO vs Supabase 비교](https://ably.com/compare/socketio-vs-supabase)

### 2.3 Tailwind CSS v4 선정 근거

- 2025년 1월 정식 출시, 안정화 완료
- 빌드 속도 5배 향상 (Rust 기반 엔진)
- CSS-first 설정으로 구성 단순화
- shadcn/ui와 완벽 호환

**출처**: [Tailwind CSS v4.0](https://tailwindcss.com/blog/tailwindcss-v4)

### 2.4 Hostinger VPS 플랜 권장 사항

| 플랜 | 사양 | 가격 | 80명 사용 적합성 |
|------|------|------|-----------------|
| KVM 1 | 1 vCPU, 4GB RAM, 50GB NVMe | $4.99/월 | ❌ 부족 |
| **KVM 2** | **2 vCPU, 8GB RAM, 100GB NVMe** | **$6.99/월** | ✅ **권장** |
| KVM 4 | 4 vCPU, 16GB RAM, 200GB NVMe | $12.99/월 | ✅ 여유 |

**권장 설정**:
- **OS 템플릿**: Ubuntu 22.04 with Node.js + OpenLiteSpeed
- **이유**: OpenLiteSpeed가 Nginx보다 4배 빠르고, Hostinger에서 사전 최적화됨

**출처**: [Hostinger VPS Plans](https://www.hostinger.com/vps-hosting)

---

## 3. 핵심 아키텍처

### 3.1 시스템 아키텍처 다이어그램 (Hostinger 최적화)

```
┌──────────────────────────────────────────────────────────────────────┐
│                    Hostinger VPS (KVM 2 권장)                         │
│                    Ubuntu 22.04 + Node.js + OpenLiteSpeed            │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────────┐  │
│  │                    OpenLiteSpeed (Port 80/443)                 │  │
│  │                    - SSL/TLS 자동 관리 (Let's Encrypt)          │  │
│  │                    - Reverse Proxy + 정적 파일 캐싱             │  │
│  └───────────────────────────┬────────────────────────────────────┘  │
│                              │                                       │
│         ┌────────────────────┼────────────────────┐                  │
│         │                    │                    │                  │
│         ▼                    ▼                    ▼                  │
│  ┌─────────────┐      ┌─────────────┐      ┌─────────────┐          │
│  │   Next.js   │      │  Socket.io  │      │    MySQL    │          │
│  │  (PM2 관리)  │      │  (PM2 관리)  │      │  (Hostinger │          │
│  │  Port 3000  │◄────►│  Port 3001  │◄────►│   기본제공)  │          │
│  └──────┬──────┘      └──────┬──────┘      │  Port 3306  │          │
│         │                    │              └─────────────┘          │
│         │                    │                                       │
│         └────────┬───────────┘                                       │
│                  │                                                   │
│           ┌──────▼──────┐                                            │
│           │    Redis    │ (선택적 - 세션/캐시)                         │
│           │  Port 6379  │                                            │
│           └─────────────┘                                            │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────────┐  │
│  │                         PM2 Ecosystem                          │  │
│  │  - next-app (cluster mode, 2 instances)                        │  │
│  │  - socket-server (fork mode, 1 instance)                       │  │
│  │  - 자동 재시작, 로그 관리, 모니터링                               │  │
│  └────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────┘
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
├── ecosystem.config.js           # PM2 설정 파일
├── next.config.ts                # Next.js 설정
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
│   │   │   ├── my-tasks/         # [신규] 마이 태스크
│   │   │   │   └── page.tsx      # 개인 업무 대시보드
│   │   │   ├── cases/
│   │   │   │   ├── page.tsx      # 케이스 목록
│   │   │   │   ├── [id]/
│   │   │   │   │   └── page.tsx  # 케이스 상세
│   │   │   │   └── new/
│   │   │   │       └── page.tsx  # 케이스 생성
│   │   │   ├── users/
│   │   │   │   └── page.tsx      # 사용자 관리 (관리자)
│   │   │   └── settings/         # [신규] 설정 확장
│   │   │       ├── page.tsx      # 설정 메인
│   │   │       ├── departments/
│   │   │       │   └── page.tsx  # 부서별 커스터마이징
│   │   │       ├── task-types/
│   │   │       │   └── page.tsx  # 작업 유형 관리
│   │   │       └── custom-fields/
│   │   │           └── page.tsx  # 커스텀 필드 관리
│   │   │
│   │   └── api/                   # API Routes
│   │       ├── auth/
│   │       │   └── [...nextauth]/
│   │       │       └── route.ts
│   │       ├── cases/
│   │       │   ├── route.ts       # GET, POST
│   │       │   ├── [id]/
│   │       │   │   └── route.ts   # GET, PUT, DELETE
│   │       │   └── assign/        # [신규] 작업자 할당
│   │       │       └── route.ts
│   │       ├── my-tasks/          # [신규] 마이 태스크 API
│   │       │   └── route.ts
│   │       ├── users/
│   │       │   └── route.ts
│   │       ├── departments/       # [신규] 부서 설정 API
│   │       │   ├── route.ts
│   │       │   └── [id]/
│   │       │       ├── task-types/
│   │       │       │   └── route.ts
│   │       │       └── custom-fields/
│   │       │           └── route.ts
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
│   │   │   ├── case-filter.tsx
│   │   │   └── assignee-modal.tsx # [신규] 작업자 할당 모달
│   │   ├── my-tasks/              # [신규] 마이 태스크 컴포넌트
│   │   │   ├── task-list.tsx
│   │   │   ├── task-card.tsx
│   │   │   └── today-summary.tsx
│   │   ├── settings/              # [신규] 설정 컴포넌트
│   │   │   ├── task-type-form.tsx
│   │   │   ├── custom-field-form.tsx
│   │   │   └── sortable-list.tsx  # 드래그 앤 드롭
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
│   │   ├── use-my-tasks.ts        # [신규] 마이 태스크 훅
│   │   └── use-realtime.ts        # 실시간 업데이트 훅
│   │
│   ├── stores/                    # Zustand 스토어
│   │   ├── case-store.ts
│   │   ├── my-task-store.ts       # [신규]
│   │   └── user-store.ts
│   │
│   ├── types/                     # TypeScript 타입
│   │   ├── case.ts
│   │   ├── user.ts
│   │   ├── task-type.ts           # [신규]
│   │   ├── custom-field.ts        # [신규]
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
  provider = "mysql"
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

  // [신규] 작업자 할당 관련
  assignedTasks     CaseAssignment[]    @relation("AssignedTo")
  tasksAssignedBy   CaseAssignment[]    @relation("AssignedBy")
  transfersFrom     AssignmentTransfer[] @relation("TransferFrom")
  transfersTo       AssignmentTransfer[] @relation("TransferTo")
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
  toothNumbers    Json        // 치아 번호 배열 (MySQL은 배열 미지원, JSON 사용)
  toothColor      String?     @db.VarChar(50)
  implantType     String?
  dueDate         DateTime

  // 작업 유형
  cocrType        String?
  solidexType     String?
  print3dType     String?

  // 노트 (다중 선택 + 자유 텍스트)
  noteOptions     Json        // 선택된 옵션들 (MySQL JSON 타입)
  noteText        String?     @db.Text  // 자유 텍스트

  // 상태 및 부서
  status          CaseStatus  @default(PENDING)
  currentDepartment Department @default(FRONT_DESK)

  // 워크플로우 (이 케이스가 거쳐야 할 부서들)
  workflow        Json        // Department 배열 (MySQL JSON 타입)

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
  assignments     CaseAssignment[]    // [신규] 작업자 할당

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

// ===== [신규] 부서별 커스터마이징 =====

// 부서별 커스텀 작업 유형
model TaskType {
  id          String      @id @default(cuid())
  department  Department
  name        String      // "풀 크라운", "인레이" 등
  description String?
  color       String?     @db.VarChar(7)  // HEX 색상 (#FF5733)
  isActive    Boolean     @default(true)
  sortOrder   Int         @default(0)
  createdAt   DateTime    @default(now())
  updatedAt   DateTime    @updatedAt

  @@unique([department, name])
  @@index([department])
}

// 부서별 커스텀 필드 정의
enum FieldType {
  TEXT
  NUMBER
  SELECT
  MULTI_SELECT
  DATE
  BOOLEAN
}

model CustomField {
  id          String      @id @default(cuid())
  department  Department
  name        String      // 필드 이름
  label       String      // 표시 레이블
  fieldType   FieldType
  options     Json?       // SELECT, MULTI_SELECT용 옵션 배열
  isRequired  Boolean     @default(false)
  isActive    Boolean     @default(true)
  sortOrder   Int         @default(0)
  createdAt   DateTime    @default(now())
  updatedAt   DateTime    @updatedAt

  @@unique([department, name])
  @@index([department])
}

// 부서별 커스텀 상태 (기본 상태 외 추가)
model CustomStatus {
  id          String      @id @default(cuid())
  department  Department
  name        String      // "검수중", "수정요청" 등
  color       String?     @db.VarChar(7)
  isActive    Boolean     @default(true)
  sortOrder   Int         @default(0)
  createdAt   DateTime    @default(now())

  @@unique([department, name])
  @@index([department])
}

// ===== [신규] 작업자 할당 =====

// 케이스-부서별 작업자 할당
model CaseAssignment {
  id            String      @id @default(cuid())
  caseId        String
  department    Department
  assigneeId    String      // 할당된 작업자
  assignedById  String      // 할당한 사람
  assignedAt    DateTime    @default(now())

  // 작업 상태 (작업자 개인 레벨)
  status        CaseStatus  @default(PENDING)
  startedAt     DateTime?
  completedAt   DateTime?

  // 커스텀 필드 값 저장
  customFieldValues Json?   // { "fieldId": "value", ... }

  case          Case        @relation(fields: [caseId], references: [id], onDelete: Cascade)
  assignee      User        @relation("AssignedTo", fields: [assigneeId], references: [id])
  assignedBy    User        @relation("AssignedBy", fields: [assignedById], references: [id])

  @@unique([caseId, department, assigneeId])
  @@index([assigneeId])
  @@index([caseId])
  @@index([status])
}

// 작업 인계 이력
model AssignmentTransfer {
  id              String      @id @default(cuid())
  caseId          String
  department      Department
  fromUserId      String
  toUserId        String
  reason          String      @db.Text
  transferredAt   DateTime    @default(now())

  fromUser        User        @relation("TransferFrom", fields: [fromUserId], references: [id])
  toUser          User        @relation("TransferTo", fields: [toUserId], references: [id])

  @@index([caseId])
  @@index([fromUserId])
  @@index([toUserId])
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

### 7.3 MySQL + Prisma 주의사항

```typescript
// ⚠️ MySQL은 네이티브 배열 타입 미지원 - Json 타입 사용
// Prisma 스키마
model Case {
  toothNumbers  Json  // ["11", "12", "21"] 형태로 저장
  noteOptions   Json  // ["option1", "option2"] 형태로 저장
  workflow      Json  // ["FRONT_DESK", "PRE_CAD"] 형태로 저장
}

// TypeScript에서 타입 안전하게 사용
interface Case {
  toothNumbers: string[];
  noteOptions: string[];
  workflow: Department[];
}

// 조회 시 타입 캐스팅
const cases = await prisma.case.findMany();
const toothNumbers = cases[0].toothNumbers as string[];
```

### 7.4 Prisma 낙관적 잠금

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

### 7.5 Tailwind CSS v4 설정

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

### 7.6 Socket.io 연결 관리

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

## 8. 배포 환경 설정 (Hostinger VPS 최적화)

### 8.1 PM2 Ecosystem 설정

```javascript
// ecosystem.config.js
module.exports = {
  apps: [
    {
      name: 'dentalflow-web',
      script: 'node_modules/next/dist/bin/next',
      args: 'start',
      cwd: '/var/www/dentalflow',
      instances: 2,           // KVM 2 기준 (2 vCPU)
      exec_mode: 'cluster',   // 클러스터 모드로 부하 분산
      env: {
        NODE_ENV: 'production',
        PORT: 3000
      },
      env_file: '.env.local',
      max_memory_restart: '500M',
      log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
      error_file: '/var/log/pm2/dentalflow-error.log',
      out_file: '/var/log/pm2/dentalflow-out.log',
      merge_logs: true
    },
    {
      name: 'dentalflow-socket',
      script: 'server/index.js',
      cwd: '/var/www/dentalflow',
      instances: 1,
      exec_mode: 'fork',
      env: {
        NODE_ENV: 'production',
        PORT: 3001
      },
      env_file: '.env.local',
      max_memory_restart: '200M',
      error_file: '/var/log/pm2/socket-error.log',
      out_file: '/var/log/pm2/socket-out.log'
    }
  ]
};
```

### 8.2 PM2 명령어

```bash
# 앱 시작
pm2 start ecosystem.config.js

# 상태 확인
pm2 status

# 로그 확인
pm2 logs dentalflow-web

# 재시작
pm2 restart all

# 시스템 부팅 시 자동 시작 설정
pm2 startup
pm2 save

# 모니터링 대시보드
pm2 monit
```

### 8.3 OpenLiteSpeed 설정

Hostinger VPS의 Node.js 템플릿은 OpenLiteSpeed가 기본 설치됩니다.
WebAdmin 패널 (https://your-ip:7080)에서 설정하거나 직접 설정 파일을 수정합니다.

```
# /usr/local/lsws/conf/vhosts/dentalflow/vhconf.conf

docRoot                   /var/www/dentalflow/public
vhDomain                  your-domain.com
enableGzip                1

# Next.js Proxy 설정
context / {
  type                    proxy
  handler                 localhost:3000
  addDefaultCharset       off
}

# Socket.io WebSocket Proxy
context /socket.io {
  type                    proxy
  handler                 localhost:3001
  addDefaultCharset       off

  # WebSocket 지원
  extraHeaders            Upgrade $http_upgrade
  extraHeaders            Connection "upgrade"
}

# 정적 파일 직접 서빙 (성능 최적화)
context /static {
  location                /var/www/dentalflow/.next/static
  allowBrowse             1
  expires                 365d
  extraHeaders            Cache-Control "public, max-age=31536000, immutable"
}

# SSL/TLS 설정 (Let's Encrypt)
vhssl {
  keyFile                 /etc/letsencrypt/live/your-domain.com/privkey.pem
  certFile                /etc/letsencrypt/live/your-domain.com/fullchain.pem
}
```

### 8.4 MySQL 설정 (Hostinger 기본 제공)

```bash
# Hostinger VPS에서 MySQL은 기본 설치됨
# 초기 설정
sudo mysql_secure_installation

# 데이터베이스 및 사용자 생성
mysql -u root -p
```

```sql
CREATE DATABASE dentalflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dentalflow'@'localhost' IDENTIFIED BY 'your-secure-password';
GRANT ALL PRIVILEGES ON dentalflow.* TO 'dentalflow'@'localhost';
FLUSH PRIVILEGES;
```

### 8.5 Redis 설치 (선택사항)

```bash
# Redis 설치
sudo apt update
sudo apt install redis-server

# 설정 파일 수정
sudo nano /etc/redis/redis.conf
# maxmemory 256mb
# maxmemory-policy allkeys-lru

# 서비스 시작
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

### 8.6 환경변수 (.env.local)

```bash
# Database
DATABASE_URL="mysql://dentalflow:your-password@localhost:3306/dentalflow"

# NextAuth
NEXTAUTH_URL="https://your-domain.com"
NEXTAUTH_SECRET="your-random-secret-key-here"

# Socket.io
NEXT_PUBLIC_SOCKET_URL="https://your-domain.com"

# Redis (선택사항)
REDIS_URL="redis://localhost:6379"
```

### 8.7 배포 스크립트

```bash
#!/bin/bash
# deploy.sh

set -e

echo "📦 Pulling latest changes..."
git pull origin main

echo "📥 Installing dependencies..."
npm ci --production=false

echo "🔨 Building application..."
npm run build

echo "🗄️ Running database migrations..."
npx prisma migrate deploy

echo "🔄 Restarting PM2 processes..."
pm2 restart ecosystem.config.js

echo "✅ Deployment complete!"
```

### 8.8 SSL 인증서 (Let's Encrypt)

```bash
# Certbot 설치
sudo apt install certbot

# 인증서 발급 (OpenLiteSpeed 중지 후)
sudo lswsctrl stop
sudo certbot certonly --standalone -d your-domain.com
sudo lswsctrl start

# 자동 갱신 설정
sudo crontab -e
# 0 0 1 * * certbot renew --pre-hook "lswsctrl stop" --post-hook "lswsctrl start"
```

---

## 9. 참고 자료 (Sources)

### Framework & Libraries
- [Next.js 15 공식 블로그](https://nextjs.org/blog/next-15)
- [Tailwind CSS v4.0](https://tailwindcss.com/blog/tailwindcss-v4)
- [Prisma ORM 공식 문서](https://www.prisma.io/docs)
- [Prisma MySQL 커넥터](https://www.prisma.io/docs/orm/overview/databases/mysql)
- [Auth.js 역할 기반 접근 제어](https://authjs.dev/guides/role-based-access-control)
- [Socket.IO 공식 문서](https://socket.io/docs/v4/)
- [shadcn/ui Tailwind v4](https://ui.shadcn.com/docs/tailwind-v4)

### Hostinger VPS & 배포
- [Hostinger VPS Plans](https://www.hostinger.com/vps-hosting)
- [Hostinger Node.js VPS](https://www.hostinger.com/vps/nodejs-hosting)
- [Hostinger Redis VPS](https://www.hostinger.com/vps/redis-hosting)
- [Next.js on Hostinger VPS with Docker](https://medium.com/@afaqak124/deploying-your-next-js-app-on-hostinger-vps-with-docker-part-1-26741c113d33)
- [PM2 공식 문서](https://pm2.keymetrics.io/docs/usage/quick-start/)
- [OpenLiteSpeed vs NGINX 비교](https://cyberhosting.cloud/blog/openlitespeed-vs-nginx/)
- [OpenLiteSpeed 공식 문서](https://openlitespeed.org/kb/)

---

*이 문서는 AI 코더가 개발 시 엄격하게 준수해야 할 기술 사양을 정의합니다.*
