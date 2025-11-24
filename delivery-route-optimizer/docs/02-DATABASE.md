# Phase 2: Database Design (JSON 기반 ERD + Schema)

## A. 고객 테이블 (customers) 스키마

### ERD 다이어그램
```
┌─────────────────────────────────────────────────────────────────────┐
│                           customers                                  │
├─────────────────────────────────────────────────────────────────────┤
│ PK │ id              │ INT          │ AUTO_INCREMENT               │
├────┼─────────────────┼──────────────┼──────────────────────────────┤
│ UK │ account_number  │ VARCHAR(50)  │ 고유 계정번호 (Account #)    │
│    │ title           │ VARCHAR(20)  │ 호칭 (Dr., Mr.)              │
│    │ first_name      │ VARCHAR(100) │ 이름 (FName)                 │
│    │ last_name       │ VARCHAR(100) │ 성 (LName)                   │
│    │ practice_name   │ VARCHAR(255) │ 사업장명 (PracticeName)      │
│    │ phone           │ VARCHAR(30)  │ 전화번호                     │
│    │ fax             │ VARCHAR(30)  │ 팩스번호                     │
│    │ cell_phone      │ VARCHAR(30)  │ 휴대전화 (CellPh)            │
│    │ email           │ VARCHAR(255) │ 이메일 (PrimaryEmail)        │
│    │ stmt_email      │ VARCHAR(255) │ 명세서 이메일 (Stmt Email)   │
│    │ addr1           │ VARCHAR(255) │ 주소1                        │
│    │ addr2           │ VARCHAR(255) │ 주소2                        │
│    │ addr3           │ VARCHAR(255) │ 주소3                        │
│    │ city            │ VARCHAR(100) │ 도시                         │
│    │ state           │ VARCHAR(10)  │ 주 코드 (statecd)            │
│    │ zip             │ VARCHAR(20)  │ 우편번호 (zipcd)             │
│    │ route_name      │ VARCHAR(100) │ 경로명 (RouteName)           │
│    │ ship_to_flag    │ TINYINT(1)   │ 배송 가능 여부               │
│    │ salesperson     │ VARCHAR(100) │ 담당 영업사원                │
│    │ account_class   │ VARCHAR(10)  │ 계정 분류                    │
│    │ account_type    │ INT          │ 계정 유형                    │
│    │ account_manager │ VARCHAR(100) │ 계정 관리자 (AcctMgr)        │
│    │ territory       │ VARCHAR(100) │ 지역                         │
│    │ latitude        │ DECIMAL(10,8)│ 위도 (Geocoding 생성)        │
│    │ longitude       │ DECIMAL(11,8)│ 경도 (Geocoding 생성)        │
│    │ geocoded_at     │ DATETIME     │ 좌표 생성 시간               │
│    │ date_created    │ DATETIME     │ 생성일 (DateCreated)         │
│    │ extended_json   │ TEXT         │ 기타 확장 필드 저장          │
│    │ created_at      │ TIMESTAMP    │ 레코드 생성 시간             │
│    │ updated_at      │ TIMESTAMP    │ 레코드 수정 시간             │
└─────────────────────────────────────────────────────────────────────┘
```

### JSON 필드 → DB 컬럼 매핑
| JSON Field | DB Column | Type | Note |
|------------|-----------|------|------|
| Account # | account_number | VARCHAR(50) | UNIQUE |
| Title | title | VARCHAR(20) | |
| FName | first_name | VARCHAR(100) | |
| LName | last_name | VARCHAR(100) | |
| PracticeName | practice_name | VARCHAR(255) | |
| Phone | phone | VARCHAR(30) | |
| fax | fax | VARCHAR(30) | |
| CellPh | cell_phone | VARCHAR(30) | |
| PrimaryEmail | email | VARCHAR(255) | |
| Stmt Email | stmt_email | VARCHAR(255) | |
| addr1 | addr1 | VARCHAR(255) | |
| addr2 | addr2 | VARCHAR(255) | |
| addr3 | addr3 | VARCHAR(255) | |
| city | city | VARCHAR(100) | |
| statecd | state | VARCHAR(10) | |
| zipcd | zip | VARCHAR(20) | |
| RouteName | route_name | VARCHAR(100) | INDEX |
| ShipToFlag | ship_to_flag | TINYINT(1) | |
| SalesPerson | salesperson | VARCHAR(100) | |
| AccountClass | account_class | VARCHAR(10) | |
| AccountType | account_type | INT | |
| AcctMgr | account_manager | VARCHAR(100) | |
| Territory | territory | VARCHAR(100) | |
| DateCreated | date_created | DATETIME | |
| (Geocoding) | latitude | DECIMAL(10,8) | API 생성 |
| (Geocoding) | longitude | DECIMAL(11,8) | API 생성 |
| (기타 모든 필드) | extended_json | TEXT | JSON 저장 |

