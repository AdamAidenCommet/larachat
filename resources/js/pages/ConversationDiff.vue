<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Bot, CheckIcon, ChevronDown, ChevronRight, CopyIcon, FileIcon, Minus, Plus, Settings } from 'lucide-vue-next';
import { computed, onUnmounted, ref, watch } from 'vue';

interface Props {
    conversationId: number;
    diffContent: string;
    conversationTitle: string;
    hasContent: boolean;
    agent?: {
        id: number;
        name: string;
        prompt: string;
    } | null;
}

interface FileDiff {
    fileName: string;
    additions: number;
    deletions: number;
    lines: Array<{
        number: number;
        content: string;
        type: string;
    }>;
}

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => {
    const items: BreadcrumbItem[] = [
        { title: 'Claude', href: '/claude' },
        { title: 'Conversation', href: `/claude/conversation/${props.conversationId}` },
    ];

    if (props.agent) {
        items.push({
            title: props.agent.name,
            icon: Bot,
        });
    }

    items.push({ title: 'Diff', href: `/claude/conversation/${props.conversationId}/diff` });

    return items;
});

const copied = ref(false);
const expandedFiles = ref<Set<string>>(new Set());
const expandAll = ref(false);
const scrollContainers = ref<HTMLElement[]>([]);

const fileDiffs = computed(() => {
    if (!props.diffContent) return [];

    const files: FileDiff[] = [];
    const lines = props.diffContent.split('\n');
    let currentFile: FileDiff | null = null;
    let lineNumber = 0;

    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];

        if (line.startsWith('diff --git')) {
            if (currentFile) {
                files.push(currentFile);
            }

            const fileNameMatch = line.match(/diff --git a\/(.*?) b\/(.*)/);
            const fileName = fileNameMatch ? fileNameMatch[2] : 'Unknown file';

            currentFile = {
                fileName,
                additions: 0,
                deletions: 0,
                lines: [],
            };
            lineNumber = 0;
        } else if (currentFile) {
            lineNumber++;
            currentFile.lines.push({
                number: lineNumber,
                content: line,
                type: line.startsWith('+')
                    ? 'addition'
                    : line.startsWith('-')
                      ? 'deletion'
                      : line.startsWith('@@')
                        ? 'chunk'
                        : line.startsWith('index ') || line.startsWith('---') || line.startsWith('+++')
                          ? 'header'
                          : 'normal',
            });

            if (line.startsWith('+') && !line.startsWith('+++')) {
                currentFile.additions++;
            } else if (line.startsWith('-') && !line.startsWith('---')) {
                currentFile.deletions++;
            }
        }
    }

    if (currentFile) {
        files.push(currentFile);
    }

    return files;
});

// Auto-expand if only one file
watch(
    fileDiffs,
    (newFileDiffs) => {
        if (newFileDiffs.length === 1 && expandedFiles.value.size === 0) {
            expandedFiles.value = new Set([newFileDiffs[0].fileName]);
        }
    },
    { immediate: true },
);

const toggleFile = (fileName: string) => {
    const newSet = new Set(expandedFiles.value);
    if (newSet.has(fileName)) {
        newSet.delete(fileName);
    } else {
        newSet.add(fileName);
    }
    expandedFiles.value = newSet;
};

const toggleAll = () => {
    if (expandAll.value) {
        expandedFiles.value = new Set();
        expandAll.value = false;
    } else {
        expandedFiles.value = new Set(fileDiffs.value.map((f) => f.fileName));
        expandAll.value = true;
    }
};

