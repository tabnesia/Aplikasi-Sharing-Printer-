<?php
/**
 * print-agent/agent.php  — versi USB / printer lokal (Windows)
 * ---------------------------------------------------------------
 * Dijalankan di komputer Windows yang printernya tercolok langsung
 * (USB), bukan printer jaringan. Script ini:
 *   1. Kirim heartbeat ke dashboard tiap beberapa detik (status jadi online)
 *   2. Ambil job pending untuk printer ini
 *   3. Download file, lalu cetak ke printer Windows yang dinamai di bawah
 *   4. Lapor status (printing/completed/failed) balik ke dashboard
 *
 * CARA JALANKAN (Windows, dari Command Prompt):
 *   php print-agent\agent.php
 * Biarkan window-nya tetap terbuka — ini proses yang berjalan terus.
 * (Untuk jalan otomatis saat komputer nyala, bisa dibuatkan Scheduled
 * Task atau file .bat + shortcut di folder Startup.)
 *
 * REKOMENDASI: install SumatraPDF (gratis, portable) supaya print PDF
 * (jenis file paling umum) bisa silent & konsisten:
 * https://www.sumatrapdfreader.org/download-free-pdf-viewer
 * Isi SUMATRA_PATH di bawah ke lokasi SumatraPDF.exe kalau sudah ada.
 *
 * Untuk file Word/Excel/PowerPoint (docx/xlsx/pptx dst), agent akan
 * convert dulu ke PDF pakai LibreOffice headless (tanpa membuka
 * aplikasi apa pun secara visual), baru dicetak lewat SumatraPDF —
 * jauh lebih andal dibanding menyuruh Word/Excel print langsung.
 * Download LibreOffice (gratis) di https://www.libreoffice.org/download/
 * lalu isi LIBREOFFICE_PATH ke lokasi soffice.exe-nya.
 *
 * Kalau SUMATRA_PATH/LIBREOFFICE_PATH tidak diisi, agent akan pakai
 * perintah "print" bawaan Windows lewat aplikasi default untuk tipe
 * file itu (kurang konsisten, dan bisa gagal kalau aplikasinya lambat
 * terbuka atau tidak ada app default untuk tipe file itu).
 * ---------------------------------------------------------------
 */

// ---- Konfigurasi ----------------------------------------------------
define('APP_BASE_URL', 'http://localhost/local_printer_smlabs/');
define('PRINTER_ID', 3);                     // sesuai id printer ini di tabel `printers`
define('WINDOWS_PRINTER_NAME', 'HP Ink Tank 310 series'); // persis seperti di Settings > Printers & scanners
define('SUMATRA_PATH', 'C:\\Tools\\SumatraPDF\\SumatraPDF.exe');   // sudah dikonfirmasi ada
define('LIBREOFFICE_PATH', 'C:\\Program Files\\LibreOffice\\program\\soffice.exe');
define('POLL_INTERVAL_SECONDS', 5);
define('DOWNLOAD_DIR', __DIR__ . '/tmp/');
// ---------------------------------------------------------------------

if (!is_dir(DOWNLOAD_DIR)) {
    mkdir(DOWNLOAD_DIR, 0755, true);
}

function api_get($path)
{
    $ch = curl_init(rtrim(APP_BASE_URL, '/') . '/' . ltrim($path, '/'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        log_line('CURL ERROR (GET ' . $path . '): ' . curl_error($ch) . ' [errno ' . curl_errno($ch) . ']');
    } else {
        log_line('DEBUG raw response (GET ' . $path . ', HTTP ' . $http_code . '): ' . substr($response, 0, 500));
    }

    curl_close($ch);

    if ($response === false || $response === '') {
        return null;
    }
    return json_decode($response, true);
}

function api_post($path, array $payload)
{
    $ch = curl_init(rtrim(APP_BASE_URL, '/') . '/' . ltrim($path, '/'));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        log_line('CURL ERROR (POST ' . $path . '): ' . curl_error($ch) . ' [errno ' . curl_errno($ch) . ']');
    } else {
        log_line('DEBUG raw response (POST ' . $path . ', HTTP ' . $http_code . '): ' . substr($response, 0, 500));
    }

    curl_close($ch);

    if ($response === false || $response === '') {
        return null;
    }
    return json_decode($response, true);
}

