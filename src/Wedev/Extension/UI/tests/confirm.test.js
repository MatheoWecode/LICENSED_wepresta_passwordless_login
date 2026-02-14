/**
 * =============================================================================
 * WEDEV UI - Tests: Confirmation Helper
 * =============================================================================
 * Tests unitaires pour le module de confirmation.
 * =============================================================================
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';

// Import du module à tester
import { 
    confirm, 
    confirmDelete, 
    confirmSave, 
    confirmLeave, 
    confirmDisable 
} from '../Assets/js/admin/utils/confirm.js';

describe('Confirmation Helpers', () => {
    let dispatchedEvents = [];

    beforeEach(() => {
        dispatchedEvents = [];
        
        // Mock de window.dispatchEvent
        vi.spyOn(window, 'dispatchEvent').mockImplementation((event) => {
            dispatchedEvents.push(event);
        });
    });

    describe('confirm()', () => {
        it('should dispatch wedev-confirm event with default options', () => {
            confirm({});

            expect(dispatchedEvents.length).toBe(1);
            expect(dispatchedEvents[0].type).toBe('wedev-confirm');
            expect(dispatchedEvents[0].detail.title).toBe('Confirmation');
            expect(dispatchedEvents[0].detail.message).toBe('Are you sure?');
        });

        it('should dispatch wedev-confirm event with custom options', () => {
            confirm({
                title: 'Custom Title',
                message: 'Custom message',
                confirmLabel: 'Yes',
                cancelLabel: 'No'
            });

            const detail = dispatchedEvents[0].detail;
            expect(detail.title).toBe('Custom Title');
            expect(detail.message).toBe('Custom message');
            expect(detail.confirmLabel).toBe('Yes');
            expect(detail.cancelLabel).toBe('No');
        });

        it('should set confirmClass to btn-danger when dangerous is true', () => {
            confirm({ dangerous: true });

            expect(dispatchedEvents[0].detail.confirmClass).toBe('btn-danger');
        });

        it('should resolve true when onConfirm is called', async () => {
            const promise = confirm({});
            
            // Simulate confirmation
            dispatchedEvents[0].detail.onConfirm();

            const result = await promise;
            expect(result).toBe(true);
        });

        it('should resolve false when onCancel is called', async () => {
            const promise = confirm({});
            
            // Simulate cancellation
            dispatchedEvents[0].detail.onCancel();

            const result = await promise;
            expect(result).toBe(false);
        });
    });

    describe('confirmDelete()', () => {
        it('should dispatch delete confirmation with item name', () => {
            confirmDelete('Product #123');

            const detail = dispatchedEvents[0].detail;
            expect(detail.title).toBe('Delete confirmation');
            expect(detail.message).toContain('Product #123');
            expect(detail.confirmLabel).toBe('Delete');
            expect(detail.confirmClass).toBe('btn-danger');
        });

        it('should use default item name when not provided', () => {
            confirmDelete();

            expect(dispatchedEvents[0].detail.message).toContain('this item');
        });
    });

    describe('confirmSave()', () => {
        it('should dispatch save confirmation', () => {
            confirmSave();

            const detail = dispatchedEvents[0].detail;
            expect(detail.title).toBe('Save changes');
            expect(detail.confirmLabel).toBe('Save');
        });

        it('should accept custom message', () => {
            confirmSave('Custom save message');

            expect(dispatchedEvents[0].detail.message).toBe('Custom save message');
        });
    });

    describe('confirmLeave()', () => {
        it('should dispatch leave confirmation', () => {
            confirmLeave();

            const detail = dispatchedEvents[0].detail;
            expect(detail.title).toBe('Unsaved changes');
            expect(detail.message).toContain('unsaved changes');
            expect(detail.confirmLabel).toBe('Leave');
            expect(detail.cancelLabel).toBe('Stay');
            expect(detail.confirmClass).toBe('btn-danger');
        });
    });

    describe('confirmDisable()', () => {
        it('should dispatch disable confirmation with item name', () => {
            confirmDisable('Feature X');

            const detail = dispatchedEvents[0].detail;
            expect(detail.title).toBe('Disable confirmation');
            expect(detail.message).toContain('Feature X');
            expect(detail.confirmLabel).toBe('Disable');
        });
    });
});





