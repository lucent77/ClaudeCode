-- =====================================================
-- Smart Delivery Route Optimizer - Database Schema
-- Hostinger MySQL Database
-- =====================================================

-- Set character set
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- A. Customers Table (고객 테이블)
-- =====================================================
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_number` VARCHAR(50) NOT NULL COMMENT '고유 계정번호 (Account #)',
    `title` VARCHAR(20) DEFAULT '' COMMENT '호칭 (Dr., Mr., Ms.)',
    `first_name` VARCHAR(100) DEFAULT '' COMMENT '이름 (FName)',
    `last_name` VARCHAR(100) DEFAULT '' COMMENT '성 (LName)',
    `practice_name` VARCHAR(255) DEFAULT '' COMMENT '사업장명 (PracticeName)',
    `phone` VARCHAR(30) DEFAULT '' COMMENT '전화번호',
    `fax` VARCHAR(30) DEFAULT '' COMMENT '팩스번호',
    `cell_phone` VARCHAR(30) DEFAULT '' COMMENT '휴대전화 (CellPh)',
    `email` VARCHAR(255) DEFAULT '' COMMENT '이메일 (PrimaryEmail)',
    `stmt_email` VARCHAR(255) DEFAULT '' COMMENT '명세서 이메일 (Stmt Email)',
    `addr1` VARCHAR(255) DEFAULT '' COMMENT '주소1',
    `addr2` VARCHAR(255) DEFAULT '' COMMENT '주소2',
    `addr3` VARCHAR(255) DEFAULT '' COMMENT '주소3',
    `city` VARCHAR(100) DEFAULT '' COMMENT '도시',
    `state` VARCHAR(10) DEFAULT '' COMMENT '주 코드 (statecd)',
    `zip` VARCHAR(20) DEFAULT '' COMMENT '우편번호 (zipcd)',
    `route_name` VARCHAR(100) DEFAULT '' COMMENT '경로명 (RouteName)',
    `ship_to_flag` TINYINT(1) DEFAULT 0 COMMENT '배송 가능 여부',
    `salesperson` VARCHAR(100) DEFAULT '' COMMENT '담당 영업사원',
    `account_class` VARCHAR(10) DEFAULT '' COMMENT '계정 분류',
    `account_type` INT DEFAULT 0 COMMENT '계정 유형',
    `account_manager` VARCHAR(100) DEFAULT '' COMMENT '계정 관리자 (AcctMgr)',
    `territory` VARCHAR(100) DEFAULT '' COMMENT '지역',
    `latitude` DECIMAL(10, 8) DEFAULT NULL COMMENT '위도 (Geocoding API 생성)',
    `longitude` DECIMAL(11, 8) DEFAULT NULL COMMENT '경도 (Geocoding API 생성)',
    `geocoded_at` DATETIME DEFAULT NULL COMMENT '좌표 생성 시간',
    `date_created` DATETIME DEFAULT NULL COMMENT '원본 생성일 (DateCreated)',
    `extended_json` TEXT DEFAULT NULL COMMENT '기타 확장 필드 JSON 저장',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '레코드 생성 시간',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '레코드 수정 시간',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_account_number` (`account_number`),
    INDEX `idx_route_name` (`route_name`),
    INDEX `idx_city_state` (`city`, `state`),
    INDEX `idx_salesperson` (`salesperson`),
    INDEX `idx_ship_to_flag` (`ship_to_flag`),
    INDEX `idx_coordinates` (`latitude`, `longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='고객 정보 테이블';

-- =====================================================
-- B. Delivery Routes Table (배송 경로 테이블)
-- =====================================================
DROP TABLE IF EXISTS `delivery_routes`;
CREATE TABLE `delivery_routes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `route_name` VARCHAR(100) DEFAULT '' COMMENT '경로 이름',
    `customer_ids` JSON NOT NULL COMMENT '선택된 고객 ID 배열',
    `optimized_order` JSON DEFAULT NULL COMMENT '최적화된 순서 배열',
    `waypoints` JSON DEFAULT NULL COMMENT '경유지 좌표 배열',
    `start_location` JSON DEFAULT NULL COMMENT '출발지 좌표 {lat, lng}',
    `end_location` JSON DEFAULT NULL COMMENT '도착지 좌표 {lat, lng}',
    `start_time` DATETIME DEFAULT NULL COMMENT '출발 예정 시간',
    `total_distance` INT UNSIGNED DEFAULT 0 COMMENT '총 거리 (meters)',
    `total_duration` INT UNSIGNED DEFAULT 0 COMMENT '총 소요시간 (seconds)',
    `duration_in_traffic` INT UNSIGNED DEFAULT 0 COMMENT '교통 반영 소요시간 (seconds)',
    `polyline` TEXT DEFAULT NULL COMMENT '인코딩된 경로 Polyline',
    `legs_data` JSON DEFAULT NULL COMMENT '각 구간별 상세 정보',
    `status` ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending' COMMENT '경로 상태',
    `driver_id` INT UNSIGNED DEFAULT NULL COMMENT '담당 드라이버 ID (확장용)',
    `notes` TEXT DEFAULT NULL COMMENT '메모',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
    PRIMARY KEY (`id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_start_time` (`start_time`),
    INDEX `idx_driver_id` (`driver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='배송 경로 테이블';

-- =====================================================
-- C. Route History Table (경로 이력 테이블)
-- =====================================================
DROP TABLE IF EXISTS `route_history`;
CREATE TABLE `route_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `route_id` INT UNSIGNED NOT NULL COMMENT 'delivery_routes.id 참조',
    `action_type` VARCHAR(50) NOT NULL COMMENT 'created/recalculated/completed/cancelled',
    `driver_lat` DECIMAL(10, 8) DEFAULT NULL COMMENT '드라이버 위치 위도',
    `driver_lng` DECIMAL(11, 8) DEFAULT NULL COMMENT '드라이버 위치 경도',
    `snapshot_data` JSON DEFAULT NULL COMMENT '당시 경로 스냅샷',
    `notes` VARCHAR(255) DEFAULT '' COMMENT '비고',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '기록 시간',
    PRIMARY KEY (`id`),
    INDEX `idx_route_id` (`route_id`),
    INDEX `idx_action_type` (`action_type`),
    INDEX `idx_created_at` (`created_at`),
    CONSTRAINT `fk_route_history_route`
        FOREIGN KEY (`route_id`)
        REFERENCES `delivery_routes` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='경로 이력 테이블';

-- =====================================================
-- D. Geocoding Cache Table (지오코딩 캐시)
-- =====================================================
DROP TABLE IF EXISTS `geocoding_cache`;
CREATE TABLE `geocoding_cache` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `address_hash` VARCHAR(64) NOT NULL COMMENT '주소 해시 (MD5)',
    `full_address` VARCHAR(500) NOT NULL COMMENT '전체 주소',
    `latitude` DECIMAL(10, 8) NOT NULL COMMENT '위도',
    `longitude` DECIMAL(11, 8) NOT NULL COMMENT '경도',
    `formatted_address` VARCHAR(500) DEFAULT '' COMMENT 'Google 포맷 주소',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '캐시 생성 시간',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_address_hash` (`address_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='지오코딩 API 캐시';

-- =====================================================
-- E. API Logs Table (API 호출 로그) - 선택사항
-- =====================================================
DROP TABLE IF EXISTS `api_logs`;
CREATE TABLE `api_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `api_type` VARCHAR(50) NOT NULL COMMENT 'geocoding/directions/distance_matrix',
    `request_data` TEXT DEFAULT NULL COMMENT '요청 데이터',
    `response_status` VARCHAR(50) DEFAULT '' COMMENT 'OK/ZERO_RESULTS/ERROR',
    `response_data` TEXT DEFAULT NULL COMMENT '응답 데이터 (요약)',
    `execution_time` DECIMAL(10, 4) DEFAULT 0 COMMENT '실행 시간 (초)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '호출 시간',
    PRIMARY KEY (`id`),
    INDEX `idx_api_type` (`api_type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Google API 호출 로그';

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 초기 데이터 (테스트용)
-- =====================================================

-- 테스트 경로 데이터는 import_json_customers.php로 생성됨
