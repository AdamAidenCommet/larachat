<script lang="ts" setup>
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <ol
    data-slot="breadcrumb-list"
    :class="cn('text-muted-foreground flex items-center gap-1.5 text-sm whitespace-nowrap sm:gap-2.5', props.class)"
  >
    <slot />
  </ol>
</template>
