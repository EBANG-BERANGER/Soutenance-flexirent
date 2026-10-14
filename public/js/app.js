document.addEventListener('DOMContentLoaded', () => {
    initScrollReveal(document);
    initSplitHeadlines();
    initTiltCards(document);
    initScrollProgress();
    initHeroParallax();
    initStepHighlight();
    initToasts();
    initPasswordToggles();
    initPhotoPickers();
    initNavbarScroll();
    initFormSpinners();
    initFloatingLabels();
    initPasswordStrengthMeter();
    initHomepageIntro();
    initKigaliClock();
    initHelpChip();
    initDayNightTheme();
});

// Keeps <html data-theme> in sync with the real time of day in Kigali. An
// inline script in <head> already sets it once before first paint (so
// there's no flash); this re-checks every 5 minutes so a tab left open
// across dusk/dawn still updates.
function initDayNightTheme() {
    const root = document.documentElement;

    const applyPhase = () => {
        const hour = Number(new Intl.DateTimeFormat('en-US', {
            timeZone: 'Africa/Kigali', hour: 'numeric', hourCycle: 'h23',
        }).format(new Date()));
        const isNight = hour < 6 || hour >= 18;
        if (isNight) {
            root.setAttribute('data-theme', 'night');
        } else {
            root.removeAttribute('data-theme');
        }
    };

    applyPhase();
    setInterval(applyPhase, 5 * 60 * 1000);
}

// Live "local time" badge in the hero, refreshed every 30s. Purely a text
// update, so it costs nothing under reduced-motion and needs no override.
function initKigaliClock() {
    const el = document.querySelector('[data-fx-clock-time]');
    if (!el) return;

    const update = () => {
        el.textContent = new Date().toLocaleTimeString('en-GB', {
            timeZone: 'Africa/Kigali', hour: '2-digit', minute: '2-digit',
        });
    };

    update();
    setInterval(update, 1000 * 30);
}

// Floating dismissible "chat with us" chip: slides in a moment after load,
// stays dismissed across visits once closed (localStorage, not sessionStorage).
function initHelpChip() {
    const chip = document.querySelector('[data-fx-help-chip]');
    if (!chip) return;

    if (localStorage.getItem('fx_help_chip_dismissed') === '1') {
        chip.remove();
        return;
    }

    setTimeout(() => chip.classList.add('is-visible'), 1200);

    const closeBtn = chip.querySelector('[data-fx-help-chip-close]');
    closeBtn.addEventListener('click', () => {
        localStorage.setItem('fx_help_chip_dismissed', '1');
        chip.classList.remove('is-visible');
        setTimeout(() => chip.remove(), 350);
    });
}

// First-visit homepage intro: a car silhouette slides across then the logo
// fades in, once per browser session (sessionStorage), skippable on click,
// capped at 1.5s. On repeat visits (or prefers-reduced-motion) the overlay
// never shows and the key stats count up instead. The two are mutually
// exclusive so the homepage only gets one "reveal moment" per load.
function initHomepageIntro() {
    const overlay = document.querySelector('[data-fx-intro]');

    if (!overlay) {
        runCounterAnimations();
        return;
    }

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const alreadyVisited = sessionStorage.getItem('fx_visited') === '1';
    sessionStorage.setItem('fx_visited', '1');

    if (reduced || alreadyVisited) {
        overlay.remove();
        runCounterAnimations();
        return;
    }

    overlay.classList.add('is-active');
    document.body.classList.add('fx-intro-locked');

    const finish = () => {
        if (overlay.dataset.done) return;
        overlay.dataset.done = '1';
        overlay.classList.add('is-hidden');
        document.body.classList.remove('fx-intro-locked');
        setCountersStatic();
        setTimeout(() => overlay.remove(), 320);
    };

    overlay.addEventListener('click', finish);
    const skipBtn = overlay.querySelector('[data-fx-intro-skip]');
    if (skipBtn) skipBtn.addEventListener('click', (e) => { e.stopPropagation(); finish(); });
    setTimeout(finish, 1500);
}

// Show every homepage stat at its final value immediately, with no count-up.
// Used right after the first-visit intro, which already served as this
// load's "reveal moment".
function setCountersStatic() {
    document.querySelectorAll('[data-count]').forEach((el) => {
        const target = parseInt(el.dataset.count, 10);
        if (target) el.textContent = target.toLocaleString();
    });
    document.querySelectorAll('[data-count-decimal]').forEach((el) => {
        const target = parseFloat(el.dataset.countDecimal);
        if (target) el.textContent = target.toFixed(1);
    });
}

