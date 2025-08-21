<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import axios from 'axios';
import { format } from 'date-fns';
import { Loader2, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

const content = ref('');
const saving = ref(false);
const notes = ref<any[]>([]);
const loadingNotes = ref(false);
const deletingNoteId = ref<number | null>(null);

const saveNote = async () => {
    if (!content.value.trim()) {
        toast.error('Please enter content for the note');
        return;
    }

    saving.value = true;
    try {
        const response = await axios.post('/api/notes', {
            title: content.value.substring(0, 50),
            content: content.value,
        });
        toast.success('Note saved');
        content.value = '';
        notes.value.unshift(response.data);
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Failed to save note');
        console.error('Failed to save note:', error);
    } finally {
        saving.value = false;
    }
};

const loadNotes = async () => {
    loadingNotes.value = true;
    try {
        const response = await axios.get('/api/notes');
        notes.value = response.data;
    } catch (error: any) {
        console.error('Failed to load notes:', error);
    } finally {
        loadingNotes.value = false;
    }
};

const deleteNote = async (noteId: number) => {
    deletingNoteId.value = noteId;
    try {
        await axios.delete(`/api/notes/${noteId}`);
        notes.value = notes.value.filter((n) => n.id !== noteId);
        toast.success('Note deleted');
    } catch (error: any) {
        toast.error('Failed to delete note');
        console.error('Failed to delete note:', error);
    } finally {
        deletingNoteId.value = null;
    }
};

const handleClose = () => {
    content.value = '';
    emit('update:modelValue', false);
};

const handleEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && props.modelValue) {
        handleClose();
    }
};

const handleKeyPress = (event: KeyboardEvent) => {
    const target = event.target as HTMLElement;
    const isNoteInput = target.id === 'quick-note-input';

    if (isNoteInput && event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        saveNote();
    }
};

const formattedNotes = computed(() => {
    return notes.value.map((note) => ({
        ...note,
        formattedDate: format(new Date(note.created_at), 'MMM d, h:mm a'),
    }));
});

watch(
    () => props.modelValue,
    (isOpen) => {
        if (isOpen) {
            loadNotes();
            setTimeout(() => {
                const noteInput = document.querySelector('#quick-note-input') as HTMLInputElement;
                noteInput?.focus();
            }, 100);
        }
    },
);

onMounted(() => {
    document.addEventListener('keydown', handleEscape);
    document.addEventListener('keydown', handleKeyPress);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape);
    document.removeEventListener('keydown', handleKeyPress);
});
</script>

<template>
    <Dialog :open="modelValue" @update:open="handleClose">
        <DialogContent class="p-0 sm:max-w-2xl">
            <div class="flex h-[600px] flex-col">
                <div class="border-b p-4">
                    <div class="relative">
                        <Input
                            id="quick-note-input"
                            v-model="content"
                            placeholder="Type your note... (Enter to save, Shift+Enter for new line)"
                            :disabled="saving"
                            class="pr-10"
                        />
                        <Button
                            size="sm"
                            variant="ghost"
                            class="absolute top-1/2 right-1 h-7 -translate-y-1/2 px-2"
                            @click="saveNote"
                            :disabled="saving || !content.trim()"
                        >
                            <Loader2 v-if="saving" class="h-4 w-4 animate-spin" />
                            <span v-else class="text-xs">Save</span>
                        </Button>
                    </div>
                </div>

                <ScrollArea class="flex-1 p-4">
                    <div v-if="loadingNotes" class="flex items-center justify-center py-8">
                        <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
                    </div>
                    <div v-else-if="notes.length === 0" class="py-8 text-center text-muted-foreground">No notes yet</div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="note in formattedNotes"
                            :key="note.id"
                            class="group relative rounded-lg border p-3 transition-colors hover:bg-muted/50"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm break-words whitespace-pre-wrap">{{ note.content }}</p>
                                    <p class="mt-1 text-xs text-muted-foreground">{{ note.formattedDate }}</p>
                                </div>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    class="h-6 w-6 p-0 opacity-0 transition-opacity group-hover:opacity-100"
                                    @click="deleteNote(note.id)"
                                    :disabled="deletingNoteId === note.id"
                                >
                                    <Loader2 v-if="deletingNoteId === note.id" class="h-3 w-3 animate-spin" />
                                    <Trash2 v-else class="h-3 w-3" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </ScrollArea>
            </div>
        </DialogContent>
    </Dialog>
</template>

