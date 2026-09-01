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
            'video' => [$videoAlreadyStored ? 'nullable' : 'required', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:'.UploadLimit::kilobytes()],
            'poster' => ['nullable', 'image', 'max:'.min(4096, UploadLimit::kilobytes())],
            'caption_ar' => ['nullable', 'string', 'max:255'],
            'caption_en' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            // PHP rejects an oversized upload before validation runs, and the default
            // "failed to upload" says nothing about why or what the ceiling is.
            'video.uploaded' => __('The video could not be uploaded. This server accepts files up to :limit — raise upload_max_filesize and post_max_size to allow more.', ['limit' => UploadLimit::label()]),
            'poster.uploaded' => __('The poster could not be uploaded. This server accepts files up to :limit.', ['limit' => UploadLimit::label()]),
        ];
    }

    public function attributes(): array
    {
        return [
            'video' => __('Video'),
            'poster' => __('Poster Image'),
        ];
    }
}
