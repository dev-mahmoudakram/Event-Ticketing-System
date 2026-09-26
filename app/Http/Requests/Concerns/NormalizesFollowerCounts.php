<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Support\FollowerCount;

/**
 * Lets follower counts be typed as "30k" or "1.2m": each one is turned into a whole number
 * before validation, and anything unreadable is left as typed so the integer rule rejects it
 * with a message that shows the accepted formats.
 */
trait NormalizesFollowerCounts
{
    /** The largest count the unsignedInteger follower columns hold. */
    protected const FOLLOWER_COUNT_MAX = 4294967295;

    /**
     * @param  list<string>  $keys
     */
    protected function normalizeFollowerCounts(array $keys): void
    {
        $normalized = [];

        foreach ($keys as $key) {
            $value = $this->input($key);

            if (FollowerCount::isBlank($value)) {
                $normalized[$key] = null;

                continue;
            }

            $normalized[$key] = FollowerCount::parse($value) ?? $value;
        }

        $this->merge($normalized);
    }

    /**
     * @return list<string>
     */
    protected function followerCountRules(): array
    {
        return ['nullable', 'integer', 'between:0,'.self::FOLLOWER_COUNT_MAX];
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, string>
     */
    protected function followerCountMessages(array $keys): array
    {
        $messages = [];

        foreach ($keys as $key) {
            $messages[$key.'.integer'] = __('Enter a number like 30000, 30k or 1.2m.');
        }

        return $messages;
    }
}
