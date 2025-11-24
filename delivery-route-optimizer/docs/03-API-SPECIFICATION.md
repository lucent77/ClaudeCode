# Phase 3: Backend API Specification

## API 엔드포인트 목록

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/customers.php` | GET | 고객 목록 조회 |
| `/api/get-route.php` | POST | 경로 최적화 요청 |
| `/api/recalculate.php` | POST | 재경로화 요청 |
| `/api/add-customer.php` | POST | 신규 고객 추가 |

---

## 1. GET /api/customers.php

### 설명
DB customers 테이블에서 고객 목록을 반환합니다.
기본 필터: `route_name = 'Local Courier'`

### Request
```http
GET /api/customers.php?route_name=Local%20Courier&city=&has_coords=
```

### Query Parameters
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| route_name | string | No | Local Courier | 경로명 필터 |
| city | string | No | - | 도시명 필터 |
| state | string | No | - | 주 코드 필터 |
| salesperson | string | No | - | 영업사원 필터 |
| has_coords | boolean | No | - | 좌표 유무 필터 (1/0) |
| limit | int | No | 100 | 반환 개수 제한 |
| offset | int | No | 0 | 페이지네이션 오프셋 |

### Response (Success)
```json
{
  "success": true,
  "data": {
    "customers": [
      {
        "id": 1,
        "account_number": "2878BACPULUCN",
        "title": "Dr.",
        "first_name": "Constantina",
        "last_name": "Bacopoulou [DO NOT USE]",
        "practice_name": "New Windsor Dental Wellness",
        "phone": "845-561-2330",
        "email": "nwdw272.info@gmail.com",
        "full_address": "272 Quassaick Ave, Rt 94, NEW WINDSOR, NY 12553",
        "addr1": "272 Quassaick Ave",
        "addr2": "Rt 94",
        "city": "NEW WINDSOR",
        "state": "NY",
        "zip": "12553",
        "route_name": "Local Courier",
        "latitude": 41.4728,
        "longitude": -74.0513,
        "has_coordinates": true
      }
    ],
    "total": 150,
    "limit": 100,
    "offset": 0
  },
  "message": "Customers retrieved successfully"
}
```

### Response (Error)
```json
{
  "success": false,
  "error": {
    "code": "DB_ERROR",
    "message": "Database connection failed"
  }
}
```

---

## 2. POST /api/get-route.php

### 설명
선택된 고객 ID 목록을 기반으로 Google Directions API를 호출하여
최적화된 경로와 ETA를 계산합니다.

### Request
```http
POST /api/get-route.php
Content-Type: application/json
```

### Request Body
```json
{
  "customer_ids": [1, 5, 12, 8, 3],
  "start_location": {
    "lat": 41.5008,
    "lng": -74.0105,
    "address": "123 Office St, Newburgh, NY"
  },
  "end_location": {
    "lat": 41.5008,
    "lng": -74.0105,
    "return_to_start": true
  },
  "departure_time": "2025-01-15T09:00:00",
  "optimize": true,
  "avoid": ["tolls", "highways"]
}
```

### Request Parameters
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| customer_ids | array | Yes | - | 선택된 고객 ID 배열 |
| start_location | object | No | 첫 고객 위치 | 출발지 좌표/주소 |
| end_location | object | No | start_location | 도착지 (원점 회귀) |
| departure_time | string | No | now | 출발 시간 (ISO 8601) |
| optimize | boolean | No | true | 경로 최적화 여부 |
| avoid | array | No | [] | 회피 옵션 (tolls, highways, ferries) |

### Response (Success)
```json
{
  "success": true,
  "data": {
    "route_id": 42,
    "optimized_order": [1, 8, 12, 5, 3],
    "waypoints": [
      {
        "customer_id": 1,
        "account_number": "2878BACPULUCN",
        "practice_name": "New Windsor Dental Wellness",
        "address": "272 Quassaick Ave, NEW WINDSOR, NY 12553",
        "lat": 41.4728,
        "lng": -74.0513,
        "stop_number": 1,
        "eta": "2025-01-15T09:25:00",
        "duration_from_previous": 1500,
        "distance_from_previous": 12500
      },
      {
        "customer_id": 8,
        "account_number": "2728BHALLAMA",
        "practice_name": "Dr. Bhalla",
        "address": "46 Fox st, Poughkeepsie, NY 12601",
        "lat": 41.7005,
        "lng": -73.9209,
        "stop_number": 2,
        "eta": "2025-01-15T10:05:00",
        "duration_from_previous": 2400,
        "distance_from_previous": 28000
      }
    ],
    "summary": {
      "total_distance": 85000,
      "total_distance_text": "85 km",
      "total_duration": 5400,
      "total_duration_text": "1 hour 30 mins",
      "duration_in_traffic": 6200,
      "duration_in_traffic_text": "1 hour 43 mins"
    },
    "polyline": "a~l~Fjk~uOwHJy@P...(encoded)",
    "bounds": {
      "northeast": { "lat": 41.75, "lng": -73.85 },
      "southwest": { "lat": 41.45, "lng": -74.10 }
    }
  },
  "message": "Route optimized successfully"
}
```

### Google Directions API 호출 파라미터
```
GET https://maps.googleapis.com/maps/api/directions/json
  ?origin=41.5008,-74.0105
  &destination=41.5008,-74.0105
  &waypoints=optimize:true|41.4728,-74.0513|41.7005,-73.9209|...
  &departure_time=1705312800
  &traffic_model=best_guess
  &avoid=tolls
  &key=YOUR_GOOGLE_API_KEY
