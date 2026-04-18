var pn = Object.defineProperty;
var Rt = (e) => {
  throw TypeError(e);
};
var gn = (e, t, n) => t in e ? pn(e, t, { enumerable: !0, configurable: !0, writable: !0, value: n }) : e[t] = n;
var X = (e, t, n) => gn(e, typeof t != "symbol" ? t + "" : t, n), lt = (e, t, n) => t.has(e) || Rt("Cannot " + n);
var f = (e, t, n) => (lt(e, t, "read from private field"), n ? n.call(e) : t.get(e)), g = (e, t, n) => t.has(e) ? Rt("Cannot add the same private member more than once") : t instanceof WeakSet ? t.add(e) : t.set(e, n), d = (e, t, n, r) => (lt(e, t, "write to private field"), r ? r.call(e, n) : t.set(e, n), n), E = (e, t, n) => (lt(e, t, "access private method"), n);
var wn = Array.isArray, mn = Array.prototype.indexOf, yn = Array.from, En = Object.defineProperty, je = Object.getOwnPropertyDescriptor, bn = Object.prototype, xn = Array.prototype, Tn = Object.getPrototypeOf, Nt = Object.isExtensible;
function Sn(e) {
  for (var t = 0; t < e.length; t++)
    e[t]();
}
function qt() {
  var e, t, n = new Promise((r, s) => {
    e = r, t = s;
  });
  return { promise: n, resolve: e, reject: t };
}
const S = 2, jt = 4, Et = 8, kn = 1 << 24, ie = 16, xe = 32, Te = 64, it = 128, U = 512, R = 1024, L = 2048, Z = 4096, ue = 8192, oe = 16384, Lt = 32768, Je = 65536, Ft = 1 << 17, Vt = 1 << 18, Ie = 1 << 19, An = 1 << 20, be = 32768, ct = 1 << 21, bt = 1 << 22, ae = 1 << 23, ut = Symbol("$state"), ke = new class extends Error {
  constructor() {
    super(...arguments);
    X(this, "name", "StaleReactionError");
    X(this, "message", "The reaction that called `getAbortSignal()` was re-run or destroyed");
  }
}();
function Rn() {
  throw new Error("https://svelte.dev/e/async_derived_orphan");
}
function Nn() {
  throw new Error("https://svelte.dev/e/effect_update_depth_exceeded");
}
function Fn() {
  throw new Error("https://svelte.dev/e/state_descriptors_fixed");
}
function On() {
  throw new Error("https://svelte.dev/e/state_prototype_fixed");
}
function Dn() {
  throw new Error("https://svelte.dev/e/state_unsafe_mutation");
}
function Cn() {
  throw new Error("https://svelte.dev/e/svelte_boundary_reset_onerror");
}
const Pn = 2, k = Symbol();
function In() {
  console.warn("https://svelte.dev/e/svelte_boundary_reset_noop");
}
function Ut(e) {
  return e === this.v;
}
let Y = null;
function De(e) {
  Y = e;
}
function Mn(e, t = !1, n) {
  Y = {
    p: Y,
    i: !1,
    c: null,
    e: null,
    s: e,
    x: null,
    l: null
  };
}
function qn(e) {
  var t = (
    /** @type {ComponentContext} */
    Y
  ), n = t.e;
  if (n !== null) {
    t.e = null;
    for (var r of n)
      nr(r);
  }
  return t.i = !0, Y = t.p, /** @type {T} */
  {};
}
function Yt() {
  return !0;
}
let Ae = [];
function jn() {
  var e = Ae;
  Ae = [], Sn(e);
}
function xt(e) {
  if (Ae.length === 0) {
    var t = Ae;
    queueMicrotask(() => {
      t === Ae && jn();
    });
  }
  Ae.push(e);
}
function Bt(e) {
  var t = m;
  if (t === null)
    return p.f |= ae, e;
  if ((t.f & Lt) === 0) {
    if ((t.f & it) === 0)
      throw e;
    t.b.error(e);
  } else
    Ce(e, t);
}
function Ce(e, t) {
  for (; t !== null; ) {
    if ((t.f & it) !== 0)
      try {
        t.b.error(e);
        return;
      } catch (n) {
        e = n;
      }
    t = t.parent;
  }
  throw e;
}
const ze = /* @__PURE__ */ new Set();
let w = null, b = null, B = [], Tt = null, _t = !1;
var Re, Ne, de, pe, Be, Fe, Oe, T, vt, Me, ht, Kt, $t;
const nt = class nt {
  constructor() {
    g(this, T);
    X(this, "committed", !1);
    /**
     * The current values of any sources that are updated in this batch
     * They keys of this map are identical to `this.#previous`
     * @type {Map<Source, any>}
     */
    X(this, "current", /* @__PURE__ */ new Map());
    /**
     * The values of any sources that are updated in this batch _before_ those updates took place.
     * They keys of this map are identical to `this.#current`
     * @type {Map<Source, any>}
     */
    X(this, "previous", /* @__PURE__ */ new Map());
    /**
     * When the batch is committed (and the DOM is updated), we need to remove old branches
     * and append new ones by calling the functions added inside (if/each/key/etc) blocks
     * @type {Set<() => void>}
     */
    g(this, Re, /* @__PURE__ */ new Set());
    /**
     * If a fork is discarded, we need to destroy any effects that are no longer needed
     * @type {Set<(batch: Batch) => void>}
     */
    g(this, Ne, /* @__PURE__ */ new Set());
    /**
     * The number of async effects that are currently in flight
     */
    g(this, de, 0);
    /**
     * The number of async effects that are currently in flight, _not_ inside a pending boundary
     */
    g(this, pe, 0);
    /**
     * A deferred that resolves when the batch is committed, used with `settled()`
     * TODO replace with Promise.withResolvers once supported widely enough
     * @type {{ promise: Promise<void>, resolve: (value?: any) => void, reject: (reason: unknown) => void } | null}
     */
    g(this, Be, null);
    /**
     * Deferred effects (which run after async work has completed) that are DIRTY
     * @type {Set<Effect>}
     */
    g(this, Fe, /* @__PURE__ */ new Set());
    /**
     * Deferred effects that are MAYBE_DIRTY
     * @type {Set<Effect>}
     */
    g(this, Oe, /* @__PURE__ */ new Set());
    /**
     * A set of branches that still exist, but will be destroyed when this batch
     * is committed — we skip over these during `process`
     * @type {Set<Effect>}
     */
    X(this, "skipped_effects", /* @__PURE__ */ new Set());
    X(this, "is_fork", !1);
  }
  is_deferred() {
    return this.is_fork || f(this, pe) > 0;
  }
  /**
   *
   * @param {Effect[]} root_effects
   */
  process(t) {
    var r;
    B = [], this.apply();
    var n = {
      parent: null,
      effect: null,
      effects: [],
      render_effects: []
    };
    for (const s of t)
      E(this, T, vt).call(this, s, n);
    this.is_fork || E(this, T, Kt).call(this), this.is_deferred() ? (E(this, T, Me).call(this, n.effects), E(this, T, Me).call(this, n.render_effects)) : (w = null, Ot(n.render_effects), Ot(n.effects), (r = f(this, Be)) == null || r.resolve()), b = null;
  }
  /**
   * Associate a change to a given source with the current
   * batch, noting its previous and current values
   * @param {Source} source
   * @param {any} value
   */
  capture(t, n) {
    this.previous.has(t) || this.previous.set(t, n), (t.f & ae) === 0 && (this.current.set(t, t.v), b == null || b.set(t, t.v));
  }
  activate() {
    w = this, this.apply();
  }
  deactivate() {
    w === this && (w = null, b = null);
  }
  flush() {
    if (this.activate(), B.length > 0) {
      if (Ln(), w !== null && w !== this)
        return;
    } else f(this, de) === 0 && this.process([]);
    this.deactivate();
  }
  discard() {
    for (const t of f(this, Ne)) t(this);
    f(this, Ne).clear();
  }
  /**
   *
   * @param {boolean} blocking
   */
  increment(t) {
    d(this, de, f(this, de) + 1), t && d(this, pe, f(this, pe) + 1);
  }
  /**
   *
   * @param {boolean} blocking
   */
  decrement(t) {
    d(this, de, f(this, de) - 1), t && d(this, pe, f(this, pe) - 1), this.revive();
  }
  revive() {
    for (const t of f(this, Fe))
      f(this, Oe).delete(t), F(t, L), Pe(t);
    for (const t of f(this, Oe))
      F(t, Z), Pe(t);
    this.flush();
  }
  /** @param {() => void} fn */
  oncommit(t) {
    f(this, Re).add(t);
  }
  /** @param {(batch: Batch) => void} fn */
  ondiscard(t) {
    f(this, Ne).add(t);
  }
  settled() {
    return (f(this, Be) ?? d(this, Be, qt())).promise;
  }
  static ensure() {
    if (w === null) {
      const t = w = new nt();
      ze.add(w), nt.enqueue(() => {
        w === t && t.flush();
      });
    }
    return w;
  }
  /** @param {() => void} task */
  static enqueue(t) {
    xt(t);
  }
  apply() {
  }
};
Re = new WeakMap(), Ne = new WeakMap(), de = new WeakMap(), pe = new WeakMap(), Be = new WeakMap(), Fe = new WeakMap(), Oe = new WeakMap(), T = new WeakSet(), /**
 * Traverse the effect tree, executing effects or stashing
 * them for later execution as appropriate
 * @param {Effect} root
 * @param {EffectTarget} target
 */
