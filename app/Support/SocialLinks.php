<?php

namespace App\Support;

final class SocialLinks
{
    private const NETWORKS = [
        'facebook' => ['Facebook', 'https://www.facebook.com/%s'],
        'instagram' => ['Instagram', 'https://www.instagram.com/%s'],
        'tiktok' => ['TikTok', 'https://www.tiktok.com/@%s'],
        'youtube' => ['YouTube', 'https://www.youtube.com/@%s'],
    ];

    /**
     * Profile links for the handles in config site.social; empty handles are left out.
     *
     * @return array<string, array{0: string, 1: string}> network => [label, url]
     */
    public static function all(): array
    {
        $links = [];

        foreach (self::NETWORKS as $network => [$label, $pattern]) {
            $handle = trim((string) config("site.social.{$network}"), " @\t\n\r\0\x0B");

            if ($handle !== '') {
                $links[$network] = [$label, sprintf($pattern, $handle)];
            }
        }

        return $links;
    }
}
