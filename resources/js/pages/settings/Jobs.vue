<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

import AlertDescription from '@/components/ui/alert-description.vue';
import Alert from '@/components/ui/alert.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import {
    Activity,
    AlertCircle,
    Check,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    Clock,
    Copy,
    Loader2,
    Play,
    RefreshCw,
    RotateCcw,
    Square,
    Trash2,
    XCircle,
} from 'lucide-vue-next';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Jobs',
        href: '/settings/jobs',
    },
];

interface WorkerStatus {
    id: string;
    pid: number;
    status: 'running' | 'stopped' | 'idle';
    startTime: string;
    processedJobs: number;
    failedJobs: number;
    memory: string;
    cpu?: string;
}

interface QueueStats {
    pending: number;
    running: number;
    failed: number;
}

interface FailedJob {
    id: number;
    uuid?: string;
    connection: string;
    queue: string;
    exception: string;
    fullException: string;
    failed_at: string;
    job_name: string;
    attempts: number;
}

const workers = ref<WorkerStatus[]>([]);
const queueStats = ref<QueueStats>({ pending: 0, running: 0, failed: 0 });
const failedJobs = ref<FailedJob[]>([]);
const expandedFailedJobs = ref<Set<number>>(new Set());
const copiedJobId = ref<number | null>(null);
const retryingJobId = ref<number | null>(null);
const discardingJobId = ref<number | null>(null);
const isLoading = ref(false);
const isStarting = ref(false);
const isStopping = ref(false);
const statusMessage = ref('');
const statusType = ref<'success' | 'error' | 'info'>('info');
const refreshInterval = ref<NodeJS.Timeout | null>(null);

const form = useForm({
    action: '',
    workerId: '',
});

const fetchWorkerStatus = async () => {
    try {
        const response = await fetch(route('settings.jobs.status'));
        const data = await response.json();
        workers.value = data.workers || [];
        queueStats.value = data.stats || { pending: 0, running: 0, failed: 0 };
        failedJobs.value = data.failedJobs || [];

        // If no workers in cache but processes are running, use those
        if (workers.value.length === 0 && data.processes && data.processes.length > 0) {
            workers.value = data.processes.map((p: any) => ({
                id: `process_${p.pid}`,
                pid: p.pid,
                status: 'running',
                startTime: new Date().toISOString(),
                processedJobs: 0,
                failedJobs: 0,
                memory: p.memory || 'N/A',
                cpu: p.cpu || '0',
            }));
        }
    } catch (error) {
        console.error('Failed to fetch worker status:', error);
    }
};

const startWorker = () => {
    isStarting.value = true;
    statusMessage.value = '';

    form.action = 'start';
    form.post(route('settings.jobs.control'), {
        preserveScroll: true,
        onSuccess: (page: any) => {
            isStarting.value = false;
            fetchWorkerStatus();
        },
        onError: (errors: any) => {
            isStarting.value = false;
            statusType.value = 'error';
            statusMessage.value = errors.message || 'Failed to start queue worker.';
        },
    });
};

const stopWorker = (workerId?: string) => {
    isStopping.value = true;
    statusMessage.value = '';

    form.action = 'stop';
    form.workerId = workerId || '';
    form.post(route('settings.jobs.control'), {
        preserveScroll: true,
        onSuccess: (page: any) => {
            isStopping.value = false;
            fetchWorkerStatus();
        },
        onError: (errors: any) => {
            isStopping.value = false;
            statusType.value = 'error';
            statusMessage.value = errors.message || 'Failed to stop queue worker.';
        },
    });
};

const refreshStatus = () => {
    isLoading.value = true;
    fetchWorkerStatus().finally(() => {
        isLoading.value = false;
    });
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'running':
            return 'text-green-600 dark:text-green-400';
        case 'idle':
            return 'text-yellow-600 dark:text-yellow-400';
        case 'stopped':
            return 'text-gray-600 dark:text-gray-400';
        default:
            return 'text-gray-600 dark:text-gray-400';
    }
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'running':
            return Activity;
        case 'idle':
            return Clock;
        case 'stopped':
            return Square;
        default:
            return AlertCircle;
    }
};

