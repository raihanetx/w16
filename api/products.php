<?php
require_once __DIR__ . '/../admin/includes/db.php';

header('Content-Type: application/json');

try {
    $pdo->exec("PRAGMA foreign_keys = ON;");

    // Fetch all products with their category name
    $products_stmt = $pdo->query("
        SELECT
            p.id, p.name, p.description, p.longDescription, p.price,
            p.image, p.isFeatured, p.stock, p.slug,
            c.name as category
        FROM products p
        JOIN categories c ON p.category_id = c.id
    ");
    $products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare statements to fetch related data
    $durations_stmt = $pdo->prepare("SELECT label, price FROM product_durations WHERE product_id = ?");
    $reviews_stmt = $pdo->prepare("SELECT id, author, rating, text, date, avatar FROM reviews WHERE product_id = ? ORDER BY date DESC");

    $output = [];
    foreach ($products as $product) {
        // Convert types for consistency with original JS
        $product['id'] = (int)$product['id'];
        $product['price'] = (float)$product['price'];
        $product['isFeatured'] = (bool)$product['isFeatured'];
        $product['stock'] = (int)$product['stock'];
        $product['category'] = strtolower($product['category']);

        // Fetch durations
        $durations_stmt->execute([$product['id']]);
        $durations = $durations_stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($durations) {
            $product['durations'] = array_map(function($d) {
                $d['price'] = (float)$d['price'];
                return $d;
            }, $durations);
        }

        // Fetch reviews
        $reviews_stmt->execute([$product['id']]);
        $reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($reviews) {
            $product['reviews'] = array_map(function($r) {
                $r['id'] = (int)$r['id'];
                $r['rating'] = (int)$r['rating'];
                return $r;
            }, $reviews);
        } else {
            $product['reviews'] = [];
        }

        $output[] = $product;
    }

    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
