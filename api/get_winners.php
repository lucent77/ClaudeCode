<?php
/**
 * 당첨자 목록 조회 API
 * GET /api/get_winners.php?limit=100&offset=0
 */

require_once 'config.php';

try {
    $pdo = getDBConnection();

    // 페이징 파라미터
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    // 최대 limit 제한
    $limit = min($limit, 1000);

    // 당첨자 목록 조회
    $stmt = $pdo->prepare("
        SELECT
            w.id,
            w.email,
            w.prize_name,
            w.drawn_at,
            p.color as prize_color
        FROM winners w
        LEFT JOIN prizes p ON w.prize_id = p.id
        ORDER BY w.drawn_at DESC
        LIMIT :limit OFFSET :offset
    ");

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $winners = $stmt->fetchAll();

    // 전체 당첨자 수 조회
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM winners");
    $totalCount = $countStmt->fetch()['total'];

    sendJSON([
        'success' => true,
        'winners' => $winners,
        'total' => $totalCount,
        'limit' => $limit,
        'offset' => $offset
    ]);

} catch (Exception $e) {
    error_log("Get Winners Error: " . $e->getMessage());
    sendJSON([
        'success' => false,
        'error' => '당첨자 목록을 불러오는데 실패했습니다.'
    ], 500);
}
