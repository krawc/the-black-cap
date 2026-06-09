# The Black Cap — WordPress Theme Developer Docs

## Overview

A classic WordPress theme (not FSE/block-theme) that renders the site as a single front-page built from six custom Gutenberg blocks. The theme has no sidebar, no widget areas, and no post archive — it is a landing page for the venue.

## Directory layout

```
theme/the-black-cap/
├── style.css                  Theme metadata (required by WP)
├── functions.php              Theme setup, block registration, asset enqueuing
├── index.php                  Fallback template
├── front-page.php             Homepage template — outputs block content from the WP editor
├── header.php                 HTML <head> + wp_head()
├── footer.php                 Footer nav + copyright + wp_footer()
├── package.json               npm scripts for building block JS
├── assets/
│   ├── css/frontend.css       All visual styles (ported from the React app verbatim)
│   ├── js/frontend.js         Nav toggle, parallax scroll, FrameGallery initialiser
│   └── svg/
│       ├── simple_flame_animated.svg   Hero animated logo
│       ├── neon-menu.svg               Decorative left-side image in Menu section
│       ├── regina.svg                  Decorative left-side image in Story section
│       └── frames/Frame {1-8}.svg      SVG photo-frame shapes used by Our Rooms
├── inc/
│   └── settings-page.php      WP admin settings page for API keys + cache clearing
└── src/blocks/                Block source (compiled to build/blocks/ by wp-scripts)
    ├── hero-nav/
    ├── whats-on/
    ├── story/
    ├── highlights/
    ├── drink-menu/
    └── our-rooms/
```

## Build system

The theme uses `@wordpress/scripts` (standard Gutenberg toolchain).

```bash
cd theme/the-black-cap
npm install
npm run build    # production build → build/
npm run start    # watch mode for development
```

Each block lives in `src/blocks/{name}/` with:
- `block.json`  — block metadata (attributes, icon, etc.)
- `index.js`    — entry point: imports `edit.js`, calls `registerBlockType`
- `edit.js`     — Gutenberg editor component (React)
- `render.php`  — server-side PHP render (the actual frontend HTML)

`wp-scripts build` compiles and copies everything to `build/blocks/`. `functions.php` auto-registers all blocks found in `build/blocks/*/block.json`.

## Installation

1. Copy `theme/the-black-cap/` into your WordPress `wp-content/themes/` directory.
2. Run `npm install && npm run build` inside the theme folder.
3. In WP Admin → Appearance → Themes, activate **The Black Cap**.
4. Create a page (e.g. "Home"), open it in the Block Editor, and insert the six blocks in order.
5. In WP Admin → Settings → Reading, set the homepage to display a static page and choose your new page.
6. Set up nav menus (see below) and API keys (Settings → Black Cap).

## Navigation menus

Two menu locations are registered:

| Location | Usage |
|---|---|
| `primary` | Hero orbit nav — up to 5 items; set each item's URL to an in-page anchor (`#story`, `#whats-on`, etc.) or an external URL |
| `footer`  | Footer legal links (Privacy Policy, Cookie Policy, etc.) |

Assign menus in Appearance → Menus or Appearance → Navigation.

## API credentials (Settings → Black Cap)

All credentials are stored as WP options and never exposed in block attributes.

### Instagram (Meta Graph API)

| Option key | Description |
|---|---|
| `tbc_instagram_access_token` | Long-lived access token (60-day expiry — set up auto-refresh) |
| `tbc_instagram_user_id` | Numeric Instagram Business/Creator account ID |

