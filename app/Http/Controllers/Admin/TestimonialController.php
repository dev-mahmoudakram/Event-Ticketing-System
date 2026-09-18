<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestimonialRequest;
use App\Models\Event;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TestimonialController extends Controller
{
    use HandlesMediaUploads;

    public function index(Event $event): View
    {
        return view('admin.testimonials.index', ['event' => $event, 'testimonials' => $event->testimonials]);
    }

    public function create(Event $event): View
    {
        return view('admin.testimonials.form', ['event' => $event, 'testimonial' => new Testimonial]);
    }

    public function store(TestimonialRequest $request, Event $event): RedirectResponse
    {
        $data = $request->safe()->except(['photo']);
        $data = $this->withUploadedMedia($data, $request, 'photo', 'photo_path', 'testimonials');

        $event->testimonials()->create($data);

        return redirect()->route('admin.events.testimonials.index', $event);
    }

    public function edit(Event $event, Testimonial $testimonial): View
    {
        $this->assertBelongsToEvent($event, $testimonial);

        return view('admin.testimonials.form', ['event' => $event, 'testimonial' => $testimonial]);
    }

    public function update(TestimonialRequest $request, Event $event, Testimonial $testimonial): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $testimonial);
        $data = $request->safe()->except(['photo']);
        $data = $this->withUploadedMedia($data, $request, 'photo', 'photo_path', 'testimonials', $testimonial->photo_path);

        $testimonial->update($data);

        return redirect()->route('admin.events.testimonials.index', $event);
    }

    public function destroy(Event $event, Testimonial $testimonial): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $testimonial);
        $this->deleteStoredMedia($testimonial->photo_path);
        $testimonial->delete();

        return redirect()->route('admin.events.testimonials.index', $event);
    }

    private function assertBelongsToEvent(Event $event, Testimonial $testimonial): void
    {
        if ($testimonial->event_id !== $event->id) {
            throw new NotFoundHttpException;
        }
    }
}
