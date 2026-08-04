document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('mpTabToggleBtn');
    const tabsBar = document.getElementById('mpTabsBar');
    const contentArea = document.getElementById('mpContentArea');
    const headerText = document.getElementById('mpPageHeaderText');

    const STORAGE_KEY = 'mpTabsBarOpen';

    /* ── 1. Restore tabs-bar open/closed state ──────────────────
       Independent of which tab is active. Only the ⚙ button
       changes this — navigating tabs must never close it. */
    const isOpenStored = localStorage.getItem(STORAGE_KEY) === 'true';
    setTabsBarOpen(isOpenStored);

    function setTabsBarOpen(open) {
        if (!tabsBar || !toggleBtn) return;
        tabsBar.classList.toggle('mp-tabs-bar--open', open);
        toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (toggleBtn && tabsBar) {
        toggleBtn.addEventListener('click', () => {
            const willBeOpen = !tabsBar.classList.contains('mp-tabs-bar--open');
            setTabsBarOpen(willBeOpen);
            localStorage.setItem(STORAGE_KEY, willBeOpen ? 'true' : 'false');
        });
    }

    /* ── 2. AJAX tab switching (no full page reload) ───────────── */
    if (tabsBar && contentArea) {
        tabsBar.addEventListener('click', (e) => {
            const link = e.target.closest('.mp-tab-item[data-tab]');
            if (!link) return;

            // Already active — nothing to do
            if (link.classList.contains('mp-tab-item--active')) {
                e.preventDefault();
                return;
            }

            e.preventDefault();
            loadTab(link.getAttribute('href'), link.dataset.tab, true);
        });

        // Support browser back/forward between tabs
        window.addEventListener('popstate', (e) => {
            const tab = (e.state && e.state.tab) || 'now_showing';
            const url = (e.state && e.state.url) || window.location.href;
            loadTab(url, tab, false);
        });

        // Seed initial history state so popstate has something to compare against
        const initialTab = contentArea.dataset.activeTab || 'now_showing';
        history.replaceState({ tab: initialTab, url: window.location.href }, '', window.location.href);
    }

    function loadTab(url, tab, pushState) {
        contentArea.classList.add('mp-content-area--loading');

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((res) => {
                if (!res.ok) throw new Error('Failed to load tab content');
                return res.text();
            })
            .then((html) => {
                contentArea.innerHTML = html;
                contentArea.dataset.activeTab = tab;
                updateActiveTabClasses(tab);
                updateHeaderText(tab);

                // Update document title based on active tab
                if (tab === 'now_showing') {
                    document.title = 'Now Showing Movies';
                } else if (tab === 'proposals') {
                    document.title = 'Movie Proposals';
                } else if (tab === 'expired') {
                    document.title = 'Expired Movies';
                }

                if (pushState) {
                    history.pushState({ tab, url }, '', url);
                }
            })
            .catch(() => {
                // Fallback: if AJAX fails for any reason, do a real navigation
                window.location.href = url;
            })
            .finally(() => {
                contentArea.classList.remove('mp-content-area--loading');
            });
    }

    function updateActiveTabClasses(tab) {
        tabsBar.querySelectorAll('.mp-tab-item[data-tab]').forEach((el) => {
            el.classList.toggle('mp-tab-item--active', el.dataset.tab === tab);
        });
    }

    function updateHeaderText(tab) {
        if (!headerText) return;

        if (tab === 'now_showing') {
            headerText.innerHTML = `
                <h1 class="ac-page-header__title">Now <span>Showing</span></h1>
                <p class="ac-page-header__sub">
                    Movies currently live and scheduled across active cinema halls.
                </p>
            `;
        } else if (tab === 'proposals') {
            headerText.innerHTML = `
                <h1 class="ac-page-header__title">Movie <span>Proposals</span></h1>
                <p class="ac-page-header__sub">
                    Showtime proposals submitted by branch managers. Click to review and approve.
                </p>
            `;
        } else if (tab === 'expired') {
            headerText.innerHTML = `
                <h1 class="ac-page-header__title">Expired <span>Movies</span></h1>
                <p class="ac-page-header__sub">
                    Movies whose quota maximum end dates have passed across cinemas.
                </p>
            `;
        }
    }
});