# Smart Delivery Route Optimizer - System Architecture

## 1. JSON 기반 고객 DB 구조 설명

### 원본 데이터 구조 (JSON)
```
Customer JSON Object:
├── Account #        → 고유 계정 번호 (Primary Key)
├── Title            → 호칭 (Dr., Mr., Ms.)
├── FName            → 이름
├── LName            → 성
├── PracticeName     → 의원/사업장 이름
├── RouteName        → 배송 경로명 (예: "Local Courier")
├── Phone            → 전화번호
├── fax              → 팩스번호
├── PrimaryEmail     → 이메일
├── addr1/2/3        → 주소 1/2/3
├── city             → 도시
├── statecd          → 주 코드 (NY, NJ 등)
├── zipcd            → 우편번호
├── ShipToFlag       → 배송 가능 여부
├── SalesPerson      → 담당 영업사원
├── AccountClass     → 계정 분류
├── DateCreated      → 생성일
└── [기타 확장 필드]  → extended_json으로 저장
```

---

## 2. 시스템 아키텍처 흐름도

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          CLIENT (Browser)                                    │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────────────┐ │
│  │  고객 선택 UI   │  │  지도 표시 UI   │  │   실시간 재경로화 UI        │ │
│  │  (Tailwind CSS) │  │ (Google Maps)   │  │   (Geolocation API)         │ │
│  └────────┬────────┘  └────────┬────────┘  └─────────────┬───────────────┘ │
│           │                    │                         │                   │
│           └────────────────────┼─────────────────────────┘                   │
│                                │                                             │
│                    ┌───────────▼───────────┐                                │
│                    │   JavaScript Layer    │                                │
│                    │   (Fetch API + AJAX)  │                                │
│                    └───────────┬───────────┘                                │
└────────────────────────────────┼────────────────────────────────────────────┘
                                 │ HTTP/JSON
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                          SERVER (Hostinger PHP)                              │
│                                                                              │
│  ┌──────────────────────────────────────────────────────────────────────┐  │
│  │                         API Layer (/api/)                            │  │
│  │  ┌─────────────┐ ┌─────────────┐ ┌──────────────┐ ┌──────────────┐  │  │
│  │  │ customers   │ │ get-route   │ │ recalculate  │ │ add-customer │  │  │
│  │  │   .php      │ │   .php      │ │    .php      │ │    .php      │  │  │
│  │  └──────┬──────┘ └──────┬──────┘ └──────┬───────┘ └──────┬───────┘  │  │
│  └─────────┼───────────────┼───────────────┼────────────────┼──────────┘  │
│            │               │               │                │              │
│  ┌─────────▼───────────────▼───────────────▼────────────────▼──────────┐  │
│  │                    Functions Layer (/functions/)                    │  │
│  │  ┌─────────────┐ ┌─────────────┐ ┌──────────────┐ ┌──────────────┐  │  │
│  │  │ google.php  │ │ route.php   │ │customer_     │ │ helpers.php  │  │  │
│  │  │(API Wrapper)│ │(최적화로직) │ │import.php    │ │              │  │  │
│  │  └──────┬──────┘ └──────┬──────┘ └──────┬───────┘ └──────────────┘  │  │
│  └─────────┼───────────────┼───────────────┼───────────────────────────┘  │
│            │               │               │                              │
│            └───────────────┼───────────────┘                              │
│                            ▼                                              │
│  ┌──────────────────────────────────────────────────────────────────────┐  │
│  │                    Config Layer (/config/)                           │  │
│  │  ┌─────────────┐ ┌─────────────┐                                     │  │
│  │  │   db.php    │ │ config.php  │                                     │  │
│  │  │(MySQL Conn) │ │(API Keys)   │                                     │  │
│  │  └─────────────┘ └─────────────┘                                     │  │
│  └──────────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         EXTERNAL SERVICES                                    │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                     Google Maps Platform                             │   │
│  │  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐   │   │
│  │  │ Geocoding   │ │ Directions  │ │ Distance    │ │ Maps JS     │   │   │
│  │  │ API         │ │ API         │ │ Matrix API  │ │ API         │   │   │
│  │  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘   │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                       MySQL Database                                 │   │
│  │  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐                    │   │
│  │  │ customers   │ │ delivery_   │ │ route_      │                    │   │
│  │  │ table       │ │ routes      │ │ history     │                    │   │
│  │  └─────────────┘ └─────────────┘ └─────────────┘                    │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Data Flow 상세

