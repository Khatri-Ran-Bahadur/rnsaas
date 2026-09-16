@extends('installer.layout', ['step' => 4])

@section('title', 'Application & Core Setup')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">System & Core Setup</h2>
        <p class="mt-2 text-sm text-slate-600">
            Define your application identity and initialize the core database schemas, statutory tax tables, and foundation seeders.
        </p>
    </div>

    <form method="POST" action="{{ route('installer.application.run') }}" class="space-y-6" id="setupForm" onsubmit="document.getElementById('submitBtn').disabled = true; document.getElementById('btnText').innerText = 'Initializing System (Please wait)...'; document.getElementById('spinner').classList.remove('hidden');">
        @csrf

        <div class="space-y-4">
            <!-- App Name -->
            <div>
                <label for="app_name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Application / Business Name</label>
                <input type="text" name="app_name" id="app_name" value="{{ old('app_name', $appName) }}" required
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-medium text-sm"
                    placeholder="SathiSaaS Platform">
            </div>

            <!-- App URL -->
            <div>
                <label for="app_url" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Application Root URL</label>
                <input type="url" name="app_url" id="app_url" value="{{ old('app_url', $appUrl) }}" required
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm"
                    placeholder="https://app.yourdomain.com">
                <p class="text-[11px] text-slate-500 mt-1.5">Must match the exact URL including scheme (http:// or https://) where your application is accessed.</p>
            </div>

            <!-- Demo Data Checkbox -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-start space-x-3.5">
                <div class="flex items-center h-5 mt-0.5">
                    <input type="checkbox" name="with_demo" id="with_demo" value="1" {{ old('with_demo') ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-white">
                </div>
                <div>
                    <label for="with_demo" class="text-sm font-semibold text-slate-800 cursor-pointer">Import Showcase Demo Data</label>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Includes sample customers, vendors, invoices, products, POS orders, and manufacturing batches for testing. Leave unchecked for a clean, pure production environment.
                    </p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
            <a href="{{ route('installer.database') }}" class="inline-flex items-center text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Back to Database</span>
            </a>

            <button type="submit" id="submitBtn" class="inline-flex items-center px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-md shadow-indigo-600/20 transition-all transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                <svg id="spinner" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white hidden" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span id="btnText">Run Migrations &amp; Core Setup</span>
                <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </div>
    </form>
</div>
@endsection
