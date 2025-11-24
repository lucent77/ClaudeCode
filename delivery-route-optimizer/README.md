# Smart Delivery Route Optimizer

JSON 기반 고객 데이터를 활용한 배송 경로 최적화 웹 애플리케이션

## Features

- **고객 관리**: JSON 파일에서 고객 데이터 자동 Import
- **경로 최적화**: Google Directions API를 활용한 최적 배송 경로 계산
- **실시간 지도**: Google Maps로 경로 시각화 및 마커 표시
- **ETA 계산**: 교통 상황을 반영한 예상 도착 시간 계산
- **실시간 추적**: Geolocation API를 활용한 드라이버 위치 추적
- **재경로화**: 현재 위치 기반 경로 재계산

## Tech Stack

- **Backend**: PHP 8.x
- **Database**: MySQL 8.x
- **Frontend**: HTML5, Tailwind CSS, Vanilla JavaScript
- **Maps**: Google Maps JavaScript API
- **APIs**: Google Geocoding, Directions API
- **Hosting**: Hostinger (PHP Shared Hosting)

## Quick Start

### 1. 데이터베이스 설정
```bash
mysql -u username -p database_name < database/schema.sql
```

### 2. 설정 파일 수정
```php
// config/db.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

// config/config.php
define('GOOGLE_API_KEY', 'your_google_api_key');
```

### 3. 고객 데이터 Import
```bash
php import_json_customers.php data/sample_customers.json
```

### 4. 웹 브라우저에서 접속
```
https://yourdomain.com/
```

## File Structure

```
delivery-route-optimizer/
├── index.php                    # 메인 고객 선택 페이지
├── route-view.php               # 경로 시각화 페이지
├── live-tracking.php            # 실시간 추적 페이지
├── import_json_customers.php    # JSON Import 스크립트
├── config/
│   ├── db.php                   # DB 연결 설정
│   └── config.php               # API Keys, 환경설정
├── functions/
│   ├── google.php               # Google API 함수
│   ├── route.php                # 경로 최적화 로직
│   └── customer_import.php      # Import 함수
├── api/
│   ├── customers.php            # GET: 고객 목록
│   ├── get-route.php            # POST: 경로 생성
│   ├── recalculate.php          # POST: 재경로화
│   └── add-customer.php         # POST: 고객 추가
├── assets/
│   ├── css/style.css
│   └── js/app.js
├── data/
│   └── sample_customers.json
├── database/
│   └── schema.sql
└── docs/
    ├── 01-ARCHITECTURE.md
    ├── 02-DATABASE.md
    ├── 03-API-SPECIFICATION.md
    └── 06-DEPLOYMENT.md
```

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/customers.php` | GET | 고객 목록 조회 |
| `/api/get-route.php` | POST | 경로 최적화 요청 |
| `/api/recalculate.php` | POST | 재경로화 요청 |
| `/api/add-customer.php` | POST | 신규 고객 추가 |

## JSON Data Format

```json
{
  "Account #": "2878BACPULUCN",
  "Title": "Dr.",
  "FName": "John",
  "LName": "Doe",
  "PracticeName": "Example Dental",
  "Phone": "845-555-1234",
  "addr1": "123 Main St",
  "city": "Poughkeepsie",
  "statecd": "NY",
  "zipcd": "12601",
  "RouteName": "Local Courier"
}
```

## Google API Setup

1. [Google Cloud Console](https://console.cloud.google.com) 접속
2. 프로젝트 생성
3. APIs 활성화:
   - Maps JavaScript API
   - Geocoding API
   - Directions API
4. API Key 생성 및 도메인 제한 설정

## Documentation

- [Architecture Guide](docs/01-ARCHITECTURE.md)
- [Database Schema](docs/02-DATABASE.md)
- [API Specification](docs/03-API-SPECIFICATION.md)
- [Deployment Guide](docs/06-DEPLOYMENT.md)

## License

MIT License
