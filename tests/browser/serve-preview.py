"""Dependency-free Blade *fixture* preview; not a Laravel integration server.
Run from any directory, then open http://127.0.0.1:8766.
Only first-party test/production scripts run. CSP blocks ALL real submissions.
"""
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
import mimetypes
import re

ROOT = Path(__file__).resolve().parents[2]

def fixture():
    view = (ROOT / 'resources/views/auth/login.blade.php').read_text()
    view = re.sub(r'@push\(.*?@endpush', '', view, flags=re.S)
    view = re.sub(r'@if\(.*?@endif', '', view, flags=re.S)
    view = re.sub(r'@error\(.*?@enderror', '', view, flags=re.S)
    view = re.sub(r'\{\{--.*?--\}\}', '', view, flags=re.S)
    view = re.sub(r'\{\{ asset\(\x27([^\x27]*)\x27\) \}\}', r'/\1', view)
    view = view.replace("{{ route('login.attempt') }}", '/login')
    view = view.replace('@csrf', '<input type="hidden" name="_token" value="synthetic-csrf">')
    view = re.sub(r"@(?:extends|section)\([^\n]*\)|@endsection", '', view)
    # Keep the token accordion open without executing the third-party bundle.
    view = view.replace('id="collapseTwo" class="accordion-collapse collapse"', 'id="collapseTwo" class="accordion-collapse collapse show"')
    view = view.replace('id="collapseOne" class="accordion-collapse collapse show"', 'id="collapseOne" class="accordion-collapse collapse"')
    return ('<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            '<title>Local synthetic wipe tests</title><link rel="stylesheet" href="/build/assets/app-71455456.css">'
            '<link rel="stylesheet" href="/build/assets/style.css"><body>'
            '<p id="test-results" role="status">Running local tests. All network submissions are blocked.</p>'
            + view + '<script type="module" src="/dom.test.js"></script></body></html>').encode()

class Handler(BaseHTTPRequestHandler):
    def do_GET(self):
        if self.path == '/':
            data, mime = fixture(), 'text/html'
        else:
            file = ROOT / 'tests/browser/dom.test.js' if self.path == '/dom.test.js' else (ROOT / 'public' / self.path.lstrip('/')).resolve()
            allowed = file == ROOT / 'tests/browser/dom.test.js' or file.is_relative_to(ROOT / 'public')
            if not allowed or not file.is_file():
                self.send_error(404)
                return
            data = file.read_bytes()
            mime = mimetypes.guess_type(str(file))[0] or 'application/octet-stream'
        self.send_response(200)
        self.send_header('Content-Type', mime)
        self.send_header('Cache-Control', 'no-store')
        self.send_header('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; connect-src 'none'; form-action 'none'; object-src 'none'")
        self.end_headers()
        self.wfile.write(data)

if __name__ == '__main__':
    ThreadingHTTPServer(('127.0.0.1', 8766), Handler).serve_forever()
