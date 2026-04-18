<?php
/**
 * Script untuk mengkonversi PNG ke ICO
 * Usage: php convert-png-to-ico.php
 */

$pngFile = __DIR__ . '/docs/logo_121.png';
$icoFile = __DIR__ . '/public/favicon.ico';

// Cek apakah file PNG ada
if (!file_exists($pngFile)) {
    die("Error: File PNG tidak ditemukan di $pngFile");
}

// Cek ekstensi GD untuk ICO
if (!function_exists('imagecreatefrompng')) {
    die("Error: GD library tidak mendukung PNG");
}

// Load gambar PNG
$image = imagecreatefrompng($pngFile);
if (!$image) {
    die("Error: Gagal memuat gambar PNG");
}

// Untuk favicon ICO sederhana, kita buat dalam beberapa ukuran
$sizes = [16, 32, 48]; // Ukuran standar favicon
$icoData = '';

// Header ICO
$icoData .= pack('v', 0); // Reserved
$icoData .= pack('v', 1); // Type (1 untuk ICO)
$icoData .= pack('v', count($sizes)); // Number of images

$imageData = '';
$offset = 6 + (16 * count($sizes)); // Header + directory entries

foreach ($sizes as $size) {
    // Resize image
    $resized = imagescale($image, $size, $size);
    
    // Create BMP data (ICO uses BMP format internally)
    ob_start();
    imagebmp($resized, null);
    $bmpData = ob_get_clean();
    
    // ICO directory entry
    $icoData .= pack('C', $size); // Width
    $icoData .= pack('C', $size); // Height
    $icoData .= pack('C', 0); // Color count
    $icoData .= pack('C', 0); // Reserved
    $icoData .= pack('v', 1); // Color planes
    $icoData .= pack('v', 32); // Bits per pixel
    $icoData .= pack('V', strlen($bmpData)); // Size of image data
    $icoData .= pack('V', $offset); // Offset to image data
    
    $imageData .= $bmpData;
    $offset += strlen($bmpData);
    
    imagedestroy($resized);
}

// Gabungkan header dan image data
$icoData .= $imageData;

// Simpan ke file
if (file_put_contents($icoFile, $icoData)) {
    echo "Sukses: favicon.ico telah dibuat di $icoFile\n";
    echo "Ukuran file: " . filesize($icoFile) . " bytes\n";
} else {
    echo "Error: Gagal menyimpan file ICO\n";
}

imagedestroy($image);
?>