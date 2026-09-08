<?php

session_start();

require_once __DIR__ . '/config/db.php';


/* =========================================================
   LOGIN REQUIRED
   ========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    empty($_SESSION['user_id'])
) {
    header('Location: login.php?checkout=1');
    exit;
}


/* =========================================================
   ADMIN CANNOT ORDER
   ========================================================= */

if (
    ($_SESSION['role'] ?? '') === 'admin'
) {
    header('Location: admin/dashboard.php');
    exit;
}


$userId = (int) $_SESSION['user_id'];


/* =========================================================
   VERIFY CUSTOMER FROM DATABASE
   ========================================================= */

$userStmt = $pdo->prepare("
    SELECT
        user_id,
        full_name,
        username,
        email,
        role
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$userStmt->execute([
    $userId
]);

$currentUser = $userStmt->fetch();


if (!$currentUser) {

    session_unset();
    session_destroy();

    header('Location: login.php');
    exit;
}


/* =========================================================
   BLOCK ADMIN EVEN IF SESSION ROLE IS OLD
   ========================================================= */

if (
    $currentUser['role'] !== 'customer'
) {

    $_SESSION['role'] =
        $currentUser['role'];

    header('Location: admin/dashboard.php');
    exit;
}


/* =========================================================
   SITE DATA
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


/* =========================================================
   FORM DEFAULT VALUES
   ========================================================= */

$checkoutMessage = '';
$checkoutMessageType = '';

$fullName =
    $currentUser['full_name'] ?? '';

$email =
    $currentUser['email'] ?? '';

$phone = '';

$address = '';

$city = '';

$province = '';

$postalCode = '';

$paymentMethod = '';

$gcashName = '';

$gcashNumber = '';

$placedOrderId = 0;


/* =========================================================
   SUCCESS MESSAGE AFTER ORDER
   ========================================================= */

if (
    isset($_GET['success']) &&
    $_GET['success'] === '1'
) {

    $successOrderId =
        isset($_GET['order_id'])
            ? (int) $_GET['order_id']
            : 0;


    if ($successOrderId > 0) {

        $successStmt = $pdo->prepare("
            SELECT
                order_id,
                status
            FROM orders
            WHERE order_id = ?
              AND user_id = ?
            LIMIT 1
        ");

        $successStmt->execute([
            $successOrderId,
            $userId
        ]);

        $successOrder =
            $successStmt->fetch();


        if ($successOrder) {

            $placedOrderId =
                $successOrderId;

            $checkoutMessage =
                'Order #' .
                $placedOrderId .
                ' placed successfully. Your order is now waiting for admin approval.';

            $checkoutMessageType =
                'success';
        }
    }
}


/* =========================================================
   PROCESS CHECKOUT
   ========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    /* -----------------------------------------------------
       CUSTOMER DETAILS
       ----------------------------------------------------- */

    $fullName =
        trim(
            $_POST['full_name']
            ?? ''
        );


    $email =
        trim(
            $_POST['email']
            ?? ''
        );


    $phone =
        trim(
            $_POST['phone']
            ?? ''
        );


    $address =
        trim(
            $_POST['address']
            ?? ''
        );


    $city =
        trim(
            $_POST['city']
            ?? ''
        );


    $province =
        trim(
            $_POST['province']
            ?? ''
        );


    $postalCode =
        trim(
            $_POST['postal_code']
            ?? ''
        );


    $paymentMethod =
        trim(
            $_POST['payment_method']
            ?? ''
        );


    $gcashName =
        trim(
            $_POST['gcash_name']
            ?? ''
        );


    $gcashNumber =
        trim(
            $_POST['gcash_number']
            ?? ''
        );


    $cartJson =
        $_POST['cart_json']
        ?? '';


    /* =====================================================
       VALIDATE CUSTOMER DETAILS
       ===================================================== */

    if (
        $fullName === '' ||
        $email === '' ||
        $phone === '' ||
        $address === '' ||
        $city === '' ||
        $province === '' ||
        $postalCode === '' ||
        $paymentMethod === ''
    ) {

        $checkoutMessage =
            'Please complete all checkout fields.';

        $checkoutMessageType =
            'error';

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $checkoutMessage =
            'Please enter a valid email address.';

        $checkoutMessageType =
            'error';

    } elseif (
        !preg_match(
            '/^09[0-9]{9}$/',
            $phone
        )
    ) {

        $checkoutMessage =
            'Please enter a valid 11-digit Philippine mobile number.';

        $checkoutMessageType =
            'error';

    } elseif (
        !in_array(
            $paymentMethod,
            [
                'Cash on Delivery',
                'GCash'
            ],
            true
        )
    ) {

        $checkoutMessage =
            'Please select a valid payment method.';

        $checkoutMessageType =
            'error';

    } elseif (
        $paymentMethod === 'GCash' &&
        $gcashName === ''
    ) {

        $checkoutMessage =
            'Please enter the GCash account name.';

        $checkoutMessageType =
            'error';

    } elseif (
        $paymentMethod === 'GCash' &&
        !preg_match(
            '/^09[0-9]{9}$/',
            $gcashNumber
        )
    ) {

        $checkoutMessage =
            'Please enter a valid 11-digit GCash number.';

        $checkoutMessageType =
            'error';

    } elseif (
        $cartJson === ''
    ) {

        $checkoutMessage =
            'Your cart is empty.';

        $checkoutMessageType =
            'error';

    } else {


        /* =================================================
           DECODE CART
           ================================================= */

        $cartItems =
            json_decode(
                $cartJson,
                true
            );


        if (
            !is_array($cartItems) ||
            count($cartItems) === 0
        ) {

            $checkoutMessage =
                'Your cart is empty or invalid.';

            $checkoutMessageType =
                'error';

        } else {


            try {

                /* =========================================
                   START TRANSACTION
                   ========================================= */

                $pdo->beginTransaction();


                $validatedItems = [];

                $serverTotal = 0;


                /* =========================================
                   PRODUCT QUERY
                   ========================================= */

                $productStmt = $pdo->prepare("
                    SELECT
                        product_id,
                        name,
                        price,
                        stock
                    FROM products
                    WHERE product_id = ?
                    LIMIT 1
                ");


                /* =========================================
                   COLOR QUERY
                   ========================================= */

                $colorStmt = $pdo->prepare("
                    SELECT
                        color_name
                    FROM product_colors
                    WHERE product_id = ?
                      AND color_name = ?
                    LIMIT 1
                ");


                /* =========================================
                   VALIDATE CART PRODUCTS
                   ========================================= */

                foreach (
                    $cartItems
                    as $cartItem
                ) {

                    $productId =
                        isset($cartItem['id'])
                            ? (int) $cartItem['id']
                            : 0;


                    $quantity =
                        isset($cartItem['quantity'])
                            ? (int) $cartItem['quantity']
                            : 0;


                    $colorName =
                        isset($cartItem['color'])
                            ? trim(
                                (string) $cartItem['color']
                            )
                            : '';


                    if ($productId <= 0) {

                        throw new Exception(
                            'One of the products in your cart is invalid.'
                        );
                    }


                    if (
                        $quantity <= 0 ||
                        $quantity > 99
                    ) {

                        throw new Exception(
                            'Invalid product quantity.'
                        );
                    }


                    /* -------------------------------------
                       GET REAL PRODUCT FROM DATABASE
                       ------------------------------------- */

                    $productStmt->execute([
                        $productId
                    ]);


                    $product =
                        $productStmt->fetch();


                    if (!$product) {

                        throw new Exception(
                            'One of the products in your cart no longer exists.'
                        );
                    }


                    /* -------------------------------------
                       CHECK STOCK
                       ------------------------------------- */

                    if (
                        (int) $product['stock'] <
                        $quantity
                    ) {

                        throw new Exception(
                            $product['name'] .
                            ' only has ' .
                            (int) $product['stock'] .
                            ' item(s) available.'
                        );
                    }


                    /* -------------------------------------
                       VALIDATE COLOR
                       ------------------------------------- */

                    if (
                        $colorName !== '' &&
                        strtolower($colorName) !== 'default'
                    ) {

                        $colorStmt->execute([
                            $productId,
                            $colorName
                        ]);


                        $validColor =
                            $colorStmt->fetch();


                        if (!$validColor) {

                            throw new Exception(
                                'Invalid color selected for ' .
                                $product['name'] .
                                '.'
                            );
                        }

                    } else {

                        $colorName =
                            null;
                    }


                    /* -------------------------------------
                       USE DATABASE PRICE
                       ------------------------------------- */

                    $realPrice =
                        (float) $product['price'];


                    $lineTotal =
                        $realPrice *
                        $quantity;


                    $serverTotal +=
                        $lineTotal;


                    $validatedItems[] = [

                        'product_id' =>
                            $productId,

                        'name' =>
                            $product['name'],

                        'color_name' =>
                            $colorName,

                        'quantity' =>
                            $quantity,

                        'price' =>
                            $realPrice

                    ];

                }


                if (
                    count($validatedItems) === 0 ||
                    $serverTotal <= 0
                ) {

                    throw new Exception(
                        'Your cart does not contain valid products.'
                    );
                }


                /* =================================================
                   CLEAR GCASH DETAILS FOR COD
                   ================================================= */

                if (
                    $paymentMethod ===
                    'Cash on Delivery'
                ) {

                    $gcashName =
                        null;

                    $gcashNumber =
                        null;
                }


                /* =================================================
                   CREATE ORDER
                   ================================================= */

                $orderStmt = $pdo->prepare("
                    INSERT INTO orders
                    (
                        user_id,
                        full_name,
                        email,
                        phone,
                        address,
                        city,
                        province,
                        postal_code,
                        payment_method,
                        gcash_name,
                        gcash_number,
                        total_amount,
                        receipt_number,
                        status,
                        approved_at,
                        completed_at
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        NULL,
                        'Pending',
                        NULL,
                        NULL
                    )
                ");


                $orderStmt->execute([

                    $userId,

                    $fullName,

                    $email,

                    $phone,

                    $address,

                    $city,

                    $province,

                    $postalCode,

                    $paymentMethod,

                    $gcashName,

                    $gcashNumber,

                    $serverTotal

                ]);


                $orderId =
                    (int) $pdo->lastInsertId();


                if ($orderId <= 0) {

                    throw new Exception(
                        'Unable to create order.'
                    );
                }


                /* =================================================
                   INSERT ORDER ITEMS
                   ================================================= */

                $orderItemStmt = $pdo->prepare("
                    INSERT INTO order_items
                    (
                        order_id,
                        product_id,
                        color_name,
                        quantity,
                        price
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");


                /* =================================================
                   STOCK UPDATE
                   ================================================= */

                $stockStmt = $pdo->prepare("
                    UPDATE products
                    SET stock = stock - ?
                    WHERE product_id = ?
                      AND stock >= ?
                ");


                foreach (
                    $validatedItems
                    as $item
                ) {

                    /* -------------------------------------
                       SAVE ORDER ITEM
                       ------------------------------------- */

                    $orderItemStmt->execute([

                        $orderId,

                        $item['product_id'],

                        $item['color_name'],

                        $item['quantity'],

                        $item['price']

                    ]);


                    /* -------------------------------------
                       REDUCE STOCK
                       ------------------------------------- */

                    $stockStmt->execute([

                        $item['quantity'],

                        $item['product_id'],

                        $item['quantity']

                    ]);


                    if (
                        $stockStmt->rowCount() !== 1
                    ) {

                        throw new Exception(
                            $item['name'] .
                            ' does not have enough stock.'
                        );
                    }

                }


                /* =================================================
                   COMMIT ORDER
                   ================================================= */

                $pdo->commit();


                /* =================================================
                   REDIRECT AFTER SUCCESS
                   ================================================= */

                header(
                    'Location: checkout.php?success=1&order_id=' .
                    $orderId
                );

                exit;


            } catch (Throwable $e) {


                if (
                    $pdo->inTransaction()
                ) {

                    $pdo->rollBack();
                }


                $checkoutMessage =
                    $e->getMessage();


                $checkoutMessageType =
                    'error';

            }

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
        Checkout — ARIP Streetwear
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
                    $nav
                    as $item
                ): ?>

                    <li>

                        <a
                            href="<?= htmlspecialchars(
                                $navLinks[$item] ?? '#'
                            ) ?>"
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


            <!-- CUSTOMER PROFILE -->

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
     CHECKOUT PAGE
     ========================================================= -->

<main class="checkout-page">

    <section class="checkout-section">

        <div class="wrap">


            <!-- HEADING -->

            <div class="checkout-heading">

                <p class="eyebrow">
                    SECURE CHECKOUT
                </p>

                <h1>
                    CHECKOUT
                </h1>

            </div>


            <!-- CHECKOUT MESSAGE -->

            <?php if (
                $checkoutMessage !== ''
            ): ?>

                <div
                    class="checkout-message <?= htmlspecialchars(
                        $checkoutMessageType
                    ) ?>"
                >

                    <?= htmlspecialchars(
                        $checkoutMessage
                    ) ?>


                    <?php if (
                        $checkoutMessageType === 'success' &&
                        $placedOrderId > 0
                    ): ?>

                        <div
                            style="
                                margin-top: 16px;
                                display: flex;
                                flex-wrap: wrap;
                                gap: 10px;
                            "
                        >

                            <a
                                href="profile.php"
                                class="btn btn-dark"
                            >
                                VIEW MY ACCOUNT →
                            </a>


                            <a
                                href="shop.php"
                                class="btn btn-dark"
                            >
                                CONTINUE SHOPPING →
                            </a>

                        </div>

                    <?php endif; ?>

                </div>

            <?php endif; ?>


            <!-- EMPTY CART -->

            <div
                id="checkout-empty"
                class="cart-empty"
                hidden
            >

                <h2>
                    YOUR CART IS EMPTY
                </h2>

                <p>
                    Add products before proceeding to checkout.
                </p>

                <a
                    href="shop.php"
                    class="btn btn-dark"
                >
                    SHOP NOW →
                </a>

            </div>


            <!-- CHECKOUT CONTENT -->

            <div
                id="checkout-content"
                class="checkout-layout"
            >


                <!-- =================================================
                     LEFT SIDE
                     ================================================= -->

                <div class="checkout-form-box">


                    <form
                        method="POST"
                        action="checkout.php"
                        id="checkout-form"
                        class="checkout-form"
                    >


                        <!-- CONTACT INFORMATION -->

                        <h2>
                            CONTACT INFORMATION
                        </h2>


                        <label for="checkout-name">
                            Full Name
                        </label>

                        <input
                            type="text"
                            id="checkout-name"
                            name="full_name"
                            value="<?= htmlspecialchars(
                                $fullName
                            ) ?>"
                            placeholder="Enter your full name"
                            required
                        >


                        <label for="checkout-email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="checkout-email"
                            name="email"
                            value="<?= htmlspecialchars(
                                $email
                            ) ?>"
                            placeholder="Enter your email"
                            required
                        >


                        <label for="checkout-phone">
                            Phone Number
                        </label>

                        <input
                            type="tel"
                            id="checkout-phone"
                            name="phone"
                            value="<?= htmlspecialchars(
                                $phone
                            ) ?>"
                            placeholder="09XXXXXXXXX"
                            inputmode="numeric"
                            maxlength="11"
                            required
                        >


                        <!-- SHIPPING -->

                        <h2 class="checkout-form-title">
                            SHIPPING ADDRESS
                        </h2>


                        <label for="checkout-address">
                            Address
                        </label>

                        <input
                            type="text"
                            id="checkout-address"
                            name="address"
                            value="<?= htmlspecialchars(
                                $address
                            ) ?>"
                            placeholder="House no., street, barangay"
                            required
                        >


                        <div class="checkout-form-row">


                            <div>

                                <label for="checkout-city">
                                    City / Municipality
                                </label>

                                <input
                                    type="text"
                                    id="checkout-city"
                                    name="city"
                                    value="<?= htmlspecialchars(
                                        $city
                                    ) ?>"
                                    required
                                >

                            </div>


                            <div>

                                <label for="checkout-province">
                                    Province
                                </label>

                                <input
                                    type="text"
                                    id="checkout-province"
                                    name="province"
                                    value="<?= htmlspecialchars(
                                        $province
                                    ) ?>"
                                    required
                                >

                            </div>


                        </div>


                        <label for="checkout-postal">
                            Postal Code
                        </label>

                        <input
                            type="text"
                            id="checkout-postal"
                            name="postal_code"
                            value="<?= htmlspecialchars(
                                $postalCode
                            ) ?>"
                            required
                        >


                        <!-- PAYMENT METHOD -->

                        <h2 class="checkout-form-title">
                            PAYMENT METHOD
                        </h2>


                        <div class="payment-options">


                            <!-- CASH ON DELIVERY -->

                            <label class="payment-option">

                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="Cash on Delivery"
                                    id="payment-cod"
                                    <?= $paymentMethod ===
                                        'Cash on Delivery'
                                            ? 'checked'
                                            : ''
                                    ?>
                                    required
                                >

                                <span>
                                    Cash on Delivery
                                </span>

                            </label>


                            <!-- GCASH -->

                            <label class="payment-option">

                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="GCash"
                                    id="payment-gcash"
                                    <?= $paymentMethod ===
                                        'GCash'
                                            ? 'checked'
                                            : ''
                                    ?>
                                    required
                                >

                                <span>
                                    GCash
                                </span>

                            </label>


                        </div>


                        <!-- GCASH DETAILS -->

                        <div
                            class="gcash-details"
                            id="gcash-details"
                            hidden
                        >

                            <div class="gcash-details-header">

                                <h3>
                                    GCASH DETAILS
                                </h3>

                                <p>
                                    Enter the GCash account details
                                    that will be used for this order.
                                </p>

                            </div>


                            <div class="gcash-field">

                                <label for="gcash-name">
                                    GCASH ACCOUNT NAME
                                </label>

                                <input
                                    type="text"
                                    id="gcash-name"
                                    name="gcash_name"
                                    value="<?= htmlspecialchars(
                                        $gcashName ?? ''
                                    ) ?>"
                                    placeholder="Enter account name"
                                >

                            </div>


                            <div class="gcash-field">

                                <label for="gcash-number">
                                    GCASH NUMBER
                                </label>

                                <input
                                    type="tel"
                                    id="gcash-number"
                                    name="gcash_number"
                                    value="<?= htmlspecialchars(
                                        $gcashNumber ?? ''
                                    ) ?>"
                                    placeholder="09XXXXXXXXX"
                                    inputmode="numeric"
                                    maxlength="11"
                                    pattern="09[0-9]{9}"
                                >

                                <small>
                                    Example: 09123456789
                                </small>

                            </div>

                        </div>


                        <!-- LOCAL CART JSON -->

                        <input
                            type="hidden"
                            name="cart_json"
                            id="checkout-cart-json"
                        >


                        <!-- DISPLAY TOTAL ONLY -->
                        <!-- PHP DOES NOT TRUST THIS TOTAL -->

                        <input
                            type="hidden"
                            name="cart_total"
                            id="checkout-cart-total"
                        >


                        <button
                            type="submit"
                            class="place-order-btn"
                        >
                            PLACE ORDER →
                        </button>


                    </form>

                </div>


                <!-- =================================================
                     ORDER SUMMARY
                     ================================================= -->

                <aside class="checkout-summary">


                    <h2>
                        YOUR ORDER
                    </h2>


                    <div
                        id="checkout-items"
                        class="checkout-items"
                    >
                    </div>


                    <div class="summary-divider"></div>


                    <div class="summary-row">

                        <span>
                            Subtotal
                        </span>

                        <strong id="checkout-subtotal">
                            ₱0.00
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Shipping
                        </span>

                        <strong>
                            Free
                        </strong>

                    </div>


                    <div class="summary-divider"></div>


                    <div class="summary-row total">

                        <span>
                            Total
                        </span>

                        <strong id="checkout-total">
                            ₱0.00
                        </strong>

                    </div>


                    <a
                        href="cart.php"
                        class="edit-cart-link"
                    >
                        ← EDIT CART
                    </a>


                </aside>


            </div>

        </div>

    </section>

