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

// POST → Simpan hasil kuis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
    $nilai = isset($_POST['nilai']) ? intval($_POST['nilai']) : 0;
    $level = isset($_POST['level']) ? intval($_POST['level']) : 1; // level dikirim dari JS

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

// GET → Ambil soal kuis
// Misalnya pakai ?level=1 untuk ambil dari tabel kuis1
$level = isset($_GET['level']) ? intval($_GET['level']) : 1;
$tabel = "kuis" . $level;

if (!preg_match('/^kuis[1-9][0-9]*$/', $tabel)) {
    http_response_code(400);
    echo json_encode(['error' => 'Level tidak valid']);
    exit;
}

$sql = "SELECT * FROM `$tabel`";
$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mengambil soal']);
    exit;
}

$questions = [];

while ($row = $result->fetch_assoc()) {
    $questions[] = [
        'question' => $row['soal'],
        'answers' => [
            ['text' => $row['pilihan_a'], 'correct' => $row['jawaban_benar'] === 'a'],
            ['text' => $row['pilihan_b'], 'correct' => $row['jawaban_benar'] === 'b'],
            ['text' => $row['pilihan_c'], 'correct' => $row['jawaban_benar'] === 'c'],
            ['text' => $row['pilihan_d'], 'correct' => $row['jawaban_benar'] === 'd'],
        ]
    ];
}

echo json_encode($questions);
$conn->close();