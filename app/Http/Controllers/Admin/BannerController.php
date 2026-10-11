<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Banners/Index', [
            'banners' => Banner::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'button_text' => ['required', 'string', 'max:50'],
            'button_link' => ['nullable', 'string', 'max:255', 'regex:/^(\/|https?:\/\/)[\w\-\.\/\?\=\&\%\#]*$/i'],
            'title_font' => ['nullable', 'string', 'max:100'],
            'subtitle_font' => ['nullable', 'string', 'max:100'],
            'button_font' => ['nullable', 'string', 'max:100'],
            'title_color' => ['nullable', 'string', 'max:20'],
            'subtitle_color' => ['nullable', 'string', 'max:20'],
            'button_text_color' => ['nullable', 'string', 'max:20'],
            'button_background' => ['nullable', 'string', 'max:20'],
            'button_gradient_color' => ['nullable', 'string', 'max:20'],
            'button_style' => ['nullable', 'string', 'in:fill,outline,gradient'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'dimensions:width=1280,height=210', 'max:5120'],
        ]);

        $ext = strtolower($request->file('image')->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $ext = 'jpg';
        }
        $filename = time() . '_' . \Illuminate\Support\Str::random(16) . '.' . $ext;
        $request->file('image')->move(public_path('images/banners'), $filename);

        Banner::create([
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'button_text' => $data['button_text'],
            'button_link' => $data['button_link'] ?? null,
            'title_font' => $data['title_font'] ?? 'Inter',
            'subtitle_font' => $data['subtitle_font'] ?? 'Inter',
            'button_font' => $data['button_font'] ?? 'Inter',
            'title_color' => $data['title_color'] ?? '#101114',
            'subtitle_color' => $data['subtitle_color'] ?? '#666762',
            'button_text_color' => $data['button_text_color'] ?? '#FFFFFF',
            'button_background' => $data['button_background'] ?? '#101114',
            'button_gradient_color' => $data['button_gradient_color'] ?? '#33D6C8',
            'button_style' => $data['button_style'] ?? 'fill',
            'image_path' => 'images/banners/' . $filename,
            'sort_order' => (Banner::max('sort_order') ?? 0) + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Banner added.');
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'button_text' => ['required', 'string', 'max:50'],
            'button_link' => ['nullable', 'string', 'max:255', 'regex:/^(\/|https?:\/\/)[\w\-\.\/\?\=\&\%\#]*$/i'],
            'title_font' => ['nullable', 'string', 'max:100'],
            'subtitle_font' => ['nullable', 'string', 'max:100'],
            'button_font' => ['nullable', 'string', 'max:100'],
            'title_color' => ['nullable', 'string', 'max:20'],
            'subtitle_color' => ['nullable', 'string', 'max:20'],
            'button_text_color' => ['nullable', 'string', 'max:20'],
            'button_background' => ['nullable', 'string', 'max:20'],
            'button_gradient_color' => ['nullable', 'string', 'max:20'],
            'button_style' => ['nullable', 'string', 'in:fill,outline,gradient'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'dimensions:width=1280,height=210', 'max:5120'],
        ]);

        $banner->title = $data['title'];
        $banner->subtitle = $data['subtitle'] ?? null;
        $banner->button_text = $data['button_text'];
        $banner->button_link = $data['button_link'] ?? null;
        $banner->title_font = $data['title_font'] ?? $banner->title_font;
        $banner->subtitle_font = $data['subtitle_font'] ?? $banner->subtitle_font;
        $banner->button_font = $data['button_font'] ?? $banner->button_font;
        $banner->title_color = $data['title_color'] ?? $banner->title_color;
        $banner->subtitle_color = $data['subtitle_color'] ?? $banner->subtitle_color;
        $banner->button_text_color = $data['button_text_color'] ?? $banner->button_text_color;
        $banner->button_background = $data['button_background'] ?? $banner->button_background;
        $banner->button_gradient_color = $data['button_gradient_color'] ?? $banner->button_gradient_color;
        $banner->button_style = $data['button_style'] ?? $banner->button_style;
        $banner->is_active = $request->boolean('is_active', $banner->is_active);

        if ($request->hasFile('image')) {
            $ext = strtolower($request->file('image')->getClientOriginalExtension() ?: 'jpg');
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $ext = 'jpg';
            }
            $filename = time() . '_' . \Illuminate\Support\Str::random(16) . '.' . $ext;
            $request->file('image')->move(public_path('images/banners'), $filename);
            $banner->image_path = 'images/banners/' . $filename;
        }

        $banner->save();

        return back()->with('success', 'Banner updated.');
    }

    public function destroy(Banner $banner)
    {
        $banner->delete();

        return back()->with('success', 'Banner removed.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:banners,id'],
        ]);

        foreach ($data['order'] as $index => $id) {
            Banner::where('id', $id)->update(['sort_order' => $index]);
        }

        return back();
    }
}