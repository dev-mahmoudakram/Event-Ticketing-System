<?php

declare(strict_types=1);

namespace App\Support;

class SocialPlatforms
{
    /**
     * The networks an admin can link to, in the order they appear.
     *
     * The same list drives the Creators Hub settings, the per-event settings, and both footers,
     * so a network added here shows up everywhere at once.
     *
     * @return array<string, array{label: string, placeholder: string}>
     */
    public static function all(): array
    {
        return [
            'instagram' => ['label' => 'Instagram', 'placeholder' => 'https://instagram.com/'],
            'facebook' => ['label' => 'Facebook', 'placeholder' => 'https://facebook.com/'],
            'x' => ['label' => 'X', 'placeholder' => 'https://x.com/'],
            'linkedin' => ['label' => 'LinkedIn', 'placeholder' => 'https://linkedin.com/company/'],
            'youtube' => ['label' => 'YouTube', 'placeholder' => 'https://youtube.com/@'],
            'tiktok' => ['label' => 'TikTok', 'placeholder' => 'https://tiktok.com/@'],
            'whatsapp' => ['label' => 'WhatsApp', 'placeholder' => 'https://wa.me/'],
        ];
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function label(string $platform): string
    {
        return self::all()[$platform]['label'] ?? ucfirst($platform);
    }

    /**
     * Keep only the networks that have a link, in the canonical order.
     *
     * @param  array<string, string|null>  $links
     * @return array<string, string>
     */
    public static function filled(array $links): array
    {
        $filled = [];

        foreach (self::keys() as $platform) {
            $url = trim((string) ($links[$platform] ?? ''));

            if ($url !== '') {
                $filled[$platform] = $url;
            }
        }

        return $filled;
    }
}
