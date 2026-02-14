/**
 * Module Starter - Front-office JavaScript
 *
 * Architecture modulaire avec classes ES6+
 */

import '../scss/front.scss';

/**
 * Composant principal du module
 */
class Wepresta_Passwordless_Login {
    constructor() {
        this.initialized = false;
        this.config = {};
        this.elements = {};
    }

    /**
     * Initialisation du module
     */
    init() {
        if (this.initialized) {
            return;
        }

        this.loadConfig();
        this.bindElements();
        this.bindEvents();
        this.initialized = true;

        console.debug('[Wepresta_Passwordless_Login] Initialized');
    }

    /**
     * Charge la configuration depuis le DOM
     */
    loadConfig() {
        const configElement = document.querySelector('[data-wepresta_passwordless_login-config]');

        if (configElement) {
            try {
                this.config = JSON.parse(configElement.dataset.wepresta_passwordless_loginConfig);
            } catch (e) {
                console.error('[Wepresta_Passwordless_Login] Invalid config JSON');
            }
        }
    }

    /**
     * Bind les éléments du DOM
     */
    bindElements() {
        this.elements = {
            container: document.querySelector('.wepresta_passwordless_login-container'),
            items: document.querySelectorAll('.wepresta_passwordless_login-item'),
            loadMore: document.querySelector('.wepresta_passwordless_login-load-more'),
        };
    }

    /**
     * Bind les événements
     */
    bindEvents() {
        // Load more avec délégation
        if (this.elements.loadMore) {
            this.elements.loadMore.addEventListener('click', this.handleLoadMore.bind(this));
        }

        // Délégation pour les items
        if (this.elements.container) {
            this.elements.container.addEventListener('click', this.handleItemClick.bind(this));
        }

        // Événements prestashop
        if (typeof prestashop !== 'undefined') {
            prestashop.on('updateCart', this.handleCartUpdate.bind(this));
            prestashop.on('updatedProduct', this.handleProductUpdate.bind(this));
        }
    }

    /**
     * Gestionnaire click sur item
     */
    handleItemClick(event) {
        const item = event.target.closest('.wepresta_passwordless_login-item');

        if (!item) {
            return;
        }

        const itemId = item.dataset.id;
        console.debug('[Wepresta_Passwordless_Login] Item clicked:', itemId);

        // Émettre un événement custom
        this.emit('item:click', { id: itemId, element: item });
    }

    /**
     * Gestionnaire load more
     */
    async handleLoadMore(event) {
        event.preventDefault();
        const button = event.currentTarget;

        button.classList.add('loading');
        button.disabled = true;

        try {
            const response = await this.fetchItems(this.getNextPage());
            this.appendItems(response.items);

            if (!response.hasMore) {
                button.remove();
            }
        } catch (error) {
            console.error('[Wepresta_Passwordless_Login] Load more failed:', error);
        } finally {
            button.classList.remove('loading');
            button.disabled = false;
        }
    }

    /**
     * Gestionnaire mise à jour panier
     */
    handleCartUpdate(event) {
        console.debug('[Wepresta_Passwordless_Login] Cart updated:', event);
        // Implémenter la logique de mise à jour
    }

    /**
     * Gestionnaire mise à jour produit
     */
    handleProductUpdate(event) {
        console.debug('[Wepresta_Passwordless_Login] Product updated:', event);
    }

    /**
     * Fetch des items via AJAX
     */
    async fetchItems(page = 1) {
        const url = new URL(this.config.ajaxUrl || '/module/wepresta_passwordless_login/ajax');
        url.searchParams.set('action', 'getItems');
        url.searchParams.set('page', page);
        url.searchParams.set('ajax', '1');

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response.json();
    }

    /**
     * Ajoute des items au container
     */
    appendItems(items) {
        if (!this.elements.container || !items?.length) {
            return;
        }

        const fragment = document.createDocumentFragment();

        items.forEach(item => {
            const element = this.createItemElement(item);
            fragment.appendChild(element);
        });

        this.elements.container.appendChild(fragment);
    }

    /**
     * Crée un élément item
     */
    createItemElement(item) {
        const template = document.querySelector('#wepresta_passwordless_login-item-template');

        if (template) {
            const clone = template.content.cloneNode(true);
            // Remplir le template avec les données
            const element = clone.querySelector('.wepresta_passwordless_login-item');
            element.dataset.id = item.id;
            element.querySelector('.title')?.textContent = item.title;
            return clone;
        }

        // Fallback sans template
        const div = document.createElement('div');
        div.className = 'wepresta_passwordless_login-item';
        div.dataset.id = item.id;
        div.innerHTML = `<span class="title">${item.title}</span>`;
        return div;
    }

    /**
     * Récupère le numéro de page suivant
     */
    getNextPage() {
        const currentItems = this.elements.container?.querySelectorAll('.wepresta_passwordless_login-item').length || 0;
        const perPage = this.config.perPage || 10;
        return Math.floor(currentItems / perPage) + 1;
    }

    /**
     * Émet un événement custom
     */
    emit(eventName, detail = {}) {
        const event = new CustomEvent(`wepresta_passwordless_login:${eventName}`, {
            bubbles: true,
            detail,
        });
        document.dispatchEvent(event);
    }

    /**
     * Écoute un événement custom
     */
    on(eventName, callback) {
        document.addEventListener(`wepresta_passwordless_login:${eventName}`, callback);
    }
}

// Instance globale
const moduleStarter = new Wepresta_Passwordless_Login();

// Initialisation au chargement du DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => moduleStarter.init());
} else {
    moduleStarter.init();
}

// Export pour accès externe
window.Wepresta_Passwordless_Login = moduleStarter;
export default moduleStarter;

