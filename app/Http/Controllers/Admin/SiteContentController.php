<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use App\Rules\SafeSvg;
use App\Support\SiteContentRegistry;
use App\Support\SiteText;
use App\Support\UploadLimit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SiteContentController extends Controller
{
    use HandlesMediaUploads;

    public function index(): View
    {
        return view('admin.site-content.index', [
            'sections' => SiteContentRegistry::sections(),
            'filled' => $this->filledCounts(),
            'images' => $this->sectionImages(),
        ]);
    }

    public function edit(string $section): View
    {
        $definition = $this->definitionFor($section);

        return view('admin.site-content.edit', [
            'section' => $section,
            'definition' => $definition,
            'stored' => SiteContent::where('section', $section)->get()->keyBy('field_key'),
            'uploadLimit' => UploadLimit::label(UploadLimit::effectiveKilobytes((int) config('media.max_image_kb'))),
            // For the jump-to-section list beside the form.
            'sections' => SiteContentRegistry::sections(),
            'filled' => $this->filledCounts(),
        ]);
    }

    public function update(Request $request, string $section): RedirectResponse
    {
        $definition = $this->definitionFor($section);

        $request->validate([
            'fields' => ['array'],
            'fields.*.ar' => ['nullable', 'string', 'max:2000'],
            'fields.*.en' => ['nullable', 'string', 'max:2000'],
            'single' => ['array'],
            'single.*' => ['nullable', 'string', 'max:255'],
            // SVG is allowed here so an illustration can be swapped for another drawing, not
            // only a photograph. Only signed-in admins reach this form.
            'images.*' => ['nullable', 'image:allow_svg', new SafeSvg, 'max:'.UploadLimit::effectiveKilobytes((int) config('media.max_image_kb'))],
        ]);

        foreach ($definition['fields'] as $fieldKey => $field) {
            if (($field['type'] ?? 'text') === 'image') {
                $this->saveImage($request, $section, $fieldKey);

                continue;
            }

            // A link, an email address or a phone number is the same in both languages, so it is
            // entered once and stored in both columns.
            if (($field['type'] ?? 'text') === 'single') {
                $value = trim((string) $request->input("single.{$fieldKey}")) ?: null;

                SiteContent::updateOrCreate(
                    ['section' => $section, 'field_key' => $fieldKey],
                    ['value_ar' => $value, 'value_en' => $value],
                );

                continue;
            }

            $values = $request->input("fields.{$fieldKey}", []);

            SiteContent::updateOrCreate(
                ['section' => $section, 'field_key' => $fieldKey],
                ['value_ar' => $values['ar'] ?? null, 'value_en' => $values['en'] ?? null],
            );
        }

        SiteText::flush();

        return redirect()
            ->route('admin.site-content.edit', $section)
            ->with('status', __('Content saved.'));
    }

    /**
     * Images are stored once for both locales, in value_en. Uploading replaces whatever was
     * there; leaving the field alone keeps it.
     */
    private function saveImage(Request $request, string $section, string $fieldKey): void
    {
        $record = SiteContent::firstOrNew(['section' => $section, 'field_key' => $fieldKey]);
        $path = $this->storeUploadedMedia($request, "images.{$fieldKey}", 'site');

        if ($path === null) {
            return;
        }

        $this->deleteStoredMedia($record->value_en);
        $record->value_en = $path;
        $record->save();
    }

    /**
     * How many fields of each section somebody has actually written into.
     *
     * @return Collection<string, int>
     */
    private function filledCounts(): Collection
    {
        return SiteContent::all()
            ->groupBy('section')
            ->map(fn ($rows) => $rows->filter(fn (SiteContent $row) => trim((string) $row->value_en.$row->value_ar) !== '')->count());
    }

    /**
     * One image per section for the index, so the list reads as pictures rather than rows.
     *
     * @return array<string, string>
     */
    private function sectionImages(): array
    {
        $images = [];

        foreach (SiteContentRegistry::sections() as $key => $definition) {
            $imageKeys = array_keys(array_filter(
                $definition['fields'],
                fn (array $field) => ($field['type'] ?? 'text') === 'image',
            ));

            foreach ($imageKeys as $fieldKey) {
                $record = SiteContent::where('section', $key)->where('field_key', $fieldKey)->first();
                $url = $record?->urlFor($record->value_en);

                if ($url !== null) {
                    $images[$key] = $url;

                    break;
                }
            }
        }

        return $images;
    }

    /**
     * @return array{label: string, description: string, anchor: ?string, fields: array<string, array{label: string, type: string, default?: string}>}
     */
    private function definitionFor(string $section): array
    {
        $definition = SiteContentRegistry::sections()[$section] ?? null;

        if ($definition === null) {
            throw new NotFoundHttpException;
        }

        return $definition;
    }
}
