<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../login.php"); exit();
}
require_once '../config/db.php';
$stock = $pdo->query("SELECT * FROM blood_stock ORDER BY blood_group")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Stock - Blood Bank</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary-crimson:#c82333; --accent-crimson:#ff4757; --dark-indigo:#0b0e14; --panel-bg:#121824; --glass-bg:rgba(18,24,36,0.7); --glass-border:rgba(255,255,255,0.08); --text-primary:#ffffff; --text-secondary:#8a99ad; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background-color:var(--dark-indigo); font-family:'Outfit',sans-serif; color:var(--text-primary); min-height:100vh; }
        .top-navbar { height:60px; background:var(--panel-bg); border-bottom:1px solid var(--glass-border); display:flex; align-items:center; justify-content:space-between; padding:0 24px; position:sticky; top:0; z-index:100; }
        .navbar-brand { font-size:1.2rem; font-weight:700; color:white; letter-spacing:1px; }
        .navbar-brand span { color:var(--accent-crimson); }
        .user-badge { background:rgba(255,71,87,0.15); border:1px solid rgba(255,71,87,0.3); color:var(--accent-crimson); padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; }
        .btn-logout { background:rgba(255,255,255,0.05); border:1px solid var(--glass-border); color:var(--text-secondary); padding:6px 14px; border-radius:8px; font-size:13px; text-decoration:none; transition:all 0.2s; }
        .btn-logout:hover { background:rgba(255,71,87,0.1); color:var(--accent-crimson); }
        .layout { display:flex; min-height:calc(100vh - 60px); }
        .sidebar { width:240px; background:var(--panel-bg); border-right:1px solid var(--glass-border); padding:24px 0; flex-shrink:0; }
        .sidebar-label { font-size:10px; letter-spacing:3px; color:var(--text-secondary); padding:0 20px 10px; text-transform:uppercase; font-weight:600; }
        .sidebar a { display:flex; align-items:center; gap:12px; padding:12px 20px; color:var(--text-secondary); text-decoration:none; font-size:14px; font-weight:500; border-left:3px solid transparent; transition:all 0.2s; }
        .sidebar a:hover,.sidebar a.active { background:rgba(255,71,87,0.08); border-left-color:var(--accent-crimson); color:white; }
        .sidebar a i { width:18px; text-align:center; }
        .main { flex:1; padding:28px 32px; }
        .page-title { font-size:1.6rem; font-weight:700; letter-spacing:-0.5px; margin-bottom:4px; }
        .page-sub { color:var(--text-secondary); font-size:13px; margin-bottom:28px; }
        .stock-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
        .stock-card { background:var(--glass-bg); border:1px solid var(--glass-border); border-radius:14px; padding:24px 16px; text-align:center; transition:all 0.2s; }
        .stock-card:hover { transform:translateY(-3px); }
        .stock-card.good  { border-color:rgba(40,167,69,0.3); }
        .stock-card.low   { border-color:rgba(255,193,7,0.3); }
        .stock-card.out   { border-color:rgba(255,71,87,0.3); }
        .stock-group { font-weight:900; font-size:2rem; letter-spacing:1px; margin-bottom:6px; }
        .stock-card.good .stock-group { color:#28a745; }
        .stock-card.low  .stock-group { color:#ffc107; }
        .stock-card.out  .stock-group { color:var(--accent-crimson); }
        .stock-units { font-size:13px; color:var(--text-secondary); margin-bottom:10px; }
        .stock-badge { display:inline-block; font-size:11px; font-weight:700; padding:3px 12px; border-radius:20px; text-transform:uppercase; margin-bottom:12px; }
        .stock-card.good .stock-badge { background:rgba(40,167,69,0.15); color:#28a745; }
        .stock-card.low  .stock-badge { background:rgba(255,193,7,0.15); color:#ffc107; }
        .stock-card.out  .stock-badge { background:rgba(255,71,87,0.15); color:var(--accent-crimson); }
        .btn-req { display:inline-block; padding:7px 16px; border-radius:8px; font-size:12px; font-weight:600; text-decoration:none; background:linear-gradient(135deg,var(--primary-crimson),#e84118); color:white; transition:all 0.2s; }
        .btn-req:hover { transform:translateY(-1px); color:white; }
        .btn-req.disabled { background:rgba(255,255,255,0.05); color:var(--text-secondary); pointer-events:none; }
        .legend { display:flex; gap:16px; margin-top:24px; padding:14px 20px; background:var(--glass-bg); border:1px solid var(--glass-border); border-radius:10px; }
        .legend-item { display:flex; align-items:center; gap:8px; font-size:12px; color:var(--text-secondary); }
        .dot { width:10px; height:10px; border-radius:50%; }
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
        <a href="stock.php" class="active"><i class="fa-solid fa-droplet"></i> Blood Stock</a>
        <a href="request-add.php"><i class="fa-solid fa-file-medical"></i> Request Blood</a>
        <a href="my-requests.php"><i class="fa-solid fa-list"></i> My Requests</a>
        <a href="register-donor.php"><i class="fa-solid fa-user-plus"></i> Become a Donor</a>
    </aside>
    <main class="main">
        <div class="page-title">🩸 Blood Stock</div>
        <div class="page-sub">Current availability of all blood groups</div>
        <div class="stock-grid">
        <?php foreach($stock as $r):
            $u = $r['units'];
            $c = $u==0?'out':($u<=5?'low':'good');
            $l = $u==0?'Not Available':($u<=5?'Low Stock':'Available');
        ?>
            <div class="stock-card <?= $c ?>">
                <div class="stock-group"><?= $r['blood_group'] ?></div>
                <div class="stock-units"><?= $u ?> units available</div>
                <div class="stock-badge"><?= $l ?></div>
                <?php if($u > 0): ?>
                    <a href="request-add.php" class="btn-req">Request This</a>
                <?php else: ?>
                    <a href="#" class="btn-req disabled">Unavailable</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
        <div class="legend">
            <div class="legend-item"><div class="dot" style="background:#28a745;"></div> 6+ units = Available</div>
            <div class="legend-item"><div class="dot" style="background:#ffc107;"></div> 1–5 units = Low Stock</div>
            <div class="legend-item"><div class="dot" style="background:var(--accent-crimson);"></div> 0 units = Not Available</div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
