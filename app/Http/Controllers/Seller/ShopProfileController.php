<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ShopProfileController extends Controller
{
    /**
     * Update the authenticated seller's shop profile.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $team = $user->currentTeam ?? $user->ownedTeams()->first() ?? $user->teams()->first();

        if (!$team) {
            return back()->withErrors(['name' => 'Walang nahanap na active store para sa account na ito.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $team->name = $data['name'];
        if (array_key_exists('description', $data)) {
            $team->description = $data['description'];
        }

        if ($request->hasFile('logo')) {
            $request->validate([
                'logo' => ['image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
            ]);

            // Delete old custom logo if in storage
            if ($team->logo_path && !str_starts_with($team->logo_path, 'images/')) {
                Storage::disk('public')->delete($team->logo_path);
            }

            $path = $request->file('logo')->store('shops', 'public');
            $team->logo_path = $path;
        }

        $team->save();

        return back()->with('success', 'Nai-save na ang shop profile!');
    }
}
