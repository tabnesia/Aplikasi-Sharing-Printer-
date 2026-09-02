<div class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p class="page-sub">Ringkasan antrean cetak hari ini</p>
    </div>
</div>

<div class="manifest">
    <div class="manifest-item pending">
        <span class="manifest-value"><?= str_pad($counts['pending'], 2, '0', STR_PAD_LEFT) ?></span>
        <span class="manifest-label">Pending</span>
    </div>
    <div class="manifest-item printing">
        <span class="manifest-value"><?= str_pad($counts['printing'] + $counts['processing'], 2, '0', STR_PAD_LEFT) ?></span>
        <span class="manifest-label">Sedang dicetak</span>
    </div>
    <div class="manifest-item completed">
        <span class="manifest-value"><?= str_pad($counts['completed'], 2, '0', STR_PAD_LEFT) ?></span>
        <span class="manifest-label">Selesai</span>
    </div>
    <div class="manifest-item failed">
        <span class="manifest-value"><?= str_pad($counts['failed'], 2, '0', STR_PAD_LEFT) ?></span>
        <span class="manifest-label">Gagal</span>
    </div>
</div>

<!-- ===== Aktivitas Cetak Hari Ini ===== -->
<div class="activity-section">
    <h2>Aktivitas Cetak Hari Ini</h2>

    <?php
    // Siapkan data 0–23 jam agar chart selalu lengkap
    $hours = range(0, 23);
    $jobs_map   = [];
    $pages_map  = [];
    $max_jobs   = 1;
    $max_pages  = 1;

    foreach ($hourly as $row) {
        $h = (int) $row->hour;
        $jobs_map[$h]  = (int) $row->job_count;
        $pages_map[$h] = (int) $row->page_count;
        if ($jobs_map[$h]  > $max_jobs)  $max_jobs  = $jobs_map[$h];
        if ($pages_map[$h] > $max_pages) $max_pages = $pages_map[$h];
    }

    $success = (int) ($today_summary->success ?? 0);
    $failed  = (int) ($today_summary->failed  ?? 0);
    $total_sf = max($success + $failed, 1);
    ?>

    <div class="activity-grid">
        <!-- Chart Job & Halaman per jam -->
        <div class="activity-card">
            <div class="activity-card-header">
                <span class="activity-title">Job & Halaman per Jam</span>
                <div class="legend">
                    <span class="legend-item"><i class="legend-dot jobs"></i> Job</span>
                    <span class="legend-item"><i class="legend-dot pages"></i> Halaman</span>
                </div>
            </div>

            <div class="chart-area">
                <div class="chart-bars">
                    <?php foreach ($hours as $h):
                        $j = $jobs_map[$h]  ?? 0;
                        $p = $pages_map[$h] ?? 0;
                        $h_jobs  = $max_jobs  > 0 ? round(($j / $max_jobs)  * 100) : 0;
                        $h_pages = $max_pages > 0 ? round(($p / $max_pages) * 100) : 0;
                        $label   = str_pad($h, 2, '0', STR_PAD_LEFT);
                    ?>
                    <div class="bar-group" title="<?= $label ?>:00 — <?= $j ?> job, <?= $p ?> halaman">
                        <div class="bar-stack">
                            <div class="bar bar-jobs"  style="height: <?= $h_jobs ?>%"></div>
                            <div class="bar bar-pages" style="height: <?= $h_pages ?>%"></div>
                        </div>
                        <span class="bar-label"><?= $label ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Berhasil vs Gagal -->
        <div class="activity-card summary-card">
            <div class="activity-card-header">
                <span class="activity-title">Berhasil vs Gagal</span>
            </div>

            <div class="sf-stats">
                <div class="sf-item success">
                    <span class="sf-value"><?= $success ?></span>
                    <span class="sf-label">Berhasil</span>
                </div>
                <div class="sf-item failed">
                    <span class="sf-value"><?= $failed ?></span>
                    <span class="sf-label">Gagal</span>
                </div>
            </div>

            <div class="sf-bar-track">
                <div class="sf-bar success" style="width: <?= round(($success / $total_sf) * 100) ?>%"></div>
                <div class="sf-bar failed"  style="width: <?= round(($failed  / $total_sf) * 100) ?>%"></div>
            </div>
            <div class="sf-percent">
                <span><?= $success > 0 ? round(($success / $total_sf) * 100) : 0 ?>% berhasil</span>
                <span><?= $failed  > 0 ? round(($failed  / $total_sf) * 100) : 0 ?>% gagal</span>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== Aktivitas Cetak Hari Ini ===== */
