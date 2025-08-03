<?php
session_start();
$conn = new mysqli("localhost", "root", "", "littlelingo");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['your_name'];
    $password = $_POST['your_pass'];

    // Ambil user berdasarkan nama (bisa pengajar atau admin)
    $stmt = $conn->prepare("SELECT * FROM pengguna WHERE nama = ?");
    $stmt->bind_param("s", $nama);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['id_pengguna'] = $user['id_pengguna'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];

            // Arahkan berdasarkan peran
            if ($user['role'] === 'admin') {
                header("Location: dashboard-pengajar.php");
            } elseif ($user['role'] === 'pengajar') {
                header("Location: dashboard-pengajar.php");
            } else {
                $error = "Role tidak dikenali.";
            }
            exit;
        } else {
            $error = "Kata sandi salah.";
        }
    } else {
        $error = "Akun tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/img/favicon.png" type="image/png" />
    <title>Little Lingo - Login Pengguna</title>
    <link rel="stylesheet" href="fonts/material-icon/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="main">
        <section class="sign-in">
            <div class="container">
                <div class="signin-content">
                    <div class="signin-image">
                        <figure><img src="images/signin-image.jpg" alt="sign in image"></figure>
                        <a href="register-pengguna.php" class="signup-image-link">Belum Memiliki Akun</a>
                    </div>

                    <div class="signin-form">
                        <h2 class="form-title">Masuk Pengguna</h2>
                        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
                        <form method="POST" class="register-form" id="login-form">
                            <div class="form-group">
                                <label for="your_name"><i class="zmdi zmdi-account material-icons-name"></i></label>
                                <input type="text" name="your_name" id="your_name" placeholder="Nama Lengkap" required />
                            </div>
                            <div class="form-group">
                                <label for="your_pass"><i class="zmdi zmdi-lock"></i></label>
                                <input type="password" name="your_pass" id="your_pass" placeholder="Kata Sandi" required />
                            </div>
                            <div class="form-group form-button">
                                <input type="submit" name="signin" id="signin" class="form-submit" value="Masuk" />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
</body>
</html>