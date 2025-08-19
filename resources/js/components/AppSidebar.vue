<script setup lang="ts">
import NavUser from '@/components/NavUser.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { Textarea } from '@/components/ui/textarea';
import { useAgents } from '@/composables/useAgents';
import { useConversations } from '@/composables/useConversations';
import { useRepositories } from '@/composables/useRepositories';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bot, FileText, GitBranch, Loader2, MessageSquarePlus, Plus, Users } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();
const { conversations, fetchConversations, cleanup } = useConversations();
const { repositories, fetchRepositories, cloneRepository, loading } = useRepositories();
const { agents, fetchAgents, createAgent, showCreateDialog: showCreateAgentDialog, createError: createAgentError } = useAgents();
const { isMobile, setOpenMobile, open } = useSidebar();

const showCloneDialog = ref(false);
const repositoryUrl = ref('');
const branch = ref('');
const cloneError = ref('');
const newAgentName = ref('');
const newAgentPrompt = ref('');
const creatingAgent = ref(false);
const sidebarRefreshInterval = ref<number | null>(null);
const lastMobileOpenState = ref(false);

// Function to start sidebar refresh interval
const startSidebarRefresh = () => {
    // Clear existing interval if any
    if (sidebarRefreshInterval.value) {
        clearInterval(sidebarRefreshInterval.value);
    }

    // Start new interval for 10 seconds
    sidebarRefreshInterval.value = window.setInterval(() => {
        // Only refresh if sidebar is visible
        if (open.value) {
            fetchConversations(true, true); // Silent forced refresh
        }
    }, 10000); // Refresh every 10 seconds
};

// Function to stop sidebar refresh interval
const stopSidebarRefresh = () => {
    if (sidebarRefreshInterval.value) {
        clearInterval(sidebarRefreshInterval.value);
        sidebarRefreshInterval.value = null;
    }
};

// Watch for sidebar visibility changes
const handleSidebarVisibilityChange = () => {
    if (open.value) {
        // Sidebar is now visible
        if (isMobile.value && !lastMobileOpenState.value) {
            // On mobile, refresh immediately when sidebar opens
            fetchConversations(true, true);
        }
        // Start the refresh interval
        startSidebarRefresh();
    } else {
        // Sidebar is hidden, stop refreshing
        stopSidebarRefresh();
    }

    // Track mobile open state
    if (isMobile.value) {
        lastMobileOpenState.value = open.value;
    }
};

onMounted(async () => {
    await fetchRepositories();
    await fetchAgents();
    await fetchConversations(false, true); // Force initial fetch

    // Set up visibility-based refresh
    if (open.value) {
        startSidebarRefresh();
    }

    // Watch for sidebar visibility changes
    const unwatch = watch(() => open.value, handleSidebarVisibilityChange);

    // Store unwatch function for cleanup
    (window as any).__sidebarUnwatch = unwatch;
});

onUnmounted(() => {
    cleanup(); // Clean up the refresh interval when component unmounts

    // Clean up sidebar refresh interval
    stopSidebarRefresh();

    // Clean up watcher
    if ((window as any).__sidebarUnwatch) {
        (window as any).__sidebarUnwatch();
        delete (window as any).__sidebarUnwatch;
    }
});

const handleCloneRepository = async () => {
    cloneError.value = '';
    try {
        await cloneRepository(repositoryUrl.value, branch.value || undefined);
        showCloneDialog.value = false;
        repositoryUrl.value = '';
        branch.value = '';
    } catch (err: any) {
        cloneError.value = err.response?.data?.error || err.response?.data?.message || 'Failed to clone repository';
    }
};

const handleRepositoryClick = (repositoryName: string) => {
    if (isMobile.value) {
        setOpenMobile(false);
    }
    router.visit(`/repository?repository=${encodeURIComponent(repositoryName)}`, {
        preserveScroll: true,
        preserveState: true,
    });
};

const handleConversationClick = () => {
    if (isMobile.value) {
        setOpenMobile(false);
    }
};

const handleLinkClick = () => {
    if (isMobile.value) {
        setOpenMobile(false);
    }
};

const handleCreateAgent = async () => {
    creatingAgent.value = true;
    try {
        await createAgent(newAgentName.value, newAgentPrompt.value);
        newAgentName.value = '';
        newAgentPrompt.value = '';
        showCreateAgentDialog.value = false;
    } catch {
        // Error is handled in the composable
    } finally {
        creatingAgent.value = false;
    }
};

