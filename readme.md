# Thikana / RoomMate Prototype

This repository is a front-end prototype for shared housing discovery, affordability analysis, roommate coordination, host-side management, and household expense planning.

## Prototype Nature

- Static HTML/CSS/JS (no backend required).
- Uses mock/sample data and simulated interactions.
- Some flows are UI simulations (success toasts, fake loading, client-only state).

## What Users Can Do Through the UI

### 1) Landing & Marketing Experience (`index.html`)
- View branded landing page with responsive navigation and mobile menu.
- Read product messaging: affordability, coliving, roommate matching.
- Explore “How it works” and feature/value sections.
- View sample visuals/testimonials style content.
- Submit the contact form (mock success flow with loading state and confirmation).

### 2) Authentication Experience (`login.html`)
- Toggle between **Log In** and **Sign Up** tabs.
- Log in with email/password (prototype validation + simulated redirect flow).
- Sign up as **Seeker** or **Host** with profile fields.
- Toggle password visibility.
- See inline success/error alerts.

### 3) Seeker Dashboard Experience (`dashboard.html` + `assets/js/dashboard.js`)

#### Navigation & Session
- Use sidebar + mobile bottom nav to switch pages.
- Toggle dark/light theme (persisted in localStorage).
- Auto-initialized session user in sessionStorage.
- Logout flow back to login page.
- Toast notifications for feedback.

#### Home
- Personalized greeting using session profile.
- Quick action cards to jump into matching, calculator, and messages.
- “Recommended for you” listing cards with sample match percentages.

#### Explore Stays
- Search listings by keyword/location/attributes.
- Filter by budget and room type (shared/private).
- Refresh/apply filters and view no-result state.
- Open actions from each card: Analyze (matcher) or Message (chat).

#### Smart Property Match
- Review base property rent and resident context.
- Adjust estimated utilities slider.
- Toggle room preference (standard vs master).
- Enter income and run affordability analysis.
- View computed monthly share, income usage %, breakdown, and risk status.
- Visual meter + budget-health classification messaging.

#### Split & Budget Calculator
- Enter rent, utilities, roommate count.
- Choose split mode (equal/custom rent split).
- Dynamically generate custom split inputs.
- Enter income, housing threshold %, food/travel/other expenses, savings goal.
- Generate budget report with:
  - monthly share,
  - total outflow,
  - rent-to-income health check,
  - total income usage,
  - remaining buffer,
  - custom split breakdown.

#### Messages
- View conversation list (mock threads).
- Select conversation and read message history.
- Send a new message into active thread (local in-memory update).
- Mobile-first conversation/chat pane switching.

### 4) Standalone Smart Matcher (`smart_match.html`)
- Analyze affordability for a sample 3BHK listing.
- Control utilities with slider and room type switch.
- Input monthly income and get:
  - calculated monthly share,
  - utility/rent split breakdown,
  - income utilization,
  - match quality status (excellent/manageable/risky).

### 5) Standalone Cost Splitter (`calculator.html`)
- Toggle visual theme.
- Enter rent, utilities, roommates, and split mode.
- Support custom per-person split entries.
- Add income, target rent percentage, lifestyle costs, and savings goal.
- Compute split amount, monthly outflow, and budget health indicators.
- Show alerts for invalid custom split input.

### 6) Host Dashboard Prototype (`host.html` + `host.css`)
- Navigate host-oriented sidebar sections (Dashboard, Properties, Seekers, Messages, Settings).
- View host welcome area and top-level property snapshot.
- See occupancy and expected monthly revenue indicators.
- Access quick actions (add property, manage rooms, view seekers).
- Review incoming seeker requests and action buttons (view/accept).
- View summary overview cards for interest and availability.

### 7) Messaging Widget Prototype (`chat.html`)
- Open/close floating chat popup.
- Enter name, phone, and message in a compact widget.
- Client-side phone pattern validation.
- Submit message with simulated loading and confirmation alert.

### 8) Advanced Expense Manager Demo (`demo/calculator.html`)
- Multi-page mini-app with modules:
  - Dashboard,
  - Smart Splitter,
  - Expenses,
  - Roommates,
  - Settlements,
  - Budget Planner.
- Track expenses by category and payer.
- Add/remove roommates and expenses.
- Auto-calculate per-person share and spending breakdown charts.
- Generate settlement suggestions (who pays whom).
- Save and reuse data via localStorage.
- Budget target + health scoring + recommendation summaries.

## Current Scope Limitations

- No real backend/API integration.
- No persistent server database (except browser local/session storage in prototype flows).
- Messaging, auth, and property data are simulated/mock.

---

If needed, this README can be extended into:
- a user-flow document,
- a feature-to-screen matrix,
- or a backend-integration roadmap.
