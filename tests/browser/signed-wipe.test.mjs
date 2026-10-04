import test from 'node:test';
import assert from 'node:assert/strict';
import {createHash, generateKeyPairSync, verify} from 'node:crypto';
import {readFileSync} from 'node:fs';
import {SignedWipeFlow, tokenKind, sendCommand, endpoint} from '../../public/js/wipe/flow.js';
import {identity} from './fixtures.mjs';

const sample = identity();
const deferred = () => { let resolve; const promise = new Promise(r => { resolve = r; }); return {promise, resolve}; };

test('legacy tokens remain legacy; reserved, malformed and unknown versions never fall back', () => {
    for (const token of ['old-token', 'a'.repeat(512), '  legacy-auth-token  ']) assert.equal(tokenKind(token), 'legacy');
    for (const token of ['', ' ', null, {}, 'spw1.abc', 'spw3.abc', 'SPW2.abc', 'spw2', 'spw', 'a'.repeat(513)]) assert.equal(tokenKind(token), 'invalid');
    assert.equal(tokenKind(sample.token), 'signed');
    assert.equal(tokenKind('spw2.broken'), 'signed');
});

test('real browser signer matches the Android wire format and independently verifies', async () => {
    const signer = await WipeSigner.prepare(sample.token);
    for (const ttl of [900, 3600, 86400]) {
        const command = await signer.sign(ttl);
        assert.equal(command.registration_id, sample.id);
        assert.equal(command.key_id, createHash('sha256').update(sample.pub).digest('base64url'));
        assert.equal(command.expires_at - command.issued_at, ttl);
        assert.equal(Object.keys(command).length, 9);
        assert.equal(Buffer.from(command.signature, 'base64url').length, 64);
        assert.ok(verify('sha256', WipeSigner.canonical(command), {key: sample.pair.publicKey, dsaEncoding: 'ieee-p1363'}, Buffer.from(command.signature, 'base64url')));
        assert.equal(JSON.stringify(command).includes(sample.token), false);
    }
});

test('damaged tokens and mismatched key pairs fail locally', async () => {
    const mismatched = identity({privateKey: generateKeyPairSync('ec', {namedCurve: 'prime256v1'}).privateKey});
    const changedVersion = Buffer.from(sample.binary); changedVersion.writeUInt32BE(2);
    const wrongLength = Buffer.from(sample.binary); wrongLength.writeUInt32BE(0xffffffff, 42);
    const invalid = [mismatched.token, 'spw2.', 'spw2.@@', sample.token + '=', sample.token + ' ',
        'spw2.' + changedVersion.toString('base64url'), 'spw2.' + wrongLength.toString('base64url'),
        'spw2.' + Buffer.concat([sample.binary, Buffer.of(0)]).toString('base64url'),
        'spw2.' + 'A'.repeat(5501)];
    for (let length = 0; length < sample.binary.length; length++) invalid.push('spw2.' + sample.binary.subarray(0, length).toString('base64url'));
    for (const token of invalid) await assert.rejects(WipeSigner.prepare(token));
});

test('load, token validation, bad confirmation and cancel cause zero sends', async () => {
    let sends = 0;
    const flow = new SignedWipeFlow({send: async () => { sends++; }});
    assert.equal(sends, 0);
    await flow.inspect(sample.token);
    assert.equal(flow.state, 'confirm');
    for (const phrase of ['', 'wipe', 'WIPE ', 'YES']) await flow.confirm(phrase);
    flow.clear();
    await flow.confirm('WIPE');
    await flow.retry();
    assert.equal(sends, 0);
});

test('invalid signed tokens do not make requests', async () => {
    let sends = 0;
    const flow = new SignedWipeFlow({send: async () => { sends++; }});
    for (const token of ['legacy', 'spw3.test', 'spw2.broken']) {
        await flow.inspect(token);
        assert.equal(flow.state, 'invalid');
        await flow.confirm('WIPE');
    }
    assert.equal(sends, 0);
});

test('rapid repeated confirmation signs/sends once; success releases key and command', async () => {
    const pending = deferred(); let sends = 0;
    const flow = new SignedWipeFlow({send: async () => { sends++; await pending.promise; }});
    await flow.inspect(sample.token);
    const first = flow.confirm('WIPE');
    for (let i = 0; i < 20; i++) { await flow.confirm('WIPE'); await flow.retry(); }
    pending.resolve(); await first;
    assert.equal(sends, 1);
    assert.equal(flow.state, 'done');
    assert.equal(flow.signer, null);
    assert.equal(flow.command, null);
});

test('timeout, invalid response and retry reuse the exact signed order', async () => {
    const bodies = [];
    const flow = new SignedWipeFlow({send: async c => { bodies.push(JSON.stringify(c)); if (bodies.length < 3) throw new Error('ambiguous response'); }});
    await flow.inspect(sample.token);
    await flow.confirm('WIPE');
    assert.equal(flow.state, 'retry');
    await flow.confirm('WIPE'); // Cannot issue another command in retry state.
    await flow.retry(); await flow.retry();
    assert.equal(bodies.length, 3);
    assert.equal(new Set(bodies).size, 1);
    assert.equal(flow.state, 'done');
});