function log_line($message)
{
    echo '[' . date('Y-m-d H:i:s') . "] $message\n";
}

/** Jalankan sebuah perintah PowerShell dan kembalikan output-nya. */
function run_powershell($command)
{
    $escaped = str_replace('"', '\\"', $command);
    exec("powershell -NoProfile -Command \"$escaped\" 2>&1", $output, $exit_code);
    return ['output' => implode("\n", $output), 'exit_code' => $exit_code];
}

/** Cetak lewat SumatraPDF (paling andal, untuk file PDF). */
function print_with_sumatra($file_path, $copies, $paper_size = null, $color_mode = null)
{
    $settings = [];

    if (!empty($paper_size)) {
        // "shrink" cuma mengecilkan konten kalau memang lebih besar dari
        // kertas target, dan tetap menghormati margin aman printer —
        // beda dengan "fit" yang memaksa isi sampai mepet ke tepi kertas
        // dan bisa kepotong di area yang secara fisik tidak bisa dicetak.
        $settings[] = 'paper=' . $paper_size;
        $settings[] = 'shrink';
    }

    if ($color_mode === 'grayscale') {
        $settings[] = 'monochrome';
    } elseif ($color_mode === 'color') {
        $settings[] = 'color';
    }

    $settings_arg = '';
    if (!empty($settings)) {
        $settings_arg = ' -print-settings "' . implode(',', $settings) . '"';
    }

    for ($i = 0; $i < $copies; $i++) {
        $cmd = '"' . SUMATRA_PATH . '" -print-to "' . WINDOWS_PRINTER_NAME . '"' . $settings_arg . ' -silent "' . $file_path . '"';
        exec($cmd, $output, $exit_code);
        if ($exit_code !== 0) {
            throw new RuntimeException('SumatraPDF gagal mencetak (exit code ' . $exit_code . ')');
        }
    }
}

/**
 * Convert dokumen Office (docx/xlsx/doc/xls/pptx/ppt) ke PDF pakai
 * LibreOffice headless (tanpa buka aplikasi apa pun secara visual).
 * Hasilnya bisa dicetak lewat SumatraPDF yang jauh lebih andal
 * dibanding menyuruh Word/Excel mencetak langsung.
 */
function convert_to_pdf($file_path, $output_dir)
{
    if (LIBREOFFICE_PATH === '' || !file_exists(LIBREOFFICE_PATH)) {
        throw new RuntimeException('LIBREOFFICE_PATH belum diisi atau soffice.exe tidak ditemukan di path itu.');
    }

    $cmd = '"' . LIBREOFFICE_PATH . '" --headless --norestore --convert-to pdf --outdir "'
        . $output_dir . '" "' . $file_path . '"';
    exec($cmd . ' 2>&1', $output, $exit_code);

    if ($exit_code !== 0) {
        throw new RuntimeException('LibreOffice gagal convert ke PDF: ' . implode(' | ', $output));
    }

    $pdf_path = rtrim($output_dir, '/\\') . DIRECTORY_SEPARATOR
        . pathinfo($file_path, PATHINFO_FILENAME) . '.pdf';

    if (!file_exists($pdf_path)) {
        throw new RuntimeException('File PDF hasil konversi tidak ditemukan di: ' . $pdf_path);
    }

    return $pdf_path;
}

/**
 * Cetak lewat aplikasi default Windows untuk tipe file itu (fallback).
 * Dilakukan dengan set printer target ini jadi default sementara,
 * lalu jalankan verb "print" di file (butuh app terkait ter-install:
 * Word untuk docx, Photos untuk jpg/png, dst), lalu kembalikan default.
 */
