/* ============================================================================
 * Bar Ordering System — demo-mode shim (GitHub Pages / static hosting)
 * 點餐系統試玩模式
 *
 * Why this file exists:
 *   On GitHub Pages there is no PHP, so api.php cannot run. This shim lets
 *   the exact same index.html / admin.html work on a static host with no
 *   other changes: it tries the real api.php first and, only when there is
 *   clearly no PHP backend, answers the same endpoints from a
 *   localStorage-backed mock instead.
 *
 * How the switch is decided (once, on the FIRST api.php call):
 *   - Real fetch fails (network error)           -> demo mode
 *   - Real fetch returns 404 / 405               -> demo mode
 *   - Real fetch returns something that is NOT JSON
 *     (a static host serves api.php as a plain file, so the raw PHP source
 *     comes back with HTTP 200)                   -> demo mode
 *   - Real fetch returns valid JSON              -> real backend confirmed;
 *     every later call goes to api.php untouched, so a working PHP
 *     deployment behaves exactly as before.
 *
 * In demo mode:
 *   - get_menu reads data/menu.json (or an admin-edited copy in localStorage)
 *   - submit_order / get_orders / update_order use localStorage orders
 *     (submit_order validates drink options the same way api.php does:
 *     the chosen option sets the price and is stored on the order item)
 *   - login accepts the documented default PIN 1234 (see config.php)
 *   - admin_save_menu stores the edited menu in localStorage (options
 *     included, validated like api.php)
 *   - a banner says orders stay in this browser; a Reset button clears the
 *     demo data. Nothing is sent to any server.
 * ========================================================================== */
