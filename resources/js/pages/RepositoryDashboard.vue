<script setup lang="ts">
import EnvFileModal from '@/components/EnvFileModal.vue';
import RepositorySettingsModal from '@/components/RepositorySettingsModal.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useAgents } from '@/composables/useAgents';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { Activity, ArrowRight, Bot, FileCode, FileKey2, Lightbulb, MessageSquare, Send, Settings, Sparkles, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
    repository: {
        id: number;
        name: string;
        url: string;
        branch?: string;
        path: string;
        has_hot_folder: boolean;
        deploy_script?: string | null;
        created_at: string;
        updated_at: string;
        is_blank?: boolean;
    };
    stats?: {
        files_count: number;
        directories_count: number;
        total_size: string;
        last_commit?: string;
    };
    recent_conversations?: Array<{
        id: number;
        title: string;
        created_at: string;
    }>;
}>();

const messageInput = ref('');
const showEnvModal = ref(false);
const showSettingsModal = ref(false);
const showDeleteModal = ref(false);
const deleteConfirmation = ref('');
const isDeleting = ref(false);
const selectedMode = ref<'ask' | 'plan' | 'code'>('ask');
const selectedAgentId = ref<string>('');

const { agents, fetchAgents } = useAgents();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [{ title: props.repository.name, href: '#' }]);

onMounted(async () => {
    await fetchAgents();
    // Auto-select first agent if available
    if (agents.value && agents.value.length > 0) {
        selectedAgentId.value = String(agents.value[0].id);
    }
});

const startChatWithMessage = (message?: string) => {
    const finalMessage = message || messageInput.value.trim();
    if (finalMessage && selectedAgentId.value) {
        // Use router.get with data to properly send parameters
        // For blank repositories, send empty string or don't send repository parameter
        const params: any = {
            message: finalMessage,
            repository: props.repository.is_blank ? '' : props.repository.name,
            mode: selectedMode.value === 'code' ? 'bypassPermissions' : selectedMode.value === 'ask' ? 'plan' : selectedMode.value === 'plan' ? 'plan' : 'ask',
            agent_id: selectedAgentId.value,
        };

        router.get('/claude/new', params);
    }
};

const quickMessages = [
    { text: 'Show this week tasks', icon: '📋' },
    { text: 'Let me ask you about', icon: '💬' },
    { text: 'Review recent changes', icon: '🔍' },
    { text: 'Help me debug an issue', icon: '🐛' },
];

