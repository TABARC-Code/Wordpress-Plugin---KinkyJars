# Kink Jars for WordPress

An adults-only kink questionnaire, as a WordPress plugin. Visitors pick a colour (or a pride palette), fill 35 jars from 1 to 5, cross out the hard no's, and save the result as a poster. They can also swap a short code with a partner and see only the jars they're both into.

Install it, press one button, publish. Nothing about your visitors is stored on your site.

![The Kink Jars app inside a WordPress page](docs/images/7-visitor-page.jpg)

**New to all this? Read [INSTALL.md](INSTALL.md).** It's the step-by-step version with screenshots, written for people who've never uploaded a plugin before.

## Quick start

1. Download **[kink-jars.zip](https://github.com/TABARC-Code/Wordpress-Plugin---KinkyJars/releases/latest/download/kink-jars.zip)**. That link always points at the newest release.
2. **Plugins > Add Plugin > Upload Plugin**, choose the zip, **Install Now**, **Activate Plugin**.
3. **Settings > Kink Jars > Create my Kink Jars page**, then **Preview page**, then **Publish it now**.

Use that zip, not GitHub's green Code button or the "Source code" downloads. WordPress refuses those with "No valid plugins were found." The test suite checks that, so it isn't just me being fussy.

## What visitors get

Everything from the [Kink Jars app](https://github.com/TABARC-Code/kinky-jars) (version 1.5.0 is bundled), unchanged:

- An 18+ confirmation on their first visit. It's a self-declaration, not age verification, and the app says so.
- 35 jars, each rated 1 to 5 or marked **Not for me**.
- A colour picker, plus 11 pride palettes (Rainbow, Progress, Trans, Bi, Pan, Ace, Non-binary, Lesbian, Intersex, Genderqueer and Aromantic).
- A poster saved as a PNG, or shared straight from a phone where the browser allows it.
- **Compare with someone**: swap codes, see the matches. Jars on only one list are counted, never named.
- An easy-read font option (Atkinson Hyperlegible, with looser spacing).

## What you get

A setup screen at **Settings > Kink Jars**. It makes the page for you, as a draft, and gives you Preview, Edit and **Publish it now** buttons. It's a draft first on purpose: adult content shouldn't go live on anyone's site before they've looked at it.

If you'd rather place it yourself, the shortcode goes in any page or post. In the block editor, use a Shortcode block:

```
[kink_jars align="wide"]
```

| Option | Default | What it does |
| --- | --- | --- |
| `align` | none | `wide` or `full` uses your theme's own wide or full-width alignment. Most themes keep content in a narrow column, which pushes the app into its phone layout on a desktop. |
| `height` | `90vh` | Height of the box: px from 100 to 9999, or vh from 10 to 999. Anything else quietly falls back to 90vh. |
| `title` | Kink Jars questionnaire | What screen readers announce for the embedded app. |

## Requirements

- WordPress 5.8 or newer and PHP 7.4 or newer. Those are the declared minimums. The only version actually tested is WordPress 7.1.3 (via WordPress Playground), so older ones are a reasonable expectation rather than a promise.
- Permission to upload plugins. Self-hosted WordPress has it out of the box. WordPress.com only allows it on plans that include plugins.
- No other plugins, no accounts, no API keys.

## Privacy and security, plainly

- **Visitors.** Names and answers live in each visitor's own browser storage for your site. The plugin doesn't save them, log them, or send them anywhere, and the app makes no requests to other sites; its fonts are bundled.
- **The catch.** That storage belongs to your site's address. Any other script running on your site (analytics, chat widgets, other plugins) could technically read it, as it could anything else in browser storage. If you run third-party scripts you don't trust, that's worth knowing.
- **Your site.** The plugin saves two things: one option (`kink_jars_page_id`, the page it made) and a welcome-notice flag that expires within a day. Deleting the plugin removes both. It never deletes your page.
- **Admin actions.** Creating a page needs the `edit_pages` capability; publishing needs `publish_pages`. Both buttons are protected by WordPress nonces. Shortcode attributes are validated and escaped, and there are tests that throw nasty values at them.

## If something's wrong

The common ones, briefly. [INSTALL.md](INSTALL.md#if-something-goes-wrong) has the full table.

- **"No valid plugins were found."** Wrong zip. Use `kink-jars.zip` from Releases.
- **Blank box where the app should be.** Something is sending `X-Frame-Options: DENY` or a strict `frame-ancestors` policy. SAMEORIGIN is fine; DENY blocks the iframe this plugin relies on.
- **Squashed on desktop.** Add `align="wide"` to the shortcode.
- **Old version still showing after an update.** Clear your page cache. The iframe address includes the version number, so it's normally the page around it that's stale, not the app.

## Updating

There are no automatic updates, because it isn't on WordPress.org and isn't going to be. Download the new `kink-jars.zip` and upload it the same way; WordPress offers **Replace current with uploaded**. Your page stays put. Watch the repository's releases if you want to hear about new versions.

## For developers

```
kink-jars/            the plugin, exactly as it's installed
  kink-jars.php       header, constants, activation notice
  includes/           shortcode.php, admin.php (setup screen)
  app/                the bundled Kink Jars app and its fonts
  readme.txt          WordPress-format readme and changelog
scripts/
  build-zip.sh        makes dist/kink-jars.zip and dist/kink-jars-<version>.zip
  sync-app.sh         pulls the latest app from TABARC-Code/kinky-jars into kink-jars/app/
tests/
  unit.php            shortcode and helper checks against stand-in WordPress functions
  check-package.sh    PHP syntax, version agreement, zip layout
  e2e.cjs             real WordPress (Playground) + real Chromium, following INSTALL.md
```

Run the checks:

```
php tests/unit.php
bash tests/check-package.sh
npm install --no-save playwright && npx playwright install chromium
node tests/e2e.cjs dist/kink-jars.zip
```

`e2e.cjs` downloads WordPress Playground through `npx`, so the first run takes a minute or two. Set `SCREENSHOTS=docs/images` and it regenerates the pictures in INSTALL.md from the same run. GitHub Actions runs all three on every pull request.

**Releasing.** Bump the version in three places: the `Version:` header and `KINK_JARS_VERSION` in `kink-jars.php`, and `Stable tag` in `readme.txt`. Then add a changelog entry to `readme.txt`. `check-package.sh` refuses to pass if those four disagree, which is mildly annoying and entirely the point. Merge to `main` and the release workflow builds the zips and publishes `v<version>`, with the changelog as the release notes.

**New app version?** `bash scripts/sync-app.sh`, bump the plugin version, add a changelog line, done.

## Licence

None declared yet. The app this bundles doesn't declare one either. Until there is one, the default copyright rules apply. The bundled fonts have their own licences (OFL 1.1, plus Apache 2.0 for Permanent Marker), included in `kink-jars/app/fonts/licences/`.

Author: TABARC-Code
