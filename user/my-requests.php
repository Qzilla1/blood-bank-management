<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../login.php"); exit();
}
require_once '../config/db.php';
$stmt = $pdo->prepare("SELECT * FROM requests WHERE user_id = :uid ORDER BY id DESC");
$stmt->execute([':uid' => $_SESSION['user_id']]);
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - Blood Bank</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary-crimson:#c82333; --accent-crimson:#ff4757; --dark-indigo:#0b0e14; --panel-bg:#121824; --glass-bg:rgba(18,24,36,0.7); --glass-border:rgba(255,255,255,0.08); --text-primary:#ffffff; --text-secondary:#8a99ad; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background-color:var(--dark-indigo); font-family:'Outfit',sans-serif; color:var(--text-primary); min-height:100vh; }
        .top-navbar { height:60px; background:var(--panel-bg); border-bottom:1px solid var(--glass-border); display:flex; align-items:center; justify-content:space-between; padding:0 24px; position:sticky; top:0; z-index:100; }
        .navbar-brand { font-size:1.2rem; font-weight:700; color:white; }
        .navbar-brand span { color:var(--accent-crimson); }
        .user-badge { background:rgba(255,71,87,0.15); border:1px solid rgba(255,71,87,0.3); color:var(--accent-crimson); padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; }
        .btn-logout { background:rgba(255,255,255,0.05); border:1px solid var(--glass-border); color:var(--text-secondary); padding:6px 14px; border-radius:8px; font-size:13px; text-decoration:none; }
        .layout { display:flex; min-height:calc(100vh - 60px); }
        .sidebar { width:240px; background:var(--panel-bg); border-right:1px solid var(--glass-border); padding:24px 0; flex-shrink:0; }
        .sidebar-label { font-size:10px; letter-spacing:3px; color:var(--text-secondary); padding:0 20px 10px; text-transform:uppercase; font-weight:600; }
        .sidebar a { display:flex; align-items:center; gap:12px; padding:12px 20px; color:var(--text-secondary); text-decoration:none; font-size:14px; font-weight:500; border-left:3px solid transparent; transition:all 0.2s; }
        .sidebar a:hover,.sidebar a.active { background:rgba(255,71,87,0.08); border-left-color:var(--accent-crimson); color:white; }
        .sidebar a i { width:18px; text-align:center; }
        .main { flex:1; padding:28px 32px; }
        .page-title { font-size:1.6rem; font-weight:700; margin-bottom:4px; }
        .page-sub { color:var(--text-secondary); font-size:13px; margin-bottom:28px; }
        .top-bar { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px; }
        .btn-new { background:linear-gradient(135deg,var(--primary-crimson),#e84118); border:none; color:white; padding:10px 20px; border-radius:10px; font-weight:600; font-size:14px; text-decoration:none; }
        .btn-new:hover { color:white; transform:translateY(-1px); }
        .glass-card { background:var(--glass-bg); border:1px solid var(--glass-border); border-radius:14px; overflow:hidden; }
        .glass-table { width:100%; border-collapse:collapse; font-size:13px; }
        .glass-table th { background:rgba(255,255,255,0.03); color:var(--text-secondary); font-size:11px; font-weight:600; letter-spacing:1px; text-transform:uppercase; padding:12px 16px; text-align:left; border-bottom:1px solid var(--glass-border); }
        .glass-table td { padding:14px 16px; border-bottom:1px solid rgba(255,255,255,0.04); color:var(--text-primary); vertical-align:middle; }
        .glass-table tr:last-child td { border-bottom:none; }
        .glass-table tr:hover td { background:rgba(255,255,255,0.02); }
        .badge-status { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-pending   { background:rgba(255,193,7,0.15);  color:#ffc107; border:1px solid rgba(255,193,7,0.3); }
        .badge-approved  { background:rgba(13,110,253,0.15); color:#6ea8fe; border:1px solid rgba(13,110,253,0.3); }
        .badge-fulfilled { background:rgba(40,167,69,0.15);  color:#28a745; border:1px solid rgba(40,167,69,0.3); }
        .badge-rejected  { background:rgba(255,71,87,0.15);  color:var(--accent-crimson); border:1px solid rgba(255,71,87,0.3); }
        .empty-state { text-align:center; padding:48px 24px; color:var(--text-secondary); }
        .empty-state i { font-size:48px; margin-bottom:16px; opacity:0.3; }
    </style>
</head>
<body>
<nav class="top-navbar">
    <div class="navbar-brand"><i class="fa-solid fa-droplet" style="color:var(--accent-crimson);"></i> <span>Lifeline</span> Bank</div>
    <div style="display:flex;align-items:center;gap:14px;">
        <span style="color:var(--text-secondary);font-size:13px;">👤 <?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <span class="user-badge">User</span>
        <a href="../logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket me-1"></i> Logout</a>
    </div>
</nav>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-label">Menu</div>
        <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="stock.php"><i class="fa-solid fa-droplet"></i> Blood Stock</a>
        <a href="request-add.php"><i class="fa-solid fa-file-medical"></i> Request Blood</a>
        <a href="my-requests.php" class="active"><i class="fa-solid fa-list"></i> My Requests</a>
        <a href="register-donor.php"><i class="fa-solid fa-user-plus"></i> Become a Donor</a>
    </aside>
    <main class="main">
        <div class="top-bar">
            <div>
                <div class="page-title">📄 My Requests</div>
                <div class="page-sub">All blood requests you have submitted</div>
            </div>
            <a href="request-add.php" class="btn-new"><i class="fa-solid fa-plus me-2"></i>New Request</a>
        </div>
        <div class="glass-card">
            <table class="glass-table">
                <thead>
                    <tr><th>#</th><th>Patient</th><th>Blood Group</th><th>Units</th><th>Hospital</th><th>Date</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php if(count($requests) > 0):
                    $i = 1;
                    foreach($requests as $r):
                        $b = match($r['status']) {
                            'Pending'   => 'pending',
                            'Approved'  => 'approved',
                            'Fulfilled' => 'fulfilled',
                            'Rejected'  => 'rejected',
                            default     => 'pending'
                        };
                ?>
                    <tr>
                        <td style="color:var(--text-secondary);"><?= $i++ ?></td>
                        <td><?= htmlspecialchars($r['patient_name']) ?></td>
                        <td><strong style="color:var(--accent-crimson);"><?= $r['blood_group'] ?></strong></td>
                        <td><?= $r['units_needed'] ?></td>
                        <td><?= htmlspecialchars($r['hospital']) ?></td>
                        <td style="color:var(--text-secondary);"><?= $r['requested_on'] ?></td>
                        <td><span class="badge-status badge-<?= $b ?>"><?= $r['status'] ?></span></td>
                    </tr>
                <?php endforeach;
                else: ?>
                    <tr><td colspan="7">
                        <div class="empty-state">
                            <i class="fa-solid fa-file-circle-xmark"></i>
                            <p>No requests yet</p>
                            <a href="request-add.php" style="color:var(--accent-crimson);font-weight:600;">Make your first request →</a>
                        </div>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
