<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
require_once __DIR__ . '/php/auth.php';
requireLogin();

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND stock > 0");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            if ($product) {
                $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$_SESSION['user_id'], $productId]);
                $existing = $stmt->fetch();
                if ($existing) {
                    $newQty = $existing['quantity'] + 1;
                    if ($newQty <= $product['stock']) {
                        $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?")->execute([$newQty, $existing['id']]);
                    }
                } else {
                    $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)")->execute([$_SESSION['user_id'], $productId]);
                }
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Produk ditambahkan ke keranjang.'];
            }
        }
        header('Location: index.php');
        exit;

    } elseif ($action === 'remove') {
        $cartId = (int)($_POST['cart_id'] ?? 0);
        // item keranjang milik user lain dengan mengetahui cart_id
        $pdo->prepare("DELETE FROM cart WHERE id = ?")->execute([$cartId]);
        header('Location: cart.php');
        exit;

    } elseif ($action === 'update') {
        $cartId = (int)($_POST['cart_id'] ?? 0);
        $qty    = (int)($_POST['quantity'] ?? 1);
        if ($qty < 1) $qty = 1;
        $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?")->execute([$qty, $cartId, $_SESSION['user_id']]);
        header('Location: cart.php');
        exit;
    }
}

$stmt = $pdo->prepare("SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.stock
    FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll();

$total = 0;
foreach ($items as $item) {
    $total += $item['price'];
}

$pageTitle = 'Keranjang — TokoKu';
include __DIR__ . '/php/header.php';
?>
<div class="container">
    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type'] ?>"><?= htmlspecialchars($_SESSION['flash']['msg']) ?></div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="page-header">
        <h1>🛒 Keranjang Belanja</h1>
        <a href="index.php" class="btn btn-secondary">Lanjut Belanja</a>
    </div>

    <?php if (empty($items)): ?>
        <div class="card"><div class="card-body" style="text-align:center;padding:3rem;">Keranjang Anda kosong. <a href="index.php">Belanja sekarang</a></div></div>
    <?php else: ?>
    <div class="card">
        <div class="card-body" style="padding:0;">
            <table>
                <thead><tr><th>Produk</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th>Hapus</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td>Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                    <td>
                        <form method="post" style="display:inline-flex;gap:.3rem;align-items:center;">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                            <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" style="width:60px;padding:.3rem;border:1px solid #ddd;border-radius:4px;">
                            <button type="submit" class="btn btn-secondary btn-sm">Update</button>
                        </form>
                    </td>
                    <td>Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Hapus item ini?')">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="cart-total">
        <span>Total: <strong>Rp <?= number_format($total, 0, ',', '.') ?></strong></span>
        <a href="checkout.php" class="btn btn-success">Checkout →</a>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
