# Thikana

Thikana is a static web prototype for affordable renting, roommate discovery, and shared living budget planning. It helps a renter explore nearby rooms, estimate whether a place fits their monthly income, split costs with roommates, and start conversations with hosts.

This project is currently a hackathon/prototype frontend. The screens are realistic enough to demonstrate the product experience, while authentication, listing data, messages, and calculations are handled in browser-side mock logic.

## Problem

Students and early-career renters often make housing decisions with incomplete information:

- The listed rent does not show the true monthly cost after utilities, food, travel, and deposits.
- Roommate sharing can make a place affordable, but the actual split is hard to compare quickly.
- Hosts, listings, budget planning, and roommate conversations are usually spread across different apps.
- Renters need a simple way to know: "Can I actually afford this place?"

## Solution

Thikana combines property discovery, rent affordability checks, roommate splitting, and messaging into one dashboard-oriented prototype.

The idea is simple: a renter should be able to search for a room, check the expected personal share, compare that share with income, and message the host or roommates before committing.

## Current Features

- Landing page for the Thikana concept and user journey.
- Login and signup prototype with client-side validation and tab switching.
- Renter dashboard with navigation for home, explore, smart match, calculator, and messages.
- Listing cards with budget, room type, area, distance, perks, and match percentage.
- Search and filter controls for exploring sample listings.
- Smart property matcher that compares rent, utilities, room type, roommates, and income.
- Split and budget calculator with equal and custom split modes.
- Budget health score based on housing share, lifestyle expenses, savings goal, and income.
- In-dashboard messaging mockup for host conversations.
- Standalone experimental pages for cost splitting, property matching, chat widget, and host dashboard.
- GitHub Actions workflow for FTP deployment to InfinityFree.

## Demo Pages

Open these files directly in a browser:

| Page | Purpose |
| --- | --- |
| `index.html` | Main landing page |
| `login.html` | Login and signup prototype |
| `dashboard.html` | Main renter dashboard prototype |
| `calculator.html` | Standalone rent split and affordability calculator |
| `smart_match.html` | Standalone smart property matcher |
| `host.html` | Host/property owner dashboard concept |
| `chat.html` | Floating chat/contact widget experiment |
| `demo/calculator.html` | Extended roommate expense manager concept |

The recommended demo flow is:

1. Start at `index.html`.
2. Open `login.html` from the landing page.
3. Use `dashboard.html` to show the complete renter experience.
4. Use `calculator.html`, `smart_match.html`, `host.html`, and `demo/calculator.html` as feature prototypes for future modules.

## Tech Stack

- HTML5
- CSS3
- Vanilla JavaScript
- Tailwind CSS CDN on the landing page
- Google Fonts
- Unsplash image URLs for prototype visuals
- GitHub Actions FTP deployment

There is no build step, package manager, backend, database, or framework dependency in the current prototype.

## Project Structure

```text
.
|-- index.html                 # Landing page
|-- login.html                 # Auth prototype
|-- dashboard.html             # Main renter dashboard
|-- calculator.html            # Standalone affordability calculator
|-- smart_match.html           # Standalone property matcher
|-- host.html                  # Host dashboard concept
|-- host.css                   # Styles for host.html
|-- chat.html                  # Floating chat widget prototype
|-- gemni.html                 # Placeholder/empty experiment file
|-- assets/
|   |-- css/login.css          # Login-related CSS asset
|   |-- js/login.js            # Login/signup behavior
|   `-- img/                  # Local visual assets
|-- demo/
|   `-- calculator.html        # Extended expense manager prototype
|-- docs/
|   |-- ARCHITECTURE.md        # Current frontend architecture
|   |-- FEATURES.md            # Detailed feature specification
|   |-- SETUP.md               # Local run and deployment guide
|   |-- ROADMAP.md             # Real-world product roadmap
|   `-- CONTRIBUTING.md        # Contribution and coding notes
`-- .github/workflows/deploy.yml
```

## Run Locally

Because this is a static frontend, you can open any HTML file directly in a browser.

For a cleaner local server:

```bash
python -m http.server 8000
```

Then visit:

```text
http://localhost:8000
```

See [docs/SETUP.md](docs/SETUP.md) for more setup and deployment details.

For a technical overview of the current static architecture, see [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Prototype Data

The dashboard uses hardcoded sample data for listings, conversations, match scores, and calculations. Some standalone pages use browser storage for demo state. This is intentional for a fast frontend prototype and should be replaced with API-backed data in a production version.

## Real-World Feature Potential

This prototype can grow into a full rental platform with:

- Verified property listings.
- Roommate profiles and compatibility matching.
- Rent affordability scoring.
- Expense tracking and settlements.
- Host dashboards and lead management.
- In-app chat and visit scheduling.
- Saved searches and listing alerts.
- Payment, deposit, and agreement workflows.

See [docs/ROADMAP.md](docs/ROADMAP.md) for the suggested production roadmap.

## Deployment

The repository includes `.github/workflows/deploy.yml`, which uploads the static site to InfinityFree using FTP credentials stored as GitHub repository secrets.

Required secrets:

- `FTP_SERVER`
- `FTP_USERNAME`
- `FTP_PASSWORD`
- `FTP_PORT`

## Status

Prototype stage. Good for demos, user testing, hackathon judging, and frontend iteration. Not production-ready until backend services, authentication, data persistence, security controls, and listing verification are added.