### 3.1 고객 선택 → 경로 생성 Flow
```
[사용자]
    │
    ▼ (1) 페이지 로드
[Frontend: index.php]
    │
    ▼ (2) GET /api/customers.php
[Backend: customers.php]
    │
    ▼ (3) SELECT * FROM customers WHERE route_name='Local Courier'
[MySQL: customers]
    │
    ▼ (4) JSON Response
[Frontend]
    │
    ▼ (5) 체크박스로 고객 선택
[사용자]
    │
    ▼ (6) "최적 경로 생성" 버튼 클릭
[Frontend: JavaScript]
    │
    ▼ (7) POST /api/get-route.php {customer_ids: [...]}
[Backend: get-route.php]
    │
    ├─▶ (8a) 좌표 없는 고객? → Geocoding API 호출 → DB 저장
    │
    ▼ (8b) Google Directions API 호출 (optimize:true)
[Google Directions API]
    │
    ▼ (9) 최적화된 경로 + ETA 반환
[Backend]
    │
    ▼ (10) delivery_routes 테이블에 저장
[MySQL]
    │
    ▼ (11) JSON Response {route, waypoints, eta, polyline}
[Frontend: route-view.php]
    │
    ▼ (12) Google Maps에 Polyline + Markers 표시
[사용자 화면]
```

### 3.2 실시간 재경로화 Flow
```
[드라이버 위치 변경]
    │
    ▼ (1) navigator.geolocation.watchPosition()
[Browser Geolocation API]
    │
    ▼ (2) 현재 좌표 획득
[Frontend JavaScript]
    │
    ▼ (3) POST /api/recalculate.php
         {current_lat, current_lng, remaining_customers: [...]}
[Backend: recalculate.php]
    │
    ▼ (4) Google Directions API 재호출
[Google API]
    │
    ▼ (5) 새로운 최적 경로 계산
[Backend]
    │
    ▼ (6) 업데이트된 경로 + ETA JSON 반환
[Frontend]
    │
    ▼ (7) 지도 Polyline 업데이트 + ETA Badge 갱신
[사용자 화면]
```

---

## 4. Google API 시퀀스

### 4.1 Geocoding API (주소 → 좌표)
```
Request:
GET https://maps.googleapis.com/maps/api/geocode/json
    ?address=272+Quassaick+Ave,+NEW+WINDSOR,+NY+12553
    &key=YOUR_API_KEY

Response:
{
  "results": [{
    "geometry": {
      "location": {
        "lat": 41.4728,
        "lng": -74.0513
      }
    }
  }],
  "status": "OK"
}
```

### 4.2 Directions API (경로 최적화)
```
Request:
POST https://maps.googleapis.com/maps/api/directions/json
    ?origin=41.4728,-74.0513
    &destination=41.4728,-74.0513 (원점 회귀)
    &waypoints=optimize:true|41.7005,-73.9209|41.6875,-73.9065
    &departure_time=now
    &traffic_model=best_guess
    &key=YOUR_API_KEY

Response:
{
  "routes": [{
    "waypoint_order": [1, 0],  // 최적화된 순서
    "legs": [
      {
        "duration": {"value": 1200, "text": "20 mins"},
        "duration_in_traffic": {"value": 1350, "text": "22 mins"},
        "distance": {"value": 15000, "text": "15 km"}
      }
    ],
    "overview_polyline": {"points": "encoded_polyline_string"}
  }]
}
```

---

## 5. JSON Import Module 설계

