# 🤖 DentalFlow - AI 코딩 착수용 프롬프트

> **문서 버전**: 1.1
> **최종 수정**: 2025-11-29
> **사용 방법**: 아래 프롬프트를 AI 코더 (Claude, GPT 등)에게 복사하여 붙여넣으세요.
> **필수 첨부**: PRD.md와 TRD.md 파일을 함께 첨부하세요.

---

## 📌 Master Prompt (전체 프로젝트 착수용)

```
너는 Next.js 15 App Router와 TypeScript의 수석 개발자야.

첨부한 [PRD.md - 요구사항 정의서]의 기능을 구현하되,
[TRD.md - 기술 사양서]의 스택과 아키텍처를 **엄격하게** 준수해.

## 절대 규칙

1. **절대 생략하거나 임의로 판단하지 마.** 사양서에 명시된 라이브러리와 버전만 사용해.
2. **TRD.md의 디렉토리 구조를 정확히 따라.** 임의로 폴더를 추가하거나 변경하지 마.
3. **AI 코딩 주의사항(섹션 7)을 반드시 숙지하고 준수해.**
4. **코드 작성 전에 항상 해당 파일의 목적을 주석으로 먼저 설명해.**
5. **Hostinger VPS + PM2 + OpenLiteSpeed 배포 환경을 고려해.**

## 개발 순서

먼저 아래 순서대로 프로젝트를 구축해줘:

### Phase 1: 프로젝트 초기 설정
1. Next.js 15 프로젝트 생성 (App Router, TypeScript)
2. 필수 의존성 설치 (TRD.md 섹션 6 참조)
3. Tailwind CSS v4 설정
4. shadcn/ui 초기화 및 기본 컴포넌트 설치
5. Prisma 초기 설정 및 스키마 작성 (TRD.md 섹션 5 참조)
6. 환경변수 템플릿 (.env.example) 생성
7. PM2 ecosystem.config.js 생성

### Phase 2: 인증 시스템
1. Auth.js v5 설정 (Credentials Provider)
2. 역할 기반 접근 제어 (RBAC) 구현
3. 미들웨어 설정 (인증 필요 라우트 보호)
4. 로그인 페이지 UI

### Phase 3: 핵심 기능 - 케이스 관리
1. 케이스 CRUD API Routes
2. 케이스 목록 페이지 (부서별 필터링)
3. 케이스 생성/수정 폼
4. 케이스 상태 변경 기능
5. 낙관적 잠금 구현

### Phase 4: 실시간 동기화
1. Socket.io 서버 설정
2. Socket.io 클라이언트 훅
3. 실시간 케이스 업데이트 브로드캐스트
4. 실시간 UI 반영

### Phase 5: 관리자 대시보드
1. 통계 API 구현
2. KPI 카드 컴포넌트
3. 부서별 작업 현황 차트 (Recharts)
4. 지연 케이스 리스트

### Phase 6: 부서별 커스터마이징 (신규)
1. TaskType, CustomField, CustomStatus 모델 API
2. 부서 설정 관리 페이지
3. 커스텀 필드 동적 렌더링
4. 드래그 앤 드롭 순서 변경

### Phase 7: 마이 태스크 & 작업자 할당 (신규)
1. CaseAssignment 모델 API
2. 작업자 할당 모달
3. 마이 태스크 대시보드
4. 작업 인계 기능

### Phase 8: 마무리 & 배포
1. 에러 핸들링 및 로딩 상태
2. 반응형 디자인 점검
3. PM2 + OpenLiteSpeed 배포 설정 (TRD.md 섹션 8 참조)

---

먼저 **Phase 1의 프로젝트 폴더 구조부터 잡아줘.**
TRD.md의 섹션 4 디렉토리 구조를 참고해서 전체 폴더와 기본 파일들을 생성해.
```

---

## 📌 단계별 상세 프롬프트

### Phase 1: 프로젝트 초기 설정