function runCounterAnimations() {
    initCounters();
    initDecimalCounters();
}

// Odometer count-up for decimal stats (e.g. average rating), companion to
// initCounters() below which handles integer stats.
function initDecimalCounters() {
    document.querySelectorAll('[data-count-decimal]').forEach((el) => {
        const target = parseFloat(el.dataset.countDecimal);
        if (!target) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                observer.unobserve(entry.target);

                const duration = 900;
                const start = performance.now();
                const step = (now) => {
                    const progress = Math.min((now - start) / duration, 1);
                    el.textContent = (target * (1 - Math.pow(1 - progress, 3))).toFixed(1);
                    if (progress < 1) requestAnimationFrame(step);
                };
                requestAnimationFrame(step);
            });
        }, { threshold: 0.4 });

        observer.observe(el);
    });
}

// Give every form a branded loading state on submit, so async-feeling
// actions (filters aside) always show visible feedback. Forms that manage
// their own submitting state (e.g. the payment page) opt out via
// data-no-spinner.
function initFormSpinners() {
    document.querySelectorAll('form:not([data-no-spinner])').forEach((form) => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('button[type="submit"], button:not([type])');
            if (!btn || btn.dataset.spinning) return;
            btn.dataset.spinning = '1';
            btn.dataset.originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="fx-spinner"></span>' + btn.textContent.trim();
        });
    });
}

// Navbar compacts and gains shadow once the page scrolls, for a bit more polish.
function initNavbarScroll() {
    const nav = document.querySelector('.fx-navbar');
    if (!nav) return;

    const update = () => nav.classList.toggle('is-scrolled', window.scrollY > 12);
    update();
    window.addEventListener('scroll', update, { passive: true });
}

const EYE_OPEN = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
const EYE_CLOSED = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a20.6 20.6 0 0 1 5.06-5.94M9.9 4.24A10.4 10.4 0 0 1 12 4c7 0 11 8 11 8a20.6 20.6 0 0 1-3.22 4.44M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>';

// Add a show/hide toggle to every password field on the page.
function initPasswordToggles() {
    document.querySelectorAll('input[type="password"]:not([data-toggle-bound])').forEach((input) => {
        input.setAttribute('data-toggle-bound', '1');

        const wrap = document.createElement('div');
        wrap.className = 'fx-password-wrap';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'fx-password-toggle';
        toggle.setAttribute('aria-label', 'Show password');
        toggle.innerHTML = EYE_OPEN;
        toggle.addEventListener('click', () => {
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            toggle.innerHTML = showing ? EYE_OPEN : EYE_CLOSED;
            toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        });
        wrap.appendChild(toggle);
    });
}

// Floating labels: a field's label rises above it once it holds a value (or
// is focused), instead of sitting statically above at all times. Driven by a
// class on the .fx-field wrapper rather than pure CSS (:placeholder-shown)
// so it survives autofill and the password-toggle script re-parenting the input.
function initFloatingLabels() {
    document.querySelectorAll('.fx-field').forEach((field) => {
        if (field.dataset.floatBound) return;
        field.dataset.floatBound = '1';

        const control = field.querySelector('input, select, textarea');
        if (!control) return;

        const sync = () => {
            const hasValue = control.tagName === 'SELECT'
                ? control.value !== ''
                : control.value.trim() !== '';
            field.classList.toggle('is-filled', hasValue);
        };

        sync();
        control.addEventListener('input', sync);
        control.addEventListener('change', sync);
        field.addEventListener('focusin', () => field.classList.add('is-focused'));
        field.addEventListener('focusout', () => {
            field.classList.remove('is-focused');
            sync();
        });

        // Autofill often skips input events until the user interacts elsewhere;
        // a couple of delayed re-checks catch it without waiting on real events.
        setTimeout(sync, 400);
        setTimeout(sync, 1200);
    });
}

// Real-time password strength meter: scores length + character variety and
// reflects it in a fill bar (red -> orange -> green) plus a text label.
function initPasswordStrengthMeter() {
    document.querySelectorAll('[data-pwd-strength-for]').forEach((meter) => {
        const input = document.getElementById(meter.dataset.pwdStrengthFor);
        if (!input) return;

        const fill = meter.querySelector('.fx-pwd-strength-fill');
        const label = meter.querySelector('.fx-pwd-strength-label');

        const update = () => {
            const value = input.value;
            const criteria = [
                value.length >= 8,
                /[A-Z]/.test(value),
                /[a-z]/.test(value),
                /[0-9]/.test(value),
                /[^A-Za-z0-9]/.test(value),
            ];
            const score = value ? criteria.filter(Boolean).length : 0;

            if (!value) {
                meter.style.visibility = 'hidden';
                return;
            }
            meter.style.visibility = 'visible';

            let level = 'weak';
            let percent = 20;
            let text = 'Weak';
            if (score >= 5) {
                level = 'strong'; percent = 100; text = 'Strong';
            } else if (score >= 3) {
                level = 'medium'; percent = 60; text = 'Medium';
            } else {
                level = 'weak'; percent = Math.max(20, score * 15); text = 'Weak';
            }

            meter.dataset.level = level;
            if (fill) fill.style.width = percent + '%';
            if (label) label.textContent = text;
        };

        update();
        input.addEventListener('input', update);
    });
}

