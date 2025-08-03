<?php
$conn = new mysqli("localhost", "root", "", "littlelingo");

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["pass"];
    $re_pass = $_POST["re_pass"];

    if ($password !== $re_pass) {
        $error = "Kata sandi tidak cocok.";
    } elseif (empty($_POST["agree-term"])) {
        $error = "Anda harus menyetujui ketentuan layanan.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = "pengajar";

        // Cek apakah email sudah digunakan
        $check = $conn->prepare("SELECT * FROM pengguna WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "Email sudah terdaftar.";
        } else {
            $stmt = $conn->prepare("INSERT INTO pengguna (nama, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $nama, $email, $hashed_password, $role);
            if ($stmt->execute()) {
                $success = "Akun berhasil dibuat. Silakan login.";
            } else {
                $error = "Terjadi kesalahan saat menyimpan data.";
            }
            $stmt->close();
        }

        $check->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/img/favicon.png" type="image/png" />
    <title>Little Lingo - Daftar Pengguna</title>

    <!-- Font Icon -->
    <link rel="stylesheet" href="fonts/material-icon/css/material-design-iconic-font.min.css">

    <!-- Main css -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="../assets/css/lineicons.css" />
</head>
<body>

<div class="main">

    <!-- Sign up form -->
    <section class="signup">
        <div class="container">
            <div class="signup-content">
                <div class="signup-form">
                    <h2 class="form-title">Daftar Pengguna</h2>
                    
                    <?php if (!empty($error)): ?>
                        <p style="color: red;"><?= $error ?></p>
                    <?php elseif (!empty($success)): ?>
                        <p style="color: green;"><?= $success ?></p>
                    <?php endif; ?>

                    <form method="POST" class="register-form" id="register-form">
                        <div class="form-group">
                            <label for="name"><i class="zmdi zmdi-account material-icons-name"></i></label>
                            <input type="text" name="name" id="name" placeholder="Nama Lengkap" required/>
                        </div>
                        <div class="form-group">
                            <label for="email"><i class="zmdi zmdi-email"></i></label>
                            <input type="email" name="email" id="email" placeholder="E-mail" required/>
                        </div>
                        <div class="form-group">
                            <label for="pass"><i class="zmdi zmdi-lock"></i></label>
                            <input type="password" name="pass" id="pass" placeholder="Kata Sandi" required/>
                        </div>
                        <div class="form-group">
                            <label for="re_pass"><i class="zmdi zmdi-lock-outline"></i></label>
                            <input type="password" name="re_pass" id="re_pass" placeholder="Ulangi Kata Sandi" required/>
                        </div>
                        <div class="form-group">
                            <input type="checkbox" name="agree-term" id="agree-term" class="agree-term" />
                            <label for="agree-term" class="label-agree-term">
                                <span><span></span></span>
                                Saya setuju dengan semua pernyataan dalam  
                                <a href="terms.html" class="term-service">Ketentuan layanan</a>
                            </label>
                        </div>
                        <div class="form-group form-button">
                            <input type="submit" name="signup" id="signup" class="form-submit" value="Konfirmasi"/>
                        </div>
                    </form>
                </div>
                <div class="signup-image">
                    <figure><img src="images/signup-image.jpg" alt="sign up image"></figure>
                    <a href="login-pengguna.php" class="signup-image-link">Sudah punya akun</a>
                </div>
            </div>
        </div>
    </section>

</div>

<!-- JS -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>