```
[Phase 1 - 프로젝트 초기 설정]

TRD.md를 참고하여 다음을 수행해:

1. Next.js 15 프로젝트 초기화 명령어 제공
   - App Router 사용
   - TypeScript 사용
   - src/ 디렉토리 사용
   - Tailwind CSS 포함

2. package.json에 TRD.md 섹션 6.1의 모든 의존성 추가

3. TRD.md 섹션 4의 디렉토리 구조대로 폴더 생성
   - 신규 폴더 포함: my-tasks, settings/departments, settings/task-types 등

4. 다음 파일들의 기본 내용 작성:
   - next.config.ts
   - tsconfig.json (경로 alias 설정)
   - src/app/globals.css (Tailwind v4 방식)
   - src/lib/utils.ts (cn 함수)
   - .env.example
   - ecosystem.config.js (PM2 설정)

5. Prisma 초기화 및 TRD.md 섹션 5의 전체 스키마 작성
   - 기본 모델 + 신규 모델 (TaskType, CustomField, CaseAssignment 등)
```

---

### Phase 2: 인증 시스템

```
[Phase 2 - 인증 시스템]

TRD.md 섹션 7.2를 참고하여 Auth.js v5 인증 시스템을 구현해:

1. src/lib/auth.config.ts
   - callbacks에서 jwt와 session에 role, department 추가
   - TRD.md의 "올바른 방식" 코드 참고

2. src/lib/auth.ts
   - Credentials Provider 설정
   - Prisma Adapter 연동
   - bcryptjs로 비밀번호 검증

3. src/app/api/auth/[...nextauth]/route.ts
   - GET, POST 핸들러

4. src/middleware.ts
   - /dashboard, /cases, /users, /my-tasks, /settings 경로 보호
   - 비인증 시 /login으로 리다이렉트
   - 관리자 전용 경로 체크 (/settings/*)

5. src/types/next-auth.d.ts
   - Session, JWT 타입 확장 (role, department 추가)

6. src/app/(auth)/login/page.tsx
   - 이메일, 비밀번호 입력 폼
   - Tailwind + shadcn/ui 사용
   - 에러 메시지 표시
```

---

### Phase 3: 케이스 관리

```
[Phase 3 - 케이스 관리 시스템]

PRD.md의 케이스 정보 필드와 TRD.md의 Prisma 스키마를 참고해:

1. API Routes 구현:
   - src/app/api/cases/route.ts (GET: 목록, POST: 생성)
   - src/app/api/cases/[id]/route.ts (GET, PUT, DELETE)
   - 부서별 필터링 로직 포함
   - Zod로 입력값 검증

2. 케이스 목록 페이지:
   - src/app/(dashboard)/cases/page.tsx
   - 작업자: 본인 부서 케이스만 표시
   - 관리자: 전체 케이스 + 부서 필터
   - 테이블 컴포넌트 (정렬, 페이지네이션)
   - 상태별 배지 색상

3. 케이스 생성/수정:
   - src/components/cases/case-form.tsx
   - 모든 필드 포함 (PRD.md 섹션 3.1 기능2 참조)
   - NOTE 다중 선택 + 자유 텍스트
   - 부서 워크플로우 선택

4. 상태 변경:
   - 드롭다운으로 빠른 상태 변경
   - 낙관적 잠금 (TRD.md 섹션 7.4 참조)
   - 충돌 시 에러 메시지
```

---

### Phase 4: 실시간 동기화

```
[Phase 4 - 실시간 동기화]

TRD.md 섹션 7.6을 참고하여 Socket.io 실시간 기능 구현:

1. Socket.io 서버 (server/index.ts):
   - Redis adapter 연결 (선택사항)
   - 이벤트: case:created, case:updated, case:deleted, assignment:changed
   - 부서별 room 구분

2. 클라이언트 설정:
   - src/lib/socket.ts (싱글톤 패턴)
   - src/hooks/use-socket.ts (연결 관리)
   - src/hooks/use-realtime.ts (케이스 구독)

3. 실시간 UI 반영:
   - 케이스 목록에서 다른 사용자 변경 즉시 반영
   - 변경된 행 하이라이트 효과 (2초)
   - "새 케이스가 추가되었습니다" 토스트 알림

4. 낙관적 업데이트:
   - UI 먼저 변경 → API 호출 → 실패 시 롤백
```

---

### Phase 5: 관리자 대시보드

```
[Phase 5 - 관리자 대시보드]

PRD.md 섹션 3.1 기능4를 참고하여 대시보드 구현:

1. 통계 API (src/app/api/stats/route.ts):
   - 전체 케이스 수, 진행중, 완료, 지연
   - 부서별 작업량
   - 일별/주별 추이
   - 작업자별 통계

2. KPI 카드 (src/components/dashboard/stats-cards.tsx):
   - 총 케이스, 진행중, 완료율, 지연 건수
   - 전일 대비 증감 표시
   - 아이콘 + 숫자 레이아웃

3. 차트 (Recharts 사용):
   - 부서별 작업 현황 바 차트
   - 일별 완료 추이 라인 차트
   - 상태별 파이 차트

4. 지연 케이스 리스트:
   - Due Date 초과 케이스 목록
   - 지연 일수 표시
   - 빨간색 강조
```