```

---

## 3. POST /api/recalculate.php

### 설명
드라이버의 현재 위치를 기반으로 남은 배달지에 대해
새로운 최적 경로를 계산합니다.

### Request
```http
POST /api/recalculate.php
Content-Type: application/json
```

### Request Body
```json
{
  "route_id": 42,
  "current_location": {
    "lat": 41.5234,
    "lng": -73.9876
  },
  "remaining_customer_ids": [12, 5, 3],
  "completed_customer_ids": [1, 8]
}
```

### Request Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| route_id | int | Yes | 기존 경로 ID |
| current_location | object | Yes | 드라이버 현재 좌표 |
| remaining_customer_ids | array | Yes | 남은 고객 ID 배열 |
| completed_customer_ids | array | No | 완료된 고객 ID 배열 |

### Response (Success)
```json
{
  "success": true,
  "data": {
    "route_id": 42,
    "recalculated_at": "2025-01-15T10:15:00",
    "optimized_order": [12, 3, 5],
    "next_stop": {
      "customer_id": 12,
      "practice_name": "Family Smiles of the Hudson Valley",
      "address": "12 Davis Ave, Poughkeepsie, NY 12603",
      "lat": 41.6875,
      "lng": -73.9065,
      "eta": "2025-01-15T10:28:00",
      "duration": 780,
      "distance": 8500
    },
    "remaining_waypoints": [
      { "customer_id": 12, "stop_number": 1, "eta": "2025-01-15T10:28:00" },
      { "customer_id": 3, "stop_number": 2, "eta": "2025-01-15T10:55:00" },
      { "customer_id": 5, "stop_number": 3, "eta": "2025-01-15T11:20:00" }
    ],
    "summary": {
      "remaining_distance": 42000,
      "remaining_duration": 3900,
      "estimated_completion": "2025-01-15T11:20:00"
    },
    "polyline": "updated_encoded_polyline..."
  },
  "message": "Route recalculated successfully"
}
```

---

## 4. POST /api/add-customer.php

### 설명
JSON 구조 기반으로 신규 고객을 추가합니다.
자동으로 Geocoding API를 호출하여 좌표를 생성합니다.

### Request
```http
POST /api/add-customer.php
Content-Type: application/json
```

### Request Body
```json
{
  "account_number": "NEW123456",
  "title": "Dr.",
  "first_name": "John",
  "last_name": "Smith",
  "practice_name": "Smith Dental Care",
  "phone": "845-555-1234",
  "fax": "845-555-1235",
  "email": "john@smithdental.com",
  "addr1": "100 Main Street",
  "addr2": "Suite 200",
  "addr3": "",
  "city": "Poughkeepsie",
  "state": "NY",
  "zip": "12601",
  "route_name": "Local Courier",
  "ship_to_flag": true,
  "salesperson": "Minsoo Gim",
  "account_class": "D",
  "geocode": true
}
```

### Request Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| account_number | string | Yes | 고유 계정번호 |
| first_name | string | No | 이름 |
| last_name | string | No | 성 |
| practice_name | string | No | 사업장명 |
| phone | string | No | 전화번호 |
| email | string | No | 이메일 |
| addr1 | string | Yes | 주소1 |
| city | string | Yes | 도시 |
| state | string | Yes | 주 코드 |
| zip | string | Yes | 우편번호 |
| route_name | string | No | 경로명 |
| geocode | boolean | No | 좌표 자동 생성 여부 (기본: true) |

### Response (Success)
```json
{
  "success": true,
  "data": {
    "customer_id": 156,
    "account_number": "NEW123456",
    "geocoding": {
      "status": "success",
      "latitude": 41.7056,
      "longitude": -73.9283,
      "formatted_address": "100 Main St #200, Poughkeepsie, NY 12601, USA"
    }
  },
  "message": "Customer added successfully"
}
```

---

## 5. Geocoding API 사용 방식

### 주소 → 좌표 변환
```php
// functions/google.php

