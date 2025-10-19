<?php
// Pastikan file 'db_connect.php' berisi detail koneksi database Anda ($conn)
include 'db_connect.php'; 
date_default_timezone_set('Asia/Makassar');

if (isset($_GET['uid'])) {
    $uid = $conn->real_escape_string($_GET['uid']);
    $sql_cek_karyawan = "SELECT * FROM karyawan WHERE uid='$uid'";
    $result_karyawan = $conn->query($sql_cek_karyawan);

    if ($result_karyawan->num_rows > 0) {
        $karyawan = $result_karyawan->fetch_assoc();
        $nama = $karyawan['nama'];
        $jabatan = $karyawan['jabatan'];
        $jam = date("H:i:s");
        $tanggal = date("Y-m-d");

        $tabel = "";
        $sesi = "";
        
        // Atur Jam Absen
        // Contoh: Pagi (08:00 - 12:00) dan Siang/Sore (14:00 - 24:00)
        if ($jam >= "07:00:00" && $jam <= "12:00:00") {
            $tabel = "absen_pagi";
            $sesi = "Pagi";
        } elseif ($jam >= "00:00:00" && $jam <= "03:59:00") {
            $tabel = "absen_siang";
            $sesi = "Siang";
        } else {
            echo "Diluar jam absen";
            exit;
        }

        // Cek apakah sudah absen pada sesi dan tanggal hari ini
        // PERBAIKAN: Memastikan kolom 'waktu' ada di tabel Anda.
        // Jika kolom waktu di database Anda bernama lain, ganti 'waktu'
        $cek_absen = "SELECT * FROM $tabel WHERE uid='$uid' AND DATE(waktu)='$tanggal'";
        $result_absen = $conn->query($cek_absen);

        if (!$result_absen) {
             // Tangani error query (misalnya kolom 'waktu' memang tidak ada)
             echo "Error DB: Kolom waktu tidak ditemukan. Cek struktur tabel!";
             exit;
        }

        if ($result_absen->num_rows > 0) {
            echo "Sdh absen $sesi!";
        } else {
            // Absen berhasil
            $sql_insert = "INSERT INTO $tabel (uid, nama, jabatan, waktu) VALUES ('$uid', '$nama', '$jabatan', NOW())";
            if ($conn->query($sql_insert) === TRUE) {
                echo "-$nama"; // Format untuk sukses: '-' diikuti nama
            } else {
                echo "Error: Gagal menyimpan data: " . $conn->error;
            }
        }
    } else {
        // Kartu belum terdaftar
        $update_sql = "UPDATE uid_terakhir SET uid='$uid' WHERE id=1"; // Untuk proses pendaftaran
        $conn->query($update_sql); 
        echo "Kartu baru, daftar!";
    }
} else {
    echo "Parameter UID hilang";
}

$conn->close();
?>