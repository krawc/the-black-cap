#!/usr/bin/env node
/**
 * gen-curves.js
 *
 * Generates flame-curve variants where the sharp "tongue tip" can sit at
 * a different node position within a ti-group than in the base curves.
 *
 * Usage:  node scripts/gen-curves.js
 *
 * Rules enforced on every output curve:
 *   1. X-monotonicity in middle 50% of nodes — no anchor can have a smaller
 *      x than the previous anchor in that range (no side-concavity on flames).
 *      Violations are fixed automatically by nudging the offending anchor to
 *      match its predecessor, shifting its control points by the same delta.
 *   2. No self-intersection — checked by sampling each cubic bezier segment
 *      at SAMPLE_N points and testing all non-adjacent pairs for proximity.
 *      Violations abort with an error so the curve is never silently saved.
 */

const fs   = require('fs');
const path = require('path');

const DIR  = path.join(__dirname, '../public/flame-curves');
const load = f => JSON.parse(fs.readFileSync(path.join(DIR, f), 'utf8'));
const save = (f, d) => {
  fs.writeFileSync(path.join(DIR, f), JSON.stringify(d, null, 2) + '\n');
  console.log('wrote', f);
};
const clone = x => JSON.parse(JSON.stringify(x));

/* ── validation constants ────────────────────────────────────── */

const INTERSECT_EPS  = 20;  // px – how close two samples must be to count
const INTERSECT_GAP  = 5;   // minimum segment-index gap before checking
const SAMPLE_N       = 24;  // samples per bezier segment

/* ── geometry helpers ────────────────────────────────────────── */

function groupIndices(curve, ti) {
  return curve.reduce((a, n, i) => (n.ti === ti ? [...a, i] : a), []);
}

function peakOffset(curve, idxs) {
  return idxs.reduce(
    (best, idx, off) => (curve[idx].a[1] < curve[idxs[best]].a[1] ? off : best),
    0
  );
}

/**
 * Catmull-Rom control points for the segment ending at index i.
 * Returns { c1, c2 } matching the JSON storage convention:
 *   c1 = first bezier CP  (departure from node i-1)
 *   c2 = second bezier CP (arrival at node i)
 */
function crCP(curve, i, alpha = 0.35) {
  const n   = curve.length;
  const pp  = curve[Math.max(0, i - 2)].a;
  const prev = curve[i - 1].a;
  const curr = curve[i].a;
  const next = curve[Math.min(n - 1, i + 1)].a;

  const TxP = (curr[0] - pp[0])   * alpha;
  const TyP = (curr[1] - pp[1])   * alpha;
  const Tx  = (next[0] - prev[0]) * alpha;
  const Ty  = (next[1] - prev[1]) * alpha;

  return {
    c1: [prev[0] + TxP, prev[1] + TyP],
    c2: [curr[0] - Tx,  curr[1] - Ty],
  };
}

/** Sample a cubic bezier at parameter t ∈ [0,1]. */
function sampleBez(p0, c1, c2, p1, t) {
  const mt = 1 - t;
  return [
    mt*mt*mt*p0[0] + 3*mt*mt*t*c1[0] + 3*mt*t*t*c2[0] + t*t*t*p1[0],
    mt*mt*mt*p0[1] + 3*mt*mt*t*c1[1] + 3*mt*t*t*c2[1] + t*t*t*p1[1],
  ];
}

/* ── Rule 1: x-monotonicity ─────────────────────────────────── */

/** Returns the index range [lo, hi] considered the "middle 50%" of the curve. */
function middleRange(curve) {
  const n = curve.length;
  return [Math.floor(n / 4), Math.floor(3 * n / 4)];
}

function xclamp(v, lo, hi) { return Math.max(lo, Math.min(hi, v)); }

/**
 * Fix any anchor in the middle 50% whose x is smaller than its predecessor's.
 * The anchor is nudged right to exactly match the predecessor; its own c1/c2
 * and the next node's c1 are shifted by the same delta to preserve local shape.
 */
function fixAnchorXMonotonic(curve) {
  const c = clone(curve);
  const [lo, hi] = middleRange(c);

  for (let i = lo + 1; i <= hi; i++) {
    if (c[i].a[0] >= c[i - 1].a[0]) continue;

    const dx = c[i - 1].a[0] - c[i].a[0]; // how far back (positive)
    c[i].a = [c[i - 1].a[0], c[i].a[1]];

    // Shift the segment's control points by the same dx to keep local shape
    if (c[i].c1) c[i].c1 = [c[i].c1[0] + dx, c[i].c1[1]];
    if (c[i].c2) c[i].c2 = [c[i].c2[0] + dx, c[i].c2[1]];
    // The departure handle from this node lives in the next node's c1
    if (i + 1 < c.length && c[i + 1].c1) {
      c[i + 1].c1 = [c[i + 1].c1[0] + dx, c[i + 1].c1[1]];
    }
  }
  return c;
}

