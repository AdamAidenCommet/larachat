import { onMounted, onUnmounted, Ref } from 'vue';

export interface KeyboardShortcut {
    key: string;
    modifiers?: {
        ctrl?: boolean;
        meta?: boolean;
        alt?: boolean;
        shift?: boolean;
    };
    handler: (event: KeyboardEvent) => void;
    description?: string;
}

export function useKeyboardShortcut(
    shortcut: KeyboardShortcut | KeyboardShortcut[],
    options: {
        enabled?: Ref<boolean> | boolean;
        preventDefault?: boolean;
        stopPropagation?: boolean;
        capture?: boolean;
    } = {}
) {
    const shortcuts = Array.isArray(shortcut) ? shortcut : [shortcut];
    const { 
        enabled = true, 
        preventDefault = true, 
        stopPropagation = true,
        capture = true 
    } = options;

    const handleKeyboard = (event: KeyboardEvent) => {
        // Check if shortcuts are enabled
        const isEnabled = typeof enabled === 'boolean' ? enabled : enabled.value;
        if (!isEnabled) return;

        // Check each shortcut
        for (const s of shortcuts) {
            const modifiers = s.modifiers || {};
            
            // Check if all required modifiers are pressed
            const modifiersMatch = 
                (modifiers.ctrl === undefined || modifiers.ctrl === event.ctrlKey) &&
                (modifiers.meta === undefined || modifiers.meta === event.metaKey) &&
                (modifiers.alt === undefined || modifiers.alt === event.altKey) &&
                (modifiers.shift === undefined || modifiers.shift === event.shiftKey);

            // Check if the key matches (case-insensitive)
            const keyMatches = 
                event.key.toLowerCase() === s.key.toLowerCase() ||
                event.code.toLowerCase() === `key${s.key}`.toLowerCase();

            if (modifiersMatch && keyMatches) {
                if (preventDefault) {
                    event.preventDefault();
                }
                if (stopPropagation) {
                    event.stopPropagation();
                    event.stopImmediatePropagation();
                }
                
                console.log(`[Keyboard] Shortcut triggered: ${s.description || s.key}`);
                s.handler(event);
                return;
            }
        }
    };

    onMounted(() => {
        // Register on multiple targets for maximum compatibility
        const targets = [document, window, document.body];
        const events = ['keydown', 'keyup'];
        
        for (const target of targets) {
            if (target) {
                for (const eventType of events) {
                    target.addEventListener(eventType, handleKeyboard as EventListener, { 
                        capture, 
                        passive: false 
                    });
                }
            }
        }
        
        console.log('[Keyboard] Shortcuts registered:', shortcuts.map(s => s.description || s.key).join(', '));
    });

    onUnmounted(() => {
        // Clean up all listeners
        const targets = [document, window, document.body];
        const events = ['keydown', 'keyup'];
        
        for (const target of targets) {
            if (target) {
                for (const eventType of events) {
                    target.removeEventListener(eventType, handleKeyboard as EventListener, capture);
                }
            }
        }
        
        console.log('[Keyboard] Shortcuts unregistered');
    });
}

// Platform detection helper
export function getPlatformModifier() {
    const isMac = navigator.userAgent.includes('Mac') || 
                  navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    return isMac ? 'meta' : 'ctrl';
}