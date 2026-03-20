<!doctype html>
<html lang="en">

<head>
<meta charset="utf-8">
<title>Rhythm E-Clinic Solutions - Forgot Password</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

<link href="opd/images/favicon.ico" rel="icon">

<style>

/* PARTICLES BACKGROUND */
#particles-js {
    position: fixed;
    width: 100%;
    height: 100%;
    background: #0f172a;
    z-index: -1;
}

/* Center container */
body {
    margin: 0;
    font-family: "Poppins", sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

/* Glass Card */
.forgot-card {
    width: 92%;
    max-width: 420px;
    padding: 40px 35px;
    background: rgba(255,255,255,0.12);
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.35);
    backdrop-filter: blur(12px);
    text-align: center;
    animation: slideUp .8s ease-out;
}

@keyframes slideUp {
    from {opacity:0; transform:translateY(50px);}
    to {opacity:1; transform:translateY(0);}
}

/* Logo */
.forgot-logo {
    width: 120px;
    animation: float 3s ease-in-out infinite;
    margin-bottom: 15px;
}
@keyframes float {
    0% {transform:translateY(0);}
    50% {transform:translateY(-12px);}
    100% {transform:translateY(0);}
}

/* Title */
.forgot-title {
    font-size: 26px;
    font-weight: 800;
    background: linear-gradient(135deg, #1dbfc2, #3246d3);
    -webkit-background-clip: text;
    color: transparent;
}

/* Input box */
.input-box {
    margin-top: 20px;
    position: relative;
}
.input-box input {
    width: 100%;
    padding: 14px 18px;
    border-radius: 12px;
    border: none;
    background: rgba(255,255,255,0.2);
    color: #fff;
    outline: none;
}
.input-box input::placeholder {
    color: #dbeafe;
}
.input-box i {
    position: absolute;
    right: 16px;
    top: 14px;
    color: white;
}

/* Button */
.btn-reset {
    margin-top: 20px;
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #3246d3, #1dbfc2);
    color: white;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    box-shadow: 0 6px 20px rgba(50,70,211,0.4);
    transition: .3s;
}
.btn-reset:hover {
    transform: translateY(-3px);
    background: linear-gradient(135deg, #1dbfc2, #3246d3);
}

/* Back to login */
.back-link {
    color: #dbeafe;
    display: inline-block;
    margin-top: 15px;
}
.back-link:hover {text-decoration:underline;}

</style>

</head>

<body>

<div id="particles-js"></div>

<div class="forgot-card">

    <img src="opd/images/logo-letter.png" class="forgot-logo" alt="Logo">

    <h2 class="forgot-title">Forgot Password?</h2>
    <p style="color:#e0e7ff;">Enter your registered Email or Username</p>

    <form method="POST">

        <div class="input-box">
            <input type="text" name="email" placeholder="Email or Username" required>
            <i class="fa fa-envelope"></i>
        </div>

        <button class="btn-reset" type="submit">Send Reset Link</button>

    </form>

    <a href="index" class="back-link">← Back to Login</a>

</div>

<script>
particlesJS("particles-js", {
    "particles": {
        "number": { "value": 60 },
        "size": { "value": 3 },
        "move": { "speed": 1.2 },
        "color": { "value": "#1dbfc2" },
        "line_linked": {
            "enable": true,
            "color": "#1dbfc2",
            "opacity": 0.4
        }
    }
});
</script>

<?php if ($message == "success") { ?>
<script>
Swal.fire("Success!", "Password reset link sent to your email.", "success");
</script>
<?php } elseif ($message == "notfound") { ?>
<script>
Swal.fire("Not Found!", "No account found with that email/username.", "warning");
</script>
<?php } elseif (strpos($message, "Mailer Error:") !== false) { ?>
<script>
Swal.fire("Mail Error!", "<?= addslashes($message); ?>", "error");
</script>
<?php } ?>

</body>
</html>
