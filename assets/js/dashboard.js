    // --- Dark Mode Logic ---
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
    const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

    function updateThemeIcons() {
        if (!themeToggleBtn || !themeToggleDarkIcon || !themeToggleLightIcon) return;

        if (document.documentElement.classList.contains('dark')) {
            themeToggleLightIcon.classList.remove('hidden');
            themeToggleDarkIcon.classList.add('hidden');
        } else {
            themeToggleLightIcon.classList.add('hidden');
            themeToggleDarkIcon.classList.remove('hidden');
        }
    }
    
    updateThemeIcons();

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', function() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
            updateThemeIcons();
        });
    }

    // --- Custom Toast System (Replaces alert()) ---
    function showToast(message, type = 'error') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        
        let bgColor = type === 'error' ? 'bg-red-500' : 'bg-green-500';
        
        toast.className = `toast-enter flex items-center p-4 mb-4 text-white rounded-xl shadow-lg ${bgColor}`;
        toast.innerHTML = `<span class="text-sm font-semibold">${message}</span>`;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }


    // --- Data & State ---
    const sessionManager = {
        init() {
            const globalUser = window.thikanaUser || null;
            let user = globalUser;

            if (!user) {
                const storedUser = sessionStorage.getItem('thikana_user');
                if (storedUser) {
                    user = JSON.parse(storedUser);
                }
            }

            if (!user || !user.firstName) {
                user = { firstName: 'User', lastName: '', email: '' };
            }

            sessionStorage.setItem('thikana_user', JSON.stringify(user));
            this.updateUI(user);
        },
        updateUI(user) {
            const firstName = user.firstName || 'User';
            const lastName = user.lastName || '';
            const initial = firstName.charAt(0).toUpperCase();
            document.getElementById('sidebar-avatar').textContent = initial;
            document.getElementById('sidebar-name').textContent = [firstName, lastName].filter(Boolean).join(' ');
            document.getElementById('welcome-firstname').textContent = firstName;
        },
        logout() {
            sessionStorage.removeItem('thikana_user');
            window.location.href = 'login.php';
        }
    };

    const propertyData = [
        { name: 'Green View PG', price: 6500, distance: '1.1 km', area: 'Koramangala', type: 'shared', room: 'Shared room', perk: 'Food available', img: 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=400&q=80', match: 94 },
        { name: 'City Heights Flat', price: 9500, distance: '2 km', area: 'HSR Layout', type: 'private', room: 'Private room', perk: 'Wi-Fi included', img: 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=400&q=80', match: 88 },
        { name: 'Student Residency', price: 5800, distance: '900 m', area: 'BTM Layout', type: 'shared', room: '3 roommates', perk: 'Electricity inc.', img: 'https://images.unsplash.com/photo-1502672260266-1c1de2422008?auto=format&fit=crop&w=400&q=80', match: 91 },
        { name: 'Metro Nest Hostel', price: 4800, distance: '2.4 km', area: 'Indiranagar', type: 'shared', room: 'Dorm share', perk: 'Laundry nearby', img: 'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?auto=format&fit=crop&w=400&q=80', match: 82 },
        { name: 'Blue Orchid Studio', price: 12000, distance: '3 km', area: 'Whitefield', type: 'private', room: 'Single occupancy', perk: 'Attached bath', img: 'https://images.unsplash.com/photo-1536376072261-38c75010e6c9?auto=format&fit=crop&w=400&q=80', match: 76 }
    ];

    const conversations = [
        { id: 'green', name: 'Green View PG', meta: 'Host: Meera', avatar: 'G', messages: [
            { who: 'them', text: 'Hi Navneet, the shared room is available from Monday.' },
            { who: 'me', text: 'Great. Is food included in the monthly amount?' },
            { who: 'them', text: 'Yes, breakfast and dinner are included. Electricity is separate.' }
        ] },
        { id: 'city', name: 'City Heights Flat', meta: 'Roommate: Arjun', avatar: 'C', messages: [
            { who: 'them', text: 'We are looking for one roommate for the private room.' },
            { who: 'them', text: 'The flat is 2 km from campus and has Wi-Fi included.' }
        ] },
        { id: 'student', name: 'Student Residency', meta: 'Host: Kavya', avatar: 'S', messages: [
            { who: 'me', text: 'Can I schedule a visit this weekend?' },
            { who: 'them', text: 'Yes, Saturday after 11 AM works.' }
        ] }
    ];
    let activeConversation = conversations[0].id;

    // --- Helpers ---
    const formatMoney = (amount) => '₹' + Math.round(amount).toLocaleString('en-IN');

    // --- Navigation ---
    function openPage(pageName, el) {
        // Hide all pages
        document.querySelectorAll('.page-section').forEach(p => p.classList.remove('active'));
        // Show target
        document.getElementById(pageName).classList.add('active');
        
        // Update Desktop Nav
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.classList.remove('active');
            btn.classList.remove('text-slate-900', 'dark:text-slate-100');
            btn.classList.add('text-slate-600', 'dark:text-slate-400');
        });
        const desktopBtn = document.querySelector(`.nav-btn[data-page="${pageName}"]`);
        if(desktopBtn) {
            desktopBtn.classList.add('active');
            desktopBtn.classList.remove('text-slate-600', 'dark:text-slate-400');
            desktopBtn.classList.add('text-slate-900', 'dark:text-slate-100');
        }

        // Update Mobile Nav
        document.querySelectorAll('.lg\\:hidden.fixed button').forEach(btn => {
            btn.classList.remove('text-brand-primary', 'dark:text-indigo-400');
            btn.classList.add('text-slate-500', 'dark:text-slate-400');
        });
        const mobileBtn = document.querySelector(`.lg\\:hidden.fixed button[data-page="${pageName}"]`);
        if(mobileBtn) {
            mobileBtn.classList.add('text-brand-primary', 'dark:text-indigo-400');
            mobileBtn.classList.remove('text-slate-500', 'dark:text-slate-400');
        }

        // Close mobile sidebar if open
        const sidebar = document.getElementById('sidebar');
        if (sidebar.classList.contains('translate-x-0') && window.innerWidth < 1024) {
            toggleSidebar();
        }
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        if (sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            overlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            overlay.classList.add('hidden');
        }
    }

    // --- STREAMING_CHUNK:Adding Render functions for Listings... ---
    // --- Render Listings ---
    function renderListings(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        let search = '', budget = 'all', type = 'all';
        
        if (containerId === 'explore-listings') {
            search = (document.getElementById('listing-search')?.value || '').toLowerCase();
            budget = document.getElementById('budget-filter')?.value || 'all';
            type = document.getElementById('type-filter')?.value || 'all';
        }

        const filtered = propertyData.filter(prop => {
            const mSearch = !search || [prop.name, prop.area, prop.room, prop.perk].join(' ').toLowerCase().includes(search);
            const mBudget = budget === 'all' || prop.price <= Number(budget);
            const mType = type === 'all' || prop.type === type;
            return mSearch && mBudget && mType;
        });

        // Slice for home page
        const displayData = containerId === 'home-listings' ? filtered.slice(0, 3) : filtered;

        container.innerHTML = displayData.map(prop => `
            <div class="glass-panel rounded-2xl overflow-hidden group hover:-translate-y-1 transition-all duration-300 flex flex-col">
                <div class="h-48 relative overflow-hidden">
                    <img src="${prop.img}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="${prop.name}">
                    <div class="absolute top-3 right-3 bg-white/90 dark:bg-slate-900/90 backdrop-blur px-2.5 py-1 rounded-lg text-xs font-bold text-brand-primary dark:text-indigo-400 shadow-sm flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path></svg>
                        ${prop.match}% Match
                    </div>
                    <div class="absolute bottom-0 w-full bg-gradient-to-t from-slate-900/80 to-transparent p-4">
                        <h3 class="text-white font-bold text-lg leading-tight">${prop.name}</h3>
                        <p class="text-slate-200 text-xs flex items-center gap-1 mt-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                            ${prop.area} | ${prop.distance}
                        </p>
                    </div>
                </div>
                <div class="p-4 flex-1 flex flex-col bg-white dark:bg-slate-800">
                    <div class="flex justify-between items-start mb-3">
                        <div class="text-xl font-extrabold text-slate-900 dark:text-white">${formatMoney(prop.price)}<span class="text-xs font-normal text-slate-500 dark:text-slate-400">/mo</span></div>
                        <span class="bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-[10px] font-bold uppercase tracking-wide px-2 py-1 rounded">${prop.type === 'shared' ? 'Shared' : 'Private'}</span>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 flex-1">${prop.room} • ${prop.perk}</p>
                    <div class="flex gap-2 mt-auto">
                        <button class="flex-1 bg-brand-light dark:bg-indigo-900/30 text-brand-primary dark:text-indigo-400 border border-brand-primary/10 dark:border-indigo-500/30 font-semibold py-2 rounded-xl text-sm hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors" onclick="openPage('matcher')">Analyze</button>
                        <button class="flex-1 bg-brand-primary text-white font-semibold py-2 rounded-xl text-sm hover:bg-brand-hover transition-colors shadow-sm" onclick="openPage('messages')">Message</button>
                    </div>
                </div>
            </div>
        `).join('') || `<div class="col-span-full p-8 text-center text-slate-500 dark:text-slate-400 glass-panel rounded-2xl">No listings match these filters.</div>`;
    }

    // --- STREAMING_CHUNK:Adding Matcher logic... ---
    // --- Smart Matcher Logic ---
    let currentRoomType = 'standard';
    let currentUtility = 3000;

    function updateUtility(val) {
        currentUtility = parseInt(val);
        document.getElementById('utility-val').innerText = formatMoney(currentUtility);
        if (!document.getElementById('match-result-box').classList.contains('hidden')) analyzeMatch();
    }

    function setRoomType(type) {
        currentRoomType = type;
        const btnStandard = document.getElementById('btn-standard');
        const btnMaster = document.getElementById('btn-master');
        
        if(type === 'standard') {
            btnStandard.classList.add('bg-white', 'dark:bg-slate-700', 'shadow-sm', 'text-brand-primary', 'dark:text-white');
            btnStandard.classList.remove('text-slate-500', 'dark:text-slate-400');
            
            btnMaster.classList.remove('bg-white', 'dark:bg-slate-700', 'shadow-sm', 'text-brand-primary', 'dark:text-white');
            btnMaster.classList.add('text-slate-500', 'dark:text-slate-400');
        } else {
            btnMaster.classList.add('bg-white', 'dark:bg-slate-700', 'shadow-sm', 'text-brand-primary', 'dark:text-white');
            btnMaster.classList.remove('text-slate-500', 'dark:text-slate-400');
            
            btnStandard.classList.remove('bg-white', 'dark:bg-slate-700', 'shadow-sm', 'text-brand-primary', 'dark:text-white');
            btnStandard.classList.add('text-slate-500', 'dark:text-slate-400');
        }
        
        if (!document.getElementById('match-result-box').classList.contains('hidden')) analyzeMatch();
    }

    function analyzeMatch() {
        const income = parseFloat(document.getElementById('user-income').value);
        if (!income || income <= 0) {
            showToast('Please enter a valid monthly income.', 'error');
            return;
        }
        const baseRent = parseFloat(document.getElementById('base-rent').getAttribute('data-value'));
        const totalPeople = 4;
        
        // Simple logic for share
        const userRentShare = currentRoomType === 'master' ? baseRent * 0.35 : baseRent * 0.2166;
        const userUtilityShare = currentUtility / totalPeople;
        const totalUserShare = userRentShare + userUtilityShare;
        const percentageUsed = (totalUserShare / income) * 100;

        // UI Updates
        document.getElementById('avatar-you').classList.remove('hidden');
        document.getElementById('avatar-you').classList.add('flex');
        
        const resultBox = document.getElementById('match-result-box');
        resultBox.classList.remove('hidden');
        
        document.getElementById('share-amount').innerText = formatMoney(totalUserShare);
        document.getElementById('breakdown-info').innerText = `Rent: ${formatMoney(userRentShare)} | Utils: ${formatMoney(userUtilityShare)}`;
        document.getElementById('percentage-used').innerText = percentageUsed.toFixed(1) + '%';

        const meterBar = document.getElementById('meter-bar');
        const statusText = document.getElementById('status-text');
        
        // Slight delay for animation
        setTimeout(() => { meterBar.style.width = Math.min(percentageUsed, 100) + '%'; }, 50);
        
        if (percentageUsed <= 25) {
            meterBar.className = 'h-full rounded-full transition-all duration-500 bg-green-500';
            statusText.className = 'text-sm font-bold p-3 rounded-lg text-center bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-400 border border-green-200 dark:border-green-800';
            statusText.innerText = 'Excellent Match. Plenty of room in your budget.';
        } else if (percentageUsed <= 40) {
            meterBar.className = 'h-full rounded-full transition-all duration-500 bg-orange-500';
            statusText.className = 'text-sm font-bold p-3 rounded-lg text-center bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-400 border border-orange-200 dark:border-orange-800';
            statusText.innerText = 'Manageable, but watch other expenses.';
        } else {
            meterBar.className = 'h-full rounded-full transition-all duration-500 bg-red-500';
            statusText.className = 'text-sm font-bold p-3 rounded-lg text-center bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400 border border-red-200 dark:border-red-800';
            statusText.innerText = 'Risky. This takes up too much of your income.';
        }
    }

    // --- STREAMING_CHUNK:Adding Calculator logic... ---
    // --- Calculator Logic ---
    function getRoommateCount() {
        return Math.max(1, parseInt(document.getElementById('calc-roommates').value) || 1);
    }

    function renderCustomSplitFields() {
        const mode = document.getElementById('calc-split-mode').value;
        const customBox = document.getElementById('calc-custom-split');
        const roommates = getRoommateCount();

        if (mode === 'custom') {
            customBox.classList.remove('hidden');
            customBox.innerHTML = Array.from({ length: roommates }, (_, index) => `
                <div>
                    <label class="block text-xs font-semibold mb-1 text-slate-500 dark:text-slate-400">Person ${index + 1} Rent</label>
                    <input type="number" class="calc-custom-share input-field py-2 text-sm" min="0" placeholder="₹">
                </div>
            `).join('');
        } else {
            customBox.classList.add('hidden');
            customBox.innerHTML = '';
        }
    }

    function calculateSplit(e) {
        e.preventDefault();
        // Gathering inputs
        const rent = parseFloat(document.getElementById('calc-rent').value) || 0;
        const utilities = parseFloat(document.getElementById('calc-util').value) || 0;
        const roommates = getRoommateCount();
        const splitMode = document.getElementById('calc-split-mode').value;
        const income = parseFloat(document.getElementById('calc-income').value) || 0;
        const maxPercent = parseFloat(document.getElementById('calc-percent').value) || 30;
        
        const food = parseFloat(document.getElementById('calc-food').value) || 0;
        const travel = parseFloat(document.getElementById('calc-travel').value) || 0;
        const other = parseFloat(document.getElementById('calc-other').value) || 0;
        const savingsGoal = parseFloat(document.getElementById('calc-savings').value) || 0;
        
        const totalCost = rent + utilities;
        let myRentShare = rent / roommates;
        let myUtilShare = utilities / roommates;
        let perPersonTotal = myRentShare + myUtilShare;
        
        let splitDetailsHTML = '';

        if (splitMode === 'custom') {
            const customShares = Array.from(document.querySelectorAll('.calc-custom-share')).map(input => parseFloat(input.value) || 0);
            const customTotal = customShares.reduce((sum, amount) => sum + amount, 0);
            if (customShares.length !== roommates || customShares.some(amount => amount <= 0)) {
                showToast('Please enter a valid amount for every person in custom split.', 'error');
                return;
            }
            if (Math.abs(customTotal - rent) > 2) {
                showToast(`Custom rent shares (${customTotal}) must add up to total rent (${rent}).`, 'error');
                return;
            }
            // Assume user is Person 1 for calc
            myRentShare = customShares[0];
            perPersonTotal = myRentShare + myUtilShare; // Utilities still split equally
            
            splitDetailsHTML = `
                <div class="mt-4 p-3 bg-white/50 dark:bg-slate-800/50 rounded-lg text-sm border border-slate-200 dark:border-slate-700">
                    <p class="font-semibold mb-2 dark:text-white">Custom Breakdown (Rent only):</p>
                    ${customShares.map((amount, i) => `<div class="flex justify-between text-slate-500 dark:text-slate-400 mb-1"><span>Person ${i + 1}${i===0?' (You)':''}</span><span class="font-medium text-slate-900 dark:text-slate-200">${formatMoney(amount)}</span></div>`).join('')}
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 italic">*Utilities (${formatMoney(myUtilShare)}/ea) added to totals.</p>
                </div>
            `;
        }

        const lifestyleExpenses = food + travel + other;
        const fullMonthlyOutflow = perPersonTotal + lifestyleExpenses;
        
        let reportHTML = `
            <div class="glass-panel rounded-3xl p-6 md:p-8 animate-fadeUp">
                <h3 class="text-xl font-extrabold mb-6 dark:text-white">Your Budget Report</h3>
                
                <!-- Share Card -->
                <div class="bg-gradient-to-br from-brand-primary to-brand-hover rounded-2xl p-6 text-white mb-6 shadow-lg shadow-brand-primary/20">
                    <p class="text-indigo-100 font-medium text-sm mb-1">${splitMode === 'custom' ? 'Your Custom Share' : 'Equal Share (Rent+Util)'}</p>
                    <h4 class="text-4xl font-extrabold mb-2">${formatMoney(perPersonTotal)}<span class="text-lg font-normal opacity-80">/mo</span></h4>
                    <p class="text-sm opacity-90 flex justify-between">
                        <span>Rent: ${formatMoney(myRentShare)}</span>
                        <span>Util: ${formatMoney(myUtilShare)}</span>
                    </p>
                </div>
                ${splitDetailsHTML}

                <!-- Total Outflow -->
                <div class="border border-slate-200 dark:border-slate-700 rounded-2xl p-5 mb-6 bg-slate-50 dark:bg-slate-800/50">
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-bold text-slate-900 dark:text-white">Total Est. Outflow</span>
                        <span class="font-extrabold text-lg dark:text-white">${formatMoney(fullMonthlyOutflow)}</span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Includes housing + all lifestyle expenses.</p>
                </div>
        `;

        if (income > 0) {
            const rentRatio = (myRentShare / income) * 100;
            const outflowRatio = (fullMonthlyOutflow / income) * 100;
            const leftover = income - fullMonthlyOutflow - savingsGoal;
            
            const isRentOk = rentRatio <= maxPercent;
            
            reportHTML += `
                <div class="mb-6">
                    <h4 class="font-bold mb-3 border-b border-slate-200 dark:border-slate-700 pb-2 dark:text-white">Health Checks</h4>
                    
                    <div class="flex items-start gap-3 mb-4 p-3 rounded-xl border ${isRentOk ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800/50' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800/50'}">
                        <svg class="w-5 h-5 mt-0.5 flex-shrink-0 ${isRentOk ? 'text-green-500' : 'text-red-500'}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="${isRentOk ? 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' : 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z'}" clip-rule="evenodd"></path></svg>
                        <div>
                            <p class="font-bold text-sm ${isRentOk ? 'text-green-800 dark:text-green-400' : 'text-red-800 dark:text-red-400'}">Rent-to-Income: ${rentRatio.toFixed(1)}%</p>
                            <p class="text-xs ${isRentOk ? 'text-green-700 dark:text-green-500' : 'text-red-700 dark:text-red-500'}">${isRentOk ? `Under your ${maxPercent}% limit.` : `Exceeds your ${maxPercent}% target.`}</p>
                        </div>
                    </div>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Total Income Used</span><span class="font-medium dark:text-slate-200">${outflowRatio.toFixed(1)}%</span></div>
                        <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Savings Goal</span><span class="font-medium text-brand-primary dark:text-indigo-400">${formatMoney(savingsGoal)}</span></div>
                        <div class="flex justify-between pt-2 border-t border-slate-200 dark:border-slate-700"><span class="font-bold dark:text-white">Remaining Buffer</span><span class="font-bold ${leftover >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">${formatMoney(leftover)}</span></div>
                    </div>
                </div>
            `;
        }

        reportHTML += `</div>`;
        
        document.getElementById('calc-empty').classList.add('hidden');
        const resultsContainer = document.getElementById('calc-results');
        resultsContainer.innerHTML = reportHTML;
        resultsContainer.classList.remove('hidden');
    }

    // --- STREAMING_CHUNK:Adding Chat logic... ---
    // --- Messaging Logic ---
    function renderConversations() {
        const list = document.getElementById('conversation-list');
        list.innerHTML = conversations.map(convo => `
            <button class="w-full text-left p-3 rounded-xl mb-1 flex items-center gap-3 transition-colors ${convo.id === activeConversation ? 'bg-white dark:bg-slate-800 shadow-sm border border-slate-200 dark:border-slate-700' : 'hover:bg-slate-100 dark:hover:bg-slate-800 border border-transparent'}" onclick="selectConversation('${convo.id}')">
                <div class="w-10 h-10 rounded-full bg-brand-light dark:bg-indigo-900/40 text-brand-primary dark:text-indigo-400 flex-shrink-0 flex items-center justify-center font-bold text-sm">${convo.avatar}</div>
                <div class="overflow-hidden flex-1">
                    <div class="flex justify-between items-baseline mb-0.5">
                        <strong class="text-sm text-slate-900 dark:text-white truncate block">${convo.name}</strong>
                    </div>
                    <span class="text-xs text-slate-500 dark:text-slate-400 truncate block">${convo.messages[convo.messages.length - 1].text}</span>
                </div>
            </button>
        `).join('');
        renderChat();
    }

    function selectConversation(id) {
        activeConversation = id;
        renderConversations();
        
        // On mobile, show chat area
        if (window.innerWidth < 768) {
            document.getElementById('conv-sidebar').classList.add('hidden');
            document.getElementById('chat-area').classList.remove('hidden');
            document.getElementById('chat-area').classList.add('flex');
        }
    }

    function toggleChatMobile() {
        document.getElementById('conv-sidebar').classList.remove('hidden');
        document.getElementById('chat-area').classList.add('hidden');
        document.getElementById('chat-area').classList.remove('flex');
    }

    function renderChat() {
        const convo = conversations.find(item => item.id === activeConversation);
        document.getElementById('chat-title').textContent = convo.name;
        document.getElementById('chat-subtitle').textContent = convo.meta;
        document.getElementById('chat-avatar').textContent = convo.avatar;
        
        const messagesHtml = convo.messages.map(msg => `
            <div class="flex ${msg.who === 'me' ? 'justify-end' : 'justify-start'}">
                <div class="max-w-[75%] px-4 py-3 rounded-2xl text-sm leading-relaxed ${msg.who === 'me' ? 'bg-brand-primary text-white rounded-br-sm shadow-md shadow-brand-primary/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white rounded-bl-sm border border-slate-200 dark:border-slate-700'}">
                    ${msg.text}
                </div>
            </div>
        `).join('');
        
        const chatContainer = document.getElementById('chat-messages');
        chatContainer.innerHTML = messagesHtml;
        // Scroll to bottom
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    function sendMessage(e) {
        e.preventDefault();
        const input = document.getElementById('message-input');
        const value = input.value.trim();
        if (!value) return;
        
        const convo = conversations.find(item => item.id === activeConversation);
        convo.messages.push({ who: 'me', text: value });
        input.value = '';
        
        renderConversations();
    }

    // --- Init ---
    window.addEventListener('DOMContentLoaded', () => {
        sessionManager.init();
        renderListings('home-listings');
        renderListings('explore-listings');
        renderConversations();
        
        // Handle window resize for chat UI & Sidebar
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                document.getElementById('conv-sidebar').classList.remove('hidden');
                document.getElementById('chat-area').classList.remove('hidden');
                document.getElementById('chat-area').classList.add('flex');
            }
            if (window.innerWidth >= 1024) {
                 document.getElementById('sidebar').classList.remove('-translate-x-full');
                 document.getElementById('sidebar').classList.add('translate-x-0');
                 document.getElementById('sidebar-overlay').classList.add('hidden');
            } else {
                 document.getElementById('sidebar').classList.add('-translate-x-full');
                 document.getElementById('sidebar').classList.remove('translate-x-0');
            }
        });
    });
