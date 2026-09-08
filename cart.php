<?php

session_start();

require_once __DIR__ . '/config/db.php';


/* =========================================================
   ADMIN CANNOT ACCESS CART
   ========================================================= */

if (
    isset($_SESSION['user_id']) &&
    ($_SESSION['role'] ?? '') === 'admin'
) {
    header('Location: admin/dashboard.php');
    exit;
}


/* =========================================================
   SITE DATA
   Same as index.php
   ========================================================= */

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
        Cart — ARIP Streetwear
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<!-- =========================================================
     HEADER
     SAME AS HOME PAGE
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
                    <?= htmlspecialchars($site['name']) ?>
                </strong>

                <em>
                    <?= htmlspecialchars($site['tagline']) ?>
                </em>

            </span>

        </a>


        <!-- NAVIGATION -->

        <nav class="main-nav">

            <ul>

                <?php foreach ($nav as $item): ?>

                    <li>

                        <a
                            href="<?= htmlspecialchars(
                                $navLinks[$item] ?? '#'
                            ) ?>"
                        >

                            <?= strtoupper(
                                htmlspecialchars($item)
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
     CART PAGE
     ========================================================= -->

<main class="cart-page">

    <section class="cart-section">

        <div class="wrap">


            <!-- HEADING -->

            <div class="cart-heading">

                <p class="eyebrow">
                    YOUR BAG
                </p>

                <h1>
                    SHOPPING CART
                </h1>

            </div>


            <!-- EMPTY CART -->

            <div
                id="cart-empty"
                class="cart-empty"
                hidden
            >

                <h2>
                    YOUR CART IS EMPTY
                </h2>

                <p>
                    Add your favorite ARIP pieces before checking out.
                </p>

                <a
                    href="shop.php"
                    class="btn btn-dark"
                >
                    SHOP NOW →
                </a>

            </div>


            <!-- CART CONTENT -->

            <div
                id="cart-content"
                class="cart-layout"
            >


                <!-- CART ITEMS -->

                <div class="cart-items-area">

                    <div
                        id="cart-items"
                        class="cart-items"
                    >
                    </div>

                </div>


                <!-- ORDER SUMMARY -->

                <aside class="cart-summary">

                    <h2>
                        ORDER SUMMARY
                    </h2>


                    <div class="summary-row">

                        <span>
                            Subtotal
                        </span>

                        <strong id="cart-subtotal">
                            ₱0.00
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Shipping
                        </span>

                        <strong>
                            Calculated at checkout
                        </strong>

                    </div>


                    <div class="summary-divider"></div>


                    <div class="summary-row total">

                        <span>
                            Total
                        </span>

                        <strong id="cart-total">
                            ₱0.00
                        </strong>

                    </div>


                    <button
                        type="button"
                        id="checkout-btn"
                        class="checkout-btn"
                    >
                        PROCEED TO CHECKOUT →
                    </button>


                    <a
                        href="shop.php"
                        class="continue-shopping"
                    >
                        ← CONTINUE SHOPPING
                    </a>

                </aside>


            </div>

        </div>

    </section>

</main>


<!-- =========================================================
     FOOTER
     SAME AS HOME PAGE
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
                        <?= htmlspecialchars($site['name']) ?>
                    </strong>

                    <em>
                        <?= htmlspecialchars($site['tagline']) ?>
                    </em>

                </span>

            </a>


            <p class="footer-slogan">
                Built Different. Made To Stand Out.
            </p>


            <p class="footer-est">
                <?= htmlspecialchars($site['est']) ?>
            </p>

        </div>


        <!-- FOOTER COLUMNS -->

        <?php foreach ($footerColumns as $heading => $links): ?>

            <div class="footer-col">

                <h4>
                    <?= strtoupper(
                        htmlspecialchars($heading)
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
                                href="<?= htmlspecialchars($href) ?>"
                            >
                                <?= htmlspecialchars($link) ?>
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
            <?= htmlspecialchars($site['year']) ?>
            ARIP STREETWEAR. ALL RIGHTS RESERVED.

        </p>

    </div>

</footer>


<!-- =========================================================
     CART JAVASCRIPT
     ========================================================= -->

<script>

(function () {

    'use strict';


    const CART_ITEMS_KEY =
        'arip_cart_items';


    const CART_COUNT_KEY =
        'arip_cart_count';


    const cartItemsContainer =
        document.getElementById(
            'cart-items'
        );


    const cartEmpty =
        document.getElementById(
            'cart-empty'
        );


    const cartContent =
        document.getElementById(
            'cart-content'
        );


    const subtotalEl =
        document.getElementById(
            'cart-subtotal'
        );


    const totalEl =
        document.getElementById(
            'cart-total'
        );


    const checkoutBtn =
        document.getElementById(
            'checkout-btn'
        );


    const cartCountEl =
        document.getElementById(
            'cart-count'
        );


    /* =====================================================
       PESO FORMAT
       ===================================================== */

    function peso(amount) {

        return (
            '₱' +
            Number(amount).toLocaleString(
                'en-PH',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            )
        );

    }


    /* =====================================================
       GET CART
       ===================================================== */

    function getCart() {

        try {

            const saved =
                localStorage.getItem(
                    CART_ITEMS_KEY
                );


            const parsed =
                saved
                    ? JSON.parse(saved)
                    : [];


            return Array.isArray(parsed)
                ? parsed
                : [];

        } catch (error) {

            return [];

        }

    }


    /* =====================================================
       SAVE CART
       ===================================================== */

    function saveCart(cart) {

        localStorage.setItem(
            CART_ITEMS_KEY,
            JSON.stringify(cart)
        );


        updateCartCount(
            cart
        );

    }


    /* =====================================================
       CART COUNT
       ===================================================== */

    function updateCartCount(cart) {

        const count =
            cart.reduce(
                function (total, item) {

                    return (
                        total +
                        Number(
                            item.quantity || 1
                        )
                    );

                },
                0
            );


        localStorage.setItem(
            CART_COUNT_KEY,
            String(count)
        );


        if (cartCountEl) {

            cartCountEl.textContent =
                String(count);


            cartCountEl.hidden =
                count === 0;

        }

    }


    /* =====================================================
       RENDER CART
       ===================================================== */

    function renderCart() {

        const cart =
            getCart();


        cartItemsContainer.innerHTML =
            '';


        /* EMPTY CART */

        if (
            cart.length === 0
        ) {

            cartEmpty.hidden =
                false;


            cartContent.hidden =
                true;


            subtotalEl.textContent =
                peso(0);


            totalEl.textContent =
                peso(0);


            updateCartCount(
                []
            );


            return;

        }


        cartEmpty.hidden =
            true;


        cartContent.hidden =
            false;


        let subtotal =
            0;


        cart.forEach(
            function (item, index) {

                const quantity =
                    Math.max(
                        1,
                        Number(
                            item.quantity || 1
                        )
                    );


                const price =
                    Number(
                        item.price || 0
                    );


                subtotal +=
                    price *
                    quantity;


                const row =
                    document.createElement(
                        'article'
                    );


                row.className =
                    'cart-item';


                row.innerHTML = `

                    <div class="cart-item-photo">

                        <img
                            src="${item.image || ''}"
                            alt="${item.name || 'ARIP Product'}"
                        >

                    </div>


                    <div class="cart-item-details">

                        <h3>
                            ${item.name || 'ARIP PRODUCT'}
                        </h3>


                        <p class="cart-item-price">
                            ${peso(price)}
                        </p>


                        <p class="cart-item-color">

                            Color:

                            <strong>
                                ${item.color || 'Default'}
                            </strong>

                        </p>


                        <div class="cart-quantity">

                            <button
                                type="button"
                                class="qty-btn minus"
                                data-index="${index}"
                                aria-label="Decrease quantity"
                            >
                                −
                            </button>


                            <span>
                                ${quantity}
                            </span>


                            <button
                                type="button"
                                class="qty-btn plus"
                                data-index="${index}"
                                aria-label="Increase quantity"
                            >
                                +
                            </button>

                        </div>


                        <button
                            type="button"
                            class="remove-cart-item"
                            data-index="${index}"
                        >
                            REMOVE
                        </button>

                    </div>


                    <div class="cart-item-total">

                        ${peso(
                            price *
                            quantity
                        )}

                    </div>

                `;


                cartItemsContainer.appendChild(
                    row
                );

            }
        );


        subtotalEl.textContent =
            peso(
                subtotal
            );


        totalEl.textContent =
            peso(
                subtotal
            );


        updateCartCount(
            cart
        );

    }


    /* =====================================================
       CART BUTTON EVENTS
       ===================================================== */

    cartItemsContainer.addEventListener(
        'click',
        function (event) {

            const cart =
                getCart();


            const plus =
                event.target.closest(
                    '.plus'
                );


            const minus =
                event.target.closest(
                    '.minus'
                );


            const remove =
                event.target.closest(
                    '.remove-cart-item'
                );


            /* PLUS */

            if (plus) {

                const index =
                    Number(
                        plus.dataset.index
                    );


                if (!cart[index]) {
                    return;
                }


                cart[index].quantity =
                    Number(
                        cart[index].quantity || 1
                    ) + 1;


                saveCart(
                    cart
                );


                renderCart();


                return;

            }


            /* MINUS */

            if (minus) {

                const index =
                    Number(
                        minus.dataset.index
                    );


                if (!cart[index]) {
                    return;
                }


                const current =
                    Number(
                        cart[index].quantity || 1
                    );


                if (
                    current > 1
                ) {

                    cart[index].quantity =
                        current - 1;

                } else {

                    cart.splice(
                        index,
                        1
                    );

                }


                saveCart(
                    cart
                );


                renderCart();


                return;

            }


            /* REMOVE */

            if (remove) {

                const index =
                    Number(
                        remove.dataset.index
                    );


                if (!cart[index]) {
                    return;
                }


                cart.splice(
                    index,
                    1
                );


                saveCart(
                    cart
                );


                renderCart();

            }

        }
    );


    /* =====================================================
       CHECKOUT BUTTON
       ===================================================== */

    if (checkoutBtn) {

        checkoutBtn.addEventListener(
            'click',
            function () {

                const cart =
                    getCart();


                if (
                    cart.length === 0
                ) {

                    return;

                }


                window.location.href =
                    'checkout.php';

            }
        );

    }


    /* =====================================================
       INIT
       ===================================================== */

    renderCart();

})();

</script>


<!-- MAIN SCRIPT -->

<script src="script.js" defer></script>


</body>

</html>