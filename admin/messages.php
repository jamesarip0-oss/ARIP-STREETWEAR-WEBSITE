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
   DELETE MESSAGE
   ========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'delete'
) {

    $messageId =
        (int) ($_POST['message_id'] ?? 0);


    if ($messageId > 0) {

        try {

            $deleteStmt =
                $pdo->prepare("
                    DELETE FROM contact_messages
                    WHERE message_id = ?
                ");

            $deleteStmt->execute([
                $messageId
            ]);


            header(
                'Location: messages.php?deleted=1'
            );

            exit;


        } catch (PDOException $e) {

            header(
                'Location: messages.php?error=1'
            );

            exit;
        }

    }

}


/* =========================================================
   MESSAGE
   ========================================================= */

$pageMessage = '';
$pageMessageType = '';


if (isset($_GET['deleted'])) {

    $pageMessage =
        'Message deleted successfully.';

    $pageMessageType =
        'success';
}


if (isset($_GET['error'])) {

    $pageMessage =
        'Unable to delete message.';

    $pageMessageType =
        'error';
}


/* =========================================================
   LOAD MESSAGES
   ========================================================= */

$messages = [];


try {

    $messageStmt =
        $pdo->query("
            SELECT
                message_id,
                full_name,
                email,
                subject,
                message,
                created_at
            FROM contact_messages
            ORDER BY created_at DESC
        ");


    $messages =
        $messageStmt->fetchAll();


} catch (PDOException $e) {

    $messages = [];
}


/* =========================================================
   VIEW MESSAGE
   ========================================================= */

$selectedMessage = null;


$viewId =
    isset($_GET['view'])
        ? (int) $_GET['view']
        : 0;


if ($viewId > 0) {

    $viewStmt =
        $pdo->prepare("
            SELECT
                message_id,
                full_name,
                email,
                subject,
                message,
                created_at
            FROM contact_messages
            WHERE message_id = ?
            LIMIT 1
        ");


    $viewStmt->execute([
        $viewId
    ]);


    $selectedMessage =
        $viewStmt->fetch();
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
    Messages — ARIP Admin
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
    color: #777;
    font-size: 11px;
}

.message-alert {
    padding: 15px 18px;
    margin-bottom: 24px;
    background: #fff;
    border: 1px solid #ccc;
    font-size: 12px;
}

.messages-panel,
.message-detail {
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

.messages-table {
    width: 100%;
    border-collapse: collapse;
}

.messages-table th,
.messages-table td {
    padding: 15px 18px;
    border-bottom: 1px solid #eee;
    text-align: left;
    font-size: 12px;
}

.messages-table th {
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

.message-content {
    padding: 28px;
}

.message-meta {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 25px;
}

.meta-box {
    padding: 16px;
    background: #f6f6f6;
}

.meta-box span {
    display: block;
    margin-bottom: 7px;
    color: #777;
    font-size: 9px;
    text-transform: uppercase;
}

.meta-box strong {
    font-size: 12px;
    overflow-wrap: anywhere;
}

.subject-box {
    margin-bottom: 20px;
    padding: 18px;
    border: 1px solid #ddd;
}

.subject-box span {
    display: block;
    margin-bottom: 8px;
    color: #777;
    font-size: 9px;
    text-transform: uppercase;
}

.message-body {
    min-height: 150px;
    padding: 22px;
    background: #f7f7f7;
    white-space: pre-wrap;
    line-height: 1.7;
    font-size: 13px;
}

.message-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.email-btn,
.delete-btn {
    padding: 11px 15px;
    font-size: 10px;
    font-weight: 800;
}

.email-btn {
    background: #111;
    color: #fff;
}

.delete-btn {
    border: 1px solid #111;
    background: #fff;
    cursor: pointer;
}

@media (max-width: 900px) {

    .admin-layout {
        grid-template-columns: 1fr;
    }

    .message-meta {
        grid-template-columns: 1fr;
    }

    .messages-table {
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

        <a href="customers.php">
            Customers
        </a>

        <a
            href="messages.php"
            class="active"
        >
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
            Messages
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


    <?php if ($pageMessage !== ''): ?>

        <div
            class="message-alert <?= htmlspecialchars(
                $pageMessageType
            ) ?>"
        >

            <?= htmlspecialchars(
                $pageMessage
            ) ?>

        </div>

    <?php endif; ?>


    <section class="messages-panel">


        <div class="panel-header">

            <h3>

                Customer Messages
                (<?= count($messages) ?>)

            </h3>

        </div>


        <table class="messages-table">


            <thead>

                <tr>

                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Action</th>

                </tr>

            </thead>


            <tbody>


            <?php if (
                count($messages) === 0
            ): ?>

                <tr>

                    <td colspan="6">
                        No customer messages yet.
                    </td>

                </tr>


            <?php else: ?>


                <?php foreach (
                    $messages
                    as $item
                ): ?>

                    <tr>


                        <td>

                            #<?= (int) $item['message_id'] ?>

                        </td>


                        <td>

                            <strong>

                                <?= htmlspecialchars(
                                    $item['full_name']
                                ) ?>

                            </strong>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $item['email']
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $item['subject']
                            ) ?>

                        </td>


                        <td>

                            <?= date(
                                'M d, Y',
                                strtotime(
                                    $item['created_at']
                                )
                            ) ?>

                        </td>


                        <td>

                            <a
                                href="messages.php?view=<?= (int) $item['message_id'] ?>"
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


    <?php if ($selectedMessage): ?>


        <section class="message-detail">


            <div class="panel-header">

                <h3>
                    Message Details
                </h3>

            </div>


            <div class="message-content">


                <div class="message-meta">


                    <div class="meta-box">

                        <span>
                            Sender
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $selectedMessage['full_name']
                            ) ?>

                        </strong>

                    </div>


                    <div class="meta-box">

                        <span>
                            Email
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $selectedMessage['email']
                            ) ?>

                        </strong>

                    </div>


                    <div class="meta-box">

                        <span>
                            Received
                        </span>

                        <strong>

                            <?= date(
                                'F d, Y - h:i A',
                                strtotime(
                                    $selectedMessage['created_at']
                                )
                            ) ?>

                        </strong>

                    </div>


                </div>


                <div class="subject-box">

                    <span>
                        Subject
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            $selectedMessage['subject']
                        ) ?>

                    </strong>

                </div>


                <div class="message-body"><?= htmlspecialchars(
                    $selectedMessage['message']
                ) ?></div>


                <div class="message-actions">


                    <a
                        href="mailto:<?= htmlspecialchars(
                            $selectedMessage['email']
                        ) ?>?subject=Re:%20<?= rawurlencode(
                            $selectedMessage['subject']
                        ) ?>"
                        class="email-btn"
                    >
                        REPLY BY EMAIL
                    </a>


                    <form
                        method="POST"
                        action="messages.php"
                        onsubmit="return confirm('Delete this message permanently?');"
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="delete"
                        >

                        <input
                            type="hidden"
                            name="message_id"
                            value="<?= (int) $selectedMessage['message_id'] ?>"
                        >

                        <button
                            type="submit"
                            class="delete-btn"
                        >
                            DELETE MESSAGE
                        </button>

                    </form>


                </div>


            </div>


        </section>


    <?php endif; ?>


</main>


</div>


</body>

</html>