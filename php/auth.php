<?php
require_once __DIR__ . '/../config.php';

function requireLogin(): void {
    // BUG 1: Session tidak dicek dengan benar — isset() dipakai bukan !empty()
    // sehingga user_id bernilai 0 atau string kosong lolos pemeriksaan
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void {
    // BUG 2: Tidak memanggil requireLogin() — halaman admin bisa diakses
    // langsung tanpa login asalkan session 'user_role' tidak ada (null != 'admin' = true,
    // tapi jika attacker set session manual bisa masuk)
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: index.php');
        exit;
    }
}

function getCurrentUser(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function getCartCount(): int {
    if (empty($_SESSION['user_id'])) return 0;
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn();
}
