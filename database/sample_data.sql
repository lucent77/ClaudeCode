-- 샘플 데이터 추가 SQL
-- 이미 schema.sql에서 기본 샘플이 들어가 있지만,
-- 추가 샘플이나 데이터 초기화가 필요한 경우 사용하세요

-- 기존 데이터 삭제 (주의: 모든 데이터가 삭제됩니다!)
-- TRUNCATE TABLE winners;
-- TRUNCATE TABLE prizes;

-- 예시 1: 일반 전시회용 상품 설정
INSERT INTO prizes (name, probability, color, stock, is_active) VALUES
('특등 - 최신 스마트폰', 0.5, '#DC2626', 1, 1),
('1등 - 무선 이어폰', 2.0, '#EA580C', 5, 1),
('2등 - 백화점 상품권 5만원', 5.0, '#CA8A04', 10, 1),
('3등 - 스타벅스 기프티콘', 15.0, '#16A34A', 30, 1),
('4등 - 편의점 상품권 1만원', 25.0, '#2563EB', 50, 1),
('참가상 - 음료 쿠폰', 30.0, '#9333EA', 100, 1),
('꽝', 22.5, '#6B7280', -1, 1);
-- 확률 합계: 100%

-- 예시 2: 명품 이벤트용 상품 설정 (비활성화)
-- INSERT INTO prizes (name, probability, color, stock, is_active) VALUES
-- ('1등 - 명품 가방', 1.0, '#DC2626', 1, 0),
-- ('2등 - 명품 지갑', 4.0, '#EA580C', 3, 0),
-- ('3등 - 향수 세트', 10.0, '#CA8A04', 10, 0),
-- ('4등 - 립스틱 세트', 20.0, '#16A34A', 20, 0),
-- ('꽝', 65.0, '#6B7280', -1, 0);

-- 예시 3: 테스트용 당첨자 데이터 (개발/테스트용)
-- INSERT INTO winners (email, prize_id, prize_name, ip_address) VALUES
-- ('test1@example.com', 1, '특등 - 최신 스마트폰', '127.0.0.1'),
-- ('test2@example.com', 2, '1등 - 무선 이어폰', '127.0.0.1'),
-- ('test3@example.com', 3, '2등 - 백화점 상품권 5만원', '127.0.0.1');

-- 확률 합계 확인 쿼리
SELECT
    SUM(probability) as total_probability,
    COUNT(*) as active_prizes
FROM prizes
WHERE is_active = 1;

-- 상품별 당첨 통계 확인 쿼리
SELECT
    p.name,
    p.probability as set_probability,
    COUNT(w.id) as win_count,
    ROUND(COUNT(w.id) * 100.0 / (SELECT COUNT(*) FROM winners), 2) as actual_probability
FROM prizes p
LEFT JOIN winners w ON p.id = w.prize_id
GROUP BY p.id, p.name, p.probability
ORDER BY p.probability DESC;