// Re-run the motion hooks (reveal + tilt) against newly injected markup, e.g. after
// an AJAX swap of the vehicle grid whose cards carry .fx-reveal / .fx-tilt.
window.fxReinit = function (container) {
    initScrollReveal(container);
    initTiltCards(container);
};

// Scroll reveal: fade/rise elements marked .fx-reveal into place as they enter the viewport.
function initScrollReveal(scope) {
    const revealTargets = scope.querySelectorAll('.fx-reveal:not(.is-visible)');
    if (!revealTargets.length) return;

    if (!('IntersectionObserver' in window)) {
        revealTargets.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

    revealTargets.forEach((el, i) => {
        el.style.transitionDelay = Math.min(i % 6, 5) * 70 + 'ms';
        observer.observe(el);
    });
}

// Count up the numeric stats once they come into view.
function initCounters() {
    document.querySelectorAll('[data-count]').forEach((el) => {
        const target = parseInt(el.dataset.count, 10);
        if (!target) return;

        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                counterObserver.unobserve(entry.target);

                const duration = 900;
                const start = performance.now();
                const step = (now) => {
                    const progress = Math.min((now - start) / duration, 1);
                    el.textContent = Math.round(target * (1 - Math.pow(1 - progress, 3))).toLocaleString();
                    if (progress < 1) requestAnimationFrame(step);
                };
                requestAnimationFrame(step);
            });
        }, { threshold: 0.4 });

        counterObserver.observe(el);
    });
}

// Split headline text into per-word spans that fade/rise in on load, staggered.
function initSplitHeadlines() {
    document.querySelectorAll('.fx-split').forEach((el) => {
        if (el.dataset.split === 'done') return;
        el.dataset.split = 'done';

        const walk = (node) => {
            const words = [];
            node.childNodes.forEach((child) => {
                if (child.nodeType === Node.TEXT_NODE) {
                    child.textContent.split(/(\s+)/).forEach((chunk) => {
                        if (chunk.trim() === '') {
                            words.push(document.createTextNode(chunk));
                        } else {
                            const span = document.createElement('span');
                            span.className = 'fx-word';
                            span.textContent = chunk;
                            words.push(span);
                        }
                    });
                } else {
                    words.push(child);
                }
            });
            node.replaceChildren(...words);
        };

        walk(el);

        let i = 0;
        el.querySelectorAll('.fx-word').forEach((word) => {
            word.style.animationDelay = (i * 45) + 'ms';
            i++;
        });
    });
}

// Subtle tilt-toward-cursor effect on cards marked .fx-tilt.
function initTiltCards(scope) {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    if (window.matchMedia('(hover: none)').matches) return;

    scope.querySelectorAll('.fx-tilt:not([data-tilt-bound])').forEach((card) => {
        card.setAttribute('data-tilt-bound', '1');
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;
            card.style.transform = `perspective(700px) rotateX(${(-y * 6).toFixed(2)}deg) rotateY(${(x * 6).toFixed(2)}deg) translateY(-8px)`;
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
        });
    });
}

// Thin progress bar at the top of the page, tracking scroll depth.
function initScrollProgress() {
    const bar = document.createElement('div');
    bar.className = 'fx-progress';
    document.body.appendChild(bar);

    const update = () => {
        const scrollable = document.documentElement.scrollHeight - window.innerHeight;
        const progress = scrollable > 0 ? (window.scrollY / scrollable) * 100 : 0;
        bar.style.width = progress + '%';
    };

    update();
    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
}

// Parallax drift on the decorative hero blobs.
function initHeroParallax() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const blobs = document.querySelectorAll('.fx-hero-blob');
    if (!blobs.length) return;

    const update = () => {
        const y = window.scrollY;
        blobs.forEach((blob, i) => {
            const factor = i === 0 ? 0.15 : -0.1;
            blob.style.transform = `translateY(${y * factor}px)`;
        });
    };

    update();
    window.addEventListener('scroll', update, { passive: true });
}

