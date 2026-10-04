import {generateKeyPairSync, randomUUID} from 'node:crypto';

// Synthetic identities only. Matches Android DataOutputStream's token format.
export function identity({id = randomUUID(), pair = generateKeyPairSync('ec', {namedCurve: 'prime256v1'}), privateKey = pair.privateKey} = {}) {
    const pub = pair.publicKey.export({type: 'spki', format: 'der'});
    const secret = privateKey.export({type: 'pkcs8', format: 'der'});
    const integer = n => { const b = Buffer.alloc(4); b.writeUInt32BE(n); return b; };
    const length = Buffer.alloc(2); length.writeUInt16BE(id.length);
    const binary = Buffer.concat([integer(1), length, Buffer.from(id), integer(pub.length), pub, integer(secret.length), secret]);
    return {token: 'spw2.' + binary.toString('base64url'), binary, id, pub, pair};
}