function print_with_shell_verb($file_path, $copies)
{
    $original = run_powershell('(Get-CimInstance -ClassName Win32_Printer -Filter "Default=TRUE").Name')['output'];
    $original = trim($original);

    $set = run_powershell('(New-Object -ComObject WScript.Network).SetDefaultPrinter("' . WINDOWS_PRINTER_NAME . '")');
    if ($set['exit_code'] !== 0) {
        throw new RuntimeException('Gagal set printer default: ' . $set['output']);
    }

    try {
        for ($i = 0; $i < $copies; $i++) {
            $result = run_powershell('Start-Process -FilePath "' . $file_path . '" -Verb Print');
            if ($result['exit_code'] !== 0) {
                throw new RuntimeException('Gagal mencetak file: ' . $result['output']);
            }
            sleep(3); // beri jeda supaya spooler tidak tabrakan antar salinan
        }
    } finally {
        if ($original !== '') {
            run_powershell('(New-Object -ComObject WScript.Network).SetDefaultPrinter("' . $original . '")');
        }
    }
}

function send_to_printer($file_path, $copies, $file_type, $paper_size = null, $color_mode = null)
{
    $file_type = strtolower($file_type);
    $office_types = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

    // Dokumen Office: convert dulu ke PDF lewat LibreOffice (headless,
    // tanpa buka Word/Excel), baru cetak lewat SumatraPDF.
    if (in_array($file_type, $office_types, true)) {
        $pdf_path = convert_to_pdf($file_path, dirname($file_path));
        try {
            if (SUMATRA_PATH === '' || !file_exists(SUMATRA_PATH)) {
                throw new RuntimeException('SUMATRA_PATH belum diisi/tidak ditemukan.');
            }
            print_with_sumatra($pdf_path, $copies, $paper_size, $color_mode);
        } finally {
            if (file_exists($pdf_path)) {
                unlink($pdf_path);
            }
        }
        return;
    }

    // PDF: langsung cetak lewat SumatraPDF kalau tersedia.
    if ($file_type === 'pdf' && SUMATRA_PATH !== '' && file_exists(SUMATRA_PATH)) {
        print_with_sumatra($file_path, $copies, $paper_size, $color_mode);
        return;
    }

    // Tipe lain (jpg/png dsb): tetap pakai jalur fallback aplikasi default
    // (tidak bisa mengatur paper_size/color_mode lewat cara ini).
    print_with_shell_verb($file_path, $copies);
}

log_line('Print agent (USB/lokal) dimulai untuk printer #' . PRINTER_ID . ' -> "' . WINDOWS_PRINTER_NAME . '"');

while (true) {
    api_post('api/printers/' . PRINTER_ID . '/heartbeat', []);

    $result = api_get('api/printers/' . PRINTER_ID . '/jobs');

    if (!empty($result['success']) && !empty($result['jobs'])) {
        foreach ($result['jobs'] as $job) {
            log_line("Memproses job {$job['job_code']} ({$job['original_name']})");

            api_post('api/jobs/' . $job['id'] . '/status', ['status' => 'processing']);

            $ext = pathinfo($job['original_name'], PATHINFO_EXTENSION);
            $local_file = DOWNLOAD_DIR . $job['job_code'] . '.' . $ext;

            try {
                $contents = file_get_contents($job['file_url']);
                if ($contents === false) {
                    throw new RuntimeException('Gagal mengunduh file dari server');
                }
                file_put_contents($local_file, $contents);

                api_post('api/jobs/' . $job['id'] . '/status', ['status' => 'printing']);
                send_to_printer(
                    $local_file,
                    $job['copies'],
                    $ext,
                    $job['paper_size'] ?? null,
                    $job['color_mode'] ?? null
                );

                api_post('api/jobs/' . $job['id'] . '/status', ['status' => 'completed']);
                log_line("Job {$job['job_code']} selesai dicetak.");
            } catch (Throwable $e) {
                api_post('api/jobs/' . $job['id'] . '/status', [
                    'status'  => 'failed',
                    'message' => $e->getMessage(),
                ]);
                log_line("Job {$job['job_code']} GAGAL: " . $e->getMessage());
            } finally {
                if (file_exists($local_file)) {
                    unlink($local_file);
                }
            }
        }
    }

    sleep(POLL_INTERVAL_SECONDS);
}