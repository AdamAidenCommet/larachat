import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

interface Agent {
    id: number;
    name: string;
    prompt: string;
    created_at: string;
    updated_at: string;
}

export function useAgents() {
    const agents = ref<Agent[]>([]);
    const loading = ref(false);
    const showCreateDialog = ref(false);
    const createError = ref('');

    const fetchAgents = async () => {
        try {
            const response = await axios.get('/api/agents');
            const newAgents = response.data.agents || [];
            
            // Update the list in-place to avoid UI jump
            // Remove agents that no longer exist
            agents.value = agents.value.filter(existingAgent => 
                newAgents.some(newAgent => newAgent.id === existingAgent.id)
            );
            
            // Update existing agents and add new ones
            newAgents.forEach(newAgent => {
                const existingIndex = agents.value.findIndex(a => a.id === newAgent.id);
                if (existingIndex !== -1) {
                    // Update existing agent
                    agents.value[existingIndex] = newAgent;
                } else {
                    // Add new agent
                    agents.value.push(newAgent);
                }
            });
        } catch (error) {
            console.error('Failed to fetch agents:', error);
        }
    };

    const createAgent = async (name: string, prompt: string) => {
        loading.value = true;
        createError.value = '';

        try {
            await axios.post('/api/agents', { name, prompt });
            await fetchAgents();
            showCreateDialog.value = false;
            router.visit('/agents', { preserveScroll: true, preserveState: true });
        } catch (error: any) {
            createError.value = error.response?.data?.message || 'Failed to create agent';
            throw error;
        } finally {
            loading.value = false;
        }
    };

    return {
        agents,
        loading,
        showCreateDialog,
        createError,
        fetchAgents,
        createAgent,
    };
}
