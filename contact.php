<?php

session_start();

require_once __DIR__ . '/config/db.php';


/* =========================================================
   CONTACT FORM MESSAGES
   ========================================================= */

$successMessage = '';
$errorMessage = '';


/* =========================================================
   PROCESS CONTACT FORM
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name =
        trim($_POST['name'] ?? '');

    $email =
        trim($_POST['email'] ?? '');

    $subject =
        trim($_POST['subject'] ?? '');

    $message =
        trim($_POST['message'] ?? '');


    /* =====================================================
       VALIDATION
       ===================================================== */

    if (
        $name === '' ||
        $email === '' ||
        $subject === '' ||
        $message === ''
    ) {

        $errorMessage =
            'Please complete all required fields.';

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errorMessage =
            'Please enter a valid email address.';

    } else {

        /* =================================================
           SAVE MESSAGE TO DATABASE
           ================================================= */

        try {

            $messageStmt =
                $pdo->prepare("
                    INSERT INTO contact_messages
                    (
                        full_name,
                        email,
                        subject,
                        message
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");


            $messageStmt->execute([
                $name,
                $email,
                $subject,
                $message
            ]);


            /*
             * Redirect after successful insert
             * to prevent duplicate submission
             * when refreshing the page.
             */

            header(
                'Location: contact.php?sent=1'
            );

            exit;


        } catch (PDOException $e) {

            $errorMessage =
                'Unable to send your message right now. Please try again.';

        }

    }

}


/* =========================================================
   SUCCESS MESSAGE
   ========================================================= */

if (
    isset($_GET['sent']) &&
    $_GET['sent'] === '1'
) {

    $successMessage =
        'Thank you! Your message has been received.';

}


/* =========================================================
   SITE DATA
   ========================================================= */

$year = date('Y');


$navLinks = [
    'Home'        => 'index.php',
    'Shop'        => 'shop.php',
    'Collections' => 'collections.php',
    'About'       => 'about.php',
    'Contact'     => 'contact.php',
];

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
        Contact — ARIP Streetwear
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<!-- ================= HEADER ================= -->

<header class="site-header">

    <div class="wrap header-inner">


        <!-- LOGO -->

        <a
            href="index.php"
            class="logo"
        >

            <span class="logo-mark">

                <img
                    src="assets/logo.png"
                    alt="ARIP logo"
                >

            </span>


            <span class="logo-text">

                <strong>
                    ARIP
                </strong>

                <em>
                    STREETWEAR
                </em>

            </span>

        </a>


        <!-- NAVIGATION -->

        <nav class="main-nav">

            <ul>

                <?php foreach ($navLinks as $label => $href): ?>

                    <li>

                        <a
                            href="<?= htmlspecialchars($href) ?>"
                            class="<?= $label === 'Contact'
                                ? 'active'
                                : ''
                            ?>"
                        >

                            <?= strtoupper(
                                htmlspecialchars($label)
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

                <a
                    href="profile.php"
                    class="header-icon-btn"
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

            <?php else: ?>

                <a
                    href="login.php"
                    class="header-icon-btn"
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


<!-- SEARCH OVERLAY -->

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


<!-- ================= CONTACT HERO ================= -->

<section class="contact-hero">

    <img
        src="assets/contact-hero.jpg"
        alt="ARIP Streetwear"
        class="contact-hero-photo"
    >

    <div class="contact-hero-overlay"></div>

    <div class="wrap contact-hero-inner">

        <div class="contact-hero-copy">

            <p class="contact-eyebrow">

                CONTACT

                <span></span>

            </p>

            <h1>
                GET IN TOUCH
            </h1>

            <h2>
                WE'D LOVE TO HEAR FROM YOU.
            </h2>

            <p>

                Whether you have a question, need support,
                collaborate, or just want to say hello —
                we're here for you.

            </p>

        </div>

    </div>

</section>


<!-- ================= CONTACT MAIN ================= -->

<section class="contact-main">

    <div class="wrap contact-main-grid">


        <!-- LEFT SIDE -->

        <div class="contact-info">

            <p class="contact-small-heading">

                CONTACT INFORMATION

                <span></span>

            </p>

            <h2>
                REACH OUT TO US.
            </h2>

            <p class="contact-intro">

                We're always here to help. Choose the best way to
                get in touch with us.

            </p>


            <!-- EMAIL -->

            <div class="contact-method">

                <div class="contact-method-icon">

                    <svg viewBox="0 0 24 24">

                        <rect
                            x="2"
                            y="5"
                            width="20"
                            height="14"
                            rx="1"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        />

                        <path
                            d="M3 6l9 7 9-7"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        />

                    </svg>

                </div>

                <div>

                    <strong>
                        EMAIL
                    </strong>

                    <a href="mailto:hello@aripstreetwear.com">

                        @aripstreetwear.com

                    </a>

                </div>

            </div>


            <!-- PHONE -->

            <div class="contact-method">

                <div class="contact-method-icon">

                    <svg viewBox="0 0 24 24">

                        <path
                            d="M5 3l4 1 2 5-3 2c2 4 4 6 8 8l2-3 5 2 1 4c-1 2-3 3-5 3C10 23 1 14 1 5c0-2 2-3 4-2z"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />

                    </svg>

                </div>

                <div>

                    <strong>
                        PHONE
                    </strong>

                    <span>
                        0983457678
                    </span>

                </div>

            </div>


            <!-- LOCATION -->

            <div class="contact-method">

                <div class="contact-method-icon">

                    <svg viewBox="0 0 24 24">

                        <path
                            d="M12 22s7-7 7-13a7 7 0 1 0-14 0c0 6 7 13 7 13z"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        />

                        <circle
                            cx="12"
                            cy="9"
                            r="2"
                            fill="currentColor"
                        />

                    </svg>

                </div>

                <div>

                    <strong>
                        LOCATION
                    </strong>

                    <span>
                        Philippines
                    </span>

                </div>

            </div>


            <!-- SOCIAL -->

            <div class="social-links">

                <a
                    href="https://www.instagram.com/"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="Instagram"
                >
                    ◎
                </a>

                <a
                    href="https://www.tiktok.com/"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="TikTok"
                >
                    ♪
                </a>

                <a
                    href="https://www.facebook.com/"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="Facebook"
                >
                    f
                </a>

            </div>

        </div>


        <!-- RIGHT SIDE FORM -->

        <div class="contact-form-box">

            <p class="contact-small-heading">

                SEND US A MESSAGE

                <span></span>

            </p>


            <?php if ($successMessage): ?>

                <div class="contact-alert success">

                    <?= htmlspecialchars($successMessage) ?>

                </div>

            <?php endif; ?>


            <?php if ($errorMessage): ?>

                <div class="contact-alert error">

                    <?= htmlspecialchars($errorMessage) ?>

                </div>

            <?php endif; ?>


            <form
                method="post"
                action="contact.php"
                id="contact-form"
                class="contact-form"
            >


                <div class="contact-form-row">

                    <input
                        type="text"
                        name="name"
                        id="contact-name"
                        placeholder="Full Name *"
                        required
                    >

                    <input
                        type="email"
                        name="email"
                        id="contact-email"
                        placeholder="Email Address *"
                        required
                    >

                </div>


                <select
                    name="subject"
                    id="contact-subject"
                    required
                >

                    <option value="">
                        Subject *
                    </option>

                    <option value="General Inquiry">
                        General Inquiry
                    </option>

                    <option value="Order Support">
                        Order Support
                    </option>

                    <option value="Collaboration">
                        Collaboration
                    </option>

                    <option value="Returns">
                        Returns
                    </option>

                    <option value="Other">
                        Other
                    </option>

                </select>


                <textarea
                    name="message"
                    id="contact-message"
                    placeholder="Your Message *"
                    required
                ></textarea>


                <button type="submit">

                    SEND MESSAGE →

                </button>


                <p
                    class="contact-js-message"
                    id="contact-js-message"
                    hidden
                ></p>


            </form>

        </div>

    </div>

</section>


<!-- ================= LOCATION ================= -->

<section class="contact-location">

    <img
        src="assets/contact-map.jpg"
        alt="Map showing ARIP location"
        class="contact-map-photo"
    >

    <div class="contact-location-overlay"></div>

    <div class="wrap contact-location-inner">

        <div class="contact-location-copy">

            <p class="contact-eyebrow">

                OUR LOCATION

                <span></span>

            </p>

            <h2>
                VISIT OUR HQ
            </h2>

            <p class="location-name">

                ● &nbsp; Philippines

            </p>

            <p>

                We're based in the Philippines and always open
                to visitors, collaborations, and new opportunities.

            </p>

            <a
                href="https://www.google.com/maps/search/Philippines"
                class="contact-direction-btn"
                target="_blank"
                rel="noopener"
            >

                GET DIRECTIONS →

            </a>

        </div>

    </div>

</section>


<!-- ================= NEWSLETTER ================= -->

<section class="newsletter">

    <div class="wrap newsletter-inner">


        <div class="newsletter-copy">

            <span class="mail-icon">

                <svg viewBox="0 0 24 24">

                    <rect
                        x="2"
                        y="5"
                        width="20"
                        height="14"
                        rx="1"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    />

                    <path
                        d="M3 6l9 7 9-7"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    />

                </svg>

            </span>

            <div>

                <p class="newsletter-title">
                    JOIN THE MOVEMENT
                </p>

                <p class="newsletter-sub">

                    Get updates on new drops,
                    exclusive offers, and more.

                </p>

            </div>

        </div>


        <form
            class="newsletter-form"
            id="newsletter-form"
        >

            <input
                type="email"
                id="newsletter-email"
                placeholder="Enter your email"
                required
            >

            <button type="submit">

                SUBSCRIBE

            </button>

        </form>


    </div>

</section>


<!-- ================= FOOTER ================= -->

<footer class="site-footer">

    <div class="wrap footer-grid">


        <!-- BRAND -->

        <div class="footer-brand">

            <a
                href="index.php"
                class="logo logo-light"
            >

                <span class="logo-mark">

                    <img
                        src="assets/logo.png"
                        alt="ARIP logo"
                    >

                </span>

                <span class="logo-text">

                    <strong>
                        ARIP
                    </strong>

                    <em>
                        STREETWEAR
                    </em>

                </span>

            </a>

            <p class="footer-slogan">

                Built Different.
                Made To Stand Out.

            </p>

            <p class="footer-est">

                EST. 2024

            </p>

        </div>


        <!-- SHOP -->

        <div class="footer-col">

            <h4>
                SHOP
            </h4>

            <ul>

                <li>
                    <a href="shop.php">
                        All Products
                    </a>
                </li>

                <li>
                    <a href="shop.php?cat=tees">
                        Tees
                    </a>
                </li>

                <li>
                    <a href="shop.php?cat=hoodies">
                        Hoodies
                    </a>
                </li>

                <li>
                    <a href="shop.php?cat=bottoms">
                        Bottoms
                    </a>
                </li>

                <li>
                    <a href="shop.php?cat=accessories">
                        Accessories
                    </a>
                </li>

            </ul>

        </div>


        <!-- INFO -->

        <div class="footer-col">

            <h4>
                INFO
            </h4>

            <ul>

                <li>
                    <a href="about.php">
                        About Us
                    </a>
                </li>

                <li>
                    <a href="#">
                        Size Guide
                    </a>
                </li>

                <li>
                    <a href="#">
                        Shipping &amp; Returns
                    </a>
                </li>

                <li>
                    <a href="#">
                        FAQ
                    </a>
                </li>

            </ul>

        </div>


        <!-- CUSTOMER CARE -->

        <div class="footer-col">

            <h4>
                CUSTOMER CARE
            </h4>

            <ul>

                <li>
                    <a href="contact.php">
                        Contact Us
                    </a>
                </li>

                <li>
                    <a href="#">
                        Track Order
                    </a>
                </li>

                <li>
                    <a href="#">
                        Returns
                    </a>
                </li>

                <li>
                    <a href="#">
                        Privacy Policy
                    </a>
                </li>

                <li>
                    <a href="#">
                        Terms &amp; Conditions
                    </a>
                </li>

            </ul>

        </div>


        <!-- CONTACT -->

        <div class="footer-col">

            <h4>
                CONTACT
            </h4>

            <p class="contact-label">
                Email
            </p>

            <p>
                @aripstreetwear.com
            </p>

            <p class="contact-label">
                Phone
            </p>

            <p>
                0983457678
            </p>

            <p class="contact-label">
                Location
            </p>

            <p>
                Philippines
            </p>

        </div>


    </div>


    <div class="footer-bottom">

        <p>

            &copy;
            <?= $year ?>
            ARIP STREETWEAR.
            ALL RIGHTS RESERVED.

        </p>

    </div>

</footer>


<script src="script.js"></script>


</body>

</html>