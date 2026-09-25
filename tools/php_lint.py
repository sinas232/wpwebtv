#!/usr/bin/env python3
"""ساختار سینتکسی ساده PHP: توازن {} () [] ، کامنت‌ها و رشته‌ها + تشخیص function تکراری."""
import re
import sys
from pathlib import Path

def strip_php(code: str) -> str:
    # حذف رشته‌ها و کامنت‌ها برای توازن پرانتزها
    out = []
    i, n = 0, len(code)
    state = None  # None, "'", '"', '//', '#', '/*'
    while i < n:
        c = code[i]
        nxt = code[i + 1] if i + 1 < n else ''
        if state is None:
            if c == "'":
                state = "'"
            elif c == '"':
                state = '"'
            elif c == '/' and nxt == '/':
                state = '//'
                i += 1
            elif c == '#':
                state = '#'
            elif c == '/' and nxt == '*':
                state = '/*'
                i += 1
            else:
                out.append(c)
        elif state in ("'", '"'):
            if c == '\\':
                i += 1
            elif c == state:
                state = None
        elif state == '//':
            if c == '\n':
                state = None
                out.append(c)
        elif state == '#':
            if c == '\n':
                state = None
                out.append(c)
        elif state == '/*':
            if c == '*' and nxt == '/':
                state = None
                i += 1
        i += 1
    return ''.join(out), state

def check_file(path: Path):
    code = path.read_text(encoding='utf-8')
    stripped, end_state = strip_php(code)
    errors = []
    if end_state is not None:
        errors.append(f"باز ماندن {end_state} (رشته/کامنت بسته نشده)")
    stack = []
    pairs = {')': '(', ']': '[', '}': '{'}
    line = 1
    for ch in stripped:
        if ch == '\n':
            line += 1
        elif ch in '([{':
            stack.append((ch, line))
        elif ch in ')]}':
            if not stack or stack[-1][0] != pairs[ch]:
                errors.append(f"خط {line}: '{ch}' بدون باز شدن متناظر")
                break
            stack.pop()
    for ch, ln in stack:
        closer = {'(': ')', '[': ']', '{': '}'}[ch]
        errors.append("خط %d: '%s%s' بسته نشده" % (ln, ch, closer))
    return errors

def main(root: str):
    root_p = Path(root)
    files = sorted(root_p.rglob('*.php'))
    funcs = {}
    failed = False
    for f in files:
        rel = f.relative_to(root_p)
        errs = check_file(f)
        code = f.read_text(encoding='utf-8')
        for m in re.finditer(r'^\s*function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(', code, re.M):
            name = m.group(1)
            funcs.setdefault(name, []).append(str(rel))
        if errs:
            failed = True
            print(f"[FAIL] {rel}")
            for e in errs:
                print(f"   - {e}")
        else:
            print(f"[OK]   {rel}")
    print("\n=== توابع تکراری (function یکسان در چند فایل) ===")
    dup = False
    for name, locations in sorted(funcs.items()):
        if len(locations) > 1:
            dup = True
            print(f"[DUP] {name}: {', '.join(locations)}")
    if not dup:
        print("هیچ تابع تکراری نیست.")
    return 1 if (failed or dup) else 0

if __name__ == '__main__':
    sys.exit(main(sys.argv[1] if len(sys.argv) > 1 else 'pixva'))
