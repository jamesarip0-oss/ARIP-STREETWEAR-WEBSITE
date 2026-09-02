

/* ============================================================
   SHOP PAGE
   ============================================================ */

(function () {

    'use strict';


    /* =========================================================
       ELEMENTS
       ========================================================= */

    const grid =
        document.getElementById(
            'product-grid'
        );


    const tabsWrap =
        document.getElementById(
            'category-tabs'
        );


    const sortSelect =
        document.getElementById(
            'sort-select'
        );


    const pagination =
        document.getElementById(
            'pagination'
        );


    const resultsCount =
        document.getElementById(
            'results-count'
        );


    const noResults =
        document.getElementById(
            'no-results'
        );


    const cartCountEl =
        document.getElementById(
            'cart-count'
        );


    const newsletterForm =
        document.getElementById(
            'newsletter-form'
        );


    const newsletterMsg =
        document.getElementById(
            'newsletter-msg'
        );


    /*
     * Stop this SHOP section
     * on pages without product-grid.
     */
    if (!grid) {
        return;
    }


    /* =========================================================
       SETTINGS
       ========================================================= */

    const PAGE_SIZE =
        parseInt(
            grid.dataset.pageSize,
            10
        ) || 8;


    const WISHLIST_KEY =
        'arip_wishlist';


    const CART_ITEMS_KEY =
        'arip_cart_items';


    const CART_COUNT_KEY =
        'arip_cart_count';


    /* =========================================================
       PRODUCT CARDS
       ========================================================= */

    const allCards =
        Array.from(
            grid.querySelectorAll(
                '.product-card'
            )
        )
        .map(
            function (element) {

                const photo =
                    element.querySelector(
                        '.product-photo'
                    ) ||
                    element.querySelector(
                        'img'
                    );


                const nameElement =
                    element.querySelector(
                        '.product-name'
                    ) ||
                    element.querySelector(
                        'h3'
                    );


                const priceElement =
                    element.querySelector(
                        '.product-price'
                    );


                let price =
                    parseFloat(
                        element.dataset.price
                    );


                /*
                 * Fallback if data-price
                 * does not exist.
                 */
                if (
                    Number.isNaN(price) &&
                    priceElement
                ) {

                    const cleanedPrice =
                        priceElement.textContent
                            .replace(
                                /[^\d.]/g,
                                ''
                            );


                    price =
                        parseFloat(
                            cleanedPrice
                        );

                }


                if (
                    Number.isNaN(price)
                ) {

                    price =
                        0;

                }


                return {

                    el:
                        element,


                    id:
                        element.dataset.productId ||
                        element.dataset.id ||
                        '',


                    category:
                        element.dataset.category ||
                        '',


                    price:
                        price,


                    name:
                        element.dataset.name ||
                        (
                            nameElement
                                ? nameElement.textContent.trim()
                                : 'ARIP Product'
                        ),


                    image:
                        element.dataset.image ||
                        (
                            photo
                                ? photo.getAttribute('src')
                                : ''
                        )

                };

            }
        );


    /* =========================================================
       INITIAL CATEGORY
       ========================================================= */

    function getInitialCategory() {

        const params =
            new URLSearchParams(
                window.location.search
            );


        const requested =
            params.get(
                'cat'
            );


        if (!tabsWrap) {

            return 'all';

        }


        const validSlugs =
            Array.from(
                tabsWrap.querySelectorAll(
                    '.tab-btn'
                )
            )
            .map(
                function (button) {

                    return (
                        button.dataset.category
                    );

                }
            );


        if (
            requested &&
            validSlugs.includes(
                requested
            )
        ) {

            return requested;

        }


        return 'all';

    }


    /* =========================================================
       SHOP STATE
       ========================================================= */

    const state = {

        category:
            getInitialCategory(),


        sort:
            'newest',


        page:
            1

    };


    /* =========================================================
       CATEGORY FROM URL
       ========================================================= */

    if (
        tabsWrap &&
        state.category !== 'all'
    ) {

        tabsWrap
            .querySelectorAll(
                '.tab-btn'
            )
            .forEach(
                function (button) {

                    const isMatch =
                        button.dataset.category ===
                        state.category;


                    button.classList.toggle(
                        'active',
                        isMatch
                    );


                    button.setAttribute(
                        'aria-selected',
                        isMatch
                            ? 'true'
                            : 'false'
                    );

                }
            );

    }


    /* =========================================================
       WISHLIST
       ========================================================= */

    function loadWishlist() {

        try {

            const saved =
                localStorage.getItem(
                    WISHLIST_KEY
                );


            return new Set(
                JSON.parse(
                    saved || '[]'
                )
            );

        } catch (error) {

            return new Set();

        }

    }


    function saveWishlist(set) {

        try {

            localStorage.setItem(
                WISHLIST_KEY,
                JSON.stringify(
                    Array.from(set)
                )
            );

        } catch (error) {

            /*
             * localStorage unavailable.
             */

        }

    }


    const wishlist =
        loadWishlist();


    function applyWishlistState() {

        document
            .querySelectorAll(
                '.wishlist-btn'
            )
            .forEach(
                function (button) {

                    const active =
                        wishlist.has(
                            button.dataset.id
                        );


                    button.classList.toggle(
                        'active',
                        active
                    );


                    button.setAttribute(
                        'aria-pressed',
                        active
                            ? 'true'
                            : 'false'
                    );

                }
            );

    }


    grid.addEventListener(
        'click',
        function (event) {

            const button =
                event.target.closest(
                    '.wishlist-btn'
                );


            if (!button) {
                return;
            }


            const id =
                button.dataset.id;


            if (wishlist.has(id)) {

                wishlist.delete(
                    id
                );

            } else {

                wishlist.add(
                    id
                );

            }


            saveWishlist(
                wishlist
            );


            applyWishlistState();

        }
    );


    /* =========================================================
       COLOR SWATCH
       ========================================================= */

    grid.addEventListener(
        'click',
        function (event) {

            const swatch =
                event.target.closest(
                    '.swatch'
                );


            if (!swatch) {
                return;
            }


            const card =
                swatch.closest(
                    '.product-card'
                );


            if (!card) {
                return;
            }


            card
                .querySelectorAll(
                    '.swatch'
                )
                .forEach(
                    function (item) {

                        item.classList.remove(
                            'selected'
                        );


                        item.classList.remove(
                            'active'
                        );


                        item.setAttribute(
                            'aria-pressed',
                            'false'
                        );

                    }
                );


            swatch.classList.add(
                'selected'
            );


            swatch.setAttribute(
                'aria-pressed',
                'true'
            );

        }
    );


    /* =========================================================
       CART FUNCTIONS
       ========================================================= */

    function getCartItems() {

        try {

            const saved =
                localStorage.getItem(
                    CART_ITEMS_KEY
                );


            if (!saved) {

                return [];

            }


            const parsed =
                JSON.parse(
                    saved
                );


            return Array.isArray(parsed)
                ? parsed
                : [];

        } catch (error) {

            return [];

        }

    }


    function calculateCartCount(
        cart
    ) {

        return cart.reduce(
            function (
                total,
                item
            ) {

                const quantity =
                    Number(
                        item.quantity || 1
                    );


                return (
                    total +
                    quantity
                );

            },
            0
        );

    }


    function updateCartBadge(
        count
    ) {

        localStorage.setItem(
            CART_COUNT_KEY,
            String(count)
        );


        if (!cartCountEl) {
            return;
        }


        cartCountEl.textContent =
            String(count);


        cartCountEl.hidden =
            count === 0;

    }


    function saveCartItems(
        cart
    ) {

        try {

            localStorage.setItem(
                CART_ITEMS_KEY,
                JSON.stringify(
                    cart
                )
            );


            const count =
                calculateCartCount(
                    cart
                );


            updateCartBadge(
                count
            );

        } catch (error) {

            console.error(
                'Unable to save cart:',
                error
            );

        }

    }


    function syncCartBadge() {

        const cart =
            getCartItems();


        const count =
            calculateCartCount(
                cart
            );


        updateCartBadge(
            count
        );

    }


    /* =========================================================
       GET PRODUCT FROM CARD
       ========================================================= */

    function getProductFromCard(
        card,
        colorOverride
    ) {

        if (!card) {
            return null;
        }


        const productData =
            allCards.find(
                function (product) {

                    return (
                        product.el === card
                    );

                }
            );


        const nameElement =
            card.querySelector(
                '.product-name'
            ) ||
            card.querySelector(
                'h3'
            );


        const priceElement =
            card.querySelector(
                '.product-price'
            );


        const imageElement =
            card.querySelector(
                '.product-photo'
            ) ||
            card.querySelector(
                'img'
            );


        const selectedSwatch =
            card.querySelector(
                '.swatch.selected'
            ) ||
            card.querySelector(
                '.swatch.active'
            );


        const id =
            (
                productData
                    ? productData.id
                    : ''
            ) ||
            card.dataset.productId ||
            card.dataset.id ||
            '';


        const name =
            (
                productData
                    ? productData.name
                    : ''
            ) ||
            card.dataset.name ||
            (
                nameElement
                    ? nameElement.textContent.trim()
                    : 'ARIP Product'
            );


        let price =
            productData
                ? Number(
                    productData.price
                )
                : Number(
                    card.dataset.price
                );


        if (
            (
                Number.isNaN(price) ||
                price <= 0
            ) &&
            priceElement
        ) {

            const priceText =
                priceElement.textContent
                    .replace(
                        /[^\d.]/g,
                        ''
                    );


            price =
                Number(
                    priceText || 0
                );

        }


        if (
            Number.isNaN(price)
        ) {

            price =
                0;

        }


        const image =
            (
                productData
                    ? productData.image
                    : ''
            ) ||
            card.dataset.image ||
            (
                imageElement
                    ? imageElement.getAttribute(
                        'src'
                    )
                    : ''
            );


        let color =
            colorOverride || '';


        if (!color) {

            if (
                selectedSwatch &&
                selectedSwatch.dataset.color
            ) {

                color =
                    selectedSwatch.dataset.color;

            } else {

                color =
                    'Default';

            }

        }


        return {

            id:
                String(
                    id || name
                ),


            name:
                name,


            price:
                Number(
                    price
                ),


            image:
                image,


            color:
                color,


            quantity:
                1

        };

    }


    /* =========================================================
       ADD PRODUCT TO CART
       ========================================================= */

    function addProductToCart(
        card,
        colorOverride
    ) {

        const product =
            getProductFromCard(
                card,
                colorOverride
            );


        if (!product) {
            return;
        }


        const cart =
            getCartItems();


        const existingItem =
            cart.find(
                function (item) {

                    return (
                        String(item.id) ===
                            String(product.id) &&
                        String(item.color) ===
                            String(product.color)
                    );

                }
            );


        if (existingItem) {

            existingItem.quantity =
                Number(
                    existingItem.quantity || 1
                ) + 1;

        } else {

            cart.push(
                product
            );

        }


        saveCartItems(
            cart
        );

    }


    /* =========================================================
       ADD TO CART BUTTON
       Supports:
       .add-to-cart-btn
       .add-to-cart
       ========================================================= */

    grid.addEventListener(
        'click',
        function (event) {

            const addButton =
                event.target.closest(
                    '.add-to-cart-btn, .add-to-cart'
                );


            if (!addButton) {
                return;
            }


            const card =
                addButton.closest(
                    '.product-card'
                );


            if (!card) {
                return;
            }


            addProductToCart(
                card
            );


            const originalText =
                addButton.textContent;


            addButton.classList.add(
                'added'
            );


            addButton.textContent =
                'ADDED ✓';


            addButton.disabled =
                true;


            setTimeout(
                function () {

                    addButton.textContent =
                        originalText;


                    addButton.classList.remove(
                        'added'
                    );


                    addButton.disabled =
                        false;

                },
                1000
            );

        }
    );


    /* =========================================================
       INITIAL CART BADGE
       ========================================================= */

    syncCartBadge();


    /* =========================================================
       QUICK VIEW
       ========================================================= */

    const qvOverlay =
        document.getElementById(
            'quick-view-overlay'
        );


    if (qvOverlay) {

        const qvClose =
            document.getElementById(
                'quick-view-close'
            );


        const qvPhoto =
            document.getElementById(
                'qv-photo'
            );


        const qvName =
            document.getElementById(
                'qv-name'
            );


        const qvPrice =
            document.getElementById(
                'qv-price'
            );


        const qvSwatches =
            document.getElementById(
                'qv-swatches'
            );


        const qvAddBtn =
            document.getElementById(
                'qv-add-to-cart'
            );


        let qvActiveId =
            null;


        let qvActiveCard =
            null;


        function peso(
            amount
        ) {

            return (
                '₱' +
                Number(amount)
                    .toLocaleString(
                        'en-PH',
                        {
                            minimumFractionDigits:
                                2,

                            maximumFractionDigits:
                                2
                        }
                    )
            );

        }


        function openQuickView(
            id
        ) {

            const product =
                allCards.find(
                    function (card) {

                        return (
                            String(card.id) ===
                            String(id)
                        );

                    }
                );


            if (!product) {
                return;
            }


            qvActiveId =
                product.id;


            qvActiveCard =
                product.el;


            if (qvPhoto) {

                qvPhoto.src =
                    product.image;


                qvPhoto.alt =
                    product.name;

            }


            if (qvName) {

                qvName.textContent =
                    product.name.toUpperCase();

            }


            if (qvPrice) {

                qvPrice.textContent =
                    peso(
                        product.price
                    );

            }


            if (qvSwatches) {

                qvSwatches.innerHTML =
                    '';


                const sourceSwatches =
                    product.el
                        .querySelectorAll(
                            '.swatch'
                        );


                sourceSwatches.forEach(
                    function (
                        swatch,
                        index
                    ) {

                        const button =
                            document.createElement(
                                'button'
                            );


                        button.type =
                            'button';


                        button.className =
                            'swatch';


                        if (
                            swatch.classList.contains(
                                'swatch-outline'
                            )
                        ) {

                            button.classList.add(
                                'swatch-outline'
                            );

                        }


                        if (
                            index === 0
                        ) {

                            button.classList.add(
                                'selected'
                            );

                        }


                        /*
                         * Copy inline background
                         */
                        button.style.backgroundColor =
                            swatch.style.backgroundColor;


                        /*
                         * Also copy normal
                         * style attribute if available.
                         */
                        if (
                            swatch.getAttribute(
                                'style'
                            )
                        ) {

                            button.setAttribute(
                                'style',
                                swatch.getAttribute(
                                    'style'
                                )
                            );

                        }


                        button.dataset.color =
                            swatch.dataset.color ||
                            'Default';


                        button.setAttribute(
                            'aria-label',
                            button.dataset.color
                        );


                        button.setAttribute(
                            'aria-pressed',
                            index === 0
                                ? 'true'
                                : 'false'
                        );


                        qvSwatches.appendChild(
                            button
                        );

                    }
                );

            }


            if (qvAddBtn) {

                qvAddBtn.textContent =
                    'Add to Cart';


                qvAddBtn.disabled =
                    false;


                qvAddBtn.classList.remove(
                    'added'
                );

            }


            qvOverlay.hidden =
                false;


            document.body.style.overflow =
                'hidden';


            if (qvClose) {

                qvClose.focus();

            }

        }


        function closeQuickView() {

            qvOverlay.hidden =
                true;


            document.body.style.overflow =
                '';


            qvActiveId =
                null;


            qvActiveCard =
                null;

        }


        /* ---------------- QUICK VIEW OPEN ---------------- */

        grid.addEventListener(
            'click',
            function (event) {

                const button =
                    event.target.closest(
                        '.quick-view-btn'
                    );


                if (!button) {
                    return;
                }


                const card =
                    button.closest(
                        '.product-card'
                    );


                const id =
                    button.dataset.id ||
                    (
                        card
                            ? (
                                card.dataset.productId ||
                                card.dataset.id
                            )
                            : ''
                    );


                openQuickView(
                    id
                );

            }
        );


        /* ---------------- QUICK VIEW CLOSE ---------------- */

        if (qvClose) {

            qvClose.addEventListener(
                'click',
                closeQuickView
            );

        }


        qvOverlay.addEventListener(
            'click',
            function (event) {

                if (
                    event.target ===
                    qvOverlay
                ) {

                    closeQuickView();

                }

            }
        );


        document.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key ===
                        'Escape' &&
                    !qvOverlay.hidden
                ) {

                    closeQuickView();

                }

            }
        );


        /* ---------------- QUICK VIEW SWATCH ---------------- */

        if (qvSwatches) {

            qvSwatches.addEventListener(
                'click',
                function (event) {

                    const swatch =
                        event.target.closest(
                            '.swatch'
                        );


                    if (!swatch) {
                        return;
                    }


                    qvSwatches
                        .querySelectorAll(
                            '.swatch'
                        )
                        .forEach(
                            function (item) {

                                item.classList.remove(
                                    'selected'
                                );


                                item.setAttribute(
                                    'aria-pressed',
                                    'false'
                                );

                            }
                        );


                    swatch.classList.add(
                        'selected'
                    );


                    swatch.setAttribute(
                        'aria-pressed',
                        'true'
                    );

                }
            );

        }


        /* ---------------- QUICK VIEW ADD TO CART ---------------- */

        if (qvAddBtn) {

            qvAddBtn.addEventListener(
                'click',
                function () {

                    if (
                        !qvActiveId ||
                        !qvActiveCard
                    ) {

                        return;

                    }


                    let selectedColor =
                        'Default';


                    if (qvSwatches) {

                        const selected =
                            qvSwatches.querySelector(
                                '.swatch.selected'
                            );


                        if (
                            selected &&
                            selected.dataset.color
                        ) {

                            selectedColor =
                                selected.dataset.color;

                        }

                    }


                    addProductToCart(
                        qvActiveCard,
                        selectedColor
                    );


                    qvAddBtn.classList.add(
                        'added'
                    );


                    qvAddBtn.textContent =
                        'Added ✓';


                    qvAddBtn.disabled =
                        true;


                    setTimeout(
                        function () {

                            closeQuickView();

                        },
                        800
                    );

                }
            );

        }

    }


    /* =========================================================
       CATEGORY TABS
       ========================================================= */

    if (tabsWrap) {

        tabsWrap.addEventListener(
            'click',
            function (event) {

                const button =
                    event.target.closest(
                        '.tab-btn'
                    );


                if (!button) {
                    return;
                }


                tabsWrap
                    .querySelectorAll(
                        '.tab-btn'
                    )
                    .forEach(
                        function (item) {

                            item.classList.remove(
                                'active'
                            );


                            item.setAttribute(
                                'aria-selected',
                                'false'
                            );

                        }
                    );


                button.classList.add(
                    'active'
                );


                button.setAttribute(
                    'aria-selected',
                    'true'
                );


                state.category =
                    button.dataset.category ||
                    'all';


                state.page =
                    1;


                render();

            }
        );

    }


    /* =========================================================
       SORT
       ========================================================= */

    if (sortSelect) {

        sortSelect.addEventListener(
            'change',
            function () {

                state.sort =
                    sortSelect.value;


                state.page =
                    1;


                render();

            }
        );

    }


    /* =========================================================
       SEARCH QUERY FROM HEADER
       ========================================================= */

    function getSearchQuery() {

        const params =
            new URLSearchParams(
                window.location.search
            );


        return (
            params.get(
                'search'
            ) || ''
        )
        .trim()
        .toLowerCase();

    }


    const searchQuery =
        getSearchQuery();


    /* =========================================================
       FILTER / SORT
       ========================================================= */

    function getFilteredSorted() {

        let list =
            allCards.filter(
                function (card) {

                    const category =
                        (
                            card.category ||
                            ''
                        )
                        .toLowerCase();


                    const productName =
                        (
                            card.name ||
                            ''
                        )
                        .toLowerCase();


                    const categoryMatch =
                        state.category ===
                            'all' ||
                        category ===
                            state.category
                                .toLowerCase();


                    const searchMatch =
                        searchQuery ===
                            '' ||
                        productName.includes(
                            searchQuery
                        ) ||
                        category.includes(
                            searchQuery
                        );


                    return (
                        categoryMatch &&
                        searchMatch
                    );

                }
            );


        switch (
            state.sort
        ) {

            case 'price-asc':

                list =
                    list
                        .slice()
                        .sort(
                            function (
                                a,
                                b
                            ) {

                                return (
                                    a.price -
                                    b.price
                                );

                            }
                        );

                break;


            case 'price-desc':

                list =
                    list
                        .slice()
                        .sort(
                            function (
                                a,
                                b
                            ) {

                                return (
                                    b.price -
                                    a.price
                                );

                            }
                        );

                break;


            case 'name-asc':

                list =
                    list
                        .slice()
                        .sort(
                            function (
                                a,
                                b
                            ) {

                                return (
                                    a.name
                                        .localeCompare(
                                            b.name
                                        )
                                );

                            }
                        );

                break;


            case 'newest':

            default:

                /*
                 * Keep original order.
                 */

                break;

        }


        return list;

    }


    /* =========================================================
       RENDER PRODUCTS
       ========================================================= */

    function render() {

        const filtered =
            getFilteredSorted();


        const totalPages =
            Math.max(
                1,
                Math.ceil(
                    filtered.length /
                    PAGE_SIZE
                )
            );


        state.page =
            Math.min(
                state.page,
                totalPages
            );


        const start =
            (
                state.page -
                1
            ) *
            PAGE_SIZE;


        const pageItems =
            filtered.slice(
                start,
                start +
                    PAGE_SIZE
            );


        const visibleElements =
            new Set(
                pageItems.map(
                    function (card) {

                        return card.el;

                    }
                )
            );


        /*
         * Reorder cards according
         * to selected sorting.
         */
        pageItems.forEach(
            function (card) {

                grid.appendChild(
                    card.el
                );

            }
        );


        allCards.forEach(
            function (card) {

                card.el.hidden =
                    !visibleElements.has(
                        card.el
                    );

            }
        );


        if (noResults) {

            noResults.hidden =
                filtered.length !== 0;

        }


        if (resultsCount) {

            if (
                filtered.length > 0
            ) {

                const end =
                    Math.min(
                        start +
                            PAGE_SIZE,
                        filtered.length
                    );


                resultsCount.textContent =
                    'Showing ' +
                    (
                        start +
                        1
                    ) +
                    '–' +
                    end +
                    ' of ' +
                    filtered.length +
                    ' products';

            } else {

                resultsCount.textContent =
                    'No products found';

            }

        }


        renderPagination(
            totalPages,
            filtered.length
        );

    }


    /* =========================================================
       PAGINATION
       ========================================================= */

    function renderPagination(
        totalPages,
        totalProducts
    ) {

        if (!pagination) {
            return;
        }


        pagination.innerHTML =
            '';


        if (
            totalPages <= 1 ||
            totalProducts === 0
        ) {

            return;

        }


        function makeBtn(
            label,
            page,
            options
        ) {

            options =
                options || {};


            const button =
                document.createElement(
                    'button'
                );


            button.type =
                'button';


            button.textContent =
                label;


            if (
                options.active
            ) {

                button.classList.add(
                    'active'
                );

            }


            if (
                options.disabled
            ) {

                button.disabled =
                    true;

            }


            button.addEventListener(
                'click',
                function () {

                    if (
                        page < 1 ||
                        page > totalPages
                    ) {

                        return;

                    }


                    state.page =
                        page;


                    render();


                    grid.scrollIntoView({
                        behavior:
                            'smooth',

                        block:
                            'start'
                    });

                }
            );


            return button;

        }


        /* Previous */

        pagination.appendChild(
            makeBtn(
                '‹',
                state.page - 1,
                {
                    disabled:
                        state.page ===
                        1
                }
            )
        );


        function addEllipsis() {

            const span =
                document.createElement(
                    'span'
                );


            span.className =
                'page-ellipsis';


            span.textContent =
                '…';


            pagination.appendChild(
                span
            );

        }


        const pagesToShow =
            new Set([
                1,
                totalPages,
                state.page,
                state.page - 1,
                state.page + 1
            ]);


        let previous =
            0;


        Array.from(
            pagesToShow
        )
        .filter(
            function (page) {

                return (
                    page >= 1 &&
                    page <= totalPages
                );

            }
        )
        .sort(
            function (
                a,
                b
            ) {

                return (
                    a - b
                );

            }
        )
        .forEach(
            function (page) {

                if (
                    previous &&
                    page - previous > 1
                ) {

                    addEllipsis();

                }


                pagination.appendChild(
                    makeBtn(
                        String(page),
                        page,
                        {
                            active:
                                page ===
                                state.page
                        }
                    )
                );


                previous =
                    page;

            }
        );


        /* Next */

        pagination.appendChild(
            makeBtn(
                '›',
                state.page + 1,
                {
                    disabled:
                        state.page ===
                        totalPages
                }
            )
        );

    }


    /* =========================================================
       NEWSLETTER
       ========================================================= */

    if (
        newsletterForm &&
        newsletterMsg
    ) {

        newsletterForm.addEventListener(
            'submit',
            function (event) {

                event.preventDefault();


                const input =
                    document.getElementById(
                        'newsletter-email'
                    );


                if (!input) {
                    return;
                }


                const email =
                    input.value.trim();


                const isValid =
                    /^[^\s@]+@[^\s@]+\.[^\s@]+$/
                        .test(
                            email
                        );


                newsletterMsg.hidden =
                    false;


                newsletterMsg.classList.remove(
                    'success',
                    'error'
                );


                if (!isValid) {

                    newsletterMsg.textContent =
                        'Please enter a valid email address.';


                    newsletterMsg.classList.add(
                        'error'
                    );


                    input.focus();


                    return;

                }


                newsletterMsg.textContent =
                    'Thanks! ' +
                    email +
                    ' has been subscribed.';


                newsletterMsg.classList.add(
                    'success'
                );


                newsletterForm.reset();

            }
        );

    }


    /* =========================================================
       INITIALIZE SHOP
       ========================================================= */

    applyWishlistState();

    syncCartBadge();

    render();


})();


