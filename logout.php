<?php
// logout.php - Sign out handler
require_once __DIR__ . '/includes/auth.php';

logoutUser();
header('Location: login.php?logged_out=1');
exit;
