@extends('installer.layout', ['step' => 3])

@section('title', 'Database Configuration')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Database Configuration</h2>
        <p class="mt-2 text-sm text-slate-600">
            Configure your MySQL database parameters. The installer will test connection directly before writing to your <code class="text-indigo-600 font-mono bg-slate-100 px-1.5 py-0.5 rounded text-xs">.env</code> configuration.
        </p>
    </div>

    <form method="POST" action="{{ route('installer.database.save') }}" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Host -->
            <div>
                <label for="db_host" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Database Host</label>
                <input type="text" name="db_host" id="db_host" value="{{ old('db_host', $currentDb['host']) }}" required
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm"
                    placeholder="127.0.0.1">
            </div>

            <!-- Port -->
            <div>
                <label for="db_port" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Port</label>
                <input type="number" name="db_port" id="db_port" value="{{ old('db_port', $currentDb['port']) }}" required
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm"
                    placeholder="3306">
            </div>

            <!-- Database Name -->
            <div class="sm:col-span-2">
                <label for="db_database" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Database Name</label>
                <input type="text" name="db_database" id="db_database" value="{{ old('db_database', $currentDb['database']) }}" required
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm"
                    placeholder="rnsaas">
                <p class="text-[11px] text-slate-500 mt-1.5">If this database does not exist yet, the installer will attempt to create it automatically.</p>
            </div>

            <!-- Username -->
            <div>
                <label for="db_username" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Database Username</label>
                <input type="text" name="db_username" id="db_username" value="{{ old('db_username', $currentDb['username']) }}" required
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm"
                    placeholder="root">
            </div>

            <!-- Password -->
            <div>
                <label for="db_password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Database Password</label>
                <input type="password" name="db_password" id="db_password" value="{{ old('db_password', $currentDb['password']) }}"
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm"
                    placeholder="••••••••">
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-200 text-xs text-indigo-900 flex items-start space-x-2.5">
            <svg class="w-4 h-4 text-indigo-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Clicking below tests live database connectivity. If successful, parameters are committed and setup moves to system initialization.</span>
        </div>

        <!-- Action Buttons -->
        <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
            <a href="{{ route('installer.license') }}" class="inline-flex items-center text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Back to License</span>
            </a>

            <button type="submit" class="inline-flex items-center px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-md shadow-indigo-600/20 transition-all transform hover:-translate-y-0.5 cursor-pointer">
                <span>Test Connection &amp; Save</span>
                <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </div>
    </form>
</div>
@endsection
