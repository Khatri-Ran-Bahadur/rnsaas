<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import {
    MessageSquare,
    Send,
    Users,
    Plus,
    Search,
    Check,
    CheckCheck,
    X,
    Paperclip,
    Mic,
    Trash2,
    Play,
    Pause,
    FileText,
    Download,
    Reply,
    Smile,
    Edit2,
    CornerDownRight,
    AlertTriangle,
} from 'lucide-vue-next';

interface StaffUser {
    id: number;
    name: string;
    email: string;
    avatar_url?: string | null;
}

interface MessageSender {
    id: number;
    name: string;
    avatar_url?: string | null;
}

interface AttachmentItem {
    url: string;
    name: string;
    size?: number;
    mime?: string;
}

interface ChatMessage {
    id: number;
    public_id: string;
    conversation_id: number;
    body: string;
    type: 'text' | 'image' | 'file' | 'voice' | string;
    attachments?: AttachmentItem[] | null;
    is_read?: boolean;
    is_deleted?: boolean;
    edited_at?: string | null;
    reactions?: Record<string, number[]>;
    reply_to?: {
        id: number;
        body: string;
        sender_name: string;
    } | null;
    created_at?: string;
    sender: MessageSender;
}

interface ConversationItem {
    id: number;
    public_id: string;
    type: 'direct' | 'group';
    title: string;
    avatar_url?: string | null;
    other_user_id?: number | null;
    other_user_email?: string | null;
    participants: StaffUser[];
    latest_message?: {
        id: number;
        body: string;
        sender_id: number;
        sender_name: string;
        created_at: string;
    } | null;
    unread_count: number;
    last_message_at?: string | null;
}

const props = defineProps<{
    conversations: ConversationItem[];
    companyStaff: StaffUser[];
    activeConversationId: number;
    initialMessages: ChatMessage[];
    companyId: number;
    currentUser: StaffUser;
    reverbConfig: {
        key: string;
        host: string;
        port: number;
        scheme: string;
    };
}>();

// Conversations & Messages State
const conversationsList = ref<ConversationItem[]>([...props.conversations]);
const activeConvId = ref<number>(props.activeConversationId);
const messages = ref<ChatMessage[]>([...props.initialMessages]);
const newMessageText = ref('');
const isSending = ref(false);
const messagesContainer = ref<HTMLElement | null>(null);

// File Attachment State
const fileInputRef = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const filePreviewUrl = ref<string | null>(null);

// Voice Message Recording State
const isRecordingVoice = ref(false);
const voiceRecordDuration = ref(0);
let voiceTimer: any = null;
let mediaRecorder: MediaRecorder | null = null;
let audioChunks: Blob[] = [];

// Audio Playback Tracking
const currentlyPlayingAudioId = ref<number | null>(null);
const audioPlayerRefs = ref<Record<number, HTMLAudioElement>>({});

// Search filter
const searchQuery = ref('');

// Real-time State
const onlineStaff = ref<StaffUser[]>([]);
const typingUser = ref<string | null>(null);
let typingTimeout: any = null;
let lastTypingTime = 0;

// WhatsApp Features State
const replyingTo = ref<ChatMessage | null>(null);
const editingMessage = ref<ChatMessage | null>(null);
const editingText = ref('');
const isSavingEdit = ref(false);
const activeReactionPickerMessageId = ref<number | null>(null);
const messageToDelete = ref<ChatMessage | null>(null);
const showDeleteConfirmModal = ref(false);
const isDeletingMessage = ref(false);
const availableEmojis = ['👍', '❤️', '😂', '😮', '😢', '🙏'];

// Group Chat Creation Modal
const showGroupModal = ref(false);
const groupTitle = ref('');
const selectedStaffIds = ref<number[]>([]);

// Filtered conversation list
const filteredConversations = computed(() => {
    if (!searchQuery.value.trim()) return conversationsList.value;
    const query = searchQuery.value.toLowerCase();
    return conversationsList.value.filter((c) =>
        c.title.toLowerCase().includes(query) ||
        c.latest_message?.body.toLowerCase().includes(query)
    );
});

// Currently active conversation object
const activeConversation = computed(() => {
    return conversationsList.value.find((c) => c.id === activeConvId.value) || null;
});

// Scroll messages to bottom
const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

// Check if a staff user is online
const isUserOnline = (userId: number) => {
    return onlineStaff.value.some((u) => u.id === userId);
};

// Format seconds into MM:SS
const formatDuration = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

