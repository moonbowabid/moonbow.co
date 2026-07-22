#!/usr/bin/env python3
"""
pixel-diff / css-diff — deterministic staging-vs-prod styling diff for any
Elementor page on the Moonbow site.

WHY THIS EXISTS
    Height/position comparisons (the older Playwright suite) are blind to styling
    that doesn't change a box's size — borders, colours, radius, shadows, subtle
    margins. This tool reads each site's *generated Elementor CSS* for a page and
    diffs it rule-by-rule so nothing of that kind slips through.

HOW IT ALIGNS
    Staging and prod are different Elementor documents (different post-ids), but a
    page duplicated between them keeps the same per-element hashes
    (`.elementor-element-<hash>`). So we normalise away the `.elementor-<docid>`
    prefix and the host in url()s, then every rule lines up by (media, selector).
    Output is bucketed:
      • REAL   — a concrete CSS property differs on an element present on BOTH
                 sides  (the deploy-worthy design differences)
      • VAR    — only Elementor `--*` layout variables differ (usually still real
                 spacing/layout; shown separately because they're noisier)
      • STRUCTURAL — rules for an element present on only one side (a section that
                 was rebuilt or a widget added/removed)

CACHE
    Every fetch is cache-busted (`?cb=<ts>`) so Cloudflare/GoDaddy returns the
    origin-fresh file. Elementor regenerates these CSS files lazily, so always run
    against a freshly-saved page.

USAGE
    python3 css-diff.py /                 # home
    python3 css-diff.py /services/ /ai-suite/   # several pages
    STAGING=https://staging.moonbow.co PROD=https://moonbow.co python3 css-diff.py /
"""
import os, re, sys, time, subprocess
from collections import defaultdict

STAGING = os.environ.get("STAGING", "https://staging.moonbow.co")
PROD    = os.environ.get("PROD",    "https://moonbow.co")
GLOBAL  = {"11", "15", "17"}          # kit + header + footer (verified identical)
CB      = str(int(time.time()))
PATHS   = sys.argv[1:] or ["/"]

# --- fetch -----------------------------------------------------------------
def curl(u):
    sep = "&" if "?" in u else "?"
    return subprocess.run(["curl", "-skL", f"{u}{sep}cb={CB}"],
                          capture_output=True, text=True).stdout

def page_css(base, path):
    html = curl(base + path)
    ids = [i for i in dict.fromkeys(re.findall(r"uploads/elementor/css/post-(\d+)\.css", html))
           if i not in GLOBAL]
    return ids, "".join(curl(f"{base}/wp-content/uploads/elementor/css/post-{i}.css") for i in ids)

# --- parse -----------------------------------------------------------------
def normalize(css, base):
    css = css.replace(base, "@SITE")
    css = re.sub(r"\.elementor-\d+(?=[\s.{])", ".elementor-DOC", css)
    return css

def parse(css):
    """{(media, selector): {prop: value}} for every rule (media queries kept)."""
    def read_block(s, start):
        depth = 0
        for j in range(start, len(s)):
            if s[j] == "{": depth += 1
            elif s[j] == "}":
                depth -= 1
                if depth == 0: return j
        return len(s) - 1

    segments, pos = [], 0
    for m in re.finditer(r"@media[^{]+\{", css):
        if m.start() > pos: segments.append(("", css[pos:m.start()]))
        end = read_block(css, m.end() - 1)
        segments.append((re.sub(r"\s+", " ", m.group(0)[:-1]).strip(), css[m.end():end]))
        pos = end + 1
    segments.append(("", css[pos:]))

    rules = defaultdict(dict)
    for media, text in segments:
        for rm in re.finditer(r"([^{}]+)\{([^{}]*)\}", text):
            sel = re.sub(r"\s+", " ", rm.group(1)).strip()
            if sel.startswith("@"): continue
            for prop, val in re.findall(r"([a-zA-Z-]+)\s*:\s*([^;]+)\s*;", rm.group(2)):
                rules[(media, sel)][prop.strip().lower()] = re.sub(r"\s+", " ", val).strip()
    return rules

hashof = lambda sel: (re.findall(r"elementor-element-([a-z0-9]+)", sel) or [sel])[-1]
mlabel = lambda m: (m.replace("@media", "").strip() if m else "desktop")

# --- diff one page ---------------------------------------------------------
def diff_page(path):
    s_ids, s_css = page_css(STAGING, path)
    p_ids, p_css = page_css(PROD, path)
    s = parse(normalize(s_css, STAGING))
    p = parse(normalize(p_css, PROD))

    real, varonly, stg_only, prd_only = [], [], set(), set()
    for k in sorted(set(s) | set(p)):
        sd, pd = s.get(k, {}), p.get(k, {})
        if sd and not pd: stg_only.add(hashof(k[1])); continue
        if pd and not sd: prd_only.add(hashof(k[1])); continue
        concrete, vs = [], []
        for prop in sorted(set(sd) | set(pd)):
            sv, pv = sd.get(prop), pd.get(prop)
            if sv == pv: continue
            (vs if prop.startswith("--") else concrete).append((prop, sv, pv))
        if concrete: real.append((k, concrete, vs))
        elif vs:     varonly.append((k, vs))

    print(f"\n{'#'*70}\n# {path}")
    print(f"#   staging docs {s_ids}  vs  prod docs {p_ids}")
    print(f"#   REAL={len(real)}  VAR-only={len(varonly)}  "
          f"staging-only={len(stg_only)}  prod-only={len(prd_only)}\n{'#'*70}")

    if real:
        print("\n== REAL design differences (concrete props, both sides present) ==")
        for (media, sel), concrete, vs in real:
            print(f"  #{hashof(sel)} [{mlabel(media)}]")
            for prop, sv, pv in concrete:
                print(f"     {prop}: staging={sv}  prod={pv}")
    if varonly:
        print("\n== VAR-only differences (Elementor layout vars — usually real spacing) ==")
        for (media, sel), vs in varonly:
            print(f"  #{hashof(sel)} [{mlabel(media)}]")
            for prop, sv, pv in vs:
                print(f"     {prop}: staging={sv}  prod={pv}")
    if stg_only or prd_only:
        print("\n== STRUCTURAL (element present on one side only — rebuilt/added/removed) ==")
        print("  staging-only:", sorted(stg_only))
        print("  prod-only   :", sorted(prd_only))
    if not (real or varonly or stg_only or prd_only):
        print("\n  ✅ no differences")

if __name__ == "__main__":
    print(f"staging={STAGING}  prod={PROD}  cb={CB}")
    for path in PATHS:
        diff_page(path)
