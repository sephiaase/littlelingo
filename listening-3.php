<?php
$conn = new mysqli("localhost", "root", "", "littlelingo");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// Ambil data tugas listening level 3
$result = $conn->query("SELECT * FROM tugas WHERE tipe = 'listening' AND level = 3 ORDER BY created_at DESC");
$tugas = [];
while ($row = $result->fetch_assoc()) {
  $tugas[] = [
    'id' => $row['id'],
    'judul' => $row['judul'],
    'deskripsi' => $row['deskripsi'],
    'file_path' => $row['file_path'],
    'gambar_path' => $row['gambar_path'],
    'is_penugasan' => $row['is_penugasan']
  ];
}

// Upload respon siswa
$upload_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_respon'])) {
  $nama = $_POST['nama_siswa'] ?? '';
  $tugas_id = $_POST['tugas_id'] ?? '';
  $teks = $_POST['teks_respon'] ?? '';
  $file_path = null;

  if (!empty($_FILES['file_respon']['name'])) {
    $dir = realpath(__DIR__ . '/../') . "/uploads/respon/";
    if (!file_exists($dir)) mkdir($dir, 0777, true);
    $safe = time() . "_" . basename($_FILES['file_respon']['name']);
    $full_path = $dir . $safe;
    $ext = strtolower(pathinfo($safe, PATHINFO_EXTENSION));
    $allowed = ['txt', 'pdf', 'mp3', 'wav'];
    if (in_array($ext, $allowed)) {
      if (move_uploaded_file($_FILES['file_respon']['tmp_name'], $full_path)) {
        $file_path = "../uploads/respon/" . $safe;
      }
    }
  }

  $stmt = $conn->prepare("INSERT INTO respon (tugas_id, nama_siswa, teks_respon, file_respon, created_at) VALUES (?, ?, ?, ?, NOW())");
  $stmt->bind_param("isss", $tugas_id, $nama, $teks, $file_path);
  $stmt->execute();
  $stmt->close();
  $upload_msg = "Respon berhasil dikirim!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="shortcut icon" href="assets/img/favicon.png" type="image/png" />
  <title>LittleLingo Listening - Level 3</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .fade { transition: opacity 0.5s ease-in-out; }
  </style>
</head>
<body class="bg-white font-[Poppins]">

<!-- Header -->
<header class="bg-white shadow-md relative">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between py-4 relative">
      <div><img src="assets/img/logo.png" alt="Logo" class="h-10 w-auto mr-2" /></div>
      <div class="absolute left-1/2 transform -translate-x-1/2">
        <span class="font-bold text-xl text-blue-600">Daily Activities</span>
      </div>
    </div>
  </div>
</header>

<!-- Konten -->
<section class="py-12 px-4 max-w-7xl mx-auto">
  <div class="text-center mb-10">
    <h6 class="text-blue-600 font-semibold uppercase text-sm">DAILY LIFE</h6>
    <h2 class="text-3xl font-bold">Let's Practice <span class="text-blue-500">Listening</span></h2>
  </div>

  <!-- Tab Tugas -->
  <div class="flex justify-center gap-6 flex-wrap mb-10">
    <?php foreach ($tugas as $index => $item): ?>
      <button onclick="showTab(<?= $index ?>)" id="tab-btn-<?= $index ?>"
        class="tab-btn flex flex-col items-center px-6 py-4 bg-white rounded-xl shadow-md hover:shadow-lg transition <?= $index === 0 ? 'ring ring-pink-400' : '' ?>">
        <img src="assets/img/content/number-<?= $index + 1 ?>.png" class="w-12 h-12 mb-2" />
        <span class="font-semibold text-gray-800"><?= htmlspecialchars($item['judul']) ?></span>
      </button>
    <?php endforeach; ?>
  </div>

  <!-- Konten Dinamis -->
  <div id="tab-content" class="bg-white rounded-2xl shadow-lg p-8 grid grid-cols-1 md:grid-cols-2 gap-10 fade opacity-100">
    <div>
      <h3 class="text-xl font-bold text-pink-600 mb-4" id="tab-title"></h3>
      <p class="text-gray-700 mb-4" id="tab-description"></p>
      <audio id="tab-audio" controls class="w-full mt-4 rounded shadow-md"><source src="" type="audio/mpeg"></audio>
    </div>
    <div id="tab-image-wrapper" class="flex justify-center items-center"></div>
  </div>

  <!-- Form Jawaban -->
  <div class="mt-10 max-w-2xl mx-auto" id="submission-form" style="display: none;">
    <?php if (!empty($upload_msg)): ?>
      <div class="bg-green-100 text-green-700 px-4 py-3 rounded mb-4"><?= $upload_msg ?></div>
    <?php endif; ?>

    <h3 class="text-xl font-bold text-gray-700 mb-4">🎧 Kirim Jawaban Listening</h3>
    <form method="POST" enctype="multipart/form-data" class="bg-gray-100 p-6 rounded-lg shadow space-y-4">
      <input type="hidden" name="tugas_id" id="form-tugas-id" value="">
      <div><label>Nama:</label><input type="text" name="nama_siswa" required class="w-full border px-4 py-2 rounded"></div>
      <div><label>Jawaban Teks:</label><textarea name="teks_respon" rows="4" class="w-full border px-4 py-2 rounded"></textarea></div>
      <div><label>Upload File (opsional):</label><input type="file" name="file_respon" class="w-full"></div>
      <button type="submit" name="submit_respon" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">Kirim</button>
    </form>
  </div>

  <!-- Tombol Kembali -->
  <div class="text-center mt-12">
    <a href="menu-3.php" class="inline-block bg-blue-500 text-white px-6 py-3 rounded-full font-semibold hover:bg-pink-600 transition">Kembali ke Menu</a>
  </div>
</section>

<script>
  const tugas = <?= json_encode($tugas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

  function showTab(index) {
    const tab = tugas[index];
    document.getElementById("tab-title").textContent = tab.judul;
    document.getElementById("tab-description").textContent = tab.deskripsi;
    document.getElementById("tab-audio").src = tab.file_path;

    const imageWrapper = document.getElementById("tab-image-wrapper");
    imageWrapper.innerHTML = "";
    if (tab.gambar_path && tab.gambar_path.trim() !== "") {
      const img = document.createElement("img");
      img.src = tab.gambar_path;
      img.className = "rounded-lg w-full max-w-sm";
      imageWrapper.appendChild(img);
    }

    const formDiv = document.getElementById("submission-form");
    const formId = document.getElementById("form-tugas-id");
    if (tab.is_penugasan == 1) {
      formDiv.style.display = "block";
      formId.value = tab.id;
    } else {
      formDiv.style.display = "none";
      formId.value = "";
    }

    document.querySelectorAll(".tab-btn").forEach(btn => btn.classList.remove("ring", "ring-pink-400"));
    document.getElementById("tab-btn-" + index).classList.add("ring", "ring-pink-400");
  }

  document.addEventListener("DOMContentLoaded", () => {
    if (tugas.length > 0) showTab(0);
  });
</script>

</body>
</html>