const handleDelete = async () => {
    // Don't allow deletion of blank repository
    if (props.repository.is_blank) {
        alert('Cannot delete blank repository');
        return;
    }

    if (deleteConfirmation.value !== props.repository.name) {
        return;
    }

    isDeleting.value = true;
    try {
        await axios.delete(`/api/repositories/${props.repository.id}`);
        router.visit('/repositories');
    } catch (error) {
        console.error('Failed to delete repository:', error);
        alert('Failed to delete repository. Please try again.');
    } finally {
        isDeleting.value = false;
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <template #header-actions>
            <DropdownMenu v-if="!repository.is_blank">
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" size="sm">
                        <Settings class="h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem @click="showEnvModal = true">
                        <FileKey2 class="mr-2 h-4 w-4" />
                        Environment
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="showSettingsModal = true">
                        <Settings class="mr-2 h-4 w-4" />
                        Repository Settings
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="showDeleteModal = true" class="text-destructive focus:text-destructive">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete Repository
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </template>

        <div class="container mx-auto py-6">
            <!-- Main CTA Section -->
            <div class="flex min-h-[60vh] flex-col items-center justify-center">
                <!-- Conversation Starter -->
                <div class="w-full max-w-2xl space-y-6">
                    <div class="text-center">
                        <div class="mb-2 flex items-center justify-center">
                            <Sparkles class="h-8 w-8 text-primary" />
                        </div>
                        <h2 class="text-2xl font-semibold">Start a conversation</h2>
                        <p class="mt-2 text-muted-foreground" v-if="repository.is_blank">Start a new conversation without a specific codebase</p>
                        <p class="mt-2 text-muted-foreground" v-else>Ask Claude about your {{ repository.name }} codebase</p>
                    </div>

                    <!-- Main Input -->
                    <div class="space-y-2">
                        <div class="relative">
                            <Textarea
                                v-model="messageInput"
                                placeholder="Type your message or question..."
                                @keydown.meta.enter.prevent="startChatWithMessage()"
                                @keydown.alt.enter.prevent="startChatWithMessage()"
                                class="scrollbar-thin scrollbar-track-transparent scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-700 hover:scrollbar-thumb-gray-400 dark:hover:scrollbar-thumb-gray-600 max-h-[300px] min-h-[100px] resize-y overflow-y-auto pr-14 pl-5 text-base"
                            />
                            <Button
                                @click="startChatWithMessage()"
                                :disabled="!messageInput.trim() || !selectedAgentId"
                                size="icon"
                                class="absolute right-2 bottom-2 h-10 w-10 rounded-full"
                            >
                                <Send class="h-4 w-4" />
                            </Button>
                        </div>

                        <!-- Agent and Mode Selection -->
                        <div class="flex items-center justify-between gap-3">
                            <!-- Agent Selection (Left) -->
                            <Select v-model="selectedAgentId">
                                <SelectTrigger class="w-[180px]">
                                    <div class="flex items-center gap-2">
                                        <Bot class="h-4 w-4" />
                                        <SelectValue placeholder="Select agent" />
                                    </div>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="agent in agents" :key="agent.id" :value="String(agent.id)">
                                        {{ agent.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>

                            <!-- Mode Selection (Right) -->
                            <div class="inline-flex rounded-lg border p-1">
                                <Button @click="selectedMode = 'ask'" :variant="selectedMode === 'ask' ? 'default' : 'ghost'" size="sm" class="gap-2">
                                    <MessageSquare class="h-4 w-4" />
                                    Ask
                                </Button>
                                <Button
                                    @click="selectedMode = 'plan'"
                                    :variant="selectedMode === 'plan' ? 'default' : 'ghost'"
                                    size="sm"
                                    class="gap-2"
                                >
                                    <Lightbulb class="h-4 w-4" />
                                    Plan
                                </Button>
                                <Button
                                    @click="selectedMode = 'code'"
                                    :variant="selectedMode === 'code' ? 'default' : 'ghost'"
                                    size="sm"
                                    class="gap-2"
                                >
                                    <FileCode class="h-4 w-4" />
                                    Code
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Messages -->
                    <div class="space-y-3">
                        <p class="text-center text-sm text-muted-foreground">Or start with:</p>
                        <div class="flex flex-wrap justify-center gap-2">
                            <Button
                                v-for="(message, index) in quickMessages"
                                :key="index"
                                @click="startChatWithMessage(message.text)"
                                variant="outline"
                                size="sm"
                                class="group"
                            >
                                <span class="mr-2">{{ message.icon }}</span>
                                {{ message.text }}
                                <ArrowRight class="ml-2 h-3 w-3 transition-transform group-hover:translate-x-0.5" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Minimal Stats -->
                <div class="mt-12 flex items-center gap-8 text-sm text-muted-foreground" v-if="stats">
                    <div class="flex items-center gap-2">
                        <FileCode class="h-4 w-4" />
                        <span>{{ stats.files_count }} files</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <MessageSquare class="h-4 w-4" />
                        <span>{{ recent_conversations?.length || 0 }} conversations</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Activity class="h-4 w-4" />
                        <span>{{ stats.total_size }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Environment Variables Modal -->
        <EnvFileModal v-model="showEnvModal" :repository-id="repository.id" />

        <!-- Repository Settings Modal -->
        <RepositorySettingsModal v-model="showSettingsModal" :repository-id="repository.id" :deploy-script="repository.deploy_script" />

        <!-- Delete Confirmation Modal -->
        <Dialog v-model:open="showDeleteModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Repository</DialogTitle>
                    <DialogDescription>
                        This action cannot be undone. This will permanently delete the
                        <strong>{{ repository.name }}</strong> repository and all associated data.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="delete-confirm">
                            Type <strong>{{ repository.name }}</strong> to confirm
                        </Label>
                        <Input id="delete-confirm" v-model="deleteConfirmation" placeholder="Enter repository name" @keydown.enter="handleDelete" />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showDeleteModal = false" :disabled="isDeleting"> Cancel </Button>
                    <Button variant="destructive" @click="handleDelete" :disabled="deleteConfirmation !== repository.name || isDeleting">
                        {{ isDeleting ? 'Deleting...' : 'Delete Repository' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
