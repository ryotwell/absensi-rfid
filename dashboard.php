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
if (!isset($_SESSION['user'])) {
    header("location: login.php");
    exit;
}
// --- Akhir Pemeriksaan Login ---

date_default_timezone_set('Asia/Makassar');

$today = date("Y-m-d");

// =================================================================
// 1. Ambil Total Karyawan & Hitung Status Kehadiran Detail
// =================================================================
$sql_total_karyawan = "SELECT COUNT(id) AS total FROM karyawan";
$result_total_karyawan = $conn->query($sql_total_karyawan);
$total_karyawan = $result_total_karyawan->fetch_assoc()['total'] ?? 0;

// Hitung Karyawan yang HADIR KEDUA SESI
$sql_hadir_keduanya = "
    SELECT COUNT(DISTINCT k.uid) AS jumlah
    FROM karyawan k
    INNER JOIN absen_pagi ap ON k.uid = ap.uid AND ap.tanggal_absen = '$today'
    INNER JOIN absen_siang asg ON k.uid = asg.uid AND asg.tanggal_absen = '$today'
";
$result_hadir_keduanya = $conn->query($sql_hadir_keduanya);
$hadir_keduanya = $result_hadir_keduanya->fetch_assoc()['jumlah'] ?? 0;

// Hitung Karyawan yang HADIR HANYA PAGI & HANYA SIANG (untuk statistik card)
$sql_hanya_pagi = "
    SELECT COUNT(DISTINCT k.uid) AS jumlah
    FROM karyawan k
    INNER JOIN absen_pagi ap ON k.uid = ap.uid AND ap.tanggal_absen = '$today'
    LEFT JOIN absen_siang asg ON k.uid = asg.uid AND asg.tanggal_absen = '$today'
    WHERE asg.uid IS NULL
";
$result_hanya_pagi = $conn->query($sql_hanya_pagi);
$hanya_pagi = $result_hanya_pagi->fetch_assoc()['jumlah'] ?? 0;

$sql_hanya_siang = "
    SELECT COUNT(DISTINCT k.uid) AS jumlah
    FROM karyawan k
    INNER JOIN absen_siang asg ON k.uid = asg.uid AND asg.tanggal_absen = '$today'
    LEFT JOIN absen_pagi ap ON k.uid = ap.uid AND ap.tanggal_absen = '$today'
    WHERE ap.uid IS NULL
";
$result_hanya_siang = $conn->query($sql_hanya_siang);
$hanya_siang = $result_hanya_siang->fetch_assoc()['jumlah'] ?? 0;

// Hitung TIDAK HADIR SAMA SEKALI
$tidak_hadir_sama_sekali = $total_karyawan - ($hadir_keduanya + $hanya_pagi + $hanya_siang);
if ($tidak_hadir_sama_sekali < 0) {
    $tidak_hadir_sama_sekali = 0;
}

// Total Hadir per Sesi (untuk Card dan Chart Line)
$absen_pagi = $hadir_keduanya + $hanya_pagi;
$absen_siang = $hadir_keduanya + $hanya_siang;
$belum_absen_pagi = $total_karyawan - $absen_pagi;
$belum_absen_siang = $total_karyawan - $absen_siang;

// Data untuk Chart
$progress_data = [
    'present' => $hadir_keduanya,
    'missing' => $total_karyawan - $hadir_keduanya
];
$persentase_penuh = $total_karyawan > 0 ? round(($hadir_keduanya / $total_karyawan) * 100) : 0;
$line_chart_data = [
    'pagi' => $absen_pagi,
    'siang' => $absen_siang
];

// =================================================================
// 2. Kueri Data Tabel Pagi
// =================================================================
$sql_sudah_absen_pagi = "
    SELECT 
        k.nama, 
        k.uid, 
        ap.jam_absen AS waktu_absen 
    FROM karyawan k
    INNER JOIN absen_pagi ap ON k.uid = ap.uid AND ap.tanggal_absen = '$today'
    ORDER BY ap.jam_absen ASC
";
$result_sudah_absen_pagi = $conn->query($sql_sudah_absen_pagi);

