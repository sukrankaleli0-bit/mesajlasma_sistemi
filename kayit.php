<?php
require_once "db.php";

$mesaj = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad = trim($_POST["ad"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $sifre = $_POST["sifre"] ?? "";

    if (empty($ad) || empty($email) || empty($sifre)) {
        $mesaj = "Lütfen tüm alanları doldurun.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mesaj = "Geçerli bir e-posta adresi girin.";
    } elseif (strlen($sifre) < 8) {
        $mesaj = "Şifre en az 8 karakter olmalıdır.";
    } else {
        $sifreli = password_hash($sifre, PASSWORD_DEFAULT);

        try {
            $sorgu = $db->prepare(
                "INSERT INTO kullanicilar (ad, email, sifre)
                 VALUES (?, ?, ?)"
            );

            $sorgu->execute([$ad, $email, $sifreli]);
            $mesaj = "Kayıt başarıyla tamamlandı!";
        } catch (PDOException $e) {
            if ($e->getCode() == "23000") {
                $mesaj = "Bu e-posta zaten kayıtlı.";
            } else {
                error_log($e->getMessage());
                $mesaj = "Kayıt sırasında hata oluştu.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kullanıcı Kaydı</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <h2>Mesajlaşma Sistemi - Kayıt Ol</h2>

    <form method="POST">
        <label>Ad Soyad:</label>
        <input type="text" name="ad" maxlength="100" required>
        <br><br>

        <label>E-posta:</label>
        <input type="email" name="email" required>
        <br><br>

        <label>Şifre:</label>
        <input type="password" name="sifre"
               minlength="8" required>
        <br><br>

        <button type="submit">Kayıt Ol</button>
    </form>

    <p><?php echo htmlspecialchars($mesaj, ENT_QUOTES, 'UTF-8'); ?></p>

</body>
</html>
