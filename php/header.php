<?php
require_once __DIR__ . '/../php/auth.php';
$cartCount = isset($_SESSION['user_id']) ? getCartCount() : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'TokoKu') ?></title>
    <link rel="stylesheet" href="<?= $cssPath ?? 'css/style.css' ?>">
</head>
<body>
<nav class="navbar">
    <a href="<?= $basePath ?? '' ?>index.php" class="brand">🛍️ TokoKu</a>
    <nav>
        <a href="<?= $basePath ?? '' ?>index.php">Produk</a>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="<?= $basePath ?? '' ?>cart.php">Keranjang <span class="cart-badge"><?= $cartCount ?></span></a>
            <a href="<?= $basePath ?? '' ?>orders.php">Pesanan</a>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="<?= $basePath ?? '' ?>admin.php">Admin</a>
            <?php endif; ?>
            <a href="<?= $basePath ?? '' ?>logout.php">Keluar (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a>
        <?php else: ?>
            <a href="<?= $basePath ?? '' ?>login.php">Masuk</a>
            <a href="<?= $basePath ?? '' ?>register.php">Daftar</a>
        <?php endif; ?>
    </nav>
</nav>
