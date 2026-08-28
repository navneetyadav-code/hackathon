<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Thikana</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #F5F7FB;
    color: #172033;
    min-height: 100vh;
}

/* =========================
   APP
========================= */
.app {
    display: flex;
    min-height: 100vh;
}

/* =========================
   SIDEBAR
========================= */
.sidebar {
    width: 240px;
    height: 100vh;
    background: #102A43;
    color: white;
    position: fixed;
    left: 0;
    top: 0;
    padding: 30px 18px;
    display: flex;
    flex-direction: column;
}

.logo {
    font-size: 25px;
    font-weight: bold;
    margin-bottom: 45px;
    padding-left: 12px;
    color: white;
}

.logo span {
    color: #93C5FD;
}

/* NAVIGATION */
.nav {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.nav-item {
    /* Accessibility Reset */
    background: transparent;
    border: none;
    width: 100%;
    font-size: 16px;
    text-align: left;
    font-family: inherit;

    padding: 14px 15px;
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 14px;
    color: #CBD5E1;
    transition: 0.2s;
}

.nav-item:hover {
    background: #1D3B5A;
    color: white;
}

.nav-item.active {
    background: #2563EB;
    color: white;
}

.nav-icon {
    font-size: 20px;
}

/* USER */
.sidebar-bottom {
    margin-top: auto;
    border-top: 1px solid #28445F;
    padding-top: 20px;
}

.user-mini {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #D9E2EC;
    color: #102A43;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* =========================
   MAIN
========================= */
.main {
    margin-left: 240px;
    width: calc(100% - 240px);
    min-height: 100vh;
}

/* TOP BAR */
.topbar {
    height: 80px;
    background: white;
    border-bottom: 1px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 45px;
}

.location {
    color: #64748B;
    font-size: 14px;
}

.top-right {
    display: flex;
    align-items: center;
    gap: 25px;
}

.notification {
    font-size: 22px;
    cursor: pointer;
}

/* =========================
   PAGE
========================= */
.page {
    display: none;
    padding: 40px 50px;
    max-width: 1400px;
    margin: auto;
}

.page.active {
    display: block;
}

/* HEADINGS */
.page-title {
    font-size: 32px;
    margin-bottom: 8px;
}

.page-subtitle {
    color: #64748B;
    margin-bottom: 30px;
}

/* =========================
   SEARCH
========================= */
.search-box {
    width: 100%;
    padding: 17px 20px;
    background: white;
    border: 1px solid #D9E2EC;
    border-radius: 12px;
    font-size: 15px;
    outline: none;
    margin-bottom: 30px;
}

.search-box:focus {
    border-color: #2563EB;
}

/* =========================
   FEATURE CARDS
========================= */
.feature-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 40px;
}

.feature-card {
    padding: 30px;
    border-radius: 18px;
    min-height: 190px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.dark-card {
    background: #102A43;
    color: white;
}

.dark-card p {
    color: #CBD5E1;
}

.green-card {
    background: #EFF6FF;
    color: #102A43;
}

.green-card p {
    color: #64748B;
}

/* BUTTONS */
.btn {
    width: fit-content;
    padding: 11px 18px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
}

.dark-card .btn {
    background: white;
    color: #102A43;
}

.green-card .btn {
    background: #2563EB;
    color: white;
}

/* =========================
   LISTINGS
========================= */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}

.section-header h2 {
    font-size: 21px;
}

.view-all {
    color: #2563EB;
    cursor: pointer;
}

.listing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.listing {
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 15px;
    overflow: hidden;
    transition: 0.2s;
}

.listing:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(16, 42, 67, 0.08);
}

.listing-image {
    height: 150px;
    background: #EAF2FB;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 45px;
}

.listing-content {
    padding: 18px;
}

.listing h3 {
    margin-bottom: 8px;
}

.price {
    font-size: 19px;
    font-weight: bold;
    color: #102A43;
    margin-bottom: 8px;
}

