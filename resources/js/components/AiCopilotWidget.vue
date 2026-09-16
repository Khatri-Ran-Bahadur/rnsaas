<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';

interface ChatMessage {
    id: string;
    role: 'user' | 'assistant';
    content: string;
    timestamp: string;
    tool_executed?: string;
}

const isOpen = ref(false);
const isExpanded = ref(false);
const inputQuery = ref('');
const isLoading = ref(false);
const isConfigured = ref(true);
const isEnabled = ref(true);
const providerName = ref('Gemini 1.5 Flash');
const messages = ref<ChatMessage[]>([]);
const messagesContainer = ref<HTMLDivElement | null>(null);
const isListening = ref(false);

const quickSuggestions = [
    {
        icon: '📊',
        title: 'बिक्री र बाँकी बिल',
        query: 'यो महिनाको कुल बिक्री, आम्दानी, र नतिरेका बिलहरूको विवरण दिनुहोस्।',
    },
    {
        icon: '📦',
        title: 'स्टक सकिन लागेका सामान',
        query: 'कुन-कुन सामानको स्टक सकिन लागेको छ र कति बाँकी छ?',
    },
    {
        icon: '🧾',
        title: 'आजको POS संकलन',
        query: 'आजको POS बिक्री, क्यास र अनलाइन संकलनको सारांश देखाउनुहोस्।',
    },
    {
        icon: '💰',
        title: 'नाफा/नोक्सान विश्लेषण',
        query: 'यो महिनाको फाइनान्सियल सारांश (बिक्री आम्दानी vs खरिद खर्च) र नाफा देखाउनुहोस्।',
    },
];

const isModuleEnabled = ref(true);

const checkStatus = async () => {
    try {
        const res = await fetch('/admin/ai/copilot/status');
        if (res.ok) {
            const data = await res.json();
            isModuleEnabled.value = data.is_module_enabled ?? true;
            isConfigured.value = data.is_configured;
            isEnabled.value = data.is_enabled;
            providerName.value = data.provider === 'gemini' ? 'Gemini 1.5 Flash' : data.provider.toUpperCase();
        } else if (res.status === 403) {
            isModuleEnabled.value = false;
        }
    } catch {
        // Silently handle if offline or not logged in
    }
};

const handleExternalToggle = () => {
    isOpen.value = true;
    checkStatus();
    scrollToBottom();
};

onMounted(() => {
    checkStatus();
    window.addEventListener('toggle-ai-copilot', handleExternalToggle);
});

onUnmounted(() => {
    window.removeEventListener('toggle-ai-copilot', handleExternalToggle);
});

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const toggleOpen = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        checkStatus();
        scrollToBottom();
    }
};

const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;
    scrollToBottom();
};

const clearChat = () => {
    messages.value = [];
};

const sendMessage = async (presetQuery?: string) => {
    const text = (presetQuery || inputQuery.value).trim();
    if (!text || isLoading.value) return;

    const userMsg: ChatMessage = {
        id: Date.now().toString(),
        role: 'user',
        content: text,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    };

    messages.value.push(userMsg);
    inputQuery.value = '';
    isLoading.value = true;
    scrollToBottom();

    try {
        const payloadMessages = messages.value.map(m => ({
            role: m.role,
            content: m.content,
        }));

        const response = await fetch('/admin/ai/copilot/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ messages: payloadMessages }),
        });

        const data = await response.json();

        const assistantMsg: ChatMessage = {
            id: (Date.now() + 1).toString(),
            role: 'assistant',
            content: data.content || 'कुनै जवाफ प्राप्त भएन।',
            timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
            tool_executed: data.tool_executed,
        };

        if (data.is_configured === false) {
            isConfigured.value = false;
        }

        messages.value.push(assistantMsg);
    } catch (e: any) {
        messages.value.push({
            id: (Date.now() + 1).toString(),
            role: 'assistant',
            content: '⚠️ सञ्चारमा त्रुटि आयो। कृपया इन्टरनेट वा AI Settings जाँच गर्नुहोस्।',
            timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        });
    } finally {
        isLoading.value = false;
        scrollToBottom();
    }
};