// Format file size
const formatFileSize = (bytes?: number) => {
    if (!bytes) return '';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

// =========================================================================
// WEBSOCKET (REVERB / ECHO) INTEGRATION
// =========================================================================

const setupWebSocket = () => {
    if (typeof window === 'undefined' || !window.Echo) return;

    // 1. Presence Channel
    const presenceChannelName = `company.${props.companyId}.presence`;
    window.Echo.join(presenceChannelName)
        .here((users: StaffUser[]) => {
            onlineStaff.value = users;
        })
        .joining((user: StaffUser) => {
            if (!onlineStaff.value.some((u) => u.id === user.id)) {
                onlineStaff.value.push(user);
            }
        })
        .leaving((user: StaffUser) => {
            onlineStaff.value = onlineStaff.value.filter((u) => u.id !== user.id);
        })
        .error((err: any) => {
            console.error('Presence Channel authorization error:', err);
        });

    // 2. Private Conversation Channel
    subscribeToConversationChannel(activeConvId.value);
};

const subscribeToConversationChannel = (convId: number) => {
    if (typeof window === 'undefined' || !window.Echo || !convId) return;

    const privateChannelName = `company.${props.companyId}.conversation.${convId}`;

    window.Echo.private(privateChannelName)
        .listen('.message.sent', (event: any) => {
            if (event.conversation_id === activeConvId.value) {
                const existingIndex = messages.value.findIndex((m) => m.id === event.id);
                if (existingIndex !== -1) {
                    messages.value[existingIndex] = event;
                } else if (event.sender.id === props.currentUser.id) {
                    // Check if an optimistic temp message exists from the current user
                    const tempIndex = messages.value.findIndex((m) => typeof m.id === 'number' && m.id > 1000000000000);
                    if (tempIndex !== -1) {
                        messages.value[tempIndex] = event;
                    } else {
                        messages.value.push(event);
                    }
                    scrollToBottom();
                } else {
                    messages.value.push(event);
                    scrollToBottom();
                }
            }

            const conv = conversationsList.value.find((c) => c.id === event.conversation_id);
            if (conv) {
                conv.latest_message = {
                    id: event.id,
                    body: event.body,
                    sender_id: event.sender.id,
                    sender_name: event.sender.name,
                    created_at: 'Just now',
                };
                if (event.conversation_id !== activeConvId.value) {
                    conv.unread_count = (conv.unread_count || 0) + 1;
                }
                conv.last_message_at = event.created_at;
            }
        })
        .listen('.message.updated', (event: any) => {
            if (event.conversation_id === activeConvId.value) {
                const idx = messages.value.findIndex((m) => m.id === event.id);
                if (idx !== -1) {
                    messages.value[idx] = event;
                }
            }
            const conv = conversationsList.value.find((c) => c.id === event.conversation_id);
            if (conv && conv.latest_message?.id === event.id) {
                conv.latest_message.body = event.body;
            }
        })
        .listen('.message.deleted', (event: any) => {
            if (event.conversation_id === activeConvId.value) {
                const idx = messages.value.findIndex((m) => m.id === event.id);
                if (idx !== -1) {
                    messages.value[idx].is_deleted = true;
                    messages.value[idx].body = '🚫 This message was deleted';
                    messages.value[idx].attachments = null;
                }
            }
            const conv = conversationsList.value.find((c) => c.id === event.conversation_id);
            if (conv && conv.latest_message?.id === event.id) {
                conv.latest_message.body = '🚫 This message was deleted';
            }
        })
        .listen('.message.reacted', (event: any) => {
            if (event.conversation_id === activeConvId.value) {
                const msg = messages.value.find((m) => m.id === event.id);
                if (msg) {
                    msg.reactions = event.reactions;
                }
            }
        })
        .listen('.messages.read', (event: { conversation_id: number; reader_id: number }) => {
            if (event.conversation_id === activeConvId.value) {
                messages.value.forEach((m) => {
                    if (m.sender.id === props.currentUser.id) {
                        m.is_read = true;
                    }
                });
            }
        })
        .listenForWhisper('seen', (event: { reader_id: number }) => {
            if (event.reader_id !== props.currentUser.id) {
                messages.value.forEach((m) => {
                    if (m.sender.id === props.currentUser.id) {
                        m.is_read = true;
                    }
                });
            }
        })
        .listenForWhisper('typing', (event: { id: number; name: string }) => {
            if (event.id !== props.currentUser.id) {
                typingUser.value = event.name;
                clearTimeout(typingTimeout);
                typingTimeout = setTimeout(() => {
                    typingUser.value = null;
                }, 2500);
            }
        });
};

const selectConversation = async (convId: number) => {
    if (activeConvId.value === convId) return;

    if (typeof window !== 'undefined' && window.Echo && activeConvId.value) {
        window.Echo.leave(`company.${props.companyId}.conversation.${activeConvId.value}`);
    }

    activeConvId.value = convId;
    typingUser.value = null;
    cancelVoiceRecording();
    removeSelectedFile();

    const conv = conversationsList.value.find((c) => c.id === convId);
    if (conv) {
        conv.unread_count = 0;
    }

    subscribeToConversationChannel(convId);

    try {
        const response = await fetch(`/admin/chat/conversations/${convId}/messages`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (response.ok) {
            const data = await response.json();
            messages.value = data.messages || [];
            scrollToBottom();
        }
    } catch (e) {
        console.error('Error loading conversation messages:', e);
    }
};

// =========================================================================
// FILE ATTACHMENT HANDLERS
// =========================================================================

const triggerFileSelect = () => {
    fileInputRef.value?.click();
};

const onFileSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (!target.files || target.files.length === 0) return;

    const file = target.files[0];
    selectedFile.value = file;

    if (file.type.startsWith('image/')) {
        filePreviewUrl.value = URL.createObjectURL(file);
    } else {
        filePreviewUrl.value = null;
    }
};

const removeSelectedFile = () => {
    selectedFile.value = null;
    if (filePreviewUrl.value) {
        URL.revokeObjectURL(filePreviewUrl.value);
        filePreviewUrl.value = null;
    }
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

// =========================================================================
// VOICE RECORDING HANDLERS
// =========================================================================

const startVoiceRecording = async () => {
    if (isRecordingVoice.value) return;

    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        audioChunks = [];

        const options = MediaRecorder.isTypeSupported('audio/webm')
            ? { mimeType: 'audio/webm' }
            : (MediaRecorder.isTypeSupported('audio/ogg') ? { mimeType: 'audio/ogg' } : undefined);

        mediaRecorder = new MediaRecorder(stream, options);

        mediaRecorder.ondataavailable = (event) => {
            if (event.data.size > 0) {
                audioChunks.push(event.data);
            }
        };

        mediaRecorder.start(100);
        isRecordingVoice.value = true;
        voiceRecordDuration.value = 0;

        voiceTimer = setInterval(() => {
            voiceRecordDuration.value++;
        }, 1000);
    } catch (err) {
        console.error('Microphone access denied:', err);
        alert('Microphone access was denied or not supported on this browser.');
    }
};

const cancelVoiceRecording = () => {
    if (!isRecordingVoice.value) return;

    if (mediaRecorder) {
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach((track) => track.stop());
        mediaRecorder = null;
    }
    clearInterval(voiceTimer);
    isRecordingVoice.value = false;
    voiceRecordDuration.value = 0;
    audioChunks = [];
};

const stopAndSendVoiceRecording = () => {
    if (!mediaRecorder || !isRecordingVoice.value) return;

    mediaRecorder.onstop = async () => {
        const mimeType = mediaRecorder?.mimeType || 'audio/webm';
        const audioBlob = new Blob(audioChunks, { type: mimeType });
        const ext = mimeType.includes('ogg') ? 'ogg' : (mimeType.includes('mp4') ? 'm4a' : 'webm');
        const audioFile = new File([audioBlob], `voice-note-${Date.now()}.${ext}`, { type: mimeType });

        mediaRecorder?.stream.getTracks().forEach((track) => track.stop());
        mediaRecorder = null;
        audioChunks = [];

        await sendPayload({
            file: audioFile,
            type: 'voice',
            body: 'Voice Message',
        });
    };

    mediaRecorder.stop();
    clearInterval(voiceTimer);
    isRecordingVoice.value = false;
    voiceRecordDuration.value = 0;
};

// =========================================================================
// AUDIO PLAYBACK HANDLER
// =========================================================================

const toggleAudioPlayback = (msgId: number) => {
    const audioEl = audioPlayerRefs.value[msgId];
    if (!audioEl) return;

    if (currentlyPlayingAudioId.value === msgId) {
        audioEl.pause();
        currentlyPlayingAudioId.value = null;
    } else {
        if (currentlyPlayingAudioId.value !== null) {
            const prev = audioPlayerRefs.value[currentlyPlayingAudioId.value];
            if (prev) prev.pause();
        }
        audioEl.play();
        currentlyPlayingAudioId.value = msgId;

        audioEl.onended = () => {
            currentlyPlayingAudioId.value = null;
        };
    }
};

// =========================================================================
// SEND MESSAGE / PAYLOAD
// =========================================================================

const sendPayload = async (payload: { body?: string; file?: File | null; type?: string }) => {
    if (!activeConvId.value || isSending.value) return;

    isSending.value = true;

    const formData = new FormData();
    if (payload.body) formData.append('body', payload.body);
    if (payload.file) formData.append('file', payload.file);
    if (payload.type) formData.append('type', payload.type);
    if (replyingTo.value) formData.append('reply_to_id', String(replyingTo.value.id));

    const tempId = Date.now();
    let tempAttachments: AttachmentItem[] | undefined;
    if (payload.file) {
        tempAttachments = [
            {
                url: URL.createObjectURL(payload.file),
                name: payload.file.name,
                size: payload.file.size,
                mime: payload.file.type,
            },
        ];
    }

    const repliedMsg = replyingTo.value;
    const optimisticMsg: ChatMessage = {
        id: tempId,
        public_id: `temp-${tempId}`,
        conversation_id: activeConvId.value,
        body: payload.body || (payload.type === 'voice' ? 'Voice Message' : payload.file?.name || 'Attachment'),
        type: payload.type || (payload.file ? (payload.file.type.startsWith('image/') ? 'image' : 'file') : 'text'),
        attachments: tempAttachments,
        is_deleted: false,
        edited_at: null,
        reactions: {},
        reply_to: repliedMsg ? {
            id: repliedMsg.id,
            body: repliedMsg.is_deleted ? 'This message was deleted' : repliedMsg.body,
            sender_name: repliedMsg.sender.name,
        } : null,
        created_at: new Date().toISOString(),
        sender: {
            id: props.currentUser.id,
            name: props.currentUser.name,
            avatar_url: props.currentUser.avatar_url,
        },
    };
    messages.value.push(optimisticMsg);
    scrollToBottom();

    removeSelectedFile();
    newMessageText.value = '';
    replyingTo.value = null;

    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        const fetchHeaders: Record<string, string> = {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        };

        // Pass socket ID so Laravel broadcast()->toOthers() ignores sender
        if (typeof window !== 'undefined' && window.Echo && typeof window.Echo.socketId === 'function') {
            const socketId = window.Echo.socketId();
            if (socketId) {
                fetchHeaders['X-Socket-ID'] = socketId;
            }
        }

        const res = await fetch(`/admin/chat/conversations/${activeConvId.value}/messages`, {
            method: 'POST',
            headers: fetchHeaders,
            body: formData,
        });

        if (res.ok) {
            const data = await res.json();
            if (data.message) {
                // If real message already inserted by WebSocket listener, remove temp item
                const realIndex = messages.value.findIndex((m) => m.id === data.message.id);
                const tempIndex = messages.value.findIndex((m) => m.id === tempId);

                if (realIndex !== -1 && tempIndex !== -1 && realIndex !== tempIndex) {
                    messages.value.splice(tempIndex, 1);
                } else if (tempIndex !== -1) {
                    messages.value[tempIndex] = data.message;
                }
            }

            const conv = conversationsList.value.find((c) => c.id === activeConvId.value);
            if (conv && data.message) {
                conv.latest_message = {
                    id: data.message.id,
                    body: data.message.body,
                    sender_id: props.currentUser.id,
                    sender_name: props.currentUser.name,
                    created_at: 'Just now',
                };
            }
        }
    } catch (err) {
        console.error('Failed to send message:', err);
    } finally {
        isSending.value = false;
        scrollToBottom();
    }
};

