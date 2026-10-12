# Description

## The short version

Kink Jars for WordPress wraps the standalone [Kink Jars](https://github.com/TABARC-Code/kinky-jars) questionnaire so a site owner can put it on their site without touching code. The app itself doesn't change. The plugin adds the bits a WordPress newcomer actually needs: a setup screen that makes the page for them, a draft-first publish step, sensible width handling, and a release zip WordPress will accept on the first try.

It's distributed from GitHub, deliberately. Adults can decide what goes on their own websites, and I'd rather spend the time on the plugin than on arguing about directory policy.

## Why it's shaped like this

**An iframe, not a script tag.** The app owns a whole page: a fixed save bar, two modal dialogues, its own fonts and its own body styles. Drop that straight into a theme and the two will fight, and themes are endlessly creative about how they fight. Inside an iframe neither can touch the other. Because the iframe is served from the same site, browser storage, downloads and the clipboard all work as they do on the standalone page. The cost is a scrolling box inside the page. That's why there's a height option, a "full screen" link under the app, and `align="wide|full"` for themes with narrow content columns.

**The app is copied in, not fetched.** `kink-jars/app/` is a straight copy of the app's `index.html` and fonts, refreshed by `scripts/sync-app.sh`. Loading it from GitHub at runtime would have been less to commit, but then every visitor would be talking to a third party, and the app's whole "makes no requests to other sites" promise would quietly die. A copy also means the site keeps working if GitHub has a bad day.

**Draft first.** "Create my page" makes a draft, never a live page. One extra click, but adult content shouldn't appear on someone's site because they pressed a button to see what it did. "Publish it now" is right there afterwards, and it needs `publish_pages` on top of `edit_pages`.

**Store nearly nothing.** The plugin keeps one option (the ID of the page it made, so the setup screen can offer Preview and Publish) and a welcome-notice transient that expires on its own. No tables, no visitor data, no cron. Uninstalling removes both and leaves the page alone: deleting someone's content uninvited is a good way to make enemies.

**Two version numbers.** The plugin has its own version (1.0.0); the bundled app has its own (1.5.0). They change for different reasons. The settings screen shows both, and the iframe address carries both, so a page cache can't serve an old app after an update.

## Architecture

- `kink-jars.php`: plugin header, constants, the activation hook that sets the welcome notice. Loads `includes/shortcode.php` everywhere and `includes/admin.php` only in the admin.
- `includes/shortcode.php`: `[kink_jars]`. Validates `height` against a whitelist pattern, `align` against `wide`/`full`, escapes everything it prints, and reads the app version out of `app/index.html`.
- `includes/admin.php`: Settings > Kink Jars, the "Set up" link on the Plugins screen, the welcome notice, and two `admin-post.php` handlers (create page, publish page), each with a capability check and a nonce.
- `uninstall.php`: removes the option and the transient.
- `app/`: the bundled questionnaire. Nothing in PHP reaches into it beyond reading the version string.

## Trust and boundaries

What crosses into the plugin: shortcode attributes (from anyone who can edit a page) and two admin form posts (from logged-in users with the right capabilities). The attributes are the realistic attack surface, so they're whitelisted rather than sanitised and hoped for.

What never crosses: visitor answers. They stay in the visitor's browser, under the site's origin. The honest caveat is that same-origin storage can be read by any other script on the site. The plugin can't fix that and doesn't pretend to; the README says so where site owners will see it.

## How it's tested

Three layers, roughly in order of how much I trust them.

`tests/unit.php` runs the shortcode and helpers against stand-in WordPress functions: defaults, hostile `height` values, attribute injection through `align` and `title`. Cheap and fast, and only as good as the stand-ins.

`tests/check-package.sh` checks that every PHP file parses, that the version agrees in all four places, and that the zip has exactly one top-level `kink-jars/` folder with the plugin file where WordPress looks for it and no development files inside. It then re-runs the unit checks against the zipped copy, because the zip is what people actually install.

`tests/e2e.cjs` is the one that matters. It starts a real WordPress through WordPress Playground and drives it in real Chromium, following INSTALL.md step by step:

1. Upload a GitHub "Source code" style zip and confirm WordPress rejects it.
2. Upload `kink-jars.zip`, then activate it.
3. Follow the welcome notice, create the draft, and publish it.
4. Visit the page and check the age gate, a palette, a rating and a PNG save.
5. Re-upload to check the update path, and confirm the page survives.

It also takes the screenshots for the guide, so the pictures can't drift away from what WordPress really shows. That's how I found out WordPress 7.1 says "Add Plugin" where older versions say "Add New", and that its admin screens fade between pages (the first screenshots caught two screens at once).

## Current state

- **Working and tested** (WordPress 7.1.3 in Playground, Chromium): everything in the guide, end to end.
- **Declared but untested:** WordPress 5.8 to 7.0, PHP 7.4, every theme other than the default block theme, real hosting with real caching and security plugins, Safari and real phones.
- **Not decided:** a licence. Neither this nor the app declares one yet.

## Limitations and awkward bits

- No automatic updates. That's the price of not being on WordPress.org; people re-upload the zip. A self-hosted update checker would fix it, but it's more moving parts than a 160 KB plugin really deserves right now.
- On a phone the app scrolls inside its box, inside the page. It works, but it's a scroll inside a scroll, and nobody's ever loved one of those. The full-screen link is the escape hatch.
- The "Set up" screen remembers only the newest page it made. Make three and it forgets the first two. They're still ordinary pages under Pages.
- `align="wide"` depends on the theme honouring the standard `alignwide` class. Block themes do. Older themes may not, and then it's just a normal-width box.

## Possible next steps

None of these exist yet:

- a proper block with a sidebar for width and height, instead of the Shortcode block
- a self-hosted update check, so new versions show up under Dashboard > Updates
- a licence, once one is chosen for the app as well

Author: TABARC-Code
