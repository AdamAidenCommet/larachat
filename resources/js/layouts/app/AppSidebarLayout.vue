<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import PageTransition from '@/components/PageTransition.vue';
import QuickNoteModal from '@/components/QuickNoteModal.vue';
import type { BreadcrumbItemType } from '@/types';
import { ref, onMounted, onUnmounted } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const showQuickNoteModal = ref(false);

const handleGlobalKeydown = (event: KeyboardEvent) => {
    // Check for Cmd+Option+N (Mac) or Ctrl+Alt+N (Windows/Linux)
    const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    const cmdOrCtrl = isMac ? event.metaKey : event.ctrlKey;
    
    if (cmdOrCtrl && event.altKey && event.key.toLowerCase() === 'n') {
        event.preventDefault();
        event.stopPropagation();
        showQuickNoteModal.value = true;
    }
};

onMounted(() => {
    // Use capture phase to intercept the event before other handlers
    document.addEventListener('keydown', handleGlobalKeydown, true);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleGlobalKeydown, true);
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