vt = function(t, n) {
  var _;
  t.f ^= R;
  for (var r = t.first; r !== null; ) {
    var s = r.f, i = (s & (xe | Te)) !== 0, a = i && (s & R) !== 0, o = a || (s & ue) !== 0 || this.skipped_effects.has(r);
    if ((r.f & it) !== 0 && ((_ = r.b) != null && _.is_pending()) && (n = {
      parent: n,
      effect: r,
      effects: [],
      render_effects: []
    }), !o && r.fn !== null) {
      i ? r.f ^= R : (s & jt) !== 0 ? n.effects.push(r) : $e(r) && ((r.f & ie) !== 0 && f(this, Fe).add(r), Ye(r));
      var l = r.first;
      if (l !== null) {
        r = l;
        continue;
      }
    }
    var u = r.parent;
    for (r = r.next; r === null && u !== null; )
      u === n.effect && (E(this, T, Me).call(this, n.effects), E(this, T, Me).call(this, n.render_effects), n = /** @type {EffectTarget} */
      n.parent), r = u.next, u = u.parent;
  }
}, /**
 * @param {Effect[]} effects
 */
Me = function(t) {
  for (const n of t)
    (n.f & L) !== 0 ? f(this, Fe).add(n) : (n.f & Z) !== 0 && f(this, Oe).add(n), E(this, T, ht).call(this, n.deps), F(n, R);
}, /**
 * @param {Value[] | null} deps
 */
