var rs = Object.defineProperty;
var Qr = (e) => {
  throw TypeError(e);
};
var is = (e, t, n) => t in e ? rs(e, t, { enumerable: !0, configurable: !0, writable: !0, value: n }) : e[t] = n;
var Ze = (e, t, n) => is(e, typeof t != "symbol" ? t + "" : t, n), mr = (e, t, n) => t.has(e) || Qr("Cannot " + n);
var c = (e, t, n) => (mr(e, t, "read from private field"), n ? n.call(e) : t.get(e)), j = (e, t, n) => t.has(e) ? Qr("Cannot add the same private member more than once") : t instanceof WeakSet ? t.add(e) : t.set(e, n), O = (e, t, n, r) => (mr(e, t, "write to private field"), r ? r.call(e, n) : t.set(e, n), n), re = (e, t, n) => (mr(e, t, "access private method"), n);
var Br = Array.isArray, ss = Array.prototype.indexOf, _n = Array.prototype.includes, or = Array.from, ls = Object.defineProperty, fn = Object.getOwnPropertyDescriptor, os = Object.getOwnPropertyDescriptors, hi = Object.prototype, as = Array.prototype, Hr = Object.getPrototypeOf, ei = Object.isExtensible;
const us = () => {
};
function fs(e) {
  for (var t = 0; t < e.length; t++)
    e[t]();
}
function vi() {
  var e, t, n = new Promise((r, i) => {
    e = r, t = i;
  });
  return { promise: n, resolve: e, reject: t };
}
const ce = 2, mn = 4, ar = 8, pi = 1 << 24, Rt = 16, et = 32, Yt = 64, yr = 128, Ve = 512, le = 1024, ke = 2048, ut = 4096, ze = 8192, $e = 16384, Xt = 32768, Er = 1 << 25, bn = 65536, ti = 1 << 17, cs = 1 << 18, En = 1 << 19, ds = 1 << 20, at = 1 << 25, Zt = 65536, kr = 1 << 21, Vr = 1 << 22, St = 1 << 23, Nn = Symbol("$state"), hs = Symbol("legacy props"), vs = Symbol(""), ht = new class extends Error {
  constructor() {
    super(...arguments);
    Ze(this, "name", "StaleReactionError");
    Ze(this, "message", "The reaction that called `getAbortSignal()` was re-run or destroyed");
  }
}();
var fi;
const ps = (
  // We gotta write it like this because after downleveling the pure comment may end up in the wrong location
  !!((fi = globalThis.document) != null && fi.contentType) && /* @__PURE__ */ globalThis.document.contentType.includes("xml")
);
function gs(e) {
  throw new Error("https://svelte.dev/e/lifecycle_outside_component");
}
function _s() {
  throw new Error("https://svelte.dev/e/async_derived_orphan");
}
function ms(e, t, n) {
  throw new Error("https://svelte.dev/e/each_key_duplicate");
}
function bs(e) {
  throw new Error("https://svelte.dev/e/effect_in_teardown");
}
function ws() {
  throw new Error("https://svelte.dev/e/effect_in_unowned_derived");
}
function xs(e) {
  throw new Error("https://svelte.dev/e/effect_orphan");
}
function ys() {
  throw new Error("https://svelte.dev/e/effect_update_depth_exceeded");
}
function Es(e) {
  throw new Error("https://svelte.dev/e/props_invalid_value");
}
function ks() {
  throw new Error("https://svelte.dev/e/state_descriptors_fixed");
}
function As() {
  throw new Error("https://svelte.dev/e/state_prototype_fixed");
}
function Ss() {
  throw new Error("https://svelte.dev/e/state_unsafe_mutation");
}
function Ts() {
  throw new Error("https://svelte.dev/e/svelte_boundary_reset_onerror");
}
const Ms = 1, Cs = 2, gi = 4, Ds = 8, Is = 16, Rs = 1, Os = 4, Ns = 8, Ps = 16, zs = 1, Ls = 2, ge = Symbol(), _i = "http://www.w3.org/1999/xhtml";
function js() {
  console.warn("https://svelte.dev/e/svelte_boundary_reset_noop");
}
function mi(e) {
  return e === this.v;
}
function Fs(e, t) {
  return e != e ? t == t : e !== t || e !== null && typeof e == "object" || typeof e == "function";
}
function bi(e) {
  return !Fs(e, this.v);
}
const Bs = [];
function br(e, t = !1, n = !1) {
  return Wn(e, /* @__PURE__ */ new Map(), "", Bs, null, n);
}
function Wn(e, t, n, r, i = null, s = !1) {
  if (typeof e == "object" && e !== null) {
    var o = t.get(e);
    if (o !== void 0) return o;
    if (e instanceof Map) return (
      /** @type {Snapshot<T>} */
      new Map(e)
    );
    if (e instanceof Set) return (
      /** @type {Snapshot<T>} */
      new Set(e)
    );
    if (Br(e)) {
      var f = (
        /** @type {Snapshot<any>} */
        Array(e.length)
      );
      t.set(e, f), i !== null && t.set(i, f);
      for (var a = 0; a < e.length; a += 1) {
        var u = e[a];
        a in e && (f[a] = Wn(u, t, n, r, null, s));
      }
      return f;
    }
    if (Hr(e) === hi) {
      f = {}, t.set(e, f), i !== null && t.set(i, f);
      for (var h of Object.keys(e))
        f[h] = Wn(
          // @ts-expect-error
          e[h],
          t,
          n,
          r,
          null,
          s
        );
      return f;
    }
    if (e instanceof Date)
      return (
        /** @type {Snapshot<T>} */
        structuredClone(e)
      );
    if (typeof /** @type {T & { toJSON?: any } } */
    e.toJSON == "function" && !s)
      return Wn(
        /** @type {T & { toJSON(): any } } */
        e.toJSON(),
        t,
        n,
        r,
        // Associate the instance with the toJSON clone
        e
      );
  }
  if (e instanceof EventTarget)
    return (
      /** @type {Snapshot<T>} */
      e
    );
  try {
    return (
      /** @type {Snapshot<T>} */
      structuredClone(e)
    );
  } catch {
    return (
      /** @type {Snapshot<T>} */
      e
    );
  }
}
let De = null;
function wn(e) {
  De = e;
}
function ur(e, t = !1, n) {
  De = {
    p: De,
    i: !1,
    c: null,
    e: null,
    s: e,
    x: null,
    r: (
      /** @type {Effect} */
      H
    ),
    l: null
  };
}
function fr(e) {
  var t = (
    /** @type {ComponentContext} */
    De
  ), n = t.e;
  if (n !== null) {
    t.e = null;
    for (var r of n)
      Bi(r);
  }
  return t.i = !0, De = t.p, /** @type {T} */
  {};
}
function wi() {
  return !0;
}
let on = [];
function Hs() {
  var e = on;
  on = [], fs(e);
}
function Tt(e) {
  if (on.length === 0) {
    var t = on;
    queueMicrotask(() => {
      t === on && Hs();
    });
  }
  on.push(e);
}
function xi(e) {
  var t = H;
  if (t === null)
    return L.f |= St, e;
  if ((t.f & Xt) === 0 && (t.f & mn) === 0)
    throw e;
  At(e, t);
}
function At(e, t) {
  for (; t !== null; ) {
    if ((t.f & yr) !== 0) {
      if ((t.f & Xt) === 0)
        throw e;
      try {
        t.b.error(e);
        return;
      } catch (n) {
        e = n;
      }
    }
    t = t.parent;
  }
  throw e;
}
const Vs = -7169;
function ie(e, t) {
  e.f = e.f & Vs | t;
}
function $r(e) {
  (e.f & Ve) !== 0 || e.deps === null ? ie(e, le) : ie(e, ut);
}
function yi(e) {
  if (e !== null)
    for (const t of e)
      (t.f & ce) === 0 || (t.f & Zt) === 0 || (t.f ^= Zt, yi(
        /** @type {Derived} */
        t.deps
      ));
}
function Ei(e, t, n) {
  (e.f & ke) !== 0 ? t.add(e) : (e.f & ut) !== 0 && n.add(e), yi(e.deps), ie(e, le);
}
let Kn = !1;
function $s(e) {
  var t = Kn;
  try {
    return Kn = !1, [e(), Kn];
  } finally {
    Kn = t;
  }
}
const Lt = /* @__PURE__ */ new Set();
let B = null, _e = null, Ar = null, wr = !1, an = null, Jn = null;
var ni = 0;
let Us = 1;
var cn, dn, hn, vn, Fn, Oe, Bt, vt, pt, pn, xe, Sr, Tr, Mr, Cr, ki;
const ir = class ir {
  constructor() {
    j(this, xe);
    // for debugging. TODO remove once async is stable
    Ze(this, "id", Us++);
    /**
     * The current values of any sources that are updated in this batch
     * They keys of this map are identical to `this.#previous`
     * @type {Map<Source, any>}
     */
    Ze(this, "current", /* @__PURE__ */ new Map());
    /**
     * The values of any sources that are updated in this batch _before_ those updates took place.
     * They keys of this map are identical to `this.#current`
     * @type {Map<Source, any>}
     */
    Ze(this, "previous", /* @__PURE__ */ new Map());
    /**
     * When the batch is committed (and the DOM is updated), we need to remove old branches
     * and append new ones by calling the functions added inside (if/each/key/etc) blocks
     * @type {Set<(batch: Batch) => void>}
     */
    j(this, cn, /* @__PURE__ */ new Set());
    /**
     * If a fork is discarded, we need to destroy any effects that are no longer needed
     * @type {Set<(batch: Batch) => void>}
     */
    j(this, dn, /* @__PURE__ */ new Set());
    /**
     * The number of async effects that are currently in flight
     */
    j(this, hn, 0);
    /**
     * The number of async effects that are currently in flight, _not_ inside a pending boundary
     */
    j(this, vn, 0);
    /**
     * A deferred that resolves when the batch is committed, used with `settled()`
     * TODO replace with Promise.withResolvers once supported widely enough
     * @type {{ promise: Promise<void>, resolve: (value?: any) => void, reject: (reason: unknown) => void } | null}
     */
    j(this, Fn, null);
    /**
     * The root effects that need to be flushed
     * @type {Effect[]}
     */
    j(this, Oe, []);
    /**
     * Deferred effects (which run after async work has completed) that are DIRTY
     * @type {Set<Effect>}
     */
    j(this, Bt, /* @__PURE__ */ new Set());
    /**
     * Deferred effects that are MAYBE_DIRTY
     * @type {Set<Effect>}
     */
    j(this, vt, /* @__PURE__ */ new Set());
    /**
     * A map of branches that still exist, but will be destroyed when this batch
     * is committed — we skip over these during `process`.
     * The value contains child effects that were dirty/maybe_dirty before being reset,
     * so they can be rescheduled if the branch survives.
     * @type {Map<Effect, { d: Effect[], m: Effect[] }>}
     */
    j(this, pt, /* @__PURE__ */ new Map());
    Ze(this, "is_fork", !1);
    j(this, pn, !1);
  }
  /**
   * Add an effect to the #skipped_branches map and reset its children
   * @param {Effect} effect
   */
  skip_effect(t) {
    c(this, pt).has(t) || c(this, pt).set(t, { d: [], m: [] });
  }
  /**
   * Remove an effect from the #skipped_branches map and reschedule
   * any tracked dirty/maybe_dirty child effects
   * @param {Effect} effect
   */
  unskip_effect(t) {
    var n = c(this, pt).get(t);
    if (n) {
      c(this, pt).delete(t);
      for (var r of n.d)
        ie(r, ke), this.schedule(r);
      for (r of n.m)
        ie(r, ut), this.schedule(r);
    }
  }
  /**
   * Associate a change to a given source with the current
   * batch, noting its previous and current values
   * @param {Source} source
   * @param {any} old_value
   */
  capture(t, n) {
    n !== ge && !this.previous.has(t) && this.previous.set(t, n), (t.f & St) === 0 && (this.current.set(t, t.v), _e == null || _e.set(t, t.v));
  }
  activate() {
    B = this;
  }
  deactivate() {
    B = null, _e = null;
  }
  flush() {
    try {
      wr = !0, B = this, re(this, xe, Tr).call(this);
    } finally {
      ni = 0, Ar = null, an = null, Jn = null, wr = !1, B = null, _e = null, Mt.clear();
    }
  }
  discard() {
    for (const t of c(this, dn)) t(this);
    c(this, dn).clear(), Lt.delete(this);
  }
  /**
   *
   * @param {boolean} blocking
   */
  increment(t) {
    O(this, hn, c(this, hn) + 1), t && O(this, vn, c(this, vn) + 1);
  }
  /**
   * @param {boolean} blocking
   * @param {boolean} skip - whether to skip updates (because this is triggered by a stale reaction)
   */
  decrement(t, n) {
    O(this, hn, c(this, hn) - 1), t && O(this, vn, c(this, vn) - 1), !(c(this, pn) || n) && (O(this, pn, !0), Tt(() => {
      O(this, pn, !1), this.flush();
    }));
  }
  /**
   * @param {Set<Effect>} dirty_effects
   * @param {Set<Effect>} maybe_dirty_effects
   */
  transfer_effects(t, n) {
    for (const r of t)
      c(this, Bt).add(r);
    for (const r of n)
      c(this, vt).add(r);
    t.clear(), n.clear();
  }
  /** @param {(batch: Batch) => void} fn */
  oncommit(t) {
    c(this, cn).add(t);
  }
  /** @param {(batch: Batch) => void} fn */
  ondiscard(t) {
    c(this, dn).add(t);
  }
  settled() {
    return (c(this, Fn) ?? O(this, Fn, vi())).promise;
  }
  static ensure() {
    if (B === null) {
      const t = B = new ir();
      wr || (Lt.add(B), Tt(() => {
        B === t && t.flush();
      }));
    }
    return B;
  }
  apply() {
    {
      _e = null;
      return;
    }
  }
  /**
   *
   * @param {Effect} effect
   */
  schedule(t) {
    var i;
    if (Ar = t, (i = t.b) != null && i.is_pending && (t.f & (mn | ar | pi)) !== 0 && (t.f & Xt) === 0) {
      t.b.defer_effect(t);
      return;
    }
    for (var n = t; n.parent !== null; ) {
      n = n.parent;
      var r = n.f;
      if (an !== null && n === H && (L === null || (L.f & ce) === 0))
        return;
      if ((r & (Yt | et)) !== 0) {
        if ((r & le) === 0)
          return;
        n.f ^= le;
      }
    }
    c(this, Oe).push(n);
  }
};
cn = new WeakMap(), dn = new WeakMap(), hn = new WeakMap(), vn = new WeakMap(), Fn = new WeakMap(), Oe = new WeakMap(), Bt = new WeakMap(), vt = new WeakMap(), pt = new WeakMap(), pn = new WeakMap(), xe = new WeakSet(), Sr = function() {
  return this.is_fork || c(this, vn) > 0;
}, Tr = function() {
  var f, a;
  if (ni++ > 1e3 && (Lt.delete(this), qs()), !re(this, xe, Sr).call(this)) {
    for (const u of c(this, Bt))
      c(this, vt).delete(u), ie(u, ke), this.schedule(u);
    for (const u of c(this, vt))
      ie(u, ut), this.schedule(u);
  }
  const t = c(this, Oe);
  O(this, Oe, []), this.apply();
  var n = an = [], r = [], i = Jn = [];
  for (const u of t)
    try {
      re(this, xe, Mr).call(this, u, n, r);
    } catch (h) {
      throw Mi(u), h;
    }
  if (B = null, i.length > 0) {
    var s = ir.ensure();
    for (const u of i)
      s.schedule(u);
  }
  if (an = null, Jn = null, re(this, xe, Sr).call(this)) {
    re(this, xe, Cr).call(this, r), re(this, xe, Cr).call(this, n);
    for (const [u, h] of c(this, pt))
      Ti(u, h);
  } else {
    c(this, hn) === 0 && Lt.delete(this), c(this, Bt).clear(), c(this, vt).clear();
    for (const u of c(this, cn)) u(this);
    c(this, cn).clear(), ri(r), ri(n), (f = c(this, Fn)) == null || f.resolve();
  }
  var o = (
    /** @type {Batch | null} */
    /** @type {unknown} */
    B
  );
  if (c(this, Oe).length > 0) {
    const u = o ?? (o = this);
    c(u, Oe).push(...c(this, Oe).filter((h) => !c(u, Oe).includes(h)));
  }
  o !== null && (Lt.add(o), re(a = o, xe, Tr).call(a)), Lt.has(this) || re(this, xe, ki).call(this);
}, /**
 * Traverse the effect tree, executing effects or stashing
 * them for later execution as appropriate
 * @param {Effect} root
 * @param {Effect[]} effects
 * @param {Effect[]} render_effects
 */