// WhatsApp Action Handlers
const startReply = (msg: ChatMessage) => {
    if (msg.is_deleted) return;
    replyingTo.value = msg;
    activeReactionPickerMessageId.value = null;
    nextTick(() => {
        const inputEl = document.querySelector('input[placeholder="Type a message..."]') as HTMLInputElement;
        inputEl?.focus();
    });
};

const cancelReply = () => {
    replyingTo.value = null;
};

const startEdit = (msg: ChatMessage) => {
    if (msg.is_deleted) return;
    editingMessage.value = msg;
    editingText.value = msg.body;
    activeReactionPickerMessageId.value = null;
};

const cancelEdit = () => {
    editingMessage.value = null;
    editingText.value = '';
    isSavingEdit.value = false;
};

const saveEdit = async () => {
    if (!editingMessage.value || !editingText.value.trim() || isSavingEdit.value) return;

    isSavingEdit.value = true;
    const msgId = editingMessage.value.id;
    const newBody = editingText.value.trim();

    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        const fetchHeaders: Record<string, string> = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        };

        const res = await fetch(`/admin/chat/conversations/${activeConvId.value}/messages/${msgId}`, {
            method: 'PUT',
            headers: fetchHeaders,
            body: JSON.stringify({ body: newBody }),
        });

        if (res.ok) {
            const data = await res.json();
            const idx = messages.value.findIndex((m) => m.id === msgId);
            if (idx !== -1 && data.message) {
                messages.value[idx] = data.message;
            }
            cancelEdit();
        }
    } catch (err) {
        console.error('Failed to edit message:', err);
    } finally {
        isSavingEdit.value = false;
    }
};

const confirmDelete = (msg: ChatMessage) => {
    messageToDelete.value = msg;
    showDeleteConfirmModal.value = true;
    activeReactionPickerMessageId.value = null;
};

const submitDelete = async () => {
    if (!messageToDelete.value || isDeletingMessage.value) return;

    isDeletingMessage.value = true;
    const msgId = messageToDelete.value.id;

    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        const fetchHeaders: Record<string, string> = {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        };

        const res = await fetch(`/admin/chat/conversations/${activeConvId.value}/messages/${msgId}`, {
            method: 'DELETE',
            headers: fetchHeaders,
        });

        if (res.ok) {
            const idx = messages.value.findIndex((m) => m.id === msgId);
            if (idx !== -1) {
                messages.value[idx].is_deleted = true;
                messages.value[idx].body = '🚫 This message was deleted';
                messages.value[idx].attachments = null;
            }
            showDeleteConfirmModal.value = false;
            messageToDelete.value = null;
        }
    } catch (err) {
        console.error('Failed to delete message:', err);
    } finally {
        isDeletingMessage.value = false;
    }
};

