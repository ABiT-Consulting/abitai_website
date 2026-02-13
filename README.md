# abit.ai website

## Deployment (cPanel)

This repo is deployed via cPanel Git Version Control:

- A push to the deploy branch triggers the GitHub Actions workflow `Deploy to cPanel`
- The workflow calls cPanel UAPI to pull/update the repo and run a deployment
- The deployment steps are defined in `.cpanel.yml` and deploy to `DEPLOYPATH="${HOME}/public_html/"`

### Required GitHub Actions secrets

- `CPANEL_HOST` (hostname only, e.g. `example.com`)
- `CPANEL_USER` (cPanel username)
- `CPANEL_TOKEN` (cPanel API token)
- `CPANEL_REPO_ROOT` (absolute path to the cPanel Git repo root)

Optional:

- `CPANEL_PORT` (defaults to `2083`)
- `CPANEL_BRANCH` (defaults to `main`, otherwise uses the pushed branch name)
- `CPANEL_INSECURE` (`true` only if you have TLS problems)

### Build output policy (only if you deploy `dist/`)

If your deployment copies a static build from `dist/`, then `dist/` must be committed:

1. Make changes
2. Run `npm run build`
3. Commit the updated `dist/`
4. Push to `main`

