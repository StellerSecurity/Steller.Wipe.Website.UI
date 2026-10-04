// Run with JAVA_HOME, PROTECT_APP and PROTECT_BACKEND set to existing checkouts.
// Synthetic identities only; invokes pure Java/PHP verifiers, never a phone/DB.
import assert from 'node:assert/strict';
import {execFileSync} from 'node:child_process';
import {mkdtempSync, writeFileSync, rmSync} from 'node:fs';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
import '../../public/js/wipe/signer.js';

const {JAVA_HOME, PROTECT_APP, PROTECT_BACKEND} = process.env;
if (!JAVA_HOME || !PROTECT_APP || !PROTECT_BACKEND) throw new Error('Set JAVA_HOME, PROTECT_APP and PROTECT_BACKEND to existing local tools/source.');
const temp = mkdtempSync(join(tmpdir(), 'website-wipe-compatibility-'));
try {
    writeFileSync(join(temp, 'WebsiteFixture.java'), `
import com.stellar.phone.protect.security.*;
import java.util.*;
public class WebsiteFixture {
 public static void main(String[] args) throws Exception {
  if(args.length == 0) { System.out.print(WipeSigningIdentity.generate().exportRecoveryToken()); return; }
  Map<String,Object> c=new HashMap<>();
  int protocol=Integer.parseInt(args[7]);
  c.put("protocol",protocol); c.put("action","wipe"); c.put("key_version",1);
  c.put("registration_id",args[1]); c.put("command_id",args[2]);
  c.put("issued_at",Long.parseLong(args[3])); if(protocol==2)c.put("expires_at",Long.parseLong(args[4]));
  c.put("key_id",args[5]); c.put("signature",args[6]);
  long received=Long.parseLong(args[3])+(protocol==3?315360000L:0);
  boolean valid=new SignedWipeCommand(c).verify(args[1],Base64.getUrlDecoder().decode(args[0]),received,Long.parseLong(args[3])-1);
  if(!valid) throw new Exception("Protect rejected website signature");
  System.out.print("verified");
 }
}`);
    const source = join(PROTECT_APP, 'app/src/main/java/com/stellar/phone/protect/security');
    execFileSync(join(JAVA_HOME, 'bin/javac'), ['-d', temp, join(source, 'WipeSigningIdentity.java'), join(source, 'SignedWipeCommand.java'), join(temp, 'WebsiteFixture.java')]);
    const token = execFileSync(join(JAVA_HOME, 'bin/java'), ['-cp', temp, 'WebsiteFixture'], {encoding: 'utf8'});
    const bytes = Buffer.from(token.slice(5), 'base64url');
    const offset = 6 + bytes.readUInt16BE(4);
    const publicKey = bytes.subarray(offset + 4, offset + 4 + bytes.readUInt32BE(offset)).toString('base64url');
    const signer = await WipeSigner.prepare(token);
    for (const ttl of [undefined, 900, 3600, 86400]) {
        const c = await signer.sign(ttl);
        assert.equal(execFileSync(join(JAVA_HOME, 'bin/java'), ['-cp', temp, 'WebsiteFixture', publicKey, c.registration_id, c.command_id, String(c.issued_at), String(c.expires_at || 0), c.key_id, c.signature, String(c.protocol)], {encoding: 'utf8'}), 'verified');
        const php = 'require $argv[1]; $c=json_decode(stream_get_contents(STDIN),true); if(!App\\Helpers\\SignedWipeProtocol::verify($c,$argv[2],$c["registration_id"],$c["issued_at"]+($c["protocol"]===3?315360000:0))) exit(1); echo "verified";';
        assert.equal(execFileSync('php', ['-n', '-r', php, join(PROTECT_BACKEND, 'app/Helpers/SignedWipeProtocol.php'), publicKey], {input: JSON.stringify(c), encoding: 'utf8'}), 'verified');
    }
    console.log('PASS: actual Protect token imports; v3 website signatures accepted after ten years offline by phone and backend; all old v2 formats still verify. No network/database/device access.');
} finally {
    rmSync(temp, {recursive: true, force: true});
}
