<?php
session_start();
$conn = new mysqli("localhost", "root", "", "littlelingo");

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'pengajar' && $_SESSION['role'] !== 'admin')) {
    header("Location: login-pengguna.php");
    exit;
}

$levels = [
    1 => "Greetings...",
    2 => "Talking...",
    3 => "Daily Activities",
    4 => "School...",
    5 => "My Experiences",
    6 => "My Future..."
];

$currentLevel = isset($_GET['level']) ? (int)$_GET['level'] : 1;
$currentLevelName = $levels[$currentLevel] ?? 'Level Tidak Ditemukan';

function upload_file($tipe, $file) {
    $targetDir = dirname(__DIR__) . "/uploads/" . $tipe . "/"; // updated to point outside login page folder
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

    $fn = basename($file['name']);
    $safe = time() . '_' . preg_replace("/[^a-zA-Z0-9.\-_]/", "_", $fn);
    $full = $targetDir . $safe;

    $ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
    $allowed = [
        'reading' => ['txt'],
        'listening' => ['mp3', 'wav'],
        'materi' => ['pdf'],
        'images' => ['jpg', 'jpeg', 'png', 'gif'],
        'respon' => ['txt', 'pdf', 'mp3', 'wav']
    ];

    if (!isset($allowed[$tipe]) || !in_array($ext, $allowed[$tipe])) return false;

    if (move_uploaded_file($file['tmp_name'], $full)) {
        return "uploads/" . $tipe . "/" . $safe; // path yang bisa diakses browser
    }

    return false;
}


// Tambah tugas
if (isset($_POST['tambah_tugas'])) {
    $judul = $_POST['judul'];
    $tipe = $_POST['kategori'];
    $deskripsi = $_POST['deskripsi'];
    $isi_teks = $_POST['isi_teks'] ?: null;
    $level = $_POST['level'];
    $is_penugasan = isset($_POST['is_penugasan']) ? 1 : 0;

    $file_path = null;
    $gambar_path = null;

    if ($_FILES['file']['error'] === 0) {
        $file_path = upload_file($tipe, $_FILES['file']) ?: null;
        if (!$file_path) $upload_error = "Format file salah";
    }
    if ($_FILES['gambar']['error'] === 0) {
        $gambar_path = upload_file('images', $_FILES['gambar']) ?: null;
        if (!$gambar_path) $upload_error = "Format gambar salah";
    }

    $stmt = $conn->prepare("INSERT INTO tugas (judul, tipe, deskripsi, isi_teks, file_path, gambar_path, level, is_penugasan, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssssii", $judul, $tipe, $deskripsi, $isi_teks, $file_path, $gambar_path, $level, $is_penugasan);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard-pengajar.php?level=" . $level);
    exit;
}

// Hapus tugas
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $conn->prepare("SELECT file_path, gambar_path FROM tugas WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($fp, $gp);
    $stmt->fetch();
    $stmt->close();
    foreach ([$fp, $gp] as $p) {
        if ($p && file_exists($p)) unlink($p);
    }
    $stmt = $conn->prepare("DELETE FROM tugas WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard-pengajar.php?level=" . $currentLevel);
    exit;
}

// Update nilai respon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_nilai'])) {
    $nilai = intval($_POST['nilai']);
    $respon_id = intval($_POST['respon_id']);
    $stmt = $conn->prepare("UPDATE respon SET nilai_pengajar = ? WHERE id = ?");
    $stmt->bind_param("ii", $nilai, $respon_id);
    $stmt->execute();
    $stmt->close();
}

// Hapus respon siswa
if (isset($_GET['hapus_respon'])) {
    $id = (int)$_GET['hapus_respon'];

    // Ambil file respon (jika ada) untuk dihapus dari server
    $stmt = $conn->prepare("SELECT file_respon FROM respon WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($file_respon);
    $stmt->fetch();
    $stmt->close();

    // Hapus file jika ada
    if ($file_respon && file_exists($file_respon)) {
        unlink($file_respon);
    }

    // Hapus data respon dari DB
    $stmt = $conn->prepare("DELETE FROM respon WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: dashboard-pengajar.php?level=$currentLevel");
    exit;
}

// Tambah soal kuis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_kuis'])) {
    $soal = $_POST['soal'];
    $a = $_POST['a'];
    $b = $_POST['b'];
    $c = $_POST['c'];
    $d = $_POST['d'];
    $jawaban = $_POST['jawaban'];
    $currentLevel = isset($_GET['level']) ? (int)$_GET['level'] : 1;

    if ($currentLevel >= 1 && $currentLevel <= 6) {
        $tabel_kuis = "kuis" . $currentLevel;
        $query = "INSERT INTO `$tabel_kuis` (soal, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("ssssss", $soal, $a, $b, $c, $d, $jawaban);
            $stmt->execute();
            $stmt->close();
            header("Location: dashboard-pengajar.php?level=$currentLevel&kuis_success=1");
            exit;
        } else {
            echo "<p class='text-red-500'>❌ Gagal menyimpan soal: " . $conn->error . "</p>";
        }
    } else {
        echo "<p class='text-red-500'>❌ Level tidak valid!</p>";
    }
}

