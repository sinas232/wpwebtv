#!/usr/bin/env python3
"""پیش‌نمایش محلی صفحه اصلی. ریشه را به preview/index.html می‌فرستد."""
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
import os

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))


class Handler(SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=ROOT, **kwargs)

    def do_GET(self):
        path = self.path.split('?', 1)[0]
        if path in ('/', '/index.html'):
            self.path = '/preview/index.html'
        return super().do_GET()


if __name__ == '__main__':
    ThreadingHTTPServer(('0.0.0.0', 4173), Handler).serve_forever()
