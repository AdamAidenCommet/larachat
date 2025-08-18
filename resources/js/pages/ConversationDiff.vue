<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/vue3';
import { CheckIcon, ChevronDown, ChevronLeft, ChevronRight, CopyIcon, FileIcon, Minus, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    conversationId: number;
    diffContent: string;
    conversationTitle: string;
    hasContent: boolean;
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Claude', href: '/claude' },
    { title: 'Conversation', href: `/claude/conversation/${props.conversationId}` },
    { title: 'Diff', href: `/claude/conversation/${props.conversationId}/diff` },
];

const copied = ref(false);
const expandedFiles = ref<Set<string>>(new Set());
const expandAll = ref(false);

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

    // Auto-expand if only one file
    if (files.length === 1 && expandedFiles.value.size === 0) {
        expandedFiles.value = new Set([files[0].fileName]);
    }

    return files;
});

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

const goBack = () => {
    router.visit(`/claude/conversation/${props.conversationId}`);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-7xl px-2 py-2">
            <div class="mb-2 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="sm" @click="goBack" class="flex items-center gap-2">
                        <ChevronLeft class="h-4 w-4" />
                        Back to Conversation
                    </Button>
                    <h1 class="text-lg font-bold">Git Diff</h1>
                </div>
                <div class="flex items-center gap-2">
                    <Button v-if="hasContent && fileDiffs.length > 1" variant="outline" size="sm" @click="toggleAll" class="flex items-center gap-2">
                        <ChevronDown v-if="!expandAll" class="h-4 w-4" />
                        <ChevronRight v-else class="h-4 w-4" />
                        {{ expandAll ? 'Collapse All' : 'Expand All' }}
                    </Button>
                    <Button v-if="hasContent" variant="outline" size="sm" @click="copyToClipboard" class="flex items-center gap-2">
                        <CopyIcon v-if="!copied" class="h-4 w-4" />
                        <CheckIcon v-else class="h-4 w-4 text-green-600" />
                        {{ copied ? 'Copied!' : 'Copy Diff' }}
                    </Button>
                </div>
            </div>

            <div v-if="!hasContent" class="py-12 text-center text-muted-foreground">
                <Card>
                    <CardContent class="pt-12">
                        <p class="text-lg">No diff available</p>
                        <p class="mt-2 text-sm">The diff will appear here after Claude makes changes to your project.</p>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="space-y-2">
                <div class="mb-2 text-xs text-muted-foreground">
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
                        <CollapsibleTrigger @click="toggleFile(file.fileName)" class="w-full">
                            <CardHeader class="cursor-pointer p-2 transition-colors hover:bg-muted/50">
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
                                <div class="overflow-x-auto rounded-b-lg border-t border-slate-800 bg-slate-900 dark:bg-slate-950">
                                    <div class="font-mono text-xs">
                                        <div
                                            v-for="(line, index) in file.lines"
                                            :key="index"
                                            :class="{
                                                'border-l-2 border-green-500 bg-green-950/30': line.type === 'addition',
                                                'border-l-2 border-red-500 bg-red-950/30': line.type === 'deletion',
                                                'bg-blue-950/50 px-2 py-1 font-semibold': line.type === 'chunk',
                                                'bg-yellow-950/30 px-2 py-0.5': line.type === 'header',
                                                'hover:bg-slate-800/30': line.type === 'normal',
                                            }"
                                            class="flex transition-colors duration-150"
                                        >
                                            <span
                                                class="inline-block w-8 flex-shrink-0 py-0.5 pr-1 text-right text-[10px] text-slate-500 select-none"
                                                :class="{
                                                    'bg-slate-900/50': line.type === 'addition' || line.type === 'deletion',
                                                }"
                                                >{{ line.type !== 'header' && line.type !== 'chunk' ? line.number : '' }}</span
                                            >
                                            <pre class="flex-1 overflow-x-auto py-0.5 pr-2"><code
                                                :class="{
                                                    'text-green-400': line.type === 'addition',
                                                    'text-red-400': line.type === 'deletion',
                                                    'text-blue-400': line.type === 'chunk',
                                                    'text-yellow-500': line.type === 'header',
                                                    'text-slate-300': line.type === 'normal'
                                                }"
                                            >{{ line.content }}</code></pre>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </CollapsibleContent>
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
    overflow-x: auto;
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
</style>
