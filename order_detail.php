<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
require_once __DIR__ . '/php/auth.php';
requireLogin();

$pdo = getDB();
$orderId = (int)($_GET['id'] ?? 0);

if ($_SESSION['user_role'] === 'admin') {
    $stmt = $pdo->prepare("SELECT o.*, u.name AS customer_name, u.email AS customer_email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
} else {
    $stmt = $pdo->prepare("SELECT o.*, u.name AS customer_name, u.email AS customer_email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?");
}

$params = [$orderId];
if ($_SESSION['user_role'] !== 'admin') $params[] = $_SESSION['user_id'];
$stmt->execute($params);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

$items = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items->execute([$orderId]);
$orderItems = $items->fetchAll();

$pageTitle = "Pesanan #$orderId — TokoKu";
include __DIR__ . '/php/header.php';
?>
<div class="container">
    <a href="orders.php" class="back-link">← Kembali ke Pesanan</a>
    <div class="card">
        <div class="card-header">Detail Pesanan #<?= $order['id'] ?></div>
        <div class="card-body">
            <p><strong>Pelanggan:</strong> <?= htmlspecialchars($order['customer_name']) ?> (<?= htmlspecialchars($order['customer_email']) ?>)</p>
            <p><strong>Tanggal:</strong> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
            <p><strong>Alamat:</strong> <?= htmlspecialchars($order['address']) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
            <hr style="margin:1rem 0;">
            <table>
                <thead><tr><th>Produk</th><th>Harga Satuan</th><th>Qty</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($orderItems as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td>Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><td colspan="3" style="text-align:right;font-weight:700;">Total</td>
                    <td style="font-weight:700;color:#e74c3c;">Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
</body>
</html>
