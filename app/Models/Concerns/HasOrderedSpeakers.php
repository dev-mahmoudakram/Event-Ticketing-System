<?php

declare(strict_types=1);

namespace App\Models\Concerns;

/**
 * Sessions and workshops keep their speakers in an order the admin chooses (pivot sort_order);
 * this writes that order in one go.
 */
trait HasOrderedSpeakers
{
    /**
     * @param  list<int|string>  $speakerIds  in display order
     */
    public function syncSpeakersInOrder(array $speakerIds): void
    {
        $this->speakers()->sync(
            collect(array_values(array_unique(array_map('intval', $speakerIds))))
                ->mapWithKeys(fn (int $id, int $position) => [$id => ['sort_order' => $position]])
                ->all()
        );
    }
}
