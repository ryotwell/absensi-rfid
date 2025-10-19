<?php
// generate_pdf.php

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

// Fungsi bantuan untuk menerjemahkan nama hari dan bulan (jika diperlukan)
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
// 2. Ambil Input & Validasi Tanggal
// =================================================================
$tanggal_awal = $_POST['tanggal_awal'] ?? null;
$tanggal_akhir = $_POST['tanggal_akhir'] ?? null;

if (empty($tanggal_awal) || empty($tanggal_akhir)) {
    die("Rentang tanggal tidak valid. Silakan kembali ke halaman Laporan.");
}

// Konversi format tanggal untuk tampilan
$periode_awal_format = date('d F Y', strtotime($tanggal_awal));
$periode_akhir_format = date('d F Y', strtotime($tanggal_akhir));

// =================================================================
// 3. Kueri Data Absensi Detail (Memastikan Pagi/Siang diisi dengan strip jika kosong)
// =================================================================

// Kueri Diperbaiki: Menggunakan UNION untuk mendapatkan semua pasangan (uid, tanggal) unik yang absen.
$sql_detail = "
    SELECT 
        k.nama,
        k.jabatan,
        t.tanggal AS tanggal,
        TIME(ap.jam_absen) AS waktu_masuk,  
        TIME(asg.jam_absen) AS waktu_keluar 
    FROM (
        -- Dapatkan semua kombinasi unik UID dan tanggal absensi (pagi atau siang)
        SELECT uid, tanggal_absen AS tanggal FROM absen_pagi WHERE tanggal_absen BETWEEN ? AND ?
        UNION 
        SELECT uid, tanggal_absen AS tanggal FROM absen_siang WHERE tanggal_absen BETWEEN ? AND ?
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
    // Bind 4 parameter untuk 4 tanda tanya pada kueri UNION
    $stmt->bind_param("ssss", $tanggal_awal, $tanggal_akhir, $tanggal_awal, $tanggal_akhir);
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
// 4. Buat Konten HTML untuk PDF (Perbaikan Label dan Strip)
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
        <h1>LAPORAN ABSENSI DETAIL KARYAWAN</h1>
        <p>Sistem Absensi RFID</p>
    </div>

    <div class="periode">
        Periode Laporan: ' . $periode_awal_format . ' s/d ' . $periode_akhir_format . '
    </div>';

if (count($data_detail) > 0) {
    foreach ($data_detail as $tanggal => $absen_per_tanggal) {
        if (empty($absen_per_tanggal)) continue;

        $html .= '<div class="tanggal-header">' . translate_date_full($tanggal) . '</div>';

        $html .= '
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="35%">Nama Karyawan</th>
                    <th width="20%">Jabatan</th>
                    <th width="20%">Absen Pagi</th> <th width="20%">Absen Siang</th> </tr>
            </thead>
            <tbody>';

        $no = 1;
        foreach ($absen_per_tanggal as $row) {
            // Logika strip: menggunakan operator null coalescing (??) untuk menangani nilai NULL dari LEFT JOIN
            $waktu_masuk = $row['waktu_masuk'] ?? '-';
            $waktu_keluar = $row['waktu_keluar'] ?? '-';

            // Tidak perlu lagi filter karena kueri UNION sudah memastikan adanya absensi
            // if ($waktu_masuk == '-' && $waktu_keluar == '-') { continue; }

            $html .= '
                <tr>
                    <td class="text-center">' . $no++ . '</td>
                    <td>' . htmlspecialchars($row['nama']) . '</td>
                    <td>' . htmlspecialchars($row['jabatan']) . '</td>
                    <td class="text-center">' . $waktu_masuk . '</td>
                    <td class="text-center">' . $waktu_keluar . '</td>
                </tr>';
        }

        $html .= '
            </tbody>
        </table>';
    }
} else {
    $html .= '<p style="text-align: center; margin-top: 30px; font-size: 14px; color: #dc3545;">❌ Tidak ada data absensi yang ditemukan dalam rentang tanggal yang dipilih.</p>';
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

$filename = "Laporan_Absensi_" . str_replace('-', '', $tanggal_awal) . "_" . str_replace('-', '', $tanggal_akhir) . ".pdf";

// "Attachment" => false memaksa browser untuk menampilkan (inline)
$dompdf->stream($filename, array("Attachment" => false));
exit(0);

// TUTUP KONEKSI DISINI
$conn->close();