---

## B. 배송 경로 테이블 (delivery_routes) 스키마

### ERD 다이어그램
```
┌─────────────────────────────────────────────────────────────────────┐
│                        delivery_routes                               │
├─────────────────────────────────────────────────────────────────────┤
│ PK │ id              │ INT          │ AUTO_INCREMENT               │
├────┼─────────────────┼──────────────┼──────────────────────────────┤
│    │ route_name      │ VARCHAR(100) │ 경로 이름                    │
│    │ customer_ids    │ JSON         │ 선택된 고객 ID 배열          │
│    │ optimized_order │ JSON         │ 최적화된 순서 배열           │
│    │ waypoints       │ JSON         │ 경유지 좌표 배열             │
│    │ start_location  │ JSON         │ 출발지 {lat, lng}            │
│    │ end_location    │ JSON         │ 도착지 {lat, lng}            │
│    │ start_time      │ DATETIME     │ 출발 예정 시간               │
│    │ total_distance  │ INT          │ 총 거리 (meters)             │
│    │ total_duration  │ INT          │ 총 소요시간 (seconds)        │
│    │ duration_traffic│ INT          │ 교통 반영 소요시간           │
│    │ polyline        │ TEXT         │ 인코딩된 경로 Polyline       │
│    │ legs_data       │ JSON         │ 각 구간별 상세 정보          │
│    │ status          │ ENUM         │ pending/active/completed     │
│    │ driver_id       │ INT          │ 담당 드라이버 (확장용)       │
│    │ created_at      │ TIMESTAMP    │ 생성 시간                    │
│    │ updated_at      │ TIMESTAMP    │ 수정 시간                    │
└─────────────────────────────────────────────────────────────────────┘

                            │ 1:N
                            ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        route_history                                 │
├─────────────────────────────────────────────────────────────────────┤
│ PK │ id              │ INT          │ AUTO_INCREMENT               │
├────┼─────────────────┼──────────────┼──────────────────────────────┤
│ FK │ route_id        │ INT          │ delivery_routes.id           │
│    │ action_type     │ VARCHAR(50)  │ created/recalculated/completed│
│    │ driver_lat      │ DECIMAL(10,8)│ 드라이버 위치 위도           │
│    │ driver_lng      │ DECIMAL(11,8)│ 드라이버 위치 경도           │
│    │ snapshot_data   │ JSON         │ 당시 경로 스냅샷             │
│    │ created_at      │ TIMESTAMP    │ 기록 시간                    │
└─────────────────────────────────────────────────────────────────────┘
```

---

## C. 전체 ERD 관계도

```
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│    customers    │       │ delivery_routes │       │  route_history  │
├─────────────────┤       ├─────────────────┤       ├─────────────────┤
│ id (PK)         │◄──┐   │ id (PK)         │───────│ route_id (FK)   │
│ account_number  │   │   │ customer_ids[]  │       │ action_type     │
│ first_name      │   └───│ (JSON array)    │       │ driver_lat/lng  │
│ last_name       │       │ optimized_order │       │ snapshot_data   │
│ practice_name   │       │ start_time      │       │ created_at      │
│ addr1/2/3       │       │ total_distance  │       └─────────────────┘
│ city/state/zip  │       │ total_duration  │
│ latitude        │       │ polyline        │
│ longitude       │       │ status          │
│ route_name      │       │ created_at      │
│ ...             │       └─────────────────┘
└─────────────────┘
```

---

➡️ **Phase 3로 이동합니다.**
