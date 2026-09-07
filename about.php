<?php

session_start();

/**
 * ARIP STREETWEAR — About page
 * Uses the same header/footer shell and CSS classes
 * as the rest of the ARIP website.
 */

$pageTitle = 'About — ARIP Streetwear';
$activeNav = 'about';
$year      = date('Y');


/* =========================================================
   NAVIGATION
   ========================================================= */

$navItems = [

    'home' => [
        'label' => 'Home',
        'href'  => 'index.php'
    ],

    'shop' => [
        'label' => 'Shop',
        'href'  => 'shop.php'
    ],

    'collections' => [
        'label' => 'Collections',
        'href'  => 'collections.php'
    ],

    'about' => [
        'label' => 'About',
        'href'  => 'about.php'
    ],

    'contact' => [
        'label' => 'Contact',
        'href'  => 'contact.php'
    ],

];


/* =========================================================
   OUR VALUES
   ========================================================= */

$values = [

    [
        'title' => 'Authenticity',
        'desc'  => 'We stay true to who we are and create pieces that represent real expression.',
        'icon'  => 'shield',
    ],

    [
        'title' => 'Quality',
        'desc'  => 'We never compromise on quality. Every detail is made to last.',
        'icon'  => 'diamond',
    ],

    [
        'title' => 'Respect',
        'desc'  => 'We respect our community, our craft, and the world around us.',
        'icon'  => 'people',
    ],

    [
        'title' => 'Creativity',
        'desc'  => 'We push boundaries and embrace creativity in everything we do.',
        'icon'  => 'bolt',
    ],

];


/* =========================================================
   COMMUNITY IMAGES
   ========================================================= */

$communityImages = [

    [
        'src' =>
            'assets/images/about/community-1.jpg',

        'alt' =>
            'ARIP hoodie back print'
    ],

    [
        'src' =>
            'assets/images/about/community-2.jpg',

        'alt' =>
            'Community member wearing ARIP tee'
    ],

    [
        'src' =>
            'assets/images/about/community-3.jpg',

        'alt' =>
            'Group of four wearing ARIP streetwear'
    ],

    [
        'src' =>
            'assets/images/about/community-4.jpg',

        'alt' =>
            'ARIP cap detail'
    ],

    [
        'src' =>
            'assets/images/about/community-5.jpg',

        'alt' =>
            'Community member in ARIP hoodie'
    ],

];


/* =========================================================
   FOOTER LINKS
   ========================================================= */

$footerColumns = [

    'Shop' => [

        'All Products' =>
            'shop.php',

        'Tees' =>
            'shop.php?cat=tees',

        'Hoodies' =>
            'shop.php?cat=hoodies',

        'Bottoms' =>
            'shop.php?cat=bottoms',

        'Accessories' =>
            'shop.php?cat=accessories',

    ],


    'Info' => [

        'About Us' =>
            'about.php',

        'Size Guide' =>
            '#',

        'Shipping & Returns' =>
            '#',

        'FAQ' =>
            '#',

    ],


    'Customer Care' => [

        'Contact Us' =>
            'contact.php',

        'Track Order' =>
            '#',

        'Returns' =>
            '#',

        'Privacy Policy' =>
            '#',

        'Terms & Conditions' =>
            '#',

    ],

];


/* =========================================================
   VALUE ICON FUNCTION
   ========================================================= */

