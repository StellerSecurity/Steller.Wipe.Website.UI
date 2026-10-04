import {SignedWipeFlow, tokenKind} from './flow.js';

const entry = document.getElementById('wipe-token-entry');
const token = document.getElementById('token');
const continueButton = document.getElementById('token-continue');
const panel = document.getElementById('signed-wipe-panel');
const confirmation = document.getElementById('signed-wipe-confirmation');
const phrase = document.getElementById('signed-wipe-phrase');
const status = document.getElementById('signed-wipe-status');
const submit = document.getElementById('signed-wipe-submit');
const retry = document.getElementById('signed-wipe-retry');
const close = document.getElementById('signed-wipe-close');
const messages = {
    checking: 'Checking your token on this device…',
    confirm: 'Token checked locally. Nothing has been sent. Check that this is the token for the phone you want to erase.',
    sending: 'Sending your signed wipe request…',
    invalid: 'The token could not be used. Copy the complete token from Protect and use an up-to-date browser over HTTPS.',
    retry: 'No confirmed receipt. The request may already have been received. You can retry the same request below. Closing this page will not cancel a received request.',
    done: 'Wipe request received. It will stay queued until the phone comes online and receives it. This receipt does not confirm that the phone has been erased.',
};

function render(flow) {
    const busy = ['checking', 'sending'].includes(flow.state);
    entry.hidden = flow.state !== 'entry';
    panel.hidden = flow.state === 'entry';
    confirmation.hidden = flow.state !== 'confirm';
    status.textContent = messages[flow.state] || '';
    status.setAttribute('aria-busy', String(busy));
    document.getElementById('signed-wipe-device').textContent = flow.registrationId;
    document.getElementById('signed-wipe-target').hidden = !flow.registrationId;
    retry.hidden = flow.state !== 'retry';
    close.disabled = flow.state === 'sending';
    close.textContent = flow.state === 'confirm' || busy ? 'Cancel' : 'Close';
    submit.disabled = flow.state !== 'confirm' || phrase.value !== 'WIPE';
    if (flow.state === 'confirm') phrase.focus();
    else if (!busy && flow.state !== 'entry') status.focus();
}

const flow = new SignedWipeFlow({changed: render});
continueButton.disabled = false;
// The secret input deliberately has no name: missing/failed JS cannot post it.
entry.addEventListener('submit', event => { event.preventDefault(); continueWithToken(); });
continueButton.addEventListener('click', continueWithToken);

async function continueWithToken() {
    if (flow.state !== 'entry' || continueButton.disabled) return;
    if (!token.reportValidity()) return;
    let value = token.value.trim();
    token.value = '';
    const kind = tokenKind(value);
    if (kind === 'legacy') {
        const legacy = document.getElementById('legacy-token-login');
        const field = legacy.elements.namedItem('token');
        field.value = value;
        value = '';
        continueButton.disabled = true;
        try {
            HTMLFormElement.prototype.submit.call(legacy);
        } finally {
            field.value = '';
        }
        return;
    }
    if (kind === 'invalid') { flow.update('invalid'); return; }
    const pending = flow.inspect(value);
    value = '';
    await pending;
}

phrase.addEventListener('input', () => {
    submit.disabled = flow.state !== 'confirm' || phrase.value !== 'WIPE';
});
confirmation.addEventListener('submit', event => {
    event.preventDefault();
    const answer = phrase.value;
    phrase.value = '';
    void flow.confirm(answer);
});
retry.addEventListener('click', () => { void flow.retry(); });
close.addEventListener('click', () => {
    if (flow.state === 'sending') return;
    reset();
    token.focus();
});

function reset() {
    flow.clear();
    token.value = '';
    phrase.value = '';
    document.getElementById('legacy-token-login').elements.namedItem('token').value = '';
    continueButton.disabled = false;
    render(flow);
}
// Do not restore secrets or a destructive confirmation from the back/forward cache.
window.addEventListener('pagehide', reset);
window.addEventListener('pageshow', event => { if (event.persisted) reset(); });
reset();
