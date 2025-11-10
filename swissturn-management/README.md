# Swissturn 생산 관리 시스템

Swissturn 부서의 생산 현황과 공구를 통합 관리하는 웹 기반 시스템입니다.

## 주요 기능

### 1. 통합 대시보드
- 8대 CNC 장비(MP1, MP2, MP3, HW1, HW2, HW3, HW4, HW5) 실시간 모니터링
- 장비 가동률 및 OEE(Overall Equipment Effectiveness) 지표
- 공구 수명 현황 한눈에 파악
- 생산 통계 및 품질 지표

### 2. 장비 현황 관리
- 일일 가동 시간 및 다운타임 기록
- 장비별 현재 작업 상태 관리
- 장비별 장착된 공구 목록 확인
- CSV 파일 일괄 업로드 지원

### 3. 공구 정보 관리
- 공구 목록 및 세부 정보 관리
- 공구별 사용 시간 추적 및 수명 관리
- 공구 상태별 필터링(정상/주의/위험)
- 공구 교체 기능
- 사용 완료 공구(Used Tool) 이력 관리

### 4. 공구 수명 관리
- 실시간 공구 사용 시간 추적
- 공구 수명 소진율 시각화
- 교체 필요 공구 자동 경고
- 공구별 사용 이력 보존

### 5. 역할 기반 접근 제어
- 관리자(Admin): 모든 기능 접근
- 작업자(Operator): 제한된 기능 접근

## 기술 스택

### Frontend
- **Framework**: React 18
- **Styling**: Tailwind CSS
- **Build Tool**: Vite
- **State Management**: React Context API
- **Icons**: Lucide React

### Backend
- **Language**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **API**: REST API (JSON)
- **Authentication**: Password Hashing (bcrypt)

## 설치 및 실행

### 필수 요구사항
- Node.js 18 이상
- npm 또는 yarn

### 설치
```bash
# 의존성 설치
npm install
```

### 개발 모드 실행
```bash
npm run dev
```

개발 서버가 http://localhost:3000 에서 실행됩니다.

### 프로덕션 빌드
```bash
npm run build
```

빌드된 파일은 `dist` 디렉토리에 생성됩니다.

### 프로덕션 미리보기
```bash
npm run preview
```

## 배포 (Hostinger)

### 1. 프로젝트 빌드
```bash
npm run build
```

### 2. Hostinger 파일 관리자로 업로드
1. Hostinger 계정에 로그인
2. 호스팅 → 파일 관리자 (File Manager)
3. `public_html` 디렉토리로 이동
4. `dist` 폴더의 모든 내용을 `public_html`에 업로드

### 3. .htaccess 설정 (SPA 라우팅 지원)
`public_html` 디렉토리에 `.htaccess` 파일 생성:

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
```

## CSV 데이터 형식

### Swiss 생산 데이터 (swiss-sample.csv)
- MONTH, WEEK, DATE: 월/주차/날짜
- MODEL NAME, SKU, LOT. NO: 제품 정보
- CNC: 장비 ID
- WORKER: 작업자
- 기타 생산 실적 데이터

### Tool 데이터 (tool-sample.csv)
- Tool Code: 공구 코드
- Tool Name: 공구명
- Category Name: 카테고리
- Tool Size: 사이즈
- Lifespan Type: 수명 타입
- Lifespan Limit: 수명 한도(시간)
- Current Stock: 현재 재고
- Minimum Stock: 최소 재고

## 사용자 매뉴얼

### 대시보드
- 전체 시스템 현황을 한눈에 확인
- 가동 중인 장비 수, 공구 상태, 평균 OEE 확인
- 각 장비의 상태 카드로 실시간 모니터링

### 장비 관리
- 각 장비의 가동 시간, 다운타임 입력
- 현재 작업 상태 업데이트
- 장착된 공구 목록 확인

### 공구 관리
- 검색 기능으로 특정 공구 찾기
- 상태별 필터링(정상/주의/위험)
- 새 공구 추가
- 공구 교체 (기존 공구는 사용 완료 목록으로 이동)
- 사용 완료 공구 이력 조회

## 라이선스

Copyright © 2024 Swissturn. All rights reserved.

## 지원

문의사항이 있으시면 시스템 관리자에게 연락하세요.
