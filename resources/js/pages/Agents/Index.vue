<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface Agent {
    id: number;
    name: string;
    prompt: string;
    created_at: string;
    updated_at: string;
}

defineProps<{
    agents: Agent[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Agents', href: '/agents' }];

const isCreateDialogOpen = ref(false);
const editingAgent = ref<Agent | null>(null);
const isEditDialogOpen = ref(false);

const createForm = useForm({
    name: '',
    prompt: '',
});

const editForm = useForm({
    name: '',
    prompt: '',
});

const createAgent = () => {
    createForm.post('/agents', {
        preserveScroll: true,
        onSuccess: () => {
            isCreateDialogOpen.value = false;
            createForm.reset();
        },
    });
};

const startEdit = (agent: Agent) => {
    editingAgent.value = agent;
    editForm.name = agent.name;
    editForm.prompt = agent.prompt;
    isEditDialogOpen.value = true;
};

const updateAgent = () => {
    if (!editingAgent.value) return;

    editForm.put(`/agents/${editingAgent.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditDialogOpen.value = false;
            editingAgent.value = null;
            editForm.reset();
        },
    });
};

const deleteAgent = (agent: Agent) => {
    if (confirm(`Are you sure you want to delete the agent "${agent.name}"?`)) {
        useForm({}).delete(`/agents/${agent.id}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Agents</h2>
                    <p class="text-muted-foreground">Manage your AI agents and their prompts</p>
                </div>

                <Dialog v-model:open="isCreateDialogOpen">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            New Agent
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[625px]">
                        <DialogHeader>
                            <DialogTitle>Create New Agent</DialogTitle>
                            <DialogDescription> Add a new AI agent with a custom prompt </DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="createAgent" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="name">Name</Label>
                                <Input id="name" v-model="createForm.name" placeholder="Agent name" required />
                                <div v-if="createForm.errors.name" class="text-sm text-destructive">
                                    {{ createForm.errors.name }}
                                </div>
                            </div>
                            <div class="space-y-2">
                                <Label for="prompt">Prompt</Label>
                                <Textarea id="prompt" v-model="createForm.prompt" placeholder="Enter the agent's prompt..." rows="8" required />
                                <div v-if="createForm.errors.prompt" class="text-sm text-destructive">
                                    {{ createForm.errors.prompt }}
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" @click="isCreateDialogOpen = false"> Cancel </Button>
                                <Button type="submit" :disabled="createForm.processing"> Create Agent </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div v-if="agents.length === 0" class="py-12 text-center">
                <p class="text-muted-foreground">No agents created yet. Create your first agent to get started.</p>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="agent in agents" :key="agent.id">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <CardTitle>{{ agent.name }}</CardTitle>
                            <div class="flex gap-2">
                                <Button size="icon" variant="ghost" @click="startEdit(agent)">
                                    <Pencil class="h-4 w-4" />
                                </Button>
                                <Button size="icon" variant="ghost" @click="deleteAgent(agent)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="line-clamp-3 text-sm text-muted-foreground">{{ agent.prompt }}</p>
                    </CardContent>
                </Card>
            </div>

            <Dialog v-model:open="isEditDialogOpen">
                <DialogContent class="sm:max-w-[625px]">
                    <DialogHeader>
                        <DialogTitle>Edit Agent</DialogTitle>
                        <DialogDescription> Update the agent's name and prompt </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="updateAgent" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="edit-name">Name</Label>
                            <Input id="edit-name" v-model="editForm.name" placeholder="Agent name" required />
                            <div v-if="editForm.errors.name" class="text-sm text-destructive">
                                {{ editForm.errors.name }}
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label for="edit-prompt">Prompt</Label>
                            <Textarea id="edit-prompt" v-model="editForm.prompt" placeholder="Enter the agent's prompt..." rows="8" required />
                            <div v-if="editForm.errors.prompt" class="text-sm text-destructive">
                                {{ editForm.errors.prompt }}
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" @click="isEditDialogOpen = false"> Cancel </Button>
                            <Button type="submit" :disabled="editForm.processing"> Update Agent </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
