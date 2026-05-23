<?php
/**
 * Database Connection Configuration using PDO
 * Optimised for local XAMPP hosting environments
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'blood_bank');

try {
    // Establish connection using PDO with UTF-8 support
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // If database connection fails, render a high-end styled diagnostic page instead of a raw stack trace.
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Error - Blood Bank Management</title>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            :root {
                --crimson: #dc3545;
                --dark-indigo: #0b0e14;
                --text-muted: #8a99ad;
                --glass-bg: rgba(255, 255, 255, 0.03);
                --glass-border: rgba(255, 255, 255, 0.08);
            }
            body {
                background-color: var(--dark-indigo);
                color: #ffffff;
                font-family: 'Outfit', sans-serif;
                height: 100vh;
                margin: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }
            .error-card {
                background: var(--glass-bg);
                backdrop-filter: blur(20px);
                border: 1px solid var(--glass-border);
                border-radius: 16px;
                padding: 40px;
                max-width: 600px;
                width: 90%;
                text-align: center;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
                position: relative;
            }
            .error-card::before {
                content: '';
                position: absolute;
                top: -2px;
                left: -2px;
                right: -2px;
                bottom: -2px;
                border-radius: 18px;
                background: linear-gradient(135deg, var(--crimson), transparent, transparent);
                z-index: -1;
            }
            .icon-wrapper {
                width: 80px;
                height: 80px;
                background: rgba(220, 53, 69, 0.1);
                border: 2px dashed var(--crimson);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 24px;
                animation: pulse 2s infinite ease-in-out;
            }
            .icon-wrapper i {
                color: var(--crimson);
                font-size: 36px;
            }
            h1 {
                font-size: 26px;
                margin-bottom: 12px;
                font-weight: 700;
            }
            p {
                color: var(--text-muted);
                line-height: 1.6;
                font-size: 15px;
                margin-bottom: 24px;
            }
            .steps-box {
                background: rgba(0, 0, 0, 0.2);
                border-radius: 8px;
                padding: 20px;
                text-align: left;
                margin-bottom: 24px;
                font-size: 14px;
                border: 1px solid var(--glass-border);
            }
            .steps-box h3 {
                margin-top: 0;
                margin-bottom: 12px;
                font-size: 15px;
                color: #ffffff;
            }
            .steps-box ol {
                margin: 0;
                padding-left: 20px;
                color: var(--text-muted);
            }
            .steps-box li {
                margin-bottom: 8px;
                line-height: 1.4;
            }
            .steps-box code {
                background: rgba(255, 255, 255, 0.1);
                padding: 2px 6px;
                border-radius: 4px;
                font-family: monospace;
                color: #e83e8c;
            }
            .btn-retry {
                background: var(--crimson);
                color: white;
                border: none;
                padding: 12px 28px;
                border-radius: 30px;
                font-weight: 600;
                font-size: 14px;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-block;
            }
            .btn-retry:hover {
                background: #c82333;
                box-shadow: 0 0 15px rgba(220, 53, 69, 0.4);
                transform: translateY(-2px);
            }
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
        </style>
    </head>
    <body>
        <div class="error-card">
            <div class="icon-wrapper">
                <i class="fa-solid fa-database"></i>
            </div>
            <h1>Database Connection Error</h1>
            <p>We are unable to establish a connection to your MySQL database server. The database <code>blood_bank</code> might not exist or the local SQL server might be offline.</p>
            
            <div class="steps-box">
                <h3><i class="fa-solid fa-circle-info"></i> How to resolve this issue:</h3>
                <ol>
                    <li>Make sure your local server environment (like <strong>XAMPP</strong> or WampServer) is running.</li>
                    <li>Ensure the <strong>MySQL Service</strong> is activated in the XAMPP Control Panel.</li>
                    <li>Open <strong>phpMyAdmin</strong> (usually <code>http://localhost/phpmyadmin</code>).</li>
                    <li>Create a new database named exactly <code>blood_bank</code>.</li>
                    <li>Import the SQL schema file located at: <br><code>blood-bank-system/database/blood_bank.sql</code></li>
                </ol>
            </div>
            
            <button onclick="window.location.reload();" class="btn-retry">
                <i class="fa-solid fa-arrows-rotate"></i> Retry Connection
            </button>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>
