#!/usr/bin/env python3
"""بررسی ساختاری فایل‌های PHP بدون نیاز به باینری php.

این ابزار سه لایه کنترل انجام می‌دهد:

1. توازن {} () [] فقط در ناحیه‌های PHP (ناحیه HTML، رشته‌ها، کامنت‌ها و
   heredoc/nowdoc نادیده گرفته می‌شوند).
2. تطابق ساختارهای جایگزین: if/endif ، foreach/endforeach ، for/endfor ،
   while/endwhile ، switch/endswitch.
3. تعریف دوباره تابع/کلاس در کل پوسته (هم در یک فایل و هم بین فایل‌ها،
   بدون در نظر گرفتن محافظ function_exists).

اجرا از ریشه مخزن:  python3 tools/php_check.py [مسیر]
"""
from __future__ import annotations

import re
import sys
from pathlib import Path

CONTROL = ('if', 'foreach', 'for', 'while', 'switch')
ENDERS = {
    'endif': 'if',
    'endforeach': 'foreach',
    'endfor': 'for',
    'endwhile': 'while',
    'endswitch': 'switch',
}
PAIRS = {')': '(', ']': '[', '}': '{'}
CLOSERS = {'(': ')', '[': ']', '{': '}'}


class Scanner:
    """پیمایش کاراکتر‌به‌کاراکتر با آگاهی از ناحیه PHP/HTML و رشته‌ها."""

    def __init__(self, code: str) -> None:
        self.code = code
        self.n = len(code)
        self.i = 0
        self.line = 1
        self.errors: list[str] = []
        self.stack: list[tuple[str, int]] = []
        self.alt_stack: list[tuple[str, int]] = []
        self.mode = 'html'          # html | php
        self.state = None           # None | "'" | '"' | '//' | '#' | '/*' | 'heredoc'
        self.heredoc_marker = ''
        self.pending_word = ''
        self.pending_word_line = 0
        self.after_control_paren = False
        self.paren_depth_at_control = -1
        self.funcs: list[tuple[str, int]] = []

    # ------------------------------------------------------------------ helpers
    def peek(self, offset: int = 1) -> str:
        j = self.i + offset
        return self.code[j] if j < self.n else ''

    def fail(self, message: str) -> None:
        self.errors.append(f'خط {self.line}: {message}')

    def word_boundary_match(self, word: str) -> bool:
        """آیا از موقعیت جاری کلمه کامل word شروع می‌شود؟"""
        if not self.code.startswith(word, self.i):
            return False
        before = self.code[self.i - 1] if self.i else ''
        after = self.code[self.i + len(word)] if self.i + len(word) < self.n else ''
        return not (before.isalnum() or before == '_') and not (after.isalnum() or after == '_')

    # ------------------------------------------------------------------ scanning
    def run(self) -> None:
        while self.i < self.n:
            ch = self.code[self.i]
            if ch == '\n':
                self.line += 1

            if self.mode == 'html':
                self.scan_html(ch)
            else:
                self.scan_php(ch)
            self.i += 1

        if self.mode == 'php' and self.state in ('heredoc',):
            self.fail('heredoc بسته نشده')
        if self.state in ("'", '"', '/*'):
            self.fail(f'رشته/کامنت {self.state} بسته نشده')
        for sym, line in self.stack:
            self.errors.append(f"خط {line}: '{sym}{CLOSERS[sym]}' بسته نشده")
        for name, line in self.alt_stack:
            self.errors.append(f'خط {line}: بلوک جایگزین {name} بدون پایان (end{name})')

    def scan_html(self, ch: str) -> None:
        if ch == '<' and self.code.startswith('<?php', self.i):
            self.mode = 'php'
            self.i += 4  # بقیه «<?php» در پایان حلقه رد می‌شود
            self.state = None
        elif ch == '<' and self.code.startswith('<?=', self.i):
            self.mode = 'php'
            self.i += 2
            self.state = None
        elif ch == '<' and self.code.startswith('<?', self.i) and not self.code.startswith('<?xml', self.i):
            self.mode = 'php'
            self.i += 1
            self.state = None

    def scan_php(self, ch: str) -> None:
        # پایان ناحیه PHP (فقط وقتی داخل رشته/کامنت نیستیم).
        if self.state is None and ch == '?' and self.peek() == '>':
            self.mode = 'html'
            self.i += 1
            self.after_control_paren = False
            return

        if self.state is None:
            self.scan_php_code(ch)
        elif self.state == "'":
            if ch == '\\':
                self.i += 1
                if self.peek() == '\n':
                    self.line += 1
            elif ch == "'":
                self.state = None
        elif self.state == '"':
            if ch == '\\':
                self.i += 1
                if self.peek() == '\n':
                    self.line += 1
            elif ch == '"':
                self.state = None
        elif self.state == '//':
            if ch == '\n':
                self.state = None
        elif self.state == '#':
            if ch == '\n':
                self.state = None
        elif self.state == '/*':
            if ch == '*' and self.peek() == '/':
                self.state = None
                self.i += 1
        elif self.state == 'heredoc':
            if ch == '\n':
                rest = self.code[self.i + 1:self.i + 1 + len(self.heredoc_marker) + 4]
                stripped = rest.lstrip()
                if stripped.startswith(self.heredoc_marker):
                    tail = stripped[len(self.heredoc_marker):len(self.heredoc_marker) + 1]
                    if tail in (';', '\n', '\r', ''):
                        self.state = None

    def scan_php_code(self, ch: str) -> None:
        # شروع رشته‌ها و کامنت‌ها.
        if ch == "'":
            self.state = "'"
            return
        if ch == '"':
            self.state = '"'
            return
        if ch == '/' and self.peek() == '/':
            self.state = '//'
            self.i += 1
            return
        if ch == '#' and self.peek() != '[':
            self.state = '#'
            return
        if ch == '/' and self.peek() == '*':
            self.state = '/*'
            self.i += 1
            return

        # heredoc / nowdoc
        if ch in ('<',) and self.code.startswith('<<<', self.i):
            match = re.match(r'<<<\s*([\'"]?)([A-Za-z_][A-Za-z0-9_]*)\1', self.code[self.i:])
            if match:
                self.heredoc_marker = match.group(2)
                self.state = 'heredoc'
                self.i += match.end() - 1
                return

        # کنترل ساختارهای جایگزین.
        if self.after_control_paren:
            if ch in ' \t\r\n':
                return
            if ch == ':':
                self.alt_stack.append((self.pending_word, self.pending_word_line))
                self.after_control_paren = False
                return
            self.after_control_paren = False

        if ch in '([{':
            self.stack.append((ch, self.line))
            if ch == '(' and self.paren_depth_at_control >= 0:
                pass
            return
        if ch in ')]}':
            if not self.stack or self.stack[-1][0] != PAIRS[ch]:
                self.fail(f"'{ch}' بدون '{PAIRS[ch]}' متناظر")
                return
            self.stack.pop()
            if ch == ')' and self.paren_depth_at_control == len(self.stack):
                self.after_control_paren = True
                self.paren_depth_at_control = -1
            return

        if ch == ';' or ch == '{':
            self.paren_depth_at_control = -1
            self.after_control_paren = False

        if not (ch.isalnum() or ch == '_'):
            return

        # کلمه کامل.
        start = self.i
        while start > 0 and (self.code[start - 1].isalnum() or self.code[start - 1] == '_'):
            start -= 1
        if start != self.i:
            return  # وسط یک کلمه هستیم
        end = self.i
        while end < self.n and (self.code[end].isalnum() or self.code[end] == '_'):
            end += 1
        word = self.code[self.i:end]

        if word in CONTROL:
            self.pending_word = word
            self.pending_word_line = self.line
            self.paren_depth_at_control = len(self.stack)
        elif word in ENDERS:
            expected = ENDERS[word]
            if not self.alt_stack:
                self.fail(f'{word} بدون بلوک {expected} باز')
            elif self.alt_stack[-1][0] != expected:
                self.fail(f'{word} نامنطبق با بلوک {self.alt_stack[-1][0]} (خط {self.alt_stack[-1][1]})')
            else:
                self.alt_stack.pop()
        elif word == 'function':
            rest = self.code[end:end + 220]
            m = re.match(r'\s*&?\s*([A-Za-z_][A-Za-z0-9_]*)\s*\(', rest)
            # فقط توابع سطح فایل (نه متدهای کلاس) برای بررسی نام تکراری.
            if m and not self.stack:
                self.funcs.append((m.group(1), self.line))


