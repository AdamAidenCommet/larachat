interface ClaudeResponse {
    type?: string;
    content?: string | { type: string; text: string };
    text?: string;
    error?: string;
}

export function parseClaudeResponse(jsonData: ClaudeResponse): string {
    // Handle error responses
    if (jsonData.error) {
        return `Error: ${jsonData.error}`;
    }

    // Handle different types of JSON responses from Claude CLI
    if (jsonData.type === 'content' && jsonData.content) {
        if (typeof jsonData.content === 'object' && jsonData.content.type === 'text' && jsonData.content.text) {
            // Claude CLI format: {"type":"content","content":{"type":"text","text":"..."}}
            return jsonData.content.text;
        } else if (typeof jsonData.content === 'string') {
            return jsonData.content;
        }
    } else if (jsonData.type === 'text' && jsonData.text) {
        return jsonData.text;
    } else if (jsonData.text && typeof jsonData.text === 'string') {
        return jsonData.text;
    } else if (jsonData.content && typeof jsonData.content === 'string') {
        return jsonData.content;
    }

    return '';
}

export function extractTextFromResponses(rawResponses: ClaudeResponse[]): string {
    let content = '';

    for (const response of rawResponses) {
        if (!response.error) {
            content += parseClaudeResponse(response);
        }
    }

    return content;
}

export function extractTextFromResponse(rawResponse: any): string {
    // Handle system initialization
    if (rawResponse.type === 'system' && rawResponse.subtype === 'init') {
        return `System initialized with model: ${rawResponse.model || 'unknown'}`;
    }

    // Handle Claude Code CLI assistant response format
    if (rawResponse.type === 'assistant' && rawResponse.message) {
        const message = rawResponse.message;
        if (message.content && Array.isArray(message.content)) {
            const parts: string[] = [];

            for (const item of message.content) {
                if (item.type === 'text') {
                    parts.push(item.text);
                } else if (item.type === 'tool_use') {
                    parts.push(`\n🔧 Using tool: ${item.name}\n${JSON.stringify(item.input, null, 2)}\n`);
                }
            }

            return parts.join('');
        }
    }

    // Handle user tool results
    if (rawResponse.type === 'user' && rawResponse.message) {
        const message = rawResponse.message;
        if (message.content && Array.isArray(message.content)) {
            const parts: string[] = [];

            for (const item of message.content) {
                if (item.type === 'tool_result') {
                    const status = item.is_error ? '❌ Error' : '✅ Success';
                    parts.push(`\n📊 Tool Result (${status}):\n${item.content}\n`);
                }
            }

            return parts.join('');
        }
    }

    // Handle result responses
    if (rawResponse.type === 'result') {
        if (rawResponse.subtype === 'success') {
            return `\n✨ Final Result:\n${rawResponse.result}\n\nDuration: ${rawResponse.duration_ms}ms | Cost: $${rawResponse.total_cost_usd || '0'}`;
        } else if (rawResponse.subtype === 'error') {
            return `\n❌ Error:\n${rawResponse.error || 'Unknown error'}`;
        }
    }

    // Handle error responses
    if (rawResponse.error) {
        return `\n❌ Error: ${rawResponse.error}`;
    }

    // Fallback to original parser
    const fallbackContent = parseClaudeResponse(rawResponse);

    // If still no content, provide debug information about the response
    if (!fallbackContent && rawResponse.type) {
        return `[${rawResponse.type} response - ${JSON.stringify(rawResponse).substring(0, 100)}...]`;
    }

    return fallbackContent;
}
