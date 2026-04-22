<?php
require_once __DIR__ . '/../../lib/util_all.php';
$pageName = "Profile";

$userId = $_SESSION['user']['me'];
$user = new User($userId);
$is2FAEnabled = $user->getTotpEnabled();

$db = new DatabaseManager();
$configResult = $db->query("SELECT config_value FROM site_config WHERE config_key = '2fa_mandatory'");
$twoFactorMandatory = $configResult && count($configResult) > 0 && $configResult[0]['config_value'] === '1';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $pageName; ?></title>
    <?php require_once __DIR__ . '/../../lib/html_header/all.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/../../lib/navbar.php'; ?>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12 col-md-8 col-xl-6 mx-auto">
                <h1>My Profile</h1>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Account Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3 fw-bold">Username:</div>
                            <div class="col-md-9"><?php echo htmlspecialchars($user->getUsername()); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3 fw-bold">Email:</div>
                            <div class="col-md-9"><?php echo htmlspecialchars($user->getEmail()); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3 fw-bold">Display Name:</div>
                            <div class="col-md-9"><?php echo htmlspecialchars($user->getNameShort() ?? ''); ?></div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 fw-bold">Password:</div>
                            <div class="col-md-9">
                                <?php if ($user->hasPassword()): ?>
                                    <span class="badge bg-success">Has Password</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">No Password Set</span>
                                <?php endif; ?>
                                <?php if (!$user->hasPassword()): ?>
                                    <a href="/set-password.php?welcome=1" class="btn btn-sm btn-primary ms-2">Set Password</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Two-Factor Authentication</h5>
                        <?php if ($twoFactorMandatory): ?>
                            <span class="badge bg-danger">Required</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($is2FAEnabled): ?>
                            <p>Two-factor authentication is currently <strong>enabled</strong> on your account.</p>
                            <p class="text-muted small">Your account is protected with a time-based one-time password (TOTP).</p>
                            <button class="btn btn-danger" id="disable2faBtn">Disable 2FA</button>
                        <?php else: ?>
                            <p>Two-factor authentication adds an extra layer of security to your account.</p>
                            <p class="text-muted small">When enabled, you'll need to enter a code from your authenticator app (like Google Authenticator, Authy, or 1Password) along with your password.</p>
                            <?php if ($twoFactorMandatory): ?>
                                <div class="alert alert-warning">
                                    <strong>2FA is required</strong> on this site. You must enable two-factor authentication to continue using your account.
                                </div>
                            <?php endif; ?>
                            <button class="btn btn-primary" id="enable2faBtn">Enable 2FA</button>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Enable 2FA Modal -->
    <div class="modal fade" id="enable2faModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enable Two-Factor Authentication</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Scan this QR code with your authenticator app (Google Authenticator, Authy, 1Password, etc.):</p>
                    <div class="text-center mb-3">
                        <img id="qrCodeImage" src="" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                    </div>
                    <p class="text-center">Or enter this secret key manually:</p>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control text-center font-monospace" id="totpSecret" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="copySecretBtn">Copy</button>
                    </div>
                    <hr>
                    <p>Enter a verification code from your authenticator app to confirm setup:</p>
                    <div class="mb-3">
                        <input type="text" class="form-control text-center" id="verify2faCode" placeholder="000000" maxlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmEnable2faBtn">Enable 2FA</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Disable 2FA Modal -->
    <div class="modal fade" id="disable2faModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Disable Two-Factor Authentication</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to disable two-factor authentication?</p>
                    <p class="text-muted small">This will make your account less secure.</p>
                    <div class="mb-3">
                        <label for="disable2faPassword" class="form-label">Enter your password to confirm:</label>
                        <input type="password" class="form-control" id="disable2faPassword">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDisable2faBtn">Disable 2FA</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../../lib/html_footer/all.php'; ?>
</body>
</html>

<script>
    let tempTotpSecret = null;

    $(function() {
        // Enable 2FA
        $('#enable2faBtn').on('click', function() {
            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: { action: 'setup2FA' },
                dataType: 'JSON',
                success: function(resp) {
                    tempTotpSecret = resp.secret;
                    $('#qrCodeImage').attr('src', resp.qrCodeUrl);
                    $('#totpSecret').val(resp.secret);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('enable2faModal')).show();
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to set up 2FA.');
                }
            });
        });

        $('#copySecretBtn').on('click', function() {
            navigator.clipboard.writeText($('#totpSecret').val());
            toastr.success('Secret copied to clipboard!');
        });

        $('#confirmEnable2faBtn').on('click', function() {
            const code = $('#verify2faCode').val().trim();
            if (!code || code.length !== 6) {
                toastr.error('Please enter a valid 6-digit code.');
                return;
            }
            
            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: { action: 'enable2FA', secret: tempTotpSecret, code: code },
                dataType: 'JSON',
                success: function() {
                    bootstrap.Modal.getInstance(document.getElementById('enable2faModal')).hide();
                    toastr.success('Two-factor authentication enabled successfully!');
                    setTimeout(() => window.location.reload(), 1000);
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to enable 2FA. Please check your code and try again.');
                }
            });
        });

        // Disable 2FA
        $('#disable2faBtn').on('click', function() {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('disable2faModal')).show();
        });

        $('#confirmDisable2faBtn').on('click', function() {
            const password = $('#disable2faPassword').val();
            if (!password) {
                toastr.error('Please enter your password.');
                return;
            }
            
            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: { action: 'disable2FA', password: password },
                dataType: 'JSON',
                success: function() {
                    bootstrap.Modal.getInstance(document.getElementById('disable2faModal')).hide();
                    toastr.success('Two-factor authentication disabled successfully!');
                    setTimeout(() => window.location.reload(), 1000);
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to disable 2FA. Please check your password.');
                }
            });
        });

        // Clear form on modal close
        $('#enable2faModal').on('hidden.bs.modal', function() {
            $('#verify2faCode').val('');
            tempTotpSecret = null;
        });

        $('#disable2faModal').on('hidden.bs.modal', function() {
            $('#disable2faPassword').val('');
        });
    });
</script>
