<?php
/**
 * 상품 목록 조회 API
 * GET /api/get_prizes.php
 */

require_once 'config.php';

try {
    $pdo = getDBConnection();

    // 활성화된 상품만 조회
    $stmt = $pdo->prepare("
        SELECT id, name, probability, color, stock
        FROM prizes
        WHERE is_active = 1
        ORDER BY probability DESC
    ");

    $stmt->execute();
    $prizes = $stmt->fetchAll();

    // 확률 합계 계산
    $totalProbability = array_sum(array_column($prizes, 'probability'));

    sendJSON([
        'success' => true,
        'prizes' => $prizes,
        'total_probability' => $totalProbability,
        'count' => count($prizes)
    ]);

} catch (Exception $e) {
    error_log("Get Prizes Error: " . $e->getMessage());
    sendJSON([
        'success' => false,
        'error' => '상품 목록을 불러오는데 실패했습니다.'
    ], 500);
}
