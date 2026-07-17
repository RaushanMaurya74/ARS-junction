<?php
$page_title = "Global Settings";
require_once 'admin_header.php';

$success_msg = '';
$error_msg = '';

// Handle POST setting updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'site_name' => trim($_POST['site_name']),
        'site_email' => trim($_POST['site_email']),
        'site_phone' => trim($_POST['site_phone']),
        'site_location' => trim($_POST['site_location']),
        'currency_symbol' => trim($_POST['currency_symbol']),
        'upi_id' => trim($_POST['upi_id']),
        'delivery_fee_default' => number_format((float)$_POST['delivery_fee_default'], 2, '.', ''),
        'tax_rate_default' => number_format((float)$_POST['tax_rate_default'], 2, '.', ''),
        'facebook_app_id' => trim($_POST['facebook_app_id']),
        'facebook_app_secret' => trim($_POST['facebook_app_secret']),
        'google_client_id' => trim($_POST['google_client_id']),
        'google_client_secret' => trim($_POST['google_client_secret']),
        'facebook_login_enabled' => isset($_POST['facebook_login_enabled']) ? '1' : '0',
        'google_login_enabled' => isset($_POST['google_login_enabled']) ? '1' : '0',
        'smtp_host' => trim($_POST['smtp_host']),
        'smtp_port' => trim($_POST['smtp_port']),
        'smtp_username' => trim($_POST['smtp_username']),
        'smtp_password' => trim($_POST['smtp_password']),
        'smtp_encryption' => trim($_POST['smtp_encryption'])
    ];
    
    $failed = false;
    foreach ($settings as $key => $val) {
        if (!update_site_setting($key, $val)) {
            $failed = true;
        }
    }
    
    if (!$failed) {
        $success_msg = "Global settings updated successfully.";
    } else {
        $error_msg = "Failed to update some settings. Please try again.";
    }
}

