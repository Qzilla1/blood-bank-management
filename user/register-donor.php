<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../login.php"); exit();
}
require_once '../config/db.php';
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $name         = trim($_POST['name']         ?? '');
    $age          = (int)($_POST['age']          ?? 0);
    $gender       = trim($_POST['gender']        ?? '');
    $blood_group  = trim($_POST['blood_group']   ?? '');
    $email        = trim($_POST['email']         ?? '');
$phone        = trim($_POST['phone']         ?? '');
$last_donated = trim($_POST['last_donated'] ?? '');
$address      = trim($_POST['address']       ?? '');
    

    if (empty($name) || empty($gender) || empty($blood_group) || empty($email) || empty($phone) || empty($last_donated) || $age < 18) {
        $error = "Please fill in all fields. Age must be 18 or above.";
    } else {
        $ins = $pdo->prepare("INSERT INTO donors (name, age, gender, blood_group, email, phone, address, last_donation_date)
                       VALUES (:name, :age, :gender, :bg, :email, :phone, :address, :ld)");
$ins->execute([
    ':name'    => $name,
    ':age'     => $age,
    ':gender'  => $gender,
    ':bg'      => $blood_group,
    ':email'   => $email,
    ':phone'   => $phone,
    ':address' => $address,
    ':ld'      => $last_donated,
]);

        // Update blood stock +1
        $pdo->prepare("UPDATE blood_stock SET units = units + 1 WHERE blood_group = :bg")
    ->execute([':bg' => $blood_group]);

$success = "Thank you! You are now registered as a blood donor 🩸";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Donor - Blood Bank</title>
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
        .glass-card { background:var(--glass-bg); border:1px solid var(--glass-border); border-radius:14px; padding:28px; max-width:600px; }
        .form-label { font-size:12px; font-weight:600; color:var(--text-secondary); margin-bottom:8px; display:block; text-transform:uppercase; letter-spacing:0.5px; }
        .form-control-custom { background:rgba(255,255,255,0.04); border:1px solid var(--glass-border); border-radius:10px; padding:12px 16px; color:white; font-size:14px; width:100%; font-family:'Outfit',sans-serif; transition:all 0.2s; margin-bottom:18px; }
        .form-control-custom:focus { background:rgba(255,255,255,0.07); border-color:var(--accent-crimson); outline:none; box-shadow:0 0 10px rgba(255,71,87,0.1); }
        .form-control-custom::placeholder { color:rgba(255,255,255,0.2); }
        select.form-control-custom option { background:#1a2030; color:white; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .form-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
        .btn-submit { background:linear-gradient(135deg,var(--primary-crimson),#e84118); border:none; color:white; padding:13px 28px; border-radius:10px; font-weight:600; font-size:15px; cursor:pointer; font-family:'Outfit',sans-serif; transition:all 0.2s; }
        .btn-submit:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(200,35,51,0.4); }
        .btn-cancel { background:rgba(255,255,255,0.05); border:1px solid var(--glass-border); color:var(--text-secondary); padding:13px 20px; border-radius:10px; font-size:14px; text-decoration:none; font-family:'Outfit',sans-serif; }
        .alert-error { background:rgba(220,53,69,0.12); border:1px solid rgba(220,53,69,0.2); color:var(--accent-crimson); padding:12px 16px; border-radius:10px; font-size:14px; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:rgba(40,167,69,0.12); border:1px solid rgba(40,167,69,0.25); color:#28a745; padding:12px 16px; border-radius:10px; font-size:14px; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .info-box { background:rgba(255,71,87,0.06); border:1px solid rgba(255,71,87,0.15); border-radius:10px; padding:16px 20px; margin-top:20px; max-width:600px; }
        .info-box ul { margin:8px 0 0 16px; font-size:13px; color:var(--text-secondary); }
        .info-box li { margin-bottom:4px; }
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
        <a href="my-requests.php"><i class="fa-solid fa-list"></i> My Requests</a>
        <a href="register-donor.php" class="active"><i class="fa-solid fa-user-plus"></i> Become a Donor</a>
    </aside>
    <main class="main">
        <div class="page-title">🧑 Become a Donor</div>
        <div class="page-sub">Register yourself as a blood donor and help save lives</div>

        <?php if($error): ?><div class="alert-error"><i class="fa-solid fa-triangle-exclamation"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if($success): ?><div class="alert-success"><i class="fa-solid fa-circle-check"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div class="glass-card">
            <form method="POST">
                <div class="form-row">
                    <div>
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control-custom"
                               value="<?= htmlspecialchars($_SESSION['user_name']) ?>" required>
                    </div>
                    <div>
                        <label class="form-label">Age</label>
                        <input type="number" name="age" class="form-control-custom"
                               min="18" max="65" placeholder="18–65" required>
                    </div>
                </div>
                <div class="form-row-3">
                    <div>
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-control-custom" required>
                            <option value="">-- Select --</option>
                            <option>Male</option>
                            <option>Female</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group" class="form-control-custom" required>
                            <option value="">-- Select --</option>
                            <?php foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg): ?>
                                <option><?= $bg ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Last Donated</label>
                        <input type="date" name="last_donated" class="form-control-custom" required>
                    </div>
                </div>
                <div>
                    <label class="form-label">Contact Number</label>
                    <div>
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control-custom"
           placeholder="your@email.com" required>
</div>
<div>
    <label class="form-label">Phone Number</label>
    <input type="text" name="phone" class="form-control-custom"
           placeholder="03xx-xxxxxxx" required>
</div>
                </div>
                <div>
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control-custom" placeholder="Your city or area">
                </div>
                <div style="display:flex;gap:12px;align-items:center;">
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-heart me-2"></i>Register as Donor
                    </button>
                    <a href="dashboard.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>

        <div class="info-box">
            <strong style="font-size:13px;color:var(--accent-crimson);">⚠️ What happens when you register?</strong>
            <ul>
                <li>Your donor record is saved in the system</li>
                <li>Blood stock increases by 1 unit for your blood group</li>
                <li>You must wait 90 days between donations</li>
                <li>Eligible age: 18–65 years old</li>
            </ul>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