### 자동 Import 프로세스
```
┌───────────────────────────────────────────────────────────────┐
│                  JSON Import Process                          │
│                                                               │
│  [JSON File]                                                  │
│      │                                                        │
│      ▼ (1) file_get_contents()                               │
│  [PHP: import_json_customers.php]                            │
│      │                                                        │
│      ▼ (2) json_decode()                                     │
│  [PHP Array]                                                  │
│      │                                                        │
│      ▼ (3) foreach($customers as $customer)                  │
│  [Loop]                                                       │
│      │                                                        │
│      ├─▶ (4a) Map JSON fields → DB columns                   │
│      │        Account # → account_number                      │
│      │        FName → first_name                              │
│      │        LName → last_name                               │
│      │        ...                                             │
│      │                                                        │
│      ├─▶ (4b) Geocoding API 호출 (좌표 없을 때)              │
│      │        addr1 + city + state + zip → lat/lng            │
│      │                                                        │
│      ├─▶ (4c) 확장 필드 → extended_json (TEXT)               │
│      │                                                        │
│      ▼ (5) INSERT INTO customers (...) VALUES (...)          │
│  [MySQL]                                                      │
│      │                                                        │
│      ▼ (6) Import 결과 리포트 생성                           │
│  [Success/Error Log]                                          │
└───────────────────────────────────────────────────────────────┘
```

---

## 6. 전체 서비스 페이지 구성

```
┌─────────────────────────────────────────────────────────────┐
│                    PAGE STRUCTURE                            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  [1] index.php - 메인 대시보드                              │
│      ├── 고객 리스트 (DataTable)                            │
│      ├── 체크박스 다중 선택                                 │
│      ├── 필터: RouteName, City, SalesPerson                 │
│      └── "최적 경로 생성" 버튼                              │
│                                                              │
│  [2] route-view.php - 경로 시각화                           │
│      ├── Google Maps 전체화면                               │
│      ├── 경로 Polyline 표시                                 │
│      ├── 고객 위치 Markers + InfoWindow                     │
│      ├── ETA Badge (실시간)                                 │
│      ├── 드래그로 경로 순서 변경                            │
│      └── "재경로화" 버튼                                    │
│                                                              │
│  [3] live-tracking.php - 실시간 추적                        │
│      ├── 드라이버 현재 위치 표시                            │
│      ├── 자동 재경로화 (위치 변경 시)                       │
│      ├── 다음 목적지 ETA                                    │
│      └── 완료된 배달 체크                                   │
│                                                              │
│  [4] admin/import.php - 데이터 관리                         │
│      ├── JSON 파일 업로드                                   │
│      ├── Import 실행                                        │
│      ├── Import 로그 확인                                   │
│      └── 고객 데이터 CRUD                                   │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 7. Hostinger 파일 트리 구조

```
public_html/
├── index.php                    # 메인 고객 선택 페이지
├── route-view.php               # 경로 시각화 페이지
├── live-tracking.php            # 실시간 추적 페이지
├── import_json_customers.php    # JSON Import 실행 스크립트
│
├── config/
│   ├── db.php                   # MySQL 연결 설정
│   └── config.php               # API Keys, 환경변수
│
├── functions/
│   ├── google.php               # Google API 래퍼 함수
│   ├── route.php                # 경로 최적화 로직
│   ├── customer_import.php      # JSON Import 함수
│   └── helpers.php              # 공통 유틸리티
│
├── api/
│   ├── customers.php            # GET: 고객 목록 조회
│   ├── get-route.php            # POST: 경로 최적화 요청
│   ├── recalculate.php          # POST: 재경로화 요청
│   └── add-customer.php         # POST: 신규 고객 추가
│
├── assets/
│   ├── css/
│   │   └── style.css            # 커스텀 스타일
│   └── js/
│       ├── app.js               # 메인 JavaScript
│       ├── map.js               # Google Maps 로직
│       └── tracking.js          # 실시간 추적 로직
│
├── data/
│   └── sample_customers.json    # 원본 JSON 데이터
│
└── docs/
    └── README.md                # 설치 가이드
```

---

## 8. 기술 스택 요약

| Layer | Technology |
|-------|------------|
| Frontend | HTML5, Tailwind CSS, Vanilla JavaScript |
| Maps | Google Maps JavaScript API |
| Backend | PHP 8.x |
| Database | MySQL 8.x |
| APIs | Google Geocoding, Directions, Distance Matrix |
| Hosting | Hostinger Shared Hosting |
| Security | CORS, Input Validation, Prepared Statements |

---

➡️ **Phase 2로 이동합니다.**
