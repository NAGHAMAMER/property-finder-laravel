<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', 'لوحة الأدمن'); ?></title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --sidebar: #0f172a;
            --bg: #f1f5f9;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --success: #15803d;
            --warning: #b45309;
            --danger: #b91c1c;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Tahoma, Arial, sans-serif; background: var(--bg); color: var(--text); }
        a { color: inherit; text-decoration: none; }
        .app { min-height: 100vh; display: grid; grid-template-columns: 250px 1fr; }
        .sidebar { background: var(--sidebar); color: #fff; padding: 24px 18px; }
        .brand { font-size: 22px; font-weight: 800; margin-bottom: 30px; }
        .brand small { display: block; color: #94a3b8; font-size: 12px; margin-top: 7px; font-weight: 400; }
        .nav-link { display: block; padding: 12px 14px; border-radius: 10px; background: rgba(255,255,255,.08); }
        .sidebar-bottom { margin-top: 28px; }
        .logout { width: 100%; border: 1px solid rgba(255,255,255,.18); color: #fff; background: transparent; padding: 11px; border-radius: 9px; cursor: pointer; }
        .main { min-width: 0; }
        .topbar { background: #fff; border-bottom: 1px solid var(--border); padding: 18px 28px; display: flex; justify-content: space-between; align-items: center; }
        .topbar h1 { margin: 0; font-size: 22px; }
        .admin-name { color: var(--muted); font-size: 14px; }
        .content { padding: 28px; }
        .alert { padding: 14px 16px; border-radius: 10px; margin-bottom: 18px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 14px; box-shadow: 0 4px 18px rgba(15,23,42,.04); }
        .card-body { padding: 20px; }
        .grid { display: grid; gap: 18px; }
        .stats { grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 20px; }
        .stat { padding: 20px; }
        .stat-label { color: var(--muted); font-size: 14px; }
        .stat-number { font-size: 30px; font-weight: 800; margin-top: 8px; }
        .filters { display: flex; gap: 12px; flex-wrap: wrap; align-items: end; }
        label { display: block; color: #334155; font-size: 13px; margin-bottom: 7px; font-weight: 700; }
        input, select, textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 9px; padding: 11px 12px; font: inherit; background: #fff; }
        textarea { min-height: 100px; resize: vertical; }
        .btn { display: inline-flex; justify-content: center; align-items: center; gap: 6px; border: 0; border-radius: 9px; padding: 10px 15px; cursor: pointer; font-weight: 700; font-family: inherit; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-success { background: var(--success); color: #fff; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-light { background: #e2e8f0; color: #0f172a; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th, td { padding: 14px 12px; text-align: right; border-bottom: 1px solid var(--border); vertical-align: middle; }
        th { font-size: 13px; color: #475569; background: #f8fafc; }
        td { font-size: 14px; }
        .badge { display: inline-block; border-radius: 999px; padding: 6px 10px; font-size: 12px; font-weight: 800; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #dcfce7; color: #166534; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .muted { color: var(--muted); }
        .actions { display: flex; gap: 7px; flex-wrap: wrap; }
        .section-title { margin: 0 0 16px; font-size: 18px; }
        .details-grid { grid-template-columns: 1.35fr .65fr; align-items: start; }
        .info-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .info-item { padding: 12px; border: 1px solid var(--border); border-radius: 10px; background: #f8fafc; }
        .info-item strong { display: block; margin-bottom: 6px; }
        .document { display: flex; justify-content: space-between; gap: 12px; padding: 12px; border: 1px solid var(--border); border-radius: 9px; margin-bottom: 9px; align-items: center; }
        .images { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 10px; }
        .images img { width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 10px; border: 1px solid var(--border); }
        .stars { color: #d97706; letter-spacing: 1px; }
        .review { border-bottom: 1px solid var(--border); padding: 12px 0; }
        .review:last-child { border-bottom: 0; }
        .map-box { width:100%; min-height:320px; border:1px solid var(--border); border-radius:12px; overflow:hidden; }
        .pagination { margin-top: 18px; }
        nav[role="navigation"] svg { width: 20px; }
        nav[role="navigation"] > div { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        nav[role="navigation"] span, nav[role="navigation"] a { display: inline-block; padding: 8px 10px; }
        @media (max-width: 1000px) {
            .app { grid-template-columns: 1fr; }
            .sidebar { display: flex; justify-content: space-between; align-items: center; padding: 15px 18px; }
            .brand { margin: 0; }
            .brand small, .sidebar-bottom { display: none; }
            .stats { grid-template-columns: repeat(2, minmax(0,1fr)); }
            .details-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 620px) {
            .content { padding: 16px; }
            .topbar { padding: 16px; }
            .stats, .info-grid { grid-template-columns: 1fr; }
            .images { grid-template-columns: 1fr 1fr; }
        }
    </style>
    <?php echo $__env->yieldPushContent('styles'); ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div>
            <div class="brand">إدارة العقارات<small>لوحة التحكم الخاصة بالأدمن</small></div>
            <a class="nav-link" href="<?php echo e(route('admin.dashboard')); ?>">جميع العقارات</a>
        </div>
        <div class="sidebar-bottom">
            <form method="POST" action="<?php echo e(route('admin.logout')); ?>">
                <?php echo csrf_field(); ?>
                <button class="logout" type="submit">تسجيل الخروج</button>
            </form>
        </div>
    </aside>
    <main class="main">
        <header class="topbar">
            <h1><?php echo $__env->yieldContent('page-title', 'لوحة التحكم'); ?></h1>
            <div class="admin-name">مرحبًا، <?php echo e(auth()->user()->name); ?></div>
        </header>
        <section class="content">
            <?php if(session('success')): ?>
                <div class="alert alert-success"><?php echo e(session('success')); ?></div>
            <?php endif; ?>
            <?php if($errors->any()): ?>
                <div class="alert alert-error">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div><?php echo e($error); ?></div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
            <?php echo $__env->yieldContent('content'); ?>
        </section>
    </main>
</div>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\نغم\Downloads\property-finder-user-web-api-final\property-finder-user-web-api-final\property_finder_final\resources\views/layouts/admin.blade.php ENDPATH**/ ?>