/**
 * For every segment in the middle 50%, clamp c1.x and c2.x to [prevX, currX].
 *
 * A handle whose x is *behind* the segment's start pulls the bezier curve
 * backward before the segment ends — creating a concave left side on the flame.
 * A handle whose x *overshoots* past the segment's end creates a concave right
 * side.  Clamping both to the segment's own x-range forces every bezier to
 * progress monotonically in x with no backward sweep.
 */
function fixHandleXMonotonic(curve) {
  const c = clone(curve);
  const [lo, hi] = middleRange(c);

  for (let i = lo + 1; i <= hi; i++) {
    if (c[i].cmd !== 'C') continue;
    const prevX = c[i - 1].a[0];
    const currX = c[i].a[0];

    if (c[i].c1) c[i].c1 = [xclamp(c[i].c1[0], prevX, currX), c[i].c1[1]];
    if (c[i].c2) c[i].c2 = [xclamp(c[i].c2[0], prevX, currX), c[i].c2[1]];
  }
  return c;
}

/**
 * Sample every bezier in the middle 50% and verify x never decreases.
 * Returns an error string on the first failure, or null if clean.
 */
function checkBezierXMonotonic(curve) {
  const [lo, hi] = middleRange(curve);
  const N = 30;

  for (let i = lo + 1; i <= hi; i++) {
    if (curve[i].cmd !== 'C') continue;
    const p0 = curve[i - 1].a;
    const c1 = curve[i].c1 || p0;
    const c2 = curve[i].c2 || curve[i].a;
    const p1 = curve[i].a;
    let prevX = p0[0];

    for (let k = 1; k <= N; k++) {
      const t  = k / N;
      const mt = 1 - t;
      const x  = mt*mt*mt*p0[0] + 3*mt*mt*t*c1[0] + 3*mt*t*t*c2[0] + t*t*t*p1[0];
      if (x < prevX - 0.5) {
        return (
          `segment ${i} (ti=${curve[i].ti}): x went backward at t=${t.toFixed(2)} ` +
          `(${prevX.toFixed(1)} → ${x.toFixed(1)})`
        );
      }
      prevX = x;
    }
  }
  return null;
}

/* ── Rule 2: self-intersection ──────────────────────────────── */

/**
 * Checks for self-intersection by sampling all bezier segments and testing
 * non-adjacent pairs for proximity. Returns a description string on the first
 * hit, or null if clean.
 */
function detectSelfIntersection(curve) {
  const segs = [];
  for (let i = 1; i < curve.length; i++) {
    if (curve[i].cmd !== 'C') continue;
    const p0 = curve[i - 1].a;
    const c1 = curve[i].c1 || curve[i - 1].a;
    const c2 = curve[i].c2 || curve[i].a;
    const p1 = curve[i].a;
    const pts = [];
    for (let k = 0; k <= SAMPLE_N; k++) {
      pts.push(sampleBez(p0, c1, c2, p1, k / SAMPLE_N));
    }
    segs.push({ i, pts });
  }

  const eps2 = INTERSECT_EPS * INTERSECT_EPS;
  for (let a = 0; a < segs.length; a++) {
    for (let b = a + INTERSECT_GAP; b < segs.length; b++) {
      for (const pa of segs[a].pts) {
        for (const pb of segs[b].pts) {
          const dx = pa[0] - pb[0], dy = pa[1] - pb[1];
          if (dx * dx + dy * dy < eps2) {
            return (
              `segments ${segs[a].i} and ${segs[b].i} come within ` +
              `${INTERSECT_EPS}px at ` +
              `(${pa[0].toFixed(0)},${pa[1].toFixed(0)}) vs ` +
              `(${pb[0].toFixed(0)},${pb[1].toFixed(0)})`
            );
          }
        }
      }
    }
  }
  return null;
}

/* ── enforce both rules, then save ─────────────────────────── */

function saveValidated(filename, curve) {
  // Rule 1a – fix any anchor that steps backward in x
  let out = fixAnchorXMonotonic(curve);

  // Rule 1b – clamp bezier handles to their segment's x-range
  out = fixHandleXMonotonic(out);

  // Verify the actual bezier curves are x-monotone after the fix
  const monoErr = checkBezierXMonotonic(out);
  if (monoErr) {
    throw new Error(`${filename}: bezier still not x-monotone after fix — ${monoErr}`);
  }

  // Rule 2 – abort on self-intersection
  const hit = detectSelfIntersection(out);
  if (hit) {
    throw new Error(`${filename}: self-intersection detected — ${hit}`);
  }

  save(filename, out);
}

/* ── peak-shift transform ────────────────────────────────────── */

/**
 * Shift the spike tip within a ti-group to `targetPeakOffset`.
 *
 * 1. Rotate y-values so the minimum lands on targetOffset (x stays fixed).
 * 2. Transplant the original spike's bezier-handle offsets onto the new peak,
 *    scaled by depth ratio so sharpness is preserved.
 * 3. Re-smooth displaced nodes with Catmull-Rom.
 */
