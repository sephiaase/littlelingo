<?php
session_start();
$conn = new mysqli("localhost", "root", "", "littlelingo");

if (!in_array($_SESSION['role'], ['admin', 'pengajar'])) {
    header("Location: login-pengguna.php");
    exit;
}

if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// Tambah pengguna
if (isset($_POST['tambah_pengguna'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO pengguna (nama, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $nama, $email, $password, $role);
    $stmt->execute();
    $stmt->close();
    header("Location: edit-user.php");
    exit;
}

// Update pengguna
if (isset($_POST['update_pengguna'])) {
    $id = $_POST['id_pengguna'];
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE pengguna SET nama=?, email=?, role=? WHERE id_pengguna=?");
    $stmt->bind_param("sssi", $nama, $email, $role, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: edit-user.php");
    exit;
}

// Hapus pengguna
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM pengguna WHERE id_pengguna=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: edit-user.php");
    exit;
}

$users = $conn->query("SELECT * FROM pengguna ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Manajemen Pengguna</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
  <div class="max-w-4xl mx-auto py-10">
    <h1 class="text-3xl font-bold mb-2 text-center">Manajemen Pengguna</h1>
      <div class="text-center mb-6">
      <a href="dashboard-pengajar.php" class="inline-block bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300">
        ← Kembali ke Dashboard
      </a>
      </div>

    <!-- Form Tambah Pengguna -->
    <div class="bg-white p-6 mb-10 rounded shadow">
      <h2 class="text-xl font-semibold mb-4">➕ Tambah Pengguna</h2>
      <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="tambah_pengguna" value="1">
        <input name="nama" placeholder="Nama" required class="border p-2">
        <input name="email" type="email" placeholder="Email" required class="border p-2">
        <input name="password" type="password" placeholder="Password" required class="border p-2">
        <select name="role" required class="border p-2">
          <option value="">-- Role --</option>
          <option value="siswa">Siswa</option>
          <option value="pengajar">Pengajar</option>
          <option value="admin">Admin</option>
        </select>
        <button class="bg-blue-600 text-white px-4 py-2 rounded md:col-span-2">Simpan</button>
      </form>
    </div>

    <!-- Daftar Pengguna -->
    <div class="bg-white p-6 rounded shadow">
      <h2 class="text-xl font-semibold mb-4">📋 Daftar Pengguna</h2>
      <table class="min-w-full border">
        <thead class="bg-gray-200">
          <tr>
            <th class="p-2 border">Nama</th>
            <th class="p-2 border">Email</th>
            <th class="p-2 border">Role</th>
            <th class="p-2 border">Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($u = $users->fetch_assoc()): ?>
          <tr>
            <form method="POST">
              <td class="border p-2">
                <input type="text" name="nama" value="<?= htmlspecialchars($u['nama']) ?>" class="border p-1 w-full">
              </td>
              <td class="border p-2">
                <input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" class="border p-1 w-full">
              </td>
              <td class="border p-2">
                <select name="role" class="border p-1 w-full">
                  <option value="siswa" <?= $u['role']=='siswa'?'selected':'' ?>>Siswa</option>
                  <option value="pengajar" <?= $u['role']=='pengajar'?'selected':'' ?>>Pengajar</option>
                  <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>Admin</option>
                </select>
              </td>
              <td class="border p-2 flex gap-2">
                <input type="hidden" name="id_pengguna" value="<?= $u['id_pengguna'] ?>">
                <button name="update_pengguna" class="bg-green-500 text-white px-2 py-1 rounded">✔ Simpan</button>
                <a href="?hapus=<?= $u['id_pengguna'] ?>" onclick="return confirm('Yakin hapus?')" class="bg-red-500 text-white px-2 py-1 rounded">🗑 Hapus</a>
              </td>
            </form>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>