<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'koneksi.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);

try {
    switch ($method) {
        case 'GET': // READ
            $stmt = $pdo->query("SELECT m.*, k.nama_kategori FROM menu m JOIN kategori k ON m.kategori_id = k.id");
            $data = $stmt->fetchAll();
            echo json_encode(["status" => "success", "data" => $data]);
            break;

        case 'POST': // CREATE
            $sql = "INSERT INTO menu (kategori_id, nama_menu, harga, stok) VALUES (:kategori_id, :nama_menu, :harga, :stok)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'kategori_id' => $input['kategori_id'], 
                'nama_menu' => $input['nama_menu'], 
                'harga' => $input['harga'], 
                'stok' => $input['stok']
            ]);
            echo json_encode(["status" => "success", "message" => "Menu berhasil ditambahkan"]);
            break;

        case 'PUT': // UPDATE
            $sql = "UPDATE menu SET harga = :harga, stok = :stok WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'harga' => $input['harga'], 
                'stok' => $input['stok'], 
                'id' => $_GET['id']
            ]);
            echo json_encode(["status" => "success", "message" => "Menu berhasil diupdate"]);
            break;

        case 'DELETE': // DELETE
            $sql = "DELETE FROM menu WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $_GET['id']]);
            echo json_encode(["status" => "success", "message" => "Menu berhasil dihapus"]);
            break;
            
        default:
            echo json_encode(["status" => "error", "message" => "Method tidak dikenali"]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>