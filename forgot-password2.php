<?php
session_start();
include('db_conn.php');
include('function.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    if (empty($username)) {
        $_SESSION['error'] = 'Enter your username';
        header("Location: forgot-password");
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
            header("Location: verify-otp");
            exit();
        } else {
            $_SESSION['error'] = "Failed to send OTP. Try again later.";
            header("Location: forgot-password");
            exit();
        }
    } else {
        $_SESSION['error'] = 'Username not found';
        header("Location: forgot-password");
        exit();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="IE=edge" http-equiv="X-UA-Compatible">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rhythm E-Clinic Solutions - Forgot Password</title>
  <link href="opd/admin/css/vendors_css.css" rel="stylesheet">
  <link href="opd/admin/css/style.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="icon" href="opd/images/favicon.ico">
</head>
<body class="bg-img hold-transition theme-primary" style="background-image:url(opd/images/auth-bg/bg.webp)">
<?php if (isset($_SESSION['error'])): ?>
<script>Swal.fire("Error!", "<?php echo addslashes($_SESSION['error']); ?>", "error");</script>
<?php unset($_SESSION['error']); endif; ?>

<?php if (isset($_SESSION['info'])): ?>
<script>Swal.fire("Info", "<?php echo addslashes($_SESSION['info']); ?>", "info");</script>
<?php unset($_SESSION['info']); endif; ?>

<div class="h-p100 container">
  <div class="row align-items-center h-p100 justify-content-md-center">
    <div class="col-12 col-lg-5 col-md-6">
      <div class="bg-white rounded10 shadow-lg">
        <div class="content-top-agile p-20 pb-0">
          <h2 class="text-primary">Forgot Password?</h2>
          <p class="mb-0">Enter your username to reset your password.</p>
        </div>
        <div class="p-40">
            <form action="" method="POST" autocomplete="off">
              <!--<label>Enter Username</label>-->
              <div class="form-group">
              <div class="input-group mb-3">
                <input class="bg-transparent form-control ps-15" name="username" placeholder="Username" required />
              </div>
            </div>
              <div class="row">
                  <div class="col-12 text-center">
                    <button class="btn btn-danger mt-10" name="sendOTP" type="submit">Send OTP</button>
                  </div>
              </div>
            </form>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>
