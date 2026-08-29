# Feature Specification

This document explains what the current prototype does, what each feature demonstrates, and how each area can become a real product feature.

## 1. Landing Page

File: `index.html`

The landing page introduces Thikana as a rent affordability and roommate finder. It includes navigation, product explanation sections, feature highlights, and calls to action that lead users toward the login/signup flow.

Prototype behavior:

- Static marketing and product education content.
- Responsive navigation.
- Links to `login.html`.
- Tailwind CDN styling with custom theme values.

Production version:

- Add analytics for CTA clicks and visitor behavior.
- Add SEO metadata, Open Graph images, and structured data.
- Connect contact or waitlist forms to a backend.
- Add city-specific landing pages for search visibility.

## 2. Authentication Prototype

File: `login.html`

The auth screen demonstrates login and signup states for renters. It includes a visual property/roommate hero area and a clean form panel.

Prototype behavior:

- Login/signup tab switching.
- Password visibility toggles.
- Client-side signup password confirmation.
- Mock async login/signup response.
- Success and error alerts.

Production version:

- Connect to secure authentication using email/password, phone OTP, or OAuth.
- Store sessions with secure cookies or token handling.
- Add forgot password and account verification flows.
- Support renter and host role selection.
- Validate and sanitize all submitted data server-side.

## 3. Renter Dashboard

File: `dashboard.html`

The dashboard is the main product experience. It brings discovery, matching, affordability, and messaging into one interface.

Prototype behavior:

- Sidebar navigation and mobile bottom navigation.
- Home overview with quick actions.
- Highlighted sample listings.
- Feature shortcuts for exploring listings, smart matching, and budget calculation.
- Static user identity and location labels.

Production version:

- Pull authenticated user profile from backend.
- Detect or select preferred city/locality.
- Show personalized listings based on budget, commute, and preferences.
- Add saved listings, recently viewed listings, and notifications.

## 4. Listing Discovery

File: `dashboard.html`

The explore page helps users search sample properties by area, budget, and room type.

Prototype behavior:

- Hardcoded listing dataset.
- Text search across name, area, room, and perk.
- Budget filter.
- Shared/private room filter.
- Listing cards with match badges.

Production version:

- Use database-backed listings.
- Add filters for city, locality, gender preference, furnishing, amenities, deposit, food, commute, and availability.
- Add map view and commute-time search.
- Add verified badges and host/property trust signals.
- Add image galleries and listing detail pages.

## 5. Smart Property Match

Files: `dashboard.html`, `smart_match.html`

The smart matcher estimates whether a selected property is affordable for the user.

Prototype behavior:

- Base rent and utility slider.
- Standard room versus master bedroom mode.
- Income input.
- Personal share calculation.
- Percentage of income used.
- Visual affordability meter.
- Status message for strong, moderate, or risky match.

Production version:

- Include deposits, maintenance, food, commute, Wi-Fi, electricity, and one-time move-in costs.
- Support actual roommate count and room assignment.
- Use user-specific preferences and financial comfort limits.
- Generate explainable match reasons.
- Save match reports per listing.

## 6. Split And Budget Calculator

Files: `dashboard.html`, `calculator.html`

The calculator helps renters estimate their share of rent and utilities, then compare that monthly housing cost against income and other expenses.

Prototype behavior:

- Equal split mode.
- Custom split mode.
- Monthly income input.
- Housing percentage target.
- Lifestyle expenses and savings goal inputs.
- Budget health score.
- Success, warning, and risk states.

Production version:

- Persist calculator scenarios.
- Compare multiple properties side-by-side.
- Suggest maximum affordable rent.
- Add roommate income-aware split options.
- Export or share split summaries with roommates.

## 7. Messaging

Files: `dashboard.html`, `chat.html`

Messaging is represented in two ways: an integrated dashboard conversation view and a standalone floating chat widget.

Prototype behavior:

- Static conversation list.
- Static message thread rendering.
- Message composer in dashboard.
- Floating contact/message form in standalone chat page.

Production version:

- Add real-time chat using WebSocket or a managed messaging service.
- Support attachments, property references, scheduling, and host response time.
- Add moderation, spam detection, and reporting.
- Notify users through email, SMS, push, or WhatsApp integrations.

## 8. Host Dashboard Concept

Files: `host.html`, `host.css`

The host page demonstrates how property owners could manage listings and tenant leads.

Prototype behavior:

- Static sidebar and host profile.
- Property owner dashboard layout.
- Add property call to action.
- Placeholder navigation for properties, seekers, messages, and settings.

Production version:

- Host onboarding and verification.
- Listing creation and editing.
- Lead inbox and applicant management.
- Visit scheduling.
- Listing performance analytics.
- Trust and safety checks.

## 9. Expense Manager Concept

File: `demo/calculator.html`

This page is a more complete roommate expense manager experiment.

Prototype behavior:

- Roommate management.
- Expenses by category.
- Equal settlements.
- Budget planner.
- Local browser storage.
- Dashboard stats and charts.

Production version:

- Shared household groups.
- Receipt upload and OCR.
- Expense approvals.
- Automated settlement reminders.
- UPI/payment link integrations.
- Monthly reports.

## Current Limitations

- No backend or database.
- No real login sessions.
- No real listing API.
- No data verification.
- No production security controls.
- Some pages are independent experiments rather than one unified app shell.
- External fonts and images require internet access.
- `gemni.html` is currently empty and should either be implemented or removed.

