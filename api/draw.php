<?php
/**
 * 추첨 실행 API
 * POST /api/draw.php
 * 요청: { "email": "user@example.com" }
 */

require_once 'config.php';

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'error' => 'Invalid request method'], 405);
}

try {
    // 입력 데이터 받기
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['email']) || empty($input['email'])) {
        sendJSON(['success' => false, 'error' => '이메일 주소를 입력해주세요.'], 400);
    }

    $email = sanitizeInput($input['email']);

    // 이메일 유효성 검사
    if (!validateEmail($email)) {
        sendJSON(['success' => false, 'error' => '유효한 이메일 주소를 입력해주세요.'], 400);
    }

    $pdo = getDBConnection();
    $pdo->beginTransaction();

    // 활성화된 상품 목록 가져오기 (재고 확인 포함)
    $stmt = $pdo->prepare("
        SELECT id, name, probability, color, stock
        FROM prizes
        WHERE is_active = 1
        AND (stock = -1 OR stock > 0)
        ORDER BY id
    ");
    $stmt->execute();
    $prizes = $stmt->fetchAll();

    if (empty($prizes)) {
        $pdo->rollBack();
        sendJSON(['success' => false, 'error' => '현재 추첨 가능한 상품이 없습니다.'], 400);
    }

    // 확률 기반 추첨 로직
    $totalProbability = array_sum(array_column($prizes, 'probability'));
    $random = mt_rand(0, $totalProbability * 100) / 100;

    $cumulativeProbability = 0;
    $selectedPrize = null;

    foreach ($prizes as $prize) {
        $cumulativeProbability += $prize['probability'];
        if ($random <= $cumulativeProbability) {
            $selectedPrize = $prize;
            break;
        }
    }

    // 만약 선택되지 않았다면 마지막 상품 선택
    if ($selectedPrize === null) {
        $selectedPrize = end($prizes);
    }

    // 당첨 결과 저장
    $stmt = $pdo->prepare("
        INSERT INTO winners (email, prize_id, prize_name, ip_address, user_agent)
        VALUES (:email, :prize_id, :prize_name, :ip_address, :user_agent)
    ");

    $stmt->execute([
        'email' => $email,
        'prize_id' => $selectedPrize['id'],
        'prize_name' => $selectedPrize['name'],
        'ip_address' => getClientIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    // 재고 차감 (재고가 있는 경우)
    if ($selectedPrize['stock'] > 0) {
        $stmt = $pdo->prepare("
            UPDATE prizes
            SET stock = stock - 1
            WHERE id = :prize_id AND stock > 0
        ");
        $stmt->execute(['prize_id' => $selectedPrize['id']]);
    }

    $pdo->commit();

    // 당첨 결과 반환
    sendJSON([
        'success' => true,
        'winner' => [
            'email' => $email,
            'prize' => [
                'id' => $selectedPrize['id'],
                'name' => $selectedPrize['name'],
                'color' => $selectedPrize['color']
            ],
            'message' => "축하합니다! '{$selectedPrize['name']}' 에 당첨되셨습니다!"
        ]
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Draw Error: " . $e->getMessage());
    sendJSON([
        'success' => false,
        'error' => '추첨 처리 중 오류가 발생했습니다.'
    ], 500);
}
