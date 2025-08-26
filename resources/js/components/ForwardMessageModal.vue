<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useAgents } from '@/composables/useAgents';
import axios from 'axios';
import { Loader2 } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    modelValue: boolean;
    messageContent: string;
    repositoryPath?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

const selectedAgentId = ref<string>('');
const forwarding = ref(false);
const { agents, fetchAgents } = useAgents();

const handleClose = () => {
    selectedAgentId.value = '';
    emit('update:modelValue', false);
};

const forwardMessage = async () => {
    if (!selectedAgentId.value) {
        toast.error('Please select an agent');
        return;
    }

    forwarding.value = true;
    try {
        const response = await axios.post('/api/claude', {
            prompt: props.messageContent,
            agent_id: parseInt(selectedAgentId.value),
            repositoryPath: props.repositoryPath || null,
        });

        toast.success('Message forwarded to new conversation');
        handleClose();

        // Redirect to the new conversation
        if (response.data.conversationId) {
            // Navigate to the Claude page with the session filename
            window.location.href = `/claude/${response.data.sessionFilename}`;
        }
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Failed to forward message');
        console.error('Failed to forward message:', error);
    } finally {
        forwarding.value = false;
    }
};

watch(
    () => props.modelValue,
    (isOpen) => {
        if (isOpen) {
            fetchAgents();
        }
    },
);

onMounted(() => {
    fetchAgents();
});
</script>

<template>
    <Dialog :open="modelValue" @update:open="handleClose">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Forward Message to Agent</DialogTitle>
                <DialogDescription>
                    Select an agent to start a new conversation with this message.
                </DialogDescription>
            </DialogHeader>
            
            <div class="space-y-4">
                <div class="space-y-2">
                    <Label for="agent-select">Select Agent</Label>
                    <Select v-model="selectedAgentId" id="agent-select">
                        <SelectTrigger>
                            <SelectValue placeholder="Choose an agent..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="agent in agents" :key="agent.id" :value="String(agent.id)">
                                {{ agent.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="rounded-lg border bg-muted/50 p-3">
                    <p class="text-xs text-muted-foreground mb-1">Message preview:</p>
                    <p class="text-sm line-clamp-3">{{ messageContent }}</p>
                </div>

                <div class="flex justify-end gap-2">
                    <Button variant="outline" @click="handleClose" :disabled="forwarding">
                        Cancel
                    </Button>
                    <Button @click="forwardMessage" :disabled="forwarding || !selectedAgentId">
                        <Loader2 v-if="forwarding" class="mr-2 h-4 w-4 animate-spin" />
                        Forward
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>