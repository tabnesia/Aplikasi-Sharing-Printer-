<div class="page-head">
    <div>
        <h1>Job print baru</h1>
        <p class="page-sub">Unggah dokumen dan pilih printer tujuan</p>
    </div>
</div>

<div class="job-create-layout">
    <form action="<?= site_url('print-jobs/store') ?>" method="post" enctype="multipart/form-data" class="form">
        <label>Nama pengirim
            <input type="text" name="user_name" required>
        </label>

        <label>Printer tujuan
            <select name="printer_id" required>
                <option value="">— pilih printer —</option>
                <?php foreach ($printers as $p): ?>
                    <option value="<?= $p->id ?>"><?= html_escape($p->name) ?> (<?= html_escape($p->location) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>File (pdf, doc, docx, xls, xlsx, jpg, png — maks 20MB)
            <input type="file" name="document" id="document-input" required>
        </label>

        <div class="form-row">
            <label>Jumlah halaman
                <input type="number" name="pages" min="1" value="1">
            </label>
            <label>Jumlah salinan
                <input type="number" name="copies" min="1" value="1" required>
            </label>
        </div>

        <div class="form-row">
            <label>Ukuran kertas
                <select name="paper_size">
                    <option value="A4">A4</option>
                    <option value="Letter">Letter</option>
                    <option value="Legal">Legal</option>
                    <option value="F4">F4</option>
                </select>
            </label>
            <label>Mode warna
                <select name="color_mode">
                    <option value="grayscale">Grayscale</option>
                    <option value="color">Warna</option>
                </select>
            </label>
        </div>

        <button type="submit" class="btn">Kirim ke antrian</button>
    </form>

    <aside id="file-preview" class="file-preview" style="display:none;">
        <div class="file-preview-header">
            <strong id="file-preview-name"></strong>
            <span id="file-preview-meta" class="muted"></span>
        </div>
        <div id="file-preview-body" class="file-preview-body"></div>
    </aside>

    <div id="file-preview-placeholder" class="file-preview-placeholder">
        Pilih file dulu untuk lihat pratinjaunya di sini
    </div>
</div>

<style>
    .job-create-layout {
        display: flex;
        align-items: flex-start;
        gap: 24px;
    }
    .job-create-layout .form {
        flex: 1 1 380px;
        min-width: 0;
    }
    .file-preview,
    .file-preview-placeholder {
        flex: 1 1 380px;
        min-width: 0;
        position: sticky;
        top: 20px;
    }
    .file-preview-placeholder {
        border: 1px dashed #ddd;
        border-radius: 8px;
        padding: 40px 16px;
        text-align: center;
        color: #999;
        font-size: 13px;
        background: #fcfcfc;
    }
    @media (max-width: 820px) {
        .job-create-layout {
            flex-direction: column;
        }
        .file-preview,
        .file-preview-placeholder {
            position: static;
            width: 100%;
        }
    }
    .file-preview {
        border: 1px solid #e2e2e2;
        border-radius: 8px;
        padding: 12px;
        background: #fafafa;
    }
    .file-preview-header {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 8px;
        margin-bottom: 8px;
        font-size: 14px;
    }
    .file-preview-header strong {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .file-preview-body {
        max-height: 420px;
        overflow: auto;
        border-radius: 6px;
        background: #fff;
        border: 1px solid #eee;
    }
    .file-preview-body img {
        max-width: 100%;
        display: block;
        margin: 0 auto;
    }
    .file-preview-body iframe {
        width: 100%;
        height: 420px;
        border: none;
        display: block;
    }
    .file-preview-body table {
        border-collapse: collapse;
        width: 100%;
        font-size: 12px;
    }
    .file-preview-body table td,
    .file-preview-body table th {
        border: 1px solid #ddd;
        padding: 4px 8px;
        white-space: nowrap;
    }
    .file-preview-doc {
        padding: 16px 20px;
        font-size: 14px;
        line-height: 1.6;
    }
    .file-preview-fallback {
        padding: 28px 16px;
        text-align: center;
        color: #888;
        font-size: 13px;
    }
</style>

<!-- Library ringan buat render preview Word (mammoth) & Excel (SheetJS) di browser -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
(function () {
    var input = document.getElementById('document-input');
    var preview = document.getElementById('file-preview');
    var placeholder = document.getElementById('file-preview-placeholder');
    var previewName = document.getElementById('file-preview-name');
    var previewMeta = document.getElementById('file-preview-meta');
    var previewBody = document.getElementById('file-preview-body');

    if (!input) return;

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function showFallback(message) {
        previewBody.innerHTML = '<div class="file-preview-fallback">' + message + '</div>';
    }

    function renderImage(file) {
        var url = URL.createObjectURL(file);
        previewBody.innerHTML = '';
        var img = document.createElement('img');
        img.src = url;
        previewBody.appendChild(img);
    }

    function renderPdf(file) {
        var url = URL.createObjectURL(file);
        previewBody.innerHTML = '<iframe src="' + url + '"></iframe>';
    }

    function renderDocx(file) {
        if (typeof mammoth === 'undefined') {
            showFallback('Pratinjau Word tidak bisa dimuat (cek koneksi internet). File tetap bisa dikirim seperti biasa.');
            return;
        }
        showFallback('Memuat pratinjau dokumen...');
        var reader = new FileReader();
        reader.onload = function (e) {
            mammoth.convertToHtml({ arrayBuffer: e.target.result })
                .then(function (result) {
                    previewBody.innerHTML = '<div class="file-preview-doc">' + result.value + '</div>';
                })
                .catch(function () {
                    showFallback('Tidak bisa menampilkan pratinjau dokumen ini. File tetap bisa dikirim seperti biasa.');
                });
        };
        reader.readAsArrayBuffer(file);
    }

    function renderExcel(file) {
        if (typeof XLSX === 'undefined') {
            showFallback('Pratinjau Excel tidak bisa dimuat (cek koneksi internet). File tetap bisa dikirim seperti biasa.');
            return;
        }
        showFallback('Memuat pratinjau spreadsheet...');
        var reader = new FileReader();
        reader.onload = function (e) {
            try {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, { type: 'array' });
                var firstSheetName = workbook.SheetNames[0];
                var sheet = workbook.Sheets[firstSheetName];
                var html = XLSX.utils.sheet_to_html(sheet, { editable: false });
                previewBody.innerHTML = html;
            } catch (err) {
                showFallback('Tidak bisa menampilkan pratinjau spreadsheet ini. File tetap bisa dikirim seperti biasa.');
            }
        };
        reader.readAsArrayBuffer(file);
    }

    function renderLegacyDoc() {
        showFallback('Pratinjau belum didukung untuk format .doc lama. File tetap bisa dikirim dan akan dicetak seperti biasa.');
    }

    input.addEventListener('change', function () {
        var file = input.files && input.files[0];

        if (!file) {
            preview.style.display = 'none';
            placeholder.style.display = 'block';
            return;
        }

        placeholder.style.display = 'none';
        preview.style.display = 'block';
        previewName.textContent = file.name;
        previewMeta.textContent = formatSize(file.size);

        var ext = file.name.split('.').pop().toLowerCase();

        if (['jpg', 'jpeg', 'png'].indexOf(ext) !== -1) {
            renderImage(file);
        } else if (ext === 'pdf') {
            renderPdf(file);
        } else if (ext === 'docx') {
            renderDocx(file);
        } else if (ext === 'xlsx' || ext === 'xls') {
            renderExcel(file);
        } else if (ext === 'doc') {
            renderLegacyDoc();
        } else {
            showFallback('Tipe file ini tidak punya pratinjau, tapi tetap bisa dikirim ke antrian print.');
        }
    });
})();
</script>