<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Create coupons table
$pdo->exec("CREATE TABLE IF NOT EXISTS coupons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    discount_percent REAL NOT NULL,
    is_active INTEGER NOT NULL DEFAULT 1
)");

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = trim($_POST['code']);
    $discount = filter_var($_POST['discount_percent'], FILTER_VALIDATE_FLOAT);

    if (empty($code) || $discount === false) {
        $error_message = "Please fill in all fields correctly.";
    } elseif ($discount <= 0 || $discount > 100) {
        $error_message = "Discount must be between 0 and 100.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_percent) VALUES (?, ?)");
            $stmt->execute([$code, $discount]);
            $success_message = "Coupon '$code' added successfully.";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error_message = "Coupon code '$code' already exists.";
            } else {
                $error_message = "Error adding coupon: " . $e->getMessage();
            }
        }
    }
}

// Fetch all coupons
$coupons = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Coupons</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Manage Coupons</h1>
            <a href="index.php" class="back-btn">Back to Dashboard</a>
        </header>
        <main>
            <div class="form-container">
                <h2>Add New Coupon</h2>
                <?php if ($success_message): ?>
                    <p class="success"><?php echo $success_message; ?></p>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <p class="error"><?php echo $error_message; ?></p>
                <?php endif; ?>
                <form action="coupons.php" method="post">
                    <div class="form-group">
                        <label for="code">Coupon Code</label>
                        <input type="text" id="code" name="code" required>
                    </div>
                    <div class="form-group">
                        <label for="discount_percent">Discount (%)</label>
                        <input type="text" id="discount_percent" name="discount_percent" required>
                    </div>
                    <button type="submit" name="add_coupon">Add Coupon</button>
                </form>
            </div>

            <div class="list-container">
                <h2>Existing Coupons</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Code</th>
                            <th>Discount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($coupons)): ?>
                            <tr>
                                <td colspan="4">No coupons found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($coupons as $coupon): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($coupon['id']); ?></td>
                                    <td><?php echo htmlspecialchars($coupon['code']); ?></td>
                                    <td><?php echo htmlspecialchars($coupon['discount_percent']); ?>%</td>
                                    <td><?php echo $coupon['is_active'] ? 'Active' : 'Inactive'; ?></td>
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
