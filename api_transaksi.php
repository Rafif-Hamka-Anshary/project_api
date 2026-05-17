<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'koneksi.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'GET': // READ TRANSAKSI
        try {
            $stmt = $pdo->query("SELECT * FROM transaksi ORDER BY tanggal DESC");
            echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    case 'POST': // CREATE TRANSAKSI & DETAIL
        try {
            $pdo->beginTransaction(); // Mulai transaksi DB

            // 1. Insert ke tabel transaksi
            $sql_trx = "INSERT INTO transaksi (nama_pelanggan, metode_pembayaran, total_belanja) VALUES (:nama_pelanggan, :metode_pembayaran, :total_belanja)";
            $stmt_trx = $pdo->prepare($sql_trx);
            $stmt_trx->execute([
                'nama_pelanggan' => $input['nama_pelanggan'],
                'metode_pembayaran' => $input['metode_pembayaran'],
                'total_belanja' => $input['total_belanja']
            ]);
            
            $transaksi_id = $pdo->lastInsertId(); // Ambil ID transaksi baru

            // 2. Insert ke tabel detail_transaksi
            $sql_detail = "INSERT INTO detail_transaksi (transaksi_id, menu_id, jumlah, harga_satuan, subtotal) VALUES (:transaksi_id, :menu_id, :jumlah, :harga_satuan, :subtotal)";
            $stmt_detail = $pdo->prepare($sql_detail);
            
            foreach ($input['detail'] as $item) {
                $stmt_detail->execute([
                    'transaksi_id' => $transaksi_id,
                    'menu_id' => $item['menu_id'],
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal' => $item['jumlah'] * $item['harga_satuan']
                ]);
            }

            $pdo->commit(); // Simpan permanen
            echo json_encode(["status" => "success", "message" => "Transaksi berhasil dicatat"]);
        } catch (Exception $e) {
            $pdo->rollBack(); // Batalkan semua jika ada error
            echo json_encode(["status" => "error", "message" => "Gagal menyimpan transaksi: " . $e->getMessage()]);
        }
        break;
}
?>