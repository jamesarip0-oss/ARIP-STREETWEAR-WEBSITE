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

$adminStmt->execute([$adminId]);

$admin = $adminStmt->fetch();


if (!$admin) {

    session_unset();
    session_destroy();

    header('Location: ../login.php');
    exit;
}


/* =========================================================
   MESSAGE
   ========================================================= */

$message = '';
$messageType = '';


/* =========================================================
   ADD PRODUCT
   ========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'add'
) {

    $name =
        trim($_POST['name'] ?? '');

    $categoryId =
        (int) ($_POST['category_id'] ?? 0);

    $price =
        (float) ($_POST['price'] ?? 0);

    $image =
        trim($_POST['image'] ?? '');

    $description =
        trim($_POST['description'] ?? '');

    $stock =
        (int) ($_POST['stock'] ?? 0);

    $isBestSeller =
        isset($_POST['is_best_seller'])
            ? 1
            : 0;


    if (
        $name === '' ||
        $categoryId <= 0 ||
        $price <= 0 ||
        $image === '' ||
        $stock < 0
    ) {

        $message =
            'Please complete all required product fields.';

        $messageType =
            'error';

    } else {

        try {

            $insertStmt = $pdo->prepare("
                INSERT INTO products
                (
                    category_id,
                    name,
                    price,
                    image,
                    description,
                    stock,
                    is_best_seller
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            $insertStmt->execute([
                $categoryId,
                $name,
                $price,
                $image,
                $description,
                $stock,
                $isBestSeller
            ]);


            header(
                'Location: products.php?added=1'
            );

            exit;


        } catch (PDOException $e) {

            $message =
                'Unable to add product: ' .
                $e->getMessage();

            $messageType =
                'error';
        }

    }

}


/* =========================================================
   UPDATE PRODUCT
   ========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'update'
) {

    $productId =
        (int) ($_POST['product_id'] ?? 0);

    $name =
        trim($_POST['name'] ?? '');

    $categoryId =
        (int) ($_POST['category_id'] ?? 0);

    $price =
        (float) ($_POST['price'] ?? 0);

    $image =
        trim($_POST['image'] ?? '');

    $description =
        trim($_POST['description'] ?? '');

    $stock =
        (int) ($_POST['stock'] ?? 0);

    $isBestSeller =
        isset($_POST['is_best_seller'])
            ? 1
            : 0;


    if (
        $productId <= 0 ||
        $name === '' ||
        $categoryId <= 0 ||
        $price <= 0 ||
        $image === '' ||
        $stock < 0
    ) {

        $message =
            'Invalid product information.';

        $messageType =
            'error';

    } else {

        try {

            $updateStmt = $pdo->prepare("
                UPDATE products
                SET
                    category_id = ?,
                    name = ?,
                    price = ?,
                    image = ?,
                    description = ?,
                    stock = ?,
                    is_best_seller = ?
                WHERE product_id = ?
            ");

            $updateStmt->execute([
                $categoryId,
                $name,
                $price,
                $image,
                $description,
                $stock,
                $isBestSeller,
                $productId
            ]);


            header(
                'Location: products.php?updated=1'
            );

            exit;


        } catch (PDOException $e) {

            $message =
                'Unable to update product: ' .
                $e->getMessage();

            $messageType =
                'error';
        }

    }

}


/* =========================================================
   DELETE PRODUCT
   ========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'delete'
) {

    $productId =
        (int) ($_POST['product_id'] ?? 0);


    if ($productId > 0) {

        try {

            $deleteStmt = $pdo->prepare("
                DELETE FROM products
                WHERE product_id = ?
            ");

            $deleteStmt->execute([
                $productId
            ]);


            header(
                'Location: products.php?deleted=1'
            );

            exit;


        } catch (PDOException $e) {

            $message =
                'This product cannot be deleted because it may already be connected to an existing order.';

            $messageType =
                'error';
        }

    }

}


/* =========================================================
   GET MESSAGE FROM URL
   ========================================================= */

if (isset($_GET['added'])) {

    $message =
        'Product added successfully.';

    $messageType =
        'success';
}


if (isset($_GET['updated'])) {

    $message =
        'Product updated successfully.';

    $messageType =
        'success';
}


if (isset($_GET['deleted'])) {

    $message =
        'Product deleted successfully.';

    $messageType =
        'success';
}


/* =========================================================
   LOAD CATEGORIES
   ========================================================= */

$categories = [];

