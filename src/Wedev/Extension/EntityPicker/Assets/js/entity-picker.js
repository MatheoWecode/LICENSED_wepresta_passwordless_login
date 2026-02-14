/**
 * =============================================================================
 * WEDEV Extension - EntityPicker
 * =============================================================================
 * Composant JavaScript vanilla pour la sélection d'entités via recherche AJAX.
 *
 * Usage:
 * ```html
 * <div class="entity-picker"
 *      data-search-url="/search"
 *      data-fetch-url="/fetch"
 *      data-multiple="true"
 *      data-min-chars="2">
 *     <input type="text" class="entity-picker-search" placeholder="Rechercher...">
 *     <div class="entity-picker-results"></div>
 *     <div class="entity-picker-selected"></div>
 *     <input type="hidden" class="entity-picker-ids" name="entity_ids">
 * </div>
 * ```
 *
 * Ou initialisation via JS:
 * ```javascript
 * WedevEntityPicker.init('.entity-picker');
 * ```
 * =============================================================================
 */

const WedevEntityPicker = {
    /**
     * Configuration par défaut
     */
    defaults: {
        minChars: 2,
        debounceMs: 300,
        maxResults: 20,
        multiple: true,
        allowClear: true,
        searchInputClass: 'entity-picker-search',
        resultsClass: 'entity-picker-results',
        selectedClass: 'entity-picker-selected',
        idsInputClass: 'entity-picker-ids',
    },

    /**
     * Instances actives
     * @type {Map<HTMLElement, object>}
     */
    instances: new Map(),

    /**
     * Initialise tous les entity pickers du DOM
     * @param {string} selector - Sélecteur CSS
     */
    init(selector = '.entity-picker') {
        document.querySelectorAll(selector).forEach((container) => {
            if (!this.instances.has(container)) {
                this.initInstance(container);
            }
        });
    },

    /**
     * Initialise une instance d'entity picker
     * @param {HTMLElement} container
     */
    initInstance(container) {
        const config = this.getConfig(container);
        const state = {
            selectedEntities: new Map(),
            searchTimeout: null,
        };

        // Éléments DOM
        const elements = {
            container,
            searchInput: container.querySelector(`.${config.searchInputClass}`),
            resultsContainer: container.querySelector(`.${config.resultsClass}`),
            selectedContainer: container.querySelector(`.${config.selectedClass}`),
            idsInput: container.querySelector(`.${config.idsInputClass}`),
        };

        if (!elements.searchInput || !elements.idsInput) {
            console.error('WedevEntityPicker: Missing required elements', container);
            return;
        }

        // Créer les conteneurs s'ils n'existent pas
        if (!elements.resultsContainer) {
            elements.resultsContainer = document.createElement('div');
            elements.resultsContainer.className = config.resultsClass + ' list-group mt-2';
            elements.resultsContainer.style.maxHeight = '200px';
            elements.resultsContainer.style.overflowY = 'auto';
            elements.searchInput.after(elements.resultsContainer);
        }

        if (!elements.selectedContainer) {
            elements.selectedContainer = document.createElement('ul');
            elements.selectedContainer.className = config.selectedClass + ' list-group mt-3';
            elements.resultsContainer.after(elements.selectedContainer);
        }

        // Instance
        const instance = { config, state, elements };
        this.instances.set(container, instance);

        // Event listeners
        this.bindEvents(instance);

        // Charger les entités initiales si des IDs sont présents
        this.loadInitialEntities(instance);
    },

    /**
     * Récupère la configuration depuis les data attributes
     */
    getConfig(container) {
        return {
            ...this.defaults,
            searchUrl: container.dataset.searchUrl || '',
            fetchUrl: container.dataset.fetchUrl || '',
            multiple: container.dataset.multiple !== 'false',
            minChars: parseInt(container.dataset.minChars, 10) || this.defaults.minChars,
            maxResults: parseInt(container.dataset.maxResults, 10) || this.defaults.maxResults,
            allowClear: container.dataset.allowClear !== 'false',
            placeholder: container.dataset.placeholder || 'Rechercher...',
            entityType: container.dataset.entityType || 'entity',
        };
    },

    /**
     * Attache les event listeners
     */
    bindEvents(instance) {
        const { elements, config, state } = instance;

        // Recherche avec debounce
        elements.searchInput.addEventListener('input', () => {
            clearTimeout(state.searchTimeout);
            const query = elements.searchInput.value.trim();

            if (query.length < config.minChars) {
                this.clearResults(instance);
                return;
            }

            state.searchTimeout = setTimeout(() => {
                this.search(instance, query);
            }, config.debounceMs);
        });

        // Fermer les résultats sur clic extérieur
        document.addEventListener('click', (e) => {
            if (!elements.container.contains(e.target)) {
                this.clearResults(instance);
            }
        });

        // Supprimer une entité sélectionnée
        elements.selectedContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('entity-picker-remove')) {
                const entityId = parseInt(e.target.dataset.entityId, 10);
                this.removeEntity(instance, entityId);
            }
        });
    },

    /**
     * Effectue une recherche AJAX
     */
    async search(instance, query) {
        const { config, elements } = instance;

        if (!config.searchUrl) {
            console.error('WedevEntityPicker: searchUrl not configured');
            return;
        }

        try {
            // Construire l'URL avec le query en path ou en param
            let url = config.searchUrl;
            if (url.includes('{query}') || url.includes('__QUERY__')) {
                url = url.replace('{query}', encodeURIComponent(query))
                         .replace('__QUERY__', encodeURIComponent(query));
            } else {
                url += (url.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(query);
            }

            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const entities = await response.json();
            this.displayResults(instance, entities);

        } catch (error) {
            console.error('WedevEntityPicker: Search failed', error);
            this.clearResults(instance);
        }
    },

    /**
     * Affiche les résultats de recherche
     */
    displayResults(instance, entities) {
        const { elements, state, config } = instance;

        elements.resultsContainer.innerHTML = '';

        if (!entities || entities.length === 0) {
            elements.resultsContainer.innerHTML = `
                <div class="list-group-item text-muted">Aucun résultat</div>
            `;
            return;
        }

        entities.forEach((entity) => {
            // Ne pas afficher les entités déjà sélectionnées
            if (state.selectedEntities.has(entity.id)) {
                return;
            }

            const item = document.createElement('a');
            item.href = '#';
            item.className = 'list-group-item list-group-item-action d-flex align-items-center';
            item.innerHTML = `
                ${entity.image ? `<img src="${entity.image}" alt="" class="img-thumbnail mr-2" style="width: 40px; height: 40px; object-fit: cover;">` : ''}
                <span>${this.escapeHtml(entity.name)}</span>
            `;

            item.addEventListener('click', (e) => {
                e.preventDefault();
                this.selectEntity(instance, entity);
                elements.searchInput.value = '';
                this.clearResults(instance);

                // Si non multiple, ne permettre qu'une seule sélection
                if (!config.multiple && state.selectedEntities.size > 0) {
                    // Remplacer l'entité existante
                    state.selectedEntities.clear();
                    elements.selectedContainer.innerHTML = '';
                }
            });

            elements.resultsContainer.appendChild(item);
        });
    },

    /**
     * Efface les résultats de recherche
     */
    clearResults(instance) {
        instance.elements.resultsContainer.innerHTML = '';
    },

    /**
     * Sélectionne une entité
     */
    selectEntity(instance, entity) {
        const { state, elements, config } = instance;

        // Si non multiple, supprimer la sélection existante
        if (!config.multiple) {
            state.selectedEntities.clear();
            elements.selectedContainer.innerHTML = '';
        }

        if (state.selectedEntities.has(entity.id)) {
            return; // Déjà sélectionnée
        }

        state.selectedEntities.set(entity.id, entity);
        this.renderSelectedEntity(instance, entity);
        this.updateHiddenInput(instance);
    },

    /**
     * Affiche une entité sélectionnée
     */
    renderSelectedEntity(instance, entity) {
        const { elements, config } = instance;

        const item = document.createElement('li');
        item.className = 'list-group-item d-flex justify-content-between align-items-center';
        item.dataset.entityId = entity.id;
        item.innerHTML = `
            <div class="d-flex align-items-center">
                ${entity.image ? `<img src="${entity.image}" alt="" class="img-thumbnail mr-2" style="width: 40px; height: 40px; object-fit: cover;">` : ''}
                <span>${this.escapeHtml(entity.name)}</span>
            </div>
            ${config.allowClear ? `<button type="button" class="btn btn-sm btn-outline-danger entity-picker-remove" data-entity-id="${entity.id}">&times;</button>` : ''}
        `;

        elements.selectedContainer.appendChild(item);
    },

    /**
     * Supprime une entité sélectionnée
     */
    removeEntity(instance, entityId) {
        const { state, elements } = instance;

        state.selectedEntities.delete(entityId);

        const item = elements.selectedContainer.querySelector(`[data-entity-id="${entityId}"]`);
        if (item) {
            item.remove();
        }

        this.updateHiddenInput(instance);
    },

    /**
     * Met à jour le champ hidden avec les IDs
     */
    updateHiddenInput(instance) {
        const { state, elements } = instance;
        const ids = Array.from(state.selectedEntities.keys());
        elements.idsInput.value = JSON.stringify(ids);

        // Dispatch un événement personnalisé
        elements.container.dispatchEvent(new CustomEvent('entitypicker:change', {
            detail: {
                ids,
                entities: Array.from(state.selectedEntities.values()),
            },
            bubbles: true,
        }));
    },

    /**
     * Charge les entités initiales à partir des IDs existants
     */
    async loadInitialEntities(instance) {
        const { elements, config } = instance;
        const currentValue = elements.idsInput.value;

        if (!currentValue) {
            return;
        }

        let ids;
        try {
            ids = JSON.parse(currentValue);
        } catch {
            // Peut être une liste séparée par des virgules
            ids = currentValue.split(',').map((id) => parseInt(id.trim(), 10)).filter((id) => id > 0);
        }

        if (!Array.isArray(ids) || ids.length === 0) {
            return;
        }

        if (!config.fetchUrl) {
            console.warn('WedevEntityPicker: fetchUrl not configured, cannot load initial entities');
            return;
        }

        try {
            const response = await fetch(config.fetchUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ ids }),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const entities = await response.json();

            entities.forEach((entity) => {
                this.selectEntity(instance, entity);
            });

        } catch (error) {
            console.error('WedevEntityPicker: Failed to load initial entities', error);
        }
    },

    /**
     * Récupère les IDs sélectionnés
     */
    getSelectedIds(container) {
        const instance = this.instances.get(container);
        if (!instance) {
            return [];
        }
        return Array.from(instance.state.selectedEntities.keys());
    },

    /**
     * Récupère les entités sélectionnées
     */
    getSelectedEntities(container) {
        const instance = this.instances.get(container);
        if (!instance) {
            return [];
        }
        return Array.from(instance.state.selectedEntities.values());
    },

    /**
     * Efface toutes les sélections
     */
    clear(container) {
        const instance = this.instances.get(container);
        if (!instance) {
            return;
        }
        instance.state.selectedEntities.clear();
        instance.elements.selectedContainer.innerHTML = '';
        this.updateHiddenInput(instance);
    },

    /**
     * Échappe le HTML pour éviter les XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
};

// Auto-init au chargement du DOM
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => WedevEntityPicker.init());
    } else {
        WedevEntityPicker.init();
    }
}

// Exposer globalement
if (typeof window !== 'undefined') {
    window.WedevEntityPicker = WedevEntityPicker;
}

