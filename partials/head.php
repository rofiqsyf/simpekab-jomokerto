<?php
// partials/head.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $pageTitle ?? 'SIMPEKAB JOMOKERTO' ?> — SIMPEKAB JOMOKERTO</title>
  <meta name="description" content="Sistem Informasi Kepegawaian SIMPEKAB JOMOKERTO — Autentikasi Aman & RBAC"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            'primary': '#ffb800', 'primary-hover': '#f0ad00', 'on-primary': '#1a1d1f',
            'surface': '#f4f6fa', 'surface-card': '#ffffff',
            'text-main': '#1a1d1f', 'text-muted': '#9a9fa5',
            'border-light': '#eaecf0',
            'success': '#10b981', 'error': '#ef4444', 'warning': '#f59e0b', 'info': '#3b82f6',
          },
          borderRadius: { 'xl': '1rem', '2xl': '1.25rem', '3xl': '1.5rem' },
          boxShadow: { 'soft': '0 10px 40px rgba(0,0,0,0.04)', 'card': '0 4px 20px rgba(0,0,0,0.03)' }
        }
      }
    };
  </script>
  <style>
    /* ======================================
       JOMOKERTO CLEAN ELEGANT DESIGN SYSTEM
       ====================================== */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background: #f4f6fa; color: #1a1d1f; min-height: 100vh; }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    /* Cards */
    .card { background: #ffffff; border: 1px solid #ffffff; border-radius: 20px; padding: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.04); transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .card-dark { background: linear-gradient(135deg, #1a1d1f 0%, #2d3134 100%); color: #ffffff; border-radius: 20px; padding: 24px; box-shadow: 0 16px 32px rgba(26,29,31,0.15); }
    
    /* Buttons */
    .btn-primary { background: #ffb800; color: #1a1d1f; border: none; padding: 12px 24px; border-radius: 999px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; text-decoration: none; box-shadow: 0 4px 12px rgba(255,184,0,0.2); }
    .btn-primary:hover { background: #f0ad00; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(255,184,0,0.3); }
    .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: none; }
    
    .btn-ghost { background: transparent; color: #1a1d1f; border: 1px solid #e2e8f0; padding: 10px 20px; border-radius: 999px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; text-decoration: none; }
    .btn-ghost:hover { background: #f8fafc; border-color: #cbd5e1; }
    
    .btn-danger { background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2; padding: 10px 20px; border-radius: 999px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
    .btn-danger:hover { background: #fee2e2; border-color: #fca5a5; }

    .btn-warning { background: #fffbeb; color: #f59e0b; border: 1px solid #fef3c7; padding: 10px 20px; border-radius: 999px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
    .btn-warning:hover { background: #fef3c7; border-color: #fde68a; }

    /* Forms */
    .input-field { width: 100%; background: #ffffff; border: 1px solid #eaecf0; color: #1a1d1f; padding: 12px 16px; border-radius: 12px; font-size: 15px; outline: none; transition: border-color 0.2s, box-shadow 0.2s; font-family: 'Inter', sans-serif; }
    .input-field:focus { border-color: #ffb800; box-shadow: 0 0 0 3px rgba(255,184,0,0.15); }
    .input-field::placeholder { color: #9a9fa5; }

    .input-card { width: 100%; background: #f8fafc; border: 1px solid #eaecf0; padding: 12px 16px; border-radius: 12px; color: #1a1d1f; font-size: 15px; outline: none; transition: all 0.2s; font-family: 'Inter', sans-serif; }
    .input-card:focus { background: #ffffff; border-color: #ffb800; box-shadow: 0 0 0 3px rgba(255,184,0,0.15); }
    .input-card::placeholder { color: #9a9fa5; }
    
    .label { display: block; font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 8px; }

    /* Badges */
    .badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    .badge-admin { background: #fee2e2; color: #ef4444; }
    .badge-manager { background: #fef3c7; color: #f59e0b; }
    .badge-karyawan { background: #e0f2fe; color: #0ea5e9; }
    .badge-active { background: #dcfce7; color: #10b981; }
    .badge-warning { background: #fef3c7; color: #f59e0b; }
    .badge-danger { background: #fee2e2; color: #ef4444; }
    .badge-info { background: #e0f2fe; color: #3b82f6; }
    .badge-secondary { background: #f1f5f9; color: #64748b; }

    /* Status dots */
    .dot-green { width: 8px; height: 8px; border-radius: 50%; background: #10b981; display: inline-block; }
    .dot-yellow { width: 8px; height: 8px; border-radius: 50%; background: #f59e0b; display: inline-block; }
    .dot-gray { width: 8px; height: 8px; border-radius: 50%; background: #94a3b8; display: inline-block; }
    .dot-red { width: 8px; height: 8px; border-radius: 50%; background: #ef4444; display: inline-block; }

    /* Alert / Flash messages */
    .alert { padding: 16px; border-radius: 12px; font-size: 14px; display: flex; align-items: center; gap: 12px; margin-bottom: 24px; font-weight: 500; }
    .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
    .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
    .alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #b45309; }
    .alert-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; }

    /* Data table */
    .data-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .data-table th { padding: 16px 20px; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #eaecf0; text-align: left; background: #f8fafc; }
    .data-table th:first-child { border-top-left-radius: 12px; }
    .data-table th:last-child { border-top-right-radius: 12px; }
    .data-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; vertical-align: middle; background: #ffffff; }
    .data-table tr:last-child td { border-bottom: none; }
    .data-table tr:hover td { background: #f8fafc; }

    /* Avatar */
    .avatar { display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: 700; flex-shrink: 0; }
    .avatar-sm { width: 36px; height: 36px; font-size: 13px; }
    .avatar-md { width: 48px; height: 48px; font-size: 16px; }
    .avatar-lg { width: 72px; height: 72px; font-size: 24px; }

    /* Progress bar */
    .progress-bar { height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 3px; transition: width 0.6s ease; }

    /* App Layout */
    .app-layout { display: flex; min-height: 100vh; }
    .main-content { flex: 1; min-width: 0; display: flex; flex-direction: column; }

    /* Sidebar */
    #sidebar { width: 280px; flex-shrink: 0; background: #ffffff; border-right: 1px solid #eaecf0; }
    @media (max-width: 768px) { #sidebar { display: none; position: fixed; left: 0; top: 0; height: 100vh; z-index: 100; box-shadow: 4px 0 24px rgba(0,0,0,0.05); } #sidebar.mobile-open { display: flex; } }

    /* Nav Item */
    .nav-item { display: flex; align-items: center; gap: 14px; padding: 12px 20px; margin: 4px 16px; border-radius: 12px; color: #64748b; font-size: 15px; font-weight: 600; text-decoration: none; transition: all 0.2s; cursor: pointer; border: none; background: transparent; width: calc(100% - 32px); }
    .nav-item:hover { background: #f8fafc; color: #1a1d1f; }
    .nav-item.active { background: #ffb800; color: #1a1d1f; box-shadow: 0 4px 12px rgba(255,184,0,0.2); }
    .nav-item .material-symbols-outlined { font-size: 22px; transition: color 0.2s; }
    .nav-item.active .material-symbols-outlined { color: #1a1d1f; }

    /* Topbar */
    .topbar { height: 72px; display: flex; align-items: center; justify-content: space-between; padding: 0 32px; position: sticky; top: 0; z-index: 50; background: rgba(244,246,250,0.8); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(234,236,240,0.5); }

    /* Modal */
    .modal-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,0.4); backdrop-filter: blur(4px); z-index: 200; display: none; align-items: center; justify-content: center; }
    .modal-backdrop.open { display: flex; }
    .modal { background: #ffffff; border-radius: 24px; padding: 32px; max-width: 520px; width: 90%; position: relative; box-shadow: 0 20px 60px rgba(0,0,0,0.1); }

    /* Stat info box */
    .stat-box { background: #f8fafc; border: 1px solid #eaecf0; border-radius: 16px; padding: 20px; }

    /* Form Layout */
    .form-group { margin-bottom: 24px; }
    .form-row { display: grid; gap: 20px; }
    .form-row.cols-2 { grid-template-columns: 1fr 1fr; }
    @media (max-width: 640px) { .form-row.cols-2 { grid-template-columns: 1fr; } }

    /* Page Content */
    .page-content { flex: 1; overflow-y: auto; padding: 32px 40px; }
    @media (max-width: 768px) { .page-content { padding: 24px 20px; } }

    /* Typography */
    .section-title { font-size: 32px; font-weight: 800; color: #1a1d1f; letter-spacing: -0.02em; margin-bottom: 8px; }
    .section-subtitle { color: #64748b; font-size: 16px; font-weight: 500; margin-bottom: 32px; }
  </style>
</head>
<body>
