<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Create products table if it doesn't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    price REAL NOT NULL,
    category_id INTEGER NOT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id)
)");

$error_message = '';
$success_message = '';

// Fetch categories for the dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $category_id = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);

    if (empty($name) || $price === false || $category_id === false) {
        $error_message = "Please fill in all required fields correctly.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $category_id]);
            $success_message = "Product '$name' added successfully.";
        } catch (PDOException $e) {
            $error_message = "Error adding product: " . $e->getMessage();
        }
    }
}

// Fetch all products with category names
$products = $pdo->query("
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.name
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Manage Products</h1>
            <a href="index.php" class="back-btn">Back to Dashboard</a>
        </header>
        <main>
            <div class="form-container">
                <h2>Add New Product</h2>
                <?php if ($success_message): ?>
                    <p class="success"><?php echo $success_message; ?></p>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <p class="error"><?php echo $error_message; ?></p>
                <?php endif; ?>
                <form action="products.php" method="post">
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="price">Price</label>
                        <input type="text" id="price" name="price" required>
                    </div>
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="add_product">Add Product</button>
                </form>
            </div>

            <div class="list-container">
                <h2>Existing Products</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="4">No products found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['id']); ?></td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td>$<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
