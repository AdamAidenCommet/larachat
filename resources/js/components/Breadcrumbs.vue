<script setup lang="ts">
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { useSidebar } from '@/components/ui/sidebar';
import { Link } from '@inertiajs/vue3';

interface BreadcrumbItemType {
    title: string;
    href?: string;
    icon?: any;
    subtitle?: boolean;
}

defineProps<{
    breadcrumbs: BreadcrumbItemType[];
}>();

const { toggleSidebar } = useSidebar();

const handleBreadcrumbClick = (item: BreadcrumbItemType, event: MouseEvent) => {
    if (item.href === '#') {
        event.preventDefault();
        toggleSidebar();
    }
};
</script>

<template>
    <div class="flex min-w-0 flex-col gap-0.5">
        <!-- First line: regular breadcrumbs -->
        <Breadcrumb class="min-w-0">
            <BreadcrumbList>
                <template v-for="(item, index) in breadcrumbs.filter((b) => !b.subtitle)" :key="index">
                    <BreadcrumbItem class="flex min-w-0 items-center gap-1">
                        <component :is="item.icon" v-if="item.icon" class="h-4 w-4 shrink-0" />
                        <template v-if="index === breadcrumbs.filter((b) => !b.subtitle).length - 1">
                            <BreadcrumbPage
                                class="max-w-[200px] truncate"
                                :class="{ 'cursor-pointer hover:text-foreground/80': item.href === '#' }"
                                @click="handleBreadcrumbClick(item, $event)"
                                >{{ item.title }}</BreadcrumbPage
                            >
                        </template>
                        <template v-else>
                            <BreadcrumbLink as-child>
                                <Link
                                    :href="item.href ?? '#'"
                                    class="inline-block max-w-[150px] truncate"
                                    @click="(e: MouseEvent) => handleBreadcrumbClick(item, e)"
                                    >{{ item.title }}</Link
                                >
                            </BreadcrumbLink>
                        </template>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator v-if="index !== breadcrumbs.filter((b) => !b.subtitle).length - 1" class="shrink-0" />
                </template>
            </BreadcrumbList>
        </Breadcrumb>

        <!-- Second line: conversation title if present -->
        <div v-if="breadcrumbs.some((b) => b.subtitle)" class="truncate text-sm text-muted-foreground">
            <template v-for="item in breadcrumbs.filter((b) => b.subtitle)" :key="item.title">
                {{ item.title }}
            </template>
        </div>
    </div>
</template>
