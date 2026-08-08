// --- Settings Initialization ---
let bcp_settings = {};
const settingsElement = document.getElementById('bcp-settings-data');

if (settingsElement) {
    try {
        bcp_settings = JSON.parse(settingsElement.textContent);
    } catch (e) {
        console.error("BCP Error: Could not parse settings data.", e);
    }
} else {
    console.error("BCP Error: Settings data element not found.");
}

// Which content types are protected on this page. Defaults to "all" so the
// module still behaves sensibly if the bridge data is ever missing a key.
const ct = Object.assign(
    { images: true, text: true, videos: true, code: true, banners: true },
    bcp_settings.content_types || {}
);

const CODE_SELECTOR = 'pre, code, .wp-block-code, .wp-block-syntaxhighlighter-code, .hljs, .EnlighterJSRAW';

const processedVideos = new WeakSet();

// --- Core Video Protection Logic ---
const protectVideo = (video) => {
    if (processedVideos.has(video) || video.dataset.bcpProtected === 'true') return;
    processedVideos.add(video);
    video.dataset.bcpProtected = 'true';

    // Apply general protections
    video.setAttribute('controlsList', 'nodownload');
    video.setAttribute('disablePictureInPicture', 'true');

    // A. Handle Watermarking
    let wrapper = video.closest('.bcp-watermark-wrapper');
    if (!wrapper) {
        wrapper = document.createElement('div');
        wrapper.classList.add('bcp-watermark-wrapper');
        video.parentNode.insertBefore(wrapper, video);
        wrapper.appendChild(video);
    }
    if (bcp_settings.enable_video_watermark && bcp_settings.watermark_text) {
        applyWatermark(wrapper);
    }

    // B. Blob URL Download Protection
    if (bcp_settings.disable_video_download) {
        const originalSrc = video.getAttribute('src') || video.querySelector('source')?.getAttribute('src');
        if (originalSrc && !originalSrc.startsWith('blob:')) {
            video.pause();
            video.removeAttribute('src');
            video.querySelectorAll('source').forEach(s => s.remove());
            video.load();

            fetch(originalSrc, { credentials: 'omit' })
                .then(response => {
                    if (!response.ok) throw new Error(`BCP: Network error fetching video: ${response.statusText}`);
                    return response.blob();
                })
                .then(blob => {
                    video.src = URL.createObjectURL(blob);
                })
                .catch(err => {
                    console.error('BCP Error:', err);
                    video.setAttribute('src', originalSrc); // Restore on failure
                });
        }
    }
};

// --- Watermark Application Logic ---
const applyWatermark = (wrapper) => {
    wrapper.querySelector('.bcp-watermark, .bcp-wm-style-pattern')?.remove();

    const opacity = parseFloat(bcp_settings.watermark_opacity) || 0.5;
    const position = bcp_settings.watermark_position || 'animated';
    const style = bcp_settings.watermark_style || 'text';
    const text = bcp_settings.watermark_text;

    if (style === 'pattern') {
        const patternContainer = document.createElement('div');
        patternContainer.className = 'bcp-wm-style-pattern';
        patternContainer.style.opacity = opacity;
        for (let i = 0; i < 30; i++) {
            const span = document.createElement('span');
            span.className = 'bcp-watermark-pattern-span';
            span.textContent = text;
            patternContainer.appendChild(span);
        }
        wrapper.appendChild(patternContainer);
    } else { // 'text' style
        const watermark = document.createElement('div');
        watermark.className = `bcp-watermark bcp-wm-style-text bcp-wm-position-${position}`;
        watermark.textContent = text;
        watermark.style.opacity = opacity;
        wrapper.appendChild(watermark);
    }
};

// --- Fullscreen Watermark Handling ---
let fullscreenWatermarkObserver = null;
const handleFullscreenChange = () => {
    fullscreenWatermarkObserver?.disconnect();
    document.querySelectorAll('.bcp-fullscreen-watermark').forEach(wm => wm.remove());

    const fullscreenElement = document.fullscreenElement || document.webkitFullscreenElement;
    if (fullscreenElement?.tagName === 'VIDEO' && processedVideos.has(fullscreenElement) && bcp_settings.enable_video_watermark && bcp_settings.watermark_text) {
        setTimeout(() => createFullscreenWatermark(fullscreenElement), 100);
    }
};

const createFullscreenWatermark = (videoElement) => {
    document.querySelectorAll('.bcp-fullscreen-watermark').forEach(wm => wm.remove()); // Final cleanup

    const watermark = document.createElement('div');
    const position = bcp_settings.watermark_position || 'animated';
    watermark.className = `bcp-watermark bcp-fullscreen-watermark bcp-wm-style-text bcp-wm-position-${position}`;
    watermark.textContent = bcp_settings.watermark_text;
    watermark.style.opacity = parseFloat(bcp_settings.watermark_opacity) || 0.5;

    videoElement.parentElement.appendChild(watermark);

    if ('ResizeObserver' in window) {
        fullscreenWatermarkObserver = new ResizeObserver(entries => {
            for (let entry of entries) {
                const smallerDim = Math.min(entry.contentRect.width, entry.contentRect.height);
                const fontSize = Math.max(12, Math.min(32, smallerDim * 0.03)); // Clamp font size
                watermark.style.fontSize = `${fontSize}px`;
            }
        });
        fullscreenWatermarkObserver.observe(videoElement);
    }
};

