<?php

session_start();

/**
 * ARIP STREETWEAR — Homepage
 * Updated header behavior:
 *
 * LOGO | HOME SHOP COLLECTIONS ABOUT CONTACT | SEARCH PROFILE CART
 *
 * PROFILE:
 * Logged in  -> profile.php
 * Logged out -> login.php
 */


$site = [
    'name'    => 'ARIP',
    'tagline' => 'STREETWEAR',
    'est'     => 'EST. 2024',
    'year'    => date('Y'),
];


$nav = [
    'Home',
    'Shop',
    'Collections',
    'About',
    'Contact'
];


$navLinks = [
    'Home'        => 'index.php',
    'Shop'        => 'shop.php',
    'Collections' => 'collections.php',
    'About'       => 'about.php',
    'Contact'     => 'contact.php',
];


$collections = [

    [
        'name'  => 'Tees',
        'slug'  => 'tees',
        'image' => 'assets/collection-tees.jpg'
    ],

    [
        'name'  => 'Hoodies',
        'slug'  => 'hoodies',
        'image' => 'assets/collection-hoodies.jpg'
    ],

    [
        'name'  => 'Bottoms',
        'slug'  => 'bottoms',
        'image' => 'assets/collection-bottoms.jpg'
    ],

    [
        'name'  => 'Accessories',
        'slug'  => 'accessories',
        'image' => 'assets/collection-accessories.jpg'
    ],

];


/* =========================================================
   BEST SELLERS
   ========================================================= */

$products = [

    [
        'name'     => 'ARIP Essential Tee',
        'price'    => 700,
        'category' => 'tees',
        'image'    => 'assets/product-tee.png',
        'swatches' => [
            '#111111',
            '#7a7a7a',
            '#ffffff'
        ],
    ],

    [
        'name'     => 'ARIP Signature Hoodie',
        'price'    => 1000,
        'category' => 'hoodies',
        'image'    => 'assets/product-hoodie.png',
        'swatches' => [
            '#111111',
            '#7a7a7a',
            '#ffffff'
        ],
    ],

    [
        'name'     => 'ARIP Cargo Pants',
        'price'    => 800,
        'category' => 'bottoms',
        'image'    => 'assets/product-pants.png',
        'swatches' => [
            '#111111',
            '#7a7a7a',
            '#ffffff'
        ],
    ],

    [
        'name'     => 'ARIP Logo Cap',
        'price'    => 400,
        'category' => 'accessories',
        'image'    => 'assets/product-cap.png',
        'swatches' => [
            '#111111',
            '#7a7a7a',
            '#ffffff'
        ],
    ],

];


/* =========================================================
   FOOTER
   ========================================================= */

$footerColumns = [

    'Shop' => [
        'All Products',
        'Tees',
        'Hoodies',
        'Bottoms',
        'Accessories'
    ],

    'Info' => [
        'About Us',
        'Size Guide',
        'Shipping & Returns',
        'FAQ'
    ],

    'Customer Care' => [
        'Contact Us',
        'Track Order',
        'Returns',
        'Privacy Policy',
        'Terms & Conditions'
    ],

];


$shopColumnLinks = [
    'All Products' => 'shop.php',
    'Tees'         => 'shop.php?cat=tees',
    'Hoodies'      => 'shop.php?cat=hoodies',
    'Bottoms'      => 'shop.php?cat=bottoms',
    'Accessories'  => 'shop.php?cat=accessories',
];


$contact = [
    'email'    => 'hello@aripstreetwear.com',
    'phone'    => '0983 457 678',
    'location' => 'Philippines',
];


/* =========================================================
   PESO FORMAT
   ========================================================= */

