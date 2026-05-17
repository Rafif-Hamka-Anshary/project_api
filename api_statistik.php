<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'koneksi.php';

try {
    // Menghitung total pendapatan dan total pesanan
    $stmt_total = $pdo->query("SELECT SUM(total_belanja) as total_pendapatan, COUNT(id) as total_pesanan FROM transaksi");
    $statistik = $stmt_total->fetch();

    // Mencari 3 menu paling laku
    $stmt_top = $pdo->query("
        SELECT m.nama_menu, SUM(dt.jumlah) as total_terjual 
        FROM detail_transaksi dt 
        JOIN menu m ON dt.menu_id = m.id 
        GROUP BY dt.menu_id 
        ORDER BY total_terjual DESC LIMIT 3
    ");
    $top_menu = $stmt_top->fetchAll();

    echo json_encode([
        "status" => "success",
        "statistik_global" => [
            "total_pendapatan" => $statistik['total_pendapatan'] ?? 0,
            "total_pesanan" => $statistik['total_pesanan'] ?? 0
        ],
        "top_3_menu" => $top_menu
    ]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>