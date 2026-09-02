<?php

session_start();

/**
 * ARIP STREETWEAR — Shop Page
 * Product grid with category tabs, sorting, and pagination.
 * All interactivity (filter / sort / paginate / wishlist / swatches / newsletter)
 * lives in script.js and works entirely client-side against the markup below.
 */

require_once __DIR__ . '/config/db.php';


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


/* =========================================================
   NAVIGATION LINKS
   ========================================================= */

$navLinks = [
    'Home'        => 'index.php',
    'Shop'        => 'shop.php',
    'Collections' => 'collections.php',
    'About'       => 'about.php',
    'Contact'     => 'contact.php',
];


/* =========================================================
   CATEGORY TABS
   ========================================================= */

$categories = [

    [
        'label' => 'All',
        'slug'  => 'all'
    ],

    [
        'label' => 'Tees',
        'slug'  => 'tees'
    ],

    [
        'label' => 'Hoodies',
        'slug'  => 'hoodies'
    ],

    [
        'label' => 'Bottoms',
        'slug'  => 'bottoms'
    ],

    [
        'label' => 'Accessories',
        'slug'  => 'accessories'
    ],

];


/* =========================================================
   SORT OPTIONS
   ========================================================= */

$sortOptions = [
    'newest'     => 'Newest',
    'price-asc'  => 'Price: Low to High',
    'price-desc' => 'Price: High to Low',
    'name-asc'   => 'Name: A-Z',
];


/* =========================================================
   PRODUCTS
   ========================================================= */

$products = [

    [
        'id'       => 1,
        'name'     => 'ARIP Essential Tee',
        'category' => 'tees',
        'price'    => 700,

        'swatches' => [
            ['#111111', 'Black'],
            ['#7a7a7a', 'Grey'],
            ['#ffffff', 'White']
        ]
    ],


    [
        'id'       => 2,
        'name'     => 'ARIP Signature Hoodie',
        'category' => 'hoodies',
        'price'    => 1000,

        'swatches' => [
            ['#111111', 'Black'],
            ['#7a7a7a', 'Grey'],
            ['#ffffff', 'White']
        ]
    ],


    [
        'id'       => 3,
        'name'     => 'ARIP Cargo Pants',
        'category' => 'bottoms',
        'price'    => 800,

        'swatches' => [
            ['#111111', 'Black'],
            ['#7a7a7a', 'Grey'],
            ['#ffffff', 'White']
        ]
    ],


    [
        'id'       => 4,
        'name'     => 'ARIP Logo Cap',
        'category' => 'accessories',
        'price'    => 400,

        'swatches' => [
            ['#111111', 'Black'],
            ['#7a7a7a', 'Grey'],
            ['#ffffff', 'White']
        ]
    ],

];


/* =========================================================
   PRODUCT IMAGES
   ========================================================= */

$categoryImages = [

    'tees' =>
        'assets/product-tee.png',

    'hoodies' =>
        'assets/product-hoodie.png',

    'bottoms' =>
        'assets/product-pants.png',

    'accessories' =>
        'assets/product-cap.png',

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
        Streetwear — Shop
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

                <?php foreach ($nav as $item): ?>

                    <li>

                        <a
                            href="<?= htmlspecialchars(
                                $navLinks[$item] ?? '#'
                            ) ?>"
                            class="<?= $item === 'Shop'
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

                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>

                    <!-- ADMIN → DASHBOARD -->

                    <a
                        href="admin/dashboard.php"
                        class="header-icon-btn"
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

                    <!-- CUSTOMER → PROFILE -->

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

                <?php endif; ?>

            <?php else: ?>

                <!-- LOGGED OUT → LOGIN.PHP -->

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

            <?php if (($_SESSION['role'] ?? '') !== 'admin'): ?>

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

            <?php endif; ?>


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
     SHOP HERO
     ========================================================= -->

