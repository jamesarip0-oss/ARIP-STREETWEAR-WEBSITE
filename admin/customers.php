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
   VERIFY ADMIN
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

$admin =
    $adminStmt->fetch();


if (!$admin) {

    session_unset();
    session_destroy();

    header('Location: ../login.php');
    exit;
}


/* =========================================================
   LOAD CUSTOMERS
   ========================================================= */

$customers = [];


try {

    $customerStmt =
        $pdo->query("
            SELECT
                u.user_id,
                u.full_name,
                u.username,
                u.email,
                u.created_at,

                COUNT(o.order_id) AS total_orders,

                COALESCE(
                    SUM(
                        CASE
                            WHEN o.status != 'Cancelled'
                            THEN o.total_amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_spent

            FROM users u

            LEFT JOIN orders o
                ON u.user_id = o.user_id

            WHERE u.role = 'customer'

            GROUP BY
                u.user_id,
                u.full_name,
                u.username,
                u.email,
                u.created_at

            ORDER BY
                u.created_at DESC
        ");


    $customers =
        $customerStmt->fetchAll();


} catch (PDOException $e) {

    $customers = [];
}


/* =========================================================
   TOTAL CUSTOMER COUNT
   ========================================================= */

$totalCustomers =
    count($customers);


/* =========================================================
   CUSTOMER VIEW
   ========================================================= */

$selectedCustomer = null;
$customerOrders = [];


$viewId =
    isset($_GET['view'])
        ? (int) $_GET['view']
        : 0;


if ($viewId > 0) {

    $viewStmt =
        $pdo->prepare("
            SELECT
                user_id,
                full_name,
                username,
                email,
                created_at
            FROM users
            WHERE user_id = ?
              AND role = 'customer'
            LIMIT 1
        ");

    $viewStmt->execute([
        $viewId
    ]);

    $selectedCustomer =
        $viewStmt->fetch();


    if ($selectedCustomer) {

        $ordersStmt =
            $pdo->prepare("
                SELECT
                    order_id,
                    total_amount,
                    payment_method,
                    status,
                    receipt_number,
                    created_at
                FROM orders
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");

        $ordersStmt->execute([
            $viewId
        ]);

        $customerOrders =
            $ordersStmt->fetchAll();
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
    Customers — ARIP Admin
</title>


<style>

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

.admin-layout {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 250px 1fr;
}

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
    text-transform: uppercase;
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

.admin-main {
    padding: 35px;
}

.admin-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.admin-topbar h2 {
    margin: 0;
    font-size: 32px;
}

.admin-user strong {
    display: block;
    font-size: 13px;
}

.admin-user span {
    font-size: 11px;
    color: #777;
}

.customer-stat {
    margin-bottom: 25px;
    padding: 22px;
    background: #111;
    color: #fff;
    max-width: 220px;
}

.customer-stat span {
    display: block;
    color: #aaa;
    font-size: 10px;
    text-transform: uppercase;
}

.customer-stat strong {
    display: block;
    margin-top: 8px;
    font-size: 30px;
}

.panel {
    margin-bottom: 30px;
    background: #fff;
    border: 1px solid #ddd;
}

.panel-header {
    padding: 20px 22px;
    border-bottom: 1px solid #ddd;
}

.panel-header h3 {
    margin: 0;
    font-size: 17px;
}

.customers-table,
.orders-table {
    width: 100%;
    border-collapse: collapse;
}

.customers-table th,
.customers-table td,
.orders-table th,
.orders-table td {
    padding: 15px 18px;
    border-bottom: 1px solid #eee;
    text-align: left;
    font-size: 12px;
}

.customers-table th,
.orders-table th {
    background: #fafafa;
    font-size: 9px;
    text-transform: uppercase;
}

.view-btn {
    display: inline-block;
    padding: 8px 11px;
    background: #111;
    color: #fff;
    font-size: 9px;
    font-weight: 800;
}

.customer-detail {
    padding: 25px;
}

.customer-info {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 25px;
}

.info-box {
    padding: 17px;
    background: #f6f6f6;
}

.info-box span {
    display: block;
    margin-bottom: 7px;
    color: #777;
    font-size: 9px;
    text-transform: uppercase;
}

.info-box strong {
    font-size: 12px;
    overflow-wrap: anywhere;
}

.status {
    display: inline-block;
    padding: 6px 9px;
    border: 1px solid #bbb;
    font-size: 9px;
    font-weight: 800;
}

@media (max-width: 900px) {

    .admin-layout {
        grid-template-columns: 1fr;
    }

    .customer-info {
        grid-template-columns: 1fr;
    }

    .customers-table,
    .orders-table {
        display: block;
        overflow-x: auto;
    }

}

</style>

</head>


<body>


<div class="admin-layout">


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

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="products.php">
            Products
        </a>

        <a href="orders.php">
            Orders
        </a>

        <a
            href="customers.php"
            class="active"
        >
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


<main class="admin-main">


    <div class="admin-topbar">

        <h2>
            Customers
        </h2>


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


    <div class="customer-stat">

        <span>
            Total Customers
        </span>

        <strong>
            <?= $totalCustomers ?>
        </strong>

    </div>


    <section class="panel">


        <div class="panel-header">

            <h3>
                Registered Customers
            </h3>

        </div>


        <table class="customers-table">


            <thead>

                <tr>

                    <th>ID</th>
                    <th>Customer</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Orders</th>
                    <th>Total Spent</th>
                    <th>Joined</th>
                    <th>Action</th>

                </tr>

            </thead>


            <tbody>


            <?php if (
                count($customers) === 0
            ): ?>

                <tr>

                    <td colspan="8">
                        No customers found.
                    </td>

                </tr>


            <?php else: ?>


                <?php foreach (
                    $customers
                    as $customer
                ): ?>

                    <tr>


                        <td>

                            #<?= (int) $customer['user_id'] ?>

                        </td>


                        <td>

                            <strong>

                                <?= htmlspecialchars(
                                    $customer['full_name']
                                ) ?>

                            </strong>

                        </td>


                        <td>

                            @<?= htmlspecialchars(
                                $customer['username']
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $customer['email']
                            ) ?>

                        </td>


                        <td>

                            <?= (int) $customer['total_orders'] ?>

                        </td>


                        <td>

                            ₱<?= number_format(
                                (float) $customer['total_spent'],
                                2
                            ) ?>

                        </td>


                        <td>

                            <?= date(
                                'M d, Y',
                                strtotime(
                                    $customer['created_at']
                                )
                            ) ?>

                        </td>


                        <td>

                            <a
                                href="customers.php?view=<?= (int) $customer['user_id'] ?>"
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


    </section>


    <?php if ($selectedCustomer): ?>


        <section class="panel">


            <div class="panel-header">

                <h3>
                    Customer Details
                </h3>

            </div>


            <div class="customer-detail">


                <div class="customer-info">


                    <div class="info-box">

                        <span>
                            Full Name
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $selectedCustomer['full_name']
                            ) ?>

                        </strong>

                    </div>


                    <div class="info-box">

                        <span>
                            Username
                        </span>

                        <strong>

                            @<?= htmlspecialchars(
                                $selectedCustomer['username']
                            ) ?>

                        </strong>

                    </div>


                    <div class="info-box">

                        <span>
                            Email
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $selectedCustomer['email']
                            ) ?>

                        </strong>

                    </div>


                    <div class="info-box">

                        <span>
                            Member Since
                        </span>

                        <strong>

                            <?= date(
                                'F d, Y',
                                strtotime(
                                    $selectedCustomer['created_at']
                                )
                            ) ?>

                        </strong>

                    </div>


                </div>


                <h3>
                    Order History
                </h3>


                <table class="orders-table">


                    <thead>

                        <tr>

                            <th>Order</th>
                            <th>Date</th>
                            <th>Payment</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Receipt</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (
                        count($customerOrders) === 0
                    ): ?>

                        <tr>

                            <td colspan="6">
                                This customer has no orders yet.
                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $customerOrders
                            as $order
                        ): ?>

                            <tr>


                                <td>

                                    <a
                                        href="orders.php?view=<?= (int) $order['order_id'] ?>"
                                    >

                                        <strong>
                                            #<?= (int) $order['order_id'] ?>
                                        </strong>

                                    </a>

                                </td>


                                <td>

                                    <?= date(
                                        'M d, Y',
                                        strtotime(
                                            $order['created_at']
                                        )
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

                                    <span class="status">

                                        <?= htmlspecialchars(
                                            $order['status']
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <?php if (
                                        !empty(
                                            $order['receipt_number']
                                        )
                                    ): ?>

                                        <a
                                            href="receipt.php?order_id=<?= (int) $order['order_id'] ?>"
                                            class="view-btn"
                                        >
                                            RECEIPT
                                        </a>

                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </td>


                            </tr>

                        <?php endforeach; ?>


                    <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </section>


    <?php endif; ?>


</main>


</div>


</body>

</html>