ht = function(t) {
  if (t !== null)
    for (const n of t)
      (n.f & S) === 0 || (n.f & be) === 0 || (n.f ^= be, E(this, T, ht).call(
        this,
        /** @type {Derived} */
        n.deps
      ));
}, Kt = function() {
  if (f(this, pe) === 0) {
    for (const t of f(this, Re)) t();
    f(this, Re).clear();
  }
  f(this, de) === 0 && E(this, T, $t).call(this);
}, $t = function() {
  var i;
  if (ze.size > 1) {
    this.previous.clear();
    var t = b, n = !0, r = {
      parent: null,
      effect: null,
      effects: [],
      render_effects: []
    };
    for (const a of ze) {
      if (a === this) {
        n = !1;
        continue;
      }
      const o = [];
      for (const [u, _] of this.current) {
        if (a.current.has(u))
          if (n && _ !== a.current.get(u))
            a.current.set(u, _);
          else
            continue;
        o.push(u);
      }
      if (o.length === 0)
        continue;
      const l = [...a.current.keys()].filter((u) => !this.current.has(u));
      if (l.length > 0) {
        var s = B;
        B = [];
        const u = /* @__PURE__ */ new Set(), _ = /* @__PURE__ */ new Map();
        for (const h of o)
          zt(h, l, u, _);
        if (B.length > 0) {
          w = a, a.apply();
          for (const h of B)
            E(i = a, T, vt).call(i, h, r);
          a.deactivate();
        }
        B = s;
      }
    }
    w = null, b = t;
  }
  this.committed = !0, ze.delete(this);
};
let re = nt;
function Ln() {
  var e = ye;
  _t = !0;
  var t = null;
  try {
    var n = 0;
    for (et(!0); B.length > 0; ) {
      var r = re.ensure();
      if (n++ > 1e3) {
        var s, i;
        Vn();
      }
      r.process(B), ce.clear();
    }
  } finally {
    _t = !1, et(e), Tt = null;
  }
}
function Vn() {
  try {
    Nn();
  } catch (e) {
    Ce(e, Tt);
  }
}
let V = null;
function Ot(e) {
  var t = e.length;
  if (t !== 0) {
    for (var n = 0; n < t; ) {
      var r = e[n++];
      if ((r.f & (oe | ue)) === 0 && $e(r) && (V = /* @__PURE__ */ new Set(), Ye(r), r.deps === null && r.first === null && r.nodes === null && (r.teardown === null && r.ac === null ? ln(r) : r.fn = null), (V == null ? void 0 : V.size) > 0)) {
        ce.clear();
        for (const s of V) {
          if ((s.f & (oe | ue)) !== 0) continue;
          const i = [s];
          let a = s.parent;
          for (; a !== null; )
            V.has(a) && (V.delete(a), i.push(a)), a = a.parent;
          for (let o = i.length - 1; o >= 0; o--) {
            const l = i[o];
            (l.f & (oe | ue)) === 0 && Ye(l);
          }
        }
        V.clear();
      }
    }
    V = null;
  }
}
function zt(e, t, n, r) {
  if (!n.has(e) && (n.add(e), e.reactions !== null))
    for (const s of e.reactions) {
      const i = s.f;
      (i & S) !== 0 ? zt(
        /** @type {Derived} */
        s,
        t,
        n,
        r
      ) : (i & (bt | ie)) !== 0 && (i & L) === 0 && Gt(s, t, r) && (F(s, L), Pe(
        /** @type {Effect} */
        s
      ));
    }
}
function Gt(e, t, n) {
  const r = n.get(e);
  if (r !== void 0) return r;
  if (e.deps !== null)
    for (const s of e.deps) {
      if (t.includes(s))
        return !0;
      if ((s.f & S) !== 0 && Gt(
        /** @type {Derived} */
        s,
        t,
        n
      ))
        return n.set(
          /** @type {Derived} */
          s,
          !0
        ), !0;
    }
  return n.set(e, !1), !1;
}
function Pe(e) {
  for (var t = Tt = e; t.parent !== null; ) {
    t = t.parent;
    var n = t.f;
    if (_t && t === m && (n & ie) !== 0 && (n & Vt) === 0)
      return;
    if ((n & (Te | xe)) !== 0) {
      if ((n & R) === 0) return;
      t.f ^= R;
    }
  }
  B.push(t);
}
function Un(e) {
  let t = 0, n = st(0), r;
  return () => {
    Ve() && (H(n), sr(() => (t === 0 && (r = hr(() => e(() => Le(n)))), t += 1, () => {
      xt(() => {
        t -= 1, t === 0 && (r == null || r(), r = void 0, Le(n));
      });
    })));
  };
}
var Yn = Je | Ie | it;
function Bn(e, t, n) {
  new Kn(e, t, n);
}
var M, q, yt, K, ge, $, j, D, z, ne, se, we, fe, me, le, rt, x, $n, zn, dt, He, We, pt;
class Kn {
  /**
   * @param {TemplateNode} node
   * @param {BoundaryProps} props
   * @param {((anchor: Node) => void)} children
   */
  constructor(t, n, r) {
    g(this, x);
    /** @type {Boundary | null} */
    X(this, "parent");
    g(this, M, !1);
    /** @type {TemplateNode} */
    g(this, q);
    /** @type {TemplateNode | null} */
    g(this, yt, null);
    /** @type {BoundaryProps} */
    g(this, K);
    /** @type {((anchor: Node) => void)} */
    g(this, ge);
    /** @type {Effect} */
    g(this, $);
    /** @type {Effect | null} */
    g(this, j, null);
    /** @type {Effect | null} */
    g(this, D, null);
    /** @type {Effect | null} */
    g(this, z, null);
    /** @type {DocumentFragment | null} */
    g(this, ne, null);
    /** @type {TemplateNode | null} */
    g(this, se, null);
    g(this, we, 0);
    g(this, fe, 0);
    g(this, me, !1);
    /**
     * A source containing the number of pending async deriveds/expressions.
     * Only created if `$effect.pending()` is used inside the boundary,
     * otherwise updating the source results in needless `Batch.ensure()`
     * calls followed by no-op flushes
     * @type {Source<number> | null}
     */
    g(this, le, null);
    g(this, rt, Un(() => (d(this, le, st(f(this, we))), () => {
      d(this, le, null);
    })));
    d(this, q, t), d(this, K, n), d(this, ge, r), this.parent = /** @type {Effect} */
    m.b, d(this, M, !!f(this, K).pending), d(this, $, lr(() => {
      m.b = this;
      {
        var s = E(this, x, dt).call(this);
        try {
          d(this, j, he(() => r(s)));
        } catch (i) {
          this.error(i);
        }
        f(this, fe) > 0 ? E(this, x, We).call(this) : d(this, M, !1);
      }
      return () => {
        var i;
        (i = f(this, se)) == null || i.remove();
      };
    }, Yn));
  }
  /**
   * Returns `true` if the effect exists inside a boundary whose pending snippet is shown
   * @returns {boolean}
   */
  is_pending() {
    return f(this, M) || !!this.parent && this.parent.is_pending();
  }
  has_pending_snippet() {
    return !!f(this, K).pending;
  }
  /**
   * Update the source that powers `$effect.pending()` inside this boundary,
   * and controls when the current `pending` snippet (if any) is removed.
   * Do not call from inside the class
   * @param {1 | -1} d
   */
  update_pending_count(t) {
    E(this, x, pt).call(this, t), d(this, we, f(this, we) + t), f(this, le) && Xe(f(this, le), f(this, we));
  }
  get_effect_pending() {
    return f(this, rt).call(this), H(
      /** @type {Source<number>} */
      f(this, le)
    );
  }
  /** @param {unknown} error */
  error(t) {
    var n = f(this, K).onerror;
    let r = f(this, K).failed;
    if (f(this, me) || !n && !r)
      throw t;
    f(this, j) && (W(f(this, j)), d(this, j, null)), f(this, D) && (W(f(this, D)), d(this, D, null)), f(this, z) && (W(f(this, z)), d(this, z, null));
    var s = !1, i = !1;
    const a = () => {
      if (s) {
        In();
        return;
      }
      s = !0, i && Cn(), re.ensure(), d(this, we, 0), f(this, z) !== null && Ze(f(this, z), () => {
        d(this, z, null);
      }), d(this, M, this.has_pending_snippet()), d(this, j, E(this, x, He).call(this, () => (d(this, me, !1), he(() => f(this, ge).call(this, f(this, q)))))), f(this, fe) > 0 ? E(this, x, We).call(this) : d(this, M, !1);
    };
    var o = p;
    try {
      C(null), i = !0, n == null || n(t, a), i = !1;
    } catch (l) {
      Ce(l, f(this, $) && f(this, $).parent);
    } finally {
      C(o);
    }
    r && xt(() => {
      d(this, z, E(this, x, He).call(this, () => {
        re.ensure(), d(this, me, !0);
        try {
          return he(() => {
            r(
              f(this, q),
              () => t,
              () => a
            );
          });
        } catch (l) {
          return Ce(
            l,
            /** @type {Effect} */
            f(this, $).parent
          ), null;
        } finally {
          d(this, me, !1);
        }
      }));
    });
  }
}
M = new WeakMap(), q = new WeakMap(), yt = new WeakMap(), K = new WeakMap(), ge = new WeakMap(), $ = new WeakMap(), j = new WeakMap(), D = new WeakMap(), z = new WeakMap(), ne = new WeakMap(), se = new WeakMap(), we = new WeakMap(), fe = new WeakMap(), me = new WeakMap(), le = new WeakMap(), rt = new WeakMap(), x = new WeakSet(), $n = function() {
  try {
    d(this, j, he(() => f(this, ge).call(this, f(this, q))));
  } catch (t) {
    this.error(t);
  }
  d(this, M, !1);
}, zn = function() {
  const t = f(this, K).pending;
  t && (d(this, D, he(() => t(f(this, q)))), re.enqueue(() => {
    var n = E(this, x, dt).call(this);
    d(this, j, E(this, x, He).call(this, () => (re.ensure(), he(() => f(this, ge).call(this, n))))), f(this, fe) > 0 ? E(this, x, We).call(this) : (Ze(
      /** @type {Effect} */
      f(this, D),
      () => {
        d(this, D, null);
      }
    ), d(this, M, !1));
  }));
}, dt = function() {
  var t = f(this, q);
  return f(this, M) && (d(this, se, tn()), f(this, q).before(f(this, se)), t = f(this, se)), t;
}, /**
 * @param {() => Effect | null} fn
 */