// Hapus soal kuis
if (isset($_GET['hapus_soal'])) {
    $id = (int)$_GET['hapus_soal'];
    $currentLevel = isset($_GET['level']) ? (int)$_GET['level'] : 1;

    // Validasi level (1–6)
    if ($currentLevel >= 1 && $currentLevel <= 6) {
        $tabel_kuis = "kuis" . $currentLevel;

        // Gunakan query string dengan backtick untuk nama tabel
        $query = "DELETE FROM `$tabel_kuis` WHERE id_soal = ?";
        $stmt = $conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            header("Location: dashboard-pengajar.php?level=$currentLevel");
            exit;
        } else {
            echo "<div style='color:red'>❌ Gagal prepare query: " . $conn->error . "</div>";
        }
    } else {
        echo "<div style='color:red'>❌ Level tidak valid!</div>";
    }
}

$tugas_q = $conn->query("SELECT * FROM tugas WHERE level=$currentLevel ORDER BY created_at DESC");
$respon_q = $conn->query("SELECT r.*, t.judul, t.tipe FROM respon r JOIN tugas t ON r.tugas_id = t.id WHERE t.level=$currentLevel AND t.is_penugasan = 1 ORDER BY r.created_at DESC");
$skor_q = $conn->query("SELECT * FROM quiz_scores WHERE level = $currentLevel ORDER BY created_at DESC");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['balas_komentar'])) {
    $comment_id = (int)$_POST['comment_id'];
    $reply = trim($_POST['reply']);

    if (!empty($reply)) {
        $stmt = $conn->prepare("UPDATE comments SET reply = ? WHERE id = ?");
        $stmt->bind_param("si", $reply, $comment_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: dashboard-pengajar.php");
    exit;
}

// Hapus komentar forum
if (isset($_GET['hapus_komentar'])) {
    $id = (int)$_GET['hapus_komentar'];
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard-pengajar.php");
    exit;
}

// Hapus balasan (hanya hapus isi balasan, bukan komentarnya)
if (isset($_GET['hapus_balasan'])) {
    $id = (int)$_GET['hapus_balasan'];
    $stmt = $conn->prepare("UPDATE comments SET reply = NULL WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard-pengajar.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <link rel="shortcut icon" href="assets/img/favicon.png" type="image/png" />
  <title>Dashboard Pengajar</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
<div class="max-w-6xl mx-auto py-8 px-4">

  <!-- Navigasi Level -->
  <div class="mb-6 flex flex-wrap gap-2">
    <?php foreach ($levels as $num => $name): ?>
      <a href="?level=<?= $num ?>" class="px-4 py-2 rounded <?= $num == $currentLevel ? 'bg-blue-600 text-white' : 'bg-gray-200' ?>">
        Level <?= $num ?>: <?= $name ?>
      </a>
    <?php endforeach; ?>
  </div>

  <h1 class="text-3xl font-bold mb-6">Dashboard Pengajar – <?= $currentLevelName ?></h1>

  <!-- Form Tambah Soal Kuis -->
  <section class="mb-12">
    <h2 class="text-xl font-semibold mb-4">🧠 Tambah Soal Kuis</h2>
    <?php if (isset($_GET['kuis_success'])): ?>
      <p class="text-green-600 mb-2">✅ Soal kuis berhasil ditambahkan.</p>
    <?php endif; ?>
    <form method="POST" class="bg-white p-6 rounded shadow space-y-4">
      <input type="hidden" name="tambah_kuis" value="1">
      <label>Soal<textarea name="soal" required class="border p-2 w-full"></textarea></label>
      <label>Pilihan A<input name="a" required class="border p-2 w-full"></label>
      <label>Pilihan B<input name="b" required class="border p-2 w-full"></label>
      <label>Pilihan C<input name="c" required class="border p-2 w-full"></label>
      <label>Pilihan D<input name="d" required class="border p-2 w-full"></label>
      <label>Jawaban Benar
        <select name="jawaban" required class="border p-2 w-full">
          <option value="">--Pilih--</option>
          <option value="a">A</option>
          <option value="b">B</option>
          <option value="c">C</option>
          <option value="d">D</option>
        </select>
      </label>
      <button class="bg-blue-600 text-white px-6 py-2 rounded">Simpan Soal</button>
    </form>
  </section>

  <!-- Daftar Soal Kuis -->
  <section class="mb-12">
    <h2 class="text-xl font-semibold mb-4">📋 Daftar Soal Kuis</h2>
    <div class="overflow-auto bg-white rounded shadow">
      <table class="min-w-full">
        <thead class="bg-gray-200">
          <tr>
            <th class="p-2">Soal</th>
            <th>Pilihan A</th>
            <th>B</th>
            <th>C</th>
            <th>D</th>
            <th>Jawaban</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $tabel_kuis = "kuis" . $currentLevel;
          $kuis_q = $conn->query("SELECT * FROM $tabel_kuis ORDER BY created_at DESC");
          while ($k = $kuis_q->fetch_assoc()): ?>
            <tr>
              <td class="p-2"><?= htmlspecialchars($k['soal']) ?></td>
              <td><?= $k['pilihan_a'] ?></td>
              <td><?= $k['pilihan_b'] ?></td>
              <td><?= $k['pilihan_c'] ?></td>
              <td><?= $k['pilihan_d'] ?></td>
              <td class="text-center font-bold"><?= strtoupper($k['jawaban_benar']) ?></td>
              <td>
                <a href="?level=<?= $currentLevel ?>&hapus_soal=<?= $k['id_soal'] ?>"
                  onclick="return confirm('Yakin hapus soal ini?')"
                  class="text-red-500 hover:underline">
                  Hapus
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </section>


  <!-- Form Tambah Tugas -->
  <section class="mb-12">
    <h2 class="text-xl font-semibold mb-4">➕ Tambah Tugas</h2>
    <?php if (!empty($upload_error)): ?><p class="text-red-500"><?= $upload_error ?></p><?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded shadow space-y-4">
      <input type="hidden" name="level" value="<?= $currentLevel ?>">
      <label>Judul<input name="judul" required class="border p-2 w-full"></label>
      <label>Kategori
        <select name="kategori" required class="border p-2 w-full">
          <option value="">--</option>
          <option value="reading">Reading</option>
          <option value="listening">Listening</option>
          <option value="materi">Materi</option>
        </select>
      </label>
      <label>Deskripsi<textarea name="deskripsi" class="border p-2 w-full"></textarea></label>
      <label>Isi Teks (Reading)<textarea name="isi_teks" class="border p-2 w-full"></textarea></label>
      <label>Upload File Tugas<input type="file" name="file" class="w-full"></label>
      <label>Upload Gambar<input type="file" name="gambar" accept="image/*" class="w-full"></label>
      <label class="inline-flex items-center space-x-2">
        <input type="checkbox" name="is_penugasan" class="form-checkbox">
        <span>Penugasan (perlu dikerjakan siswa)</span>
      </label>
      <button name="tambah_tugas" class="bg-blue-600 text-white px-6 py-2 rounded">Simpan</button>
    </form>
  </section>

  <!-- Daftar Tugas -->
  <section class="mb-12">
    <h2 class="text-xl font-semibold mb-4">📂 Daftar Tugas</h2>
    <div class="overflow-auto bg-white rounded shadow">
      <table class="min-w-full">
        <thead class="bg-yellow-100"><tr><th>Judul</th><th>Kategori</th><th>File</th><th>Gbr</th><th>Penugasan</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php while ($t = $tugas_q->fetch_assoc()): ?>
          <tr>
            <td><?= $t['judul'] ?></td>
            <td><?= $t['tipe'] ?></td>
            <td><?= $t['file_path'] ? "<a href='{$t['file_path']}' download>Lihat</a>" : "-" ?></td>
            <td><?= $t['gambar_path'] ? "<img src='{$t['gambar_path']}' class='w-16'>" : "-" ?></td>
            <td><?= $t['is_penugasan'] ? '✅' : '—' ?></td>
            <td><a href="?hapus=<?= $t['id'] ?>" onclick="return confirm('Yakin?')" class="text-red-500">Hapus</a></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Respon Siswa -->
  <section class="mb-12">
    <h2 class="text-xl font-semibold mb-4">📝 Respon Siswa</h2>
    <table class="min-w-full bg-white rounded shadow">
      <thead class="bg-blue-100"><tr><th>Siswa</th><th>Tugas</th><th>Respon</th><th>Tanggal</th><th>Nilai</th></tr></thead>
      <tbody>
      <?php while ($r = $respon_q->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($r['nama_siswa']) ?></td>
          <td><?= htmlspecialchars($r['judul']) ?></td>
          <td>
            <?php
              $is_audio = in_array(strtolower(pathinfo($r['file_respon'], PATHINFO_EXTENSION)), ['mp3','wav']);
              if ($is_audio && file_exists($r['file_respon'])) {
                echo "<audio controls class='w-48'><source src='{$r['file_respon']}' type='audio/" . pathinfo($r['file_respon'], PATHINFO_EXTENSION) . "'></audio>";
              } elseif ($r['file_respon']) {
                echo "<a href='{$r['file_respon']}' download class='text-blue-600'>Unduh</a>";
              } else {
                echo nl2br(htmlspecialchars($r['teks_respon']));
              }
            ?>
          </td>
          <td><?= date('d M Y H:i', strtotime($r['created_at'])) ?></td>
          <td>
            <form method="POST" class="flex items-center gap-2">
              <input type="hidden" name="respon_id" value="<?= $r['id'] ?>">
              <input type="number" name="nilai" min="0" max="100" value="<?= $r['nilai_pengajar'] ?? '' ?>" class="border p-1 w-20">
              <button name="update_nilai" class="bg-green-500 text-white px-2 py-1 rounded">✔</button>
            </form>
            <a href="?level=<?= $currentLevel ?>&hapus_respon=<?= $r['id'] ?>" onclick="return confirm('Yakin ingin menghapus respon ini?')" class="text-red-600 text-sm hover:underline block mt-1">Hapus</a>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </section>

  <!-- Skor Kuis -->
  <section>
    <h2 class="text-xl font-semibold mb-4">📊 Skor Kuis Siswa</h2>
    <table class="min-w-full bg-white rounded shadow">
      <thead class="bg-green-100"><tr><th>Nama</th><th>Nilai</th><th>Tanggal</th></tr></thead>
      <tbody>
      <?php while ($row = $skor_q->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
          <td><?= $row['nilai'] ?></td>
          <td><?= date("d M Y H:i", strtotime($row['created_at'])) ?></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </section>

  <!-- Forum Diskusi -->
  <section class="mt-16">
    <h2 class="text-xl font-bold mb-4">💬 Komentar Forum</h2>
    <div class="space-y-6">
      <?php
      $conn = new mysqli("localhost", "root", "", "littlelingo");
      $result = $conn->query("SELECT * FROM comments ORDER BY created_at DESC");

      while ($row = $result->fetch_assoc()):
      ?>
        <div class="bg-white p-4 rounded shadow">
          <div class="flex justify-between">
            <span class="font-semibold text-gray-800"><?= htmlspecialchars($row['name']) ?></span>
            <span class="text-sm text-gray-500"><?= date('d M Y H:i', strtotime($row['created_at'])) ?></span>
          </div>
          <p class="mt-2 text-gray-700"><?= nl2br(htmlspecialchars($row['message'])) ?></p>

          <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'pengajar'): ?>
            <a href="?hapus_komentar=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus komentar ini?')" class="text-red-600 text-sm hover:underline ml-2">Hapus Komentar</a>
          <?php endif; ?>

          <?php if (!empty($row['reply'])): ?>
            <div class="mt-4 bg-gray-100 p-3 rounded">
              <p class="text-sm text-gray-600">
                <strong>Balasan Pengajar:</strong><br><?= nl2br(htmlspecialchars($row['reply'])) ?>
                <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'pengajar'): ?>
                  <br><a href="?hapus_balasan=<?= $row['id'] ?>" onclick="return confirm('Hapus balasan ini?')" class="text-red-600 text-sm hover:underline">Hapus Balasan</a>
                <?php endif; ?>
              </p>
            </div>
          <?php else: ?>
            <form method="POST" class="mt-4">
              <input type="hidden" name="comment_id" value="<?= $row['id'] ?>">
              <textarea name="reply" rows="2" class="border w-full p-2 rounded" placeholder="Tulis balasan..."></textarea>
              <button type="submit" name="balas_komentar" class="mt-2 bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700">Balas</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endwhile; ?>
    </div>
  </section>

<?php if ($_SESSION['role'] === 'admin'): ?>
  <section class="mb-12">
    <h2 class="text-xl font-semibold mb-4">👨‍💼 Manajemen Admin</h2>
    <a href="edit-user.php" class="bg-green-600 text-white px-4 py-2 rounded">Tambah/Edit Pengguna</a>
  </section>
<?php endif; ?>
</div>
</body>
</html>