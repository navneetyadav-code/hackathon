# Setup And Deployment

Thikana is currently a static HTML/CSS/JavaScript project. It does not require Node.js, npm, a bundler, or a database to run the current prototype.

## Requirements

Required:

- A modern browser such as Chrome, Edge, Firefox, or Safari.

Optional:

- Python 3, if you want to serve the project locally over HTTP.
- Git, if you want to clone, version, and deploy from GitHub.

## Run By Opening Files

Open any HTML file directly in your browser:

- `index.html`
- `login.html`
- `dashboard.html`
- `calculator.html`
- `smart_match.html`
- `host.html`
- `chat.html`
- `demo/calculator.html`

The strongest demo path is:

```text
index.html -> login.html -> dashboard.html
```

## Run With A Local Server

From the repository root:

```bash
python -m http.server 8000
```

Then open:

```text
http://localhost:8000
```

This is useful because browser behavior is closer to deployment when files are served over HTTP.

## External Assets

The prototype uses external resources:

- Google Fonts.
- Tailwind CSS CDN on `index.html`.
- Unsplash image URLs.
- RandomUser image URLs in `smart_match.html`.

If the network is unavailable, layout still loads, but some fonts/images/CDN styles may not render as intended.

## Deployment Workflow

The repository includes:

```text
.github/workflows/deploy.yml
```

It deploys the static site to InfinityFree over FTP whenever code is pushed to the `main` branch.

Required GitHub repository secrets:

```text
FTP_SERVER
FTP_USERNAME
FTP_PASSWORD
FTP_PORT
```

The workflow uploads files to:

```text
/hackathon.navneetyadav.me/htdocs/
```

## Deployment Checklist

Before deploying:

1. Open `index.html`, `login.html`, and `dashboard.html` locally.
2. Check mobile layout using browser responsive tools.
3. Confirm all links point to the correct local pages.
4. Confirm external images and fonts load.
5. Confirm GitHub secrets are configured.
6. Push to `main`.
7. Check the GitHub Actions run.
8. Visit the deployed domain and test the same demo flow.

## Common Issues

### Landing page styles do not load

`index.html` uses Tailwind through the CDN. Check internet connectivity and browser console errors.

### Images do not appear

Some visuals are loaded from external image URLs. If they fail, the source service or internet connection may be unavailable.

### Login does not really authenticate

This is expected. Login and signup are mocked in frontend JavaScript.

### Dashboard data resets

Most dashboard data is hardcoded. The extended demo calculator uses local browser storage, but there is no shared database yet.

