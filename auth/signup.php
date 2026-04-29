<?php
// auth/signup.php
require_once '../config/db.php';

if (isset($_SESSION['user_id'])) {
    redirect('../' . $_SESSION['role'] . '/dashboard.php');
}

$error = '';
$old   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old      = $_POST;
    $name     = trim($_POST['name']             ?? '');
    $email    = trim($_POST['email']            ?? '');
    $phone    = trim($_POST['phone']            ?? '');
    $password = trim($_POST['password']         ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match. Please try again.';
    } else {
        // Check duplicate email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        if ($exists) {
            $error = 'This email is already registered. Please sign in instead.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt2  = $conn->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'customer')");
            $stmt2->bind_param('ssss', $name, $email, $phone, $hashed);

            if ($stmt2->execute()) {
                $stmt2->close();
                setFlash('success', 'Account created successfully! Please sign in.');
                redirect('login.php');
            } else {
                $error = 'Registration failed: ' . $stmt2->error . '. Please try again.';
                $stmt2->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — Élite Catering</title>
  <link rel="stylesheet" href="../assets/css/variables.css">
  <link rel="stylesheet" href="../assets/css/base.css">
  <link rel="stylesheet" href="../assets/css/components.css">
  <link rel="stylesheet" href="../assets/css/auth.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="auth-page">

  <div class="auth-visual"
       style="background-image:url('https://images.unsplash.com/photo-1555244162-803834f70033?w=1200&q=80')">
    <a href="../index.php" class="auth-visual-logo">Élite<span> Catering</span></a>
    <div class="auth-visual-content">
      <h2>Start Your<br><em>Culinary Journey</em></h2>
      <p>Join thousands of satisfied clients who trust us for their most important occasions.</p>
      <div class="auth-trust">
        <div class="auth-trust-item"><i class="fas fa-check-circle"></i> Free to join</div>
        <div class="auth-trust-item"><i class="fas fa-lock"></i> Secure account</div>
        <div class="auth-trust-item"><i class="fas fa-calendar-check"></i> Easy booking</div>
      </div>
    </div>
  </div>

  <div class="auth-form-panel">
    <a href="../index.php" class="auth-back-link">
      <i class="fas fa-arrow-left"></i> Back to Home
    </a>

    <span class="auth-heading-tag">New Account</span>
    <h1 class="auth-heading-title">Create Account</h1>
    <p class="auth-heading-sub">Register to book catering services for your events.</p>

    <?php if ($error !== ''): ?>
      <div class="alert-flash error">
        <i class="fas fa-circle-exclamation"></i>
        <span><?= e($error) ?></span>
      </div>
    <?php endif; ?>

    <form class="auth-form" method="POST" action="">
      <div class="form-group">
        <label class="form-label" for="name">Full Name *</label>
        <input type="text"
               name="name"
               id="name"
               class="form-input"
               placeholder="Ahmed Khan"
               value="<?= e($old['name'] ?? '') ?>"
               autocomplete="name"
               required>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email Address *</label>
        <input type="email"
               name="email"
               id="email"
               class="form-input"
               placeholder="you@example.com"
               value="<?= e($old['email'] ?? '') ?>"
               autocomplete="email"
               required>
      </div>

      <div class="form-group">
        <label class="form-label" for="phone">Phone Number</label>
        <input type="tel"
               name="phone"
               id="phone"
               class="form-input"
               placeholder="0300-0000000"
               value="<?= e($old['phone'] ?? '') ?>"
               autocomplete="tel">
      </div>

      <div class="grid-2">
        <div class="form-group">
          <label class="form-label" for="password">Password *</label>
          <div class="pass-wrap">
            <input type="password"
                   name="password"
                   id="password"
                   class="form-input"
                   placeholder="Min. 6 characters"
                   required>
            <button type="button" class="toggle-pass"><i class="fas fa-eye"></i></button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="confirm_password">Confirm Password *</label>
          <div class="pass-wrap">
            <input type="password"
                   name="confirm_password"
                   id="confirm_password"
                   class="form-input"
                   placeholder="Repeat password"
                   required>
            <button type="button" class="toggle-pass"><i class="fas fa-eye"></i></button>
          </div>
        </div>
      </div>

      <button type="submit" class="btn-gold btn-full">
        <i class="fas fa-user-plus"></i> Create Account
      </button>
    </form>

    <div class="auth-footer-link">
      Already have an account? <a href="login.php">Sign In</a>
    </div>
  </div>

</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
