# Releasing

Creating a GitHub release whose tag is `v{version}` publishes that version to WordPress.org. Everything else is
automatic.

## Choose the version

[Semantic versioning](https://semver.org/):

- **patch** (`1.2.1`): fixes only;
- **minor** (`1.3.0`): new features, nothing broken;
- **major** (`2.0.0`): something a site owner must act on, such as new class names their CSS relied on.

## Prepare it in a pull request

On a branch (for example `chore/release-1.3.0`):

1. Set the version in all three places. The release fails if they differ from the tag.
   - `ultimate-crypto-widget.php`: ` * Version:` in the header.
   - `package.json`: `"version"`. Run `npm install --package-lock-only` so `package-lock.json` matches.
   - `readme.txt`: `Stable tag:`.
2. In `readme.txt`:
   - add a `= {version} =` entry at the top of **Changelog**, written for site owners;
   - add a short **Upgrade Notice** for it (at most 300 characters; WordPress shows it beside "update now");
   - check `Tested up to:` names the latest major WordPress version (for example `7.1`), and test on it first.
3. Run `composer make-pot` if any text changed, and the checks in [Development](development.md).
4. Open the pull request, wait for CI, merge.

## Release it

1. On GitHub: **Releases → Draft a new release**.
2. **Tag:** `v{version}` (for example `v1.3.0`), created on publish, **target `main`**.
3. **Title** and **notes**: what changed, for site owners. The changelog entry is a good start.
4. **Publish release.**

The **Build, Package, and Deploy on Release** workflow then:

1. checks the tag matches the version in the header, `package.json` and the stable tag;
2. builds the scripts, checks the code, makes the translation template, builds and verifies the zip;
3. attaches `ultimate-crypto-widget-v{version}.zip` to the release;
4. commits the zip's contents to WordPress.org's SVN `trunk/`, copies it to `tags/{version}/`, and updates the banner and
   icon (`assets/images/banner-*`, `icon-*`).

Within a few minutes the plugin's page on WordPress.org shows the new version, and sites see the update. Check it at
`https://wordpress.org/plugins/ultimate-crypto-widget/`.

## The WordPress.org login

The workflow signs in to SVN with two repository secrets (**Settings → Secrets and variables → Actions**):

| Secret | Value |
| --- | --- |
| `SVN_USERNAME` | The WordPress.org **username** of a committer on the plugin (case-sensitive; not the email address). |
| `SVN_PASSWORD` | That account's **SVN password**, generated on WordPress.org under **Edit profile → Account & Security → Subversion password**. The normal login password does not work. |

Committers are listed at `https://wordpress.org/plugins/ultimate-crypto-widget/advanced/` under **Committers**; an
existing committer can add a new one there. Repository secrets belong to the repository, not to whoever publishes the
release.

When handing the project over, add the new maintainer as a committer and replace both secrets with their own login.

## When a release fails

Open the failed run (**Actions** tab) and find the red step.

| Failed step | Cause | Fix |
| --- | --- | --- |
| Check that every version matches the tag | The header, `package.json` or stable tag differs from the tag. | Fix the version on `main`, then re-create the release (below). |
| Upload Free version to release | The zip is already attached to the release, from an earlier attempt. | Delete that zip from the release, then **Re-run failed jobs**. |
| Deploy to WordPress.org: `Authentication failed` | The SVN secrets are wrong or out of date. | Update both secrets, then delete the attached zip (as above) and **Re-run failed jobs**. |
| Deploy to WordPress.org: `svn: command not found` | The runner image lacks Subversion. | The workflow installs it; check the "Install Subversion" step is still in `release.yml` on the tagged commit. |
| Deploy to WordPress.org: `tag … already exists` | That version is already on WordPress.org. | Release a new version number; a published WordPress.org tag is never replaced. |

**Re-run or re-create?** A re-run uses the workflow file *from the tagged commit*. If the fix is in the workflow or the
code, a re-run will fail the same way: delete the release and its tag (**Releases → Tags**), then create the release
again from `main`. This is safe as long as nothing reached WordPress.org (the run stopped before "Committed revision").

## After a release

- Check the plugin's page shows the new version, and install it on a test site from WordPress.org.
- Watch the [support forum](https://wordpress.org/support/plugin/ultimate-crypto-widget/) for a few days.
- When Pro is sold (tiered edition), release Pro at the matching version after this plugin; see the Pro repository's docs.
