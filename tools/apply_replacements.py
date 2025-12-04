#!/usr/bin/env python3
"""
Apply conservative replacements of hardcoded UI strings using keys from `missing_translations.json`.

Replacements performed:
- In `.blade.php` files under `resources/views`:
  - Replace inner text that exactly matches a key: `>Label<` -> `>{{ __('Label') }}<`.
  - Replace attribute values that exactly match a key: `attr="Label"` -> `attr="{{ __('Label') }}"` (and single-quoted variants).
- In PHP files under `app/Livewire` and `app/Http` and `app/Providers`:
  - Replace plain string literals `'Label'` or "Label" with `__('Label')` unless already inside a translation call.

This is conservative and skips `vendor/` and `public/` files.
"""
import json
import os
import re
import subprocess
from pathlib import Path


ROOT = Path(os.getcwd())


def load_missing():
    p = ROOT / 'missing_translations.json'
    if not p.exists():
        print('missing_translations.json not found')
        return None
    return json.loads(p.read_text(encoding='utf-8'))


def replace_in_blade(path, literals):
    s = path.read_text(encoding='utf-8')
    orig = s
    for lit in literals:
        if not lit.strip():
            continue
        esc = re.escape(lit)
        # inner text: >   LIT   <
        s = re.sub(r'(>\s*)' + esc + r'(\s*<)', r"\1{{ __('" + lit + r"') }}\2", s)
        # attribute double-quoted
        s = s.replace('="' + lit + '"', '="{{ __(\'' + lit.replace("'", "\\'") + '\') }}"')
        # attribute single-quoted
        s = s.replace("='" + lit + "'", "='{{ __('" + lit.replace("'", "\\'") + "') }}'")
    if s != orig:
        path.write_text(s, encoding='utf-8')
        return True
    return False


def replace_in_php(path, literals):
    s = path.read_text(encoding='utf-8')
    orig = s
    for lit in literals:
        if not lit.strip():
            continue
        # skip if already used in __(), trans(, Lang::get
        if ("__('" + lit + "')") in s or '("' + lit + '")' in s:
            continue
        # replace 'lit' -> __('lit')
        s = s.replace("'" + lit + "'", "__('" + lit.replace("'", "\\'") + "')")
        s = s.replace('"' + lit + '"', "__('" + lit.replace('"', '\\"') + "')")
    if s != orig:
        path.write_text(s, encoding='utf-8')
        return True
    return False


def main():
    data = load_missing()
    if data is None:
        return
    literals = list(data.get('generated', {}).get('json', {}).keys())

    changed_files = []

    # Blade files
    for p in ROOT.rglob('resources/views/**/*.blade.php'):
        if '/vendor/' in str(p):
            continue
        try:
            if replace_in_blade(p, literals):
                changed_files.append(str(p))
        except Exception as e:
            print('Error processing', p, e)

    # Livewire and app PHP files
    php_targets = [ROOT / 'app' / 'Livewire', ROOT / 'app' / 'Http', ROOT / 'app' / 'Providers']
    for base in php_targets:
        if not base.exists():
            continue
        for p in base.rglob('*.php'):
            if '/vendor/' in str(p):
                continue
            try:
                if replace_in_php(p, literals):
                    changed_files.append(str(p))
            except Exception as e:
                print('Error processing', p, e)

    # Optionally commit changes
    if changed_files:
        print('Files changed:', len(changed_files))
        subprocess.check_call(['git', 'add'] + changed_files)
        subprocess.check_call(['git', 'commit', '-m', 'i18n: replace hardcoded UI strings with translation lookups'])
        print('Committed replacements')
    else:
        print('No files changed')


if __name__ == '__main__':
    main()
