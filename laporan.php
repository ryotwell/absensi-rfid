<?php
require_once 'db_connect.php';
require_once 'bootstrap.php';

session_start();

if(!isset($_SESSION['user'])) {
    return redirectTo('login.php');
}

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
 die("Koneksi gagal: " . $conn->connect_error);
}

date_default_timezone_set('Asia/Makassar');

$today = date('Y-m-d');
// Default awal: 7 hari yang lalu
$default_awal = date('Y-m-d', strtotime('-7 days'));

// --- LOGIKA BARU: Ambil semua daftar karyawan untuk dropdown Laporan Perorangan ---
$sql_karyawan = "SELECT uid, nama, jabatan FROM karyawan ORDER BY nama ASC";
$result_karyawan = $conn->query($sql_karyawan);
// --- AKHIR LOGIKA BARU ---
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        :root {
            --color-sidebar-dark: #20354b;
            --color-main-bg: #f5f7fa;
            --color-accent-blue: #007bff;
            --color-text-dark: #343a40;
            --color-text-light: #f8f9fa;
            --color-card-bg: #ffffff;
            --color-table-header: #e9ecef;
        }

        body {
            margin: 0;
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background-color: var(--color-main-bg);
            color: var(--color-text-dark);
            display: flex;
            min-height: 100vh;
        }

        .main {
            margin-left: 250px;
            flex-grow: 1;
            padding: 30px;
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

        .card {
            background: var(--color-card-bg);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto 30px auto;
        }

        h2 {
            font-size: 24px;
            color: var(--color-text-dark);
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }

        input[type="date"],
        select {
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
            font-size: 16px;
        }

        button {
            background-color: var(--color-accent-blue);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #0056b3;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-col {
            flex: 1;
        }

        .separator {
            border-top: 2px dashed #ced4da;
            margin: 40px auto;
            width: 90%;
        }
    </style>
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

        <a href="daftar.php"
            style="display: flex; align-items: center; padding: 10px 15px; text-decoration: none; color: white; font-size: 16px; margin: 5px;">
            <i class="bi bi-person-vcard-fill" style="font-size: 22px; margin-right: 10px; vertical-align: middle;"></i> Daftar Kartu
        </a>

        <a href="laporan.php" class="active"
            style="display: flex; align-items: center; padding: 10px 15px; text-decoration: none; color: white; font-size: 16px; background-color: #007bff; border-radius: 5px; margin: 5px;">
            <i class="bi bi-file-earmark-arrow-down-fill" style="font-size: 22px; margin-right: 10px; vertical-align: middle;"></i> Laporan
        </a>

        <a href="logout.php"
            style="display: flex; align-items: center; padding: 10px 15px; text-decoration: none; color: white; font-size: 16px; margin: 5px;">
            🚪 Logout
        </a>
    </div>

    <div class="main">

        <div class="card">
            <h2>📄 Buat Laporan Umum (Semua Karyawan)</h2>

            <form action="generate_pdf.php" method="POST" target="_blank">
                <p>Pilih rentang tanggal untuk membuat laporan absensi gabungan semua karyawan (seperti laporan bulanan).</p>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="tanggal_awal_umum">Tanggal Awal:</label>
                            <input type="date" id="tanggal_awal_umum" name="tanggal_awal" value="<?php echo htmlspecialchars($default_awal); ?>" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="tanggal_akhir_umum">Tanggal Akhir:</label>
                            <input type="date" id="tanggal_akhir_umum" name="tanggal_akhir" value="<?php echo htmlspecialchars($today); ?>" max="<?php echo htmlspecialchars($today); ?>" required>
                        </div>
                    </div>
                </div>

                <button type="submit">
                    <i class="bi bi-file-earmark-spreadsheet-fill"></i> Cetak Laporan Umum PDF
                </button>
            </form>
        </div>

        <div class="separator"></div>

        <div class="card">
            <h2>👤 Buat Laporan Detail Perorangan</h2>

            <form action="generate_pdf_per_user.php" method="POST" target="_blank">
                <p>Pilih karyawan dan rentang tanggal untuk mencetak laporan detail absensi perorangan.</p>

                <div class="form-group">
                    <label for="uid_karyawan">Pilih Karyawan:</label>
                    <select id="uid_karyawan" name="uid_karyawan" required>
                        <option value="">-- Pilih Karyawan --</option>
                        <?php 
                        if ($result_karyawan->num_rows > 0) {
                            $result_karyawan->data_seek(0); 
                            while($row = $result_karyawan->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($row['uid']) . '">' . htmlspecialchars($row['nama']) . ' (' . htmlspecialchars($row['jabatan']) . ')</option>';
                            }
                        } else {
                            echo '<option value="" disabled>Tidak ada karyawan terdaftar.</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="tanggal_awal_personal">Tanggal Awal:</label>
                            <input type="date" id="tanggal_awal_personal" name="tanggal_awal" value="<?php echo htmlspecialchars($default_awal); ?>" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="tanggal_akhir_personal">Tanggal Akhir:</label>
                            <input type="date" id="tanggal_akhir_personal" name="tanggal_akhir" value="<?php echo htmlspecialchars($today); ?>" max="<?php echo htmlspecialchars($today); ?>" required>
                        </div>
                    </div>
                </div>

                <button type="submit">
                    <i class="bi bi-file-earmark-person-fill"></i> Cetak Laporan Perorangan PDF
                </button>
            </form>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
