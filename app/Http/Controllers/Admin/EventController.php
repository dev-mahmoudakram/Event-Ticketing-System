<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Models\Event;
use App\Support\SocialPlatforms;
use App\Support\UploadLimit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EventController extends Controller
{
    use HandlesMediaUploads;

    /** @var array<string, string> The file inputs on the event form, and the column each one fills. */
    private const UPLOADS = [
        'cover_image' => 'cover_image_path',
        'logo' => 'logo_path',
        'footer_logo' => 'footer_logo_path',
        'favicon' => 'favicon_path',
        'apple_touch_icon' => 'apple_touch_icon_path',
        'share_image' => 'share_image_path',
    ];

    public function index(): View
    {
        return view('admin.events.index', ['events' => Event::orderBy('start_date')->get()]);
    }

    public function create(): View
    {
        return view('admin.events.form', ['event' => new Event, 'uploadLimit' => $this->uploadLimitLabel()]);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        $data = $this->withBranding($request->safe()->except(self::UPLOADS), $request);

        Event::create($data);

        return redirect()->route('admin.events.index');
    }

    public function edit(Event $event): View
    {
        return view('admin.events.form', ['event' => $event, 'uploadLimit' => $this->uploadLimitLabel()]);
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $data = $this->withBranding($request->safe()->except(self::UPLOADS), $request, $event);

        $event->update($data);

        return redirect()->route('admin.events.index');
    }

    public function destroy(Event $event): RedirectResponse
    {
        foreach (self::UPLOADS as $input => $column) {
            $this->deleteStoredMedia($event->{$column});
        }

        $event->delete();

        return redirect()->route('admin.events.index');
    }

    /**
     * Fold the uploaded files and the social links into the data being saved.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withBranding(array $data, EventRequest $request, ?Event $event = null): array
    {
        foreach (self::UPLOADS as $input => $column) {
            $data = $this->withUploadedMedia($data, $request, $input, $column, 'events', $event?->{$column});
        }

        $links = [];
        foreach (SocialPlatforms::keys() as $platform) {
            $url = trim((string) $request->input("social_links.{$platform}"));

            if ($url !== '') {
                $links[$platform] = $url;
            }
        }

        $data['social_links'] = $links;

        return $data;
    }

    private function uploadLimitLabel(): string
    {
        return UploadLimit::label(UploadLimit::effectiveKilobytes((int) config('media.max_image_kb')));
    }
}
