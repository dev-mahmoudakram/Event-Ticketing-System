<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Reel;
use App\Support\UploadLimit;
use Illuminate\Foundation\Http\FormRequest;

class ReelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reel = $this->route('reel');
        $videoAlreadyStored = $reel instanceof Reel && $reel->exists;

        return [
            // A reel is nothing without its clip, so require one on create but let an edit keep
            // whatever is already stored when no replacement is chosen.
            'video' => [
                $videoAlreadyStored ? 'nullable' : 'required',
                'file',
                'mimetypes:video/mp4,video/webm,video/quicktime',
                'max:'.UploadLimit::effectiveKilobytes($this->maxVideoKilobytes()),
            ],
            'poster' => ['nullable', 'image', 'max:'.UploadLimit::effectiveKilobytes($this->maxImageKilobytes())],
            'caption_ar' => ['nullable', 'string', 'max:255'],
            'caption_en' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            // PHP discards an oversized upload before validation runs, so the default
            // "failed to upload" says nothing about why or what the ceiling is.
            'video.uploaded' => $this->oversizeMessage($this->maxVideoKilobytes()),
            'video.max' => $this->oversizeMessage($this->maxVideoKilobytes()),
            'poster.uploaded' => $this->oversizeMessage($this->maxImageKilobytes()),
        ];
    }

    public function attributes(): array
    {
        return [
            'video' => __('Video'),
            'poster' => __('Poster Image'),
        ];
    }

    private function maxVideoKilobytes(): int
    {
        return (int) config('media.max_video_kb');
    }

    private function maxImageKilobytes(): int
    {
        return (int) config('media.max_image_kb');
    }

    /**
     * Separates the two reasons a file can be rejected for size: it is genuinely over this
     * application's limit, or the server cannot accept files that large yet — which is a
     * php.ini change rather than something the uploader can fix by picking another file.
     */
    private function oversizeMessage(int $intendedKilobytes): string
    {
        if (UploadLimit::isServerConstrained($intendedKilobytes)) {
            return __('This file is too large. The limit is :intended, but this server currently accepts only :server — raise upload_max_filesize and post_max_size to allow the full size.', [
                'intended' => UploadLimit::label($intendedKilobytes),
                'server' => UploadLimit::label(UploadLimit::serverKilobytes()),
            ]);
        }

        return __('This file is too large. The limit is :intended.', [
            'intended' => UploadLimit::label($intendedKilobytes),
        ]);
    }
}
