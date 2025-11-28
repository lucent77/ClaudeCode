# LifeCanvas - 자기 관리 웹서비스

만다라트(Mandal-Art)와 7가지 습관을 기반으로 한 종합 자기 관리 웹서비스입니다.

## 🎯 주요 기능

### 만다라트 목표 관리
- 9×9 격자로 목표를 체계적으로 세분화
- 핵심 목표 → 8개 세부 목표 → 64개 실행 과제
- 인터랙티브한 그리드 UI로 시각화

### 일일 실행 트래커
- 매일 할 일 계획 및 체크
- 중요도에 따른 우선순위 설정
- 실행 점수 자동 계산

### 게이미피케이션
- 포인트 시스템 (실행 과제 완료 시 획득)
- 레벨 시스템 (포인트 누적에 따른 레벨업)
- 배지/업적 시스템 (특정 조건 달성 시 획득)
- 리더보드 (주간/전체 순위)

### 시각적 대시보드
- 실행 점수 추이 차트
- 영역별 달성률 시각화
- 주간/월간 통계

### 스마트 코칭
- 실행률 분석 및 개선 제안
- 빈도 조절 권장
- 격려 및 경고 메시지

### 회고 시스템
- 주간/월간 회고 작성
- 성취, 도전, 배움, 다음 계획 정리

## 🛠 기술 스택

- **Backend**: PHP 8.x
- **Database**: MySQL 8.x
- **Frontend**: HTML5, Tailwind CSS, Alpine.js
- **Charts**: Chart.js
- **Hosting**: Hostinger PHP Hosting

## 📁 프로젝트 구조

```
├── api/                 # API 엔드포인트
│   ├── goals.php        # 목표 관리 API
│   └── daily.php        # 일일 계획 API
├── assets/              # 정적 자원
│   ├── css/
│   ├── js/
│   └── images/
├── classes/             # PHP 클래스
│   ├── User.php         # 사용자 관리
│   ├── Goal.php         # 목표 관리
│   └── DailyTracker.php # 일일 추적
├── config/              # 설정 파일
│   └── database.php     # DB 연결
├── database/            # 데이터베이스
│   └── schema.sql       # DB 스키마
├── includes/            # 공통 파일
│   ├── header.php       # 헤더 템플릿
│   ├── footer.php       # 푸터 템플릿
│   └── session.php      # 세션 관리
├── index.php            # 랜딩 페이지
├── login.php            # 로그인
├── register.php         # 회원가입
├── dashboard.php        # 대시보드
├── mandalart.php        # 만다라트
├── daily.php            # 오늘의 할일
├── review.php           # 회고
├── leaderboard.php      # 리더보드
├── achievements.php     # 업적
├── profile.php          # 프로필
├── settings.php         # 설정
└── .htaccess            # Apache 설정
```

## 🚀 설치 방법

### 1. 데이터베이스 설정

```sql
-- MySQL에서 schema.sql 실행
source database/schema.sql;
```

### 2. 설정 파일 수정

`config/database.php` 파일에서 데이터베이스 연결 정보를 수정하세요:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'self_management');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 3. Hostinger 업로드

1. Hostinger cPanel에 접속
2. File Manager에서 public_html 폴더로 이동
3. 모든 파일 업로드
4. phpMyAdmin에서 schema.sql 실행

### 4. 권한 설정

```bash
chmod 755 -R /path/to/project
chmod 644 config/database.php
```

## 📖 7가지 습관 통합

| 습관 | 적용 방식 |
|------|----------|
| 1. 주도적으로 행동하기 | 매일 계획 수립 및 실행 유도 |
| 2. 끝을 생각하며 시작하기 | 만다라트로 최종 목표 시각화 |
| 3. 소중한 것을 먼저 하라 | 중요도 우선순위 설정 |
| 4. 상호 이익 추구 | 협력적 리더보드 |
| 5. 경청과 공감 | 커뮤니티 피드 |
| 6. 시너지 | 팀 챌린지 (예정) |
| 7. 끊임없이 쇄신하기 | 주간/월간 회고 |

## 🎮 게이미피케이션 시스템

### 레벨
| 레벨 | 포인트 | 타이틀 | 아이콘 |
|------|--------|--------|--------|
| 1 | 0 | 새싹 | 🌱 |
| 2 | 500 | 성장 | 🌿 |
| 3 | 1,500 | 발전 | 🌳 |
| 4 | 3,500 | 성취 | ⭐ |
| 5 | 7,000 | 마스터 | 🏆 |
| 6 | 12,000 | 챔피언 | 👑 |
| 7 | 20,000 | 전설 | 💎 |
| 8 | 35,000 | 영웅 | 🦸 |
| 9 | 55,000 | 현자 | 🧙 |
| 10 | 80,000 | 완성 | 🌟 |

### 배지 예시
- 🔥 7일 연속 - 7일 연속으로 목표 달성
- 📋 만다라트 완성 - 81칸 모두 작성
- ✨ 완벽한 한 주 - 일주일간 100% 실행률
- 🌅 아침형 인간 - 오전 6시 전 30회 완료

## 📝 라이선스

MIT License

## 🙏 기여

이슈 리포트, 기능 제안, PR은 언제나 환영합니다!

---

**LifeCanvas** - 목표를 현실로, 만다라트로 그려보세요 🎨
