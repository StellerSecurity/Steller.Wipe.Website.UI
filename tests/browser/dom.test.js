// This fixture uses synthetic keys and mocks EVERY outgoing operation. It is
// served only by serve-preview.py, with connect-src/form-action 'none'.
const byId = id => document.getElementById(id);
const result = byId('test-results');
const calls = [], legacy = [];
let fail = true;
window.fetch = async (url, options) => {
    calls.push({url, body: options.body});
    if (fail) throw new Error('Synthetic timeout');
    return {ok: true, json: async () => ({accepted: true})};
};
HTMLFormElement.prototype.submit = function () { legacy.push(Object.fromEntries(new FormData(this))); };
const check = (condition, label) => { if (!condition) throw new Error(label); };
const tick = () => new Promise(resolve => setTimeout(resolve, 10));
async function until(condition) {
    for (let n = 0; n < 300; n++) { if (condition()) return; await tick(); }
    throw new Error('UI condition timed out');
}
async function tokenFixture() {
    const pair = await crypto.subtle.generateKey({name: 'ECDSA', namedCurve: 'P-256'}, true, ['sign', 'verify']);
    const pub = new Uint8Array(await crypto.subtle.exportKey('spki', pair.publicKey));
    const secret = new Uint8Array(await crypto.subtle.exportKey('pkcs8', pair.privateKey));
    const id = new TextEncoder().encode(crypto.randomUUID());
    const bytes = new Uint8Array(4 + 2 + id.length + 4 + pub.length + 4 + secret.length);
    const view = new DataView(bytes.buffer); let i = 0;
    view.setUint32(i, 1); i += 4; view.setUint16(i, id.length); i += 2;
    bytes.set(id, i); i += id.length;
    view.setUint32(i, pub.length); i += 4; bytes.set(pub, i); i += pub.length;
    view.setUint32(i, secret.length); i += 4; bytes.set(secret, i);
    return 'spw2.' + btoa(String.fromCharCode(...bytes)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}
function enter(value) { byId('token').value = value; byId('token-continue').click(); }
function phrase(value) { byId('signed-wipe-phrase').value = value; byId('signed-wipe-phrase').dispatchEvent(new Event('input')); }
function restored() { window.dispatchEvent(new PageTransitionEvent('pageshow', {persisted: true})); }

try {
    await import('/js/wipe/login.js');
    check(calls.length === 0 && legacy.length === 0, 'Page load must do nothing');
    check(byId('signed-wipe-submit').disabled, 'Confirmation starts disabled');
    enter('spw2.invalid');
    await until(() => byId('signed-wipe-status').textContent.includes('could not'));
    check(calls.length === 0 && legacy.length === 0, 'Bad token leaked or sent');
    byId('signed-wipe-close').click();
    enter('synthetic-old-token');
    check(legacy.length === 1 && legacy[0].token === 'synthetic-old-token' && legacy[0].method === '1' && legacy[0]._token === 'synthetic-csrf', 'Legacy form contract changed');
    check(byId('legacy-token-login').elements.namedItem('token').value === '', 'Legacy token not cleared');
    restored();
    const token = await tokenFixture();
    enter(token);
    await until(() => !byId('signed-wipe-confirmation').hidden);
    check(calls.length === 0 && legacy.length === 1, 'Validation sent the signed token');
    check(byId('token').value === '', 'Private input not cleared');
    phrase('wipe'); check(byId('signed-wipe-submit').disabled, 'Wrong phrase allowed');
    byId('signed-wipe-close').click();
    check(calls.length === 0, 'Cancel sent a wipe');
    enter(token);
    await until(() => !byId('signed-wipe-confirmation').hidden);
    phrase('WIPE'); byId('signed-wipe-submit').click(); byId('signed-wipe-submit').click();
    await until(() => !byId('signed-wipe-retry').hidden);
    check(calls.length === 1, 'Double click sent twice');
    const command = JSON.parse(calls[0].body);
    check(!calls[0].body.includes(token) && Object.keys(command).length === 8, 'Token leaked into request');
    check(command.protocol === 3 && !Object.hasOwn(command, 'expires_at'), 'Request must not expire');
    fail = false; byId('signed-wipe-retry').click(); byId('signed-wipe-retry').click();
    await until(() => byId('signed-wipe-status').textContent.startsWith('Wipe request received'));
    check(calls.length === 2 && calls[0].body === calls[1].body, 'Retry changed the signed command');
    window.dispatchEvent(new PageTransitionEvent('pagehide'));
    restored();
    check(byId('token').value === '' && byId('signed-wipe-phrase').value === '' && byId('signed-wipe-panel').hidden, 'Back/forward cache retained signing state');
    // Leave a safe synthetic confirmation on screen for visual review.
    enter(token);
    await until(() => !byId('signed-wipe-confirmation').hidden);
    result.textContent = 'PASS: browser validation, legacy form, confirmation, cancel, double click, same-command retry, token clearing and back/forward reset. No real network calls.';
} catch (error) {
    result.textContent = 'FAIL: ' + error.message;
}
