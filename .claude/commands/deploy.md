# Deploy

Deploys the current `main` branch to staging or production by opening a PR to the
target release branch. Merging the PR triggers the GitHub Actions deploy automatically.

`workflow_dispatch` is available as a fallback for emergency or out-of-band deploys
(see bottom of this file).

## Usage
```
/deploy staging
/deploy production
```

## Standard flow (branch release)

1. **Determine target** from $ARGUMENTS. If not provided or ambiguous, ask: staging or production?

2. **Pre-flight checks** — run in parallel:
   - `git fetch origin && git log origin/main..HEAD` — warn if local main is ahead of remote (unpushed commits won't be in the deploy)
   - `git log origin/<target>..origin/main --oneline` — show what commits will be deployed (what's on main but not yet on the target branch)
   - `gh pr list --base <target> --state open` — warn if a release PR is already open for that target

3. **Production gate** — if target is `production`, ask: "Have you validated this on staging?" and show the latest `main` commit (hash + message). Do not proceed until the user confirms.

4. **Open the PR**:
   ```
   gh pr create \
     --base <target-branch> \
     --head main \
     --title "release: deploy main to <target> — $(date +%Y-%m-%d)" \
     --body "Deploying main to <target>. Merging this PR triggers the GitHub Actions deploy."
   ```
   Present the PR URL to the user.

5. **Merging** — the user merges the PR. Merging triggers the deploy workflow automatically via the `push` event. Claude does not merge release PRs unless explicitly asked.

6. **After merge** — optionally watch the workflow run with `gh run list --workflow=deploy-<target>.yaml --limit=1` and report status when done.

## Environment branches and what they mean
- `staging` branch = what is currently live on `staging.wikitongues.org`
- `production` branch = what is currently live on `wikitongues.org`
- Both branches always track `main` — never deploy from a feature branch

## Fallback: manual workflow dispatch
For emergencies or when branch management isn't appropriate:
```
gh workflow run deploy-staging.yaml --ref main
gh workflow run deploy-production.yml --ref main
```
This deploys immediately without a PR, but leaves the release branch stale.

## Notes
- Both environment branches have push protection — always go through a PR, never push directly
- A successful staging deploy health-checks `https://staging.wikitongues.org`
- A successful production deploy health-checks `https://wikitongues.org`
- The deploy workflow file names: `deploy-staging.yaml` and `deploy-production.yml`
