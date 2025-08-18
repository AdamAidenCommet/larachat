<script setup lang="ts">
import { MessageSquare } from 'lucide-vue-next';
</script>

<template>
    <div class="relative flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
        <MessageSquare class="size-6 text-white dark:text-black" />
        <span class="absolute text-[8px] font-bold text-white dark:text-black">&lt;/&gt;</span>
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">LaraChat</span>
    </div>
</template>