const toggleReaction = async (msg: ChatMessage, emoji: string) => {
    if (msg.is_deleted) return;
    activeReactionPickerMessageId.value = null;

    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        const fetchHeaders: Record<string, string> = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        };

        // Optimistic toggle
        const reactions = { ...(msg.reactions || {}) };
        if (!reactions[emoji]) reactions[emoji] = [];
        if (reactions[emoji].includes(props.currentUser.id)) {
            reactions[emoji] = reactions[emoji].filter((id) => id !== props.currentUser.id);
            if (reactions[emoji].length === 0) delete reactions[emoji];
        } else {
            reactions[emoji].push(props.currentUser.id);
        }
        msg.reactions = reactions;

        const res = await fetch(`/admin/chat/conversations/${activeConvId.value}/messages/${msg.id}/react`, {
            method: 'POST',
            headers: fetchHeaders,
            body: JSON.stringify({ emoji }),
        });

        if (res.ok) {
            const data = await res.json();
            msg.reactions = data.reactions;
        }
    } catch (err) {
        console.error('Failed to toggle reaction:', err);
    }
};

const handleSendButtonClick = () => {
    const text = newMessageText.value.trim();
    if (!text && !selectedFile.value) return;

    sendPayload({
        body: text,
        file: selectedFile.value,
        type: selectedFile.value ? (selectedFile.value.type.startsWith('image/') ? 'image' : 'file') : 'text',
    });
};

// WhatsApp Focus & Typing Handlers
const handleFocusAndMarkRead = () => {
    if (activeConvId.value) {
        // Mark as read on server
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        fetch(`/admin/chat/conversations/${activeConvId.value}/read`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).catch(() => {});

        // Whisper 'seen' instantaneously to the other participant
        if (window.Echo) {
            window.Echo.private(`company.${props.companyId}.conversation.${activeConvId.value}`)
                .whisper('seen', {
                    reader_id: props.currentUser.id,
                });
        }
    }
};

// Whisper "User is typing..."
const handleTyping = () => {
    const now = Date.now();
    handleFocusAndMarkRead();

    if (now - lastTypingTime > 1500 && activeConvId.value && window.Echo) {
        lastTypingTime = now;
        window.Echo.private(`company.${props.companyId}.conversation.${activeConvId.value}`)
            .whisper('typing', {
                id: props.currentUser.id,
                name: props.currentUser.name,
            });
    }
};

const startDirectChatWith = (staffId: number) => {
    router.post('/admin/chat/direct', {
        target_user_id: staffId,
    }, {
        preserveScroll: true,
    });
};

const createGroupChat = () => {
    if (!groupTitle.value.trim() || selectedStaffIds.value.length === 0) return;

    router.post('/admin/chat/group', {
        title: groupTitle.value.trim(),
        user_ids: selectedStaffIds.value,
    }, {
        onSuccess: () => {
            showGroupModal.value = false;
            groupTitle.value = '';
            selectedStaffIds.value = [];
        },
    });
};

onMounted(() => {
    setupWebSocket();
    scrollToBottom();
});

onUnmounted(() => {
    cancelVoiceRecording();
    if (typeof window !== 'undefined' && window.Echo) {
        window.Echo.leave(`company.${props.companyId}.presence`);
        if (activeConvId.value) {
            window.Echo.leave(`company.${props.companyId}.conversation.${activeConvId.value}`);
        }
    }
});