He = function(t) {
  var n = m, r = p, s = Y;
  J(f(this, $)), C(f(this, $)), De(f(this, $).ctx);
  try {
    return t();
  } catch (i) {
    return Bt(i), null;
  } finally {
    J(n), C(r), De(s);
  }
}, We = function() {
  const t = (
    /** @type {(anchor: Node) => void} */
    f(this, K).pending
  );
  f(this, j) !== null && (d(this, ne, document.createDocumentFragment()), f(this, ne).append(
    /** @type {TemplateNode} */
    f(this, se)
  ), ar(f(this, j), f(this, ne))), f(this, D) === null && d(this, D, he(() => t(f(this, q))));
}, /**
 * Updates the pending count associated with the currently visible pending snippet,
 * if any, such that we can replace the snippet with content once work is done
 * @param {1 | -1} d
 */
pt = function(t) {
  var n;
  if (!this.has_pending_snippet()) {
    this.parent && E(n = this.parent, x, pt).call(n, t);
    return;
  }
  d(this, fe, f(this, fe) + t), f(this, fe) === 0 && (d(this, M, !1), f(this, D) && Ze(f(this, D), () => {
    d(this, D, null);
  }), f(this, ne) && (f(this, q).before(f(this, ne)), d(this, ne, null)));
};
function Gn(e, t, n, r) {
  const s = Wn;
  if (n.length === 0 && e.length === 0) {
    r(t.map(s));
    return;
  }
  var i = w, a = (
    /** @type {Effect} */
    m
  ), o = Hn();
  function l() {
    Promise.all(n.map((u) => /* @__PURE__ */ Zn(u))).then((u) => {
      o();
      try {
        r([...t.map(s), ...u]);
      } catch (_) {
        (a.f & oe) === 0 && Ce(_, a);
      }
      i == null || i.deactivate(), Qe();
    }).catch((u) => {
      Ce(u, a);
    });
  }
  e.length > 0 ? Promise.all(e).then(() => {
    o();
    try {
      return l();
    } finally {
      i == null || i.deactivate(), Qe();
    }
  }) : l();
}
function Hn() {
  var e = m, t = p, n = Y, r = w;
  return function(i = !0) {
    J(e), C(t), De(n), i && (r == null || r.activate());
  };
}
function Qe() {
  J(null), C(null), De(null);
}
// @__NO_SIDE_EFFECTS__
function Wn(e) {
  var t = S | L, n = p !== null && (p.f & S) !== 0 ? (
    /** @type {Derived} */
    p
  ) : null;
  return m !== null && (m.f |= Ie), {
    ctx: Y,
    deps: null,
    effects: null,
    equals: Ut,
    f: t,
    fn: e,
    reactions: null,
    rv: 0,
    v: (
      /** @type {V} */
      k
    ),
    wv: 0,
    parent: n ?? m,
    ac: null
  };
}
// @__NO_SIDE_EFFECTS__
function Zn(e, t) {
  let n = (
    /** @type {Effect | null} */
    m
  );
  n === null && Rn();
  var r = (
    /** @type {Boundary} */
    n.b
  ), s = (
    /** @type {Promise<V>} */
    /** @type {unknown} */
    void 0
  ), i = st(
    /** @type {V} */
    k
  ), a = !p, o = /* @__PURE__ */ new Map();
  return ir(() => {
    var v;
    var l = qt();
    s = l.promise;
    try {
      Promise.resolve(e()).then(l.resolve, l.reject).then(() => {
        u === w && u.committed && u.deactivate(), Qe();
      });
    } catch (c) {
      l.reject(c), Qe();
    }
    var u = (
      /** @type {Batch} */
      w
    );
    if (a) {
      var _ = !r.is_pending();
      r.update_pending_count(1), u.increment(_), (v = o.get(u)) == null || v.reject(ke), o.delete(u), o.set(u, l);
    }
    const h = (c, y = void 0) => {
      if (u.activate(), y)
        y !== ke && (i.f |= ae, Xe(i, y));
      else {
        (i.f & ae) !== 0 && (i.f ^= ae), Xe(i, c);
        for (const [O, Q] of o) {
          if (o.delete(O), O === u) break;
          Q.reject(ke);
        }
      }
      a && (r.update_pending_count(-1), u.decrement(_));
    };
    l.promise.then(h, (c) => h(null, c || "unknown"));
  }), tr(() => {
    for (const l of o.values())
      l.reject(ke);
  }), new Promise((l) => {
    function u(_) {
      function h() {
        _ === s ? l(i) : u(s);
      }
      _.then(h, h);
    }
    u(s);
  });
}
function Ht(e) {
  var t = e.effects;
  if (t !== null) {
    e.effects = null;
    for (var n = 0; n < t.length; n += 1)
      W(
        /** @type {Effect} */
        t[n]
      );
  }
}
function Jn(e) {
  for (var t = e.parent; t !== null; ) {
    if ((t.f & S) === 0)
      return (t.f & oe) === 0 ? (
        /** @type {Effect} */
        t
      ) : null;
    t = t.parent;
  }
  return null;
}
function St(e) {
  var t, n = m;
  J(Jn(e));
  try {
    e.f &= ~be, Ht(e), t = _n(e);
  } finally {
    J(n);
  }
  return t;
}
function Wt(e) {
  var t = St(e);
  if (e.equals(t) || (w != null && w.is_fork || (e.v = t), e.wv = an()), !Ke)
    if (b !== null)
      (Ve() || w != null && w.is_fork) && b.set(e, t);
    else {
      var n = (e.f & U) === 0 ? Z : R;
      F(e, n);
    }
}
let gt = /* @__PURE__ */ new Set();
const ce = /* @__PURE__ */ new Map();
let Zt = !1;
function st(e, t) {
  var n = {
    f: 0,
    // TODO ideally we could skip this altogether, but it causes type errors
    v: e,
    reactions: null,
    equals: Ut,
    rv: 0,
    wv: 0
  };
  return n;
}
// @__NO_SIDE_EFFECTS__
function ee(e, t) {
  const n = st(e);
  return cr(n), n;
}
function te(e, t, n = !1) {
  p !== null && // since we are untracking the function inside `$inspect.with` we need to add this check
  // to ensure we error if state is set inside an inspect effect
  (!G || (p.f & Ft) !== 0) && Yt() && (p.f & (S | ie | bt | Ft)) !== 0 && !(N != null && N.includes(e)) && Dn();
  let r = n ? qe(t) : t;
  return Xe(e, r);
}
function Xe(e, t) {
  if (!e.equals(t)) {
    var n = e.v;
    Ke ? ce.set(e, t) : ce.set(e, n), e.v = t;
    var r = re.ensure();
    r.capture(e, n), (e.f & S) !== 0 && ((e.f & L) !== 0 && St(
      /** @type {Derived} */
      e
    ), F(e, (e.f & U) !== 0 ? R : Z)), e.wv = an(), Jt(e, L), m !== null && (m.f & R) !== 0 && (m.f & (xe | Te)) === 0 && (I === null ? _r([e]) : I.push(e)), !r.is_fork && gt.size > 0 && !Zt && Qn();
  }
  return t;
}
function Qn() {
  Zt = !1;
  var e = ye;
  et(!0);
  const t = Array.from(gt);
  try {
    for (const n of t)
      (n.f & R) !== 0 && F(n, Z), $e(n) && Ye(n);
  } finally {
    et(e);
  }
  gt.clear();
}
function Le(e) {
  te(e, e.v + 1);
}
function Jt(e, t) {
  var n = e.reactions;
  if (n !== null)
    for (var r = n.length, s = 0; s < r; s++) {
      var i = n[s], a = i.f, o = (a & L) === 0;
      if (o && F(i, t), (a & S) !== 0) {
        var l = (
          /** @type {Derived} */
          i
        );
        b == null || b.delete(l), (a & be) === 0 && (a & U && (i.f |= be), Jt(l, Z));
      } else o && ((a & ie) !== 0 && V !== null && V.add(
        /** @type {Effect} */
        i
      ), Pe(
        /** @type {Effect} */
        i
      ));
    }
}
function qe(e) {
  if (typeof e != "object" || e === null || ut in e)
    return e;
  const t = Tn(e);
  if (t !== bn && t !== xn)
    return e;
  var n = /* @__PURE__ */ new Map(), r = wn(e), s = /* @__PURE__ */ ee(0), i = Ee, a = (o) => {
    if (Ee === i)
      return o();
    var l = p, u = Ee;
    C(null), Pt(i);
    var _ = o();
    return C(l), Pt(u), _;
  };
  return r && n.set("length", /* @__PURE__ */ ee(
    /** @type {any[]} */
    e.length
  )), new Proxy(
    /** @type {any} */
    e,
    {
      defineProperty(o, l, u) {
        (!("value" in u) || u.configurable === !1 || u.enumerable === !1 || u.writable === !1) && Fn();
        var _ = n.get(l);
        return _ === void 0 ? _ = a(() => {
          var h = /* @__PURE__ */ ee(u.value);
          return n.set(l, h), h;
        }) : te(_, u.value, !0), !0;
      },
      deleteProperty(o, l) {
        var u = n.get(l);
        if (u === void 0) {
          if (l in o) {
            const _ = a(() => /* @__PURE__ */ ee(k));
            n.set(l, _), Le(s);
          }
        } else
          te(u, k), Le(s);
        return !0;
      },
      get(o, l, u) {
        var c;
        if (l === ut)
          return e;
        var _ = n.get(l), h = l in o;
        if (_ === void 0 && (!h || (c = je(o, l)) != null && c.writable) && (_ = a(() => {
          var y = qe(h ? o[l] : k), O = /* @__PURE__ */ ee(y);
          return O;
        }), n.set(l, _)), _ !== void 0) {
          var v = H(_);
          return v === k ? void 0 : v;
        }
        return Reflect.get(o, l, u);
      },
      getOwnPropertyDescriptor(o, l) {
        var u = Reflect.getOwnPropertyDescriptor(o, l);
        if (u && "value" in u) {
          var _ = n.get(l);
          _ && (u.value = H(_));
        } else if (u === void 0) {
          var h = n.get(l), v = h == null ? void 0 : h.v;
          if (h !== void 0 && v !== k)
            return {
              enumerable: !0,
              configurable: !0,
              value: v,
              writable: !0
            };
        }
        return u;
      },
      has(o, l) {
        var v;
        if (l === ut)
          return !0;
        var u = n.get(l), _ = u !== void 0 && u.v !== k || Reflect.has(o, l);
        if (u !== void 0 || m !== null && (!_ || (v = je(o, l)) != null && v.writable)) {
          u === void 0 && (u = a(() => {
            var c = _ ? qe(o[l]) : k, y = /* @__PURE__ */ ee(c);
            return y;
          }), n.set(l, u));
          var h = H(u);
          if (h === k)
            return !1;
        }
        return _;
      },
      set(o, l, u, _) {
        var At;
        var h = n.get(l), v = l in o;
        if (r && l === "length")
          for (var c = u; c < /** @type {Source<number>} */
          h.v; c += 1) {
            var y = n.get(c + "");
            y !== void 0 ? te(y, k) : c in o && (y = a(() => /* @__PURE__ */ ee(k)), n.set(c + "", y));
          }
        if (h === void 0)
          (!v || (At = je(o, l)) != null && At.writable) && (h = a(() => /* @__PURE__ */ ee(void 0)), te(h, qe(u)), n.set(l, h));
        else {
          v = h.v !== k;
          var O = a(() => qe(u));
          te(h, O);
        }
        var Q = Reflect.getOwnPropertyDescriptor(o, l);
        if (Q != null && Q.set && Q.set.call(_, u), !v) {
          if (r && typeof l == "string") {
            var ve = (
              /** @type {Source<number>} */
              n.get("length")
            ), ft = Number(l);
            Number.isInteger(ft) && ft >= ve.v && te(ve, ft + 1);
          }
          Le(s);
        }
        return !0;
      },
      ownKeys(o) {
        H(s);
        var l = Reflect.ownKeys(o).filter((h) => {
          var v = n.get(h);
          return v === void 0 || v.v !== k;
        });
        for (var [u, _] of n)
          _.v !== k && !(u in o) && l.push(u);
        return l;
      },
      setPrototypeOf() {
        On();
      }
    }
  );
}
var Dt, Qt, Xt, en;
function Xn() {
  if (Dt === void 0) {
    Dt = window, Qt = /Firefox/.test(navigator.userAgent);
    var e = Element.prototype, t = Node.prototype, n = Text.prototype;
    Xt = je(t, "firstChild").get, en = je(t, "nextSibling").get, Nt(e) && (e.__click = void 0, e.__className = void 0, e.__attributes = null, e.__style = void 0, e.__e = void 0), Nt(n) && (n.__t = void 0);
  }
}
function tn(e = "") {
  return document.createTextNode(e);
}
// @__NO_SIDE_EFFECTS__
function nn(e) {
  return (
    /** @type {TemplateNode | null} */
    Xt.call(e)
  );
}
// @__NO_SIDE_EFFECTS__
function kt(e) {
  return (
    /** @type {TemplateNode | null} */
    en.call(e)
  );
}
function ot(e, t) {
  return /* @__PURE__ */ nn(e);
}
function at(e, t = 1, n = !1) {
  let r = e;
  for (; t--; )
    r = /** @type {TemplateNode} */
    /* @__PURE__ */ kt(r);
  return r;
}
function rn(e) {
  var t = p, n = m;
  C(null), J(null);
  try {
    return e();
  } finally {
    C(t), J(n);
  }
}
function er(e, t) {
  var n = t.last;
  n === null ? t.last = t.first = e : (n.next = e, e.prev = n, t.last = e);
}
function _e(e, t, n) {
  var r = m;
  r !== null && (r.f & ue) !== 0 && (e |= ue);
  var s = {
    ctx: Y,
    deps: null,
    nodes: null,
    f: e | L | U,
    first: null,
    fn: t,
    last: null,
    next: null,
    parent: r,
    b: r && r.b,
    prev: null,
    teardown: null,
    wv: 0,
    ac: null
  };
  if (n)
    try {
      Ye(s), s.f |= Lt;
    } catch (o) {
      throw W(s), o;
    }
  else t !== null && Pe(s);
  var i = s;
  if (n && i.deps === null && i.teardown === null && i.nodes === null && i.first === i.last && // either `null`, or a singular child
  (i.f & Ie) === 0 && (i = i.first, (e & ie) !== 0 && (e & Je) !== 0 && i !== null && (i.f |= Je)), i !== null && (i.parent = r, r !== null && er(i, r), p !== null && (p.f & S) !== 0 && (e & Te) === 0)) {
    var a = (
      /** @type {Derived} */
      p
    );
    (a.effects ?? (a.effects = [])).push(i);
  }
  return s;
}
function Ve() {
  return p !== null && !G;
}
function tr(e) {
  const t = _e(Et, null, !1);
  return F(t, R), t.teardown = e, t;
}
function nr(e) {
  return _e(jt | An, e, !1);
}
function rr(e) {
  re.ensure();
  const t = _e(Te | Ie, e, !0);
  return (n = {}) => new Promise((r) => {
    n.outro ? Ze(t, () => {
      W(t), r(void 0);
    }) : (W(t), r(void 0));
  });
}
function ir(e) {
  return _e(bt | Ie, e, !0);
}
function sr(e, t = 0) {
  return _e(Et | t, e, !0);
}
function fr(e, t = [], n = [], r = []) {
  Gn(r, t, n, (s) => {
    _e(Et, () => e(...s.map(H)), !0);
  });
}
function lr(e, t = 0) {
  var n = _e(ie | t, e, !0);
  return n;
}
function he(e) {
  return _e(xe | Ie, e, !0);
}
function sn(e) {
  var t = e.teardown;
  if (t !== null) {
    const n = Ke, r = p;
    Ct(!0), C(null);
    try {
      t.call(null);
    } finally {
      Ct(n), C(r);
    }
  }
}
function fn(e, t = !1) {
  var n = e.first;
  for (e.first = e.last = null; n !== null; ) {
    const s = n.ac;
    s !== null && rn(() => {
      s.abort(ke);
    });
    var r = n.next;
    (n.f & Te) !== 0 ? n.parent = null : W(n, t), n = r;
  }
}
function ur(e) {
  for (var t = e.first; t !== null; ) {
    var n = t.next;
    (t.f & xe) === 0 && W(t), t = n;
  }
}
function W(e, t = !0) {
  var n = !1;
  (t || (e.f & Vt) !== 0) && e.nodes !== null && e.nodes.end !== null && (or(
    e.nodes.start,
    /** @type {TemplateNode} */
    e.nodes.end
  ), n = !0), fn(e, t && !n), tt(e, 0), F(e, oe);
  var r = e.nodes && e.nodes.t;
  if (r !== null)
    for (const i of r)
      i.stop();
  sn(e);
  var s = e.parent;
  s !== null && s.first !== null && ln(e), e.next = e.prev = e.teardown = e.ctx = e.deps = e.fn = e.nodes = e.ac = null;
}
function or(e, t) {
  for (; e !== null; ) {
    var n = e === t ? null : /* @__PURE__ */ kt(e);
    e.remove(), e = n;
  }
}
function ln(e) {
  var t = e.parent, n = e.prev, r = e.next;
  n !== null && (n.next = r), r !== null && (r.prev = n), t !== null && (t.first === e && (t.first = r), t.last === e && (t.last = n));
}
function Ze(e, t, n = !0) {
  var r = [];
  un(e, r, !0);
  var s = () => {
    n && W(e), t && t();
  }, i = r.length;
  if (i > 0) {
    var a = () => --i || s();
    for (var o of r)
      o.out(a);
  } else
    s();
}
function un(e, t, n) {
  if ((e.f & ue) === 0) {
    e.f ^= ue;
    var r = e.nodes && e.nodes.t;
    if (r !== null)
      for (const o of r)
        (o.is_global || n) && t.push(o);
    for (var s = e.first; s !== null; ) {
      var i = s.next, a = (s.f & Je) !== 0 || // If this is a branch effect without a block effect parent,
      // it means the parent block effect was pruned. In that case,
      // transparency information was transferred to the branch effect.
      (s.f & xe) !== 0 && (e.f & ie) !== 0;
      un(s, t, a ? n : !1), s = i;
    }
  }
}
function ar(e, t) {
  if (e.nodes)
    for (var n = e.nodes.start, r = e.nodes.end; n !== null; ) {
      var s = n === r ? null : /* @__PURE__ */ kt(n);
      t.append(n), n = s;
    }
}
let ye = !1;
function et(e) {
  ye = e;
}
let Ke = !1;
function Ct(e) {
  Ke = e;
}
let p = null, G = !1;
function C(e) {
  p = e;
}
let m = null;
function J(e) {
  m = e;
}
let N = null;
function cr(e) {
  p !== null && (N === null ? N = [e] : N.push(e));
}
let A = null, P = 0, I = null;
function _r(e) {
  I = e;
}
let on = 1, Ue = 0, Ee = Ue;
function Pt(e) {
  Ee = e;
}
function an() {
  return ++on;
}
function $e(e) {
  var t = e.f;
  if ((t & L) !== 0)
    return !0;
  if (t & S && (e.f &= ~be), (t & Z) !== 0) {
    var n = e.deps;
    if (n !== null)
      for (var r = n.length, s = 0; s < r; s++) {
        var i = n[s];
        if ($e(
          /** @type {Derived} */
          i
        ) && Wt(
          /** @type {Derived} */
          i
        ), i.wv > e.wv)
          return !0;
      }
    (t & U) !== 0 && // During time traveling we don't want to reset the status so that
    // traversal of the graph in the other batches still happens
    b === null && F(e, R);
  }
  return !1;
}
function cn(e, t, n = !0) {
  var r = e.reactions;
  if (r !== null && !(N != null && N.includes(e)))
    for (var s = 0; s < r.length; s++) {
      var i = r[s];
      (i.f & S) !== 0 ? cn(
        /** @type {Derived} */
        i,
        t,
        !1
      ) : t === i && (n ? F(i, L) : (i.f & R) !== 0 && F(i, Z), Pe(
        /** @type {Effect} */
        i
      ));
    }
}
function _n(e) {
  var y;
  var t = A, n = P, r = I, s = p, i = N, a = Y, o = G, l = Ee, u = e.f;
  A = /** @type {null | Value[]} */
  null, P = 0, I = null, p = (u & (xe | Te)) === 0 ? e : null, N = null, De(e.ctx), G = !1, Ee = ++Ue, e.ac !== null && (rn(() => {
    e.ac.abort(ke);
  }), e.ac = null);
  try {
    e.f |= ct;
    var _ = (
      /** @type {Function} */
      e.fn
    ), h = _(), v = e.deps;
    if (A !== null) {
      var c;
      if (tt(e, P), v !== null && P > 0)
        for (v.length = P + A.length, c = 0; c < A.length; c++)
          v[P + c] = A[c];
      else
        e.deps = v = A;
      if (Ve() && (e.f & U) !== 0)
        for (c = P; c < v.length; c++)
          ((y = v[c]).reactions ?? (y.reactions = [])).push(e);
    } else v !== null && P < v.length && (tt(e, P), v.length = P);
    if (Yt() && I !== null && !G && v !== null && (e.f & (S | Z | L)) === 0)
      for (c = 0; c < /** @type {Source[]} */
      I.length; c++)
        cn(
          I[c],
          /** @type {Effect} */
          e
        );
    return s !== null && s !== e && (Ue++, I !== null && (r === null ? r = I : r.push(.../** @type {Source[]} */
    I))), (e.f & ae) !== 0 && (e.f ^= ae), h;
  } catch (O) {
    return Bt(O);
  } finally {
    e.f ^= ct, A = t, P = n, I = r, p = s, N = i, De(a), G = o, Ee = l;
  }
}
function vr(e, t) {
  let n = t.reactions;
  if (n !== null) {
    var r = mn.call(n, e);
    if (r !== -1) {
      var s = n.length - 1;
      s === 0 ? n = t.reactions = null : (n[r] = n[s], n.pop());
    }
  }
  n === null && (t.f & S) !== 0 && // Destroying a child effect while updating a parent effect can cause a dependency to appear
  // to be unused, when in fact it is used by the currently-updating parent. Checking `new_deps`
  // allows us to skip the expensive work of disconnecting and immediately reconnecting it
  (A === null || !A.includes(t)) && (F(t, Z), (t.f & U) !== 0 && (t.f ^= U, t.f &= ~be), Ht(
    /** @type {Derived} **/
    t
  ), tt(
    /** @type {Derived} **/
    t,
    0
  ));
}
function tt(e, t) {
  var n = e.deps;
  if (n !== null)
    for (var r = t; r < n.length; r++)
      vr(e, n[r]);
}
function Ye(e) {
  var t = e.f;
  if ((t & oe) === 0) {
    F(e, R);
    var n = m, r = ye;
    m = e, ye = !0;
    try {
      (t & (ie | kn)) !== 0 ? ur(e) : fn(e), sn(e);
      var s = _n(e);
      e.teardown = typeof s == "function" ? s : null, e.wv = on;
      var i;
    } finally {
      ye = r, m = n;
    }
  }
}
function H(e) {
  var t = e.f, n = (t & S) !== 0;
  if (p !== null && !G) {
    var r = m !== null && (m.f & oe) !== 0;
    if (!r && !(N != null && N.includes(e))) {
      var s = p.deps;
      if ((p.f & ct) !== 0)
        e.rv < Ue && (e.rv = Ue, A === null && s !== null && s[P] === e ? P++ : A === null ? A = [e] : A.includes(e) || A.push(e));
      else {
        (p.deps ?? (p.deps = [])).push(e);
        var i = e.reactions;
        i === null ? e.reactions = [p] : i.includes(p) || i.push(p);
      }
    }
  }
  if (Ke) {
    if (ce.has(e))
      return ce.get(e);
    if (n) {
      var a = (
        /** @type {Derived} */
        e
      ), o = a.v;
      return ((a.f & R) === 0 && a.reactions !== null || hn(a)) && (o = St(a)), ce.set(a, o), o;
    }
  } else n && (!(b != null && b.has(e)) || w != null && w.is_fork && !Ve()) && (a = /** @type {Derived} */
  e, $e(a) && Wt(a), ye && Ve() && (a.f & U) === 0 && vn(a));
  if (b != null && b.has(e))
    return b.get(e);
  if ((e.f & ae) !== 0)
    throw e.v;
  return e.v;
}
function vn(e) {
  if (e.deps !== null) {
    e.f ^= U;
    for (const t of e.deps)
      (t.reactions ?? (t.reactions = [])).push(e), (t.f & S) !== 0 && (t.f & U) === 0 && vn(
        /** @type {Derived} */
        t
      );
  }
}
function hn(e) {
  if (e.v === k) return !0;
  if (e.deps === null) return !1;
  for (const t of e.deps)
    if (ce.has(t) || (t.f & S) !== 0 && hn(
      /** @type {Derived} */
      t
    ))
      return !0;
  return !1;
}
function hr(e) {
  var t = G;
  try {
    return G = !0, e();
  } finally {
    G = t;
  }
}
const dr = -7169;
function F(e, t) {
  e.f = e.f & dr | t;
}
const pr = ["touchstart", "touchmove"];
function gr(e) {
  return pr.includes(e);
}
const dn = /* @__PURE__ */ new Set(), wt = /* @__PURE__ */ new Set();
function wr(e) {
  for (var t = 0; t < e.length; t++)
    dn.add(e[t]);
  for (var n of wt)
    n(e);
}
let It = null;
function Ge(e) {
  var Q;
  var t = this, n = (
    /** @type {Node} */
    t.ownerDocument
  ), r = e.type, s = ((Q = e.composedPath) == null ? void 0 : Q.call(e)) || [], i = (
    /** @type {null | Element} */
    s[0] || e.target
  );
  It = e;
  var a = 0, o = It === e && e.__root;
  if (o) {
    var l = s.indexOf(o);
    if (l !== -1 && (t === document || t === /** @type {any} */
    window)) {
      e.__root = t;
      return;
    }
    var u = s.indexOf(t);
    if (u === -1)
      return;
    l <= u && (a = l);
  }
  if (i = /** @type {Element} */
  s[a] || e.target, i !== t) {
    En(e, "currentTarget", {
      configurable: !0,
      get() {
        return i || n;
      }
    });
    var _ = p, h = m;
    C(null), J(null);
    try {
      for (var v, c = []; i !== null; ) {
        var y = i.assignedSlot || i.parentNode || /** @type {any} */
        i.host || null;
        try {
          var O = i["__" + r];
          O != null && (!/** @type {any} */
          i.disabled || // DOM could've been updated already by the time this is reached, so we check this as well
          // -> the target could not have been disabled because it emits the event in the first place
          e.target === i) && O.call(i, e);
        } catch (ve) {
          v ? c.push(ve) : v = ve;
        }
        if (e.cancelBubble || y === t || y === null)
          break;
        i = y;
      }
      if (v) {
        for (let ve of c)
          queueMicrotask(() => {
            throw ve;
          });
        throw v;
      }
    } finally {
      e.__root = t, delete e.currentTarget, C(_), J(h);
    }
  }
}
function mr(e) {
  var t = document.createElement("template");
  return t.innerHTML = e.replaceAll("<!>", "<!---->"), t.content;
}
function yr(e, t) {
  var n = (
    /** @type {Effect} */
    m
  );
  n.nodes === null && (n.nodes = { start: e, end: t, a: null, t: null });
}
// @__NO_SIDE_EFFECTS__
function Er(e, t) {
  var n = (t & Pn) !== 0, r, s = !e.startsWith("<!>");
  return () => {
    r === void 0 && (r = mr(s ? e : "<!>" + e), r = /** @type {TemplateNode} */
    /* @__PURE__ */ nn(r));
    var i = (
      /** @type {TemplateNode} */
      n || Qt ? document.importNode(r, !0) : r.cloneNode(!0)
    );
    return yr(i, i), i;
  };
}
function br(e, t) {
  e !== null && e.before(
    /** @type {Node} */
    t
  );
}
function xr(e, t) {
  var n = t == null ? "" : typeof t == "object" ? t + "" : t;
  n !== (e.__t ?? (e.__t = e.nodeValue)) && (e.__t = n, e.nodeValue = n + "");
}
function Tr(e, t) {
  return Sr(e, t);
}
const Se = /* @__PURE__ */ new Map();
function Sr(e, { target: t, anchor: n, props: r = {}, events: s, context: i, intro: a = !0 }) {
  Xn();
  var o = /* @__PURE__ */ new Set(), l = (h) => {
    for (var v = 0; v < h.length; v++) {
      var c = h[v];
      if (!o.has(c)) {
        o.add(c);
        var y = gr(c);
        t.addEventListener(c, Ge, { passive: y });
        var O = Se.get(c);
        O === void 0 ? (document.addEventListener(c, Ge, { passive: y }), Se.set(c, 1)) : Se.set(c, O + 1);
      }
    }
  };
  l(yn(dn)), wt.add(l);
  var u = void 0, _ = rr(() => {
    var h = n ?? t.appendChild(tn());
    return Bn(
      /** @type {TemplateNode} */
      h,
      {
        pending: () => {
        }
      },
      (v) => {
        if (i) {
          Mn({});
          var c = (
            /** @type {ComponentContext} */
            Y
          );
          c.c = i;
        }
        s && (r.$$events = s), u = e(v, r) || {}, i && qn();
      }
    ), () => {
      var y;
      for (var v of o) {
        t.removeEventListener(v, Ge);
        var c = (
          /** @type {number} */
          Se.get(v)
        );
        --c === 0 ? (document.removeEventListener(v, Ge), Se.delete(v)) : Se.set(v, c);
      }
      wt.delete(l), h !== n && ((y = h.parentNode) == null || y.removeChild(h));
    };
  });
  return mt.set(u, _), u;
}
let mt = /* @__PURE__ */ new WeakMap();
function kr(e, t) {
  const n = mt.get(e);
  return n ? (mt.delete(e), n(t)) : Promise.resolve();
}
const Ar = "5";
var Mt;
typeof window < "u" && ((Mt = window.__svelte ?? (window.__svelte = {})).v ?? (Mt.v = /* @__PURE__ */ new Set())).add(Ar);
var Rr = /* @__PURE__ */ Er('<div class="svelte-app svelte-1n46o8q"><h2 class="svelte-1n46o8q">Svelte Component</h2> <p>This is a Svelte app integrated with OroPlatform!</p> <div class="counter svelte-1n46o8q"><button class="svelte-1n46o8q">-</button> <span class="count svelte-1n46o8q"> </span> <button class="svelte-1n46o8q">+</button></div></div>');
function Nr(e) {
  let t = /* @__PURE__ */ ee(0);
  function n() {
    te(t, H(t) + 1);
  }
  function r() {
    te(t, H(t) - 1);
  }
  var s = Rr(), i = at(ot(s), 4), a = ot(i);
  a.__click = r;
  var o = at(a, 2), l = ot(o), u = at(o, 2);
  u.__click = n, fr(() => xr(l, H(t))), br(e, s);
}
wr(["click"]);
function Fr(e, t = {}) {
  const n = Tr(Nr, {
    target: e,
    props: t
  });
  return n.$destroy = () => {
    kr(n);
  }, n;
}
const Cr = { mountApp: Fr };
export {
  Cr as default,
  Fr as mountApp
};