test('confirmed orders have no expiry and retries stay identical after years offline', async () => {
    const realNow = Date.now;
    let elapsed = 0;
    const base = realNow();
    Date.now = () => base + elapsed;
    const bodies = [];
    try {
        const flow = new SignedWipeFlow({send: async c => { bodies.push(JSON.stringify(c)); throw new Error('Offline'); }});
        await flow.inspect(sample.token);
        elapsed += 600000;
        await flow.confirm('WIPE');
        assert.equal(flow.state, 'retry');
        assert.equal(flow.command.protocol, 3);
        assert.equal(Object.hasOwn(flow.command, 'expires_at'), false);
        elapsed += 5 * 365 * 86400000;
        await flow.retry();
        assert.equal(flow.state, 'retry');
        assert.equal(bodies.length, 2);
        assert.equal(bodies[0], bodies[1]);
    } finally { Date.now = realNow; }
});

test('new signatures use a separate domain and cannot revive expired v2 orders', async () => {
    const signer = await WipeSigner.prepare(sample.token);
    const command = await signer.sign();
    assert.equal(command.protocol, 3);
    assert.equal(Object.keys(command).length, 8);
    assert.equal(Object.hasOwn(command, 'expires_at'), false);
    assert.ok(verify('sha256', WipeSigner.canonical(command), {key: sample.pair.publicKey, dsaEncoding: 'ieee-p1363'}, Buffer.from(command.signature, 'base64url')));
    assert.throws(() => WipeSigner.canonical({...command, expires_at: 0}));
    const old = await signer.sign(900);
    const forged = {...old, protocol: 3}; delete forged.expires_at;
    assert.equal(verify('sha256', WipeSigner.canonical(forged), {key: sample.pair.publicKey, dsaEncoding: 'ieee-p1363'}, Buffer.from(old.signature, 'base64url')), false);
});

test('cancel/back navigation during asynchronous token validation cannot revive confirmation', async () => {
    const pending = deferred(); let sends = 0;
    const flow = new SignedWipeFlow({prepare: () => pending.promise, send: async () => { sends++; }});
    const check = flow.inspect(sample.token);
    flow.clear(); pending.resolve(await WipeSigner.prepare(sample.token)); await check;
    assert.equal(flow.state, 'entry'); assert.equal(flow.signer, null); assert.equal(sends, 0);
});

test('pagehide during asynchronous signing prevents the request', async () => {
    const pending = deferred(); let sends = 0;
    const signer = await WipeSigner.prepare(sample.token);
    const flow = new SignedWipeFlow({prepare: async () => ({...signer, sign: () => pending.promise}), send: async () => { sends++; }});
    await flow.inspect(sample.token);
    const task = flow.confirm('WIPE');
    flow.clear(); pending.resolve(await signer.sign()); await task;
    assert.equal(sends, 0); assert.equal(flow.command, null);
});

test('retry double clicks cannot send parallel requests', async () => {
    const pending = deferred(); let sends = 0;
    const flow = new SignedWipeFlow({send: async () => { if (++sends === 1) throw new Error(); await pending.promise; }});
    await flow.inspect(sample.token); await flow.confirm('WIPE');
    const task = flow.retry(); await flow.retry(); await flow.retry();
    pending.resolve(); await task;
    assert.equal(sends, 2);
});

test('transport sends only a signed JSON command to the fixed relay, never credentials', async () => {
    const previous = globalThis.fetch;
    const command = await (await WipeSigner.prepare(sample.token)).sign();
    const calls = [];
    try {
        globalThis.fetch = async (url, options) => { calls.push({url, options}); return {ok: true, json: async () => ({accepted: true})}; };
        await sendCommand(command, new AbortController().signal);
        assert.equal(calls.length, 1);
        const {url, options} = calls[0];
        assert.equal(url, endpoint);
        assert.equal(options.credentials, 'omit'); assert.equal(options.redirect, 'error');
        assert.equal(options.referrerPolicy, 'no-referrer'); assert.equal(options.cache, 'no-store');
        assert.deepEqual(options.headers, {'Content-Type': 'application/json'});
        assert.deepEqual(JSON.parse(options.body), command);
        assert.equal(options.body.includes(sample.token), false);
        for (const response of [{ok: false}, {ok: true, json: async () => ({})}, {ok: true, json: async () => ({accepted: 'true'})}, {ok: true, json: async () => { throw new Error(); }}]) {
            globalThis.fetch = async () => response;
            await assert.rejects(sendCommand(command, new AbortController().signal));
        }
    } finally { globalThis.fetch = previous; }
});

test('the private token field cannot be submitted by native forms without JS', () => {
    const html = readFileSync(new URL('../../resources/views/auth/login.blade.php', import.meta.url), 'utf8');
    const input = html.match(/<input[^>]*id="token"[^>]*>/)[0];
    assert.doesNotMatch(input, /\bname=/);
    assert.match(html, /id="token-continue" type="button" disabled/);
    assert.match(html, /id="legacy-token-login"[^>]*method="POST"/);
    assert.match(html, /id="signed-wipe-submit" type="submit" disabled/);
});
