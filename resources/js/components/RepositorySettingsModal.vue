<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import axios from 'axios';
import { ref, watch } from 'vue';

const props = defineProps<{
    modelValue: boolean;
    repositoryId: number;
    deployScript?: string | null;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

const deployScriptContent = ref(props.deployScript || '');
const isSaving = ref(false);
const error = ref('');

watch(() => props.modelValue, (newValue) => {
    if (newValue) {
        deployScriptContent.value = props.deployScript || '';
        error.value = '';
    }
});

const saveSettings = async () => {
    isSaving.value = true;
    error.value = '';
    
    try {
        await axios.put(`/api/repositories/${props.repositoryId}/settings`, {
            deploy_script: deployScriptContent.value
        });
        emit('update:modelValue', false);
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Failed to save settings';
    } finally {
        isSaving.value = false;
    }
};
</script>

<template>
    <Dialog :open="modelValue" @update:open="emit('update:modelValue', $event)">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>Repository Settings</DialogTitle>
                <DialogDescription>
                    Configure deployment scripts and other repository settings.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <div class="space-y-2">
                    <Label for="deploy-script">Deploy Script</Label>
                    <Textarea
                        id="deploy-script"
                        v-model="deployScriptContent"
                        placeholder="Enter deployment script (e.g., npm run build && npm run deploy)"
                        class="min-h-[200px] font-mono text-sm"
                    />
                    <p class="text-sm text-muted-foreground">
                        This script will be saved but not executed automatically. You can use it as a reference for deployment.
                    </p>
                </div>

                <div v-if="error" class="text-sm text-destructive">
                    {{ error }}
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="emit('update:modelValue', false)" :disabled="isSaving">
                    Cancel
                </Button>
                <Button @click="saveSettings" :disabled="isSaving">
                    {{ isSaving ? 'Saving...' : 'Save Settings' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>