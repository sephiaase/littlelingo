<?php
header('Content-Type: application/json');

// Koneksi database
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'littlelingo';
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Koneksi ke database gagal']);
    exit;
}

// POST → Simpan hasil kuis level 2
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
    $nilai = isset($_POST['nilai']) ? intval($_POST['nilai']) : 0;
    $level = 2;

    if ($nama === '' || $nilai === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Nama atau nilai kosong']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO quiz_scores (nama_siswa, nilai, level, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sii", $nama, $nilai, $level);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal menyimpan skor']);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// GET → Ambil soal dari tabel `kuis2`
$sql = "SELECT * FROM kuis2";
$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mengambil soal dari kuis2']);
    exit;
}

$questions = [];

while ($row = $result->fetch_assoc()) {
    $benar = strtolower($row['jawaban_benar']); // pastikan lowercase
    $questions[] = [
        'question' => $row['soal'],
        'answers' => [
            ['text' => $row['pilihan_a'], 'correct' => $benar === 'a'],
            ['text' => $row['pilihan_b'], 'correct' => $benar === 'b'],
            ['text' => $row['pilihan_c'], 'correct' => $benar === 'c'],
            ['text' => $row['pilihan_d'], 'correct' => $benar === 'd'],
        ]
    ];
}

echo json_encode($questions);
$conn->close();