function about_value_icon(string $name): string
{

    $icons = [

        'shield' => '
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.6"
            >
                <path
                    d="M12 3l7 3v5c0 4.5-3 8.2-7 9.5C8 19.2 5 15.5 5 11V6l7-3z"
                />
                <path
                    d="M8.5 12l2.3 2.3L15.5 9.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        ',


        'diamond' => '
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.6"
                stroke-linejoin="round"
            >
                <path
                    d="M4 9l4-6h8l4 6-8 12-8-12z"
                />
                <path
                    d="M4 9h16M9.5 3L8 9l4 12 4-12-1.5-6"
                />
            </svg>
        ',


        'people' => '
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.6"
            >
                <circle
                    cx="12"
                    cy="8"
                    r="2.6"
                />

                <circle
                    cx="5"
                    cy="10"
                    r="2.1"
                />

                <circle
                    cx="19"
                    cy="10"
                    r="2.1"
                />

                <path
                    d="M2.5 19c.6-2.8 2.7-4.5 6-4.5M21.5 19c-.6-2.8-2.7-4.5-6-4.5"
                    stroke-linecap="round"
                />

                <path
                    d="M6.5 19c.7-3.2 3-5 5.5-5s4.8 1.8 5.5 5"
                    stroke-linecap="round"
                />
            </svg>
        ',


        'bolt' => '
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.6"
                stroke-linejoin="round"
            >
                <path
                    d="M13 2L4 14h6l-1 8 9-12h-6l1-8z"
                />
            </svg>
        ',

    ];


    return $icons[$name] ?? '';

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
        <?= htmlspecialchars($pageTitle) ?>
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


        <!-- ================= LOGO ================= -->

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
                    ARIP
                </strong>

                <em>
                    STREETWEAR
                </em>

            </span>

        </a>


        <!-- ================= NAVIGATION ================= -->

        <nav class="main-nav">

            <ul>


                <?php foreach (
                    $navItems as $slug => $item
                ): ?>


                    <li>

                        <a
                            href="<?= htmlspecialchars(
                                $item['href']
                            ) ?>"

                            class="<?= $activeNav === $slug
                                ? 'active'
                                : ''
                            ?>"
                        >

                            <?= strtoupper(
                                htmlspecialchars(
                                    $item['label']
                                )
                            ) ?>

                        </a>

                    </li>


                <?php endforeach; ?>


            </ul>

        </nav>


        <!-- =================================================
             HEADER ICONS
             SEARCH → PROFILE → CART
             ================================================= -->

        <div class="header-icons">


            <!-- ================= SEARCH ================= -->

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


            <!-- ================= PROFILE ================= -->

            <?php if (
                isset($_SESSION['user_id']) &&
                !empty($_SESSION['user_id'])
            ): ?>


                <!-- LOGGED IN -->

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


                <!-- LOGGED OUT -->

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


            <!-- ================= CART ================= -->

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
     MAIN CONTENT
     ========================================================= -->

