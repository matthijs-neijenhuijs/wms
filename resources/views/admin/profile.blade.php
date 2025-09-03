{{-- Filament will automatically inject MFA UI into the profile page if enabled in panel config --}}
@extends('filament::components.layouts.base')

@section('content')
    <livewire:filament-profile />

    <div class="container mx-auto mt-8">
        <div class="max-w-xl mx-auto bg-white shadow rounded p-6">
            <h2 class="text-lg font-semibold mb-4">Change Password</h2>

            @if(session('status'))
                <div class="text-green-600 mb-3">{{ session('status') }}</div>
            @endif

            @if($errors->any())
                <div class="text-red-600 mb-3">
                    <ul class="list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.profile.password.update') }}">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Current password</label>
                    <input type="password" name="current_password" class="w-full border rounded px-3 py-2" required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">New password</label>
                    <input type="password" name="password" class="w-full border rounded px-3 py-2" required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Confirm password</label>
                    <input type="password" name="password_confirmation" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded">Update password</button>
                </div>
            </form>
        </div>
    </div>

    <div class="container mx-auto mt-8">
        <div class="max-w-xl mx-auto bg-white shadow rounded p-6">
            <h2 class="text-lg font-semibold mb-4">Two-Factor Authentication</h2>

            <form method="POST" action="{{ route('admin.profile.2fa.update') }}">
                @csrf

                <div class="mb-4 flex items-center">
                    <input type="hidden" name="enable_email_2fa" value="0">
                    <input type="checkbox" name="enable_email_2fa" id="enable_email_2fa" value="1" class="mr-2" {{ auth()->user()->has_email_authentication ? 'checked' : '' }}>
                    <label for="enable_email_2fa" class="text-sm">Enable email-based 2FA</label>
                </div>

                <div class="mb-4">
                    <button type="submit" name="action" value="generate" class="inline-flex items-center px-3 py-2 bg-green-600 text-white rounded mr-2">Generate App Secret</button>
                    <button type="submit" name="action" value="clear" class="inline-flex items-center px-3 py-2 bg-red-600 text-white rounded">Clear App Secret</button>
                </div>
            </form>

            @if(auth()->user()->getAppAuthenticationSecret())
                <div class="mt-4 bg-gray-50 p-3 rounded">
                    <strong>App authentication secret:</strong>
                    <div class="mt-2 font-mono bg-white p-2 rounded">{{ auth()->user()->getAppAuthenticationSecret() }}</div>
                    <p class="text-sm text-gray-600 mt-2">Use this secret with an authenticator app (TOTP) — this example uses a generated secret string. For production use a proper TOTP implementation.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