</main>


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
            $footerColumns
            as $heading => $links
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


                    <?php foreach (
                        $links
                        as $link
                    ): ?>


                        <?php

                        $href = '#';


                        if (
                            $heading === 'Shop'
                        ) {

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


    <div class="footer-bottom">

        <p>

            &copy;

            <?= htmlspecialchars(
                $site['year']
            ) ?>

            ARIP STREETWEAR. ALL RIGHTS RESERVED.

        </p>

    </div>

</footer>


<!-- =========================================================
     CHECKOUT CART SCRIPT
     ========================================================= -->

<script>

(function () {

    'use strict';


    const CART_ITEMS_KEY =
        'arip_cart_items';


    const CART_COUNT_KEY =
        'arip_cart_count';


    const ORDER_PLACED =
        <?= $placedOrderId > 0
            ? 'true'
            : 'false'
        ?>;


    /* =====================================================
       CLEAR CART AFTER SUCCESS
       ===================================================== */

    if (ORDER_PLACED) {

        localStorage.removeItem(
            CART_ITEMS_KEY
        );

        localStorage.setItem(
            CART_COUNT_KEY,
            '0'
        );
    }


    const checkoutItems =
        document.getElementById(
            'checkout-items'
        );


    const checkoutSubtotal =
        document.getElementById(
            'checkout-subtotal'
        );


    const checkoutTotal =
        document.getElementById(
            'checkout-total'
        );


    const cartJsonInput =
        document.getElementById(
            'checkout-cart-json'
        );


    const cartTotalInput =
        document.getElementById(
            'checkout-cart-total'
        );


    const checkoutEmpty =
        document.getElementById(
            'checkout-empty'
        );


    const checkoutContent =
        document.getElementById(
            'checkout-content'
        );


    const cartCountEl =
        document.getElementById(
            'cart-count'
        );


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
       RENDER CHECKOUT
       ===================================================== */

    function renderCheckout() {

        const cart =
            getCart();


        if (checkoutItems) {

            checkoutItems.innerHTML =
                '';
        }


        /* =================================================
           EMPTY CART
           ================================================= */

        if (
            cart.length === 0
        ) {

            if (checkoutEmpty) {

                checkoutEmpty.hidden =
                    ORDER_PLACED;
            }


            if (checkoutContent) {

                checkoutContent.hidden =
                    true;
            }


            if (cartCountEl) {

                cartCountEl.textContent =
                    '0';

                cartCountEl.hidden =
                    true;
            }


            if (cartJsonInput) {

                cartJsonInput.value =
                    '[]';
            }


            if (cartTotalInput) {

                cartTotalInput.value =
                    '0.00';
            }


            return;
        }


        /* =================================================
           CART HAS ITEMS
           ================================================= */

        if (checkoutEmpty) {

            checkoutEmpty.hidden =
                true;
        }


        if (checkoutContent) {

            checkoutContent.hidden =
                false;
        }


        let subtotal = 0;

        let totalQuantity = 0;


        cart.forEach(
            function (item) {


                const price =
                    Number(
                        item.price || 0
                    );


                const quantity =
                    Math.max(
                        1,
                        Number(
                            item.quantity || 1
                        )
                    );


                const lineTotal =
                    price *
                    quantity;


                subtotal +=
                    lineTotal;


                totalQuantity +=
                    quantity;


                if (!checkoutItems) {

                    return;
                }


                /* =========================================
                   ITEM
                   ========================================= */

                const row =
                    document.createElement(
                        'div'
                    );


                row.className =
                    'checkout-item';


                /* =========================================
                   IMAGE
                   ========================================= */

                const photoWrap =
                    document.createElement(
                        'div'
                    );


                photoWrap.className =
                    'checkout-item-photo';


                const image =
                    document.createElement(
                        'img'
                    );


                image.src =
                    item.image || '';


                image.alt =
                    item.name ||
                    'ARIP Product';


                photoWrap.appendChild(
                    image
                );


                /* =========================================
                   QUANTITY BADGE
                   ========================================= */

                const quantityBadge =
                    document.createElement(
                        'span'
                    );


                quantityBadge.className =
                    'checkout-item-qty';


                quantityBadge.textContent =
                    String(quantity);


                photoWrap.appendChild(
                    quantityBadge
                );


                /* =========================================
                   DETAILS
                   ========================================= */

                const details =
                    document.createElement(
                        'div'
                    );


                details.className =
                    'checkout-item-details';


                const productName =
                    document.createElement(
                        'strong'
                    );


                productName.textContent =
                    item.name ||
                    'ARIP PRODUCT';


                details.appendChild(
                    productName
                );


                const color =
                    document.createElement(
                        'span'
                    );


                color.textContent =
                    item.color ||
                    'Default';


                details.appendChild(
                    color
                );


                /* =========================================
                   PRICE
                   ========================================= */

                const priceElement =
                    document.createElement(
                        'strong'
                    );


                priceElement.className =
                    'checkout-item-price';


                priceElement.textContent =
                    peso(
                        lineTotal
                    );


                /* =========================================
                   APPEND
                   ========================================= */

                row.appendChild(
                    photoWrap
                );


                row.appendChild(
                    details
                );


                row.appendChild(
                    priceElement
                );


                checkoutItems.appendChild(
                    row
                );

            }
        );


        /* =================================================
           DISPLAY TOTAL
           ================================================= */

        if (checkoutSubtotal) {

            checkoutSubtotal.textContent =
                peso(
                    subtotal
                );
        }


        if (checkoutTotal) {

            checkoutTotal.textContent =
                peso(
                    subtotal
                );
        }


        /* =================================================
           CART JSON
           ================================================= */

        if (cartJsonInput) {

            cartJsonInput.value =
                JSON.stringify(
                    cart
                );
        }


        /* =================================================
           CLIENT TOTAL
           DISPLAY ONLY
           ================================================= */

        if (cartTotalInput) {

            cartTotalInput.value =
                subtotal.toFixed(
                    2
                );
        }


        /* =================================================
           CART BADGE
           ================================================= */

        localStorage.setItem(
            CART_COUNT_KEY,
            String(
                totalQuantity
            )
        );


        if (cartCountEl) {

            cartCountEl.textContent =
                String(
                    totalQuantity
                );


            cartCountEl.hidden =
                totalQuantity === 0;
        }
    }


    renderCheckout();


    /* =====================================================
       PREVENT EMPTY CART SUBMIT
       ===================================================== */

    const checkoutForm =
        document.getElementById(
            'checkout-form'
        );


    if (checkoutForm) {

        checkoutForm.addEventListener(
            'submit',
            function (event) {


                const cart =
                    getCart();


                if (
                    !Array.isArray(cart) ||
                    cart.length === 0
                ) {

                    event.preventDefault();


                    alert(
                        'Your cart is empty.'
                    );


                    return;
                }


                /* REFRESH CART JSON */

                if (cartJsonInput) {

                    cartJsonInput.value =
                        JSON.stringify(
                            cart
                        );
                }

            }
        );
    }


    /* =====================================================
       CART BUTTON
       ===================================================== */

    const cartBtn =
        document.getElementById(
            'cart-btn'
        );


    if (cartBtn) {

        cartBtn.addEventListener(
            'click',
            function () {

                window.location.href =
                    'cart.php';
            }
        );
    }

})();

</script>


<!-- =========================================================
     PAYMENT / GCASH SCRIPT
     ========================================================= -->

<script>

(function () {

    'use strict';


    const gcashRadio =
        document.getElementById(
            'payment-gcash'
        );


    const codRadio =
        document.getElementById(
            'payment-cod'
        );


    const gcashDetails =
        document.getElementById(
            'gcash-details'
        );


    const gcashName =
        document.getElementById(
            'gcash-name'
        );


    const gcashNumber =
        document.getElementById(
            'gcash-number'
        );


    const checkoutPhone =
        document.getElementById(
            'checkout-phone'
        );


    if (
        !gcashRadio ||
        !codRadio ||
        !gcashDetails
    ) {

        return;
    }


    /* =====================================================
       PAYMENT FIELDS
       ===================================================== */

    function updatePaymentFields() {

        if (
            gcashRadio.checked
        ) {

            gcashDetails.hidden =
                false;


            gcashDetails.classList.add(
                'show'
            );


            if (gcashName) {

                gcashName.required =
                    true;
            }


            if (gcashNumber) {

                gcashNumber.required =
                    true;
            }

        } else {

            gcashDetails.hidden =
                true;


            gcashDetails.classList.remove(
                'show'
            );


            if (gcashName) {

                gcashName.required =
                    false;
            }


            if (gcashNumber) {

                gcashNumber.required =
                    false;
            }
        }
    }


    gcashRadio.addEventListener(
        'change',
        updatePaymentFields
    );


    codRadio.addEventListener(
        'change',
        updatePaymentFields
    );


    /* =====================================================
       GCASH NUMBER DIGITS ONLY
       ===================================================== */

    if (gcashNumber) {

        gcashNumber.addEventListener(
            'input',
            function () {

                gcashNumber.value =
                    gcashNumber.value
                        .replace(
                            /\D/g,
                            ''
                        )
                        .slice(
                            0,
                            11
                        );
            }
        );
    }


    /* =====================================================
       PHONE DIGITS ONLY
       ===================================================== */

    if (checkoutPhone) {

        checkoutPhone.addEventListener(
            'input',
            function () {

                checkoutPhone.value =
                    checkoutPhone.value
                        .replace(
                            /\D/g,
                            ''
                        )
                        .slice(
                            0,
                            11
                        );
            }
        );
    }


    updatePaymentFields();

})();

</script>


<!-- =========================================================
     MAIN JS
     ========================================================= -->

<script
    src="script.js"
    defer
></script>


</body>

</html>