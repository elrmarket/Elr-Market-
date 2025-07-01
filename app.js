// frontend/js/app.js

document.addEventListener('DOMContentLoaded', () => {
    // Initialisation du panier depuis le localStorage
    let cart = JSON.parse(localStorage.getItem('elrMarketCart')) || [];

    // Fonctions utilitaires
    const saveCart = () => {
        localStorage.setItem('elrMarketCart', JSON.stringify(cart));
        updateCartDisplay();
    };
// Fonction pour insérer le footer automatiquement
function insertFooter() {
    const footerHTML = `
        <footer>
            <p>&copy; 2025 Elr Market. Tous droits réservés.</p>
        </footer>
    `;
    
    document.body.insertAdjacentHTML('beforeend', footerHTML);
}

// Charger le footer sur chaque page
document.addEventListener('DOMContentLoaded', insertFooter);

    const getProductById = async (id) => {
        // Dans une application réelle, vous feriez un appel API spécifique
        // Pour cet exemple simple, nous chargeons tous les produits et trouvons celui-ci
        try {
            const response = await fetch('http://localhost:8000/backend/api/produits.php'); // Assurez-vous que votre serveur PHP tourne sur le port 8000
            const data = await response.json();
            if (data.success) {
                return data.data.find(product => product.id == id);
            } else {
                console.error('Erreur lors du chargement des produits:', data.message);
                return null;
            }
        } catch (error) {
            console.error('Erreur réseau lors du chargement des produits:', error);
            return null;
        }
    };

    // --- Fonctions pour la page d'accueil (index.html) ---
    const loadProducts = async () => {
        const productListDiv = document.getElementById('product-list');
        if (!productListDiv) return; // Ne pas exécuter si nous ne sommes pas sur la bonne page

        productListDiv.innerHTML = '<p>Chargement des produits...</p>';

        try {
            const response = await fetch('http://localhost:8000/backend/api/produits.php'); // Adaptez l'URL si nécessaire
            const data = await response.json();

            if (data.success) {
                productListDiv.innerHTML = ''; // Nettoie le message de chargement
                data.data.forEach(product => {
                    const productCard = `
                        <div class="product-card">
                            <img src="${product.image || 'https://placehold.co/400x300/e0e0e0/ffffff?text=Image+Produit'}" alt="${product.nom}">
                            <div class="product-card-content">
                                <h3>${product.nom}</h3>
                                <p>${product.description.substring(0, 100)}...</p>
                                <div class="price">${product.prix} €</div>
                                <a href="product.html?id=${product.id}" class="view-details-btn">Voir détails</a>
                                <button class="add-to-cart-btn" data-id="${product.id}" data-name="${product.nom}" data-price="${product.prix}" data-image="${product.image}">Ajouter au panier</button>
                            </div>
                        </div>
                    `;
                    productListDiv.insertAdjacentHTML('beforeend', productCard);
                });

                // Attache les écouteurs d'événements pour les boutons "Ajouter au panier"
                document.querySelectorAll('.add-to-cart-btn').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const { id, name, price, image } = e.target.dataset;
                        addToCart({ id: parseInt(id), nom: name, prix: parseFloat(price), image: image, quantite: 1 });
                    });
                });

            } else {
                productListDiv.innerHTML = `<p>Erreur: ${data.message}</p>`;
            }
        } catch (error) {
            productListDiv.innerHTML = `<p>Erreur réseau: Impossible de charger les produits. Veuillez vérifier que le serveur PHP est en cours d'exécution.</p>`;
            console.error('Erreur:', error);
        }
    };

    // --- Fonctions pour la page de détail produit (product.html) ---
    const loadProductDetail = async () => {
        const urlParams = new URLSearchParams(window.location.search);
        const productId = urlParams.get('id');

        const productDetailContainer = document.getElementById('product-detail-container');
        if (!productDetailContainer) return;

        if (!productId) {
            productDetailContainer.innerHTML = '<p>Produit non trouvé.</p>';
            return;
        }

        productDetailContainer.innerHTML = '<p>Chargement du produit...</p>';
        const product = await getProductById(productId);

        if (product) {
            productDetailContainer.innerHTML = `
                <div class="product-detail">
                    <div class="product-detail-image">
                        <img src="${product.image || 'https://placehold.co/600x450/e0e0e0/ffffff?text=Image+Produit'}" alt="${product.nom}">
                    </div>
                    <div class="product-detail-info">
                        <h2>${product.nom}</h2>
                        <div class="price">${product.prix} €</div>
                        <p>${product.description}</p>
                        <label for="quantity">Quantité :</label>
                        <input type="number" id="quantity" value="1" min="1" max="10">
                        <button class="add-to-cart-btn" data-id="${product.id}" data-name="${product.nom}" data-price="${product.prix}" data-image="${product.image}">Ajouter au panier</button>
                    </div>
                </div>
            `;
            document.querySelector('.product-detail-info .add-to-cart-btn').addEventListener('click', (e) => {
                const quantity = parseInt(document.getElementById('quantity').value);
                const { id, name, price, image } = e.target.dataset;
                addToCart({ id: parseInt(id), nom: name, prix: parseFloat(price), image: image, quantite: quantity });
            });
        } else {
            productDetailContainer.innerHTML = '<p>Erreur: Produit introuvable ou problème de chargement.</p>';
        }
    };

    // --- Fonctions Panier (cart.html) ---
    const addToCart = (productToAdd) => {
        const existingProductIndex = cart.findIndex(item => item.id === productToAdd.id);

        if (existingProductIndex > -1) {
            cart[existingProductIndex].quantite += productToAdd.quantite;
        } else {
            cart.push(productToAdd);
        }
        alert(`${productToAdd.nom} a été ajouté au panier !`);
        saveCart();
    };

    const removeFromCart = (productId) => {
        cart = cart.filter(item => item.id !== productId);
        saveCart();
    };

    const updateQuantity = (productId, newQuantity) => {
        const item = cart.find(item => item.id === productId);
        if (item) {
            item.quantite = parseInt(newQuantity);
            if (item.quantite <= 0) {
                removeFromCart(productId);
            } else {
                saveCart();
            }
        }
    };

    const updateCartDisplay = () => {
        const cartItemsContainer = document.getElementById('cart-items');
        const cartTotalSpan = document.getElementById('cart-total');

        if (!cartItemsContainer || !cartTotalSpan) return;

        cartItemsContainer.innerHTML = '';
        let total = 0;

        if (cart.length === 0) {
            cartItemsContainer.innerHTML = '<p>Votre panier est vide.</p>';
            cartTotalSpan.textContent = '0.00 €';
            return;
        }

        cart.forEach(item => {
            const itemTotal = item.prix * item.quantite;
            total += itemTotal;

            const cartItemHTML = `
                <div class="cart-item">
                    <img src="${item.image || 'https://placehold.co/80x80/e0e0e0/ffffff?text=Produit'}" alt="${item.nom}">
                    <div class="cart-item-details">
                        <h4>${item.nom}</h4>
                        <div class="price">${item.prix} €</div>
                    </div>
                    <div class="cart-item-quantity">
                        <button data-id="${item.id}" data-action="decrease">-</button>
                        <input type="number" value="${item.quantite}" min="1" data-id="${item.id}">
                        <button data-id="${item.id}" data-action="increase">+</button>
                    </div>
                    <button class="remove-item-btn" data-id="${item.id}">Supprimer</button>
                </div>
            `;
            cartItemsContainer.insertAdjacentHTML('beforeend', cartItemHTML);
        });

        cartTotalSpan.textContent = total.toFixed(2) + ' €';

        // Attache les écouteurs d'événements pour le panier
        cartItemsContainer.querySelectorAll('.remove-item-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                removeFromCart(parseInt(e.target.dataset.id));
            });
        });

        cartItemsContainer.querySelectorAll('.cart-item-quantity input').forEach(input => {
            input.addEventListener('change', (e) => {
                updateQuantity(parseInt(e.target.dataset.id), e.target.value);
            });
        });

        cartItemsContainer.querySelectorAll('.cart-item-quantity button').forEach(button => {
            button.addEventListener('click', (e) => {
                const id = parseInt(e.target.dataset.id);
                const action = e.target.dataset.action;
                const input = e.target.parentNode.querySelector('input');
                let currentQuantity = parseInt(input.value);

                if (action === 'increase') {
                    currentQuantity++;
                } else if (action === 'decrease' && currentQuantity > 1) {
                    currentQuantity--;
                }
                input.value = currentQuantity; // Met à jour l'input visuellement
                updateQuantity(id, currentQuantity); // Met à jour le modèle de données et sauvegarde
            });
        });
    };

    // --- Fonctions Page de Commande (checkout.html) ---
    const loadCheckoutSummary = () => {
        const orderSummaryList = document.getElementById('order-summary-list');
        const checkoutTotalSpan = document.getElementById('checkout-total');

        if (!orderSummaryList || !checkoutTotalSpan) return;

        orderSummaryList.innerHTML = '';
        let total = 0;

        if (cart.length === 0) {
            orderSummaryList.innerHTML = '<p>Votre panier est vide. <a href="index.html">Retour à la boutique</a></p>';
            checkoutTotalSpan.textContent = '0.00 €';
            document.getElementById('place-order-button').disabled = true;
            return;
        }

        cart.forEach(item => {
            const itemTotal = item.prix * item.quantite;
            total += itemTotal;
            const listItem = `
                <li>
                    <span>${item.nom} (x${item.quantite})</span>
                    <span>${(itemTotal).toFixed(2)} €</span>
                </li>
            `;
            orderSummaryList.insertAdjacentHTML('beforeend', listItem);
        });
        checkoutTotalSpan.textContent = total.toFixed(2) + ' €';
    };

    const placeOrder = async (event) => {
        event.preventDefault(); // Empêche le rechargement de la page par le formulaire

        const clientNom = document.getElementById('client_nom').value;
        const clientEmail = document.getElementById('client_email').value;
        const clientAdresse = document.getElementById('client_adresse').value;

        if (!clientNom || !clientEmail || !clientAdresse || cart.length === 0) {
            alert('Veuillez remplir tous les champs et avoir des produits dans votre panier.');
            return;
        }

        const productsForOrder = cart.map(item => ({
            produit_id: item.id,
            quantite: item.quantite,
            prix_unitaire: item.prix
        }));

        const orderData = {
            client_nom: clientNom,
            client_email: clientEmail,
            client_adresse: clientAdresse,
            produits: productsForOrder
        };

        try {
            const response = await fetch('http://localhost:8000/backend/api/commande.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(orderData),
            });

            const data = await response.json();

            if (data.success) {
                alert('Commande passée avec succès ! Votre numéro de commande est : ' + data.commande_id);
                cart = []; // Vide le panier après la commande
                saveCart(); // Met à jour le localStorage
                window.location.href = 'index.html'; // Redirige vers la page d'accueil
            } else {
                alert('Erreur lors du passage de la commande : ' + data.message);
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Une erreur réseau est survenue lors du passage de la commande. Veuillez vérifier que le serveur PHP est en cours d\'exécution.');
        }
    };

    // --- Routage simple côté client pour charger le bon contenu ---
    const path = window.location.pathname;

    if (path.includes('product.html')) {
        loadProductDetail();
    } else if (path.includes('cart.html')) {
        updateCartDisplay();
    } else if (path.includes('checkout.html')) {
        loadCheckoutSummary();
        const checkoutForm = document.getElementById('checkout-form');
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', placeOrder);
        }
    } else if (path.includes('admin.html')) {
        // La logique admin est côté serveur PHP, pas ici en JS client
        // Cette page admin.html va juste pointer vers le backend admin
        // Il n'y a pas de JS pour charger dynamiquement le contenu admin ici
        console.log("Admin page loaded. Content handled by PHP.");
    } else {
        // Par défaut, charger les produits sur la page d'accueil
        loadProducts();
    }

    // Mise à jour de l'affichage du panier au chargement initial de n'importe quelle page
    // (pour que le nombre d'articles dans le panier s'affiche correctement si on a un icône de panier)
    // Non implémenté dans cette version simple mais bonne pratique.
});
