<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\SiteSection;
use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteContentController extends Controller
{
    public function edit(): View
    {
        $stored = SiteContent::all()->keyBy(
            fn (SiteContent $content) => $content->section->value.'.'.$content->field_key
        );

        return view('admin.site-content.edit', [
            'sections' => SiteSection::cases(),
            'stored' => $stored,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'content' => ['array'],
            'content.*.*.ar' => ['nullable', 'string', 'max:2000'],
            'content.*.*.en' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach ($validated['content'] ?? [] as $sectionValue => $fields) {
            $section = SiteSection::tryFrom($sectionValue);

            if ($section === null) {
                continue;
            }

            foreach ($fields as $fieldKey => $values) {
                // Ignore anything the enum does not declare, so a tampered form cannot
                // write arbitrary keys into the content table.
                if (! array_key_exists($fieldKey, $section->fields())) {
                    continue;
                }

                SiteContent::updateOrCreate(
                    ['section' => $section, 'field_key' => $fieldKey],
                    ['value_ar' => $values['ar'] ?? null, 'value_en' => $values['en'] ?? null],
                );
            }
        }

        return redirect()->route('admin.site-content.edit')->with('status', __('Content saved.'));
    }
}
