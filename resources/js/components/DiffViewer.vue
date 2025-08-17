<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import axios from 'axios';
import { GitCompare, X, FileText, Plus, Minus, AlertCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    conversationId: number | null;
}>();

const emit = defineEmits<{
    close: [];
}>();

const isLoading = ref(false);
const diffData = ref<{
    diff: string;
    hasChanges: boolean;
    status: string;
    projectDirectory?: string;
    error?: string;
} | null>(null);
const error = ref<string | null>(null);

const loadDiff = async () => {
    if (!props.conversationId) return;
    
    isLoading.value = true;
    error.value = null;
    
    try {
        const response = await axios.get(`/api/conversations/${props.conversationId}/diff`);
        diffData.value = response.data;
    } catch (err: any) {
        error.value = err.response?.data?.error || 'Failed to load diff';
        console.error('Error loading diff:', err);
    } finally {
        isLoading.value = false;
    }
};

const parsedDiff = computed(() => {
    if (!diffData.value?.diff) return [];
    
    const lines = diffData.value.diff.split('\n');
    const files: Array<{
        filename: string;
        oldFile: string;
        newFile: string;
        chunks: Array<{
            header: string;
            lines: Array<{
                type: 'add' | 'remove' | 'context' | 'info';
                content: string;
                lineNumber?: { old?: number; new?: number };
            }>;
        }>;
    }> = [];
    
    let currentFile: typeof files[0] | null = null;
    let currentChunk: typeof files[0]['chunks'][0] | null = null;
    let oldLineNum = 0;
    let newLineNum = 0;
    
    for (const line of lines) {
        if (line.startsWith('diff --git')) {
            // New file
            const match = line.match(/diff --git a\/(.*) b\/(.*)/);
            if (match) {
                currentFile = {
                    filename: match[2],
                    oldFile: match[1],
                    newFile: match[2],
                    chunks: [],
                };
                files.push(currentFile);
                currentChunk = null;
            }
        } else if (line.startsWith('@@')) {
            // New chunk
            const match = line.match(/@@ -(\d+)(?:,\d+)? \+(\d+)(?:,\d+)? @@(.*)/);
            if (match && currentFile) {
                oldLineNum = parseInt(match[1], 10);
                newLineNum = parseInt(match[2], 10);
                currentChunk = {
                    header: line,
                    lines: [],
                };
                currentFile.chunks.push(currentChunk);
            }
        } else if (currentChunk) {
            if (line.startsWith('+')) {
                currentChunk.lines.push({
                    type: 'add',
                    content: line.substring(1),
                    lineNumber: { new: newLineNum++ },
                });
            } else if (line.startsWith('-')) {
                currentChunk.lines.push({
                    type: 'remove',
                    content: line.substring(1),
                    lineNumber: { old: oldLineNum++ },
                });
            } else if (line.startsWith(' ')) {
                currentChunk.lines.push({
                    type: 'context',
                    content: line.substring(1),
                    lineNumber: { old: oldLineNum++, new: newLineNum++ },
                });
            } else if (line.startsWith('\\')) {
                // Info line (e.g., "\ No newline at end of file")
                currentChunk.lines.push({
                    type: 'info',
                    content: line,
                });
            }
        }
    }
    
    return files;
});

const statusFiles = computed(() => {
    if (!diffData.value?.status) return [];
    
    return diffData.value.status.split('\n').filter(line => line.trim()).map(line => {
        const [status, ...filenameParts] = line.trim().split(/\s+/);
        const filename = filenameParts.join(' ');
        return { status, filename };
    });
});

