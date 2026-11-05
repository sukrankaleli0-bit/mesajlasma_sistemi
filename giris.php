<?php
session_start();
require_once "db.php";

$mesaj = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $sifre = $_POST["sifre"] ?? "";

    if (empty($email) || empty($sifre)) {
        $mesaj = "Lütfen tüm alanları doldurun.";
    } else {
        $sorgu = $db->prepare(
            "SELECT id, ad, sifre FROM kullanicilar WHERE email = ?"
        );
        $sorgu->execute([$email]);
        $kullanici = $sorgu->fetch(PDO::FETCH_ASSOC);

        if ($kullanici && password_verify($sifre, $kullanici["sifre"])) {
            session_regenerate_id(true);

            $_SESSION["kullanici_id"] = $kullanici["id"];
            $_SESSION["kullanici_adi"] = $kullanici["ad"];

            header("Location: mesajlar.php");
            exit;
        } else {
            $mesaj = "E-posta veya şifre hatalı.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kullanıcı Girişi</title>
</head>
<body>

    <h2>Mesajlaşma Sistemi - Giriş Yap</h2>

    <form method="POST">
        <label>E-posta:</label>
        <input type="email" name="email" required>
        <br><br>

        <label>Şifre:</label>
        <input type="password" name="sifre" required>
        <br><br>

        <button type="submit">Giriş Yap</button>
    </form>

    <p>
        <?php echo htmlspecialchars($mesaj, ENT_QUOTES, 'UTF-8'); ?>
    </p>

    <p>Hesabın yok mu? <a href="kayit.php">Kayıt Ol</a></p>

</body>
</html>
