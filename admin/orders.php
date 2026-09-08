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
   CSRF TOKEN
   ========================================================= */

if (empty($_SESSION['admin_csrf_token'])) {

    $_SESSION['admin_csrf_token'] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    $_SESSION['admin_csrf_token'];


/* =========================================================
   PAGE MESSAGE
   ========================================================= */

$message = '';

$messageType = '';


if (isset($_GET['approved'])) {

    $message =
        'Order approved successfully. Digital receipt generated and customer notified.';

    $messageType =
        'success';
}


if (isset($_GET['shipped'])) {

    $message =
        'Order marked as Shipped. Customer has been notified.';

    $messageType =
        'success';
}


if (isset($_GET['completed'])) {

    $message =
        'Order completed successfully. Customer has been notified.';

    $messageType =
        'success';
}


if (isset($_GET['cancelled'])) {

    $message =
        'Order cancelled. Product stock was restored and customer notified.';

    $messageType =
        'success';
}


/* =========================================================
   CREATE CUSTOMER NOTIFICATION
   ========================================================= */

function createNotification(
    PDO $pdo,
    int $userId,
    int $orderId,
    string $title,
    string $message
): void {

    $notificationStmt = $pdo->prepare("
        INSERT INTO notifications
        (
            user_id,
            order_id,
            title,
            message,
            is_read
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            0
        )
    ");

    $notificationStmt->execute([
        $userId,
        $orderId,
        $title,
        $message
    ]);
}


/* =========================================================
   PROCESS ORDER ACTION
   ========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    $postedToken =
        $_POST['csrf_token']
        ?? '';


    if (
        !hash_equals(
            $csrfToken,
            $postedToken
        )
    ) {

        $message =
            'Invalid request. Please refresh the page and try again.';

        $messageType =
            'error';

    } else {

        $orderId =
            isset($_POST['order_id'])
                ? (int) $_POST['order_id']
                : 0;


        $action =
            trim(
                $_POST['action']
                ?? ''
            );


        $allowedActions = [
            'approve',
            'ship',
            'complete',
            'cancel'
        ];


        if (
            $orderId <= 0 ||
            !in_array(
                $action,
                $allowedActions,
                true
            )
        ) {

            $message =
                'Invalid order action.';

            $messageType =
                'error';

        } else {

            try {

                /* =========================================
                   START TRANSACTION
                   ========================================= */

                $pdo->beginTransaction();


                /* =========================================
                   LOCK ORDER
                   ========================================= */

                $orderLockStmt =
                    $pdo->prepare("
                        SELECT
                            order_id,
                            user_id,
                            full_name,
                            status,
                            receipt_number,
                            approved_at,
                            completed_at
                        FROM orders
                        WHERE order_id = ?
                        FOR UPDATE
                    ");


                $orderLockStmt->execute([
                    $orderId
                ]);


                $lockedOrder =
                    $orderLockStmt->fetch();


                if (!$lockedOrder) {

                    throw new Exception(
                        'Order not found.'
                    );
                }


                $currentStatus =
                    $lockedOrder['status'];


                $customerId =
                    (int) $lockedOrder['user_id'];


                /* =================================================
                   APPROVE
                   Pending -> Processing

                   IMPORTANT:
                   Stock was already deducted during checkout.
                   DO NOT deduct again here.
                   ================================================= */

                if ($action === 'approve') {

                    if (
                        $currentStatus !== 'Pending'
                    ) {

                        throw new Exception(
                            'Only Pending orders can be approved.'
                        );
                    }


                    /* -----------------------------------------
                       GENERATE RECEIPT NUMBER
                       ----------------------------------------- */

                    $receiptNumber =
                        $lockedOrder['receipt_number'];


                    if (empty($receiptNumber)) {

                        $receiptNumber =
                            'ARIP-' .
                            date('Ymd') .
                            '-' .
                            str_pad(
                                (string) $orderId,
                                6,
                                '0',
                                STR_PAD_LEFT
                            );
                    }


                    /* -----------------------------------------
                       UPDATE ORDER
                       ----------------------------------------- */

                    $approveStmt =
                        $pdo->prepare("
                            UPDATE orders
                            SET
                                status = 'Processing',
                                receipt_number = ?,
                                approved_at = NOW()
                            WHERE order_id = ?
                              AND status = 'Pending'
                        ");


                    $approveStmt->execute([
                        $receiptNumber,
                        $orderId
                    ]);


                    if (
                        $approveStmt->rowCount() !== 1
                    ) {

                        throw new Exception(
                            'Unable to approve this order.'
                        );
                    }


                    /* -----------------------------------------
                       CUSTOMER NOTIFICATION
                       ----------------------------------------- */

                    createNotification(
                        $pdo,
                        $customerId,
                        $orderId,
                        'Order Approved',
                        'Your order #' .
                        $orderId .
                        ' has been approved. Your digital receipt is now ready.'
                    );


                    /* -----------------------------------------
                       COMMIT
                       ----------------------------------------- */

                    $pdo->commit();


                    header(
                        'Location: orders.php?view=' .
                        $orderId .
                        '&approved=1'
                    );

                    exit;
                }


                /* =================================================
                   SHIP
                   Processing -> Shipped
                   ================================================= */

                if ($action === 'ship') {

                    if (
                        $currentStatus !== 'Processing'
                    ) {

                        throw new Exception(
                            'Only Processing orders can be marked as Shipped.'
                        );
                    }


                    /* -----------------------------------------
                       UPDATE ORDER
                       ----------------------------------------- */

                    $shipStmt =
                        $pdo->prepare("
                            UPDATE orders
                            SET status = 'Shipped'
                            WHERE order_id = ?
                              AND status = 'Processing'
                        ");


                    $shipStmt->execute([
                        $orderId
                    ]);


                    if (
                        $shipStmt->rowCount() !== 1
                    ) {

                        throw new Exception(
                            'Unable to mark this order as Shipped.'
                        );
                    }


                    /* -----------------------------------------
                       CUSTOMER NOTIFICATION
                       ----------------------------------------- */

                    createNotification(
                        $pdo,
                        $customerId,
                        $orderId,
                        'Order Shipped',
                        'Your order #' .
                        $orderId .
                        ' has been shipped.'
                    );


                    /* -----------------------------------------
                       COMMIT
                       ----------------------------------------- */

                    $pdo->commit();


                    header(
                        'Location: orders.php?view=' .
                        $orderId .
                        '&shipped=1'
                    );

                    exit;
                }


                /* =================================================
                   COMPLETE
                   Shipped -> Completed
                   ================================================= */

                if ($action === 'complete') {

                    if (
                        $currentStatus !== 'Shipped'
                    ) {

                        throw new Exception(
                            'Only Shipped orders can be completed.'
                        );
                    }


                    /* -----------------------------------------
                       UPDATE ORDER
                       ----------------------------------------- */

                    $completeStmt =
                        $pdo->prepare("
                            UPDATE orders
                            SET
                                status = 'Completed',
                                completed_at = NOW()
                            WHERE order_id = ?
                              AND status = 'Shipped'
                        ");


                    $completeStmt->execute([
                        $orderId
                    ]);


                    if (
                        $completeStmt->rowCount() !== 1
                    ) {

                        throw new Exception(
                            'Unable to complete this order.'
                        );
                    }


                    /* -----------------------------------------
                       CUSTOMER NOTIFICATION
                       ----------------------------------------- */

                    createNotification(
                        $pdo,
                        $customerId,
                        $orderId,
                        'Order Completed',
                        'Your order #' .
                        $orderId .
                        ' has been completed. Thank you for shopping with ARIP.'
                    );


                    /* -----------------------------------------
                       COMMIT
                       ----------------------------------------- */

                    $pdo->commit();


                    header(
                        'Location: orders.php?view=' .
                        $orderId .
                        '&completed=1'
                    );

                    exit;
                }


                /* =================================================
                   CANCEL

                   Allowed:
                   Pending
                   Processing

                   Not allowed:
                   Shipped
                   Completed
                   Cancelled

                   Stock was deducted at checkout,
                   therefore restore stock here.
                   ================================================= */

                if ($action === 'cancel') {

                    if (
                        !in_array(
                            $currentStatus,
                            [
                                'Pending',
                                'Processing'
                            ],
                            true
                        )
                    ) {

                        throw new Exception(
                            'Only Pending or Processing orders can be cancelled.'
                        );
                    }


                    /* -----------------------------------------
                       LOCK ORDER ITEMS / PRODUCTS
                       ----------------------------------------- */

                    $itemsStmt =
                        $pdo->prepare("
                            SELECT
                                oi.product_id,
                                oi.quantity,
                                p.name
                            FROM order_items oi

                            INNER JOIN products p
                                ON oi.product_id = p.product_id

                            WHERE oi.order_id = ?

                            FOR UPDATE
                        ");


                    $itemsStmt->execute([
                        $orderId
                    ]);


                    $orderItems =
                        $itemsStmt->fetchAll();


                    if (
                        count($orderItems) === 0
                    ) {

                        throw new Exception(
                            'This order has no order items.'
                        );
                    }


                    /* -----------------------------------------
                       RESTORE STOCK
                       ----------------------------------------- */

                    $restoreStockStmt =
                        $pdo->prepare("
                            UPDATE products
                            SET stock = stock + ?
                            WHERE product_id = ?
                        ");


                    foreach (
                        $orderItems
                        as $item
                    ) {

                        $restoreStockStmt->execute([

                            (int) $item['quantity'],

                            (int) $item['product_id']

                        ]);


                        if (
                            $restoreStockStmt->rowCount() !== 1
                        ) {

                            throw new Exception(
                                'Unable to restore stock for ' .
                                $item['name'] .
                                '.'
                            );
                        }
                    }


                    /* -----------------------------------------
                       CANCEL ORDER
                       ----------------------------------------- */

                    $cancelStmt =
                        $pdo->prepare("
                            UPDATE orders
                            SET status = 'Cancelled'
                            WHERE order_id = ?
                              AND status IN (
                                  'Pending',
                                  'Processing'
                              )
                        ");


                    $cancelStmt->execute([
                        $orderId
                    ]);


                    if (
                        $cancelStmt->rowCount() !== 1
                    ) {

                        throw new Exception(
                            'Unable to cancel this order.'
                        );
                    }


                    /* -----------------------------------------
                       CUSTOMER NOTIFICATION
                       ----------------------------------------- */

                    createNotification(
                        $pdo,
                        $customerId,
                        $orderId,
                        'Order Cancelled',
                        'Your order #' .
                        $orderId .
                        ' has been cancelled.'
                    );


                    /* -----------------------------------------
                       COMMIT
                       ----------------------------------------- */

                    $pdo->commit();


                    header(
                        'Location: orders.php?view=' .
                        $orderId .
                        '&cancelled=1'
                    );

                    exit;
                }


            } catch (Throwable $e) {

                if (
                    $pdo->inTransaction()
                ) {

                    $pdo->rollBack();
                }


                $message =
                    $e->getMessage();

                $messageType =
                    'error';
            }

        }

    }

}


/* =========================================================
   STATUS FILTERS
   ========================================================= */

$allowedFilters = [
    'All',
    'Pending',
    'Processing',
    'Shipped',
    'Completed',
    'Cancelled'
];


$statusFilter =
    trim(
        $_GET['status']
        ?? 'All'
    );


if (
    !in_array(
        $statusFilter,
        $allowedFilters,
        true
    )
) {

    $statusFilter =
        'All';
}


/* =========================================================
   ORDER COUNTS
   ========================================================= */

$totalOrders =
    (int) $pdo
        ->query("
            SELECT COUNT(*)
            FROM orders
        ")
        ->fetchColumn();


$pendingOrders =
    (int) $pdo
        ->query("
            SELECT COUNT(*)
            FROM orders
            WHERE status = 'Pending'
        ")
        ->fetchColumn();


$processingOrders =
    (int) $pdo
        ->query("
            SELECT COUNT(*)
            FROM orders
            WHERE status = 'Processing'
        ")
        ->fetchColumn();


$shippedOrders =
    (int) $pdo
        ->query("
            SELECT COUNT(*)
            FROM orders
            WHERE status = 'Shipped'
        ")
        ->fetchColumn();


$completedOrders =
    (int) $pdo
        ->query("
            SELECT COUNT(*)
            FROM orders
            WHERE status = 'Completed'
        ")
        ->fetchColumn();


/* =========================================================
   LOAD ORDERS
   ========================================================= */

$orders = [];


if ($statusFilter === 'All') {

    $ordersStmt =
        $pdo->query("
            SELECT
                o.order_id,
                o.user_id,
                o.full_name,
                o.email,
                o.phone,
                o.payment_method,
                o.total_amount,
                o.receipt_number,
                o.status,
                o.approved_at,
                o.completed_at,
                o.created_at,
                u.username
            FROM orders o

            INNER JOIN users u
                ON o.user_id = u.user_id

            ORDER BY
                o.created_at DESC
        ");


    $orders =
        $ordersStmt->fetchAll();

} else {

    $ordersStmt =
        $pdo->prepare("
            SELECT
                o.order_id,
                o.user_id,
                o.full_name,
                o.email,
                o.phone,
                o.payment_method,
                o.total_amount,
                o.receipt_number,
                o.status,
                o.approved_at,
                o.completed_at,
                o.created_at,
                u.username
            FROM orders o

            INNER JOIN users u
                ON o.user_id = u.user_id

            WHERE o.status = ?

            ORDER BY
                o.created_at DESC
        ");


    $ordersStmt->execute([
        $statusFilter
    ]);


    $orders =
        $ordersStmt->fetchAll();
}


/* =========================================================
   VIEW SINGLE ORDER
   ========================================================= */

$selectedOrder = null;

$selectedItems = [];


$viewOrderId =
    isset($_GET['view'])
        ? (int) $_GET['view']
        : 0;


if ($viewOrderId > 0) {

    /* -----------------------------------------------------
       ORDER
       ----------------------------------------------------- */

    $viewStmt =
        $pdo->prepare("
            SELECT
                o.order_id,
                o.user_id,
                o.full_name,
                o.email,
                o.phone,
                o.address,
                o.city,
                o.province,
                o.postal_code,
                o.payment_method,
                o.gcash_name,
                o.gcash_number,
                o.total_amount,
                o.receipt_number,
                o.status,
                o.approved_at,
                o.completed_at,
                o.created_at,
                u.username
            FROM orders o

            INNER JOIN users u
                ON o.user_id = u.user_id

            WHERE o.order_id = ?

            LIMIT 1
        ");


    $viewStmt->execute([
        $viewOrderId
    ]);


    $selectedOrder =
        $viewStmt->fetch();


    /* -----------------------------------------------------
       ORDER ITEMS
       ----------------------------------------------------- */

    if ($selectedOrder) {

        $itemsStmt =
            $pdo->prepare("
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

                ORDER BY
                    oi.order_item_id ASC
            ");


        $itemsStmt->execute([
            $viewOrderId
        ]);


        $selectedItems =
            $itemsStmt->fetchAll();
    }
}


/* =========================================================
   MASK GCASH NUMBER
   ========================================================= */

function maskGcashNumber(?string $number): string
{
    $number =
        trim(
            (string) $number
        );


    if (
        strlen($number) < 7
    ) {

        return $number;
    }


    return
        substr($number, 0, 4) .
        '****' .
        substr($number, -3);
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
    Orders — ARIP Admin
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

button {
    font: inherit;
}

.admin-layout {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 250px minmax(0, 1fr);
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
    letter-spacing: .05em;
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

.admin-sidebar-bottom a {
    color: #ccc;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
}

.admin-main {
    min-width: 0;
    padding: 35px;
}

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

.message {
    margin-bottom: 25px;
    padding: 15px 18px;
    background: #fff;
    border: 1px solid #ccc;
    font-size: 12px;
}

.message.success {
    border-color: #111;
}

.message.error {
    border-color: #999;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-card {
    padding: 20px;
    background: #fff;
    border: 1px solid #ddd;
}

.stat-card span {
    display: block;
    margin-bottom: 8px;
    color: #777;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.stat-card strong {
    display: block;
    font-size: 25px;
}

.filter-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 20px;
}

.filter-btn {
    padding: 9px 13px;
    background: #fff;
    border: 1px solid #ccc;
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
}

.filter-btn:hover,
.filter-btn.active {
    background: #111;
    border-color: #111;
    color: #fff;
}

.orders-panel,
.order-detail {
    margin-bottom: 30px;
    background: #fff;
    border: 1px solid #ddd;
}

.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 20px 22px;
    border-bottom: 1px solid #ddd;
}

.panel-header h3 {
    margin: 0;
    font-size: 17px;
}

.panel-header span {
    color: #777;
    font-size: 10px;
}

.table-wrap {
    width: 100%;
    overflow-x: auto;
}

.orders-table,
.item-table {
    width: 100%;
    border-collapse: collapse;
}

.orders-table {
    min-width: 900px;
}

.orders-table th,
.orders-table td,
.item-table th,
.item-table td {
    padding: 15px 17px;
    border-bottom: 1px solid #eee;
    text-align: left;
    font-size: 12px;
}

.orders-table th,
.item-table th {
    background: #fafafa;
    color: #555;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.status {
    display: inline-block;
    padding: 6px 9px;
    border: 1px solid #bbb;
    background: #fff;
    font-size: 9px;
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

.view-btn {
    display: inline-block;
    padding: 8px 11px;
    background: #111;
    color: #fff;
    font-size: 9px;
    font-weight: 800;
}

.empty-row {
    padding: 35px !important;
    color: #777;
    text-align: center !important;
}

.order-detail-body {
    padding: 25px;
}

.order-number-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 25px;
}

.order-number-row h2 {
    margin: 0;
    font-size: 24px;
}

.order-number-row p {
    margin: 5px 0 0;
    color: #777;
    font-size: 11px;
}

.order-meta {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 25px;
}

.meta-box {
    padding: 17px;
    background: #f7f7f7;
}

.meta-box span {
    display: block;
    margin-bottom: 7px;
    color: #777;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .05em;
    text-transform: uppercase;
}

.meta-box strong {
    display: block;
    font-size: 12px;
    overflow-wrap: anywhere;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.detail-card {
    padding: 20px;
    border: 1px solid #ddd;
}

.detail-card h3 {
    margin: 0 0 14px;
    font-size: 12px;
    text-transform: uppercase;
}

.detail-card p {
    margin: 0 0 7px;
    color: #555;
    font-size: 12px;
    line-height: 1.6;
}

.detail-card strong {
    color: #111;
}

.item-product {
    display: flex;
    align-items: center;
    gap: 12px;
}

.item-product img {
    width: 55px;
    height: 55px;
    object-fit: cover;
    background: #eee;
}

.item-product strong {
    font-size: 12px;
}

.order-total {
    display: flex;
    justify-content: flex-end;
    margin: 20px 0 30px;
}

.order-total-box {
    width: 300px;
    padding: 18px;
    background: #111;
    color: #fff;
}

.order-total-box span {
    display: block;
    margin-bottom: 7px;
    color: #aaa;
    font-size: 9px;
    text-transform: uppercase;
}

.order-total-box strong {
    font-size: 24px;
}

.order-actions {
    padding-top: 25px;
    border-top: 1px solid #ddd;
}

.order-actions h3 {
    margin: 0 0 15px;
    font-size: 13px;
    text-transform: uppercase;
}

.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.action-form {
    margin: 0;
}

.action-btn {
    min-height: 42px;
    padding: 0 17px;
    border: 1px solid #111;
    background: #111;
    color: #fff;
    cursor: pointer;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.action-btn:hover {
    background: #fff;
    color: #111;
}

.action-btn.cancel {
    background: #fff;
    color: #111;
}

.action-btn.cancel:hover {
    background: #111;
    color: #fff;
}

.receipt-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 42px;
    padding: 0 17px;
    border: 1px solid #111;
    background: #fff;
    color: #111;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.receipt-btn:hover {
    background: #111;
    color: #fff;
}

.no-actions {
    margin: 0;
    color: #777;
    font-size: 12px;
}

@media (max-width: 1150px) {

    .stats-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .order-meta {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 850px) {

    .admin-layout {
        grid-template-columns: 1fr;
    }

    .admin-main {
        padding: 25px 18px;
    }

    .stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .detail-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 550px) {

    .admin-topbar,
    .order-number-row {
        align-items: flex-start;
        flex-direction: column;
    }

    .admin-user {
        text-align: left;
    }

    .stats-grid,
    .order-meta {
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

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="products.php">
                Products
            </a>

            <a
                href="orders.php"
                class="active"
            >
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
         MAIN
         ===================================================== -->

    <main class="admin-main">


        <!-- TOP BAR -->

        <div class="admin-topbar">


            <div>

                <h2>
                    Orders
                </h2>

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
             MESSAGE
             ================================================= -->

        <?php if ($message !== ''): ?>

            <div
                class="message <?= htmlspecialchars(
                    $messageType
                ) ?>"
            >

                <?= htmlspecialchars(
                    $message
                ) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             STATS
             ================================================= -->

        <section class="stats-grid">


            <div class="stat-card">

                <span>
                    All Orders
                </span>

                <strong>
                    <?= $totalOrders ?>
                </strong>

            </div>


            <div class="stat-card">

                <span>
                    Pending
                </span>

                <strong>
                    <?= $pendingOrders ?>
                </strong>

            </div>


            <div class="stat-card">

                <span>
                    Processing
                </span>

                <strong>
                    <?= $processingOrders ?>
                </strong>

            </div>


            <div class="stat-card">

                <span>
                    Shipped
                </span>

                <strong>
                    <?= $shippedOrders ?>
                </strong>

            </div>


            <div class="stat-card">

                <span>
                    Completed
                </span>

                <strong>
                    <?= $completedOrders ?>
                </strong>

            </div>


        </section>


        <!-- =================================================
             FILTERS
             ================================================= -->

        <div class="filter-bar">


            <?php foreach (
                $allowedFilters
                as $filter
            ): ?>


                <a
                    href="orders.php?status=<?= urlencode(
                        $filter
                    ) ?>"
                    class="filter-btn <?= $statusFilter === $filter
                        ? 'active'
                        : ''
                    ?>"
                >

                    <?= htmlspecialchars(
                        $filter
                    ) ?>

                </a>


            <?php endforeach; ?>


        </div>


        <!-- =================================================
             ORDERS TABLE
             ================================================= -->

        <section class="orders-panel">


            <div class="panel-header">

                <h3>
                    Customer Orders
                </h3>

                <span>

                    <?= count($orders) ?>
                    order(s)

                </span>

            </div>


            <div class="table-wrap">


                <table class="orders-table">


                    <thead>

                        <tr>

                            <th>
                                Order
                            </th>

                            <th>
                                Customer
                            </th>

                            <th>
                                Username
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
                                Date
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (
                        count($orders) === 0
                    ): ?>


                        <tr>

                            <td
                                colspan="8"
                                class="empty-row"
                            >
                                No orders found.
                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $orders
                            as $order
                        ): ?>


                            <?php

                            $orderStatusClass =
                                strtolower(
                                    $order['status']
                                );

                            ?>


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

                                    @<?= htmlspecialchars(
                                        $order['username']
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
                                            $orderStatusClass
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $order['status']
                                        ) ?>

                                    </span>

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


        <!-- =================================================
             SELECTED ORDER
             ================================================= -->

        <?php if ($selectedOrder): ?>


            <?php

            $selectedStatusClass =
                strtolower(
                    $selectedOrder['status']
                );

            ?>


            <section class="order-detail">


                <div class="panel-header">

                    <h3>
                        Order Details
                    </h3>

                    <a
                        href="orders.php"
                        class="view-btn"
                    >
                        CLOSE
                    </a>

                </div>


                <div class="order-detail-body">


                    <!-- ORDER HEADING -->

                    <div class="order-number-row">


                        <div>

                            <h2>

                                Order
                                #<?= (int) $selectedOrder['order_id'] ?>

                            </h2>

                            <p>

                                Placed on

                                <?= date(
                                    'F d, Y - h:i A',
                                    strtotime(
                                        $selectedOrder['created_at']
                                    )
                                ) ?>

                            </p>

                        </div>


                        <span
                            class="status <?= htmlspecialchars(
                                $selectedStatusClass
                            ) ?>"
                        >

                            <?= htmlspecialchars(
                                $selectedOrder['status']
                            ) ?>

                        </span>


                    </div>


                    <!-- ORDER META -->

                    <div class="order-meta">


                        <div class="meta-box">

                            <span>
                                Customer
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $selectedOrder['full_name']
                                ) ?>

                            </strong>

                        </div>


                        <div class="meta-box">

                            <span>
                                Username
                            </span>

                            <strong>

                                @<?= htmlspecialchars(
                                    $selectedOrder['username']
                                ) ?>

                            </strong>

                        </div>


                        <div class="meta-box">

                            <span>
                                Email
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $selectedOrder['email']
                                ) ?>

                            </strong>

                        </div>


                        <div class="meta-box">

                            <span>
                                Phone
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $selectedOrder['phone']
                                ) ?>

                            </strong>

                        </div>


                    </div>


                    <!-- SHIPPING / PAYMENT -->

                    <div class="detail-grid">


                        <div class="detail-card">


                            <h3>
                                Shipping Address
                            </h3>


                            <p>

                                <strong>

                                    <?= htmlspecialchars(
                                        $selectedOrder['full_name']
                                    ) ?>

                                </strong>

                            </p>


                            <p>

                                <?= htmlspecialchars(
                                    $selectedOrder['address']
                                ) ?>

                            </p>


                            <p>

                                <?= htmlspecialchars(
                                    $selectedOrder['city']
                                ) ?>,
                                <?= htmlspecialchars(
                                    $selectedOrder['province']
                                ) ?>

                            </p>


                            <p>

                                Postal Code:

                                <?= htmlspecialchars(
                                    $selectedOrder['postal_code']
                                ) ?>

                            </p>


                            <p>

                                Phone:

                                <?= htmlspecialchars(
                                    $selectedOrder['phone']
                                ) ?>

                            </p>


                        </div>


                        <div class="detail-card">


                            <h3>
                                Payment Information
                            </h3>


                            <p>

                                Payment Method:

                                <strong>

                                    <?= htmlspecialchars(
                                        $selectedOrder['payment_method']
                                    ) ?>

                                </strong>

                            </p>


                            <?php if (
                                $selectedOrder['payment_method'] === 'GCash'
                            ): ?>


                                <p>

                                    GCash Name:

                                    <strong>

                                        <?= htmlspecialchars(
                                            $selectedOrder['gcash_name']
                                            ?? ''
                                        ) ?>

                                    </strong>

                                </p>


                                <p>

                                    GCash Number:

                                    <strong>

                                        <?= htmlspecialchars(
                                            maskGcashNumber(
                                                $selectedOrder['gcash_number']
                                                ?? ''
                                            )
                                        ) ?>

                                    </strong>

                                </p>


                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $selectedOrder['receipt_number']
                                )
                            ): ?>


                                <p>

                                    Receipt:

                                    <strong>

                                        <?= htmlspecialchars(
                                            $selectedOrder['receipt_number']
                                        ) ?>

                                    </strong>

                                </p>


                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $selectedOrder['approved_at']
                                )
                            ): ?>


                                <p>

                                    Approved:

                                    <strong>

                                        <?= date(
                                            'M d, Y - h:i A',
                                            strtotime(
                                                $selectedOrder['approved_at']
                                            )
                                        ) ?>

                                    </strong>

                                </p>


                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $selectedOrder['completed_at']
                                )
                            ): ?>


                                <p>

                                    Completed:

                                    <strong>

                                        <?= date(
                                            'M d, Y - h:i A',
                                            strtotime(
                                                $selectedOrder['completed_at']
                                            )
                                        ) ?>

                                    </strong>

                                </p>


                            <?php endif; ?>


                        </div>


                    </div>


                    <!-- PRODUCTS -->

                    <div class="table-wrap">


                        <table class="item-table">


                            <thead>

                                <tr>

                                    <th>
                                        Product
                                    </th>

                                    <th>
                                        Color
                                    </th>

                                    <th>
                                        Quantity
                                    </th>

                                    <th>
                                        Unit Price
                                    </th>

                                    <th>
                                        Amount
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php if (
                                count($selectedItems) === 0
                            ): ?>


                                <tr>

                                    <td
                                        colspan="5"
                                        class="empty-row"
                                    >
                                        No order items found.
                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach (
                                    $selectedItems
                                    as $item
                                ): ?>


                                    <?php

                                    $lineTotal =
                                        (float) $item['price'] *
                                        (int) $item['quantity'];

                                    ?>


                                    <tr>


                                        <td>

                                            <div class="item-product">


                                                <?php if (
                                                    !empty(
                                                        $item['image']
                                                    )
                                                ): ?>

                                                    <img
                                                        src="../<?= htmlspecialchars(
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


                                        <td>

                                            ₱<?= number_format(
                                                (float) $item['price'],
                                                2
                                            ) ?>

                                        </td>


                                        <td>

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


                    <!-- TOTAL -->

                    <div class="order-total">


                        <div class="order-total-box">

                            <span>
                                Order Total
                            </span>

                            <strong>

                                ₱<?= number_format(
                                    (float) $selectedOrder['total_amount'],
                                    2
                                ) ?>

                            </strong>

                        </div>


                    </div>


                    <!-- =================================================
                         ADMIN ACTIONS
                         ================================================= -->

                    <div class="order-actions">


                        <h3>
                            Admin Actions
                        </h3>


                        <div class="action-buttons">


                            <!-- =========================================
                                 PENDING
                                 ========================================= -->

                            <?php if (
                                $selectedOrder['status'] === 'Pending'
                            ): ?>


                                <form
                                    method="POST"
                                    action="orders.php?view=<?= (int) $selectedOrder['order_id'] ?>"
                                    class="action-form"
                                    onsubmit="return confirm('Approve this order?');"
                                >


                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars(
                                            $csrfToken
                                        ) ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?= (int) $selectedOrder['order_id'] ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="action"
                                        value="approve"
                                    >


                                    <button
                                        type="submit"
                                        class="action-btn"
                                    >
                                        APPROVE ORDER
                                    </button>


                                </form>


                                <form
                                    method="POST"
                                    action="orders.php?view=<?= (int) $selectedOrder['order_id'] ?>"
                                    class="action-form"
                                    onsubmit="return confirm('Cancel this order? Product stock will be restored.');"
                                >


                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars(
                                            $csrfToken
                                        ) ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?= (int) $selectedOrder['order_id'] ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="action"
                                        value="cancel"
                                    >


                                    <button
                                        type="submit"
                                        class="action-btn cancel"
                                    >
                                        CANCEL ORDER
                                    </button>


                                </form>


                            <?php endif; ?>


                            <!-- =========================================
                                 PROCESSING
                                 ========================================= -->

                            <?php if (
                                $selectedOrder['status'] === 'Processing'
                            ): ?>


                                <form
                                    method="POST"
                                    action="orders.php?view=<?= (int) $selectedOrder['order_id'] ?>"
                                    class="action-form"
                                    onsubmit="return confirm('Mark this order as shipped?');"
                                >


                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars(
                                            $csrfToken
                                        ) ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?= (int) $selectedOrder['order_id'] ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="action"
                                        value="ship"
                                    >


                                    <button
                                        type="submit"
                                        class="action-btn"
                                    >
                                        MARK AS SHIPPED
                                    </button>


                                </form>


                                <form
                                    method="POST"
                                    action="orders.php?view=<?= (int) $selectedOrder['order_id'] ?>"
                                    class="action-form"
                                    onsubmit="return confirm('Cancel this order? Product stock will be restored.');"
                                >


                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars(
                                            $csrfToken
                                        ) ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?= (int) $selectedOrder['order_id'] ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="action"
                                        value="cancel"
                                    >


                                    <button
                                        type="submit"
                                        class="action-btn cancel"
                                    >
                                        CANCEL ORDER
                                    </button>


                                </form>


                            <?php endif; ?>


                            <!-- =========================================
                                 SHIPPED
                                 ========================================= -->

                            <?php if (
                                $selectedOrder['status'] === 'Shipped'
                            ): ?>


                                <form
                                    method="POST"
                                    action="orders.php?view=<?= (int) $selectedOrder['order_id'] ?>"
                                    class="action-form"
                                    onsubmit="return confirm('Mark this order as completed?');"
                                >


                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars(
                                            $csrfToken
                                        ) ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?= (int) $selectedOrder['order_id'] ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="action"
                                        value="complete"
                                    >


                                    <button
                                        type="submit"
                                        class="action-btn"
                                    >
                                        COMPLETE ORDER
                                    </button>


                                </form>


                            <?php endif; ?>


                            <!-- =========================================
                                 RECEIPT
                                 ========================================= -->

                            <?php if (
                                !empty(
                                    $selectedOrder['receipt_number']
                                ) &&
                                $selectedOrder['status'] !== 'Cancelled'
                            ): ?>


                                <a
                                    href="receipt.php?order_id=<?= (int) $selectedOrder['order_id'] ?>"
                                    class="receipt-btn"
                                >
                                    VIEW RECEIPT
                                </a>


                            <?php endif; ?>


                            <!-- =========================================
                                 COMPLETED
                                 ========================================= -->

                            <?php if (
                                $selectedOrder['status'] === 'Completed'
                            ): ?>


                                <p class="no-actions">

                                    This order has been completed.

                                </p>


                            <?php endif; ?>


                            <!-- =========================================
                                 CANCELLED
                                 ========================================= -->

                            <?php if (
                                $selectedOrder['status'] === 'Cancelled'
                            ): ?>


                                <p class="no-actions">

                                    This order has been cancelled.
                                    Stock has been restored and no further actions are allowed.

                                </p>


                            <?php endif; ?>


                        </div>


                    </div>


                </div>


            </section>


        <?php elseif (
            $viewOrderId > 0
        ): ?>


            <div class="message error">

                Order not found.

            </div>


        <?php endif; ?>


    </main>


</div>


</body>

</html>