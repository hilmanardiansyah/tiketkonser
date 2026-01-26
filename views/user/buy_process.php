<?php
// 1. Inisialisasi sistem dan koneksi database
require_once __DIR__ . '/../_init.php';

// 2. Keamanan: Pastikan hanya user yang sudah login yang bisa beli
$u = require_login(); 
$pdo = db(); 

// 3. Ambil ID Event dari URL (misal: buy_process.php?event_id=1)
$event_id = $_GET['event_id'] ?? null;

if (!$event_id) {
    // Jika tidak ada ID event, kembalikan ke katalog
    header("Location: events.php");
    exit;
}

try {
    // 4. Mulai Transaksi Database
    $pdo->beginTransaction();

    // 5. Cek apakah stok tiket masih ada
    $stmt_check = $pdo->prepare("SELECT name, stock FROM events WHERE id = ? FOR UPDATE");
    $stmt_check->execute([$event_id]);
    $event = $stmt_check->fetch();

    if (!$event || $event['stock'] <= 0) {
        throw new Exception("Maaf, tiket sudah habis!");
    }

    // 6. Generate Kode Order unik (Contoh: FES-A1B2C)
    $order_code = "FES-" . strtoupper(substr(md5(uniqid()), 0, 5));

    // 7. Simpan data ke tabel 'orders'
    $stmt_order = $pdo->prepare("
        INSERT INTO orders (order_code, user_id, event_id, order_date, status) 
        VALUES (?, ?, ?, NOW(), 'PAID')
    ");
    $stmt_order->execute([$order_code, $u['id'], $event_id]);

    // 8. Kurangi stok di tabel 'events'
    $stmt_update = $pdo->prepare("UPDATE events SET stock = stock - 1 WHERE id = ?");
    $stmt_update->execute([$event_id]);

    // 9. Selesaikan Transaksi
    $pdo->commit();

    // 10. Redirect ke dashboard dengan pesan sukses
    header("Location: dashboard.php?status=success");
    exit;

} catch (Exception $e) {
    // Jika ada error, batalkan semua perubahan database
    $pdo->rollBack();
    die("Gagal membeli tiket: " . $e->getMessage());
}