const formatTime = (dateStr?: string) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <OrganizationLayout title="Team Chat">
        <Head title="Team Chat" />

        <div class="h-[calc(100vh-140px)] min-h-[580px] rounded-2xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900 overflow-hidden flex">
            <!-- Left Sidebar (Conversations & Direct Contacts) -->
            <div class="w-80 md:w-96 flex flex-col border-r border-zinc-200/80 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-950/40 shrink-0">
                <!-- Sidebar Header -->
                <div class="p-4 border-b border-zinc-200/80 dark:border-zinc-800/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Messages</h2>
                        <button
                            type="button"
                            @click="showGroupModal = true"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-violet-500 transition-colors cursor-pointer"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            <span>New Group</span>
                        </button>
                    </div>

                    <!-- Search Input with fixed Dark Mode Focus -->
                    <div class="relative">
                        <Search class="absolute left-3 top-2.5 h-4 w-4 text-zinc-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search messages or staff..."
                            class="w-full rounded-xl border border-zinc-200 bg-white pl-9 pr-3 py-1.5 text-xs text-zinc-900 placeholder-zinc-400 focus:border-violet-500 focus:bg-white dark:focus:bg-zinc-900 focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100 dark:placeholder-zinc-500 transition-colors"
                        />
                    </div>
                </div>

                <!-- Online Staff Avatars Row -->
                <div v-if="companyStaff.length > 0" class="px-4 py-3 border-b border-zinc-200/60 dark:border-zinc-800/60">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Team Members
                        </span>
                        <span class="text-[10px] text-zinc-400">{{ onlineStaff.length }} active</span>
                    </div>

                    <div class="flex items-center gap-2.5 overflow-x-auto pb-1 scrollbar-none">
                        <div
                            v-for="staff in companyStaff"
                            :key="staff.id"
                            @click="startDirectChatWith(staff.id)"
                            class="group relative flex flex-col items-center shrink-0 cursor-pointer"
                            :title="staff.name"
                        >
                            <div class="relative">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200/60 dark:border-violet-800/60 transition-transform group-hover:scale-105">
                                    {{ staff.name.charAt(0) }}
                                </div>
                                <span
                                    class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full ring-2 ring-white dark:ring-zinc-900"
                                    :class="isUserOnline(staff.id) ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-700'"
                                />
                            </div>
                            <span class="mt-1 max-w-[54px] truncate text-[10px] font-medium text-zinc-600 group-hover:text-violet-600 dark:text-zinc-400">
                                {{ staff.name.split(' ')[0] }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Conversations List -->
                <div class="flex-1 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-800/40">
                    <div
                        v-for="conv in filteredConversations"
                        :key="conv.id"
                        @click="selectConversation(conv.id)"
                        class="flex items-center gap-3 p-3.5 cursor-pointer transition-colors"
                        :class="conv.id === activeConvId
                            ? 'bg-violet-50/80 dark:bg-violet-950/30'
                            : 'hover:bg-zinc-100/60 dark:hover:bg-zinc-900/60'"
                    >
                        <div class="relative shrink-0">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-zinc-100 text-sm font-bold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border border-zinc-200/70 dark:border-zinc-700">
                                <Users v-if="conv.type === 'group'" class="h-5 w-5 text-violet-600 dark:text-violet-400" />
                                <span v-else>{{ conv.title.charAt(0) }}</span>
                            </div>
                            <span
                                v-if="conv.type === 'direct' && conv.other_user_id"
                                class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full ring-2 ring-white dark:ring-zinc-900"
                                :class="isUserOnline(conv.other_user_id) ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-700'"
                            />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between mb-0.5">
                                <h4 class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ conv.title }}
                                </h4>
                                <span class="text-[10px] text-zinc-400 shrink-0">
                                    {{ conv.latest_message?.created_at || '' }}
                                </span>
                            </div>
                            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                <span v-if="conv.latest_message?.sender_name" class="font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ conv.latest_message.sender_id === currentUser.id ? 'You' : conv.latest_message.sender_name }}:
                                </span>
                                {{ conv.latest_message?.body || 'No messages yet' }}
                            </p>
                        </div>

                        <span
                            v-if="conv.unread_count > 0"
                            class="flex h-5 min-w-5 items-center justify-center rounded-full bg-violet-600 px-1.5 text-[10px] font-bold text-white shrink-0"
                        >
                            {{ conv.unread_count }}
                        </span>
                    </div>

                    <div v-if="filteredConversations.length === 0" class="p-8 text-center text-xs text-zinc-400">
                        No conversations found.
                    </div>
                </div>
            </div>

            <!-- Right Main Chat Workspace -->
            <div class="flex-1 flex flex-col min-w-0 bg-white dark:bg-zinc-900">
                <template v-if="activeConversation">
                    <!-- Clean Conversation Header -->
                    <div class="h-16 flex items-center justify-between border-b border-zinc-200/80 px-6 dark:border-zinc-800/80 shrink-0">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="relative">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-600 text-sm font-bold text-white shadow-xs">
                                    <Users v-if="activeConversation.type === 'group'" class="h-5 w-5" />
                                    <span v-else>{{ activeConversation.title.charAt(0) }}</span>
                                </div>
                                <span
                                    v-if="activeConversation.type === 'direct' && activeConversation.other_user_id"
                                    class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full ring-2 ring-white dark:ring-zinc-900"
                                    :class="isUserOnline(activeConversation.other_user_id) ? 'bg-emerald-500' : 'bg-zinc-400'"
                                />
                            </div>
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ activeConversation.title }}
                                </h3>
                                <p v-if="typingUser" class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1 animate-pulse">
                                    <span>typing...</span>
                                </p>
                                <p
                                    v-else-if="activeConversation.type === 'direct' && activeConversation.other_user_id"
                                    class="text-[11px] font-medium"
                                    :class="isUserOnline(activeConversation.other_user_id) ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400'"
                                >
                                    {{ isUserOnline(activeConversation.other_user_id) ? 'Active Now' : 'Offline' }}
                                </p>
                                <p v-else class="text-[11px] text-zinc-400 truncate">
                                    {{ activeConversation.participants.length }} members
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Scrollable Messages Area -->
                    <div
                        ref="messagesContainer"
                        class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 bg-zinc-50/30 dark:bg-zinc-950/20"
                    >
                        <div v-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center py-12">
                            <MessageSquare class="h-10 w-10 text-zinc-300 dark:text-zinc-700 mb-2" />
                            <h4 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">No messages yet</h4>
                            <p class="text-xs text-zinc-400 max-w-sm mt-1">
                                Send a message to start the conversation.
                            </p>
                        </div>

                        <div
                            v-for="msg in messages"
                            :key="msg.id"
                            class="group relative flex items-end gap-2.5"
                            :class="msg.sender.id === currentUser.id ? 'justify-end' : 'justify-start'"
                        >
                            <!-- Avatar (for received messages) -->
                            <div
                                v-if="msg.sender.id !== currentUser.id"
                                class="flex h-7 w-7 items-center justify-center rounded-full bg-zinc-200 text-xs font-bold text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300 shrink-0"
                            >
                                {{ msg.sender.name.charAt(0) }}
                            </div>

                            <!-- Message Wrapper -->
                            <div class="relative max-w-[75%] sm:max-w-[70%]">
                                <!-- Floating WhatsApp Action Bar (Hover Trigger) -->
                                <div
                                    v-if="!msg.is_deleted"
                                    class="absolute -top-7 z-20 hidden group-hover:flex items-center gap-0.5 rounded-lg border border-zinc-200 bg-white/95 px-1 py-0.5 shadow-md backdrop-blur-xs dark:border-zinc-700 dark:bg-zinc-800/95 transition-opacity"
                                    :class="msg.sender.id === currentUser.id ? 'right-0' : 'left-0'"
                                >
                                    <!-- Emoji React -->
                                    <button
                                        type="button"
                                        @click="activeReactionPickerMessageId = activeReactionPickerMessageId === msg.id ? null : msg.id"
                                        class="rounded p-1 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-zinc-200 cursor-pointer transition-colors"
                                        title="React"
                                    >
                                        <Smile class="h-3.5 w-3.5" />
                                    </button>

                                    <!-- Reply -->
                                    <button
                                        type="button"
                                        @click="startReply(msg)"
                                        class="rounded p-1 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-zinc-200 cursor-pointer transition-colors"
                                        title="Reply"
                                    >
                                        <Reply class="h-3.5 w-3.5" />
                                    </button>

                                    <!-- Edit (Sender & text only) -->
                                    <button
                                        v-if="msg.sender.id === currentUser.id && msg.type === 'text'"
                                        type="button"
                                        @click="startEdit(msg)"
                                        class="rounded p-1 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-zinc-200 cursor-pointer transition-colors"
                                        title="Edit"
                                    >
                                        <Edit2 class="h-3.5 w-3.5" />
                                    </button>

                                    <!-- Delete (Sender only) -->
                                    <button
                                        v-if="msg.sender.id === currentUser.id"
                                        type="button"
                                        @click="confirmDelete(msg)"
                                        class="rounded p-1 text-zinc-500 hover:bg-red-50 hover:text-red-600 dark:text-zinc-400 dark:hover:bg-red-950/50 dark:hover:text-red-400 cursor-pointer transition-colors"
                                        title="Delete for everyone"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>

                                <!-- Floating Emoji Picker Tray -->
                                <div
                                    v-if="activeReactionPickerMessageId === msg.id"
                                    class="absolute -top-12 z-30 flex items-center gap-1 rounded-full border border-zinc-200 bg-white px-2 py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
                                    :class="msg.sender.id === currentUser.id ? 'right-0' : 'left-0'"
                                >
                                    <button
                                        v-for="emoji in availableEmojis"
                                        :key="emoji"
                                        type="button"
                                        @click="toggleReaction(msg, emoji)"
                                        class="flex h-7 w-7 items-center justify-center rounded-full hover:scale-125 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-all text-sm cursor-pointer"
                                    >
                                        {{ emoji }}
                                    </button>
                                </div>

                                <!-- Message Bubble Box -->
                                <div
                                    class="rounded-2xl px-4 py-2.5 shadow-xs text-xs sm:text-sm"
                                    :class="[
                                        msg.is_deleted
                                            ? 'border border-zinc-200 bg-zinc-100/70 text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/60 dark:text-zinc-400 italic'
                                            : (msg.sender.id === currentUser.id
                                                ? 'bg-violet-600 text-white rounded-br-none'
                                                : 'bg-white text-zinc-900 border border-zinc-200/80 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-100 rounded-bl-none')
                                    ]"
                                >
                                    <!-- Sender Name for Groups -->
                                    <p
                                        v-if="activeConversation.type === 'group' && msg.sender.id !== currentUser.id && !msg.is_deleted"
                                        class="text-[10px] font-bold text-violet-600 dark:text-violet-400 mb-1"
                                    >
                                        {{ msg.sender.name }}
                                    </p>

                                    <!-- Quoted / Replied Snippet (WhatsApp style) -->
                                    <div
                                        v-if="msg.reply_to && !msg.is_deleted"
                                        class="mb-2 rounded-lg border-l-3 px-2.5 py-1.5 text-xs select-none"
                                        :class="msg.sender.id === currentUser.id
                                            ? 'border-violet-300 bg-violet-700/60 text-violet-100'
                                            : 'border-violet-500 bg-zinc-100 dark:bg-zinc-700/60 text-zinc-600 dark:text-zinc-300'"
                                    >
                                        <p class="font-bold text-[10px] text-violet-300 dark:text-violet-400">
                                            {{ msg.reply_to.sender_name }}
                                        </p>
                                        <p class="truncate text-[11px] opacity-90">
                                            {{ msg.reply_to.body }}
                                        </p>
                                    </div>

                                    <!-- Deleted Message Notification -->
                                    <div v-if="msg.is_deleted" class="flex items-center gap-1.5 py-0.5 text-zinc-500 dark:text-zinc-400">
                                        <span class="text-xs">🚫</span>
                                        <span class="italic text-xs">This message was deleted</span>
                                    </div>

                                    <!-- Voice Message Player -->
                                    <div v-else-if="msg.type === 'voice' && msg.attachments && msg.attachments.length > 0" class="py-1">
                                        <div class="flex items-center gap-3">
                                            <button
                                                type="button"
                                                @click="toggleAudioPlayback(msg.id)"
                                                class="flex h-8 w-8 items-center justify-center rounded-full transition-transform active:scale-95 shrink-0 cursor-pointer"
                                                :class="msg.sender.id === currentUser.id
                                                    ? 'bg-white text-violet-600 shadow-xs hover:bg-violet-50'
                                                    : 'bg-violet-600 text-white shadow-xs hover:bg-violet-500'"
                                            >
                                                <Pause v-if="currentlyPlayingAudioId === msg.id" class="h-3.5 w-3.5" />
                                                <Play v-else class="h-3.5 w-3.5 ml-0.5" />
                                            </button>

                                            <!-- Waveform graphic -->
                                            <div class="flex items-center gap-1 h-5 flex-1 min-w-[120px]">
                                                <span
                                                    v-for="bar in 16"
                                                    :key="bar"
                                                    class="w-1 rounded-full transition-all"
                                                    :style="{ height: `${Math.sin(bar * 0.8) * 9 + 11}px` }"
                                                    :class="msg.sender.id === currentUser.id
                                                        ? (currentlyPlayingAudioId === msg.id ? 'bg-white animate-pulse' : 'bg-violet-300')
                                                        : (currentlyPlayingAudioId === msg.id ? 'bg-violet-600 animate-pulse' : 'bg-zinc-300 dark:bg-zinc-600')"
                                                />
                                            </div>

                                            <span
                                                class="text-[11px] font-mono shrink-0"
                                                :class="msg.sender.id === currentUser.id ? 'text-violet-200' : 'text-zinc-500 dark:text-zinc-400'"
                                            >
                                                Voice
                                            </span>
                                        </div>
                                        <audio
                                            :ref="(el) => { if (el) audioPlayerRefs[msg.id] = el as HTMLAudioElement; }"
                                            :src="msg.attachments[0].url"
                                            class="hidden"
                                        />
                                    </div>

                                    <!-- Image Attachment -->
                                    <div v-else-if="msg.type === 'image' && msg.attachments && msg.attachments.length > 0" class="my-1">
                                        <a :href="msg.attachments[0].url" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-xl">
                                            <img
                                                :src="msg.attachments[0].url"
                                                :alt="msg.attachments[0].name"
                                                class="max-h-60 max-w-full rounded-xl object-cover hover:opacity-95 transition-opacity"
                                            />
                                        </a>
                                        <p v-if="msg.body && msg.body !== msg.attachments[0].name" class="mt-1.5 leading-relaxed whitespace-pre-wrap break-words">
                                            {{ msg.body }}
                                        </p>
                                    </div>

                                    <!-- File Attachment -->
                                    <div v-else-if="msg.type === 'file' && msg.attachments && msg.attachments.length > 0" class="my-1">
                                        <a
                                            :href="msg.attachments[0].url"
                                            target="_blank"
                                            download
                                            class="flex items-center gap-2.5 rounded-xl p-2 transition-colors"
                                            :class="msg.sender.id === currentUser.id
                                                ? 'bg-violet-700/60 hover:bg-violet-700 text-white'
                                                : 'bg-zinc-100 hover:bg-zinc-200 text-zinc-900 dark:bg-zinc-700/60 dark:hover:bg-zinc-700 dark:text-white'"
                                        >
                                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-black/10 shrink-0">
                                                <FileText class="h-4 w-4" />
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate font-medium text-xs">{{ msg.attachments[0].name }}</p>
                                                <span class="text-[10px] opacity-75">{{ formatFileSize(msg.attachments[0].size) }}</span>
                                            </div>
                                            <Download class="h-4 w-4 shrink-0 opacity-80" />
                                        </a>
                                        <p v-if="msg.body && msg.body !== msg.attachments[0].name" class="mt-1.5 leading-relaxed whitespace-pre-wrap break-words">
                                            {{ msg.body }}
                                        </p>
                                    </div>

                                    <!-- Standard Text Message -->
                                    <p v-else class="leading-relaxed whitespace-pre-wrap break-words">{{ msg.body }}</p>

                                    <!-- Timestamp, (edited) label & WhatsApp Blue Double Checks -->
                                    <div
                                        class="flex items-center justify-end gap-1 mt-1 text-[10px]"
                                        :class="msg.sender.id === currentUser.id ? 'text-violet-200' : 'text-zinc-400'"
                                    >
                                        <span v-if="msg.edited_at && !msg.is_deleted" class="italic opacity-80">(edited)</span>
                                        <span>{{ formatTime(msg.created_at) }}</span>
                                        <template v-if="msg.sender.id === currentUser.id && !msg.is_deleted">
                                            <!-- Blue Double Tick when Seen / Read -->
                                            <CheckCheck
                                                v-if="msg.is_read"
                                                class="h-3.5 w-3.5 text-cyan-300 drop-shadow-xs"
                                                title="Read"
                                            />
                                            <!-- Delivered Double Tick -->
                                            <CheckCheck
                                                v-else-if="typeof msg.id === 'number' && msg.id < 1000000000000"
                                                class="h-3.5 w-3.5 text-violet-300/80"
                                                title="Delivered"
                                            />
                                            <!-- Sending Single Tick -->
                                            <Check
                                                v-else
                                                class="h-3 w-3 text-violet-300/70"
                                                title="Sent"
                                            />
                                        </template>
                                    </div>
                                </div>

                                <!-- Reactions Badges (WhatsApp Pills) -->
                                <div
                                    v-if="msg.reactions && Object.keys(msg.reactions).length > 0 && !msg.is_deleted"
                                    class="flex flex-wrap gap-1 mt-1"
                                    :class="msg.sender.id === currentUser.id ? 'justify-end' : 'justify-start'"
                                >
                                    <button
                                        v-for="(userIds, emoji) in msg.reactions"
                                        :key="emoji"
                                        type="button"
                                        @click="toggleReaction(msg, String(emoji))"
                                        class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] shadow-2xs transition-all cursor-pointer"
                                        :class="userIds.includes(currentUser.id)
                                            ? 'border-violet-300 bg-violet-100 text-violet-800 dark:border-violet-700 dark:bg-violet-950 dark:text-violet-200 font-bold'
                                            : 'border-zinc-200 bg-white text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'"
                                        :title="`${userIds.length} reaction${userIds.length > 1 ? 's' : ''}`"
                                    >
                                        <span>{{ emoji }}</span>
                                        <span class="text-[10px]">{{ userIds.length }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Typing Indicator -->
                        <div v-if="typingUser" class="flex items-center gap-2 text-xs text-zinc-500 italic py-1">
                            <span class="flex gap-1">
                                <span class="h-1.5 w-1.5 rounded-full bg-violet-500 animate-bounce" />
                                <span class="h-1.5 w-1.5 rounded-full bg-violet-500 animate-bounce [animation-delay:0.2s]" />
                                <span class="h-1.5 w-1.5 rounded-full bg-violet-500 animate-bounce [animation-delay:0.4s]" />
                            </span>
                            <span>{{ typingUser }} is typing...</span>
                        </div>
                    </div>

                    <!-- Quoted / Replying-to Preview Bar (WhatsApp style) -->
                    <div v-if="replyingTo" class="px-5 py-2.5 border-t border-zinc-200/80 dark:border-zinc-800/80 bg-violet-50/70 dark:bg-violet-950/40 flex items-center justify-between">
                        <div class="flex items-center gap-2.5 min-w-0 border-l-3 border-violet-600 pl-2.5">
                            <div class="min-w-0">
                                <p class="text-[11px] font-bold text-violet-700 dark:text-violet-300 flex items-center gap-1">
                                    <Reply class="h-3 w-3" />
                                    <span>Replying to {{ replyingTo.sender.name }}</span>
                                </p>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 truncate">
                                    {{ replyingTo.body }}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="cancelReply"
                            class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 p-1 cursor-pointer transition-colors"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Selected File Preview Bar -->
                    <div v-if="selectedFile" class="px-5 py-2.5 border-t border-zinc-200/80 dark:border-zinc-800/80 bg-zinc-50 dark:bg-zinc-950 flex items-center justify-between">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <img
                                v-if="filePreviewUrl"
                                :src="filePreviewUrl"
                                alt="Preview"
                                class="h-10 w-10 rounded-lg object-cover border border-zinc-200 dark:border-zinc-700 shrink-0"
                            />
                            <span v-else class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 shrink-0">
                                <FileText class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold truncate text-zinc-900 dark:text-zinc-100">{{ selectedFile.name }}</p>
                                <p class="text-[10px] text-zinc-400">{{ formatFileSize(selectedFile.size) }}</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="removeSelectedFile"
                            class="text-zinc-400 hover:text-red-500 dark:hover:text-red-400 p-1 cursor-pointer"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Message Input Bar -->
                    <div class="border-t border-zinc-200/80 p-3.5 dark:border-zinc-800/80 bg-white dark:bg-zinc-900">
                        <input
                            ref="fileInputRef"
                            type="file"
                            @change="onFileSelected"
                            class="hidden"
                            accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip"
                        />

                        <!-- Voice Recording Active Bar -->
                        <div v-if="isRecordingVoice" class="flex items-center justify-between gap-3 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/60 rounded-xl px-4 py-2">
                            <div class="flex items-center gap-2.5">
                                <span class="relative flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75" />
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-red-600" />
                                </span>
                                <span class="text-xs font-semibold text-red-700 dark:text-red-400">
                                    Recording Voice...
                                </span>
                                <span class="text-xs font-mono font-bold text-red-800 dark:text-red-300 bg-red-100 dark:bg-red-900/60 px-2 py-0.5 rounded-md">
                                    {{ formatDuration(voiceRecordDuration) }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="cancelVoiceRecording"
                                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-zinc-600 hover:bg-red-100 hover:text-red-700 dark:text-zinc-400 dark:hover:bg-red-900/50 dark:hover:text-red-300 transition-colors cursor-pointer"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                    <span>Cancel</span>
                                </button>
                                <button
                                    type="button"
                                    @click="stopAndSendVoiceRecording"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-red-500 shadow-xs transition-colors cursor-pointer"
                                >
                                    <Send class="h-3.5 w-3.5" />
                                    <span>Send</span>
                                </button>
                            </div>
                        </div>

                        <!-- Regular Text Input -->
                        <form v-else @submit.prevent="handleSendButtonClick" class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="triggerFileSelect"
                                class="flex h-9 w-9 items-center justify-center rounded-xl text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors shrink-0 cursor-pointer"
                                title="Attach File"
                            >
                                <Paperclip class="h-4 w-4" />
                            </button>

                            <input
                                v-model="newMessageText"
                                @input="handleTyping"
                                @focus="handleFocusAndMarkRead"
                                type="text"
                                placeholder="Type a message..."
                                class="flex-1 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-2 text-xs sm:text-sm text-zinc-900 placeholder-zinc-400 focus:border-violet-500 focus:bg-white dark:focus:bg-zinc-900 focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100 dark:placeholder-zinc-500 transition-colors"
                            />

                            <button
                                type="button"
                                @click="startVoiceRecording"
                                class="flex h-9 w-9 items-center justify-center rounded-xl text-zinc-500 hover:bg-violet-50 hover:text-violet-600 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-violet-400 transition-colors shrink-0 cursor-pointer"
                                title="Record Voice"
                            >
                                <Mic class="h-4 w-4" />
                            </button>

                            <button
                                type="submit"
                                :disabled="(!newMessageText.trim() && !selectedFile) || isSending"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-violet-500 disabled:opacity-50 transition-colors shrink-0 cursor-pointer"
                            >
                                <Send class="h-3.5 w-3.5" />
                                <span>Send</span>
                            </button>
                        </form>
                    </div>
                </template>

                <div v-else class="flex flex-col items-center justify-center flex-1 p-12 text-center">
                    <MessageSquare class="h-12 w-12 text-zinc-300 dark:text-zinc-700 mb-3" />
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Select a Conversation</h3>
                    <p class="text-xs text-zinc-400 mt-1 max-w-sm">
                        Choose a conversation or click on a colleague from the sidebar to chat.
                    </p>
                </div>
            </div>
        </div>

        <!-- Modal: Edit Message (WhatsApp style) -->
        <div v-if="editingMessage" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                <div class="flex items-center justify-between pb-3 border-b border-zinc-200 dark:border-zinc-800">
                    <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <Edit2 class="h-4 w-4 text-violet-600" />
                        <span>Edit Message</span>
                    </h3>
                    <button type="button" @click="cancelEdit" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 cursor-pointer">
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <div class="mt-4 space-y-4">
                    <textarea
                        v-model="editingText"
                        rows="3"
                        class="w-full rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-xs sm:text-sm text-zinc-900 placeholder-zinc-400 focus:border-violet-500 focus:bg-white dark:focus:bg-zinc-900 focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100 resize-none"
                        placeholder="Edit message..."
                    />
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            @click="cancelEdit"
                            class="rounded-xl px-4 py-2 text-xs font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800 cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="saveEdit"
                            :disabled="!editingText.trim() || isSavingEdit"
                            class="rounded-xl bg-violet-600 px-4 py-2 text-xs font-semibold text-white hover:bg-violet-500 disabled:opacity-50 cursor-pointer"
                        >
                            {{ isSavingEdit ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Delete Confirmation (WhatsApp style) -->
        <div v-if="showDeleteConfirmModal && messageToDelete" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-950/60 dark:text-red-400 mb-3">
                    <Trash2 class="h-6 w-6" />
                </div>
                <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-100">Delete Message?</h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    This message will be deleted for everyone in this conversation.
                </p>
                <div class="flex justify-center gap-2 mt-5">
                    <button
                        type="button"
                        @click="showDeleteConfirmModal = false; messageToDelete = null;"
                        class="rounded-xl px-4 py-2 text-xs font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800 cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitDelete"
                        :disabled="isDeletingMessage"
                        class="rounded-xl bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-500 disabled:opacity-50 cursor-pointer"
                    >
                        {{ isDeletingMessage ? 'Deleting...' : 'Delete for Everyone' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal: New Group Chat -->
        <div v-if="showGroupModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                <div class="flex items-center justify-between pb-4 border-b border-zinc-200 dark:border-zinc-800">
                    <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100">Create Group</h3>
                    <button type="button" @click="showGroupModal = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 cursor-pointer">
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Group Name</label>
                        <input
                            v-model="groupTitle"
                            type="text"
                            placeholder="e.g. Sales Team"
                            class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2 text-xs text-zinc-900 placeholder-zinc-400 focus:border-violet-500 focus:bg-white dark:focus:bg-zinc-900 focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100 dark:placeholder-zinc-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Add Members</label>
                        <div class="max-h-48 overflow-y-auto space-y-2 divide-y divide-zinc-100 dark:divide-zinc-800/60">
                            <label
                                v-for="staff in companyStaff"
                                :key="staff.id"
                                class="flex items-center justify-between py-1.5 cursor-pointer text-xs"
                            >
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 rounded-full bg-violet-100 text-[10px] font-bold text-violet-700 flex items-center justify-center">
                                        {{ staff.name.charAt(0) }}
                                    </div>
                                    <span class="text-zinc-800 dark:text-zinc-200">{{ staff.name }}</span>
                                </div>
                                <input
                                    type="checkbox"
                                    :value="staff.id"
                                    v-model="selectedStaffIds"
                                    class="rounded border-zinc-300 text-violet-600 focus:ring-violet-500"
                                />
                            </label>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                        <button
                            type="button"
                            @click="showGroupModal = false"
                            class="rounded-xl px-4 py-2 text-xs font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800 cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="createGroupChat"
                            :disabled="!groupTitle.trim() || selectedStaffIds.length === 0"
                            class="rounded-xl bg-violet-600 px-4 py-2 text-xs font-semibold text-white hover:bg-violet-500 disabled:opacity-50 cursor-pointer"
                        >
                            Create Group
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