function shiftPeak(curve, ti, targetPeakOffset) {
  const c    = clone(curve);
  const idxs = groupIndices(c, ti);
  if (idxs.length < 2) return c;

  const curOff = peakOffset(c, idxs);
  if (curOff === targetPeakOffset) return c;

  const spIdx  = idxs[curOff];
  const newSpIdx = idxs[targetPeakOffset];
  const spNode   = c[spIdx];

  // Capture spike handle offsets before modifying anything
  const c2Off = spNode.c2
    ? [spNode.c2[0] - spNode.a[0], spNode.c2[1] - spNode.a[1]]
    : null;

  const afterSpIdx = spIdx + 1;
  const c1DepOff = (afterSpIdx < c.length && c[afterSpIdx].c1)
    ? [c[afterSpIdx].c1[0] - spNode.a[0], c[afterSpIdx].c1[1] - spNode.a[1]]
    : null;

  const preSpIdx = spIdx - 1;
  const c1AppOff = (preSpIdx >= 0 && spNode.c1)
    ? [spNode.c1[0] - c[preSpIdx].a[0], spNode.c1[1] - c[preSpIdx].a[1]]
    : null;

  const spNeighY = (
    (preSpIdx  >= 0        ? c[preSpIdx].a[1]    : spNode.a[1]) +
    (afterSpIdx < c.length ? c[afterSpIdx].a[1]  : spNode.a[1])
  ) / 2;
  const origDepth = spNeighY - spNode.a[1];

  // Rotate y-values within the group
  const ys    = idxs.map(i => c[i].a[1]);
  const steps = (curOff - targetPeakOffset + idxs.length) % idxs.length;
  const newYs = ys.map((_, i) => ys[(i + steps) % ys.length]);
  idxs.forEach((idx, off) => { c[idx].a = [c[idx].a[0], newYs[off]]; });

  // Transplant spike handles onto the new peak
  const newSp       = c[newSpIdx];
  const newPreIdx   = newSpIdx - 1;
  const newAfterIdx = newSpIdx + 1;

  const newNeighY = (
    (newPreIdx   >= 0        ? c[newPreIdx].a[1]   : newSp.a[1]) +
    (newAfterIdx < c.length  ? c[newAfterIdx].a[1] : newSp.a[1])
  ) / 2;
  const newDepth = newNeighY - newSp.a[1];
  const scale    = origDepth > 1 ? newDepth / origDepth : 1;

  if (c2Off) {
    c[newSpIdx].c2 = [newSp.a[0] + c2Off[0], newSp.a[1] + c2Off[1] * scale];
  }
  if (c1DepOff && newAfterIdx < c.length) {
    c[newAfterIdx].c1 = [newSp.a[0] + c1DepOff[0], newSp.a[1] + c1DepOff[1] * scale];
  }
  if (c1AppOff && newPreIdx >= 0) {
    c[newSpIdx].c1 = [c[newPreIdx].a[0] + c1AppOff[0], c[newPreIdx].a[1] + c1AppOff[1] * scale];
  }

  // Re-smooth displaced nodes (not the new spike — it already has transplanted handles)
  const smooth = new Set([
    ...(idxs[0] > 1 ? [idxs[0] - 1] : []),
    ...idxs,
    ...(idxs[idxs.length - 1] + 1 < c.length ? [idxs[idxs.length - 1] + 1] : []),
  ]);
  smooth.delete(newSpIdx);
  smooth.delete(newAfterIdx);

  for (const idx of smooth) {
    if (idx <= 0) continue;
    const { c1, c2 } = crCP(c, idx);
    c[idx].c1 = c1;
    c[idx].c2 = c2;
  }

  return c;
}

/* ── generate curves 3-5 ─────────────────────────────────── */

// Base peak offsets (0-based within ti-group):
//   ti=17 : offset 1 (middle of 3-node group)
//   ti=21 : offset 0 (first  of 3-node group)
//   ti=25 : offset 1 (middle of 3-node group)
//   ti=27 : offset 0 (first  of 2-node group)

let c3 = load('curve-001.json');
c3 = shiftPeak(c3, 21, 1);   // ti=21 spike → middle node
c3 = shiftPeak(c3, 25, 2);   // ti=25 spike → last node

let c4 = load('curve-001.json');
c4 = shiftPeak(c4, 17, 0);   // ti=17 spike → first node
c4 = shiftPeak(c4, 21, 2);   // ti=21 spike → last node

let c5 = load('curve-002.json');
c5 = shiftPeak(c5, 17, 2);   // ti=17 spike → last node
c5 = shiftPeak(c5, 27, 1);   // ti=27 spike → second node

saveValidated('curve-003.json', c3);
saveValidated('curve-004.json', c4);
saveValidated('curve-005.json', c5);