/* ============================================================
   GLOBAL CART BADGE SYNC
   Works on every page
   ============================================================ */

(function () {

    'use strict';


    const cartCountEl =
        document.getElementById(
            'cart-count'
        );


    if (!cartCountEl) {
        return;
    }


    const CART_ITEMS_KEY =
        'arip_cart_items';


    const CART_COUNT_KEY =
        'arip_cart_count';


    let cart =
        [];


    try {

        const stored =
            localStorage.getItem(
                CART_ITEMS_KEY
            );


        cart =
            stored
                ? JSON.parse(
                    stored
                )
                : [];


        if (
            !Array.isArray(
                cart
            )
        ) {

            cart =
                [];

        }

    } catch (error) {

        cart =
            [];

    }


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


    cartCountEl.textContent =
        String(count);


    cartCountEl.hidden =
        count === 0;


})();


/* ============================================================
   CONTACT PAGE
   ============================================================ */

(function () {

    'use strict';


    const form =
        document.getElementById(
            'contact-form'
        );


    if (!form) {
        return;
    }


    const nameInput =
        document.getElementById(
            'contact-name'
        );


    const emailInput =
        document.getElementById(
            'contact-email'
        );


    const subjectInput =
        document.getElementById(
            'contact-subject'
        );


    const messageInput =
        document.getElementById(
            'contact-message'
        );


    const statusMessage =
        document.getElementById(
            'contact-js-message'
        );


    form.addEventListener(
        'submit',
        function (event) {

            const name =
                nameInput
                    ? nameInput.value.trim()
                    : '';


            const email =
                emailInput
                    ? emailInput.value.trim()
                    : '';


            const subject =
                subjectInput
                    ? subjectInput.value.trim()
                    : '';


            const message =
                messageInput
                    ? messageInput.value.trim()
                    : '';


            if (
                !name ||
                !email ||
                !subject ||
                !message
            ) {

                event.preventDefault();


                if (
                    statusMessage
                ) {

                    statusMessage.hidden =
                        false;


                    statusMessage.textContent =
                        'Please complete all required fields.';

                }


                return;

            }


            const emailPattern =
                /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


            if (
                !emailPattern.test(
                    email
                )
            ) {

                event.preventDefault();


                if (
                    statusMessage
                ) {

                    statusMessage.hidden =
                        false;


                    statusMessage.textContent =
                        'Please enter a valid email address.';

                }


                if (
                    emailInput
                ) {

                    emailInput.focus();

                }


                return;

            }


            if (
                statusMessage
            ) {

                statusMessage.hidden =
                    true;

            }

        }
    );


})();