**Getting a token:**
1. Create a Meta App at [developers.facebook.com](https://developers.facebook.com) (Business type).
2. Add the Instagram Graph API product and connect your Instagram account.
3. Generate a short-lived token, exchange it for a long-lived token via:
   `GET https://graph.facebook.com/v18.0/oauth/access_token?grant_type=fb_exchange_token&client_id=APP_ID&client_secret=APP_SECRET&fb_exchange_token=SHORT_TOKEN`
4. Find your User ID: `GET https://graph.instagram.com/me?fields=id&access_token=LONG_TOKEN`

Tokens are cached in a WP transient (`tbc_instagram_posts`) for 1 hour. Clear via the "Clear API cache" button on the settings page.

### TikTok (Display API)

| Option key | Description |
|---|---|
| `tbc_tiktok_access_token` | OAuth access token from developers.tiktok.com |

**Getting a token:**
1. Create a TikTok app at [developers.tiktok.com](https://developers.tiktok.com).
2. Add the Display API product; enable the `video.list` scope.
3. Complete the OAuth flow for the venue's TikTok account to get an access token.

Cached as `tbc_tiktok_videos` for 1 hour.

## Blocks reference

### `the-black-cap/hero-nav` — Hero / Navigation

Outputs the full-viewport hero section: animated flame logo button + orbital rainbow nav + venue contact info.

| Attribute | Type | Notes |
|---|---|---|
| `menuSlug` | string | Slug of the WP nav menu to use for orbital items (max 5 items shown) |
| `address` | string | Venue address shown in the footer strip |
| `phone` | string | Phone number (auto-stripped for tel: href) |
| `email` | string | Email address |

The orbital item positions and colours are hardcoded presets (5 slots). If the nav menu has fewer than 5 items, only those slots are filled.

---

### `the-black-cap/whats-on` — What's On (Instagram)

Horizontal scroll slider of Instagram post embeds.

| Attribute | Type | Notes |
|---|---|---|
| `shortcodes` | string | Comma-separated post shortcodes used as fallback |
| `limit` | integer | Max posts to show (default 8) |

When `tbc_instagram_access_token` and `tbc_instagram_user_id` are set, the render.php calls the Instagram Graph API and caches the result. Falls back to `shortcodes` if the API is unavailable.

---

### `the-black-cap/story` — Story

The "Legendary" section: headline, body copy, and a parallax photo gallery.

| Attribute | Type | Notes |
|---|---|---|
| `title` | string | Section headline |
| `copy` | string | Body paragraph |
| `photos` | array | Each item: `{ id, url, scale, driftX, driftY }` |

**Photo parallax:** `driftX` and `driftY` are the maximum translation in `rem` applied at full scroll offset. The JS reads `data-drift-x` / `data-drift-y` from each `.photoPlaceholder` and applies a `translate()` proportional to how far the photo row is from the vertical centre of the viewport. `scale` sets the photo container height via `--h-scale` CSS custom property.

---

### `the-black-cap/highlights` — Highlights (TikTok)

Horizontal scroll slider of TikTok video embeds.

| Attribute | Type | Notes |
|---|---|---|
| `videoIds` | string | Comma-separated numeric TikTok video IDs as fallback |
| `limit` | integer | Max videos to show (default 8) |

When `tbc_tiktok_access_token` is set, calls `POST https://open.tiktokapis.com/v2/video/list/` and caches results.

---

### `the-black-cap/drink-menu` — Drink Menu

Full menu with category sections.

| Attribute | Type | Notes |
|---|---|---|
| `sections` | array | Each item: `{ category: string, items: [{ name, price }] }` |

Built with a custom repeater UI in the sidebar (no InnerBlocks). All editing happens in the Inspector panel.

---

### `the-black-cap/our-rooms` — Our Rooms

A CSS grid of SVG photo frames with images clipped to the frame shapes.

| Attribute | Type | Notes |
|---|---|---|
| `frames` | array | Each item: `{ svgFile, photos: string[], wide: boolean }` |

**How the clip-path works:**
The render.php outputs empty `.frameGallery` divs with `data-svg` (URL of the frame SVG) and `data-photos` (JSON array of image URLs) attributes. The FrameGallery initialiser in `frontend.js` then:
1. Fetches the SVG text, tags every `fill="#FF0000"` (pure red) element with `data-fg-index`.
2. Sets the tagged SVG as `innerHTML` of the container.
3. For each tagged element: creates a `<clipPath>` containing a clone of the shape, then replaces the original with an `<image>` clipped to that path. Photos cycle through the array if there are fewer photos than red shapes.

The frame SVGs must use pure `#FF0000` fill for the photo placeholder regions and any other fill for the frame/border parts.

**`wide` toggle:** makes the frame span 2 columns in the 5-column grid (`grid-column: span 2`).

## Frontend JS (`assets/js/frontend.js`)

Three self-contained IIFEs:

1. **Nav orbit toggle** — clicks `#tbc-logo-btn` to add/remove `.isOpen` on `#tbc-logo-orbit`. In-page hash links use smooth scroll; external links navigate normally.
2. **Story parallax** — listens to `scroll` and translates each `.photoPlaceholder` based on `data-drift-x` / `data-drift-y` and viewport position.
3. **FrameGallery** — initialises all `.frameGallery[data-svg]` elements and provides a lightbox for clicked photos.

## CSS (`assets/css/frontend.css`)

Directly ported from the React app's `src/styles.css` with minimal changes:
- Added `.admin-bar .hero` offset for the WP toolbar.
- Added `.rainbowItem` display fix for `<a>` tags (the React app used `<button>`).

All class names are identical to the React source, so the visual output is a 1:1 match.

## Adding a new block

1. Create `src/blocks/{name}/` with `block.json`, `index.js`, `edit.js`, `render.php`.
2. Register the block name as `the-black-cap/{name}`.
3. Run `npm run build`.
4. The block auto-registers via the glob in `functions.php` — no PHP change needed.
