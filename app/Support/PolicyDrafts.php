<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\RequiredPage;
use App\Models\Event;

/**
 * Starter text for the required pages, filled from what the event already knows. Anything only
 * the organiser knows stays as a [bracketed placeholder] for them to replace. Takes plain data so
 * the migration can use it without loading models.
 */
final class PolicyDrafts
{
    /**
     * @param  array{name: string, email: ?string, phone: ?string, venue: ?string, address: ?string, currency: string}  $event
     */
    public static function render(RequiredPage $page, string $locale, array $event): string
    {
        return trim(view("event-pages.drafts.{$page->value}-{$locale}", ['event' => $event])->render());
    }

    /**
     * @return array{name: string, email: ?string, phone: ?string, venue: ?string, address: ?string, currency: string}
     */
    public static function eventData(Event $event, string $locale): array
    {
        $ar = $locale === 'ar';

        return [
            'name' => $ar ? $event->name_ar : $event->name_en,
            'email' => $event->contact_email,
            'phone' => $event->contact_phone,
            'venue' => $ar ? $event->venue_name_ar : $event->venue_name_en,
            'address' => $ar ? $event->venue_address_ar : $event->venue_address_en,
            'currency' => (string) ($event->ticketTypes()->value('currency') ?: 'EGP'),
        ];
    }
}
