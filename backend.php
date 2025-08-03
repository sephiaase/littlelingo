<?php
$host = 'localhost'; // Host database
$dbname = 'littlelingo'; // Nama database
$username = 'root'; // Username database
$password = ''; // Password database (kosong jika tidak ada password)

try {
    // Koneksi ke database menggunakan PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ambil artikel berdasarkan ID dari parameter URL
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = :id");
        $stmt->execute(['id' => $_GET['id']]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);

        // Periksa apakah artikel ditemukan
        if (!$article) {
            die("Artikel tidak ditemukan.");
        }
    } else {
        die("ID artikel tidak diberikan.");
    }
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>