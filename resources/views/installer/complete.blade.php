@extends('installer.layout', ['step' => 6])

@section('title', 'Installation Complete')

@section('content')
<div class="text-center py-6 space-y-6">
    <!-- Success Icon -->
    <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-3xl bg-emerald-50 border-2 border-emerald-200 text-emerald-600 shadow-md shadow-emerald-500/10">
        <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
        </svg>
    </div>

    <div>
        <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Installation Successfully Completed!</h2>
        <p class="mt-2 text-sm text-slate-600 max-w-lg mx-auto">
            SathiSaaS is fully configured and ready for live production use. Your software license has been verified and registered for this domain.
        </p>
    </div>

    <!-- Credentials & Lock Status Summary -->
    <div class="max-w-md mx-auto p-5 rounded-2xl bg-slate-50 border border-slate-200 text-left space-y-3">
        <div class="flex justify-between items-center text-xs pb-2.5 border-b border-slate-200">
            <span class="text-slate-500">Installation State</span>
            <span class="text-emerald-700 font-bold font-mono uppercase text-[11px] px-2 py-0.5 rounded bg-emerald-100 border border-emerald-300">Locked &amp; Secured</span>
        </div>
        <div class="flex justify-between items-center text-xs pb-2.5 border-b border-slate-200">
            <span class="text-slate-500">Software License</span>
            <span class="text-indigo-700 font-bold font-mono text-[11px] px-2 py-0.5 rounded bg-indigo-50 border border-indigo-200">
                {{ !empty($installedData['license_key']) ? (new \App\Services\LicenseService)->maskKey($installedData['license_key']) : 'ACTIVE' }}
            </span>
        </div>
        <div class="flex justify-between items-center text-xs pb-2.5 border-b border-slate-200">
            <span class="text-slate-500">Licensed Buyer</span>
            <span class="text-slate-800 font-medium text-xs">{{ $installedData['buyer_name'] ?? 'Licensed Customer' }}</span>
        </div>
        <div class="flex justify-between items-center text-xs pb-2.5 border-b border-slate-200">
            <span class="text-slate-500">License Edition</span>
            <span class="text-slate-700 text-xs">{{ $installedData['license_type'] ?? 'Extended SaaS Commercial License' }}</span>
        </div>
        <div class="flex justify-between items-center text-xs pb-2.5 border-b border-slate-200">
            <span class="text-slate-500">SuperAdmin Email</span>
            <span class="text-indigo-600 font-semibold text-xs">{{ $installedData['admin_email'] ?? 'admin@sathisaas.com' }}</span>
        </div>
        <div class="flex justify-between items-center text-xs">
            <span class="text-slate-500">Platform Version</span>
            <span class="text-slate-700 font-mono text-[11px]">v{{ $installedData['app_version'] ?? '1.0.0' }}</span>
        </div>
    </div>

    <div class="p-4 max-w-md mx-auto rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-500 text-center">
        <p>For security, the installer routes are now permanently locked. If you ship an upgrade in the future, use the <a href="{{ url('/update') }}" class="text-indigo-600 underline font-medium">System Updater</a>.</p>
    </div>

    <!-- Navigation Buttons -->
    <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
        <a href="{{ url('/login') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-md shadow-indigo-600/20 transition-all transform hover:-translate-y-0.5">
            <span>Log In to Application</span>
            <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
        </a>
        <a href="{{ url('/admin') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-sm font-semibold shadow-sm transition-all">
            <span>Go to Admin Dashboard</span>
            <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>
@endsection
