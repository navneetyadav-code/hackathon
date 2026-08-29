# Product Roadmap

This roadmap turns the current Thikana prototype into a real rental and roommate platform.

## Phase 1: Prototype Cleanup

Goal: Make the current demo consistent and easy to present.

- Keep `index.html`, `login.html`, and `dashboard.html` as the primary flow.
- Decide whether standalone pages should be linked as experiments or merged into the dashboard.
- Remove or implement `gemni.html`.
- Standardize naming between Thikana, RoomMate, and RoomMate Pro.
- Move repeated CSS and JavaScript into shared assets.
- Replace emoji-style controls with a consistent icon system.
- Add basic accessibility checks for labels, focus states, and keyboard navigation.

## Phase 2: Backend Foundation

Goal: Replace mock data with real persistence.

- Add a backend API.
- Add database tables for users, hosts, listings, rooms, messages, matches, and saved properties.
- Implement secure authentication.
- Add role-based accounts for renters and hosts.
- Store user preferences such as budget, city, commute needs, gender preference, food preference, and move-in date.
- Add server-side validation and error handling.

## Phase 3: Real Listing Platform

Goal: Make property discovery useful in real life.

- Add host listing creation.
- Support listing images, rent, deposit, maintenance, utilities, food availability, furnishing, house rules, and room types.
- Add listing verification status.
- Add area and commute filters.
- Add map search.
- Add saved listings and watchlists.
- Add availability dates and visit scheduling.

## Phase 4: Smart Matching

Goal: Make affordability and compatibility the product advantage.

- Build an affordability model using rent, utilities, food, commute, deposits, savings, and income.
- Add roommate compatibility based on lifestyle, budget, cleanliness, work/study hours, food preference, and stay duration.
- Explain why a property is a strong or weak match.
- Compare multiple listings side-by-side.
- Recommend maximum affordable rent.
- Add risk warnings when total housing cost crosses user-defined limits.

## Phase 5: Messaging And Workflow

Goal: Help renters move from interest to decision.

- Add real-time renter-host messaging.
- Add property-specific chat threads.
- Add roommate group conversations.
- Add visit scheduling.
- Add notifications for replies, saved search matches, and price changes.
- Add abuse reporting and moderation.

## Phase 6: Payments And Settlements

Goal: Support shared living after move-in.

- Create household groups.
- Track rent, utilities, groceries, Wi-Fi, maintenance, and one-time expenses.
- Calculate who owes whom.
- Add payment links or UPI integration.
- Add monthly reports.
- Add reminders for unpaid balances.

## Phase 7: Trust, Safety, And Scale

Goal: Prepare for production users.

- Verify hosts and properties.
- Add identity verification options.
- Add listing fraud detection.
- Add privacy controls for renter profiles.
- Add admin dashboards for support and moderation.
- Add audit logs.
- Add backups and monitoring.

## Suggested Production Stack

One practical path:

- Frontend: React or Next.js.
- Styling: Tailwind CSS or a component system.
- Backend: Node.js/Express, NestJS, Django, or FastAPI.
- Database: PostgreSQL.
- Storage: S3-compatible object storage for listing images.
- Auth: Auth.js, Firebase Auth, Supabase Auth, Clerk, or custom secure auth.
- Realtime: WebSockets, Supabase Realtime, Firebase, or Pusher.
- Maps: Google Maps, Mapbox, or OpenStreetMap.
- Deployment: Vercel/Netlify for frontend and Render/Fly.io/Railway/AWS for backend.

## Success Metrics

- Search-to-listing-view conversion.
- Listing-view-to-message conversion.
- Average affordability score for contacted listings.
- Number of saved properties per user.
- Host response rate.
- Match acceptance rate.
- Time from search to scheduled visit.
- Number of households using expense splitting after moving in.