// Web Speech Voice-to-Text Recognition
const toggleVoice = () => {
    const SpeechRecognition = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition;
    if (!SpeechRecognition) {
        alert('तपाईंको ब्राउजरमा Voice Recognition उपलब्ध छैन। (Google Chrome सिफारिस गरिन्छ)');
        return;
    }

    if (isListening.value) {
        isListening.value = false;
        return;
    }

    const recognition = new SpeechRecognition();
    recognition.lang = 'ne-NP';
    recognition.interimResults = false;

    recognition.onstart = () => {
        isListening.value = true;
    };

    recognition.onresult = (event: any) => {
        const transcript = event.results[0][0].transcript;
        inputQuery.value = (inputQuery.value ? inputQuery.value + ' ' : '') + transcript;
        isListening.value = false;
    };

    recognition.onerror = () => {
        isListening.value = false;
    };

    recognition.onend = () => {
        isListening.value = false;
    };

    recognition.start();
};

const copyContent = (text: string) => {
    navigator.clipboard.writeText(text);
};

// Simple Rich Markdown Parser for UI Display
const formatMarkdown = (text: string): string => {
    if (!text) return '';

    let formatted = text
        // Escape HTML tags to prevent XSS
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        // Bold
        .replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-gray-900 dark:text-white">$1</strong>')
        // Italic
        .replace(/\*(.*?)\*/g, '<em class="italic">$1</em>')
        // Inline Code
        .replace(/`([^`]+)`/g, '<code class="px-1.5 py-0.5 rounded-md bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300 font-mono text-xs">$1</code>')
        // Headers ###
        .replace(/^### (.*$)/gim, '<h4 class="text-sm font-bold text-gray-900 dark:text-white mt-2 mb-1">$1</h4>')
        // Headers ##
        .replace(/^## (.*$)/gim, '<h3 class="text-base font-bold text-purple-700 dark:text-purple-400 mt-3 mb-1">$1</h3>')
        // Bullet Lists
        .replace(/^\s*[\-\*]\s+(.*)$/gim, '<li class="ml-4 list-disc text-xs leading-relaxed my-0.5">$1</li>')
        // Numbered Lists
        .replace(/^\s*(\d+)\.\s+(.*)$/gim, '<li class="ml-4 list-decimal text-xs leading-relaxed my-0.5">$2</li>')
        // Line breaks
        .replace(/\n/g, '<br />');

    return formatted;
};
</script>

<template>
    <div class="fixed bottom-6 right-6 z-50 font-sans">
        <!-- FLOATING COPILOT LAUNCH BUTTON -->
        <div v-if="!isOpen" class="relative group">
            <button
                @click="toggleOpen"
                class="relative flex items-center gap-2.5 px-4 py-3 bg-gradient-to-r from-purple-600 via-indigo-600 to-indigo-700 hover:from-purple-500 hover:to-indigo-600 text-white font-medium text-sm rounded-full shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-200 cursor-pointer ring-4 ring-purple-100 dark:ring-purple-950"
                aria-label="Open AI Copilot"
            >
                <!-- Glowing Pulsing Indicator -->
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>

                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>

                <span class="font-semibold tracking-wide">AI Copilot</span>
            </button>
        </div>

        <!-- EXPANDABLE COPILOT WINDOW -->
        <div
            v-else
            :class="[
                'bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-3xl shadow-2xl flex flex-col transition-all duration-300 overflow-hidden',
                isExpanded
                    ? 'w-[90vw] md:w-[700px] h-[85vh]'
                    : 'w-[92vw] sm:w-[420px] h-[580px]'
            ]"
        >
            <!-- TOP BAR HEADER -->
            <div class="px-5 py-4 bg-gradient-to-r from-purple-700 via-indigo-700 to-indigo-800 text-white flex items-center justify-between shadow-sm shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center backdrop-blur-xs border border-white/20">
                        <svg class="w-5 h-5 text-yellow-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-sm leading-none">Sathi AI Copilot</h3>
                            <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-white/20 text-white">Live Data</span>
                        </div>
                        <p class="text-[11px] text-purple-200 mt-0.5">{{ providerName }} &bull; Enterprise ERP</p>
                    </div>
                </div>

                <!-- Window Controls -->
                <div class="flex items-center gap-1.5 text-white/80">
                    <button
                        @click="clearChat"
                        title="Clear Conversation"
                        class="p-1.5 hover:bg-white/10 rounded-lg transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>

                    <button
                        @click="toggleExpand"
                        :title="isExpanded ? 'Restore Size' : 'Expand Size'"
                        class="p-1.5 hover:bg-white/10 rounded-lg transition-colors cursor-pointer hidden sm:block"
                    >
                        <svg v-if="!isExpanded" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <button
                        @click="toggleOpen"
                        title="Close Copilot"
                        class="p-1.5 hover:bg-white/10 rounded-lg transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- UNCONFIGURED NOTICE BANNER -->
            <div
                v-if="isModuleEnabled && !isConfigured"
                class="bg-amber-50 dark:bg-amber-950/50 border-b border-amber-200 dark:border-amber-900/60 p-3 text-xs text-amber-800 dark:text-amber-200 flex items-center justify-between"
            >
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>API Key आवश्यक छ।</span>
                </div>
                <a
                    href="/admin/company-settings"
                    class="font-semibold text-amber-900 dark:text-amber-100 underline hover:no-underline"
                >
                    Setup Key &rarr;
                </a>
            </div>

            <!-- SUBSCRIPTION UPGRADE REQUIRED CARD -->
            <div
                v-if="!isModuleEnabled"
                class="flex-1 flex flex-col items-center justify-center p-6 text-center space-y-4 bg-zinc-50/60 dark:bg-zinc-950/40"
            >
                <div class="w-16 h-16 rounded-2xl bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center text-3xl shadow-sm">
                    🔒
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 dark:text-white text-base">AI Copilot Subscription Required</h4>
                    <p class="text-xs text-gray-500 dark:text-zinc-400 max-w-xs mx-auto mt-1.5 leading-relaxed">
                        AI Agent & Copilot सुविधा तपाईंको हालको प्लानमा सक्रिय गरिएको छैन। कृपया प्लान अपग्रेड गर्नुहोस्।
                    </p>
                </div>
                <a
                    href="/admin/subscription/plans"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-linear-to-r from-purple-600 to-indigo-600 rounded-lg shadow-sm hover:from-purple-700 hover:to-indigo-700 transition"
                >
                    <span>Upgrade Subscription Plan &rarr;</span>
                </a>
            </div>

            <!-- CHAT MESSAGES BODY -->
            <div
                v-else
                ref="messagesContainer"
                class="flex-1 overflow-y-auto p-4 space-y-4 bg-zinc-50/60 dark:bg-zinc-950/40 text-sm"
            >
                <!-- EMPTY STATE / QUICK SUGGESTIONS -->
                <div v-if="messages.length === 0" class="py-4 text-center space-y-4">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center text-2xl shadow-inner">
                        🤖
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 dark:text-white text-base">नमस्ते! म कसरी मद्दत गरौँ?</h4>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 max-w-xs mx-auto mt-1">
                            तपाईंको कम्पनीको Accounting, Inventory, र POS डेटाबाट प्रत्यक्ष जवाफ पाउनुहोस्।
                        </p>
                    </div>

                    <!-- Quick Suggestions Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-left pt-2">
                        <button
                            v-for="sug in quickSuggestions"
                            :key="sug.title"
                            @click="sendMessage(sug.query)"
                            class="p-2.5 rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 hover:border-purple-400 dark:hover:border-purple-600 hover:bg-purple-50/40 dark:hover:bg-purple-950/30 transition-all cursor-pointer group shadow-2xs"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-base">{{ sug.icon }}</span>
                                <span class="text-xs font-semibold text-gray-800 dark:text-zinc-200 group-hover:text-purple-600 dark:group-hover:text-purple-400">
                                    {{ sug.title }}
                                </span>
                            </div>
                            <p class="text-[11px] text-gray-400 dark:text-zinc-500 mt-1 line-clamp-2">
                                {{ sug.query }}
                            </p>
                        </button>
                    </div>
                </div>

                <!-- MESSAGES LIST -->
                <template v-else>
                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        :class="[
                            'flex flex-col',
                            msg.role === 'user' ? 'items-end' : 'items-start'
                        ]"
                    >
                        <!-- Tool Execution Tag Badge -->
                        <div
                            v-if="msg.tool_executed"
                            class="mb-1 flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-[10px] text-emerald-700 dark:text-emerald-300 font-mono"
                        >
                            <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Live Data Fetched: {{ msg.tool_executed }}</span>
                        </div>

                        <div
                            :class="[
                                'max-w-[85%] rounded-2xl p-3.5 shadow-2xs text-xs leading-relaxed transition-all',
                                msg.role === 'user'
                                    ? 'bg-purple-600 text-white rounded-br-xs'
                                    : 'bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 text-gray-800 dark:text-zinc-200 rounded-bl-xs'
                            ]"
                        >
                            <!-- User Message -->
                            <div v-if="msg.role === 'user'" class="whitespace-pre-wrap font-medium">
                                {{ msg.content }}
                            </div>

                            <!-- Assistant Message with Formatted Markdown -->
                            <div
                                v-else
                                class="prose prose-xs dark:prose-invert max-w-none break-words"
                                v-html="formatMarkdown(msg.content)"
                            ></div>
                        </div>

                        <!-- Message Meta & Copy -->
                        <div class="flex items-center gap-2 mt-1 px-1 text-[10px] text-gray-400">
                            <span>{{ msg.timestamp }}</span>
                            <button
                                v-if="msg.role === 'assistant'"
                                @click="copyContent(msg.content)"
                                title="Copy message"
                                class="hover:text-purple-600 dark:hover:text-purple-400 cursor-pointer"
                            >
                                Copy
                            </button>
                        </div>
                    </div>

                    <!-- AI Typing Spinner Indicator -->
                    <div v-if="isLoading" class="flex items-center gap-2 text-xs text-purple-600 dark:text-purple-400 pt-1">
                        <div class="flex gap-1">
                            <span class="w-2 h-2 rounded-full bg-purple-600 animate-bounce"></span>
                            <span class="w-2 h-2 rounded-full bg-purple-600 animate-bounce [animation-delay:0.2s]"></span>
                            <span class="w-2 h-2 rounded-full bg-purple-600 animate-bounce [animation-delay:0.4s]"></span>
                        </div>
                        <span class="text-[11px] font-medium">डेटा विश्लेषण गर्दैछ... (Analyzing ERP Data)</span>
                    </div>
                </template>
            </div>

            <!-- INPUT FOOTER -->
            <div class="p-3 bg-white dark:bg-zinc-900 border-t border-gray-200 dark:border-zinc-800 shrink-0">
                <form @submit.prevent="sendMessage()" class="flex items-center gap-2">
                    <!-- Voice Input Button -->
                    <button
                        type="button"
                        @click="toggleVoice"
                        :title="isListening ? 'Listening...' : 'Voice Input (नेपाली)'"
                        :class="[
                            'p-2 rounded-xl transition-all cursor-pointer shrink-0',
                            isListening
                                ? 'bg-rose-500 text-white animate-pulse'
                                : 'text-gray-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-zinc-800'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                    </button>

                    <!-- Text Input Field -->
                    <input
                        v-model="inputQuery"
                        type="text"
                        :disabled="isLoading"
                        placeholder="AI सँग केही सोध्नुहोस् (Ask anything)..."
                        class="flex-1 px-3.5 py-2.5 bg-zinc-100 dark:bg-zinc-800 border-0 rounded-xl text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400"
                    />

                    <!-- Send Button -->
                    <button
                        type="submit"
                        :disabled="!inputQuery.trim() || isLoading"
                        class="p-2.5 bg-purple-600 hover:bg-purple-700 disabled:opacity-40 text-white rounded-xl shadow-xs transition-colors cursor-pointer shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
