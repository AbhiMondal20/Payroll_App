<?php
session_start();
ob_start();
include('db_conn.php');

$message = "";
$alertType = ""; // success | error | warning
$redirectToLogin = false;

// ---- CSRF helper ----
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function csrf_check($token)
{
    return isset($token) && hash_equals($_SESSION['csrf_token'], $token);
}

// ---- Get token from URL ----
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// ---- If POST: handle password reset submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $newPass = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

    // CSRF check
    if (!csrf_check($csrf)) {
        $message = "Invalid session. Please reload the page and try again.";
        $alertType = "error";
    } else {
        // Validate token presence
        if ($token === '') {
            $message = "Invalid or missing reset token.";
            $alertType = "error";
        } else {
            // Find user with this token and not expired
            $stmt = $conn->prepare("SELECT userid, dUSERNAME, email, reset_expires FROM dba WHERE reset_token = ? LIMIT 1");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows === 0) {
                $message = "This reset link is invalid. Please request a new one.";
                $alertType = "error";
            } else {
                $user = $res->fetch_assoc();

                // Check expiry
                $now = new DateTimeImmutable('now');
                $expires = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $user['reset_expires']);
                if (!$expires || $now > $expires) {
                    $message = "This reset link has expired. Please request a new one.";
                    $alertType = "warning";
                } else {
                    // Basic password validation (server-side)
                    // Rules: min 8 chars, include at least one letter & one number
                    $errors = [];
                    if (strlen($newPass) < 8) {
                        $errors[] = "Password must be at least 8 characters.";
                    }
                    if (!preg_match('/[A-Za-z]/', $newPass) || !preg_match('/\d/', $newPass)) {
                        $errors[] = "Password must include at least one letter and one number.";
                    }
                    if ($newPass !== $confirm) {
                        $errors[] = "Passwords do not match.";
                    }

                    if (!empty($errors)) {
                        $message = implode(" ", $errors);
                        $alertType = "error";
                    } else {
                        // Hash password and update
                        // $hash = password_hash($newPass, PASSWORD_DEFAULT);
                        // NOTE: Change `password` to your actual column name if different
                        $upd = $conn->prepare("UPDATE dba SET dPASSWORD = ?, reset_token = NULL, reset_expires = NULL WHERE userid = ?");
                        $upd->bind_param("si", $newPass, $user['userid']);
                        if ($upd->execute()) {
                            $message = "Your password has been reset successfully. Redirecting to login…";
                            $alertType = "success";
                            $redirectToLogin = true;
                        } else {
                            $message = "Could not update password. Please try again.";
                            $alertType = "error";
                        }
                    }
                }
            }
        }
    }
}

// ---- If GET: pre-validate token so we know whether to show the form ----
$tokenValid = false;
$userName = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($token !== '') {
        $stmt = $conn->prepare("SELECT dUSERNAME, reset_expires FROM dba WHERE reset_token = ? LIMIT 1");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $userName = $row['dUSERNAME'];
            $now = new DateTimeImmutable('now');
            $expires = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['reset_expires']);
            if ($expires && $now <= $expires) {
                $tokenValid = true;
            } else {
                $message = "This reset link has expired. Please request a new one.";
                $alertType = "warning";
            }
        } else {
            $message = "This reset link is invalid. Please request a new one.";
            $alertType = "error";
        }
    } else {
        $message = "Missing reset token. Please use the link from your email.";
        $alertType = "warning";
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="IE=edge" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Password - Rhythm E-Clinic</title>
    <link href="opd/admin/css/vendors_css.css" rel="stylesheet">
    <link href="opd/admin/css/style.css" rel="stylesheet">
    <script src="opd/admin/js/sweetalert.min.js"></script>
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <link rel="icon" href="opd/images/favicon.ico">

</head>

<body class="bg-img hold-transition theme-primary" style="background-image: url('opd/images/auth-bg/bg.webp');">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="bg-white rounded-3 shadow-lg p-4">
                <div class="text-center mb-4">
                    <img src="https://erp.sriscan.in/opd/images/logo-letter.png" alt="Rhythm E-Clinic"
                        style="max-width:120px; margin-bottom:10px;">
                    <h2 class="text-primary">Set a New Password</h2>
                    <?php if ($userName): ?>
                        <p>Hello <strong><?php echo htmlspecialchars($userName); ?></strong>, please create a new password.
                        </p>
                    <?php else: ?>
                        <p>Please create a new password.</p>
                    <?php endif; ?>
                </div>

                <?php if ($tokenValid): ?>
                    <form method="POST" autocomplete="off" novalidate>
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <input type="hidden" name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <!-- New Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required
                                    minlength="8" placeholder="Enter new password">
                                <button type="button" class="btn btn-outline-secondary" id="toggle1">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">
                                Minimum 8 characters, include at least one letter and one number.
                            </small>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                    required minlength="8" placeholder="Re-enter new password">
                                <button type="button" class="btn btn-outline-secondary" id="toggle2">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid mb-2">
                            <button type="submit" class="btn btn-success">Update Password</button>
                        </div>
                        <div class="text-center">
                            <a href="index" class="text-primary" style="text-decoration: underline;">Back to Login</a>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-center">
                        <a href="forgot-password" class="btn btn-primary">Request New Link</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($message)) { ?>
        <script>
            swal(
                "<?php echo ucfirst($alertType ?: 'Info'); ?>",
                "<?php echo addslashes($message); ?>",
                "<?php echo $alertType ?: 'info'; ?>"
            );
        </script>
    <?php } ?>

    <?php if ($redirectToLogin) { ?>
        <script>
            setTimeout(function () {
                window.location.href = "index";
            }, 2500);
        </script>
    <?php } ?>

    <script>
        function setupPasswordToggle(toggleId, inputId) {
            const toggleBtn = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            toggleBtn.addEventListener("click", () => {
                const icon = toggleBtn.querySelector("i");
                if (input.type === "password") {
                    input.type = "text";
                    icon.classList.remove("bi-eye");
                    icon.classList.add("bi-eye-slash");
                } else {
                    input.type = "password";
                    icon.classList.remove("bi-eye-slash");
                    icon.classList.add("bi-eye");
                }
            });
        }

        setupPasswordToggle("toggle1", "password");
        setupPasswordToggle("toggle2", "confirm_password");
    </script>
</body>

</html>