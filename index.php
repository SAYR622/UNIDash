<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniDash</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,300;0,400;0,600;0,1000;1,300;1,400;1,600;1,1000&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<script>

    // --- SHOPPING CART LOGIC ---
    let cart = [];

    // 1. Function to Add Item (Now tracks the Outlet!)
    function addToCart(name, price, outlet) {
        // Check if item already exists from the SAME outlet
        const existingItem = cart.find(item => item.name === name && item.outlet === outlet);

        if (existingItem) {
            existingItem.qty++;
        } else {
            // Push the item AND the outlet name into the cart
            cart.push({ name: name, price: price, qty: 1, outlet: outlet || 'Unknown' });
        }

        updateCartUI();
    }

    // 2. Function to Remove/Decrease Item
    function removeFromCart(name, outlet) {
        const itemIndex = cart.findIndex(item => item.name === name && item.outlet === outlet);
        if (itemIndex > -1) {
            if (cart[itemIndex].qty > 1) {
                cart[itemIndex].qty--;
            } else {
                cart.splice(itemIndex, 1);
            }
        }
        updateCartUI();
    }

    // 3. Update Visuals (Badge & Modal)
    function updateCartUI() {
        const badge = document.getElementById('cart-badge');
        const container = document.getElementById('cart-items-container');
        const totalDisplay = document.getElementById('cart-total');
        
        // Update Badge
        const totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
        if (totalQty > 0) {
            badge.style.display = 'flex';
            badge.textContent = totalQty;
        } else {
            badge.style.display = 'none';
        }

        // Calculate Total Price
        const totalPrice = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        totalDisplay.textContent = `LKR ${totalPrice}`;

        // Render Modal List (Now shows the outlet name!)
        if (cart.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="fas fa-shopping-basket mb-3 fs-1 opacity-50"></i>
                    <p>Your cart is empty.</p>
                </div>`;
        } else {
            let html = '<ul class="list-group list-group-flush">';
            cart.forEach(item => {
                html += `
                    <li class="list-group-item bg-transparent text-white border-secondary d-flex justify-content-between align-items-center px-0">
                        <div>
                            <div class="fw-bold">${item.name}</div>
                            <div class="small text-muted"><span class="text-accent-cyan">${item.outlet}</span> • LKR ${item.price} x ${item.qty}</div>
                        </div>
                        <div class="d-flex align-items-center">
                            <button class="btn btn-sm btn-outline-light rounded-circle" style="width:30px; height:30px; padding:0;" onclick="removeFromCart('${item.name}', '${item.outlet}')">-</button>
                            <span class="mx-2">${item.qty}</span>
                            <button class="btn btn-sm btn-accent rounded-circle" style="width:30px; height:30px; padding:0;" onclick="addToCart('${item.name}', ${item.price}, '${item.outlet}')">+</button>
                        </div>
                    </li>
                `;
            });
            html += '</ul>';
            container.innerHTML = html;
        }
    }

    // 4. Prepare Data for PHP Form
    // --- CHECKOUT & PAYMENT FLOW ---
    
    // 1. Move from Cart to Payment Modal
    function proceedToPayment() {
        if (cart.length === 0) {
            alert("Your cart is empty!");
            return;
        }

        // If not logged in, show signup
        if (!localStorage.getItem('uni_fname')) {
            const cartModalEl = document.getElementById('cartModal');
            const cartModalInstance = bootstrap.Modal.getInstance(cartModalEl) || new bootstrap.Modal(cartModalEl);
            cartModalInstance.hide();

            setTimeout(() => {
                const signupModalEl = document.getElementById('signupModal');
                const signupModalInstance = new bootstrap.Modal(signupModalEl);
                signupModalInstance.show();
            }, 300);
            return; 
        }

        // If logged in, calculate total, hide cart, and show payment modal
        const totalPrice = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        document.getElementById('payment-total-display').textContent = `LKR ${totalPrice}`;

        const cartModalEl = document.getElementById('cartModal');
        const cartModalInstance = bootstrap.Modal.getInstance(cartModalEl) || new bootstrap.Modal(cartModalEl);
        cartModalInstance.hide();

        setTimeout(() => {
            const paymentModalEl = document.getElementById('paymentModal');
            const paymentModalInstance = new bootstrap.Modal(paymentModalEl);
            paymentModalInstance.show();
        }, 300);
    }

    // 2. Hide/Show the dummy credit card fields
    function toggleCardFields() {
        const isCard = document.getElementById('pay_card').checked;
        const cardFields = document.getElementById('card-details-form');
        
        if(isCard) {
            cardFields.classList.remove('d-none');
            document.getElementById('cc_num').required = true;
            document.getElementById('cc_exp').required = true;
            document.getElementById('cc_cvv').required = true;
        } else {
            cardFields.classList.add('d-none');
            document.getElementById('cc_num').required = false;
            document.getElementById('cc_exp').required = false;
            document.getElementById('cc_cvv').required = false;
        }
    }

    // 3. Finalize data and send to backend
    function finalizeCheckout() {
        document.getElementById('checkout_fname').value = localStorage.getItem('uni_fname');
        document.getElementById('checkout_membertype').value = localStorage.getItem('uni_type');
        
        const locationText = document.getElementById('currentLocation').textContent.trim();
        document.getElementById('checkout_deliver_to').value = locationText;

        const cartJSON = JSON.stringify(cart);
        document.getElementById('cart_hidden_input').value = cartJSON;

        return true; 
    }

    // --- 1. Smooth Scroll Function ---
    function scrollToSection(id) {
        // NEW: If the admin is editing, ignore the click and DO NOT scroll!
        if (typeof isEditMode !== 'undefined' && isEditMode) return; 
        
        const element = document.getElementById(id);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // --- 2. The Edge Sub-Outlet Switcher ---
    function switchEdgeOutlet(outletName) {
        // 1. Update Buttons (Visuals)
        // We look for all buttons inside the 'edge-nav' container
        const buttons = document.querySelectorAll('.edge-nav .btn');
        buttons.forEach(btn => {
            btn.classList.remove('btn-accent', 'text-black'); // Active Style
            btn.classList.add('btn-outline-light', 'text-white'); // Inactive Style
        });

        // Highlight the clicked button (passed as 'this' in HTML)
        const activeBtn = document.getElementById('btn-' + outletName);
        activeBtn.classList.remove('btn-outline-light', 'text-white');
        activeBtn.classList.add('btn-accent', 'text-black');

        // 2. Show/Hide Items
        const allItems = document.querySelectorAll('.edge-item');
        allItems.forEach(item => {
            if (item.getAttribute('data-outlet') === outletName) {
                item.classList.remove('d-none');
                // Small animation to make it feel responsive
                item.classList.add('fade-enter-end'); 
            } else {
                item.classList.add('d-none');
                item.classList.remove('fade-enter-end');
            }
        });
    }


    // Updated Helper: Sorts container AND updates the specific button text
    function sortSpecificContainer(containerId, sortBy, buttonId, buttonText) {
        const container = document.getElementById(containerId);
        if (!container) return;

        // Grab all the individual item cards inside the container
        const cards = Array.from(container.getElementsByClassName('col-6'));

        // Sort the cards based on the requested criteria
        cards.sort((a, b) => {
            if (sortBy === 'name') {
                // Alphabetical sorting (A-Z)
                const nameA = a.getAttribute('data-name') || '';
                const nameB = b.getAttribute('data-name') || '';
                return nameA.localeCompare(nameB);
                
            } else if (sortBy === 'price') {
                // Ascending sorting (Lowest to Highest)
                const priceA = parseInt(a.getAttribute('data-price') || 0);
                const priceB = parseInt(b.getAttribute('data-price') || 0);
                return priceA - priceB;
                
            } else if (sortBy === 'rating') {
                // Descending sorting (Highest rating to Lowest)
                const ratingA = parseFloat(a.getAttribute('data-rating') || 0);
                const ratingB = parseFloat(b.getAttribute('data-rating') || 0);
                return ratingB - ratingA; 
            }
        });

        // Clear the current un-sorted HTML and append the newly sorted cards
        container.innerHTML = '';
        cards.forEach(card => container.appendChild(card));

        // Update the specific button's text to reflect the current sort state
        if (buttonId && buttonText) {
            const button = document.getElementById(buttonId);
            if (button) {
                button.innerHTML = `Sorted by ${buttonText} <i class="fas fa-sliders-h ms-2"></i>`;
            }
        }
    }

    // Main Function for the TOP dropdown (Restaurants)
    function sortCards(sortBy, text) {
        sortSpecificContainer('cardsContainer', sortBy, 'sortDropdown', text);
    }

    function sortCards(sortBy, text) {
        // 1. Get the container and all cards inside it
        const container = document.getElementById('cardsContainer');
        const cards = Array.from(container.getElementsByClassName('col-6'));

        // 2. Sort the array of cards based on the criteria
        cards.sort((a, b) => {
            if (sortBy === 'name') {
                // A-Z string comparison
                return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
            } else if (sortBy === 'rating') {
                // Highest number first (Descending)
                return b.getAttribute('data-rating') - a.getAttribute('data-rating');
            } else if (sortBy === 'time') {
                // Lowest number first (Ascending)
                return a.getAttribute('data-time') - b.getAttribute('data-time');
            }
        });

        // 3. Clear the container and append cards in new order
        container.innerHTML = '';
        cards.forEach(card => container.appendChild(card));

        // 4. Update the button text to show current sort
        const button = document.getElementById('sortDropdown');
        button.innerHTML = `Sorted by ${text} <i class="fas fa-sliders-h ms-2"></i>`;
    }

    // --- NEW CODE: Run this immediately when the page loads ---
    document.addEventListener("DOMContentLoaded", function() {
        sortCards('rating', 'Top Rated');
    });

    // --- LIVE SEARCH LOGIC ---
    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById('searchInput');
        
        if(searchInput) {
            searchInput.addEventListener('input', function(e) {
                // Get the search query and convert to lowercase
                const query = e.target.value.toLowerCase().trim();
                
                // Select all elements on the page that have a 'data-name' attribute
                // This targets both your Outlet Cards AND your individual Menu/Store items
                const itemsToFilter = document.querySelectorAll('[data-name]');
                
                itemsToFilter.forEach(item => {
                    const itemName = item.getAttribute('data-name').toLowerCase();
                    
                    // If the item's name includes the typed text
                    if (itemName.includes(query)) {
                        // Remove inline display rules so it behaves normally
                        item.style.setProperty('display', '', 'important');
                    } else {
                        // Forcefully hide the item if it doesn't match
                        item.style.setProperty('display', 'none', 'important');
                    }
                });
            });
        }
    });

    function updateLocation(element) {
        // 1. Get the text from the clicked item (e.g., "Engineering Faculty")
        const newText = element.textContent.trim();
        
        // 2. Find the main text span and update it
        document.getElementById('currentLocation').textContent = newText;

        // 3. (Optional) Move the blue 'active' highlight to the new item
        // Remove 'active' from all items first
        document.querySelectorAll('.location-selector + .dropdown-menu .dropdown-item').forEach(item => {
            item.classList.remove('active');
        });
        // Add 'active' to the one we just clicked
        element.classList.add('active');
    }

    function switchTab(category) {
        // 1. Update Buttons (Visuals only)
        const btnUni = document.getElementById('btn-uni');
        const btnOut = document.getElementById('btn-out');
        
        // Reset styles
        [btnUni, btnOut].forEach(btn => {
            btn.classList.remove('active', 'text-dark', 'fw-bold');
            btn.classList.add('text-white', 'fw-semibold');
        });

        // Highlight clicked button
        const activeBtn = (category === 'uni') ? btnUni : btnOut;
        activeBtn.classList.add('active', 'text-dark', 'fw-bold');
        activeBtn.classList.remove('text-white', 'fw-semibold');


        // 2. THE ANIMATION LOGIC
        const allCards = document.querySelectorAll('.anim-card');
        
        // Step A: Fade OUT currently visible cards
        allCards.forEach(card => {
            if (!card.classList.contains('d-none')) {
                card.classList.add('fade-exit'); // Trigger fade out CSS
            }
        });

        // Step B: Wait 300ms (for fade out to finish), then Swap
        setTimeout(() => {
            allCards.forEach(card => {
                // Clean up exit class
                card.classList.remove('fade-exit');
                
                // Check if this card belongs to the new category
                if (card.getAttribute('data-category') === category) {
                    // PREPARE TO ENTER:
                    // 1. Unhide it
                    card.classList.remove('d-none');
                    // 2. Set starting position (invisible & low)
                    card.classList.add('fade-enter-start');
                    
                    // 3. Force browser to accept the new position (Reflow)
                    void card.offsetWidth; 
                    
                    // 4. ANIMATE IN:
                    // Remove start position and add end position
                    card.classList.remove('fade-enter-start');
                    card.classList.add('fade-enter-end');
                    
                } else {
                    // Hide cards that don't match
                    card.classList.add('d-none');
                    card.classList.remove('fade-enter-end');
                }
            });
            
            // Update Title text based on selection
            const title = document.querySelector('h1');
            title.textContent = (category === 'uni') ? "University Outlets" : "City Eats";
            
        }, 300); // This 300 matches the CSS transition time
    }

    // Initial setup
    document.addEventListener("DOMContentLoaded", function() {
        // Add click listeners
        document.getElementById('btn-uni').onclick = function() { switchTab('uni'); };
        document.getElementById('btn-out').onclick = function() { switchTab('out'); };
    });

</script>

<div class="mx-5">
    <nav class="navbar ss navbar-expand-lg py-2">
        <div class="container-fluid px-4 position-relative">
            
            <a class="navbar-brand fw-bold me-5 mb-0" href="index.php"><img height="45px" width="140px" id="myGif"></a>
            
            <div class="dropdown me-auto">
                <div class="d-flex align-items-center text-white location-selector" 
                    role="button" 
                    id="locationDropdown" 
                    data-bs-toggle="dropdown" 
                    aria-expanded="false">
                    
                    <div class="icon-circle me-2"><i class="fas fa-map-marker-alt"></i></div>
                    <span class="fw-semibold fs-7" id="currentLocation">Main Entrance</span>
                    <i class="fas fa-chevron-down ms-2 fs-7"></i>
                </div>

                <ul class="dropdown-menu dropdown-menu-dark mt-2 shadow-lg border-0 fs-7" aria-labelledby="locationDropdown">
                    <li><a class="dropdown-item active" href="#" onclick="updateLocation(this)"><i class="fas fa-university me-2 ms-2"></i>Main Entrance</a></li>
                    <li><a class="dropdown-item" href="#" onclick="updateLocation(this)"><i class="fas fa-cogs me-2 ms-1"></i>Eng/Sci Faculty</a></li>
                    <li><a class="dropdown-item" href="#" onclick="updateLocation(this)"><i class="fa-solid fa-desktop me-2 ms-1"></i>Computing Faculty</a></li>
                    <li><a class="dropdown-item" href="#" onclick="updateLocation(this)"><i class="fa-brands fa-sellsy me-2 ms-1"></i>Business Faculty</a></li>
                    <li><a class="dropdown-item" href="#" onclick="updateLocation(this)"><i class="fa-solid fa-s me-2 ms-2"></i>Student Centre</a></li>
                </ul>
            </div>

            <form class="d-flex search-form position-absolute start-50 top-50 translate-middle d-none d-lg-flex" onsubmit="event.preventDefault();">
                <div class="input-group">
                    <span class="input-group-text border-0 bg-transparent text-muted"><i class="fas fa-search"></i></span>
                    <input id="searchInput" class="form-control fs-7 border-0 bg-transparent text-white shadow-none" type="search" placeholder="Search UniDash...">
                </div>
            </form>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                
                <ul class="navbar-nav align-items-center" id="guest-buttons">
                    <li class="nav-item">
                        <a class="nav-link text-white fw-semibold mx-2 fs-7" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Log in</a>
                    </li>
                    <li class="nav-item">
                        <button class="btn btn-accent fw-bold px-4 py-2 ms-2 fs-7" data-bs-toggle="modal" data-bs-target="#signupModal">Sign up</button>
                    </li>
                </ul>

                <div id="admin-nav-dashboard" class="d-none ms-4">
                    <a href="admin_dashboard.php" class="btn btn-info fw-bold py-2 px-3 shadow-sm text-dark rounded-pill">
                        <i class="fas fa-clipboard-list me-2"></i>Orders
                    </a>
                </div>

                <div id="user-profile" class="d-none ms-3">
                    <button class="btn btn-accent rounded-circle fw-bold d-flex align-items-center justify-content-center border-0 shadow" 
                            style="width: 45px; height: 45px; font-size: 1.3rem; color: #000;" 
                            data-bs-toggle="modal" data-bs-target="#profileModal" id="nav-user-initial">
                        U
                    </button>
                </div>

            </div>

        </div>
    </nav>

    <script>
        var gifImage = document.getElementById('myGif');
        // Set the source with the timestamp immediately
        gifImage.src = 'Images/Logos/1.svg?t=' + new Date().getTime();
    </script>

    <div class="container-fluid px-4 mt-4">
        
        <div class="d-flex ss justify-content-center mb-5">
            <div class="nav-pills-container d-flex p-1 fs-7">

                <a href="#" 
                id="btn-uni" 
                onclick="switchTab('uni')" 
                class="nav-pill-link active text-dark text-decoration-none fw-bold px-4 py-2 rounded-pill me-2">
                <i class="fas fa-university me-2"></i>UNI
                </a>

                <a href="#" 
                id="btn-out" 
                onclick="switchTab('out')" 
                class="nav-pill-link text-white text-decoration-none fw-semibold px-4 py-2 rounded-pill">
                <i class="fas fa-map-location-dot me-2"></i>OUT
                </a>

            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="display-5 fw-bold py-2 text-white fs-2">University Outlets</h1>
            
            <div class="dropdown">
                <button class="btn btn-sort border-0 rounded-pill px-3 py-2 fw-semibold fs-7 dropdown-toggle" 
                        type="button" 
                        id="sortDropdown" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false">
                    Sorted by Top Rated <i class="fas fa-sliders-h ms-2"></i>
                </button>

                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg fs-7 border-0" aria-labelledby="sortDropdown">
                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortCards('rating', 'Top Rated')"><i class="fas fa-thumbs-up me-2"></i>Top Rated</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortCards('time', 'Fastest Delivery')"><i class="fas fa-clock me-2"></i>Fastest Delivery</a></li>
                    <li><hr class="dropdown-divider border-secondary"></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortCards('name', 'Alphabetical (A-Z)')"><i class="fas fa-sort-alpha-down me-2"></i>Alphabetical (A-Z)</a></li>
                </ul>
            </div>
        </div>

        <div class="mb-5">
            <div class="row g-4 ss" id="cardsContainer">

                <div class="col-6 col-md-4 col-lg-2 anim-card" 
                    data-category="uni" 
                    data-name="Cargills Express" 
                    data-rating="4.4" 
                    data-time="8" 
                    onclick="scrollToSection('cargillsSection')" 
                    style="cursor: pointer;">
                    
                    <div class="card category-card h-100 border-0">
                        <img src="Images/Logos/cargills express.png" class="card-img-top">
                        <div class="card-body">
                            <h5 class="card-title mb-1 fs-6">Cargills Express</h5>
                            <p class="card-text text-muted small">⭐ 4.4 • 8 min</p>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2 anim-card" 
                    data-category="uni" 
                    data-name="The Edge" 
                    data-rating="4.8" 
                    data-time="15" 
                    onclick="scrollToSection('edgeSection')" 
                    style="cursor: pointer;">
                    
                    <div class="card category-card h-100 border-0">
                        <img src="Images/Logos/edge.jpg" class="card-img-top">
                        <div class="card-body">
                            <h5 class="card-title mb-1 fs-6">The Edge</h5>
                            <p class="card-text text-muted small">⭐ 4.8 • 15 min</p>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2 anim-card" 
                    data-category="uni" 
                    data-name="P&S" 
                    data-rating="4.5" 
                    data-time="5" 
                    onclick="scrollToSection('pnsSection')" 
                    style="cursor: pointer;">
                    
                    <div class="card category-card h-100 border-0">
                        <img src="Images/Logos/P&S.png" class="card-img-top">
                        <div class="card-body">
                            <h5 class="card-title mb-1 fs-6">P&S</h5>
                            <p class="card-text text-muted small">⭐ 4.5 • 5 min</p>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2 anim-card" 
                    data-category="uni" 
                    data-name="Barista" 
                    data-rating="3.9" 
                    data-time="10" 
                    onclick="scrollToSection('baristaSection')" 
                    style="cursor: pointer;">
                    
                    <div class="card category-card h-100 border-0">
                        <img src="Images/Logos/barista.jpg" class="card-img-top">
                        <div class="card-body">
                            <h5 class="card-title mb-1 fs-6">Barista</h5>
                            <p class="card-text text-muted small">⭐ 3.9 • 10 min</p>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2 anim-card" 
                    data-category="uni" 
                    data-name="Finagle" 
                    data-rating="4.2" 
                    data-time="6" 
                    onclick="scrollToSection('finagleSection')" 
                    style="cursor: pointer;">
                    
                    <div class="card category-card h-100 border-0">
                        <img src="Images/Logos/finagle.jpg" class="card-img-top">
                        <div class="card-body">
                            <h5 class="card-title mb-1 fs-6">Finagle</h5>
                            <p class="card-text text-muted small">⭐ 4.2 • 6 min</p>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2 anim-card" 
                    data-category="uni" 
                    data-name="Hostel Canteen" 
                    data-rating="3.5" 
                    data-time="15" 
                    onclick="scrollToSection('hostelSection')" 
                    style="cursor: pointer;">
                    
                    <div class="card category-card h-100 border-0">
                        <img src="Images/Logos/Hostel.png" class="card-img-top">
                        <div class="card-body">
                            <h5 class="card-title mb-1 fs-6">Hostel Canteen</h5>
                            <p class="card-text text-muted small">⭐ 3.5 • 15 min</p>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2 anim-card d-none" 
                    data-category="out" 
                    data-name="KFC" 
                    data-rating="4.1" 
                    data-time="40"
                    onclick="scrollToSection('kfcSection')" 
                    style="cursor: pointer;">
                    <div class="card category-card h-100 border-0">
                        <img src="Images/Logos/KFC.png" class="card-img-top">
                        <div class="card-body">
                            <h5 class="card-title mb-1 fs-6">KFC</h5>
                            <p class="card-text text-muted small">⭐ 4.1 • 40 min</p>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2 anim-card d-none" 
                    data-category="out" 
                    data-name="Pizza Hut" 
                    data-rating="4.3" 
                    data-time="45"
                    onclick="scrollToSection('pizzaHutSection')" 
                    style="cursor: pointer;">
                    <div class="card category-card h-100 border-0">
                        <img src="Images/Logos/Pizzahut.jpg" class="card-img-top">
                        <div class="card-body">
                            <h5 class="card-title mb-1 fs-6">Pizza Hut</h5>
                            <p class="card-text text-muted small">⭐ 4.3 • 45 min</p>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2 anim-card d-none" 
                    data-category="out" 
                    data-name="Domino's Pizza" 
                    data-rating="4.0" 
                    data-time="35"
                    onclick="scrollToSection('dominosSection')" 
                    style="cursor: pointer;">
                    <div class="card category-card h-100 border-0">
                        <img src="Images/Logos/dominos.png" class="card-img-top">
                        <div class="card-body">
                            <h5 class="card-title mb-1 fs-6">Domino's</h5>
                            <p class="card-text text-muted small">⭐ 4.0 • 35 min</p>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-2 anim-card d-none" 
                    data-category="out" 
                    data-name="The Fab" 
                    data-rating="4.6" 
                    data-time="25"
                    onclick="scrollToSection('fabSection')" 
                    style="cursor: pointer;">
                    <div class="card category-card h-100 border-0">
                        <img src="Images/Logos/fab.png" class="card-img-top">
                        <div class="card-body">
                            <h5 class="card-title mb-1 fs-6">The Fab</h5>
                            <p class="card-text text-muted small">⭐ 4.6 • 25 min</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-5 anim-card" data-category="uni" id="edgeSection">
                <div class="col-12 d-flex justify-content-between align-items-start mb-2">
                    <div class="text-start">
                        <h2 class="text-white fw-bold display-6 mb-0">The Edge</h2>
                        <p class="text-muted m-0">Select an outlet to view their menu</p>
                        <div class="edge-nav d-inline-flex gap-2 mt-2 bg-dark p-2 rounded-pill border border-secondary">
                            <button class="btn btn-accent text-black fw-bold rounded-pill px-4" id="btn-one" onclick="switchEdgeOutlet('one')"><i class="fas fa-utensils me-2"></i>Serenity</button>
                            <button class="btn btn-outline-light text-white fw-semibold rounded-pill px-4" id="btn-two" onclick="switchEdgeOutlet('two')"><i class="fas fa-utensils me-2"></i>Remarko</button>
                            <button class="btn btn-outline-light text-white fw-semibold rounded-pill px-4" id="btn-juice" onclick="switchEdgeOutlet('juice')"><i class="fas fa-glass-martini-alt me-2"></i>Juice Bar</button>
                        </div>
                    </div>
                    
                    <div class="dropdown mt-2">
                        <button class="btn btn-sort border-0 rounded-pill px-3 py-2 fw-semibold fs-7 dropdown-toggle" type="button" id="edgeSortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Sorted by Alphabetical <i class="fas fa-sliders-h ms-2"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg fs-7 border-0" aria-labelledby="edgeSortDropdown">
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('edgeItemsContainer', 'price', 'edgeSortDropdown', 'Lowest Price')"><i class="fas fa-tag me-2"></i>Lowest Price</a></li>
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('edgeItemsContainer', 'name', 'edgeSortDropdown', 'Alphabetical')"><i class="fas fa-sort-alpha-down me-2"></i>Alphabetical</a></li>
                        </ul>
                    </div>
                </div>

                <div class="row g-4" id="edgeItemsContainer">
                    
                    <?php
                    if (!isset($db_conn)) {
                        $db_conn = new mysqli("localhost", "root", "", "unidash");
                    }
                    
                    // 1. Fetch Serenity items
                    $sql_serenity = "SELECT id, item_name, price, image_url FROM menu_items WHERE outlet_id = 'serenity'";
                    $res_serenity = $db_conn->query($sql_serenity);
                    
                    if ($res_serenity && $res_serenity->num_rows > 0) {
                        while($row = $res_serenity->fetch_assoc()) {
                            $name = htmlspecialchars($row["item_name"]);
                            $price = $row["price"];
                            $img = htmlspecialchars($row["image_url"]);
                            
                            // Notice 'edge-item' and 'data-outlet="one"' are added here
                            echo '
                            <div class="col-6 col-md-4 col-lg-3 edge-item" data-outlet="one" data-item-id="'.$row["id"].'" data-name="'.$name.'" data-price="'.$price.'">
                                <div class="card category-card h-100 border-0">
                                    <img src="'.$img.'" style="object-fit: cover;" class="card-img-top">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title mb-1 fs-6">'.$name.'</h5>
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <p class="card-text text-muted small m-0">LKR '.$price.'</p>
                                            <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="event.stopPropagation(); addToCart(\''.$name.'\', '.$price.', \'Serenity\')">Add +</button>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        }
                    }

                    // 2. Fetch Remarko items
                    $sql_remarko = "SELECT id, item_name, price, image_url FROM menu_items WHERE outlet_id = 'remarko'";
                    $res_remarko = $db_conn->query($sql_remarko);
                    
                    if ($res_remarko && $res_remarko->num_rows > 0) {
                        while($row = $res_remarko->fetch_assoc()) {
                            $name = htmlspecialchars($row["item_name"]);
                            $price = $row["price"];
                            $img = htmlspecialchars($row["image_url"]);
                            
                            // Notice 'edge-item d-none' and 'data-outlet="two"' are added here
                            echo '
                            <div class="col-6 col-md-4 col-lg-3 edge-item d-none" data-outlet="two" data-item-id="'.$row["id"].'" data-name="'.$name.'" data-price="'.$price.'">
                                <div class="card category-card h-100 border-0">
                                    <img src="'.$img.'" style="object-fit: cover;" class="card-img-top">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title mb-1 fs-6">'.$name.'</h5>
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <p class="card-text text-muted small m-0">LKR '.$price.'</p>
                                            <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="event.stopPropagation(); addToCart(\''.$name.'\', '.$price.', \'Remarko\')">Add +</button>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        }
                    }

                    // 3. Fetch Juice Bar items
                    $sql_juice = "SELECT id, item_name, price, image_url FROM menu_items WHERE outlet_id = 'juice'";
                    $res_juice = $db_conn->query($sql_juice);
                    
                    if ($res_juice && $res_juice->num_rows > 0) {
                        while($row = $res_juice->fetch_assoc()) {
                            $name = htmlspecialchars($row["item_name"]);
                            $price = $row["price"];
                            $img = htmlspecialchars($row["image_url"]);
                            
                            // Notice 'edge-item d-none' and 'data-outlet="juice"' are added here
                            echo '
                            <div class="col-6 col-md-4 col-lg-3 edge-item d-none" data-outlet="juice" data-item-id="'.$row["id"].'" data-name="'.$name.'" data-price="'.$price.'">
                                <div class="card category-card h-100 border-0">
                                    <img src="'.$img.'" style="object-fit: cover;" class="card-img-top">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title mb-1 fs-6">'.$name.'</h5>
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <p class="card-text text-muted small m-0">LKR '.$price.'</p>
                                            <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="event.stopPropagation(); addToCart(\''.$name.'\', '.$price.', \'Juice Bar\')">Add +</button>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        }
                    }
                    ?>
                    
                </div>
            </div>

            <div class="row g-4 mt-5 anim-card" data-category="uni" id="pnsSection">
                <div class="col-12 d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="text-white fw-bold display-6 mb-0">P & S</h2>
                        <p class="text-muted m-0">Quick Eats & Pastries</p>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-sort border-0 rounded-pill px-3 py-2 fw-semibold fs-7 dropdown-toggle" type="button" id="pnsSortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Sorted by Alphabetical <i class="fas fa-sliders-h ms-2"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg fs-7 border-0" aria-labelledby="pnsSortDropdown">
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('pnsItemsContainer', 'price', 'pnsSortDropdown', 'Lowest Price')"><i class="fas fa-tag me-2"></i>Lowest Price</a></li>
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('pnsItemsContainer', 'name', 'pnsSortDropdown', 'Alphabetical')"><i class="fas fa-sort-alpha-down me-2"></i>Alphabetical</a></li>
                        </ul>
                    </div>
                </div>

                <div class="row g-4" id="pnsItemsContainer">
                    <?php
                    // 1. Connect to the database
                    $db_conn = new mysqli("localhost", "root", "", "unidash");
                    
                    // 2. Request only the items that belong to P&S
                    $sql_select = "SELECT id, item_name, price, image_url FROM menu_items WHERE outlet_id = 'pns'";
                    $result = $db_conn->query($sql_select);
                    
                    // 3. Loop through the results (Just like in your lecture!)
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            
                            // Store database values in variables
                            $name = htmlspecialchars($row["item_name"]);
                            $price = $row["price"];
                            $img = htmlspecialchars($row["image_url"]);
                            
                            // Echo the HTML, injecting the variables where the hardcoded text used to be
                            echo '
                            <div class="col-6 col-md-4 col-lg-2" data-item-id="'.$row["id"].'" data-name="'.$name.'" data-price="'.$price.'">
                                <div class="card category-card h-100 border-0">
                                    <img src="'.$img.'" style="object-fit: cover;" class="card-img-top">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title mb-1 fs-6">'.$name.'</h5>
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <p class="card-text text-muted small m-0">LKR '.$price.'</p>
                                            
                                            <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="event.stopPropagation(); addToCart(\''.$name.'\', '.$price.', \'P&S\')">Add +</button>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        }
                    } else {
                        echo "<p class='text-muted w-100 text-center mt-4'>Menu items coming soon...</p>";
                    }
                    ?>
                </div>
                
            </div>

            <div class="row g-4 mt-5 anim-card" data-category="uni" id="cargillsSection">
                <div class="col-12 d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="text-white fw-bold display-6 mb-0">Cargills Express</h2>
                        <p class="text-muted m-0">Groceries, Snacks & Essentials</p>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-sort border-0 rounded-pill px-3 py-2 fw-semibold fs-7 dropdown-toggle" type="button" id="cargillsSortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Sorted by Alphabetical <i class="fas fa-sliders-h ms-2"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg fs-7 border-0" aria-labelledby="cargillsSortDropdown">
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('cargillsItemsContainer', 'price', 'cargillsSortDropdown', 'Lowest Price')"><i class="fas fa-tag me-2"></i>Lowest Price</a></li>
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('cargillsItemsContainer', 'name', 'cargillsSortDropdown', 'Alphabetical')"><i class="fas fa-sort-alpha-down me-2"></i>Alphabetical</a></li>
                        </ul>
                    </div>
                </div>

                <div class="row g-4" id="cargillsItemsContainer">
                    <?php
                    // 1. Connect to the database
                    $db_conn = new mysqli("localhost", "root", "", "unidash");
                    
                    // 2. Request only the items that belong to P&S
                    $sql_select = "SELECT id, item_name, price, image_url FROM menu_items WHERE outlet_id = 'cargills'";
                    $result = $db_conn->query($sql_select);
                    
                    // 3. Loop through the results (Just like in your lecture!)
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            
                            // Store database values in variables
                            $name = htmlspecialchars($row["item_name"]);
                            $price = $row["price"];
                            $img = htmlspecialchars($row["image_url"]);
                            
                            // Echo the HTML, injecting the variables where the hardcoded text used to be
                            echo '
                            <div class="col-6 col-md-4 col-lg-2" data-item-id="'.$row["id"].'" data-name="'.$name.'" data-price="'.$price.'">
                                <div class="card category-card h-100 border-0">
                                    <img src="'.$img.'" style="object-fit: cover;" class="card-img-top">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title mb-1 fs-6">'.$name.'</h5>
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <p class="card-text text-muted small m-0">LKR '.$price.'</p>
                                            
                                            <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="event.stopPropagation(); addToCart(\''.$name.'\', '.$price.', \'Cargills\')">Add +</button>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        }
                    } else {
                        echo "<p class='text-muted w-100 text-center mt-4'>Menu items coming soon...</p>";
                    }
                    ?>
                </div>
            </div>

            <div class="row g-4 mt-5 anim-card" data-category="uni" id="finagleSection">
                <div class="col-12 d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="text-white fw-bold display-6 mb-0">Finagle</h2>
                        <p class="text-muted m-0">Frozen & Fresh Bakery Products</p>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-sort border-0 rounded-pill px-3 py-2 fw-semibold fs-7 dropdown-toggle" type="button" id="finagleSortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Sorted by Alphabetical <i class="fas fa-sliders-h ms-2"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg fs-7 border-0" aria-labelledby="finagleSortDropdown">
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('finagleItemsContainer', 'price', 'finagleSortDropdown', 'Lowest Price')"><i class="fas fa-tag me-2"></i>Lowest Price</a></li>
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('finagleItemsContainer', 'name', 'finagleSortDropdown', 'Alphabetical')"><i class="fas fa-sort-alpha-down me-2"></i>Alphabetical</a></li>
                        </ul>
                    </div>
                </div>

                <div class="row g-4" id="finagleItemsContainer">
                    <?php
                    // 1. Connect to the database
                    $db_conn = new mysqli("localhost", "root", "", "unidash");
                    
                    // 2. Request only the items that belong to P&S
                    $sql_select = "SELECT id, item_name, price, image_url FROM menu_items WHERE outlet_id = 'finagle'";
                    $result = $db_conn->query($sql_select);
                    
                    // 3. Loop through the results (Just like in your lecture!)
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            
                            // Store database values in variables
                            $name = htmlspecialchars($row["item_name"]);
                            $price = $row["price"];
                            $img = htmlspecialchars($row["image_url"]);
                            
                            // Echo the HTML, injecting the variables where the hardcoded text used to be
                            echo '
                            <div class="col-6 col-md-4 col-lg-2" data-item-id="'.$row["id"].'" data-name="'.$name.'" data-price="'.$price.'">
                                <div class="card category-card h-100 border-0">
                                    <img src="'.$img.'" style="object-fit: cover;" class="card-img-top">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title mb-1 fs-6">'.$name.'</h5>
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <p class="card-text text-muted small m-0">LKR '.$price.'</p>
                                            
                                            <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="event.stopPropagation(); addToCart(\''.$name.'\', '.$price.', \'Finagle\')">Add +</button>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        }
                    } else {
                        echo "<p class='text-muted w-100 text-center mt-4'>Menu items coming soon...</p>";
                    }
                    ?>
                </div>
            </div>

            <div class="row g-4 mt-5 anim-card" data-category="uni" id="baristaSection">
                <div class="col-12 d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="text-white fw-bold display-6 mb-0">Barista</h2>
                        <p class="text-muted m-0">Cafe</p>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-sort border-0 rounded-pill px-3 py-2 fw-semibold fs-7 dropdown-toggle" type="button" id="baristaSortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Sorted by Alphabetical <i class="fas fa-sliders-h ms-2"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg fs-7 border-0" aria-labelledby="baristaSortDropdown">
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('baristaItemsContainer', 'price', 'baristaSortDropdown', 'Lowest Price')"><i class="fas fa-tag me-2"></i>Lowest Price</a></li>
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('baristaItemsContainer', 'name', 'baristaSortDropdown', 'Alphabetical')"><i class="fas fa-sort-alpha-down me-2"></i>Alphabetical</a></li>
                        </ul>
                    </div>
                </div>

                <div class="row g-4" id="baristaItemsContainer">
                    <?php
                    // Reusing the database connection if it's already open, or open a new one if needed
                    if (!isset($db_conn)) {
                        $db_conn = new mysqli("localhost", "root", "", "unidash");
                    }
                    
                    $sql_finagle = "SELECT id, item_name, price, image_url FROM menu_items WHERE outlet_id = 'barista'";
                    $res_finagle = $db_conn->query($sql_finagle);
                    
                    if ($res_finagle && $res_finagle->num_rows > 0) {
                        while($row = $res_finagle->fetch_assoc()) {
                            $name = htmlspecialchars($row["item_name"]);
                            $price = $row["price"];
                            $img = htmlspecialchars($row["image_url"]);
                            
                            echo '
                            <div class="col-6 col-md-4 col-lg-2" data-item-id="'.$row["id"].'" data-name="'.$name.'" data-price="'.$price.'">
                                <div class="card category-card h-100 border-0">
                                    <img src="'.$img.'" style="object-fit: cover;" class="card-img-top">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title mb-1 fs-6">'.$name.'</h5>
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <p class="card-text text-muted small m-0">LKR '.$price.'</p>
                                            <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="event.stopPropagation(); addToCart(\''.$name.'\', '.$price.', \'Barista\')">Add +</button>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        }
                    } else {
                        echo "<p class='text-muted w-100 text-center mt-4'>Finagle menu coming soon...</p>";
                    }
                    ?>
                </div>
            </div>
            <div class="row g-4 mt-5 anim-card" data-category="uni" id="hostelSection">
                <div class="col-12 d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="text-white fw-bold display-6 mb-0">Hostel Canteen</h2>
                        <p class="text-muted m-0">Budget Friendly Meals & Munchies</p>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-sort border-0 rounded-pill px-3 py-2 fw-semibold fs-7 dropdown-toggle" type="button" id="hostelSortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Sorted by Alphabetical <i class="fas fa-sliders-h ms-2"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg fs-7 border-0" aria-labelledby="hostelSortDropdown">
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('hostelItemsContainer', 'price', 'hostelSortDropdown', 'Lowest Price')"><i class="fas fa-tag me-2"></i>Lowest Price</a></li>
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('hostelItemsContainer', 'name', 'hostelSortDropdown', 'Alphabetical')"><i class="fas fa-sort-alpha-down me-2"></i>Alphabetical</a></li>
                        </ul>
                    </div>
                </div>

                <div class="row g-4" id="hostelItemsContainer">
                    <?php
                    // Reusing the database connection if it's already open
                    if (!isset($db_conn)) {
                        $db_conn = new mysqli("localhost", "root", "", "unidash");
                    }
                    
                    $sql_hostel = "SELECT id, item_name, price, image_url FROM menu_items WHERE outlet_id = 'hostel'";
                    $res_hostel = $db_conn->query($sql_hostel);
                    
                    if ($res_hostel && $res_hostel->num_rows > 0) {
                        while($row = $res_hostel->fetch_assoc()) {
                            $name = htmlspecialchars($row["item_name"]);
                            $price = $row["price"];
                            $img = htmlspecialchars($row["image_url"]);
                            
                            echo '
                            <div class="col-6 col-md-4 col-lg-2" data-item-id="'.$row["id"].'" data-name="'.$name.'" data-price="'.$price.'">
                                <div class="card category-card h-100 border-0">
                                    <img src="'.$img.'" style="object-fit: cover;" class="card-img-top" alt="'.$name.'">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title mb-1 fs-6">'.$name.'</h5>
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <p class="card-text text-muted small m-0">LKR '.$price.'</p>
                                            <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="event.stopPropagation(); addToCart(\''.$name.'\', '.$price.', \'Hostel Canteen\')">Add +</button>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        }
                    } else {
                        echo "<p class='text-muted w-100 text-center mt-4'>Hostel Canteen menu coming soon...</p>";
                    }
                    ?>
                </div>
            </div>

            <div class="row g-4 mt-5 anim-card d-none" data-category="out">
    
               <div class="col-12 d-flex justify-content-between align-items-center pb-3 mb-4">
                    <h2 class="text-white fw-bold">
                        <i class="fas fa-shopping-bag me-4 text-accent-cyan"></i>From UniDash Store
                    </h2>

                    <div class="dropdown">
                        <button class="btn btn-sort border-0 rounded-pill px-3 py-2 fw-semibold fs-7 dropdown-toggle" 
                                type="button" 
                                id="storeSortDropdown" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false">
                            Sorted by Top Rated <i class="fas fa-sliders-h ms-2"></i>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg fs-7 border-0" aria-labelledby="storeSortDropdown">
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('storeContainer', 'rating', 'storeSortDropdown', 'Top Rated')"><i class="fas fa-thumbs-up me-2"></i>Top Rated</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('storeContainer', 'price', 'storeSortDropdown', 'Lowest Price')"><i class="fas fa-tag me-2"></i>Lowest Price</a></li>
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('storeContainer', 'name', 'storeSortDropdown', 'Alphabetical')"><i class="fas fa-sort-alpha-down me-2"></i>Alphabetical</a></li>
                        </ul>
                    </div>

                </div>

                <div class="row g-4 ss" id="storeContainer">
                    <?php
                    // Reusing the database connection if it's already open
                    if (!isset($db_conn)) {
                        $db_conn = new mysqli("localhost", "root", "", "unidash");
                    }
                    
                    // We now fetch the rating column as well
                    $sql_store = "SELECT id, item_name, price, image_url, rating FROM menu_items WHERE outlet_id = 'unidash_store'";
                    $res_store = $db_conn->query($sql_store);
                    
                    if ($res_store && $res_store->num_rows > 0) {
                        while($row = $res_store->fetch_assoc()) {
                            $name = htmlspecialchars($row["item_name"]);
                            $price = $row["price"];
                            $img = htmlspecialchars($row["image_url"]);
                            
                            // Format rating to always show one decimal place (e.g. 5.0 instead of just 5)
                            $rating = number_format($row["rating"], 1); 
                            
                            echo '
                            <div class="col-6 col-md-4 col-lg-2" data-item-id="'.$row["id"].'" data-name="'.$name.'" data-price="'.$price.'">
                                <div class="card category-card h-100 border-0">
                                    <img src="'.$img.'" style="object-fit: cover;" class="card-img-top" alt="'.$name.'">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title mb-1 fs-6">'.$name.'</h5>
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <p class="card-text text-muted small m-0">LKR '.number_format($price).' • ⭐ '.$rating.'</p>
                                            <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="event.stopPropagation(); addToCart(\''.$name.'\', '.$price.', \'UniDash Store\')">Add +</button>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        }
                    } else {
                        echo "<p class='text-muted w-100 text-center mt-4'>Store items coming soon...</p>";
                    }
                    ?>
                </div>

                <div class="row g-4 mt-5 anim-card d-none" data-category="out" id="kfcSection">
                <div class="col-12 d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="text-white fw-bold display-6 mb-0">KFC</h2>
                        <p class="text-muted m-0">Finger Lickin' Good</p>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-sort border-0 rounded-pill px-3 py-2 fw-semibold fs-7 dropdown-toggle" 
                                type="button" id="kfcSortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Sorted by Alphabetical <i class="fas fa-sliders-h ms-2"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg fs-7 border-0" aria-labelledby="kfcSortDropdown">
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('kfcItemsContainer', 'price', 'kfcSortDropdown', 'Lowest Price')"><i class="fas fa-tag me-2"></i>Lowest Price</a></li>
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('kfcItemsContainer', 'name', 'kfcSortDropdown', 'Alphabetical')"><i class="fas fa-sort-alpha-down me-2"></i>Alphabetical</a></li>
                        </ul>
                    </div>
                </div>

                <div class="row g-4" id="kfcItemsContainer">
                    <?php
                    // Reusing the database connection if it's already open
                    if (!isset($db_conn)) {
                        $db_conn = new mysqli("localhost", "root", "", "unidash");
                    }
                    
                    $sql_kfc = "SELECT id, item_name, price, image_url FROM menu_items WHERE outlet_id = 'kfc'";
                    $res_kfc = $db_conn->query($sql_kfc);
                    
                    if ($res_kfc && $res_kfc->num_rows > 0) {
                        while($row = $res_kfc->fetch_assoc()) {
                            $name = htmlspecialchars($row["item_name"]);
                            $price = $row["price"];
                            $img = htmlspecialchars($row["image_url"]);
                            
                            echo '
                            <div class="col-6 col-md-4 col-lg-2" data-item-id="'.$row["id"].'" data-name="'.$name.'" data-price="'.$price.'">
                                <div class="card category-card h-100 border-0">
                                    <img src="'.$img.'" style="object-fit: cover;" class="card-img-top" alt="'.$name.'">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title mb-1 fs-6">'.$name.'</h5>
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <p class="card-text text-muted small m-0">LKR '.$price.'</p>
                                            <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="event.stopPropagation(); addToCart(\''.$name.'\', '.$price.', \'KFC\')">Add +</button>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        }
                    } else {
                        echo "<p class='text-muted w-100 text-center mt-4'>KFC menu coming soon...</p>";
                    }
                    ?>
                </div>
            </div>

            <div class="row g-4 mt-5 anim-card d-none" data-category="out" id="pizzaHutSection">
                <div class="col-12 d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="text-white fw-bold display-6 mb-0">Pizza Hut</h2>
                        <p class="text-muted m-0">World's Favorite Pan Pizza</p>
                    </div>
                        
                    <div class="dropdown">
                        <button class="btn btn-sort border-0 rounded-pill px-3 py-2 fw-semibold fs-7 dropdown-toggle" 
                                type="button" id="phSortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Sorted by Alphabetical <i class="fas fa-sliders-h ms-2"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg fs-7 border-0" aria-labelledby="phSortDropdown">
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('phItemsContainer', 'price', 'phSortDropdown', 'Lowest Price')"><i class="fas fa-tag me-2"></i>Lowest Price</a></li>
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('phItemsContainer', 'name', 'phSortDropdown', 'Alphabetical')"><i class="fas fa-sort-alpha-down me-2"></i>Alphabetical</a></li>
                        </ul>
                 </div>
            </div>

                <div class="row g-4" id="phItemsContainer">
                    <?php
                    // Reusing the database connection if it's already open
                    if (!isset($db_conn)) {
                        $db_conn = new mysqli("localhost", "root", "", "unidash");
                    }
                    
                    $sql_pizzahut = "SELECT id, item_name, price, image_url FROM menu_items WHERE outlet_id = 'pizzahut'";
                    $res_pizzahut = $db_conn->query($sql_pizzahut);
                    
                    if ($res_pizzahut && $res_pizzahut->num_rows > 0) {
                        while($row = $res_pizzahut->fetch_assoc()) {
                            $name = htmlspecialchars($row["item_name"]);
                            $price = $row["price"];
                            $img = htmlspecialchars($row["image_url"]);
                            
                            echo '
                            <div class="col-6 col-md-4 col-lg-2" data-item-id="'.$row["id"].'" data-name="'.$name.'" data-price="'.$price.'">
                                <div class="card category-card h-100 border-0">
                                    <img src="'.$img.'" style="object-fit: cover;" class="card-img-top" alt="'.$name.'">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title mb-1 fs-6">'.$name.'</h5>
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <p class="card-text text-muted small m-0">LKR '.$price.'</p>
                                            <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="event.stopPropagation(); addToCart(\''.$name.'\', '.$price.', \'KFC\')">Add +</button>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        }
                    } else {
                        echo "<p class='text-muted w-100 text-center mt-4'>Pizza Hut menu coming soon...</p>";
                    }
                    ?>
                </div>
            </div>

            <div class="row g-4 mt-5 anim-card d-none" data-category="out" id="dominosSection">
                <div class="col-12 d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="text-white fw-bold display-6 mb-0">Domino's Pizza</h2>
                        <p class="text-muted m-0">The Pizza Delivery Experts</p>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sort border-0 rounded-pill px-3 py-2 fw-semibold fs-7 dropdown-toggle" 
                                type="button" id="dominosSortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Sorted by Alphabetical <i class="fas fa-sliders-h ms-2"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg fs-7 border-0" aria-labelledby="dominosSortDropdown">
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('dominosItemsContainer', 'price', 'dominosSortDropdown', 'Lowest Price')"><i class="fas fa-tag me-2"></i>Lowest Price</a></li>
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('dominosItemsContainer', 'name', 'dominosSortDropdown', 'Alphabetical')"><i class="fas fa-sort-alpha-down me-2"></i>Alphabetical</a></li>
                        </ul>
                    </div>
                </div>

                <div class="row g-4" id="dominosItemsContainer">
                    <?php
                    // Reusing the database connection if it's already open
                    if (!isset($db_conn)) {
                        $db_conn = new mysqli("localhost", "root", "", "unidash");
                    }
                    
                    $sql_dominos = "SELECT id, item_name, price, image_url FROM menu_items WHERE outlet_id = 'dominos'";
                    $res_dominos = $db_conn->query($sql_dominos);
                    
                    if ($res_dominos && $res_dominos->num_rows > 0) {
                        while($row = $res_dominos->fetch_assoc()) {
                            $name = htmlspecialchars($row["item_name"]);
                            $price = $row["price"];
                            $img = htmlspecialchars($row["image_url"]);
                            
                            echo '
                            <div class="col-6 col-md-4 col-lg-2" data-item-id="'.$row["id"].'" data-name="'.$name.'" data-price="'.$price.'">
                                <div class="card category-card h-100 border-0">
                                    <img src="'.$img.'" style="object-fit: cover;" class="card-img-top" alt="'.$name.'">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title mb-1 fs-6">'.$name.'</h5>
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <p class="card-text text-muted small m-0">LKR '.$price.'</p>
                                            <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="event.stopPropagation(); addToCart(\''.$name.'\', '.$price.', \'Dominos\')">Add +</button>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        }
                    } else {
                        echo "<p class='text-muted w-100 text-center mt-4'>Domino's menu coming soon...</p>";
                    }
                    ?>
                </div>
            </div>
               <div class="row g-4 mt-5 anim-card d-none" data-category="out" id="fabSection">
                <div class="col-12 d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="text-white fw-bold display-6 mb-0">The Fab</h2>
                        <p class="text-muted m-0">Exclusive Cakes, Lamprais & Short Eats</p>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sort border-0 rounded-pill px-3 py-2 fw-semibold fs-7 dropdown-toggle" 
                                type="button" id="fabSortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Sorted by Alphabetical <i class="fas fa-sliders-h ms-2"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg fs-7 border-0" aria-labelledby="fabSortDropdown">
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('fabItemsContainer', 'price', 'fabSortDropdown', 'Lowest Price')"><i class="fas fa-tag me-2"></i>Lowest Price</a></li>
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="sortSpecificContainer('fabItemsContainer', 'name', 'fabSortDropdown', 'Alphabetical')"><i class="fas fa-sort-alpha-down me-2"></i>Alphabetical</a></li>
                        </ul>
                    </div>
                </div>

                <div class="row g-4" id="fabItemsContainer">
                    <?php
                    // Reusing the database connection if it's already open
                    if (!isset($db_conn)) {
                        $db_conn = new mysqli("localhost", "root", "", "unidash");
                    }
                    
                    $sql_fab = "SELECT id, item_name, price, image_url FROM menu_items WHERE outlet_id = 'fab'";
                    $res_fab = $db_conn->query($sql_fab);
                    
                    if ($res_fab && $res_fab->num_rows > 0) {
                        while($row = $res_fab->fetch_assoc()) {
                            $name = htmlspecialchars($row["item_name"]);
                            $price = $row["price"];
                            $img = htmlspecialchars($row["image_url"]);
                            
                            echo '
                            <div class="col-6 col-md-4 col-lg-2" data-item-id="'.$row["id"].'" data-name="'.$name.'" data-price="'.$price.'">
                                <div class="card category-card h-100 border-0">
                                    <img src="'.$img.'" style="object-fit: cover;" class="card-img-top" alt="'.$name.'">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title mb-1 fs-6">'.$name.'</h5>
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <p class="card-text text-muted small m-0">LKR '.$price.'</p>
                                            <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="event.stopPropagation(); addToCart(\''.$name.'\', '.$price.', \'The Fab\')">Add +</button>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        }
                    } else {
                        echo "<p class='text-muted w-100 text-center mt-4'>The Fab menu coming soon...</p>";
                    }
                    ?>
                </div>
            </div>
            </div>
        </div>
    </div>  
</div>

<div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-white fs-2">Welcome Back!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                
                <form method="POST" action="backend.php">
                    
                    <input type="hidden" name="form_type" value="login">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small">Email Address</label>
                        <input type="text" name="login_email" class="form-control custom-input" placeholder="student@nsbm.ac.lk">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label text-muted small">Password</label>
                        <input type="password" name="login_pass" class="form-control custom-input" placeholder="••••••••">
                    </div>
                    
                    <button type="submit" class="btn btn-accent w-100 fw-bold py-2">Log In</button>
                </form>
                
                <div class="text-center mt-3">
                    <p class="small text-muted">Don't have an account? <a href="#" class="text-accent-cyan text-decoration-none" data-bs-toggle="modal" data-bs-target="#signupModal">Sign up</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="signupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <div class="modal-header border-0 justify-content-center position-relative">
                <h5 class="modal-title fw-bold text-white fs-2 pb-3">Join UniDash</h5>
                <button type="button" class="btn-close btn-close-white position-absolute end-0 me-3 mb-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <form method="post" action="backend.php">
                    <input type="hidden" name="form_type" value="signup">
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small">First Name</label>
                            <input type="text" name="fname" class="form-control custom-input" placeholder="John" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small">Last Name</label>
                            <input type="text" name="lname" class="form-control custom-input" placeholder="Doe" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">University Email</label>
                        <input type="email" name="uniemail" class="form-control custom-input" placeholder="student@nsbm.ac.lk" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small">Create Password</label>
                        <input type="password" name="pass" class="form-control custom-input" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-accent w-100 fw-bold py-2">Create Account</button>
                </form>
                <div class="text-center mt-3">
                    <p class="small text-muted">Already have an account? <a href="#" class="text-accent-cyan text-decoration-none" data-bs-toggle="modal" data-bs-target="#loginModal">Log in</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<button type="button" 
        id="btn-floating-cart" 
        data-bs-toggle="modal" 
        data-bs-target="#cartModal"
        style="position: fixed; 
               bottom: 90px; /* Above the Back to Top button */
               right: 30px; 
               z-index: 9999; 
               width: 60px; 
               height: 60px; 
               border-radius: 50%; 
               background-color: #ffffff; 
               color: #000; 
               border: none; 
               box-shadow: 0 0 20px rgba(0, 255, 255, 0.6);
               display: flex;
               align-items: center; 
               justify-content: center;
               cursor: pointer;
               transition: transform 0.2s;">
    <i class="fas fa-shopping-cart fs-3"></i>
    <span id="cart-badge" 
          style="position: absolute; 
                 top: -5px; 
                 right: -5px; 
                 background-color: red; 
                 color: white; 
                 border-radius: 50%; 
                 width: 25px; 
                 height: 25px; 
                 font-size: 12px; 
                 font-weight: bold; 
                 display: flex; 
                 align-items: center; 
                 justify-content: center;
                 display: none;">0</span>
</button>


<div class="modal fade" id="cartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content custom-modal">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-white"><i class="fas fa-shopping-cart me-2"></i>Your Cart</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="cart-items-container">
                <div class="text-center text-muted py-5">
                    <i class="fas fa-shopping-basket mb-3 fs-1 opacity-50"></i>
                    <p>Your cart is empty.</p>
                </div>
            </div>
            <div class="modal-footer border-secondary justify-content-between">
                <div>
                    <small class="text-muted">Total:</small>
                    <h4 class="text-white fw-bold m-0" id="cart-total">LKR 0</h4>
                </div>
                <button type="button" class="btn btn-accent fw-bold px-4" onclick="proceedToPayment()">Proceed</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-white"><i class="fas fa-wallet me-2"></i>Payment Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="post" action="backend.php" onsubmit="return finalizeCheckout()">
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label text-muted small mb-2">How would you like to pay?</label>
                        
                        <div class="form-check bg-dark border border-secondary rounded p-3 mb-2" style="cursor: pointer;" onclick="document.getElementById('pay_cod').click()">
                            <input class="form-check-input ms-1" type="radio" name="payment_method" id="pay_cod" value="Cash on Delivery" checked onchange="toggleCardFields()">
                            <label class="form-check-label ms-2 fw-bold text-white w-100" for="pay_cod" style="cursor: pointer;">
                                <i class="fas fa-money-bill-wave text-success me-2"></i>Cash on Delivery (COD)
                            </label>
                        </div>
                        
                        <div class="form-check bg-dark border border-secondary rounded p-3" style="cursor: pointer;" onclick="document.getElementById('pay_card').click()">
                            <input class="form-check-input ms-1" type="radio" name="payment_method" id="pay_card" value="Online Payment" onchange="toggleCardFields()">
                            <label class="form-check-label ms-2 fw-bold text-white w-100" for="pay_card" style="cursor: pointer;">
                                <i class="fas fa-credit-card text-info me-2"></i>Pay by Card (Online)
                            </label>
                        </div>
                    </div>

                    <div id="card-details-form" class="d-none bg-dark border border-secondary rounded p-3 mb-3">
                        <h6 class="text-white mb-3 fs-7">Enter Card Details</h6>
                        <input type="text" id="cc_num" class="form-control custom-input mb-2 text-white" placeholder="Card Number (0000 0000 0000 0000)">
                        <div class="d-flex gap-2">
                            <input type="text" id="cc_exp" class="form-control custom-input text-white" placeholder="MM/YY">
                            <input type="password" id="cc_cvv" class="form-control custom-input text-white" placeholder="CVV">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-2 border-top border-secondary">
                        <span class="text-muted">Total to Pay:</span>
                        <h4 class="text-accent fw-bold m-0" id="payment-total-display">LKR 0</h4>
                    </div>
                </div>
                
                <div class="modal-footer border-secondary">
                    <input type="hidden" name="form_type" value="checkout">
                    <input type="hidden" name="cart_data" id="cart_hidden_input">
                    <input type="hidden" name="fname" id="checkout_fname">
                    <input type="hidden" name="membertype" id="checkout_membertype">
                    <input type="hidden" name="deliver_to" id="checkout_deliver_to">
                    
                    <button type="submit" class="btn btn-accent fw-bold w-100 py-2">Confirm Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addDispatcherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <div class="modal-header border-0 justify-content-center position-relative">
                <h5 class="modal-title fw-bold text-white fs-3 pb-3"><i class="fas fa-motorcycle text-warning me-2"></i>Hire Dispatcher</h5>
                <button type="button" class="btn-close btn-close-white position-absolute end-0 me-3 mb-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <form method="post" action="backend.php">
                    <input type="hidden" name="form_type" value="add_dispatcher">
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small">First Name</label>
                            <input type="text" name="fname" class="form-control custom-input" placeholder="e.g. Nimal" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small">Last Name</label>
                            <input type="text" name="lname" class="form-control custom-input" placeholder="e.g. Perera" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Staff Email</label>
                        <input type="email" name="uniemail" class="form-control custom-input" placeholder="dispatcher@unidash.lk" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small">Create Password</label>
                        <input type="password" name="pass" class="form-control custom-input" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-warning text-dark w-100 fw-bold py-2">Create Dispatcher Account</button>
                </form>
            </div>
        </div>
    </div>
</div>

<button type="button" 
        id="btn-add-item" 
        data-bs-toggle="modal" 
        data-bs-target="#addItemModal"
        class="d-none"
        style="position: fixed; 
               bottom: 90px; 
               left: 30px; 
               z-index: 9999; 
               width: 60px; 
               height: 60px; 
               border-radius: 50%; 
               background-color: #ffc107; 
               color: #000; 
               border: none; 
               box-shadow: 0 0 20px rgba(255, 193, 7, 0.6);
               align-items: center; 
               justify-content: center;
               cursor: pointer;
               transition: transform 0.2s;">
    <i class="fas fa-plus fs-3"></i>
</button>

<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-white"><i class="fas fa-plus-circle me-2 text-warning"></i>Add New Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="add_item.php">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Select Outlet</label>
                        <select name="outlet_id" class="form-control custom-input bg-dark text-white border-secondary" required>
                            <option value="pns">P&S</option>
                            <option value="cargills">Cargills Express</option>
                            <option value="finagle">Finagle</option>
                            <option value="barista">Barista</option>
                            <option value="hostel">Hostel Canteen</option>
                            <option value="kfc">KFC</option>
                            <option value="pizzahut">Pizza Hut</option>
                            <option value="dominos">Domino's Pizza</option>
                            <option value="fab">The Fab</option>
                            <option value="serenity">The Edge - Serenity</option>
                            <option value="remarko">The Edge - Remarko</option>
                            <option value="juice">The Edge - Juice Bar</option>
                            <option value="unidash_store">UniDash Store (Electronics/Merch)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Item Name</label>
                        <input type="text" name="item_name" class="form-control custom-input text-white" placeholder="e.g. Fish Bun" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Price (LKR)</label>
                        <input type="number" name="price" class="form-control custom-input text-white" placeholder="e.g. 150" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small">Image Path (from Images folder)</label>
                        <input type="text" name="image_url" class="form-control custom-input text-white" placeholder="e.g. Images/Logos/default.jpg" value="Images/Logos/1.svg" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100 fw-bold py-2 text-dark">Add Item to Database</button>
                </form>
            </div>
        </div>
    </div>
</div>

<button type="button" 
        id="btn-back-to-top" 
        onclick="scrollToTop()"
        style="display: none; /* Hidden by default */
               position: fixed; 
               bottom: 30px; 
               right: 30px; 
               z-index: 9999; 
               width: 50px; 
               height: 50px; 
               border-radius: 50%; 
               background-color: #00FFFF; /* Cyan */
               color: #000000; /* Black Icon */
               border: none; 
               box-shadow: 0 0 15px rgba(0, 255, 255, 0.6);
               align-items: center; 
               justify-content: center;
               cursor: pointer;">
    <i class="fas fa-arrow-up"></i>
</button>

<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <div class="modal-header border-0 pb-0 justify-content-between position-relative">
                <h5 class="modal-title fw-bold text-white fs-3">My Profile</h5>
                <button type="button" class="btn-close btn-close-white position-absolute end-0 me-3 mt-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center mt-2">
                
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold mb-3 shadow" 
                     style="width: 80px; height: 80px; font-size: 3rem; background-color: #00FFFF !important; color: #000000 !important; line-height: 1;" 
                     id="modal-user-initial">
                    U
                </div>
                
                <h4 class="text-white fw-bold m-0" id="profile-name">User Name</h4>
                <span class="badge bg-secondary text-uppercase mt-2 px-3 py-1" id="profile-type">Type</span>
                
                <div class="bg-dark p-3 rounded border border-secondary mt-4 mb-4 text-start">
                    <small class="text-muted d-block mb-1"><i class="fas fa-envelope me-2"></i>University Email</small>
                    <div class="text-white fs-6 fw-semibold" id="profile-email">email@domain.com</div>
                </div>
                
                <button class="btn btn-outline-danger w-100 fw-bold py-2" onclick="logout()">Log Out</button>
            </div>
        </div>
    </div>
</div>
</div>
    </div>
</div>

<script>
    // --- 5. AUTHENTICATION & ADMIN PRIVILEGES ---
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        let fname, lname, email, type;
        let isLoggedIn = false;

        // SCENARIO 1: User just logged in (Data is in the URL)
        if (urlParams.has('logged_in') || urlParams.has('fname')) {
            fname = urlParams.get('fname') || 'User';
            lname = urlParams.get('lname') || '';
            email = urlParams.get('email') || '';
            type = urlParams.get('type') || 'customer'; 
            
            // Save to browser memory so it survives page reloads!
            localStorage.setItem('uni_fname', fname);
            localStorage.setItem('uni_lname', lname);
            localStorage.setItem('uni_email', email);
            localStorage.setItem('uni_type', type);
            
            isLoggedIn = true;
            
            // Clean URL exactly once
            window.history.replaceState({}, document.title, window.location.pathname);
        } 
        // SCENARIO 2: User is returning from the Dashboard (Data is in Memory)
        else if (localStorage.getItem('uni_fname')) {
            fname = localStorage.getItem('uni_fname');
            lname = localStorage.getItem('uni_lname');
            email = localStorage.getItem('uni_email');
            type = localStorage.getItem('uni_type');
            
            isLoggedIn = true;
        }

        // If we confirmed they are logged in (either via URL or Memory), set up the UI
        if (isLoggedIn) {
            // Show Profile, Hide Guest Buttons
            const guestBtns = document.getElementById('guest-buttons');
            const userProfile = document.getElementById('user-profile');
            const userProfileIcon = document.getElementById('user-profile-icon');
            
            if(guestBtns) guestBtns.classList.add('d-none');
            if(userProfile) userProfile.classList.remove('d-none');
            if(userProfileIcon) userProfileIcon.classList.remove('d-none');

            // Set Profile Letters & Text
            const initial = fname.charAt(0).toUpperCase();
            
            const navUserInit = document.getElementById('nav-user-initial');
            if(navUserInit) navUserInit.textContent = initial;
            
            const modalUserInit = document.getElementById('modal-user-initial');
            if(modalUserInit) modalUserInit.textContent = initial;
            
            const userInit = document.getElementById('user-initial');
            if(userInit) userInit.textContent = initial;

            document.getElementById('profile-name').textContent = fname.charAt(0).toUpperCase() + fname.slice(1) + ' ' + lname.charAt(0).toUpperCase() + lname.slice(1);
            document.getElementById('profile-email').textContent = email;
            
            const badge = document.getElementById('profile-type');
            if(badge) badge.textContent = type.toUpperCase();

            // ==========================================
            // NEW: "MY ORDERS" BUTTON FOR ALL LOGGED-IN USERS
            // ==========================================
            const modalBody = document.querySelector('#profileModal .modal-body');
            
            if (modalBody && !document.getElementById('my-orders-btn')) {
                const myOrdersBtn = document.createElement('a');
                // Pass the user's first name in the URL so PHP knows whose orders to fetch!
                myOrdersBtn.href = 'my_orders.php?user=' + encodeURIComponent(fname); 
                myOrdersBtn.className = 'btn btn-primary w-100 fw-bold py-2 mb-2 shadow-sm text-white';
                myOrdersBtn.innerHTML = '<i class="fas fa-shopping-bag me-2"></i>My Past Orders';
                myOrdersBtn.id = 'my-orders-btn';
                
                const logoutBtn = modalBody.querySelector('.btn-outline-danger');
                if(logoutBtn) {
                    modalBody.insertBefore(myOrdersBtn, logoutBtn);
                }
            }

            // ==========================================
            // ADMIN & DISPATCHER BUTTON INJECTION 
            // ==========================================
            const userType = type.toLowerCase();
            
            if (userType === 'admin' || userType === 'dispatcher') {
                
                // 1. Change badge color (Red for Admin, Yellow for Dispatcher)
                if(badge) {
                    badge.classList.remove('bg-secondary');
                    if (userType === 'admin') {
                        badge.classList.add('bg-danger');
                    } else {
                        badge.classList.add('bg-warning', 'text-dark');
                    }
                }

                // 2. Unhide the Orders button on the top Navbar!
                const navDashboard = document.getElementById('admin-nav-dashboard');
                if (navDashboard) navDashboard.classList.remove('d-none');
                
                // 3. Find the Modal Body
                const modalBody = document.querySelector('#profileModal .modal-body');
                
                // 4. Inject Dashboard Button inside Profile Modal (For Both)
                if (modalBody && !document.getElementById('admin-dashboard-btn')) {
                    const dashboardBtn = document.createElement('a');
                    dashboardBtn.href = 'admin_dashboard.php'; 
                    dashboardBtn.className = 'btn btn-info w-100 fw-bold py-2 mb-2 shadow-sm text-dark';
                    dashboardBtn.innerHTML = '<i class="fas fa-motorcycle me-2"></i>Dispatch Dashboard';
                    dashboardBtn.id = 'admin-dashboard-btn';

                    const logoutBtn = modalBody.querySelector('.btn-outline-danger');
                    if(logoutBtn) modalBody.insertBefore(dashboardBtn, logoutBtn);
                }

                // 5. Inject Live Edit & Create Dispatcher Buttons (ONLY FOR ADMINS!)
                if (userType === 'admin' && modalBody && !document.getElementById('admin-edit-btn')) {
                    
                    // Create Dispatcher Button
                    const addDispatcherBtn = document.createElement('button');
                    addDispatcherBtn.className = 'btn btn-outline-light w-100 fw-bold py-2 mb-2 shadow-sm';
                    addDispatcherBtn.innerHTML = '<i class="fas fa-user-plus me-2 text-warning"></i>Create Dispatcher Account';
                    addDispatcherBtn.id = 'add-dispatcher-btn';
                    addDispatcherBtn.setAttribute('data-bs-toggle', 'modal');
                    addDispatcherBtn.setAttribute('data-bs-target', '#addDispatcherModal');
                    
                    // Close the profile modal when opening the dispatcher modal
                    addDispatcherBtn.onclick = function() {
                        const profileModalEl = document.getElementById('profileModal');
                        const profileModalInstance = bootstrap.Modal.getInstance(profileModalEl);
                        profileModalInstance.hide();
                    };

                    // Live Edit Button
                    const adminBtn = document.createElement('button');
                    adminBtn.className = 'btn btn-warning w-100 fw-bold py-2 mb-4 shadow-sm text-dark';
                    adminBtn.innerHTML = '<i class="fas fa-edit me-2"></i>Enable Live Edit Mode';
                    adminBtn.id = 'admin-edit-btn';
                    
                    adminBtn.onclick = function() { toggleAdminEditMode(); };
                    
                    // Insert them right above the logout button
                    const logoutBtn = modalBody.querySelector('.btn-outline-danger');
                    if(logoutBtn) {
                        modalBody.insertBefore(addDispatcherBtn, logoutBtn);
                        modalBody.insertBefore(adminBtn, logoutBtn);
                    }
                }
            }
        }
    });

    // --- 7. LOGOUT LOGIC (Updated to clear memory) ---
    function logout() {
        // Erase the user from the browser memory
        localStorage.clear(); 
        window.location.href = window.location.pathname; 
    }

    // --- 6. LIVE EDIT & DELETE LOGIC ---
    let isEditMode = false;
    function toggleAdminEditMode() {
        isEditMode = !isEditMode;
        const btn = document.getElementById('admin-edit-btn');
        const addBtn = document.getElementById('btn-add-item'); 

        if (isEditMode) {
            btn.innerHTML = '<i class="fas fa-save me-2"></i>Save All Changes';
            btn.classList.replace('btn-warning', 'btn-success');
            
            if (addBtn) addBtn.classList.remove('d-none'); // Show plus button
            if (addBtn) addBtn.style.display = 'flex';
            
            // 1. Make text editable
            document.querySelectorAll('.card-title, .card-text').forEach(el => {
                el.contentEditable = "true";
                el.style.border = "1px dashed #00FFFF";
            });

            // 2. Inject and Show Delete Buttons on every item
            document.querySelectorAll('[data-item-id]').forEach(cardContainer => {
                const card = cardContainer.querySelector('.card');
                
                // If it doesn't have a delete button yet, create one!
                if (card && !cardContainer.querySelector('.delete-item-btn')) {
                    card.classList.add('position-relative'); // Required for absolute positioning
                    
                    const delBtn = document.createElement('button');
                    delBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0 m-2 delete-item-btn shadow';
                    delBtn.style.zIndex = '10';
                    delBtn.innerHTML = '<i class="fas fa-trash"></i>';
                    
                    // What happens when Admin clicks Delete
                    delBtn.onclick = function(e) {
                        e.stopPropagation(); // Stop the card from doing anything else
                        if(confirm("Are you sure you want to permanently delete this item?")) {
                            const itemId = cardContainer.getAttribute('data-item-id');
                            
                            // Send delete request to database
                            fetch('delete_item.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `id=${itemId}`
                            }).then(() => {
                                // Visually remove the card from the screen immediately
                                cardContainer.remove(); 
                            });
                        }
                    };
                    card.appendChild(delBtn);
                }
                
                // Show the delete buttons
                const existingDelBtn = cardContainer.querySelector('.delete-item-btn');
                if (existingDelBtn) existingDelBtn.style.display = 'block';
            });
            
            // Close the modal
            const modalElement = document.getElementById('profileModal');
            const modalInstance = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
            modalInstance.hide();
            
        } else {
            // --- SAVING LOGIC ---
            document.querySelectorAll('[data-item-id]').forEach(cardContainer => {
                const itemId = cardContainer.getAttribute('data-item-id');
                const newName = cardContainer.querySelector('.card-title').innerText;
                
                // FIX: Grab the text, but split it at the '•' and only keep the first half!
                const fullPriceText = cardContainer.querySelector('.card-body p').innerText;
                const justPriceText = fullPriceText.split('•')[0]; // This isolates "LKR 3,500 "
                
                // Now safely strip the letters from the isolated string
                const newPrice = justPriceText.replace(/\D/g, ""); 

                fetch('update_item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${itemId}&name=${encodeURIComponent(newName)}&price=${newPrice}`
                });
            });

            // Turn off Edit Mode UI
            btn.innerHTML = '<i class="fas fa-edit me-2"></i>Enable Live Edit Mode';
            btn.classList.replace('btn-success', 'btn-warning');
            
            if (addBtn) addBtn.classList.add('d-none');
            if (addBtn) addBtn.style.display = 'none';
            
            // Lock text editing
            document.querySelectorAll('.card-title, .card-text').forEach(el => {
                el.contentEditable = "false";
                el.style.border = "none";
            });

            // Hide all delete buttons
            document.querySelectorAll('.delete-item-btn').forEach(delBtn => {
                delBtn.style.display = 'none';
            });

            alert("Database Updated Successfully!");
        }
    }

    // --- 8. FORCEFUL BACK TO TOP LOGIC ---
    const myButton = document.getElementById("btn-back-to-top");

    // Listen to the window scroll event
    window.onscroll = function() {
        if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
            if (myButton) myButton.style.display = "flex"; 
        } else {
            if (myButton) myButton.style.display = "none";
        }
    };

    // Smooth Scroll to Top
    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>