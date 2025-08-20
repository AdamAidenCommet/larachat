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
    <div class="flex flex-col gap-0.5">
        <!-- First line: regular breadcrumbs -->
        <Breadcrumb>
            <BreadcrumbList>
                <template v-for="(item, index) in breadcrumbs.filter(b => !b.subtitle)" :key="index">
                    <BreadcrumbItem class="flex items-center gap-1">
                        <component :is="item.icon" v-if="item.icon" class="h-4 w-4" />
                        <template v-if="index === breadcrumbs.filter(b => !b.subtitle).length - 1">
                            <BreadcrumbPage>{{ item.title }}</BreadcrumbPage>
                        </template>
                        <template v-else>
                            <BreadcrumbLink as-child>
                                <Link :href="item.href ?? '#'">{{ item.title }}</Link>
                            </BreadcrumbLink>
                        </template>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator v-if="index !== breadcrumbs.filter(b => !b.subtitle).length - 1" />
                </template>
            </BreadcrumbList>
        </Breadcrumb>
        
        <!-- Second line: conversation title if present -->
        <div v-if="breadcrumbs.some(b => b.subtitle)" class="text-sm text-muted-foreground pl-7">
            <template v-for="item in breadcrumbs.filter(b => b.subtitle)" :key="item.title">
                {{ item.title }}
            </template>
        </div>
    </div>
</template>
