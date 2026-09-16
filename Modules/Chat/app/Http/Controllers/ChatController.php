<?php

namespace Modules\Chat\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Chat\Events\CompanyAnnouncement;
use Modules\Chat\Events\MessageDeleted;
use Modules\Chat\Events\MessageReacted;
use Modules\Chat\Events\MessageSent;
use Modules\Chat\Events\MessagesRead;
use Modules\Chat\Events\MessageUpdated;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\ConversationParticipant;
use Modules\Chat\Models\Message;
use Modules\Tenancy\Models\Tenant;

class ChatController extends Controller
{
    use ResolvesCurrentTenantId;

    /**
     * Display the Real-time Chat dashboard with conversations and company staff.
     */
    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);
        $user = $request->user();

        // १. यो कम्पनी भित्रका प्रयोगकर्ता सामेल भएका कुराकानीहरू
        $conversations = Conversation::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with([
                'users:id,name,email,avatar_path',
                'latestMessage.sender:id,name,avatar_path',
                'participants' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                },
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Conversation $conversation) use ($user) {
                $myParticipant = $conversation->participants->first();
                $lastReadAt = $myParticipant?->last_read_at;

                // गन्ति: मैले नपढेका नयाँ म्यासेजहरू
                $unreadCount = Message::query()
                    ->where('conversation_id', $conversation->id)
                    ->where('sender_id', '!=', $user->id)
                    ->when($lastReadAt, function ($query, $lastReadAt) {
                        $query->where('created_at', '>', $lastReadAt);
                    })
                    ->count();

                // Direct chat मा अर्को प्रयोगकर्ताको नाम र अवतार पत्ता लगाउने
                $otherUser = $conversation->type === 'direct'
                    ? $conversation->users->firstWhere('id', '!==', $user->id)
                    : null;

                return [
                    'id' => $conversation->id,
                    'public_id' => $conversation->public_id,
                    'type' => $conversation->type,
                    'title' => $conversation->type === 'direct' ? ($otherUser?->name ?? 'Staff') : ($conversation->title ?? 'Group Chat'),
                    'avatar_url' => $otherUser?->avatar_url,
                    'other_user_id' => $otherUser?->id,
                    'other_user_email' => $otherUser?->email,
                    'participants' => $conversation->users->map(fn (User $u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'avatar_url' => $u->avatar_url,
                    ]),
                    'latest_message' => $conversation->latestMessage ? [
                        'id' => $conversation->latestMessage->id,
                        'body' => $conversation->latestMessage->body,
                        'sender_id' => $conversation->latestMessage->sender_id,
                        'sender_name' => $conversation->latestMessage->sender?->name,
                        'created_at' => $conversation->latestMessage->created_at?->diffForHumans(),
                    ] : null,
                    'unread_count' => $unreadCount,
                    'last_message_at' => $conversation->last_message_at?->toISOString(),
                ];
            });

        // २. यो कम्पनी भित्रका सम्पूर्ण स्टाफहरू (नयाँ च्याट सुरु गर्न वा Online Presence हेर्न)
        $companyStaff = Tenant::findOrFail($tenantId)
            ->users()
            ->where('users.id', '!=', $user->id)
            ->select('users.id', 'users.name', 'users.email', 'users.avatar_path')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'avatar_url' => $u->avatar_url,
            ]);

        // ३. Active conversation messages (यदि initial conversation select छ भने)
        $activeConversationId = (int) $request->input('conversation_id', $conversations->first()['id'] ?? 0);
        $initialMessages = [];

        if ($activeConversationId > 0) {
            $initialMessages = $this->fetchConversationMessages($tenantId, $activeConversationId, $user->id);

            // Mark conversation as read
            ConversationParticipant::where('conversation_id', $activeConversationId)
                ->where('user_id', $user->id)
                ->update(['last_read_at' => now()]);
        }

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
            'companyStaff' => $companyStaff,
            'activeConversationId' => $activeConversationId,
            'initialMessages' => $initialMessages,
            'companyId' => $tenantId,
            'currentUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
            ],
            'reverbConfig' => [
                'key' => config('reverb.apps.apps.0.key', env('REVERB_APP_KEY')),
                'host' => env('REVERB_HOST', 'localhost'),
                'port' => (int) env('REVERB_PORT', 8080),
                'scheme' => env('REVERB_SCHEME', 'http'),
            ],
        ]);
    }

    /**
     * Get paginated/recent messages for a conversation.
     */
    public function getMessages(Request $request, int $id): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        $user = $request->user();

        $messages = $this->fetchConversationMessages($tenantId, $id, $user->id);

        return response()->json([
            'messages' => $messages,
        ]);
    }

    /**
     * Send a new message & broadcast over WebSocket via Reverb.
     */
    public function sendMessage(Request $request, int $id): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        $user = $request->user();

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'type' => ['nullable', 'string', 'in:text,image,file,voice'],
            'reply_to_id' => ['nullable', 'integer'],
            'file' => ['nullable', 'file', 'max:25600'], // up to 25MB
        ]);

        if (empty($validated['body']) && ! $request->hasFile('file')) {
            return response()->json([
                'errors' => ['body' => ['Either a message body or a file/voice recording is required.']],
            ], 422);
        }

        // सुरक्षा चेक: Conversation सोही Tenant को हुनुपर्छ र User Participant हुनुपर्छ
        $conversation = Conversation::where('tenant_id', $tenantId)
            ->where('id', $id)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->firstOrFail();

        $type = $validated['type'] ?? 'text';
        $attachments = null;

        // File or Voice Audio Attachment Upload
        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');
            $mime = $uploadedFile->getMimeType() ?: '';
            $path = $uploadedFile->store("chat_uploads/{$tenantId}/{$conversation->id}", 'public');

            if ($type === 'text') {
                if (str_starts_with($mime, 'image/')) {
                    $type = 'image';
                } elseif (str_starts_with($mime, 'audio/')) {
                    $type = 'voice';
                } else {
                    $type = 'file';
                }
            }

            $attachments = [
                [
                    'url' => Storage::url($path),
                    'name' => $uploadedFile->getClientOriginalName(),
                    'size' => $uploadedFile->getSize(),
                    'mime' => $mime,
                ],
            ];
        }

        $bodyText = trim((string) ($validated['body'] ?? ''));
        if ($bodyText === '' && $attachments !== null) {
            $bodyText = $type === 'voice' ? 'Voice Message' : ($attachments[0]['name'] ?? 'Attachment');
        }

        $replyToId = ! empty($validated['reply_to_id']) ? (int) $validated['reply_to_id'] : null;

        $message = Message::create([
            'tenant_id' => $tenantId,
            'conversation_id' => $conversation->id,
            'reply_to_id' => $replyToId,
            'sender_id' => $user->id,
            'body' => $bodyText,
            'type' => $type,
            'attachments' => $attachments,
        ]);

        // Update conversation last message timestamp
        $conversation->update(['last_message_at' => now()]);

        // Eager load relationships for serialization
        $message->load(['sender:id,name,avatar_path', 'replyTo.sender:id,name']);

        // BROADCAST EVENT TO WEBSOCKET SERVER (REVERB)
        // .toOthers() sends to other connected participants without echoing back to current sender
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'public_id' => $message->public_id,
                'conversation_id' => $message->conversation_id,
                'body' => $message->body,
                'type' => $message->type,
                'attachments' => $message->attachments,
                'is_deleted' => false,
                'edited_at' => null,
                'reactions' => [],
                'reply_to' => $message->replyTo ? [
                    'id' => $message->replyTo->id,
                    'body' => $message->replyTo->is_deleted ? 'This message was deleted' : $message->replyTo->body,
                    'sender_name' => $message->replyTo->sender?->name ?? 'User',
                ] : null,
                'created_at' => $message->created_at?->toISOString(),
                'sender' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                ],
            ],
        ]);
    }

    /**
     * Start or retrieve a 1-on-1 Direct Chat with another staff member.
     */
    public function startDirect(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);
        $user = $request->user();

        $validated = $request->validate([
            'target_user_id' => ['required', 'integer', 'different:user_id'],
        ]);

        $targetUserId = (int) $validated['target_user_id'];

        // चेक: target staff पनि यही कम्पनीमा हुनुपर्छ! (Cross-company leak रोक्न)
        $targetUser = Tenant::findOrFail($tenantId)
            ->users()
            ->where('users.id', $targetUserId)
            ->firstOrFail();

        // के यी दुई बीच पहिल्यै direct conversation छ?
        $existing = Conversation::query()
            ->where('tenant_id', $tenantId)
            ->where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $targetUserId))
            ->first();

        if ($existing) {
            return redirect()->route('admin.chat.index', ['conversation_id' => $existing->id]);
        }

        // नयाँ कुराकानी सुरु गर्ने
        $conversation = Conversation::create([
            'tenant_id' => $tenantId,
            'type' => 'direct',
            'created_by' => $user->id,
            'last_message_at' => now(),
        ]);

        $conversation->participants()->createMany([
            ['user_id' => $user->id, 'role' => 'admin'],
            ['user_id' => $targetUserId, 'role' => 'member'],
        ]);

        return redirect()->route('admin.chat.index', ['conversation_id' => $conversation->id]);
    }

    /**
     * Start a Group Chat between multiple staff members in this company.
     */
    public function startGroup(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer'],
        ]);

        $memberIds = array_unique(array_map('intval', $validated['user_ids']));

        // प्रमाणीकरण: सबै सदस्यहरू यही कम्पनीका हुनुपर्छ
        $validMembersCount = Tenant::findOrFail($tenantId)
            ->users()
            ->whereIn('users.id', $memberIds)
            ->count();

        if ($validMembersCount !== count($memberIds)) {
            abort(422, 'Some selected staff members do not belong to your company.');
        }

        $conversation = Conversation::create([
            'tenant_id' => $tenantId,
            'type' => 'group',
            'title' => $validated['title'],
            'created_by' => $user->id,
            'last_message_at' => now(),
        ]);

        // Add creator
        $conversation->participants()->create([
            'user_id' => $user->id,
            'role' => 'admin',
        ]);

        // Add selected members
        foreach ($memberIds as $mId) {
            if ($mId !== $user->id) {
                $conversation->participants()->create([
                    'user_id' => $mId,
                    'role' => 'member',
                ]);
            }
        }

        return redirect()->route('admin.chat.index', ['conversation_id' => $conversation->id]);
    }

    /**
     * Mark conversation messages as read.
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        $user = $request->user();

        ConversationParticipant::query()
            ->where('conversation_id', $id)
            ->where('user_id', $user->id)
            ->whereHas('conversation', fn ($q) => $q->where('tenant_id', $tenantId))
            ->update(['last_read_at' => now()]);

        // Mark all messages from other senders as read
        Message::query()
            ->where('tenant_id', $tenantId)
            ->where('conversation_id', $id)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // Broadcast to notify senders that their messages were seen (Blue Tick)
        broadcast(new MessagesRead($tenantId, $id, $user->id))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Broadcast a Company-wide Public Announcement (Public Channel Demo).
     */
    public function broadcastAnnouncement(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        // Public Channel Broadcast
        broadcast(new CompanyAnnouncement(
            $tenantId,
            $validated['title'],
            $validated['message'],
            $user->name
        ));

        return response()->json([
            'success' => true,
            'message' => 'Announcement broadcasted to public channel successfully!',
        ]);
    }

    /**
     * Edit a chat message body (WhatsApp style).
     */
    public function editMessage(Request $request, int $conversationId, int $messageId): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        $user = $request->user();

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = Message::where('tenant_id', $tenantId)
            ->where('conversation_id', $conversationId)
            ->where('id', $messageId)
            ->firstOrFail();

        // Only the sender or SuperAdmin can edit their message, and cannot edit deleted messages
        if ($message->sender_id !== $user->id && ! $user->hasRole('SuperAdmin')) {
            return response()->json(['error' => 'Unauthorized to edit this message.'], 403);
        }

        if ($message->is_deleted) {
            return response()->json(['error' => 'Cannot edit a deleted message.'], 422);
        }

        $message->update([
            'body' => trim($validated['body']),
            'edited_at' => now(),
        ]);

        $message->load(['sender:id,name,avatar_path', 'replyTo.sender:id,name']);

        // Broadcast to all participants on WebSocket
        broadcast(new MessageUpdated($message));

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'public_id' => $message->public_id,
                'conversation_id' => $message->conversation_id,
                'body' => $message->body,
                'type' => $message->type,
                'attachments' => $message->attachments,
                'is_deleted' => false,
                'edited_at' => $message->edited_at?->toISOString(),
                'reactions' => $message->reactions ?? [],
                'reply_to' => $message->replyTo ? [
                    'id' => $message->replyTo->id,
                    'body' => $message->replyTo->is_deleted ? 'This message was deleted' : $message->replyTo->body,
                    'sender_name' => $message->replyTo->sender?->name ?? 'User',
                ] : null,
                'created_at' => $message->created_at?->toISOString(),
                'sender' => [
                    'id' => $message->sender?->id,
                    'name' => $message->sender?->name,
                    'avatar_url' => $message->sender?->avatar_url,
                ],
            ],
        ]);
    }

    /**
     * Delete a chat message (WhatsApp style - "Delete for everyone").
     */
    public function deleteMessage(Request $request, int $conversationId, int $messageId): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        $user = $request->user();

        $message = Message::where('tenant_id', $tenantId)
            ->where('conversation_id', $conversationId)
            ->where('id', $messageId)
            ->firstOrFail();

        // Only sender or SuperAdmin can delete
        if ($message->sender_id !== $user->id && ! $user->hasRole('SuperAdmin')) {
            return response()->json(['error' => 'Unauthorized to delete this message.'], 403);
        }

        $message->update([
            'is_deleted' => true,
            'body' => '🚫 This message was deleted',
            'attachments' => null,
        ]);

        // Broadcast to all participants on WebSocket
        broadcast(new MessageDeleted($tenantId, $conversationId, $message->id));

        return response()->json([
            'success' => true,
            'message_id' => $message->id,
        ]);
    }

    /**
     * React to a message with an emoji (WhatsApp style).
     */
    public function reactMessage(Request $request, int $conversationId, int $messageId): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        $user = $request->user();

        $validated = $request->validate([
            'emoji' => ['required', 'string', 'max:10'],
        ]);

        $message = Message::where('tenant_id', $tenantId)
            ->where('conversation_id', $conversationId)
            ->where('id', $messageId)
            ->firstOrFail();

        $emoji = $validated['emoji'];
        $reactions = $message->reactions ?? [];

        if (! isset($reactions[$emoji])) {
            $reactions[$emoji] = [];
        }

        // Toggle reaction for current user
        if (in_array($user->id, $reactions[$emoji])) {
            $reactions[$emoji] = array_values(array_filter($reactions[$emoji], fn ($id) => $id !== $user->id));
            if (empty($reactions[$emoji])) {
                unset($reactions[$emoji]);
            }
        } else {
            $reactions[$emoji][] = $user->id;
        }

        $message->update(['reactions' => $reactions]);

        // Broadcast to all participants on WebSocket
        broadcast(new MessageReacted($tenantId, $conversationId, $message->id, $reactions));

        return response()->json([
            'success' => true,
            'reactions' => $reactions,
        ]);
    }

    /**
     * Helper to fetch conversation messages with tenant scoping.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchConversationMessages(int $tenantId, int $conversationId, int $userId): array
    {
        // सुरक्षा चेक: Conversation यही Tenant को हो र User participant हो
        $conversation = Conversation::where('tenant_id', $tenantId)
            ->where('id', $conversationId)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->first();

        if (! $conversation) {
            return [];
        }

        return Message::query()
            ->where('tenant_id', $tenantId)
            ->where('conversation_id', $conversationId)
            ->with(['sender:id,name,avatar_path', 'replyTo.sender:id,name'])
            ->orderBy('created_at', 'asc')
            ->limit(100)
            ->get()
            ->map(fn (Message $m) => [
                'id' => $m->id,
                'public_id' => $m->public_id,
                'conversation_id' => $m->conversation_id,
                'body' => $m->body,
                'type' => $m->type,
                'attachments' => $m->attachments,
                'is_read' => (bool) $m->is_read,
                'is_deleted' => (bool) $m->is_deleted,
                'edited_at' => $m->edited_at?->toISOString(),
                'reactions' => $m->reactions ?? [],
                'reply_to' => $m->replyTo ? [
                    'id' => $m->replyTo->id,
                    'body' => $m->replyTo->is_deleted ? 'This message was deleted' : $m->replyTo->body,
                    'sender_name' => $m->replyTo->sender?->name ?? 'User',
                ] : null,
                'created_at' => $m->created_at?->toISOString(),
                'sender' => [
                    'id' => $m->sender?->id,
                    'name' => $m->sender?->name,
                    'avatar_url' => $m->sender?->avatar_url,
                ],
            ])
            ->all();
    }
}
