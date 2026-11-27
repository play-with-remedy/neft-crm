<style>
    .crm-brand {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 32px;
        font-size: 24px;
        font-weight: 700;
        line-height: 1;
        letter-spacing: 0.02em;
        white-space: nowrap;
    }

    .crm-brand > span {
        font-family: 'Palatino Linotype', Palatino, 'Book Antiqua', serif;
        font-style: normal;
    }

    .crm-brand-icon {
        flex: 0 0 32px;
        width: 32px;
        height: 32px;
    }

    .crm-sidebar {
        --crm-sidebar-surface: #fff;
        --crm-sidebar-border: #e5e7eb;
        --crm-sidebar-muted: #6b7280;
        --crm-sidebar-text: #111827;
        --crm-sidebar-hover: #f3f4f6;
        --crm-sidebar-accent: #b45309;
        background: var(--crm-sidebar-surface);
        border-inline-end: 1px solid var(--crm-sidebar-border);
    }

    .dark .crm-sidebar {
        --crm-sidebar-surface: #18181b;
        --crm-sidebar-border: #2b2b30;
        --crm-sidebar-muted: #a1a1aa;
        --crm-sidebar-text: #f4f4f5;
        --crm-sidebar-hover: rgba(255, 255, 255, 0.06);
        --crm-sidebar-accent: #fbbf24;
    }

    .crm-sidebar-layout {
        display: grid;
        grid-template-columns: 56px minmax(0, 1fr);
        flex: 1;
        min-height: 0;
        overflow: hidden;
    }

    .crm-sidebar.fi-sidebar.crm-sidebar-collapsed {
        width: 56px;
    }

    .crm-sidebar-collapsed .crm-sidebar-layout {
        grid-template-columns: minmax(0, 1fr);
    }

    .crm-sidebar-collapsed .crm-sidebar-rail {
        border-inline-end: 0;
    }

    .crm-sidebar.crm-sidebar-collapsed .crm-sidebar-menu,
    .crm-sidebar.crm-sidebar-collapsed .fi-sidebar-header-logo-ctn {
        display: none;
    }

    .crm-sidebar.crm-sidebar-collapsed .fi-sidebar-header {
        justify-content: center;
        padding-inline: 6px;
    }

    .crm-sidebar-rail {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        min-height: 0;
        padding: 20px 6px;
        overflow-x: hidden;
        overflow-y: auto;
        border-inline-end: 1px solid var(--crm-sidebar-border);
        scrollbar-width: thin;
    }

    .crm-sidebar-group-button {
        display: grid;
        place-items: center;
        position: relative;
        flex: 0 0 auto;
        width: 42px;
        height: 42px;
        padding: 0;
        border: 0;
        border-radius: 9px;
        background: transparent;
        color: var(--crm-sidebar-muted);
        cursor: pointer;
        transition: background-color 120ms, color 120ms;
    }

    .crm-sidebar-group-button > .fi-icon {
        width: 21px;
        height: 21px;
    }

    .crm-sidebar-group-button:hover,
    .crm-sidebar-group-button.is-selected {
        background: var(--crm-sidebar-hover);
        color: var(--crm-sidebar-text);
    }

    .crm-sidebar-group-button.is-selected {
        color: var(--crm-sidebar-accent);
    }

    .crm-sidebar-group-button.is-selected::before {
        content: '';
        position: absolute;
        inset-inline-start: -6px;
        top: 12px;
        bottom: 12px;
        width: 3px;
        border-radius: 3px;
        background: var(--crm-sidebar-accent);
    }

    .crm-sidebar-group-button:focus-visible {
        outline: 2px solid var(--crm-sidebar-accent);
        outline-offset: 2px;
    }

    .crm-sidebar-menu.fi-sidebar-nav {
        display: block;
        min-width: 0;
        padding: 24px 12px;
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-gutter: auto;
        scrollbar-width: thin;
    }

    .crm-sidebar-heading {
        margin: 0 8px 20px;
        color: var(--crm-sidebar-text);
        font-size: 14px;
        font-weight: 600;
        line-height: 1.5;
    }

    .crm-sidebar-items {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .crm-sidebar-menu .fi-sidebar-item-btn {
        justify-content: flex-start;
        gap: 10px;
        min-height: 40px;
        padding: 8px;
    }

    .crm-sidebar-menu .fi-sidebar-item-icon {
        width: 20px;
        height: 20px;
    }

    .crm-sidebar-menu .fi-sidebar-item-label {
        white-space: normal;
        overflow: visible;
        text-overflow: clip;
        line-height: 1.5;
    }

    @media (min-width: 1024px) {
        .fi-topbar {
            padding-inline-start: 0;
        }

        .fi-topbar-start .fi-logo {
            margin-inline-start: 0;
        }

        .fi-topbar .crm-brand {
            display: grid;
            grid-template-columns: 56px auto;
            column-gap: 0;
        }

        .fi-topbar .crm-brand-icon {
            justify-self: center;
        }

        .crm-sidebar-mobile-close {
            display: none;
        }
    }

    @media (max-width: 1023px) {
        .crm-brand {
            font-size: 22px;
        }

        .fi-topbar-start {
            display: flex;
            margin-inline-end: 12px;
        }

        .crm-sidebar.fi-sidebar {
            width: min(var(--sidebar-width), calc(100vw - 16px));
        }

        .crm-sidebar .fi-sidebar-header {
            padding-inline: 16px;
        }

        .crm-sidebar-group-button {
            width: 44px;
            height: 44px;
        }

        .crm-sidebar-rail {
            padding-inline: 5px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .crm-sidebar-group-button {
            transition: none;
        }
    }
</style>

<script>
    window.crmTwoLevelSidebar = (groups, initial, active, storageKey) => ({
        selected: initial,

        init() {
            try {
                const saved = localStorage.getItem(storageKey);
                if (saved === 'collapsed') this.selected = null;
                else if (!active && groups.includes(saved)) this.selected = saved;
            } catch (_) {}

            this.$nextTick(() => this.scrollToActiveItem());
        },

        select(group) {
            if (!groups.includes(group)) return;
            this.selected = this.selected === group ? null : group;

            try {
                localStorage.setItem(storageKey, this.selected ?? 'collapsed');
            } catch (_) {}

            this.$refs.menu.scrollTop = 0;
            this.$nextTick(() => this.scrollToActiveItem());
        },

        focusGroup(button, direction) {
            const buttons = [...button.parentElement.querySelectorAll('button')];
            buttons[(buttons.indexOf(button) + direction + buttons.length) % buttons.length]?.focus();
        },

        scrollToActiveItem() {
            if (this.selected === null) return;
            const menu = this.$refs.menu;
            const panel = menu.querySelector('[data-sidebar-panel="' + this.selected + '"]');
            const item = panel?.querySelector('.fi-sidebar-item.fi-active');
            if (!item) return;

            const row = item.getBoundingClientRect();
            const viewport = menu.getBoundingClientRect();
            if (row.top < viewport.top || row.bottom > viewport.bottom) {
                menu.scrollTop += row.top - viewport.top - (menu.clientHeight - row.height) / 2;
            }
        },
    });
</script>
