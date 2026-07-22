/* pixel-diff / extract.js — paste into the browser console (Claude-in-Chrome
 * javascript_tool) on a live page to read COMPUTED styles per Elementor element.
 *
 * Use this to verify what css-diff.py finds and to compare sections that were
 * *rebuilt* (different element hashes, so css-diff can only mark them one-sided).
 * Computed styles reflect what actually renders — immune to stale generated CSS.
 *
 * Helpers defined on window:
 *   pdExtract()                 -> { hash: {compact computed styles + text} }
 *   pdSection(headingHash)      -> section's elements in DOM order (rebuilt-section compare)
 *   pdForceLoadImages(scopeSel) -> load lazy imgs before measuring (see LAZY TRAP)
 *   pdStructural(otherHashes)   -> added/removed vs an injected hash list
 *
 * LAZY TRAP: an Elementor image widget with no explicit size collapses to 0x0
 * until its file loads; a 0x0 element may never trigger its own lazy-load, so a
 * programmatic off-screen measurement reads 0x0 even though a scrolling user sees
 * it fine. Call pdForceLoadImages() before measuring image sizes.
 */
(() => {
  const hashOf = el => { const m=(el.className||'').toString().match(/elementor-element-([a-z0-9]+)/); return m?m[1]:null; };
  const px = v => v.replace(/(\d+(?:\.\d+)?)px/g,'$1');
  const CONTENT = /heading|text-editor|button|icon-list|image|counter|testimonial|price/i;
  const stripHost = s => s.replace(/https?:\/\/[^/)"']+/g,'@SITE');
  const sides = ['Top','Right','Bottom','Left'];

  window.pdExtract = function () {
    const out = {};
    for (const el of document.querySelectorAll('[class*="elementor-element-"]')) {
      const h = hashOf(el); if (!h) continue;
      const cs = getComputedStyle(el); const r = {};
      for (const s of sides) { const w=parseFloat(cs['border'+s+'Width'])||0;
        if (w>0) r['bd'+s]=px(cs['border'+s+'Width'])+' '+cs['border'+s+'Style']+' '+cs['border'+s+'Color']; }
      for (const [k,p] of [['rTL','borderTopLeftRadius'],['rTR','borderTopRightRadius'],['rBR','borderBottomRightRadius'],['rBL','borderBottomLeftRadius']]) { const v=cs[p]; if (v && v!=='0px') r[k]=px(v); }
      for (const s of sides) { const v=cs['padding'+s]; if (parseFloat(v)) r['pd'+s]=px(v); }
      for (const s of sides) { const v=cs['margin'+s]; if (parseFloat(v)) r['mg'+s]=px(v); }
      if (cs.backgroundColor!=='rgba(0, 0, 0, 0)') r.bg=cs.backgroundColor;
      if (cs.backgroundImage!=='none') r.bgi=stripHost(cs.backgroundImage);
      if (cs.boxShadow!=='none') r.sh=px(cs.boxShadow);
      if (cs.display) r.disp=cs.display;
      if (cs.display==='flex'){ r.fd=cs.flexDirection; if(cs.justifyContent!=='normal')r.jc=cs.justifyContent; if(cs.alignItems!=='normal')r.ai=cs.alignItems; if(cs.gap!=='normal')r.gap=px(cs.gap); }
      if (cs.opacity!=='1') r.op=cs.opacity;
      const wt = el.getAttribute('data-widget_type')||el.getAttribute('data-element_type')||'';
      if (CONTENT.test(wt)) { r.col=cs.color; r.fs=px(cs.fontSize); r.fw=cs.fontWeight; r.lh=px(cs.lineHeight);
        if(cs.letterSpacing!=='normal')r.ls=px(cs.letterSpacing); r.ta=cs.textAlign;
        if(cs.textTransform!=='none')r.tt=cs.textTransform; if(cs.textDecorationLine!=='none')r.td=cs.textDecorationLine;
        const t=(el.innerText||'').replace(/\s+/g,' ').trim().slice(0,160); if(t)r.txt=t; }
      r.wt = wt; out[h]=r;
    }
    return out;
  };

  window.pdSection = function (headingHash) {
    const h = document.querySelector('.elementor-element-'+headingHash);
    if (!h) return 'not found: '+headingHash;
    const sec = h.closest('.elementor-top-section, .e-con.e-parent') || h.closest('.e-con');
    return Array.from(sec.querySelectorAll('[class*="elementor-element-"]')).map(el=>{
      const cs=getComputedStyle(el), r=el.getBoundingClientRect();
      const wt=(el.getAttribute('data-widget_type')||el.getAttribute('data-element_type')||'').replace('.default','');
      const bt=parseFloat(cs.borderTopWidth)||0,br=parseFloat(cs.borderRightWidth)||0,bb=parseFloat(cs.borderBottomWidth)||0,bl=parseFloat(cs.borderLeftWidth)||0;
      const bd=(bt||br||bb||bl)?`bd:${bt},${br},${bb},${bl}(${cs.borderTopColor})`:'';
      return `${wt} ${Math.round(r.width)}x${Math.round(r.height)} ${bd}`.trim();
    });
  };

  window.pdForceLoadImages = async function (scopeSel) {
    const scope = scopeSel ? document.querySelector(scopeSel) : document;
    const imgs = Array.from(scope.querySelectorAll('img'));
    for (const im of imgs) { im.loading='eager'; im.setAttribute('src', im.getAttribute('src')); }
    await Promise.all(imgs.map(im=>im.decode().catch(()=>{})));
    await new Promise(r=>setTimeout(r,300));
    return imgs.map(im=>({file:(im.currentSrc||im.src).split('/').pop(), w:im.naturalWidth, h:im.naturalHeight}));
  };

  window.pdStructural = function (otherHashes) {
    const other = new Set(otherHashes);
    const here = Object.keys(window.pdExtract());
    return { addedHere: here.filter(h=>!other.has(h)), missingHere: otherHashes.filter(h=>!here.includes(h)) };
  };

  return 'pixel-diff helpers ready: pdExtract, pdSection, pdForceLoadImages, pdStructural';
})();
