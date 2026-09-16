<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Modules\Chat\Models\Conversation;

/*
|--------------------------------------------------------------------------
| Broadcast Channels (WebSocket Authorization Callbacks)
|--------------------------------------------------------------------------
|
| यहाँ Private र Presence Channels को Authorization नियमहरू लेखिन्छ।
| जब Client ले Echo मार्फत कुनै channel subscribe गर्छ, Laravel ले
| यी Callbacks रन गर्छ। 'false' return भएमा 403 Forbidden हुन्छ।
|
*/

// Default User Channel (व्यक्तिगत नोटिफिकेसन)
Broadcast::channel('App.Models.User.{id}', function (User $user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * 1. MULTI-TENANT PRIVATE CONVERSATION CHANNEL:
 * Channel: private-company.{companyId}.conversation.{conversationId}
 *
 * सुरक्षा नियम (Security Rules):
 * - Rule 1: प्रयोगकर्ता यो Company (Tenant) को सदस्य हुनुपर्छ। ($user->tenants->contains('id', $companyId))
 * - Rule 2: कुराकानी (Conversation) सोही Company को हुनुपर्छ। (tenant_id == $companyId)
 * - Rule 3: प्रयोगकर्ता सो Conversation को participant हुनुपर्छ।
 *
 * यसले गर्दा Company A का Staff ले Company B को च्याट कहिल्यै सुन्न पाउँदैनन्!
 */
Broadcast::channel('company.{companyId}.conversation.{conversationId}', function (User $user, int|string $companyId, int|string $conversationId) {
    $companyId = (int) $companyId;
    $conversationId = (int) $conversationId;

    $isSuperAdmin = $user->hasRole('SuperAdmin') || (int) $user->id === 1;

    // चेक १: के यो प्रयोगकर्ता यो कम्पनीमा आबद्ध छ वा SuperAdmin हो?
    if (! $isSuperAdmin && ! $user->tenants()->where('tenants.id', $companyId)->exists()) {
        return false;
    }

    // SuperAdmin bypass for active tenant inspection
    if ($isSuperAdmin) {
        return Conversation::query()
            ->where('id', $conversationId)
            ->where('tenant_id', $companyId)
            ->exists();
    }

    // चेक २: के यो कुराकानी सोही कम्पनीको हो र प्रयोगकर्ता यसमा सामेल छ?
    return Conversation::query()
        ->where('id', $conversationId)
        ->where('tenant_id', $companyId)
        ->whereHas('participants', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->exists();
});

/**
 * 2. MULTI-TENANT PRESENCE CHANNEL:
 * Channel: presence-company.{companyId}.presence
 *
 * Presence Channel ले च्याटमा को-को Online छन् हेर्न मद्दत गर्छ।
 * NOTE: Presence Channel मा Boolean (true/false) को सट्टा User को Data array
 * फर्काउनुपर्छ, जसले गर्दा अन्य जोडिएका Staff ले यो User को info देख्न सक्छन्।
 */
Broadcast::channel('company.{companyId}.presence', function (User $user, int|string $companyId) {
    $companyId = (int) $companyId;

    $isSuperAdmin = $user->hasRole('SuperAdmin') || (int) $user->id === 1;

    // यदि कम्पनीको सदस्य होइन र SuperAdmin पनि होइन भने प्रवेश निषेध (403)
    if (! $isSuperAdmin && ! $user->tenants()->where('tenants.id', $companyId)->exists()) {
        return false;
    }

    // Presence Data: अन्य सदस्यहरूलाई प्रसारण हुने जानकारी
    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'avatar_url' => $user->avatar_url,
    ];
});

/**
 * 3. MULTI-TENANT USER PERSONAL CHANNEL:
 * Channel: private-company.{companyId}.user.{userId}
 *
 * कम्पनी भित्र निश्चित स्टाफलाई मात्र नयाँ म्यासेज अलर्ट वा ब्याड्ज पठाउन।
 */
Broadcast::channel('company.{companyId}.user.{userId}', function (User $user, int|string $companyId, int|string $userId) {
    $companyId = (int) $companyId;
    $userId = (int) $userId;

    if ((int) $user->id !== $userId) {
        return false;
    }

    $isSuperAdmin = $user->hasRole('SuperAdmin') || (int) $user->id === 1;

    return $isSuperAdmin || $user->tenants()->where('tenants.id', $companyId)->exists();
});
