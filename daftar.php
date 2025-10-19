<?php

include 'db_connect.php';

// Mulai session di awal setiap halaman yang memerlukannya
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
 die("Koneksi gagal: " . $conn->connect_error);
}

// --- Tambahkan Pemeriksaan Login di sini ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    header("location: login.php");
    exit;
}
// --- Akhir Pemeriksaan Login ---

$pesan = '';
$edit_data = null;

// ===============================
// 1. LOGIKA CRUD (hapus, edit, tambah)
// ===============================
if (isset($_GET['action']) && $_GET['action'] == 'hapus' && isset($_GET['id'])) {
    $id_hapus = $conn->real_escape_string($_GET['id']);
    $conn->query("DELETE FROM karyawan WHERE id='$id_hapus'");
    header("Location: daftar.php");
    exit();
}

if (isset($_POST['action']) && $_POST['action'] == 'update') {
    $id_update = $conn->real_escape_string($_POST['edit_id']);
    $nama_update = $conn->real_escape_string($_POST['nama']);
    $jabatan_update = $conn->real_escape_string($_POST['jabatan']);
    $conn->query("UPDATE karyawan SET nama='$nama_update', jabatan='$jabatan_update' WHERE id='$id_update'");
}

if (isset($_GET['action']) && $_GET['action'] == 'edit') {
    $id_edit = $conn->real_escape_string($_GET['id']);
    $edit_data = $conn->query("SELECT * FROM karyawan WHERE id='$id_edit'")->fetch_assoc();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) {
    $uid = $conn->real_escape_string($_POST['uid']);
    $nama = $conn->real_escape_string($_POST['nama']);
    $jabatan = $conn->real_escape_string($_POST['jabatan']);
    $cek = $conn->query("SELECT * FROM karyawan WHERE uid='$uid'");
    if ($cek->num_rows == 0) {
        $conn->query("INSERT INTO karyawan (uid,nama,jabatan) VALUES ('$uid','$nama','$jabatan')");
        $conn->query("UPDATE uid_terakhir SET uid='' WHERE id=1");
    }
}

