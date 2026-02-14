/**
 * =============================================================================
 * WEDEV UI - Tests: Notifications Helper
 * =============================================================================
 * Tests unitaires pour le module de notifications toast.
 * =============================================================================
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';

// Import du module à tester
import { notify, toast } from '../Assets/js/admin/utils/notifications.js';

describe('Notification Helpers', () => {
    let dispatchedEvents = [];

    beforeEach(() => {
        dispatchedEvents = [];
        
        // Mock de window.dispatchEvent
        vi.spyOn(window, 'dispatchEvent').mockImplementation((event) => {
            dispatchedEvents.push(event);
        });
    });

    describe('notify.show()', () => {
        it('should dispatch wedev-toast event', () => {
            notify.show('Test message', 'info', 5000);

            expect(dispatchedEvents.length).toBe(1);
            expect(dispatchedEvents[0].type).toBe('wedev-toast');
        });

        it('should include message, type, and duration in detail', () => {
            notify.show('Hello World', 'success', 3000);

            const detail = dispatchedEvents[0].detail;
            expect(detail.message).toBe('Hello World');
            expect(detail.type).toBe('success');
            expect(detail.duration).toBe(3000);
        });

        it('should use default values for type and duration', () => {
            notify.show('Default message');

            const detail = dispatchedEvents[0].detail;
            expect(detail.type).toBe('info');
            expect(detail.duration).toBe(5000);
        });
    });

    describe('notify.success()', () => {
        it('should dispatch success notification', () => {
            notify.success('Saved!');

            const detail = dispatchedEvents[0].detail;
            expect(detail.message).toBe('Saved!');
            expect(detail.type).toBe('success');
            expect(detail.duration).toBe(5000);
        });

        it('should accept custom duration', () => {
            notify.success('Saved!', 10000);

            expect(dispatchedEvents[0].detail.duration).toBe(10000);
        });
    });

    describe('notify.error()', () => {
        it('should dispatch error notification with longer duration', () => {
            notify.error('Something went wrong');

            const detail = dispatchedEvents[0].detail;
            expect(detail.message).toBe('Something went wrong');
            expect(detail.type).toBe('danger');
            expect(detail.duration).toBe(8000); // Errors have longer default duration
        });
    });

    describe('notify.warning()', () => {
        it('should dispatch warning notification', () => {
            notify.warning('Please check your input');

            const detail = dispatchedEvents[0].detail;
            expect(detail.message).toBe('Please check your input');
            expect(detail.type).toBe('warning');
            expect(detail.duration).toBe(6000);
        });
    });

    describe('notify.info()', () => {
        it('should dispatch info notification', () => {
            notify.info('Processing...');

            const detail = dispatchedEvents[0].detail;
            expect(detail.message).toBe('Processing...');
            expect(detail.type).toBe('info');
            expect(detail.duration).toBe(5000);
        });
    });

    describe('notify.persistent()', () => {
        it('should dispatch notification with duration 0', () => {
            notify.persistent('Important!', 'warning');

            const detail = dispatchedEvents[0].detail;
            expect(detail.message).toBe('Important!');
            expect(detail.type).toBe('warning');
            expect(detail.duration).toBe(0);
        });
    });

    describe('notify.loading()', () => {
        it('should dispatch loading notification', () => {
            notify.loading('Uploading...');

            const detail = dispatchedEvents[0].detail;
            expect(detail.message).toBe('Uploading...');
            expect(detail.type).toBe('info');
            expect(detail.duration).toBe(0);
            expect(detail.isLoading).toBe(true);
        });

        it('should return a close function', () => {
            const close = notify.loading('Loading...');

            expect(typeof close).toBe('function');
        });

        it('should dispatch remove event when close is called', () => {
            const close = notify.loading('Loading...');
            const loadingId = dispatchedEvents[0].detail.id;

            close();

            expect(dispatchedEvents.length).toBe(2);
            expect(dispatchedEvents[1].type).toBe('wedev-toast-remove');
            expect(dispatchedEvents[1].detail.id).toBe(loadingId);
        });
    });

    describe('notify.promise()', () => {
        it('should show loading, then success on resolved promise', async () => {
            const promise = Promise.resolve({ data: 'test' });

            await notify.promise(promise, {
                loading: 'Loading...',
                success: 'Done!',
                error: 'Failed'
            });

            // Should have: loading, remove loading, success
            expect(dispatchedEvents.length).toBe(3);
            expect(dispatchedEvents[0].detail.message).toBe('Loading...');
            expect(dispatchedEvents[2].detail.message).toBe('Done!');
            expect(dispatchedEvents[2].detail.type).toBe('success');
        });

        it('should show loading, then error on rejected promise', async () => {
            const promise = Promise.reject(new Error('Test error'));

            try {
                await notify.promise(promise, {
                    loading: 'Loading...',
                    success: 'Done!',
                    error: 'Failed'
                });
            } catch {
                // Expected
            }

            // Should have: loading, remove loading, error
            expect(dispatchedEvents.length).toBe(3);
            expect(dispatchedEvents[2].detail.message).toBe('Failed');
            expect(dispatchedEvents[2].detail.type).toBe('danger');
        });

        it('should support error message function', async () => {
            const promise = Promise.reject(new Error('Custom error'));

            try {
                await notify.promise(promise, {
                    loading: 'Loading...',
                    success: 'Done!',
                    error: (err) => `Error: ${err.message}`
                });
            } catch {
                // Expected
            }

            expect(dispatchedEvents[2].detail.message).toBe('Error: Custom error');
        });
    });

    describe('toast alias', () => {
        it('should be the same as notify', () => {
            expect(toast).toBe(notify);
        });
    });
});





