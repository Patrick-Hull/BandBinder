<?php
require_once __DIR__ . '/../lib/util_all.php';
$pageName = "Forgot Password";

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        $db = new DatabaseManager();
        $result = $db->query("SELECT * FROM `users` WHERE `email` = ?", [$email]);
        
        if (empty($result)) {
            $success = true;
        } else {
            $user = $result[0];
            
            if (empty($user['password'])) {
                $error = 'This account does not have a password set. Please contact your administrator to send a welcome email.';
            } else {
                $configResult = $db->query("SELECT config_value FROM site_config WHERE config_key = 'password_reset_enabled'");
                $passwordResetEnabled = $configResult && count($configResult) > 0 ? $configResult[0]['config_value'] : '0';
                
                if ($passwordResetEnabled !== '1') {
                    $error = 'Password reset is not enabled on this site. Please contact your administrator.';
                } else {
                    $token = bin2hex(random_bytes(32));
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    $tokenId = Helper::UUIDv4();
                    $db->query(
                        "INSERT INTO `password_reset_tokens` (`id`, `user_id`, `token`, `expires_at`) VALUES (?, ?, ?, ?)",
                        [$tokenId, $user['id'], $token, $expiresAt]
                    );

                    $mailResult = $db->query("SELECT config_value FROM site_config WHERE config_key = 'mail_settings'");
                    if ($mailResult && count($mailResult) > 0) {
                        $mailConfig = json_decode($mailResult[0]['config_value'], true);
                        
                        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                        $resetUrl = $baseUrl . '/reset-password.php?token=' . $token;

                        $subject = 'BandBinder - Password Reset';
                        $body = '
                        <html>
                        <head>
                            <style>
                                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; line-height: 1.6; color: #333; }
                                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                .card { background: #f8f9fa; border-radius: 8px; padding: 30px; margin-top: 20px; }
                                .btn { display: inline-block; padding: 12px 24px; background: #0d6efd; color: #fff; text-decoration: none; border-radius: 6px; margin-top: 20px; }
                                .footer { margin-top: 30px; font-size: 12px; color: #666; }
                            </style>
                        </head>
                        <body>
                            <div class="container">
                                <h2>Password Reset Request</h2>
                                <p>Hello,</p>
                                <p>We received a request to reset your BandBinder password. Click the button below to create a new password:</p>
                                <div class="card">
                                    <a href="' . $resetUrl . '" class="btn">Reset Password</a>
                                </div>
                                <p>This link will expire in 1 hour.</p>
                                <p>If you did not request a password reset, please ignore this email.</p>
                                <div class="footer">
                                    <p>If the button does not work, copy and paste this link into your browser:<br>' . $resetUrl . '</p>
                                </div>
                            </div>
                        </body>
                        </html>
                        ';

                        Mail::send($user['email'], $subject, $body);
                    }
                    
                    $success = true;
                }
            }
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
        .forgot-wrap {
            width: 100%;
            max-width: 400px;
        }
        .forgot-brand {
            text-align: center;
            margin-bottom: 1.75rem;
        }
        .forgot-brand .brand-icon-wrap {
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
        .forgot-brand .brand-icon-wrap i {
            font-size: 2rem;
            color: #fff;
        }
        .forgot-brand h1 {
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 0.2rem;
        }
        .forgot-brand .tagline {
            font-size: 0.85rem;
            opacity: 0.6;
        }
        .forgot-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.10);
            padding: 2rem;
        }
        .forgot-card .form-floating > .form-control {
            border-radius: 10px;
        }
        .forgot-card .btn-forgot {
            border-radius: 10px;
            padding: .65rem;
            font-weight: 600;
            font-size: 1rem;
        }
        .forgot-footer {
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.78rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>

<div class="forgot-wrap">

    <div class="forgot-brand">
        <div class="brand-icon-wrap">
            <i class="bi bi-key"></i>
        </div>
        <h1>Reset Password</h1>
        <div class="tagline">Enter your email to receive a reset link</div>
    </div>

    <div class="card forgot-card">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <p>If an account with that email exists and password reset is enabled, you will receive a password reset link shortly.</p>
                <p>Please check your email.</p>
            </div>
            <div class="d-grid">
                <a href="/login.php" class="btn btn-primary btn-forgot">Back to Login</a>
            </div>
        <?php else: ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form id="forgotPasswordForm" method="POST">
                <div class="form-floating mb-4">
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="Email" autocomplete="email">
                    <label for="email">Email Address</label>
                </div>

                <div class="d-grid">
                    <button class="btn btn-primary btn-forgot" type="submit" id="forgotPasswordBtn">
                        Send Reset Link
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <div class="forgot-footer">
        <a href="/login.php" class="text-decoration-none" style="color: inherit;">Back to Login</a>
    </div>
</div>

<?php require_once __DIR__ . '/../lib/html_footer/all.php'; ?>

</body>
</html>