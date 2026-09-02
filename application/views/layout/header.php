<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title . ' — ' : '' ?>Local Printer SMLabs</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>
<?php $segment = $this->uri->segment(1) ?: 'dashboard'; ?>
<div class="app">
    <aside class="sidebar">
        <div class="brand">
            <svg class="brand-mark" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="1" y="1" width="18" height="18" rx="2" stroke="#4fb3ac" stroke-width="1.4"/>
                <line x1="10" y1="1" x2="10" y2="6" stroke="#4fb3ac" stroke-width="1.4"/>
                <line x1="10" y1="14" x2="10" y2="19" stroke="#4fb3ac" stroke-width="1.4"/>
                <line x1="1" y1="10" x2="6" y2="10" stroke="#4fb3ac" stroke-width="1.4"/>
                <line x1="14" y1="10" x2="19" y2="10" stroke="#4fb3ac" stroke-width="1.4"/>
                <circle cx="10" cy="10" r="2.4" stroke="#4fb3ac" stroke-width="1.4"/>
            </svg>
            SMLabs Printer
        </div>
        <p class="brand-sub">Print queue &amp; device status</p>

        <nav>
            <a href="<?= site_url('dashboard') ?>" class="<?= $segment === 'dashboard' ? 'active' : '' ?>">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M2 9h4v5H2V9zM6.5 2h4v12h-4V2zM11 6h4v8h-4V6z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg>
                Dashboard
            </a>
            <a href="<?= site_url('print-jobs') ?>" class="<?= $segment === 'print-jobs' ? 'active' : '' ?>">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 1.5h5.5L12 4v10.5H4V1.5z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/><path d="M6 8h4M6 10.5h4M6 5.5h2" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
                Print Jobs
            </a>
            <a href="<?= site_url('printers') ?>" class="<?= $segment === 'printers' ? 'active' : '' ?>">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 1.5h8v4H4v-4zM2.5 5.5h11a1 1 0 0 1 1 1v4.5h-3v3h-7v-3h-3V6.5a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/><rect x="5.5" y="10" width="5" height="3.5" stroke="currentColor" stroke-width="1.1"/></svg>
                Printer
            </a>
            <a href="<?= site_url('print-jobs/create') ?>" class="btn-nav">+ Job baru</a>
        </nav>

        <div class="sidebar-footer">local-printer-smlabs</div>
    </aside>

    <main class="content">
        <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success"><?= $this->session->flashdata('success') ?></div>
        <?php endif; ?>
        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-error"><?= $this->session->flashdata('error') ?></div>
        <?php endif; ?>
