<?php
require_once __DIR__ . '/config/db.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (
        $fullName === '' ||
        $username === '' ||
        $email === '' ||
        $password === '' ||
        $confirmPassword === ''
    ) {
        $message = 'Please complete all fields.';
        $messageType = 'error';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = 'Please enter a valid email address.';
        $messageType = 'error';

    } elseif ($password !== $confirmPassword) {

        $message = 'Passwords do not match.';
        $messageType = 'error';

    } elseif (strlen($password) < 6) {

        $message = 'Password must be at least 6 characters.';
        $messageType = 'error';

    } else {

        $check = $pdo->prepare("
            SELECT user_id
            FROM users
            WHERE username = ? OR email = ?
        ");

        $check->execute([$username, $email]);

        if ($check->fetch()) {

            $message = 'Username or email already exists.';
            $messageType = 'error';

        } else {

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                INSERT INTO users
                (full_name, username, email, password)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $fullName,
                $username,
                $email,
                $hashedPassword
            ]);

            header('Location: login.php?registered=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Create Account — ARIP Streetwear</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<main class="login-page">

    <section class="login-panel">

        <div class="login-copy">
            <p class="eyebrow">ARIP ACCOUNT</p>

            <h1>JOIN ARIP.</h1>

            <p>
                Create your account and become part
                of the ARIP Streetwear community.
            </p>
        </div>

        <div class="login-box">

            <h2>CREATE ACCOUNT</h2>

            <?php if ($message !== ''): ?>

                <p class="register-message <?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </p>

            <?php endif; ?>

            <form method="POST"
                  action="register.php"
                  class="login-form">

                <label>Full Name</label>

                <input
                    type="text"
                    name="full_name"
                    required
                >

                <label>Username</label>

                <input
                    type="text"
                    name="username"
                    required
                >

                <label>Email Address</label>

                <input
                    type="email"
                    name="email"
                    required
                >

                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    required
                >

                <label>Confirm Password</label>

                <input
                    type="password"
                    name="confirm_password"
                    required
                >

                <button type="submit"
                        class="login-submit">
                    CREATE ACCOUNT →
                </button>

            </form>

            <div class="login-divider">
                <span></span>
                <p>OR</p>
                <span></span>
            </div>

            <a href="login.php"
               class="create-account-btn">
                BACK TO LOGIN
            </a>

        </div>

    </section>

</main>

</body>
</html>