---

### Phase 6: 부서별 커스터마이징 (신규)

```
[Phase 6 - 부서별 커스터마이징]

PRD.md 섹션 3.1 기능6과 TRD.md의 신규 Prisma 모델을 참고해:

1. API Routes 구현:
   - src/app/api/departments/[id]/task-types/route.ts
     - GET: 부서별 작업 유형 목록
     - POST: 새 작업 유형 추가
     - PUT: 순서 변경 (sortOrder)
   - src/app/api/departments/[id]/custom-fields/route.ts
     - GET: 부서별 커스텀 필드 목록
     - POST: 새 커스텀 필드 추가
   - src/app/api/departments/[id]/custom-statuses/route.ts
     - 부서별 추가 상태 관리

2. 부서 설정 페이지:
   - src/app/(dashboard)/settings/departments/page.tsx
   - 부서 선택 → 해당 부서 설정 표시
   - 탭: 작업 유형 | 커스텀 필드 | 상태 관리

3. 작업 유형 관리:
   - src/components/settings/task-type-form.tsx
   - 이름, 설명, 색상(HEX) 입력
   - 활성화/비활성화 토글
   - src/components/settings/sortable-list.tsx
   - 드래그 앤 드롭으로 순서 변경 (@dnd-kit/core 사용)

4. 커스텀 필드 관리:
   - src/components/settings/custom-field-form.tsx
   - 필드 타입 선택 (TEXT, NUMBER, SELECT, MULTI_SELECT, DATE, BOOLEAN)
   - SELECT/MULTI_SELECT 시 옵션 목록 입력
   - 필수 여부 체크박스

5. 케이스 폼에 커스텀 필드 동적 렌더링:
   - 케이스 생성/수정 시 해당 부서의 커스텀 필드 자동 표시
   - 필드 타입에 맞는 입력 컴포넌트 렌더링
   - 값은 CaseAssignment.customFieldValues에 JSON으로 저장
```

---

### Phase 7: 마이 태스크 & 작업자 할당 (신규)

```
[Phase 7 - 마이 태스크 & 작업자 할당]

PRD.md 섹션 3.1 기능7과 TRD.md의 CaseAssignment 모델을 참고해:

1. API Routes 구현:
   - src/app/api/cases/assign/route.ts
     - POST: 작업자 할당
     - PUT: 작업 인계
   - src/app/api/my-tasks/route.ts
     - GET: 본인에게 할당된 작업 목록
     - 필터: 오늘 마감, 진행중, 대기중, 완료

2. 작업자 할당 모달:
   - src/components/cases/assignee-modal.tsx
   - 케이스 상세에서 "작업자 할당" 버튼 클릭 시 열림
   - 부서별 작업자 목록 (같은 부서만)
   - 각 작업자의 현재 작업량 표시 (바쁨 정도)
   - 할당 버튼 클릭 → CaseAssignment 생성

3. 마이 태스크 페이지:
   - src/app/(dashboard)/my-tasks/page.tsx
   - 작업자 로그인 시 기본 랜딩 페이지

   - src/components/my-tasks/today-summary.tsx
     - 오늘의 요약 카드: 오늘 할 일, 진행중, 완료

   - src/components/my-tasks/task-list.tsx
     - 4개 섹션: 긴급(오늘 마감), 진행중, 대기중, 최근 완료
     - 각 작업 카드 클릭 → 상세 모달

   - src/components/my-tasks/task-card.tsx
     - 케이스 정보 요약
     - 상태 변경 버튼
     - "작업 시작" / "완료" 버튼

4. 작업 가져오기 (Self-Assign):
   - 케이스 목록에서 미할당 작업 필터
   - "내가 할게요" 버튼 → 본인에게 자동 할당

5. 작업 인계 기능:
   - 마이 태스크에서 "인계하기" 버튼
   - 같은 부서 작업자 선택
   - 인계 사유 입력 (필수)
   - AssignmentTransfer 테이블에 기록

6. src/hooks/use-my-tasks.ts
   - 마이 태스크 데이터 페칭 및 캐싱
   - 실시간 업데이트 구독
```

