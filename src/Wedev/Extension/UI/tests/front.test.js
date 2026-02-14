/**
 * =============================================================================
 * WEDEV UI - Tests: Front-Office Utilities
 * =============================================================================
 * Tests unitaires pour WedevFront.
 * =============================================================================
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Note: WedevFront est exposé globalement, on doit le charger après setup
describe('WedevFront', () => {
    let WedevFront;
    let mockFetch;

    beforeEach(async () => {
        // Mock fetch
        mockFetch = vi.fn();
        global.fetch = mockFetch;

        // Mock prestashop global
        global.window = global.window || {};
        window.prestashop = {
            static_token: 'test_token_123',
            currency: {
                sign: '€',
                format: '%price% %s'
            },
            urls: {
                base_url: 'https://shop.test/',
                actions: {
                    add_to_cart: 'https://shop.test/cart/add'
                }
            }
        };

        // Mock localStorage
        const localStorageMock = {
            store: {},
            getItem: vi.fn((key) => localStorageMock.store[key] || null),
            setItem: vi.fn((key, value) => { localStorageMock.store[key] = value; }),
            removeItem: vi.fn((key) => { delete localStorageMock.store[key]; }),
            clear: vi.fn(() => { localStorageMock.store = {}; })
        };
        global.localStorage = localStorageMock;

        // Mock navigator
        global.navigator = {
            userAgent: 'Mozilla/5.0 Test Browser',
            clipboard: {
                writeText: vi.fn().mockResolvedValue(undefined)
            }
        };

        // Import dynamique après setup des mocks
        const module = await import('../Assets/js/front/wedev-front.js');
        WedevFront = window.WedevFront;
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    describe('ajax()', () => {
        it('should make POST request with FormData', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                headers: new Headers({ 'content-type': 'application/json' }),
                json: () => Promise.resolve({ success: true })
            });

            const result = await WedevFront.ajax('/api/test', { id: 123 });

            expect(mockFetch).toHaveBeenCalledTimes(1);
            expect(result).toEqual({ success: true });
        });

        it('should include PrestaShop token', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                headers: new Headers({ 'content-type': 'application/json' }),
                json: () => Promise.resolve({})
            });

            await WedevFront.ajax('/api/test', {});

            const [, options] = mockFetch.mock.calls[0];
            expect(options.body.get('token')).toBe('test_token_123');
        });
    });

    describe('debounce()', () => {
        it('should delay function execution', async () => {
            vi.useFakeTimers();
            const fn = vi.fn();
            const debouncedFn = WedevFront.debounce(fn, 100);

            debouncedFn('arg1');
            debouncedFn('arg2');
            debouncedFn('arg3');

            expect(fn).not.toHaveBeenCalled();

            vi.advanceTimersByTime(100);

            expect(fn).toHaveBeenCalledTimes(1);
            expect(fn).toHaveBeenCalledWith('arg3');

            vi.useRealTimers();
        });
    });

    describe('throttle()', () => {
        it('should limit function calls', () => {
            vi.useFakeTimers();
            const fn = vi.fn();
            const throttledFn = WedevFront.throttle(fn, 100);

            throttledFn();
            throttledFn();
            throttledFn();

            expect(fn).toHaveBeenCalledTimes(1);

            vi.advanceTimersByTime(100);
            throttledFn();

            expect(fn).toHaveBeenCalledTimes(2);

            vi.useRealTimers();
        });
    });

    describe('formatPrice()', () => {
        it('should format price with currency', () => {
            const formatted = WedevFront.formatPrice(29.99);
            expect(formatted).toBe('29.99 €');
        });

        it('should use custom currency sign', () => {
            const formatted = WedevFront.formatPrice(100, '$');
            expect(formatted).toBe('100.00 $');
        });
    });

    describe('isMobile()', () => {
        it('should return false for desktop user agent', () => {
            expect(WedevFront.isMobile()).toBe(false);
        });

        it('should return true for narrow viewport', () => {
            const originalWidth = window.innerWidth;
            Object.defineProperty(window, 'innerWidth', { value: 500, writable: true });
            
            expect(WedevFront.isMobile()).toBe(true);
            
            Object.defineProperty(window, 'innerWidth', { value: originalWidth, writable: true });
        });
    });

    describe('getUrlParam()', () => {
        it('should extract URL parameter', () => {
            const result = WedevFront.getUrlParam('id', 'https://test.com?id=123&name=test');
            expect(result).toBe('123');
        });

        it('should return null for missing parameter', () => {
            const result = WedevFront.getUrlParam('missing', 'https://test.com?id=123');
            expect(result).toBeNull();
        });
    });

    describe('storage()', () => {
        it('should store and retrieve value', () => {
            WedevFront.storage('testKey', 'testValue');
            const stored = localStorage.setItem.mock.calls[0];
            
            expect(stored[0]).toBe('wedev_testKey');
            expect(JSON.parse(stored[1]).value).toBe('testValue');
        });

        it('should delete value when set to null', () => {
            WedevFront.storage('testKey', null);
            
            expect(localStorage.removeItem).toHaveBeenCalledWith('wedev_testKey');
        });
    });

    describe('copyToClipboard()', () => {
        it('should copy text to clipboard', async () => {
            const result = await WedevFront.copyToClipboard('test text');
            
            expect(navigator.clipboard.writeText).toHaveBeenCalledWith('test text');
            expect(result).toBe(true);
        });
    });
});





