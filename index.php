<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());

require_once __DIR__ . '/php/auth.php';

$search = trim($_GET['search'] ?? '');
$pdo = getDB();

if ($search !== '') {
    // BUG 6: Kesalahan logika SQL — hanya mencari di kolom name, bukan description
    // juga menggunakan = bukan LIKE sehingga pencarian harus persis
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name = ? ORDER BY name");
    $stmt->execute([$search]);
} else {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY name");
}
$products = $stmt->fetchAll();

$pageTitle = 'Beranda — TokoKu';
include __DIR__ . '/php/header.php';
?>
<div class="container">
    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type'] ?>"><?= htmlspecialchars($_SESSION['flash']['msg']) ?></div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="page-header">
        <h1>Daftar Produk</h1>
        <form method="get" style="display:flex;gap:.5rem;">
            <input type="text" name="search" placeholder="Cari produk..." value="<?= htmlspecialchars($search) ?>" style="padding:.5rem .9rem;border:1px solid #ddd;border-radius:6px;">
            <button type="submit" class="btn btn-primary">Cari</button>
            <?php if ($search): ?><a href="index.php" class="btn btn-secondary">Reset</a><?php endif; ?>
        </form>
    </div>

    <?php if (empty($products)): ?>
        <p style="color:#888;text-align:center;padding:3rem 0;">Tidak ada produk ditemukan.</p>
    <?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $p): ?>
        <div class="product-card">
            <div class="product-img">📦</div>
            <div class="product-info">
                <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                <div class="product-price">Rp <?= number_format($p['price'], 0, ',', '.') ?></div>
                <div class="product-stock">Stok: <?= $p['stock'] ?></div>
                <p style="font-size:.82rem;color:#777;margin-bottom:.75rem;"><?= htmlspecialchars(mb_strimwidth($p['description'], 0, 80, '...')) ?></p>
                <?php if (!empty($_SESSION['user_id']) && $p['stock'] > 0): ?>
                <form method="post" action="cart.php">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn btn-primary btn-sm">+ Keranjang</button>
                </form>
                <?php elseif ($p['stock'] == 0): ?>
                    <span class="badge badge-danger">Habis</span>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary btn-sm">Masuk untuk beli</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
