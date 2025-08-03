<?php
require_once __DIR__ . '/../admin/includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['product_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'product_id is required.']);
        exit;
    }
    $product_id = filter_var($_GET['product_id'], FILTER_VALIDATE_INT);

    try {
        $stmt = $pdo->prepare("SELECT id, author, rating, text, date, avatar FROM reviews WHERE product_id = ? ORDER BY date DESC");
        $stmt->execute([$product_id]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $reviews = array_map(function($r) {
            $r['id'] = (int)$r['id'];
            $r['rating'] = (int)$r['rating'];
            return $r;
        }, $reviews);

        echo json_encode($reviews);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['product_id']) || !isset($data['author']) || !isset($data['rating']) || !isset($data['text'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required review data.']);
        exit;
    }

    $product_id = filter_var($data['product_id'], FILTER_VALIDATE_INT);
    $author = trim($data['author']);
    $rating = filter_var($data['rating'], FILTER_VALIDATE_INT);
    $text = trim($data['text']);
    $date = date('Y-m-d');

    if ($product_id === false || empty($author) || $rating === false || empty($text) || $rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO reviews (product_id, author, rating, text, date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$product_id, $author, $rating, $text, $date]);
        $new_review_id = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'review' => [
                'id' => (int)$new_review_id,
                'product_id' => $product_id,
                'author' => $author,
                'rating' => $rating,
                'text' => $text,
                'date' => $date,
                'avatar' => null
            ]
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
}
?>
