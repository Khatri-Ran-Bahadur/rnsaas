<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 text-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Updater - SathiSaaS Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-full flex flex-col justify-between antialiased selection:bg-indigo-600 selection:text-white bg-slate-50 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(99,102,241,0.08),rgba(255,255,255,0))]">

    <!-- Header -->
    <header class="border-b border-slate-200 bg-white/80 backdrop-blur-md sticky top-0 z-30 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-purple-600 via-indigo-600 to-blue-600 flex items-center justify-center shadow-md shadow-indigo-500/20">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-base font-bold text-slate-900 tracking-tight">SathiSaaS <span class="text-xs px-2.5 py-0.5 ml-1.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200 font-semibold">System Updater</span></h1>
                </div>
            </div>
            <a href="{{ url('/') }}" class="text-xs text-slate-600 hover:text-indigo-600 font-semibold transition-colors">&larr; Back to App</a>
        </div>
    </header>

    <main class="flex-1 max-w-3xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <!-- Flash alerts -->
        @if(session('success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-start space-x-3 text-sm shadow-sm">
                <svg class="w-5 h-5 text-emerald-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="font-medium">{{ session('success') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-sm shadow-sm">
                <div class="font-bold text-rose-800 mb-1">Update encountered errors:</div>
                <ul class="list-disc list-inside space-y-1 text-rose-700 ml-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-10 shadow-xl shadow-slate-200/50 space-y-8">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Application & Schema Updates</h2>
                <p class="mt-2 text-sm text-slate-600">
                    When you update the code or extract a new version ZIP file, this tool executes pending database migrations and flushes compiled framework caches automatically.
                </p>
            </div>

            <!-- Version Status Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                    <div class="text-xs text-slate-500 font-medium">Currently Installed Version</div>
                    <div class="text-2xl font-bold font-mono text-slate-900 mt-1">v{{ $currentVersion }}</div>
                    <div class="text-[11px] text-slate-400 mt-1">From storage/installed</div>
                </div>

                <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-200">
                    <div class="text-xs text-indigo-700 font-medium">Target Codebase Version</div>
                    <div class="text-2xl font-bold font-mono text-indigo-900 mt-1">v{{ $targetVersion }}</div>
                    <div class="text-[11px] text-indigo-600/80 mt-1">Latest release package</div>
                </div>
            </div>

            <!-- Update Action Form -->
            <form method="POST" action="{{ route('updater.run') }}" onsubmit="document.getElementById('updateBtn').disabled = true; document.getElementById('updateText').innerText = 'Executing migrations & optimizations...';">
                @csrf

                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2 text-xs text-slate-600 mb-6">
                    <div class="font-semibold text-slate-800">What happens during update:</div>
                    <ul class="list-disc list-inside space-y-1 text-slate-600">
                        <li>Executes all newly added migration files across modules (<code class="font-mono text-indigo-600 bg-white px-1.5 py-0.5 rounded border border-slate-200">php artisan migrate --force</code>)</li>
                        <li>Flushes and rebuilds compiled routes, views, events, and configuration caches</li>
                        <li>Updates the system lockfile to reflect the new release version</li>
                    </ul>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-200">
                    <a href="{{ url('/') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
                        &larr; Cancel &amp; Return
                    </a>

                    <button type="submit" id="updateBtn" class="inline-flex items-center px-6 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-sm font-semibold shadow-md shadow-indigo-600/20 transition-all transform hover:-translate-y-0.5 cursor-pointer">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span id="updateText">Run Database Migrations &amp; Update</span>
                    </button>
                </div>
            </form>

            @if(session('output'))
                <div class="mt-6">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Artisan Migration Output:</div>
                    <pre class="p-4 rounded-xl bg-slate-900 text-emerald-400 text-xs font-mono overflow-x-auto whitespace-pre-wrap shadow-inner">{{ session('output') }}</pre>
                </div>
            @endif
        </div>
    </main>

    <footer class="border-t border-slate-200 py-6 text-center text-xs text-slate-500 bg-white/40">
        <p>&copy; {{ date('Y') }} SathiSaaS Technologies. Built for seamless continuous upgrades.</p>
    </footer>

</body>
</html>
