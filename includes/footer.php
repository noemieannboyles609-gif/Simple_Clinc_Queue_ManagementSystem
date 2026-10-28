<?php
// includes/footer.php
?>
<footer style="background: rgba(11, 17, 32, 0.95); border-top: 1px solid var(--border-color); padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.85rem; margin-top: auto;">
    <div style="max-width: 1440px; margin: 0 auto; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem;">
        <div>
            <strong style="color: #fff;">CareQueue Clinic</strong> &bull; Simple Clinic Queue Management System
            <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.2rem;">
                Demonstration system featuring fictional patient data only.
            </div>
        </div>

        <div class="live-clock" style="font-size: 0.85rem; color: var(--text-secondary);"></div>

        <div style="display: flex; gap: 1rem; font-size: 0.8rem;">
            <a href="kiosk.php">Patient Kiosk</a>
            <a href="display.php" target="_blank">TV Board</a>
            <a href="staff.php">Staff Console</a>
            <a href="admin.php">Admin Panel</a>
        </div>
    </div>
</footer>

<!-- Shared JavaScript Core -->
<script src="assets/js/app.js"></script>
</body>
</html>
