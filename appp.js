// frontend/js/app.js

document.addEventListener('DOMContentLoaded', () => {
    // Sécurisation du panier avec validation des données
    let cart = [];
    try {
        const cartData = localStorage.getItem('elrMarketCart');
        cart = cartData ? JSON.parse(cartData) : [];
        if (!Array.isArray(cart)) cart = [];
    } catch (e) {
        console.warn('Corruption des données du panier détectée, réinitialisation');
        cart = [];
    }

    // Fonction sécurisée pour sauvegarder le panier
    const saveCart = () => {
        try {
            // Validation des données avant sauvegarde
            const validatedCart = cart.map(item => ({
                id: Number.isInteger(item.id) ? item.id : 0,
                nom: typeof item.nom === 'string' ? item.nom.substring(0, 100) : '',
                prix: !isNaN(parseFloat(item.prix)) ? parseFloat(item.prix) : 0,
                image: typeof item.image === 'string' ? item.image.substring(0, 500) : '',
                quantite: (Number.isInteger(item.quantite) && item.quantite > 0) ? item.quantite : 1
            })).filter(item => item.id > 0);
            
            localStorage.setItem('elrMarketCart', JSON.stringify(validatedCart));
            updateCartDisplay();
        } catch (error) {
            console.error('Erreur de sauvegarde du panier:', error);
        }
    };

    // Fonction pour insérer le footer avec sécurisation XSS
    /*
    function insertFooter() {
        const currentYear = new Date().getFullYear();
        const footerElement = document.createElement('footer');
        const paragraph = document.createElement('p');
       // paragraph.textContent = '© ${currentYear} Elr Market. Tous droits réservés.';
        footerElement.appendChild(paragraph);
        document.body.appendChild(footerElement);
    } */

    // Fonction d'échappement HTML
    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Fonction de validation d'URL
    function sanitizeUrl(url) {
        if (typeof url !== 'string') return 'https://placehold.co/400x300/e0e0e0/ffffff?text=Image+Produit';
        try {
            const parsed = new URL(url);
            if (['http:', 'https:', 'data:'].includes(parsed.protocol)) {
                return url;
            }
        } catch (e) {
            return 'https://placehold.co/400x300/e0e0e0/ffffff?text=Image+Produit';
        }
        return 'https://placehold.co/400x300/e0e0e0/ffffff?text=Image+Produit';
    }

    // Fonction sécurisée pour récupérer un produit
    const getProductById = async (id) => {
        const productId = parseInt(id);
        if (isNaN(productId)) return null;

        try {
            const response = await fetch('http://localhost:8000/backend/api/produits.php', {
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Erreur réseau');

            const data = await response.json();
            
            if (data.success && Array.isArray(data.data)) {
                return data.data.find(product => product.id === productId) || null;
            }
            return null;
        } catch (error) {
            console.error('Erreur:', error);
            return null;
        }
    };

    // Fonction sécurisée pour charger les produits
    const loadProducts = async () => {
        const productListDiv = document.getElementById('product-list');
        if (!productListDiv) return;

        productListDiv.textContent = 'Chargement des produits...';

        try {
            const response = await fetch('http://localhost:8000/backend/api/produits.php', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Erreur réseau');

            const data = await response.json();

            if (data.success && Array.isArray(data.data)) {
                productListDiv.innerHTML = '';
                
                data.data.forEach(product => {
                    const safeProduct = {
                        id: parseInt(product.id) || 0,
                        nom: escapeHtml(product.nom || ''),
                        description: escapeHtml(product.description ? product.description.substring(0, 100) : ''),
                        prix: parseFloat(product.prix) || 0,
                        image: sanitizeUrl(product.image || '')
                    };

                    if (safeProduct.id <= 0) return;

                    const productCard = document.createElement('div');
                    productCard.className = 'product-card';
                    productCard.innerHTML = `
                        <img src="${safeProduct.image}" alt="${safeProduct.nom}">
                        <div class="product-card-content">
                            <h3>${safeProduct.nom}</h3>
                            <p>${safeProduct.description}...</p>
                            <div class="price">${safeProduct.prix.toFixed(2)} €</div>
                            <a href="product.html?id=${safeProduct.id}" class="view-details-btn">Voir détails</a>
                            <button class="add-to-cart-btn" data-id="${safeProduct.id}">Ajouter au panier</button>
                        </div>
                    `;
                    
                    productListDiv.appendChild(productCard);
                });

                document.querySelectorAll('.add-to-cart-btn').forEach(button => {
                    button.addEventListener('click', async (e) => {
                        const productId = parseInt(e.target.getAttribute('data-id'));
                        if (isNaN(productId)) return;
                        
                        const product = await getProductById(productId);
                        if (product) {
                            addToCart({
                                id: product.id,
                                nom: escapeHtml(product.nom || ''),
                                prix: parseFloat(product.prix) || 0,
                                image: sanitizeUrl(product.image || ''),
                                quantite: 1
                            });
                        }
                    });
                });
            } else {
                productListDiv.textContent = 'Erreur: Données invalides reçues';
            }
        } catch (error) {
            productListDiv.textContent = 'Erreur réseau. Veuillez réessayer.';
            console.error('Erreur:', error);
        }
    };

    // Fonction pour ajouter au panier
    const addToCart = (productToAdd) => {
        if (!productToAdd || !productToAdd.id) return;
        
        const existingProductIndex = cart.findIndex(item => item.id === productToAdd.id);
        
        if (existingProductIndex > -1) {
            cart[existingProductIndex].quantite += productToAdd.quantite || 1;
        } else {
            cart.push({
                id: productToAdd.id,
                nom: productToAdd.nom || '',
                prix: productToAdd.prix || 0,
                image: productToAdd.image || '',
                quantite: productToAdd.quantite || 1
            });
        }
        saveCart();
    };

    // ... (autres fonctions avec les mêmes principes de sécurité)

    // Initialisation
    insertFooter();
    
    const path = window.location.pathname;
    if (path.includes('product.html')) {
        loadProductDetail();
    } else if (path.includes('cart.html')) {
        updateCartDisplay();
    } else if (path.includes('checkout.html')) {
        loadCheckoutSummary();
        const checkoutForm = document.getElementById('checkout-form');
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', (e) => {
                e.preventDefault();
                placeOrder(e).catch(console.error);
            });
        }
    } else {
        loadProducts();
    }
});