// Load diff when component is mounted
loadDiff();
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="relative h-[90vh] w-[90vw] max-w-7xl rounded-lg bg-background shadow-lg">
            <!-- Header -->
            <div class="flex items-center justify-between border-b px-4 py-3">
                <div class="flex items-center gap-2">
                    <GitCompare class="h-5 w-5" />
                    <h2 class="text-lg font-semibold">Git Diff View</h2>
                    <span v-if="diffData?.projectDirectory" class="text-sm text-muted-foreground">
                        {{ diffData.projectDirectory.split('/').pop() }}
                    </span>
                </div>
                <Button @click="emit('close')" variant="ghost" size="icon">
                    <X class="h-4 w-4" />
                </Button>
            </div>
            
            <!-- Content -->
            <ScrollArea class="h-[calc(90vh-4rem)]">
                <div class="p-4">
                    <!-- Loading state -->
                    <div v-if="isLoading" class="space-y-4">
                        <Skeleton class="h-4 w-full" />
                        <Skeleton class="h-4 w-3/4" />
                        <Skeleton class="h-32 w-full" />
                    </div>
                    
                    <!-- Error state -->
                    <div v-else-if="error" class="flex items-center gap-2 text-destructive">
                        <AlertCircle class="h-5 w-5" />
                        <span>{{ error }}</span>
                    </div>
                    
                    <!-- No changes -->
                    <div v-else-if="diffData && !diffData.hasChanges" class="text-center text-muted-foreground">
                        <FileText class="mx-auto mb-2 h-12 w-12 opacity-50" />
                        <p>No changes detected in this conversation's project directory.</p>
                    </div>
                    
                    <!-- Diff content -->
                    <div v-else-if="diffData" class="space-y-6">
                        <!-- File status summary -->
                        <div v-if="statusFiles.length > 0" class="rounded-lg border bg-muted/30 p-3">
                            <h3 class="mb-2 text-sm font-semibold">Changed Files</h3>
                            <div class="space-y-1 font-mono text-xs">
                                <div v-for="file in statusFiles" :key="file.filename" class="flex items-center gap-2">
                                    <span 
                                        :class="cn(
                                            'font-bold',
                                            file.status.includes('M') && 'text-yellow-600 dark:text-yellow-400',
                                            file.status.includes('A') && 'text-green-600 dark:text-green-400',
                                            file.status.includes('D') && 'text-red-600 dark:text-red-400',
                                        )"
                                    >
                                        {{ file.status }}
                                    </span>
                                    <span>{{ file.filename }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Diff display -->
                        <div v-for="file in parsedDiff" :key="file.filename" class="overflow-hidden rounded-lg border">
                            <div class="bg-muted px-4 py-2">
                                <h3 class="font-mono text-sm font-semibold">{{ file.filename }}</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full font-mono text-xs">
                                    <tbody>
                                        <template v-for="chunk in file.chunks" :key="chunk.header">
                                            <tr>
                                                <td colspan="3" class="bg-blue-50 px-4 py-1 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400">
                                                    {{ chunk.header }}
                                                </td>
                                            </tr>
                                            <tr v-for="(line, idx) in chunk.lines" :key="idx">
                                                <td 
                                                    class="w-10 select-none px-2 text-right text-muted-foreground"
                                                    :class="{
                                                        'bg-red-50 dark:bg-red-950/20': line.type === 'remove',
                                                        'bg-green-50 dark:bg-green-950/20': line.type === 'add',
                                                    }"
                                                >
                                                    {{ line.lineNumber?.old || '' }}
                                                </td>
                                                <td 
                                                    class="w-10 select-none px-2 text-right text-muted-foreground"
                                                    :class="{
                                                        'bg-red-50 dark:bg-red-950/20': line.type === 'remove',
                                                        'bg-green-50 dark:bg-green-950/20': line.type === 'add',
                                                    }"
                                                >
                                                    {{ line.lineNumber?.new || '' }}
                                                </td>
                                                <td 
                                                    class="px-4 py-0.5"
                                                    :class="{
                                                        'bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400': line.type === 'remove',
                                                        'bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400': line.type === 'add',
                                                        'text-gray-500 dark:text-gray-400': line.type === 'info',
                                                    }"
                                                >
                                                    <span v-if="line.type === 'add'" class="select-none">+</span>
                                                    <span v-else-if="line.type === 'remove'" class="select-none">-</span>
                                                    <span v-else class="select-none"> </span>
                                                    <span class="whitespace-pre">{{ line.content }}</span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Raw diff fallback -->
                        <div v-if="parsedDiff.length === 0 && diffData.diff" class="rounded-lg border bg-muted/30 p-4">
                            <pre class="overflow-x-auto font-mono text-xs">{{ diffData.diff }}</pre>
                        </div>
                    </div>
                </div>
            </ScrollArea>
        </div>
    </div>
</template>