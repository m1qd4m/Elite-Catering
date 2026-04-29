<?php
// auth/login.php
require_once '../config/db.php';

// Already logged in
if (isset($_SESSION['user_id'])) {
    redirect('../' . $_SESSION['role'] . '/dashboard.php');
}

$error = '';
$old_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $old_email = $email;

    if ($email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            setFlash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect('../' . $user['role'] . '/dashboard.php');
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — Élite Catering</title>
  <link rel="stylesheet" href="../assets/css/variables.css">
  <link rel="stylesheet" href="../assets/css/base.css">
  <link rel="stylesheet" href="../assets/css/components.css">
  <link rel="stylesheet" href="../assets/css/auth.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="auth-page">

  <div class="auth-visual">
    <a href="../index.php" class="auth-visual-logo">Élite<span> Catering</span></a>
    <div class="auth-visual-content">
      <h2>Crafting <em>Memorable</em><br>Experiences</h2>
      <p>Luxury catering for weddings, corporate events, and celebrations.</p>
      <div class="auth-trust">
        <div class="auth-trust-item"><i class="fas fa-star"></i> 500+ Events</div>
        <div class="auth-trust-item"><i class="fas fa-shield-alt"></i> Secure</div>
        <div class="auth-trust-item"><i class="fas fa-headset"></i> 24/7 Support</div>
      </div>
    </div>
  </div>

  <div class="auth-form-panel">
    <a href="../index.php" class="auth-back-link">
      <i class="fas fa-arrow-left"></i> Back to Home
    </a>

    <span class="auth-heading-tag">Welcome Back</span>
    <h1 class="auth-heading-title">Sign In</h1>
    <p class="auth-heading-sub">Access your account to manage bookings.</p>

    <?php if ($error !== ''): ?>
      <div class="alert-flash error">
        <i class="fas fa-circle-exclamation"></i>
        <span><?= e($error) ?></span>
      </div>
    <?php endif; ?>

    <form class="auth-form" method="POST" action="">
      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input type="email"
               name="email"
               id="email"
               class="form-input"
               placeholder="you@example.com"
               value="<?= e($old_email) ?>"
               autocomplete="email"
               required>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="pass-wrap">
          <input type="password"
                 name="password"
                 id="password"
                 class="form-input"
                 placeholder="Enter your password"
                 autocomplete="current-password"
                 required>
          <button type="button" class="toggle-pass" aria-label="Show/hide password">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-gold btn-full">
        <i class="fas fa-sign-in-alt"></i> Sign In
      </button>
    </form>

    <div class="auth-footer-link">
      Don't have an account? <a href="signup.php">Create Account</a>
    </div>

    <div class="auth-demo-hint">
      <strong>Demo Admin:</strong> admin@catering.com &nbsp;·&nbsp; Password: <code>password</code>
    </div>
  </div>

</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
