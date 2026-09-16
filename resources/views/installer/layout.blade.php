<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 text-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Installation Wizard') - SathiSaaS Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="min-h-full flex flex-col justify-between antialiased selection:bg-indigo-600 selection:text-white bg-slate-50 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(99,102,241,0.08),rgba(255,255,255,0))]">

    <!-- Top Navigation / Header -->
    <header class="border-b border-slate-200 bg-white/80 backdrop-blur-md sticky top-0 z-30 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-purple-600 flex items-center justify-center shadow-md shadow-indigo-500/20">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-base font-bold text-slate-900 tracking-tight">SathiSaaS <span class="text-xs px-2.5 py-0.5 ml-1.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 font-semibold">Installer</span></h1>
                </div>
            </div>
            <div class="flex items-center space-x-3 text-xs text-slate-500">
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 font-mono font-medium">v1.0.0 Pro</span>
                <a href="{{ url('/update') }}" class="hover:text-indigo-600 text-slate-600 transition-colors font-semibold">System Updater &rarr;</a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-4xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        
        <!-- Wizard Step Tracker -->
        @php
            $currentStep = $step ?? 1;
            $steps = [
                1 => ['label' => 'Requirements', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                2 => ['label' => 'License', 'icon' => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'],
                3 => ['label' => 'Database', 'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4'],
                4 => ['label' => 'System Setup', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                5 => ['label' => 'Super Admin', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                6 => ['label' => 'Ready', 'icon' => 'M5 13l4 4L19 7'],
            ];
        @endphp

        <div class="mb-10">
            <div class="grid grid-cols-6 gap-2 sm:gap-4 text-center">
                @foreach($steps as $num => $s)
                    <div class="flex flex-col items-center">
                        <div class="h-10 w-10 sm:h-12 sm:w-12 rounded-2xl flex items-center justify-center font-bold text-sm transition-all duration-300
                            {{ $num < $currentStep ? 'bg-emerald-50 text-emerald-600 border border-emerald-200 shadow-sm shadow-emerald-500/10' : '' }}
                            {{ $num == $currentStep ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25 ring-4 ring-indigo-100' : '' }}
                            {{ $num > $currentStep ? 'bg-white text-slate-400 border border-slate-200 shadow-sm' : '' }}
                        ">
                            @if($num < $currentStep)
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $s['icon'] }}"/>
                                </svg>
                            @endif
                        </div>
                        <span class="mt-2 text-xs {{ $num == $currentStep ? 'text-indigo-600 font-bold' : ($num < $currentStep ? 'text-emerald-600 font-semibold' : 'text-slate-400 font-medium') }} hidden sm:block">
                            {{ $s['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Global Flash Alerts -->
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
                <div class="font-bold text-rose-800 flex items-center space-x-2 mb-1.5">
                    <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Please correct the following errors:</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-rose-700 ml-6">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Card Container -->
        <div class="bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-10 shadow-xl shadow-slate-200/50 relative overflow-hidden">
            <div class="absolute -right-20 -top-20 w-60 h-60 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>
            @yield('content')
        </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 py-6 text-center text-xs text-slate-500 bg-white/40">
        <p>&copy; {{ date('Y') }} SathiSaaS Technologies. All rights reserved. Built for high reliability and scale.</p>
    </footer>

</body>
</html>
