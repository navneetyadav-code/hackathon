# Architecture

Thikana is currently a static frontend prototype. The application is built from standalone HTML files with inline CSS and JavaScript, plus a small `assets/` directory for shared resources.

## Current Architecture

```text
Browser
  |
  |-- Static HTML pages
  |-- Inline CSS and page-level styles
  |-- Vanilla JavaScript event handlers
  |-- Hardcoded demo data
  |-- Optional browser localStorage in demo pages
  |
Static hosting / FTP deployment
```

There is no backend service, database, API gateway, authentication server, or build pipeline yet.

## Page Responsibilities

| File | Responsibility |
| --- | --- |
| `index.html` | Public landing page and product explanation |
| `login.html` | Auth UI prototype with login/signup states |
| `dashboard.html` | Main renter dashboard and combined product demo |
| `calculator.html` | Standalone affordability calculator |
| `smart_match.html` | Standalone property match calculator |
| `host.html` | Host dashboard concept |
| `chat.html` | Floating chat widget concept |
| `demo/calculator.html` | Expanded roommate expense manager prototype |

## Data Flow

The current data flow is intentionally simple:

1. A user opens an HTML page.
2. The page loads static markup, styles, and JavaScript.
3. JavaScript reads user input from form fields.
4. Calculations run directly in the browser.
5. Results are written back into the DOM.

Sample property listings, conversations, and match data are hardcoded in JavaScript. The extended demo calculator stores some state in browser `localStorage`.

## State Management

Current state types:

- Form input state in DOM fields.
- Active tab/page state through CSS classes.
- Hardcoded arrays for listings and conversations.
- `localStorage` for the extended expense manager demo.

Production state should move to:

- Backend database for durable records.
- Authenticated user sessions.
- API responses for listings, matches, messages, and host data.
- Client cache for fast UI interactions.

## Routing

Routing is currently file-based:

- `index.html`
- `login.html`
- `dashboard.html`
- Other standalone prototype pages

Inside `dashboard.html`, JavaScript switches between page sections by adding and removing `active` classes.

Production routing options:

- Keep multi-page static routing for a simple MVP.
- Move to a single-page app if dashboard complexity grows.
- Move to Next.js or another full-stack framework for SEO pages, authenticated dashboards, and API routes.

## Deployment

The app can be deployed as static files. The included GitHub Actions workflow deploys over FTP to InfinityFree on pushes to `main`.

Current deployment path:

```text
GitHub main branch -> GitHub Actions -> FTP upload -> Static hosting
```

## Suggested Production Architecture

```text
Client app
  |
Backend API
  |
  |-- Auth service
  |-- Listings service
  |-- Matching service
  |-- Messaging service
  |-- Expense/split service
  |
PostgreSQL database
Object storage for listing images
Realtime channel for chat
Notification provider
Admin/moderation dashboard
```

## Main Refactor Priorities

1. Unify naming and product identity.
2. Extract repeated CSS and JavaScript.
3. Move hardcoded data into JSON fixtures.
4. Merge successful standalone experiments into the main dashboard.
5. Add backend APIs and persistent storage.
6. Add real authentication and authorization.

