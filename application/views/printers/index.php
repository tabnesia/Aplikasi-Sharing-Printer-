<div class="page-head">
    <div>
        <h1>Printer</h1>
        <p class="page-sub">Kelola perangkat printer &amp; cara menghubungkannya</p>
    </div>
    <a class="btn" href="<?= site_url('printers/create') ?>">+ Tambah printer</a>
</div>

<div class="table-wrap">
<table class="table">
    <thead>
        <tr>
            <th>Nama</th><th>Lokasi</th><th>Koneksi</th><th>Target</th><th>Status</th><th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($printers as $p): ?>
            <tr>
                <td><span class="dot <?= $p->status ?>"></span> <?= html_escape($p->name) ?></td>
                <td><?= html_escape($p->location) ?></td>
                <td><?= $p->driver === 'windows' ? 'USB / lokal' : 'Jaringan (IP)' ?></td>
                <td class="mono">
                    <?= $p->driver === 'windows'
                        ? html_escape($p->windows_printer_name ?: '—')
                        : html_escape(($p->ip_address ?: '—') . ':' . $p->port) ?>
                </td>
                <td><span class="badge badge-<?= $p->status === 'online' ? 'completed' : ($p->status === 'busy' ? 'pending' : 'cancelled') ?>"><?= ucfirst($p->status) ?></span></td>
                <td>
                    <a href="<?= site_url('printers/' . $p->id . '/edit') ?>">Edit</a>
                    &nbsp;·&nbsp;
                    <a href="<?= site_url('printers/' . $p->id . '/delete') ?>" onclick="return confirm('Hapus printer ini?')">Hapus</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($printers)): ?>
            <tr><td colspan="6"><div class="empty-state">Belum ada printer. Tambahkan lewat "+ Tambah printer".</div></td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>

<h2>Cara menghubungkan printer USB</h2>
<table class="detail-table">
    <tr><th>1. Daftarkan</th><td>Tambahkan printer di sini dengan tipe koneksi "USB / lokal", isi nama printer persis seperti yang tampil di Windows (Settings → Printers &amp; scanners).</td></tr>
    <tr><th>2. Siapkan agent</th><td>Di komputer yang printernya tercolok, buka <code>print-agent/agent.php</code>, isi <code>PRINTER_ID</code> sesuai ID printer ini dan <code>WINDOWS_PRINTER_NAME</code> sesuai nama printernya.</td></tr>
    <tr><th>3. Jalankan</th><td>Jalankan <code>php print-agent/agent.php</code> di komputer itu. Biarkan berjalan terus — dia akan otomatis mengambil job yang masuk dan mencetaknya.</td></tr>
</table>
