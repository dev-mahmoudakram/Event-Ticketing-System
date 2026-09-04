<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SiteFaqRequest;
use App\Models\SiteFaq;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SiteFaqController extends Controller
{
    public function index(): View
    {
        return view('admin.site-faqs.index', ['faqs' => SiteFaq::orderBy('sort_order')->get()]);
    }

    public function create(): View
    {
        return view('admin.site-faqs.form', ['faq' => new SiteFaq]);
    }

    public function store(SiteFaqRequest $request): RedirectResponse
    {
        SiteFaq::create($request->validated());

        return redirect()->route('admin.site-faqs.index');
    }

    public function edit(SiteFaq $siteFaq): View
    {
        return view('admin.site-faqs.form', ['faq' => $siteFaq]);
    }

    public function update(SiteFaqRequest $request, SiteFaq $siteFaq): RedirectResponse
    {
        $siteFaq->update($request->validated());

        return redirect()->route('admin.site-faqs.index');
    }

    public function destroy(SiteFaq $siteFaq): RedirectResponse
    {
        $siteFaq->delete();

        return redirect()->route('admin.site-faqs.index');
    }
}