function geocodeAddress($address, $apiKey) {
    $url = "https://maps.googleapis.com/maps/api/geocode/json";
    $params = [
        'address' => $address,
        'key' => $apiKey
    ];

    $response = file_get_contents($url . '?' . http_build_query($params));
    $data = json_decode($response, true);

    if ($data['status'] === 'OK') {
        return [
            'success' => true,
            'lat' => $data['results'][0]['geometry']['location']['lat'],
            'lng' => $data['results'][0]['geometry']['location']['lng'],
            'formatted_address' => $data['results'][0]['formatted_address']
        ];
    }

    return ['success' => false, 'error' => $data['status']];
}
```

### 캐시 활용
```php
// Geocoding 결과를 geocoding_cache 테이블에 저장
// 동일 주소 재요청 시 캐시에서 반환 (API 비용 절감)

function getCachedGeocode($address, $pdo) {
    $hash = md5(strtolower(trim($address)));
    $stmt = $pdo->prepare("SELECT * FROM geocoding_cache WHERE address_hash = ?");
    $stmt->execute([$hash]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
```

---

## 6. 오류 처리 규칙

### 오류 코드 체계
| Code | HTTP Status | Description |
|------|-------------|-------------|
| VALIDATION_ERROR | 400 | 요청 파라미터 검증 실패 |
| INVALID_CUSTOMER | 400 | 존재하지 않는 고객 ID |
| NO_COORDINATES | 400 | 좌표 없는 고객 포함 |
| GEOCODING_FAILED | 500 | Geocoding API 실패 |
| DIRECTIONS_FAILED | 500 | Directions API 실패 |
| DB_ERROR | 500 | 데이터베이스 오류 |
| AUTH_ERROR | 401 | 인증 실패 (확장용) |
| RATE_LIMIT | 429 | API 요청 제한 초과 |

### 오류 응답 형식
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "customer_ids is required and must be an array",
    "details": {
      "field": "customer_ids",
      "value": null
    }
  }
}
```

### HTTP 상태 코드 사용
- 200: 성공
- 400: 클라이언트 오류 (잘못된 요청)
- 404: 리소스 없음
- 500: 서버 오류

---

## 7. CORS 설정

모든 API 응답에 다음 헤더를 포함:
```php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

---

➡️ **Phase 4로 이동합니다.**