</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('claude')" :preserve-scroll="true" :preserve-state="true" @click="handleLinkClick">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup class="px-2 py-0">
                <div class="flex items-center justify-between">
                    <SidebarGroupLabel>Agents</SidebarGroupLabel>
                    <button
                        class="flex h-5 w-5 items-center justify-center rounded-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                        @click="showCreateAgentDialog = true"
                    >
                        <Plus class="h-3 w-3" />
                    </button>
                </div>
                <SidebarMenu>
                    <SidebarMenuItem v-if="agents.length === 0">
                        <SidebarMenuButton disabled>
                            <Users />
                            <span>No agents created yet</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem v-for="agent in agents" :key="agent.id">
                        <SidebarMenuButton as-child>
                            <Link href="/agents" :preserve-scroll="true" :preserve-state="true" @click="handleLinkClick">
                                <Users />
                                <span class="flex-1 truncate">{{ agent.name }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <SidebarGroup class="px-2 py-0">
                <div class="flex items-center justify-between">
                    <SidebarGroupLabel>Repositories</SidebarGroupLabel>
                    <button
                        class="flex h-5 w-5 items-center justify-center rounded-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                        @click="showCloneDialog = true"
                    >
                        <Plus class="h-3 w-3" />
                    </button>
                </div>
                <SidebarMenu>
                    <SidebarMenuItem v-if="repositories.length === 0">
                        <SidebarMenuButton disabled>
                            <GitBranch />
                            <span>No repositories cloned yet</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem v-for="repo in repositories" :key="repo.id">
                        <SidebarMenuButton @click="handleRepositoryClick(repo.name)" :tooltip="repo.url">
                            <GitBranch />
                            <span class="flex-1 truncate">{{ repo.name }}</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <SidebarGroup class="px-2 py-0" v-if="conversations.length > 0">
                <SidebarGroupLabel>Conversations</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem v-for="conversation in conversations" :key="conversation.id" class="mb-1">
                        <SidebarMenuButton as-child :is-active="page.url === `/claude/conversation/${conversation.id}`">
                            <Link
                                :href="`/claude/conversation/${conversation.id}`"
                                :preserve-scroll="true"
                                :preserve-state="true"
                                class="flex items-center"
                                @click="handleConversationClick(conversation.id)"
                            >
                                <MessageSquarePlus />
                                <div class="min-w-0 flex-1">
                                    <span class="block truncate">{{ conversation.title }}</span>
                                    <div class="mt-0.5 flex items-center justify-between gap-1 text-xs text-muted-foreground">
                                        <div v-if="conversation.repository" class="flex min-w-0 items-center gap-1">
                                            <GitBranch class="h-3 w-3 shrink-0" />
                                            <span class="truncate">{{ conversation.repository }}</span>
                                        </div>
                                    </div>
                                </div>
                                <Loader2 v-if="conversation.is_processing" class="ml-auto h-3 w-3 animate-spin text-muted-foreground" />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <Dialog v-model:open="showCloneDialog">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>Clone Repository</DialogTitle>
                <DialogDescription> Enter the repository URL to clone it to your workspace. </DialogDescription>
            </DialogHeader>
            <div class="space-y-4 py-4">
                <div class="space-y-2">
                    <label for="repo-url" class="text-sm font-medium">Repository URL</label>
                    <Input
                        id="repo-url"
                        v-model="repositoryUrl"
                        placeholder="https://github.com/username/repository.git"
                        type="url"
                        :disabled="loading"
                    />
                </div>
                <div class="space-y-2">
                    <label for="branch" class="text-sm font-medium">Branch (optional)</label>
                    <Input id="branch" v-model="branch" placeholder="Leave empty for default branch" :disabled="loading" />
                </div>
                <div v-if="cloneError" class="text-sm text-destructive">
                    {{ cloneError }}
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="showCloneDialog = false" :disabled="loading"> Cancel </Button>
                <Button @click="handleCloneRepository" :disabled="loading || !repositoryUrl">
                    <Loader2 v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
                    Clone Repository
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="showCreateAgentDialog">
        <DialogContent class="sm:max-w-[625px]">
            <DialogHeader>
                <DialogTitle>Create New Agent</DialogTitle>
                <DialogDescription>Add a new AI agent with a custom prompt</DialogDescription>
            </DialogHeader>
            <div class="space-y-4 py-4">
                <div class="space-y-2">
                    <Label for="agent-name">Name</Label>
                    <Input id="agent-name" v-model="newAgentName" placeholder="Agent name" :disabled="creatingAgent" />
                </div>
                <div class="space-y-2">
                    <Label for="agent-prompt">Prompt</Label>
                    <Textarea
                        id="agent-prompt"
                        v-model="newAgentPrompt"
                        placeholder="Enter the agent's prompt..."
                        rows="8"
                        :disabled="creatingAgent"
                    />
                </div>
                <div v-if="createAgentError" class="text-sm text-destructive">
                    {{ createAgentError }}
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="showCreateAgentDialog = false" :disabled="creatingAgent"> Cancel </Button>
                <Button @click="handleCreateAgent" :disabled="creatingAgent || !newAgentName || !newAgentPrompt">
                    <Loader2 v-if="creatingAgent" class="mr-2 h-4 w-4 animate-spin" />
                    Create Agent
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <slot />
</template>