function peso(float $amount): string
{
    return '₱' . number_format(
        $amount,
        2
    );
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
        <?= htmlspecialchars($site['name']) ?>
        Streetwear — Built Different. Made to Stand Out.
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<!-- =========================================================
     HEADER

     LOGO
     HOME SHOP COLLECTIONS ABOUT CONTACT
     SEARCH PROFILE CART
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


        <!-- ================= NAVIGATION ================= -->

        <nav class="main-nav">

            <ul>

                <?php foreach ($nav as $i => $item): ?>

                    <li>

                        <a
                            href="<?= htmlspecialchars(
                                $navLinks[$item] ?? '#'
                            ) ?>"
                            class="<?= $i === 0
                                ? 'active'
                                : ''
                            ?>"
                        >

                            <?= strtoupper(
                                htmlspecialchars(
                                    $item
                                )
                            ) ?>

                        </a>

                    </li>

                <?php endforeach; ?>

            </ul>

        </nav>


        <!-- =================================================
             HEADER ICONS
             SEARCH -> PROFILE -> CART
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

                <!--
                    LOGGED IN
                    PROFILE ICON -> profile.php
                -->

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


                <!--
                    LOGGED OUT
                    PROFILE ICON -> login.php
                -->

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
     HERO
     ========================================================= -->

<section class="hero">

    <div class="hero-inner">


        <div class="hero-media">

            <img
                src="assets/hero-model.jpg"
                alt="Model wearing an ARIP black hoodie"
                class="hero-photo"
            >

        </div>


        <div class="hero-scrim"></div>


        <div class="hero-copy">


            <p class="eyebrow">
                Timeless Streetwear
            </p>


            <h1>

                Built<br>

                Different.<br>

                Made To<br>

                Stand Out.

            </h1>


            <span class="rule"></span>


            <p class="hero-desc">

                Timeless streetwear crafted for confidence,
                individuality, and authentic self-expression.

            </p>


            <a
                href="shop.php"
                class="btn btn-light"
            >

                Shop Now

                <span aria-hidden="true">
                    →
                </span>

            </a>


        </div>

    </div>

</section>


<!-- =========================================================
     COLLECTIONS
     ========================================================= -->

<section
    id="collections"
    class="collections"
>

    <div class="wrap">


        <div class="section-heading">

            <p class="eyebrow center">
                Explore Our
            </p>

            <h2>
                Collections
            </h2>

            <span class="rule center"></span>

        </div>


        <div class="collection-grid">


            <?php foreach ($collections as $c): ?>


                <a
                    href="shop.php?cat=<?= htmlspecialchars(
                        $c['slug']
                    ) ?>"
                    class="collection-card"
                    data-slug="<?= htmlspecialchars(
                        $c['slug']
                    ) ?>"
                >


                    <img
                        src="<?= htmlspecialchars(
                            $c['image']
                        ) ?>"
                        alt="<?= htmlspecialchars(
                            $c['name']
                        ) ?> collection"
                        class="collection-photo"
                    >


                    <span class="collection-label">


                        <span class="collection-name">

                            <?= strtoupper(
                                htmlspecialchars(
                                    $c['name']
                                )
                            ) ?>

                        </span>


                        <span class="collection-cta">
                            Shop Now →
                        </span>


                    </span>


                </a>


            <?php endforeach; ?>


        </div>


        <div class="center-btn">

            <a
                href="shop.php"
                class="btn btn-outline"
            >

                View All Collections →

            </a>

        </div>


    </div>

</section>


<!-- =========================================================
     BEST SELLERS
     ========================================================= -->

<section class="best-sellers">

    <div class="wrap">


        <div class="best-sellers-head">


            <h2>
                Best Sellers
            </h2>


            <a
                href="shop.php"
                class="view-all"
            >
                View All →
            </a>


        </div>


        <span class="rule"></span>


        <div class="product-grid">


            <?php foreach ($products as $p): ?>


                <a
                    href="shop.php?cat=<?= htmlspecialchars(
                        $p['category']
                    ) ?>"
                    class="product-card"
                >


                    <div class="product-photo-placeholder">

                        <img
                            src="<?= htmlspecialchars(
                                $p['image']
                            ) ?>"
                            alt="<?= htmlspecialchars(
                                $p['name']
                            ) ?>"
                            class="product-photo"
                        >

                    </div>


                    <h3 class="product-name">

                        <?= strtoupper(
                            htmlspecialchars(
                                $p['name']
                            )
                        ) ?>

                    </h3>


                    <p class="product-price">

                        <?= peso(
                            $p['price']
                        ) ?>

                    </p>


                    <div class="swatches">


                        <?php foreach (
                            $p['swatches'] as $j => $hex
                        ): ?>


                            <span
                                class="swatch <?= $hex === '#ffffff'
                                    ? 'swatch-outline'
                                    : ''
                                ?>"
                                style="background-color: <?= htmlspecialchars(
                                    $hex
                                ) ?>;"
                                aria-label="Color option <?= $j + 1 ?>"
                            ></span>


                        <?php endforeach; ?>


                    </div>


                </a>


            <?php endforeach; ?>


        </div>

    </div>

