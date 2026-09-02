<div class="page-head">
    <div>
        <h1 class="mono" style="font-family: var(--font-mono);"><?= $job->job_code ?></h1>
        <p class="page-sub">Detail print job</p>
    </div>
</div>

<table class="detail-table">
    <tr><th>File</th><td><?= html_escape($job->original_name) ?></td></tr>
    <tr><th>Pengirim</th><td><?= html_escape($job->user_name) ?></td></tr>
    <tr><th>Printer</th><td><?= html_escape($job->printer_name ?? '—') ?></td></tr>
    <tr><th>Salinan</th><td><?= $job->copies ?></td></tr>
    <tr><th>Ukuran kertas</th><td><?= html_escape($job->paper_size) ?></td></tr>
    <tr><th>Mode warna</th><td><?= ucfirst($job->color_mode) ?></td></tr>
    <tr><th>Status</th><td><span class="badge badge-<?= $job->status ?>"><?= ucfirst($job->status) ?></span></td></tr>
    <?php if ($job->error_message): ?>
        <tr><th>Pesan error</th><td class="text-error"><?= html_escape($job->error_message) ?></td></tr>
    <?php endif; ?>
    <tr><th>Dikirim</th><td><?= date('d M Y, H:i', strtotime($job->submitted_at)) ?></td></tr>
    <?php if ($job->completed_at): ?>
        <tr><th>Selesai</th><td><?= date('d M Y, H:i', strtotime($job->completed_at)) ?></td></tr>
    <?php endif; ?>
</table>

<a class="btn-back" href="<?= site_url('print-jobs') ?>">← Kembali ke daftar job</a>