<main>


    <!-- =====================================================
         ABOUT HERO
         ===================================================== -->

    <section class="about-hero">

        <div class="about-hero-inner">


            <div class="about-hero-copy">


                <p class="eyebrow-inline">

                    <span class="line"></span>

                    About ARIP

                </p>


                <h1>

                    Built different.<br>
                    Made to stand out.

                </h1>


                <p>

                    ARIP Streetwear is more than clothing.
                    It's a mindset, a culture, and a statement
                    for those who dare to be different.

                </p>


            </div>


            <div class="about-hero-media">


                <img
                    src="assets/images/about/hero.jpg"
                    alt="Model wearing an ARIP hoodie, back view"
                >


            </div>


        </div>

    </section>


    <!-- =====================================================
         OUR STORY
         ===================================================== -->

    <section class="story-section">

        <div class="wrap story-grid">


            <div class="story-copy">


                <p class="eyebrow-inline">

                    <span class="line"></span>

                    Our Story

                </p>


                <h2>

                    From an idea to<br>
                    a movement.

                </h2>


                <p>

                    ARIP began with a simple idea:
                    to create streetwear that represents
                    authenticity, confidence, and individuality.

                </p>


                <p>

                    What started as a passion project has grown
                    into a brand that connects with a community
                    of dreamers, creators, and doers.

                </p>


                <p>

                    We're here to break norms and build a legacy
                    that inspires the next generation.

                </p>


            </div>


            <div class="story-media">


                <img
                    src="assets/images/about/story.jpg"
                    alt="Four ARIP community members standing together, back view"
                >


            </div>


        </div>

    </section>


    <!-- =====================================================
         OUR VALUES
         ===================================================== -->

    <section class="values-section">

        <div class="wrap">


            <p class="eyebrow-inline">

                <span class="line"></span>

                Our Values

            </p>


            <div class="values-grid">


                <?php foreach ($values as $value): ?>


                    <div class="value-item">


                        <div class="value-icon">

                            <?= about_value_icon(
                                $value['icon']
                            ) ?>

                        </div>


                        <h3>

                            <?= htmlspecialchars(
                                $value['title']
                            ) ?>

                        </h3>


                        <p>

                            <?= htmlspecialchars(
                                $value['desc']
                            ) ?>

                        </p>


                    </div>


                <?php endforeach; ?>


            </div>


        </div>

    </section>


    <!-- =====================================================
         OUR MISSION
         ===================================================== -->

    <section class="mission-section">

        <div class="wrap mission-grid">


            <div class="mission-media">


                <img
                    src="assets/images/about/mission.jpg"
                    alt="Model wearing an ARIP tee and cap"
                >


            </div>


            <div class="mission-copy">


                <p class="eyebrow-inline">

                    <span class="line"></span>

                    Our Mission

                </p>


                <h2>

                    Inspire confidence.<br>
                    Empower individuality.

                </h2>


                <p>

                    Our mission is to design timeless streetwear
                    that empowers people to be confident, bold,
                    and unapologetically themselves.

                    We believe what you wear should reflect
                    who you are.

                    Stand out. Stay true. That's ARIP.

                </p>


                <a
                    href="collections.php"
                    class="btn btn-dark"
                >

                    Explore Collections →

                </a>


            </div>


        </div>

    </section>


    <!-- =====================================================
         COMMUNITY
         ===================================================== -->

    <section class="community-section">

        <div class="wrap community-grid">


            <div class="community-copy">


                <p class="eyebrow-inline">

                    <span class="line"></span>

                    Be Part of the Movement

                </p>


                <h2>

                    It's more than fashion.<br>
                    It's a culture.

                </h2>


                <p>

                    Join the ARIP community and be the first
                    to know about new drops, events,
                    and exclusive offers.

                </p>


            </div>


            <div class="community-collage">


                <?php foreach (
                    $communityImages as $img
                ): ?>


                    <div class="collage-item">


                        <img
                            src="<?= htmlspecialchars(
                                $img['src']
                            ) ?>"
                            alt="<?= htmlspecialchars(
                                $img['alt']
                            ) ?>"
                        >


                    </div>


                <?php endforeach; ?>


            </div>


        </div>

    </section>


    <!-- =====================================================
         NEWSLETTER
         ===================================================== -->

    <section class="newsletter">

        <div class="wrap newsletter-inner">


            <div class="newsletter-copy">


                <svg
                    class="mail-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                >

                    <rect
                        x="2.5"
                        y="5"
                        width="19"
                        height="14"
                        rx="2"
                    />

                    <path
                        d="M3 6.5l9 6.5 9-6.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />

                </svg>


                <div>


                    <p class="newsletter-title">
                        Join the Movement
                    </p>


                    <p class="newsletter-sub">

                        Get updates on new drops,
                        exclusive offers, and more.

                    </p>


                </div>


            </div>


            <form
                id="newsletter-form"
                class="newsletter-form"
                novalidate
            >


                <input
                    type="email"
                    id="newsletter-email"
                    placeholder="Enter your email"
                    required
                >


                <button type="submit">
                    Subscribe
                </button>


                <p
                    id="newsletter-msg"
                    class="newsletter-msg"
                    hidden
                ></p>


            </form>


        </div>

    </section>


</main>


<!-- =========================================================
     FOOTER
     ========================================================= -->

<footer class="site-footer">

    <div class="wrap footer-grid">


        <!-- ================= BRAND ================= -->

        <div class="footer-brand">


            <a
                href="index.php"
                class="logo logo-light"
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


        <!-- ================= SHOP ================= -->

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


        <!-- ================= INFO ================= -->

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


        <!-- ================= CUSTOMER CARE ================= -->

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


        <!-- ================= CONTACT ================= -->

        <div class="footer-col">


            <h4>
                CONTACT
            </h4>


            <p class="contact-label">
                Email
            </p>


            <p>

                <a href="mailto:hello@aripstreetwear.com">

                    hello@aripstreetwear.com

                </a>

            </p>


            <p class="contact-label">
                Phone
            </p>


            <p>
                0983 457 678
            </p>


            <p class="contact-label">
                Location
            </p>


            <p>
                Philippines
            </p>


        </div>


    </div>


    <!-- ================= COPYRIGHT ================= -->

    <div class="footer-bottom">


        <p>

            &copy;
            <?= htmlspecialchars(
                (string) $year
            ) ?>
            ARIP STREETWEAR.
            ALL RIGHTS RESERVED.

        </p>


    </div>


</footer>


<!-- =========================================================
     JAVASCRIPT
     ========================================================= -->

<script
    src="script.js"
    defer
></script>


</body>

</html>