// Highlight the "How it works" step currently centered in the viewport, and
// fill the route-line progress bar up to that step as the user scrolls.
function initStepHighlight() {
    const steps = document.querySelectorAll('.step-pin');
    if (!steps.length || !('IntersectionObserver' in window)) return;

    const stepList = Array.from(steps);
    const progressBar = document.querySelector('[data-steps-progress]');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            entry.target.classList.toggle('is-active', entry.isIntersecting);
        });

        if (progressBar) {
            const activeIndex = stepList.reduce((acc, step, i) => step.classList.contains('is-active') ? i : acc, -1);
            const pct = activeIndex >= 0 ? (activeIndex / (stepList.length - 1)) * 88 : 0;
            progressBar.style.width = pct + '%';
        }
    }, { threshold: 0.6 });

    steps.forEach((step) => observer.observe(step));
}

// Toasts: turn flashed session alerts into dismissible, auto-expiring toasts.
function initToasts() {
    const source = document.getElementById('fx-flash-data');
    if (!source) return;

    let wrap = document.querySelector('.fx-toast-wrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.className = 'fx-toast-wrap';
        document.body.appendChild(wrap);
    }

    if (source.dataset.status) {
        showToast(wrap, source.dataset.status, 'ok');
    }

    try {
        const errors = JSON.parse(source.dataset.errors || '[]');
        if (errors.length === 1) {
            showToast(wrap, errors[0], 'danger');
        } else if (errors.length > 1) {
            // textContent instead of innerHTML so a message can't inject HTML (XSS)
            const list = document.createElement('ul');
            list.className = 'mb-0 ps-3';
            errors.forEach((e) => {
                const li = document.createElement('li');
                li.textContent = e;
                list.appendChild(li);
            });
            showToast(wrap, list, 'danger');
        }
    } catch (e) { /* no errors payload */ }
}

function showToast(wrap, content, type) {
    const toast = document.createElement('div');
    toast.className = 'fx-toast' + (type === 'danger' ? ' fx-toast-danger' : '');
    // textContent instead of innerHTML so a message can't inject HTML (XSS)
    const body = document.createElement('span');
    if (content instanceof Node) body.appendChild(content);
    else body.textContent = String(content);
    toast.appendChild(body);

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.setAttribute('aria-label', 'Dismiss');
    closeBtn.textContent = '×';
    closeBtn.addEventListener('click', () => dismissToast(toast));
    toast.appendChild(closeBtn);

    wrap.appendChild(toast);
    const lifespan = content instanceof Node ? 9000 : 5000;
    setTimeout(() => dismissToast(toast), lifespan);
}

function dismissToast(toast) {
    if (toast.classList.contains('is-leaving')) return;
    toast.classList.add('is-leaving');
    toast.addEventListener('animationend', () => toast.remove(), { once: true });
}

// Multi-photo picker: lets the user build up a set of photos by clicking
// "Add photos" repeatedly (or by multi-selecting in one go) instead of
// relying on the native file input, which silently replaces the whole
// selection every time it's reopened.
function initPhotoPickers() {
    document.querySelectorAll('[data-photo-picker]').forEach((wrap) => {
        if (wrap.dataset.bound) return;
        wrap.dataset.bound = '1';

        const input = wrap.querySelector('[data-photo-input]');
        const grid = wrap.querySelector('[data-photo-grid]');
        const addBtn = wrap.querySelector('[data-photo-add]');
        let files = [];

        addBtn.addEventListener('click', () => input.click());

        input.addEventListener('change', () => {
            Array.from(input.files).forEach((file) => files.push(file));
            syncInput();
            render();
        });

        function syncInput() {
            const dt = new DataTransfer();
            files.forEach((file) => dt.items.add(file));
            input.files = dt.files;
        }

        function render() {
            grid.innerHTML = '';
            files.forEach((file, i) => {
                const url = URL.createObjectURL(file);
                const item = document.createElement('div');
                item.className = 'fx-photo-thumb';
                item.innerHTML = '<img src="' + url + '" alt="">' +
                    '<button type="button" aria-label="Remove photo">&times;</button>' +
                    (i === 0 ? '<span class="fx-photo-cover">Cover</span>' : '');
                item.querySelector('button').addEventListener('click', () => {
                    files.splice(i, 1);
                    syncInput();
                    render();
                });
                grid.appendChild(item);
            });
            addBtn.textContent = files.length ? '+ Add more photos' : '+ Add photos';
        }
    });
}
