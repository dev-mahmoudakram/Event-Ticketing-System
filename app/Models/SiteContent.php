<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ResolvesStoredMedia;
use Illuminate\Database\Eloquent\Model;

class SiteContent extends Model
{
    use ResolvesStoredMedia;

    protected $fillable = ['section', 'field_key', 'value_ar', 'value_en'];

    /**
     * Resolve a stored media path to a URL, reusing the same rules as the rest of the app so
     * uploads and external URLs both keep working.
     */
    public function urlFor(?string $path): ?string
    {
        return $this->storedMediaUrl($path);
    }
}
