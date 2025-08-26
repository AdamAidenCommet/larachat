import type { Message } from '@/types/claude';
import { extractTextFromResponse } from '@/utils/claudeResponseParser';
import { ref } from 'vue';

export function useChatMessages() {
    const messages = ref<Message[]>([]);

    const addUserMessage = (content: string): Message => {
        const message: Message = {
            id: Date.now(),
            content,
            role: 'user',
            timestamp: new Date(),
        };
        messages.value.push(message);
        return message;
    };

    const addAssistantMessage = (): Message => {
        const message: Message = {
            id: Date.now() + 1,
            content: '',
            role: 'assistant',
            timestamp: new Date(),
            rawResponses: [],
        };
        messages.value.push(message);
        return message;
    };

    const updateMessage = (messageId: number, updates: Partial<Message>) => {
        const index = messages.value.findIndex((m) => m.id === messageId);
        if (index !== -1) {
            messages.value[index] = { ...messages.value[index], ...updates };
        }
    };

    const appendToMessage = (messageId: number, text: string, rawResponse?: any) => {
        const index = messages.value.findIndex((m) => m.id === messageId);
        if (index !== -1) {
            const message = messages.value[index];

            if (rawResponse) {
                // Add the raw response
                if (!message.rawResponses) {
                    message.rawResponses = [];
                }
                message.rawResponses.push(rawResponse);

                // Rebuild content from all raw responses
                message.content = '';
                for (const response of message.rawResponses) {
                    const extractedText = extractTextFromResponse(response);
                    if (extractedText) {
                        message.content += extractedText;
                    }
                }
            } else if (text) {
                // Fallback to appending text if no raw response
                message.content += text;
            }

            // Trigger reactivity
            messages.value[index] = { ...message };
        }
    };

    const formatTime = (date: Date | string): string => {
        // Ensure we have a valid Date object
        const dateObj = date instanceof Date ? date : new Date(date);
        
        // Check if the date is valid
        if (isNaN(dateObj.getTime())) {
            return 'Invalid time';
        }
        
        const now = new Date();
        const isToday = dateObj.toDateString() === now.toDateString();
        const yesterday = new Date(now);
        yesterday.setDate(yesterday.getDate() - 1);
        const isYesterday = dateObj.toDateString() === yesterday.toDateString();
        
        // Format time
        const timeString = dateObj.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        });
        
        // Add date prefix if not today
        if (isToday) {
            return timeString;
        } else if (isYesterday) {
            return `Yesterday ${timeString}`;
        } else {
            // Show date for older messages
            const dateString = dateObj.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: dateObj.getFullYear() !== now.getFullYear() ? 'numeric' : undefined,
            });
            return `${dateString} ${timeString}`;
        }
    };

    return {
        messages,
        addUserMessage,
        addAssistantMessage,
        updateMessage,
        appendToMessage,
        formatTime,
    };
}
