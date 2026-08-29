# Contributing

This project is in prototype stage, so contributions should keep the demo clear, stable, and easy to explain.

## Development Principles

- Keep the primary flow simple: landing page, auth page, dashboard.
- Prefer small, focused changes.
- Avoid adding build tooling unless the project is ready to move beyond static HTML.
- Keep mock data easy to find and edit.
- Clearly separate demo behavior from production behavior.
- Test changes in both desktop and mobile viewport sizes.

## File Guidelines

- Use `index.html` for the public landing page.
- Use `login.html` for authentication UI.
- Use `dashboard.html` for the main renter product experience.
- Use `host.html` and `host.css` for host-facing ideas.
- Use `calculator.html`, `smart_match.html`, and `demo/calculator.html` for experiments unless they are merged into the dashboard.
- Place reusable CSS in `assets/css/`.
- Place reusable JavaScript in `assets/js/`.
- Place local images in `assets/img/`.

## Naming Guidelines

The product name is currently documented as `Thikana`.

Some prototype files still use names such as `RoomMate` and `RoomMate Pro`. When polishing the product, standardize these labels unless a separate module intentionally uses a different name.

## Manual Test Checklist

Before submitting changes:

1. Open `index.html`.
2. Check navigation links and responsive menu behavior.
3. Open `login.html`.
4. Test login, signup, password toggle, and validation messages.
5. Open `dashboard.html`.
6. Test each sidebar and mobile nav page.
7. Use listing search and filters.
8. Run smart match with low, medium, and high income values.
9. Run calculator with equal and custom split modes.
10. Open messages and send a sample message if composer logic is present.
11. Check the browser console for errors.

## Documentation Updates

Update docs whenever a feature changes:

- Update `README.md` for major project or demo-flow changes.
- Update `docs/FEATURES.md` when adding, removing, or changing screens.
- Update `docs/SETUP.md` when setup or deployment changes.
- Update `docs/ROADMAP.md` when product direction changes.

## Future Refactor Suggestions

When the prototype grows, consider:

- Extracting shared styles into common CSS.
- Extracting repeated JavaScript utilities.
- Creating a single app shell for renter pages.
- Moving listing, user, and message data into JSON fixtures.
- Adding a real frontend framework only when component reuse becomes painful.

