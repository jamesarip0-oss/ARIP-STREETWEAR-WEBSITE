<?php

session_start();

require_once __DIR__ . '/../config/db.php';


/* =========================================================
   ADMIN ACCESS ONLY
   ========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header('Location: ../login.php');
    exit;
}


$adminId = (int) $_SESSION['user_id'];


/* =========================================================
   VERIFY ADMIN FROM DATABASE
   ========================================================= */

$adminStmt = $pdo->prepare("
    SELECT
        user_id,
        full_name,
        username,
        email,
        role
    FROM users
    WHERE user_id = ?
      AND role = 'admin'
    LIMIT 1
");

$adminStmt->execute([
    $adminId
]);

$admin = $adminStmt->fetch();


if (!$admin) {

    session_unset();
    session_destroy();

    header('Location: ../login.php');
    exit;
}


/* =========================================================
   DASHBOARD COUNTS
   ========================================================= */

$productCount = 0;
$customerCount = 0;
$orderCount = 0;
$pendingCount = 0;
$messageCount = 0;
$processingCount = 0;
$shippedCount = 0;
$completedCount = 0;
$totalIncome = 0;


/* TOTAL PRODUCTS */

try {

    $productCount =
        (int) $pdo
            ->query("
                SELECT COUNT(*)
                FROM products
            ")
            ->fetchColumn();

} catch (PDOException $e) {

    $productCount = 0;
}


/* TOTAL CUSTOMERS */

try {

    $customerCount =
        (int) $pdo
            ->query("
                SELECT COUNT(*)
                FROM users
                WHERE role = 'customer'
            ")
            ->fetchColumn();

} catch (PDOException $e) {

    $customerCount = 0;
}


/* TOTAL ORDERS */

try {

    $orderCount =
        (int) $pdo
            ->query("
                SELECT COUNT(*)
                FROM orders
            ")
            ->fetchColumn();

} catch (PDOException $e) {

    $orderCount = 0;
}


/* PENDING ORDERS */

try {

    $pendingCount =
        (int) $pdo
            ->query("
                SELECT COUNT(*)
                FROM orders
                WHERE status = 'Pending'
            ")
            ->fetchColumn();

} catch (PDOException $e) {

    $pendingCount = 0;
}


/* PROCESSING ORDERS */

try {

    $processingCount =
        (int) $pdo
            ->query("
                SELECT COUNT(*)
                FROM orders
                WHERE status = 'Processing'
            ")
            ->fetchColumn();

} catch (PDOException $e) {

    $processingCount = 0;
}


/* SHIPPED ORDERS */

try {

    $shippedCount =
        (int) $pdo
            ->query("
                SELECT COUNT(*)
                FROM orders
                WHERE status = 'Shipped'
            ")
            ->fetchColumn();

} catch (PDOException $e) {

    $shippedCount = 0;
}


/* COMPLETED ORDERS */

try {

    $completedCount =
        (int) $pdo
            ->query("
                SELECT COUNT(*)
                FROM orders
                WHERE status = 'Completed'
            ")
            ->fetchColumn();

} catch (PDOException $e) {

    $completedCount = 0;
}


/* TOTAL INCOME
   COMPLETED ORDERS ONLY
*/

try {

    $totalIncome =
        (float) $pdo
            ->query("
                SELECT
                    COALESCE(
                        SUM(total_amount),
                        0
                    )
                FROM orders
                WHERE status = 'Completed'
            ")
            ->fetchColumn();

} catch (PDOException $e) {

    $totalIncome = 0;
}


/* CONTACT MESSAGES */

try {

    $messageCount =
        (int) $pdo
            ->query("
                SELECT COUNT(*)
                FROM contact_messages
            ")
            ->fetchColumn();

} catch (PDOException $e) {

    $messageCount = 0;
}


/* =========================================================
   RECENT ORDERS
   ========================================================= */

$recentOrders = [];


try {

    $recentOrderStmt =
        $pdo->query("
            SELECT
                o.order_id,
                o.user_id,
                o.full_name,
                o.total_amount,
                o.payment_method,
                o.status,
                o.receipt_number,
                o.created_at
            FROM orders o
            ORDER BY o.created_at DESC
            LIMIT 5
        ");


    $recentOrders =
        $recentOrderStmt->fetchAll();

} catch (PDOException $e) {

    $recentOrders = [];
}


/* =========================================================
   RECENT CUSTOMERS
   ========================================================= */

$recentCustomers = [];


try {

    $recentCustomerStmt =
        $pdo->query("
            SELECT
                user_id,
                full_name,
                username,
                email,
                created_at
            FROM users
            WHERE role = 'customer'
            ORDER BY created_at DESC
            LIMIT 5
        ");


    $recentCustomers =
        $recentCustomerStmt->fetchAll();

} catch (PDOException $e) {

    $recentCustomers = [];
}


/* =========================================================
   STATUS CLASS
   ========================================================= */

function getStatusClass(string $status): string
{
    return strtolower(
        trim($status)
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
        Dashboard — ARIP Admin
    </title>


    <style>

        /* =====================================================
           RESET
           ===================================================== */

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f3f3;
            color: #111;
        }


        a {
            color: inherit;
            text-decoration: none;
        }


        button {
            font: inherit;
        }


        /* =====================================================
           ADMIN LAYOUT
           ===================================================== */

        .admin-layout {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 250px minmax(0, 1fr);
        }


        /* =====================================================
           SIDEBAR
           ===================================================== */

        .admin-sidebar {
            background: #111;
            color: #fff;
            padding: 30px 20px;
        }


        .admin-brand {
            margin-bottom: 40px;
        }


        .admin-brand h1 {
            margin: 0;
            font-size: 26px;
            letter-spacing: .08em;
        }


        .admin-brand p {
            margin: 6px 0 0;
            color: #888;
            font-size: 11px;
            letter-spacing: .1em;
        }


        .admin-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }


        .admin-nav a {
            padding: 14px 15px;
            color: #ccc;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            transition: .2s ease;
        }


        .admin-nav a:hover,
        .admin-nav a.active {
            background: #fff;
            color: #111;
        }


        .admin-sidebar-bottom {
            margin-top: 50px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }


        .admin-sidebar-bottom a {
            color: #ccc;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }


        .admin-sidebar-bottom a:hover {
            color: #fff;
        }


        /* =====================================================
           MAIN
           ===================================================== */

        .admin-main {
            min-width: 0;
            padding: 35px;
        }


        /* =====================================================
           TOP BAR
           ===================================================== */

        .admin-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 30px;
        }


        .admin-topbar h2 {
            margin: 0;
            font-size: 32px;
            font-weight: 800;
        }


        .admin-topbar p {
            margin: 7px 0 0;
            color: #777;
            font-size: 12px;
        }


        .admin-user {
            text-align: right;
        }


        .admin-user strong {
            display: block;
            font-size: 13px;
        }


        .admin-user span {
            color: #777;
            font-size: 11px;
        }


        /* =====================================================
           PRIMARY STATS
           ===================================================== */

        .stats-grid {
            display: grid;
            grid-template-columns:
                repeat(6, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }


        .stat-card {
            display: block;
            padding: 22px;
            background: #fff;
            border: 1px solid #ddd;
            transition: .2s ease;
        }


        .stat-card:hover {
            border-color: #111;
            transform: translateY(-2px);
        }


        .stat-card span {
            display: block;
            margin-bottom: 10px;
            color: #777;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }


        .stat-card strong {
            display: block;
            font-size: 28px;
            font-weight: 800;
        }


        .stat-card small {
            display: block;
            margin-top: 10px;
            color: #888;
            font-size: 9px;
            text-transform: uppercase;
        }


        .income-card {
            background: #111;
            color: #fff;
            border-color: #111;
        }


        .income-card span,
        .income-card small {
            color: #aaa;
        }


        .income-card:hover {
            border-color: #111;
            background: #1d1d1d;
        }


        /* =====================================================
           ORDER STATUS SUMMARY
           ===================================================== */

        .status-summary {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }


        .status-summary-card {
            padding: 20px;
            background: #111;
            color: #fff;
        }


        .status-summary-card span {
            display: block;
            margin-bottom: 8px;
            color: #999;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }


        .status-summary-card strong {
            display: block;
            font-size: 23px;
        }


        /* =====================================================
           DASHBOARD GRID
           ===================================================== */

        .dashboard-grid {
            display: grid;
            grid-template-columns:
                minmax(0, 1.6fr)
                minmax(280px, .8fr);
            gap: 25px;
            align-items: start;
        }


        /* =====================================================
           PANEL
           ===================================================== */

        .dashboard-panel {
            margin-bottom: 25px;
            background: #fff;
            border: 1px solid #ddd;
        }


        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 20px 22px;
            border-bottom: 1px solid #ddd;
        }


        .panel-header h3 {
            margin: 0;
            font-size: 17px;
            font-weight: 800;
        }


        .panel-link {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
            text-decoration: underline;
        }


        /* =====================================================
           TABLE
           ===================================================== */

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }


        .dashboard-table {
            width: 100%;
            min-width: 650px;
            border-collapse: collapse;
        }


        .dashboard-table th,
        .dashboard-table td {
            padding: 15px 17px;
            border-bottom: 1px solid #eee;
            text-align: left;
            font-size: 11px;
        }


        .dashboard-table th {
            background: #fafafa;
            color: #555;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
        }


        .dashboard-table tbody tr:last-child td {
            border-bottom: 0;
        }


        .dashboard-table tbody tr:hover {
            background: #fafafa;
        }


        /* =====================================================
           STATUS
           ===================================================== */

        .status {
            display: inline-block;
            padding: 6px 9px;
            border: 1px solid #bbb;
            background: #fff;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            white-space: nowrap;
        }


        .status.pending {
            border-style: dashed;
        }


        .status.processing {
            border-color: #111;
        }


        .status.shipped {
            background: #eee;
        }


        .status.completed {
            background: #111;
            border-color: #111;
            color: #fff;
        }


        .status.cancelled {
            color: #777;
            text-decoration: line-through;
        }


        /* =====================================================
           VIEW BUTTON
           ===================================================== */

        .view-btn {
            display: inline-block;
            padding: 8px 11px;
            background: #111;
            color: #fff;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }


        .view-btn:hover {
            background: #333;
        }


        /* =====================================================
           EMPTY
           ===================================================== */

        .empty-row {
            padding: 35px !important;
            color: #777;
            text-align: center !important;
        }


        /* =====================================================
           QUICK ACTIONS
           ===================================================== */

        .quick-actions {
            display: flex;
            flex-direction: column;
        }


        .quick-action {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 18px 20px;
            border-bottom: 1px solid #eee;
            transition: .2s ease;
        }


        .quick-action:last-child {
            border-bottom: 0;
        }


        .quick-action:hover {
            background: #111;
            color: #fff;
        }


        .quick-action-text strong {
            display: block;
            margin-bottom: 4px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }


        .quick-action-text span {
            display: block;
            color: #777;
            font-size: 9px;
            line-height: 1.4;
        }


        .quick-action:hover
        .quick-action-text span {
            color: #aaa;
        }


        .quick-arrow {
            font-size: 18px;
            font-weight: 700;
        }


        /* =====================================================
           RECENT CUSTOMERS
           ===================================================== */

        .customer-list {
            display: flex;
            flex-direction: column;
        }


        .customer-row {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
        }


        .customer-row:last-child {
            border-bottom: 0;
        }


        .customer-avatar {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #111;
            color: #fff;
            font-size: 13px;
            font-weight: 800;
        }


        .customer-info {
            min-width: 0;
        }


        .customer-info strong {
            display: block;
            margin-bottom: 4px;
            font-size: 11px;
            overflow-wrap: anywhere;
        }


        .customer-info span {
            display: block;
            color: #777;
            font-size: 9px;
            overflow-wrap: anywhere;
        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 1350px) {

            .stats-grid {
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }

        }


        @media (max-width: 1200px) {

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 900px) {

            .admin-layout {
                grid-template-columns: 1fr;
            }


            .admin-main {
                padding: 25px 18px;
            }


            .status-summary {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }


        @media (max-width: 650px) {

            .admin-topbar {
                align-items: flex-start;
                flex-direction: column;
            }


            .admin-user {
                text-align: left;
            }


            .stats-grid,
            .status-summary {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<div class="admin-layout">


    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <aside class="admin-sidebar">


        <div class="admin-brand">

            <h1>
                ARIP
            </h1>

            <p>
                ADMIN PANEL
            </p>

        </div>


        <nav class="admin-nav">

            <a
                href="dashboard.php"
                class="active"
            >
                Dashboard
            </a>


            <a href="products.php">
                Products
            </a>


            <a href="orders.php">
                Orders
            </a>


            <a href="customers.php">
                Customers
            </a>


            <a href="messages.php">
                Messages
            </a>

        </nav>


        <div class="admin-sidebar-bottom">

            <a href="../index.php">
                View Store
            </a>

            <a href="../logout.php">
                Logout
            </a>

        </div>


    </aside>


    <!-- =====================================================
         MAIN DASHBOARD
         ===================================================== -->

    <main class="admin-main">


        <!-- =================================================
             TOP BAR
             ================================================= -->

        <div class="admin-topbar">


            <div>

                <h2>
                    Dashboard
                </h2>

                <p>
                    Overview of your ARIP Streetwear store.
                </p>

            </div>


            <div class="admin-user">

                <strong>

                    <?= htmlspecialchars(
                        $admin['full_name']
                    ) ?>

                </strong>

                <span>

                    @<?= htmlspecialchars(
                        $admin['username']
                    ) ?>

                </span>

            </div>


        </div>


        <!-- =================================================
             MAIN STATISTICS
             ================================================= -->

        <section class="stats-grid">


            <!-- PRODUCTS -->

            <a
                href="products.php"
                class="stat-card"
            >

                <span>
                    Total Products
                </span>

                <strong>
                    <?= $productCount ?>
                </strong>

                <small>
                    Manage Products →
                </small>

            </a>


            <!-- CUSTOMERS -->

            <a
                href="customers.php"
                class="stat-card"
            >

                <span>
                    Customers
                </span>

                <strong>
                    <?= $customerCount ?>
                </strong>

                <small>
                    View Customers →
                </small>

            </a>


            <!-- ORDERS -->

            <a
                href="orders.php"
                class="stat-card"
            >

                <span>
                    Total Orders
                </span>

                <strong>
                    <?= $orderCount ?>
                </strong>

                <small>
                    Manage Orders →
                </small>

            </a>


            <!-- PENDING -->

            <a
                href="orders.php?status=Pending"
                class="stat-card"
            >

                <span>
                    Pending Orders
                </span>

                <strong>
                    <?= $pendingCount ?>
                </strong>

                <small>
                    Review Orders →
                </small>

            </a>


            <!-- MESSAGES -->

            <a
                href="messages.php"
                class="stat-card"
            >

                <span>
                    Messages
                </span>

                <strong>
                    <?= $messageCount ?>
                </strong>

                <small>
                    View Messages →
                </small>

            </a>

            <!-- TOTAL INCOME -->

            <div
                class="stat-card income-card"
            >

                <span>
                    Total Income
                </span>

                <strong>
                    ₱<?= number_format(
                        $totalIncome,
                        2
                    ) ?>
                </strong>

                <small>
                    Completed Orders Only
                </small>

            </div>


        </section>


        <!-- =================================================
             ORDER STATUS SUMMARY
             ================================================= -->

        <section class="status-summary">


            <div class="status-summary-card">

                <span>
                    Pending
                </span>

                <strong>
                    <?= $pendingCount ?>
                </strong>

            </div>


            <div class="status-summary-card">

                <span>
                    Processing
                </span>

                <strong>
                    <?= $processingCount ?>
                </strong>

            </div>


            <div class="status-summary-card">

                <span>
                    Shipped
                </span>

                <strong>
                    <?= $shippedCount ?>
                </strong>

            </div>


            <div class="status-summary-card">

                <span>
                    Completed
                </span>

                <strong>
                    <?= $completedCount ?>
                </strong>

            </div>


        </section>


        <!-- =================================================
             DASHBOARD CONTENT
             ================================================= -->

        <div class="dashboard-grid">


            <!-- =============================================
                 LEFT
                 ============================================= -->

            <div>


                <!-- =========================================
                     RECENT ORDERS
                     ========================================= -->

                <section class="dashboard-panel">


                    <div class="panel-header">

                        <h3>
                            Recent Orders
                        </h3>

                        <a
                            href="orders.php"
                            class="panel-link"
                        >
                            VIEW ALL →
                        </a>

                    </div>


                    <div class="table-wrap">


                        <table class="dashboard-table">


                            <thead>

                                <tr>

                                    <th>
                                        Order
                                    </th>

                                    <th>
                                        Customer
                                    </th>

                                    <th>
                                        Payment
                                    </th>

                                    <th>
                                        Total
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php if (
                                count($recentOrders) === 0
                            ): ?>


                                <tr>

                                    <td
                                        colspan="6"
                                        class="empty-row"
                                    >
                                        No customer orders yet.
                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach (
                                    $recentOrders
                                    as $order
                                ): ?>


                                    <tr>


                                        <td>

                                            <strong>

                                                #<?= (int) $order['order_id'] ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $order['full_name']
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $order['payment_method']
                                            ) ?>

                                        </td>


                                        <td>

                                            ₱<?= number_format(
                                                (float) $order['total_amount'],
                                                2
                                            ) ?>

                                        </td>


                                        <td>

                                            <span
                                                class="status <?= htmlspecialchars(
                                                    getStatusClass(
                                                        $order['status']
                                                    )
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $order['status']
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <a
                                                href="orders.php?view=<?= (int) $order['order_id'] ?>"
                                                class="view-btn"
                                            >
                                                VIEW
                                            </a>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            <?php endif; ?>


                            </tbody>


                        </table>


                    </div>


                </section>


            </div>


            <!-- =============================================
                 RIGHT
                 ============================================= -->

            <div>


                <!-- =========================================
                     QUICK ACTIONS
                     ========================================= -->

                <section class="dashboard-panel">


                    <div class="panel-header">

                        <h3>
                            Quick Actions
                        </h3>

                    </div>


                    <div class="quick-actions">


                        <a
                            href="products.php"
                            class="quick-action"
                        >

                            <div class="quick-action-text">

                                <strong>
                                    Manage Products
                                </strong>

                                <span>
                                    Add, edit and manage product stock.
                                </span>

                            </div>

                            <span class="quick-arrow">
                                →
                            </span>

                        </a>


                        <a
                            href="orders.php?status=Pending"
                            class="quick-action"
                        >

                            <div class="quick-action-text">

                                <strong>
                                    Pending Orders
                                </strong>

                                <span>
                                    Review and approve customer orders.
                                </span>

                            </div>

                            <span class="quick-arrow">
                                →
                            </span>

                        </a>


                        <a
                            href="customers.php"
                            class="quick-action"
                        >

                            <div class="quick-action-text">

                                <strong>
                                    Customers
                                </strong>

                                <span>
                                    View registered ARIP customers.
                                </span>

                            </div>

                            <span class="quick-arrow">
                                →
                            </span>

                        </a>


                        <a
                            href="messages.php"
                            class="quick-action"
                        >

                            <div class="quick-action-text">

                                <strong>
                                    Messages
                                </strong>

                                <span>
                                    Read customer contact messages.
                                </span>

                            </div>

                            <span class="quick-arrow">
                                →
                            </span>

                        </a>


                        <a
                            href="../index.php"
                            class="quick-action"
                        >

                            <div class="quick-action-text">

                                <strong>
                                    View Store
                                </strong>

                                <span>
                                    Open the ARIP customer website.
                                </span>

                            </div>

                            <span class="quick-arrow">
                                →
                            </span>

                        </a>


                    </div>


                </section>


                <!-- =========================================
                     RECENT CUSTOMERS
                     ========================================= -->

                <section class="dashboard-panel">


                    <div class="panel-header">

                        <h3>
                            Recent Customers
                        </h3>

                        <a
                            href="customers.php"
                            class="panel-link"
                        >
                            VIEW ALL →
                        </a>

                    </div>


                    <?php if (
                        count($recentCustomers) === 0
                    ): ?>


                        <div
                            style="
                                padding: 35px 20px;
                                color: #777;
                                font-size: 11px;
                                text-align: center;
                            "
                        >
                            No customers yet.
                        </div>


                    <?php else: ?>


                        <div class="customer-list">


                            <?php foreach (
                                $recentCustomers
                                as $customer
                            ): ?>


                                <?php

                                $customerInitial = 'C';


                                if (
                                    !empty(
                                        $customer['full_name']
                                    )
                                ) {

                                    $customerInitial =
                                        strtoupper(
                                            substr(
                                                trim(
                                                    $customer['full_name']
                                                ),
                                                0,
                                                1
                                            )
                                        );
                                }

                                ?>


                                <div class="customer-row">


                                    <div class="customer-avatar">

                                        <?= htmlspecialchars(
                                            $customerInitial
                                        ) ?>

                                    </div>


                                    <div class="customer-info">

                                        <strong>

                                            <?= htmlspecialchars(
                                                $customer['full_name']
                                            ) ?>

                                        </strong>

                                        <span>

                                            @<?= htmlspecialchars(
                                                $customer['username']
                                            ) ?>

                                        </span>

                                        <span>

                                            <?= htmlspecialchars(
                                                $customer['email']
                                            ) ?>

                                        </span>

                                    </div>


                                </div>


                            <?php endforeach; ?>


                        </div>


                    <?php endif; ?>


                </section>


            </div>


        </div>


    </main>


</div>


</body>

</html>