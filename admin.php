<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
require_once __DIR__ . '/php/auth.php';
requireAdmin();

$pdo = getDB();
$action = $_GET['action'] ?? 'products';

$msg = '';
$error = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    if ($act === 'add_product') {
        $name  = trim($_POST['name'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $price = $_POST['price'] ?? '';
        $stock = $_POST['stock'] ?? '';

        if ($name === '' || $price === '' || $stock === '') {
            $error = 'Nama, harga, dan stok wajib diisi.';
        } elseif (!is_numeric($price) || $price < 0) {
            $error = 'Harga harus berupa angka positif.';
        } elseif (!ctype_digit($stock) || $stock < 0) {
            $error = 'Stok harus berupa angka bulat positif.';
        } else {
            $pdo->prepare("INSERT INTO products (name, description, price, stock) VALUES (?, ?, ?, ?)")
                ->execute([$name, $desc, (float)$price, (int)$stock]);
            $msg = 'Produk berhasil ditambahkan.';
        }

    } elseif ($act === 'edit_product') {
        $id    = (int)($_POST['id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $price = $_POST['price'] ?? '';
        $stock = $_POST['stock'] ?? '';

        if ($name === '' || $price === '' || $stock === '') {
            $error = 'Nama, harga, dan stok wajib diisi.';
        } elseif (!is_numeric($price) || $price < 0) {
            $error = 'Harga harus berupa angka positif.';
        } else {
            $pdo->prepare("UPDATE products SET name=?, description=?, price=?, stock=? WHERE id=?")
                ->execute([$name, $desc, (float)$price, (int)$stock, $id]);
            $msg = 'Produk berhasil diperbarui.';
        }

    } elseif ($act === 'delete_product') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        $msg = 'Produk berhasil dihapus.';
    }
}

$products = $pdo->query("SELECT * FROM products ORDER BY name")->fetchAll();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status='delivered'")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();

$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editProduct = $stmt->fetch();
}

$pageTitle = 'Admin — TokoKu';
include __DIR__ . '/php/header.php';
?>
<div class="container">
    <div class="page-header"><h1>Panel Admin</h1></div>

    <div class="stats-row">
        <div class="stat-card"><div class="stat-label">Total Produk</div><div class="stat-value"><?= count($products) ?></div></div>
        <div class="stat-card"><div class="stat-label">Total Pesanan</div><div class="stat-value"><?= $totalOrders ?></div></div>
        <div class="stat-card"><div class="stat-label">Pendapatan</div><div class="stat-value" style="font-size:1.2rem;">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></div></div>
        <div class="stat-card"><div class="stat-label">Pelanggan</div><div class="stat-value"><?= $totalUsers ?></div></div>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Add/Edit Product Form -->
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-header"><?= $editProduct ? 'Edit Produk' : 'Tambah Produk' ?></div>
        <div class="card-body">
            <form method="post" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:.75rem;align-items:end;">
                <input type="hidden" name="action" value="<?= $editProduct ? 'edit_product' : 'add_product' ?>">
                <?php if ($editProduct): ?><input type="hidden" name="id" value="<?= $editProduct['id'] ?>"><?php endif; ?>
                <div class="form-group" style="margin:0;">
                    <label>Nama Produk</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Deskripsi</label>
                    <input type="text" name="description" value="<?= htmlspecialchars($editProduct['description'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Harga (Rp)</label>
                    <input type="number" name="price" value="<?= $editProduct['price'] ?? '' ?>" min="0" step="0.01" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Stok</label>
                    <input type="number" name="stock" value="<?= $editProduct['stock'] ?? '' ?>" min="0" required>
                </div>
                <button type="submit" class="btn btn-<?= $editProduct ? 'primary' : 'success' ?>"><?= $editProduct ? 'Update' : 'Tambah' ?></button>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-header">Daftar Produk</div>
        <div class="card-body" style="padding:0;">
            <table>
                <thead><tr><th>#</th><th>Nama</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td>Rp <?= number_format($p['price'], 0, ',', '.') ?></td>
                    <td><?= $p['stock'] ?></td>
                    <td style="display:flex;gap:.4rem;">
                        <a href="admin.php?edit=<?= $p['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                        <form method="post" onsubmit="return confirm('Hapus produk ini?')">
                            <input type="hidden" name="action" value="delete_product">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div style="margin-top:1rem;"><a href="orders.php" class="btn btn-secondary">Lihat Semua Pesanan</a></div>
</div>
</body>
</html>
