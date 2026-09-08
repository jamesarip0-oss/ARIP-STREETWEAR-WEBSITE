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
   ADMIN CANNOT USE CUSTOMER RECEIPT
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

$userStmt->execute([$userId]);

$currentUser = $userStmt->fetch();


if (!$currentUser) {

    session_unset();
    session_destroy();

    header('Location: login.php');
    exit;
}


/* =========================================================
   MAKE SURE USER IS CUSTOMER
   ========================================================= */

if ($currentUser['role'] !== 'customer') {

    $_SESSION['role'] = $currentUser['role'];

    header('Location: admin/dashboard.php');
    exit;
}


/* =========================================================
   REFRESH SESSION DATA
   ========================================================= */

$_SESSION['user_id'] = (int) $currentUser['user_id'];
$_SESSION['full_name'] = $currentUser['full_name'];
$_SESSION['username'] = $currentUser['username'];
$_SESSION['email'] = $currentUser['email'];
$_SESSION['role'] = $currentUser['role'];


/* =========================================================
   GET ORDER ID
   ========================================================= */

$orderId =
    isset($_GET['order_id'])
        ? (int) $_GET['order_id']
        : 0;


if ($orderId <= 0) {
    header('Location: profile.php');
    exit;
}


/* =========================================================
   LOAD CUSTOMER ORDER

   order_id + user_id are both checked so the customer
   cannot access another customer's receipt.
   ========================================================= */

