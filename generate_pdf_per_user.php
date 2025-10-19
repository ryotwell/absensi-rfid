<?php
// generate_pdf_per_user.php

// Pastikan koneksi database tersedia
include 'db_connect.php';
date_default_timezone_set('Asia/Makassar');

// =================================================================
// 1. Inisialisasi Dompdf & Kebutuhan Dasar
// =================================================================

// UBAH PATH INI SESUAI LOKASI DOMPDF ANDA
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Set konfigurasi Dompdf
$options = new Options();
$options->set('defaultFont', 'sans-serif');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Fungsi bantuan untuk menerjemahkan nama hari dan bulan
function translate_date_full($date_string)
{
    if (empty($date_string)) return '';
    $dayNames = ['Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu', 'Sun' => 'Minggu'];
    $monthNames = ['Jan' => 'Januari', 'Feb' => 'Februari', 'Mar' => 'Maret', 'Apr' => 'April', 'May' => 'Mei', 'Jun' => 'Juni', 'Jul' => 'Juli', 'Aug' => 'Agustus', 'Sep' => 'September', 'Oct' => 'Oktober', 'Nov' => 'November', 'Dec' => 'Desember'];

    $timestamp = strtotime($date_string);
    $day = $dayNames[date('D', $timestamp)];
    $month = str_replace(array_keys($monthNames), array_values($monthNames), date('M', $timestamp));

    return $day . ', ' . date('d', $timestamp) . ' ' . $month . ' ' . date('Y', $timestamp);
}

// =================================================================
// 2. Ambil Input & Validasi (Termasuk UID Karyawan)
// =================================================================
$tanggal_awal = $_POST['tanggal_awal'] ?? null;
$tanggal_akhir = $_POST['tanggal_akhir'] ?? null;
$uid_karyawan = $_POST['uid_karyawan'] ?? null; // INPUT BARU

if (empty($tanggal_awal) || empty($tanggal_akhir) || empty($uid_karyawan)) {
    die("Data input tidak valid. Silakan kembali dan pilih karyawan serta rentang tanggal.");
}

// Konversi format tanggal untuk tampilan
$periode_awal_format = date('d F Y', strtotime($tanggal_awal));
$periode_akhir_format = date('d F Y', strtotime($tanggal_akhir));

// Ambil Nama Karyawan untuk Judul Laporan
$nama_karyawan = '';
$sql_get_nama = "SELECT nama FROM karyawan WHERE uid = ?";
if ($stmt_nama = $conn->prepare($sql_get_nama)) {
    $stmt_nama->bind_param("s", $uid_karyawan);
    $stmt_nama->execute();
    $result_nama = $stmt_nama->get_result();
    if ($row_nama = $result_nama->fetch_assoc()) {
        $nama_karyawan = $row_nama['nama'];
    }
    $stmt_nama->close();
}


// =================================================================
// 3. Kueri Data Absensi DETAIL (Filter Berdasarkan UID)
// =================================================================

// Parameter untuk Prepared Statement: 1 UID dan 4 Tanggal
$params = [$uid_karyawan, $tanggal_awal, $tanggal_akhir, $uid_karyawan, $tanggal_awal, $tanggal_akhir];
$types = "ssssss"; // 6 string

$sql_detail = "
    SELECT 
        k.nama,
        k.jabatan,
        t.tanggal AS tanggal,
        TIME(ap.jam_absen) AS waktu_masuk,  
        TIME(asg.jam_absen) AS waktu_keluar 
    FROM (
        -- Dapatkan semua kombinasi unik UID dan tanggal absensi dari absen_pagi
        SELECT uid, tanggal_absen AS tanggal FROM absen_pagi WHERE uid = ? AND tanggal_absen BETWEEN ? AND ?
        UNION 
        -- Gabungkan dengan absensi dari absen_siang
        SELECT uid, tanggal_absen AS tanggal FROM absen_siang WHERE uid = ? AND tanggal_absen BETWEEN ? AND ?
    ) t
    -- Gabungkan dengan tabel karyawan untuk nama/jabatan
    INNER JOIN karyawan k ON t.uid = k.uid
    -- Gabungkan dengan absen_pagi (LEFT JOIN agar tidak wajib ada)
    LEFT JOIN absen_pagi ap 
        ON t.uid = ap.uid AND t.tanggal = ap.tanggal_absen
    -- Gabungkan dengan absen_siang (LEFT JOIN agar tidak wajib ada)
    LEFT JOIN absen_siang asg 
        ON t.uid = asg.uid AND t.tanggal = asg.tanggal_absen
    ORDER BY t.tanggal ASC, k.nama ASC
