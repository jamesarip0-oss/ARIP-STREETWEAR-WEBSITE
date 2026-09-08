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
    header('Location: login.php');
    exit;
}


/* =========================================================
   ADMIN SHOULD NOT USE CUSTOMER PROFILE
   ========================================================= */

if (
    ($_SESSION['role'] ?? '') === 'admin'
) {
    header('Location: admin/dashboard.php');
    exit;
}


$userId = (int) $_SESSION['user_id'];


/* =========================================================
   GET CUSTOMER
   ========================================================= */

$userStmt = $pdo->prepare("
    SELECT
        user_id,
        full_name,
        username,
        email,
        role,
        created_at
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$userStmt->execute([
    $userId
]);

$user = $userStmt->fetch();


if (!$user) {

    session_unset();
    session_destroy();

    header('Location: login.php');
    exit;
}


/* =========================================================
   VERIFY ROLE FROM DATABASE
   ========================================================= */

if ($user['role'] !== 'customer') {

    $_SESSION['role'] =
        $user['role'];

    header('Location: admin/dashboard.php');
    exit;
}


/* =========================================================
   REFRESH SESSION DATA
   ========================================================= */

$_SESSION['user_id'] =
    (int) $user['user_id'];

$_SESSION['full_name'] =
    $user['full_name'];

$_SESSION['username'] =
    $user['username'];

$_SESSION['email'] =
    $user['email'];

$_SESSION['role'] =
    $user['role'];


/* =========================================================
   LOAD CUSTOMER ORDERS
   ========================================================= */

$orderStmt = $pdo->prepare("
    SELECT
        order_id,
        total_amount,
        payment_method,
        receipt_number,
        status,
        approved_at,
        completed_at,
        created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$orderStmt->execute([
    $userId
]);

$orders = $orderStmt->fetchAll();


/* =========================================================
   ORDER COUNTS
   ========================================================= */

$totalOrders = count($orders);

$pendingOrders = 0;

$processingOrders = 0;

$completedOrders = 0;


foreach ($orders as $order) {

    if ($order['status'] === 'Pending') {
        $pendingOrders++;
    }

    if (
        $order['status'] === 'Processing' ||
        $order['status'] === 'Shipped'
    ) {
        $processingOrders++;
    }

    if ($order['status'] === 'Completed') {
        $completedOrders++;
    }
}


/* =========================================================
   CHECK IF NOTIFICATIONS TABLE EXISTS

   This prevents an error if the notifications table
   has not been created yet.
   ========================================================= */

$notificationsTableExists = false;

$notifications = [];

$unreadNotifications = 0;


try {

    $notificationTableStmt =
        $pdo->query("
            SHOW TABLES LIKE 'notifications'
        ");


    $notificationsTableExists =
        (bool) $notificationTableStmt->fetchColumn();


    if ($notificationsTableExists) {

        $notificationStmt =
            $pdo->prepare("
                SELECT
                    notification_id,
                    order_id,
                    title,
                    message,
                    is_read,
                    created_at
                FROM notifications
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT 10
            ");


        $notificationStmt->execute([
            $userId
        ]);


        $notifications =
            $notificationStmt->fetchAll();


        foreach (
            $notifications
            as $notification
        ) {

            if (
                (int) $notification['is_read'] === 0
            ) {

                $unreadNotifications++;
            }
        }
    }

} catch (PDOException $e) {

    $notificationsTableExists = false;

    $notifications = [];

    $unreadNotifications = 0;
}


/* =========================================================
   CUSTOMER INITIAL
   ========================================================= */

$initial = 'A';


if (
    !empty(
        $user['full_name']
    )
) {

    $initial =
        strtoupper(
            substr(
                trim(
                    $user['full_name']
                ),
                0,
                1
            )
        );
}


/* =========================================================
   MEMBER SINCE
   ========================================================= */

$memberSince =
    !empty($user['created_at'])
        ? date(
            'F Y',
            strtotime(
                $user['created_at']
            )
        )
        : '—';


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
   ORDER STATUS MESSAGE
   ========================================================= */

function getOrderMessage(string $status): string
{
    switch ($status) {

        case 'Pending':
            return 'Waiting for admin approval.';

        case 'Processing':
            return 'Your order has been approved and is being processed.';

        case 'Shipped':
            return 'Your order has been shipped.';

        case 'Completed':
            return 'Your order has been completed.';

        case 'Cancelled':
            return 'This order has been cancelled.';

        default:
            return '';
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
        My Account — ARIP Streetwear
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >


    <style>

        /* =====================================================
           PROFILE ORDERS / NOTIFICATIONS
           ===================================================== */

        .account-extra-section {
            padding: 0 0 90px;
            background: #f4f4f2;
        }


        .account-section-block {
            margin-top: 32px;
            background: #fff;
            border: 1px solid #dedede;
        }


        .account-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 25px 28px;
            border-bottom: 1px solid #dedede;
        }


        .account-section-header h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -.02em;
            text-transform: uppercase;
        }


        .account-section-header span {
            color: #777;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }


        /* =====================================================
           ACCOUNT STATS
           ===================================================== */

        .profile-order-stats {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 1px;
            background: #ddd;
            border: 1px solid #ddd;
            margin-top: 32px;
        }


        .profile-order-stat {
            padding: 23px;
            background: #fff;
        }


        .profile-order-stat span {
            display: block;
            margin-bottom: 8px;
            color: #777;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }


        .profile-order-stat strong {
            display: block;
            font-size: 25px;
            font-weight: 800;
        }


        /* =====================================================
           ORDERS
           ===================================================== */

        .orders-list {
            display: flex;
            flex-direction: column;
        }


        .customer-order {
            padding: 25px 28px;
            border-bottom: 1px solid #e5e5e5;
        }


        .customer-order:last-child {
            border-bottom: 0;
        }


        .customer-order-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
        }


        .customer-order-number span {
            display: block;
            margin-bottom: 6px;
            color: #777;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }


        .customer-order-number strong {
            display: block;
            font-size: 18px;
            font-weight: 800;
        }


        .customer-order-date {
            margin-top: 6px;
            color: #777;
            font-size: 10px;
        }


        .customer-order-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 30px;
            padding: 0 11px;
            border: 1px solid #bbb;
            background: #fff;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
        }


        .customer-order-status.processing {
            border-color: #111;
        }


        .customer-order-status.shipped {
            background: #ededed;
        }


        .customer-order-status.completed {
            background: #111;
            border-color: #111;
            color: #fff;
        }


        .customer-order-status.cancelled {
            color: #777;
            text-decoration: line-through;
        }


        .customer-order-info {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }


        .customer-order-info-item span {
            display: block;
            margin-bottom: 6px;
            color: #777;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }


        .customer-order-info-item strong {
            display: block;
            font-size: 12px;
            overflow-wrap: anywhere;
        }


        .order-status-description {
            margin: 18px 0 0;
            color: #666;
            font-size: 11px;
            line-height: 1.6;
        }


        .customer-order-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            margin-top: 18px;
        }


        .customer-receipt-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 15px;
            border: 1px solid #111;
            background: #111;
            color: #fff;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
            transition: .2s ease;
        }


        .customer-receipt-btn:hover {
            background: #fff;
            color: #111;
        }


        .receipt-waiting {
            display: inline-flex;
            align-items: center;
            min-height: 40px;
            color: #777;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }


        /* =====================================================
           EMPTY
           ===================================================== */

        .account-empty {
            padding: 50px 28px;
            text-align: center;
        }


        .account-empty h3 {
            margin: 0 0 10px;
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
        }


        .account-empty p {
            margin: 0 0 20px;
            color: #777;
            font-size: 11px;
            line-height: 1.6;
        }


        /* =====================================================
           NOTIFICATIONS
           ===================================================== */

        .notification-list {
            display: flex;
            flex-direction: column;
        }


        .notification-card {
            position: relative;
            padding: 22px 28px;
            border-bottom: 1px solid #e5e5e5;
        }


        .notification-card:last-child {
            border-bottom: 0;
        }


        .notification-card.unread {
            background: #f8f8f6;
        }


        .notification-card.unread::before {
            content: "";
            position: absolute;
            top: 25px;
            left: 12px;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #111;
        }


        .notification-card h3 {
            margin: 0 0 7px;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
        }


        .notification-card p {
            margin: 0;
            color: #666;
            font-size: 11px;
            line-height: 1.6;
        }


        .notification-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 12px;
            color: #888;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }


        .notification-receipt-link {
            display: inline-block;
            margin-top: 13px;
            font-size: 9px;
            font-weight: 800;
            text-decoration: underline;
            text-transform: uppercase;
        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 850px) {

            .profile-order-stats {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }


            .customer-order-info {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 600px) {

            .account-section-header,
            .customer-order-top {
                align-items: flex-start;
                flex-direction: column;
            }


            .profile-order-stats {
                grid-template-columns: 1fr;
            }


            .customer-order,
            .notification-card {
                padding-left: 20px;
                padding-right: 20px;
            }

        }

    </style>

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
                                $navLinks[$item]
                                ?? '#'
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


        <!-- ICONS -->

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


            <!-- PROFILE -->

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
     CUSTOMER PROFILE
     ========================================================= -->

<main class="profile-page">

    <section class="profile-section">

        <div class="wrap">


            <!-- HEADING -->

            <div class="profile-heading">

                <p class="eyebrow">
                    MY ACCOUNT
                </p>

                <h1>
                    CUSTOMER PROFILE
                </h1>

            </div>


            <!-- PROFILE LAYOUT -->

            <div class="profile-layout">


                <!-- =========================================
                     LEFT CUSTOMER CARD
                     ========================================= -->

                <aside class="profile-card">


                    <div class="profile-avatar">

                        <?= htmlspecialchars(
                            $initial
                        ) ?>

                    </div>


                    <h2>

                        <?= htmlspecialchars(
                            $user['full_name']
                        ) ?>

                    </h2>


                    <p class="profile-username">

                        @<?= htmlspecialchars(
                            $user['username']
                        ) ?>

                    </p>


                    <span class="profile-member">
                        ARIP MEMBER
                    </span>


                    <p class="profile-joined">

                        MEMBER SINCE

                        <?= strtoupper(
                            htmlspecialchars(
                                $memberSince
                            )
                        ) ?>

                    </p>


                    <a
                        href="logout.php"
                        class="profile-logout-btn"
                    >
                        LOGOUT
                    </a>


                </aside>


                <!-- =========================================
                     RIGHT PROFILE DETAILS
                     ========================================= -->

                <div class="profile-details">


                    <div class="profile-details-header">

                        <h2>
                            PERSONAL INFORMATION
                        </h2>

                    </div>


                    <div class="profile-info-grid">


                        <!-- FULL NAME -->

                        <div class="profile-info-item">

                            <span>
                                Full Name
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $user['full_name']
                                ) ?>

                            </strong>

                        </div>


                        <!-- USERNAME -->

                        <div class="profile-info-item">

                            <span>
                                Username
                            </span>

                            <strong>

                                @<?= htmlspecialchars(
                                    $user['username']
                                ) ?>

                            </strong>

                        </div>


                        <!-- EMAIL -->

                        <div class="profile-info-item">

                            <span>
                                Email Address
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $user['email']
                                ) ?>

                            </strong>

                        </div>


                        <!-- MEMBER -->

                        <div class="profile-info-item">

                            <span>
                                Member Since
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $memberSince
                                ) ?>

                            </strong>

                        </div>


                    </div>


                    <div class="profile-actions">

                        <a
                            href="shop.php"
                            class="profile-shop-btn"
                        >
                            CONTINUE SHOPPING →
                        </a>

                    </div>


                </div>


            </div>


            <!-- =================================================
                 ORDER STATS
                 ================================================= -->

            <div class="profile-order-stats">


                <div class="profile-order-stat">

                    <span>
                        Total Orders
                    </span>

                    <strong>
                        <?= $totalOrders ?>
                    </strong>

                </div>


                <div class="profile-order-stat">

                    <span>
                        Pending
                    </span>

                    <strong>
                        <?= $pendingOrders ?>
                    </strong>

                </div>


                <div class="profile-order-stat">

                    <span>
                        Active
                    </span>

                    <strong>
                        <?= $processingOrders ?>
                    </strong>

                </div>


                <div class="profile-order-stat">

                    <span>
                        Completed
                    </span>

                    <strong>
                        <?= $completedOrders ?>
                    </strong>

                </div>


            </div>


            <!-- =================================================
                 NOTIFICATIONS
                 ================================================= -->

            <?php if (
                $notificationsTableExists
            ): ?>

                <section class="account-section-block">


                    <div class="account-section-header">


                        <h2>
                            Notifications
                        </h2>


                        <span>

                            <?= $unreadNotifications ?>
                            unread

                        </span>


                    </div>


                    <?php if (
                        count($notifications) === 0
                    ): ?>


                        <div class="account-empty">

                            <h3>
                                No Notifications
                            </h3>

                            <p>
                                Your order updates will appear here.
                            </p>

                        </div>


                    <?php else: ?>


                        <div class="notification-list">


                            <?php foreach (
                                $notifications
                                as $notification
                            ): ?>


                                <article
                                    class="notification-card <?= (int) $notification['is_read'] === 0
                                        ? 'unread'
                                        : ''
                                    ?>"
                                >


                                    <h3>

                                        <?= htmlspecialchars(
                                            $notification['title']
                                        ) ?>

                                    </h3>


                                    <p>

                                        <?= htmlspecialchars(
                                            $notification['message']
                                        ) ?>

                                    </p>


                                    <div class="notification-meta">


                                        <span>

                                            <?= date(
                                                'M d, Y - h:i A',
                                                strtotime(
                                                    $notification['created_at']
                                                )
                                            ) ?>

                                        </span>


                                        <?php if (
                                            !empty(
                                                $notification['order_id']
                                            )
                                        ): ?>

                                            <span>

                                                ORDER
                                                #<?= (int) $notification['order_id'] ?>

                                            </span>

                                        <?php endif; ?>


                                    </div>


                                    <?php if (
                                        !empty(
                                            $notification['order_id']
                                        )
                                    ): ?>


                                        <?php

                                        $notificationOrderId =
                                            (int) $notification['order_id'];


                                        $notificationReceiptReady =
                                            false;


                                        foreach (
                                            $orders
                                            as $customerOrder
                                        ) {

                                            if (
                                                (int) $customerOrder['order_id'] ===
                                                $notificationOrderId &&
                                                !empty(
                                                    $customerOrder['receipt_number']
                                                ) &&
                                                !in_array(
                                                    $customerOrder['status'],
                                                    [
                                                        'Pending',
                                                        'Cancelled'
                                                    ],
                                                    true
                                                )
                                            ) {

                                                $notificationReceiptReady =
                                                    true;

                                                break;
                                            }
                                        }

                                        ?>


                                        <?php if (
                                            $notificationReceiptReady
                                        ): ?>

                                            <a
                                                href="receipt.php?order_id=<?= $notificationOrderId ?>"
                                                class="notification-receipt-link"
                                            >
                                                VIEW DIGITAL RECEIPT →
                                            </a>

                                        <?php endif; ?>


                                    <?php endif; ?>


                                </article>


                            <?php endforeach; ?>


                        </div>


                    <?php endif; ?>


                </section>

            <?php endif; ?>


            <!-- =================================================
                 MY ORDERS
                 ================================================= -->

            <section class="account-section-block">


                <div class="account-section-header">


                    <h2>
                        My Orders
                    </h2>


                    <span>

                        <?= $totalOrders ?>
                        order(s)

                    </span>


                </div>


                <?php if (
                    count($orders) === 0
                ): ?>


                    <div class="account-empty">


                        <h3>
                            No Orders Yet
                        </h3>


                        <p>
                            You haven't placed an order yet.
                        </p>


                        <a
                            href="shop.php"
                            class="customer-receipt-btn"
                        >
                            SHOP NOW →
                        </a>


                    </div>


                <?php else: ?>


                    <div class="orders-list">


                        <?php foreach (
                            $orders
                            as $order
                        ): ?>


                            <?php

                            $statusClass =
                                strtolower(
                                    $order['status']
                                );


                            $receiptReady =
                                !empty(
                                    $order['receipt_number']
                                ) &&
                                !in_array(
                                    $order['status'],
                                    [
                                        'Pending',
                                        'Cancelled'
                                    ],
                                    true
                                );

                            ?>


                            <article class="customer-order">


                                <!-- =================================
                                     ORDER TOP
                                     ================================= -->

                                <div class="customer-order-top">


                                    <div class="customer-order-number">


                                        <span>
                                            Order
                                        </span>


                                        <strong>

                                            #<?= (int) $order['order_id'] ?>

                                        </strong>


                                        <p class="customer-order-date">

                                            <?= date(
                                                'F d, Y - h:i A',
                                                strtotime(
                                                    $order['created_at']
                                                )
                                            ) ?>

                                        </p>


                                    </div>


                                    <span
                                        class="customer-order-status <?= htmlspecialchars(
                                            $statusClass
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $order['status']
                                        ) ?>

                                    </span>


                                </div>


                                <!-- =================================
                                     ORDER INFO
                                     ================================= -->

                                <div class="customer-order-info">


                                    <div class="customer-order-info-item">

                                        <span>
                                            Order Total
                                        </span>

                                        <strong>

                                            ₱<?= number_format(
                                                (float) $order['total_amount'],
                                                2
                                            ) ?>

                                        </strong>

                                    </div>


                                    <div class="customer-order-info-item">

                                        <span>
                                            Payment
                                        </span>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $order['payment_method']
                                            ) ?>

                                        </strong>

                                    </div>


                                    <div class="customer-order-info-item">

                                        <span>
                                            Receipt
                                        </span>

                                        <strong>

                                            <?= !empty(
                                                $order['receipt_number']
                                            )
                                                ? htmlspecialchars(
                                                    $order['receipt_number']
                                                )
                                                : 'Waiting for approval'
                                            ?>

                                        </strong>

                                    </div>


                                </div>


                                <!-- =================================
                                     STATUS DESCRIPTION
                                     ================================= -->

                                <p class="order-status-description">

                                    <?= htmlspecialchars(
                                        getOrderMessage(
                                            $order['status']
                                        )
                                    ) ?>

                                </p>


                                <!-- =================================
                                     ACTIONS
                                     ================================= -->

                                <div class="customer-order-actions">


                                    <?php if (
                                        $receiptReady
                                    ): ?>


                                        <a
                                            href="receipt.php?order_id=<?= (int) $order['order_id'] ?>"
                                            class="customer-receipt-btn"
                                        >
                                            VIEW RECEIPT →
                                        </a>


                                    <?php elseif (
                                        $order['status'] === 'Pending'
                                    ): ?>


                                        <span class="receipt-waiting">
                                            RECEIPT AVAILABLE AFTER APPROVAL
                                        </span>


                                    <?php elseif (
                                        $order['status'] === 'Cancelled'
                                    ): ?>


                                        <span class="receipt-waiting">
                                            RECEIPT UNAVAILABLE
                                        </span>


                                    <?php endif; ?>


                                </div>


                            </article>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            </section>


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
     PROFILE CART SCRIPT
     ========================================================= -->

<script>

(function () {

    'use strict';


    const CART_ITEMS_KEY =
        'arip_cart_items';


    const CART_COUNT_KEY =
        'arip_cart_count';


    const cartCount =
        document.getElementById(
            'cart-count'
        );


    const cartBtn =
        document.getElementById(
            'cart-btn'
        );


    function getCartItems() {

        try {

            const saved =
                localStorage.getItem(
                    CART_ITEMS_KEY
                );


            const cart =
                saved
                    ? JSON.parse(saved)
                    : [];


            return Array.isArray(cart)
                ? cart
                : [];

        } catch (error) {

            return [];
        }
    }


    function updateCartBadge() {

        const cart =
            getCartItems();


        const count =
            cart.reduce(
                function (
                    total,
                    item
                ) {

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


        if (cartCount) {

            cartCount.textContent =
                String(count);


            cartCount.hidden =
                count <= 0;
        }
    }


    if (cartBtn) {

        cartBtn.addEventListener(
            'click',
            function () {

                window.location.href =
                    'cart.php';
            }
        );
    }


    updateCartBadge();

})();

</script>


<script
    src="script.js"
    defer
></script>


</body>

</html>