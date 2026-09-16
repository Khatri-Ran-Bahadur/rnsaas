<?php

namespace Modules\Chat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Demonstrates a Public Channel (Channel).
 * Public channels require no authentication callback in channels.php.
 */
class CompanyAnnouncement implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $tenantId,
        public string $title,
        public string $message,
        public string $senderName
    ) {}

    /**
     * Broadcast on a public channel.
     * Anyone connected can subscribe without passing /broadcasting/auth.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("company.{$this->tenantId}.public-announcements"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'announcement.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'sender_name' => $this->senderName,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