$orderStmt = $pdo->prepare("
    SELECT
        order_id,
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
        completed_at,
        created_at
    FROM orders
    WHERE order_id = ?
      AND user_id = ?
    LIMIT 1
");

$orderStmt->execute([
    $orderId,
    $userId
]);

$order = $orderStmt->fetch();


/* =========================================================
   ORDER NOT FOUND / NOT OWNED BY CUSTOMER
   ========================================================= */

if (!$order) {
    header('Location: profile.php');
    exit;
}


/* =========================================================
   RECEIPT MUST BE AVAILABLE

   Customer can only open receipt after approval.
   ========================================================= */

if (
    empty($order['receipt_number']) ||
    $order['status'] === 'Pending' ||
    $order['status'] === 'Cancelled'
) {
    header('Location: profile.php');
    exit;
}


/* =========================================================
   MARK NOTIFICATIONS FOR THIS ORDER AS READ
   ========================================================= */

try {

    $notificationTableStmt = $pdo->query("
        SHOW TABLES LIKE 'notifications'
    ");

    $notificationsTableExists =
        (bool) $notificationTableStmt->fetchColumn();


    if ($notificationsTableExists) {

        $readNotificationStmt = $pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?
              AND order_id = ?
        ");

        $readNotificationStmt->execute([
            $userId,
            $orderId
        ]);
    }

} catch (PDOException $e) {

    /*
       Do not stop the receipt page if there is
       a notification-related database problem.
    */

}


/* =========================================================
   LOAD ORDER ITEMS
   ========================================================= */

$itemStmt = $pdo->prepare("
    SELECT
        oi.order_item_id,
        oi.product_id,
        oi.color_name,
        oi.quantity,
        oi.price,
        p.name,
        p.image
    FROM order_items oi
    INNER JOIN products p
        ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
    ORDER BY oi.order_item_id ASC
");

$itemStmt->execute([$orderId]);

$orderItems = $itemStmt->fetchAll();


/* =========================================================
   CALCULATE SUBTOTAL
   ========================================================= */

$subtotal = 0;

foreach ($orderItems as $item) {

    $subtotal +=
        (float) $item['price'] *
        (int) $item['quantity'];
}


/* =========================================================
   FORMAT DATES
   ========================================================= */

$orderDate =
    !empty($order['created_at'])
        ? date(
            'F d, Y - h:i A',
            strtotime($order['created_at'])
        )
        : '—';


$approvedDate =
    !empty($order['approved_at'])
        ? date(
            'F d, Y - h:i A',
            strtotime($order['approved_at'])
        )
        : '—';


$completedDate =
    !empty($order['completed_at'])
        ? date(
            'F d, Y - h:i A',
            strtotime($order['completed_at'])
        )
        : '—';


/* =========================================================
   MASK GCASH NUMBER
   ========================================================= */

function maskGcashNumber(?string $number): string
{
    $digits = preg_replace(
        '/\D+/',
        '',
        (string) $number
    );


    if ($digits === '') {
        return '—';
    }


    if (strlen($digits) <= 4) {
        return $digits;
    }


    return
        str_repeat(
            '•',
            strlen($digits) - 4
        )
        .
        substr(
            $digits,
            -4
        );
}


/* =========================================================
   ORDER STATUS DESCRIPTION
   ========================================================= */

function getStatusDescription(string $status): string
{
    switch ($status) {

        case 'Processing':
            return 'Your order has been approved and is being processed.';

        case 'Shipped':
            return 'Your order has been shipped and is on the way.';

        case 'Completed':
            return 'Your order has been completed. Thank you for shopping with ARIP.';

        default:
            return 'Your order is being processed.';
    }
}


/* =========================================================
   SITE DATA
   ========================================================= */

$site = [
    'name'    => 'ARIP',
    'tagline' => 'STREETWEAR',
    'year'    => date('Y'),
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
        Receipt <?= htmlspecialchars($order['receipt_number']) ?>
        — ARIP Streetwear
    </title>


    <style>

        /* =====================================================
           GLOBAL
           ===================================================== */

        * {
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #eeeeec;
            color: #111;
        }


        a {
            color: inherit;
            text-decoration: none;
        }


        /* =====================================================
           PAGE
           ===================================================== */

        .receipt-page {
            min-height: 100vh;
            padding: 45px 20px 70px;
        }


        .receipt-wrap {
            width: min(920px, 100%);
            margin: 0 auto;
        }


        /* =====================================================
           BACK BUTTON
           ===================================================== */

        .receipt-actions {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 20px;
        }


        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 18px;

            border: 1px solid #111;

            background: #fff;
            color: #111;

            font-size: 10px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;

            transition: .2s ease;
        }


        .back-btn:hover {
            background: #111;
            color: #fff;
        }


        /* =====================================================
           RECEIPT
           ===================================================== */

        .receipt {
            background: #fff;
            border: 1px solid #d5d5d5;
        }


        /* =====================================================
           RECEIPT HEADER
           ===================================================== */

        .receipt-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 30px;

            padding: 40px;

            border-bottom: 2px solid #111;
        }


        .receipt-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }


        .receipt-logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
        }


        .receipt-brand-text h1 {
            margin: 0;

            font-size: 30px;
            line-height: 1;
            letter-spacing: .08em;
        }


        .receipt-brand-text p {
            margin: 7px 0 0;

            color: #777;

            font-size: 10px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
        }


        .receipt-title {
            text-align: right;
        }


        .receipt-title span {
            display: block;
            margin-bottom: 7px;

            color: #777;

            font-size: 9px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }


        .receipt-title strong {
            display: block;

            font-size: 20px;

            overflow-wrap: anywhere;
        }


        /* =====================================================
           STATUS BAR
           ===================================================== */

        .receipt-status-bar {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 20px;

            padding: 18px 40px;

            background: #111;
            color: #fff;
        }


        .status-item span {
            display: block;
            margin-bottom: 5px;

            color: #999;

            font-size: 8px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }


        .status-item strong {
            display: block;

            font-size: 11px;
            letter-spacing: .04em;
            text-transform: uppercase;
        }


        /* =====================================================
           STATUS MESSAGE
           ===================================================== */

        .status-message {
            padding: 18px 40px;

            background: #f5f5f3;

            border-bottom: 1px solid #ddd;
        }


        .status-message strong {
            display: block;

            margin-bottom: 5px;

            font-size: 11px;
            text-transform: uppercase;
        }


        .status-message p {
            margin: 0;

            color: #666;

            font-size: 11px;
            line-height: 1.6;
        }


        /* =====================================================
           SECTIONS
           ===================================================== */

        .receipt-section {
            padding: 32px 40px;

            border-bottom: 1px solid #ddd;
        }


        .receipt-section:last-child {
            border-bottom: 0;
        }


        .section-heading {
            margin: 0 0 20px;

            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }


        /* =====================================================
           INFORMATION GRID
           ===================================================== */

        .info-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 20px;
        }


        .info-box {
            padding: 18px;

            background: #f6f6f4;
        }


        .info-box span {
            display: block;

            margin-bottom: 7px;

            color: #777;

            font-size: 9px;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
        }


        .info-box strong,
        .info-box p {
            margin: 0;

            font-size: 12px;
            line-height: 1.6;

            overflow-wrap: anywhere;
        }


        /* =====================================================
           ORDER ITEMS TABLE
           ===================================================== */

        .table-wrap {
            width: 100%;

            overflow-x: auto;
        }


        .receipt-table {
            width: 100%;
            min-width: 650px;

            border-collapse: collapse;
        }


        .receipt-table th,
        .receipt-table td {
            padding: 15px 12px;

            border-bottom: 1px solid #e6e6e6;

            text-align: left;

            font-size: 12px;
        }


        .receipt-table th {
            padding-top: 0;

            color: #777;

            font-size: 9px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }


        .receipt-table tbody tr:last-child td {
            border-bottom: 0;
        }


        /* =====================================================
           PRODUCT
           ===================================================== */

        .product-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }


        .product-cell img {
            width: 58px;
            height: 58px;

            object-fit: cover;

            background: #eee;
        }


        .product-cell strong {
            font-size: 12px;
        }


        .text-right {
            text-align: right !important;
        }


        /* =====================================================
           ORDER SUMMARY
           ===================================================== */

        .receipt-summary {
            display: flex;
            justify-content: flex-end;
        }


        .summary-box {
            width: min(360px, 100%);
        }


        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;

            padding: 11px 0;

            border-bottom: 1px solid #ddd;

            font-size: 12px;
        }


        .summary-row span {
            color: #666;
        }


        .summary-row strong {
            font-weight: 800;
        }


        .summary-row.total {
            padding-top: 18px;

            border-bottom: 0;

            font-size: 18px;
        }


        .summary-row.total span,
        .summary-row.total strong {
            color: #111;
        }


        /* =====================================================
           RECEIPT FOOTER
           ===================================================== */

        .receipt-footer {
            padding: 35px 40px;

            background: #111;
            color: #fff;

            text-align: center;
        }


        .receipt-footer h2 {
            margin: 0 0 8px;

            font-size: 18px;
            letter-spacing: .06em;
        }


        .receipt-footer p {
            margin: 0;

            color: #aaa;

            font-size: 10px;
            letter-spacing: .05em;
            line-height: 1.6;
        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 700px) {

            .receipt-page {
                padding: 25px 12px 45px;
            }


            .receipt-actions {
                align-items: stretch;
            }


            .back-btn {
                width: 100%;
            }


            .receipt-header {
                align-items: flex-start;
                flex-direction: column;
            }


            .receipt-title {
                text-align: left;
            }


            .receipt-status-bar {
                grid-template-columns: 1fr;
            }


            .receipt-header,
            .receipt-section,
            .receipt-status-bar,
            .status-message {
                padding-left: 24px;
                padding-right: 24px;
            }


            .info-grid {
                grid-template-columns: 1fr;
            }


            .receipt-footer {
                padding-left: 24px;
                padding-right: 24px;
            }

        }

    </style>

