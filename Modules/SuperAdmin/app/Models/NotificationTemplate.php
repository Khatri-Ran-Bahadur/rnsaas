<?php

namespace Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'subject',
        'content',
        'variables',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Replace template variables with provided key-value array.
     */
    public function render(array $data = []): array
    {
        $subject = $this->subject ?? '';
        $content = $this->content ?? '';

        foreach ($data as $key => $value) {
            $placeholder = '{'.$key.'}';
            $subject = str_replace($placeholder, (string) $value, $subject);
            $content = str_replace($placeholder, (string) $value, $content);
        }

        return [
            'subject' => $subject,
            'content' => $content,
        ];
    }
}