/* ============================================================
   GLOBAL HEADER
   SEARCH + CART
   ============================================================ */

(function () {

    'use strict';


    const searchBtn =
        document.getElementById(
            'search-btn'
        );


    const searchOverlay =
        document.getElementById(
            'search-overlay'
        );


    const searchInput =
        document.getElementById(
            'search-input'
        );


    const searchClose =
        document.getElementById(
            'search-close'
        );


    const cartBtn =
        document.getElementById(
            'cart-btn'
        );


    /* =========================================================
       OPEN SEARCH
       ========================================================= */

    if (
        searchBtn &&
        searchOverlay
    ) {

        searchBtn.addEventListener(
            'click',
            function () {

                searchOverlay.classList.add(
                    'active'
                );


                if (
                    searchInput
                ) {

                    setTimeout(
                        function () {

                            searchInput.focus();

                        },
                        100
                    );

                }

            }
        );

    }


    /* =========================================================
       CLOSE SEARCH
       ========================================================= */

    if (
        searchClose &&
        searchOverlay
    ) {

        searchClose.addEventListener(
            'click',
            function () {

                searchOverlay.classList.remove(
                    'active'
                );

            }
        );

    }


    /* =========================================================
       SEARCH ENTER
       ========================================================= */

    if (
        searchInput
    ) {

        searchInput.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key !==
                    'Enter'
                ) {

                    return;

                }


                const query =
                    searchInput.value.trim();


                if (
                    query === ''
                ) {

                    return;

                }


                window.location.href =
                    'shop.php?search=' +
                    encodeURIComponent(
                        query
                    ) +
                    '#product-grid';

            }
        );

    }


    /* =========================================================
       CLICK OUTSIDE SEARCH
       ========================================================= */

    if (
        searchOverlay
    ) {

        searchOverlay.addEventListener(
            'click',
            function (event) {

                if (
                    event.target ===
                    searchOverlay
                ) {

                    searchOverlay.classList.remove(
                        'active'
                    );

                }

            }
        );

    }


    /* =========================================================
       ESC CLOSE SEARCH
       ========================================================= */

    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key ===
                    'Escape' &&
                searchOverlay &&
                searchOverlay.classList.contains(
                    'active'
                )
            ) {

                searchOverlay.classList.remove(
                    'active'
                );

            }

        }
    );


    /* =========================================================
       HEADER CART BUTTON
       ========================================================= */

    if (
        cartBtn
    ) {

        cartBtn.addEventListener(
            'click',
            function (event) {

                /*
                 * Works whether cartBtn
                 * is button or link.
                 */
                const tagName =
                    cartBtn.tagName
                        .toLowerCase();


                if (
                    tagName === 'a' &&
                    cartBtn.getAttribute(
                        'href'
                    ) === 'cart.php'
                ) {

                    return;

                }


                event.preventDefault();


                window.location.href =
                    'cart.php';

            }
        );

    }


})();


