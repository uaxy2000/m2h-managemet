<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — M2H Management</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full"
      x-data="{
          sidebar: localStorage.getItem('sidebar') !== 'closed',
          mobileNav: false,
          toggleSidebar() {
              if (window.innerWidth >= 1024) {
                  this.sidebar = !this.sidebar;
                  localStorage.setItem('sidebar', this.sidebar ? 'open' : 'closed');
              } else {
                  this.mobileNav = !this.mobileNav;
              }
          }
      }">

<div class="flex h-screen overflow-hidden">

    {{-- Mobile backdrop --}}
    <div x-show="mobileNav" x-cloak
         class="fixed inset-0 bg-black/50 z-40 lg:hidden"
         @click="mobileNav = false"></div>

    {{-- Sidebar --}}
    <aside class="fixed lg:relative inset-y-0 left-0 z-50 lg:z-auto
                  w-64 bg-slate-900 flex-shrink-0 flex flex-col
                  transition-all duration-200 ease-in-out overflow-hidden
                  lg:translate-x-0"
           :class="{
               '-translate-x-full': !mobileNav,
               'translate-x-0': mobileNav,
               'lg:w-64': sidebar,
               'lg:w-16': !sidebar,
           }">

        {{-- Brand --}}
        <div class="h-16 flex items-center border-b border-slate-700/60 flex-shrink-0"
             :class="sidebar ? 'px-6' : 'lg:justify-center lg:px-0 px-6'">
            <span x-show="sidebar || mobileNav" class="text-white font-bold text-lg tracking-tight whitespace-nowrap">M2H Management</span>
            <span x-show="!sidebar && !mobileNav" class="text-white font-bold text-lg hidden lg:block">M</span>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-2 py-5 space-y-0.5 overflow-y-auto overflow-x-hidden">

            <a href="{{ route('dashboard') }}"
               :class="(sidebar || mobileNav) ? 'px-3 gap-3' : 'lg:justify-center lg:px-0 px-3 gap-3'"
               class="group flex items-center py-2 rounded-lg text-sm font-medium transition-colors
                      {{ request()->routeIs('dashboard') ? 'bg-slate-700 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
               :title="(!sidebar && !mobileNav) ? 'Dashboard' : ''">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                <span x-show="sidebar || mobileNav" class="whitespace-nowrap">Dashboard</span>
            </a>

            <a href="{{ route('leads.index') }}"
               :class="(sidebar || mobileNav) ? 'px-3 gap-3' : 'lg:justify-center lg:px-0 px-3 gap-3'"
               class="group flex items-center py-2 rounded-lg text-sm font-medium transition-colors
                      {{ request()->is('leads*') ? 'bg-slate-700 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
               :title="(!sidebar && !mobileNav) ? 'Leads' : ''">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                <span x-show="sidebar || mobileNav" class="whitespace-nowrap">Leads</span>
            </a>

            <a href="#"
               :class="(sidebar || mobileNav) ? 'px-3 gap-3' : 'lg:justify-center lg:px-0 px-3 gap-3'"
               class="group flex items-center py-2 rounded-lg text-sm font-medium text-slate-500 cursor-not-allowed"
               :title="(!sidebar && !mobileNav) ? 'Pipeline (soon)' : ''">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                </svg>
                <span x-show="sidebar || mobileNav" class="whitespace-nowrap">Pipeline</span>
                <span x-show="sidebar || mobileNav" class="ml-auto text-xs text-slate-600 whitespace-nowrap">Soon</span>
            </a>

            <a href="#"
               :class="(sidebar || mobileNav) ? 'px-3 gap-3' : 'lg:justify-center lg:px-0 px-3 gap-3'"
               class="group flex items-center py-2 rounded-lg text-sm font-medium text-slate-500 cursor-not-allowed"
               :title="(!sidebar && !mobileNav) ? 'Tasks (soon)' : ''">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span x-show="sidebar || mobileNav" class="whitespace-nowrap">Tasks</span>
                <span x-show="sidebar || mobileNav" class="ml-auto text-xs text-slate-600 whitespace-nowrap">Soon</span>
            </a>

            @if(auth()->user()->isAdmin())
            <div class="pt-4 mt-4 border-t border-slate-700/60">
                <a href="{{ route('settings.pipelines.index') }}"
                   :class="(sidebar || mobileNav) ? 'px-3 gap-3' : 'lg:justify-center lg:px-0 px-3 gap-3'"
                   class="group flex items-center py-2 rounded-lg text-sm font-medium transition-colors
                          {{ request()->is('settings/*') ? 'bg-slate-700 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                   :title="(!sidebar && !mobileNav) ? 'Settings' : ''">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <span x-show="sidebar || mobileNav" class="whitespace-nowrap">Settings</span>
                </a>
            </div>
            @endif

        </nav>

        {{-- User section --}}
        <div class="border-t border-slate-700/60 flex-shrink-0"
             :class="(sidebar || mobileNav) ? 'p-4' : 'lg:p-2 p-4'">
            <div class="flex items-center"
                 :class="(sidebar || mobileNav) ? 'gap-3' : 'lg:justify-center gap-3'">
                <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white text-sm font-semibold flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0" x-show="sidebar || mobileNav">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ auth()->user()->roleLabel() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" x-show="sidebar || mobileNav">
                    @csrf
                    <button type="submit" title="Sign out"
                            class="text-slate-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>

    </aside>

    {{-- Main area --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Top bar --}}
        <header class="h-16 bg-white border-b border-gray-200 flex items-center gap-3 px-4 flex-shrink-0">
            <button @click="toggleSidebar()" type="button"
                    class="text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0 p-1 rounded-md hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>
            <h1 class="text-lg font-semibold text-gray-800 flex-1 truncate">@yield('heading', 'Dashboard')</h1>
            <div class="flex items-center gap-3 flex-shrink-0">
                <span class="text-xs font-medium px-2.5 py-1 rounded-full {{ auth()->user()->roleBadgeColor() }}">
                    {{ auth()->user()->roleLabel() }}
                </span>
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 @yield('main-class', 'overflow-y-auto p-4 lg:p-6')">
            @yield('content')
        </main>

    </div>

</div>

@stack('scripts')
</body>
</html>