";

$data_detail = [];
if ($stmt = $conn->prepare($sql_detail)) {
    // Bind 6 parameter (UID, Tanggal Awal, Tanggal Akhir, UID, Tanggal Awal, Tanggal Akhir)
    $stmt->bind_param($types, ...$params); 
    $stmt->execute();
    $result = $stmt->get_result();

    // Kelompokkan data berdasarkan tanggal
    while ($row = $result->fetch_assoc()) {
        $tanggal = $row['tanggal'];
        if (!isset($data_detail[$tanggal])) {
            $data_detail[$tanggal] = [];
        }
        $data_detail[$tanggal][] = $row;
    }
    $stmt->close();
}


// =================================================================
// 4. Buat Konten HTML untuk PDF (Mengubah Judul)
// =================================================================

$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; margin: 0; padding: 0; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; color: #1E293B; }
        .header p { margin: 5px 0; font-size: 12px; }
        .periode { margin-bottom: 25px; font-size: 13px; text-align: center; font-weight: bold; color: #444; }
        .info { margin: 10px auto; width: 80%; font-size: 12px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; text-align: center; font-size: 11px;}
        td { font-size: 10px; }
        .tanggal-header { background-color: #e9ecef; font-size: 12px; font-weight: bold; padding: 8px; text-align: left; margin-top: 15px; margin-bottom: 5px; border-left: 5px solid #007bff; }
        .text-center { text-align: center; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN ABSENSI DETAIL PERORANGAN</h1>
        <p>Sistem Absensi RFID</p>
    </div>
    
    <div class="info">
        Nama Karyawan: <strong>' . htmlspecialchars($nama_karyawan) . '</strong><br>
        Periode: <strong>' . $periode_awal_format . ' s/d ' . $periode_akhir_format . '</strong>
    </div>
    <div style="height: 10px;"></div>
'; // Margin pemisah

if (count($data_detail) > 0) {
    foreach ($data_detail as $tanggal => $absen_per_tanggal) {
        if (empty($absen_per_tanggal)) continue;

        $html .= '<div class="tanggal-header">' . translate_date_full($tanggal) . '</div>';

        // NOTE: Karena ini laporan perorangan, kolom Nama dan Jabatan dihilangkan/digabung agar lebih ringkas
        $html .= '
        <table>
            <thead>
                <tr>
                    <th width="10%">No</th>
                    <th width="35%">UID Kartu</th>
                    <th width="25%">Absen Pagi</th> 
                    <th width="25%">Absen Siang</th> 
                </tr>
            </thead>
            <tbody>';

        $no = 1;
        foreach ($absen_per_tanggal as $row) {
            // Logika strip: menggunakan operator null coalescing (??)
            $waktu_masuk = $row['waktu_masuk'] ?? '-';
            $waktu_keluar = $row['waktu_keluar'] ?? '-';

            // Nama dan Jabatan tidak ditampilkan karena sudah ada di header info
            $html .= '
                <tr>
                    <td class="text-center">' . $no++ . '</td>
                    <td class="text-center">' . htmlspecialchars($uid_karyawan) . '</td>
                    <td class="text-center">' . $waktu_masuk . '</td>
                    <td class="text-center">' . $waktu_keluar . '</td>
                </tr>';
        }

        $html .= '
            </tbody>
        </table>';
    }
} else {
    $html .= '<p style="text-align: center; margin-top: 30px; font-size: 14px; color: #dc3545;">❌ Tidak ada data absensi yang ditemukan untuk karyawan ini dalam rentang tanggal yang dipilih.</p>';
}

$html .= '
    <div class="footer">
        Dicetak pada: ' . date('d/m/Y H:i:s') . ' | Dibuat oleh Sistem Absensi RFID Desa Jurit <br>
        KELOMPOK 14 KKN DESA JURIT FAKULTAS TEKNIK UNIVERSITAS HAMZANWADI
    </div>
</body>
</html>';

// =================================================================
// 5. Render dan Output PDF
// =================================================================
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "Laporan_Absensi_" . htmlspecialchars($nama_karyawan) . "_" . str_replace('-', '', $tanggal_awal) . "_" . str_replace('-', '', $tanggal_akhir) . ".pdf";

$dompdf->stream($filename, array("Attachment" => false));

// TUTUP KONEKSI SETELAH SEMUA SELESAI
$conn->close();
exit(0);