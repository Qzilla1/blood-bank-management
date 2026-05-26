<?php
/**
 * User Dashboard
 */
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../login.php"); exit();
}
require_once '../config/db.php';

// Get blood stock
$stock = $pdo->query("SELECT * FROM blood_stock ORDER BY blood_group")->fetchAll();

// Get this user's recent requests
$stmt = $pdo->prepare("SELECT * FROM requests WHERE user_id = :uid ORDER BY id DESC LIMIT 5");
$stmt->execute([':uid' => $_SESSION['user_id']]);
$my_requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Blood Bank</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-crimson: #c82333;
            --accent-crimson: #ff4757;
            --dark-indigo: #0b0e14;
            --panel-bg: #121824;
            --glass-bg: rgba(18,24,36,0.7);
            --glass-border: rgba(255,255,255,0.08);
            --text-primary: #ffffff;
            --text-secondary: #8a99ad;
            --sidebar-width: 240px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background-color:var(--dark-indigo); font-family:'Outfit',sans-serif; color:var(--text-primary); min-height:100vh; }

        /* NAVBAR */
        .top-navbar {
            height: 60px;
            background: var(--panel-bg);
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .navbar-brand {
            font-size: 1.2rem;
            font-weight: 700;
            color: white;
            letter-spacing: 1px;
        }
        .navbar-brand span { color: var(--accent-crimson); }
        .navbar-right { display:flex; align-items:center; gap:14px; }
        .user-badge {
            background: rgba(255,71,87,0.15);
            border: 1px solid rgba(255,71,87,0.3);
            color: var(--accent-crimson);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .btn-logout {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
            color: var(--text-secondary);
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.2s;
            font-family: 'Outfit', sans-serif;
        }
        .btn-logout:hover { background:rgba(255,71,87,0.1); color:var(--accent-crimson); border-color:rgba(255,71,87,0.3); }

        /* LAYOUT */
        .layout { display:flex; min-height:calc(100vh - 60px); }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--panel-bg);
            border-right: 1px solid var(--glass-border);
            padding: 24px 0;
            flex-shrink: 0;
        }
        .sidebar-label {
            font-size: 10px;
            letter-spacing: 3px;
            color: var(--text-secondary);
            padding: 0 20px 10px;
            text-transform: uppercase;
            font-weight: 600;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,71,87,0.08);
            border-left-color: var(--accent-crimson);
            color: white;
        }
        .sidebar a i { width: 18px; text-align:center; font-size:15px; }

        /* MAIN */
        .main { flex:1; padding:28px 32px; overflow-x:hidden; }

        /* CARDS */
        .glass-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            padding: 20px;
        }
        .card-title-text {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 16px;
        }

        /* BLOOD STOCK GRID */
        .blood-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
        .blood-cell {
            border-radius: 10px;
            padding: 14px 10px;
            text-align: center;
            border: 1px solid;
        }
        .blood-cell.good  { border-color:rgba(40,167,69,0.4);  background:rgba(40,167,69,0.08); }
        .blood-cell.low   { border-color:rgba(255,193,7,0.4);  background:rgba(255,193,7,0.08); }
        .blood-cell.out   { border-color:rgba(255,71,87,0.4);  background:rgba(255,71,87,0.08); }
        .blood-group { font-weight:800; font-size:1.3rem; letter-spacing:1px; }
        .blood-cell.good .blood-group  { color:#28a745; }
        .blood-cell.low  .blood-group  { color:#ffc107; }
        .blood-cell.out  .blood-group  { color:var(--accent-crimson); }
        .blood-units { font-size:11px; color:var(--text-secondary); margin:3px 0; }
        .blood-badge {
            display:inline-block; font-size:10px; font-weight:700;
            padding:2px 8px; border-radius:20px; margin-top:4px;
            text-transform:uppercase; letter-spacing:0.5px;
        }
        .blood-cell.good .blood-badge { background:rgba(40,167,69,0.2);  color:#28a745; }
        .blood-cell.low  .blood-badge { background:rgba(255,193,7,0.2);  color:#ffc107; }
        .blood-cell.out  .blood-badge { background:rgba(255,71,87,0.2);  color:var(--accent-crimson); }

        /* TABLE */
        .glass-table { width:100%; border-collapse:collapse; font-size:13px; }
        .glass-table th {
            background: rgba(255,255,255,0.03);
            color: var(--text-secondary);
            font-size:11px; font-weight:600;
            letter-spacing:1px; text-transform:uppercase;
            padding: 10px 14px;
            text-align:left;
            border-bottom: 1px solid var(--glass-border);
        }
        .glass-table td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            color: var(--text-primary);
            vertical-align: middle;
        }
        .glass-table tr:last-child td { border-bottom:none; }
        .glass-table tr:hover td { background:rgba(255,255,255,0.02); }

        /* STATUS BADGES */
        .badge-status {
            display:inline-block; padding:3px 10px;
            border-radius:20px; font-size:11px; font-weight:600;
        }
        .badge-pending   { background:rgba(255,193,7,0.15);  color:#ffc107; border:1px solid rgba(255,193,7,0.3); }
        .badge-approved  { background:rgba(13,110,253,0.15); color:#6ea8fe; border:1px solid rgba(13,110,253,0.3); }
        .badge-fulfilled { background:rgba(40,167,69,0.15);  color:#28a745; border:1px solid rgba(40,167,69,0.3); }
        .badge-rejected  { background:rgba(255,71,87,0.15);  color:var(--accent-crimson); border:1px solid rgba(255,71,87,0.3); }

        /* BANNER */
        .banner {
            background: linear-gradient(135deg, rgba(200,35,51,0.2), rgba(255,71,87,0.1));
            border: 1px solid rgba(255,71,87,0.2);
            border-radius: 14px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        .banner-title { font-size:16px; font-weight:700; margin-bottom:4px; }
        .banner-sub   { font-size:13px; color:var(--text-secondary); }
        .btn-request {
            background: linear-gradient(135deg, var(--primary-crimson), #e84118);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            white-space: nowrap;
            transition: all 0.2s;
        }
        .btn-request:hover { transform:translateY(-2px); color:white; box-shadow:0 6px 20px rgba(200,35,51,0.4); }

        .page-title { font-size:1.6rem; font-weight:700; letter-spacing:-0.5px; margin-bottom:4px; }
        .page-sub   { color:var(--text-secondary); font-size:13px; margin-bottom:24px; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="top-navbar">
    <div class="navbar-brand"><i class="fa-solid fa-droplet" style="color:var(--accent-crimson);"></i> <span>Lifeline</span> Bank</div>
    <div class="navbar-right">
        <span style="color:var(--text-secondary);font-size:13px;">👤 <?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <span class="user-badge">User</span>
        <a href="../logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket me-1"></i> Logout</a>
    </div>
</nav>

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-label">Menu</div>
        <a href="dashboard.php" class="active"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="stock.php"><i class="fa-solid fa-droplet"></i> Blood Stock</a>
        <a href="request-add.php"><i class="fa-solid fa-file-medical"></i> Request Blood</a>
        <a href="my-requests.php"><i class="fa-solid fa-list"></i> My Requests</a>
        <a href="register-donor.php"><i class="fa-solid fa-user-plus"></i> Become a Donor</a>
    </aside>

    <!-- MAIN -->
    <main class="main">
        <div class="page-title">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>!</div>
        <div class="page-sub">Here's the current blood availability at a glance</div>

        <!-- BANNER -->
        <div class="banner">
            <div>
                <div class="banner-title">🩸 Need Blood Urgently?</div>
                <div class="banner-sub">Submit a request and our admin will process it shortly.</div>
            </div>
            <a href="request-add.php" class="btn-request">
                <i class="fa-solid fa-plus me-1"></i> Request Blood
            </a>
        </div>

        <!-- BLOOD STOCK -->
        <div class="glass-card mb-4">
            <div class="card-title-text"><i class="fa-solid fa-droplet me-2" style="color:var(--accent-crimson);"></i>Blood Availability</div>
            <div class="blood-grid">
                <?php foreach($stock as $r):
                    $u = $r['units'];
                    $c = $u == 0 ? 'out' : ($u <= 5 ? 'low' : 'good');
                    $l = $u == 0 ? 'Not Available' : ($u <= 5 ? 'Low Stock' : 'Available');
                ?>
                <div class="blood-cell <?= $c ?>">
                    <div class="blood-group"><?= $r['blood_group'] ?></div>
                    <div class="blood-units"><?= $u ?> units</div>
                    <div class="blood-badge"><?= $l ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- RECENT REQUESTS -->
        <div class="glass-card">
            <div class="card-title-text"><i class="fa-solid fa-list me-2" style="color:var(--accent-crimson);"></i>My Recent Requests</div>
            <table class="glass-table">
                <thead>
                    <tr>
                        <th>Patient</th><th>Blood Group</th><th>Units</th>
                        <th>Hospital</th><th>Date</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(count($my_requests) > 0): ?>
                    <?php foreach($my_requests as $r):
                        $b = match($r['status']) {
                            'Pending'   => 'pending',
                            'Approved'  => 'approved',
                            'Fulfilled' => 'fulfilled',
                            'Rejected'  => 'rejected',
                            default     => 'pending'
                        };
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($r['patient_name']) ?></td>
                        <td><strong><?= $r['blood_group'] ?></strong></td>
                        <td><?= $r['units_needed'] ?></td>
                        <td><?= htmlspecialchars($r['hospital']) ?></td>
                        <td><?= $r['requested_on'] ?></td>
                        <td><span class="badge-status badge-<?= $b ?>"><?= $r['status'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--text-secondary);padding:24px;">
                        No requests yet — <a href="request-add.php" style="color:var(--accent-crimson);">make your first request</a>
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