$karyawan_list = $conn->query("SELECT * FROM karyawan ORDER BY nama ASC");
$uid_terakhir = $conn->query("SELECT uid FROM uid_terakhir WHERE id=1")->fetch_assoc()['uid'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Daftar Karyawan RFID</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        /* Variabel Warna (Admin Klasik: Terang/Gelap/Aksen Biru) */
        :root {
            --color-sidebar-dark: #20354b;
            /* Biru Gelap - Sidebar */
            --color-main-bg: #f5f7fa;
            /* Latar belakang konten utama */
            --color-accent-blue: #007bff;
            /* Biru sebagai warna aksen utama */
            --color-text-dark: #343a40;
            --color-text-light: #f8f9fa;
            --color-card-bg: #ffffff;
            --color-table-header: #e9ecef;
            --color-danger: #dc3545;
            /* Merah standar */
            --color-warning: #ffc107;
            /* Kuning standar */
        }

        body {
            margin: 0;
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background-color: var(--color-main-bg);
            color: var(--color-text-dark);
            display: flex;
            min-height: 100vh;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: var(--color-main-bg);
            color: var(--color-text-dark);
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: var(--color-sidebar-dark);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding-top: 20px;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }

        .sidebar h2 {
            color: #f8f9fa;
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 30px 0;
            padding: 10px 0 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }

        .sidebar a {
            color: #f8f9fa;
            text-decoration: none;
            padding: 14px 25px;
            display: block;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s, border-left 0.3s;
            border-left: 3px solid transparent;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #34495e;
            border-left-color: var(--color-accent-blue);
        }

        .main {
            margin-left: 250px;
            flex-grow: 1;
            padding: 30px;
            background-color: var(--color-main-bg);
        }

        .main h2 {
            color: var(--color-text-dark);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }

        .date-info {
            font-size: 16px;
            margin-bottom: 30px;
            color: #6c757d;
            font-weight: 500;
        }

        /* === Konten utama & Card === */
        .main {
            margin-left: 250px;
            flex-grow: 1;
            padding: 30px;
            background-color: var(--color-main-bg);
        }

        .card {
            background: var(--color-card-bg);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            /* Bayangan halus dan tegas */
            max-width: 900px;
            margin: auto;
        }

        h2,
        h3 {
            color: var(--color-text-dark);
            font-weight: 600;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 24px;
        }

        h3 {
            font-size: 20px;
            margin-top: 40px;
        }

        /* === Form === */
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }

        input[type="text"],
        input[type="text"][disabled] {
            padding: 10px 15px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 15px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input[type="text"]:focus {
            border-color: var(--color-accent-blue);
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            outline: none;
        }

        input[type="text"][disabled] {
            background-color: #e9ecef;
            cursor: not-allowed;
        }

        input[type="submit"] {
            background-color: var(--color-accent-blue);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: 0.3s;
            font-weight: 600;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .uid-note {
            text-align: left;
            font-size: 14px;
            color: #6c757d;
            margin-top: -5px;
        }

        /* === Tabel === */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: var(--color-card-bg);
        }

        th,
        td {
            border: 1px solid #dee2e6;
            padding: 12px;
            text-align: left;
            font-size: 14px;
        }

        th {
            background-color: var(--color-table-header);
            color: var(--color-text-dark);
            font-weight: 700;
            text-align: center;
        }

        td {
            text-align: center;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #e2f2ff;
        }

        /* === Tombol Aksi === */
        .btn-edit {
            background-color: var(--color-warning);
            color: var(--color-text-dark);
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 600;
            transition: 0.2s;
            margin-right: 5px;
        }

        .btn-hapus {
            background-color: var(--color-danger);
            color: white;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn-edit:hover {
            background-color: #e0a800;
        }

        .btn-hapus:hover {
            background-color: #bd2130;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            background-color: var(--color-accent-blue);
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            font-weight: 500;
            transition: 0.3s;
        }

        .back-link:hover {
            background-color: #0056b3;
        }

        
    </style>
    <script>
        function konfirmasiHapus(id) {
            if (confirm("Yakin ingin menghapus data ini?")) {
                window.location.href = 'daftar.php?action=hapus&id=' + id;
            }
        }
    </script>
</head>

<body>
    <div class="sidebar" style="width: 230px; background: #2c3e50; min-height: 100vh; padding: 15px 0;">
        <div style="display: flex; justify-content: center; align-items: center; margin-top: 5px;">
            <img src="./asset/pemda.png" alt="Pemda Lotim" width="35px" style="padding: 0 5px;">
            <img src="./asset/pemda.png" alt="Pemda Lotim" width="35px" style="padding: 0 5px;">
        </div>

        <h2 style="text-align: center; color: white; margin: 5px 0;">ABSENSI RFID DESA JURIT</h2>

        <a href="dashboard.php"
            style="display: flex; align-items: center; padding: 10px 15px; text-decoration: none; color: white; font-size: 16px; margin: 5px;">
            <i class="bi bi-house-gear-fill" style="font-size: 22px; margin-right: 10px; vertical-align: middle;"></i> Dashboard
        </a>

        <a href="index.php"
            style="display: flex; align-items: center; padding: 10px 15px; text-decoration: none; color: white; font-size: 16px; margin: 5px;">

            <i class="bi bi-clock-fill" style="font-size: 22px; margin-right: 10px; vertical-align: middle;"></i> Data Absensi
        </a>

        <a href="daftar.php" class="active"
            style="display: flex; align-items: center; padding: 10px 15px; text-decoration: none; color: white; font-size: 16px; background-color: #007bff; border-radius: 5px; margin: 5px;">

            <i class="bi bi-person-vcard-fill" style="font-size: 22px; margin-right: 10px; vertical-align: middle;"></i> Daftar Kartu
        </a>

        <a href="laporan.php"
            style="display: flex; align-items: center; padding: 10px 15px; text-decoration: none; color: white; font-size: 16px; margin: 5px;">
            <i class="bi bi-file-earmark-arrow-down-fill" style="font-size: 22px; margin-right: 10px; vertical-align: middle;"></i> Laporan
        </a>

        <a href="logout.php"
            style="display: flex; align-items: center; padding: 10px 15px; text-decoration: none; color: white; font-size: 16px; margin: 5px;">
            🚪 Logout
        </a>
    </div>

    <div class="main">
        <div class="card">
            <h2><?php echo $edit_data ? "Edit Data Karyawan" : "Pendaftaran Karyawan Baru"; ?></h2>

            <form method="post" action="daftar.php">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="edit_id" value="<?php echo $edit_data['id']; ?>">
                    <input type="text" name="nama" value="<?php echo htmlspecialchars($edit_data['nama']); ?>" required>
                    <input type="text" name="jabatan" value="<?php echo htmlspecialchars($edit_data['jabatan']); ?>" required>
                    <input type="text" value="<?php echo htmlspecialchars($edit_data['uid']); ?>" disabled>
                    <input type="submit" value="Update Data">
                <?php else: ?>
                    <input type="text" name="nama" placeholder="Nama Lengkap" required>
                    <input type="text" name="jabatan" placeholder="Jabatan" required>
                    <input type="text" name="uid" placeholder="UID Kartu RFID" value="<?php echo htmlspecialchars($uid_terakhir); ?>" required>
                    <p class="uid-note">Tempelkan kartu di alat, lalu refresh halaman ini. UID akan muncul otomatis.</p>
                    <input type="submit" value="Daftarkan Karyawan">
                <?php endif; ?>
            </form>

            <?php if ($edit_data): ?>
                <p style="text-align:center;"><a href="daftar.php" class="back-link">← Kembali ke Pendaftaran</a></p>
            <?php endif; ?>

            <h3>Data Karyawan Terdaftar</h3>
            <table>
                <tr>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th>UID Kartu</th>
                    <th>Aksi</th>
                </tr>
                <?php if ($karyawan_list->num_rows > 0): ?>
                    <?php while ($row = $karyawan_list->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nama']); ?></td>
                            <td><?php echo htmlspecialchars($row['jabatan']); ?></td>
                            <td><?php echo htmlspecialchars($row['uid']); ?></td>
                            <td>
                                <a href="daftar.php?action=edit&id=<?php echo $row['id']; ?>" class="btn-edit">Edit</a>
                                <a href="javascript:void(0)" onclick="konfirmasiHapus(<?php echo $row['id']; ?>)" class="btn-hapus">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">Belum ada karyawan terdaftar</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
    
</body>

</html>