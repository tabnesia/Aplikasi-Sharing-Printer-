<div class="page-head">
    <div>
        <h1>Print Jobs</h1>
        <p class="page-sub"><?= count($jobs) ?> job dalam daftar ini</p>
    </div>
    <a class="btn" href="<?= site_url('print-jobs/create') ?>">+ Job baru</a>
</div>

<form method="get" class="filter-bar">
    <select name="status" onchange="this.form.submit()">
        <option value="">Semua status</option>
        <?php foreach (['pending', 'processing', 'printing', 'completed', 'failed', 'cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="printer_id" onchange="this.form.submit()">
        <option value="">Semua printer</option>
        <?php foreach ($printers as $p): ?>
            <option value="<?= $p->id ?>" <?= ($filters['printer_id'] ?? '') == $p->id ? 'selected' : '' ?>><?= html_escape($p->name) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<div class="table-wrap">
<table class="table">
    <thead>
        <tr>
            <th class="mono">Kode</th><th>File</th><th>Pengirim</th><th>Printer</th><th>Salinan</th><th>Status</th><th>Waktu</th><th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($jobs as $job): ?>
            <tr data-status="<?= $job->status ?>">
                <td class="mono"><a href="<?= site_url('print-jobs/' . $job->id) ?>"><?= $job->job_code ?></a></td>
                <td><?= html_escape($job->original_name) ?></td>
                <td><?= html_escape($job->user_name) ?></td>
                <td><?= html_escape($job->printer_name ?? '—') ?></td>
                <td class="mono"><?= $job->copies ?></td>
                <td>
                    <span class="badge badge-<?= $job->status ?>">
                        <?php if (in_array($job->status, ['processing', 'printing'], true)): ?>
                            <svg class="spin-icon" width="11" height="11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="40 16"/>
                            </svg>
                        <?php endif; ?>
                        <?= ucfirst($job->status) ?>
                    </span>
                </td>
                <td class="muted"><?= date('d M, H:i', strtotime($job->submitted_at)) ?></td>
                <td>
                    <?php if ($job->status === 'pending'): ?>
                        <a href="<?= site_url('print-jobs/' . $job->id . '/cancel') ?>"
                           onclick="return confirm('Batalkan job ini?')">Batalkan</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($jobs)): ?>
            <tr><td colspan="8"><div class="empty-state">Tidak ada print job yang cocok dengan filter ini.</div></td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>

<style>
    @keyframes spin-print {
        from { transform: rotate(0deg); }
        to   { transform: rotate(360deg); }
    }
    .spin-icon {
        display: inline-block;
        vertical-align: -2px;
        margin-right: 4px;
        animation: spin-print 0.9s linear infinite;
    }
</style>

<script>
    (function () {
        var ACTIVE_STATUSES = ['pending', 'processing', 'printing'];
        var REFRESH_INTERVAL_MS = 5000;

        var rows = document.querySelectorAll('.table tbody tr[data-status]');
        var hasActiveJob = Array.prototype.some.call(rows, function (row) {
            return ACTIVE_STATUSES.indexOf(row.getAttribute('data-status')) !== -1;
        });

        if (hasActiveJob) {
            setTimeout(function () {
                window.location.reload();
            }, REFRESH_INTERVAL_MS);
        }
    })();
</script>