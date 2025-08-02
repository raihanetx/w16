<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Create orders table
$pdo->exec("CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name TEXT NOT NULL,
    total_price REAL NOT NULL,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Create order_items table
$pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL,
    price_per_unit REAL NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
)");


// --- Sample Data Insertion (for demonstration) ---
// To avoid re-inserting data on every page load, we check if orders table is empty
$order_count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
if ($order_count == 0) {
    // Sample Categories
    $pdo->exec("INSERT OR IGNORE INTO categories (name) VALUES ('Electronics'), ('Books')");
    $cat_electronics_id = $pdo->lastInsertId();
    $cat_books_id = $pdo->query("SELECT id FROM categories WHERE name = 'Books'")->fetchColumn();

    // Sample Products
    $pdo->exec("INSERT OR IGNORE INTO products (name, description, price, category_id) VALUES ('Laptop', 'A cool laptop', 999.99, $cat_electronics_id)");
    $prod_laptop_id = $pdo->lastInsertId();
    $pdo->exec("INSERT OR IGNORE INTO products (name, description, price, category_id) VALUES ('PHP for Beginners', 'A great book', 29.99, $cat_books_id)");
    $prod_book_id = $pdo->lastInsertId();

    // Sample Order 1
    $pdo->exec("INSERT INTO orders (customer_name, total_price) VALUES ('John Doe', 1029.98)");
    $order1_id = $pdo->lastInsertId();
    $pdo->exec("INSERT INTO order_items (order_id, product_id, quantity, price_per_unit) VALUES ($order1_id, $prod_laptop_id, 1, 999.99)");
    $pdo->exec("INSERT INTO order_items (order_id, product_id, quantity, price_per_unit) VALUES ($order1_id, $prod_book_id, 1, 29.99)");

    // Sample Order 2
    $pdo->exec("INSERT INTO orders (customer_name, total_price) VALUES ('Jane Smith', 59.98)");
    $order2_id = $pdo->lastInsertId();
    $pdo->exec("INSERT INTO order_items (order_id, product_id, quantity, price_per_unit) VALUES ($order2_id, $prod_book_id, 2, 29.99)");
}
// --- End of Sample Data ---


// Fetch all orders
$orders = $pdo->query("SELECT * FROM orders ORDER BY order_date DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all order items
$order_items_stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .order { border: 1px solid #ddd; border-radius: 5px; margin-bottom: 1.5rem; }
        .order-header { background-color: #f7f7f7; padding: 1rem; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; }
        .order-body { padding: 1rem; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Manage Orders</h1>
            <a href="index.php" class="back-btn">Back to Dashboard</a>
        </header>
        <main>
            <div class="list-container">
                <h2>All Orders</h2>
                <?php if (empty($orders)): ?>
                    <p>No orders found.</p>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="order">
                            <div class="order-header">
                                <div>
                                    <strong>Order ID:</strong> <?php echo htmlspecialchars($order['id']); ?><br>
                                    <strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?>
                                </div>
                                <div>
                                    <strong>Date:</strong> <?php echo htmlspecialchars(date("Y-m-d H:i", strtotime($order['order_date']))); ?><br>
                                    <strong>Total:</strong> $<?php echo htmlspecialchars(number_format($order['total_price'], 2)); ?>
                                </div>
                            </div>
                            <div class="order-body">
                                <h4>Items:</h4>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Price per Unit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $order_items_stmt->execute([$order['id']]);
                                        $items = $order_items_stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($items as $item):
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                            <td>$<?php echo htmlspecialchars(number_format($item['price_per_unit'], 2)); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
