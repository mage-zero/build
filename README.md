# MageZero Build Workflows

This repository hosts reusable GitHub Actions workflows for building and
deploying Magento stacks. The workflows are public, but **secrets stay in the
customer repo** (GitHub does not expose secrets to the workflow source).

## Build Artifact Model

Customer images are produced **inside the stack**, but the CI pipeline runs on
GitHub-hosted runners. The workflow builds a **compressed source artifact**
and uploads it to the customer R2 backup bucket under `builds/`. A stack-side
deployment service pulls the artifact, assembles the final image, and deploys
it with zero-downtime logic.

## Zero-Secret Model (Preferred)

The pipeline runs with **no customer secrets**. GitHub OIDC is used to
authenticate to `mz-control`, which returns short-lived upload credentials and
the deploy trigger endpoint.

Inputs required:
- `mz_control_url` (e.g. `https://mz-control.magezero.com`)

Optional:
- `COMPOSER_AUTH` (only if you need private Composer repos)
- `extra_build_command` (project-specific build tweaks; runs before the default build)

## Optional Stack Runtime Metadata

Customer repos can also include `.magezero/build.yaml` inside the build
artifact when they need to extend stack-side deployment behavior without
forking the platform build workflow.

Current supported keys:

```yaml
magezero:
  k3s:
    php:
      writable_paths:
        - /var/www/html/magento/var/import
        - path: /var/www/html/magento/pub/media/custom
          size_limit: 32Mi
```

Notes:
- Paths must be narrow absolute paths, not broad mounts.
- Broad writable roots like `/var/www/html/magento/var`, `app/etc`, or whole
  `pub/media` are rejected by the stack deployer.
- These entries extend the default transient writable allowlist for PHP pods;
  they do not create persistent shared storage.

## Example Usage (Customer Repo)

```yaml
name: Build + Deploy

on:
  push:
    branches: [ main ]

jobs:
  build:
    uses: mage-zero/build/.github/workflows/magezero-build.yml@main
    with:
      image_tag: ${{ github.sha }}
      mz_control_url: https://mz-control.magezero.com
      extra_build_command: |
        # Optional project-specific build tweaks.
        # Runs before the default MageZero build steps.
    secrets:
      COMPOSER_AUTH: ${{ secrets.COMPOSER_AUTH }}
```

Legacy:
- `build_command` is still supported but overrides the default MageZero build (not recommended).

## Storage Notes

- Artifacts are uploaded to `s3://<backup-bucket>/builds/<repo>/<sha>.tar.zst`.
- `mz-control` returns short-lived credentials scoped to `builds/`.
- `mz-control` also returns the PHP version for the environment.
