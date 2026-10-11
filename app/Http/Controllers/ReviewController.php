<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_item_id' => ['required', 'exists:order_items,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'video' => ['nullable', 'file', 'mimes:mp4,mov,webm', 'max:20480'],
        ]);

        $orderItem = OrderItem::with('orderShop.order')->findOrFail($data['order_item_id']);

        abort_unless($orderItem->orderShop->order->user_id === $request->user()->id, 403);
        abort_unless($orderItem->orderShop->status === \App\Enums\OrderShopStatus::Completed, 422);
        abort_if($orderItem->review()->exists(), 422, 'You already reviewed this item.');

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                $file->move(public_path('images/reviews'), $filename);
                $imagePaths[] = 'images/reviews/' . $filename;
            }
        }

        $videoPath = null;
        if ($request->hasFile('video')) {
            $file = $request->file('video');
            $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
            $file->move(public_path('videos/reviews'), $filename);
            $videoPath = 'videos/reviews/' . $filename;
        }

        $review = $orderItem->review()->create([
            'product_id' => $orderItem->variant->product_id,
            'user_id' => $request->user()->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'image_paths' => $imagePaths ?: null,
            'video_path' => $videoPath,
        ]);

        $orderItem->variant->product->refreshRating();

        return back()->with('success', 'Review submitted.');
    }
}