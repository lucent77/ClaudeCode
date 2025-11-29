# 🤖 DentalFlow - AI 코딩 착수용 프롬프트

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

## 개발 순서

먼저 아래 순서대로 프로젝트를 구축해줘:

### Phase 1: 프로젝트 초기 설정
1. Next.js 15 프로젝트 생성 (App Router, TypeScript)
2. 필수 의존성 설치 (TRD.md 섹션 6 참조)
3. Tailwind CSS v4 설정
4. shadcn/ui 초기화 및 기본 컴포넌트 설치
5. Prisma 초기 설정 및 스키마 작성 (TRD.md 섹션 5 참조)
6. 환경변수 템플릿 (.env.example) 생성

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

### Phase 6: 마무리
1. 에러 핸들링 및 로딩 상태
2. 반응형 디자인 점검
3. Docker 설정

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

4. 다음 파일들의 기본 내용 작성:
   - next.config.ts
   - tsconfig.json (경로 alias 설정)
   - src/app/globals.css (Tailwind v4 방식)
   - src/lib/utils.ts (cn 함수)
   - .env.example

5. Prisma 초기화 및 TRD.md 섹션 5의 스키마 작성
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
   - /dashboard, /cases, /users 경로 보호
   - 비인증 시 /login으로 리다이렉트

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
   - 낙관적 잠금 (TRD.md 섹션 7.3 참조)
   - 충돌 시 에러 메시지
```

---

### Phase 4: 실시간 동기화

```
[Phase 4 - 실시간 동기화]

TRD.md 섹션 7.5를 참고하여 Socket.io 실시간 기능 구현:

1. Socket.io 서버 (server/index.ts):
   - Redis adapter 연결
   - 이벤트: case:created, case:updated, case:deleted
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
5. Prisma 쿼리 최적화 여부
6. 실시간 동기화 로직 안정성

문제가 있다면 수정된 코드를 제공해줘.
```

---

## ⚠️ AI 코더 사용 시 주의사항

1. **항상 PRD.md와 TRD.md를 함께 첨부하세요.**
2. **한 번에 너무 많은 기능을 요청하지 마세요.** Phase별로 진행하세요.
3. **생성된 코드를 바로 실행하기 전에 검토하세요.**
4. **에러 발생 시 에러 메시지 전체를 AI에게 공유하세요.**
5. **TRD.md에 명시되지 않은 라이브러리 추가를 AI가 제안하면 거부하세요.**

---

*이 문서는 AI 코더가 일관된 품질의 코드를 생성하도록 가이드합니다.*
