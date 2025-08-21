<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import PageTransition from '@/components/PageTransition.vue';
import QuickNoteModal from '@/components/QuickNoteModal.vue';
import { getPlatformModifier, useKeyboardShortcut } from '@/composables/useKeyboardShortcuts';
import type { BreadcrumbItemType } from '@/types';
import { onMounted, ref } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const showQuickNoteModal = ref(false);

// Get platform-specific modifier key
const platformMod = getPlatformModifier();

// Register keyboard shortcuts using the robust composable
useKeyboardShortcut([
    {
        key: 'n',
        modifiers: {
            [platformMod]: true,
            alt: true,
        },
        handler: () => {
            showQuickNoteModal.value = !showQuickNoteModal.value;
        },
        description: 'Toggle Quick Note (CMD/CTRL+ALT+N)',
    },
]);

// Expose globally for debugging and testing
onMounted(() => {
    if (typeof window !== 'undefined') {
        (window as any).__openQuickNote = () => {
            console.log('[QuickNote] Toggling via global function');
            showQuickNoteModal.value = !showQuickNoteModal.value;
        };
        (window as any).__quickNoteModal = showQuickNoteModal;

        console.log('[QuickNote] Quick Note shortcut initialized');
        console.log('  Shortcut: CMD/CTRL + ALT + N (toggle)');
        console.log('  Debug: window.__openQuickNote()');
    }
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar @open-quick-note="showQuickNoteModal = true" />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs">
                <template #actions>
                    <slot name="header-actions" />
                </template>
            </AppSidebarHeader>
            <PageTransition>
                <slot />
            </PageTransition>
        </AppContent>
    </AppShell>
    <QuickNoteModal v-model="showQuickNoteModal" />
</template>
