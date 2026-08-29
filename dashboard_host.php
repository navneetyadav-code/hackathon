<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Thikana - Host Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark')
        }
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: { primary: '#4F46E5', hover: '#4338CA', secondary: '#F97316', light: '#EEF2FF', dark: '#1E1B4B' }
                    },
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
                    boxShadow: { soft: '0 4px 20px -2px rgba(0, 0, 0, 0.05)', floating: '0 10px 30px -5px rgba(79, 70, 229, 0.15)' }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            .glass-panel { @apply bg-white/90 border border-slate-200 shadow-soft backdrop-blur-md dark:bg-slate-900/90 dark:border-slate-700/50; }
            .nav-btn { @apply flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 hover:bg-slate-100 hover:text-slate-900 w-full text-left transition-all dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100; }
            .nav-btn.active { @apply bg-brand-primary text-white font-semibold shadow-floating dark:bg-brand-primary; }
            .nav-btn svg { color: currentColor; }
            .nav-btn.active svg, .nav-btn.active span { color: #ffffff !important; }
            .card { @apply bg-white border border-slate-200 rounded-2xl p-6 shadow-soft dark:bg-slate-900 dark:border-slate-800; }
            .btn-primary { @apply px-5 py-2.5 bg-brand-primary hover:bg-brand-hover text-white font-semibold rounded-xl transition-all shadow-md hover:shadow-floating; }
            .btn-secondary { @apply px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition-all dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300; }
        }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #94A3B8; border-radius: 4px; opacity: 0.5; }
        ::-webkit-scrollbar-thumb:hover { background: #64748B; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
        .page-section { display: none; opacity: 0; transform: translateY(10px); transition: opacity 0.3s ease, transform 0.3s ease; }
        .page-section.active { display: block; animation: fadeUp 0.4s forwards; }
        @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }
        @keyframes slideInDown { from { transform: translate(-50%, -100%); opacity: 0; } to { transform: translate(-50%, 0); opacity: 1; } }
        .toast-enter { animation: slideInDown 0.3s ease-out forwards; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 transition-colors duration-200 dark:bg-slate-950 dark:text-slate-100">

<div id="toast-container" class="fixed top-4 left-1/2 -translate-x-1/2 z-[100] flex flex-col gap-2 pointer-events-none"></div>

<div class="flex min-h-screen">
    <aside id="sidebar" class="sidebar w-[280px] h-screen fixed left-0 top-0 z-40 transition-transform duration-300 bg-white border-r border-slate-200 flex flex-col shadow-soft dark:bg-slate-900 dark:border-slate-800 lg:translate-x-0 -translate-x-full">
        <div class="h-20 flex items-center px-6 border-b border-slate-200 dark:border-slate-800">
            <a href="#" class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-white group">
                <svg class="w-8 h-8 text-brand-primary group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Room<span class="text-brand-primary">Mate</span>
            </a>
            <button class="lg:hidden ml-auto text-slate-500 hover:text-brand-primary dark:text-slate-400" onclick="toggleSidebar()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <nav class="flex-1 px-4 py-6 overflow-y-auto flex flex-col gap-2">
            <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-3 mb-2">Host Menu</p>
            
            <button class="nav-btn active" data-page="dashboard" onclick="openPage('dashboard', this)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span>Dashboard</span>
            </button>
            
            <button class="nav-btn" data-page="properties" onclick="openPage('properties', this)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                <span>My Properties</span>
            </button>
            
            <button class="nav-btn" data-page="seekers" onclick="openPage('seekers', this)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <span>Seekers</span>
            </button>
            
            <button class="nav-btn" data-page="messages" onclick="openPage('messages', this)">
                <div class="relative">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-brand-secondary rounded-full border-2 border-white dark:border-slate-900"></span>
                </div>
                <span>Messages</span>
            </button>

            <button class="nav-btn" data-page="settings" onclick="openPage('settings', this)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span>Settings</span>
            </button>
        </nav>

        <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900">
            <div class="flex items-center gap-3 p-2 rounded-xl">
                <div class="w-10 h-10 rounded-full bg-brand-light dark:bg-indigo-900/50 text-brand-primary dark:text-indigo-300 flex items-center justify-center font-bold text-lg border border-brand-primary/20">H</div>
                <div class="overflow-hidden">
                    <p class="font-bold text-slate-900 dark:text-white text-sm truncate">Host Name</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">Property Owner</p>
                </div>
            </div>
        </div>
    </aside>

    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-30 hidden lg:hidden" onclick="toggleSidebar()"></div>

    <main class="flex-1 w-full flex flex-col transition-all duration-300 lg:ml-[280px]">
        
        <header class="sticky top-0 z-30 h-20 px-6 flex items-center justify-between bg-white/85 dark:bg-slate-950/85 backdrop-blur-md border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-4">
                <button class="lg:hidden p-2 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg" onclick="toggleSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-full border border-slate-200 dark:border-slate-700 shadow-sm">
                    <svg class="w-4 h-4 text-brand-secondary" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                    <span>Your Location</span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button id="theme-toggle" class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition-transform hover:scale-105">
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                </button>
                
                <button class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm relative transition-transform hover:scale-105" onclick="showToast('You have new notifications!')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>
            </div>
        </header>

        <div class="flex-1 p-6 md:p-8 max-w-7xl mx-auto w-full">
            
            <section id="dashboard" class="page-section active">
                
                <div class="bg-gradient-to-br from-slate-900 to-indigo-600 dark:from-slate-900 dark:to-brand-primary rounded-3xl p-8 md:p-10 mb-8 shadow-floating text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-8 relative overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                    
                    <div class="relative z-10">
                        <h1 class="text-3xl md:text-5xl font-extrabold mb-2 leading-tight text-white drop-shadow-sm">Good morning 👋</h1>
                        <p class="text-indigo-100 text-lg">Here's what's happening with your property.</p>
                    </div>
                    
                    <button class="relative z-10 px-6 py-3 bg-white text-indigo-600 font-bold rounded-xl hover:bg-slate-50 transition-all shadow-lg hover:shadow-xl flex items-center gap-2" onclick="showToast('Add Property form opened!')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add Property
                    </button>
                </div>

                <div class="card mb-8">
                    <div class="flex flex-col md:flex-row gap-6 items-start md:items-center">
                        <div class="w-16 h-16 rounded-2xl bg-brand-light dark:bg-indigo-900/40 flex items-center justify-center text-3xl">🏠</div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-1">
                                <span class="text-xs font-bold text-brand-primary uppercase tracking-wider px-2 py-1 bg-brand-light dark:bg-indigo-900/30 rounded-md">Your Property</span>
                                <span class="flex items-center gap-1 text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-2 py-1 rounded-md">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                                </span>
                            </div>
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-1">Green View PG</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Near College, Your City
                            </p>
                        </div>
                        <button class="btn-secondary w-full md:w-auto whitespace-nowrap flex items-center justify-center gap-2" onclick="showToast('Navigating to Property Management...')">
                            Manage Property
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl">
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">4</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Total Rooms</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl">
                            <p class="text-2xl font-bold text-brand-primary">2</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Available</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl">
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">2</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Occupied</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">₹24K</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Expected / Month</p>
                        </div>
                    </div>
                </div>

                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    <button class="card flex items-center gap-4 hover:border-brand-primary/50 hover:shadow-md transition-all text-left" onclick="showToast('Add Property form opened!')">
                        <div class="w-12 h-12 rounded-full bg-brand-light dark:bg-indigo-900/40 text-brand-primary flex items-center justify-center text-xl">➕</div>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-sm">Add Property</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">List a new room, PG or flat.</p>
                        </div>
                    </button>
                    <button class="card flex items-center gap-4 hover:border-brand-primary/50 hover:shadow-md transition-all text-left" onclick="showToast('Room Management opened!')">
                        <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-500 flex items-center justify-center text-xl">🛏️</div>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-sm">Manage Rooms</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Update availability and rent.</p>
                        </div>
                    </button>
                    <button class="card flex items-center gap-4 hover:border-brand-primary/50 hover:shadow-md transition-all text-left" onclick="openPage('seekers', document.querySelector('[data-page=seekers]'))">
                        <div class="w-12 h-12 rounded-full bg-orange-50 dark:bg-orange-900/20 text-orange-500 flex items-center justify-center text-xl">👥</div>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-sm">View Seekers</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">See interested people.</p>
                        </div>
                    </button>
                </div>

                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">New Seeker Requests</h2>
                    <button class="text-sm font-medium text-brand-primary hover:text-brand-hover" onclick="openPage('seekers', document.querySelector('[data-page=seekers]'))">View all →</button>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
                    <div class="card flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                        <div class="w-12 h-12 rounded-full bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 flex items-center justify-center font-bold text-lg shrink-0">R</div>
                        <div class="flex-1">
                            <h3 class="font-bold text-slate-900 dark:text-white">Rahul</h3>
                            <p class="text-sm font-medium text-brand-primary">Budget: ₹6,000 / month</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Wants to move in September</p>
                        </div>
                        <div class="flex gap-2 w-full sm:w-auto mt-2 sm:mt-0">
                            <button class="flex-1 sm:flex-none px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-sm font-semibold transition-colors" onclick="showToast('Viewing Rahul\'s profile')">View</button>
                            <button class="flex-1 sm:flex-none px-4 py-2 bg-brand-primary hover:bg-brand-hover text-white rounded-lg text-sm font-semibold transition-colors shadow-sm" onclick="showToast('Accepted Rahul\'s request!')">Accept</button>
                        </div>
                    </div>
                    <div class="card flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                        <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 flex items-center justify-center font-bold text-lg shrink-0">A</div>
                        <div class="flex-1">
                            <h3 class="font-bold text-slate-900 dark:text-white">Aditya</h3>
                            <p class="text-sm font-medium text-brand-primary">Budget: ₹5,500 / month</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Wants to move in September</p>
                        </div>
                        <div class="flex gap-2 w-full sm:w-auto mt-2 sm:mt-0">
                            <button class="flex-1 sm:flex-none px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-sm font-semibold transition-colors" onclick="showToast('Viewing Aditya\'s profile')">View</button>
                            <button class="flex-1 sm:flex-none px-4 py-2 bg-brand-primary hover:bg-brand-hover text-white rounded-lg text-sm font-semibold transition-colors shadow-sm" onclick="showToast('Accepted Aditya\'s request!')">Accept</button>
                        </div>
                    </div>
                </div>

                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Overview</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="card flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-500 dark:bg-orange-900/20 flex items-center justify-center text-xl">👥</div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">7</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">People Interested</p>
                        </div>
                    </div>
                    <div class="card flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-500 dark:bg-blue-900/20 flex items-center justify-center text-xl">🏠</div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">2</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Rooms Available</p>
                        </div>
                    </div>
                    <div class="card flex items-center gap-4 border-b-4 border-b-green-500">
                        <div class="w-12 h-12 rounded-xl bg-green-50 text-green-500 dark:bg-green-900/20 flex items-center justify-center text-xl font-bold">₹</div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">₹24,000</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Monthly Expected</p>
                        </div>
                    </div>
                </div>

            </section>

            <section id="properties" class="page-section">
                <div class="card flex flex-col items-center justify-center py-16 text-center">
                    <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center text-3xl mb-4">🏠</div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">My Properties</h2>
                    <p class="text-slate-500 dark:text-slate-400 mb-6 max-w-sm">Manage all your listed properties, update rent, and change availability status.</p>
                    <button class="btn-primary" onclick="showToast('Add Property Modal Opened')">+ Add New Property</button>
                </div>
            </section>

            <section id="seekers" class="page-section">
                <div class="card flex flex-col items-center justify-center py-16 text-center">
                    <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center text-3xl mb-4">👥</div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Interested Seekers</h2>
                    <p class="text-slate-500 dark:text-slate-400 mb-6 max-w-sm">Review profiles of people looking for a place and accept their requests.</p>
                    <button class="btn-secondary" onclick="openPage('dashboard', document.querySelector('[data-page=dashboard]'))">Back to Dashboard</button>
                </div>
            </section>

            <section id="messages" class="page-section">
                <div class="card flex flex-col items-center justify-center py-16 text-center">
                    <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center text-3xl mb-4">💬</div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Messages</h2>
                    <p class="text-slate-500 dark:text-slate-400 max-w-sm">Chat with potential roommates and seekers directly.</p>
                </div>
            </section>

            <section id="settings" class="page-section">
                <div class="card flex flex-col items-center justify-center py-16 text-center">
                    <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center text-3xl mb-4">⚙️</div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Settings</h2>
                    <p class="text-slate-500 dark:text-slate-400 max-w-sm">Update your host profile and notification preferences.</p>
                </div>
            </section>

        </div>
    </main>
</div>

<script>
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
    const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        themeToggleLightIcon.classList.remove('hidden');
    } else {
        themeToggleDarkIcon.classList.remove('hidden');
    }

    themeToggleBtn.addEventListener('click', function() {
        themeToggleDarkIcon.classList.toggle('hidden');
        themeToggleLightIcon.classList.toggle('hidden');
        if (localStorage.getItem('color-theme')) {
            if (localStorage.getItem('color-theme') === 'light') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            }
        } else {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
        }
    });

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }

    function openPage(pageId, btnElement) {
        document.querySelectorAll('.page-section').forEach(el => {
            el.classList.remove('active');
        });
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.getElementById(pageId).classList.add('active');
        if(btnElement) {
            btnElement.classList.add('active');
        } else {
            document.querySelector(`[data-page="${pageId}"]`).classList.add('active');
        }
        if(window.innerWidth < 1024 && !document.getElementById('sidebar').classList.contains('-translate-x-full')) {
            toggleSidebar();
        }
    }

    function showToast(message) {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = 'glass-panel px-6 py-3 rounded-xl flex items-center gap-3 font-medium text-sm text-slate-800 dark:text-white toast-enter border-l-4 border-l-brand-primary';
        toast.innerHTML = `<svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> <span>${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translate(-50%, -100%)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
</script>

</body>
</html>