---

### Phase 8: 마무리 & 배포

```
[Phase 8 - 마무리 & 배포]

1. 에러 핸들링:
   - src/app/error.tsx (전역 에러 바운더리)
   - src/app/not-found.tsx (404 페이지)
   - API 에러 응답 표준화

2. 로딩 상태:
   - src/app/loading.tsx (전역 로딩)
   - 각 페이지별 Skeleton 컴포넌트

3. 반응형 디자인:
   - 모바일: 사이드바 → 하단 네비게이션
   - 테이블 → 카드 리스트 변환

4. PM2 배포 설정 (TRD.md 섹션 8 참조):
   - ecosystem.config.js 최종 점검
   - 배포 스크립트 (deploy.sh) 작성

5. OpenLiteSpeed 설정:
   - Reverse Proxy 설정
   - Socket.io WebSocket 프록시
   - 정적 파일 캐싱
   - SSL 인증서

6. 초기 데이터:
   - prisma/seed.ts 작성
   - 기본 관리자 계정
   - 기본 NOTE 옵션
   - 샘플 부서별 작업 유형
```

---

## 📌 긴급 수정용 프롬프트

### 버그 수정

```
[버그 수정 요청]

문제 상황: [에러 메시지 또는 증상 설명]
관련 파일: [파일 경로]

PRD.md와 TRD.md의 사양을 유지하면서 이 문제를 해결해줘.
수정 전후 코드를 비교해서 보여주고, 왜 이 문제가 발생했는지 설명해.
```

---

### 기능 추가

```
[기능 추가 요청]

추가할 기능: [기능 설명]

주의사항:
1. TRD.md의 기술 스택과 디렉토리 구조를 유지해
2. 기존 코드 패턴과 일관성 유지
3. 새로운 라이브러리 추가 금지 (필요시 먼저 확인)
```

---

## 📌 코드 리뷰 요청 프롬프트

```
[코드 리뷰 요청]

다음 코드가 TRD.md의 사양을 준수하는지 검토해줘:

[코드 붙여넣기]

체크리스트:
1. Next.js 15 App Router 베스트 프랙티스 준수 여부
2. 'use client' 최소화 여부
3. Tailwind CSS v4 문법 준수 여부
4. Auth.js v5 역할 기반 인증 패턴 준수 여부
5. Prisma 쿼리 최적화 여부 (MySQL)
6. 실시간 동기화 로직 안정성
7. PM2 + OpenLiteSpeed 배포 호환성

문제가 있다면 수정된 코드를 제공해줘.
```

---

## 📌 배포 점검 프롬프트

```
[배포 전 점검 요청]

Hostinger VPS (KVM 2)에 배포하기 전 다음을 점검해줘:

1. 환경변수 확인:
   - .env.example의 모든 변수가 설정되었는지
   - DATABASE_URL이 Hostinger MySQL 형식인지

2. PM2 설정 점검:
   - ecosystem.config.js의 instances 수가 적절한지 (2 vCPU 기준)
   - 메모리 제한이 8GB RAM에 맞게 설정되었는지

3. OpenLiteSpeed 설정:
   - Reverse Proxy가 올바르게 설정되었는지
   - WebSocket (Socket.io) 프록시가 작동하는지

4. 빌드 테스트:
   - npm run build가 에러 없이 완료되는지
   - prisma generate가 정상 실행되는지

5. 보안 점검:
   - NEXTAUTH_SECRET이 강력한 랜덤 값인지
   - MySQL 비밀번호가 안전한지
   - .env.local이 .gitignore에 포함되었는지
```

---

## ⚠️ AI 코더 사용 시 주의사항

1. **항상 PRD.md와 TRD.md를 함께 첨부하세요.**
2. **한 번에 너무 많은 기능을 요청하지 마세요.** Phase별로 진행하세요.
3. **생성된 코드를 바로 실행하기 전에 검토하세요.**
4. **에러 발생 시 에러 메시지 전체를 AI에게 공유하세요.**
5. **TRD.md에 명시되지 않은 라이브러리 추가를 AI가 제안하면 거부하세요.**
6. **Hostinger VPS 환경 (MySQL, OpenLiteSpeed)을 항상 고려하세요.**

---

*이 문서는 AI 코더가 일관된 품질의 코드를 생성하도록 가이드합니다.*
