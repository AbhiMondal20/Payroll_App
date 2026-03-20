<?php
session_start();
include('db_conn.php');
include('function.php');

$mobile = $_SESSION['otp_mobile'] ?? null;
if (!$mobile) {
    $_SESSION['error'] = "Start password reset process first.";
    header("Location: forgot-password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    if (empty($otp)) {
        $_SESSION['error'] = "Enter OTP.";
        header("Location: verify-otp.php");
        exit();
    }

    // Fetch latest unused OTP for this mobile
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE mobile = ? AND used = 0 ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $mobile);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if (new DateTime() > new DateTime($row['expires_at'])) {
            $_SESSION['error'] = "OTP expired. Request again.";
            header("Location: forgot-password.php");
            exit();
        }
        if (password_verify($otp, $row['otp_hash'])) {
            // Mark used and allow reset
            $stmt2 = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
            $stmt2->bind_param("i", $row['id']);
            $stmt2->execute();
            $stmt2->close();

            $_SESSION['reset_user_id'] = $row['user_id'];
            write_log($conn, $row['user_id'], null, 'otp_verified', 'OTP verified for reset', 'success');
            header("Location: reset-password.php");
            exit();
        } else {
            write_log($conn, $row['user_id'], null, 'otp_failed', 'Invalid OTP attempt', 'failed');
            $_SESSION['error'] = "Invalid OTP.";
            header("Location: verify-otp.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "OTP not found. Request again.";
        header("Location: forgot-password.php");
        exit();
    }
}
?>
<!doctype html>
<html>
<head><title>Verify OTP</title><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script></head>
<body>
<?php if (isset($_SESSION['error'])): ?>
<script>Swal.fire("Error!", "<?php echo addslashes($_SESSION['error']); ?>", "error");</script>
<?php unset($_SESSION['error']); endif; ?>
<form method="POST" action="">
  <label>Enter OTP</label>
  <input type="text" name="otp" required />
  <button type="submit">Verify OTP</button>
</form>
</body>
</html>
