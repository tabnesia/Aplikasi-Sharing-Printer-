<?php

define('APP_BASE_URL', 'http://localhost/local_printer_smlabs/');
define('PRINTER_ID', 3);                   
define('WINDOWS_PRINTER_NAME', 'HP Ink Tank 310 series'); 
define('SUMATRA_PATH', 'C:\\Tools\\SumatraPDF\\SumatraPDF.exe');   
define('LIBREOFFICE_PATH', 'C:\\Program Files\\LibreOffice\\program\\soffice.exe');
define('POLL_INTERVAL_SECONDS', 5);
define('DOWNLOAD_DIR', __DIR__ . '/tmp/');

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


function run_powershell($command)
{
    $escaped = str_replace('"', '\\"', $command);
    exec("powershell -NoProfile -Command \"$escaped\" 2>&1", $output, $exit_code);
    return ['output' => implode("\n", $output), 'exit_code' => $exit_code];
}


function print_with_sumatra($file_path, $copies, $paper_size = null, $color_mode = null)
{
    $settings = [];

    if (!empty($paper_size)) {
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
            sleep(3); 
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

  
    if ($file_type === 'pdf' && SUMATRA_PATH !== '' && file_exists(SUMATRA_PATH)) {
        print_with_sumatra($file_path, $copies, $paper_size, $color_mode);
        return;
    }

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