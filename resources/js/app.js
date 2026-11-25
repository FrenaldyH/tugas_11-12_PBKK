import './bootstrap';
import hljs from 'highlight.js/lib/common';

const XL = '(min-width: 1280px)';

// Read/write a per-browser preference; storage can be blocked (private mode), so never let it throw
const remember = {
    get(key) { try { return localStorage.getItem(key); } catch { return null; } },
    set(key, value) { try { localStorage.setItem(key, value); } catch { /* ignore */ } },
};

// Alpine comes with Livewire (do not import it again). Register helpers before Alpine starts.
document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // Workspace layout state shared by all Livewire components on the page:
    // sidebar = conversation drawer (below lg), panel = inspector (Kode / Scan), tab = active inspector tab.
    const wide = window.matchMedia(XL).matches;
    const saved = remember.get('promptlab.panel');
    Alpine.store('ui', {
        sidebar: false,
        panel: wide && saved !== '0',
        tab: 'code',
        togglePanel(force) {
            this.panel = force ?? !this.panel;
            if (window.matchMedia(XL).matches) remember.set('promptlab.panel', this.panel ? '1' : '0');
        },
        open(tab) {
            this.tab = tab;
            this.togglePanel(true);
        },
        closeDrawers() {
            this.sidebar = false;
            if (!window.matchMedia(XL).matches) this.panel = false;
        },
    });

    // x-highlight: color every <pre><code> inside the element once (Challenge 2) and give each block a small
    // header with its language and a copy button. Elements using it are wire:ignore'd, so Livewire's DOM
    // morphing never strips the colors again.
    Alpine.directive('highlight', (el, { modifiers }) => {
        el.querySelectorAll('pre code').forEach((block) => {
            if (block.dataset.highlighted) return;
            hljs.highlightElement(block);

            if (modifiers.includes('bare')) return; // code panel has its own header
            const pre = block.parentElement;
            const lang = (block.className.match(/language-([\w#+.-]+)/) || [])[1] || 'kode';
            const head = document.createElement('div');
            head.className = 'code-head';
            head.innerHTML = '<span></span><button type="button">Salin</button>';
            head.querySelector('span').textContent = lang;
            head.querySelector('button').addEventListener('click', (event) => {
                navigator.clipboard?.writeText(block.innerText);
                event.target.textContent = 'Tersalin';
                setTimeout(() => (event.target.textContent = 'Salin'), 1500);
            });
            pre.before(head);
        });
    });

    // x-autoscroll: keep a chat list scrolled to the newest bubble, also while an answer streams in,
    // unless the user scrolled up to read older messages.
    Alpine.directive('autoscroll', (el, _, { cleanup }) => {
        const nearBottom = () => el.scrollHeight - el.scrollTop - el.clientHeight < 120;
        let follow = true;
        const onScroll = () => { follow = nearBottom(); };
        const observer = new MutationObserver(() => {
            if (follow) el.scrollTop = el.scrollHeight;
        });

        el.scrollTop = el.scrollHeight;
        el.addEventListener('scroll', onScroll);
        observer.observe(el, { childList: true, subtree: true, characterData: true });
        cleanup(() => {
            observer.disconnect();
            el.removeEventListener('scroll', onScroll);
        });
    });

    // x-autogrow: textarea grows with its content up to a max height, and shrinks back when cleared
    Alpine.directive('autogrow', (el, _, { cleanup }) => {
        const fit = () => {
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 220) + 'px';
        };
        const observer = new MutationObserver(fit);
        el.addEventListener('input', fit);
        observer.observe(el, { attributes: true, attributeFilter: ['value'] });
        const later = () => requestAnimationFrame(fit);
        window.addEventListener('conversation-saved', later);
        later();
        cleanup(() => {
            el.removeEventListener('input', fit);
            window.removeEventListener('conversation-saved', later);
            observer.disconnect();
        });
    });
});