/* ============================================================
   LOGIN PAGE
   PHP + MySQL handles real authentication.
   JavaScript ONLY handles Show / Hide Password.
   ============================================================ */

(function () {

    'use strict';


    const passwordInput =
        document.getElementById(
            'login-password'
        );


    const togglePassword =
        document.getElementById(
            'toggle-password'
        );


    if (
        !passwordInput ||
        !togglePassword
    ) {

        return;

    }


    togglePassword.addEventListener(
        'click',
        function () {

            const isHidden =
                passwordInput.type ===
                'password';


            if (
                isHidden
            ) {

                passwordInput.type =
                    'text';


                togglePassword.textContent =
                    'HIDE';

            } else {

                passwordInput.type =
                    'password';


                togglePassword.textContent =
                    'SHOW';

            }

        }
    );


})();
/* ============================================================
   CHECKOUT — GCASH DETAILS
   ============================================================ */

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


    if (
        !gcashRadio ||
        !codRadio ||
        !gcashDetails
    ) {
        return;
    }


    function updatePaymentFields() {

        if (gcashRadio.checked) {

            gcashDetails.hidden =
                false;

            gcashDetails.classList.add(
                'show'
            );

            if (gcashName) {
                gcashName.required = true;
            }

            if (gcashNumber) {
                gcashNumber.required = true;
            }

        } else {

            gcashDetails.hidden =
                true;

            gcashDetails.classList.remove(
                'show'
            );

            if (gcashName) {
                gcashName.required = false;
            }

            if (gcashNumber) {
                gcashNumber.required = false;
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


    /*
     * Only allow numbers in
     * the GCash number field.
     */
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


    updatePaymentFields();

})();