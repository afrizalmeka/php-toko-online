<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
require_once __DIR__ . '/php/auth.php';
requireLogin();

$pdo = getDB();

$stmt = $pdo->prepare("SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.stock
    FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll();

if (empty($items)) {
    header('Location: cart.php');
    exit;
}

// BUG sama dengan cart.php — total dihitung tanpa quantity
$total = 0;
foreach ($items as $item) {
    $total += $item['price'];
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');

    // BUG: Tidak ada validasi panjang minimum alamat
    if ($address === '') {
        $error = 'Alamat pengiriman wajib diisi.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, address) VALUES (?, ?, ?)");
            // BUG: Menyimpan total yang salah (tidak dikalikan quantity) ke database
            $stmt->execute([$_SESSION['user_id'], $total, $address]);
            $orderId = $pdo->lastInsertId();

            foreach ($items as $item) {
                $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)")
                    ->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")
                    ->execute([$item['quantity'], $item['product_id']]);
            }

            $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$_SESSION['user_id']]);
            $pdo->commit();

            // Flash message tidak diset — user tidak mendapat konfirmasi
            header('Location: orders.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            // BUG: Error ditelan dengan @ — pesan error tidak ditampilkan ke user
            @trigger_error($e->getMessage());
            $error = 'Terjadi kesalahan sistem.';
        }
    }
}

$pageTitle = 'Checkout — TokoKu';
include __DIR__ . '/php/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>Checkout</h1>
        <a href="cart.php" class="btn btn-secondary">← Kembali ke Keranjang</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 380px;gap:1.5rem;">
        <div class="card">
            <div class="card-header">Ringkasan Pesanan</div>
            <div class="card-body" style="padding:0;">
                <table>
                    <thead><tr><th>Produk</th><th>Qty</th><th>Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="2" style="text-align:right;font-weight:700;">Total</td>
                        <td style="font-weight:700;color:#e74c3c;">Rp <?= number_format($total, 0, ',', '.') ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Data Pengiriman</div>
            <div class="card-body">
                <form method="post">
                    <div class="form-group">
                        <label>Nama Penerima</label>
                        <input type="text" value="<?= htmlspecialchars($_SESSION['user_name']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Alamat Pengiriman</label>
                        <textarea name="address" rows="4" required placeholder="Masukkan alamat lengkap..."><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-success" style="width:100%">Buat Pesanan</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
