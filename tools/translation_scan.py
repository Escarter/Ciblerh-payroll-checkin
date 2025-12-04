#!/usr/bin/env python3
"""
Scan the repository for translation usages and hardcoded UI strings.
Generates `translation_report.json` in the repo root.

By default this script excludes `vendor/` and `public/vendor/` to avoid noisy
third-party files. To include vendor files, set INCLUDE_VENDOR=1 in the
environment when running.
"""
import os
import re
import json
import sys

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
EXCLUDE = {'vendor', 'public/vendor', 'node_modules', '.git'}
INCLUDE_VENDOR = os.environ.get('INCLUDE_VENDOR', '') == '1'

translation_patterns = [
    re.compile(r"__\(\s*['\"]([^'\"]+)['\"]"),
    re.compile(r"@lang\(\s*['\"]([^'\"]+)['\"]"),
    re.compile(r"\btrans\(\s*['\"]([^'\"]+)['\"]"),
    re.compile(r"Lang::get\(\s*['\"]([^'\"]+)['\"]"),
]

js_string_re = re.compile(r"['\"]([A-Za-z0-9][^'\"\n]{2,})['\"]")
blade_text_re = re.compile(r">([^<\n]{3,}?)<")

report = {
    'translation_calls': [],
    'blade_hardcoded': [],
    'js_strings': [],
    'stats': {
        'files_scanned': 0,
        'translation_calls_found': 0,
        'blade_hardcoded_found': 0,
        'js_strings_found': 0,
    }
}

def should_skip(path):
    if INCLUDE_VENDOR:
        return False
    for ex in EXCLUDE:
        if path.startswith(os.path.join(ROOT, ex) + os.sep) or path == os.path.join(ROOT, ex):
            return True
    return False

def scan_file(path):
    rel = os.path.relpath(path, ROOT)
    try:
        with open(path, 'r', encoding='utf-8', errors='ignore') as f:
            text = f.read()
    except Exception:
        return

    ext = os.path.splitext(path)[1].lower()

    # translation calls
    for p in translation_patterns:
        for m in p.finditer(text):
            report['translation_calls'].append({'file': rel, 'match': m.group(0), 'key': m.group(1)})

    # blade hardcoded text nodes
    if path.endswith('.blade.php'):
        for m in blade_text_re.finditer(text):
            s = m.group(1).strip()
            # ignore if contains translation helpers or blade expressions
            if any(t in s for t in ['__(', '@lang', '{{', '}}', '@if', '@foreach', '<!--']):
                continue
            # ignore strings that are mostly punctuation or numbers
            if re.match(r'^[\W\d_]+$', s):
                continue
            report['blade_hardcoded'].append({'file': rel, 'text': s})

    # JS strings
    if ext in {'.js', '.ts', '.vue', '.jsx', '.tsx'}:
        for m in js_string_re.finditer(text):
            s = m.group(1).strip()
            # heuristics: skip short tokens and code-like strings
            if len(s) < 3:
                continue
            if re.search(r'^[\w_\-/.]+$', s):
                continue
            report['js_strings'].append({'file': rel, 'text': s})


for dirpath, dirnames, filenames in os.walk(ROOT):
    # skip excluded dirs early
    if should_skip(dirpath):
        continue
    for fn in filenames:
        path = os.path.join(dirpath, fn)
        if should_skip(path):
            continue
        # only scan relevant extensions
        if any(fn.endswith(ext) for ext in ('.blade.php', '.php', '.js', '.ts', '.vue', '.jsx', '.tsx')):
            report['stats']['files_scanned'] += 1
            scan_file(path)

report['stats']['translation_calls_found'] = len(report['translation_calls'])
report['stats']['blade_hardcoded_found'] = len(report['blade_hardcoded'])
report['stats']['js_strings_found'] = len(report['js_strings'])

out = os.path.join(ROOT, 'translation_report.json')
with open(out, 'w', encoding='utf-8') as f:
    json.dump(report, f, indent=2, ensure_ascii=False)

print('Wrote', out)
