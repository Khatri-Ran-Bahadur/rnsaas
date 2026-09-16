@extends('installer.layout', ['step' => 2])

@section('title', 'License Verification & Activation')

@section('content')
<div class="space-y-6">
    <div>
        <div class="flex items-center space-x-3 mb-2">
            <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-600">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </span>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Software License & Activation</h2>
        </div>
        <p class="mt-1 text-sm text-slate-600">
            Please enter your purchase code or product license key. This verifies your purchase, activates platform features, and registers your domain for updates.
        </p>
    </div>

    @if($errors->has('license_error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-sm flex items-start space-x-3 shadow-sm">
            <svg class="w-5 h-5 text-rose-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <div class="font-bold text-rose-800">License Verification Failed</div>
                <div class="mt-0.5 text-rose-700">{{ $errors->first('license_error') }}</div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('installer.license.verify') }}" class="space-y-5">
        @csrf

        <!-- License Key / Purchase Code -->
        <div>
            <label for="license_key" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                License Key / Purchase Code *
            </label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <input
                    type="text"
                    name="license_key"
                    id="license_key"
                    value="{{ old('license_key', $currentLicense['key']) }}"
                    required
                    autofocus
                    placeholder="e.g. 883f9821-4f12-4be3-85f0-82390a12e84c or RN-SAAS-XXXX-XXXX"
                    class="w-full pl-10 pr-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm uppercase tracking-wide">
            </div>
            <p class="text-[11px] text-slate-500 mt-1.5 flex items-center gap-1">
                <span>Standard Envato Purchase Code (UUID format) or SathiSaaS Commercial License Key.</span>
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Buyer Name -->
            <div>
                <label for="buyer_name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                    Client / Buyer Full Name *
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <input
                        type="text"
                        name="buyer_name"
                        id="buyer_name"
                        value="{{ old('buyer_name', $currentLicense['buyer_name']) }}"
                        required
                        placeholder="e.g. John Doe / Acme Technologies"
                        class="w-full pl-10 pr-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                </div>
            </div>

            <!-- Buyer Email -->
            <div>
                <label for="buyer_email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                    License Registered Email
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206"/>
                        </svg>
                    </div>
                    <input
                        type="email"
                        name="buyer_email"
                        id="buyer_email"
                        value="{{ old('buyer_email', $currentLicense['buyer_email']) }}"
                        placeholder="buyer@example.com"
                        class="w-full pl-10 pr-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                </div>
            </div>
        </div>

        <!-- License Type -->
        <div>
            <label for="license_type" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                License Edition
            </label>
            <select
                name="license_type"
                id="license_type"
                class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                <option value="extended" {{ old('license_type', $currentLicense['license_type']) === 'extended' ? 'selected' : '' }}>
                    Extended SaaS Commercial License (Multi-Tenant & Commercial Billing Enabled)
                </option>
                <option value="regular" {{ old('license_type', $currentLicense['license_type']) === 'regular' ? 'selected' : '' }}>
                    Regular Single Installation License (Standard ERP Operations)
                </option>
            </select>
        </div>

        <!-- Helpful Guides & Demo Key Callout -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
                <div class="font-bold text-slate-900 flex items-center gap-1.5 mb-1">
                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Where to find your Purchase Code?</span>
                </div>
                <p>Go to your CodeCanyon/Envato account &gt; <strong>Downloads</strong> &gt; Click <em>License certificate &amp; purchase code</em>. The 36-character code is inside.</p>
            </div>

            <div class="p-3.5 rounded-xl bg-indigo-50/70 border border-indigo-200 text-xs text-indigo-900">
                <div class="font-bold text-indigo-900 flex items-center gap-1.5 mb-1">
                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>Testing / Local Development</span>
                </div>
                <p>Use developer demo key: <code class="font-mono bg-white border border-indigo-200 px-1.5 py-0.5 rounded text-indigo-700 font-semibold cursor-pointer select-all" onclick="document.getElementById('license_key').value='RN-SAAS-PRO-2026-ACTIVE'">RN-SAAS-PRO-2026-ACTIVE</code></p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
            <a href="{{ route('installer.welcome') }}" class="inline-flex items-center text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Back to Requirements</span>
            </a>

            <button type="submit" class="inline-flex items-center px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-md shadow-indigo-600/20 transition-all transform hover:-translate-y-0.5 cursor-pointer">
                <span>Verify &amp; Activate License</span>
                <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </div>
    </form>
</div>
@endsection