.details {
    color: #64748B;
    font-size: 14px;
    line-height: 1.6;
}

.listing .btn {
    margin-top: 15px;
    background: #2563EB;
    color: white;
}

/* =========================
   FILTERS
========================= */
.filters {
    display: flex;
    gap: 12px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.filter {
    padding: 11px 15px;
    border: 1px solid #D9E2EC;
    background: white;
    border-radius: 8px;
    cursor: pointer;
}

.filter:hover {
    border-color: #2563EB;
    color: #2563EB;
}

/* =========================
   ROOMMATES
========================= */
.roommate-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.person {
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 15px;
    padding: 22px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #DBEAFE;
    color: #1D4ED8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: bold;
    flex-shrink: 0;
}

.person-info h3 {
    margin-bottom: 6px;
}

/* =========================
   SMART SPLIT
========================= */
.split-container {
    max-width: 600px;
}

.form-box {
    background: white;
    padding: 30px;
    border-radius: 16px;
    border: 1px solid #E2E8F0;
}

label {
    display: block;
    margin-top: 15px;
    margin-bottom: 7px;
    font-weight: bold;
}

input {
    width: 100%;
    padding: 13px;
    border: 1px solid #D9E2EC;
    border-radius: 8px;
    font-size: 15px;
}

input:focus {
    outline: none;
    border-color: #2563EB;
}

.calculate {
    width: 100%;
    margin-top: 22px;
    padding: 14px;
    background: #2563EB;
    color: white;
    border: none;
    border-radius: 9px;
    cursor: pointer;
    font-weight: bold;
}

.calculate:hover {
    background: #1D4ED8;
}

#result {
    display: none;
    margin-top: 20px;
    padding: 20px;
    background: #ECFDF5;
    border-radius: 10px;
    color: #166534;
}

/* =========================
   PROFILE
========================= */
.profile-container {
    max-width: 600px;
}

.profile-card {
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 35px;
    display: flex;
    align-items: center;
    gap: 25px;
}

.big-avatar {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: #DBEAFE;
    color: #1D4ED8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: bold;
}

/* =========================
   MOBILE NAV
========================= */
.mobile-nav {
    display: none;
}

/* =========================
   TABLET
========================= */
@media (max-width: 900px) {
    .sidebar { width: 200px; }
    .main { margin-left: 200px; width: calc(100% - 200px); }
    .feature-grid { grid-template-columns: 1fr; }
    .page { padding: 30px; }
}

/* =========================
   PHONE
========================= */
@media (max-width: 650px) {
    .sidebar { display: none; }
    .main { margin-left: 0; width: 100%; }
    .topbar { padding: 0 20px; height: 65px; }
    .page { padding: 25px 18px 100px; }
    .page-title { font-size: 26px; }
    .feature-grid { grid-template-columns: 1fr; }
    .listing-grid { grid-template-columns: 1fr; }
    .roommate-grid { grid-template-columns: 1fr; }
    
    .mobile-nav {
        display: flex;
        position: fixed;
        bottom: 0; left: 0; right: 0;
        height: 70px;
        background: white;
        border-top: 1px solid #E2E8F0;
        justify-content: space-around;
        align-items: center;
        z-index: 100;
    }

    .mobile-nav .nav-item {
        padding: 7px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        font-size: 11px;
    }
}

.back-btn {
    background: none; border: none;
    color: #2563EB; font-size: 15px;
    font-weight: bold; cursor: pointer;
    margin-bottom: 25px;
}

.room-header {
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 18px;
    overflow: hidden;
    display: flex;
    margin-bottom: 35px;
}

.room-image {
    width: 45%; min-height: 330px;
    background: #EAF2FB; display: flex;
    align-items: center; justify-content: center;
    font-size: 90px;
}

.room-info {
    padding: 35px; flex: 1;
}

