<?php
// generate_password.php
// Letakkan file ini di folder project, lalu akses via browser.
// Contoh: http://localhost/absensi_rfid/generate_password.php

if (php_sapi_name() === 'cli') {
    echo "Run via browser\n";
    exit;
}

function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$username = '';
$password = '';
$hash = '';
$sql_insert = '';
$sql_update = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Username dan password harus diisi.";
    } else {
        // generate hash
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // prepare SQL (escape single quotes for safe paste)
        $u_escaped = str_replace("'", "''", $username);
        $h_escaped = str_replace("'", "''", $hash);

        // Insert (untuk menambahkan akun baru)
        $sql_insert = "INSERT INTO admin (username, password) VALUES ('" . $u_escaped . "', '" . $h_escaped . "');";

        // Update (untuk mengganti password user yang sudah ada)
        $sql_update = "UPDATE admin SET password = '" . $h_escaped . "' WHERE username = '" . $u_escaped . "';";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Generate Password Hash & SQL - absensi_rfid</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body{font-family:Arial,Helvetica,sans-serif;background:#f7f7f7;padding:24px;}
        .card{max-width:760px;margin:0 auto;background:#fff;padding:18px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.06);}
        label{display:block;margin-top:10px;font-weight:600;}
        input[type=text], input[type=password]{width:100%;padding:10px;margin-top:6px;border:1px solid #d0d0d0;border-radius:6px;}
        button{margin-top:12px;padding:10px 14px;border:0;border-radius:6px;background:#0b74de;color:#fff;cursor:pointer;}
        pre{background:#f1f1f1;padding:12px;border-radius:6px;overflow:auto;}
        .note{font-size:0.9em;color:#444;margin-top:8px;}
        .err{color:#b00020;margin-top:8px}
    </style>
</head>
<body>
    <div class="card">
        <h2>Generate Password Hash & SQL untuk Tabel <code>admin</code></h2>
        <p class="note">Masukkan <strong>username</strong> dan <strong>password</strong> di bawah — lalu klik <em>Generate</em>. Salin SQL yang muncul dan paste ke phpMyAdmin.</p>

        <?php if ($error): ?>
            <div class="err"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" value="<?= h($username ?: 'admin') ?>" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" value="<?= h($password ?: 'adit') ?>" required>

            <button type="submit">Generate</button>
        </form>

        <?php if ($hash): ?>
            <h3 style="margin-top:18px">Hasil</h3>

            <label>Password plain</label>
            <pre><?= h($password) ?></pre>

            <label>Password hash (simpan ini di kolom <code>password</code>)</label>
            <pre><?= h($hash) ?></pre>

            <label>SQL untuk <strong>INSERT</strong> (menambah akun baru)</label>
            <pre><?= h($sql_insert) ?></pre>
            <div class="note">Cara pakai: buka <code>phpMyAdmin → absensi_rfid → tab SQL</code>, paste perintah ini lalu klik <strong>Go</strong>.</div>

            <label style="margin-top:8px">SQL untuk <strong>UPDATE</strong> (ganti password user yang sudah ada)</label>
            <pre><?= h($sql_update) ?></pre>
            <div class="note">Gunakan ini jika username sudah ada — paste di SQL phpMyAdmin lalu Go.</div>
        <?php endif; ?>

        <hr style="margin-top:18px">

        <div class="note">
            <strong>Petunjuk cepat:</strong>
            <ol>
                <li>Letakkan file ini di folder project: <code>C:\laragon\www\absensi_rfid\generate_password.php</code></li>
                <li>Buka di browser: <code>http://localhost/absensi_rfid/generate_password.php</code></li>
                <li>Isi username & password lalu klik Generate</li>
                <li>Salin SQL yang tampil dan paste di phpMyAdmin → database <code>absensi_rfid</code> → tab SQL → klik Go</li>
            </ol>
        </div>
    </div>
</body>
</html>
