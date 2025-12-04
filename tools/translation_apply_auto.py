#!/usr/bin/env python3
"""
Compare translation_report.json with `lang/en` and `lang/fr`, auto-generate French translations,
write `lang/fr/*.php` and `lang/fr.json` (for literal strings), and emit `missing_translations.json`.

Usage: python3 tools/translation_apply_auto.py --report translation_report.json

Provider: tries to use googletrans if available; otherwise falls back to prefixing with "[FR AUTO] ".
"""
import argparse
import json
import os
import re
import sys


def load_report(path):
    with open(path, 'r', encoding='utf-8') as f:
        return json.load(f)


def list_lang_files(locale_dir):
    files = {}
    if not os.path.isdir(locale_dir):
        return files
    for root, dirs, filenames in os.walk(locale_dir):
        for fn in filenames:
            if fn.endswith('.php'):
                rel = os.path.relpath(os.path.join(root, fn), locale_dir)
                key = rel.replace(os.sep, '/')
                files[key] = os.path.join(root, fn)
    return files


def parse_simple_php_array(path):
    # Very small parser for simple php files that return a flat array.
    try:
        s = open(path, 'r', encoding='utf-8').read()
    except FileNotFoundError:
        return {}
    m = re.search(r"return\s*\[([\s\S]*)\];", s)
    if not m:
        return {}
    body = m.group(1)
    # Remove comments
    body = re.sub(r"/\*[\s\S]*?\*/", '', body)
    body = re.sub(r"//.*?$", '', body, flags=re.M)
    entries = {}
    # Match lines like 'key' => 'value',
    for pair in re.finditer(r"(['\"])(?P<k>.*?)\1\s*=>\s*(['\"])(?P<v>.*?)\3\s*,?", body, flags=re.S):
        k = pair.group('k')
        v = pair.group('v')
        entries[k] = v
    return entries


def write_php_array(path, data):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    lines = ["<?php", "", "return ["]
    for k, v in sorted(data.items()):
        # escape single quotes
        vk = v.replace("'", "\\'")
        lines.append(f"    '{k}' => '{vk}',")
    lines.append("];")
    content = "\n".join(lines) + "\n"
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)


def try_get_translator():
    try:
        from googletrans import Translator
        return Translator()
    except Exception:
        return None


def translate_text(translator, text):
    if not translator:
        return '[FR AUTO] ' + text
    try:
        res = translator.translate(text, dest='fr')
        return res.text
    except Exception:
        return '[FR AUTO] ' + text


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--report', default='translation_report.json')
    p.add_argument('--branch', default='i18n/auto-fr')
    p.add_argument('--commit', action='store_true')
    args = p.parse_args()

    report = load_report(args.report)

    # Collect keys
    keys = set()
    for item in report.get('translation_calls', []):
        k = item.get('key')
        if k:
            keys.add(k)
    for item in report.get('blade_hardcoded', []):
        k = item.get('text') or item.get('key')
        if k:
            keys.add(k)
    for item in report.get('js_strings', []):
        k = item.get('text') or item.get('key')
        if k:
            keys.add(k)

    # locate lang dirs
    base = os.path.join(os.getcwd(), 'lang')
    en_dir = os.path.join(base, 'en')
    fr_dir = os.path.join(base, 'fr')

    en_files = list_lang_files(en_dir)
    fr_files = list_lang_files(fr_dir)

    translator = try_get_translator()
    if translator:
        print('Using googletrans for translations')
    else:
        print('googletrans not available, falling back to prefixing translations')

    missing = {
        'dot_keys_missing_in_fr': [],
        'literals_missing_in_fr': [],
        'generated': {}
    }

    # Handle dot keys like 'auth.register'
    dot_keys = [k for k in keys if '.' in k]
    for dk in dot_keys:
        file_part, subkey = dk.split('.', 1)
        file_path = os.path.join(en_dir, f"{file_part}.php")
        en_entries = parse_simple_php_array(file_path)
        if not en_entries:
            # try nested file path
            continue
        if subkey in en_entries:
            # verify fr has file
            fr_path = os.path.join(fr_dir, f"{file_part}.php")
            fr_entries = parse_simple_php_array(fr_path)
            if subkey not in fr_entries:
                en_text = en_entries[subkey]
                tr = translate_text(translator, en_text)
                # prepare to write into fr_path
                if 'files' not in missing['generated']:
                    missing['generated']['files'] = {}
                missing['generated']['files'].setdefault(fr_path, {})[subkey] = tr
                missing['dot_keys_missing_in_fr'].append(dk)

    # Handle literal strings (no dot)
    literal_keys = [k for k in keys if '.' not in k]
    json_map = {}
    for lit in literal_keys:
        # Check if exists in any fr file values
        exists_in_fr = False
        for path in fr_files.values():
            fr_entries = parse_simple_php_array(path)
            if lit in fr_entries.values():
                exists_in_fr = True
                break
        if not exists_in_fr:
            tr = translate_text(translator, lit)
            json_map[lit] = tr
            missing['literals_missing_in_fr'].append(lit)
            missing['generated'].setdefault('json', {})[lit] = tr

    # Write generated FR files
    # 1) update php files
    for fr_path, entries in missing.get('generated', {}).get('files', {}).items():
        existing = parse_simple_php_array(fr_path)
        merged = existing.copy()
        merged.update(entries)
        write_php_array(fr_path, merged)
        print(f'Wrote/updated {fr_path}')

    # 2) write lang/fr.json for literal mapping
    if json_map:
        fr_json_path = os.path.join(fr_dir, 'fr.json')
        os.makedirs(fr_dir, exist_ok=True)
        with open(fr_json_path, 'w', encoding='utf-8') as f:
            json.dump(json_map, f, indent=2, ensure_ascii=False)
        print(f'Wrote {fr_json_path}')

    # Emit missing_translations.json
    out_path = os.path.join(os.getcwd(), 'missing_translations.json')
    with open(out_path, 'w', encoding='utf-8') as f:
        json.dump(missing, f, indent=2, ensure_ascii=False)
    print('Wrote', out_path)

    # Optionally create git branch and commit
    if args.commit:
        # Create branch and commit
        import subprocess
        try:
            subprocess.check_call(['git', 'checkout', '-b', args.branch])
        except subprocess.CalledProcessError:
            print('Branch may already exist; continuing')
        try:
            subprocess.check_call(['git', 'add', os.path.join('lang', 'fr')])
            subprocess.check_call(['git', 'add', 'missing_translations.json'])
            subprocess.check_call(['git', 'commit', '-m', 'i18n: auto-generate fr translations (machine-assisted)'])
            print('Committed changes on branch', args.branch)
        except subprocess.CalledProcessError as e:
            print('Git commit failed:', e)


if __name__ == '__main__':
    main()
