
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $conn = new mysqli("localhost", "root", "", "littlelingo");

  if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
  }

  $name = $_POST['name'];
  $message = $_POST['message'];

  if (!empty($name) && !empty($message)) {
    $stmt = $conn->prepare("INSERT INTO comments (name, message) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $message);
    $stmt->execute();
    $stmt->close();
  }

  $conn->close();
  header("Location: menu-1.php#forum");
  exit;
}
?>
