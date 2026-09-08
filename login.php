<?php

session_start();

require_once __DIR__ . '/config/db.php';


/* =========================================================
   SITE DATA
   ========================================================= */

$site = [
    'name'    => 'ARIP',
    'tagline' => 'STREETWEAR',
];


$navLinks = [
    'Home'        => 'index.php',
    'Shop'        => 'shop.php',
    'Collections' => 'collections.php',
    'About'       => 'about.php',
    'Contact'     => 'contact.php',
];


$loginError = '';
$registeredMessage = '';


/* =========================================================
   ACCOUNT CREATED MESSAGE
   ========================================================= */

if (
    isset($_GET['registered']) &&
    $_GET['registered'] === '1'
) {

    $registeredMessage =
        'Account created successfully. Please log in using your username and password.';

}


/* =========================================================
   LOGIN PROCESS
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username =
        trim(
            $_POST['username'] ?? ''
        );


    $password =
        $_POST['password'] ?? '';


    /* EMPTY FIELDS */

    if (
        $username === '' ||
        $password === ''
    ) {

        $loginError =
            'Please enter your username and password.';

    } else {


        /* =====================================================
           GET USER
           CUSTOMER + ADMIN
           ===================================================== */

        $stmt =
            $pdo->prepare("
                SELECT
                    user_id,
                    full_name,
                    username,
                    email,
                    password,
                    role
                FROM users
                WHERE username = ?
                LIMIT 1
            ");


        $stmt->execute([
            $username
        ]);


        $user =
            $stmt->fetch();


        /* =====================================================
           CHECK PASSWORD
           ===================================================== */

        if (
            $user &&
            password_verify(
                $password,
                $user['password']
            )
        ) {


            /* PREVENT SESSION FIXATION */

            session_regenerate_id(true);


            /* =================================================
               SAVE USER SESSION
               ================================================= */

            $_SESSION['user_id'] =
                $user['user_id'];


            $_SESSION['username'] =
                $user['username'];


            $_SESSION['full_name'] =
                $user['full_name'];


            $_SESSION['email'] =
                $user['email'];


            $_SESSION['role'] =
                $user['role'];


            /* =================================================
               ADMIN LOGIN
               ================================================= */

            if (
                $user['role'] === 'admin'
            ) {

                header(
                    'Location: admin/dashboard.php'
                );

                exit;

            }


            /* =================================================
               CUSTOMER LOGIN
               ================================================= */

            header(
                'Location: index.php'
            );

            exit;


        } else {


            /* INVALID LOGIN */

            $loginError =
                'Invalid username or password.';

        }

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Login — ARIP Streetwear
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<!-- =========================================================
     HEADER
     ========================================================= -->

<header class="site-header">

    <div class="wrap header-inner">


        <!-- LOGO -->

        <a
            href="index.php"
            class="logo"
        >

            <span
                class="logo-mark"
                aria-hidden="true"
            >

                <img
                    src="assets/logo.png"
                    alt="ARIP logo"
                >

            </span>


            <span class="logo-text">

                <strong>

                    <?= htmlspecialchars(
                        $site['name']
                    ) ?>

                </strong>

                <em>

                    <?= htmlspecialchars(
                        $site['tagline']
                    ) ?>

                </em>

            </span>

        </a>


        <!-- NAVIGATION -->

        <nav class="main-nav">

            <ul>

                <?php foreach (
                    $navLinks as $label => $href
                ): ?>

                    <li>

                        <a
                            href="<?= htmlspecialchars(
                                $href
                            ) ?>"
                        >

                            <?= strtoupper(
                                htmlspecialchars(
                                    $label
                                )
                            ) ?>

                        </a>

                    </li>

                <?php endforeach; ?>

            </ul>

        </nav>


        <!-- HEADER ICONS -->

        <div class="header-icons">


            <!-- SEARCH -->

            <button
                type="button"
                class="header-icon-btn"
                id="search-btn"
                aria-label="Search"
                title="Search"
            >

                <svg viewBox="0 0 24 24">

                    <circle
                        cx="11"
                        cy="11"
                        r="7"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    />

                    <line
                        x1="21"
                        y1="21"
                        x2="16.6"
                        y2="16.6"
                        stroke="currentColor"
                        stroke-width="2"
                    />

                </svg>

            </button>


            <!-- ACCOUNT / PROFILE -->

            <?php if (
                isset($_SESSION['user_id']) &&
                !empty($_SESSION['user_id'])
            ): ?>


                <?php if (
                    ($_SESSION['role'] ?? '') === 'admin'
                ): ?>


                    <!-- ADMIN -->

                    <a
                        href="admin/dashboard.php"
                        class="header-icon-btn active-account"
                        aria-label="Admin Dashboard"
                        title="Admin Dashboard"
                    >

                        <svg viewBox="0 0 24 24">

                            <circle
                                cx="12"
                                cy="8"
                                r="4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            />

                            <path
                                d="M4 20c1.5-4 5-6 8-6s6.5 2 8 6"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            />

                        </svg>

                    </a>


                <?php else: ?>


                    <!-- CUSTOMER -->

                    <a
                        href="profile.php"
                        class="header-icon-btn active-account"
                        aria-label="My Profile"
                        title="My Profile"
                    >

                        <svg viewBox="0 0 24 24">

                            <circle
                                cx="12"
                                cy="8"
                                r="4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            />

                            <path
                                d="M4 20c1.5-4 5-6 8-6s6.5 2 8 6"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            />

                        </svg>

                    </a>


                <?php endif; ?>


            <?php else: ?>


                <!-- LOGGED OUT -->

                <a
                    href="login.php"
                    class="header-icon-btn active-account"
                    aria-label="Login"
                    title="Login"
                >

                    <svg viewBox="0 0 24 24">

                        <circle
                            cx="12"
                            cy="8"
                            r="4"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        />

                        <path
                            d="M4 20c1.5-4 5-6 8-6s6.5 2 8 6"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        />

                    </svg>

                </a>


            <?php endif; ?>


            <!-- CART -->

            <button
                type="button"
                class="header-icon-btn"
                id="cart-btn"
                aria-label="Cart"
                title="Cart"
            >

                <svg viewBox="0 0 24 24">

                    <path
                        d="M6 8h12l-1 12H7L6 8z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    />

                    <path
                        d="M9 8V6a3 3 0 0 1 6 0v2"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    />

                </svg>


                <span
                    class="cart-count"
                    id="cart-count"
                    hidden
                >
                    0
                </span>

            </button>


        </div>

    </div>

</header>


<!-- =========================================================
     SEARCH OVERLAY
     ========================================================= -->

<div
    class="search-overlay"
    id="search-overlay"
>

    <div class="search-box">

        <input
            type="text"
            id="search-input"
            placeholder="Search products..."
            autocomplete="off"
        >

        <button
            type="button"
            id="search-close"
            aria-label="Close search"
        >
            ×
        </button>

    </div>

</div>


<!-- =========================================================
     LOGIN PAGE
     ========================================================= -->

<main class="login-page">

    <section class="login-panel">


        <!-- LEFT SIDE -->

        <div class="login-copy">

            <p class="eyebrow">
                ARIP ACCOUNT
            </p>


            <h1>
                WELCOME BACK.
            </h1>


            <p>

                Sign in to access your account,
                saved items, and shopping activity.

            </p>

        </div>


        <!-- RIGHT SIDE -->

        <div class="login-box">


            <h2>
                LOGIN
            </h2>


            <!-- SUCCESS MESSAGE AFTER REGISTRATION -->

            <?php if (
                $registeredMessage !== ''
            ): ?>

                <p class="login-message success">

                    <?= htmlspecialchars(
                        $registeredMessage
                    ) ?>

                </p>

            <?php endif; ?>


            <!-- LOGIN ERROR -->

            <?php if (
                $loginError !== ''
            ): ?>

                <p class="login-message error">

                    <?= htmlspecialchars(
                        $loginError
                    ) ?>

                </p>

            <?php endif; ?>


            <!-- LOGIN FORM -->

            <form
                method="POST"
                action="login.php"
                id="login-form"
                class="login-form"
            >


                <!-- USERNAME -->

                <label for="login-username">
                    Username
                </label>


                <input
                    type="text"
                    id="login-username"
                    name="username"
                    placeholder="Enter your username"
                    autocomplete="username"
                    value="<?= htmlspecialchars(
                        $_POST['username'] ?? ''
                    ) ?>"
                    required
                >


                <!-- PASSWORD -->

                <label for="login-password">
                    Password
                </label>


                <div class="password-wrap">


                    <input
                        type="password"
                        id="login-password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >


                    <button
                        type="button"
                        id="toggle-password"
                        class="password-toggle"
                        aria-label="Show or hide password"
                    >
                        SHOW
                    </button>


                </div>


                <!-- OPTIONS -->

                <div class="login-options">


                    <label class="remember-me">

                        <input
                            type="checkbox"
                            name="remember_me"
                            id="remember-me"
                        >

                        Remember me

                    </label>


                    <a
                        href="#"
                        class="forgot-link"
                    >
                        Forgot Password?
                    </a>


                </div>


                <!-- LOGIN BUTTON -->

                <button
                    type="submit"
                    class="login-submit"
                >
                    LOGIN →
                </button>


            </form>


            <!-- DIVIDER -->

            <div class="login-divider">

                <span></span>

                <p>
                    OR
                </p>

                <span></span>

            </div>


            <!-- CREATE ACCOUNT -->

            <a
                href="register.php"
                class="create-account-btn"
            >
                CREATE ACCOUNT
            </a>


        </div>

    </section>

</main>


<!-- =========================================================
     JAVASCRIPT
     ========================================================= -->

<script src="script.js"></script>


</body>

</html>