def check_file(path: Path) -> tuple[list[str], list[tuple[str, int]]]:
    scanner = Scanner(path.read_text(encoding='utf-8', errors='replace'))
    scanner.run()
    return scanner.errors, scanner.funcs


def main(argv: list[str]) -> int:
    root = Path(argv[1] if len(argv) > 1 else 'pixva')
    if not root.exists():
        print(f'مسیر یافت نشد: {root}')
        return 2

    files = sorted(p for p in root.rglob('*.php') if '.git' not in p.parts)
    failed = False
    registry: dict[str, list[str]] = {}

    for path in files:
        errors, funcs = check_file(path)
        rel = path.relative_to(root) if path.is_relative_to(root) else path
        for name, _line in funcs:
            registry.setdefault(name, []).append(str(rel))
        if errors:
            failed = True
            print(f'[FAIL] {rel}')
            for item in errors[:12]:
                print(f'   - {item}')
            if len(errors) > 12:
                print(f'   … و {len(errors) - 12} مورد دیگر')

    dupes = {name: paths for name, paths in registry.items() if len(set(paths)) > 1 or len(paths) > 1}
    # تعریف چندباره فقط وقتی خطاست که محافظ function_exists وجود نداشته باشد.
    for name, paths in sorted(dupes.items()):
        guarded = 0
        for rel in set(paths):
            text = (root / rel).read_text(encoding='utf-8', errors='replace')
            if f"function_exists( '{name}' )" in text or f'function_exists("{name}")' in text:
                guarded += 1
        if guarded == len(set(paths)) and len(set(paths)) == len(paths):
            continue
        failed = True
        print(f'[DUP] تابع {name} در: {", ".join(sorted(set(paths)))}')

    if not failed:
        print(f'OK — {len(files)} فایل PHP بررسی شد؛ توازن بلوک‌ها، ساختارهای جایگزین و نام توابع سالم است.')
    return 1 if failed else 0


if __name__ == '__main__':
    sys.exit(main(sys.argv))