const copyToClipboard = async () => {
    try {
        await navigator.clipboard.writeText(props.diffContent);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};

// Synchronized horizontal scrolling
const handleScroll = (event: Event) => {
    const target = event.target as HTMLElement;
    const scrollLeft = target.scrollLeft;

    // Sync all scroll containers
    scrollContainers.value.forEach((container) => {
        if (container !== target && container) {
            container.scrollLeft = scrollLeft;
        }
    });
};

const registerScrollContainer = (el: HTMLElement | null) => {
    if (el && !scrollContainers.value.includes(el)) {
        scrollContainers.value.push(el);
        el.addEventListener('scroll', handleScroll);
    }
};

onUnmounted(() => {
    scrollContainers.value.forEach((container) => {
        if (container) {
            container.removeEventListener('scroll', handleScroll);
        }
    });
    scrollContainers.value = [];
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-7xl px-2 py-2">
            <div class="mb-1 flex items-center justify-between">
                <h1 class="text-lg font-bold">Git Diff</h1>
                <DropdownMenu v-if="hasContent">
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" size="sm">
                            <Settings class="h-4 w-4" />
                            <span class="ml-2">Options</span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-48">
                        <DropdownMenuItem v-if="fileDiffs.length > 1" @click="toggleAll" class="cursor-pointer">
                            <ChevronDown v-if="!expandAll" class="mr-2 h-4 w-4" />
                            <ChevronRight v-else class="mr-2 h-4 w-4" />
                            <span>{{ expandAll ? 'Collapse All' : 'Expand All' }}</span>
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="copyToClipboard" class="cursor-pointer">
                            <CopyIcon v-if="!copied" class="mr-2 h-4 w-4" />
                            <CheckIcon v-else class="mr-2 h-4 w-4 text-green-600" />
                            <span>{{ copied ? 'Copied!' : 'Copy Diff' }}</span>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <div v-if="!hasContent" class="py-12 text-center text-muted-foreground">
                <Card>
                    <CardContent class="pt-12">
                        <p class="text-lg">No diff available</p>
                        <p class="mt-2 text-sm">The diff will appear here after Claude makes changes to your project.</p>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="space-y-1">
                <div class="mb-1 text-xs text-muted-foreground">
                    <span class="font-medium">{{ fileDiffs.length }} file{{ fileDiffs.length === 1 ? '' : 's' }} changed</span>
                    <span v-if="fileDiffs.reduce((sum, f) => sum + f.additions, 0) > 0" class="ml-4">
                        <Plus class="inline h-3 w-3 text-green-600" />
                        <span class="text-green-600">{{ fileDiffs.reduce((sum, f) => sum + f.additions, 0) }} additions</span>
                    </span>
                    <span v-if="fileDiffs.reduce((sum, f) => sum + f.deletions, 0) > 0" class="ml-4">
                        <Minus class="inline h-3 w-3 text-red-600" />
                        <span class="text-red-600">{{ fileDiffs.reduce((sum, f) => sum + f.deletions, 0) }} deletions</span>
                    </span>
                </div>

                <Card v-for="file in fileDiffs" :key="file.fileName" class="overflow-hidden">
                    <Collapsible :open="expandedFiles.has(file.fileName)">
                        <div class="relative">
                            <CollapsibleTrigger @click="toggleFile(file.fileName)" class="sticky top-0 z-10 w-full bg-background">
                                <CardHeader class="cursor-pointer px-2 py-1 transition-colors hover:bg-muted/50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <ChevronRight v-if="!expandedFiles.has(file.fileName)" class="h-4 w-4 transition-transform" />
                                            <ChevronDown v-else class="h-4 w-4 transition-transform" />
                                            <FileIcon class="h-4 w-4" />
                                            <span class="font-mono text-xs">{{ file.fileName }}</span>
                                        </div>
                                        <div class="flex items-center gap-3 text-xs">
                                            <span v-if="file.additions > 0" class="flex items-center gap-1 text-green-600">
                                                <Plus class="h-3 w-3" />
                                                {{ file.additions }}
                                            </span>
                                            <span v-if="file.deletions > 0" class="flex items-center gap-1 text-red-600">
                                                <Minus class="h-3 w-3" />
                                                {{ file.deletions }}
                                            </span>
                                        </div>
                                    </div>
                                </CardHeader>
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <CardContent class="p-0">
                                    <div
                                        class="diff-container overflow-x-auto rounded-b-lg border-t border-slate-800 bg-slate-900 dark:bg-slate-950"
                                        :ref="(el) => registerScrollContainer(el as HTMLElement | null)"
                                    >
                                        <div class="min-w-fit font-mono text-xs">
                                            <div
                                                v-for="(line, index) in file.lines"
                                                :key="index"
                                                :class="{
                                                    'border-green-500 bg-green-950/30 sm:border-l-2': line.type === 'addition',
                                                    'border-red-500 bg-red-950/30 sm:border-l-2': line.type === 'deletion',
                                                    'bg-blue-950/50 px-2 py-1 font-semibold': line.type === 'chunk',
                                                    'bg-yellow-950/30 px-2 py-0.5': line.type === 'header',
                                                    'hover:bg-slate-800/30': line.type === 'normal',
                                                }"
                                                class="diff-line flex transition-colors duration-150"
                                            >
                                                <span
                                                    class="hidden w-8 flex-shrink-0 py-0.5 pr-1 text-right text-[10px] text-slate-500 select-none sm:inline-block"
                                                    :class="{
                                                        'bg-slate-900/50': line.type === 'addition' || line.type === 'deletion',
                                                    }"
                                                    >{{ line.type !== 'header' && line.type !== 'chunk' ? line.number : '' }}</span
                                                >
                                                <span
                                                    v-if="line.type === 'addition' || line.type === 'deletion'"
                                                    class="inline-block w-4 flex-shrink-0 py-0.5 text-center text-xs select-none sm:hidden"
                                                    :class="{
                                                        'bg-green-950/50 text-green-400': line.type === 'addition',
                                                        'bg-red-950/50 text-red-400': line.type === 'deletion',
                                                    }"
                                                >
                                                    {{ line.type === 'addition' ? '+' : '-' }}
                                                </span>
                                                <pre class="flex-1 py-0.5 pr-2 whitespace-pre"><code
                                                :class="{
                                                    'text-green-400': line.type === 'addition',
                                                    'text-red-400': line.type === 'deletion',
                                                    'text-blue-400': line.type === 'chunk',
                                                    'text-yellow-500': line.type === 'header',
                                                    'text-slate-300': line.type === 'normal'
                                                }"
                                                class="diff-code-content"
                                            ><span class="hidden sm:inline">{{ line.content }}</span><span class="sm:hidden">{{ line.content.replace(/^[\+\-@]|^(index |---|\+\+\+|diff --git).*/, '') }}</span></code></pre>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </CollapsibleContent>
                        </div>
                    </Collapsible>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
pre {
    white-space: pre;
    word-wrap: normal;
    margin: 0;
}

code {
    display: inline;
    font-family: ui-monospace, SFMono-Regular, 'SF Mono', Consolas, 'Liberation Mono', Menlo, monospace;
}

.flex-1 pre {
    background: transparent;
    padding: 0;
}

/* Synchronized scrolling */
.diff-container {
    max-width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.diff-line {
    min-width: max-content;
}

/* Mobile optimizations */
@media (max-width: 640px) {
    .diff-code-content {
        font-size: 0.7rem;
    }

    .diff-line {
        padding-left: 0.25rem;
    }
}
</style>
