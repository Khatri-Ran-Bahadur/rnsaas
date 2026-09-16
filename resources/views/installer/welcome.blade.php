@extends('installer.layout', ['step' => 1])

@section('title', 'System Requirements Check')

@section('content')
<div class="space-y-8">
    <div>
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">System Requirements & Permissions</h2>
        <p class="mt-2 text-sm text-slate-600">
            Welcome to the SathiSaaS Installation Wizard. Before proceeding with system setup, verify that your server environment meets all necessary requirements.
        </p>
    </div>

    <!-- PHP Extensions Grid -->
    <div>
        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 flex items-center space-x-2">
            <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
            </svg>
            <span>PHP Environment & Extensions</span>
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($requirements as $key => $req)
                <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                    <div>
                        <div class="text-xs font-semibold text-slate-800">{{ $req['label'] }}</div>
                        <div class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $req['current'] }}</div>
                    </div>
                    @if($req['status'])
                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-300">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                    @else
                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-rose-100 text-rose-700 border border-rose-300">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Directory Permissions -->
    <div>
        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 flex items-center space-x-2">
            <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
            </svg>
            <span>Directory Write Permissions</span>
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($permissions as $key => $perm)
                <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                    <div>
                        <div class="text-xs font-semibold text-slate-800 font-mono">{{ $perm['path'] }}</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">{{ $perm['status'] ? 'Writable' : 'Permission Denied (chmod 775)' }}</div>
                    </div>
                    @if($perm['status'])
                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-300">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                    @else
                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-rose-100 text-rose-700 border border-rose-300">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="text-xs">
            @if($canProceed)
                <span class="text-emerald-700 font-semibold flex items-center space-x-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>All system requirements satisfied. Ready to proceed.</span>
                </span>
            @else
                <span class="text-rose-600 font-semibold">Please resolve the failed checks above before continuing.</span>
            @endif
        </div>

        @if($canProceed)
            <a href="{{ route('installer.license') }}" class="inline-flex items-center px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-md shadow-indigo-600/20 transition-all transform hover:-translate-y-0.5">
                <span>Next: License Verification</span>
                <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
        @else
            <button disabled class="inline-flex items-center px-6 py-3 rounded-xl bg-slate-100 text-slate-400 text-sm font-semibold cursor-not-allowed border border-slate-200">
                <span>Fix Requirements First</span>
            </button>
        @endif
    </div>
</div>
@endsection
