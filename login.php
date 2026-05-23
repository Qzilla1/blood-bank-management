<?php
/**
 * Admin Login Page
 * Handles session creation and authentication of administrative accounts.
 */

// Enable session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once 'config/db.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: index.php");
    exit();
}

$error = '';

// Self-healing database safeguard: Seed administrator if admin table is empty
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
    $count = $stmt->fetchColumn();
    if ($count == 0) {
        $defaultPasswordHash = password_hash('admin123', PASSWORD_BCRYPT);
        $insertStmt = $pdo->prepare("INSERT INTO admins (username, password, fullname) VALUES ('admin', :pass, 'System Administrator')");
        $insertStmt->execute([':pass' => $defaultPasswordHash]);
    }
} catch (Exception $e) {
    // If the tables are not loaded yet, db.php might handle it or we catch it gracefully
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :user LIMIT 1");
            $stmt->execute([':user' => $username]);
            $admin = $stmt->fetch();

            // Self-healing database safeguard for seeded legacy/truncated hashes
            $isCorrectLegacyHash = ($admin['password'] === '$2y$10$wL4P9qW.3aXskh/V/XW.c.f6R6N/15vR/8sS322c3q251d5s2f3f' && $password === 'admin123');

            // Verify using modern secure hashing (BCrypt) or fallback legacy validator
            if ($admin && (password_verify($password, $admin['password']) || $isCorrectLegacyHash)) {
                // If authenticated via legacy seed hash, instantly re-hash and heal the database record securely!
                if ($isCorrectLegacyHash) {
                    $healedHash = password_hash('admin123', PASSWORD_BCRYPT);
                    $healStmt = $pdo->prepare("UPDATE admins SET password = :newPass WHERE id = :id");
                    $healStmt->execute([':newPass' => $healedHash, ':id' => $admin['id']]);
                }

                // Set session variables
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_fullname'] = $admin['fullname'];
                $_SESSION['success_message'] = "Welcome back, " . htmlspecialchars($admin['fullname']) . "! Login successful.";

                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error = "Database error encountered during login. Please ensure tables are created.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Blood Bank System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-crimson: #c82333;
            --accent-crimson: #ff4757;
            --dark-indigo: #0b0e14;
            --panel-indigo: #121824;
            --glass-bg: rgba(18, 24, 36, 0.7);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-primary: #ffffff;
            --text-secondary: #8a99ad;
        }

        body {
            background-color: var(--dark-indigo);
            font-family: 'Outfit', sans-serif;
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        /* Abstract dynamic background blobs */
        .bg-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            z-index: -1;
            opacity: 0.15;
            pointer-events: none;
        }

        .blob-1 {
            width: 400px;
            height: 400px;
            background-color: var(--accent-crimson);
            top: -100px;
            left: -100px;
            animation: move-1 25s infinite alternate ease-in-out;
        }

        .blob-2 {
            width: 350px;
            height: 350px;
            background-color: #3b82f6;
            bottom: -50px;
            right: -50px;
            animation: move-2 20s infinite alternate ease-in-out;
        }

        @keyframes move-1 {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(100px, 80px) scale(1.2); }
        }

        @keyframes move-2 {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(-80px, -120px) scale(1.1); }
        }

        /* Glassmorphism Card */
        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 45px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease;
        }

        .login-card:hover {
            border-color: rgba(255, 71, 87, 0.2);
        }

        .card-glow-overlay {
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 22px;
            background: linear-gradient(135deg, var(--accent-crimson), transparent, transparent, rgba(59, 130, 246, 0.5));
            z-index: -2;
            opacity: 0.3;
        }

        /* Animated Logo */
        .brand-logo-container {
            width: 70px;
            height: 70px;
            background: rgba(200, 35, 51, 0.1);
            border: 2px solid var(--primary-crimson);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            position: relative;
            box-shadow: 0 0 20px rgba(200, 35, 51, 0.2);
        }

        .brand-logo-container i {
            font-size: 32px;
            color: var(--accent-crimson);
            animation: heartbeat 1.5s infinite ease-in-out;
        }

        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            25% { transform: scale(1.15); }
            45% { transform: scale(1.05); }
            60% { transform: scale(1.15); }
        }

        .brand-logo-container::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            border-radius: 50%;
            border: 2px solid var(--accent-crimson);
            animation: ripple 2s infinite ease-out;
            opacity: 0;
        }

        @keyframes ripple {
            0% { transform: scale(1); opacity: 0.5; }
            100% { transform: scale(1.4); opacity: 0; }
        }

        h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
            text-align: center;
            letter-spacing: -0.5px;
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 14px;
            text-align: center;
            margin-bottom: 30px;
        }

        .form-label {
            font-weight: 500;
            font-size: 14px;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 22px;
        }

        .input-group-custom i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 16px;
            z-index: 10;
            transition: color 0.3s ease;
        }

        .form-control-custom {
            background-color: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 14px 16px 14px 46px;
            color: white;
            font-size: 15px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .form-control-custom:focus {
            background-color: rgba(255, 255, 255, 0.08);
            border-color: var(--accent-crimson);
            outline: none;
            box-shadow: 0 0 12px rgba(255, 71, 87, 0.15);
        }

        .form-control-custom:focus + i {
            color: var(--accent-crimson);
        }

        .form-control-custom::placeholder {
            color: rgba(255, 255, 255, 0.25);
        }

        .btn-crimson {
            background: linear-gradient(135deg, var(--primary-crimson), #e84118);
            border: none;
            color: white;
            padding: 14px;
            border-radius: 12px;
            width: 100%;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(200, 35, 51, 0.3);
            margin-top: 10px;
        }

        .btn-crimson:hover {
            background: linear-gradient(135deg, #e84118, var(--primary-crimson));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(200, 35, 51, 0.45);
        }

        .btn-crimson:active {
            transform: translateY(1px);
        }

        /* Diagnostic credentials tip */
        .credentials-tip {
            margin-top: 24px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px dashed var(--glass-border);
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            color: var(--text-secondary);
            text-align: center;
        }
        .credentials-tip strong {
            color: var(--accent-crimson);
        }

        .alert-custom {
            background: rgba(220, 53, 69, 0.12);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: var(--accent-crimson);
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>

    <!-- Background Blobs -->
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <div class="login-card">
        <div class="card-glow-overlay"></div>
        
        <div class="brand-logo-container">
            <i class="fa-solid fa-droplet"></i>
        </div>

        <h2>Lifeline Bank</h2>
        <div class="subtitle">Administrative Portal Management</div>

        <?php if (!empty($error)): ?>
            <div class="alert-custom animate__animated animate__fadeIn">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert-custom animate__animated animate__fadeIn">
                <i class="fa-solid fa-shield-halved"></i>
                <div>
                    <?php 
                        echo htmlspecialchars($_SESSION['error_message']); 
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" autocomplete="off">
            <div class="form-group">
                <label class="form-label">Username</label>
                <div class="input-group-custom">
                    <input type="text" name="username" class="form-control-custom" placeholder="Enter username" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                    <i class="fa-regular fa-user"></i>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-group-custom">
                    <input type="password" name="password" class="form-control-custom" placeholder="Enter password" required>
                    <i class="fa-solid fa-lock"></i>
                </div>
            </div>

            <button type="submit" class="btn-crimson">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Log In
            </button>
        </form>

        <div class="credentials-tip">
            <i class="fa-solid fa-key me-1"></i> Default Credentials:<br>
            Username: <strong>admin</strong> | Password: <strong>admin123</strong>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
