<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $conn = new mysqli("localhost", "root", "", "littlelingo");
  if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
  }

  $name = $_POST['name'];
  $message = $_POST['message'];
  $level = 2; // Level khusus menu ini

  if (!empty($name) && !empty($message)) {
    $stmt = $conn->prepare("INSERT INTO comments (name, message, level) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $name, $message, $level);
    $stmt->execute();
    $stmt->close();
  }

  $conn->close();
  header("Location: menu-level2.php#forum");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="shortcut icon" href="assets/img/favicon.png" type="image/png" />
  <title>LittleLingo - Favorite Things</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-white font-[Poppins]">

<header class="bg-white shadow-md relative">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between py-4 relative">

      <!-- Logo dibungkus <a> agar bisa diklik -->
      <div class="flex items-center">
        <a href="index.html" class="flex items-center hover:opacity-80">
          <img src="assets/img/logo.png" alt="Logo" class="h-10 w-auto mr-2" />
        </a>
      </div>

      <!-- Judul di tengah -->
      <div class="absolute left-1/2 transform -translate-x-1/2">
        <span class="font-bold text-xl text-blue-600">Talking About Favorite Things</span>
      </div>
      
    </div>
  </div>
</header>


<section class="max-w-6xl mx-auto px-4 py-12">
  <div class="text-center mb-10">
    <h2 class="text-3xl font-bold">Your <em class="text-yellow-500">Activities</em></h2>
    <div class="h-1 w-20 bg-yellow-500 mx-auto mt-2 rounded-full"></div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
    <!-- Kuis -->
    <div class="bg-white rounded-3xl shadow-lg overflow-hidden">
      <img src="assets/img/menu-quiz.png" alt="Quiz" class="w-full h-64 object-cover">
      <div class="p-6">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs bg-blue-200 text-blue-700 px-3 py-1 rounded-full">Quiz</span>
        </div>
        <h3 class="text-xl font-bold mb-2">Talking About Favorite Things</h3>
        <p class="text-gray-500 text-sm mb-4">Uji pemahamanmu setelah belajar tentang kesukaan dan preferensi!</p>
        <div class="flex items-center justify-between">
          <a href="kuis-2.html" class="bg-blue-500 text-white text-sm px-6 py-4 rounded-full hover:bg-blue-600 transition">Kerjakan Kuis Sekarang!</a>
        </div>
      </div>
    </div>

    <!-- Reading, Listening, Materi -->
    <div class="flex flex-col space-y-6">
      <a href="reading-2.php" class="flex items-start space-x-4 hover:bg-blue-50 p-2 rounded-xl transition">
        <img src="assets/img/menu-read.png" alt="Reading" class="w-24 h-24 rounded-2xl object-cover">
        <div>
          <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full"><i>Reading</i></span>
          <h4 class="font-semibold mt-1">Hobi dan Makanan Favorit</h4>
          <p class="text-gray-500 text-sm">Pelajari bagaimana menyatakan hobi dan makanan yang kamu sukai.</p>
        </div>
      </a>

      <a href="listening-2.php" class="flex items-start space-x-4 hover:bg-blue-50 p-2 rounded-xl transition">
        <img src="assets/img/menu-listen.png" alt="Listening" class="w-24 h-24 rounded-2xl object-cover">
        <div>
          <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full"><i>Listening</i></span>
          <h4 class="font-semibold mt-1">Mendengarkan Kesukaan Teman</h4>
          <p class="text-gray-500 text-sm">Latih kemampuan mendengarmu untuk mengenali kata dan frasa tentang kesukaan.</p>
        </div>
      </a>

      <a href="uploads/materi/Talking About Favorite Things-2.pdf" download class="flex items-start space-x-4 hover:bg-blue-50 p-2 rounded-xl transition">
        <img src="assets/img/menu-materi.png" alt="Materi PDF" class="w-24 h-24 rounded-2xl object-cover">
        <div>
          <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">Materi Pembelajaran</span>
          <h4 class="font-semibold mt-1">Dialog Kesukaan</h4>
          <p class="text-gray-500 text-sm">Unduh materi PDF untuk belajar lebih lanjut tentang percakapan seputar kesukaan.</p>
        </div>
      </a>
    </div>
  </div>
</section>

<section class="bg-gray-100 py-12 mt-16" id="forum">
  <div class="max-w-4xl mx-auto px-4">
    <h2 class="text-2xl font-bold text-center text-blue-600 mb-6">💬 Forum Diskusi</h2>
    <p class="text-center text-gray-600 mb-8">Tanyakan apapun tentang topik favorit, materi, atau kuis!</p>

    <!-- Form Komentar -->
    <form method="POST" class="bg-white p-6 rounded-xl shadow-md space-y-4">
      <input type="hidden" name="level" value="2">
      <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Nama:</label>
        <input type="text" id="name" name="name" class="mt-1 block w-full border rounded-lg px-4 py-2" required>
      </div>
      <div>
        <label for="message" class="block text-sm font-medium text-gray-700">Komentar:</label>
        <textarea id="message" name="message" rows="4" class="mt-1 block w-full border rounded-lg px-4 py-2" required></textarea>
      </div>
      <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700">Kirim</button>
    </form>

    <!-- Daftar Komentar -->
    <div class="mt-8 space-y-4">
      <?php
      $conn = new mysqli("localhost", "root", "", "littlelingo");
      if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

      $sql = "SELECT * FROM comments WHERE level = 2 ORDER BY created_at DESC";
      $result = $conn->query($sql);

      if ($result->num_rows > 0):
        while ($row = $result->fetch_assoc()):
      ?>
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="flex justify-between items-center">
            <span class="font-semibold text-gray-700"><?= htmlspecialchars($row['name']) ?></span>
            <span class="text-sm text-gray-400"><?= date('d M Y H:i', strtotime($row['created_at'])) ?></span>
          </div>
          <p class="text-gray-600 mt-2"><?= nl2br(htmlspecialchars($row['message'])) ?></p>

          <?php if (!empty($row['reply'])): ?>
          <div class="mt-3 ml-4 p-3 bg-blue-50 border-l-4 border-blue-400 rounded">
            <p class="text-sm text-gray-700"><strong>Balasan Pengajar:</strong></p>
            <p class="text-gray-800"><?= nl2br(htmlspecialchars($row['reply'])) ?></p>
          </div>
          <?php endif; ?>
        </div>
      <?php
        endwhile;
      else:
        echo "<p class='text-gray-500'>Belum ada komentar untuk level ini.</p>";
      endif;

      $conn->close();
      ?>
    </div>
  </div>
</section>

</body>
</html>
