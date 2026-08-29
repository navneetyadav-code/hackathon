<?php
session_start();
require_once __DIR__ . '/config/app.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'host') {
    header('Location: ' . (($_SESSION['role'] ?? '') === 'seeker' ? 'dashboard_seek.php' : 'login.php'));
    exit;
}

$redirect = thikana_host_dashboard_redirect($_SESSION['user_id']);
if ($redirect !== 'dashboard_host.php') {
    header('Location: ' . $redirect);
    exit;
}

$pdo = thikana_db();
$propertyTypes = [
    'pg' => 'PG / Hostel',
    'flat' => 'Flat / Apartment',
    'room' => 'Single Room',
    'hostel' => 'Hostel'
];
$propertyStatuses = ['active', 'inactive', 'draft'];

function thikana_money($amount): string
{
    return 'Rs ' . number_format((float) $amount, 0);
}

function thikana_property_status_class(string $status): string
{
    return $status === 'active'
        ? 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20'
        : 'text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800';
}

function thikana_property_icon(string $type): string
{
    return [
        'pg' => 'PG',
        'flat' => 'FL',
        'room' => 'RM',
        'hostel' => 'HS'
    ][$type] ?? 'PR';
}

function thikana_handle_host_image_upload($file, int $userId): ?string
{
    if (!isset($file['name']) || !$file['name']) {
        return null;
    }

    if (!is_array($file) || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }

    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowed, true)) {
        throw new InvalidArgumentException('Only JPG, PNG, and WebP images are allowed.');
    }

    $uploadDir = __DIR__ . '/uploads/host_onboarding/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Unable to create upload directory.');
    }

    $safeFileName = 'host_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $targetPath = $uploadDir . $safeFileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Failed to upload image.');
    }

    return '/uploads/host_onboarding/' . $safeFileName;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!$pdo) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection is not available.']);
        exit;
    }

    $payload = [];
    $uploadedImage = null;

    if (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
        $payload = $_POST;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadedImage = $_FILES['image'];
        }
    } else {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        $payload = is_array($input) ? $input : [];
    }

    $action = $payload['action'] ?? '';

    try {
        if ($action === 'save_property' || $action === 'update_property') {
            $propertyName = trim((string) ($payload['property_name'] ?? ''));
            $propertyType = trim((string) ($payload['property_type'] ?? ''));
            $address = trim((string) ($payload['address'] ?? ''));
            $city = trim((string) ($payload['city'] ?? ''));
            $totalRooms = (int) ($payload['total_rooms'] ?? 0);
            $availableRooms = (int) ($payload['available_rooms'] ?? 0);
            $rent = (float) ($payload['rent'] ?? 0);
            $deposit = (float) ($payload['deposit'] ?? 0);
            $status = trim((string) ($payload['status'] ?? 'active'));
            
            // Handle array structures for JSON insertion
            $amenitiesInput = trim((string) ($payload['amenities'] ?? ''));
            $amenitiesArr = $amenitiesInput ? array_map('trim', explode(',', $amenitiesInput)) : [];
            $amenitiesJson = !empty($amenitiesArr) ? json_encode($amenitiesArr) : null;

            $rulesInput = trim((string) ($payload['house_rules'] ?? ''));
            $rulesArr = $rulesInput ? array_map('trim', explode(',', $rulesInput)) : [];
            $rulesJson = !empty($rulesArr) ? json_encode($rulesArr) : null;
            
            $imagePath = null;

            if ($uploadedImage) {
                $imagePath = thikana_handle_host_image_upload($uploadedImage, (int) $_SESSION['user_id']);
            } elseif (!empty($payload['image_path'])) {
                $imagePath = trim((string) $payload['image_path']);
            }

            if ($propertyName === '' || $address === '' || $city === '' || !array_key_exists($propertyType, $propertyTypes)) {
                echo json_encode(['status' => 'error', 'message' => 'Please complete all required property details.']);
                exit;
            }

            if ($totalRooms < 1 || $availableRooms < 0 || $availableRooms > $totalRooms || $rent < 0 || $deposit < 0 || !in_array($status, $propertyStatuses, true)) {
                echo json_encode(['status' => 'error', 'message' => 'Please enter valid numbers and configurations.']);
                exit;
            }

            if ($action === 'save_property') {
                $stmt = $pdo->prepare(
                    'INSERT INTO properties (user_id, property_count, property_type, property_name, address, city, total_rooms, available_rooms, rent, deposit, amenities, house_rules, status, image_path, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
                );
                $stmt->execute([$_SESSION['user_id'], 'single', $propertyType, $propertyName, $address, $city, $totalRooms, $availableRooms, $rent, $deposit, $amenitiesJson, $rulesJson, $status, $imagePath]);

                try {
                    $checkHostOnboarding = $pdo->prepare('SELECT 1 FROM host_onboarding WHERE user_id = ? LIMIT 1');
                    $checkHostOnboarding->execute([$_SESSION['user_id']]);
                    if ($checkHostOnboarding->fetchColumn()) {
                        $stmtHost = $pdo->prepare('UPDATE host_onboarding SET image_path = ? WHERE user_id = ?');
                        $stmtHost->execute([$imagePath, $_SESSION['user_id']]);
                    } else {
                        $stmtHost = $pdo->prepare('INSERT INTO host_onboarding (user_id, image_path, created_at) VALUES (?, ?, NOW())');
                        $stmtHost->execute([$_SESSION['user_id'], $imagePath]);
                    }
                } catch (Throwable $e) {
                    error_log('Host onboarding image sync failed: ' . $e->getMessage());
                }

                echo json_encode(['status' => 'success', 'message' => 'Property added successfully!']);
                exit;
            }

            $propertyId = (int) ($payload['property_id'] ?? 0);
            if ($propertyId < 1) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid property selected.']);
                exit;
            }

            if ($imagePath !== null) {
                $stmt = $pdo->prepare(
                    'UPDATE properties
                     SET property_type = ?, property_name = ?, address = ?, city = ?, total_rooms = ?, available_rooms = ?, rent = ?, deposit = ?, amenities = ?, house_rules = ?, status = ?, image_path = ?
                     WHERE id = ? AND user_id = ?'
                );
                $stmt->execute([$propertyType, $propertyName, $address, $city, $totalRooms, $availableRooms, $rent, $deposit, $amenitiesJson, $rulesJson, $status, $imagePath, $propertyId, $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE properties
                     SET property_type = ?, property_name = ?, address = ?, city = ?, total_rooms = ?, available_rooms = ?, rent = ?, deposit = ?, amenities = ?, house_rules = ?, status = ?
                     WHERE id = ? AND user_id = ?'
                );
                $stmt->execute([$propertyType, $propertyName, $address, $city, $totalRooms, $availableRooms, $rent, $deposit, $amenitiesJson, $rulesJson, $status, $propertyId, $_SESSION['user_id']]);
            }

            echo json_encode([
                'status' => $stmt->rowCount() >= 0 ? 'success' : 'error',
                'message' => 'Property updated successfully!'
            ]);
            exit;
        }

        echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    } catch (Throwable $e) {
        error_log('Host dashboard property save failed: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

$properties = [];
$seekersList = [];

if ($pdo) {
    // 1. Fetch Real Properties
    $stmt = $pdo->prepare('SELECT * FROM properties WHERE user_id = ? ORDER BY created_at DESC, id DESC');
    $stmt->execute([$_SESSION['user_id']]);
    $properties = $stmt->fetchAll();

    $hostOnboardingImage = null;
    try {
        $hostImageStmt = $pdo->prepare('SELECT image_path FROM host_onboarding WHERE user_id = ? LIMIT 1');
        $hostImageStmt->execute([$_SESSION['user_id']]);
        $hostOnboardingImage = $hostImageStmt->fetchColumn();
    } catch (Throwable $e) {}

    foreach ($properties as &$property) {
        if (empty($property['image_path']) && !empty($hostOnboardingImage)) {
            $property['image_path'] = $hostOnboardingImage;
        }
    }
    unset($property);

    // 2. Fetch Real Seekers Dynamically 
    try {
        $seekerStmt = $pdo->query("SELECT id, first_name, last_name, email, phone, created_at FROM users WHERE role = 'seeker' ORDER BY created_at DESC LIMIT 20");
        if ($seekerStmt) {
            $seekersList = $seekerStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {}
}

$featuredProperty = $properties[0] ?? null;
$totalProperties = count($properties);
$totalRooms = array_sum(array_map(fn($property) => (int) $property['total_rooms'], $properties));
$availableRooms = array_sum(array_map(fn($property) => (int) $property['available_rooms'], $properties));
$occupiedRooms = max(0, $totalRooms - $availableRooms);
$monthlyRent = array_sum(array_map(fn($property) => (float) $property['rent'], $properties));

$firstName = $_SESSION['first_name'] ?? 'User';
$fullName = trim(
    ($_SESSION['first_name'] ?? '') . ' ' .
    ($_SESSION['last_name'] ?? '')
);

$displayName = $fullName ?: $firstName;
$initial = strtoupper(substr($firstName, 0, 1));
?>
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
            .input-field { @apply w-full px-4 py-2 mt-1 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-brand-primary dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:focus:ring-brand-primary; }
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
        .modal-overlay { transition: opacity 0.3s ease; }
        .modal-content { transition: transform 0.3s ease, opacity 0.3s ease; transform: scale(0.95); opacity: 0; }
        .modal-active .modal-content { transform: scale(1); opacity: 1; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 transition-colors duration-200 dark:bg-slate-950 dark:text-slate-100">

<div id="toast-container" class="fixed top-4 left-1/2 -translate-x-1/2 z-[200] flex flex-col gap-2 pointer-events-none"></div>

<div class="flex min-h-screen">
    <aside id="sidebar" class="sidebar w-[280px] h-screen fixed left-0 top-0 z-40 transition-transform duration-300 bg-white border-r border-slate-200 flex flex-col shadow-soft dark:bg-slate-900 dark:border-slate-800 lg:translate-x-0 -translate-x-full">
        <div class="h-20 flex items-center px-6 border-b border-slate-200 dark:border-slate-800">
            <a href="#" class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-white group">
                <svg class="w-8 h-8 text-brand-primary group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Thikana
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
                <div class="w-10 h-10 rounded-full bg-brand-light dark:bg-indigo-900/50 text-brand-primary dark:text-indigo-300 flex items-center justify-center font-bold text-lg border border-brand-primary/20"><?= htmlspecialchars($initial) ?></div>
                <div class="overflow-hidden">
                    <p class="font-bold text-slate-900 dark:text-white text-sm truncate"> <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></p>
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
                    <span>Dashboard View</span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button id="theme-toggle" class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition-transform hover:scale-105">
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                </button>
                <button class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm relative transition-transform hover:scale-105" onclick="showToast('Your recent activity is up to date!')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                </button>
            </div>
        </header>

        <div class="flex-1 p-6 md:p-8 max-w-7xl mx-auto w-full">
            <section id="dashboard" class="page-section active">
                <div class="bg-gradient-to-br from-slate-900 to-indigo-600 dark:from-slate-900 dark:to-brand-primary rounded-3xl p-8 md:p-10 mb-8 shadow-floating text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-8 relative overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="relative z-10">
                        <h1 class="text-3xl md:text-5xl font-extrabold mb-2 leading-tight text-white drop-shadow-sm">Hey <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>👋</h1>
                        <p class="text-indigo-100 text-lg">Here's what's happening with your property portfolio.</p>
                    </div>
                    <button class="relative z-10 px-6 py-3 bg-white text-indigo-600 font-bold rounded-xl hover:bg-slate-50 transition-all shadow-lg hover:shadow-xl flex items-center gap-2" onclick="openModal('add-property-modal')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add Property
                    </button>
                </div>

                <div class="card mb-8 hover:border-brand-primary/50 transition-colors">
                    <?php if ($featuredProperty): ?>
                    <div class="flex flex-col md:flex-row gap-6 items-start md:items-center">
                        <?php if (!empty($featuredProperty['image_path'])): ?>
                            <img src="<?= htmlspecialchars($featuredProperty['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Property Image" class="w-20 h-20 rounded-2xl object-cover border border-slate-200 dark:border-slate-700">
                        <?php else: ?>
                            <div class="w-20 h-20 rounded-2xl bg-brand-light dark:bg-indigo-900/40 text-brand-primary flex items-center justify-center text-xl font-extrabold"><?= htmlspecialchars(thikana_property_icon($featuredProperty['property_type']), ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                        
                        <div class="flex-1 cursor-pointer" onclick='openEditProperty(<?= json_encode($featuredProperty, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                            <div class="flex flex-wrap items-center gap-3 mb-2">
                                <span class="text-xs font-bold text-brand-primary uppercase tracking-wider px-2 py-1 bg-brand-light dark:bg-indigo-900/30 rounded-md"><?= htmlspecialchars($propertyTypes[$featuredProperty['property_type']] ?? 'Property', ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="flex items-center gap-1 text-xs font-medium <?= thikana_property_status_class((string) $featuredProperty['status']) ?> px-2 py-1 rounded-md">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span> <?= htmlspecialchars(ucfirst((string) $featuredProperty['status']), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-1"><?= htmlspecialchars($featuredProperty['property_name'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <?= htmlspecialchars($featuredProperty['address'] . ', ' . $featuredProperty['city'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                        <button class="btn-secondary w-full md:w-auto whitespace-nowrap flex items-center justify-center gap-2" onclick='openPage("properties", document.querySelector("[data-page=\"properties\"]")); openEditProperty(<?= json_encode($featuredProperty, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                            Manage Property
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="flex flex-col md:flex-row gap-6 items-start md:items-center">
                        <div class="w-16 h-16 rounded-2xl bg-brand-light dark:bg-indigo-900/40 text-brand-primary flex items-center justify-center text-3xl">+</div>
                        <div class="flex-1">
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-1">No properties yet</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Add your first room, PG, flat, or hostel to start managing your authentic listings.</p>
                        </div>
                        <button class="btn-primary w-full md:w-auto" onclick="openModal('add-property-modal')">Add Property</button>
                    </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl">
                            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $totalRooms ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Total Rooms</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl">
                            <p class="text-2xl font-bold text-brand-primary"><?= $availableRooms ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Available</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl">
                            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $occupiedRooms ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Occupied</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400"><?= htmlspecialchars(thikana_money($monthlyRent), ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Listed Rent / Month</p>
                        </div>
                    </div>
                </div>

                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    <button class="card flex items-center gap-4 hover:border-brand-primary/50 hover:shadow-md transition-all text-left" onclick="openModal('add-property-modal')">
                        <div class="w-12 h-12 rounded-full bg-brand-light dark:bg-indigo-900/40 text-brand-primary flex items-center justify-center text-xl">➕</div>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-sm">Add Property</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">List a new room, PG or flat.</p>
                        </div>
                    </button>
                    <button class="card flex items-center gap-4 hover:border-brand-primary/50 hover:shadow-md transition-all text-left" onclick="openPage('properties', document.querySelector('[data-page=properties]'))">
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
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Recent Seeker Profiles</h2>
                    <button class="text-sm font-medium text-brand-primary hover:text-brand-hover" onclick="openPage('seekers', document.querySelector('[data-page=seekers]'))">View all →</button>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
                    <?php if (!empty($seekersList)): ?>
                        <?php foreach(array_slice($seekersList, 0, 4) as $seekerInfo): ?>
                            <?php 
                            $sInit = strtoupper(substr($seekerInfo['first_name'], 0, 1)); 
                            $sName = trim($seekerInfo['first_name'] . ' ' . $seekerInfo['last_name']);
                            ?>
                            <div class="card flex flex-col sm:flex-row gap-4 items-start sm:items-center hover:border-brand-primary/50 transition-colors">
                                <div class="w-12 h-12 rounded-full bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 flex items-center justify-center font-bold text-lg shrink-0"><?= htmlspecialchars($sInit) ?></div>
                                <div class="flex-1">
                                    <h3 class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($sName) ?></h3>
                                    <p class="text-sm font-medium text-brand-primary">Looking for Accommodation</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Joined: <?= date('M d, Y', strtotime($seekerInfo['created_at'])) ?></p>
                                </div>
                                <div class="flex gap-2 w-full sm:w-auto mt-2 sm:mt-0">
                                    <button class="flex-1 sm:flex-none px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-sm font-semibold transition-colors" onclick='openSeekerModal(<?= json_encode($seekerInfo, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>View</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-1 lg:col-span-2 card text-center py-8 text-slate-500 dark:text-slate-400">
                            No active seekers found in the database recently.
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section id="properties" class="page-section">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white">My Properties</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?= $totalProperties ?> listed <?= $totalProperties === 1 ? 'property' : 'properties' ?> populated directly from your database.</p>
                    </div>
                    <button class="btn-primary" onclick="openModal('add-property-modal')">Add Property</button>
                </div>

                <?php if ($properties): ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <?php foreach ($properties as $property): ?>
                    <?php $propertyPayload = json_encode($property, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
                    <article class="card hover:border-brand-primary/50 transition-colors flex flex-col justify-between">
                        <div>
                            <div class="flex items-start gap-4 mb-4">
                                <?php if (!empty($property['image_path'])): ?>
                                    <img src="<?= htmlspecialchars($property['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Property Image" class="w-16 h-16 rounded-xl object-cover shrink-0 border border-slate-200 dark:border-slate-700">
                                <?php else: ?>
                                    <div class="w-16 h-16 rounded-xl bg-brand-light dark:bg-indigo-900/40 text-brand-primary flex items-center justify-center text-xl font-extrabold shrink-0"><?= htmlspecialchars(thikana_property_icon($property['property_type']), ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                                
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <span class="text-xs font-bold text-brand-primary uppercase tracking-wider px-2 py-1 bg-brand-light dark:bg-indigo-900/30 rounded-md"><?= htmlspecialchars($propertyTypes[$property['property_type']] ?? 'Property', ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="text-xs font-semibold <?= thikana_property_status_class((string) $property['status']) ?> px-2 py-1 rounded-md"><?= htmlspecialchars(ucfirst((string) $property['status']), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <h3 class="text-xl font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($property['property_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 line-clamp-2"><?= htmlspecialchars($property['address'] . ', ' . $property['city'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>

                            <?php 
                            $amenitiesArr = json_decode($property['amenities'] ?? '[]', true) ?: []; 
                            if (!empty($amenitiesArr)): ?>
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <?php foreach(array_slice($amenitiesArr, 0, 4) as $am): ?>
                                        <span class="text-[11px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-medium px-2.5 py-1 rounded-full border border-slate-200 dark:border-slate-700"><?= htmlspecialchars($am) ?></span>
                                    <?php endforeach; ?>
                                    <?php if(count($amenitiesArr) > 4): ?>
                                        <span class="text-[11px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-medium px-2.5 py-1 rounded-full border border-slate-200 dark:border-slate-700">+<?= count($amenitiesArr) - 4 ?> more</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <div class="grid grid-cols-3 gap-3 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-3 text-center">
                                    <p class="text-lg font-bold text-slate-900 dark:text-white"><?= (int) $property['total_rooms'] ?></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Total Rooms</p>
                                </div>
                                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-3 text-center">
                                    <p class="text-lg font-bold text-brand-primary"><?= (int) $property['available_rooms'] ?></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Available</p>
                                </div>
                                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-3 text-center">
                                    <p class="text-lg font-bold text-green-600 dark:text-green-400"><?= htmlspecialchars(thikana_money($property['rent']), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Mo. Rent</p>
                                </div>
                            </div>
                            <button class="mt-4 w-full btn-secondary" onclick='openEditProperty(<?= $propertyPayload ?>)'>Edit Details & Configuration</button>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="card flex flex-col items-center justify-center py-16 text-center">
                    <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center text-3xl mb-4">+</div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No properties found</h2>
                    <p class="text-slate-500 dark:text-slate-400 mb-6 max-w-sm">Add your first real property listing and it will appear here immediately from the database.</p>
                    <button class="btn-primary" onclick="openModal('add-property-modal')">Add New Property</button>
                </div>
                <?php endif; ?>
            </section>

            <section id="seekers" class="page-section">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white">Active Seekers List</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Review real profiles of people looking for a place from your user base.</p>
                    </div>
                </div>

                <?php if (!empty($seekersList)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach($seekersList as $seekerInfo): ?>
                            <?php 
                            $sInit = strtoupper(substr($seekerInfo['first_name'], 0, 1)); 
                            $sName = trim($seekerInfo['first_name'] . ' ' . $seekerInfo['last_name']);
                            ?>
                            <div class="card flex flex-col items-center text-center hover:border-brand-primary/50 transition-colors">
                                <div class="w-16 h-16 rounded-full bg-brand-light dark:bg-indigo-900/30 text-brand-primary flex items-center justify-center font-bold text-2xl mb-4"><?= htmlspecialchars($sInit) ?></div>
                                <h3 class="font-bold text-lg text-slate-900 dark:text-white"><?= htmlspecialchars($sName) ?></h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4"><?= htmlspecialchars($seekerInfo['email']) ?></p>
                                <button class="btn-secondary w-full" onclick='openSeekerModal(<?= json_encode($seekerInfo, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>View Profile</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card flex flex-col items-center justify-center py-16 text-center">
                        <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center text-3xl mb-4">👥</div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No Seekers Available</h2>
                        <p class="text-slate-500 dark:text-slate-400 mb-6 max-w-sm">When new seekers register in the system, they will automatically appear here populated from the database.</p>
                    </div>
                <?php endif; ?>
            </section>

            <section id="messages" class="page-section">
                <div class="card flex flex-col items-center justify-center py-16 text-center">
                    <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center text-3xl mb-4">💬</div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Messages</h2>
                    <p class="text-slate-500 dark:text-slate-400 max-w-sm">Message center for chatting with potential roommates and seekers.</p>
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

<!-- Add Property Modal -->
<div id="add-property-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] hidden items-center justify-center modal-overlay">
    <div class="bg-white dark:bg-slate-900 rounded-2xl w-full max-w-2xl shadow-2xl border border-slate-200 dark:border-slate-800 modal-content mx-4 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span>➕</span> Add New Property Entry
            </h3>
            <button onclick="closeModal('add-property-modal')" class="text-slate-400 hover:text-red-500 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">
            <form id="add-property-form" onsubmit="submitProperty(event, 'save_property')" class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Property Name</label>
                        <input type="text" name="property_name" placeholder="e.g. Sunrise PG, Royal Flats" class="input-field" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Property Type</label>
                        <select name="property_type" class="input-field" required>
                            <?php foreach ($propertyTypes as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Publishing Status</label>
                        <select name="status" class="input-field" required>
                            <option value="active">Active (Visible to Seekers)</option>
                            <option value="inactive">Inactive</option>
                            <option value="draft">Draft Mode</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Full Address</label>
                        <textarea name="address" rows="2" placeholder="Enter complete location address" class="input-field" required></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">City</label>
                        <input type="text" name="city" placeholder="e.g. Phagwara, Amritsar" class="input-field" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Total Rooms</label>
                        <input type="number" name="total_rooms" min="1" placeholder="e.g. 10" class="input-field" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Available Rooms</label>
                        <input type="number" name="available_rooms" min="0" placeholder="e.g. 4" class="input-field" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Monthly Rent (Rs)</label>
                        <input type="number" name="rent" min="0" step="0.01" placeholder="e.g. 5000" class="input-field" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Security Deposit (Rs)</label>
                        <input type="number" name="deposit" min="0" step="0.01" placeholder="e.g. 5000" class="input-field">
                    </div>
                    <div class="md:col-span-2 border-t border-slate-200 dark:border-slate-800 pt-4 mt-2">
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-3">Additional Details (Stored as JSON structure)</h4>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Amenities (comma separated)</label>
                        <input type="text" name="amenities" placeholder="e.g. WiFi, AC, Geyser, Laundry, RO Water" class="input-field mb-4">
                        
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">House Rules (comma separated)</label>
                        <input type="text" name="house_rules" placeholder="e.g. No smoking, Curfew 10PM, No pets" class="input-field">
                    </div>
                </div>
                
                <div class="pt-6 flex gap-3">
                    <button type="button" onclick="closeModal('add-property-modal')" class="flex-1 btn-secondary py-3">Cancel</button>
                    <button type="submit" class="flex-1 btn-primary py-3 text-lg">Save Property Listing</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Property Modal -->
<div id="edit-property-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] hidden items-center justify-center modal-overlay">
    <div class="bg-white dark:bg-slate-900 rounded-2xl w-full max-w-2xl shadow-2xl border border-slate-200 dark:border-slate-800 modal-content mx-4 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span>✏️</span> Update Property Details
            </h3>
            <button onclick="closeModal('edit-property-modal')" class="text-slate-400 hover:text-red-500 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">
            <form id="edit-property-form" onsubmit="submitProperty(event, 'update_property')" class="space-y-5">
                <input type="hidden" name="property_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Property Name</label>
                        <input type="text" name="property_name" class="input-field" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Property Type</label>
                        <select name="property_type" class="input-field" required>
                            <?php foreach ($propertyTypes as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Publishing Status</label>
                        <select name="status" class="input-field" required>
                            <option value="active">Active (Visible to Seekers)</option>
                            <option value="inactive">Inactive</option>
                            <option value="draft">Draft Mode</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Full Address</label>
                        <textarea name="address" rows="2" class="input-field" required></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">City</label>
                        <input type="text" name="city" class="input-field" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Total Rooms</label>
                        <input type="number" name="total_rooms" min="1" class="input-field" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Available Rooms</label>
                        <input type="number" name="available_rooms" min="0" class="input-field" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Monthly Rent (Rs)</label>
                        <input type="number" name="rent" min="0" step="0.01" class="input-field" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Security Deposit (Rs)</label>
                        <input type="number" name="deposit" min="0" step="0.01" class="input-field">
                    </div>
                    
                    <div class="md:col-span-2 border-t border-slate-200 dark:border-slate-800 pt-4 mt-2">
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-3">Additional Details (JSON structure)</h4>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Amenities (comma separated)</label>
                        <input type="text" name="amenities" class="input-field mb-4">
                        
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">House Rules (comma separated)</label>
                        <input type="text" name="house_rules" class="input-field">
                    </div>
                </div>
                <div class="pt-6 flex gap-3">
                    <button type="button" onclick="closeModal('edit-property-modal')" class="flex-1 btn-secondary py-3">Cancel</button>
                    <button type="submit" class="flex-1 btn-primary py-3 text-lg">Update All Details</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Seeker Real Data Modal -->
<div id="view-seeker-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] hidden items-center justify-center modal-overlay">
    <div class="bg-white dark:bg-slate-900 rounded-2xl w-full max-w-sm shadow-2xl border border-slate-200 dark:border-slate-800 modal-content mx-4 overflow-hidden">
        <div class="p-6 text-center">
            <div id="modal-seeker-init" class="w-20 h-20 mx-auto rounded-full bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 flex items-center justify-center font-bold text-3xl mb-4"></div>
            <h3 id="modal-seeker-name" class="text-2xl font-bold text-slate-900 dark:text-white"></h3>
            <p class="text-brand-primary font-medium mb-4">Verified Seeker from Database</p>

            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 text-left space-y-2 mb-6 shadow-sm border border-slate-100 dark:border-slate-700">
                <p class="text-sm flex flex-col"><span class="text-slate-500 text-xs uppercase tracking-wide font-bold">Email Address:</span> <span id="modal-seeker-email" class="font-bold dark:text-white truncate"></span></p>
                <div class="h-px bg-slate-200 dark:bg-slate-700 my-1"></div>
                <p class="text-sm flex flex-col"><span class="text-slate-500 text-xs uppercase tracking-wide font-bold">Phone Number:</span> <span id="modal-seeker-phone" class="font-bold dark:text-white"></span></p>
                <div class="h-px bg-slate-200 dark:bg-slate-700 my-1"></div>
                <p class="text-sm flex flex-col"><span class="text-slate-500 text-xs uppercase tracking-wide font-bold">Registration Date:</span> <span id="modal-seeker-date" class="font-bold dark:text-white"></span></p>
            </div>

            <div class="flex gap-3">
                <button onclick="closeModal('view-seeker-modal')" class="flex-1 btn-secondary">Close Window</button>
                <a id="modal-seeker-contact" href="#" class="flex-1 btn-primary bg-indigo-600 flex items-center justify-center gap-2 text-center shadow-md">Send Email</a>
            </div>
        </div>
    </div>
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

    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            modal.classList.add('modal-active');
        }, 10);
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.remove('modal-active');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }

    function formPayload(form, action) {
        const formData = new FormData(form);
        const payload = { action };
        formData.forEach((value, key) => {
            payload[key] = typeof value === 'string' ? value.trim() : value;
        });
        return payload;
    }

    async function submitProperty(event, action) {
        event.preventDefault();
        const form = event.target;
        const button = form.querySelector('button[type="submit"]');
        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = 'Saving Listing...';

        try {
            const response = await fetch('dashboard_host.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formPayload(form, action))
            });

            const data = await response.json();
            if (!response.ok || data.status !== 'success') {
                throw new Error(data.message || 'Unable to save property entry.');
            }

            showToast(data.message || 'Property saved successfully!');
            setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            showToast(error.message || 'Unable to complete your request.');
            button.disabled = false;
            button.textContent = originalText;
        }
    }

    function setField(form, name, value) {
        const field = form.elements[name];
        if (field) {
            field.value = value ?? '';
        }
    }

    function openEditProperty(property) {
        const form = document.getElementById('edit-property-form');
        setField(form, 'property_id', property.id);
        setField(form, 'property_name', property.property_name);
        setField(form, 'property_type', property.property_type);
        setField(form, 'status', property.status);
        setField(form, 'address', property.address);
        setField(form, 'city', property.city);
        setField(form, 'total_rooms', property.total_rooms);
        setField(form, 'available_rooms', property.available_rooms);
        setField(form, 'rent', property.rent);
        setField(form, 'deposit', property.deposit);
        
        // Render JSON Amenities & Rules into comma separated strings for editing
        let amenitiesStr = '';
        try { if(property.amenities) amenitiesStr = JSON.parse(property.amenities).join(', '); } catch(e){}
        setField(form, 'amenities', amenitiesStr);
        
        let rulesStr = '';
        try { if(property.house_rules) rulesStr = JSON.parse(property.house_rules).join(', '); } catch(e){}
        setField(form, 'house_rules', rulesStr);
        
        openModal('edit-property-modal');
    }

    function openSeekerModal(seeker) {
        const initial = seeker.first_name ? seeker.first_name.charAt(0).toUpperCase() : '?';
        document.getElementById('modal-seeker-init').textContent = initial;
        
        const fullName = (seeker.first_name || '') + ' ' + (seeker.last_name || '');
        document.getElementById('modal-seeker-name').textContent = fullName.trim() || 'Unknown User';
        
        document.getElementById('modal-seeker-email').textContent = seeker.email || 'Not Available';
        document.getElementById('modal-seeker-phone').textContent = seeker.phone || 'No phone provided';
        
        const dateOpt = { year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('modal-seeker-date').textContent = seeker.created_at ? new Date(seeker.created_at).toLocaleDateString('en-US', dateOpt) : 'Unknown Date';
        
        const contactBtn = document.getElementById('modal-seeker-contact');
        if(seeker.email) {
            contactBtn.href = "mailto:" + seeker.email;
            contactBtn.style.display = "flex";
        } else {
            contactBtn.style.display = "none";
        }
        
        openModal('view-seeker-modal');
    }
</script>

</body>
</html>