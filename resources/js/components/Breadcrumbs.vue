<script setup lang="ts">
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
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
</script>

<template>
    <div class="flex flex-col gap-0.5 min-w-0">
        <!-- First line: regular breadcrumbs -->
        <Breadcrumb class="min-w-0">
            <BreadcrumbList>
                <template v-for="(item, index) in breadcrumbs.filter(b => !b.subtitle)" :key="index">
                    <BreadcrumbItem class="flex items-center gap-1 min-w-0">
                        <component :is="item.icon" v-if="item.icon" class="h-4 w-4 shrink-0" />
                        <template v-if="index === breadcrumbs.filter(b => !b.subtitle).length - 1">
                            <BreadcrumbPage class="max-w-[200px] truncate">{{ item.title }}</BreadcrumbPage>
                        </template>
                        <template v-else>
                            <BreadcrumbLink as-child>
                                <Link :href="item.href ?? '#'" class="max-w-[150px] truncate inline-block">{{ item.title }}</Link>
                            </BreadcrumbLink>
                        </template>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator v-if="index !== breadcrumbs.filter(b => !b.subtitle).length - 1" class="shrink-0" />
                </template>
            </BreadcrumbList>
        </Breadcrumb>
        
        <!-- Second line: conversation title if present -->
        <div v-if="breadcrumbs.some(b => b.subtitle)" class="text-sm text-muted-foreground pl-7 truncate">
            <template v-for="item in breadcrumbs.filter(b => b.subtitle)" :key="item.title">
                {{ item.title }}
            </template>
        </div>
    </div>
</template>
