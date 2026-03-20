<?php
session_start();
include('db_conn.php');
include('function.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    if (empty($username)) {
        $_SESSION['error'] = 'Enter your username';
        header("Location: forgot-password.php");
        exit();
    }

    // Find user
    $stmt = $conn->prepare("SELECT id, UNAME, mobile FROM dba WHERE dUSERNAME = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $user_id = (int)$row['id'];
        $mobile = $row['mobile'];

        // Generate numeric 6-digit OTP
        $otp = random_int(100000, 999999);
        $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Save to password_resets
        $stmt2 = $conn->prepare("INSERT INTO password_resets (user_id, mobile, otp_hash, expires_at) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("isss", $user_id, $mobile, $otp_hash, $expires);
        $stmt2->execute();
        $stmt2->close();

        // Send SMS
        $message = "Your OTP for password reset is: {$otp}. It expires in 10 minutes.";
        $smsResult = send_sms($mobile, $message);

        // Log
        write_log($conn, $user_id, null, 'otp_requested', "OTP requested for reset", 'success');

        if ($smsResult['success']) {
            $_SESSION['info'] = "OTP sent to your mobile number.";
            // Store a temporary identifier to verify later (we'll use mobile)
            $_SESSION['otp_mobile'] = $mobile;
            header("Location: verify-otp.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to send OTP. Try again later.";
            header("Location: forgot-password.php");
            exit();
        }
    } else {
        $_SESSION['error'] = 'Username not found';
        header("Location: forgot-password.php");
        exit();
    }
}
?>
<!doctype html>
<html>
<head>
  <title>Forgot Password</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php if (isset($_SESSION['error'])): ?>
<script>Swal.fire("Error!", "<?php echo addslashes($_SESSION['error']); ?>", "error");</script>
<?php unset($_SESSION['error']); endif; ?>

<?php if (isset($_SESSION['info'])): ?>
<script>Swal.fire("Info", "<?php echo addslashes($_SESSION['info']); ?>", "info");</script>
<?php unset($_SESSION['info']); endif; ?>

<form method="POST" action="">
  <label>Enter Username</label>
  <input type="text" name="username" required />
  <button type="submit">Send OTP</button>
</form>
</body>
</html>