Mr = function(t, n, r) {
  t.f ^= le;
  for (var i = t.first; i !== null; ) {
    var s = i.f, o = (s & (et | Yt)) !== 0, f = o && (s & le) !== 0, a = f || (s & ze) !== 0 || c(this, pt).has(i);
    if (!a && i.fn !== null) {
      o ? i.f ^= le : (s & mn) !== 0 ? n.push(i) : qn(i) && ((s & Rt) !== 0 && c(this, vt).add(i), yn(i));
      var u = i.first;
      if (u !== null) {
        i = u;
        continue;
      }
    }
    for (; i !== null; ) {
      var h = i.next;
      if (h !== null) {
        i = h;
        break;
      }
      i = i.parent;
    }
  }
}, /**
 * @param {Effect[]} effects
 */
Cr = function(t) {
  for (var n = 0; n < t.length; n += 1)
    Ei(t[n], c(this, Bt), c(this, vt));
}, ki = function() {
  var a;
  for (const u of Lt) {
    var t = u.id < this.id, n = [];
    for (const [h, m] of this.current) {
      if (u.current.has(h))
        if (t && m !== u.current.get(h))
          u.current.set(h, m);
        else
          continue;
      n.push(h);
    }
    var r = [...u.current.keys()].filter((h) => !this.current.has(h));
    if (r.length === 0)
      t && u.discard();
    else if (n.length > 0) {
      u.activate();
      var i = /* @__PURE__ */ new Set(), s = /* @__PURE__ */ new Map();
      for (var o of n)
        Ai(o, r, i, s);
      if (c(u, Oe).length > 0) {
        u.apply();
        for (var f of c(u, Oe))
          re(a = u, xe, Mr).call(a, f, [], []);
        O(u, Oe, []);
      }
      u.deactivate();
    }
  }
};
let Gt = ir;
function qs() {
  try {
    ys();
  } catch (e) {
    At(e, Ar);
  }
}
let Ge = null;
function ri(e) {
  var t = e.length;
  if (t !== 0) {
    for (var n = 0; n < t; ) {
      var r = e[n++];
      if ((r.f & ($e | ze)) === 0 && qn(r) && (Ge = /* @__PURE__ */ new Set(), yn(r), r.deps === null && r.first === null && r.nodes === null && r.teardown === null && r.ac === null && $i(r), (Ge == null ? void 0 : Ge.size) > 0)) {
        Mt.clear();
        for (const i of Ge) {
          if ((i.f & ($e | ze)) !== 0) continue;
          const s = [i];
          let o = i.parent;
          for (; o !== null; )
            Ge.has(o) && (Ge.delete(o), s.push(o)), o = o.parent;
          for (let f = s.length - 1; f >= 0; f--) {
            const a = s[f];
            (a.f & ($e | ze)) === 0 && yn(a);
          }
        }
        Ge.clear();
      }
    }
    Ge = null;
  }
}
function Ai(e, t, n, r) {
  if (!n.has(e) && (n.add(e), e.reactions !== null))
    for (const i of e.reactions) {
      const s = i.f;
      (s & ce) !== 0 ? Ai(
        /** @type {Derived} */
        i,
        t,
        n,
        r
      ) : (s & (Vr | Rt)) !== 0 && (s & ke) === 0 && Si(i, t, r) && (ie(i, ke), Ur(
        /** @type {Effect} */
        i
      ));
    }
}
function Si(e, t, n) {
  const r = n.get(e);
  if (r !== void 0) return r;
  if (e.deps !== null)
    for (const i of e.deps) {
      if (_n.call(t, i))
        return !0;
      if ((i.f & ce) !== 0 && Si(
        /** @type {Derived} */
        i,
        t,
        n
      ))
        return n.set(
          /** @type {Derived} */
          i,
          !0
        ), !0;
    }
  return n.set(e, !1), !1;
}
function Ur(e) {
  B.schedule(e);
}
function Ti(e, t) {
  if (!((e.f & et) !== 0 && (e.f & le) !== 0)) {
    (e.f & ke) !== 0 ? t.d.push(e) : (e.f & ut) !== 0 && t.m.push(e), ie(e, le);
    for (var n = e.first; n !== null; )
      Ti(n, t), n = n.next;
  }
}
function Mi(e) {
  ie(e, le);
  for (var t = e.first; t !== null; )
    Mi(t), t = t.next;
}
function Ys(e) {
  let t = 0, n = Kt(0), r;
  return () => {
    Zr() && (l(n), Hi(() => (t === 0 && (r = dr(() => e(() => Pn(n)))), t += 1, () => {
      Tt(() => {
        t -= 1, t === 0 && (r == null || r(), r = void 0, Pn(n));
      });
    })));
  };
}
var Zs = bn | En;
function Gs(e, t, n, r) {
  new Ks(e, t, n, r);
}
var je, Fr, st, Ht, Te, lt, Ne, Xe, gt, Vt, kt, gn, Bn, Hn, _t, sr, oe, Xs, Ws, Js, Dr, Qn, er, Ir;
class Ks {
  /**
   * @param {TemplateNode} node
   * @param {BoundaryProps} props
   * @param {((anchor: Node) => void)} children
   * @param {((error: unknown) => unknown) | undefined} [transform_error]
   */
  constructor(t, n, r, i) {
    j(this, oe);
    /** @type {Boundary | null} */
    Ze(this, "parent");
    Ze(this, "is_pending", !1);
    /**
     * API-level transformError transform function. Transforms errors before they reach the `failed` snippet.
     * Inherited from parent boundary, or defaults to identity.
     * @type {(error: unknown) => unknown}
     */
    Ze(this, "transform_error");
    /** @type {TemplateNode} */
    j(this, je);
    /** @type {TemplateNode | null} */
    j(this, Fr, null);
    /** @type {BoundaryProps} */
    j(this, st);
    /** @type {((anchor: Node) => void)} */
    j(this, Ht);
    /** @type {Effect} */
    j(this, Te);
    /** @type {Effect | null} */
    j(this, lt, null);
    /** @type {Effect | null} */
    j(this, Ne, null);
    /** @type {Effect | null} */
    j(this, Xe, null);
    /** @type {DocumentFragment | null} */
    j(this, gt, null);
    j(this, Vt, 0);
    j(this, kt, 0);
    j(this, gn, !1);
    /** @type {Set<Effect>} */
    j(this, Bn, /* @__PURE__ */ new Set());
    /** @type {Set<Effect>} */
    j(this, Hn, /* @__PURE__ */ new Set());
    /**
     * A source containing the number of pending async deriveds/expressions.
     * Only created if `$effect.pending()` is used inside the boundary,
     * otherwise updating the source results in needless `Batch.ensure()`
     * calls followed by no-op flushes
     * @type {Source<number> | null}
     */
    j(this, _t, null);
    j(this, sr, Ys(() => (O(this, _t, Kt(c(this, Vt))), () => {
      O(this, _t, null);
    })));
    var s;
    O(this, je, t), O(this, st, n), O(this, Ht, (o) => {
      var f = (
        /** @type {Effect} */
        H
      );
      f.b = this, f.f |= yr, r(o);
    }), this.parent = /** @type {Effect} */
    H.b, this.transform_error = i ?? ((s = this.parent) == null ? void 0 : s.transform_error) ?? ((o) => o), O(this, Te, Gr(() => {
      re(this, oe, Dr).call(this);
    }, Zs));
  }
  /**
   * Defer an effect inside a pending boundary until the boundary resolves
   * @param {Effect} effect
   */
  defer_effect(t) {
    Ei(t, c(this, Bn), c(this, Hn));
  }
  /**
   * Returns `false` if the effect exists inside a boundary whose pending snippet is shown
   * @returns {boolean}
   */
  is_rendered() {
    return !this.is_pending && (!this.parent || this.parent.is_rendered());
  }
  has_pending_snippet() {
    return !!c(this, st).pending;
  }
  /**
   * Update the source that powers `$effect.pending()` inside this boundary,
   * and controls when the current `pending` snippet (if any) is removed.
   * Do not call from inside the class
   * @param {1 | -1} d
   * @param {Batch} batch
   */
  update_pending_count(t, n) {
    re(this, oe, Ir).call(this, t, n), O(this, Vt, c(this, Vt) + t), !(!c(this, _t) || c(this, gn)) && (O(this, gn, !0), Tt(() => {
      O(this, gn, !1), c(this, _t) && xn(c(this, _t), c(this, Vt));
    }));
  }
  get_effect_pending() {
    return c(this, sr).call(this), l(
      /** @type {Source<number>} */
      c(this, _t)
    );
  }
  /** @param {unknown} error */
  error(t) {
    var n = c(this, st).onerror;
    let r = c(this, st).failed;
    if (!n && !r)
      throw t;
    c(this, lt) && (Ce(c(this, lt)), O(this, lt, null)), c(this, Ne) && (Ce(c(this, Ne)), O(this, Ne, null)), c(this, Xe) && (Ce(c(this, Xe)), O(this, Xe, null));
    var i = !1, s = !1;
    const o = () => {
      if (i) {
        js();
        return;
      }
      i = !0, s && Ts(), c(this, Xe) !== null && Ut(c(this, Xe), () => {
        O(this, Xe, null);
      }), re(this, oe, er).call(this, () => {
        re(this, oe, Dr).call(this);
      });
    }, f = (a) => {
      try {
        s = !0, n == null || n(a, o), s = !1;
      } catch (u) {
        At(u, c(this, Te) && c(this, Te).parent);
      }
      r && O(this, Xe, re(this, oe, er).call(this, () => {
        try {
          return Fe(() => {
            var u = (
              /** @type {Effect} */
              H
            );
            u.b = this, u.f |= yr, r(
              c(this, je),
              () => a,
              () => o
            );
          });
        } catch (u) {
          return At(
            u,
            /** @type {Effect} */
            c(this, Te).parent
          ), null;
        }
      }));
    };
    Tt(() => {
      var a;
      try {
        a = this.transform_error(t);
      } catch (u) {
        At(u, c(this, Te) && c(this, Te).parent);
        return;
      }
      a !== null && typeof a == "object" && typeof /** @type {any} */
      a.then == "function" ? a.then(
        f,
        /** @param {unknown} e */
        (u) => At(u, c(this, Te) && c(this, Te).parent)
      ) : f(a);
    });
  }
}
je = new WeakMap(), Fr = new WeakMap(), st = new WeakMap(), Ht = new WeakMap(), Te = new WeakMap(), lt = new WeakMap(), Ne = new WeakMap(), Xe = new WeakMap(), gt = new WeakMap(), Vt = new WeakMap(), kt = new WeakMap(), gn = new WeakMap(), Bn = new WeakMap(), Hn = new WeakMap(), _t = new WeakMap(), sr = new WeakMap(), oe = new WeakSet(), Xs = function() {
  try {
    O(this, lt, Fe(() => c(this, Ht).call(this, c(this, je))));
  } catch (t) {
    this.error(t);
  }
}, /**
 * @param {unknown} error The deserialized error from the server's hydration comment
 */
Ws = function(t) {
  const n = c(this, st).failed;
  n && O(this, Xe, Fe(() => {
    n(
      c(this, je),
      () => t,
      () => () => {
      }
    );
  }));
}, Js = function() {
  const t = c(this, st).pending;
  t && (this.is_pending = !0, O(this, Ne, Fe(() => t(c(this, je)))), Tt(() => {
    var n = O(this, gt, document.createDocumentFragment()), r = Dt();
    n.append(r), O(this, lt, re(this, oe, er).call(this, () => Fe(() => c(this, Ht).call(this, r)))), c(this, kt) === 0 && (c(this, je).before(n), O(this, gt, null), Ut(
      /** @type {Effect} */
      c(this, Ne),
      () => {
        O(this, Ne, null);
      }
    ), re(this, oe, Qn).call(
      this,
      /** @type {Batch} */
      B
    ));
  }));
}, Dr = function() {
  try {
    if (this.is_pending = this.has_pending_snippet(), O(this, kt, 0), O(this, Vt, 0), O(this, lt, Fe(() => {
      c(this, Ht).call(this, c(this, je));
    })), c(this, kt) > 0) {
      var t = O(this, gt, document.createDocumentFragment());
      Wr(c(this, lt), t);
      const n = (
        /** @type {(anchor: Node) => void} */
        c(this, st).pending
      );
      O(this, Ne, Fe(() => n(c(this, je))));
    } else
      re(this, oe, Qn).call(
        this,
        /** @type {Batch} */
        B
      );
  } catch (n) {
    this.error(n);
  }
}, /**
 * @param {Batch} batch
 */
Qn = function(t) {
  this.is_pending = !1, t.transfer_effects(c(this, Bn), c(this, Hn));
}, /**
 * @template T
 * @param {() => T} fn
 */
er = function(t) {
  var n = H, r = L, i = De;
  ft(c(this, Te)), qe(c(this, Te)), wn(c(this, Te).ctx);
  try {
    return Gt.ensure(), t();
  } catch (s) {
    return xi(s), null;
  } finally {
    ft(n), qe(r), wn(i);
  }
}, /**
 * Updates the pending count associated with the currently visible pending snippet,
 * if any, such that we can replace the snippet with content once work is done
 * @param {1 | -1} d
 * @param {Batch} batch
 */
