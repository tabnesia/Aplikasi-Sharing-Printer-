' run-agent-hidden.vbs
' ---------------------------------------------------------------
' Menjalankan print-agent/agent.php di background TANPA window
' Command Prompt yang kelihatan sama sekali. Dipakai lewat Windows
' Task Scheduler supaya agent otomatis jalan setiap kali komputer
' ini login, tanpa perlu buka CMD manual atau isi password apa pun.
'
' CARA PAKAI:
'   1. Sesuaikan PROJECT_DIR di bawah kalau lokasi project kamu beda.
'   2. Simpan file ini di dalam folder print-agent/ (sejajar dengan agent.php).
'   3. Daftarkan lewat Task Scheduler (lihat instruksi terpisah) untuk
'      menjalankan file .vbs ini saat login.
' ---------------------------------------------------------------

Dim objShell, projectDir, command

projectDir = "C:\xampp\htdocs\local_printer_smlabs"
phpExe = "C:\xampp\php\php.exe"
command = "cmd /c cd /d """ & projectDir & """ && """ & phpExe & """ print-agent\agent.php >> print-agent\agent-log.txt 2>&1"

Set objShell = CreateObject("WScript.Shell")

' Parameter kedua (0) = jendela disembunyikan total.
' Parameter ketiga (False) = tidak menunggu proses selesai (agent jalan terus di background).
objShell.Run command, 0, False

Set objShell = Nothing