const toggleFailedJobExpansion = (jobId: number) => {
    if (expandedFailedJobs.value.has(jobId)) {
        expandedFailedJobs.value.delete(jobId);
    } else {
        expandedFailedJobs.value.add(jobId);
    }
};

const formatDateTime = (dateTime: string) => {
    const date = new Date(dateTime);
    return date.toLocaleString();
};

const copyException = async (job: FailedJob) => {
    try {
        await navigator.clipboard.writeText(job.fullException || job.exception);
        copiedJobId.value = job.id;
        setTimeout(() => {
            copiedJobId.value = null;
        }, 2000);
    } catch (error) {
        console.error('Failed to copy exception:', error);
    }
};

const formatExceptionForDisplay = (exception: string) => {
    // Remove file paths to make it more readable, keeping just the relevant parts
    const lines = exception.split('\n');
    const formattedLines = lines.map((line) => {
        // Highlight the main error message (usually the first line)
        if (lines.indexOf(line) === 0) {
            return line;
        }
        // Format stack trace lines
        if (line.includes(' at ') || line.includes('#')) {
            // Extract just the relevant part of the path
            return line.replace(/\/[^\/]+([\/\w-]+\/vendor\/)/, '.../');
        }
        return line;
    });
    return formattedLines.join('\n');
};

const retryJob = async (jobId: number) => {
    retryingJobId.value = jobId;
    statusMessage.value = '';

    form.post(route('settings.jobs.retry', { id: jobId }), {
        preserveScroll: true,
        onSuccess: (page: any) => {
            retryingJobId.value = null;
            statusType.value = 'success';
            statusMessage.value = page.props.flash?.message || 'Job has been queued for retry';
            fetchWorkerStatus();
        },
        onError: (errors: any) => {
            retryingJobId.value = null;
            statusType.value = 'error';
            statusMessage.value = errors.message || 'Failed to retry job';
        },
    });
};

const discardJob = async (jobId: number) => {
    discardingJobId.value = jobId;
    statusMessage.value = '';

    form.delete(route('settings.jobs.discard', { id: jobId }), {
        preserveScroll: true,
        onSuccess: (page: any) => {
            discardingJobId.value = null;
            fetchWorkerStatus();
        },
        onError: (errors: any) => {
            discardingJobId.value = null;
            statusType.value = 'error';
            statusMessage.value = errors.message || 'Failed to discard job';
        },
    });
};

onMounted(() => {
    fetchWorkerStatus();
    refreshInterval.value = setInterval(fetchWorkerStatus, 5000);
});

