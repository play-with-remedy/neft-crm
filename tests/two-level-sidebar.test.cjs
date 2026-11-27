const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function sidebar({ active = 'club', saved = 'reports', blockedStorage = false } = {}) {
    const values = new Map([['current-user', saved]]);
    const context = {
        window: {},
        localStorage: {
            getItem(key) { if (blockedStorage) throw new Error('Storage unavailable'); return values.get(key); },
            setItem(key, value) { if (blockedStorage) throw new Error('Storage unavailable'); values.set(key, value); },
        },
    };
    const source = fs.readFileSync(path.join(__dirname, '../resources/views/filament/partials/two-level-sidebar-assets.blade.php'), 'utf8');
    vm.runInNewContext(source.split('<script>')[1].split('</script>')[0], context);
    const state = context.window.crmTwoLevelSidebar(['club', 'sales', 'reports'], active || 'club', active, 'current-user');
    state.$refs = { menu: { scrollTop: 80, querySelector: () => null } };
    state.$nextTick = callback => callback();
    state.init();
    return { state, values };
}

test('the current route wins over a remembered group', () => {
    assert.equal(sidebar().state.selected, 'club');
});

test('a remembered authorized group is restored when the route has no menu item', () => {
    assert.equal(sidebar({ active: null }).state.selected, 'reports');
    assert.equal(sidebar({ active: null, saved: 'unauthorized-group' }).state.selected, 'club');
});

test('selecting a group updates the panel, resets scroll, and remembers the choice', () => {
    const { state, values } = sidebar();
    state.select('sales');
    assert.equal(state.selected, 'sales');
    assert.equal(state.$refs.menu.scrollTop, 0);
    assert.equal(values.get('current-user'), 'sales');
    state.select('unauthorized-group');
    assert.equal(state.selected, 'sales');
});

test('navigation works when browser storage is unavailable', () => {
    const { state } = sidebar({ active: null, blockedStorage: true });
    assert.equal(state.selected, 'club');
    state.select('reports');
    assert.equal(state.selected, 'reports');
});

test('clicking the selected group collapses it and clicking again reopens it', () => {
    const { state, values } = sidebar();
    state.select('club');
    assert.equal(state.selected, null);
    assert.equal(values.get('current-user'), 'collapsed');
    state.select('club');
    assert.equal(state.selected, 'club');
    assert.equal(values.get('current-user'), 'club');
});

test('explicitly collapsed state survives reload even when the route has an active group', () => {
    assert.equal(sidebar({ saved: 'collapsed' }).state.selected, null);
    const { state } = sidebar({ active: null, saved: 'collapsed' });
    assert.equal(state.selected, null);
    state.select('sales');
    assert.equal(state.selected, 'sales');
});

test('arrow keys move focus between group buttons and wrap at either end', () => {
    const { state } = sidebar();
    let focused = -1;
    const buttons = [0, 1, 2].map(index => ({ focus() { focused = index; } }));
    const parentElement = { querySelectorAll: () => buttons };
    buttons.forEach(button => { button.parentElement = parentElement; });
    state.focusGroup(buttons[2], 1);
    assert.equal(focused, 0);
    state.focusGroup(buttons[0], -1);
    assert.equal(focused, 2);
});
