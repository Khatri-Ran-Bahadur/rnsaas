@extends('installer.layout', ['step' => 5])

@section('title', 'Super Administrator Setup')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Super Administrator Account</h2>
        <p class="mt-2 text-sm text-slate-600">
            Create the primary Super Administrator account. This user will possess root administrative privileges across all platform modules and tenant organizations.
        </p>
    </div>

    <form method="POST" action="{{ route('installer.admin.create') }}" class="space-y-6">
        @csrf

        <div class="space-y-4">
            <!-- Full Name -->
            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Administrator Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', 'Super Administrator') }}" required
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-medium text-sm"
                    placeholder="John Doe">
            </div>

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Admin Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email', 'admin@sathisaas.com') }}" required
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-medium text-sm"
                    placeholder="admin@yourdomain.com">
            </div>

            <!-- Password Fields -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Password</label>
                    <input type="password" name="password" id="password" required minlength="8"
                        class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm"
                        placeholder="••••••••">
                    <p class="text-[11px] text-slate-500 mt-1">Minimum 8 characters.</p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8"
                        class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm"
                        placeholder="••••••••">
                </div>
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-start space-x-2.5">
            <svg class="w-4 h-4 text-amber-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span>Upon submission, this account will be created and the installer will permanently lock to secure your system.</span>
        </div>

        <!-- Action Buttons -->
        <div class="pt-4 border-t border-slate-200 flex items-center justify-end">
            <button type="submit" class="inline-flex items-center px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-md shadow-indigo-600/20 transition-all transform hover:-translate-y-0.5 cursor-pointer">
                <span>Complete Installation &amp; Lock</span>
                <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </button>
        </div>
    </form>
</div>
@endsection
