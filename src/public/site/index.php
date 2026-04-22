<?php
require_once __DIR__ . '/../../lib/util_all.php';
$pageName = "Site Management";
$canManageSiteConfig = in_array('siteconfig', $_SESSION['user']['permissions']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $pageName; ?></title>
    <?php require_once __DIR__ . '/../../lib/html_header/all.php'; ?>
    <?php require_once __DIR__ . '/../../lib/html_header/tomselect.php'; ?>
</head>
<body>
    <?php require_once __DIR__ . '/../../lib/navbar.php'; ?>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-8 mx-auto">
                <?php if(!$canManageSiteConfig): ?>
                    <div class="alert alert-danger">You do not have permission to manage site settings.</div>
                <?php else: ?>
                    <h1>Site Management</h1>
                    
                    <ul class="nav nav-tabs" id="siteConfigTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="mail-tab" data-bs-toggle="tab" data-bs-target="#mailConfig" type="button" role="tab">Mail Configuration</button>
                        </li>
                    </ul>
                    
                    <div class="tab-content mt-3" id="siteConfigContent">
                        <div class="tab-pane fade show active" id="mailConfig" role="tabpanel">
                            <h5 class="mb-3">Mail Configuration</h5>
                            <form id="mailConfigForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="protocol" class="form-label">Protocol</label>
                                        <select class="form-select" id="protocol">
                                            <option value="smtp">SMTP</option>
                                            <option value="mailgun">Mailgun</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div id="smtpFields">
                                    <h6 class="text-muted mb-3">SMTP Settings</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="smtp_host" class="form-label">SMTP Host <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="smtp_host" placeholder="e.g. smtp.example.com">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="smtp_port" class="form-label">Port <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="smtp_port" placeholder="e.g. 587">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="smtp_username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="smtp_username" placeholder="Username">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="smtp_password" class="form-label">Password</label>
                                            <input type="password" class="form-control" id="smtp_password" placeholder="Password">
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="mailgunFields" style="display: none;">
                                    <h6 class="text-muted mb-3">Mailgun Settings</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="mailgun_api_key" class="form-label">API Key <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="mailgun_api_key" placeholder="API Key">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="mailgun_domain" class="form-label">Domain <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="mailgun_domain" placeholder="e.g. sandbox123.mailgun.org">
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <h6 class="text-muted mb-3">From Address</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="from_email" class="form-label">From Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="from_email" placeholder="e.g. no-reply@example.com">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="from_name" class="form-label">From Name</label>
                                        <input type="text" class="form-control" id="from_name" placeholder="e.g. BandBinder">
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">Save Configuration</button>
                                    <button type="button" class="btn btn-outline-secondary ms-2" id="testSmtpBtn">Send Test Email</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php require_once __DIR__ . '/../../lib/html_footer/all.php'; ?>
</body>
</html>

<script>
    $(function() {
        // Toggle fields based on protocol
        $('#protocol').on('change', function() {
            if ($(this).val() === 'smtp') {
                $('#smtpFields').show();
                $('#mailgunFields').hide();
            } else {
                $('#smtpFields').hide();
                $('#mailgunFields').show();
            }
        });

        // Load existing configuration
        $.ajax({
            type: 'POST',
            url: 'lib/action.php',
            data: { action: 'getSiteConfig' },
            dataType: 'JSON',
            success: function(resp) {
                if (resp.success && resp.config) {
                    const config = resp.config;
                    $('#protocol').val(config.protocol).trigger('change');
                    $('#smtp_host').val(config.smtp_host || '');
                    $('#smtp_port').val(config.smtp_port || '');
                    $('#smtp_username').val(config.smtp_username || '');
                    $('#smtp_password').val(config.smtp_password || '');
                    $('#mailgun_api_key').val(config.mailgun_api_key || '');
                    $('#mailgun_domain').val(config.mailgun_domain || '');
                    $('#from_email').val(config.from_email || '');
                    $('#from_name').val(config.from_name || '');
                }
            },
            error: function(xhr) {
                toastr.error('Failed to load site configuration.');
            }
        });

        // Save configuration
        $('#mailConfigForm').on('submit', function(e) {
            e.preventDefault();
            const config = {
                protocol: $('#protocol').val(),
                smtp_host: $('#smtp_host').val(),
                smtp_port: $('#smtp_port').val(),
                smtp_username: $('#smtp_username').val(),
                smtp_password: $('#smtp_password').val(),
                mailgun_api_key: $('#mailgun_api_key').val(),
                mailgun_domain: $('#mailgun_domain').val(),
                from_email: $('#from_email').val(),
                from_name: $('#from_name').val()
            };
            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: { action: 'updateSiteConfig', config: config },
                dataType: 'JSON',
                success: function() {
                    toastr.success('Site configuration saved successfully.');
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to save site configuration.');
                }
            });
        });

        // Test SMTP connection
        $('#testSmtpBtn').on('click', function() {
            const btn = $(this);
            btn.prop('disabled', true).text('Testing...');
            
            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: { action: 'testSmtpConfig' },
                dataType: 'JSON',
                success: function(resp) {
                    if (resp.success) {
                        toastr.success(resp.message);
                    } else {
                        toastr.error(resp.message);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to test SMTP connection.');
                },
                complete: function() {
                    btn.prop('disabled', false).text('Send Test Email');
                }
            });
        });
    });
</script>