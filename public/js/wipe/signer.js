/* Protocol v3 (no expiry) and legacy v2 signer. No network or persistent storage. */
(function (root) {
  'use strict';
  const uuid = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/;
  const encode = bytes => btoa(String.fromCharCode(...bytes)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  function decode(text) {
    if (!/^[A-Za-z0-9_-]+$/.test(text) || text.length > 5500) throw new Error('Invalid token encoding');
    const result = Uint8Array.from(atob(text.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));
    if (encode(result) !== text) throw new Error('Non-canonical token encoding');
    return result;
  }
  function parse(token) {
    if (typeof token !== 'string' || !token.startsWith('spw2.')) throw new Error('A new spw2 token is required');
    const bytes = decode(token.slice(5));
    try {
      if (bytes.length > 4096) throw new Error('Token too large');
      const view = new DataView(bytes.buffer); let offset = 0;
      function number(size) {
        if (offset + size > bytes.length) throw new Error('Incomplete token');
        const n = size === 4 ? view.getUint32(offset) : view.getUint16(offset); offset += size; return n;
      }
      function take(size) {
        if (size < 1 || size > 1024 || offset + size > bytes.length) throw new Error('Invalid field length');
        const result = bytes.slice(offset, offset + size); offset += size; return result;
      }
      if (number(4) !== 1) throw new Error('Unsupported token version');
      // Java writeUTF is identical to UTF-8 for this strictly ASCII UUID field.
      const id = new TextDecoder('utf-8', {fatal: true}).decode(take(number(2)));
      if (!uuid.test(id)) throw new Error('Invalid registration ID');
      const publicKey = take(number(4)); const privateKey = take(number(4));
      if (offset !== bytes.length) { privateKey.fill(0); throw new Error('Unexpected token data'); }
      return {id, publicKey, privateKey};
    } finally { bytes.fill(0); }
  }
  function canonical(command) {
    const version = command.protocol;
    if (![2, 3].includes(version) || command.action !== 'wipe' || command.key_version !== 1 ||
        !uuid.test(command.registration_id) || !uuid.test(command.command_id) ||
        !/^[A-Za-z0-9_-]{43}$/.test(command.key_id) ||
        !Number.isSafeInteger(command.issued_at) || command.issued_at <= 0 || command.issued_at > 253402300799 ||
        (version === 2 && (!Number.isSafeInteger(command.expires_at) || command.expires_at <= command.issued_at ||
        command.expires_at - command.issued_at > 86400))) throw new Error('Invalid command');
    const keys = ['protocol', 'action', 'registration_id', 'key_version', 'key_id', 'command_id', 'issued_at'];
    if (version === 2) keys.push('expires_at');
    if (Object.hasOwn(command, 'signature')) keys.push('signature');
    if (Object.keys(command).sort().join(',') !== keys.sort().join(',')) throw new Error('Invalid command fields');
    const lines = ['STELLAR-PROTECT-REMOTE-WIPE-V' + version, 'wipe',
      command.registration_id, String(command.key_version), command.key_id, command.command_id,
      String(command.issued_at)];
    if (version === 2) lines.push(String(command.expires_at));
    lines.push('');
    return new TextEncoder().encode(lines.join('\n'));
  }
  async function prepare(token) {
    if (!root.crypto?.subtle) throw new Error('Web Crypto is unavailable; use a secure browser context');
    const parsed = parse(token);
    try {
      const algorithm = {name: 'ECDSA', namedCurve: 'P-256'};
      const privateKey = await crypto.subtle.importKey('pkcs8', parsed.privateKey, algorithm, false, ['sign']);
      const publicKey = await crypto.subtle.importKey('spki', parsed.publicKey, algorithm, false, ['verify']);
      const challenge = crypto.getRandomValues(new Uint8Array(32));
      const proof = await crypto.subtle.sign({name: 'ECDSA', hash: 'SHA-256'}, privateKey, challenge);
      if (!await crypto.subtle.verify({name: 'ECDSA', hash: 'SHA-256'}, publicKey, proof, challenge)) {
        throw new Error('Token keys do not match');
      }
      const keyId = encode(new Uint8Array(await crypto.subtle.digest('SHA-256', parsed.publicKey)));
      return Object.freeze({registrationId: parsed.id, keyId,
        async sign(validForSeconds) {
          // New user flows omit the argument and always sign v3 without expiry.
          // Explicit lifetimes remain only for compatibility with existing v2 tools.
          const persistent = validForSeconds === undefined;
          if (!persistent && ![900, 3600, 86400].includes(validForSeconds)) throw new Error('Unsupported validity period');
          const now = Math.floor(Date.now() / 1000);
          const command = {protocol: persistent ? 3 : 2, action: 'wipe', registration_id: parsed.id,
            key_version: 1, key_id: keyId, command_id: crypto.randomUUID(),
            issued_at: now};
          if (!persistent) command.expires_at = now + validForSeconds;
          const signature = new Uint8Array(await crypto.subtle.sign(
            {name: 'ECDSA', hash: 'SHA-256'}, privateKey, canonical(command)));
          if (signature.length !== 64) throw new Error('Unexpected signature format');
          return {...command, signature: encode(signature)};
        }
      });
    } finally { parsed.privateKey.fill(0); }
  }
  root.WipeSigner = Object.freeze({prepare, canonical});
})(globalThis);