(function () {
  "use strict";

  if (window.__barDemoModeInstalled) return;
  window.__barDemoModeInstalled = true;

  var DEMO_PIN = "1234";
  var LS_ORDERS = "bar-demo-orders-v1";
  var LS_MENU = "bar-demo-menu-v1";
  var LS_AUTH = "bar-demo-auth-v1";
  var ORDER_STATUSES = ["待製作", "製作中", "完成"];
  var CATEGORIES = {
    cocktail: "雞尾酒", draft: "精釀啤酒", whisky: "威士忌",
    special: "基酒特調", zero: "無酒精特飲", sparkling: "氣泡特飲",
    cans: "環球特選啤酒", wine: "紅酒", shisha: "Shisha"
  };

  var origFetch = window.fetch.bind(window);
  var demoActive = false;     // mock is serving api.php calls
  var backendDecided = false; // a real JSON reply proved PHP is present

  /* ---------- storage (localStorage, with in-memory fallback) ---------- */
  var memStore = {};
  function lsGet(k) { try { return window.localStorage.getItem(k); } catch (e) { return memStore.hasOwnProperty(k) ? memStore[k] : null; } }
  function lsSet(k, v) { try { window.localStorage.setItem(k, v); } catch (e) { memStore[k] = v; } }
  function lsDel(k) { try { window.localStorage.removeItem(k); } catch (e) { delete memStore[k]; } }

  /* ---------- small helpers ---------- */
  function pad(n) { return (n < 10 ? "0" : "") + n; }
  function todayStr() { var d = new Date(); return d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate()); }
  function nowHM() { var d = new Date(); return pad(d.getHours()) + ":" + pad(d.getMinutes()); }
  function newOrderId() {
    var hex = "", i;
    try {
      var bytes = new Uint8Array(6);
      (window.crypto || window.msCrypto).getRandomValues(bytes);
      for (i = 0; i < bytes.length; i++) hex += ("0" + bytes[i].toString(16)).slice(-2);
    } catch (e) {
      for (i = 0; i < 12; i++) hex += "0123456789abcdef".charAt(Math.floor(Math.random() * 16));
    }
    return "ord-" + hex;
  }
  function jsonResp(data, status) {
    return new Response(JSON.stringify(data), {
      status: status || 200,
      headers: { "Content-Type": "application/json; charset=utf-8" }
    });
  }
  function okResp(data) { return jsonResp(data, 200); }
  function errResp(msg, status) { return jsonResp({ ok: false, error: msg }, status || 400); }
  function parseAction(url) {
    var m = /[?&]action=([^&#]*)/.exec(url);
    return m ? decodeURIComponent(m[1]) : "";
  }
  function readBody(opts) {
    if (!opts || !opts.body) return {};
    try { var b = JSON.parse(opts.body); return (b && typeof b === "object") ? b : {}; }
    catch (e) { return {}; }
  }
  function seatLabel(spot) {
    if (spot === "bar") return { label: "吧枱", en: "BAR" };
    var m = /^seat([1-6])$/.exec(spot);
    if (m) return { label: m[1] + " 號位", en: "BAR SEAT" };
    m = /^table([1-3])$/.exec(spot);
    if (m) return { label: m[1] + " 號枱", en: "TABLE" };
    return null;
  }
  function isAuthed() { return lsGet(LS_AUTH) === "1"; }

  /* ---------- menu (data/menu.json, or admin-edited copy) ---------- */
  var menuCache = null;
  var menuPromise = null;
  function loadMenuData() {
    if (menuCache) return Promise.resolve(menuCache);
    var saved = lsGet(LS_MENU);
    if (saved) {
      try {
        var m = JSON.parse(saved);
        if (Array.isArray(m) && m.length) { menuCache = m; return Promise.resolve(m); }
      } catch (e) { /* fall through to the file */ }
    }
    if (menuPromise) return menuPromise;
    menuPromise = origFetch("data/menu.json").then(function (r) {
      if (!r.ok) throw new Error("HTTP " + r.status);
      return r.json();
    }).then(function (menu) {
      menuCache = menu;
      return menu;
    }).catch(function () {
      menuPromise = null;
      throw new Error("menu");
    });
    return menuPromise;
  }

  /* ---------- orders (localStorage) ---------- */
  function readOrders() {
    try {
      var o = JSON.parse(lsGet(LS_ORDERS) || "[]");
      return Array.isArray(o) ? o : [];
    } catch (e) { return []; }
  }
  function writeOrders(orders) { lsSet(LS_ORDERS, JSON.stringify(orders)); }

  /* ---------- mock api.php (same endpoints, same response shapes) ---------- */
  function mockRequest(url, opts) {
    var action = parseAction(url);
    var method = (opts && opts.method ? String(opts.method) : "GET").toUpperCase();
    var body = readBody(opts);

    switch (action) {
      case "get_menu":
        if (method !== "GET") return Promise.resolve(errResp("方法唔啱", 405));
        return loadMenuData().then(function (menu) {
          return okResp({ ok: true, menu: menu });
        }).catch(function () {
          return errResp("酒單讀唔到", 500);
        });

      case "submit_order": {
        if (method !== "POST") return Promise.resolve(errResp("方法唔啱", 405));
        var items = body.items;
        var spot = typeof body.spot === "string" ? body.spot : "";
        var note = typeof body.note === "string" ? body.note : "";
        if (!Array.isArray(items) || items.length === 0 || items.length > 20) {
          return Promise.resolve(errResp("訂單冇嘢", 400));
        }
        var seat = seatLabel(spot);
        if (!seat) return Promise.resolve(errResp("座位唔啱", 400));
        if (note.length > 200) return Promise.resolve(errResp("備註太長", 400));
        return loadMenuData().then(function (menu) {
          var byId = {};
          menu.forEach(function (m) { byId[m.id] = m; });
          var orderItems = [];
          var total = 0;
          for (var i = 0; i < items.length; i++) {
            var it = items[i];
            if (!it || typeof it !== "object" || it.id === undefined || it.qty === undefined) {
              return errResp("訂單格式唔啱", 400);
            }
            var id = String(it.id);
            var qty = parseInt(it.qty, 10);
            if (!byId[id]) return errResp("有飲品唔存在", 400);
            if (isNaN(qty) || qty < 1 || qty > 20) return errResp("數量唔啱", 400);
            var m = byId[id];
            if (m.price === null || m.price === undefined) {
              return errResp("呢款要問吧台，唔可以直接落單", 400);
            }
            /* Drink options (cold/hot, 6/12-pack, 1L): the price follows
               the chosen option. No option sent means the first one, so
               older saved guest pages still order. Unknown label -> 400. */
            var unitPrice = parseInt(m.price, 10);
            var optLabel = "";
            if (m.opts && Array.isArray(m.opts) && m.opts.length > 0) {
              var opt = (typeof it.opt === "string") ? it.opt : "";
              var found = null;
              for (var oi = 0; oi < m.opts.length; oi++) {
                if (m.opts[oi] && m.opts[oi].label === opt) { found = m.opts[oi]; break; }
              }
              if (found === null && opt === "") found = m.opts[0];
              if (found === null) return errResp("選項唔啱", 400);
              optLabel = found.label;
              unitPrice = parseInt(found.price, 10);
            }
            var orderItem = {
              id: id, n: m.name, p: unitPrice, q: qty,
              img: m.img || "", ing: m.ing || ""
            };
            if (optLabel !== "") orderItem.o = optLabel;
            orderItems.push(orderItem);
            total += unitPrice * qty;
          }
          var orders = readOrders();
          var no = 1;
          orders.forEach(function (o) { if (o.no && o.no >= no) no = o.no + 1; });
          var order = {
            id: newOrderId(), no: no,
            date: todayStr(), time: nowHM(),
            seat: seat.label, seatEn: seat.en,
            items: orderItems, total: total,
            note: note.replace(/^\s+|\s+$/g, ""), status: "待製作"
          };
          orders.push(order);
          writeOrders(orders);
          return okResp({ ok: true, order: order });
        }).catch(function () {
          return errResp("酒單讀唔到", 500);
        });
      }

      case "login": {
        if (method !== "POST") return Promise.resolve(errResp("方法唔啱", 405));
        var pin = typeof body.pin === "string" ? body.pin : "";
        if (/^\d{4}$/.test(pin) && pin === DEMO_PIN) {
          lsSet(LS_AUTH, "1");
          return Promise.resolve(okResp({ ok: true }));
        }
        return Promise.resolve(errResp("PIN 唔啱", 401));
      }

      case "logout":
        if (method !== "POST") return Promise.resolve(errResp("方法唔啱", 405));
        lsDel(LS_AUTH);
        return Promise.resolve(okResp({ ok: true }));

      case "get_orders": {
        if (method !== "GET") return Promise.resolve(errResp("方法唔啱", 405));
        if (!isAuthed()) return Promise.resolve(errResp("未登入", 401));
        var sorted = readOrders().slice().sort(function (a, b) { return (b.no || 0) - (a.no || 0); });
        return Promise.resolve(okResp({ ok: true, orders: sorted.slice(0, 100) }));
      }

      case "update_order": {
        if (method !== "POST") return Promise.resolve(errResp("方法唔啱", 405));
        if (!isAuthed()) return Promise.resolve(errResp("未登入", 401));
        var oid = typeof body.id === "string" ? body.id : "";
        var status = typeof body.status === "string" ? body.status : "";
        if (oid === "" || ORDER_STATUSES.indexOf(status) === -1) {
          return Promise.resolve(errResp("資料唔啱", 400));
        }
        var orders2 = readOrders();
        for (var j = 0; j < orders2.length; j++) {
          if (orders2[j].id === oid) {
            orders2[j].status = status;
            writeOrders(orders2);
            return Promise.resolve(okResp({ ok: true, order: orders2[j] }));
          }
        }
        return Promise.resolve(errResp("搵唔到呢張單", 404));
      }

      case "admin_save_menu": {
        if (method !== "POST") return Promise.resolve(errResp("方法唔啱", 405));
        if (!isAuthed()) return Promise.resolve(errResp("未登入", 401));
        var newMenu = body.menu;
        if (!Array.isArray(newMenu) || newMenu.length === 0 || newMenu.length > 200) {
          return Promise.resolve(errResp("酒單格式唔啱", 400));
        }
        var seen = {};
        var clean = [];
        for (var k = 0; k < newMenu.length; k++) {
          var it2 = newMenu[k];
          if (!it2 || typeof it2 !== "object") return Promise.resolve(errResp("酒單格式唔啱", 400));
          var mid = typeof it2.id === "string" ? it2.id : "";
          var mname = typeof it2.name === "string" ? it2.name.replace(/^\s+|\s+$/g, "") : "";
          var mcat = typeof it2.category === "string" ? it2.category : "";
          var mprice = ("price" in it2) ? it2.price : null;
          var msub = typeof it2.sub === "string" ? it2.sub.replace(/^\s+|\s+$/g, "") : "";
          var ming = typeof it2.ing === "string" ? it2.ing.replace(/^\s+|\s+$/g, "") : "";
          var mimg = typeof it2.img === "string" ? it2.img.replace(/^\s+|\s+$/g, "") : "";
          var mopts = ("opts" in it2) ? it2.opts : null;
          var cleanOpts = null;
          if (mopts !== null) {
            if (!Array.isArray(mopts) || mopts.length === 0 || mopts.length > 6) return Promise.resolve(errResp("選項唔啱", 400));
            cleanOpts = [];
            var seenOpt = {};
            for (var oi2 = 0; oi2 < mopts.length; oi2++) {
              var oo = mopts[oi2];
              if (!oo || typeof oo !== "object") return Promise.resolve(errResp("選項唔啱", 400));
              var ol = (typeof oo.label === "string") ? oo.label.replace(/^\s+|\s+$/g, "") : "";
              var op = parseInt(oo.price, 10);
              if (ol === "" || ol.length > 20 || seenOpt[ol]) return Promise.resolve(errResp("選項唔啱", 400));
              seenOpt[ol] = true;
              if (isNaN(op) || op < 0 || op > 100000) return Promise.resolve(errResp("選項價錢唔啱", 400));
              cleanOpts.push({ label: ol, price: op });
            }
          }
          if (!/^[a-z0-9][a-z0-9\-]*$/.test(mid)) return Promise.resolve(errResp("飲品編號唔啱", 400));
          if (seen[mid]) return Promise.resolve(errResp("飲品編號重複", 400));
          seen[mid] = true;
          if (mname === "" || mname.length > 60) return Promise.resolve(errResp("飲品名唔啱", 400));
          if (!CATEGORIES.hasOwnProperty(mcat)) return Promise.resolve(errResp("分類唔啱", 400));
          if (mprice !== null) {
            mprice = parseInt(mprice, 10);
            if (isNaN(mprice) || mprice < 0 || mprice > 100000) return Promise.resolve(errResp("價錢唔啱", 400));
          }
          if (msub.length > 100) return Promise.resolve(errResp("副標題太長", 400));
          if (ming.length > 200) return Promise.resolve(errResp("英文成份太長", 400));
          if (mimg !== "" && !/^assets\/img\/[a-z0-9\-]+\.(jpg|jpeg|png|webp)$/.test(mimg)) {
            return Promise.resolve(errResp("相片路徑唔啱", 400));
          }
          var row = { id: mid, name: mname, price: mprice, category: mcat, category_name: CATEGORIES[mcat], img: mimg };
          if (msub !== "") row.sub = msub;
          if (ming !== "") row.ing = ming;
          if (cleanOpts !== null) row.opts = cleanOpts;
          clean.push(row);
        }
        menuCache = clean;
        lsSet(LS_MENU, JSON.stringify(clean));
        return Promise.resolve(okResp({ ok: true, count: clean.length }));
      }

      default:
        return Promise.resolve(errResp("唔知你想做咩", 400));
    }
  }

  /* ---------- demo banner ---------- */
  var bannerShown = false;
  function showBanner() {
    if (bannerShown) return;
    bannerShown = true;
    if (!document || !document.body) return;
    var style = document.createElement("style");
    style.textContent =
      "#demo-mode-banner{position:sticky;top:0;z-index:200;display:flex;flex-wrap:wrap;" +
      "gap:6px 12px;align-items:center;justify-content:center;padding:8px 14px;" +
      "background:#3a2f10;color:#f5e6b8;font-size:13px;line-height:1.45;text-align:center;" +
      "border-bottom:1px solid #6b5518;font-family:system-ui,-apple-system,sans-serif}" +
      "#demo-mode-banner button{border:1px solid #e3c078;background:none;color:#e3c078;" +
      "border-radius:999px;padding:2px 12px;font-size:12.5px;cursor:pointer;min-height:28px}";
    document.head.appendChild(style);
    var bar = document.createElement("div");
    bar.id = "demo-mode-banner";
    bar.setAttribute("role", "status");
    var msg = document.createElement("span");
    msg.textContent = "Demo mode · 試玩模式 — Orders are stored only in your browser · 訂單只會儲喺你個瀏覽器，唔會傳去任何伺服器";
    var btn = document.createElement("button");
    btn.type = "button";
    btn.textContent = "清除試玩訂單 Reset";
    btn.addEventListener("click", function () {
      lsDel(LS_ORDERS); lsDel(LS_MENU); lsDel(LS_AUTH);
      location.reload();
    });
    bar.appendChild(msg);
    bar.appendChild(btn);
    document.body.insertBefore(bar, document.body.firstChild);
  }

  /* ---------- fetch wrapper: real backend first, mock on fallback ---------- */
  function isApiCall(url) {
    return typeof url === "string" && /(^|\/)api\.php(\?|$)/.test(url);
  }
  function enableDemo() {
    demoActive = true;
    showBanner();
  }

  window.fetch = function (url, opts) {
    if (!isApiCall(url)) return origFetch(url, opts);
    if (demoActive) return mockRequest(url, opts);
    if (backendDecided) return origFetch(url, opts);
    return origFetch(url, opts).then(function (resp) {
      if (resp.status === 404 || resp.status === 405) {
        enableDemo();
        return mockRequest(url, opts);
      }
      /* A static host serves api.php as a plain file (HTTP 200, raw PHP
         source). Only a real backend answers with JSON. */
      return resp.clone().text().then(function (text) {
        try {
          var data = JSON.parse(text);
          if (data && typeof data === "object") {
            backendDecided = true;
            return resp;
          }
        } catch (e) { /* not JSON: no PHP backend */ }
        enableDemo();
        return mockRequest(url, opts);
      });
    }).catch(function () {
      enableDemo();
      return mockRequest(url, opts);
    });
  };

  /* Exposed for debugging / automated checks. */
  window.BarDemoMode = {
    isDemo: function () { return demoActive; },
    isDecided: function () { return backendDecided || demoActive; },
    reset: function () { lsDel(LS_ORDERS); lsDel(LS_MENU); lsDel(LS_AUTH); }
  };
})();
