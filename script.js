// Filter coffee list by region
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.tab-btn');
    const cards = document.querySelectorAll('.card');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            const selectedRegion = tab.dataset.region;

            cards.forEach(card => {
                const cardRegion = card.dataset.region;
                if (selectedRegion === 'all' || cardRegion === selectedRegion) {
                    card.classList.remove('hide');
                } else {
                    card.classList.add('hide');
                }
            });
        });
    });

    // Check URL query params for switching tab on landing
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tab') === 'signin') {
        switchTab('signin');
    }
});

// Exchange rates relative to 1 USD
const exchangeRates = {
    USD: { symbol: "$", rate: 1, prefix: true },
    NPR: { symbol: "Rs. ", rate: 154.5, prefix: true },
    EUR: { symbol: "€", rate: 0.92, prefix: true },
    GBP: { symbol: "£", rate: 0.78, prefix: true }
};

function updatePrices() {
    const currencyElement = document.getElementById("currency");
    if (!currencyElement) return;

    const selectedCurrency = currencyElement.value;
    const { symbol, rate, prefix } = exchangeRates[selectedCurrency];

    document.querySelectorAll(".price").forEach((el) => {
        const usdPrice = parseFloat(el.getAttribute("data-price-usd"));
        const converted = Math.round(usdPrice * rate);
        el.innerText = prefix
            ? `${symbol}${converted.toLocaleString()}`
            : `${converted.toLocaleString()} ${symbol}`;
    });

    document.querySelectorAll(".btn-primary").forEach((btn) => {
        const usdPrice = parseFloat(btn.getAttribute("data-price-usd"));
        const converted = Math.round(usdPrice * rate);
        btn.innerText = prefix
            ? `Add to Order — ${symbol}${converted.toLocaleString()}`
            : `Add to Order — ${converted.toLocaleString()} ${symbol}`;
    });
}

document.addEventListener("DOMContentLoaded", updatePrices);

// Toggle Sign In / Sign Up tabs
function switchTab(tab) {
    const signinBtn  = document.getElementById('signin-btn');
    const signupBtn  = document.getElementById('signup-btn');
    const signinView = document.getElementById('signin-view');
    const signupView = document.getElementById('signup-view');

    if (!signinBtn || !signupBtn || !signinView || !signupView) return;

    if (tab === 'signin') {
        signinBtn.classList.add('active');
        signupBtn.classList.remove('active');
        signinView.classList.add('active');
        signupView.classList.remove('active');
    } else {
        signupBtn.classList.add('active');
        signinBtn.classList.remove('active');
        signupView.classList.add('active');
        signinView.classList.remove('active');
    }
}


/* ==========================================
   BeanFlow Order & Cart Overlay Management
   ========================================== */

