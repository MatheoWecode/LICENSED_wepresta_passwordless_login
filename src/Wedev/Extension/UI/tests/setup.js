/**
 * =============================================================================
 * WEDEV UI - Test Setup
 * =============================================================================
 * Configuration Vitest pour les tests de l'Extension UI.
 *
 * Ce fichier est chargé avant l'exécution des tests pour configurer
 * l'environnement de test (mocks globaux, polyfills, etc.)
 * =============================================================================
 */

import { vi } from 'vitest';

// =============================================================================
// Mock du DOM minimal
// =============================================================================

// Mock de document si nécessaire
if (typeof document === 'undefined') {
    global.document = {
        body: {
            innerHTML: '',
            appendChild: vi.fn(),
            removeChild: vi.fn()
        },
        documentElement: {
            classList: {
                add: vi.fn(),
                remove: vi.fn()
            }
        },
        querySelector: vi.fn(() => null),
        querySelectorAll: vi.fn(() => []),
        createElement: vi.fn((tag) => ({
            tagName: tag.toUpperCase(),
            style: {},
            setAttribute: vi.fn(),
            addEventListener: vi.fn(),
            appendChild: vi.fn()
        })),
        addEventListener: vi.fn(),
        readyState: 'complete'
    };
}

// =============================================================================
// Mock de window
// =============================================================================

if (typeof window === 'undefined') {
    global.window = {
        location: {
            href: 'https://test.com/',
            search: ''
        },
        innerWidth: 1024,
        innerHeight: 768,
        pageYOffset: 0,
        scrollTo: vi.fn(),
        dispatchEvent: vi.fn(),
        addEventListener: vi.fn()
    };
}

// Exposer window globalement
global.window = global.window || {};

// =============================================================================
// Mock de fetch API
// =============================================================================

global.fetch = vi.fn(() => 
    Promise.resolve({
        ok: true,
        status: 200,
        headers: new Headers({ 'content-type': 'application/json' }),
        json: () => Promise.resolve({}),
        text: () => Promise.resolve('')
    })
);

// =============================================================================
// Mock de FormData
// =============================================================================

if (typeof FormData === 'undefined') {
    global.FormData = class FormData {
        constructor() {
            this._data = new Map();
        }
        
        append(key, value) {
            this._data.set(key, value);
        }
        
        get(key) {
            return this._data.get(key);
        }
        
        has(key) {
            return this._data.has(key);
        }
        
        entries() {
            return this._data.entries();
        }
        
        forEach(callback) {
            this._data.forEach((value, key) => callback(value, key));
        }
    };
}

// =============================================================================
// Mock de Headers
// =============================================================================

if (typeof Headers === 'undefined') {
    global.Headers = class Headers {
        constructor(init = {}) {
            this._headers = new Map(Object.entries(init));
        }
        
        get(name) {
            return this._headers.get(name.toLowerCase());
        }
        
        set(name, value) {
            this._headers.set(name.toLowerCase(), value);
        }
        
        has(name) {
            return this._headers.has(name.toLowerCase());
        }
    };
}

// =============================================================================
// Mock de URL et URLSearchParams
// =============================================================================

if (typeof URLSearchParams === 'undefined') {
    global.URLSearchParams = class URLSearchParams {
        constructor(init = '') {
            this._params = new Map();
            if (typeof init === 'string' && init) {
                init.split('&').forEach(pair => {
                    const [key, value] = pair.split('=');
                    this._params.set(decodeURIComponent(key), decodeURIComponent(value || ''));
                });
            }
        }
        
        get(name) {
            return this._params.get(name) || null;
        }
        
        set(name, value) {
            this._params.set(name, value);
        }
        
        toString() {
            const pairs = [];
            this._params.forEach((value, key) => {
                pairs.push(`${encodeURIComponent(key)}=${encodeURIComponent(value)}`);
            });
            return pairs.join('&');
        }
    };
}

// =============================================================================
// Mock de AbortController
// =============================================================================

if (typeof AbortController === 'undefined') {
    global.AbortController = class AbortController {
        constructor() {
            this.signal = { aborted: false };
        }
        
        abort() {
            this.signal.aborted = true;
        }
    };
}

// =============================================================================
// Mock de console
// =============================================================================

// Supprime les logs pendant les tests (optionnel)
// global.console = {
//     ...console,
//     log: vi.fn(),
//     warn: vi.fn(),
//     error: vi.fn()
// };

// =============================================================================
// Helpers pour les tests
// =============================================================================

/**
 * Simule un délai
 */
global.sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

/**
 * Crée un mock de réponse fetch
 */
global.mockFetchResponse = (data, options = {}) => {
    return Promise.resolve({
        ok: options.ok !== false,
        status: options.status || 200,
        statusText: options.statusText || 'OK',
        headers: new Headers({ 'content-type': 'application/json', ...options.headers }),
        json: () => Promise.resolve(data),
        text: () => Promise.resolve(JSON.stringify(data))
    });
};

// =============================================================================
// Cleanup après chaque test
// =============================================================================

import { afterEach } from 'vitest';

afterEach(() => {
    vi.clearAllMocks();
});





