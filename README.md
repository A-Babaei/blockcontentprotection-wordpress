<div align="center">

# 🛡️ Block Content Protection for WordPress

**A complete, professional content-protection plugin for WordPress.**
Stop content theft, screenshots, screen recording, right-click saving, and unauthorized copying — with full control over *which pages* and *which content types* get protected.

![Version](https://img.shields.io/badge/version-2.0.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-0073aa)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)
![License](https://img.shields.io/badge/license-MIT-green)

[Features](#-features) · [Installation](#-installation) · [Configuration Guide](#-configuration-guide) · [FAQ](#-faq) · [Changelog](#-changelog) · [فارسی](#-پلاگین-محافظت-از-محتوا-برای-وردپرس)

</div>

---

## About

Block Content Protection adds multiple layers of client-side deterrence against content theft: right-click blocking, developer-tools blocking, copy blocking, text-selection blocking, image-drag blocking, video-download blocking, screenshot/screen-recording deterrence, and dynamic watermarking.

Version 2.0 adds granular control that most competing plugins don't offer:

- **Choose which pages are protected** — protect everything except a list, or protect only a hand-picked list of posts/pages, or override the decision on any single page.
- **Choose which kind of content is protected** — images, text, videos, code blocks, and banners/logos can each be turned on or off independently, globally or per page.

> ⚠️ **Honesty first:** no client-side technique can make screenshots or screen recording 100% impossible. This plugin raises the effort required to copy your content and deters casual theft — it is not a DRM replacement. See [Technical Limitations](#technical-limitations).

---

## ✨ Features

### Basic Protection
- **Disable Right-Click** — prevents the context menu from opening.
- **Block Developer Tools** — blocks common shortcuts (F12, Ctrl+Shift+I/J/C, Ctrl+U).
- **Disable Copying** — blocks the copy event and Ctrl+C.
- **Block Text Selection** — makes page text unselectable.
- **Disable Image Dragging** — makes images undraggable.
- **Disable Video Download** — strips native download controls and serves video via blob URLs.

### Advanced Protection
- **Disable Screenshot Shortcuts** — blocks PrintScreen and macOS shortcuts (Cmd+Shift+3/4), with a blackout effect on window-blur to frustrate OS-level snipping tools.
- **Mobile Screenshot Block** — `FLAG_SECURE`-style meta tags to deter screenshots on supporting Android devices.
- **Video Screen Recording Block** — detects `getDisplayMedia()` usage and blacks out video during a detected capture.
- **Enhanced Screen Protection** — additional CSS layers that interfere with some capture tools.
- **Dynamic Video Watermark** — animated or fixed-position watermark with placeholders for user login, email, phone, IP address, and date — great for tracing leaks back to an account.

### 🆕 Content Type Protection
Turn protection on or off **per kind of content**, instead of an all-or-nothing page:

| Type | What it does |
|---|---|
| **Images** | Drag and right-click prevention on `<img>` elements. |
| **Text** | Text-selection prevention on page content. |
| **Videos** | Gates the entire video pipeline: download blocking, watermarking, recording detection. |
| **Code Blocks** | Blocks select, copy, and right-click on `<pre>`, `<code>`, and common syntax-highlighter markup. |
| **Banners / Logos** | Blocks drag, select, and right-click on elements matched by a CSS selector you define (e.g. `.site-logo, .banner`). |

### 🆕 Page Targeting
- **Protect all pages except a list**, or **protect only a selected list** — chosen from Settings via a live, type-to-search post/page picker (no more typing raw IDs).
- **Per-page override** — a "Content Protection" box in every post/page editor lets you force protection on or off for that one page, and choose exactly which content types apply there, independent of the site-wide defaults.

### Customization
- **IP Whitelist** — exempt specific IP addresses from all protections (useful for admins/staff).
- **Custom Alert Messages** — replace the default browser alerts with your own copy.

### Built for a Professional Deployment
- Strict, allow-listed input sanitization on every setting.
- Nonce and capability checks on the settings form, the per-page meta box, and the AJAX post-search endpoint.
- Clean uninstall — removes all options and post meta when the plugin is deleted (multisite-aware).
- Non-destructive upgrades — new settings are merged into existing installs safely; legacy data formats are migrated automatically.
- Single version constant used for all asset enqueues, so browsers reliably pick up updates.

---

## 📦 Installation

1. Download `block-content-protection.zip` (the `block-content-protection` folder, zipped).
2. In your WordPress admin, go to **Plugins → Add New → Upload Plugin**.
3. Choose the zip file and click **Install Now**, then **Activate**.
4. Go to **Content Protection** in the admin menu to configure it.

Manual (FTP/SFTP) install: upload the `block-content-protection` folder to `wp-content/plugins/`, then activate it from the **Plugins** screen.

---

## ⚙️ Configuration Guide

### 1. Protection Settings
Turn on the browser-level protections you want site-wide: right-click, dev tools, copy, text selection, image drag, video download, screenshot shortcuts, enhanced protection, mobile screenshot block, and screen-recording block.

### 2. Content Type Protection
Decide **what** those protections apply to:
- Toggle **Protect Images / Text / Videos / Code Blocks / Banners** on or off.
- For banners/logos, set the **Banner / Logo CSS Selector** field to match your theme's markup, e.g. `.site-logo, .custom-logo, .site-branding img, .banner`.

### 3. Page Selection
Decide **where** protection is active:
- **All posts & pages (except excluded)** — the default. Add specific posts/pages to the **Excluded Posts/Pages** picker to exempt them (e.g. a Contact page).
- **Only selected posts & pages** — protection is off everywhere except the posts/pages you add to the **Included Posts/Pages** picker.
- Both pickers are type-to-search: start typing a title and click a result to add it as a chip; click the `×` on a chip to remove it.

### 4. Per-Page Override
Open any post or page in the editor and find the **Content Protection** box in the sidebar:
- **Use global settings** — follows the rules configured in step 3 above (default).
- **Enable protection on this page** — always protects this page, regardless of the global Page Selection mode, using the content types you check below.
- **Disable protection on this page** — always exempts this page, regardless of the global settings.

### 5. Watermark & Messages
Enable **Video Watermark**, set its text (with `{user_login}`, `{user_email}`, `{user_mobile}`, `{ip_address}`, `{date}` placeholders), opacity, position, and style. Optionally enable **Custom Messages** to replace the default screenshot/recording alerts.

---

## 🧠 How It Works

**Screenshot deterrence:** intercepts PrintScreen/OS screenshot shortcuts and window-blur events to trigger a full-screen blackout, plus a CSS `@media (display-capture: monitor)` rule that blacks out the page during a detected capture.

**Video protection:** replaces the native `src` with a blob-fetched copy (defeats simple "save video as"), disables Picture-in-Picture and the native download control, and monitors `navigator.mediaDevices.getDisplayMedia()` to detect screen recording and black out the video mid-stream.

**Content-type scoping:** the front-end script reads a small JSON settings bridge rendered in the page footer (derived from your global settings and any per-page override) and only wires up protection for the content types you've enabled — a `MutationObserver` keeps watching so protection also applies to content injected later (AJAX-loaded posts, lazy-loaded videos, etc.).

---

## 🗂 File Structure

```
block-content-protection/
├── block-content-protection.php   # Main plugin file (settings, meta box, enqueue logic)
├── uninstall.php                  # Removes options/post meta on plugin deletion
├── readme.txt                     # WordPress-standard user readme
├── admin/
│   ├── css/admin-styles.css       # Settings page & meta box styles
│   └── js/admin-scripts.js        # Settings page & meta box behavior, AJAX post picker
├── css/
│   └── protect.css                # Front-end protection styles (watermark, blackout, etc.)
├── js/
│   └── protect.module.js          # Front-end protection logic (ES module)
└── languages/
    └── block-content-protection.pot
```

---

## ✅ Compatibility

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **Browsers:** Chrome, Firefox, Safari, Edge (latest versions)
- **Mobile:** Android 5.0+, iOS 13+ (screenshot blocking has limited support on iOS)

---

## Technical Limitations

**Please understand these important points before relying on this plugin for sensitive content:**

1. **No protection is 100% foolproof** — a determined user can photograph the screen, use external capture hardware, or use a browser/OS that isn't covered by these techniques.
2. **Mobile screenshot protection** works better on Android; iOS offers very limited screenshot-blocking capability to web pages.
3. **Video recording protection** detects common software recording APIs; it cannot prevent hardware-based capture.
4. **Best practice:** combine multiple layers (protection settings + visible watermarks + copyright notices) rather than relying on any single technique.

---

## ❓ FAQ

**Does this stop screenshots completely?**
No. It makes casual screenshotting and copying meaningfully harder and adds traceability (via watermarks), but no client-side plugin can guarantee 100% prevention — see [Technical Limitations](#technical-limitations).

**Can I protect only my product images, not my blog text?**
Yes — turn off **Protect Text** and leave **Protect Images** on in Content Type Protection, either globally or per page via the editor's Content Protection box.

**Can I exempt my own admin account or staff from all protections?**
Yes — add your IP address(es) to the **Whitelisted IP Addresses** field.

**Will this slow down my site?**
The scripts are small and only load on pages where protection is active. "Enhanced Screen Protection" and continuous recording detection are the heaviest features; disable them if you notice a performance impact.

**I updated the plugin and a video looks black — is that a bug?**
That's the recording-detection blackout, usually a false positive from certain browser privacy settings. Try disabling "Video Screen Recording Block" to confirm, and check that your IP is whitelisted if you're testing as an admin.

---

## 📝 Changelog

See [`block-content-protection/Changelog`](block-content-protection/Changelog) for the full history. Highlights:

**2.0.0**
- Content Type Protection (images/text/videos/code/banners).
- Per-page protection override with a dedicated meta box.
- Page selection modes (all-except / selected-only) with an AJAX post/page picker.
- Security hardening, clean uninstall, non-destructive upgrades.

---

## 🙏 Credits

- **Base app developed by:** Mohammad Babaei — [Adschi](https://adschi.com/)
- **Extension & development by:** A. Babaei

## 📄 License

Licensed under the MIT License. See [`LICENSE`](LICENSE) for details.

## 💬 Support

- Website: [adschi.com](https://adschi.com)
- Issues & feature requests: open a GitHub issue on this repository

---
---

# 🛡 پلاگین محافظت از محتوا برای وردپرس

یک پلاگین کامل و حرفه‌ای برای محافظت از محتوای وب‌سایت‌های وردپرسی، طراحی‌شده برای جلوگیری از سرقت محتوا، اسکرین‌شات، ضبط صفحه و استفاده غیرمجاز — با کنترل کامل بر اینکه **کدام صفحات** و **کدام نوع محتوا** محافظت شوند.

**پایه افزونه توسعه‌یافته توسط:** محمد بابایی - [adschi.com](https://adschi.com)
**توسعه و افزودن قابلیت‌ها توسط:** ا. بابایی

---

## ویژگی‌ها

### محافظت پایه
-   **غیرفعال کردن راست کلیک**: جلوگیری از باز شدن منوی راست کلیک
-   **مسدود کردن ابزارهای توسعه‌دهنده**: مسدود کردن دسترسی به ابزارهای توسعه‌دهنده مرورگر
-   **جلوگیری از کپی کردن**: غیرفعال کردن کلیدهای میانبر کپی (مانند Ctrl+C)
-   **جلوگیری از انتخاب متن**: غیرفعال کردن قابلیت انتخاب متن
-   **غیرفعال کردن کشیدن تصویر**: جلوگیری از کشیدن تصاویر
-   **غیرفعال کردن دانلود ویدئو**: حذف گزینه دانلود از پخش‌کننده‌های ویدئو

### محافظت پیشرفته
-   **مسدود کردن کلیدهای اسکرین‌شات**: مسدود کردن PrintScreen و میانبرهای اسکرین‌شات مک
-   **مسدود کردن اسکرین‌شات موبایل**: تلاش برای جلوگیری از اسکرین‌شات با روش‌های متعدد
-   **محافظت ویدئو از ضبط صفحه**: تشخیص ضبط صفحه و سیاه کردن ویدئوها
-   **محافظت پیشرفته صفحه**: افزودن لایه‌های محافظ CSS و مکانیزم‌های تشخیص
-   **واترمارک ویدئو**: اعمال یک واترمارک متحرک و داینامیک بر روی ویدئوهای شما.

### محافظت بر اساس نوع محتوا
-   **تصاویر، متن، ویدئو، بلوک‌های کد و بنر/لوگو**: هرکدام را جداگانه، به‌صورت سراسری یا برای هر صفحه، فعال یا غیرفعال کنید.
-   برای بنر/لوگو می‌توانید یک CSS selector سفارشی وارد کنید.

### هدف‌گذاری صفحات
-   **حالت‌های سراسری**: محافظت از همه صفحات به‌جز فهرست استثنا، یا فقط از صفحات انتخاب‌شده — با یک فیلد جست‌وجوی زنده به‌جای وارد کردن شناسه.
-   **بازنویسی برای هر صفحه**: از جعبه «محافظت از محتوا» در ویرایشگر هر پست/صفحه، محافظت را فعال، غیرفعال یا سفارشی کنید.

### سفارشی‌سازی
-   **لیست سفید IP**: حذف آدرس‌های IP خاص از تمام محافظت‌ها
-   **پیام‌های سفارشی**: شخصی‌سازی پیام‌های نمایش داده شده به کاربران

---

## نحوه نصب

۱. فایل `block-content-protection.zip` را دانلود کنید.
۲. وارد پنل مدیریت وردپرس خود شوید و به بخش **افزونه‌ها > افزودن** بروید.
۳. روی **بارگذاری افزونه** کلیک کرده و فایل `.zip` را انتخاب کنید، سپس **فعال کردن** را بزنید.
۴. به بخش **Content Protection** در منوی مدیریت بروید و تنظیمات را انجام دهید.

## راهنمای تنظیمات

1. **Protection Settings**: محافظت‌های سطح مرورگر را که می‌خواهید فعال کنید.
2. **Content Type Protection**: مشخص کنید این محافظت‌ها روی چه نوع محتوایی اعمال شوند (تصویر، متن، ویدئو، کد، بنر).
3. **Page Selection**: مشخص کنید محافظت روی کدام صفحات فعال باشد — همه به‌جز چند صفحه، یا فقط چند صفحه‌ی انتخابی.
4. **بازنویسی برای هر صفحه**: از جعبه «Content Protection» در ویرایشگر هر پست/صفحه، برای آن صفحه خاص تنظیمات را بازنویسی کنید.
5. **واترمارک و پیام‌ها**: واترمارک ویدئو و پیام‌های هشدار سفارشی را تنظیم کنید.

## نکات مهم

هیچ روش سمت-کاربری نمی‌تواند اسکرین‌شات یا ضبط صفحه را صددرصد غیرممکن کند؛ این افزونه سرقت محتوا را دشوارتر و قابل ردیابی‌تر می‌کند، نه غیرممکن.

## پشتیبانی

- وب‌سایت: [adschi.com](https://adschi.com)
- نسخه: 2.0.0

## مجوز

این پلاگین تحت مجوز MIT منتشر شده است. برای جزئیات به فایل [`LICENSE`](LICENSE) مراجعه کنید.

- Copyright (c) 2025 Mohammad Babaei - Adschi (پایه افزونه)
- Copyright (c) 2026 A. Babaei (توسعه و افزودن قابلیت‌ها)
