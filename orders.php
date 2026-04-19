<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
require_once __DIR__ . '/php/auth.php';
requireLogin();

$pdo = getDB();

if ($_SESSION['user_role'] === 'admin') {
    $orders = $pdo->query("SELECT o.*, u.name AS customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll();
}

// Admin update status
if ($_SESSION['user_role'] === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status  = $_POST['status'] ?? '';
    $allowed = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if ($orderId > 0 && in_array($status, $allowed)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, $orderId]);
    }
    header('Location: orders.php');
    exit;
}

$statusLabel = [
    'pending'    => ['label' => 'Menunggu',    'class' => 'badge-warning'],
    'processing' => ['label' => 'Diproses',    'class' => 'badge-info'],
    'shipped'    => ['label' => 'Dikirim',     'class' => 'badge-info'],
    'delivered'  => ['label' => 'Selesai',     'class' => 'badge-success'],
    'cancelled'  => ['label' => 'Dibatalkan',  'class' => 'badge-danger'],
];

$pageTitle = 'Pesanan — TokoKu';
include __DIR__ . '/php/header.php';
?>
<div class="container">
    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type'] ?>"><?= htmlspecialchars($_SESSION['flash']['msg']) ?></div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="page-header"><h1>📦 Riwayat Pesanan</h1></div>

    <?php if (empty($orders)): ?>
        <div class="card"><div class="card-body" style="text-align:center;padding:2rem;">Belum ada pesanan.</div></div>
    <?php else: ?>
    <div class="card">
        <div class="card-body" style="padding:0;">
            <table>
                <thead>
                    <tr>
                        <th>#ID</th>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?><th>Pelanggan</th><?php endif; ?>
                        <th>Tanggal</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Detail</th>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?><th>Update</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                <?php $sl = $statusLabel[$order['status']] ?? ['label' => $order['status'], 'class' => 'badge-secondary']; ?>
                <tr>
                    <td>#<?= $order['id'] ?></td>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?><td><?= htmlspecialchars($order['customer_name']) ?></td><?php endif; ?>
                    <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                    <td>Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td>
                    <td><span class="badge <?= $sl['class'] ?>"><?= $sl['label'] ?></span></td>
                    <td><a href="order_detail.php?id=<?= $order['id'] ?>" class="btn btn-secondary btn-sm">Detail</a></td>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <td>
                        <form method="post" style="display:flex;gap:.3rem;">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <select name="status" style="padding:.3rem;border:1px solid #ddd;border-radius:4px;font-size:.85rem;">
                                <?php foreach ($statusLabel as $val => $lbl): ?>
                                    <option value="<?= $val ?>" <?= $order['status'] === $val ? 'selected' : '' ?>><?= $lbl['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">OK</button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