// Load current setting values
$site_name = get_site_setting('site_name', 'ARS Junction');
$site_email = get_site_setting('site_email', 'officialarsjunction@gmail.com');
$site_phone = get_site_setting('site_phone', '7979730721');
$site_location = get_site_setting('site_location', 'AT - PIRO, BHOJPUR, BIHAR, INDIA-802207');
$currency_symbol = get_site_setting('currency_symbol', '₹');
$upi_id = get_site_setting('upi_id', '7979730721@rapl');
$delivery_fee_default = get_site_setting('delivery_fee_default', '50.00');
$tax_rate_default = get_site_setting('tax_rate_default', '5.00');
$facebook_app_id = get_site_setting('facebook_app_id', 'YOUR_FACEBOOK_APP_ID');
$facebook_app_secret = get_site_setting('facebook_app_secret', 'YOUR_FACEBOOK_APP_SECRET');
$google_client_id = get_site_setting('google_client_id', 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com');
$google_client_secret = get_site_setting('google_client_secret', 'YOUR_GOOGLE_CLIENT_SECRET');
$facebook_login_enabled = get_site_setting('facebook_login_enabled', '1');
$google_login_enabled = get_site_setting('google_login_enabled', '1');
$smtp_host = get_site_setting('smtp_host', 'smtp.gmail.com');
$smtp_port = get_site_setting('smtp_port', '465');
$smtp_username = get_site_setting('smtp_username', 'officialarsjunction@gmail.com');
$smtp_password = get_site_setting('smtp_password', '');
$smtp_encryption = get_site_setting('smtp_encryption', 'ssl');
?>

<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4 text-gray-800"><i class="fas fa-cogs text-primary me-2"></i> Global Site Configuration</h1>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="post" action="settings.php">
        <div class="row">
            <!-- General Settings Card -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-sliders-h me-2"></i> General Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="site_name" class="form-label font-weight-bold small text-muted">Site Brand Name</label>
                            <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="site_email" class="form-label font-weight-bold small text-muted">Contact Support Email</label>
                            <input type="email" class="form-control" id="site_email" name="site_email" value="<?php echo htmlspecialchars($site_email); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="site_phone" class="form-label font-weight-bold small text-muted">Contact Phone Number</label>
                            <input type="text" class="form-control" id="site_phone" name="site_phone" value="<?php echo htmlspecialchars($site_phone); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="site_location" class="form-label font-weight-bold small text-muted">Site Physical Location</label>
                            <input type="text" class="form-control" id="site_location" name="site_location" value="<?php echo htmlspecialchars($site_location); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="currency_symbol" class="form-label font-weight-bold small text-muted">Currency Symbol</label>
                            <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="<?php echo htmlspecialchars($currency_symbol); ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial & Payment Settings Card -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-wallet me-2"></i> Payment & Policies</h6>
                    </div>
                    <div class="card-body">
                        <!-- UPI Payee address -->
                        <div class="mb-3">
                            <label for="upi_id" class="form-label font-weight-bold small text-muted">Receiver UPI VPA Address (Payee ID)</label>
                            <input type="text" class="form-control" id="upi_id" name="upi_id" value="<?php echo htmlspecialchars($upi_id); ?>" placeholder="e.g. 7979730721@rapl" required>
                            <div class="form-text text-info small">
                                <i class="fas fa-info-circle me-1"></i> Changing this updates all generated payment QR codes on client invoices and delivery panel screens instantly.
                            </div>
                        </div>
                        
                        <hr class="my-3">
                        
                        <div class="mb-3">
                            <label for="delivery_fee_default" class="form-label font-weight-bold small text-muted">Default Delivery Fee (<?php echo htmlspecialchars($currency_symbol); ?>)</label>
                            <input type="number" step="0.01" class="form-control" id="delivery_fee_default" name="delivery_fee_default" value="<?php echo htmlspecialchars($delivery_fee_default); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tax_rate_default" class="form-label font-weight-bold small text-muted">Default Tax Rate (%)</label>
                            <input type="number" step="0.01" class="form-control" id="tax_rate_default" name="tax_rate_default" value="<?php echo htmlspecialchars($tax_rate_default); ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social API Keys Card -->
            <div class="col-12 mb-4">
                <div class="card shadow border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-key me-2"></i> Social Authentication API Keys</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4 border-bottom pb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="facebook_login_enabled" name="facebook_login_enabled" value="1" <?php echo ($facebook_login_enabled == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label font-weight-bold text-dark" for="facebook_login_enabled">Enable Facebook Login</label>
                                    <div class="text-muted small">Toggle to show or hide the Facebook login button on login/register screens.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="google_login_enabled" name="google_login_enabled" value="1" <?php echo ($google_login_enabled == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label font-weight-bold text-dark" for="google_login_enabled">Enable Google Login</label>
                                    <div class="text-muted small">Toggle to show or hide the Google sign-in options.</div>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="facebook_app_id" class="form-label font-weight-bold small text-muted">Facebook App ID</label>
                                <input type="text" class="form-control" id="facebook_app_id" name="facebook_app_id" value="<?php echo htmlspecialchars($facebook_app_id); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="facebook_app_secret" class="form-label font-weight-bold small text-muted">Facebook App Secret</label>
                                <input type="password" class="form-control" id="facebook_app_secret" name="facebook_app_secret" value="<?php echo htmlspecialchars($facebook_app_secret); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="google_client_id" class="form-label font-weight-bold small text-muted">Google Client ID</label>
                                <input type="text" class="form-control" id="google_client_id" name="google_client_id" value="<?php echo htmlspecialchars($google_client_id); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="google_client_secret" class="form-label font-weight-bold small text-muted">Google Client Secret</label>
                                <input type="password" class="form-control" id="google_client_secret" name="google_client_secret" value="<?php echo htmlspecialchars($google_client_secret); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SMTP Settings Card -->
            <div class="col-12 mb-4">
                <div class="card shadow border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-envelope-open-text me-2"></i> SMTP Email Server Configuration</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="smtp_host" class="form-label font-weight-bold small text-muted">SMTP Server Host</label>
                                <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($smtp_host); ?>" placeholder="e.g. smtp.gmail.com" required>
                            </div>
                            <div class="col-md-3">
                                <label for="smtp_port" class="form-label font-weight-bold small text-muted">SMTP Server Port</label>
                                <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($smtp_port); ?>" placeholder="e.g. 465 or 587" required>
                            </div>
                            <div class="col-md-3">
                                <label for="smtp_encryption" class="form-label font-weight-bold small text-muted">SMTP Security Encryption</label>
                                <select class="form-select" id="smtp_encryption" name="smtp_encryption" required>
                                    <option value="ssl" <?php echo ($smtp_encryption === 'ssl') ? 'selected' : ''; ?>>SSL (Port 465)</option>
                                    <option value="tls" <?php echo ($smtp_encryption === 'tls') ? 'selected' : ''; ?>>TLS (Port 587)</option>
                                    <option value="none" <?php echo ($smtp_encryption === 'none') ? 'selected' : ''; ?>>None</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="smtp_username" class="form-label font-weight-bold small text-muted">SMTP Username / Account Email</label>
                                <input type="email" class="form-control" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars($smtp_username); ?>" placeholder="e.g. officialarsjunction@gmail.com" required>
                            </div>
                            <div class="col-md-6">
                                <label for="smtp_password" class="form-label font-weight-bold small text-muted">SMTP App Password</label>
                                <input type="password" class="form-control" id="smtp_password" name="smtp_password" value="<?php echo htmlspecialchars($smtp_password); ?>" placeholder="Enter 16-character App Password">
                                <div class="form-text text-info small">
                                    <i class="fas fa-question-circle me-1"></i> For Gmail, generate a 16-character App Password under your Google Account Security tab.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12 text-end mb-4">
                <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold shadow-sm">
                    <i class="fas fa-save me-2"></i> Save Configurations
                </button>
            </div>
        </div>
    </form>
</div>

<?php
require_once 'admin_footer.php';
?>
