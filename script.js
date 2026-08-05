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
