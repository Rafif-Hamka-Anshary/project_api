<?php
// Memaksa PHP menggunakan zona waktu Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta'); 

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

    case 'POST': // CREATE TRANSAKSI, DETAIL, & POTONG STOK
        try {
            $pdo->beginTransaction(); // Mulai transaksi DB

            // 1. Insert ke tabel transaksi utama
            $sql_trx = "INSERT INTO transaksi (nama_pelanggan, metode_pembayaran, total_belanja) VALUES (:nama_pelanggan, :metode_pembayaran, :total_belanja)";
            $stmt_trx = $pdo->prepare($sql_trx);
            $stmt_trx->execute([
                'nama_pelanggan' => $input['nama_pelanggan'],
                'metode_pembayaran' => $input['metode_pembayaran'],
                'total_belanja' => $input['total_belanja']
            ]);
            
            $transaksi_id = $pdo->lastInsertId(); // Ambil ID transaksi baru

            // 2. Siapkan template query untuk simpan detail transaksi
            $sql_detail = "INSERT INTO detail_transaksi (transaksi_id, menu_id, jumlah, harga_satuan, subtotal) VALUES (:transaksi_id, :menu_id, :jumlah, :harga_satuan, :subtotal)";
            $stmt_detail = $pdo->prepare($sql_detail);
            
            // 3. Siapkan template query untuk mengurangi stok menu
            $sql_update_stok = "UPDATE menu SET stok = stok - :jumlah WHERE id = :menu_id";
            $stmt_update_stok = $pdo->prepare($sql_update_stok);
            
            // Looping isi keranjang belanja dari frontend index.html
            foreach ($input['detail'] as $item) {
                // Eksekusi Simpan Detail
                $stmt_detail->execute([
                    'transaksi_id' => $transaksi_id,
                    'menu_id' => $item['menu_id'],
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal' => $item['jumlah'] * $item['harga_satuan']
                ]);

                // Eksekusi Potong Stok Menu
                $stmt_update_stok->execute([
                    'jumlah' => $item['jumlah'],
                    'menu_id' => $item['menu_id']
                ]);
            }

            $pdo->commit(); // Simpan seluruh rangkaian operasi secara permanen ke database
            echo json_encode(["status" => "success", "message" => "Transaksi berhasil dicatat dan stok berhasil diperbarui!"]);
        } catch (Exception $e) {
            $pdo->rollBack(); // Batalkan semua operasi jika ada salah satu yang gagal
            echo json_encode(["status" => "error", "message" => "Gagal menyimpan transaksi: " . $e->getMessage()]);
        }
        break;
}
?>