$sql_belum_absen_pagi_detail = "
    SELECT 
        k.nama, 
        k.uid
    FROM karyawan k
    LEFT JOIN absen_pagi ap ON k.uid = ap.uid AND ap.tanggal_absen = '$today'
    WHERE ap.uid IS NULL
    ORDER BY k.nama ASC
";
$result_belum_absen_pagi_detail = $conn->query($sql_belum_absen_pagi_detail);

// =================================================================
// 3. Kueri Data Tabel Siang
// =================================================================
$sql_sudah_absen_siang = "
    SELECT 
        k.nama, 
        k.uid, 
        asg.jam_absen AS waktu_absen 
    FROM karyawan k
    INNER JOIN absen_siang asg ON k.uid = asg.uid AND asg.tanggal_absen = '$today'
    ORDER BY asg.jam_absen ASC
";
$result_sudah_absen_siang = $conn->query($sql_sudah_absen_siang);

$sql_belum_absen_siang_detail = "
    SELECT 
        k.nama, 
        k.uid
    FROM karyawan k
    LEFT JOIN absen_siang asg ON k.uid = asg.uid AND asg.tanggal_absen = '$today'
    WHERE asg.uid IS NULL
    ORDER BY k.nama ASC
";
$result_belum_absen_siang_detail = $conn->query($sql_belum_absen_siang_detail);

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Absensi RFID</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

    <style>
        :root {
            --color-sidebar-dark: #20354b;
            --color-main-bg: #f5f7fa;
            --color-accent-blue: #007bff;
            --color-text-dark: #343a40;
            --color-card-bg: #ffffff;
            --color-success: #28a745;
            --color-danger: #dc3545;
            --color-info: #17a2b8;
        }

        /* DARK MODE OVERRIDES */
        body.darkmode, .darkmode body {
            --color-sidebar-dark: #171e29;
            --color-main-bg: #181c1f;
            --color-accent-blue: #4695fa;
            --color-text-dark: #ecf0f2;
            --color-card-bg: #232a34;
            --color-success: #50e878;
            --color-danger: #ff647d;
            --color-info: #51c6ff;
            background-color: var(--color-main-bg) !important;
            color: var(--color-text-dark) !important;
        }

        .darkmode .sidebar,
        body.darkmode .sidebar {
            background-color: var(--color-sidebar-dark) !important;
            box-shadow: 2px 0 12px rgba(0,0,0,0.25);
        }
        .darkmode .sidebar h2,
        .darkmode .sidebar a,
        body.darkmode .sidebar h2,
        body.darkmode .sidebar a {
            color: #ecf0f2 !important;
        }
        .darkmode .sidebar a.active,
        body.darkmode .sidebar a.active {
            background-color: #254378 !important;
        }
        .darkmode .main,
        body.darkmode .main {
            background-color: var(--color-main-bg) !important;
            color: var(--color-text-dark) !important;
        }
        .darkmode .stat-card,
        .darkmode .table-container,
        .darkmode .chart-box,
        body.darkmode .stat-card,
        body.darkmode .table-container,
        body.darkmode .chart-box {
            background: var(--color-card-bg) !important;
            color: var(--color-text-dark) !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .darkmode .stat-card h3, .darkmode .table-container h3, body.darkmode .stat-card h3, body.darkmode .table-container h3 {
            color: #b1bed1 !important;
        }
        .darkmode .stat-card p, .darkmode .gauge-label, body.darkmode .stat-card p, body.darkmode .gauge-label {
            color: var(--color-text-dark) !important;
        }
        .darkmode .styled-table thead tr,
        body.darkmode .styled-table thead tr {
            background-color: #1f2a35 !important;
            color: #fff !important;
        }
        .darkmode .styled-table tbody tr:nth-of-type(even), body.darkmode .styled-table tbody tr:nth-of-type(even) {
            background-color: #202934 !important;
        }
        .darkmode .styled-table th,
        .darkmode .styled-table td,
        body.darkmode .styled-table th,
        body.darkmode .styled-table td {
            border: 1px solid #425066 !important;
            color: var(--color-text-dark) !important;
        }
        .darkmode .styled-table tbody tr:hover,
        body.darkmode .styled-table tbody tr:hover {
            background-color: #222e3d !important;
        }
        .darkmode .date-info, body.darkmode .date-info {
            color: #9eb2c8 !important;
        }
        .darkmode .status-hadir {
            color: var(--color-success) !important;
        }
        .darkmode .status-belum {
            color: var(--color-danger) !important;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--color-card-bg);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
            border-left: 5px solid var(--color-accent-blue);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-card.total {
            border-left-color: var(--color-info);
        }

        .stat-card.hadir {
            border-left-color: var(--color-success);
        }

        .stat-card.belum {
            border-left-color: var(--color-danger);
        }

        .stat-card.ganda {
            border-left-color: var(--color-accent-blue);
        }

        .stat-card h3 {
            font-size: 14px;
            color: #6c757d;
            margin: 0 0 10px 0;
            font-weight: 600;
        }

        .stat-card p {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
            color: var(--color-text-dark);
        }

        .charts-row {
            display: flex;
            gap: 30px;
            margin-top: 30px;
            align-items: flex-start;
        }

        .chart-box {
            background: var(--color-card-bg);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .chart-box.gauge {
            width: 250px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .chart-box.line {
            flex-grow: 1;
            max-width: 600px;
        }

        .gauge-label {
            font-size: 48px;
            font-weight: 700;
            color: var(--color-accent-blue);
            margin-top: 10px;
        }

        .table-section {
            margin-top: 50px;
        }

        .table-row {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }

        .table-container {
            flex: 1;
            background: var(--color-card-bg);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }

        .table-container h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .styled-table {
            width: 100%;
            border-collapse: collapse;
        }

        .styled-table thead tr {
            background-color: var(--color-sidebar-dark);
            color: white;
            text-align: left;
        }

        .styled-table th,
        .styled-table td {
            padding: 12px 15px;
            border: 1px solid #ddd;
        }

        .styled-table tbody tr {
            border-bottom: 1px solid #dddddd;
        }

        .styled-table tbody tr:nth-of-type(even) {
            background-color: #f3f3f3;
        }

        .styled-table tbody tr:hover {
            background-color: #e2e6ea;
            cursor: default;
        }

        .status-hadir {
            color: var(--color-success);
            font-weight: 600;
        }

        .status-belum {
            color: var(--color-danger);
            font-weight: 600;
        }

        /* Dark mode toggle button */
        .dark-toggle-btn {
            position: fixed;
            top: 18px;
            right: 32px;
            z-index: 100;
            background: var(--color-card-bg);
            color: var(--color-text-dark);
            border: none;
            outline: none;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
            border-radius: 24px;
            padding: 7px 22px 7px 14px;
            cursor: pointer;
            font-size: 15px;
            font-family: inherit;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s, color 0.2s;
        }
        .darkmode .dark-toggle-btn, body.darkmode .dark-toggle-btn {
            background: #1e262f;
            color: #d6dbdf;
            box-shadow: 0 2px 12px rgba(0,0,0,0.2);
        }
    </style>
</head>

<body>
    <button class="dark-toggle-btn" id="toggleDark" type="button" aria-label="Toggle dark mode">
        <i class="bi bi-moon"></i>
        <span id="toggle-label">Dark Mode</span>
    </button>

    <div class="sidebar" style="width: 230px; background: #2c3e50; min-height: 100vh; padding: 15px 0;">
        <div style="display: flex; justify-content: center; align-items: center; margin-top: 5px;">
            <img src="./asset/pemda.png" alt="Pemda Lotim" width="35px" style="padding: 0 5px;">
            <img src="./asset/kkn-logo.jpg" alt="Pemda Lotim" width="35px" style="padding: 0 5px;">
        </div>

        <h2 style="text-align: center; color: white; margin: 5px 0;">ABSENSI RFID DESA JURIT</h2>

        <a href="dashboard.php" class="active"
            style="display: flex; align-items: center; padding: 10px 15px; text-decoration: none; color: white; font-size: 16px; background-color: #007bff; border-radius: 5px; margin: 5px;">
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
        <h2>📊 Dashboard Absensi Harian</h2>
        <p class="date-info">Data untuk tanggal: <b><?php echo date('d F Y', strtotime($today)); ?></b></p>

        <div class="stats-grid">
            <div class="stat-card total">
                <h3>Total Karyawan</h3>
                <p><?php echo $total_karyawan; ?></p>
            </div>

            <div class="stat-card hadir">
                <h3>Absen Pagi</h3>
                <p><?php echo $absen_pagi; ?></p>
            </div>
            <div class="stat-card belum">
                <h3>Belum Absen Pagi</h3>
                <p><?php echo $belum_absen_pagi; ?></p>
            </div>

            <div class="stat-card hadir">
                <h3>Absen Siang</h3>
                <p><?php echo $absen_siang; ?></p>
            </div>
            <div class="stat-card belum">
                <h3>Belum Absen Siang</h3>
                <p><?php echo $belum_absen_siang; ?></p>
            </div>

            <div class="stat-card ganda">
                <h3>Hadir Kedua Sesi</h3>
                <p><?php echo $hadir_keduanya; ?></p>
            </div>
            <div class="stat-card belum">
                <h3>Tidak Hadir Sama Sekali</h3>
                <p><?php echo $tidak_hadir_sama_sekali; ?></p>
            </div>
        </div>

        <div class="charts-row">
            <div class="chart-box gauge">
                <h3>Target Kehadiran Penuh</h3>
                <div style="height: 250px; width: 100px; margin-top: 15px;">
                    <canvas id="gaugeChart"></canvas>
                </div>
                <div class="gauge-label"><?php echo $persentase_penuh; ?>%</div>
                <p style="font-size: 12px; margin-top: 5px; color: #6c757d;">(<?php echo $hadir_keduanya; ?> dari <?php echo $total_karyawan; ?>)</p>
            </div>

            <div class="chart-box line">
                <h3>Perbandingan Kehadiran Sesi</h3>
                <canvas id="lineChart"></canvas>
            </div>
        </div>

        <div class="table-section">
            <h2 style="margin-top: 40px;">⏰ Detail Status Absensi Pagi</h2>
            <div class="table-row">

                <div class="table-container">
                    <h3 style="color: var(--color-success);">✅ Sudah Absen Pagi (Total: <?php echo $result_sudah_absen_pagi->num_rows; ?>)</h3>
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama Karyawan</th>
                                <th>Waktu Absen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if ($result_sudah_absen_pagi->num_rows > 0) {
                                while ($row = $result_sudah_absen_pagi->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $no++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                                    echo "<td><span class='status-hadir'>" . htmlspecialchars($row['waktu_absen']) . "</span></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' style='text-align: center;'>Belum ada karyawan yang absen pagi hari ini.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-container">
                    <h3 style="color: var(--color-danger);">❌ Belum Absen Pagi (Total: <?php echo $result_belum_absen_pagi_detail->num_rows; ?>)</h3>
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama Karyawan</th>
                                <th>UID Kartu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if ($result_belum_absen_pagi_detail->num_rows > 0) {
                                while ($row = $result_belum_absen_pagi_detail->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $no++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                                    echo "<td><span class='status-belum'>" . htmlspecialchars($row['uid']) . "</span></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' style='text-align: center;'>Semua karyawan sudah absen pagi!</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <div class="table-section">
            <h2 style="margin-top: 40px;">☀️ Detail Status Absensi Siang</h2>
            <div class="table-row">

                <div class="table-container">
                    <h3 style="color: var(--color-success);">✅ Sudah Absen Siang (Total: <?php echo $result_sudah_absen_siang->num_rows; ?>)</h3>
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama Karyawan</th>
                                <th>Waktu Absen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if ($result_sudah_absen_siang->num_rows > 0) {
                                while ($row = $result_sudah_absen_siang->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $no++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                                    echo "<td><span class='status-hadir'>" . htmlspecialchars($row['waktu_absen']) . "</span></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' style='text-align: center;'>Belum ada karyawan yang absen siang hari ini.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-container">
                    <h3 style="color: var(--color-danger);">❌ Belum Absen Siang (Total: <?php echo $result_belum_absen_siang_detail->num_rows; ?>)</h3>
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama Karyawan</th>
                                <th>UID Kartu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if ($result_belum_absen_siang_detail->num_rows > 0) {
                                while ($row = $result_belum_absen_siang_detail->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $no++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                                    echo "<td><span class='status-belum'>" . htmlspecialchars($row['uid']) . "</span></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' style='text-align: center;'>Semua karyawan sudah absen siang! Kinerja sempurna.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>

    <script>
        // ===================================================
        // DARK MODE TOGGLE LOGIC
        // ===================================================
        (function () {
            const preferDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
            let isDark = false;
            // Check if saved in localStorage
            if (localStorage.getItem('darkmode') === '1') {
                document.body.classList.add('darkmode');
                isDark = true;
            } else if (localStorage.getItem('darkmode') === '0') {
                document.body.classList.remove('darkmode');
                isDark = false;
            } else if (preferDark) {
                document.body.classList.add('darkmode');
                isDark = true;
            }

            function darkUpdateBtn(dark) {
                let btn = document.getElementById("toggleDark");
                let label = document.getElementById("toggle-label");
                if (dark) {
                    btn.querySelector('.bi').className = "bi bi-sun";
                    label.innerText = 'Light Mode';
                } else {
                    btn.querySelector('.bi').className = "bi bi-moon";
                    label.innerText = 'Dark Mode';
                }
            }
            darkUpdateBtn(isDark);

            document.getElementById('toggleDark').addEventListener('click', function () {
                isDark = !document.body.classList.contains('darkmode');
                document.body.classList.toggle('darkmode');
                localStorage.setItem('darkmode', isDark ? '1' : '0');
                darkUpdateBtn(isDark);
            });

            // If page changes class by other means, update button accordingly
            const observer = new MutationObserver(function () {
                darkUpdateBtn(document.body.classList.contains('darkmode'));
            });
            observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
        })();

        // ===================================================
        // 1. GAUGE CHART (Dibuat dengan Stacked Bar Vertikal)
        // ===================================================
        const progressData = {
            labels: ['Kehadiran Penuh'],
            datasets: [{
                    label: 'Tercapai',
                    data: [<?php echo $progress_data['present']; ?>],
                    backgroundColor: 'rgba(0, 123, 255, 1)',
                    stack: 'Stack 0',
                },
                {
                    label: 'Target Sisa',
                    data: [<?php echo $progress_data['missing']; ?>],
                    backgroundColor: 'rgba(233, 236, 239, 1)',
                    stack: 'Stack 0',
                }
            ]
        };

        const progressConfig = {
            type: 'bar',
            data: progressData,
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    title: {
                        display: false,
                    }
                },
                scales: {
                    x: {
                        display: false,
                        stacked: true,
                        max: <?php echo $total_karyawan; ?>,
                    },
                    y: {
                        display: false,
                        stacked: true,
                    }
                }
            },
        };

        new Chart(
            document.getElementById('gaugeChart'),
            progressConfig
        );

        // ===================================================
        // 2. LINE CHART (Tren Kehadiran Sesi)
        // ===================================================
        const lineData = {
            labels: ['Absensi Pagi', 'Absensi Siang'],
            datasets: [{
                label: 'Jumlah Hadir Hari Ini',
                data: [<?php echo $line_chart_data['pagi']; ?>, <?php echo $line_chart_data['siang']; ?>],
                fill: false,
                borderColor: 'rgb(25, 135, 84)',
                tension: 0.1,
                pointBackgroundColor: ['rgba(40, 167, 69, 1)', 'rgba(0, 123, 255, 1)'],
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        };

        const lineConfig = {
            type: 'line',
            data: lineData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: <?php echo $total_karyawan + 1; ?>,
                        title: {
                            display: true,
                            text: 'Jumlah Karyawan'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    title: {
                        display: false
                    }
                }
            }
        };

        new Chart(
            document.getElementById('lineChart'),
            lineConfig
        );
    </script>
</body>

</html>