// --- Code Block Protection ---
const protectCodeBlock = (el) => {
    if (el.dataset.bcpCodeProtected === 'true') return;
    el.dataset.bcpCodeProtected = 'true';
    el.classList.add('bcp-protect-noselect');
    el.addEventListener('copy', preventDefault);
    el.addEventListener('contextmenu', preventDefault);
    el.addEventListener('dragstart', preventDefault);
};

// --- Banner / Logo Protection ---
const protectBanner = (el) => {
    if (el.dataset.bcpBannerProtected === 'true') return;
    el.dataset.bcpBannerProtected = 'true';
    el.classList.add('bcp-protect-noselect', 'bcp-protect-nodrag');
    el.addEventListener('dragstart', preventDefault);
    el.addEventListener('contextmenu', preventDefault);
};

// Applies `protectFn` to `root` itself (if it matches `selector`) and to any
// matching descendants. Used both for the initial page scan and for nodes
// added later via the MutationObserver.
const scanAndProtect = (root, selector, protectFn) => {
    if (!selector || root.nodeType !== 1) return;
    try {
        if (root.matches?.(selector)) protectFn(root);
        root.querySelectorAll?.(selector).forEach(protectFn);
    } catch (e) {
        console.error('BCP Error: Invalid selector.', selector, e);
    }
};

// --- Event Handlers ---
const preventDefault = e => e.preventDefault();

const handleKeydown = (e) => {
    const key = e.key.toUpperCase();
    const ctrl = e.ctrlKey || e.metaKey;

    if (bcp_settings.disable_devtools && (e.key === 'F12' || (ctrl && e.shiftKey && ['I', 'J', 'C'].includes(key)) || (ctrl && key === 'U'))) {
        e.preventDefault();
    }
    if (bcp_settings.disable_screenshot && (e.key === 'PrintScreen' || (ctrl && e.shiftKey && ['3', '4', 'S'].includes(key)))) {
        e.preventDefault();
        document.body.classList.add('bcp-screenshot-detected');
        if (bcp_settings.enable_custom_messages && bcp_settings.screenshot_alert_message) {
            alert(bcp_settings.screenshot_alert_message);
        }
        setTimeout(() => document.body.classList.remove('bcp-screenshot-detected'), 1000);
    }
};

const handleScreenRecording = () => {
    if (!bcp_settings.video_screen_record_block || !navigator.mediaDevices?.getDisplayMedia) return;

    const originalGetDisplayMedia = navigator.mediaDevices.getDisplayMedia;
    navigator.mediaDevices.getDisplayMedia = async function(...args) {
        try {
            const stream = await originalGetDisplayMedia.apply(this, args);
            document.querySelectorAll('video').forEach(v => v.closest('.bcp-watermark-wrapper, video')?.classList.add('bcp-recording-detected'));
            stream.getTracks().forEach(track => {
                track.onended = () => document.querySelectorAll('.bcp-recording-detected').forEach(el => el.classList.remove('bcp-recording-detected'));
            });
            return stream;
        } catch (err) {
            document.querySelectorAll('.bcp-recording-detected').forEach(el => el.classList.remove('bcp-recording-detected'));
            throw err;
        }
    };
};

// --- Initialization ---
const initProtection = () => {
    if (ct.videos) document.querySelectorAll('video').forEach(protectVideo);
    if (ct.code) document.querySelectorAll(CODE_SELECTOR).forEach(protectCodeBlock);
    if (ct.banners) scanAndProtect(document.documentElement, bcp_settings.banner_selector, protectBanner);

    if (bcp_settings.disable_text_selection && ct.text) {
        document.body.style.cssText += 'user-select:none;-webkit-user-select:none;';
    }
    if (bcp_settings.enhanced_protection) {
        document.body.classList.add('bcp-enhanced-protection');
    }
};

const observer = new MutationObserver(mutations => {
    mutations.forEach(mutation => {
        mutation.addedNodes.forEach(node => {
            if (node.nodeType !== 1) return;
            if (ct.videos) scanAndProtect(node, 'video', protectVideo);
            if (ct.code) scanAndProtect(node, CODE_SELECTOR, protectCodeBlock);
            if (ct.banners) scanAndProtect(node, bcp_settings.banner_selector, protectBanner);
        });
    });
});

// --- Self-Executing Initialization ---
const BCP_Init = () => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProtection);
    } else {
        initProtection();
    }

    observer.observe(document.body, { childList: true, subtree: true });

    // Event-based protections
    ['fullscreenchange', 'webkitfullscreenchange'].forEach(e => document.addEventListener(e, handleFullscreenChange, false));
    if (bcp_settings.disable_right_click) document.addEventListener('contextmenu', preventDefault, false);
    if (bcp_settings.disable_copy) document.addEventListener('copy', preventDefault, false);
    if (bcp_settings.disable_image_drag && ct.images) document.addEventListener('dragstart', e => { if (e.target.tagName === 'IMG') e.preventDefault(); }, false);

    if (bcp_settings.disable_devtools || bcp_settings.disable_screenshot) {
        document.addEventListener('keydown', handleKeydown);
    }
     if (bcp_settings.disable_screenshot) {
        window.addEventListener('blur', () => document.body.classList.add('bcp-screenshot-detected'));
        window.addEventListener('focus', () => document.body.classList.remove('bcp-screenshot-detected'));
    }

    if (ct.videos) handleScreenRecording();
};

// Run the initialization
BCP_Init();
