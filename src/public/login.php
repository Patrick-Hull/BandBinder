<?php
require_once __DIR__ . '/../lib/util_all.php';
$pageName = "Login";
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
            max-width: 400px;
        }
        .login-brand {
            text-align: center;
            margin-bottom: 1.75rem;
        }
        .login-brand .brand-icon-wrap {
            width: 64px;
            height: 64px;
            background: #0d6efd;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
            box-shadow: 0 4px 16px rgba(13,110,253,.35);
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
        .login-card .btn-login {
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
        <div class="tagline">Your band's music library</div>
    </div>

    <!-- Card -->
    <div class="card login-card">
        <form id="loginForm" action="lib/login.php" autocomplete="on">

            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username"
                       placeholder="Username" autocomplete="username">
                <label for="username"><i class="bi bi-person me-1"></i>Username</label>
            </div>

            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="password" name="password"
                       placeholder="Password" autocomplete="current-password">
                <label for="password"><i class="bi bi-lock me-1"></i>Password</label>
            </div>

            <div class="d-grid">
                <button class="btn btn-primary btn-login" type="submit" id="loginBtn">
                    Sign In
                </button>
            </div>

        </form>
    </div>

    <div class="login-footer">BandBinder &copy; <?php echo date('Y'); ?></div>
</div>

<?php require_once __DIR__ . '/../lib/html_footer/all.php'; ?>

<script>
    $("#loginForm").on("submit", function (e) {
        e.preventDefault();
        const btn = $("#loginBtn");
        btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2"></span>Signing in…');

        $.ajax({
            type: "POST",
            url: $(this).attr("action"),
            data: $(this).serialize(),
            dataType: "JSON",
            success: function () {
                window.location.href = "/";
            },
            error: function (xhr) {
                btn.prop("disabled", false).text("Sign In");
                const r = xhr.responseJSON;
                toastr.error(r?.message || "Login failed. Please try again.");
            }
        });
    });
</script>
</body>
</html>