<section class="shop-hero">


    <img
        src="assets/contact-hero.jpg"
        alt="ARIP Streetwear"
        class="shop-hero-photo"
    >


    <div class="shop-hero-overlay"></div>


    <div class="wrap shop-hero-inner">

        <div class="shop-hero-copy">


            <p class="eyebrow">
                Timeless Streetwear
            </p>


            <h1>
                The ARIP Shop
            </h1>


            <span class="rule"></span>


            <p class="hero-desc">
                Built different. Made to stand out.
            </p>


            <a
                href="collections.php"
                class="btn btn-outline-light"
            >
                Explore The Collection →
            </a>


        </div>

    </div>


</section>


<!-- =========================================================
     SHOP TOOLBAR
     ========================================================= -->

<section class="shop-toolbar">

    <div class="wrap toolbar-inner">


        <!-- CATEGORY FILTER -->

        <div
            class="category-tabs"
            id="category-tabs"
            role="tablist"
            aria-label="Filter products by category"
        >


            <?php foreach (
                $categories as $i => $c
            ): ?>


                <button
                    type="button"
                    class="tab-btn <?= $i === 0
                        ? 'active'
                        : ''
                    ?>"
                    data-category="<?= htmlspecialchars(
                        $c['slug']
                    ) ?>"
                    role="tab"
                    aria-selected="<?= $i === 0
                        ? 'true'
                        : 'false'
                    ?>"
                >

                    <?= strtoupper(
                        htmlspecialchars(
                            $c['label']
                        )
                    ) ?>

                </button>


                <?php if (
                    $i < count($categories) - 1
                ): ?>

                    <span class="tab-sep">
                        |
                    </span>

                <?php endif; ?>


            <?php endforeach; ?>


        </div>


        <!-- SORT -->

        <div class="sort-control">

            <label for="sort-select">
                Sort By
            </label>


            <select id="sort-select">


                <?php foreach (
                    $sortOptions as $value => $label
                ): ?>


                    <option
                        value="<?= htmlspecialchars(
                            $value
                        ) ?>"
                    >

                        <?= htmlspecialchars(
                            $label
                        ) ?>

                    </option>


                <?php endforeach; ?>


            </select>


        </div>


    </div>

</section>


<!-- =========================================================
     PRODUCT GRID
     ========================================================= -->

