import './bootstrap';
import hljs from 'highlight.js/lib/common';

// Alpine comes with Livewire (do not import it again). Register helpers before Alpine starts.
document.addEventListener('alpine:init', () => {
    // x-highlight: color every <pre><code> inside the element once (Challenge 2: syntax highlighting).
    // Elements using it are wire:ignore'd, so Livewire's DOM morphing never strips the colors again.
    window.Alpine.directive('highlight', (el) => {
        el.querySelectorAll('pre code').forEach((block) => {
            if (!block.dataset.highlighted) {
                hljs.highlightElement(block);
            }
        });
    });

    // x-autoscroll: keep a chat list scrolled to the newest bubble, also while an answer streams in,
    // unless the user scrolled up to read older messages.
    window.Alpine.directive('autoscroll', (el, _, { cleanup }) => {
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
});
