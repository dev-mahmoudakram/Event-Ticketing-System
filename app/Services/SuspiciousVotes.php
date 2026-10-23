<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Award;
use App\Models\AwardVote;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Flags IP addresses that cast an unusual burst of votes in one award.
 *
 * IP is never a vote limit — a whole venue can share one network — so this only marks
 * votes for an admin to look at. The window slides: any THRESHOLD counted votes from one
 * IP that fit inside WINDOW_MINUTES are a burst, whichever clock hours they straddle.
 */
class SuspiciousVotes
{
    public const THRESHOLD = 5;

    public const WINDOW_MINUTES = 60;

    /**
     * @var array<int, Collection<int, string>>
     */
    private array $ipsByAward = [];

    /**
     * IPs with at least THRESHOLD counted votes in the award inside some WINDOW_MINUTES window.
     *
     * Two queries whatever the award's size: one finds the IPs with enough votes to matter at
     * all, the other reads just their timestamps, which a two-pointer pass then slides over.
     *
     * @return Collection<int, string>
     */
    public function ipsFor(Award $award): Collection
    {
        return $this->ipsByAward[$award->id] ??= $this->findIps($award);
    }

    public function isSuspicious(AwardVote $vote): bool
    {
        if ($vote->isRemoved() || $vote->ip_address === null) {
            return false;
        }

        return $this->ipsFor($vote->award)->contains($vote->ip_address);
    }

    /**
     * @return Collection<int, string>
     */
    private function findIps(Award $award): Collection
    {
        $candidateIps = $award->votes()
            ->counted()
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->havingRaw('COUNT(*) >= ?', [self::THRESHOLD])
            ->pluck('ip_address');

        if ($candidateIps->isEmpty()) {
            return collect();
        }

        $windowSeconds = self::WINDOW_MINUTES * 60;

        return $award->votes()
            ->counted()
            ->whereIn('ip_address', $candidateIps)
            ->orderBy('ip_address')
            ->orderBy('created_at')
            ->toBase()
            ->get(['ip_address', 'created_at'])
            ->groupBy('ip_address')
            ->filter(function (Collection $votes) use ($windowSeconds): bool {
                $times = $votes->map(fn (object $vote): int => Carbon::parse($vote->created_at)->getTimestamp())->values()->all();

                $start = 0;
                foreach ($times as $end => $time) {
                    while ($time - $times[$start] > $windowSeconds) {
                        $start++;
                    }

                    if ($end - $start + 1 >= self::THRESHOLD) {
                        return true;
                    }
                }

                return false;
            })
            ->keys()
            ->map(fn (int|string $ip): string => (string) $ip)
            ->values();
    }
}
