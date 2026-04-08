<?php
require_once __DIR__ . '/../lib/util_all.php';

// If users already exist, setup is complete — redirect to login
try {
    $db = new DatabaseManager();
    $userCount = (int)($db->query("SELECT COUNT(*) as cnt FROM `users`")[0]['cnt'] ?? 0);
    if ($userCount > 0) {
        header("Location: /login.php");
        exit;
    }
} catch (Exception $e) {
    // Table doesn't exist yet (migrations pending) — show the setup form anyway
}

$pageName = "Setup";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $pageName; ?> — BandBinder</title>
    <?php require_once __DIR__ . '/../lib/html_header/all.php'; ?>
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
        [data-bs-theme="dark"] body,
        [data-bs-theme="dark"] {
            --bb-login-bg: #1a1d21;
        }
        .login-wrap {
            width: 100%;
            max-width: 440px;
        }
        .login-brand {
            text-align: center;
            margin-bottom: 1.75rem;
        }
        .login-brand .brand-icon-wrap {
            width: 64px;
            height: 64px;
            background: #198754;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
            box-shadow: 0 4px 16px rgba(25,135,84,.35);
        }
        .login-brand .brand-icon-wrap i {
            font-size: 2rem;
            color: #fff;
        }
        .login-brand h1 {
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 0.2rem;
        }
        .login-brand .tagline {
            font-size: 0.85rem;
            opacity: 0.6;
        }
        .login-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.10);
            padding: 2rem;
        }
        .login-card .form-floating > .form-control {
            border-radius: 10px;
        }
        .login-card .btn-setup {
            border-radius: 10px;
            padding: .65rem;
            font-weight: 600;
            font-size: 1rem;
        }
        .login-footer {
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.78rem;
            opacity: 0.5;
        }
        .setup-notice {
            background: rgba(25,135,84,.1);
            border-left: 3px solid #198754;
            border-radius: 8px;
            padding: .75rem 1rem;
            font-size: .85rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>

<div class="login-wrap">

    <!-- Brand -->
    <div class="login-brand">
        <div class="brand-icon-wrap">
            <i class="bi bi-music-note-beamed"></i>
        </div>
        <h1>BandBinder</h1>
        <div class="tagline">Initial Setup</div>
    </div>

    <!-- Card -->
    <div class="card login-card">

        <div class="setup-notice">
            <i class="bi bi-shield-lock me-1"></i>
            Create your administrator account. This account will have full access to all features.
        </div>

        <form id="setupForm" action="lib/setup.php" autocomplete="off">

            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username"
                       placeholder="Username" autocomplete="off" required>
                <label for="username"><i class="bi bi-person me-1"></i>Username</label>
            </div>

            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="nameShort" name="nameShort"
                       placeholder="Display Name" autocomplete="off">
                <label for="nameShort"><i class="bi bi-badge-cc me-1"></i>Display Name <span class="text-muted">(optional)</span></label>
            </div>

            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="email" name="email"
                       placeholder="Email" autocomplete="off" required>
                <label for="email"><i class="bi bi-envelope me-1"></i>Email</label>
            </div>

            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password"
                       placeholder="Password" autocomplete="new-password" required>
                <label for="password"><i class="bi bi-lock me-1"></i>Password</label>
            </div>

            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="confirm" name="confirm"
                       placeholder="Confirm Password" autocomplete="new-password" required>
                <label for="confirm"><i class="bi bi-lock-fill me-1"></i>Confirm Password</label>
            </div>

            <div class="d-grid">
                <button class="btn btn-success btn-setup" type="submit" id="setupBtn">
                    Create Administrator Account
                </button>
            </div>

        </form>
    </div>

    <div class="login-footer">BandBinder &copy; <?php echo date('Y'); ?></div>
</div>

<?php require_once __DIR__ . '/../lib/html_footer/all.php'; ?>

<script>
    $("#setupForm").on("submit", function (e) {
        e.preventDefault();

        const password = $("#password").val();
        const confirm  = $("#confirm").val();

        if (password !== confirm) {
            toastr.error("Passwords do not match.");
            return;
        }
        if (password.length < 8) {
            toastr.error("Password must be at least 8 characters.");
            return;
        }

        const btn = $("#setupBtn");
        btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2"></span>Creating account…');

        $.ajax({
            type: "POST",
            url: $(this).attr("action"),
            data: $(this).serialize(),
            dataType: "JSON",
            success: function () {
                toastr.success("Account created! Redirecting to login…");
                setTimeout(function () {
                    window.location.href = "/login.php";
                }, 1500);
            },
            error: function (xhr) {
                btn.prop("disabled", false).text("Create Administrator Account");
                const r = xhr.responseJSON;
                toastr.error(r?.message || "Setup failed. Please try again.");
            }
        });
    });
</script>
</body>
</html>