<section class="shop-products">

    <div class="wrap">


        <p
            class="results-count"
            id="results-count"
            aria-live="polite"
        ></p>


        <div
            class="product-grid"
            id="product-grid"
        >


            <?php foreach ($products as $p): ?>


                <?php

                $productImage =
                    $categoryImages[
                        $p['category']
                    ] ?? '';

                ?>


                <article
                    class="product-card"

                    data-id="<?= (int) $p['id'] ?>"

                    data-product-id="<?= (int) $p['id'] ?>"

                    data-category="<?= htmlspecialchars(
                        $p['category']
                    ) ?>"

                    data-price="<?= htmlspecialchars(
                        (string) $p['price']
                    ) ?>"

                    data-name="<?= htmlspecialchars(
                        $p['name']
                    ) ?>"

                    data-image="<?= htmlspecialchars(
                        $productImage
                    ) ?>"
                >


                    <!-- PRODUCT IMAGE -->

                    <div class="product-photo-placeholder">


                        <img
                            src="<?= htmlspecialchars(
                                $productImage
                            ) ?>"
                            alt="<?= htmlspecialchars(
                                $p['name']
                            ) ?>"
                            class="product-photo"
                        >


                        <!-- WISHLIST -->

                        <button
                            type="button"
                            class="wishlist-btn"
                            data-id="<?= (int) $p['id'] ?>"
                            aria-label="Add <?= htmlspecialchars(
                                $p['name']
                            ) ?> to wishlist"
                            aria-pressed="false"
                        >

                            <svg viewBox="0 0 24 24">

                                <path
                                    d="M12 21s-7.5-4.6-10-9.2C.5 8.3 2.3 5 5.8 5c2 0 3.5 1.1 4.2 2.4C10.7 6.1 12.2 5 14.2 5c3.5 0 5.3 3.3 3.8 6.8C19.5 16.4 12 21 12 21z"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                />

                            </svg>

                        </button>


                    </div>


                    <!-- PRODUCT NAME -->

                    <h3 class="product-name">

                        <?= strtoupper(
                            htmlspecialchars(
                                $p['name']
                            )
                        ) ?>

                    </h3>


                    <!-- PRODUCT PRICE -->

                    <p class="product-price">

                        <?= peso(
                            $p['price']
                        ) ?>

                    </p>


                    <!-- COLORS -->

                    <div
                        class="swatches"
                        role="group"
                        aria-label="Color options"
                    >


                        <?php foreach (
                            $p['swatches'] as $j => $s
                        ): ?>


                            <?php

                            [$hex, $colorName] =
                                $s;

                            ?>


                            <button
                                type="button"

                                class="swatch
                                <?= $hex === '#ffffff'
                                    ? 'swatch-outline'
                                    : ''
                                ?>
                                <?= $j === 0
                                    ? 'selected'
                                    : ''
                                ?>"

                                style="background-color: <?= htmlspecialchars(
                                    $hex
                                ) ?>;"

                                data-color="<?= htmlspecialchars(
                                    $colorName
                                ) ?>"

                                aria-label="<?= htmlspecialchars(
                                    $colorName
                                ) ?>"

                                aria-pressed="<?= $j === 0
                                    ? 'true'
                                    : 'false'
                                ?>"
                            ></button>


                        <?php endforeach; ?>


                    </div>


                    <!-- ADD TO CART -->

                    <?php if (($_SESSION['role'] ?? '') !== 'admin'): ?>

                        <button
                            type="button"
                            class="btn btn-outline add-to-cart-btn"

                            data-id="<?= (int) $p['id'] ?>"

                            data-name="<?= htmlspecialchars(
                                $p['name']
                            ) ?>"

                            data-price="<?= htmlspecialchars(
                                (string) $p['price']
                            ) ?>"
                        >

                            Add to Cart

                        </button>

                    <?php endif; ?>


                </article>


            <?php endforeach; ?>


        </div>


        <!-- NO RESULTS -->

        <p
            class="no-results"
            id="no-results"
            hidden
        >
            No products match this filter.
        </p>


        <!-- PAGINATION -->

        <nav
            class="pagination"
            id="pagination"
            aria-label="Product pages"
        ></nav>


    </div>

</section>


<!-- =========================================================
     FEATURED COLLECTION
     ========================================================= -->

<section
    class="brand-statement shop-brand-statement"
>


    <div class="brand-copy">


        <p
            class="eyebrow"
            style="color:#cfcfca;"
        >
            Featured Collection
        </p>


        <h2>
            ARIP Essentials
        </h2>


        <span class="rule"></span>


        <p>

            Designed for everyday wear.
            Premium quality.
            Timeless style.
            Built around the ARIP identity.

        </p>


        <a
            href="collections.php"
            class="btn btn-outline-light"
        >
            Shop Essentials →
        </a>


    </div>


    <div class="brand-media">


        <img
            src="assets/images/about/mission.jpg"
            alt="ARIP Essentials"
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
            id="newsletter-form"
            method="post"
            action="#"
            novalidate
        >


            <input
                type="email"
                name="email"
                id="newsletter-email"
                placeholder="Enter your email"
                required
            >


            <button type="submit">
                Subscribe
            </button>


        </form>


        <p
            class="newsletter-msg"
            id="newsletter-msg"
            role="status"
            aria-live="polite"
            hidden
        ></p>


    </div>

</section>


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

                <?= htmlspecialchars(
                    $site['est']
                ) ?>

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

<script src="script.js"></script>


</body>

</html>