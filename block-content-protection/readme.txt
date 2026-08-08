=== Block Content Protection ===
Contributors: mohammadbabaei, ababaei
Tags: content protection, copy protection, screenshot, watermark, security
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Protect images, text, videos, code and banners from theft. Choose exactly which pages and which content types are protected, site-wide or per page.

== Description ==

Block Content Protection is a comprehensive content-protection plugin for WordPress. It deters content theft with right-click blocking, developer-tools blocking, copy blocking, text-selection blocking, image-drag blocking, video-download blocking, screenshot/screen-recording deterrence, and dynamic watermarking.

Unlike simpler protection plugins, Block Content Protection lets you decide:

* **Which pages are protected** — protect everything except a list of pages, protect only a hand-picked list of pages, or override the decision on any single post/page.
* **Which kind of content is protected** — turn Images, Text, Videos, Code Blocks, and Banners/Logos on or off independently, globally or per page.

= Base app developed by =
Mohammad Babaei — Adschi ( https://adschi.com/ )

= Extension & development by =
A. Babaei

= Key Features =

**Basic Protection**

* Disable right-click / context menu
* Block developer tools (F12, Ctrl+Shift+I/J/C, Ctrl+U)
* Disable copying (copy event and Ctrl+C)
* Block text selection
* Disable image dragging
* Disable video download (native controls + blob-based delivery)

**Advanced Protection**

* Disable screenshot shortcuts (PrintScreen, Cmd+Shift+3/4) with a window-blur blackout effect
* Mobile screenshot deterrence (FLAG_SECURE-style meta tags)
* Video screen-recording detection with automatic blackout
* Enhanced screen protection (extra CSS layers)
* Dynamic, animated video watermark with placeholders: `{user_login}`, `{user_email}`, `{user_mobile}`, `{ip_address}`, `{date}`

**Content Type Protection (new in 2.0)**

* Protect Images, Text, Videos, Code Blocks, and Banners/Logos independently
* Custom CSS selector for banners/logos (e.g. `.site-logo, .banner`)
* Dedicated protection for code blocks (`<pre>`, `<code>`) — blocks select, copy, and right-click
* Dedicated protection for banner/logo elements — blocks drag, select, and right-click

**Page Targeting (new in 2.0)**

* Protect all posts/pages except an exclusion list, or protect only a selected list
* Live, type-to-search post/page picker — no more typing raw post IDs
* Per-page override from a "Content Protection" box in the post/page editor: force protection on, force it off, or customize which content types apply to that page

**Other**

* IP whitelist to exempt staff/admins from all protections
* Custom alert messages for screenshot and recording detection
* Clean uninstall (removes all settings and post meta, multisite-aware)

= Important: Read Before Relying on This Plugin =

No client-side technique can make screenshots or screen recording 100% impossible. A determined user can always photograph the screen or use external capture hardware. This plugin significantly raises the effort required to copy your content and adds traceability through watermarking — it is a deterrent, not a DRM replacement. Combine it with visible watermarks and clear copyright notices for the best results.

== Installation ==

**From the WordPress admin (recommended)**

1. Go to Plugins → Add New → Upload Plugin.
2. Choose the `block-content-protection.zip` file and click Install Now.
3. Click Activate.
4. Go to the "Content Protection" menu item to configure your settings.

**Manual (FTP/SFTP)**

1. Upload the `block-content-protection` folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Go to the "Content Protection" menu item to configure your settings.

**After activation**

1. Under "Protection Settings", enable the browser-level protections you want.
2. Under "Content Type Protection", choose which kinds of content those protections apply to, and set a CSS selector for your banners/logo if you use that feature.
3. Under "Page Selection", choose whether to protect everything except a list, or only a selected list of posts/pages.
4. Optionally, open any post/page and use the "Content Protection" box in the sidebar to override the site-wide decision just for that page.
5. Optionally, enable and configure the video watermark and custom alert messages.
6. Save your settings.

== Frequently Asked Questions ==

= Does this stop screenshots completely? =

No. It makes casual screenshotting and copying meaningfully harder and adds traceability via watermarks, but no client-side plugin can guarantee 100% prevention. See the "Important" note in the description.

= Can I protect only my product images, and leave my blog text selectable? =

Yes. Turn off "Protect Text" and leave "Protect Images" on under Content Type Protection, either globally or for a specific page via the editor's Content Protection box.

= Can I protect only certain pages, like a members-only gallery? =

Yes. Set "Apply Protection To" to "Only selected posts & pages" and add those pages via the picker, or leave it on "All posts & pages" and use the per-page override to force-enable protection on the pages you need.

= Can I exempt my own account or staff from all protections? =

Yes. Add your IP address(es), one per line, to the "Whitelisted IP Addresses" field under IP Exclusions.

= Will this slow down my site? =

The scripts are small and only load on pages where protection is active for the current visitor. "Enhanced Screen Protection" and continuous recording detection are the heaviest features; disable them if you notice a performance impact on a specific page.

= A video looks black even though nobody is recording — is that a bug? =

That's the recording-detection blackout, and it can occasionally be a false positive depending on browser privacy settings. Try temporarily disabling "Block Video Screen Recording" to confirm, and make sure your IP is whitelisted if you are testing as an admin.

= Does the per-page setting override the global setting? =

Yes. If a post/page's "Content Protection" box is set to "Enable protection on this page" or "Disable protection on this page", that choice always wins over the global Page Selection mode. Leaving it on "Use global settings" (the default) follows your site-wide configuration.

== Screenshots ==

1. Protection Settings — the main site-wide protection toggles.
2. Content Type Protection — choose which kinds of content are protected.
3. Page Selection — protect all pages except a list, or only a selected list, via a live search picker.
4. Per-page "Content Protection" box in the post/page editor.
5. Watermark settings with live placeholders.

== Changelog ==

= 2.0.0 =
* Added: Content Type Protection (Images, Text, Videos, Code Blocks, Banners/Logos), globally and per page.
* Added: Per-page protection override via a new "Content Protection" meta box in the post/page editor.
* Added: Page selection modes — protect all except an exclusion list, or protect only a selected list.
* Added: Live AJAX search-and-select picker for excluded/included posts and pages.
* Added: Dedicated code-block and banner/logo protection (select/copy/right-click/drag blocking).
* Added: Clean uninstall routine (removes options and post meta, multisite-aware).
* Changed: Hardened input sanitization, added nonce/capability checks on the new meta box and AJAX endpoint.
* Changed: Non-destructive settings migration for existing installs; consistent version-based cache-busting.
* Removed: The unused legacy `js/protect.js` script (superseded by `js/protect.module.js` since 1.6.4).

= 1.6.6 =
* Fixed: Fullscreen watermark now correctly appears and resizes when a video enters fullscreen mode.

= 1.6.4 =
* Changed: Front-end script converted to a modern JavaScript module.
* Removed: Full-page watermark feature (video watermark remains).

= 1.6.2 =
* Fixed: A bug where disabling a feature via its checkbox was not saved correctly.

= 1.6.1 =
* Fixed: More reliable screenshot blocking via a window-blur blackout effect.

= 1.6.0 =
* Added: Full-page dynamic watermark (later removed in 1.6.4) and a CSS-based screenshot blackout.

= 1.5.6 – 1.5.9 =
* Fixed: Several issues where protection overlays interfered with native video controls.

= 1.4.0 =
* Added: Mobile screenshot blocking, video screen-recording protection, developer-tools detection.

= 1.3.0 =
* Added: Enhanced screen protection, screenshot shortcut blocking, custom alert messages, IP whitelist, page exclusions.

= 1.2.0 =
* Added: Video download protection, image drag prevention, developer tools blocking, copy prevention.

= 1.1.0 =
* Added: Text selection blocking, basic right-click protection.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 2.0.0 =
Major update: adds per-page and per-content-type protection controls plus security hardening. Existing settings are migrated automatically; review the new "Content Type Protection" and "Page Selection" sections after updating to make sure they match your previous exclusion list.

== Credits ==

* Base app developed by Mohammad Babaei — Adschi ( https://adschi.com/ )
* Extension & development by A. Babaei