.room-label {
    font-size: 11px; color: #64748B;
    font-weight: bold; letter-spacing: 1px;
}

.room-info h1 {
    font-size: 32px; margin: 8px 0;
}

.room-info > p {
    color: #64748B; margin-bottom: 20px;
}

.room-info h2 {
    font-size: 25px; margin-bottom: 18px;
}

.room-info h2 span {
    color: #64748B; font-size: 14px; font-weight: normal;
}

.room-tags {
    display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 25px;
}

.room-tags span {
    background: #EFF6FF; color: #1D4ED8;
    padding: 8px 12px; border-radius: 8px; font-size: 13px;
}

.interest-btn {
    background: #2563EB; color: white; border: none;
    padding: 13px 22px; border-radius: 9px;
    font-weight: bold; cursor: pointer;
}

.interest-btn:hover { background: #1D4ED8; }
#interest-status { color: #16A34A; font-weight: bold; margin-top: 12px; margin-bottom: 0; }
.room-section { margin-top: 30px; }
.section-subtitle { color: #64748B; margin: -8px 0 20px; }

.seeker-list {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px;
}

.seeker-card {
    background: white; border: 1px solid #E2E8F0;
    border-radius: 15px; padding: 20px; display: flex;
    align-items: flex-start; gap: 13px; flex-wrap: wrap;
}

.seeker-avatar {
    width: 55px; height: 55px; background: #DBEAFE;
    color: #1D4ED8; border-radius: 50%; display: flex;
    align-items: center; justify-content: center;
    font-weight: bold; font-size: 20px;
}

.seeker-info {
    flex: 1; min-width: 150px;
}

.seeker-info h3 { margin-bottom: 6px; }
.seeker-info p { color: #64748B; font-size: 13px; margin-bottom: 3px; }
.seeker-info span { color: #64748B; font-size: 12px; }

.friend-btn {
    width: 100%; padding: 10px; border: none;
    background: #2563EB; color: white; border-radius: 8px;
    cursor: pointer; font-weight: bold;
}

.friend-btn.sent { background: #E2E8F0; color: #475569; }

.popup {
    display: none; position: fixed; inset: 0;
    background: rgba(16, 42, 67, 0.45);
    align-items: center; justify-content: center; z-index: 500;
}

.popup.show { display: flex; }

.popup-box {
    background: white; width: 420px; max-width: 90%;
    border-radius: 18px; padding: 30px; text-align: center; position: relative;
}

.close-popup {
    position: absolute; right: 18px; top: 15px; border: none;
    background: none; font-size: 25px; cursor: pointer; color: #64748B;
}

.popup-avatar {
    width: 80px; height: 80px; background: #DBEAFE;
    color: #1D4ED8; border-radius: 50%; margin: auto; display: flex;
    align-items: center; justify-content: center; font-size: 30px;
    font-weight: bold; margin-bottom: 15px;
}

.popup-box > p { color: #64748B; }
.profile-details { margin-top: 25px; text-align: left; }
.profile-details div { padding: 12px 0; border-bottom: 1px solid #E2E8F0; color: #64748B; font-size: 13px; }
.profile-details strong { display: block; color: #172033; margin-top: 3px; }

.popup-chat-btn {
    width: 100%; margin-top: 22px; padding: 13px;
    background: #2563EB; color: white; border: none;
    border-radius: 9px; font-weight: bold; cursor: pointer;
}

.chat-container {
    max-width: 800px; height: calc(100vh - 160px); min-height: 550px;
    background: white; border: 1px solid #E2E8F0; border-radius: 18px;
    overflow: hidden; display: flex; flex-direction: column;
}

.chat-header {
    padding: 18px 22px; border-bottom: 1px solid #E2E8F0;
    display: flex; align-items: center; gap: 12px;
}

.back-chat { border: none; background: none; font-size: 22px; cursor: pointer; }

.chat-user-avatar {
    width: 45px; height: 45px; border-radius: 50%;
    background: #DBEAFE; color: #1D4ED8; display: flex;
    align-items: center; justify-content: center; font-weight: bold;
}

.chat-header p { color: #64748B; font-size: 12px; margin-top: 3px; }

.room-chat-info {
    background: #EFF6FF; color: #1D4ED8; padding: 12px 20px;
    font-size: 13px; font-weight: bold;
}

.room-chat-info span { margin-left: 10px; color: #64748B; }

.messages {
    flex: 1; padding: 25px; overflow-y: auto;
    display: flex; flex-direction: column; gap: 12px;
}

.message {
    max-width: 65%; padding: 11px 15px;
    border-radius: 13px; font-size: 14px; line-height: 1.5;
}

.message.received { align-self: flex-start; background: #F1F5F9; color: #172033; }
.message.sent { align-self: flex-end; background: #2563EB; color: white; }

.message-input {
    border-top: 1px solid #E2E8F0; padding: 15px; display: flex; gap: 10px;
}

.message-input input { flex: 1; }

.message-input button {
    width: 48px; border: none; background: #2563EB;
    color: white; border-radius: 8px; cursor: pointer; font-size: 18px;
}

@media (max-width: 900px) {
    .seeker-list { grid-template-columns: repeat(2, 1fr); }
    .room-header { flex-direction: column; }
    .room-image { width: 100%; min-height: 220px; }
}

@media (max-width: 650px) {
    .seeker-list { grid-template-columns: 1fr; }
    .room-info { padding: 22px; }
    .room-info h1 { font-size: 26px; }
    .chat-container { height: calc(100vh - 120px); min-height: 450px; }
}
</style>
</head>

<body>

<div class="app">

<aside class="sidebar">
    <div class="logo">
        Room<span>Mate</span>
    </div>

    <div class="nav">
        <button class="nav-item active" onclick="openPage('home', this)">
            <div class="nav-icon">🏠</div>
            <div>Home</div>
        </button>
        <button class="nav-item" onclick="openPage('explore', this)">
            <div class="nav-icon">🔍</div>
            <div>Explore</div>
        </button>
        <button class="nav-item" onclick="openPage('roommates', this)">
            <div class="nav-icon">🤝</div>
            <div>Roommates</div>
        </button>
        <button class="nav-item" onclick="openPage('split', this)">
            <div class="nav-icon">💰</div>
            <div>Smart Split</div>
        </button>
        <button class="nav-item" onclick="openPage('saved', this)">
            <div class="nav-icon">❤️</div>
            <div>Saved</div>
        </button>
    </div>

    <div class="sidebar-bottom">
        <div class="user-mini">
            <div class="user-avatar" id="user-avatar">H</div>
            <div>
                <b id="user-name-display">Your Name</b>
                <div class="details">Student</div>
            </div>
        </div>
    </div>
</aside>

<main class="main">

<header class="topbar">
    <div class="location" id="current-location">📍 Your Location</div>
    <div class="top-right">
        <div class="notification">🔔</div>
    </div>
</header>

<section id="home" class="page active">
    <h1 class="page-title">Hey 👋</h1>
    <p class="page-subtitle">Find a place that fits your budget.</p>

    <input class="search-box" type="text" placeholder="🔍 Search area, PG, hostel or flat...">

    <div class="feature-grid">
        <div class="feature-card dark-card">
            <div>
                <h2>🏠 Find Your Place</h2>
                <p>Find rooms, PGs, hostels and flats near you.</p>
            </div>
            <button class="btn" onclick="openPage('explore')">Explore Rooms →</button>
        </div>

        <div class="feature-card green-card">
            <div>
                <h2>💰 Split Rent Smartly</h2>
                <p>Divide rent and shared expenses fairly.</p>
            </div>
            <button class="btn" onclick="openPage('split')">Calculate →</button>
        </div>
    </div>

    <div class="section-header">
        <h2>⭐ Recommended for you</h2>
        <div class="view-all" onclick="openPage('explore')">View all →</div>
    </div>

    <div class="listing-grid" id="home-listings"></div>
</section>


<section id="explore" class="page">
    <h1 class="page-title">🔍 Explore</h1>
    <p class="page-subtitle">Find your next place.</p>

    <input class="search-box" placeholder="Search location...">

    <div class="filters">
        <div class="filter">💰 Budget</div>
        <div class="filter">📍 Distance</div>
        <div class="filter">🛏️ Room Type</div>
        <div class="filter">🍛 Food</div>
    </div>

    <div class="listing-grid" id="explore-listings"></div>
</section>


<section id="roommates" class="page">
    <h1 class="page-title">🤝 Roommates</h1>
    <p class="page-subtitle">Find people looking for a place too.</p>

    <div class="roommate-grid">
        <div class="person">
            <div class="avatar">A</div>
            <div class="person-info">
                <h3>Aditya</h3>
                <div class="details">
                    Budget: ₹5,000–₹6,000<br>
                    📍 Near College<br>
                    🧑‍🎓 Student
                </div>
            </div>
        </div>

        <div class="person">
            <div class="avatar">R</div>
            <div class="person-info">
                <h3>Rahul</h3>
                <div class="details">
                    Budget: ₹6,000–₹7,000<br>
                    📍 City Centre<br>
                    🧑‍🎓 Student
                </div>
            </div>
        </div>

        <div class="person">
            <div class="avatar">S</div>
            <div class="person-info">
                <h3>Simran</h3>
                <div class="details">
                    Budget: ₹5,000–₹6,500<br>
                    📍 Near University<br>
                    🧑‍🎓 Student
                </div>
            </div>
        </div>
    </div>
</section>

<section id="split" class="page">
    <h1 class="page-title">💰 Smart Split</h1>
    <p class="page-subtitle">Calculate everyone's rent share instantly.</p>

    <div class="split-container">
        <div class="form-box">
            <label for="rent">Total Rent</label>
            <input id="rent" type="number" placeholder="Example: 18000">

            <label for="people">Number of People</label>
            <input id="people" type="number" placeholder="Example: 3">

            <button class="calculate" onclick="calculateRent()">Calculate Split</button>

            <div id="result"></div>
        </div>
    </div>
</section>

<section id="saved" class="page">
    <h1 class="page-title">❤️ Saved Places</h1>
    <p class="page-subtitle">Places you've saved for later.</p>

    <div class="listing-grid">
        <div class="listing">
            <div class="listing-image">🏠</div>
            <div class="listing-content">
                <h3>Green View PG</h3>
                <div class="price">₹5,500 / month</div>
                <div class="details">📍 1.1 km from college</div>
            </div>
        </div>
    </div>
</section>

<section id="room-details" class="page">
    <button class="back-btn" onclick="openPage('explore')">← Back to Explore</button>

    <div class="room-header">
        <div class="room-image">🏠</div>
        <div class="room-info">
            <span class="room-label">PG • SHARED ROOM</span>
            <h1>Green View PG</h1>
            <p>📍 Near College, Your City</p>
            <h2>₹18,000 <span>/ month</span></h2>
            <div class="room-tags">
                <span>🛏️ 2BHK</span>
                <span>👥 3 People</span>
                <span>📶 Wi-Fi</span>
                <span>🍛 Food</span>
            </div>
            <button class="interest-btn" onclick="showInterest()">I'm Interested</button>
            <p id="interest-status"></p>
        </div>
    </div>

    <div class="room-section">
        <h2>👥 Interested Seekers</h2>
        <p class="section-subtitle">Connect with people interested in this same room.</p>
        
        <div class="seeker-list">
            <div class="seeker-card">
                <div class="seeker-avatar">R</div>
                <div class="seeker-info">
                    <h3>Rahul</h3>
                    <p>💰 Budget: ₹5,000–₹7,000</p>
                    <p>📅 Moving in September</p>
                    <span>🧑‍🎓 Student</span>
                </div>
                <button class="friend-btn" onclick="sendFriendRequest(this, 'Rahul')">Add Friend</button>
            </div>

            <div class="seeker-card">
                <div class="seeker-avatar">A</div>
                <div class="seeker-info">
                    <h3>Aditya</h3>
                    <p>💰 Budget: ₹5,000–₹6,000</p>
                    <p>📅 Moving in September</p>
                    <span>🧑‍🎓 Student</span>
                </div>
                <button class="friend-btn" onclick="sendFriendRequest(this, 'Aditya')">Add Friend</button>
            </div>

            <div class="seeker-card">
                <div class="seeker-avatar">S</div>
                <div class="seeker-info">
                    <h3>Simran</h3>
                    <p>💰 Budget: ₹6,000–₹7,000</p>
                    <p>📅 Moving in October</p>
                    <span>🧑‍🎓 Student</span>
                </div>
                <button class="friend-btn" onclick="sendFriendRequest(this, 'Simran')">Add Friend</button>
            </div>
        </div>
    </div>
</section>

<div id="profile-popup" class="popup">
    <div class="popup-box">
        <button class="close-popup" onclick="closeProfile()">×</button>
        <div class="popup-avatar">R</div>
        <h2 id="popup-name">Rahul</h2>
        <p>🧑‍🎓 Student</p>
        <div class="profile-details">
            <div>💰 Budget <strong>₹5,000–₹7,000</strong></div>
            <div>📍 Preferred Area <strong>Near College</strong></div>
            <div>📅 Move-in <strong>September</strong></div>
        </div>
        <button class="popup-chat-btn" onclick="openChat('Rahul')">💬 Message</button>
    </div>
</div>

<section id="chat" class="page">
    <div class="chat-container">
        <div class="chat-header">
            <button class="back-chat" onclick="openPage('room-details')">←</button>
            <div class="chat-user-avatar">R</div>
            <div>
                <h3 id="chat-name">Rahul</h3>
                <p>Green View PG</p>
            </div>
        </div>

        <div class="room-chat-info">
            🏠 Green View PG
            <span>₹18,000 / month</span>
        </div>

        <div class="messages" id="messages">
            <div class="message received">Hey! Are you still interested in this room?</div>
            <div class="message sent">Yeah, I'm looking for roommates.</div>
        </div>

        <div class="message-input">
            <input id="messageInput" type="text" placeholder="Type a message..." onkeydown="handleEnter(event)">
            <button onclick="sendMessage()">➤</button>
        </div>
    </div>
</section>
</main>
</div>

<nav class="mobile-nav">
    <button class="nav-item active" onclick="openPage('home', this)">
        <div>🏠</div>
        <small>Home</small>
    </button>
    <button class="nav-item" onclick="openPage('explore', this)">
        <div>🔍</div>
        <small>Explore</small>
    </button>
    <button class="nav-item" onclick="openPage('roommates', this)">
        <div>🤝</div>
        <small>Roommates</small>
    </button>
    <button class="nav-item" onclick="openPage('split', this)">
        <div>💰</div>
        <small>Split</small>
    </button>
    <button class="nav-item" onclick="openPage('saved', this)">
        <div>❤️</div>
        <small>Saved</small>
    </button>
</nav>

<script>
/* =========================
   DYNAMIC DATA RENDERING
========================= */
const propertyData = [
    { name: "Green View PG", price: "5,500", distance: "1.1 km", type: "Shared room", perk: "🍛 Food available", icon: "🏠" },
    { name: "City Heights Flat", price: "7,000", distance: "2 km", type: "Private room", perk: "📶 Wi-Fi included", icon: "🏢" },
    { name: "Student Residency", price: "6,200", distance: "900 m", type: "2 roommates", perk: "⚡ Electricity included", icon: "🏠" }
];

function renderListings(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    container.innerHTML = propertyData.map(prop => `
        <div class="listing">
            <div class="listing-image">${prop.icon}</div>
            <div class="listing-content">
                <h3>${prop.name}</h3>
                <div class="price">₹${prop.price} / month</div>
                <div class="details">
                    📍 ${prop.distance} from college<br>
                    👥 ${prop.type}<br>
                    ${prop.perk}
                </div>
                <button class="btn" onclick="openPage('room-details')">View Details</button>
            </div>
        </div>
    `).join('');
}

// Render dynamic elements when page loads
window.addEventListener('DOMContentLoaded', () => {
    renderListings('home-listings');
    renderListings('explore-listings');
});


/* =========================
   PAGE SWITCHING
========================= */
function openPage(pageName, element) {
    document.querySelectorAll(".page").forEach(page => {
        page.classList.remove("active");
    });

    document.getElementById(pageName).classList.add("active");

    document.querySelectorAll(".sidebar .nav-item").forEach(item => {
        item.classList.remove("active");
    });

    document.querySelectorAll(".mobile-nav .nav-item").forEach(item => {
        item.classList.remove("active");
    });

    document.querySelectorAll(".nav-item").forEach(item => {
        if (item.innerText.toLowerCase().includes(pageName === "split" ? "split" : pageName)) {
            item.classList.add("active");
        }
    });
    window.scrollTo(0, 0);
}

/* =========================
   RENT CALCULATOR
========================= */
function calculateRent() {
    const rent = Number(document.getElementById("rent").value);
    const people = Number(document.getElementById("people").value);
    const result = document.getElementById("result");

    if (rent <= 0 || people <= 0) {
        result.style.display = "block";
        result.innerHTML = "⚠️ Please enter valid values.";
        return;
    }

    const share = rent / people;
    result.style.display = "block";
    result.innerHTML = `
        <h3>Split Result</h3><br>
        Total Rent: <b>₹${rent.toLocaleString("en-IN")}</b><br>
        People: <b>${people}</b><br><br>
        Each person pays:
        <h2>₹${share.toLocaleString("en-IN", { maximumFractionDigits: 2 })}</h2>
    `;
}

/* =========================
   INTERACTIONS
========================= */
function showInterest() {
    const status = document.getElementById("interest-status");
    status.innerText = "✓ You're interested in this room";
}

function sendFriendRequest(button, name) {
    button.innerText = "Request Sent ✓";
    button.classList.add("sent");
    button.disabled = true;
}

function openProfile(name) {
    document.getElementById("popup-name").innerText = name;
    document.getElementById("profile-popup").classList.add("show");
}

function closeProfile() {
    document.getElementById("profile-popup").classList.remove("show");
}

function openChat(name) {
    closeProfile();
    document.getElementById("chat-name").innerText = name;
    openPage("chat");
}

/* =========================
   CHAT SYSTEM (With Mock Reply)
========================= */
function sendMessage() {
    const input = document.getElementById("messageInput");
    const message = input.value.trim();

    if (message === "") return;

    const messages = document.getElementById("messages");

    // Add sent message
    const newMessage = document.createElement("div");
    newMessage.className = "message sent";
    newMessage.innerText = message;
    messages.appendChild(newMessage);

    input.value = "";
    messages.scrollTop = messages.scrollHeight;

    // Simulate auto-reply delay
    setTimeout(() => {
        const replyMessage = document.createElement("div");
        replyMessage.className = "message received";
        replyMessage.innerText = "Thanks for the message! I'll get back to you shortly.";
        messages.appendChild(replyMessage);
        messages.scrollTop = messages.scrollHeight;
    }, 1000);
}

function handleEnter(event) {
    if (event.key === "Enter") {
        sendMessage();
    }
}
</script>

</body>
</html>