</section>


<!-- =========================================================
     BRAND STATEMENT
     ========================================================= -->

<section class="brand-statement">


    <div class="brand-copy">


        <h2>

            More Than<br>
            Just Clothing.

        </h2>


        <span class="rule"></span>


        <p>

            ARIP Streetwear is built for those who dare to be
            different. We believe in quality, authenticity,
            and creating pieces that speak for who you are.

        </p>


        <a
            href="about.php"
            class="btn btn-outline-light"
        >
            Learn More →
        </a>


    </div>


    <div class="brand-media">


        <a
            href="https://www.instagram.com/"
            class="ig-badge"
            target="_blank"
            rel="noopener noreferrer"
        >

            Follow Us On Instagram →

        </a>


        <img
            src="assets/brand-statement.jpg"
            alt="ARIP Streetwear brand photo"
            class="brand-photo"
        >


    </div>


</section>


<!-- =========================================================
     NEWSLETTER
     ========================================================= -->

<section class="newsletter">

    <div class="wrap newsletter-inner">


        <div class="newsletter-copy">


            <span
                class="mail-icon"
                aria-hidden="true"
            >

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
                    Join The Movement
                </p>

                <p class="newsletter-sub">

                    Get updates on new drops,
                    exclusive offers, and more.

                </p>

            </div>


        </div>


        <form
            class="newsletter-form"
            method="post"
            action="#"
        >

            <input
                type="email"
                name="email"
                placeholder="Enter your email"
                required
            >

            <button type="submit">
                Subscribe
            </button>

        </form>


    </div>

</section>


<!-- =========================================================
     FOOTER
     ========================================================= -->

<footer class="site-footer">

    <div class="wrap footer-grid">


        <!-- BRAND -->

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


            <p class="footer-slogan">

                Built Different. Made To Stand Out.

            </p>


            <p class="footer-est">

                <?= htmlspecialchars(
                    $site['est']
                ) ?>

            </p>


        </div>


        <!-- FOOTER COLUMNS -->

        <?php foreach (
            $footerColumns as $heading => $links
        ): ?>


            <div class="footer-col">


                <h4>

                    <?= strtoupper(
                        htmlspecialchars(
                            $heading
                        )
                    ) ?>

                </h4>


                <ul>


                    <?php foreach ($links as $link): ?>


                        <?php

                        $href = '#';


                        if ($heading === 'Shop') {

                            $href =
                                $shopColumnLinks[$link]
                                ?? 'shop.php';

                        } elseif (
                            $heading === 'Info' &&
                            $link === 'About Us'
                        ) {

                            $href =
                                'about.php';

                        } elseif (
                            $heading === 'Customer Care' &&
                            $link === 'Contact Us'
                        ) {

                            $href =
                                'contact.php';

                        }

                        ?>


                        <li>

                            <a
                                href="<?= htmlspecialchars(
                                    $href
                                ) ?>"
                            >

                                <?= htmlspecialchars(
                                    $link
                                ) ?>

                            </a>

                        </li>


                    <?php endforeach; ?>


                </ul>


            </div>


        <?php endforeach; ?>


        <!-- CONTACT -->

        <div class="footer-col">


            <h4>
                CONTACT
            </h4>


            <p class="contact-label">
                Email
            </p>


            <p>

                <a
                    href="mailto:<?= htmlspecialchars(
                        $contact['email']
                    ) ?>"
                >

                    <?= htmlspecialchars(
                        $contact['email']
                    ) ?>

                </a>

            </p>


            <p class="contact-label">
                Phone
            </p>


            <p>

                <?= htmlspecialchars(
                    $contact['phone']
                ) ?>

            </p>


            <p class="contact-label">
                Location
            </p>


            <p>

                <?= htmlspecialchars(
                    $contact['location']
                ) ?>

            </p>


        </div>


    </div>


    <!-- COPYRIGHT -->

    <div class="footer-bottom">


        <p>

            &copy;

            <?= htmlspecialchars(
                $site['year']
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