<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show()
    {
        return view('admin.profile');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Current password does not match our records.']);
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        return back()->with('status', 'Password updated successfully.');
    }

    public function updateTwoFactor(Request $request)
    {
        $request->validate([
            'enable_email_2fa' => ['nullable', 'boolean'],
            'action' => ['nullable', 'in:generate,clear'],
        ]);

        $user = Auth::user();

        // Toggle email 2FA (User model provides toggleEmailAuthentication)
        $enableEmail = (bool) $request->input('enable_email_2fa', false);
        $user->toggleEmailAuthentication($enableEmail);

        // Handle app-based secret generation or clearing
        $action = $request->input('action');
        if ($action === 'generate') {
            // Simple secret for example purposes; in real usage use a proper TOTP lib
            $secret = bin2hex(random_bytes(10));
            $user->saveAppAuthenticationSecret($secret);
        } elseif ($action === 'clear') {
            $user->saveAppAuthenticationSecret(null);
        }

        return back()->with('status', 'Two-factor settings updated.');
    }
}
