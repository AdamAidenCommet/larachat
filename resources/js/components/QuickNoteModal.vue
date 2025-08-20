<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import axios from 'axios';
import { Loader2, StickyNote } from 'lucide-vue-next';
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

const title = ref('');
const content = ref('');
const saving = ref(false);

const saveNote = async () => {
    if (!title.value.trim() && !content.value.trim()) {
        toast.error('Please enter a title or content for the note');
        return;
    }

    saving.value = true;
    try {
        await axios.post('/api/notes', {
            title: title.value || 'Quick Note',
            content: content.value,
        });
        toast.success('Note saved successfully');
        handleClose();
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Failed to save note');
        console.error('Failed to save note:', error);
    } finally {
        saving.value = false;
    }
};

const handleClose = () => {
    title.value = '';
    content.value = '';
    emit('update:modelValue', false);
};

const handleEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && props.modelValue) {
        handleClose();
    }
};

const handleSaveShortcut = (event: KeyboardEvent) => {
    if ((event.metaKey || event.ctrlKey) && event.key === 'Enter' && props.modelValue) {
        event.preventDefault();
        saveNote();
    }
};

watch(
    () => props.modelValue,
    (isOpen) => {
        if (isOpen) {
            setTimeout(() => {
                const titleInput = document.querySelector('#quick-note-title') as HTMLInputElement;
                titleInput?.focus();
            }, 100);
        }
    },
);

onMounted(() => {
    document.addEventListener('keydown', handleEscape);
    document.addEventListener('keydown', handleSaveShortcut);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape);
    document.removeEventListener('keydown', handleSaveShortcut);
});
</script>

<template>
    <Dialog :open="modelValue" @update:open="handleClose">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <StickyNote class="h-5 w-5" />
                    Quick Note
                </DialogTitle>
                <DialogDescription>
                    Create a quick note. Press <kbd class="text-xs">⌘</kbd>+<kbd class="text-xs">Enter</kbd> to save.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <div class="space-y-2">
                    <Label htmlFor="quick-note-title">Title (optional)</Label>
                    <Input
                        id="quick-note-title"
                        v-model="title"
                        placeholder="Enter note title..."
                        :disabled="saving"
                        @keydown.enter.prevent="() => {
                            const textarea = document.querySelector('#quick-note-content') as HTMLTextAreaElement;
                            textarea?.focus();
                        }"
                    />
                </div>
                <div class="space-y-2">
                    <Label htmlFor="quick-note-content">Note</Label>
                    <Textarea
                        id="quick-note-content"
                        v-model="content"
                        placeholder="Type your note here..."
                        class="min-h-[120px] resize-none"
                        :disabled="saving"
                    />
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="handleClose" :disabled="saving">
                    Cancel
                </Button>
                <Button @click="saveNote" :disabled="saving">
                    <Loader2 v-if="saving" class="mr-2 h-4 w-4 animate-spin" />
                    Save Note
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>