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
      build_command: |
        composer install --no-dev --prefer-dist
        bin/magento setup:di:compile
        composer dump-autoload --optimize
        bin/magento setup:static-content:deploy -f
        # TODO: generate opcache preload file once the script is finalized.
    secrets:
      COMPOSER_AUTH: ${{ secrets.COMPOSER_AUTH }}
```

## Storage Notes

- Artifacts are uploaded to `s3://<backup-bucket>/builds/<repo>/<sha>.tar.zst`.
- `mz-control` returns short-lived credentials scoped to `builds/`.
- `mz-control` also returns the PHP version for the environment.
