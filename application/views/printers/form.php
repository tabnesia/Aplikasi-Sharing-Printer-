<div class="page-head">
    <div>
        <h1><?= $printer ? 'Edit printer' : 'Tambah printer' ?></h1>
        <p class="page-sub">Pilih tipe koneksi sesuai printernya</p>
    </div>
</div>

<form action="<?= $printer ? site_url('printers/' . $printer->id . '/update') : site_url('printers/store') ?>" method="post" class="form">
    <label>Nama printer
        <input type="text" name="name" required value="<?= $printer ? html_escape($printer->name) : '' ?>" placeholder="cth. Canon G2010 - Meja Depan">
    </label>

    <label>Lokasi
        <input type="text" name="location" value="<?= $printer ? html_escape($printer->location) : '' ?>" placeholder="cth. Ruang Admin">
    </label>

    <label>Tipe koneksi
        <select name="driver" id="driver-select">
            <option value="windows" <?= (!$printer || $printer->driver === 'windows') ? 'selected' : '' ?>>USB / lokal (nyolok ke satu PC)</option>
            <option value="raw" <?= ($printer && $printer->driver === 'raw') ? 'selected' : '' ?>>Jaringan (printer punya IP sendiri)</option>
        </select>
    </label>

    <div id="field-windows">
        <label>Nama printer di Windows
            <input type="text" name="windows_printer_name" value="<?= $printer ? html_escape($printer->windows_printer_name) : '' ?>" placeholder="persis seperti di Settings > Printers & scanners">
        </label>
    </div>

    <div id="field-network" class="form-row">
        <label>IP Address
            <input type="text" name="ip_address" value="<?= $printer ? html_escape($printer->ip_address) : '' ?>" placeholder="192.168.1.50">
        </label>
        <label>Port
            <input type="number" name="port" value="<?= $printer ? $printer->port : 9100 ?>">
        </label>
    </div>

    <label style="flex-direction: row; align-items: center; gap: 8px;">
        <input type="checkbox" name="is_active" value="1" style="width: auto;" <?= (!$printer || $printer->is_active) ? 'checked' : '' ?>>
        Aktif (bisa dipilih saat membuat job baru)
    </label>

    <button type="submit" class="btn"><?= $printer ? 'Simpan perubahan' : 'Tambah printer' ?></button>
</form>

<a class="btn-back" href="<?= site_url('printers') ?>">← Kembali ke daftar printer</a>

<script>
  // Tampilkan hanya field yang relevan sesuai tipe koneksi yang dipilih.
  (function () {
    var select = document.getElementById('driver-select');
    var windowsField = document.getElementById('field-windows');
    var networkField = document.getElementById('field-network');

    function sync() {
      var isWindows = select.value === 'windows';
      windowsField.style.display = isWindows ? '' : 'none';
      networkField.style.display = isWindows ? 'none' : 'flex';
    }
    select.addEventListener('change', sync);
    sync();
  })();
</script>
