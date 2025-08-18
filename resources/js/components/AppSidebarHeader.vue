<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="sticky top-0 z-50 flex flex-col sm:h-16 shrink-0 sm:flex-row sm:items-center gap-2 border-b border-sidebar-border/70 bg-background px-6 py-3 sm:py-0 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:sm:h-12 md:px-4"
    >
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" class="flex-1 sm:flex-none" />
            </template>
        </div>
        <div class="ml-0 sm:ml-auto flex items-center gap-2">
            <slot name="actions" />
        </div>
    </header>
</template>
