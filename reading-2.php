<?php
$conn = new mysqli("localhost", "root", "", "littlelingo");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// Ambil data tugas level 2 tipe reading
$result = $conn->query("SELECT * FROM tugas WHERE tipe = 'reading' AND level = 2 ORDER BY created_at DESC");
$tugas = [];
while ($row = $result->fetch_assoc()) {
  $tugas[] = [
    'id' => $row['id'],
    'judul' => $row['judul'],
    'deskripsi' => $row['deskripsi'],
    'isi' => $row['isi_teks'] ?? '',
    'gambar' => $row['gambar_path'] ?? '',
    'is_penugasan' => $row['is_penugasan'] ?? 0
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
    $dir = __DIR__ . "/uploads/respon/";
    if (!file_exists($dir)) mkdir($dir, 0777, true);
    $safe = time() . "_" . basename($_FILES['file_respon']['name']);
    $full_path = $dir . $safe;
    $ext = strtolower(pathinfo($safe, PATHINFO_EXTENSION));
    $allowed = ['webm', 'mp3', 'wav', 'ogg'];
    if (in_array($ext, $allowed)) {
      if (move_uploaded_file($_FILES['file_respon']['tmp_name'], $full_path)) {
        $file_path = "uploads/respon/" . $safe;
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

<!-- Bagian HTML -->
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="shortcut icon" href="assets/img/favicon.png" type="image/png" />
  <title>Reading Level 2 - LittleLingo</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .fade { transition: opacity 0.5s ease-in-out; }
    pre.txt-display {
      white-space: pre-wrap;
      background-color: #f9fafb;
      padding: 1rem;
      border-radius: 0.5rem;
      font-family: monospace;
      color: #374151;
      font-size: 0.9rem;
    }
  </style>
</head>
<body class="bg-white font-[Poppins]">
<header class="bg-white shadow-md relative">
  <div class="max-w-7xl mx-auto px-4">
    <div class="flex items-center justify-between py-4 relative">
      <div><img src="assets/img/logo.png" alt="Logo" class="h-10 w-auto mr-2" /></div>
      <div class="absolute left-1/2 transform -translate-x-1/2">
        <span class="font-bold text-xl text-blue-600">Talking About Favorite Things</span>
      </div>
    </div>
  </div>
</header>

<section class="py-12 px-4 max-w-7xl mx-auto">
  <div class="text-center mb-10">
    <h6 class="text-blue-600 font-semibold uppercase text-sm">FAVORITE THINGS</h6>
    <h2 class="text-3xl font-bold">Let's Have A Fun <span class="text-blue-500">Reading Session</span></h2>
  </div>

  <div class="flex justify-center gap-6 flex-wrap mb-10">
    <?php foreach ($tugas as $index => $item): ?>
      <button onclick="showTab(<?= $index ?>)" id="tab-btn-<?= $index ?>"
        class="tab-btn flex flex-col items-center px-6 py-4 bg-white rounded-xl shadow-md hover:shadow-lg transition <?= $index === 0 ? 'ring ring-pink-400' : '' ?>">
        <img src="assets/img/content/number-<?= $index + 1 ?>.png" class="w-12 h-12 mb-2" />
        <span class="font-semibold text-gray-800"><?= htmlspecialchars($item['judul']) ?></span>
      </button>
    <?php endforeach; ?>
  </div>

  <div id="tab-content" class="bg-white rounded-2xl shadow-lg p-8 grid grid-cols-1 md:grid-cols-2 gap-10 fade opacity-100">
    <div>
      <h3 class="text-xl font-bold text-pink-600 mb-4" id="tab-title"></h3>
      <p class="text-gray-700 mb-4" id="tab-description"></p>
      <pre class="txt-display" id="tab-isi"></pre>
    </div>
    <div id="image-wrapper" class="flex justify-center items-center">
      <img id="tab-image" src="" class="rounded-lg w-full max-w-sm hidden" alt="Reading Illustration" />
    </div>
  </div>

  <!-- FORM -->
  <div class="mt-10 max-w-2xl mx-auto" id="submission-form" style="display: none;">
    <?php if (!empty($upload_msg)): ?>
      <div class="bg-green-100 text-green-700 px-4 py-3 rounded mb-4"><?= $upload_msg ?></div>
    <?php endif; ?>
    <h3 class="text-xl font-bold text-gray-700 mb-4">🎤 Rekam & Kirim Jawaban Kamu</h3>
    <form method="POST" enctype="multipart/form-data" class="bg-gray-100 p-6 rounded-lg shadow space-y-4">
      <input type="hidden" name="tugas_id" id="form-tugas-id" value="">
      <div><label>Nama:</label><input type="text" name="nama_siswa" required class="w-full border px-4 py-2 rounded"></div>
      <div><label>Jawaban Teks (opsional):</label><textarea name="teks_respon" rows="4" class="w-full border px-4 py-2 rounded"></textarea></div>

      <div>
        <label>Rekaman Suara:</label><br/>
        <button type="button" onclick="startRecording()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Mulai Rekam</button>
        <button type="button" onclick="stopRecording()" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 ml-2">Stop</button>
        <p class="text-sm mt-2 text-gray-600">Tekan "Stop" sebelum mengirim.</p>
        <audio id="audioPlayback" controls class="mt-3 w-full hidden"></audio>
        <input type="file" name="file_respon" id="audioBlobInput" hidden>
      </div>

      <button type="submit" name="submit_respon" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">Kirim</button>
    </form>
  </div>

  <div class="text-center mt-12">
    <a href="menu-2.php" class="inline-block bg-blue-500 text-white px-6 py-3 rounded-full font-semibold hover:bg-pink-600 transition">Kembali ke Menu</a>
  </div>
</section>

<script>
  const tugas = <?= json_encode($tugas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

  function showTab(index) {
    const tab = tugas[index];
    document.getElementById("tab-title").textContent = tab.judul;
    document.getElementById("tab-description").textContent = tab.deskripsi;
    document.getElementById("tab-isi").textContent = tab.isi;
    document.getElementById("form-tugas-id").value = tab.id;

    const imgWrapper = document.getElementById("image-wrapper");
    const img = document.getElementById("tab-image");

    if (tab.gambar && tab.gambar.trim() !== "") {
      img.src = tab.gambar;
      img.classList.remove("hidden");
      imgWrapper.style.display = "flex";
    } else {
      img.src = "";
      img.classList.add("hidden");
      imgWrapper.style.display = "none";
    }

    document.getElementById("submission-form").style.display = tab.is_penugasan == 1 ? "block" : "none";

    document.querySelectorAll(".tab-btn").forEach(btn => btn.classList.remove("ring", "ring-pink-400"));
    document.getElementById("tab-btn-" + index).classList.add("ring", "ring-pink-400");
  }

  document.addEventListener("DOMContentLoaded", () => {
    if (tugas.length > 0) showTab(0);
  });

  let mediaRecorder;
  let audioChunks = [];

  function startRecording() {
    navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
      mediaRecorder = new MediaRecorder(stream);
      audioChunks = [];

      mediaRecorder.ondataavailable = event => {
        if (event.data.size > 0) audioChunks.push(event.data);
      };

      mediaRecorder.onstop = () => {
        const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
        const audioUrl = URL.createObjectURL(audioBlob);
        document.getElementById("audioPlayback").src = audioUrl;
        document.getElementById("audioPlayback").classList.remove("hidden");

        const file = new File([audioBlob], "recording_" + Date.now() + ".webm", { type: "audio/webm" });
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        document.getElementById("audioBlobInput").files = dataTransfer.files;
      };

      mediaRecorder.start();
      alert("Rekaman dimulai...");
    });
  }

  function stopRecording() {
    if (mediaRecorder && mediaRecorder.state !== "inactive") {
      mediaRecorder.stop();
      alert("Rekaman dihentikan.");
    }
  }
</script>
</body>
</html>