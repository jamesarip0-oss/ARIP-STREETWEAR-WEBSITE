<?php

session_start();

/**
 * ARIP STREETWEAR — Collections Page
 * A richer product-grid page than shop.php: bigger photo hero, and each
 * card uses a "Quick View" link (opens a modal, handled in script.js)
 * instead of an inline Add to Cart button.
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
   CATEGORY FILTERS
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
   PRODUCT COLOR SETS
   ========================================================= */

$swatchSets = [

    'tees' => [
        ['#111111', 'Black'],
        ['#7a7a7a', 'Grey'],
        ['#ffffff', 'White']
    ],

    'hoodies' => [
        ['#111111', 'Black'],
        ['#7a7a7a', 'Grey'],
        ['#c9c9c5', 'Light Grey']
    ],

    'bottoms' => [
        ['#111111', 'Black'],
        ['#4b4f3d', 'Olive'],
        ['#7a7a7a', 'Grey']
    ],

    'accessories' => [
        ['#111111', 'Black'],
        ['#7a7a7a', 'Grey'],
        ['#c9a768', 'Tan']
    ],

];


/* =========================================================
   PRODUCTS
   ========================================================= */

$products = [

    [
        'id'       => 1,
        'name'     => 'ARIP Essential Tee',
        'category' => 'tees',
        'price'    => 700
    ],

    [
        'id'       => 2,
        'name'     => 'ARIP Signature Hoodie',
        'category' => 'hoodies',
        'price'    => 1000
    ],

    [
        'id'       => 3,
        'name'     => 'ARIP Cargo Pants',
        'category' => 'bottoms',
        'price'    => 800
    ],

    [
        'id'       => 4,
        'name'     => 'ARIP Logo Cap',
        'category' => 'accessories',
        'price'    => 400
    ],

];


/* =========================================================
   PESO FORMATTER
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
        Streetwear — Collections
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
                            class="<?= $item === 'Collections'
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


                <!-- LOGGED IN → PROFILE.PHP -->

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
     COLLECTIONS HERO
     ========================================================= -->

<section class="collections-hero">


    <img
        src="assets/Essentials.png"
        alt="Model wearing an ARIP black hoodie"
        class="collections-hero-photo"
    >


    <div class="wrap collections-hero-inner">


        <p class="eyebrow">
            Collections
        </p>


        <h1>
            Timeless Pieces.<br>
            Lasting Impression.
        </h1>


        <p class="hero-desc">

            Explore our full collection of premium streetwear,
            designed for those who move differently.

        </p>


    </div>


</section>


<!-- =========================================================
     TOOLBAR
     ========================================================= -->

<section class="shop-toolbar">

    <div class="wrap toolbar-inner">


        <!-- CATEGORY TABS -->

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
            data-page-size="12"
        >


            <?php foreach ($products as $p): ?>


                <?php

                $swatches =
                    $swatchSets[
                        $p['category']
                    ] ?? [];

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


                    <div class="card-footer-row">


                        <!-- COLOR OPTIONS -->

                        <div
                            class="swatches"
                            role="group"
                            aria-label="Color options"
                        >


                            <?php foreach (
                                $swatches as $j => $s
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


                        <!-- QUICK VIEW -->

                        <button
                            type="button"
                            class="quick-view-btn"
                            data-id="<?= (int) $p['id'] ?>"
                        >
                            Quick View →
                        </button>


                    </div>


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
    class="brand-statement collection-essentials"
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
            href="shop.php"
            class="btn btn-outline-light"
        >
            Shop Essentials →
        </a>


    </div>


    <div class="brand-media">


        <img
            src="assets/contact-hero.jpg"
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
     QUICK VIEW MODAL
     ========================================================= -->

<div
    class="modal-overlay"
    id="quick-view-overlay"
    hidden
>

    <div
        class="modal-box"
        role="dialog"
        aria-modal="true"
        aria-labelledby="qv-name"
    >


        <!-- CLOSE -->

        <button
            type="button"
            class="modal-close"
            id="quick-view-close"
            aria-label="Close quick view"
        >
            &times;
        </button>


        <!-- IMAGE -->

        <div class="modal-photo">

            <img
                src=""
                alt=""
                id="qv-photo"
            >

        </div>


        <!-- DETAILS -->

        <div class="modal-details">


            <h3 id="qv-name"></h3>


            <p
                class="modal-price"
                id="qv-price"
            ></p>


            <div
                class="swatches"
                id="qv-swatches"
                role="group"
                aria-label="Color options"
            ></div>


            <button
                type="button"
                class="btn btn-outline modal-add-btn"
                id="qv-add-to-cart"
            >
                Add to Cart
            </button>


        </div>


    </div>

</div>


<!-- =========================================================
     JAVASCRIPT
     ========================================================= -->

<script
    src="script.js"
    defer
></script>


</body>

</html>