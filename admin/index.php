<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Admin Dashboard</h1>
            <a href="logout.php" class="logout-btn">Logout</a>
        </header>
        <nav>
            <ul>
                <li><a href="categories.php">Manage Categories</a></li>
                <li><a href="products.php">Manage Products</a></li>
                <li><a href="orders.php">Manage Orders</a></li>
                <li><a href="coupons.php">Manage Coupons</a></li>
            </ul>
        </nav>
        <main>
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?>!</h2>
            <p>Select an option from the navigation menu to get started.</p>
        </main>
    </div>
</body>
</html>
