<?php
session_start();
require_once __DIR__ . '/config/app.php';

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    // header('Location: login.php'); // Uncomment in production
    // exit;
}

// Seeker dashboard can only be accessed by seekers
if (($_SESSION['role'] ?? 'seeker') !== 'seeker') {
    if (($_SESSION['role'] ?? '') === 'host') {
        if(function_exists('thikana_host_dashboard_redirect')){
             header('Location: ' . thikana_host_dashboard_redirect($_SESSION['user_id']));
        }
    } else {
        // header('Location: login.php'); // Uncomment in production
    }
    // exit;
}

$firstName = $_SESSION['first_name'] ?? 'Guest';
$fullName = trim(
    ($_SESSION['first_name'] ?? 'Guest') . ' ' .
    ($_SESSION['last_name'] ?? 'User')
);

$displayName = $fullName ?: $firstName;
$initial = strtoupper(substr($firstName, 0, 1));
$thikanaUser = [
    'firstName' => $firstName,
    'lastName' => $_SESSION['last_name'] ?? '',
    'email' => $_SESSION['email'] ?? ''
];

// Database Connection Setup
$recommendedProperties = [];
$allProperties = [];
$db_error = null;
$pdo = thikana_db();

try {
    if (!$pdo) {
        throw new RuntimeException('Database connection is not available.');
    }

    $tableExists = function (string $tableName) use ($pdo): bool {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $stmt->execute([$tableName]);

        return (int) $stmt->fetchColumn() > 0;
    };

    $hasPropertyImages = $tableExists('property_images');
    $hasPropertyAmenities = $tableExists('property_amenities');
    $mainImageSelect = $hasPropertyImages
        ? "COALESCE((SELECT image_path FROM property_images pi WHERE pi.property_id = p.id AND pi.is_primary = 1 LIMIT 1), p.image_path) AS main_image"
        : "p.image_path AS main_image";
    $amenitiesSelect = $hasPropertyAmenities
        ? ", (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM property_amenities WHERE property_id = p.id) AS amenities_list"
        : ", p.amenities AS amenities_list";

    // 1. Fetch Recommended Properties (Active, has rooms, ordered by newest)
    $stmtRec = $pdo->prepare("
        SELECT p.*, $mainImageSelect
        FROM properties p
        WHERE p.status = 'active' AND p.available_rooms > 0
        ORDER BY p.created_at DESC
        LIMIT 3
    ");
    $stmtRec->execute();
    $recommendedProperties = $stmtRec->fetchAll();

    // 2. Fetch All Properties for the 'Explore' section (For JS filtering)
    $stmtAll = $pdo->prepare("
        SELECT p.*, $mainImageSelect $amenitiesSelect
        FROM properties p
        WHERE p.status = 'active'
        ORDER BY p.created_at DESC
    ");
    $stmtAll->execute();
    $allProperties = $stmtAll->fetchAll();

} catch (Throwable $e) {
    error_log('Seeker dashboard property fetch failed: ' . $e->getMessage());
    $db_error = "Unable to load properties from the database. Please check the database connection.";
}

// Helper function for placeholder images if none exists in DB
function getPropImage($path) {
    if (empty($path)) {
        return 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=800&q=80';
    }
    return htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Thikana - Dashboard</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Prevent dark mode flash
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark')
        }
        
        // Pass user and real database properties to frontend JS
        window.thikanaUser = <?= json_encode($thikanaUser, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.realProperties = <?= json_encode($allProperties, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            primary: '#4F46E5',    /* Indigo */
                            hover: '#4338CA',
                            secondary: '#F97316',  /* Warm Orange */
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

        /* Range Slider & Scrollbars */
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

        .page-section { display: none; opacity: 0; transform: translateY(10px); transition: opacity 0.3s ease, transform 0.3s ease; }
        .page-section.active { display: block; animation: fadeUp 0.4s forwards; }
        @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }
        
        @keyframes slideInDown {
            from { transform: translate(-50%, -100%); opacity: 0; }
            to { transform: translate(-50%, 0); opacity: 1; }
        }
        .toast-enter { animation: slideInDown 0.3s ease-out forwards; }
    </style>
</head>

<body class="bg-slate-50 text-slate-900 transition-colors duration-200 dark:bg-slate-950 dark:text-slate-100">

<div id="toast-container" class="fixed top-4 left-1/2 -translate-x-1/2 z-[100] flex flex-col gap-2 pointer-events-none"></div>

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
            
            <button class="nav-btn active" data-page="home" onclick="openPage('home')">
                <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span>Dashboard</span>
            </button>
            
            <button class="nav-btn" data-page="explore" onclick="openPage('explore')">
                <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <span>Explore Stays</span>
            </button>
            
            <button class="nav-btn" data-page="matcher" onclick="openPage('matcher')">
                <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                <span>Smart Match</span>
            </button>
            
            <button class="nav-btn" data-page="calculator" onclick="openPage('calculator')">
                <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                <span>Split & Budget</span>
            </button>
            
            <button class="nav-btn" data-page="messages" onclick="openPage('messages')">
                <div class="relative">
                    <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-brand-secondary rounded-full border-2 border-white dark:border-slate-900"></span>
                </div>
                <span>Messages</span>
            </button>

            <div class="mt-8">
                <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-3 mb-2">Account</p>
                <button class="nav-btn hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400" onclick="logout()">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span>Logout</span>
                </button>
            </div>
        </nav>

        <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900">
            <div class="flex items-center gap-3 p-2 rounded-xl">
                <div class="w-10 h-10 rounded-full bg-brand-light dark:bg-indigo-900/50 text-brand-primary dark:text-indigo-300 flex items-center justify-center font-bold text-lg border border-brand-primary/20">
                    <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div class="overflow-hidden">
                    <p class="font-bold text-slate-900 dark:text-white text-sm truncate"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">Seeker Profile</p>
                </div>
            </div>
        </div>
    </aside>

    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-30 hidden lg:hidden" onclick="toggleSidebar()"></div>
    
    <!-- Main Content -->
    <main class="flex-1 w-full flex flex-col transition-all duration-300 lg:ml-[280px]">
        
        <header class="sticky top-0 z-30 h-20 px-6 flex items-center justify-between bg-white/85 dark:bg-slate-950/85 backdrop-blur-md border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-4">
                <button class="lg:hidden p-2 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg" onclick="toggleSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>

            <div class="flex items-center gap-4">
                <!-- Theme Toggle -->
                <button id="theme-toggle" class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition-transform hover:scale-105" onclick="toggleTheme()">
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                </button>
                
                <button class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm relative transition-transform hover:scale-105" onclick="openPage('messages')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>
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
                            Hey <?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?>, <br>
                            <span class="text-brand-secondary drop-shadow-md text-lg md:text-2xl font-semibold leading-snug">Your Room. Your Vibe. Your People.</span>
                        </h1>
                    </div>
                </div>

                <div class="grid lg:grid-cols-3 gap-8 mb-8">
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
                            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">You have new unread messages from hosts.</p>
                            <button class="mt-auto w-full py-2 bg-brand-primary text-white rounded-lg font-medium hover:bg-brand-hover transition-colors" onclick="openPage('messages')">
                                View Messages
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-end mb-6">
                    <div>
                        <h2 class="text-2xl font-bold">Recommended for you</h2>
                        <p class="text-slate-500 dark:text-slate-400 text-sm">Fresh, active listings matching your criteria.</p>
                    </div>
                    <button class="text-brand-primary dark:text-indigo-400 font-semibold hover:text-brand-hover dark:hover:text-indigo-300 flex items-center gap-1" onclick="openPage('explore')">
                        View all <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>
                
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (!empty($db_error)): ?>
                        <div class="col-span-full bg-red-50 text-red-600 p-4 rounded-xl text-center border border-red-200 dark:bg-red-900/20 dark:border-red-900/50">
                            <?= htmlspecialchars($db_error) ?>
                        </div>
                    <?php elseif (empty($recommendedProperties)): ?>
                        <div class="col-span-full bg-slate-100 text-slate-500 p-8 rounded-2xl text-center border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                            <svg class="w-12 h-12 mx-auto mb-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            <p class="font-bold text-lg">No active properties right now.</p>
                            <p class="text-sm mt-1">Check back later or expand your search.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recommendedProperties as $prop): ?>
                            <div class="glass-panel rounded-2xl overflow-hidden flex flex-col group hover:-translate-y-1 transition-all">
                                <div class="h-56 relative overflow-hidden bg-slate-200 dark:bg-slate-800">
                                    <img src="<?= getPropImage($prop['main_image']) ?>" alt="<?= htmlspecialchars($prop['property_name']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                                    <div class="absolute top-3 left-3 bg-white/90 dark:bg-slate-900/90 backdrop-blur px-2 py-1 rounded text-xs font-bold text-slate-900 dark:text-white shadow-sm uppercase">
                                        <?= htmlspecialchars(strtoupper($prop['property_type'])) ?>
                                    </div>
                                    <div class="absolute bottom-3 left-4 right-4 flex justify-between items-end">
                                        <div class="text-white">
                                            <p class="text-xs font-medium flex items-center gap-1 opacity-90">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                                                <?= htmlspecialchars($prop['city']) ?>
                                            </p>
                                        </div>
                                        <div class="bg-brand-primary text-white px-3 py-1.5 rounded-lg font-bold shadow-md">
                                            ₹<?= number_format($prop['rent']) ?><span class="text-[10px] font-normal opacity-80">/mo</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-5 flex-1 flex flex-col bg-white dark:bg-slate-800">
                                    <h3 class="font-bold text-lg leading-tight mb-2 text-slate-900 dark:text-white line-clamp-1"><?= htmlspecialchars($prop['property_name']) ?></h3>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 line-clamp-2"><?= htmlspecialchars($prop['address']) ?></p>

                                    <div class="mt-auto flex items-center justify-between border-t border-slate-100 dark:border-slate-700 pt-4">
                                        <span class="text-xs font-semibold px-2 py-1 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded">
                                            <?= (int)$prop['available_rooms'] ?> Room(s) Left
                                        </span>
                                        <button class="text-sm font-bold text-brand-primary dark:text-indigo-400 hover:text-brand-hover" onclick="populateMatcherAndOpen(<?= $prop['rent'] ?>)">
                                            Analyze Match →
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Explore Page -->
            <section id="explore" class="page-section">
                <div class="mb-8">
                    <h1 class="text-3xl font-extrabold mb-2">Explore All Stays</h1>
                    <p class="text-slate-500 dark:text-slate-400">Search dynamically against all active database properties.</p>
                </div>

                <div class="glass-panel p-4 md:p-6 rounded-2xl mb-8 flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-1 w-full">
                        <label class="block text-sm font-semibold text-slate-900 dark:text-slate-200 mb-2">Search Location or Name</label>
                        <div class="relative">
                            <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <input type="text" id="listing-search" class="input-field pl-10" placeholder="e.g., Koramangala, PG..." oninput="renderFilteredListings()">
                        </div>
                    </div>
                    <div class="w-full md:w-48">
                        <label class="block text-sm font-semibold text-slate-900 dark:text-slate-200 mb-2">Max Budget</label>
                        <select id="budget-filter" class="input-field" onchange="renderFilteredListings()">
                            <option value="all">Any budget</option>
                            <option value="5000">Under ₹5,000</option>
                            <option value="10000">Under ₹10,000</option>
                            <option value="15000">Under ₹15,000</option>
                            <option value="25000">Under ₹25,000</option>
                        </select>
                    </div>
                    <div class="w-full md:w-48">
                        <label class="block text-sm font-semibold text-slate-900 dark:text-slate-200 mb-2">Property Type</label>
                        <select id="type-filter" class="input-field" onchange="renderFilteredListings()">
                            <option value="all">Any type</option>
                            <option value="pg">PG</option>
                            <option value="flat">Flat</option>
                            <option value="room">Room</option>
                            <option value="hostel">Hostel</option>
                        </select>
                    </div>
                    <button class="w-full md:w-auto bg-brand-primary text-white px-6 py-3 rounded-xl font-bold hover:bg-brand-hover shadow-md transition-all" onclick="renderFilteredListings()">
                        Apply Filter
                    </button>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6" id="explore-listings-container">
                    <!-- Real listings injected by JS dynamically here -->
                </div>
            </section>

            <!-- Matcher Page -->
            <section id="matcher" class="page-section">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8">
                    <div>
                        <h1 class="text-3xl font-extrabold mb-2">Smart Property Match</h1>
                        <p class="text-slate-500 dark:text-slate-400">Analyze a property share against your income.</p>
                    </div>
                    <button class="bg-white dark:bg-slate-800 border-2 border-brand-primary dark:border-indigo-500 text-brand-primary dark:text-indigo-400 px-5 py-2.5 rounded-xl font-bold hover:bg-brand-light dark:hover:bg-slate-700 transition-colors" onclick="openPage('calculator')">
                        Open Full Budget
                    </button>
                </div>

                <div class="grid lg:grid-cols-2 gap-8">
                    <div class="glass-panel rounded-3xl overflow-hidden flex flex-col">
                        <div class="h-64 relative">
                            <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=800&q=80" alt="Apartment" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 to-transparent"></div>
                            <div class="absolute bottom-4 left-6 text-white">
                                <span class="bg-brand-primary text-white text-xs font-bold px-2 py-1 rounded mb-2 inline-block">Selected Property</span>
                                <h2 class="text-2xl font-bold">Analysis Target Property</h2>
                            </div>
                            <div class="absolute top-4 right-4 bg-white/90 dark:bg-slate-900/90 backdrop-blur text-slate-900 dark:text-white px-3 py-1.5 rounded-lg font-extrabold shadow-sm" id="matcher-base-rent" data-value="15000">
                                ₹15,000 <span class="text-xs font-normal text-slate-500 dark:text-slate-400">/mo total</span>
                            </div>
                        </div>
                        <div class="p-6 flex-1 bg-white dark:bg-slate-800">
                            <div class="flex justify-between items-center mb-6">
                                <div>
                                    <h3 class="font-bold text-lg dark:text-white">Current Setup</h3>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Calculating based on total rent</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="glass-panel rounded-3xl p-6 md:p-8 flex flex-col justify-center">
                        <h3 class="font-bold text-xl mb-6">Analyze Your Fit</h3>
                        
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-slate-900 dark:text-slate-200 mb-3">Room Target Type</label>
                            <div class="bg-slate-50 dark:bg-slate-800 p-1 rounded-xl border border-slate-200 dark:border-slate-700 flex">
                                <button id="btn-standard" class="flex-1 py-2 text-center rounded-lg font-semibold bg-white dark:bg-slate-700 text-brand-primary dark:text-white shadow-sm transition-all" onclick="setRoomType('standard')">Standard Share</button>
                                <button id="btn-master" class="flex-1 py-2 text-center rounded-lg font-medium text-slate-500 dark:text-slate-400 transition-all" onclick="setRoomType('master')">Master/Private</button>
                            </div>
                        </div>

                        <div class="mb-6">
                            <div class="flex justify-between text-sm font-semibold mb-3">
                                <span>Est. total flat utilities</span>
                                <span id="utility-val" class="text-brand-primary dark:text-indigo-400">₹2,000</span>
                            </div>
                            <input type="range" id="utility-slider" min="0" max="10000" step="500" value="2000" oninput="updateUtility(this.value)">
                            <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400 mt-2">
                                <span>₹0</span><span>₹10k</span>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-slate-900 dark:text-slate-200 mb-2">Your Monthly Income (₹)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">₹</span>
                                <input type="number" id="user-income" class="input-field pl-8 font-semibold text-lg" placeholder="e.g. 45000" min="0">
                            </div>
                        </div>

                        <button class="w-full bg-brand-primary text-white py-3.5 rounded-xl font-bold shadow-lg shadow-brand-primary/30 hover:bg-brand-hover transition-all" onclick="analyzeMatch()">
                            Calculate My Share
                        </button>

                        <div id="match-result-box" class="mt-8 bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 hidden">
                            <div class="flex justify-between items-end mb-4">
                                <div>
                                    <p class="text-sm font-bold text-slate-500 dark:text-slate-400">Your Est. Share</p>
                                    <h2 id="share-amount" class="text-3xl font-extrabold text-slate-900 dark:text-white">₹0</h2>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-slate-500 dark:text-slate-400">Income Used</p>
                                    <h2 id="percentage-used" class="text-2xl font-bold text-slate-900 dark:text-white">0%</h2>
                                </div>
                            </div>
                            
                            <div class="w-full h-2.5 bg-slate-200 dark:bg-slate-700 rounded-full mb-3 overflow-hidden">
                                <div id="meter-bar" class="h-full rounded-full transition-all duration-500 w-0"></div>
                            </div>
                            
                            <p id="breakdown-info" class="text-xs text-slate-500 dark:text-slate-400 text-center mb-4"></p>
                            <div id="status-text" class="text-sm font-bold p-3 rounded-lg text-center"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Calculator Page -->
            <section id="calculator" class="page-section">
                <div class="mb-8 max-w-3xl">
                    <h1 class="text-3xl font-extrabold mb-2">Split & Budget Calculator</h1>
                    <p class="text-slate-500 dark:text-slate-400">Figure out fair roommate splits and verify affordability.</p>
                </div>

                <div class="grid lg:grid-cols-5 gap-8 items-start">
                    <div class="lg:col-span-3 glass-panel rounded-3xl p-6 md:p-8">
                        <form onsubmit="calculateSplit(event)">
                            <div class="mb-8">
                                <h2 class="text-lg font-bold border-b border-slate-200 dark:border-slate-700 pb-2 mb-4 dark:text-white">1. Housing Costs</h2>
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold mb-2 dark:text-slate-200">Total Rent (₹)</label>
                                        <input type="number" id="calc-rent" class="input-field" placeholder="e.g. 20000" required min="0">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-2 dark:text-slate-200">Total Utilities (₹)</label>
                                        <input type="number" id="calc-util" class="input-field" placeholder="e.g. 2000" value="0" min="0">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-2 dark:text-slate-200">Total Roommates</label>
                                        <input type="number" id="calc-roommates" class="input-field" value="2" min="1" required oninput="renderCustomSplitFields()">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-2 dark:text-slate-200">Split Strategy</label>
                                        <select id="calc-split-mode" class="input-field" onchange="renderCustomSplitFields()">
                                            <option value="equal">Equal Split</option>
                                            <option value="custom">Custom (By Room)</option>
                                        </select>
                                    </div>
                                </div>
                                <div id="calc-custom-split" class="grid sm:grid-cols-2 gap-4 mt-4 hidden"></div>
                            </div>

                            <div class="mb-8">
                                <h2 class="text-lg font-bold border-b border-slate-200 dark:border-slate-700 pb-2 mb-4 dark:text-white">2. Your Financials</h2>
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold mb-2 dark:text-slate-200">Monthly Income (₹)</label>
                                        <input type="number" id="calc-income" class="input-field" placeholder="e.g. 50000" min="0">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-2 dark:text-slate-200">Max Rent Target (%)</label>
                                        <input type="number" id="calc-percent" class="input-field" placeholder="Usually 30%" value="30" min="1" max="100">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="w-full bg-brand-primary text-white py-3.5 rounded-xl font-bold hover:bg-brand-hover transition-colors">
                                Generate Budget Report
                            </button>
                        </form>
                    </div>

                    <div class="lg:col-span-2">
                        <div id="calc-results" class="sticky top-28 hidden"></div>
                        <div id="calc-empty" class="glass-panel rounded-3xl p-8 text-center flex flex-col items-center justify-center h-full min-h-[400px]">
                            <div class="w-16 h-16 bg-brand-light dark:bg-slate-800 rounded-full flex items-center justify-center text-brand-primary dark:text-indigo-400 mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <h3 class="font-bold text-lg mb-2 dark:text-white">Awaiting Data</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Fill out the form and generate your report to see a detailed breakdown.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Messages Page -->
            <section id="messages" class="page-section h-[calc(100vh-140px)] min-h-[500px]">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl h-full flex overflow-hidden shadow-soft">
                    <div class="w-full md:w-80 border-r border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 flex flex-col items-center justify-center text-slate-500 p-8 text-center">
                        <svg class="w-12 h-12 mb-3 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        <p class="font-semibold text-slate-900 dark:text-slate-200">Message center</p>
                        <p class="text-sm">When you contact a host, your conversations will appear here.</p>
                    </div>
                    <div class="flex-1 hidden md:flex flex-col items-center justify-center bg-slate-50 dark:bg-slate-900">
                         <p class="text-slate-400 font-medium">Select a conversation to start messaging</p>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>

<!-- Mobile Bottom Nav -->
<nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex justify-around p-2 z-40 pb-safe shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
    <button class="flex flex-col items-center p-2 text-slate-500 dark:text-slate-400 hover:text-brand-primary" data-page="home" onclick="openPage('home')">
        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        <span class="text-[10px] font-medium">Home</span>
    </button>
    <button class="flex flex-col items-center p-2 text-slate-500 dark:text-slate-400 hover:text-brand-primary" data-page="explore" onclick="openPage('explore')">
        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        <span class="text-[10px] font-medium">Explore</span>
    </button>
    <button class="flex flex-col items-center p-2 text-slate-500 dark:text-slate-400 hover:text-brand-primary" data-page="matcher" onclick="openPage('matcher')">
        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
        <span class="text-[10px] font-medium">Match</span>
    </button>
    <button class="flex flex-col items-center p-2 text-slate-500 dark:text-slate-400 hover:text-brand-primary" data-page="calculator" onclick="openPage('calculator')">
         <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
        <span class="text-[10px] font-medium">Budget</span>
    </button>
    <button class="flex flex-col items-center p-2 text-slate-500 dark:text-slate-400 hover:text-brand-primary" data-page="messages" onclick="openPage('messages')">
        <div class="relative">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
        </div>
        <span class="text-[10px] font-medium">Chat</span>
    </button>
</nav>
<style> @supports (padding-bottom: env(safe-area-inset-bottom)) { .pb-safe { padding-bottom: env(safe-area-inset-bottom); } } </style>

<script>
    // Embedded core Javascript logic to replace external missing dependencies
    
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const isClosed = sidebar.classList.contains('-translate-x-full');
        if (isClosed) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    }

    function toggleTheme() {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('color-theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('color-theme', 'dark');
        }
        updateThemeIcons();
    }

    function updateThemeIcons() {
        const isDark = document.documentElement.classList.contains('dark');
        document.getElementById('theme-toggle-dark-icon').classList.toggle('hidden', !isDark);
        document.getElementById('theme-toggle-light-icon').classList.toggle('hidden', isDark);
    }

    // Initialize Theme
    updateThemeIcons();

    function openPage(pageId) {
        document.querySelectorAll('.page-section').forEach(el => el.classList.remove('active'));
        document.getElementById(pageId).classList.add('active');
        
        // Update Nav buttons
        document.querySelectorAll('.nav-btn, nav.lg\\:hidden button').forEach(el => {
            if(el.dataset.page) el.classList.remove('active', 'text-brand-primary');
        });
        
        document.querySelectorAll(`[data-page="${pageId}"]`).forEach(el => {
            el.classList.add('active');
            if(el.tagName.toLowerCase() === 'button' && el.parentElement.tagName.toLowerCase() === 'nav' && el.parentElement.classList.contains('lg:hidden')) {
                 el.classList.add('text-brand-primary');
            }
        });

        if (window.innerWidth < 1024) toggleSidebar();
        window.scrollTo(0, 0);

        if(pageId === 'explore') renderFilteredListings();
    }

    function populateMatcherAndOpen(rent) {
        const el = document.getElementById('matcher-base-rent');
        if (el) {
            el.dataset.value = rent;
            el.innerHTML = `₹${parseInt(rent).toLocaleString()} <span class="text-xs font-normal text-slate-500 dark:text-slate-400">/mo total</span>`;
        }
        openPage('matcher');
    }

    function logout() {
        // Mock logout logic. Replace with form submit or location href to logout.php
        window.location.href = 'login.php'; 
    }

    // Explore Listing Logic using PHP injected REAL DATABASE properties
    function renderFilteredListings() {
        const container = document.getElementById('explore-listings-container');
        if(!container) return;

        const searchQuery = document.getElementById('listing-search').value.toLowerCase();
        const budgetFilter = document.getElementById('budget-filter').value;
        const typeFilter = document.getElementById('type-filter').value;

        container.innerHTML = '';
        
        const properties = window.realProperties || [];
        
        const filtered = properties.filter(prop => {
            const matchesSearch = prop.property_name.toLowerCase().includes(searchQuery) || 
                                  prop.city.toLowerCase().includes(searchQuery) || 
                                  prop.address.toLowerCase().includes(searchQuery);
            const matchesBudget = budgetFilter === 'all' || parseInt(prop.rent) <= parseInt(budgetFilter);
            const matchesType = typeFilter === 'all' || prop.property_type.toLowerCase() === typeFilter.toLowerCase();
            
            return matchesSearch && matchesBudget && matchesType;
        });

        if(filtered.length === 0) {
            container.innerHTML = `
                <div class="col-span-full bg-slate-100 text-slate-500 p-8 rounded-2xl text-center border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                    <p class="font-bold text-lg">No properties found.</p>
                    <p class="text-sm mt-1">Try adjusting your filters.</p>
                </div>`;
            return;
        }

        filtered.forEach(prop => {
            const imgPath = prop.main_image ? prop.main_image : 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=800&q=80';
            
            const html = `
            <div class="glass-panel rounded-2xl overflow-hidden flex flex-col group hover:-translate-y-1 transition-all">
                <div class="h-48 relative overflow-hidden bg-slate-200 dark:bg-slate-800">
                    <img src="${imgPath}" alt="${prop.property_name}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                    <div class="absolute top-3 left-3 bg-white/90 dark:bg-slate-900/90 backdrop-blur px-2 py-1 rounded text-xs font-bold text-slate-900 shadow-sm uppercase">
                        ${prop.property_type}
                    </div>
                    <div class="absolute bottom-3 left-4 right-4 flex justify-between items-end">
                        <p class="text-white text-xs font-medium flex items-center gap-1 opacity-90">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                            ${prop.city}
                        </p>
                        <div class="bg-brand-primary text-white px-3 py-1.5 rounded-lg font-bold shadow-md">
                            ₹${parseInt(prop.rent).toLocaleString()}<span class="text-[10px] font-normal opacity-80">/mo</span>
                        </div>
                    </div>
                </div>
                <div class="p-5 flex-1 flex flex-col bg-white dark:bg-slate-800">
                    <h3 class="font-bold text-lg leading-tight mb-2 text-slate-900 dark:text-white line-clamp-1">${prop.property_name}</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 line-clamp-2">${prop.address}</p>
                    <div class="mt-auto flex items-center justify-between border-t border-slate-100 dark:border-slate-700 pt-4">
                        <span class="text-xs font-semibold px-2 py-1 bg-brand-light dark:bg-indigo-900/30 text-brand-primary dark:text-indigo-400 rounded">
                            ${prop.available_rooms} Room(s) Left
                        </span>
                        <button class="text-sm font-bold text-brand-primary dark:text-indigo-400 hover:text-brand-hover" onclick="populateMatcherAndOpen(${prop.rent})">
                            Analyze Match →
                        </button>
                    </div>
                </div>
            </div>`;
            container.insertAdjacentHTML('beforeend', html);
        });
    }

    // Matcher logic
    let currentRoomType = 'standard';
    function setRoomType(type) {
        currentRoomType = type;
        const btnS = document.getElementById('btn-standard');
        const btnM = document.getElementById('btn-master');
        
        btnS.className = type === 'standard' 
            ? "flex-1 py-2 text-center rounded-lg font-semibold bg-white dark:bg-slate-700 text-brand-primary dark:text-white shadow-sm transition-all" 
            : "flex-1 py-2 text-center rounded-lg font-medium text-slate-500 dark:text-slate-400 transition-all";
            
        btnM.className = type === 'master' 
            ? "flex-1 py-2 text-center rounded-lg font-semibold bg-white dark:bg-slate-700 text-brand-primary dark:text-white shadow-sm transition-all" 
            : "flex-1 py-2 text-center rounded-lg font-medium text-slate-500 dark:text-slate-400 transition-all";
    }

    function updateUtility(val) {
        document.getElementById('utility-val').innerText = `₹${parseInt(val).toLocaleString()}`;
    }

    function analyzeMatch() {
        const baseRentRaw = document.getElementById('matcher-base-rent').dataset.value;
        const baseRent = parseInt(baseRentRaw) || 0;
        const utility = parseInt(document.getElementById('utility-slider').value) || 0;
        const income = parseInt(document.getElementById('user-income').value);

        if (!income || income <= 0) {
            alert('Please enter a valid monthly income.');
            return;
        }

        // Simple logic for standard vs master room cost split
        let userRentShare = currentRoomType === 'master' ? (baseRent * 0.6) : (baseRent * 0.4); 
        let userUtilityShare = utility / 2; // Split 50/50 for utility
        
        let totalEst = Math.round(userRentShare + userUtilityShare);
        let percentage = ((totalEst / income) * 100).toFixed(1);
        
        document.getElementById('share-amount').innerText = `₹${totalEst.toLocaleString()}`;
        document.getElementById('percentage-used').innerText = `${percentage}%`;
        
        const meter = document.getElementById('meter-bar');
        const status = document.getElementById('status-text');
        
        let pVal = Math.min(percentage, 100);
        meter.style.width = `${pVal}%`;
        
        if (percentage <= 30) {
            meter.className = "h-full rounded-full transition-all duration-500 bg-green-500";
            status.className = "text-sm font-bold p-3 rounded-lg text-center bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400";
            status.innerText = "Great match! Highly affordable based on standard 30% rules.";
        } else if (percentage <= 45) {
            meter.className = "h-full rounded-full transition-all duration-500 bg-yellow-500";
            status.className = "text-sm font-bold p-3 rounded-lg text-center bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400";
            status.innerText = "Stretch budget. Manageable but might require tight personal budgeting.";
        } else {
            meter.className = "h-full rounded-full transition-all duration-500 bg-red-500";
            status.className = "text-sm font-bold p-3 rounded-lg text-center bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400";
            status.innerText = "High financial strain. This exceeds safe rental allocations.";
        }
        
        document.getElementById('breakdown-info').innerText = `Rent: ₹${Math.round(userRentShare).toLocaleString()} | Util: ₹${Math.round(userUtilityShare).toLocaleString()}`;
        document.getElementById('match-result-box').classList.remove('hidden');
    }

    // Split calculator logic
    function renderCustomSplitFields() {
        const mode = document.getElementById('calc-split-mode').value;
        const count = parseInt(document.getElementById('calc-roommates').value) || 2;
        const customDiv = document.getElementById('calc-custom-split');
        
        if (mode === 'custom' && count > 0) {
            let html = '';
            for(let i=1; i<=count; i++) {
                html += `<div><label class="block text-xs font-semibold mb-1 dark:text-slate-300">Roommate ${i} %</label><input type="number" class="input-field py-2 text-sm custom-split-input" placeholder="%" value="${Math.floor(100/count)}" min="0" max="100"></div>`;
            }
            customDiv.innerHTML = html;
            customDiv.classList.remove('hidden');
        } else {
            customDiv.classList.add('hidden');
        }
    }

    function calculateSplit(e) {
        e.preventDefault();
        const rent = parseFloat(document.getElementById('calc-rent').value) || 0;
        const util = parseFloat(document.getElementById('calc-util').value) || 0;
        const mode = document.getElementById('calc-split-mode').value;
        const count = parseInt(document.getElementById('calc-roommates').value) || 2;
        const income = parseFloat(document.getElementById('calc-income').value) || 0;
        
        let shareRent = 0;
        
        if (mode === 'equal') {
            shareRent = rent / count;
        } else {
            // Take the first custom input as "Your" share
            const inputs = document.querySelectorAll('.custom-split-input');
            let myPercent = parseFloat(inputs[0]?.value) || (100/count);
            shareRent = (rent * myPercent) / 100;
        }
        
        let shareUtil = util / count;
        let totalShare = shareRent + shareUtil;
        
        let html = `
        <div class="glass-panel rounded-3xl p-8 mb-6">
            <h3 class="font-bold text-xl mb-4 border-b border-slate-200 dark:border-slate-700 pb-2 dark:text-white">Your Monthly Split</h3>
            <div class="flex justify-between items-end mb-6">
                <div>
                    <p class="text-slate-500 dark:text-slate-400 text-sm font-semibold">Total Share</p>
                    <h2 class="text-4xl font-extrabold text-brand-primary dark:text-indigo-400">₹${Math.round(totalShare).toLocaleString()}</h2>
                </div>
            </div>
            <ul class="space-y-2 text-sm">
                <li class="flex justify-between text-slate-700 dark:text-slate-300"><span>Rent Share:</span> <strong class="dark:text-white">₹${Math.round(shareRent).toLocaleString()}</strong></li>
                <li class="flex justify-between text-slate-700 dark:text-slate-300"><span>Utility Share:</span> <strong class="dark:text-white">₹${Math.round(shareUtil).toLocaleString()}</strong></li>
            </ul>
        </div>`;

        if (income > 0) {
            const percent = ((totalShare / income) * 100).toFixed(1);
            const targetStr = document.getElementById('calc-percent').value || 30;
            const target = parseFloat(targetStr);
            const isHealthy = percent <= target;
            
            html += `
            <div class="glass-panel rounded-3xl p-6 ${isHealthy ? 'border-l-4 border-green-500' : 'border-l-4 border-red-500'}">
                <h4 class="font-bold mb-2 dark:text-white">Financial Health Check</h4>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">Your share takes up <strong class="${isHealthy ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">${percent}%</strong> of your income.</p>
                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                    <div class="h-2 rounded-full ${isHealthy ? 'bg-green-500' : 'bg-red-500'}" style="width: ${Math.min(percent, 100)}%"></div>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Target was set to ≤${target}%</p>
            </div>`;
        }

        document.getElementById('calc-empty').classList.add('hidden');
        document.getElementById('calc-results').innerHTML = html;
        document.getElementById('calc-results').classList.remove('hidden');
    }

</script>
</body>
</html>
