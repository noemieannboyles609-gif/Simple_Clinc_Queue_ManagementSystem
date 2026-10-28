<?php
// includes/navbar.php
require_once __DIR__ . '/auth.php';
$currentPage = basename($_SERVER['PHP_SELF']);
$user = currentUser();
?>
<nav class="navbar" id="mainNavbar">
    <div class="nav-container">
        <a href="index.php" class="brand">
            <div class="brand-icon">🏥</div>
            <div class="brand-text">
                CareQueue
                <small>Clinic Queue System</small>
            </div>
        </a>

        <ul class="nav-links">
            <li>
                <a href="index.php" class="nav-link <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
                    <span>🏠</span> Overview
                </a>
            </li>
            <li>
                <a href="kiosk.php" class="nav-link <?= ($currentPage == 'kiosk.php') ? 'active' : '' ?>">
                    <span>🎟️</span> Patient Kiosk
                </a>
            </li>
            <li>
                <a href="display.php" class="nav-link <?= ($currentPage == 'display.php') ? 'active' : '' ?>" target="_blank" title="Open TV Display Board in new tab">
                    <span>📺</span> TV Board ↗
                </a>
            </li>
            <li>
                <a href="staff.php" class="nav-link <?= ($currentPage == 'staff.php') ? 'active' : '' ?>">
                    <span>👨‍⚕️</span> Staff Station
                </a>
            </li>
            <li>
                <a href="admin.php" class="nav-link <?= ($currentPage == 'admin.php') ? 'active' : '' ?>">
                    <span>📊</span> Admin Dashboard
                </a>
            </li>
            <li>
                <a href="history.php" class="nav-link <?= ($currentPage == 'history.php') ? 'active' : '' ?>">
                    <span>📜</span> History
                </a>
            </li>
        </ul>

        <div class="nav-controls">
            <button class="audio-btn" onclick="App.toggleAudio()" title="Toggle Voice/Sound Announcements">
                🔊
            </button>

            <!-- User Auth Profile Badge or Sign In Button -->
            <?php if ($user): ?>
                <div style="display: flex; align-items: center; gap: 0.65rem; background: rgba(255, 255, 255, 0.06); border: 1px solid var(--border-color); border-radius: var(--radius-full); padding: 0.25rem 0.5rem 0.25rem 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; color: #fff; font-weight: 600;">
                        <span>👤</span>
                        <span style="max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($user['full_name']) ?></span>
                    </div>
                    <?php if ($user['role'] === 'Administrator'): ?>
                        <span class="badge" style="background: rgba(139, 92, 246, 0.25); color: var(--purple-light); font-size: 0.7rem; padding: 0.15rem 0.5rem;">Admin</span>
                    <?php elseif ($user['role'] === 'Clinic Staff'): ?>
                        <span class="badge badge-waiting" style="font-size: 0.7rem; padding: 0.15rem 0.5rem;">Staff</span>
                    <?php else: ?>
                        <span class="badge badge-regular" style="font-size: 0.7rem; padding: 0.15rem 0.5rem;">Patient</span>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-outline btn-sm" style="padding: 0.2rem 0.55rem; font-size: 0.75rem; border-radius: var(--radius-full);" title="Sign Out">
                        Sign Out
                    </a>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-sm" style="border-radius: var(--radius-full); font-size: 0.82rem; padding: 0.35rem 0.85rem;">
                    <span>🔐</span> Sign In
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>