document.addEventListener("DOMContentLoaded", () => {
    // 1. Initialize State
    let cart = JSON.parse(localStorage.getItem("beanflow_cart")) || [];

    // 2. Select DOM Elements
    const drawerOverlay = document.getElementById("cart-drawer-overlay");
    const closeCartBtn = document.getElementById("close-cart-btn");
    const placeOrderBtn = document.getElementById("place-order-btn");
    const container = document.getElementById("cart-items-container");
    const totalEl = document.getElementById("cart-total-price");
    const currencySelect = document.getElementById("currency");
    const cartButtons = document.querySelectorAll(".cart-button");

    // 3. Global Event Listeners
    cartButtons.forEach(btn => btn.addEventListener("click", openCartDrawer));
    if (closeCartBtn) closeCartBtn.addEventListener("click", closeCartDrawer);
    if (placeOrderBtn) placeOrderBtn.addEventListener("click", handleCheckout);

    if (drawerOverlay) {
        drawerOverlay.addEventListener("click", (e) => {
            if (e.target === drawerOverlay) closeCartDrawer();
        });
    }

    if (currencySelect) {
        currencySelect.addEventListener("change", () => {
            updatePrices();
            renderCart();
        });
    }

    // Event Delegation for dynamically rendered quantity and remove buttons
    if (container) {
        container.addEventListener("click", (e) => {
            const target = e.target;
            const idx = parseInt(target.getAttribute("data-index"));

            if (isNaN(idx)) return;

            if (target.classList.contains("qty-btn")) {
                const action = target.getAttribute("data-action");
                if (action === "inc") cart[idx].quantity += 1;
                if (action === "dec") {
                    cart[idx].quantity -= 1;
                    if (cart[idx].quantity <= 0) cart.splice(idx, 1);
                }
                saveAndRender();
            }
        });
    }

    attachAddToCartListeners();
    renderCart();

    /* --- Core Functions --- */

    function openCartDrawer() {
        if (drawerOverlay) drawerOverlay.style.display = "flex";
    }

    function closeCartDrawer() {
        if (drawerOverlay) drawerOverlay.style.display = "none";
    }

    function attachAddToCartListeners() {
    const addBtns = document.querySelectorAll(".btn-primary");
    addBtns.forEach(btn => {
        btn.addEventListener("click", (e) => {
            const card = e.target.closest(".card");
            if (!card) return;

            const title = card.querySelector(".card-title")?.innerText.trim() || "Specialty Coffee";
            const priceUsd = parseFloat(btn.getAttribute("data-price-usd")) || 0;
            const image = card.querySelector("img")?.src || "https://via.placeholder.com/50";

            // Extract the badge text (e.g., "ET Ethiopia")
            const originBadge = card.querySelector(".badge.top-left")?.innerText.trim() 
                             || card.querySelector(".badge")?.innerText.trim() 
                             || "";

            addToCart({ title, priceUsd, image, originBadge, quantity: 1 });
            openCartDrawer();
        });
    });
}   

    function addToCart(item) {
        const existingIndex = cart.findIndex(i => i.title === item.title);
        if (existingIndex > -1) {
            cart[existingIndex].quantity += 1;
        } else {
            cart.push(item);
        }
        saveAndRender();
    }

    function saveAndRender() {
        localStorage.setItem("beanflow_cart", JSON.stringify(cart));
        renderCart();
    }

    function renderCart() {
        if (!container || !totalEl) return;

        if (cart.length === 0) {
        // Updated onclick handler below to call closeCartDrawer()
        container.innerHTML = `
            <p class="cart-empty-msg">
                Your order tab is empty. <br> 
                <button class="add-coffees-msg" id="empty-cart-shop-btn">Add some coffees to get started.</button>
            </p>`;
        totalEl.innerText = formatPrice(0);

        // Attach listener to close drawer on click
        document.getElementById("empty-cart-shop-btn")?.addEventListener("click", () => {
            closeCartDrawer();
            window.location.href = "index.html#coffees";
        });

        return;
    }

        let grandTotalUsd = 0;
        let itemsHTML = "";

        cart.forEach((item, index) => {
    const itemTotalUsd = item.priceUsd * item.quantity;
    grandTotalUsd += itemTotalUsd;

    itemsHTML += `
        <div class="cart-item-row">
            <div class="cart-item-info">
                <div class="cart-item-img">
                    <img src="${item.image}" alt="${item.title}" />
                </div>
                <div>
                    <div class="cart-item-title">${item.title}</div>
                    ${item.originBadge ? `<div class="cart-item-origin-badge">${item.originBadge}</div>` : ""}
                    <div class="cart-item-price">${formatPrice(item.priceUsd)} each</div>
                </div>
            </div>
            <div class="cart-item-actions">
                <button class="qty-btn" data-index="${index}" data-action="dec">-</button>
                <span>${item.quantity}</span>
                <button class="qty-btn" data-index="${index}" data-action="inc">+</button>
            </div>
        </div>
    `;
});

        container.innerHTML = itemsHTML;
        totalEl.innerText = formatPrice(grandTotalUsd);
    }

    function formatPrice(usdAmount) {
        const selectedCurrency = currencySelect ? currencySelect.value : "NPR";
        
        const rates = {
            USD: { symbol: "$", rate: 1, prefix: true },
            NPR: { symbol: "Rs. ", rate: 154.5, prefix: true },
            EUR: { symbol: "€", rate: 0.92, prefix: true },
            GBP: { symbol: "£", rate: 0.78, prefix: true }
        };

        const curr = rates[selectedCurrency] || rates["USD"];
        const converted = Math.round(usdAmount * curr.rate);
        return curr.prefix ? `${curr.symbol}${converted.toLocaleString()}` : `${converted.toLocaleString()} ${curr.symbol}`;
    }

    // Guards against duplicate submissions from rapid/multiple clicks
    let isPlacingOrder = false;

    async function handleCheckout() {
        if (isPlacingOrder) return; // a request is already in flight

        if (cart.length === 0) {
            alert("Your cart is empty! Please add items before placing an order.");
            return;
        }

        // Calculate total price in USD
        const totalAmount = cart.reduce((sum, item) => sum + (item.priceUsd * item.quantity), 0);

        isPlacingOrder = true;
        const originalBtnText = placeOrderBtn ? placeOrderBtn.textContent : "";
        if (placeOrderBtn) {
            placeOrderBtn.disabled = true;
            placeOrderBtn.textContent = "Placing Order...";
        }

        try {
            const response = await fetch('place_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    items: cart,
                    total: totalAmount
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                alert(result.message);
                cart = [];
                saveAndRender();
                closeCartDrawer();
            } else {
                alert("Order failed: " + result.message);
            }
        } catch (err) {
            alert("Unable to place your order. Please check your connection and try again.");
            console.error(err);
        } finally {
            isPlacingOrder = false;
            if (placeOrderBtn) {
                placeOrderBtn.disabled = false;
                placeOrderBtn.textContent = originalBtnText;
            }
        }
    }
});