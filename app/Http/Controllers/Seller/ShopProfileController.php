<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ShopProfileController extends Controller
{
    /**
     * Update the authenticated seller's shop profile.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $team = $user->currentTeam;

        if (!$team) {
            abort(403, 'No store associated with account.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
        ]);

        $team->name = $data['name'];
        $team->description = $data['description'] ?? null;

        if ($request->hasFile('logo')) {
            // Delete existing logo if in storage
            if ($team->logo_path && !str_starts_with($team->logo_path, 'images/')) {
                Storage::disk('public')->delete($team->logo_path);
            }

            $path = $request->file('logo')->store('shops', 'public');
            $team->logo_path = $path;
        }

        $team->save();

        return back()->with('success', 'Shop profile updated successfully!');
    }
}
