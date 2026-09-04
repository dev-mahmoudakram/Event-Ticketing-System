<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ResolvesStoredMedia;
use Illuminate\Database\Eloquent\Model;

class HubPartner extends Model
{
    use ResolvesStoredMedia;

    protected $fillable = ['name_ar', 'name_en', 'logo_path', 'website_url', 'sort_order'];

    public function logoUrl(): ?string
    {
        return $this->storedMediaUrl($this->logo_path);
    }

    public function name(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }
}
