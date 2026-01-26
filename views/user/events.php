<?php
// Menggunakan __DIR__ agar otomatis mendeteksi path folder tanpa perlu edit manual
require_once __DIR__ . '/../_init.php';
$u = require_login(); 
$pdo = db(); 

// Ambil semua data event (Pastikan tabel bernama 'events')
$st = $pdo->query("SELECT * FROM events ORDER BY date ASC");
$events = $st->fetchAll();

$title = 'Katalog Konser - Fesmic';
require __DIR__ . '/../layout/header.php';
?>

<div class="container py-5" style="background-color: #0F0F0F; min-height: 100vh; color: white;">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-0">Explore Events</h2>
            <p class="text-muted">Temukan tiket konser favoritmu di sini.</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-light rounded-pill px-4">
            <i class="bi bi-arrow-left me-2"></i>Dashboard
        </a>
    </div>

    <div class="row g-4">
        <?php foreach ($events as $ev): ?>
        <div class="col-md-4 col-lg-3">
            <div class="card bg-dark text-white border-secondary h-100 rounded-4 overflow-hidden shadow-sm">
                <div style="height: 200px; overflow: hidden;">
                    <img src="../public/img/<?= e($ev['image']) ?>" class="card-img-top w-100 h-100" style="object-fit: cover;" onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
                </div>
                
                <div class="card-body d-flex flex-column">
                    <h5 class="fw-bold mb-1"><?= e($ev['name']) ?></h5>
                    <p class="text-primary small mb-3">
                        <i class="bi bi-geo-alt me-1"></i><?= e($ev['location']) ?>
                    </p>
                    
                    <div class="mt-auto">
                        <div class="d-flex justify-content-between small text-muted mb-3">
                            <span><i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($ev['date'])) ?></span>
                            <span><i class="bi bi-ticket-perforated me-1"></i><?= $ev['stock'] ?> Pcs</span>
                        </div>
                        
                        <div class="mb-3">
                            <span class="h5 fw-bold text-success">Rp <?= number_format($ev['price'], 0, ',', '.') ?></span>
                        </div>

                        <?php if ($ev['stock'] > 0): ?>
                            <a href="buy_process.php?event_id=<?= $ev['id'] ?>" 
                               class="btn btn-primary w-100 fw-bold rounded-pill py-2"
                               onclick="return confirm('Apakah Anda yakin ingin membeli tiket <?= e($ev['name']) ?>?')">
                               Beli Tiket
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary w-100 fw-bold rounded-pill py-2" disabled>Tiket Habis</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
