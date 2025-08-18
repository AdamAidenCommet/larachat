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
            agents.value = response.data.agents || [];
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