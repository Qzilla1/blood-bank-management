<?php
/**
 * User Registration Page
 * Allows new users to create an account.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    // Basic validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match!";
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);

            if ($stmt->fetch()) {
                $error = "This email is already registered. Try logging in.";
            } else {
                // Hash password using BCrypt (same as admin)
                $hashed = password_hash($password, PASSWORD_BCRYPT);

                $insert = $pdo->prepare("INSERT INTO users (name, email, password, role)
                                         VALUES (:name, :email, :password, 'user')");
                $insert->execute([
                    ':name'     => $name,
                    ':email'    => $email,
                    ':password' => $hashed,
                ]);

                $success = "Account created successfully! You can now log in.";
            }
        } catch (PDOException $e) {
            $error = "Database error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Blood Bank System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-crimson: #c82333;
            --accent-crimson:  #ff4757;
            --dark-indigo:     #0b0e14;
            --glass-bg:        rgba(18, 24, 36, 0.7);
            --glass-border:    rgba(255, 255, 255, 0.08);
            --text-primary:    #ffffff;
            --text-secondary:  #8a99ad;
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

        .bg-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            z-index: -1;
            opacity: 0.15;
            pointer-events: none;
        }
        .blob-1 {
            width: 400px; height: 400px;
            background-color: var(--accent-crimson);
            top: -100px; left: -100px;
            animation: move-1 25s infinite alternate ease-in-out;
        }
        .blob-2 {
            width: 350px; height: 350px;
            background-color: #3b82f6;
            bottom: -50px; right: -50px;
            animation: move-2 20s infinite alternate ease-in-out;
        }
        @keyframes move-1 {
            0%   { transform: translate(0, 0) scale(1); }
            100% { transform: translate(100px, 80px) scale(1.2); }
        }
        @keyframes move-2 {
            0%   { transform: translate(0, 0) scale(1); }
            100% { transform: translate(-80px, -120px) scale(1.1); }
        }

        .register-card {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 45px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,.6);
            position: relative;
            z-index: 1;
        }

        .register-card:hover {
            border-color: rgba(255, 71, 87, 0.2);
        }

        .card-glow-overlay {
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            border-radius: 22px;
            background: linear-gradient(135deg, var(--accent-crimson), transparent, transparent, rgba(59,130,246,0.5));
            z-index: -2;
            opacity: 0.3;
        }

        .brand-logo-container {
            width: 70px; height: 70px;
            background: rgba(200, 35, 51, 0.1);
            border: 2px solid var(--primary-crimson);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 0 20px rgba(200,35,51,0.2);
            position: relative;
        }

        .brand-logo-container i {
            font-size: 32px;
            color: var(--accent-crimson);
            animation: heartbeat 1.5s infinite ease-in-out;
        }

        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            25%       { transform: scale(1.15); }
            45%       { transform: scale(1.05); }
            60%       { transform: scale(1.15); }
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
            0%   { transform: scale(1);   opacity: 0.5; }
            100% { transform: scale(1.4); opacity: 0;   }
        }

        h2 {
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            letter-spacing: -0.5px;
            margin-bottom: 5px;
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 14px;
            text-align: center;
            margin-bottom: 28px;
        }

        .form-label {
            font-weight: 500;
            font-size: 14px;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 18px;
        }

        .input-group-custom i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 15px;
            z-index: 10;
        }

        .form-control-custom {
            background-color: rgba(255,255,255,0.04);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 13px 16px 13px 46px;
            color: white;
            font-size: 15px;
            width: 100%;
            transition: all 0.3s ease;
            font-family: 'Outfit', sans-serif;
        }

        .form-control-custom:focus {
            background-color: rgba(255,255,255,0.08);
            border-color: var(--accent-crimson);
            outline: none;
            box-shadow: 0 0 12px rgba(255,71,87,0.15);
        }

        .form-control-custom::placeholder {
            color: rgba(255,255,255,0.25);
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
            box-shadow: 0 4px 15px rgba(200,35,51,0.3);
            margin-top: 8px;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
        }

        .btn-crimson:hover {
            background: linear-gradient(135deg, #e84118, var(--primary-crimson));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(200,35,51,0.45);
        }

        .btn-crimson:active {
            transform: translateY(1px);
        }

        .alert-custom {
            background: rgba(220,53,69,0.12);
            border: 1px solid rgba(220,53,69,0.2);
            color: var(--accent-crimson);
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success-custom {
            background: rgba(25,135,84,0.12);
            border: 1px solid rgba(25,135,84,0.25);
            color: #5cb85c;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-link {
            text-align: center;
            margin-top: 18px;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .login-link a {
            color: var(--accent-crimson);
            font-weight: 600;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <!-- Background Blobs -->
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <div class="register-card">
        <div class="card-glow-overlay"></div>

        <div class="brand-logo-container">
            <i class="fa-solid fa-droplet"></i>
        </div>

        <h2>Create Account</h2>
        <div class="subtitle">Join the Lifeline Bank as a User</div>

        <!-- Error Message -->
        <?php if (!empty($error)): ?>
            <div class="alert-custom">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <!-- Success Message -->
        <?php if (!empty($success)): ?>
            <div class="alert-success-custom">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <?= htmlspecialchars($success) ?>
                    <a href="login.php" style="color:#5cb85c;font-weight:700;">
                        Login now →
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" autocomplete="off">

            <!-- Full Name -->
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <div class="input-group-custom">
                    <input type="text" name="name" class="form-control-custom"
                           placeholder="Ali Raza" required
                           value="<?= isset($name) ? htmlspecialchars($name) : '' ?>">
                    <i class="fa-regular fa-user"></i>
                </div>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <div class="input-group-custom">
                    <input type="email" name="email" class="form-control-custom"
                           placeholder="you@email.com" required
                           value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
                    <i class="fa-regular fa-envelope"></i>
                </div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-group-custom">
                    <input type="password" name="password" class="form-control-custom"
                           placeholder="Min. 6 characters" required>
                    <i class="fa-solid fa-lock"></i>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <div class="input-group-custom">
                    <input type="password" name="confirm" class="form-control-custom"
                           placeholder="Repeat your password" required>
                    <i class="fa-solid fa-lock"></i>
                </div>
            </div>

            <button type="submit" class="btn-crimson">
                <i class="fa-solid fa-user-plus me-2"></i> Create Account
            </button>

        </form>

        <div class="login-link">
            Already have an account?
            <a href="login.php">Login here</a>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