try {

    $categoryStmt =
        $pdo->query("
            SELECT
                category_id,
                name,
                slug
            FROM categories
            ORDER BY category_id ASC
        ");

    $categories =
        $categoryStmt->fetchAll();

} catch (PDOException $e) {

    $categories = [];
}


/* =========================================================
   LOAD PRODUCTS
   ========================================================= */

$products = [];

try {

    $productStmt =
        $pdo->query("
            SELECT
                p.product_id,
                p.category_id,
                p.name,
                p.price,
                p.image,
                p.description,
                p.stock,
                p.is_best_seller,
                p.created_at,
                c.name AS category_name
            FROM products p

            INNER JOIN categories c
                ON p.category_id = c.category_id

            ORDER BY p.product_id ASC
        ");

    $products =
        $productStmt->fetchAll();

} catch (PDOException $e) {

    $products = [];
}


/* =========================================================
   EDIT PRODUCT
   ========================================================= */

$editProduct = null;


$editId =
    isset($_GET['edit'])
        ? (int) $_GET['edit']
        : 0;


if ($editId > 0) {

    $editStmt = $pdo->prepare("
        SELECT
            product_id,
            category_id,
            name,
            price,
            image,
            description,
            stock,
            is_best_seller
        FROM products
        WHERE product_id = ?
        LIMIT 1
    ");

    $editStmt->execute([
        $editId
    ]);

    $editProduct =
        $editStmt->fetch();
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
    Products — ARIP Admin
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

button,
input,
select,
textarea {
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

.message {
    padding: 15px 18px;
    margin-bottom: 25px;
    background: #fff;
    border: 1px solid #ccc;
    font-size: 13px;
}

.message.success {
    border-color: #111;
}

.product-layout {
    display: grid;
    grid-template-columns: 340px minmax(0, 1fr);
    gap: 25px;
    align-items: start;
}

.form-panel,
.products-panel {
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

.product-form {
    padding: 22px;
}

.product-form label {
    display: block;
    margin: 0 0 7px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
}

.product-form input,
.product-form select,
.product-form textarea {
    width: 100%;
    margin-bottom: 18px;
    padding: 12px;
    border: 1px solid #ccc;
    background: #fff;
}

.product-form textarea {
    min-height: 100px;
    resize: vertical;
}

.checkbox-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
}

.checkbox-row input {
    width: auto;
    margin: 0;
}

.form-btn {
    width: 100%;
    padding: 13px;
    border: 1px solid #111;
    background: #111;
    color: #fff;
    cursor: pointer;
    font-weight: 800;
    font-size: 11px;
}

.cancel-btn {
    display: block;
    margin-top: 10px;
    padding: 12px;
    border: 1px solid #111;
    text-align: center;
    font-size: 11px;
    font-weight: 700;
}

.products-table {
    width: 100%;
    border-collapse: collapse;
}

.products-table th,
.products-table td {
    padding: 14px;
    border-bottom: 1px solid #eee;
    text-align: left;
    font-size: 12px;
}

.products-table th {
    background: #fafafa;
    font-size: 9px;
    text-transform: uppercase;
}

.product-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.product-info img {
    width: 52px;
    height: 52px;
    object-fit: cover;
    background: #eee;
}

.action-row {
    display: flex;
    gap: 6px;
}

.edit-btn,
.delete-btn {
    padding: 8px 10px;
    font-size: 9px;
    font-weight: 800;
    cursor: pointer;
}

.edit-btn {
    background: #111;
    color: #fff;
}

.delete-btn {
    border: 1px solid #111;
    background: #fff;
    color: #111;
}

@media (max-width: 1100px) {

    .product-layout {
        grid-template-columns: 1fr;
    }

}

@media (max-width: 800px) {

    .admin-layout {
        grid-template-columns: 1fr;
    }

    .products-table {
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

        <h1>ARIP</h1>

        <p>ADMIN PANEL</p>

    </div>


    <nav class="admin-nav">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a
            href="products.php"
            class="active"
        >
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


<main class="admin-main">


    <div class="admin-topbar">

        <h2>
            Products
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


    <div class="product-layout">


        <!-- =================================================
             ADD / EDIT FORM
             ================================================= -->

        <section class="form-panel">


            <div class="panel-header">

                <h3>

                    <?= $editProduct
                        ? 'Edit Product'
                        : 'Add Product'
                    ?>

                </h3>

            </div>


            <form
                method="POST"
                action="products.php"
                class="product-form"
            >


                <input
                    type="hidden"
                    name="action"
                    value="<?= $editProduct
                        ? 'update'
                        : 'add'
                    ?>"
                >


                <?php if ($editProduct): ?>

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= (int) $editProduct['product_id'] ?>"
                    >

                <?php endif; ?>


                <label>
                    Product Name
                </label>

                <input
                    type="text"
                    name="name"
                    value="<?= htmlspecialchars(
                        $editProduct['name'] ?? ''
                    ) ?>"
                    required
                >


                <label>
                    Category
                </label>

                <select
                    name="category_id"
                    required
                >

                    <option value="">
                        Select Category
                    </option>

                    <?php foreach (
                        $categories
                        as $category
                    ): ?>

                        <option
                            value="<?= (int) $category['category_id'] ?>"
                            <?= isset($editProduct['category_id']) &&
                                (int) $editProduct['category_id'] ===
                                (int) $category['category_id']
                                    ? 'selected'
                                    : ''
                            ?>
                        >

                            <?= htmlspecialchars(
                                $category['name']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <label>
                    Price
                </label>

                <input
                    type="number"
                    name="price"
                    step="0.01"
                    min="0.01"
                    value="<?= htmlspecialchars(
                        $editProduct['price'] ?? ''
                    ) ?>"
                    required
                >


                <label>
                    Stock
                </label>

                <input
                    type="number"
                    name="stock"
                    min="0"
                    value="<?= htmlspecialchars(
                        $editProduct['stock'] ?? '20'
                    ) ?>"
                    required
                >


                <label>
                    Image Path
                </label>

                <input
                    type="text"
                    name="image"
                    placeholder="assets/product-tee.png"
                    value="<?= htmlspecialchars(
                        $editProduct['image'] ?? ''
                    ) ?>"
                    required
                >


                <label>
                    Description
                </label>

                <textarea
                    name="description"
                ><?= htmlspecialchars(
                    $editProduct['description'] ?? ''
                ) ?></textarea>


                <div class="checkbox-row">

                    <input
                        type="checkbox"
                        name="is_best_seller"
                        id="best-seller"
                        <?= !isset($editProduct) ||
                            !empty($editProduct['is_best_seller'])
                                ? 'checked'
                                : ''
                        ?>
                    >

                    <label
                        for="best-seller"
                        style="margin:0;"
                    >
                        Best Seller
                    </label>

                </div>


                <button
                    type="submit"
                    class="form-btn"
                >

                    <?= $editProduct
                        ? 'UPDATE PRODUCT'
                        : 'ADD PRODUCT'
                    ?>

                </button>


                <?php if ($editProduct): ?>

                    <a
                        href="products.php"
                        class="cancel-btn"
                    >
                        CANCEL EDIT
                    </a>

                <?php endif; ?>


            </form>

        </section>


        <!-- =================================================
             PRODUCTS TABLE
             ================================================= -->

        <section class="products-panel">


            <div class="panel-header">

                <h3>
                    All Products
                </h3>

            </div>


            <table class="products-table">


                <thead>

                <tr>

                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Best Seller</th>
                    <th>Actions</th>

                </tr>

                </thead>


                <tbody>


                <?php if (
                    count($products) === 0
                ): ?>

                    <tr>

                        <td colspan="6">
                            No products found.
                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach (
                        $products
                        as $product
                    ): ?>

                        <tr>


                            <td>

                                <div class="product-info">

                                    <img
                                        src="../<?= htmlspecialchars(
                                            $product['image']
                                        ) ?>"
                                        alt=""
                                    >

                                    <strong>

                                        <?= htmlspecialchars(
                                            $product['name']
                                        ) ?>

                                    </strong>

                                </div>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $product['category_name']
                                ) ?>

                            </td>


                            <td>

                                ₱<?= number_format(
                                    (float) $product['price'],
                                    2
                                ) ?>

                            </td>


                            <td>

                                <?= (int) $product['stock'] ?>

                            </td>


                            <td>

                                <?= (int) $product['is_best_seller'] === 1
                                    ? 'YES'
                                    : 'NO'
                                ?>

                            </td>


                            <td>

                                <div class="action-row">


                                    <a
                                        href="products.php?edit=<?= (int) $product['product_id'] ?>"
                                        class="edit-btn"
                                    >
                                        EDIT
                                    </a>


                                    <form
                                        method="POST"
                                        action="products.php"
                                        onsubmit="return confirm('Delete this product?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= (int) $product['product_id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="delete-btn"
                                        >
                                            DELETE
                                        </button>

                                    </form>


                                </div>

                            </td>


                        </tr>

                    <?php endforeach; ?>


                <?php endif; ?>


                </tbody>

            </table>


        </section>


    </div>


</main>


</div>

</body>
</html>