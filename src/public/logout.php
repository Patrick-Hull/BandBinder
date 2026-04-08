<?php
require_once __DIR__ . "/../lib/util_all.php";
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Logged Out — BandBinder</title>
    <?php require_once __DIR__ . '/../lib/html_header/bootstrap.php'; ?>
    <style>
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bb-login-bg, #f0f2f5);
            padding: 1rem;
        }
        [data-bs-theme="dark"] {
            --bb-login-bg: #1a1d21;
        }
        .logout-wrap {
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .logout-icon-wrap {
            width: 64px;
            height: 64px;
            background: #198754;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 4px 16px rgba(25,135,84,.30);
        }
        .logout-icon-wrap i {
            font-size: 2rem;
            color: #fff;
        }
        .logout-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.10);
            padding: 2rem;
            margin-bottom: 1rem;
        }
        .countdown-ring {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid #198754;
            font-weight: 700;
            font-size: 1rem;
            color: #198754;
            margin-bottom: .5rem;
        }
    </style>
</head>
<body>

<div class="logout-wrap">

    <div class="logout-icon-wrap">
        <i class="bi bi-box-arrow-left"></i>
    </div>

    <div class="card logout-card">
        <h2 class="fw-700 mb-1" style="font-weight:700">Logged Out</h2>
        <p class="text-muted mb-3">You have been successfully signed out of BandBinder.</p>

        <div class="countdown-ring" id="countdownNum">5</div>
        <div class="text-muted small mb-3">Redirecting to login in <strong id="countdownText">5</strong> seconds…</div>

        <a href="/login.php" class="btn btn-primary w-100" style="border-radius:10px;font-weight:600">
            <i class="bi bi-arrow-left me-1"></i> Back to Login
        </a>
    </div>

    <div style="font-size:.78rem;opacity:.5">BandBinder &copy; <?php echo date('Y'); ?></div>
</div>

<script>
(function () {
    var n = 5;
    var ring = document.getElementById('countdownNum');
    var txt  = document.getElementById('countdownText');
    var iv   = setInterval(function () {
        n--;
        ring.textContent = n;
        txt.textContent  = n;
        if (n <= 0) {
            clearInterval(iv);
            window.location.href = '/login.php';
        }
    }, 1000);
})();
</script>

</body>
</html>
