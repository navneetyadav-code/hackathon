<?php
session_start();
require_once 'config.php';

// 1. Handle Logout Action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// 2. Role-Based Access Control (RBAC) - Ensure only 'seeker' can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seeker') {
    header("Location: login.php");
    exit;
}

// 3. Fetch current user data from the database dynamically
$stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Security fallback: If user was deleted from DB but session exists
if (!$user) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Prepare dynamic variables for the UI
$firstName = htmlspecialchars($user['first_name']);
$lastName = htmlspecialchars($user['last_name']);
$fullName = $firstName . ' ' . $lastName;
$userInitial = strtoupper(substr($firstName, 0, 1));
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Thikana - Seeker Dashboard</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Check local storage for dark mode preference
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
                        brand: {
                            primary: '#4F46E5',
                            hover: '#4338CA',
                            secondary: '#F97316',
                            light: '#EEF2FF',
                            dark: '#1E1B4B'
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    boxShadow: {
                        'soft': '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
                        'floating': '0 10px 30px -5px rgba(79, 70, 229, 0.15)',
                    }
                }
            }
        }
    </script>

    <style type="text/tailwindcss">
        @layer utilities {
            .glass-panel {
                @apply bg-white/90 border border-slate-200 shadow-soft backdrop-blur-md dark:bg-slate-900/90 dark:border-slate-700/50;
            }
            .input-field {
                @apply w-full p-3 py-3 px-4 rounded-xl border border-slate-200 bg-white text-slate-900 focus:border-brand-primary focus:ring-2 focus:ring-brand-light outline-none transition-all dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:focus:ring-brand-primary/30;
            }
            .nav-btn {
                @apply flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 hover:bg-slate-100 hover:text-slate-900 w-full text-left transition-all dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100;
            }
            .nav-btn.active {
                @apply bg-brand-primary text-white font-semibold shadow-floating dark:bg-brand-primary;
            }
            .nav-btn svg { color: currentColor; }
            .nav-btn.active svg, .nav-btn.active span { color: #ffffff !important; }
        }

        /* Range Slider & Scrollbar Styling */
        input[type=range] { -webkit-appearance: none; width: 100%; background: transparent; }
        input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; height: 20px; width: 20px; border-radius: 50%; background: #4F46E5; cursor: pointer; margin-top: -8px; box-shadow: 0 2px 6px rgba(79, 70, 229, 0.4); }
        input[type=range]::-webkit-slider-runnable-track { width: 100%; height: 6px; cursor: pointer; background: #E2E8F0; border-radius: 999px; }
        .dark input[type=range]::-webkit-slider-runnable-track { background: #334155; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #94A3B8; border-radius: 4px; opacity: 0.5; }
        ::-webkit-scrollbar-thumb:hover { background: #64748B; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }

        /* Animations */
        .page-section { display: none; opacity: 0; transform: translateY(10px); transition: opacity 0.3s ease, transform 0.3s ease; }
        .page-section.active { display: block; animation: fadeUp 0.4s forwards; }
        @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }
    </style>
</head>

<body class="bg-slate-50 text-slate-900 transition-colors duration-200 dark:bg-slate-950 dark:text-slate-100">

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar w-[280px] h-screen fixed left-0 top-0 z-40 transition-transform duration-300 bg-white border-r border-slate-200 flex flex-col shadow-soft dark:bg-slate-900 dark:border-slate-800 lg:translate-x-0 -translate-x-full">
        <div class="h-20 flex items-center px-6 border-b border-slate-200 dark:border-slate-800">
            <a href="#" class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-white group">
                <svg class="w-8 h-8 text-brand-primary group-hover:scale-110 transition-transform" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                    <path d="M12 3L2 12h3v8h5v-6h4v6h5v-8h3L12 3zm0 2.5l5.5 4.95V18h-1v-6H7.5v6h-1v-7.55L12 5.5z"/>
                    <path d="M12 11a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"/>
                </svg>
                Thikana
            </a>
            <button class="lg:hidden ml-auto text-slate-500 hover:text-brand-primary dark:text-slate-400" onclick="toggleSidebar()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <nav class="flex-1 px-4 py-6 overflow-y-auto flex flex-col gap-2">
            <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-3 mb-2">Main Menu</p>
            
            <button class="nav-btn active" data-page="home" onclick="openPage('home', this)">
                <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span>Dashboard</span>
            </button>
            
            <button class="nav-btn" data-page="explore" onclick="openPage('explore', this)">
                <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <span>Explore Stays</span>
            </button>
            
            <button class="nav-btn" data-page="matcher" onclick="openPage('matcher', this)">
                <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                <span>Smart Match</span>
            </button>
            
            <button class="nav-btn" data-page="calculator" onclick="openPage('calculator', this)">
                <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                <span>Split & Budget</span>
            </button>
            
            <button class="nav-btn" data-page="messages" onclick="openPage('messages', this)">
                <div class="relative">
                    <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-brand-secondary rounded-full border-2 border-white dark:border-slate-900"></span>
                </div>
                <span>Messages</span>
            </button>

            <div class="mt-8">
                <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-3 mb-2">Account</p>
                <!-- DYNAMIC LOGOUT LINK -->
                <button class="nav-btn hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400" onclick="window.location.href='dashboard_seek.php?action=logout'">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span>Logout</span>
                </button>
            </div>
        </nav>

        <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900">
            <div class="flex items-center gap-3 p-2 rounded-xl">
                <!-- DYNAMIC AVATAR & NAME -->
                <div id="sidebar-avatar" class="w-10 h-10 rounded-full bg-brand-light dark:bg-indigo-900/50 text-brand-primary dark:text-indigo-300 flex items-center justify-center font-bold text-lg border border-brand-primary/20">
                    <?= $userInitial ?>
                </div>
                <div class="overflow-hidden">
                    <p id="sidebar-name" class="font-bold text-slate-900 dark:text-white text-sm truncate"><?= $fullName ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">Seeker Profile</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Overlay for mobile sidebar -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-30 hidden lg:hidden" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <main class="flex-1 w-full flex flex-col transition-all duration-300 lg:ml-[280px]">
        
        <!-- Topbar -->
        <header class="sticky top-0 z-30 h-20 px-6 flex items-center justify-between bg-white/85 dark:bg-slate-950/85 backdrop-blur-md border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-4">
                <button class="lg:hidden p-2 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg" onclick="toggleSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-full border border-slate-200 dark:border-slate-700 shadow-sm">
                    <svg class="w-4 h-4 text-brand-secondary" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                    <span id="current-location">Phagwara, Punjab</span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="hidden md:flex items-center gap-2 px-3 py-1.5 bg-brand-light dark:bg-indigo-900/30 text-brand-primary dark:text-indigo-300 rounded-full text-sm font-semibold border border-brand-primary/20 dark:border-indigo-500/30">
                    <span class="w-2 h-2 rounded-full bg-brand-primary dark:bg-indigo-400 animate-pulse"></span>
                    Budget Mode On
                </div>
                
                <button onclick="toggleDarkMode()" class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition-transform hover:scale-105">
                    <svg class="w-5 h-5 dark:hidden" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg class="w-5 h-5 hidden dark:block" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                </button>
            </div>
        </header>

        <div class="flex-1 p-6 md:p-8 max-w-7xl mx-auto w-full">
            
            <!-- Dashboard Home -->
            <section id="home" class="page-section active">
                
                <div class="bg-gradient-to-br from-slate-900 to-indigo-600 dark:from-slate-900 dark:to-brand-primary rounded-3xl p-8 md:p-10 mb-8 shadow-floating text-white flex flex-col md:flex-row justify-between items-start md:items-end gap-8 relative overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                    
                    <div class="max-w-2xl relative z-10">
                        <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium mb-4 border border-white/20 shadow-sm">Welcome back!</span>
                        <h1 class="text-3xl md:text-5xl font-extrabold mb-4 leading-tight text-white drop-shadow-sm">
                            <!-- DYNAMIC FIRST NAME -->
                            Hey <span id="welcome-firstname"><?= $firstName ?></span>, <br>
                            <span class="text-brand-secondary drop-shadow-md">find a place</span> that fits your money.
                        </h1>
                    </div>
                    
                    <div class="flex flex-row md:flex-col gap-4 w-full md:w-auto relative z-10">
                        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 flex-1 shadow-sm">
                            <p class="text-indigo-100 text-xs uppercase font-bold tracking-wider mb-1">Matches Nearby</p>
                            <p class="text-3xl font-bold text-white">12</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 flex-1 shadow-sm">
                            <p class="text-indigo-100 text-xs uppercase font-bold tracking-wider mb-1">Avg Monthly Share</p>
                            <p class="text-3xl font-bold text-white">₹8.5K</p>
                        </div>
                    </div>
                </div>

                <div class="grid lg:grid-cols-3 gap-8 mb-8">
                    <!-- Quick Actions -->
                    <div class="lg:col-span-2 grid sm:grid-cols-2 gap-4">
                        <button class="glass-panel p-6 rounded-2xl text-left hover:-translate-y-1 transition-all group" onclick="openPage('matcher')">
                            <div class="w-12 h-12 bg-brand-light dark:bg-indigo-900/40 text-brand-primary dark:text-indigo-400 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                            </div>
                            <h3 class="font-bold text-lg mb-1 group-hover:text-brand-primary dark:group-hover:text-indigo-400">Run Smart Match</h3>
                            <p class="text-slate-500 dark:text-slate-400 text-sm">Compare specific properties with your monthly income.</p>
                        </button>
                        
                        <button class="glass-panel p-6 rounded-2xl text-left hover:-translate-y-1 transition-all group" onclick="openPage('calculator')">
                            <div class="w-12 h-12 bg-orange-50 dark:bg-orange-900/30 text-brand-secondary dark:text-orange-400 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            </div>
                            <h3 class="font-bold text-lg mb-1 group-hover:text-brand-secondary dark:group-hover:text-orange-400">Calculate Split</h3>
                            <p class="text-slate-500 dark:text-slate-400 text-sm">Figure out equal or custom roommate splits easily.</p>
                        </button>
                    </div>

                    <!-- Next Best Action -->
                    <div class="glass-panel rounded-2xl p-6 flex flex-col">
                        <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-brand-primary dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            Priority Task
                        </h3>
                        <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-4 flex-1 flex flex-col justify-center">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-semibold text-slate-900 dark:text-slate-200">Host replied</span>
                                <span class="text-xs text-brand-primary dark:text-indigo-300 font-bold bg-brand-light dark:bg-indigo-900/50 px-2 py-1 rounded">New</span>
                            </div>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Meera from Green View PG just responded to your query.</p>
                            <button class="mt-auto w-full py-2 bg-brand-primary text-white rounded-lg font-medium hover:bg-brand-hover transition-colors" onclick="openPage('messages')">
                                View Message
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Explore Page -->
            <section id="explore" class="page-section">
                <div class="mb-8">
                    <h1 class="text-3xl font-extrabold mb-2">Explore Stays</h1>
                    <p class="text-slate-500 dark:text-slate-400">Search by area, budget and room type without leaving the dashboard.</p>
                </div>
                <!-- Filtering interface -->
                <div class="glass-panel p-4 md:p-6 rounded-2xl mb-8 flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-1 w-full">
                        <label class="block text-sm font-semibold text-slate-900 dark:text-slate-200 mb-2">Search Location or Name</label>
                        <input type="text" class="input-field" placeholder="e.g., Koramangala, PG...">
                    </div>
                    <button class="w-full md:w-auto bg-brand-primary text-white px-6 py-3 rounded-xl font-bold hover:bg-brand-hover shadow-md transition-all">
                        Search
                    </button>
                </div>
                <div class="text-center text-slate-500 py-10 glass-panel rounded-xl">
                    Dynamic search listings will appear here.
                </div>
            </section>

            <!-- Matcher Page -->
            <section id="matcher" class="page-section">
                <div class="mb-8">
                    <h1 class="text-3xl font-extrabold mb-2">Smart Property Match</h1>
                    <p class="text-slate-500 dark:text-slate-400">Analyze a property share against your income.</p>
                </div>
                <div class="text-center text-slate-500 py-10 glass-panel rounded-xl">
                    Smart property matcher interface requires property selection. Go to Explore to match.
                </div>
            </section>

            <!-- Calculator Page -->
            <section id="calculator" class="page-section">
                <div class="mb-8 max-w-3xl">
                    <h1 class="text-3xl font-extrabold mb-2">Split & Budget Calculator</h1>
                    <p class="text-slate-500 dark:text-slate-400">Figure out fair roommate splits and verify affordability for any property.</p>
                </div>
                <div class="text-center text-slate-500 py-10 glass-panel rounded-xl">
                    Stand-alone calculator logic initializing...
                </div>
            </section>

            <!-- Messages Page -->
            <section id="messages" class="page-section h-[calc(100vh-140px)] min-h-[500px]">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl h-full flex items-center justify-center shadow-soft">
                    <p class="text-slate-500">Your chat threads will appear here.</p>
                </div>
            </section>

        </div>
    </main>
</div>

<!-- Mobile Bottom Nav -->
<nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex justify-around p-2 z-40 shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
    <button class="flex flex-col items-center p-2 text-slate-500 dark:text-slate-400 hover:text-brand-primary" onclick="openPage('home', this)">
        <span class="text-[10px] font-medium mt-1">Home</span>
    </button>
    <button class="flex flex-col items-center p-2 text-slate-500 dark:text-slate-400 hover:text-brand-primary" onclick="openPage('explore', this)">
        <span class="text-[10px] font-medium mt-1">Explore</span>
    </button>
    <button class="flex flex-col items-center p-2 text-slate-500 dark:text-slate-400 hover:text-brand-primary" onclick="openPage('matcher', this)">
        <span class="text-[10px] font-medium mt-1">Match</span>
    </button>
</nav>

<script>
    // Tab switching functionality to make the dashboard dynamic without external JS dependencies
    function openPage(pageId, btn) {
        // Hide all pages
        document.querySelectorAll('.page-section').forEach(el => {
            el.classList.remove('active');
        });
        
        // Show target page
        document.getElementById(pageId).classList.add('active');
        
        // Update active states on buttons if passed
        if(btn) {
            document.querySelectorAll('.nav-btn').forEach(el => el.classList.remove('active'));
            btn.classList.add('active');
        }
        
        // Auto-close sidebar on mobile
        if(window.innerWidth < 1024) {
            document.getElementById('sidebar').classList.add('-translate-x-full');
            document.getElementById('sidebar-overlay').classList.add('hidden');
        }
    }

    // Mobile Sidebar Toggle
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }

    // Dark Mode Toggle
    function toggleDarkMode() {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('color-theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('color-theme', 'dark');
        }
    }
</script>

<!-- If you still want to include your custom external scripts, you can keep this -->
<script src="assets/js/dashboard.js"></script>
</body>
</html>