</head>


<body>


<main class="receipt-page">


    <div class="receipt-wrap">


        <!-- =================================================
             BACK BUTTON
             ================================================= -->

        <div class="receipt-actions">

            <a
                href="profile.php"
                class="back-btn"
            >
                ← BACK TO MY ACCOUNT
            </a>

        </div>


        <!-- =================================================
             RECEIPT
             ================================================= -->

        <article class="receipt">


            <!-- =============================================
                 RECEIPT HEADER
                 ============================================= -->

            <header class="receipt-header">


                <div class="receipt-brand">


                    <img
                        src="assets/logo.png"
                        alt="ARIP Logo"
                        class="receipt-logo"
                    >


                    <div class="receipt-brand-text">

                        <h1>
                            <?= htmlspecialchars($site['name']) ?>
                        </h1>

                        <p>
                            <?= htmlspecialchars($site['tagline']) ?>
                        </p>

                    </div>


                </div>


                <div class="receipt-title">

                    <span>
                        Digital Receipt
                    </span>

                    <strong>
                        <?= htmlspecialchars($order['receipt_number']) ?>
                    </strong>

                </div>


            </header>


            <!-- =============================================
                 STATUS BAR
                 ============================================= -->

            <div class="receipt-status-bar">


                <div class="status-item">

                    <span>
                        Order Number
                    </span>

                    <strong>
                        #<?= (int) $order['order_id'] ?>
                    </strong>

                </div>


                <div class="status-item">

                    <span>
                        Order Status
                    </span>

                    <strong>
                        <?= htmlspecialchars($order['status']) ?>
                    </strong>

                </div>


                <div class="status-item">

                    <span>
                        Approved
                    </span>

                    <strong>
                        <?= htmlspecialchars($approvedDate) ?>
                    </strong>

                </div>


            </div>


            <!-- =============================================
                 STATUS MESSAGE
                 ============================================= -->

            <div class="status-message">

                <strong>
                    <?= htmlspecialchars($order['status']) ?>
                </strong>

                <p>
                    <?= htmlspecialchars(
                        getStatusDescription($order['status'])
                    ) ?>
                </p>

            </div>


            <!-- =============================================
                 CUSTOMER INFORMATION
                 ============================================= -->

            <section class="receipt-section">


                <h2 class="section-heading">
                    Customer Information
                </h2>


                <div class="info-grid">


                    <div class="info-box">

                        <span>
                            Full Name
                        </span>

                        <strong>
                            <?= htmlspecialchars($order['full_name']) ?>
                        </strong>

                    </div>


                    <div class="info-box">

                        <span>
                            Email Address
                        </span>

                        <strong>
                            <?= htmlspecialchars($order['email']) ?>
                        </strong>

                    </div>


                    <div class="info-box">

                        <span>
                            Phone Number
                        </span>

                        <strong>
                            <?= htmlspecialchars($order['phone']) ?>
                        </strong>

                    </div>


                    <div class="info-box">

                        <span>
                            Order Date
                        </span>

                        <strong>
                            <?= htmlspecialchars($orderDate) ?>
                        </strong>

                    </div>


                </div>


            </section>


            <!-- =============================================
                 SHIPPING & PAYMENT
                 ============================================= -->

            <section class="receipt-section">


                <h2 class="section-heading">
                    Shipping & Payment
                </h2>


                <div class="info-grid">


                    <div class="info-box">


                        <span>
                            Shipping Address
                        </span>


                        <p>

                            <strong>
                                <?= htmlspecialchars($order['full_name']) ?>
                            </strong>

                            <br>

                            <?= htmlspecialchars($order['address']) ?>

                            <br>

                            <?= htmlspecialchars($order['city']) ?>,
                            <?= htmlspecialchars($order['province']) ?>

                            <br>

                            Postal Code:
                            <?= htmlspecialchars($order['postal_code']) ?>

                        </p>


                    </div>


                    <div class="info-box">


                        <span>
                            Payment Method
                        </span>


                        <strong>
                            <?= htmlspecialchars($order['payment_method']) ?>
                        </strong>


                        <?php if (
                            $order['payment_method'] === 'GCash'
                        ): ?>


                            <p style="margin-top: 12px;">

                                Account Name:

                                <strong>
                                    <?= htmlspecialchars(
                                        $order['gcash_name'] ?? '—'
                                    ) ?>
                                </strong>

                                <br>

                                GCash Number:

                                <strong>
                                    <?= htmlspecialchars(
                                        maskGcashNumber(
                                            $order['gcash_number'] ?? ''
                                        )
                                    ) ?>
                                </strong>

                            </p>


                        <?php endif; ?>


                    </div>


                </div>


            </section>


            <!-- =============================================
                 ORDER INFORMATION
                 ============================================= -->

            <section class="receipt-section">


                <h2 class="section-heading">
                    Order Information
                </h2>


                <div class="info-grid">


                    <div class="info-box">

                        <span>
                            Receipt Number
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $order['receipt_number']
                            ) ?>
                        </strong>

                    </div>


                    <div class="info-box">

                        <span>
                            Current Status
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $order['status']
                            ) ?>
                        </strong>

                    </div>


                    <div class="info-box">

                        <span>
                            Approved Date
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $approvedDate
                            ) ?>
                        </strong>

                    </div>


                    <div class="info-box">

                        <span>
                            Completed Date
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $completedDate
                            ) ?>
                        </strong>

                    </div>


                </div>


            </section>


            <!-- =============================================
                 ORDER ITEMS
                 ============================================= -->

            <section class="receipt-section">


                <h2 class="section-heading">
                    Order Items
                </h2>


                <div class="table-wrap">


                    <table class="receipt-table">


                        <thead>

                            <tr>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Color
                                </th>

                                <th>
                                    Qty
                                </th>

                                <th class="text-right">
                                    Unit Price
                                </th>

                                <th class="text-right">
                                    Amount
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (
                            count($orderItems) === 0
                        ): ?>


                            <tr>

                                <td colspan="5">
                                    No order items found.
                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach (
                                $orderItems
                                as $item
                            ): ?>


                                <?php

                                $lineTotal =
                                    (float) $item['price'] *
                                    (int) $item['quantity'];

                                ?>


                                <tr>


                                    <td>


                                        <div class="product-cell">


                                            <?php if (
                                                !empty($item['image'])
                                            ): ?>


                                                <img
                                                    src="<?= htmlspecialchars(
                                                        $item['image']
                                                    ) ?>"
                                                    alt="<?= htmlspecialchars(
                                                        $item['name']
                                                    ) ?>"
                                                >


                                            <?php endif; ?>


                                            <strong>
                                                <?= htmlspecialchars(
                                                    $item['name']
                                                ) ?>
                                            </strong>


                                        </div>


                                    </td>


                                    <td>
                                        <?= htmlspecialchars(
                                            $item['color_name']
                                            ?: 'Default'
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= (int) $item['quantity'] ?>
                                    </td>


                                    <td class="text-right">
                                        ₱<?= number_format(
                                            (float) $item['price'],
                                            2
                                        ) ?>
                                    </td>


                                    <td class="text-right">
                                        ₱<?= number_format(
                                            $lineTotal,
                                            2
                                        ) ?>
                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </section>


            <!-- =============================================
                 ORDER TOTAL
                 ============================================= -->

            <section class="receipt-section">


                <div class="receipt-summary">


                    <div class="summary-box">


                        <div class="summary-row">

                            <span>
                                Subtotal
                            </span>

                            <strong>
                                ₱<?= number_format(
                                    $subtotal,
                                    2
                                ) ?>
                            </strong>

                        </div>


                        <div class="summary-row">

                            <span>
                                Shipping
                            </span>

                            <strong>
                                FREE
                            </strong>

                        </div>


                        <div class="summary-row total">

                            <span>
                                TOTAL
                            </span>

                            <strong>
                                ₱<?= number_format(
                                    (float) $order['total_amount'],
                                    2
                                ) ?>
                            </strong>

                        </div>


                    </div>


                </div>


            </section>


            <!-- =============================================
                 FOOTER
                 ============================================= -->

            <footer class="receipt-footer">


                <h2>
                    THANK YOU FOR SHOPPING WITH ARIP.
                </h2>


                <p>

                    This digital receipt was generated for
                    Order #<?= (int) $order['order_id'] ?>.

                    <br>

                    Receipt:
                    <?= htmlspecialchars(
                        $order['receipt_number']
                    ) ?>

                    <br>

                    &copy;
                    <?= htmlspecialchars($site['year']) ?>
                    ARIP STREETWEAR.
                    ALL RIGHTS RESERVED.

                </p>


            </footer>


        </article>


    </div>


</main>


</body>

</html>