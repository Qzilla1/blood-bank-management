<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../login.php"); exit();
}
require_once '../config/db.php';
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_name = trim($_POST['patient_name'] ?? '');
    $blood_group  = trim($_POST['blood_group']  ?? '');
    $units_needed = (int)($_POST['units_needed'] ?? 0);
    $hospital     = trim($_POST['hospital']      ?? '');
    $contact      = trim($_POST['contact']       ?? '');
    $user_id      = $_SESSION['user_id'];

    if (empty($patient_name) || empty($blood_group) || empty($hospital) || empty($contact) || $units_needed < 1) {
        $error = "Please fill in all fields correctly.";
    } else {
        // Check stock
        $s = $pdo->prepare("SELECT units FROM blood_stock WHERE blood_group = :bg");
        $s->execute([':bg' => $blood_group]);
        $stock = $s->fetch();

        if (!$stock || $stock['units'] < $units_needed) {
            $avail = $stock['units'] ?? 0;
            $error = "Only $avail units of $blood_group available right now!";
        } else {
            $ins = $pdo->prepare("INSERT INTO requests
                (patient_name, blood_group, units_needed, hospital, contact, user_id, requested_on)
                VALUES (:pn, :bg, :un, :hosp, :cont, :uid, CURDATE())");
            $ins->execute([
                ':pn'   => $patient_name,
                ':bg'   => $blood_group,
                ':un'   => $units_needed,
                ':hosp' => $hospital,
                ':cont' => $contact,
                ':uid'  => $user_id,
            ]);
            $success = "Request submitted successfully! Admin will review it shortly.";
        }
    }
}
$stock_all = $pdo->query("SELECT * FROM blood_stock ORDER BY blood_group")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Blood - Blood Bank</title>
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
        .btn-logout:hover { color:var(--accent-crimson); }
        .layout { display:flex; min-height:calc(100vh - 60px); }
        .sidebar { width:240px; background:var(--panel-bg); border-right:1px solid var(--glass-border); padding:24px 0; flex-shrink:0; }
        .sidebar-label { font-size:10px; letter-spacing:3px; color:var(--text-secondary); padding:0 20px 10px; text-transform:uppercase; font-weight:600; }
        .sidebar a { display:flex; align-items:center; gap:12px; padding:12px 20px; color:var(--text-secondary); text-decoration:none; font-size:14px; font-weight:500; border-left:3px solid transparent; transition:all 0.2s; }
        .sidebar a:hover,.sidebar a.active { background:rgba(255,71,87,0.08); border-left-color:var(--accent-crimson); color:white; }
        .sidebar a i { width:18px; text-align:center; }
        .main { flex:1; padding:28px 32px; }
        .page-title { font-size:1.6rem; font-weight:700; margin-bottom:4px; }
        .page-sub { color:var(--text-secondary); font-size:13px; margin-bottom:28px; }
        .glass-card { background:var(--glass-bg); border:1px solid var(--glass-border); border-radius:14px; padding:28px; }
        .form-label { font-size:13px; font-weight:600; color:var(--text-secondary); margin-bottom:8px; display:block; text-transform:uppercase; letter-spacing:0.5px; }
        .form-control-custom { background:rgba(255,255,255,0.04); border:1px solid var(--glass-border); border-radius:10px; padding:12px 16px; color:white; font-size:14px; width:100%; font-family:'Outfit',sans-serif; transition:all 0.2s; }
        .form-control-custom:focus { background:rgba(255,255,255,0.07); border-color:var(--accent-crimson); outline:none; box-shadow:0 0 10px rgba(255,71,87,0.1); }
        .form-control-custom::placeholder { color:rgba(255,255,255,0.2); }
        select.form-control-custom option { background:#1a2030; color:white; }
        .btn-submit { background:linear-gradient(135deg,var(--primary-crimson),#e84118); border:none; color:white; padding:13px 28px; border-radius:10px; font-weight:600; font-size:15px; cursor:pointer; font-family:'Outfit',sans-serif; transition:all 0.2s; }
        .btn-submit:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(200,35,51,0.4); }
        .btn-cancel { background:rgba(255,255,255,0.05); border:1px solid var(--glass-border); color:var(--text-secondary); padding:13px 20px; border-radius:10px; font-size:14px; text-decoration:none; font-family:'Outfit',sans-serif; }
        .alert-error { background:rgba(220,53,69,0.12); border:1px solid rgba(220,53,69,0.2); color:var(--accent-crimson); padding:12px 16px; border-radius:10px; font-size:14px; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:rgba(40,167,69,0.12); border:1px solid rgba(40,167,69,0.25); color:#28a745; padding:12px 16px; border-radius:10px; font-size:14px; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .stock-ref { background:var(--glass-bg); border:1px solid var(--glass-border); border-radius:14px; padding:20px; }
        .stock-ref-title { font-size:12px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-secondary); margin-bottom:14px; }
        .stock-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.04); font-size:13px; }
        .stock-row:last-child { border-bottom:none; }
        .s-badge { font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; }
        .s-good { background:rgba(40,167,69,0.15); color:#28a745; }
        .s-low  { background:rgba(255,193,7,0.15);  color:#ffc107; }
        .s-out  { background:rgba(255,71,87,0.15);  color:var(--accent-crimson); }
        .mb-20 { margin-bottom:20px; }
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
        <a href="request-add.php" class="active"><i class="fa-solid fa-file-medical"></i> Request Blood</a>
        <a href="my-requests.php"><i class="fa-solid fa-list"></i> My Requests</a>
        <a href="register-donor.php"><i class="fa-solid fa-user-plus"></i> Become a Donor</a>
    </aside>
    <main class="main">
        <div class="page-title">📋 Request Blood</div>
        <div class="page-sub">Fill in the details below to submit a blood request</div>

        <?php if($error): ?><div class="alert-error"><i class="fa-solid fa-triangle-exclamation"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if($success): ?><div class="alert-success"><i class="fa-solid fa-circle-check"></i><?= htmlspecialchars($success) ?> <a href="my-requests.php" style="color:#28a745;font-weight:700;margin-left:8px;">View requests →</a></div><?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 280px;gap:24px;">
            <div class="glass-card">
                <form method="POST">
                    <div class="mb-20">
                        <label class="form-label">Patient Name</label>
                        <input type="text" name="patient_name" class="form-control-custom" placeholder="Full name of patient" required>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="mb-20">
                        <div>
                            <label class="form-label">Blood Group Needed</label>
                            <select name="blood_group" class="form-control-custom" required>
                                <option value="">-- Select --</option>
                                <?php foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg): ?>
                                    <option><?= $bg ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Units Needed</label>
                            <input type="number" name="units_needed" class="form-control-custom" min="1" placeholder="e.g. 2" required>
                        </div>
                    </div>
                    <div class="mb-20">
                        <label class="form-label">Hospital Name</label>
                        <input type="text" name="hospital" class="form-control-custom" placeholder="Hospital or clinic name" required>
                    </div>
                    <div class="mb-20">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact" class="form-control-custom" placeholder="03xx-xxxxxxx" required>
                    </div>
                    <div style="display:flex;gap:12px;align-items:center;">
                        <button type="submit" class="btn-submit"><i class="fa-solid fa-paper-plane me-2"></i>Submit Request</button>
                        <a href="dashboard.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
            <div class="stock-ref">
                <div class="stock-ref-title"><i class="fa-solid fa-droplet me-2" style="color:var(--accent-crimson);"></i>Current Stock</div>
                <?php foreach($stock_all as $s):
                    $u = $s['units'];
                    $c = $u==0?'s-out':($u<=5?'s-low':'s-good');
                    $l = $u==0?'None':($u<=5?'Low':'Good');
                ?>
                <div class="stock-row">
                    <strong><?= $s['blood_group'] ?></strong>
                    <span style="color:var(--text-secondary);"><?= $u ?> units</span>
                    <span class="s-badge <?= $c ?>"><?= $l ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
