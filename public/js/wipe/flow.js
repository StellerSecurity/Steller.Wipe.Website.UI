import './signer.js';

// Never route an unknown/new token through the legacy token lookup.
export function tokenKind(value) {
    if (typeof value !== 'string') return 'invalid';
    const token = value.trim();
    if (token.startsWith('spw2.')) return 'signed';
    if (!token || /^spw/i.test(token) || token.length > 512) return 'invalid';
    return 'legacy';
}

export const endpoint = 'https://stellerprotectuiappapiprod.azurewebsites.net/api/v1/signed-wipe/commands';

// All state is ephemeral. No token, key or command goes in a URL or storage.
export class SignedWipeFlow {
    constructor({prepare = globalThis.WipeSigner.prepare, send = sendCommand, changed = () => {}} = {}) {
        this.prepare = prepare;
        this.send = send;
        this.changed = changed;
        this.generation = 0;
        this.clear();
    }

    clear() {
        ++this.generation;
        this.abort?.abort();
        this.abort = null;
        this.signer = null;
        this.command = null;
        this.registrationId = '';
        this.state = 'entry';
    }

    update(state) {
        this.state = state;
        this.changed(this);
    }

    async inspect(token) {
        if (this.state === 'sending' || this.state === 'checking') return;
        this.clear();
        const generation = this.generation;
        this.update('checking');
        try {
            if (tokenKind(token) !== 'signed') throw new Error('Invalid token');
            const pending = this.prepare(token.trim());
            token = ''; // Also clear the visible input before calling inspect.
            const signer = await pending;
            if (generation !== this.generation) return;
            this.signer = signer;
            this.registrationId = signer.registrationId;
            this.update('confirm');
        } catch {
            if (generation === this.generation) this.update('invalid');
        }
    }

    async confirm(phrase) {
        if (this.state !== 'confirm' || phrase !== 'WIPE' || !this.signer) return;
        const generation = this.generation;
        const signer = this.signer;
        this.signer = null;
        this.update('sending'); // Synchronous guard against double clicks / Enter.
        try {
            const command = await signer.sign();
            if (generation !== this.generation) return;
            this.command = Object.freeze(command);
        } catch {
            if (generation === this.generation) this.update('invalid');
            return;
        }
        await this.deliver(generation);
    }

    async retry() {
        if (this.state !== 'retry' || !this.command) return;
        this.update('sending');
        await this.deliver(this.generation);
    }

    async deliver(generation) {
        this.abort = new AbortController();
        try {
            await this.send(this.command, this.abort.signal);
            if (generation !== this.generation) return;
            this.command = null;
            this.update('done');
        } catch {
            // An ambiguous response may mean the relay already accepted it.
            // Retrying reuses the exact command ID, timestamps and signature.
            if (generation === this.generation) this.update('retry');
        }
    }
}

export async function sendCommand(command, signal) {
    const controller = new AbortController();
    const cancel = () => controller.abort();
    if (signal.aborted) throw new Error('Cancelled');
    signal.addEventListener('abort', cancel, {once: true});
    const timeout = setTimeout(cancel, 20000);
    try {
        const response = await fetch(endpoint, {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(command), credentials: 'omit', redirect: 'error',
            referrerPolicy: 'no-referrer', cache: 'no-store', signal: controller.signal,
        });
        if (!response.ok || (await response.json()).accepted !== true) {
            throw new Error('No confirmed receipt');
        }
    } finally {
        clearTimeout(timeout);
        signal.removeEventListener('abort', cancel);
    }
}
