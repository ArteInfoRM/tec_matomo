"""Exercise the real HTTP sender against a loopback fixture only."""
import json
import subprocess
import threading
import urllib.parse
from http.server import BaseHTTPRequestHandler, HTTPServer
from pathlib import Path

requests = []


class Handler(BaseHTTPRequestHandler):
    def do_POST(self):
        data = urllib.parse.parse_qs(
            self.rfile.read(int(self.headers.get("Content-Length", "0"))).decode()
        )
        requests.append(data)
        self.send_response(204 if self.path == "/ok" else 302 if self.path == "/redirect" else 500)
        if self.path == "/redirect":
            self.send_header("Location", "http://127.0.0.1:1/forbidden")
        self.end_headers()

    def log_message(self, *args):
        pass


server = HTTPServer(("127.0.0.1", 0), Handler)
threading.Thread(target=server.serve_forever, daemon=True).start()
try:
    for route, token, expected in [
        ("ok", "", "accepted"),
        ("redirect", "", "rejected"),
        ("error", "", "rejected"),
        ("ok", "a" * 32, "rejected"),
    ]:
        result = subprocess.run(
            ["php", str(Path(__file__).with_suffix(".php")),
             f"http://127.0.0.1:{server.server_port}/{route}", token],
            capture_output=True, text=True, check=True,
        )
        assert result.stdout == expected, (result.stdout, result.stderr)
    assert len(requests) == 3, "Redirects and plaintext-token requests must not be sent."
    for data in requests:
        assert data["idgoal"] == ["0"]
        assert data["revenue"] == ["18.3"]
        assert data["send_image"] == ["0"] and data["cookie"] == ["0"]
        assert not any(key in data for key in ["token_auth", "cip", "uid", "email", "urlref"])
        assert len(data["_id"][0]) == 16
        assert json.loads(data["ec_items"][0]) == [["1v0", "Fixture", "", 18.3, 1]]
    print("4 local HTTP transport scenarios and payload checks passed.")
finally:
    server.shutdown()