Ir = function(t, n) {
  var r;
  if (!this.has_pending_snippet()) {
    this.parent && re(r = this.parent, oe, Ir).call(r, t, n);
    return;
  }
  O(this, kt, c(this, kt) + t), c(this, kt) === 0 && (re(this, oe, Qn).call(this, n), c(this, Ne) && Ut(c(this, Ne), () => {
    O(this, Ne, null);
  }), c(this, gt) && (c(this, je).before(c(this, gt)), O(this, gt, null)));
};
function Qs(e, t, n, r) {
  const i = cr;
  var s = e.filter((_) => !_.settled);
  if (n.length === 0 && s.length === 0) {
    r(t.map(i));
    return;
  }
  var o = (
    /** @type {Effect} */
    H
  ), f = el(), a = s.length === 1 ? s[0].promise : s.length > 1 ? Promise.all(s.map((_) => _.promise)) : null;
  function u(_) {
    f();
    try {
      r(_);
    } catch (E) {
      (o.f & $e) === 0 && At(E, o);
    }
    nr();
  }
  if (n.length === 0) {
    a.then(() => u(t.map(i)));
    return;
  }
  var h = Ci();
  function m() {
    Promise.all(n.map((_) => /* @__PURE__ */ tl(_))).then((_) => u([...t.map(i), ..._])).catch((_) => At(_, o)).finally(() => h());
  }
  a ? a.then(() => {
    f(), m(), nr();
  }) : m();
}
function el() {
  var e = (
    /** @type {Effect} */
    H
  ), t = L, n = De, r = (
    /** @type {Batch} */
    B
  );
  return function(s = !0) {
    ft(e), qe(t), wn(n), s && (e.f & $e) === 0 && (r == null || r.activate(), r == null || r.apply());
  };
}
function nr(e = !0) {
  ft(null), qe(null), wn(null), e && (B == null || B.deactivate());
}
function Ci() {
  var e = (
    /** @type {Boundary} */
    /** @type {Effect} */
    H.b
  ), t = (
    /** @type {Batch} */
    B
  ), n = e.is_rendered();
  return e.update_pending_count(1, t), t.increment(n), (r = !1) => {
    e.update_pending_count(-1, t), t.decrement(n, r);
  };
}
// @__NO_SIDE_EFFECTS__
function cr(e) {
  var t = ce | ke, n = L !== null && (L.f & ce) !== 0 ? (
    /** @type {Derived} */
    L
  ) : null;
  return H !== null && (H.f |= En), {
    ctx: De,
    deps: null,
    effects: null,
    equals: mi,
    f: t,
    fn: e,
    reactions: null,
    rv: 0,
    v: (
      /** @type {V} */
      ge
    ),
    wv: 0,
    parent: n ?? H,
    ac: null
  };
}
// @__NO_SIDE_EFFECTS__
function tl(e, t, n) {
  let r = (
    /** @type {Effect | null} */
    H
  );
  r === null && _s();
  var i = (
    /** @type {Promise<V>} */
    /** @type {unknown} */
    void 0
  ), s = Kt(
    /** @type {V} */
    ge
  ), o = !L, f = /* @__PURE__ */ new Map();
  return vl(() => {
    var E;
    var a = (
      /** @type {Effect} */
      H
    ), u = vi();
    i = u.promise;
    try {
      Promise.resolve(e()).then(u.resolve, u.reject).finally(nr);
    } catch (y) {
      u.reject(y), nr();
    }
    var h = (
      /** @type {Batch} */
      B
    );
    if (o) {
      if ((a.f & Xt) !== 0)
        var m = Ci();
      if (
        /** @type {Boundary} */
        r.b.is_rendered()
      )
        (E = f.get(h)) == null || E.reject(ht), f.delete(h);
      else {
        for (const y of f.values())
          y.reject(ht);
        f.clear();
      }
      f.set(h, u);
    }
    const _ = (y, S = void 0) => {
      if (m) {
        var g = S === ht;
        m(g);
      }
      if (!(S === ht || (a.f & $e) !== 0)) {
        if (h.activate(), S)
          s.f |= St, xn(s, S);
        else {
          (s.f & St) !== 0 && (s.f ^= St), xn(s, y);
          for (const [k, z] of f) {
            if (f.delete(k), k === h) break;
            z.reject(ht);
          }
        }
        h.deactivate();
      }
    };
    u.promise.then(_, (y) => _(null, y || "unknown"));
  }), Fi(() => {
    for (const a of f.values())
      a.reject(ht);
  }), new Promise((a) => {
    function u(h) {
      function m() {
        h === i ? a(s) : u(i);
      }
      h.then(m, m);
    }
    u(i);
  });
}
// @__NO_SIDE_EFFECTS__
function Be(e) {
  const t = /* @__PURE__ */ cr(e);
  return Yi(t), t;
}
// @__NO_SIDE_EFFECTS__
function Di(e) {
  const t = /* @__PURE__ */ cr(e);
  return t.equals = bi, t;
}
function nl(e) {
  var t = e.effects;
  if (t !== null) {
    e.effects = null;
    for (var n = 0; n < t.length; n += 1)
      Ce(
        /** @type {Effect} */
        t[n]
      );
  }
}
function rl(e) {
  for (var t = e.parent; t !== null; ) {
    if ((t.f & ce) === 0)
      return (t.f & $e) === 0 ? (
        /** @type {Effect} */
        t
      ) : null;
    t = t.parent;
  }
  return null;
}
function qr(e) {
  var t, n = H;
  ft(rl(e));
  try {
    e.f &= ~Zt, nl(e), t = Xi(e);
  } finally {
    ft(n);
  }
  return t;
}
function Ii(e) {
  var t = e.v, n = qr(e);
  if (!e.equals(n) && (e.wv = Gi(), (!(B != null && B.is_fork) || e.deps === null) && (e.v = n, B == null || B.capture(e, t), e.deps === null))) {
    ie(e, le);
    return;
  }
  It || (_e !== null ? (Zr() || B != null && B.is_fork) && _e.set(e, n) : $r(e));
}
function il(e) {
  var t, n;
  if (e.effects !== null)
    for (const r of e.effects)
      (r.teardown || r.ac) && ((t = r.teardown) == null || t.call(r), (n = r.ac) == null || n.abort(ht), r.teardown = us, r.ac = null, Ln(r, 0), Kr(r));
}
function Ri(e) {
  if (e.effects !== null)
    for (const t of e.effects)
      t.teardown && yn(t);
}
let Rr = /* @__PURE__ */ new Set();
const Mt = /* @__PURE__ */ new Map();
let Oi = !1;
function Kt(e, t) {
  var n = {
    f: 0,
    // TODO ideally we could skip this altogether, but it causes type errors
    v: e,
    reactions: null,
    equals: mi,
    rv: 0,
    wv: 0
  };
  return n;
}
// @__NO_SIDE_EFFECTS__
function P(e, t) {
  const n = Kt(e);
  return Yi(n), n;
}
// @__NO_SIDE_EFFECTS__
function sl(e, t = !1, n = !0) {
  const r = Kt(e);
  return t || (r.equals = bi), r;
}
function b(e, t, n = !1) {
  L !== null && // since we are untracking the function inside `$inspect.with` we need to add this check
  // to ensure we error if state is set inside an inspect effect
  (!Qe || (L.f & ti) !== 0) && wi() && (L.f & (ce | Rt | Vr | ti)) !== 0 && (Ue === null || !_n.call(Ue, e)) && Ss();
  let r = n ? He(t) : t;
  return xn(e, r, Jn);
}
function xn(e, t, n = null) {
  if (!e.equals(t)) {
    var r = e.v;
    It ? Mt.set(e, t) : Mt.set(e, r), e.v = t;
    var i = Gt.ensure();
    if (i.capture(e, r), (e.f & ce) !== 0) {
      const s = (
        /** @type {Derived} */
        e
      );
      (e.f & ke) !== 0 && qr(s), _e === null && $r(s);
    }
    e.wv = Gi(), Ni(e, ke, n), H !== null && (H.f & le) !== 0 && (H.f & (et | Yt)) === 0 && (Le === null ? _l([e]) : Le.push(e)), !i.is_fork && Rr.size > 0 && !Oi && ll();
  }
  return t;
}
function ll() {
  Oi = !1;
  for (const e of Rr)
    (e.f & le) !== 0 && ie(e, ut), qn(e) && yn(e);
  Rr.clear();
}
function Pn(e) {
  b(e, e.v + 1);
}
function Ni(e, t, n) {
  var r = e.reactions;
  if (r !== null)
    for (var i = r.length, s = 0; s < i; s++) {
      var o = r[s], f = o.f, a = (f & ke) === 0;
      if (a && ie(o, t), (f & ce) !== 0) {
        var u = (
          /** @type {Derived} */
          o
        );
        _e == null || _e.delete(u), (f & Zt) === 0 && (f & Ve && (o.f |= Zt), Ni(u, ut, n));
      } else if (a) {
        var h = (
          /** @type {Effect} */
          o
        );
        (f & Rt) !== 0 && Ge !== null && Ge.add(h), n !== null ? n.push(h) : Ur(h);
      }
    }
}
function He(e) {
  if (typeof e != "object" || e === null || Nn in e)
    return e;
  const t = Hr(e);
  if (t !== hi && t !== as)
    return e;
  var n = /* @__PURE__ */ new Map(), r = Br(e), i = /* @__PURE__ */ P(0), s = qt, o = (f) => {
    if (qt === s)
      return f();
    var a = L, u = qt;
    qe(null), si(s);
    var h = f();
    return qe(a), si(u), h;
  };
  return r && n.set("length", /* @__PURE__ */ P(
    /** @type {any[]} */
    e.length
  )), new Proxy(
    /** @type {any} */
    e,
    {
      defineProperty(f, a, u) {
        (!("value" in u) || u.configurable === !1 || u.enumerable === !1 || u.writable === !1) && ks();
        var h = n.get(a);
        return h === void 0 ? o(() => {
          var m = /* @__PURE__ */ P(u.value);
          return n.set(a, m), m;
        }) : b(h, u.value, !0), !0;
      },
      deleteProperty(f, a) {
        var u = n.get(a);
        if (u === void 0) {
          if (a in f) {
            const h = o(() => /* @__PURE__ */ P(ge));
            n.set(a, h), Pn(i);
          }
        } else
          b(u, ge), Pn(i);
        return !0;
      },
      get(f, a, u) {
        var E;
        if (a === Nn)
          return e;
        var h = n.get(a), m = a in f;
        if (h === void 0 && (!m || (E = fn(f, a)) != null && E.writable) && (h = o(() => {
          var y = He(m ? f[a] : ge), S = /* @__PURE__ */ P(y);
          return S;
        }), n.set(a, h)), h !== void 0) {
          var _ = l(h);
          return _ === ge ? void 0 : _;
        }
        return Reflect.get(f, a, u);
      },
      getOwnPropertyDescriptor(f, a) {
        var u = Reflect.getOwnPropertyDescriptor(f, a);
        if (u && "value" in u) {
          var h = n.get(a);
          h && (u.value = l(h));
        } else if (u === void 0) {
          var m = n.get(a), _ = m == null ? void 0 : m.v;
          if (m !== void 0 && _ !== ge)
            return {
              enumerable: !0,
              configurable: !0,
              value: _,
              writable: !0
            };
        }
        return u;
      },
      has(f, a) {
        var _;
        if (a === Nn)
          return !0;
        var u = n.get(a), h = u !== void 0 && u.v !== ge || Reflect.has(f, a);
        if (u !== void 0 || H !== null && (!h || (_ = fn(f, a)) != null && _.writable)) {
          u === void 0 && (u = o(() => {
            var E = h ? He(f[a]) : ge, y = /* @__PURE__ */ P(E);
            return y;
          }), n.set(a, u));
          var m = l(u);
          if (m === ge)
            return !1;
        }
        return h;
      },
      set(f, a, u, h) {
        var T;
        var m = n.get(a), _ = a in f;
        if (r && a === "length")
          for (var E = u; E < /** @type {Source<number>} */
          m.v; E += 1) {
            var y = n.get(E + "");
            y !== void 0 ? b(y, ge) : E in f && (y = o(() => /* @__PURE__ */ P(ge)), n.set(E + "", y));
          }
        if (m === void 0)
          (!_ || (T = fn(f, a)) != null && T.writable) && (m = o(() => /* @__PURE__ */ P(void 0)), b(m, He(u)), n.set(a, m));
        else {
          _ = m.v !== ge;
          var S = o(() => He(u));
          b(m, S);
        }
        var g = Reflect.getOwnPropertyDescriptor(f, a);
        if (g != null && g.set && g.set.call(h, u), !_) {
          if (r && typeof a == "string") {
            var k = (
              /** @type {Source<number>} */
              n.get("length")
            ), z = Number(a);
            Number.isInteger(z) && z >= k.v && b(k, z + 1);
          }
          Pn(i);
        }
        return !0;
      },
      ownKeys(f) {
        l(i);
        var a = Reflect.ownKeys(f).filter((m) => {
          var _ = n.get(m);
          return _ === void 0 || _.v !== ge;
        });
        for (var [u, h] of n)
          h.v !== ge && !(u in f) && a.push(u);
        return a;
      },
      setPrototypeOf() {
        As();
      }
    }
  );
}
var Ct, Pi, zi, Li;
function ol() {
  if (Ct === void 0) {
    Ct = window, Pi = /Firefox/.test(navigator.userAgent);
    var e = Element.prototype, t = Node.prototype, n = Text.prototype;
    zi = fn(t, "firstChild").get, Li = fn(t, "nextSibling").get, ei(e) && (e.__click = void 0, e.__className = void 0, e.__attributes = null, e.__style = void 0, e.__e = void 0), ei(n) && (n.__t = void 0);
  }
}
function Dt(e = "") {
  return document.createTextNode(e);
}
// @__NO_SIDE_EFFECTS__
function rr(e) {
  return (
    /** @type {TemplateNode | null} */
    zi.call(e)
  );
}
// @__NO_SIDE_EFFECTS__
function Un(e) {
  return (
    /** @type {TemplateNode | null} */
    Li.call(e)
  );
}
function C(e, t) {
  return /* @__PURE__ */ rr(e);
}
function Or(e, t = !1) {
  {
    var n = /* @__PURE__ */ rr(e);
    return n instanceof Comment && n.data === "" ? /* @__PURE__ */ Un(n) : n;
  }
}
function D(e, t = 1, n = !1) {
  let r = e;
  for (; t--; )
    r = /** @type {TemplateNode} */
    /* @__PURE__ */ Un(r);
  return r;
}
function al(e) {
  e.textContent = "";
}
function ji() {
  return !1;
}
function ul(e, t, n) {
  return (
    /** @type {T extends keyof HTMLElementTagNameMap ? HTMLElementTagNameMap[T] : Element} */
    document.createElementNS(_i, e, void 0)
  );
}
function Yr(e) {
  var t = L, n = H;
  qe(null), ft(null);
  try {
    return e();
  } finally {
    qe(t), ft(n);
  }
}
function fl(e) {
  H === null && (L === null && xs(), ws()), It && bs();
}
function cl(e, t) {
  var n = t.last;
  n === null ? t.last = t.first = e : (n.next = e, e.prev = n, t.last = e);
}
function mt(e, t) {
  var n = H;
  n !== null && (n.f & ze) !== 0 && (e |= ze);
  var r = {
    ctx: De,
    deps: null,
    nodes: null,
    f: e | ke | Ve,
    first: null,
    fn: t,
    last: null,
    next: null,
    parent: n,
    b: n && n.b,
    prev: null,
    teardown: null,
    wv: 0,
    ac: null
  }, i = r;
  if ((e & mn) !== 0)
    an !== null ? an.push(r) : Gt.ensure().schedule(r);
  else if (t !== null) {
    try {
      yn(r);
    } catch (o) {
      throw Ce(r), o;
    }
    i.deps === null && i.teardown === null && i.nodes === null && i.first === i.last && // either `null`, or a singular child
    (i.f & En) === 0 && (i = i.first, (e & Rt) !== 0 && (e & bn) !== 0 && i !== null && (i.f |= bn));
  }
  if (i !== null && (i.parent = n, n !== null && cl(i, n), L !== null && (L.f & ce) !== 0 && (e & Yt) === 0)) {
    var s = (
      /** @type {Derived} */
      L
    );
    (s.effects ?? (s.effects = [])).push(i);
  }
  return r;
}
function Zr() {
  return L !== null && !Qe;
}
function Fi(e) {
  const t = mt(ar, null);
  return ie(t, le), t.teardown = e, t;
}
function it(e) {
  fl();
  var t = (
    /** @type {Effect} */
    H.f
  ), n = !L && (t & et) !== 0 && (t & Xt) === 0;
  if (n) {
    var r = (
      /** @type {ComponentContext} */
      De
    );
    (r.e ?? (r.e = [])).push(e);
  } else
    return Bi(e);
}
function Bi(e) {
  return mt(mn | ds, e);
}
function dl(e) {
  Gt.ensure();
  const t = mt(Yt | En, e);
  return (n = {}) => new Promise((r) => {
    n.outro ? Ut(t, () => {
      Ce(t), r(void 0);
    }) : (Ce(t), r(void 0));
  });
}
function hl(e) {
  return mt(mn, e);
}
function vl(e) {
  return mt(Vr | En, e);
}
function Hi(e, t = 0) {
  return mt(ar | t, e);
}
function we(e, t = [], n = [], r = []) {
  Qs(r, t, n, (i) => {
    mt(ar, () => e(...i.map(l)));
  });
}
function Gr(e, t = 0) {
  var n = mt(Rt | t, e);
  return n;
}
function Fe(e) {
  return mt(et | En, e);
}
function Vi(e) {
  var t = e.teardown;
  if (t !== null) {
    const n = It, r = L;
    ii(!0), qe(null);
    try {
      t.call(null);
    } finally {
      ii(n), qe(r);
    }
  }
}
function Kr(e, t = !1) {
  var n = e.first;
  for (e.first = e.last = null; n !== null; ) {
    const i = n.ac;
    i !== null && Yr(() => {
      i.abort(ht);
    });
    var r = n.next;
    (n.f & Yt) !== 0 ? n.parent = null : Ce(n, t), n = r;
  }
}
function pl(e) {
  for (var t = e.first; t !== null; ) {
    var n = t.next;
    (t.f & et) === 0 && Ce(t), t = n;
  }
}
function Ce(e, t = !0) {
  var n = !1;
  (t || (e.f & cs) !== 0) && e.nodes !== null && e.nodes.end !== null && (gl(
    e.nodes.start,
    /** @type {TemplateNode} */
    e.nodes.end
  ), n = !0), ie(e, Er), Kr(e, t && !n), Ln(e, 0);
  var r = e.nodes && e.nodes.t;
  if (r !== null)
    for (const s of r)
      s.stop();
  Vi(e), e.f ^= Er, e.f |= $e;
  var i = e.parent;
  i !== null && i.first !== null && $i(e), e.next = e.prev = e.teardown = e.ctx = e.deps = e.fn = e.nodes = e.ac = null;
}
function gl(e, t) {
  for (; e !== null; ) {
    var n = e === t ? null : /* @__PURE__ */ Un(e);
    e.remove(), e = n;
  }
}
function $i(e) {
  var t = e.parent, n = e.prev, r = e.next;
  n !== null && (n.next = r), r !== null && (r.prev = n), t !== null && (t.first === e && (t.first = r), t.last === e && (t.last = n));
}
function Ut(e, t, n = !0) {
  var r = [];
  Ui(e, r, !0);
  var i = () => {
    n && Ce(e), t && t();
  }, s = r.length;
  if (s > 0) {
    var o = () => --s || i();
    for (var f of r)
      f.out(o);
  } else
    i();
}
function Ui(e, t, n) {
  if ((e.f & ze) === 0) {
    e.f ^= ze;
    var r = e.nodes && e.nodes.t;
    if (r !== null)
      for (const f of r)
        (f.is_global || n) && t.push(f);
    for (var i = e.first; i !== null; ) {
      var s = i.next, o = (i.f & bn) !== 0 || // If this is a branch effect without a block effect parent,
      // it means the parent block effect was pruned. In that case,
      // transparency information was transferred to the branch effect.
      (i.f & et) !== 0 && (e.f & Rt) !== 0;
      Ui(i, t, o ? n : !1), i = s;
    }
  }
}
function Xr(e) {
  qi(e, !0);
}
function qi(e, t) {
  if ((e.f & ze) !== 0) {
    e.f ^= ze, (e.f & le) === 0 && (ie(e, ke), Gt.ensure().schedule(e));
    for (var n = e.first; n !== null; ) {
      var r = n.next, i = (n.f & bn) !== 0 || (n.f & et) !== 0;
      qi(n, i ? t : !1), n = r;
    }
    var s = e.nodes && e.nodes.t;
    if (s !== null)
      for (const o of s)
        (o.is_global || t) && o.in();
  }
}
function Wr(e, t) {
  if (e.nodes)
    for (var n = e.nodes.start, r = e.nodes.end; n !== null; ) {
      var i = n === r ? null : /* @__PURE__ */ Un(n);
      t.append(n), n = i;
    }
}
let tr = !1, It = !1;
function ii(e) {
  It = e;
}
let L = null, Qe = !1;
function qe(e) {
  L = e;
}
let H = null;
function ft(e) {
  H = e;
}
let Ue = null;
function Yi(e) {
  L !== null && (Ue === null ? Ue = [e] : Ue.push(e));
}
let Me = null, Re = 0, Le = null;
function _l(e) {
  Le = e;
}
let Zi = 1, jt = 0, qt = jt;
function si(e) {
  qt = e;
}
function Gi() {
  return ++Zi;
}
function qn(e) {
  var t = e.f;
  if ((t & ke) !== 0)
    return !0;
  if (t & ce && (e.f &= ~Zt), (t & ut) !== 0) {
    for (var n = (
      /** @type {Value[]} */
      e.deps
    ), r = n.length, i = 0; i < r; i++) {
      var s = n[i];
      if (qn(
        /** @type {Derived} */
        s
      ) && Ii(
        /** @type {Derived} */
        s
      ), s.wv > e.wv)
        return !0;
    }
    (t & Ve) !== 0 && // During time traveling we don't want to reset the status so that
    // traversal of the graph in the other batches still happens
    _e === null && ie(e, le);
  }
  return !1;
}
function Ki(e, t, n = !0) {
  var r = e.reactions;
  if (r !== null && !(Ue !== null && _n.call(Ue, e)))
    for (var i = 0; i < r.length; i++) {
      var s = r[i];
      (s.f & ce) !== 0 ? Ki(
        /** @type {Derived} */
        s,
        t,
        !1
      ) : t === s && (n ? ie(s, ke) : (s.f & le) !== 0 && ie(s, ut), Ur(
        /** @type {Effect} */
        s
      ));
    }
}
function Xi(e) {
  var S;
  var t = Me, n = Re, r = Le, i = L, s = Ue, o = De, f = Qe, a = qt, u = e.f;
  Me = /** @type {null | Value[]} */
  null, Re = 0, Le = null, L = (u & (et | Yt)) === 0 ? e : null, Ue = null, wn(e.ctx), Qe = !1, qt = ++jt, e.ac !== null && (Yr(() => {
    e.ac.abort(ht);
  }), e.ac = null);
  try {
    e.f |= kr;
    var h = (
      /** @type {Function} */
      e.fn
    ), m = h();
    e.f |= Xt;
    var _ = e.deps, E = B == null ? void 0 : B.is_fork;
    if (Me !== null) {
      var y;
      if (E || Ln(e, Re), _ !== null && Re > 0)
        for (_.length = Re + Me.length, y = 0; y < Me.length; y++)
          _[Re + y] = Me[y];
      else
        e.deps = _ = Me;
      if (Zr() && (e.f & Ve) !== 0)
        for (y = Re; y < _.length; y++)
          ((S = _[y]).reactions ?? (S.reactions = [])).push(e);
    } else !E && _ !== null && Re < _.length && (Ln(e, Re), _.length = Re);
    if (wi() && Le !== null && !Qe && _ !== null && (e.f & (ce | ut | ke)) === 0)
      for (y = 0; y < /** @type {Source[]} */
      Le.length; y++)
        Ki(
          Le[y],
          /** @type {Effect} */
          e
        );
    if (i !== null && i !== e) {
      if (jt++, i.deps !== null)
        for (let g = 0; g < n; g += 1)
          i.deps[g].rv = jt;
      if (t !== null)
        for (const g of t)
          g.rv = jt;
      Le !== null && (r === null ? r = Le : r.push(.../** @type {Source[]} */
      Le));
    }
    return (e.f & St) !== 0 && (e.f ^= St), m;
  } catch (g) {
    return xi(g);
  } finally {
    e.f ^= kr, Me = t, Re = n, Le = r, L = i, Ue = s, wn(o), Qe = f, qt = a;
  }
}
function ml(e, t) {
  let n = t.reactions;
  if (n !== null) {
    var r = ss.call(n, e);
    if (r !== -1) {
      var i = n.length - 1;
      i === 0 ? n = t.reactions = null : (n[r] = n[i], n.pop());
    }
  }
  if (n === null && (t.f & ce) !== 0 && // Destroying a child effect while updating a parent effect can cause a dependency to appear
  // to be unused, when in fact it is used by the currently-updating parent. Checking `new_deps`
  // allows us to skip the expensive work of disconnecting and immediately reconnecting it
  (Me === null || !_n.call(Me, t))) {
    var s = (
      /** @type {Derived} */
      t
    );
    (s.f & Ve) !== 0 && (s.f ^= Ve, s.f &= ~Zt), $r(s), il(s), Ln(s, 0);
  }
}
function Ln(e, t) {
  var n = e.deps;
  if (n !== null)
    for (var r = t; r < n.length; r++)
      ml(e, n[r]);
}
function yn(e) {
  var t = e.f;
  if ((t & $e) === 0) {
    ie(e, le);
    var n = H, r = tr;
    H = e, tr = !0;
    try {
      (t & (Rt | pi)) !== 0 ? pl(e) : Kr(e), Vi(e);
      var i = Xi(e);
      e.teardown = typeof i == "function" ? i : null, e.wv = Zi;
      var s;
    } finally {
      tr = r, H = n;
    }
  }
}
function l(e) {
  var t = e.f, n = (t & ce) !== 0;
  if (L !== null && !Qe) {
    var r = H !== null && (H.f & $e) !== 0;
    if (!r && (Ue === null || !_n.call(Ue, e))) {
      var i = L.deps;
      if ((L.f & kr) !== 0)
        e.rv < jt && (e.rv = jt, Me === null && i !== null && i[Re] === e ? Re++ : Me === null ? Me = [e] : Me.push(e));
      else {
        (L.deps ?? (L.deps = [])).push(e);
        var s = e.reactions;
        s === null ? e.reactions = [L] : _n.call(s, L) || s.push(L);
      }
    }
  }
  if (It && Mt.has(e))
    return Mt.get(e);
  if (n) {
    var o = (
      /** @type {Derived} */
      e
    );
    if (It) {
      var f = o.v;
      return ((o.f & le) === 0 && o.reactions !== null || Ji(o)) && (f = qr(o)), Mt.set(o, f), f;
    }
    var a = (o.f & Ve) === 0 && !Qe && L !== null && (tr || (L.f & Ve) !== 0), u = (o.f & Xt) === 0;
    qn(o) && (a && (o.f |= Ve), Ii(o)), a && !u && (Ri(o), Wi(o));
  }
  if (_e != null && _e.has(e))
    return _e.get(e);
  if ((e.f & St) !== 0)
    throw e.v;
  return e.v;
}
function Wi(e) {
  if (e.f |= Ve, e.deps !== null)
    for (const t of e.deps)
      (t.reactions ?? (t.reactions = [])).push(e), (t.f & ce) !== 0 && (t.f & Ve) === 0 && (Ri(
        /** @type {Derived} */
        t
      ), Wi(
        /** @type {Derived} */
        t
      ));
}
function Ji(e) {
  if (e.v === ge) return !0;
  if (e.deps === null) return !1;
  for (const t of e.deps)
    if (Mt.has(t) || (t.f & ce) !== 0 && Ji(
      /** @type {Derived} */
      t
    ))
      return !0;
  return !1;
}
function dr(e) {
  var t = Qe;
  try {
    return Qe = !0, e();
  } finally {
    Qe = t;
  }
}
const bl = ["touchstart", "touchmove"];
function wl(e) {
  return bl.includes(e);
}
const Ft = Symbol("events"), Qi = /* @__PURE__ */ new Set(), Nr = /* @__PURE__ */ new Set();
function xl(e, t, n, r = {}) {
  function i(s) {
    if (r.capture || Pr.call(t, s), !s.cancelBubble)
      return Yr(() => n == null ? void 0 : n.call(this, s));
  }
  return e.startsWith("pointer") || e.startsWith("touch") || e === "wheel" ? Tt(() => {
    t.addEventListener(e, i, r);
  }) : t.addEventListener(e, i, r), i;
}
function Je(e, t, n, r, i) {
  var s = { capture: r, passive: i }, o = xl(e, t, n, s);
  (t === document.body || // @ts-ignore
  t === window || // @ts-ignore
  t === document || // Firefox has quirky behavior, it can happen that we still get "canplay" events when the element is already removed
  t instanceof HTMLMediaElement) && Fi(() => {
    t.removeEventListener(e, o, s);
  });
}
function X(e, t, n) {
  (t[Ft] ?? (t[Ft] = {}))[e] = n;
}
function Jr(e) {
  for (var t = 0; t < e.length; t++)
    Qi.add(e[t]);
  for (var n of Nr)
    n(e);
}
let li = null;
function Pr(e) {
  var g, k;
  var t = this, n = (
    /** @type {Node} */
    t.ownerDocument
  ), r = e.type, i = ((g = e.composedPath) == null ? void 0 : g.call(e)) || [], s = (
    /** @type {null | Element} */
    i[0] || e.target
  );
  li = e;
  var o = 0, f = li === e && e[Ft];
  if (f) {
    var a = i.indexOf(f);
    if (a !== -1 && (t === document || t === /** @type {any} */
    window)) {
      e[Ft] = t;
      return;
    }
    var u = i.indexOf(t);
    if (u === -1)
      return;
    a <= u && (o = a);
  }
  if (s = /** @type {Element} */
  i[o] || e.target, s !== t) {
    ls(e, "currentTarget", {
      configurable: !0,
      get() {
        return s || n;
      }
    });
    var h = L, m = H;
    qe(null), ft(null);
    try {
      for (var _, E = []; s !== null; ) {
        var y = s.assignedSlot || s.parentNode || /** @type {any} */
        s.host || null;
        try {
          var S = (k = s[Ft]) == null ? void 0 : k[r];
          S != null && (!/** @type {any} */
          s.disabled || // DOM could've been updated already by the time this is reached, so we check this as well
          // -> the target could not have been disabled because it emits the event in the first place
          e.target === s) && S.call(s, e);
        } catch (z) {
          _ ? E.push(z) : _ = z;
        }
        if (e.cancelBubble || y === t || y === null)
          break;
        s = y;
      }
      if (_) {
        for (let z of E)
          queueMicrotask(() => {
            throw z;
          });
        throw _;
      }
    } finally {
      e[Ft] = t, delete e.currentTarget, qe(h), ft(m);
    }
  }
}
var ci;
const xr = (
  // We gotta write it like this because after downleveling the pure comment may end up in the wrong location
  ((ci = globalThis == null ? void 0 : globalThis.window) == null ? void 0 : ci.trustedTypes) && /* @__PURE__ */ globalThis.window.trustedTypes.createPolicy("svelte-trusted-html", {
    /** @param {string} html */
    createHTML: (e) => e
  })
);
function yl(e) {
  return (
    /** @type {string} */
    (xr == null ? void 0 : xr.createHTML(e)) ?? e
  );
}
function El(e) {
  var t = ul("template");
  return t.innerHTML = yl(e.replaceAll("<!>", "<!---->")), t.content;
}
function zr(e, t) {
  var n = (
    /** @type {Effect} */
    H
  );
  n.nodes === null && (n.nodes = { start: e, end: t, a: null, t: null });
}
// @__NO_SIDE_EFFECTS__
function Q(e, t) {
  var n = (t & zs) !== 0, r = (t & Ls) !== 0, i, s = !e.startsWith("<!>");
  return () => {
    i === void 0 && (i = El(s ? e : "<!>" + e), n || (i = /** @type {TemplateNode} */
    /* @__PURE__ */ rr(i)));
    var o = (
      /** @type {TemplateNode} */
      r || Pi ? document.importNode(i, !0) : i.cloneNode(!0)
    );
    if (n) {
      var f = (
        /** @type {TemplateNode} */
        /* @__PURE__ */ rr(o)
      ), a = (
        /** @type {TemplateNode} */
        o.lastChild
      );
      zr(f, a);
    } else
      zr(o, o);
    return o;
  };
}
function kl() {
  var e = document.createDocumentFragment(), t = document.createComment(""), n = Dt();
  return e.append(t, n), zr(t, n), e;
}
function W(e, t) {
  e !== null && e.before(
    /** @type {Node} */
    t
  );
}
function Ke(e, t) {
  var n = t == null ? "" : typeof t == "object" ? `${t}` : t;
  n !== (e.__t ?? (e.__t = e.nodeValue)) && (e.__t = n, e.nodeValue = `${n}`);
}
function Al(e, t) {
  return Sl(e, t);
}
const Xn = /* @__PURE__ */ new Map();
function Sl(e, { target: t, anchor: n, props: r = {}, events: i, context: s, intro: o = !0, transformError: f }) {
  ol();
  var a = void 0, u = dl(() => {
    var h = n ?? t.appendChild(Dt());
    Gs(
      /** @type {TemplateNode} */
      h,
      {
        pending: () => {
        }
      },
      (E) => {
        ur({});
        var y = (
          /** @type {ComponentContext} */
          De
        );
        s && (y.c = s), i && (r.$$events = i), a = e(E, r) || {}, fr();
      },
      f
    );
    var m = /* @__PURE__ */ new Set(), _ = (E) => {
      for (var y = 0; y < E.length; y++) {
        var S = E[y];
        if (!m.has(S)) {
          m.add(S);
          var g = wl(S);
          for (const T of [t, document]) {
            var k = Xn.get(T);
            k === void 0 && (k = /* @__PURE__ */ new Map(), Xn.set(T, k));
            var z = k.get(S);
            z === void 0 ? (T.addEventListener(S, Pr, { passive: g }), k.set(S, 1)) : k.set(S, z + 1);
          }
        }
      }
    };
    return _(or(Qi)), Nr.add(_), () => {
      var g;
      for (var E of m)
        for (const k of [t, document]) {
          var y = (
            /** @type {Map<string, number>} */
            Xn.get(k)
          ), S = (
            /** @type {number} */
            y.get(E)
          );
          --S == 0 ? (k.removeEventListener(E, Pr), y.delete(E), y.size === 0 && Xn.delete(k)) : y.set(E, S);
        }
      Nr.delete(_), h !== n && ((g = h.parentNode) == null || g.removeChild(h));
    };
  });
  return Lr.set(a, u), a;
}
let Lr = /* @__PURE__ */ new WeakMap();
function Tl(e, t) {
  const n = Lr.get(e);
  return n ? (Lr.delete(e), n(t)) : Promise.resolve();
}
var We, ot, Pe, $t, Vn, $n, lr;
class Ml {
  /**
   * @param {TemplateNode} anchor
   * @param {boolean} transition
   */
  constructor(t, n = !0) {
    /** @type {TemplateNode} */
    Ze(this, "anchor");
    /** @type {Map<Batch, Key>} */
    j(this, We, /* @__PURE__ */ new Map());
    /**
     * Map of keys to effects that are currently rendered in the DOM.
     * These effects are visible and actively part of the document tree.
     * Example:
     * ```
     * {#if condition}
     * 	foo
     * {:else}
     * 	bar
     * {/if}
     * ```
     * Can result in the entries `true->Effect` and `false->Effect`
     * @type {Map<Key, Effect>}
     */
    j(this, ot, /* @__PURE__ */ new Map());
    /**
     * Similar to #onscreen with respect to the keys, but contains branches that are not yet
     * in the DOM, because their insertion is deferred.
     * @type {Map<Key, Branch>}
     */
    j(this, Pe, /* @__PURE__ */ new Map());
    /**
     * Keys of effects that are currently outroing
     * @type {Set<Key>}
     */
    j(this, $t, /* @__PURE__ */ new Set());
    /**
     * Whether to pause (i.e. outro) on change, or destroy immediately.
     * This is necessary for `<svelte:element>`
     */
    j(this, Vn, !0);
    /**
     * @param {Batch} batch
     */
    j(this, $n, (t) => {
      if (c(this, We).has(t)) {
        var n = (
          /** @type {Key} */
          c(this, We).get(t)
        ), r = c(this, ot).get(n);
        if (r)
          Xr(r), c(this, $t).delete(n);
        else {
          var i = c(this, Pe).get(n);
          i && (c(this, ot).set(n, i.effect), c(this, Pe).delete(n), i.fragment.lastChild.remove(), this.anchor.before(i.fragment), r = i.effect);
        }
        for (const [s, o] of c(this, We)) {
          if (c(this, We).delete(s), s === t)
            break;
          const f = c(this, Pe).get(o);
          f && (Ce(f.effect), c(this, Pe).delete(o));
        }
        for (const [s, o] of c(this, ot)) {
          if (s === n || c(this, $t).has(s)) continue;
          const f = () => {
            if (Array.from(c(this, We).values()).includes(s)) {
              var u = document.createDocumentFragment();
              Wr(o, u), u.append(Dt()), c(this, Pe).set(s, { effect: o, fragment: u });
            } else
              Ce(o);
            c(this, $t).delete(s), c(this, ot).delete(s);
          };
          c(this, Vn) || !r ? (c(this, $t).add(s), Ut(o, f, !1)) : f();
        }
      }
    });
    /**
     * @param {Batch} batch
     */
    j(this, lr, (t) => {
      c(this, We).delete(t);
      const n = Array.from(c(this, We).values());
      for (const [r, i] of c(this, Pe))
        n.includes(r) || (Ce(i.effect), c(this, Pe).delete(r));
    });
    this.anchor = t, O(this, Vn, n);
  }
  /**
   *
   * @param {any} key
   * @param {null | ((target: TemplateNode) => void)} fn
   */
  ensure(t, n) {
    var r = (
      /** @type {Batch} */
      B
    ), i = ji();
    if (n && !c(this, ot).has(t) && !c(this, Pe).has(t))
      if (i) {
        var s = document.createDocumentFragment(), o = Dt();
        s.append(o), c(this, Pe).set(t, {
          effect: Fe(() => n(o)),
          fragment: s
        });
      } else
        c(this, ot).set(
          t,
          Fe(() => n(this.anchor))
        );
    if (c(this, We).set(r, t), i) {
      for (const [f, a] of c(this, ot))
        f === t ? r.unskip_effect(a) : r.skip_effect(a);
      for (const [f, a] of c(this, Pe))
        f === t ? r.unskip_effect(a.effect) : r.skip_effect(a.effect);
      r.oncommit(c(this, $n)), r.ondiscard(c(this, lr));
    } else
      c(this, $n).call(this, r);
  }
}
We = new WeakMap(), ot = new WeakMap(), Pe = new WeakMap(), $t = new WeakMap(), Vn = new WeakMap(), $n = new WeakMap(), lr = new WeakMap();
function be(e, t, n = !1) {
  var r = new Ml(e), i = n ? bn : 0;
  function s(o, f) {
    r.ensure(o, f);
  }
  Gr(() => {
    var o = !1;
    t((f, a = 0) => {
      o = !0, s(a, f);
    }), o || s(-1, null);
  }, i);
}
function Cl(e, t, n) {
  for (var r = [], i = t.length, s, o = t.length, f = 0; f < i; f++) {
    let m = t[f];
    Ut(
      m,
      () => {
        if (s) {
          if (s.pending.delete(m), s.done.add(m), s.pending.size === 0) {
            var _ = (
              /** @type {Set<EachOutroGroup>} */
              e.outrogroups
            );
            jr(e, or(s.done)), _.delete(s), _.size === 0 && (e.outrogroups = null);
          }
        } else
          o -= 1;
      },
      !1
    );
  }
  if (o === 0) {
    var a = r.length === 0 && n !== null;
    if (a) {
      var u = (
        /** @type {Element} */
        n
      ), h = (
        /** @type {Element} */
        u.parentNode
      );
      al(h), h.append(u), e.items.clear();
    }
    jr(e, t, !a);
  } else
    s = {
      pending: new Set(t),
      done: /* @__PURE__ */ new Set()
    }, (e.outrogroups ?? (e.outrogroups = /* @__PURE__ */ new Set())).add(s);
}
function jr(e, t, n = !0) {
  var r;
  if (e.pending.size > 0) {
    r = /* @__PURE__ */ new Set();
    for (const o of e.pending.values())
      for (const f of o)
        r.add(
          /** @type {EachItem} */
          e.items.get(f).e
        );
  }
  for (var i = 0; i < t.length; i++) {
    var s = t[i];
    if (r != null && r.has(s)) {
      s.f |= at;
      const o = document.createDocumentFragment();
      Wr(s, o);
    } else
      Ce(t[i], n);
  }
}
var oi;
function jn(e, t, n, r, i, s = null) {
  var o = e, f = /* @__PURE__ */ new Map(), a = (t & gi) !== 0;
  if (a) {
    var u = (
      /** @type {Element} */
      e
    );
    o = u.appendChild(Dt());
  }
  var h = null, m = /* @__PURE__ */ Di(() => {
    var T = n();
    return Br(T) ? T : T == null ? [] : or(T);
  }), _, E = /* @__PURE__ */ new Map(), y = !0;
  function S(T) {
    (z.effect.f & $e) === 0 && (z.pending.delete(T), z.fallback = h, Dl(z, _, o, t, r), h !== null && (_.length === 0 ? (h.f & at) === 0 ? Xr(h) : (h.f ^= at, On(h, null, o)) : Ut(h, () => {
      h = null;
    })));
  }
  function g(T) {
    z.pending.delete(T);
  }
  var k = Gr(() => {
    _ = /** @type {V[]} */
    l(m);
    for (var T = _.length, N = /* @__PURE__ */ new Set(), q = (
      /** @type {Batch} */
      B
    ), de = ji(), F = 0; F < T; F += 1) {
      var V = _[F], Z = r(V, F), Y = y ? null : f.get(Z);
      Y ? (Y.v && xn(Y.v, V), Y.i && xn(Y.i, F), de && q.unskip_effect(Y.e)) : (Y = Il(
        f,
        y ? o : oi ?? (oi = Dt()),
        V,
        Z,
        F,
        i,
        t,
        n
      ), y || (Y.e.f |= at), f.set(Z, Y)), N.add(Z);
    }
    if (T === 0 && s && !h && (y ? h = Fe(() => s(o)) : (h = Fe(() => s(oi ?? (oi = Dt()))), h.f |= at)), T > N.size && ms(), !y)
      if (E.set(q, N), de) {
        for (const [he, se] of f)
          N.has(he) || q.skip_effect(se.e);
        q.oncommit(S), q.ondiscard(g);
      } else
        S(q);
    l(m);
  }), z = { effect: k, items: f, pending: E, outrogroups: null, fallback: h };
  y = !1;
}
function Rn(e) {
  for (; e !== null && (e.f & et) === 0; )
    e = e.next;
  return e;
}
function Dl(e, t, n, r, i) {
  var Y, he, se, me, $, J, ae, ue, ee;
  var s = (r & Ds) !== 0, o = t.length, f = e.items, a = Rn(e.effect.first), u, h = null, m, _ = [], E = [], y, S, g, k;
  if (s)
    for (k = 0; k < o; k += 1)
      y = t[k], S = i(y, k), g = /** @type {EachItem} */
      f.get(S).e, (g.f & at) === 0 && ((he = (Y = g.nodes) == null ? void 0 : Y.a) == null || he.measure(), (m ?? (m = /* @__PURE__ */ new Set())).add(g));
  for (k = 0; k < o; k += 1) {
    if (y = t[k], S = i(y, k), g = /** @type {EachItem} */
    f.get(S).e, e.outrogroups !== null)
      for (const G of e.outrogroups)
        G.pending.delete(g), G.done.delete(g);
    if ((g.f & ze) !== 0 && (Xr(g), s && ((me = (se = g.nodes) == null ? void 0 : se.a) == null || me.unfix(), (m ?? (m = /* @__PURE__ */ new Set())).delete(g))), (g.f & at) !== 0)
      if (g.f ^= at, g === a)
        On(g, null, n);
      else {
        var z = h ? h.next : a;
        g === e.effect.last && (e.effect.last = g.prev), g.prev && (g.prev.next = g.next), g.next && (g.next.prev = g.prev), yt(e, h, g), yt(e, g, z), On(g, z, n), h = g, _ = [], E = [], a = Rn(h.next);
        continue;
      }
    if (g !== a) {
      if (u !== void 0 && u.has(g)) {
        if (_.length < E.length) {
          var T = E[0], N;
          h = T.prev;
          var q = _[0], de = _[_.length - 1];
          for (N = 0; N < _.length; N += 1)
            On(_[N], T, n);
          for (N = 0; N < E.length; N += 1)
            u.delete(E[N]);
          yt(e, q.prev, de.next), yt(e, h, q), yt(e, de, T), a = T, h = de, k -= 1, _ = [], E = [];
        } else
          u.delete(g), On(g, a, n), yt(e, g.prev, g.next), yt(e, g, h === null ? e.effect.first : h.next), yt(e, h, g), h = g;
        continue;
      }
      for (_ = [], E = []; a !== null && a !== g; )
        (u ?? (u = /* @__PURE__ */ new Set())).add(a), E.push(a), a = Rn(a.next);
      if (a === null)
        continue;
    }
    (g.f & at) === 0 && _.push(g), h = g, a = Rn(g.next);
  }
  if (e.outrogroups !== null) {
    for (const G of e.outrogroups)
      G.pending.size === 0 && (jr(e, or(G.done)), ($ = e.outrogroups) == null || $.delete(G));
    e.outrogroups.size === 0 && (e.outrogroups = null);
  }
  if (a !== null || u !== void 0) {
    var F = [];
    if (u !== void 0)
      for (g of u)
        (g.f & ze) === 0 && F.push(g);
    for (; a !== null; )
      (a.f & ze) === 0 && a !== e.fallback && F.push(a), a = Rn(a.next);
    var V = F.length;
    if (V > 0) {
      var Z = (r & gi) !== 0 && o === 0 ? n : null;
      if (s) {
        for (k = 0; k < V; k += 1)
          (ae = (J = F[k].nodes) == null ? void 0 : J.a) == null || ae.measure();
        for (k = 0; k < V; k += 1)
          (ee = (ue = F[k].nodes) == null ? void 0 : ue.a) == null || ee.fix();
      }
      Cl(e, F, Z);
    }
  }
  s && Tt(() => {
    var G, Ye;
    if (m !== void 0)
      for (g of m)
        (Ye = (G = g.nodes) == null ? void 0 : G.a) == null || Ye.apply();
  });
}
function Il(e, t, n, r, i, s, o, f) {
  var a = (o & Ms) !== 0 ? (o & Is) === 0 ? /* @__PURE__ */ sl(n, !1, !1) : Kt(n) : null, u = (o & Cs) !== 0 ? Kt(i) : null;
  return {
    v: a,
    i: u,
    e: Fe(() => (s(t, a ?? n, u ?? i, f), () => {
      e.delete(r);
    }))
  };
}
function On(e, t, n) {
  if (e.nodes)
    for (var r = e.nodes.start, i = e.nodes.end, s = t && (t.f & at) === 0 ? (
      /** @type {EffectNodes} */
      t.nodes.start
    ) : n; r !== null; ) {
      var o = (
        /** @type {TemplateNode} */
        /* @__PURE__ */ Un(r)
      );
      if (s.before(r), r === i)
        return;
      r = o;
    }
}
function yt(e, t, n) {
  t === null ? e.effect.first = n : t.next = n, n === null ? e.effect.last = t : n.prev = t;
}
function es(e) {
  var t, n, r = "";
  if (typeof e == "string" || typeof e == "number") r += e;
  else if (typeof e == "object") if (Array.isArray(e)) {
    var i = e.length;
    for (t = 0; t < i; t++) e[t] && (n = es(e[t])) && (r && (r += " "), r += n);
  } else for (n in e) e[n] && (r && (r += " "), r += n);
  return r;
}
function Rl() {
  for (var e, t, n = 0, r = "", i = arguments.length; n < i; n++) (e = arguments[n]) && (t = es(e)) && (r && (r += " "), r += t);
  return r;
}
function ln(e) {
  return typeof e == "object" ? Rl(e) : e ?? "";
}
function Ol(e, t, n) {
  var r = e == null ? "" : "" + e;
  return r === "" ? null : r;
}
function Nl(e, t) {
  return e == null ? null : String(e);
}
function Ee(e, t, n, r, i, s) {
  var o = e.__className;
  if (o !== n || o === void 0) {
    var f = Ol(n);
    f == null ? e.removeAttribute("class") : e.className = f, e.__className = n;
  }
  return s;
}
function rt(e, t, n, r) {
  var i = e.__style;
  if (i !== t) {
    var s = Nl(t);
    s == null ? e.removeAttribute("style") : e.style.cssText = s, e.__style = t;
  }
  return r;
}
const Pl = Symbol("is custom element"), zl = Symbol("is html"), Ll = ps ? "progress" : "PROGRESS";
function jl(e, t) {
  var n = ts(e);
  n.value === (n.value = // treat null and undefined the same for the initial value
  t ?? void 0) || // @ts-expect-error
  // `progress` elements always need their value set when it's `0`
  e.value === t && (t !== 0 || e.nodeName !== Ll) || (e.value = t ?? "");
}
function Et(e, t, n, r) {
  var i = ts(e);
  i[t] !== (i[t] = n) && (t === "loading" && (e[vs] = n), n == null ? e.removeAttribute(t) : typeof n != "string" && Fl(e).includes(t) ? e[t] = n : e.setAttribute(t, n));
}
function ts(e) {
  return (
    /** @type {Record<string | symbol, unknown>} **/
    // @ts-expect-error
    e.__attributes ?? (e.__attributes = {
      [Pl]: e.nodeName.includes("-"),
      [zl]: e.namespaceURI === _i
    })
  );
}
var ai = /* @__PURE__ */ new Map();
function Fl(e) {
  var t = e.getAttribute("is") || e.nodeName, n = ai.get(t);
  if (n) return n;
  ai.set(t, n = []);
  for (var r, i = e, s = Element.prototype; s !== i; ) {
    r = os(i);
    for (var o in r)
      r[o].set && n.push(o);
    i = Hr(i);
  }
  return n;
}
function ui(e, t) {
  return e === t || (e == null ? void 0 : e[Nn]) === t;
}
function zn(e = {}, t, n, r) {
  var i = (
    /** @type {ComponentContext} */
    De.r
  ), s = (
    /** @type {Effect} */
    H
  );
  return hl(() => {
    var o, f;
    return Hi(() => {
      o = f, f = [], dr(() => {
        e !== n(...f) && (t(e, ...f), o && ui(n(...o), e) && t(null, ...o));
      });
    }), () => {
      let a = s;
      for (; a !== i && a.parent !== null && a.parent.f & Er; )
        a = a.parent;
      const u = () => {
        f && ui(n(...f), e) && t(null, ...f);
      }, h = a.teardown;
      a.teardown = () => {
        u(), h == null || h();
      };
    };
  }), e;
}
function U(e, t, n, r) {
  var z;
  var i = (n & Ns) !== 0, s = (n & Ps) !== 0, o = (
    /** @type {V} */
    r
  ), f = !0, a = () => (f && (f = !1, o = s ? dr(
    /** @type {() => V} */
    r
  ) : (
    /** @type {V} */
    r
  )), o);
  let u;
  if (i) {
    var h = Nn in e || hs in e;
    u = ((z = fn(e, t)) == null ? void 0 : z.set) ?? (h && t in e ? (T) => e[t] = T : void 0);
  }
  var m, _ = !1;
  i ? [m, _] = $s(() => (
    /** @type {V} */
    e[t]
  )) : m = /** @type {V} */
  e[t], m === void 0 && r !== void 0 && (m = a(), u && (Es(), u(m)));
  var E;
  if (E = () => {
    var T = (
      /** @type {V} */
      e[t]
    );
    return T === void 0 ? a() : (f = !0, T);
  }, (n & Os) === 0)
    return E;
  if (u) {
    var y = e.$$legacy;
    return (
      /** @type {() => V} */
      (function(T, N) {
        return arguments.length > 0 ? ((!N || y || _) && u(N ? E() : T), T) : E();
      })
    );
  }
  var S = !1, g = ((n & Rs) !== 0 ? cr : Di)(() => (S = !1, E()));
  i && l(g);
  var k = (
    /** @type {Effect} */
    H
  );
  return (
    /** @type {() => V} */
    (function(T, N) {
      if (arguments.length > 0) {
        const q = N ? l(g) : i ? He(T) : T;
        return b(g, q), S = !0, o !== void 0 && (o = q), T;
      }
      return It && S || (k.f & $e) !== 0 ? g.v : l(g);
    })
  );
}
function Bl(e) {
  De === null && gs(), it(() => {
    const t = dr(e);
    if (typeof t == "function") return (
      /** @type {() => void} */
      t
    );
  });
}
const Hl = "5";
var di;
typeof window < "u" && ((di = window.__svelte ?? (window.__svelte = {})).v ?? (di.v = /* @__PURE__ */ new Set())).add(Hl);
const un = [
  "#ef4444",
  "#f97316",
  "#f59e0b",
  "#eab308",
  "#84cc16",
  "#22c55e",
  "#10b981",
  "#14b8a6",
  "#06b6d4",
  "#0ea5e9",
  "#3b82f6",
  "#6366f1",
  "#8b5cf6",
  "#a855f7",
  "#d946ef",
  "#ec4899",
  "#f43f5e",
  "#64748b",
  "#78716c",
  "#78350f",
  "#c2410c",
  "#0e7490",
  "#7e22ce"
];
var Vl = /* @__PURE__ */ Q('<div class="absolute inset-y-0 pointer-events-none z-30"></div> <div class="absolute inset-x-0 pointer-events-none z-30"></div> <div class="absolute pointer-events-none z-30 rounded-full"></div>', 1), $l = /* @__PURE__ */ Q('<div class="resize-handle absolute bg-white border-2 border-blue-600 shadow-sm z-50"></div>'), Ul = /* @__PURE__ */ Q('<div class="annotation-label absolute bottom-full left-0 mb-1 whitespace-nowrap px-1.5 py-0.5 rounded text-[10px] font-bold text-white shadow-lg pointer-events-none"> </div> <!>', 1), ql = /* @__PURE__ */ Q("<div><!></div>"), Yl = /* @__PURE__ */ Q('<div class="absolute border-2 border-dashed border-white bg-blue-500/20 shadow-2xl pointer-events-none z-40"></div>'), Zl = /* @__PURE__ */ Q('<div class="absolute inset-x-0 top-4 z-[60] flex justify-center pointer-events-none"><div class="inline-flex items-center gap-2 rounded-full bg-slate-950/85 px-3 py-2 text-xs font-semibold text-white shadow-2xl ring-1 ring-white/10"><span class="h-2.5 w-2.5 rounded-full bg-emerald-400 animate-pulse"></span> <span>Detecting object...</span></div></div>'), Gl = /* @__PURE__ */ Q('<div class="relative w-full h-full flex flex-col items-center justify-center bg-slate-200 rounded-xl overflow-hidden shadow-inner select-none"><div class="canvas-stage relative origin-top-left"><img alt="" decoding="sync" loading="eager" fetchpriority="high" class="max-h-full max-w-full w-auto block pointer-events-none select-none shadow-2xl" draggable="false"/> <!> <!> <!></div> <!></div>');
function Kl(e, t) {
  ur(t, !0);
  let n = U(t, "annotations", 15), r = U(t, "imageDimensions", 15), i = U(t, "scale", 15, 1), s = U(t, "offset", 31, () => He({ x: 0, y: 0 })), o = U(t, "defaultAnnotationTitle", 3, null), f = U(t, "aiAssistEnabled", 3, !1), a = U(t, "aiAssistPending", 3, !1), u = U(t, "requestAutoDetect", 3, null), h = /* @__PURE__ */ P(!1), m = /* @__PURE__ */ P(!1), _ = /* @__PURE__ */ P(!1), E = /* @__PURE__ */ P(!1), y = /* @__PURE__ */ P(null), S = /* @__PURE__ */ P(null), g = /* @__PURE__ */ P(He({ x: 0, y: 0 })), k = /* @__PURE__ */ P(!1), z = /* @__PURE__ */ P(He({ x: 50, y: 50 })), T = /* @__PURE__ */ P(!1), N;
  function q(v) {
    !v || !v.naturalWidth || !v.naturalHeight || r({ width: v.naturalWidth, height: v.naturalHeight });
  }
  function de(v) {
    q(v.target);
  }
  let F = /* @__PURE__ */ P(null), V = /* @__PURE__ */ P(
    null
    // For drawing preview
  ), Z = /* @__PURE__ */ P(null), Y = /* @__PURE__ */ P(null), he = /* @__PURE__ */ P(null), se, me;
  it(() => {
    N && N.complete && N.naturalWidth > 0 && N.naturalHeight > 0 && q(N);
  });
  const $ = 1, J = 12, ae = 28, ue = 8, ee = 4, G = (v, d, x) => Math.min(Math.max(v, d), x), Ye = [
    {
      name: "nw",
      style: `left: -${ee + J}px; top: -${ee + J}px; width: ${J}px; height: ${J}px; border-radius: 9999px; cursor: nwse-resize !important; pointer-events: auto;`
    },
    {
      name: "n",
      style: `left: calc(50% - ${ae / 2}px); top: -${ee + ue}px; width: ${ae}px; height: ${ue}px; border-radius: 9999px; cursor: ns-resize !important; pointer-events: auto;`
    },
    {
      name: "ne",
      style: `right: -${ee + J}px; top: -${ee + J}px; width: ${J}px; height: ${J}px; border-radius: 9999px; cursor: nesw-resize !important; pointer-events: auto;`
    },
    {
      name: "e",
      style: `right: -${ee + ue}px; top: calc(50% - ${ae / 2}px); width: ${ue}px; height: ${ae}px; border-radius: 9999px; cursor: ew-resize !important; pointer-events: auto;`
    },
    {
      name: "se",
      style: `right: -${ee + J}px; bottom: -${ee + J}px; width: ${J}px; height: ${J}px; border-radius: 9999px; cursor: nwse-resize !important; pointer-events: auto;`
    },
    {
      name: "s",
      style: `left: calc(50% - ${ae / 2}px); bottom: -${ee + ue}px; width: ${ae}px; height: ${ue}px; border-radius: 9999px; cursor: ns-resize !important; pointer-events: auto;`
    },
    {
      name: "sw",
      style: `left: -${ee + J}px; bottom: -${ee + J}px; width: ${J}px; height: ${J}px; border-radius: 9999px; cursor: nesw-resize !important; pointer-events: auto;`
    },
    {
      name: "w",
      style: `left: -${ee + ue}px; top: calc(50% - ${ae / 2}px); width: ${ue}px; height: ${ae}px; border-radius: 9999px; cursor: ew-resize !important; pointer-events: auto;`
    }
  ];
  function kn() {
    const v = typeof o() == "string" ? o().trim() : "";
    if (v)
      return v;
    const d = [...n()].reverse().find((x) => x.title && x.title.trim().length > 0);
    return (d == null ? void 0 : d.title) ?? `Area ${n().length + 1}`;
  }
  function tt(v) {
    b(T, !!v.altKey, !0), v.altKey && b(k, !1);
  }
  function An() {
    b(T, !1);
  }
  function Ae(v) {
    if (!me) return { x: 0, y: 0 };
    const d = me.getBoundingClientRect(), x = v.clientX - d.left, A = v.clientY - d.top;
    return {
      x: G(x / d.width * 100, 0, 100),
      y: G(A / d.height * 100, 0, 100)
    };
  }
  function Wt(v, d, x) {
    const A = v.x, M = v.y, R = v.x + v.width, K = v.y + v.height;
    let ve = A, fe = M, ne = R, pe = K;
    return x.includes("w") && (ve = G(d.x, 0, R - $)), x.includes("e") && (ne = G(d.x, A + $, 100)), x.includes("n") && (fe = G(d.y, 0, K - $)), x.includes("s") && (pe = G(d.y, M + $, 100)), {
      x: ve,
      y: fe,
      width: ne - ve,
      height: pe - fe
    };
  }
  function Sn(v, d) {
    const x = v.x + v.width, A = v.y + v.height;
    return {
      x: d.includes("w") ? v.x : d.includes("e") ? x : null,
      y: d.includes("n") ? v.y : d.includes("s") ? A : null
    };
  }
  function Tn(v) {
    if (!me || a()) {
      b(k, !1);
      return;
    }
    const d = v.target, x = !!d.closest(".resize-handle"), A = !!d.closest(".canvas-stage");
    b(k, A && !x && !v.altKey, !0), l(k) && b(z, Ae(v), !0);
  }
  function Mn() {
    b(k, !1);
  }
  function Ot(v) {
    if (tt(v), v.button === 0 && v.altKey) {
      v.preventDefault(), b(h, !0), b(Z, { x: v.clientX, y: v.clientY }, !0);
      return;
    }
    if (v.button !== 0) return;
    const d = v.target, x = d.closest(".annotation-marker");
    if (x) {
      const M = x.dataset.id, R = n().find((K) => K.id === M);
      if (R) {
        v.preventDefault();
        const K = d.closest(".resize-handle");
        if (K) {
          const fe = K.dataset.handle || "se", ne = Ae(v), pe = {
            x: R.x,
            y: R.y,
            width: R.width,
            height: R.height
          }, Se = Sn(pe, fe);
          b(E, !0), b(Y, M, !0), b(y, fe, !0), b(S, pe, !0), b(
            g,
            {
              x: Se.x === null ? 0 : ne.x - Se.x,
              y: Se.y === null ? 0 : ne.y - Se.y
            },
            !0
          ), n(n().map((Ie) => ({ ...Ie, isActive: Ie.id === M })));
          return;
        }
        b(_, !0), b(Y, M, !0);
        const ve = Ae(v);
        b(he, { x: ve.x - R.x, y: ve.y - R.y }, !0), n(n().map((fe) => ({ ...fe, isActive: fe.id === M })));
        return;
      }
    }
    if ((f() || v.shiftKey) && !a() && typeof u() == "function") {
      v.preventDefault();
      const M = Ae(v);
      u()({ xPct: M.x, yPct: M.y });
      return;
    }
    v.preventDefault(), b(F, Ae(v), !0), b(V, l(F), !0), b(m, !0), n(n().map((M) => ({ ...M, isActive: !1 })));
  }
  function bt(v) {
    if (tt(v), Tn(v), (l(h) || l(_) || l(E) || l(m)) && v.preventDefault(), l(h) && l(Z)) {
      const d = v.clientX - l(Z).x, x = v.clientY - l(Z).y;
      s({ x: s().x + d, y: s().y + x }), b(Z, { x: v.clientX, y: v.clientY }, !0);
      return;
    }
    if (l(_) && l(Y)) {
      const d = Ae(v), x = n().findIndex((A) => A.id === l(Y));
      if (x !== -1) {
        const A = n()[x], M = G(d.x - l(he).x, 0, 100 - A.width), R = G(d.y - l(he).y, 0, 100 - A.height);
        n(n()[x].x = M, !0), n(n()[x].y = R, !0);
      }
      return;
    }
    if (l(E) && l(Y) && l(S) && l(y)) {
      const d = Ae(v), x = {
        x: d.x - l(g).x,
        y: d.y - l(g).y
      }, A = n().findIndex((M) => M.id === l(Y));
      if (A !== -1) {
        const M = Wt(l(S), x, l(y));
        n(n()[A].x = M.x, !0), n(n()[A].y = M.y, !0), n(n()[A].width = M.width, !0), n(n()[A].height = M.height, !0);
      }
      return;
    }
    l(m) && l(F) && b(V, Ae(v), !0);
  }
  function Nt(v) {
    if (tt(v), l(h)) {
      b(h, !1), b(Z, null);
      return;
    }
    if (l(_)) {
      b(_, !1), b(Y, null);
      return;
    }
    if (l(E)) {
      b(E, !1), b(Y, null), b(y, null), b(S, null), b(g, { x: 0, y: 0 }, !0);
      return;
    }
    if (l(m) && l(F)) {
      b(V, Ae(v), !0);
      const d = Math.min(l(F).x, l(V).x), x = Math.min(l(F).y, l(V).y), A = Math.abs(l(F).x - l(V).x), M = Math.abs(l(F).y - l(V).y);
      if (A > 0.5 && M > 0.5) {
        const R = {
          id: crypto.randomUUID(),
          x: d,
          y: x,
          width: A,
          height: M,
          color: un[n().length % un.length],
          title: kn(),
          isActive: !0
        };
        n([...n(), R]), n(n().map((K) => ({ ...K, isActive: K.id === R.id })));
      }
    }
    b(m, !1), b(F, null), b(V, null);
  }
  function Jt(v) {
    if (tt(v), v.ctrlKey) {
      v.preventDefault();
      const d = Math.pow(1.1, -v.deltaY / 100), x = Math.min(Math.max(i() * d, 0.5), 10);
      if (!me) {
        i(x);
        return;
      }
      const A = me.getBoundingClientRect(), M = v.clientX >= A.left && v.clientX <= A.right && v.clientY >= A.top && v.clientY <= A.bottom;
      let R, K;
      M ? (R = (v.clientX - A.left) / i(), K = (v.clientY - A.top) / i()) : (R = A.width / (2 * i()), K = A.height / (2 * i())), s({
        x: s().x - R * (x - i()),
        y: s().y - K * (x - i())
      }), i(x);
    } else
      s({ x: s().x - v.deltaX, y: s().y - v.deltaY });
  }
  var ye = Gl();
  Je("keydown", Ct, tt), Je("keyup", Ct, tt), Je("blur", Ct, An);
  var ct = C(ye), wt = C(ct);
  zn(wt, (v) => N = v, () => N);
  var Qt = D(wt, 2);
  {
    var Cn = (v) => {
      var d = Vl(), x = Or(d), A = D(x, 2), M = D(A, 2);
      we(() => {
        rt(x, `
                    left: ${l(z).x ?? ""}%;
                    margin-left: -0.5px;
                    width: 1px;
                    background-image: repeating-linear-gradient(
                        to bottom,
                        rgba(255, 255, 255, 0.95) 0 6px,
                        rgba(15, 23, 42, 0.95) 6px 12px
                    );
                `), rt(A, `
                    top: ${l(z).y ?? ""}%;
                    margin-top: -0.5px;
                    height: 1px;
                    background-image: repeating-linear-gradient(
                        to right,
                        rgba(255, 255, 255, 0.95) 0 6px,
                        rgba(15, 23, 42, 0.95) 6px 12px
                    );
                `), rt(M, `
                    left: ${l(z).x ?? ""}%;
                    top: ${l(z).y ?? ""}%;
                    margin-left: -4px;
                    margin-top: -4px;
                    width: 8px;
                    height: 8px;
                    background-color: rgba(255, 255, 255, 0.98);
                    border: 2px solid rgba(15, 23, 42, 0.95);
                    box-sizing: border-box;
                `);
      }), W(v, d);
    };
    be(Qt, (v) => {
      l(k) && v(Cn);
    });
  }
  var xt = D(Qt, 2);
  jn(xt, 17, n, (v) => v.id, (v, d) => {
    var x = ql(), A = C(x);
    {
      var M = (R) => {
        var K = Ul(), ve = Or(K), fe = C(ve), ne = D(ve, 2);
        jn(ne, 17, () => Ye, (pe) => pe.name, (pe, Se) => {
          var Ie = $l();
          we(() => {
            Et(Ie, "data-handle", l(Se).name), rt(Ie, l(Se).style);
          }), W(pe, Ie);
        }), we(() => {
          rt(ve, `
                            background-color: ${l(d).color ?? ""};
                            transform: scale(${1 / i()});
                            transform-origin: bottom left;
                        `), Ke(fe, l(d).title);
        }), W(R, K);
      };
      be(A, (R) => {
        l(d).isActive && R(M);
      });
    }
    we(() => {
      Ee(x, 1, `annotation-marker absolute border-2 ${l(d).isActive ? "ring-2 ring-white z-50" : "z-40"}`), Et(x, "data-id", l(d).id), rt(x, `
                    left: ${l(d).x ?? ""}%;
                    top: ${l(d).y ?? ""}%;
                    width: ${l(d).width ?? ""}%;
                    height: ${l(d).height ?? ""}%;
                    border: 2px solid ${l(d).color ?? ""} !important;
                    background-color: ${l(d).isActive ? l(d).color + "33" : "transparent"};
                    display: block !important;
                    box-sizing: border-box;
                `);
    }), W(v, x);
  });
  var Pt = D(xt, 2);
  {
    var zt = (v) => {
      const d = /* @__PURE__ */ Be(() => Math.min(l(F).x, l(V).x)), x = /* @__PURE__ */ Be(() => Math.min(l(F).y, l(V).y)), A = /* @__PURE__ */ Be(() => Math.abs(l(F).x - l(V).x)), M = /* @__PURE__ */ Be(() => Math.abs(l(F).y - l(V).y));
      var R = Yl();
      we(() => rt(R, `
                    left: ${l(d) ?? ""}%;
                    top: ${l(x) ?? ""}%;
                    width: ${l(A) ?? ""}%;
                    height: ${l(M) ?? ""}%;
                    border: 2px dashed white !important;
                    background-color: rgba(59, 130, 246, 0.3) !important;
                    z-index: 100 !important;
                    display: block !important;
                `)), W(v, R);
    };
    be(Pt, (v) => {
      l(m) && l(F) && l(V) && v(zt);
    });
  }
  zn(ct, (v) => me = v, () => me);
  var en = D(ct, 2);
  {
    var tn = (v) => {
      var d = Zl();
      W(v, d);
    };
    be(en, (v) => {
      a() && v(tn);
    });
  }
  zn(ye, (v) => se = v, () => se), we(() => {
    rt(ye, `cursor: ${a() ? "progress" : l(h) ? "grabbing" : l(_) ? "move" : l(T) ? "grab" : l(k) ? "none" : "default"}; touch-action: none;`), rt(ct, `transform: translate(${s().x ?? ""}px, ${s().y ?? ""}px) scale(${i() ?? ""}); will-change: transform;`), Et(wt, "src", t.imageSrc);
  }), X("mousedown", ye, Ot), X("mousemove", ye, bt), X("mouseup", ye, Nt), Je("wheel", ye, Jt), X("contextmenu", ye, (v) => v.preventDefault()), Je("mouseleave", ct, Mn), Je("load", wt, de), W(e, ye), fr();
}
Jr(["mousedown", "mousemove", "mouseup", "contextmenu"]);
var Xl = /* @__PURE__ */ Q('<button type="button" class="block w-full px-4 py-3 text-left text-sm text-slate-700 hover:bg-slate-50"> </button>'), Wl = /* @__PURE__ */ Q('<div class="absolute left-0 right-0 top-full z-20 mt-2 max-h-56 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl"></div>'), Jl = /* @__PURE__ */ Q('<div class="animate-in fade-in slide-in-from-right duration-300"><div class="flex items-center justify-between mb-4"><button class="text-xs text-blue-600 font-semibold flex items-center gap-1 hover:underline">Back to list</button> <div class="flex items-center gap-2"><button class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete Area"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button></div></div> <div class="space-y-4"><div class="relative"><label for="annot-title" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Title</label> <input id="annot-title" type="text" class="block w-full box-border h-12 px-4 bg-white border border-slate-200 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 text-sm leading-normal text-slate-700 outline-none appearance-none" style="width: 100%; max-width: none; min-width: 0;" autocomplete="off"/> <!></div> <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 space-y-2"><div class="flex items-center justify-between text-xs px-1"><span class="font-bold text-slate-400 uppercase tracking-tighter">X Position</span> <span class="font-bold text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200"> </span></div> <div class="flex items-center justify-between text-xs px-1"><span class="font-bold text-slate-400 uppercase tracking-tighter">Y Position</span> <span class="font-bold text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200"> </span></div> <div class="flex items-center justify-between text-xs px-1"><span class="font-bold text-slate-400 uppercase tracking-tighter">Dimensions</span> <span class="font-bold text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200"> </span></div> <div class="flex items-center justify-between text-xs px-1"><span class="font-bold text-slate-400 uppercase tracking-tighter">Area</span> <span class="font-bold text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200"> </span></div></div></div></div>'), Ql = /* @__PURE__ */ Q('<div class="annot-item p-3 rounded-lg border-0 ring-1 ring-inset ring-slate-200 bg-white cursor-pointer hover:shadow-md transition-all flex items-center gap-3"><div class="w-3 h-3 rounded-full shrink-0"></div> <div class="flex-1 min-w-0 text-left"><div class="text-sm font-semibold truncate text-slate-800"> </div> <div class="text-[10px] text-slate-400 font-medium"> </div></div></div>'), eo = /* @__PURE__ */ Q('<div class="text-center py-10 px-4 text-sm text-slate-400">No areas selected. Draw on the image to get started.</div>'), to = /* @__PURE__ */ Q("<div><span>Shift</span> <span>AI</span></div>"), no = /* @__PURE__ */ Q('<div class="relative h-full bg-white border-l border-slate-200 transition-all duration-300 z-30 flex flex-col w-80 text-left" style="border-left-width: 1px;"><div class="p-6 border-b border-slate-100" style="border-bottom-width: 1px;"><h2 class="text-lg font-bold flex items-center justify-between text-slate-800"><span>Annotations</span> <span class="text-xs bg-slate-100 px-2 py-1 rounded-full text-slate-500 font-medium"> </span></h2></div> <div class="flex-1 overflow-y-auto p-4 space-y-4"><!></div> <div class="p-4 bg-slate-50 border-t border-slate-200 space-y-2" style="border-top-width: 1px;"><div><div><span>Ctrl</span> <span>Scroll</span></div> <div><span>Alt</span> <span>Drag</span></div> <!></div> <div class="rounded-xl border-0 ring-1 ring-inset ring-slate-200 bg-white p-1 shadow-sm"><div class="flex items-center gap-1"><button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-700 transition-colors hover:bg-slate-100" title="Zoom out" aria-label="Zoom out"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"></path></svg></button> <div class="min-w-0 flex-1 text-center text-xs font-bold text-slate-500"> </div> <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-700 transition-colors hover:bg-slate-100" title="Zoom in" aria-label="Zoom in"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"></path></svg></button> <div class="h-4 w-px bg-slate-200"></div> <button type="button" class="inline-flex h-8 items-center justify-center rounded-lg px-2.5 text-[11px] font-bold text-blue-600 transition-colors hover:bg-slate-100" title="Reset zoom" aria-label="Reset zoom">Reset</button></div></div></div></div>');
function ro(e, t) {
  ur(t, !0);
  let n = U(t, "annotations", 15), r = U(t, "imageDimensions", 19, () => ({ width: 0, height: 0 })), i = U(t, "titleOptions", 19, () => []), s = U(t, "onTitleChange", 3, null), o = U(t, "showAiShortcut", 3, !1), f = U(t, "zoomScale", 3, 1), a = U(t, "onZoomIn", 3, null), u = U(t, "onZoomOut", 3, null), h = U(t, "onZoomReset", 3, null), m = /* @__PURE__ */ Be(() => n().find((d) => d.isActive)), _ = /* @__PURE__ */ P(!1), E = /* @__PURE__ */ P(""), y = /* @__PURE__ */ P(!1), S = /* @__PURE__ */ P(!1), g = /* @__PURE__ */ P(!1);
  const k = (d, x) => Math.round(d / 100 * x), z = (d) => k(d.width, r().width), T = (d) => k(d.height, r().height), N = (d) => z(d) * T(d);
  let q = /* @__PURE__ */ Be(() => {
    const d = l(E).trim().toLowerCase();
    return i().filter((x) => x ? d.length === 0 || x.toLowerCase().includes(d) : !1);
  });
  function de() {
    l(m) && n(n().filter((d) => d.id !== l(m).id));
  }
  function F(d) {
    n(n().map((x) => ({ ...x, isActive: x.id === d })));
  }
  function V() {
    n(n().map((d) => ({ ...d, isActive: !1 })));
  }
  function Z() {
    b(E, ""), b(_, !0);
  }
  function Y() {
    setTimeout(
      () => {
        b(_, !1);
      },
      100
    );
  }
  function he(d) {
    se(d, { closeOptions: !0 });
  }
  function se(d, { closeOptions: x = !1 } = {}) {
    l(m) && (n(n().map((A) => A.id === l(m).id ? { ...A, title: d } : A)), b(E, d, !0), b(_, !x), me(d));
  }
  function me(d) {
    if (typeof s() != "function")
      return;
    const x = typeof d == "string" ? d.trim() : "";
    x && s()(x);
  }
  function $(d) {
    b(y, !!d.altKey, !0), b(S, !!d.ctrlKey, !0), b(g, !!d.shiftKey, !0);
  }
  function J() {
    b(y, !1), b(S, !1), b(g, !1);
  }
  function ae(d) {
    return `inline-flex items-center gap-1 rounded-md px-2 py-1 transition-all ${d ? "bg-blue-600 text-white shadow-sm ring-1 ring-inset ring-blue-500/30" : "bg-white text-slate-500 border-0 ring-1 ring-inset ring-slate-200"}`;
  }
  function ue(d) {
    return `rounded border px-1 py-0.5 text-[9px] font-bold uppercase tracking-[0.12em] ${d ? "border-0 bg-white/15 text-white ring-1 ring-inset ring-white/30" : "border-0 bg-slate-50 text-slate-600 ring-1 ring-inset ring-slate-300"}`;
  }
  function ee(d) {
    typeof d == "function" && d();
  }
  var G = no();
  Je("keydown", Ct, $), Je("keyup", Ct, $), Je("blur", Ct, J);
  var Ye = C(G), kn = C(Ye), tt = D(C(kn), 2), An = C(tt), Ae = D(Ye, 2), Wt = C(Ae);
  {
    var Sn = (d) => {
      var x = Jl(), A = C(x), M = C(A), R = D(M, 2), K = C(R), ve = D(A, 2), fe = C(ve), ne = D(C(fe), 2), pe = D(ne, 2);
      {
        var Se = (dt) => {
          var p = Wl();
          jn(p, 20, () => l(q), (w) => w, (w, I) => {
            var te = Xl(), nt = C(te);
            we(() => Ke(nt, I)), X("mousedown", te, (sn) => {
              sn.preventDefault(), he(I);
            }), W(w, te);
          }), W(dt, p);
        };
        be(pe, (dt) => {
          l(_) && l(q).length > 0 && dt(Se);
        });
      }
      var Ie = D(fe, 2), nn = C(Ie), rn = D(C(nn), 2), Dn = C(rn), In = D(nn, 2), hr = D(C(In), 2), Yn = C(hr), Zn = D(In, 2), vr = D(C(Zn), 2), pr = C(vr), Gn = D(Zn, 2), gr = D(C(Gn), 2), _r = C(gr);
      we(
        (dt, p, w, I, te) => {
          jl(ne, l(m).title), Ke(Dn, `${dt ?? ""} px`), Ke(Yn, `${p ?? ""} px`), Ke(pr, `${w ?? ""} x ${I ?? ""}`), Ke(_r, `${te ?? ""} px2`);
        },
        [
          () => k(l(m).x, r().width),
          () => k(l(m).y, r().height),
          () => z(l(m)),
          () => T(l(m)),
          () => N(l(m))
        ]
      ), X("click", M, V), X("click", K, de), Je("focus", ne, Z), X("click", ne, Z), X("input", ne, (dt) => {
        se(dt.currentTarget.value);
      }), Je("blur", ne, Y), W(d, x);
    }, Tn = (d) => {
      var x = kl(), A = Or(x);
      jn(A, 17, n, (M) => M.id, (M, R) => {
        var K = Ql(), ve = C(K), fe = D(ve, 2), ne = C(fe), pe = C(ne), Se = D(ne, 2), Ie = C(Se);
        we(
          (nn, rn, Dn) => {
            rt(ve, `background-color: ${l(R).color ?? ""}`), Ke(pe, l(R).title), Ke(Ie, `${nn ?? ""} x ${rn ?? ""} • ${Dn ?? ""} px2`);
          },
          [
            () => z(l(R)),
            () => T(l(R)),
            () => N(l(R))
          ]
        ), X("click", K, () => F(l(R).id)), W(M, K);
      }), W(d, x);
    }, Mn = (d) => {
      var x = eo();
      W(d, x);
    };
    be(Wt, (d) => {
      l(m) ? d(Sn) : n().length > 0 ? d(Tn, 1) : d(Mn, -1);
    });
  }
  var Ot = D(Ae, 2), bt = C(Ot), Nt = C(bt), Jt = C(Nt), ye = D(Nt, 2), ct = C(ye), wt = D(ye, 2);
  {
    var Qt = (d) => {
      var x = to(), A = C(x);
      we(
        (M, R) => {
          Ee(x, 1, M), Ee(A, 1, R);
        },
        [
          () => ln(ae(l(g))),
          () => ln(ue(l(g)))
        ]
      ), W(d, x);
    };
    be(wt, (d) => {
      o() && d(Qt);
    });
  }
  var Cn = D(bt, 2), xt = C(Cn), Pt = C(xt), zt = D(Pt, 2), en = C(zt), tn = D(zt, 2), v = D(tn, 4);
  we(
    (d, x, A, M, R) => {
      Ke(An, n().length), Ee(bt, 1, `flex flex-nowrap items-center justify-center gap-1 text-[10px] font-medium ${l(y) || l(S) || l(g) ? "text-slate-700" : "text-slate-400"}`), Ee(Nt, 1, d), Ee(Jt, 1, x), Ee(ye, 1, A), Ee(ct, 1, M), Ke(en, `${R ?? ""}%`);
    },
    [
      () => ln(ae(l(S))),
      () => ln(ue(l(S))),
      () => ln(ae(l(y))),
      () => ln(ue(l(y))),
      () => Math.round(f() * 100)
    ]
  ), X("click", Pt, () => ee(u())), X("click", tn, () => ee(a())), X("click", v, () => ee(h())), W(e, G), fr();
}
Jr(["click", "input", "mousedown"]);
var io = /* @__PURE__ */ Q('<button type="button"> </button>'), so = /* @__PURE__ */ Q('<div class="flex items-center gap-2 min-w-0"><span class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400 whitespace-nowrap">Group</span> <div class="inline-flex items-center h-10 rounded-lg border-0 ring-1 ring-inset ring-slate-300 bg-slate-50 p-1 gap-1"></div></div>'), lo = /* @__PURE__ */ Q('<button type="button" class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-red-600 hover:bg-red-700 text-white shadow-sm transition-all" title="Delete Item" aria-label="Delete Item"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>'), oo = /* @__PURE__ */ Q('<button type="button" class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-white hover:bg-slate-50 text-slate-500 border border-slate-200 shadow-sm transition-all" title="Undo all changes" aria-label="Undo all changes"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a4 4 0 010 8H3m0-8l4-4m-4 4l4 4"></path></svg></button>'), ao = /* @__PURE__ */ Q('<button type="button"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.813 15.904L9 18l-.813-2.096A4.5 4.5 0 005.904 13.187L3.808 12l2.096-.813A4.5 4.5 0 008.187 8.904L9 6.808l.813 2.096A4.5 4.5 0 0012.096 11.187L14.192 12l-2.096.813A4.5 4.5 0 009.813 15.904zM18 14v4m-2-2h4M17 5l.47 1.222L18.692 6.7l-1.222.47L17 8.392l-.47-1.222-1.222-.47 1.222-.47L17 5z"></path></svg></button>'), uo = /* @__PURE__ */ Q('<button type="button" title="Previous item" aria-label="Previous item"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg></button>'), fo = /* @__PURE__ */ Q('<button type="button" title="Next item" aria-label="Next item"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></button>'), co = /* @__PURE__ */ Q('<div class="h-full flex flex-col items-center justify-center text-center max-w-lg mx-auto"><div class="w-24 h-24 bg-white rounded-3xl shadow-xl flex items-center justify-center mb-8 rotate-3 border border-slate-100"><svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg></div> <h2 class="text-3xl font-extrabold mb-3 text-slate-800 tracking-tight">Manual Image Annotation</h2> <p class="text-slate-500 mb-10 text-lg leading-relaxed">Zoom into details, drag markers, and define custom areas with specific information.</p> <button class="px-10 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-all font-bold text-lg shadow-2xl shadow-blue-500/30 active:scale-95">Get Started</button></div>'), ho = /* @__PURE__ */ Q('<div class="absolute inset-0 z-50 flex items-center justify-center bg-white/60 backdrop-blur-sm"><div class="flex flex-col items-center gap-3"><svg class="w-10 h-10 text-emerald-600 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <span class="text-sm font-semibold text-slate-600">Loading...</span></div></div>'), vo = /* @__PURE__ */ Q('<div class="sq-svelte-image-editor flex flex-col h-full min-h-0 overflow-hidden text-slate-900 bg-slate-100 font-sans border border-slate-200 rounded-xl shadow-sm" style="border-width: 1px;"><header class="bg-white border-b border-slate-200 px-6 py-2 flex items-center justify-between z-40 shrink-0 shadow-sm" style="border-bottom-width: 1px;"><div class="flex items-center gap-3 min-w-0"><!> <!> <button type="button" class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-blue-600 hover:bg-blue-700 text-white shadow-sm transition-all"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg></button> <button type="button" aria-label="Toggle Auto-Save"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path><polyline stroke-linecap="round" stroke-linejoin="round" stroke-width="2" points="17 21 17 13 7 13 7 21"></polyline><polyline stroke-linecap="round" stroke-linejoin="round" stroke-width="2" points="7 3 7 8 15 8"></polyline></svg></button> <!> <!></div> <div class="flex items-center gap-2"><div class="inline-flex items-center h-10 rounded-lg border-0 ring-1 ring-inset ring-slate-300 bg-slate-50 p-1 gap-1"><button type="button">Not Ready</button> <button type="button">Ready</button></div> <!> <!> <input type="file" accept="image/*" class="hidden"/></div></header> <main class="flex-1 min-h-0 flex overflow-hidden relative"><div class="flex-1 min-h-0 relative overflow-hidden bg-slate-100 p-4 md:p-8 flex items-center justify-center"><!></div> <!> <!></main></div>');
function po(e, t) {
  ur(t, !0);
  function n(p) {
    if (!p || typeof p != "object")
      return { width: 0, height: 0 };
    const w = Number.parseInt(p.width, 10), I = Number.parseInt(p.height, 10);
    return !w || !I || w < 1 || I < 1 ? { width: 0, height: 0 } : { width: w, height: I };
  }
  function r(p) {
    return JSON.stringify(p.map((w) => ({
      id: w.id,
      x: w.x,
      y: w.y,
      width: w.width,
      height: w.height,
      title: w.title
    })));
  }
  let i = U(t, "imageSrc", 15, null), s = U(t, "initialImageDimensions", 3, null), o = U(t, "annotations", 31, () => He([])), f = U(t, "groupOptions", 19, () => []), a = U(t, "groupValue", 3, null), u = U(t, "readyValue", 3, !1), h = U(t, "titleOptions", 19, () => []), m = U(t, "lastUsedTitle", 3, null), _ = U(t, "previousItemAction", 3, null), E = U(t, "nextItemAction", 3, null), y = U(t, "autoDetectRequest", 3, null), S = U(t, "canDeleteItem", 3, !1), g = U(t, "serverAnnotations", 3, null), k = U(t, "serverGroupValue", 3, void 0), z = U(t, "serverReadyValue", 3, void 0), T = /* @__PURE__ */ P(He({ width: 0, height: 0 })), N = /* @__PURE__ */ P(null), q = /* @__PURE__ */ P(!1), de = /* @__PURE__ */ P(""), F = /* @__PURE__ */ P(!1), V = /* @__PURE__ */ P(!1), Z = /* @__PURE__ */ P(1), Y = /* @__PURE__ */ P(He({ x: 0, y: 0 })), he = /* @__PURE__ */ P(!1), se = /* @__PURE__ */ P(!0), me, $, J = /* @__PURE__ */ P(!1), ae = [], ue = "", ee = null, G = !1, Ye = /* @__PURE__ */ Be(() => l(J) ? r(br(o())) !== ue || l(N) !== ee || l(q) !== G : !1), kn = /* @__PURE__ */ Be(() => {
    var w;
    const p = [...h()];
    for (const I of o()) {
      const te = (w = I.title) == null ? void 0 : w.trim();
      te && p.push(te);
    }
    return [...new Set(p)];
  }), tt = /* @__PURE__ */ Be(() => l(se) ? "bg-emerald-600 text-white border-emerald-600 hover:bg-emerald-700" : l(Ye) ? "bg-red-50 text-red-600 border-red-200 hover:bg-red-100" : "bg-white text-slate-400 border-slate-200 hover:bg-slate-50"), An = /* @__PURE__ */ Be(() => l(se) ? "Auto-Save is ON" : l(Ye) ? "No Save — Unsaved Changes!" : "Auto-Save is OFF");
  it(() => {
    let p = !1;
    const w = o().map((I, te) => I.color ? I : (p = !0, { ...I, color: un[te % un.length] }));
    p && o(w);
  }), Bl(() => {
    const p = g() !== null ? g() : br(o());
    ae = p.map((w) => ({ ...w })), ue = r(p), ee = k() !== void 0 ? k() : l(N), G = z() !== void 0 ? z() : l(q), b(J, !0);
  });
  function Ae(p) {
    var I;
    const w = (I = p.target.files) == null ? void 0 : I[0];
    if (w) {
      const te = new FileReader();
      te.onload = (nt) => {
        i(nt.target.result), o(
          []
          // Reset on new image
        ), b(
          T,
          { width: 0, height: 0 },
          // Reset dimensions
          !0
        ), b(Z, 1), b(Y, { x: 0, y: 0 }, !0);
      }, te.readAsDataURL(w);
    }
  }
  function Wt() {
    me.click();
  }
  function Sn() {
    if (l(de))
      return l(de);
    const p = [...o()].reverse().find((w) => w.title && w.title.trim().length > 0);
    return (p == null ? void 0 : p.title) ?? `Area ${o().length + 1}`;
  }
  async function Tn(p) {
    if (l(V) || typeof y() != "function")
      return null;
    b(V, !0);
    try {
      const w = await y()(p), I = w == null ? void 0 : w.bbox;
      if (!I)
        return null;
      const te = {
        id: crypto.randomUUID(),
        x: I.x,
        y: I.y,
        width: I.width,
        height: I.height,
        color: un[o().length % un.length],
        title: Sn(),
        isActive: !0
      };
      return o([...o(), te]), o(o().map((nt) => ({ ...nt, isActive: nt.id === te.id }))), w;
    } catch (w) {
      return console.error("Automatic object detection failed", w), null;
    } finally {
      b(V, !1);
    }
  }
  function Mn(p) {
    !p || p === l(N) || (b(N, p, !0), $ && $.dispatchEvent(new CustomEvent("groupchange", { detail: { value: p }, bubbles: !0, composed: !0 })), Ot(p));
  }
  function Ot(p) {
    if (!$)
      return;
    const w = $.closest("form");
    if (!w)
      return;
    const I = w.querySelector('select[name$="[group]"], input[name$="[group]"]'), te = p == null ? "" : String(p);
    !I || I.value === te || (I.value = te, I.dispatchEvent(new Event("input", { bubbles: !0 })), I.dispatchEvent(new Event("change", { bubbles: !0 })));
  }
  function bt(p) {
    !$ || !p || (b(he, !0), $.dispatchEvent(new CustomEvent("saveandnavigate", {
      detail: { action: p, shouldSave: l(se) },
      bubbles: !0,
      composed: !0
    })));
  }
  function Nt() {
    !$ || !S() || $.dispatchEvent(new CustomEvent("deleteitem", { bubbles: !0, composed: !0 }));
  }
  function Jt(p) {
    p !== l(q) && (b(q, p, !0), $ && $.dispatchEvent(new CustomEvent("readychange", { detail: { value: p }, bubbles: !0, composed: !0 })));
  }
  function ye() {
    o(ae.map((p) => ({ ...p }))), b(N, ee, !0), b(q, G, !0), $ && ($.dispatchEvent(new CustomEvent("groupchange", {
      detail: { value: l(N) },
      bubbles: !0,
      composed: !0
    })), $.dispatchEvent(new CustomEvent("readychange", {
      detail: { value: l(q) },
      bubbles: !0,
      composed: !0
    }))), Ot(l(N));
  }
  function ct(p) {
    const w = typeof p == "string" ? p.trim() : "";
    !w || w === l(de) || (b(de, w, !0), $ && $.dispatchEvent(new CustomEvent("lastusedtitlechange", {
      detail: { value: w },
      bubbles: !0,
      composed: !0
    })));
  }
  function wt() {
    b(Z, Math.min(l(Z) * 1.5, 10), !0);
  }
  function Qt() {
    b(Z, Math.max(l(Z) / 1.5, 0.5), !0);
  }
  function Cn() {
    b(Z, 1), b(Y, { x: 0, y: 0 }, !0);
  }
  it(() => {
    $ && $.dispatchEvent(new CustomEvent("change", {
      detail: br(o()),
      bubbles: !0,
      composed: !0
    }));
  }), it(() => {
    const p = a() ?? null;
    b(N, p, !0), Ot(p);
  }), it(() => {
    b(q, !!u(), !0);
  }), it(() => {
    b(T, n(s()), !0);
  }), it(() => {
    b(de, typeof m() == "string" ? m().trim() : "", !0);
  }), it(() => {
    const p = localStorage.getItem("syntetiq-auto-save");
    p !== null && b(se, p === "true");
  }), it(() => {
    localStorage.setItem("syntetiq-auto-save", l(se)), $ && $.dispatchEvent(new CustomEvent("autosavechange", {
      detail: { value: l(se) },
      bubbles: !0,
      composed: !0
    }));
  });
  var xt = vo(), Pt = C(xt), zt = C(Pt), en = C(zt);
  {
    var tn = (p) => {
      var w = so(), I = D(C(w), 2);
      jn(I, 21, f, (te) => te.value, (te, nt) => {
        var sn = io(), ns = C(sn);
        we(() => {
          Ee(sn, 1, `inline-flex h-full items-center rounded-md px-3 py-1.5 text-xs font-semibold transition-colors ${l(nt).value === l(N) ? "bg-white text-blue-600 shadow-sm ring-1 ring-blue-100" : "text-slate-500 hover:bg-white hover:text-slate-700"}`), Ke(ns, l(nt).label);
        }), X("click", sn, () => Mn(l(nt).value)), W(te, sn);
      }), W(p, w);
    };
    be(en, (p) => {
      f().length > 0 && p(tn);
    });
  }
  var v = D(en, 2);
  {
    var d = (p) => {
      var w = lo();
      X("click", w, Nt), W(p, w);
    };
    be(v, (p) => {
      S() && p(d);
    });
  }
  var x = D(v, 2), A = D(x, 2), M = D(A, 2);
  {
    var R = (p) => {
      var w = oo();
      X("click", w, ye), W(p, w);
    };
    be(M, (p) => {
      l(Ye) && p(R);
    });
  }
  var K = D(M, 2);
  {
    var ve = (p) => {
      var w = ao();
      we(() => {
        Ee(w, 1, `inline-flex items-center justify-center w-10 h-10 rounded-md shadow-sm transition-all border ${l(F) ? "bg-emerald-600 hover:bg-emerald-700 text-white border-emerald-600" : "bg-white hover:bg-slate-50 text-slate-700 border-slate-200"} ${l(V) ? "opacity-70 cursor-not-allowed" : ""}`), Et(w, "title", l(V) ? "Detecting..." : "AI Select"), Et(w, "aria-label", l(V) ? "Detecting..." : "AI Select");
      }), X("click", w, () => {
        l(V) || b(F, !l(F));
      }), W(p, w);
    };
    be(K, (p) => {
      i() && y() && p(ve);
    });
  }
  var fe = D(zt, 2), ne = C(fe), pe = C(ne), Se = D(pe, 2), Ie = D(ne, 2);
  {
    var nn = (p) => {
      var w = uo();
      we(() => {
        w.disabled = l(he), Ee(w, 1, `inline-flex items-center justify-center w-10 h-10 rounded-md shadow-sm transition-all text-white ${l(he) ? "bg-emerald-500 opacity-50 cursor-not-allowed" : "bg-emerald-600 hover:bg-emerald-700"}`);
      }), X("click", w, () => bt(_())), W(p, w);
    };
    be(Ie, (p) => {
      _() && p(nn);
    });
  }
  var rn = D(Ie, 2);
  {
    var Dn = (p) => {
      var w = fo();
      we(() => {
        w.disabled = l(he), Ee(w, 1, `inline-flex items-center justify-center w-10 h-10 rounded-md shadow-sm transition-all text-white ${l(he) ? "bg-emerald-500 opacity-50 cursor-not-allowed" : "bg-emerald-600 hover:bg-emerald-700"}`);
      }), X("click", w, () => bt(E())), W(p, w);
    };
    be(rn, (p) => {
      E() && p(Dn);
    });
  }
  var In = D(rn, 2);
  zn(In, (p) => me = p, () => me);
  var hr = D(Pt, 2), Yn = C(hr), Zn = C(Yn);
  {
    var vr = (p) => {
      var w = co(), I = D(C(w), 6);
      X("click", I, Wt), W(p, w);
    }, pr = (p) => {
      {
        let w = /* @__PURE__ */ Be(() => l(de) || null);
        Kl(p, {
          get imageSrc() {
            return i();
          },
          get defaultAnnotationTitle() {
            return l(w);
          },
          get aiAssistEnabled() {
            return l(F);
          },
          get aiAssistPending() {
            return l(V);
          },
          requestAutoDetect: Tn,
          get annotations() {
            return o();
          },
          set annotations(I) {
            o(I);
          },
          get imageDimensions() {
            return l(T);
          },
          set imageDimensions(I) {
            b(T, I, !0);
          },
          get scale() {
            return l(Z);
          },
          set scale(I) {
            b(Z, I, !0);
          },
          get offset() {
            return l(Y);
          },
          set offset(I) {
            b(Y, I, !0);
          }
        });
      }
    };
    be(Zn, (p) => {
      i() ? p(pr, -1) : p(vr);
    });
  }
  var Gn = D(Yn, 2);
  {
    var gr = (p) => {
      {
        let w = /* @__PURE__ */ Be(() => !!y());
        ro(p, {
          get imageDimensions() {
            return l(T);
          },
          get titleOptions() {
            return l(kn);
          },
          onTitleChange: ct,
          get showAiShortcut() {
            return l(w);
          },
          get zoomScale() {
            return l(Z);
          },
          onZoomIn: wt,
          onZoomOut: Qt,
          onZoomReset: Cn,
          get annotations() {
            return o();
          },
          set annotations(I) {
            o(I);
          }
        });
      }
    };
    be(Gn, (p) => {
      i() && p(gr);
    });
  }
  var _r = D(Gn, 2);
  {
    var dt = (p) => {
      var w = ho();
      W(p, w);
    };
    be(_r, (p) => {
      l(he) && p(dt);
    });
  }
  zn(xt, (p) => $ = p, () => $), we(() => {
    Et(x, "title", i() ? "Change Image" : "Upload Image"), Et(x, "aria-label", i() ? "Change Image" : "Upload Image"), Ee(A, 1, `inline-flex items-center justify-center w-10 h-10 rounded-lg shadow-sm transition-all border ${l(tt)}`), Et(A, "title", l(An)), Ee(pe, 1, `inline-flex h-full items-center rounded-md px-3 py-1.5 text-xs font-semibold transition-colors ${l(q) ? "text-slate-500 hover:bg-white hover:text-slate-700" : "bg-white text-amber-600 shadow-sm ring-1 ring-amber-100"}`), Ee(Se, 1, `inline-flex h-full items-center rounded-md px-3 py-1.5 text-xs font-semibold transition-colors ${l(q) ? "bg-white text-emerald-600 shadow-sm ring-1 ring-emerald-100" : "text-slate-500 hover:bg-white hover:text-slate-700"}`);
  }), X("click", x, Wt), X("click", A, () => b(se, !l(se))), X("click", pe, () => Jt(!1)), X("click", Se, () => Jt(!0)), X("change", In, Ae), W(e, xt), fr();
}
Jr(["click", "change"]);
function go(e, t = {}) {
  const n = Al(po, {
    target: e,
    props: t
  });
  return n.$destroy = () => {
    Tl(n);
  }, n;
}
const bo = { mountApp: go };
export {
  bo as default,
  go as mountApp
};
