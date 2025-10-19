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

// --- Proses Pencarian ---
$search_nama = isset($_GET['search_nama']) ? $conn->real_escape_string($_GET['search_nama']) : '';
$search_jabatan = isset($_GET['search_jabatan']) ? $conn->real_escape_string($_GET['search_jabatan']) : '';
$search_tanggal = isset($_GET['search_tanggal']) ? $conn->real_escape_string($_GET['search_tanggal']) : '';

$where_clause = "";
$conditions = [];

if (!empty($search_nama)) {
    $conditions[] = "nama LIKE '%$search_nama%'";
}
if (!empty($search_jabatan)) {
    $conditions[] = "jabatan LIKE '%$search_jabatan%'";
}
if (!empty($search_tanggal)) {
    $conditions[] = "tanggal_absen = '$search_tanggal'";
}
if (count($conditions) > 0) {
    $where_clause = "WHERE " . implode(" AND ", $conditions);
}
// --- Akhir Proses Pencarian ---

// Fungsi bantuan untuk terjemahan tanggal
function translate_date($date_string)
{
    if (empty($date_string) || $date_string === '0000-00-00') {
        return '';
    }

    $dayNames = ['Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu', 'Sun' => 'Minggu'];
    $monthNames = ['Jan' => 'Januari', 'Feb' => 'Februari', 'Mar' => 'Maret', 'Apr' => 'April', 'May' => 'Mei', 'Jun' => 'Juni', 'Jul' => 'Juli', 'Aug' => 'Agustus', 'Sep' => 'September', 'Oct' => 'Oktober', 'Nov' => 'November', 'Dec' => 'Desember'];

    $timestamp = strtotime($date_string);
    $day = date('D', $timestamp);
    $month = date('M', $timestamp);
    $formatted_date = str_replace(array_keys($dayNames), array_values($dayNames), $day) . ', ' .
        date('d', $timestamp) . ' ' .
        str_replace(array_keys($monthNames), array_values($monthNames), $month) . ' ' .
        date('Y', $timestamp);

    return $formatted_date;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Absensi RFID</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        html { scroll-behavior: smooth; }
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
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        .header-section:first-child {
            border-top: none;
            padding-top: 0;
        }
        .header-section h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .session-buttons a {
            text-decoration: none;
            padding: 8px 15px;
            background-color: #6c757d;
            color: white;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        .session-buttons a:hover {
            background-color: #5a6268;
        }
        .main > h2:first-of-type {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 25px;
            border-left: 4px solid var(--color-accent-blue);
            padding-left: 10px;
            border-bottom: none;
        }
        .search-form {
            background: var(--color-card-bg);
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }
        .search-form label {
            font-size: 14px;
            font-weight: 600;
            color: var(--color-text-dark);
            display: block;
            margin-bottom: 5px;
        }
        .search-form input[type="text"],
        .search-form input[type="date"] {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            width: 180px;
            transition: border-color 0.3s;
        }
        .search-form button {
            padding: 10px 15px;
            background: var(--color-accent-blue);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .search-form button:hover { background: #0056b3; }
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--color-card-bg);
            border-radius: 6px;
            overflow: hidden;
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }
        th {
            background: var(--color-table-header);
            color: var(--color-text-dark);
            padding: 12px 15px;
            font-size: 14px;
            font-weight: 600;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        td {
            padding: 10px 15px;
            border-bottom: 1px solid #e9ecef;
            text-align: left;
            color: var(--color-text-dark);
            font-size: 14px;
        }
        tr th:first-child, tr td:first-child,
        tr th:last-child, tr td:last-child { text-align: center; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        tr:hover { background-color: #e2f2ff; }
        .bottom-link {
            margin-top: 30px;
            display: inline-block;
            background: var(--color-accent-blue);
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: 0.3s;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0, 123, 255, 0.3);
        }
        .bottom-link:hover {
            background: #0056b3;
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.4);
        }
    </style>
</head>
<body>
    <div class="sidebar" style="width:230px;background:#2c3e50;min-height:100vh;padding:15px 0;">
        <div style="display:flex;justify-content:center;align-items:center;margin-top:5px;">
            <img src="./asset/pemda.png" width="35px" style="padding:0 5px;">
            <img src="./asset/pemda.png" width="35px" style="padding:0 5px;">
        </div>
        <h2 style="text-align:center;color:white;margin:5px 0;">ABSENSI RFID DESA JURIT</h2>
        <a href="dashboard.php" style="display:flex;align-items:center;padding:10px 15px;text-decoration:none;color:white;font-size:16px;margin:5px;">
            <i class="bi bi-house-gear-fill" style="font-size:22px;margin-right:10px;"></i> Dashboard
        </a>
        <a href="index.php" class="active" style="display:flex;align-items:center;padding:10px 15px;text-decoration:none;color:white;font-size:16px;background-color:#007bff;border-radius:5px;margin:5px;">
            <i class="bi bi-clock-fill" style="font-size:22px;margin-right:10px;"></i> Data Absensi
        </a>
        <a href="daftar.php" style="display:flex;align-items:center;padding:10px 15px;text-decoration:none;color:white;font-size:16px;margin:5px;">
            <i class="bi bi-person-vcard-fill" style="font-size:22px;margin-right:10px;"></i> Daftar Kartu
        </a>
        <a href="laporan.php" style="display:flex;align-items:center;padding:10px 15px;text-decoration:none;color:white;font-size:16px;margin:5px;">
            <i class="bi bi-file-earmark-arrow-down-fill" style="font-size:22px;margin-right:10px;"></i> Laporan
        </a>
        <a href="logout.php" style="display:flex;align-items:center;padding:10px 15px;text-decoration:none;color:white;font-size:16px;margin:5px;">🚪 Logout</a>
    </div>

    <div class="main">
        <h2>Pencarian Data Absensi</h2>

        <form method="GET" class="search-form">
            <div class="input-group">
                <label for="search_nama">Cari Nama</label>
                <input type="text" id="search_nama" name="search_nama" placeholder="Nama..." value="<?php echo htmlspecialchars($search_nama); ?>">
            </div>
            <div class="input-group">
                <label for="search_jabatan">Cari Jabatan</label>
                <input type="text" id="search_jabatan" name="search_jabatan" placeholder="Jabatan..." value="<?php echo htmlspecialchars($search_jabatan); ?>">
            </div>
            <div class="input-group">
                <label for="search_tanggal">Cari Tanggal</label>
                <input type="date" id="search_tanggal" name="search_tanggal" value="<?php echo htmlspecialchars($search_tanggal); ?>">
            </div>
            <button type="submit">🔍 Cari Data</button>
            <div class="input-group">
                <label style="color:transparent;">Reset</label>
                <a href="index.php" style="display:inline-block;padding:8px 12px;background:#6c757d;color:white;text-decoration:none;border-radius:4px;font-size:14px;font-weight:600;">🔄 Reset</a>
            </div>
        </form>

        <div id="pagi" class="header-section">
            <h2>Absensi RFID - Sesi Pagi</h2>
            <div class="session-buttons">
                <a href="#siang" title="Pergi ke tabel Absensi Siang">Absen Siang <i class="bi bi-arrow-down-circle-fill"></i></a>
            </div>
        </div>

        <table>
            <tr>
                <th>No</th>
                <th>UID Kartu</th>
                <th>Nama</th>
                <th>Jabatan</th>
                <th>Tanggal Absen</th>
                <th>Waktu Absen</th>
            </tr>
            <?php
            $sql_pagi = "SELECT * FROM absen_pagi $where_clause ORDER BY tanggal_absen DESC, jam_absen DESC";
            $result_pagi = $conn->query($sql_pagi);
            $no = 1;
            if ($result_pagi->num_rows > 0) {
                while ($row = $result_pagi->fetch_assoc()) {
                    $tanggal_formatted = translate_date($row['tanggal_absen']);
                    echo "<tr>
                            <td>{$no}</td>
                            <td>{$row['uid']}</td>
                            <td>{$row['nama']}</td>
                            <td>{$row['jabatan']}</td>
                            <td>{$tanggal_formatted}</td>
                            <td>{$row['jam_absen']}</td>
                          </tr>";
                    $no++;
                }
            } else {
                echo "<tr><td colspan='6' style='text-align:center;'>Tidak ada data absen pagi yang cocok dengan kriteria pencarian.</td></tr>";
            }
            ?>
        </table>

        <div id="siang" class="header-section">
            <h2>Absensi RFID - Sesi Siang</h2>
            <div class="session-buttons">
                <a href="#pagi" title="Pergi ke tabel Absensi Pagi"><i class="bi bi-arrow-up-circle-fill"></i> Absen Pagi</a>
            </div>
        </div>

        <table>
            <tr>
                <th>No</th>
                <th>UID Kartu</th>
                <th>Nama</th>
                <th>Jabatan</th>
                <th>Tanggal Absen</th>
                <th>Waktu Absen</th>
            </tr>
            <?php
            $sql_siang = "SELECT * FROM absen_siang $where_clause ORDER BY tanggal_absen DESC, jam_absen DESC";
            $result_siang = $conn->query($sql_siang);
            $no = 1;
            if ($result_siang->num_rows > 0) {
                while ($row = $result_siang->fetch_assoc()) {
                    $tanggal_formatted = translate_date($row['tanggal_absen']);
                    echo "<tr>
                            <td>{$no}</td>
                            <td>{$row['uid']}</td>
                            <td>{$row['nama']}</td>
                            <td>{$row['jabatan']}</td>
                            <td>{$tanggal_formatted}</td>
                            <td>{$row['jam_absen']}</td>
                          </tr>";
                    $no++;
                }
            } else {
                echo "<tr><td colspan='6' style='text-align:center;'>Tidak ada data absen siang yang cocok dengan kriteria pencarian.</td></tr>";
            }
            $conn->close();
            ?>
        </table>

        <a href="daftar.php" class="bottom-link">💳 Ke Daftar Kartu</a>
    </div>
</body>
</html>