onUnmounted(() => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Jobs" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <div class="space-y-4">
                    <Alert>
                        <AlertCircle class="h-4 w-4" />
                        <AlertDescription>
                            Queue workers process background jobs such as sending emails, processing uploads, and other asynchronous tasks. The worker
                            will run with a timeout of 3600 seconds (1 hour).
                        </AlertDescription>
                    </Alert>

                    <Alert
                        v-if="statusMessage"
                        :class="{
                            'border-green-500 bg-green-50 dark:bg-green-950/20': statusType === 'success',
                            'border-red-500 bg-red-50 dark:bg-red-950/20': statusType === 'error',
                            'border-blue-500 bg-blue-50 dark:bg-blue-950/20': statusType === 'info',
                        }"
                    >
                        <CheckCircle2 v-if="statusType === 'success'" class="h-4 w-4 text-green-600" />
                        <AlertCircle v-if="statusType === 'error'" class="h-4 w-4 text-red-600" />
                        <AlertCircle v-if="statusType === 'info'" class="h-4 w-4 text-blue-600" />
                        <AlertDescription
                            :class="{
                                'text-green-800 dark:text-green-300': statusType === 'success',
                                'text-red-800 dark:text-red-300': statusType === 'error',
                                'text-blue-800 dark:text-blue-300': statusType === 'info',
                            }"
                        >
                            {{ statusMessage }}
                        </AlertDescription>
                    </Alert>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold">Queue Statistics</h3>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div class="rounded-lg border p-3">
                                <div class="text-sm text-muted-foreground">Pending Jobs</div>
                                <div class="text-2xl font-semibold">{{ queueStats.pending }}</div>
                            </div>
                            <div class="rounded-lg border p-3">
                                <div class="text-sm text-muted-foreground">Running Jobs</div>
                                <div class="text-2xl font-semibold text-blue-600 dark:text-blue-400">{{ queueStats.running }}</div>
                            </div>
                            <div class="rounded-lg border p-3">
                                <div class="text-sm text-muted-foreground">Failed Jobs</div>
                                <div class="text-2xl font-semibold text-red-600 dark:text-red-400">{{ queueStats.failed }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold">Active Workers</h3>
                            <div class="flex items-center gap-4">
                                <Button @click="startWorker" :disabled="isStarting || isStopping" variant="default">
                                    <Play v-if="!isStarting" class="mr-2 h-4 w-4" />
                                    <Loader2 v-if="isStarting" class="mr-2 h-4 w-4 animate-spin" />
                                    {{ isStarting ? 'Starting...' : 'Start Queue Worker' }}
                                </Button>

                                <Button @click="refreshStatus" :disabled="isLoading" variant="outline">
                                    <RefreshCw :class="['h-4 w-4', isLoading ? 'animate-spin' : '']" />
                                    <span class="ml-2">Refresh</span>
                                </Button>
                            </div>
                        </div>

                        <div v-if="workers.length === 0" class="text-muted-foreground">
                            No active workers found. Click "Start Queue Worker" to begin processing jobs.
                        </div>

                        <div v-else class="space-y-3">
                            <div v-for="worker in workers" :key="worker.id" class="space-y-2 rounded-lg border p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <component :is="getStatusIcon(worker.status)" :class="['h-5 w-5', getStatusColor(worker.status)]" />
                                        <span class="font-medium">Worker PID: {{ worker.pid }}</span>
                                        <span :class="['text-sm', getStatusColor(worker.status)]"> ({{ worker.status }}) </span>
                                    </div>
                                    <Button @click="stopWorker(worker.id)" :disabled="isStopping" variant="outline" size="sm">
                                        <Square class="mr-1 h-3 w-3" />
                                        Stop
                                    </Button>
                                </div>

                                <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-5">
                                    <div>
                                        <span class="text-muted-foreground">Started:</span>
                                        <div class="font-mono text-xs">{{ new Date(worker.startTime).toLocaleTimeString() }}</div>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground">Processed:</span>
                                        <div class="font-mono">{{ worker.processedJobs }}</div>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground">Failed:</span>
                                        <div class="font-mono text-red-600 dark:text-red-400">
                                            {{ worker.failedJobs }}
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground">Memory:</span>
                                        <div class="font-mono">{{ worker.memory }}</div>
                                    </div>
                                    <div v-if="worker.cpu">
                                        <span class="text-muted-foreground">CPU:</span>
                                        <div class="font-mono">{{ worker.cpu }}%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="failedJobs.length > 0" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-red-600 dark:text-red-400">Failed Jobs ({{ failedJobs.length }})</h3>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="job in failedJobs"
                                :key="job.id"
                                class="overflow-hidden rounded-lg border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950/20"
                            >
                                <!-- Header -->
                                <div class="border-b border-red-200 bg-red-100 px-4 py-3 dark:border-red-800 dark:bg-red-900/30">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <XCircle class="h-5 w-5 text-red-600 dark:text-red-400" />
                                            <span class="font-semibold text-red-900 dark:text-red-200">
                                                {{ job.job_name }}
                                            </span>
                                            <span class="rounded-full bg-red-200 px-2 py-0.5 text-xs text-red-700 dark:bg-red-800 dark:text-red-300">
                                                {{ job.attempts }} {{ job.attempts === 1 ? 'attempt' : 'attempts' }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Button
                                                @click="retryJob(job.id)"
                                                variant="ghost"
                                                size="sm"
                                                class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                                :disabled="retryingJobId === job.id || discardingJobId === job.id"
                                                title="Retry this job"
                                            >
                                                <Loader2 v-if="retryingJobId === job.id" class="h-4 w-4 animate-spin" />
                                                <RotateCcw v-else class="h-4 w-4" />
                                                <span class="ml-1 text-xs">Retry</span>
                                            </Button>
                                            <Button
                                                @click="discardJob(job.id)"
                                                variant="ghost"
                                                size="sm"
                                                class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                                :disabled="retryingJobId === job.id || discardingJobId === job.id"
                                                title="Discard this job"
                                            >
                                                <Loader2 v-if="discardingJobId === job.id" class="h-4 w-4 animate-spin" />
                                                <Trash2 v-else class="h-4 w-4" />
                                                <span class="ml-1 text-xs">Discard</span>
                                            </Button>
                                            <Button
                                                @click="copyException(job)"
                                                variant="ghost"
                                                size="sm"
                                                class="text-gray-600 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                                :title="'Copy exception to clipboard'"
                                            >
                                                <Check v-if="copiedJobId === job.id" class="h-4 w-4 text-green-600" />
                                                <Copy v-else class="h-4 w-4" />
                                                <span class="ml-1 text-xs">{{ copiedJobId === job.id ? 'Copied!' : 'Copy' }}</span>
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Body -->
                                <div class="p-4">
                                    <!-- Error Message -->
                                    <div class="mb-3">
                                        <div class="mb-1 text-xs font-medium text-red-700 dark:text-red-300">Error Message:</div>
                                        <div class="rounded border border-red-200 bg-red-100 p-2 dark:border-red-800 dark:bg-red-900/20">
                                            <code class="font-mono text-xs break-all text-red-800 dark:text-red-200">
                                                {{ job.exception }}
                                            </code>
                                        </div>
                                    </div>

                                    <!-- Metadata -->
                                    <div class="flex flex-wrap gap-3 text-xs text-red-600 dark:text-red-400">
                                        <div class="flex items-center gap-1">
                                            <span class="font-medium">Queue:</span>
                                            <span class="font-mono">{{ job.queue }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span class="font-medium">Failed:</span>
                                            <span>{{ formatDateTime(job.failed_at) }}</span>
                                        </div>
                                        <div v-if="job.uuid" class="flex items-center gap-1">
                                            <span class="font-medium">UUID:</span>
                                            <span class="font-mono">{{ job.uuid.substring(0, 8) }}...</span>
                                        </div>
                                    </div>

                                    <!-- Full Exception (Expandable) -->
                                    <div v-if="job.fullException" class="mt-3">
                                        <Button
                                            @click="toggleFailedJobExpansion(job.id)"
                                            variant="outline"
                                            size="sm"
                                            class="w-full border-red-300 text-red-600 hover:text-red-700 dark:border-red-700 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                            <ChevronDown v-if="!expandedFailedJobs.has(job.id)" class="mr-1 h-4 w-4" />
                                            <ChevronUp v-else class="mr-1 h-4 w-4" />
                                            {{ expandedFailedJobs.has(job.id) ? 'Hide' : 'Show' }} Full Stack Trace
                                        </Button>

                                        <div v-if="expandedFailedJobs.has(job.id)" class="relative mt-2">
                                            <div class="absolute top-2 right-2 z-10">
                                                <Button
                                                    @click="copyException(job)"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="bg-red-100 text-red-600 hover:bg-red-200 dark:bg-red-900/50 dark:text-red-400 dark:hover:bg-red-900/70"
                                                >
                                                    <Check v-if="copiedJobId === job.id" class="h-3 w-3 text-green-600" />
                                                    <Copy v-else class="h-3 w-3" />
                                                </Button>
                                            </div>
                                            <div
                                                class="overflow-x-auto rounded border border-red-200 bg-gray-900 p-3 dark:border-red-800 dark:bg-black"
                                            >
                                                <pre class="font-mono text-xs leading-relaxed whitespace-pre text-red-400">{{
                                                    formatExceptionForDisplay(job.fullException)
                                                }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
