<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PopupAd;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PopupAdController extends Controller
{
    /**
     * Chromium-based browsers can sometimes submit a garbage "0001-01-01"
     * value for a <input type="datetime-local"> that the user only
     * partially interacted with (e.g. touched the time but never picked
     * a date). That string passes Laravel's `date` rule but MySQL's
     * DATETIME column rejects it outright — so we treat anything
     * unreasonably old as "not set" before validation ever sees it.
     *
     * We also convert the value from Philippine time (what the admin sees
     * and types in their browser) to UTC (what the app/database store
     * everything in, per config('app.timezone')) — otherwise "now" typed
     * into the picker gets misread as 8 hours in the future.
     */
    private function sanitizeDateFields(Request $request): void
    {
        foreach (['starts_at', 'ends_at'] as $field) {
            $value = $request->input($field);

            if (! $value) {
                continue;
            }

            try {
                $parsed = \Carbon\Carbon::parse($value, 'Asia/Manila');

                if ($parsed->year < 2000) {
                    $request->merge([$field => null]);
                } else {
                    $request->merge([$field => $parsed->utc()->toDateTimeString()]);
                }
            } catch (\Throwable $e) {
                $request->merge([$field => null]);
            }
        }
    }

    public function index(): Response
    {
        return Inertia::render('Admin/PopupAds/Index', [
            'popupAds' => PopupAd::orderByDesc('created_at')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->sanitizeDateFields($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:1000'],
            'button_text' => ['nullable', 'string', 'max:50'],
            'button_link' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $filename = time() . '_' . $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path('images/popup-ads'), $filename);
            $imagePath = 'images/popup-ads/' . $filename;
        }

        PopupAd::create([
            'title' => $data['title'],
            'message' => $data['message'] ?? null,
            'button_text' => $data['button_text'] ?? null,
            'button_link' => $data['button_link'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'image_path' => $imagePath,
            'is_active' => true,
        ]);

        return back()->with('success', 'Pop-up ad added.');
    }

    public function update(Request $request, PopupAd $popupAd)
    {
        $this->sanitizeDateFields($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:1000'],
            'button_text' => ['nullable', 'string', 'max:50'],
            'button_link' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $popupAd->title = $data['title'];
        $popupAd->message = $data['message'] ?? null;
        $popupAd->button_text = $data['button_text'] ?? null;
        $popupAd->button_link = $data['button_link'] ?? null;
        $popupAd->starts_at = $data['starts_at'] ?? null;
        $popupAd->ends_at = $data['ends_at'] ?? null;
        $popupAd->is_active = $request->boolean('is_active', $popupAd->is_active);

        if ($request->hasFile('image')) {
            $filename = time() . '_' . $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path('images/popup-ads'), $filename);
            $popupAd->image_path = 'images/popup-ads/' . $filename;
        }

        $popupAd->save();

        return back()->with('success', 'Pop-up ad updated.');
    }

    public function destroy(PopupAd $popupAd)
    {
        $popupAd->delete();

        return back()->with('success', 'Pop-up ad removed.');
    }
}