<?php
require_once __DIR__ . '/../lib/util_all.php';
$pageName = "Reset Password";

$token = $_GET['token'] ?? '';
$error = '';
$success = false;

if (empty($token)) {
    $error = 'Invalid or missing token.';
} else {
    $db = new DatabaseManager();
    $result = $db->query(
        "SELECT * FROM `password_reset_tokens` WHERE `token` = ? AND `used_at` IS NULL AND `expires_at` > NOW()",
        [$token]
    );

    if (empty($result)) {
        $error = 'Invalid or expired token. Please request a new password reset.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $token = $_POST['token'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $db = new DatabaseManager();
        $result = $db->query(
            "SELECT * FROM `password_reset_tokens` WHERE `token` = ? AND `used_at` IS NULL AND `expires_at` > NOW()",
            [$token]
        );

        if (!empty($result)) {
            $tokenRow = $result[0];
            $userId = $tokenRow['user_id'];
            
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $db->query("UPDATE `users` SET `password` = ? WHERE `id` = ?", [$hashedPassword, $userId]);
            
            $db->query(
                "UPDATE `password_reset_tokens` SET `used_at` = NOW() WHERE `id` = ?",
                [$tokenRow['id']]
            );

            $success = true;
        } else {
            $error = 'Invalid or expired token. Please request a new password reset.';
        }
    }
}
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
        .password-wrap {
            width: 100%;
            max-width: 400px;
        }
        .password-brand {
            text-align: center;
            margin-bottom: 1.75rem;
        }
        .password-brand .brand-icon-wrap {
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
        .password-brand .brand-icon-wrap i {
            font-size: 2rem;
            color: #fff;
        }
        .password-brand h1 {
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 0.2rem;
        }
        .password-brand .tagline {
            font-size: 0.85rem;
            opacity: 0.6;
        }
        .password-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.10);
            padding: 2rem;
        }
        .password-card .form-floating > .form-control {
            border-radius: 10px;
        }
        .password-card .btn-reset-password {
            border-radius: 10px;
            padding: .65rem;
            font-weight: 600;
            font-size: 1rem;
        }
    </style>
</head>
<body>

<div class="password-wrap">

    <?php if ($success): ?>
    <div class="password-brand">
        <div class="brand-icon-wrap">
            <i class="bi bi-check-lg"></i>
        </div>
        <h1>Password Reset!</h1>
        <div class="tagline">Your password has been successfully reset.</div>
    </div>
    <div class="card password-card">
        <p>You can now log in with your new password.</p>
        <div class="d-grid">
            <a href="/login.php" class="btn btn-primary btn-reset-password">Go to Login</a>
        </div>
    </div>
    <?php else: ?>
    <div class="password-brand">
        <div class="brand-icon-wrap">
            <i class="bi bi-key"></i>
        </div>
        <h1>Reset Your Password</h1>
        <div class="tagline">Enter your new password below</div>
    </div>

    <div class="card password-card">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form id="resetPasswordForm" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password"
                       placeholder="Password" autocomplete="new-password" <?php echo empty($token) ? 'disabled' : ''; ?>>
                <label for="password">New Password</label>
            </div>

            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="confirmPassword" name="confirm_password"
                       placeholder="Confirm Password" autocomplete="new-password" <?php echo empty($token) ? 'disabled' : ''; ?>>
                <label for="confirmPassword">Confirm New Password</label>
            </div>

            <div class="d-grid">
                <button class="btn btn-primary btn-reset-password" type="submit" id="resetPasswordBtn" <?php echo empty($token) ? 'disabled' : ''; ?>>
                    Reset Password
                </button>
            </div>
        </form>
    </div>

    <div class="text-center mt-3">
        <a href="/login.php" class="text-decoration-none">Back to Login</a>
    </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../lib/html_footer/all.php'; ?>

</body>
</html>
