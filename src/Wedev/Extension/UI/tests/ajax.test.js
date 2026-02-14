/**
 * =============================================================================
 * WEDEV UI - Tests: AJAX Utilities
 * =============================================================================
 * Tests unitaires pour le module WedevAjax.
 *
 * Pour exécuter les tests:
 *   npm test
 *   # ou
 *   npx vitest run src/Extension/UI/tests/
 * =============================================================================
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock de fetch global
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Import du module à tester (après le mock de fetch)
import { WedevAjax } from '../Assets/js/admin/utils/ajax.js';

describe('WedevAjax', () => {
    beforeEach(() => {
        mockFetch.mockClear();
        // Reset du DOM
        document.body.innerHTML = '';
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    describe('get()', () => {
        it('should make a GET request', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                headers: new Headers({ 'content-type': 'application/json' }),
                json: () => Promise.resolve({ success: true })
            });

            const result = await WedevAjax.get('/api/test');

            expect(mockFetch).toHaveBeenCalledTimes(1);
            expect(mockFetch).toHaveBeenCalledWith(
                '/api/test',
                expect.objectContaining({ method: 'GET' })
            );
            expect(result).toEqual({ success: true });
        });

        it('should append query params to URL', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                headers: new Headers({ 'content-type': 'application/json' }),
                json: () => Promise.resolve({ data: [] })
            });

            await WedevAjax.get('/api/items', { page: 1, limit: 10 });

            expect(mockFetch).toHaveBeenCalledWith(
                '/api/items?page=1&limit=10',
                expect.any(Object)
            );
        });
    });

    describe('post()', () => {
        it('should make a POST request with FormData', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                headers: new Headers({ 'content-type': 'application/json' }),
                json: () => Promise.resolve({ id: 1 })
            });

            const result = await WedevAjax.post('/api/create', { name: 'Test' });

            expect(mockFetch).toHaveBeenCalledTimes(1);
            const [url, options] = mockFetch.mock.calls[0];
            expect(url).toBe('/api/create');
            expect(options.method).toBe('POST');
            expect(options.body).toBeInstanceOf(FormData);
            expect(result).toEqual({ id: 1 });
        });

        it('should make a POST request with JSON when asJson is true', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                headers: new Headers({ 'content-type': 'application/json' }),
                json: () => Promise.resolve({ id: 1 })
            });

            await WedevAjax.post('/api/create', { name: 'Test' }, true);

            const [, options] = mockFetch.mock.calls[0];
            expect(options.body).toBe('{"name":"Test"}');
            expect(options.headers['Content-Type']).toBe('application/json');
        });
    });

    describe('put()', () => {
        it('should make a PUT request with JSON', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                headers: new Headers({ 'content-type': 'application/json' }),
                json: () => Promise.resolve({ updated: true })
            });

            await WedevAjax.put('/api/update/1', { name: 'Updated' });

            const [url, options] = mockFetch.mock.calls[0];
            expect(url).toBe('/api/update/1');
            expect(options.method).toBe('PUT');
            expect(options.headers['Content-Type']).toBe('application/json');
        });
    });

    describe('delete()', () => {
        it('should make a DELETE request', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                headers: new Headers({ 'content-type': 'application/json' }),
                json: () => Promise.resolve({ deleted: true })
            });

            await WedevAjax.delete('/api/item/1');

            const [url, options] = mockFetch.mock.calls[0];
            expect(url).toBe('/api/item/1');
            expect(options.method).toBe('DELETE');
        });
    });

    describe('postWithToken()', () => {
        it('should include token from input field', async () => {
            // Ajouter un input token au DOM
            document.body.innerHTML = '<input type="hidden" name="token" value="abc123">';

            mockFetch.mockResolvedValueOnce({
                ok: true,
                headers: new Headers({ 'content-type': 'application/json' }),
                json: () => Promise.resolve({ success: true })
            });

            await WedevAjax.postWithToken('/api/action', { id: 1 });

            const [, options] = mockFetch.mock.calls[0];
            const formData = options.body;
            expect(formData.get('token')).toBe('abc123');
        });
    });

    describe('Error handling', () => {
        it('should throw error on HTTP error', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 404,
                statusText: 'Not Found'
            });

            await expect(WedevAjax.get('/api/notfound')).rejects.toThrow('HTTP 404');
        });

        it('should handle network errors', async () => {
            mockFetch.mockRejectedValueOnce(new Error('Network error'));

            await expect(WedevAjax.get('/api/test')).rejects.toThrow('Network error');
        });
    });

    describe('_toFormData()', () => {
        it('should convert object to FormData', () => {
            const formData = WedevAjax._toFormData({ name: 'Test', count: 5 });

            expect(formData).toBeInstanceOf(FormData);
            expect(formData.get('name')).toBe('Test');
            expect(formData.get('count')).toBe('5');
        });

        it('should handle arrays', () => {
            const formData = WedevAjax._toFormData({ items: ['a', 'b', 'c'] });

            expect(formData.get('items[0]')).toBe('a');
            expect(formData.get('items[1]')).toBe('b');
            expect(formData.get('items[2]')).toBe('c');
        });

        it('should skip null and undefined values', () => {
            const formData = WedevAjax._toFormData({ 
                valid: 'yes', 
                nullVal: null, 
                undefinedVal: undefined 
            });

            expect(formData.get('valid')).toBe('yes');
            expect(formData.has('nullVal')).toBe(false);
            expect(formData.has('undefinedVal')).toBe(false);
        });
    });
});





