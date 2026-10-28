<?php
// login.php - Authentication Portal for Administrator, Staff, and Patients
$pageTitle = 'Sign In';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect based on role
if (isLoggedIn()) {
    $user = currentUser();
    if ($user['role'] === 'Administrator') {
        header('Location: admin.php');
    } elseif ($user['role'] === 'Clinic Staff') {
        header('Location: staff.php');
    } else {
        header('Location: kiosk.php');
    }
    exit;
}

$error = '';
$redirect = $_GET['redirect'] ?? '';

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = trim($_POST['redirect'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Find user by username or email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user) {
            // Verify password (hash or raw demo fallback)
            if (password_verify($password, $user['password']) || $password === 'admin123' || $password === 'doctor123' || $password === 'nurse123' || $password === 'patient123') {
                loginUser($user);

                // Determine redirect target
                if (!empty($redirect) && !str_contains($redirect, 'login.php')) {
                    header("Location: $redirect");
                } elseif ($user['role'] === 'Administrator') {
                    header('Location: admin.php');
                } elseif ($user['role'] === 'Clinic Staff') {
                    $counterParam = !empty($user['counter_id']) ? "?counter_id={$user['counter_id']}" : "";
                    header("Location: staff.php$counterParam");
                } else {
                    header('Location: kiosk.php');
                }
                exit;
            } else {
                $error = 'Incorrect password. Please try again.';
            }
        } else {
            $error = 'No user account found with that username or email.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | CareQueue Clinic System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
        }

        .login-card {
            background: rgba(18, 26, 43, 0.88);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 480px;
            box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.7);
            padding: 2.25rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--cyan), var(--success));
        }

        .quick-demo-chip {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 0.65rem 0.85rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
        }

        .quick-demo-chip:hover {
            border-color: var(--primary);
            background: rgba(14, 165, 233, 0.12);
            transform: translateY(-1px);
        }

        .input-group {
            position: relative;
        }

        .input-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1rem;
            transition: var(--transition);
        }

        .input-toggle-btn:hover {
            color: #fff;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">
            
            <!-- Branding Header -->
            <div style="text-align: center; margin-bottom: 1.75rem;">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 54px; height: 54px; background: linear-gradient(135deg, var(--primary), var(--cyan)); border-radius: var(--radius-lg); font-size: 1.75rem; margin-bottom: 0.75rem; box-shadow: 0 8px 24px var(--primary-glow);">
                    🏥
                </div>
                <h1 style="font-size: 1.75rem; font-weight: 800; color: #fff; letter-spacing: -0.02em;">CareQueue Clinic</h1>
                <p style="font-size: 0.88rem; color: var(--text-secondary); margin-top: 0.25rem;">
                    Simple Clinic Queue Management System
                </p>
                
                <div style="margin-top: 0.65rem;">
                    <span class="live-pill">
                        <span class="pulse-dot"></span>
                        <span>Sign In to Continue</span>
                    </span>
                </div>
            </div>

            <!-- Flash Alert Messages -->
            <?php if (!empty($error)): ?>
                <div style="background: rgba(244, 63, 94, 0.15); border: 1px solid var(--danger); border-radius: var(--radius-md); padding: 0.75rem 1rem; margin-bottom: 1.25rem; font-size: 0.88rem; color: var(--danger-light); display: flex; align-items: center; gap: 0.6rem;">
                    <span>⚠️</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['logged_out'])): ?>
                <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); border-radius: var(--radius-md); padding: 0.75rem 1rem; margin-bottom: 1.25rem; font-size: 0.88rem; color: var(--success-light); display: flex; align-items: center; gap: 0.6rem;">
                    <span>✅</span>
                    <span>You have been successfully logged out.</span>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['unauthorized'])): ?>
                <div style="background: rgba(245, 158, 11, 0.15); border: 1px solid var(--warning); border-radius: var(--radius-md); padding: 0.75rem 1rem; margin-bottom: 1.25rem; font-size: 0.88rem; color: var(--warning-light); display: flex; align-items: center; gap: 0.6rem;">
                    <span>🔒</span>
                    <span>Please sign in with appropriate role permissions to access that page.</span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="login.php" id="loginForm">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                <div class="form-group">
                    <label class="form-label" for="username">Username or Email</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter username (e.g. admin or doctor)" required autofocus>
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.45rem;">
                        <label class="form-label" for="password" style="margin-bottom: 0;">Password</label>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">Default demo: role + 123</span>
                    </div>
                    <div class="input-group">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
                        <button type="button" class="input-toggle-btn" onclick="togglePasswordVisibility()" title="Show/Hide Password" id="togglePassBtn">
                            👁️
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.5rem; margin-bottom: 1.5rem;" id="btnLogin">
                    <span>🔐</span> Sign In
                </button>
            </form>

            <!-- Quick Demo Accounts One-Click Picker -->
            <div style="background: rgba(11, 17, 32, 0.6); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1rem; margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                    <span style="font-size: 0.78rem; text-transform: uppercase; font-weight: 700; color: var(--text-secondary); letter-spacing: 0.05em;">
                        ⚡ Quick Demo Logins
                    </span>
                    <span style="font-size: 0.75rem; color: var(--primary-light);">Click to fill & login</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <div class="quick-demo-chip" onclick="quickFill('admin', 'admin123')">
                        <div>
                            <strong style="color: #fff;">👑 Administrator</strong>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">admin &bull; admin123</div>
                        </div>
                        <span class="badge" style="background: rgba(139, 92, 246, 0.2); color: var(--purple-light);">Admin Panel</span>
                    </div>

                    <div class="quick-demo-chip" onclick="quickFill('doctor', 'doctor123')">
                        <div>
                            <strong style="color: #fff;">👨‍⚕️ Clinic Staff / Doctor</strong>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">doctor &bull; doctor123 (Room 101)</div>
                        </div>
                        <span class="badge badge-waiting">Calling Station</span>
                    </div>

                    <div class="quick-demo-chip" onclick="quickFill('patient', 'patient123')">
                        <div>
                            <strong style="color: #fff;">🎟️ Patient Profile</strong>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">patient &bull; patient123</div>
                        </div>
                        <span class="badge badge-regular">Ticket Kiosk</span>
                    </div>
                </div>
            </div>

            <!-- Alternative Quick Navigation -->
            <div style="text-align: center; border-top: 1px solid var(--border-color); padding-top: 1.25rem; font-size: 0.85rem; color: var(--text-secondary); display: flex; flex-direction: column; gap: 0.6rem;">
                <div>
                    <span>Testing walk-in patient flow? </span>
                    <a href="kiosk.php" style="color: var(--primary-light); font-weight: 600;">Open Patient Kiosk as Guest &rarr;</a>
                </div>
                <div>
                    <a href="display.php" target="_blank" style="color: var(--text-muted);">Launch Public TV Display Board ↗</a>
                    <span style="color: var(--border-color); margin: 0 0.5rem;">|</span>
                    <a href="index.php" style="color: var(--text-muted);">&larr; Return to Overview</a>
                </div>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/app.js"></script>
    <script>
        function togglePasswordVisibility() {
            const passInput = document.getElementById('password');
            const toggleBtn = document.getElementById('togglePassBtn');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                toggleBtn.textContent = '🔒';
            } else {
                passInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        function quickFill(user, pass) {
            document.getElementById('username').value = user;
            document.getElementById('password').value = pass;
            App.showToast(`Selected demo credentials for ${user}! Signing in...`, 'info');
            setTimeout(() => {
                document.getElementById('loginForm').submit();
            }, 300);
        }
    </script>
</body>
</html>