.activity-section {
    margin: 28px 0 32px;
}

.activity-grid {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 20px;
    margin-top: 12px;
}

@media (max-width: 900px) {
    .activity-grid { grid-template-columns: 1fr; }
}

.activity-card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 12px;
    padding: 18px 20px 16px;
}

.activity-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.activity-title {
    font-weight: 600;
    font-size: 14px;
    color: var(--text, #111);
}

.legend {
    display: flex;
    gap: 14px;
    font-size: 12px;
    color: #6b7280;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.legend-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 3px;
}
.legend-dot.jobs  { background: #3b82f6; }
.legend-dot.pages { background: #94a3b8; }

/* Chart bars */
.chart-area {
    overflow-x: auto;
    padding-bottom: 4px;
}

.chart-bars {
    display: flex;
    align-items: flex-end;
    gap: 4px;
    height: 140px;
    min-width: 520px;
}

.bar-group {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
}

.bar-stack {
    flex: 1;
    width: 100%;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    gap: 2px;
    position: relative;
}

.bar {
    width: 45%;
    max-width: 14px;
    border-radius: 3px 3px 0 0;
    min-height: 2px;
    transition: height 0.3s ease;
}

.bar-jobs  { background: #3b82f6; }
.bar-pages { background: #94a3b8; }

.bar-label {
    font-size: 10px;
    color: #9ca3af;
    margin-top: 6px;
    font-variant-numeric: tabular-nums;
}

/* Success vs Failed */
.summary-card {
    display: flex;
    flex-direction: column;
}

.sf-stats {
    display: flex;
    gap: 16px;
    margin-bottom: 18px;
}

.sf-item {
    flex: 1;
    text-align: center;
    padding: 12px 8px;
    border-radius: 8px;
}

.sf-item.success { background: #ecfdf5; }
.sf-item.failed  { background: #fef2f2; }

.sf-value {
    display: block;
    font-size: 28px;
    font-weight: 700;
    line-height: 1.1;
}

.sf-item.success .sf-value { color: #059669; }
.sf-item.failed  .sf-value { color: #dc2626; }

.sf-label {
    font-size: 12px;
    color: #6b7280;
    margin-top: 2px;
}

.sf-bar-track {
    display: flex;
    height: 10px;
    border-radius: 5px;
    overflow: hidden;
    background: #f3f4f6;
    margin-bottom: 8px;
}

.sf-bar.success { background: #10b981; }
.sf-bar.failed  { background: #ef4444; }

.sf-percent {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #6b7280;
}
</style>

<h2>Printer <a href="<?= site_url('printers') ?>" style="font-weight: 400; font-size: 12px;">Kelola →</a></h2>
<div class="printer-list">
    <?php foreach ($printers as $p): ?>
        <div class="printer-row">
            <span class="dot <?= $p->status ?>"></span>
            <span class="printer-name"><?= html_escape($p->name) ?></span>
            <span class="printer-loc"><?= html_escape($p->location) ?></span>
            <span class="printer-status"><?= ucfirst($p->status) ?></span>
        </div>
    <?php endforeach; ?>
    <?php if (empty($printers)): ?>
        <div class="empty-state">Belum ada printer terdaftar.</div>
    <?php endif; ?>
</div>

<h2>Job terbaru</h2>
<div class="table-wrap">
<table class="table">
    <thead>
        <tr>
            <th class="mono">Kode</th><th>Pengirim</th><th>Printer</th><th>Status</th><th>Waktu</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($recent_jobs as $job): ?>
            <tr>
                <td class="mono"><a href="<?= site_url('print-jobs/' . $job->id) ?>"><?= $job->job_code ?></a></td>
                <td><?= html_escape($job->user_name) ?></td>
                <td><?= html_escape($job->printer_name ?? '—') ?></td>
                <td><span class="badge badge-<?= $job->status ?>"><?= ucfirst($job->status) ?></span></td>
                <td class="muted"><?= date('d M, H:i', strtotime($job->submitted_at)) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($recent_jobs)): ?>
            <tr><td colspan="5"><div class="empty-state">Belum ada print job. Kirim job pertama lewat "+ Job baru".</div></td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>