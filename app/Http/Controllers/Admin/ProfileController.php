<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('admin.profile', [
            'user' => $request->user(),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! $user instanceof User) {
            return back()->withErrors(['current_password' => 'Authentication required.']);
        }

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Current password does not match our records.']);
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        return back()->with('status', 'Password updated successfully.');
    }

    public function updateTwoFactor(Request $request): RedirectResponse
    {
        $request->validate([
            'enable_email_2fa' => ['nullable', 'boolean'],
            'action' => ['nullable', 'in:generate,clear'],
        ]);

        $user = $request->user();

        if (! $user instanceof User) {
            return back()->withErrors(['current_password' => 'Authentication required.']);
        }

        $enableEmail = (bool) $request->input('enable_email_2fa', false);
        $user->toggleEmailAuthentication($enableEmail);

        $action = $request->input('action');
        if ($action === 'generate') {
            $secret = bin2hex(random_bytes(10));
            $user->saveAppAuthenticationSecret($secret);
        } elseif ($action === 'clear') {
            $user->saveAppAuthenticationSecret(null);
        }

        return back()->with('status', 'Two-factor settings updated.');
    }
}
