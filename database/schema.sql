-- 경품 추첨 프로그램 데이터베이스 스키마
-- Hostinger MySQL 데이터베이스에서 실행하세요

-- 상품 테이블
CREATE TABLE IF NOT EXISTS prizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    probability DECIMAL(5,2) NOT NULL COMMENT '당첨 확률 (%)',
    color VARCHAR(7) DEFAULT '#3B82F6' COMMENT '룰렛 섹션 색상',
    stock INT DEFAULT -1 COMMENT '재고 (-1: 무제한)',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_probability (probability)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 당첨자 테이블
CREATE TABLE IF NOT EXISTS winners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    prize_id INT NOT NULL,
    prize_name VARCHAR(255) NOT NULL COMMENT '당첨 당시 상품명 (기록용)',
    ip_address VARCHAR(45),
    user_agent TEXT,
    drawn_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prize_id) REFERENCES prizes(id) ON DELETE RESTRICT,
    INDEX idx_email (email),
    INDEX idx_drawn_at (drawn_at),
    INDEX idx_prize_id (prize_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 샘플 데이터 삽입
INSERT INTO prizes (name, probability, color, stock) VALUES
('1등 - iPhone 15 Pro', 1.00, '#EF4444', 2),
('2등 - AirPods Pro', 3.00, '#F59E0B', 5),
('3등 - 스타벅스 기프티콘 5만원', 10.00, '#10B981', 20),
('4등 - 스타벅스 기프티콘 1만원', 20.00, '#3B82F6', 50),
('5등 - 편의점 기프티콘 5천원', 30.00, '#8B5CF6', 100),
('꽝', 36.00, '#6B7280', -1);

-- 확률 합계 확인 (100%여야 함)
SELECT SUM(probability) as total_probability FROM prizes WHERE is_active = 1;
