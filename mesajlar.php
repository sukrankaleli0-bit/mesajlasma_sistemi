<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["kullanici_id"])) {
    header("Location: giris.php");
    exit;
}

$id = (int) $_SESSION["kullanici_id"];
$hata = "";
$basari = $_SESSION["basari"] ?? "";
unset($_SESSION["basari"]);

if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function temizle($deger) {
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

// Mesaj gönderme ve okundu işaretleme
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $hata = "Geçersiz işlem. Sayfayı yenileyin.";
    } elseif (($_POST["islem"] ?? "") === "gonder") {
        $alici = filter_var(
            $_POST["alici_id"] ?? null,
            FILTER_VALIDATE_INT
        );
        $mesaj = trim($_POST["mesaj"] ?? "");

        if (!$alici || $alici == $id || $mesaj === "") {
            $hata = "Geçerli bir alıcı ve mesaj seçin.";
        } elseif (strlen($mesaj) > 5000) {
            $hata = "Mesaj çok uzun.";
        } else {
            $kontrol = $db->prepare(
                "SELECT id FROM kullanicilar WHERE id = ?"
            );
            $kontrol->execute([$alici]);

            if (!$kontrol->fetch()) {
                $hata = "Alıcı bulunamadı.";
            } else {
                $sorgu = $db->prepare(
                    "INSERT INTO mesajlar
                    (gonderen_id, alici_id, mesaj)
                    VALUES (?, ?, ?)"
                );
                $sorgu->execute([$id, $alici, $mesaj]);

                $_SESSION["basari"] = "Mesaj gönderildi!";
                header("Location: mesajlar.php");
                exit;
            }
        }
    } elseif (($_POST["islem"] ?? "") === "okundu") {
        $mesaj_id = filter_var(
            $_POST["mesaj_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($mesaj_id) {
            $sorgu = $db->prepare(
                "UPDATE mesajlar SET is_read = 1
                 WHERE id = ? AND alici_id = ?"
            );
            $sorgu->execute([$mesaj_id, $id]);

            header("Location: mesajlar.php");
            exit;
        }
    }
}

// Alıcı listesini getir
$sorgu = $db->prepare(
    "SELECT id, ad FROM kullanicilar
     WHERE id != ? ORDER BY ad"
);
$sorgu->execute([$id]);
$kullanicilar = $sorgu->fetchAll(PDO::FETCH_ASSOC);

// Okunmamış mesaj sayısı
$sorgu = $db->prepare(
    "SELECT COUNT(*) FROM mesajlar
     WHERE alici_id = ? AND is_read = 0"
);
$sorgu->execute([$id]);
$okunmamis = $sorgu->fetchColumn();

// Kullanıcının mesajlarını getir
$sorgu = $db->prepare(
    "SELECT m.*, g.ad AS gonderen_adi,
            a.ad AS alici_adi
     FROM mesajlar m
     JOIN kullanicilar g ON g.id = m.gonderen_id
     JOIN kullanicilar a ON a.id = m.alici_id
     WHERE m.gonderen_id = ? OR m.alici_id = ?
     ORDER BY m.id DESC LIMIT 50"
);
$sorgu->execute([$id, $id]);
$mesajlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">
    <title>Mesajlarım</title>
</head>
<body>

<h2>Mesajlaşma Sistemine Hoş Geldiniz</h2>

<p>
    Merhaba,
    <?php echo temizle($_SESSION["kullanici_adi"]); ?>
</p>

<p>
    Okunmamış mesaj sayısı:
    <strong><?php echo $okunmamis; ?></strong>
</p>

<?php if ($hata): ?>
    <p><?php echo temizle($hata); ?></p>
<?php endif; ?>

<?php if ($basari): ?>
    <p><?php echo temizle($basari); ?></p>
<?php endif; ?>

<h3>Yeni Mesaj Gönder</h3>

<form method="POST">
    <input type="hidden" name="islem" value="gonder">
    <input type="hidden" name="csrf_token"
           value="<?php echo temizle($_SESSION["csrf_token"]); ?>">

    <label>Alıcı seç:</label>
    <select name="alici_id" required>
        <option value="">Kullanıcı seçiniz</option>
        <?php foreach ($kullanicilar as $k): ?>
            <option value="<?php echo (int)$k["id"]; ?>">
                <?php echo temizle($k["ad"]); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <br><br>

    <label>Mesajınız:</label><br>
    <textarea name="mesaj" rows="5"
              cols="40" maxlength="1000" required></textarea>

    <br><br>
    <button type="submit">Mesaj Gönder</button>
</form>

<hr>

<h3>Mesajlarım</h3>

<?php if (empty($mesajlar)): ?>
    <p>Henüz mesajınız bulunmuyor.</p>
<?php endif; ?>

<?php foreach ($mesajlar as $m): ?>
    <div>
        <p>
            <strong>Gönderen:</strong>
            <?php echo temizle($m["gonderen_adi"]); ?>
        </p>

        <p>
            <strong>Alıcı:</strong>
            <?php echo temizle($m["alici_adi"]); ?>
        </p>

        <p><?php echo nl2br(temizle($m["mesaj"])); ?></p>

        <small>
            <?php echo temizle($m["gonderim_tarihi"]); ?>
        </small>

        <?php if ($m["alici_id"] == $id): ?>
            <?php if ($m["is_read"] == 0): ?>
                <p><strong>Okunmadı</strong></p>

                <form method="POST">
                    <input type="hidden" name="islem"
                           value="okundu">
                    <input type="hidden" name="csrf_token"
                           value="<?php echo temizle($_SESSION["csrf_token"]); ?>">
                    <input type="hidden" name="mesaj_id"
                           value="<?php echo (int)$m["id"]; ?>">
                    <button type="submit">
                        Okundu İşaretle
                    </button>
                </form>
            <?php else: ?>
                <p>Okundu</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <hr>
<?php endforeach; ?>

<p><a href="cikis.php">Çıkış Yap</a></p>

</body>
</html>
