const { useState, useEffect, useMemo, useRef } = React;
            const html = htm.bind(React.createElement);

            /* helpers */
            const pad = (n) => String(n).padStart(2, "0");
            const todayStr = () => {
                const d = new Date();
                return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
            };
            const niceDate = (s) => {
                try {
                    return new Date(s + "T00:00:00").toLocaleDateString(
                        "en-GB",
                        {
                            weekday: "short",
                            day: "numeric",
                            month: "short",
                            year: "numeric",
                        },
                    );
                } catch {
                    return s;
                }
            };
            const newId = (p) =>
                p +
                Date.now().toString(36) +
                Math.random().toString(36).slice(2, 6);
            const ls = {
                get(k, d) {
                    try {
                        const v = localStorage.getItem(k);
                        return v == null ? d : JSON.parse(v);
                    } catch {
                        return d;
                    }
                },
                set(k, v) {
                    try {
                        localStorage.setItem(k, JSON.stringify(v));
                    } catch {}
                },
            };
            const isObj = (x) =>
                x && typeof x === "object" && !Array.isArray(x);
            const deepMerge = (a, b) => {
                const o = { ...(a || {}) };
                for (const k in b) {
                    o[k] =
                        isObj(b[k]) && isObj(o[k])
                            ? deepMerge(o[k], b[k])
                            : b[k];
                }
                return o;
            };
            const rs = (n) =>
                "Rs. " + (Math.round(n * 100) / 100).toLocaleString("en-LK");
            const sum = (arr) => arr.reduce((a, b) => a + b, 0);
            const slug = (s) =>
                s
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, "_")
                    .replace(/^_|_$/g, "") || "item";
            const qtyOf = (o) =>
                o
                    ? sum(
                          Object.values(o.q || {})
                              .map(Number)
                              .filter((n) => n > 0),
                      )
                    : 0;
            const csvCell = (v) => {
                const s = String(v ?? "");
                return /[",\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
            };
            const toCsv = (rows) =>
                rows.map((r) => r.map(csvCell).join(",")).join("\n");
            /* price for an item at a shop: the shop's fixed price, else the default item price */
            const priceOf = (shop, items, k) => {
                const sp = shop && shop.prices ? shop.prices[k] : null;
                if (sp != null && sp !== "" && !isNaN(Number(sp)))
                    return Number(sp);
                return Number((items[k] || {}).price) || 0;
            };
            const lineNet = (l) =>
                Math.max(0, (Number(l.qty) || 0) - (Number(l.ret) || 0));
            const billTotal = (lines) =>
                sum(
                    (lines || []).map(
                        (l) => lineNet(l) * (Number(l.price) || 0),
                    ),
                );
            const orderSig = (o) =>
                JSON.stringify(
                    Object.entries((o && o.q) || {})
                        .map(([k, v]) => [k, Number(v) || 0])
                        .filter((x) => x[1] > 0)
                        .sort(),
                );
            const billSig = (b) =>
                JSON.stringify(
                    ((b && b.lines) || [])
                        .map((l) => [l.id, Number(l.qty) || 0])
                        .sort(),
                );
            /* payment state of one shop bill */
            function payInfo(b) {
                const total = billTotal(b.lines);
                const p = b.pay || null;
                const paid = !p
                    ? 0
                    : p.method === "cash"
                      ? total
                      : Math.min(total, Number(p.paid) || 0);
                const balance = Math.max(0, total - paid);
                const status = !p
                    ? "due"
                    : balance <= 0
                      ? "paid"
                      : paid > 0
                        ? "partial"
                        : "credit";
                return { total, paid, balance, status };
            }
            const PAY_LABEL = {
                due: "Not paid yet",
                credit: "Credit",
                partial: "Partly paid",
                paid: "Paid",
            };
            const PayPill = ({ status }) =>
                html`<span class=${"pill pay-" + status}
                    >${PAY_LABEL[status]}</span
                >`;
            async function recordPayment(
                date,
                routeId,
                shopId,
                bill,
                kind,
                amount,
            ) {
                const info = payInfo(bill);
                const log = ((bill.pay && bill.pay.log) || []).slice(-30);
                let pay;
                if (kind === "cash") pay = { method: "cash", paid: info.total };
                else if (kind === "credit") pay = { method: "credit", paid: 0 };
                else if (kind === "partial")
                    pay = {
                        method: "partial",
                        paid: Math.max(0, Number(amount) || 0),
                    };
                else {
                    const np = info.paid + Math.max(0, Number(amount) || 0);
                    pay =
                        np >= info.total
                            ? { method: "cash", paid: info.total }
                            : { method: "partial", paid: np };
                }
                pay.at = Date.now();
                pay.log = [
                    ...log,
                    {
                        kind,
                        amount:
                            kind === "collect" || kind === "partial"
                                ? Number(amount) || 0
                                : kind === "cash"
                                  ? info.total
                                  : 0,
                        at: Date.now(),
                    },
                ];
                return Store.merge(`bills/${date}_${routeId}`, {
                    date,
                    routeId,
                    shops: { [shopId]: { pay } },
                });
            }
            function buildLines(shop, order, items, prevLines) {
                const prev = Object.fromEntries(
                    (prevLines || []).map((l) => [l.id, l]),
                );
                const ids = [
                    ...((shop && shop.items) || []),
                    ...Object.keys(order.q || {}),
                ].filter((v, i, a) => a.indexOf(v) === i);
                return ids
                    .map((k) => [k, Number((order.q || {})[k]) || 0])
                    .filter((x) => x[1] > 0)
                    .map(([k, v]) => ({
                        id: k,
                        name: (items[k] || {}).name || k,
                        qty: v,
                        ret: prev[k]
                            ? Math.min(Number(prev[k].ret) || 0, v)
                            : 0,
                        price: priceOf(shop, items, k),
                    }));
            }

            /* store: connected to the Laravel API and SQL database */
            const BH = window.BH_CONFIG || {
                api: "/api",
                setup: "/setup",
                logo: "/images/logo.png",
            };
            const norm = (w) => (!w ? [] : Array.isArray(w[0]) ? w : [w]);
            const test = (v, op, x) =>
                op === "=="
                    ? v === x
                    : op === ">="
                      ? v >= x
                      : op === "<="
                        ? v <= x
                        : op === ">"
                          ? v > x
                          : op === "<"
                            ? v < x
                            : op === "!="
                              ? v !== x
                              : true;
            const matches = (d, w) =>
                norm(w).every(([f, op, x]) => test(d[f], op, x));

            /* talks to the Laravel API: session cookie + XSRF token, refreshes watched data every few seconds */
            const Server = (base) => {
                const watchers = new Set();
                let onSignedOut = null;
                const cookie = (name) => {
                    const m = document.cookie.match(
                        new RegExp("(?:^|; )" + name + "=([^;]*)"),
                    );
                    return m ? decodeURIComponent(m[1]) : null;
                };
                async function request(method, url, body, retry = true) {
                    const headers = {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    };
                    const token = cookie("XSRF-TOKEN");
                    if (token) headers["X-XSRF-TOKEN"] = token;
                    if (body !== undefined)
                        headers["Content-Type"] = "application/json";
                    let res;
                    try {
                        res = await fetch(url, {
                            method,
                            headers,
                            credentials: "same-origin",
                            body: body === undefined ? undefined : JSON.stringify(body),
                        });
                    } catch (e) {
                        const err = new Error(
                            "Could not reach the server. Check your connection.",
                        );
                        err.code = "unavailable";
                        throw err;
                    }
                    if (res.status === 419 && retry) {
                        await request("GET", base + "/session", undefined, false);
                        return request(method, url, body, false);
                    }
                    const data =
                        res.status === 204 ? null : await res.json().catch(() => null);
                    if (!res.ok) {
                        if (res.status === 401 && onSignedOut) onSignedOut();
                        const first =
                            data && data.errors && Object.values(data.errors)[0];
                        const err = new Error(
                            (first && first[0]) ||
                                (data && data.message) ||
                                "The server could not save this. Try again.",
                        );
                        err.status = res.status;
                        err.code =
                            res.status === 403 || res.status === 422
                                ? "invalid_argument"
                                : "unavailable";
                        throw err;
                    }
                    return data;
                }
                const query = (where) => {
                    const p = new URLSearchParams();
                    norm(where).forEach(([f, op, x]) => {
                        if (f === "date" && op === "==") p.set("date", x);
                        else if (f === "date" && op === ">=") p.set("from", x);
                        else if (f === "date" && op === "<=") p.set("to", x);
                        else if (f === "routeId" && op === "==") p.set("routeId", x);
                    });
                    const q = p.toString();
                    return q ? "?" + q : "";
                };
                async function fetchCol(col, where) {
                    if (col === "requests") return [];
                    const res = await request("GET", base + "/" + col + query(where));
                    return ((res && res.data) || []).filter((d) => matches(d, where));
                }
                async function refresh(w) {
                    try {
                        const docs = await fetchCol(w.col, w.where);
                        const sig = JSON.stringify(docs);
                        if (w.live && sig !== w.sig) {
                            w.sig = sig;
                            w.cb(docs);
                        }
                    } catch (e) {
                        if (w.live && w.sig === undefined && w.onErr && !w.failed)
                            w.onErr(e);
                        w.failed = true;
                    }
                }
                const refreshCol = (col) =>
                    Promise.all(
                        [...watchers].filter((w) => w.col === col).map(refresh),
                    );
                const refreshAll = () =>
                    Promise.all([...watchers].map(refresh));
                const poll = () => {
                    if (document.visibilityState === "visible")
                        watchers.forEach(refresh);
                };
                setInterval(poll, (BH && BH.pollSeconds ? BH.pollSeconds : 15) * 1000);
                document.addEventListener("visibilitychange", poll);
                const docUrl = (path) => {
                    const i = path.indexOf("/");
                    return [
                        path.slice(0, i),
                        base +
                            "/" +
                            path.slice(0, i) +
                            "/" +
                            encodeURIComponent(path.slice(i + 1)),
                    ];
                };
                async function write(method, path, data) {
                    const [col, url] = docUrl(path);
                    const res = await request(method, url, data);
                    await refreshCol(col === "items" ? "config" : col);
                    return res;
                }
                return {
                    session: () =>
                        request("GET", base + "/session").then((r) => r.data),
                    login: (username, password, remember) =>
                        request("POST", base + "/session", {
                            username,
                            password,
                            remember,
                        }).then((r) => r.data),
                    logout: () => request("DELETE", base + "/session"),
                    setup: (f) =>
                        request("POST", BH.setup || "/setup", f).then((r) => r.data),
                    importData: async (data) => {
                        const r = await request("POST", base + "/imports", { data });
                        await refreshAll();
                        return r.data;
                    },
                    onSignedOut: (f) => {
                        onSignedOut = f;
                    },
                    watch(col, cb, where, onErr) {
                        const w = { col, cb, where, onErr, live: true };
                        watchers.add(w);
                        refresh(w);
                        return () => {
                            w.live = false;
                            watchers.delete(w);
                        };
                    },
                    list: fetchCol,
                    set: (path, data) => write("PUT", path, data),
                    merge: (path, data) => write("PATCH", path, data),
                    del: (path) => write("DELETE", path),
                };
            };

            const Store = (() => {
                let mode = "loading",
                    session = null;
                const server = Server(BH.api || "/api");
                return {
                    get mode() {
                        return mode;
                    },
                    /* {user, needsSetup} from the last session check */
                    get session() {
                        return session;
                    },
                    setSession(nextSession) {
                        session = nextSession;
                    },
                    server,
                    async init() {
                        session = await server.session();
                        mode = "server";
                        return mode;
                    },
                    watch(col, cb, where, onErr) {
                        return server.watch(col, cb, where, onErr);
                    },
                    async list(col, where) {
                        return server.list(col, where);
                    },
                    async set(path, data) {
                        return server.set(path, data);
                    },
                    async merge(path, data) {
                        return server.merge(path, data);
                    },
                    async del(path) {
                        return server.del(path);
                    },
                };
            })();
            const ownAccounts = () => true;
            const minPass = () => 8;

            async function saveFile(filename, data) {
                try {
                    const dl =
                        window.claude && window.claude.use
                            ? await window.claude.use("downloads")
                            : null;
                    if (dl) {
                        await dl.save({ filename, data });
                        return null;
                    }
                    const csv = filename.toLowerCase().endsWith(".csv");
                    const blob = new Blob([csv ? "\ufeff" + data : data], {
                        type: csv
                            ? "text/csv;charset=utf-8"
                            : "text/plain;charset=utf-8",
                    });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement("a");
                    link.href = url;
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    setTimeout(() => URL.revokeObjectURL(url), 1000);
                    return null;
                } catch (e) {
                    return e && e.code === "declined"
                        ? null
                        : "Could not save the file.";
                }
            }
            function saveWorkbook(filename, sheets) {
                if (!window.XLSX) return "Excel export is unavailable. Check your connection and try again.";
                try {
                    const workbook = window.XLSX.utils.book_new();
                    sheets.forEach(({ name, rows }) => {
                        const worksheet = window.XLSX.utils.aoa_to_sheet(rows);
                        const columnCount = Math.max(
                            0,
                            ...rows.map((row) => row.length),
                        );
                        worksheet["!cols"] = Array.from(
                            { length: columnCount },
                            (_, column) => ({
                                wch: Math.min(
                                    36,
                                    Math.max(
                                        12,
                                        ...rows.map((row) =>
                                            String(row[column] ?? "").length,
                                        ),
                                    ),
                                ),
                            }),
                        );
                        window.XLSX.utils.book_append_sheet(
                            workbook,
                            worksheet,
                            name.slice(0, 31),
                        );
                    });
                    window.XLSX.writeFile(workbook, filename);
                    return null;
                } catch {
                    return "Could not create the Excel workbook.";
                }
            }
            const printEscape = (value) =>
                String(value ?? "").replace(/[&<>"']/g, (character) =>
                    ({
                        "&": "&amp;",
                        "<": "&lt;",
                        ">": "&gt;",
                        '"': "&quot;",
                        "'": "&#39;",
                    })[character],
                );
            function printDocument(title, subtitle, sections) {
                const frame = document.createElement("iframe");
                frame.title = `Print ${title}`;
                frame.style.cssText = "position:fixed;right:0;bottom:0;width:0;height:0;border:0";
                document.body.appendChild(frame);
                const popup = frame.contentWindow;
                const doc = frame.contentDocument || (popup && popup.document);
                if (!popup || !doc) {
                    frame.remove();
                    return "Could not open the print dialog.";
                }
                const content = sections
                    .map(
                        ({ heading, columns, rows }) =>
                            `<section><h2>${printEscape(heading)}</h2><table><thead><tr>${columns.map((column) => `<th>${printEscape(column)}</th>`).join("")}</tr></thead><tbody>${rows.map((row) => `<tr>${row.map((cell) => `<td>${printEscape(cell)}</td>`).join("")}</tr>`).join("")}</tbody></table></section>`,
                    )
                    .join("");
                doc.open();
                doc.write(`<!doctype html><html><head><meta charset="utf-8"><title>${printEscape(title)}</title><style>body{font:14px/1.45 Arial,sans-serif;color:#24372b;margin:32px}h1{font-size:24px;margin:0 0 4px}h2{font-size:17px;margin:24px 0 8px;break-after:avoid;page-break-after:avoid}p{color:#526252;margin:0 0 18px}table{width:100%;border-collapse:collapse;font-size:12px}thead{display:table-header-group}th,td{padding:7px 8px;border:1px solid #cbd5c7;text-align:left;vertical-align:top}th{background:#edf2e8}tr{break-inside:avoid;page-break-inside:avoid}section{break-inside:auto}@page{margin:14mm}@media print{body{margin:0}}</style></head><body><header><h1>${printEscape(title)}</h1><p>${printEscape(subtitle)}</p></header>${content}</body></html>`);
                doc.close();
                popup.addEventListener("afterprint", () => frame.remove(), {
                    once: true,
                });
                setTimeout(() => {
                    popup.focus();
                    popup.print();
                }, 300);
                return null;
            }
            function printShopByShopDocument(title, subtitle, shopList) {
                const frame = document.createElement("iframe");
                frame.title = `Print ${title}`;
                frame.style.cssText = "position:fixed;right:0;bottom:0;width:0;height:0;border:0";
                document.body.appendChild(frame);
                const popup = frame.contentWindow;
                const doc = frame.contentDocument || (popup && popup.document);
                if (!popup || !doc) {
                    frame.remove();
                    return "Could not open the print dialog.";
                }
                const columnCount = 4;
                const maxRowsPerPage = 34;
                const pages = [Array.from({ length: columnCount }, () => [])];
                shopList.forEach((shop) => {
                    const itemRows = (shop.items || []).map((item) => ({
                        type: "item",
                        name: item.name,
                        qty: item.qty,
                    }));
                    const blockHeight = 1 + itemRows.length;
                    let targetPage = null;
                    let targetCol = -1;
                    for (let p = 0; p < pages.length; p++) {
                        const pageCols = pages[p];
                        let minLen = Infinity;
                        for (let c = 0; c < columnCount; c++) {
                            const used = pageCols[c].length;
                            const needed = (used > 0 ? 1 : 0) + blockHeight;
                            const pageEmpty = pageCols.every((col) => col.length === 0);
                            if (used + needed <= maxRowsPerPage || (pageEmpty && c === 0)) {
                                if (used < minLen) {
                                    minLen = used;
                                    targetCol = c;
                                }
                            }
                        }
                        if (targetCol !== -1) {
                            targetPage = pageCols;
                            break;
                        }
                    }
                    if (!targetPage) {
                        targetPage = Array.from({ length: columnCount }, () => []);
                        pages.push(targetPage);
                        targetCol = 0;
                    }
                    const col = targetPage[targetCol];
                    if (col.length > 0) {
                        col.push({ type: "empty" });
                    }
                    col.push({
                        type: "head",
                        name: shop.name,
                        total: shop.total,
                    });
                    itemRows.forEach((row) => col.push(row));
                });
                const renderCell = (cell) => {
                    if (!cell || cell.type === "empty") {
                        return `<td class="cell-empty">&nbsp;</td>`;
                    }
                    if (cell.type === "head") {
                        return `<td class="cell-head"><div class="shop-head"><span>${printEscape(cell.name)}</span><span class="shop-pcs">${printEscape(cell.total)} pcs</span></div></td>`;
                    }
                    return `<td class="cell-item">${printEscape(cell.name)} - ${printEscape(cell.qty)}</td>`;
                };
                const content = pages
                    .map((pageCols, pageIdx) => {
                        const rowCount = Math.max(
                            1,
                            ...pageCols.map((col) => col.length),
                        );
                        const rowsHtml = Array.from(
                            { length: rowCount },
                            (_, r) =>
                                `<tr>${pageCols.map((col) => renderCell(col[r])).join("")}</tr>`,
                        ).join("");
                        return `<section class="sheet-page"><header><h1>${printEscape(title)}</h1><p>${printEscape(subtitle)}</p></header><table class="shop-grid"><tbody>${rowsHtml}</tbody></table><footer class="sheet-footer"><span>Janahitha Bake House</span><span>Page ${pageIdx + 1} of ${pages.length}</span></footer></section>`;
                    })
                    .join("");
                doc.open();
                doc.write(`<!doctype html><html><head><meta charset="utf-8"><title>${printEscape(title)}</title><style>*{box-sizing:border-box}body{font:14px/1.35 Arial,sans-serif;color:#24372b;margin:22px;-webkit-print-color-adjust:exact;print-color-adjust:exact}h1{font-size:22px;margin:0 0 2px}p{color:#526252;margin:0 0 10px;font-size:14px}.sheet-page{break-inside:avoid;page-break-inside:avoid}.sheet-page+.sheet-page{break-before:page;page-break-before:always;margin-top:24px}table.shop-grid{width:100%;border-collapse:collapse;table-layout:fixed;font-size:14px}table.shop-grid td{width:25%;padding:3px 6px;border:1px solid #cbd5c7;text-align:left;vertical-align:middle;height:20px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}table.shop-grid td.cell-head{background:#e9ede6;font-weight:700;color:#1b2a20}table.shop-grid .shop-head{display:flex;justify-content:space-between;align-items:center;gap:6px}table.shop-grid .shop-pcs{font-size:13px;font-weight:700;white-space:nowrap}table.shop-grid tr{break-inside:avoid;page-break-inside:avoid}.sheet-footer{display:flex;justify-content:space-between;align-items:center;margin-top:8px;font-size:13px;color:#526252}@page{size:A4 portrait;margin:10mm}@media print{body{margin:0}.sheet-page+.sheet-page{margin-top:0}}</style></head><body>${content}</body></html>`);
                doc.close();
                popup.addEventListener("afterprint", () => frame.remove(), {
                    once: true,
                });
                setTimeout(() => {
                    popup.focus();
                    popup.print();
                }, 300);
                return null;
            }
            function printBillsDocument(title, subtitle, billPages) {
                const frame = document.createElement("iframe");
                frame.title = `Print ${title}`;
                frame.style.cssText = "position:fixed;right:0;bottom:0;width:0;height:0;border:0";
                document.body.appendChild(frame);
                const popup = frame.contentWindow;
                const doc = frame.contentDocument || (popup && popup.document);
                if (!popup || !doc) {
                    frame.remove();
                    return "Could not open the print dialog.";
                }
                const content = billPages
                    .map((page, pageIdx) => {
                        const itemCount = Number(page.itemCount) || 0;
                        const density =
                            itemCount > 26
                                ? "bill-dense"
                                : itemCount > 16
                                  ? "bill-compact"
                                  : "bill-normal";
                        const sectionsHtml = (page.sections || [])
                            .map(
                                ({ heading, columns, rows }) =>
                                    `<div class="bill-section"><h2>${printEscape(heading)}</h2><table><thead><tr>${columns.map((column) => `<th>${printEscape(column)}</th>`).join("")}</tr></thead><tbody>${rows.map((row) => `<tr>${row.map((cell) => `<td>${printEscape(cell)}</td>`).join("")}</tr>`).join("")}</tbody></table></div>`,
                            )
                            .join("");
                        return `<section class="bill-page ${density}"><header><h1>${printEscape(page.title || title)}</h1><p>${printEscape(page.subtitle || subtitle)}</p></header><div class="bill-body">${sectionsHtml}</div><footer class="bill-footer"><span>Janahitha Bake House</span><span>Page ${pageIdx + 1} of ${billPages.length}</span></footer></section>`;
                    })
                    .join("");
                doc.open();
                doc.write(`<!doctype html><html><head><meta charset="utf-8"><title>${printEscape(title)}</title><style>*{box-sizing:border-box}body{font:16px/1.4 Arial,sans-serif;color:#24372b;margin:24px;-webkit-print-color-adjust:exact;print-color-adjust:exact}h1{font-size:24px;margin:0 0 3px}h2{font-size:16px;margin:14px 0 6px;break-after:avoid;page-break-after:avoid}p{color:#526252;margin:0 0 12px;font-size:14px}.bill-page{break-inside:avoid;page-break-inside:avoid;max-height:273mm;overflow:hidden;display:flex;flex-direction:column}.bill-page+.bill-page{break-before:page;page-break-before:always;margin-top:28px}.bill-section{break-inside:avoid;page-break-inside:avoid}table{width:100%;border-collapse:collapse;font-size:14px}th,td{padding:6px 8px;border:1px solid #cbd5c7;text-align:left;vertical-align:top}th{background:#edf2e8}tr{break-inside:avoid;page-break-inside:avoid}.bill-compact h1{font-size:20px}.bill-compact p{margin-bottom:8px;font-size:14px}.bill-compact h2{font-size:16px;margin:9px 0 4px}.bill-compact table{font-size:12px}.bill-compact th,.bill-compact td{padding:3.5px 6px}.bill-dense h1{font-size:18px}.bill-dense p{margin-bottom:6px;font-size:12px}.bill-dense h2{font-size:14px;margin:6px 0 3px}.bill-dense table{font-size:11px}.bill-dense th,.bill-dense td{padding:2px 4px}.bill-footer{display:flex;justify-content:space-between;align-items:center;margin-top:12px;font-size:12px;color:#526252}@page{size:A4 portrait;margin:10mm}@media print{body{margin:0}.bill-page+.bill-page{margin-top:0}}</style></head><body>${content}</body></html>`);
                doc.close();
                popup.addEventListener("afterprint", () => frame.remove(), {
                    once: true,
                });
                setTimeout(() => {
                    popup.focus();
                    popup.print();
                }, 300);
                return null;
            }
            function downloadPdf(filename, title, subtitle, sections) {
                return printDocument(title, subtitle, sections);
            }
            async function copyText(t) {
                try {
                    await navigator.clipboard.writeText(t);
                    return true;
                } catch {
                    return false;
                }
            }

            /*  small pieces  */
            const Logo = () =>
                html`<img
                    class="brand-logo"
                    src=${(BH && BH.logo) || "./public/images/logo.png"}
                    alt="Janahitha Bake House logo"
                />`;
    



            const Chev = () =>
                html`<svg
                    class="chev"
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path d="m9 6 6 6-6 6" />
                </svg>`;

            function RoutePicker({ routes, value, onChange, counts }) {
                if (!routes.length) return null;
                return html`<div class="chips" role="group" aria-label="Route">
                    ${routes.map(
                        (r) =>
                            html`<button
                                key=${r.id}
                                class="chip"
                                aria-pressed=${value === r.id}
                                onClick=${() => onChange(r.id)}
                            >
                                ${r.name}${counts && counts[r.id] != null
                                    ? html`<span class="n"
                                          >${counts[r.id]}</span
                                      >`
                                    : null}
                            </button>`,
                    )}
                </div>`;
            }

            const Sidenav = ({ groups, active, onSelect, me, acc, signOut }) =>
                html`<nav class="sidenav" aria-label="Main navigation">
                    <a class="side-brand" href="./" aria-label="Janahitha Bake House home">
                        <${Logo} />
                        <span><strong>Janahitha</strong><small>Bake House</small></span>
                    </a>
                    ${groups.map(
                        (group) =>
                            html`<section class="sidenav-group" key=${group.label}>
                                <${Sidenav.GroupLabel}>${group.label}</${Sidenav.GroupLabel}>
                                <div class="sidenav-items">
                                    ${group.items.map(
                                        ([id, label]) =>
                                            html`<button
                                                type="button"
                                                class="sidenav-link"
                                                aria-current=${active === id ? "page" : null}
                                                onClick=${() => onSelect(id)}
                                            >
                                                <span class=${"nav-icon nav-icon-" + id} aria-hidden="true"></span>
                                                <span>${label.replace(" •", "")}</span>
                                            </button>`,
                                    )}
                                </div>
                            </section>`,
                    )}
                    ${me && acc && html`<div class="side-account">
                        <span class="account-avatar" aria-hidden="true">${(me.name || "B").slice(0, 1).toUpperCase()}</span>
                        <span class="account-copy"><strong>${me.name || "You"}</strong><small>${roleLabel(acc.role)}</small></span>
                        ${acc && ownAccounts() && html`<button class="account-exit" type="button" title="Sign out" aria-label="Sign out" onClick=${signOut}>↗</button>`}
                    </div>`}
                </nav>`;
            Sidenav.GroupLabel = ({ children }) =>
                html`<div class="sidenav-group-label">${children}</div>`;

            /*  ORDERS  */
            function OrdersView({ routes, shops, items, days, date, toast }) {
                const [routeId, setRouteId] = useState(
                    ls.get("bh-route", null),
                );
                const [openShop, setOpenShop] = useState(null);
                useEffect(() => {
                    if (routes.length && !routes.find((r) => r.id === routeId))
                        setRouteId(routes[0].id);
                }, [routes]);
                useEffect(() => {
                    if (routeId) ls.set("bh-route", routeId);
                }, [routeId]);
                const route = routes.find((r) => r.id === routeId);
                const list = shops.filter((s) => s.routeId === routeId);
                const dayDoc = days.find((d) => d.routeId === routeId);
                const orderOf = (s) =>
                    dayDoc && dayDoc.shops ? dayDoc.shops[s.id] : null;
                const doneCount = list.filter(
                    (s) => qtyOf(orderOf(s)) > 0,
                ).length;
                const counts = {};
                routes.forEach(
                    (r) =>
                        (counts[r.id] = shops.filter(
                            (s) => s.routeId === r.id,
                        ).length),
                );

                if (!routes.length)
                    return html`<div class="empty">
                        <h2 style=${{ fontSize: "18px", color: "var(--ink)" }}>
                            No routes yet
                        </h2>
                        <p>
                            An admin adds routes and shops in the Admin tab.
                            Each shop gets its usual item list, then orders here
                            take only the quantities.
                        </p>
                    </div>`;

                const idx = openShop
                    ? list.findIndex((s) => s.id === openShop)
                    : -1;
                const shop = idx >= 0 ? list[idx] : null;

                return html`<${React.Fragment}>
    <${RoutePicker} routes=${routes} value=${routeId} onChange=${setRouteId} counts=${counts}/>
    ${
        route &&
        html`<div class="head">
            <div>
                <div class="label">
                    ${route.driver ? "Driver: " + route.driver : "Route"}
                </div>
                <h2>${route.name} · ${niceDate(date)}</h2>
            </div>
            <div
                style=${{
                    display: "flex",
                    alignItems: "center",
                    gap: "10px",
                    minWidth: "200px",
                    flex: "0 1 280px",
                }}
            >
                <span class="small muted"
                    >${doneCount} of ${list.length} shops</span
                >
                <div class="progress">
                    <i
                        style=${{
                            width:
                                (list.length
                                    ? (doneCount / list.length) * 100
                                    : 0) + "%",
                        }}
                    ></i>
                </div>
            </div>
        </div>`
    }
    ${
        list.length
            ? html`<div class="list">
                  ${list.map((s, i) => {
                      const o = orderOf(s);
                      const q = qtyOf(o);
                      return html`<button
                          key=${s.id}
                          class="row"
                          onClick=${() => setOpenShop(s.id)}
                      >
                          <span class="idx">${i + 1}</span>
                          <span class="main"
                              ><div class="name">${s.name}</div>
                              <div class="sub">
                                  ${q > 0
                                      ? Object.entries(o.q)
                                            .filter(([k, v]) => v > 0)
                                            .map(
                                                ([k, v]) =>
                                                    `${(items[k] || {}).name || k} ${v}`,
                                            )
                                            .join(" · ")
                                      : (s.items || []).length + " usual items"}
                              </div></span
                          >
                          ${q > 0
                              ? html`<span class="pill done">${q} pcs</span>`
                              : html`<span class="pill todo"
                                    >Not entered</span
                                >`}
                          <${Chev} />
                      </button>`;
                  })}
              </div>`
            : html`<div class="empty">
                  No shops on this route yet. Add them in Admin → Shops.
              </div>`
    }
    ${
        shop &&
        html`<${EntrySheet}
            key=${shop.id + date}
            shop=${shop}
            route=${route}
            date=${date}
            existing=${orderOf(shop)}
            items=${items}
            hasNext=${idx < list.length - 1}
            onClose=${() => setOpenShop(null)}
            toast=${toast}
            onSaved=${(next) =>
                setOpenShop(
                    next && idx < list.length - 1 ? list[idx + 1].id : null,
                )}
        />`
    }
    </${React.Fragment}>`;
            }

            function EntrySheet({
                shop,
                route,
                date,
                existing,
                items,
                hasNext,
                onClose,
                onSaved,
                toast,
            }) {
                const init = {};
                Object.entries((existing && existing.q) || {}).forEach(
                    ([k, v]) => {
                        if (v > 0) init[k] = String(v);
                    },
                );
                const [q, setQ] = useState(init);
                const [extra, setExtra] = useState(
                    Object.keys(init).filter(
                        (k) => !(shop.items || []).includes(k),
                    ),
                );
                const [adding, setAdding] = useState(false);
                const [busy, setBusy] = useState(false);
                const refs = useRef({});
                const rowIds = [...(shop.items || []), ...extra].filter(
                    (v, i, a) => a.indexOf(v) === i,
                );
                const val = (k) => Math.max(0, parseInt(q[k] || "0", 10) || 0);
                const total = sum(rowIds.map(val));
                const value = sum(
                    rowIds.map((k) => val(k) * priceOf(shop, items, k)),
                );
                const setV = (k, v) => setQ((p) => ({ ...p, [k]: v }));
                const bump = (k, d) => setV(k, String(Math.max(0, val(k) + d)));

                useEffect(() => {
                    const t = setTimeout(() => {
                        const f = refs.current[rowIds[0]];
                        if (f && window.matchMedia("(min-width:700px)").matches)
                            f.focus();
                    }, 60);
                    return () => clearTimeout(t);
                }, []);
                useEffect(() => {
                    const k = (e) => {
                        if (e.key === "Escape") onClose();
                    };
                    window.addEventListener("keydown", k);
                    return () => window.removeEventListener("keydown", k);
                }, []);

                async function repeatLast() {
                    try {
                        const docs = await Store.list("days", [
                            "routeId",
                            "==",
                            route.id,
                        ]);
                        const prev = docs
                            .filter(
                                (d) =>
                                    d.date < date &&
                                    d.shops &&
                                    qtyOf(d.shops[shop.id]) > 0,
                            )
                            .sort((a, b) => (a.date < b.date ? 1 : -1))[0];
                        if (!prev) {
                            toast("No earlier order for this shop");
                            return;
                        }
                        const nq = {};
                        Object.entries(prev.shops[shop.id].q).forEach(
                            ([k, v]) => {
                                if (v > 0) nq[k] = String(v);
                            },
                        );
                        setQ(nq);
                        setExtra(
                            Object.keys(nq).filter(
                                (k) => !(shop.items || []).includes(k),
                            ),
                        );
                        toast("Filled from " + niceDate(prev.date));
                    } catch (e) {
                        toast("Could not load the last order");
                    }
                }
                async function save(next) {
                    setBusy(true);
                    const out = {};
                    [
                        ...rowIds,
                        ...Object.keys((existing && existing.q) || {}),
                    ].forEach((k) => (out[k] = val(k)));
                    try {
                        await Store.merge(`days/${date}_${route.id}`, {
                            date,
                            routeId: route.id,
                            shops: { [shop.id]: { q: out, at: Date.now() } },
                        });
                        toast(`Saved ${shop.name} · ${total} pcs`);
                        onSaved(next);
                    } catch (e) {
                        toast(
                            e && e.code === "invalid_argument"
                                ? "You do not have permission to save orders"
                                : "Save failed, check connection and try again",
                        );
                        setBusy(false);
                    }
                }
                const notIn = Object.values(items).filter(
                    (it) => !rowIds.includes(it.id),
                );

                return html`<div
                    class="scrim"
                    onClick=${(e) => {
                        if (e.target === e.currentTarget) onClose();
                    }}
                >
                    <div
                        class="sheet"
                        role="dialog"
                        aria-modal="true"
                        aria-label=${"Order for " + shop.name}
                    >
                        <div class="sh-head">
                            <div class="sh-title">
                                <h3>${shop.name}</h3>
                                <button
                                    class="x"
                                    aria-label="Close"
                                    onClick=${onClose}
                                >
                                    ×
                                </button>
                            </div>
                            <div class="toolbar">
                                <span class="small muted"
                                    >${route.name} · ${niceDate(date)}</span
                                >
                                <button
                                    class="btn ghost sm"
                                    onClick=${repeatLast}
                                >
                                    ↺ Repeat last order
                                </button>
                                <button
                                    class="btn ghost sm"
                                    onClick=${() => setQ({})}
                                >
                                    Clear
                                </button>
                            </div>
                        </div>
                        <div class="sh-body">
                            ${rowIds.map((k, i) => {
                                const it = items[k] || { name: k };
                                const has = val(k) > 0;
                                const pr = priceOf(shop, items, k);
                                return html`<div
                                    key=${k}
                                    class=${"qrow" + (has ? " has" : "")}
                                >
                                    <label class="nm" for=${"q-" + k}
                                        >${it.name}${pr > 0
                                            ? html`<small
                                                  >${rs(pr)} each</small
                                              >`
                                            : null}</label
                                    >
                                    <div class="step">
                                        <button
                                            aria-label=${"Less " + it.name}
                                            onClick=${() => bump(k, -1)}
                                        >
                                            −
                                        </button>
                                        <input
                                            id=${"q-" + k}
                                            ref=${(el) =>
                                                (refs.current[k] = el)}
                                            type="number"
                                            inputmode="numeric"
                                            min="0"
                                            placeholder="0"
                                            value=${q[k] || ""}
                                            onFocus=${(e) => e.target.select()}
                                            onChange=${(e) =>
                                                setV(
                                                    k,
                                                    e.target.value.replace(
                                                        /[^0-9]/g,
                                                        "",
                                                    ),
                                                )}
                                            onKeyDown=${(e) => {
                                                if (e.key === "Enter") {
                                                    e.preventDefault();
                                                    const n =
                                                        refs.current[
                                                            rowIds[i + 1]
                                                        ];
                                                    if (n) n.focus();
                                                    else save(true);
                                                }
                                            }}
                                        />
                                        <button
                                            aria-label=${"More " + it.name}
                                            onClick=${() => bump(k, 1)}
                                        >
                                            +
                                        </button>
                                    </div>
                                </div>`;
                            })}
                            ${adding
                                ? html`<div class="addpick">
                                      ${notIn.length
                                          ? notIn.map(
                                                (it) =>
                                                    html`<button
                                                        key=${it.id}
                                                        class="chip"
                                                        onClick=${() => {
                                                            setExtra((x) => [
                                                                ...x,
                                                                it.id,
                                                            ]);
                                                            setAdding(false);
                                                            setTimeout(
                                                                () =>
                                                                    refs
                                                                        .current[
                                                                        it.id
                                                                    ] &&
                                                                    refs.current[
                                                                        it.id
                                                                    ].focus(),
                                                                50,
                                                            );
                                                        }}
                                                    >
                                                        + ${it.name}
                                                    </button>`,
                                            )
                                          : html`<span class="small muted"
                                                >Every item is already
                                                listed.</span
                                            >`}
                                  </div>`
                                : html`<div class="addpick">
                                      <button
                                          class="chip add"
                                          onClick=${() => setAdding(true)}
                                      >
                                          + Add another item today
                                      </button>
                                  </div>`}
                        </div>
                        <div class="sh-foot">
                            <div class="tot">
                                <b>${total}</b>
                                <span class="muted">pcs</span>${value > 0
                                    ? html`<div class="small muted">
                                          ${rs(value)}
                                      </div>`
                                    : null}
                            </div>
                            <button
                                class="btn"
                                disabled=${busy}
                                onClick=${() => save(false)}
                            >
                                Save
                            </button>
                            ${hasNext &&
                            html`<button
                                class="btn primary"
                                disabled=${busy}
                                onClick=${() => save(true)}
                            >
                                Save & next shop
                            </button>`}
                        </div>
                    </div>
                </div>`;
            }

            /*  REPORTS  */
            /*  DATE-RANGE REPORT: income, credit, returns, shop by shop  */
            const dstr = (d) =>
                `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
            function quickRange(k) {
                const n = new Date();
                const d = new Date(n.getFullYear(), n.getMonth(), n.getDate());
                if (k === "today") return [dstr(d), dstr(d)];
                if (k === "yesterday") {
                    d.setDate(d.getDate() - 1);
                    return [dstr(d), dstr(d)];
                }
                if (k === "week") {
                    const s = new Date(d);
                    s.setDate(d.getDate() - ((d.getDay() + 6) % 7));
                    return [dstr(s), dstr(d)];
                }
                if (k === "month")
                    return [
                        dstr(new Date(d.getFullYear(), d.getMonth(), 1)),
                        dstr(d),
                    ];
                if (k === "last")
                    return [
                        dstr(new Date(d.getFullYear(), d.getMonth() - 1, 1)),
                        dstr(new Date(d.getFullYear(), d.getMonth(), 0)),
                    ];
            }
            function RangeReport({ routes, shops, items, acc, limit, toast }) {
                const [from, setFrom] = useState(
                    ls.get("bh-from", quickRange("month")[0]),
                );
                const [to, setTo] = useState(ls.get("bh-to", todayStr()));
                const [rid, setRid] = useState("all");
                const [bills, setBills] = useState(null);
                const [days, setDays] = useState(null);
                const [collect, setCollect] = useState(null); // {key, amt}
                useEffect(() => {
                    ls.set("bh-from", from);
                    ls.set("bh-to", to);
                }, [from, to]);
                useEffect(() => {
                    if (!from || !to || from > to) return;
                    setBills(null);
                    setDays(null);
                    const w = [
                        ["date", ">=", from],
                        ["date", "<=", to],
                    ];
                    const u1 = Store.watch("bills", setBills, w, () =>
                        toast("Could not load bills"),
                    );
                    const u2 = Store.watch("days", setDays, w, () => {});
                    return () => {
                        u1();
                        u2();
                    };
                }, [from, to]);
                const shopById = useMemo(
                    () => Object.fromEntries(shops.map((s) => [s.id, s])),
                    [shops],
                );
                const routeById = useMemo(
                    () => Object.fromEntries(routes.map((r) => [r.id, r])),
                    [routes],
                );
                const pick = (arr) =>
                    (arr || []).filter(
                        (d) =>
                            (rid === "all" || d.routeId === rid) &&
                            (!limit || limit.includes(d.routeId)),
                    );

                const S = {},
                    ret = {},
                    daily = {},
                    creditList = [];
                const shopRow = (sid, routeId, name) =>
                    S[sid] ||
                    (S[sid] = {
                        sid,
                        routeId,
                        name:
                            (shopById[sid] && shopById[sid].name) ||
                            name ||
                            sid,
                        days: new Set(),
                        pcs: 0,
                        ret: 0,
                        retVal: 0,
                        sales: 0,
                        paid: 0,
                        bal: 0,
                        bills: 0,
                    });
                const dayRow = (d) =>
                    daily[d] ||
                    (daily[d] = {
                        date: d,
                        pcs: 0,
                        sales: 0,
                        paid: 0,
                        bal: 0,
                        ret: 0,
                    });
                pick(days).forEach((d) =>
                    Object.entries(d.shops || {}).forEach(([sid, o]) => {
                        const q = qtyOf(o);
                        if (q <= 0) return;
                        const r = shopRow(sid, d.routeId);
                        r.days.add(d.date);
                        r.pcs += q;
                        dayRow(d.date).pcs += q;
                    }),
                );
                pick(bills).forEach((b) =>
                    Object.entries(b.shops || {}).forEach(([sid, sb]) => {
                        if (!sb.lines || !sb.lines.length) return;
                        const pi = payInfo(sb);
                        const r = shopRow(sid, b.routeId, sb.shopName);
                        const dr = dayRow(b.date);
                        r.bills++;
                        r.sales += pi.total;
                        r.paid += pi.paid;
                        r.bal += pi.balance;
                        dr.sales += pi.total;
                        dr.paid += pi.paid;
                        dr.bal += pi.balance;
                        sb.lines.forEach((l) => {
                            const n = Number(l.ret) || 0;
                            if (!n) return;
                            r.ret += n;
                            r.retVal += n * (Number(l.price) || 0);
                            dr.ret += n;
                            const it =
                                ret[l.id] ||
                                (ret[l.id] = { name: l.name, pcs: 0, val: 0 });
                            it.pcs += n;
                            it.val += n * (Number(l.price) || 0);
                        });
                        if (pi.balance > 0)
                            creditList.push({
                                key: b.id + "/" + sid,
                                date: b.date,
                                routeId: b.routeId,
                                sid,
                                bill: sb,
                                pi,
                                name: r.name,
                            });
                    }),
                );
                const rOrder = (id) =>
                    (routeById[id] && routeById[id].order) || 99;
                const rows = Object.values(S).sort(
                    (a, b) =>
                        rOrder(a.routeId) - rOrder(b.routeId) ||
                        ((shopById[a.sid] || {}).order || 99) -
                            ((shopById[b.sid] || {}).order || 99),
                );
                const T = rows.reduce(
                    (t, r) => ({
                        pcs: t.pcs + r.pcs,
                        ret: t.ret + r.ret,
                        retVal: t.retVal + r.retVal,
                        sales: t.sales + r.sales,
                        paid: t.paid + r.paid,
                        bal: t.bal + r.bal,
                        bills: t.bills + r.bills,
                    }),
                    {
                        pcs: 0,
                        ret: 0,
                        retVal: 0,
                        sales: 0,
                        paid: 0,
                        bal: 0,
                        bills: 0,
                    },
                );
                creditList.sort((a, b) =>
                    a.date < b.date
                        ? -1
                        : a.date > b.date
                          ? 1
                          : a.name.localeCompare(b.name),
                );
                const retRows = Object.values(ret).sort(
                    (a, b) => b.pcs - a.pcs,
                );
                const dayRows = Object.values(daily).sort((a, b) =>
                    a.date < b.date ? -1 : 1,
                );
                const loading = bills === null || days === null;
                const label = `${from}_to_${to}${rid === "all" ? "" : "-" + slug((routeById[rid] || {}).name || "")}`;

                async function dlShops() {
                    const out = [
                        [
                            "Shop",
                            "Route",
                            "Order days",
                            "Ordered pcs",
                            "Bills",
                            "Returned pcs",
                            "Return value",
                            "Sales",
                            "Collected",
                            "Credit balance",
                        ],
                    ];
                    rows.forEach((r) =>
                        out.push([
                            r.name,
                            (routeById[r.routeId] || {}).name || "",
                            r.days.size,
                            r.pcs,
                            r.bills,
                            r.ret,
                            r.retVal,
                            r.sales,
                            r.paid,
                            r.bal,
                        ]),
                    );
                    out.push([
                        "Total",
                        "",
                        "",
                        T.pcs,
                        T.bills,
                        T.ret,
                        T.retVal,
                        T.sales,
                        T.paid,
                        T.bal,
                    ]);
                    const e = await saveFile(`shops-${label}.csv`, toCsv(out));
                    if (e) toast(e);
                }
                async function dlCredit() {
                    const out = [
                        [
                            "Date",
                            "Shop",
                            "Route",
                            "Bill total",
                            "Paid",
                            "Balance",
                            "Status",
                        ],
                    ];
                    creditList.forEach((c) =>
                        out.push([
                            c.date,
                            c.name,
                            (routeById[c.routeId] || {}).name || "",
                            c.pi.total,
                            c.pi.paid,
                            c.pi.balance,
                            PAY_LABEL[c.pi.status],
                        ]),
                    );
                    const e = await saveFile(`credit-${label}.csv`, toCsv(out));
                    if (e) toast(e);
                }
                const shopExportRows = [
                    [
                        "Shop",
                        "Route",
                        "Order days",
                        "Ordered pcs",
                        "Bills",
                        "Returned pcs",
                        "Return value",
                        "Sales",
                        "Collected",
                        "Credit balance",
                    ],
                    ...rows.map((r) => [
                        r.name,
                        (routeById[r.routeId] || {}).name || "",
                        r.days.size,
                        r.pcs,
                        r.bills,
                        r.ret,
                        r.retVal,
                        r.sales,
                        r.paid,
                        r.bal,
                    ]),
                    [
                        "Total",
                        "",
                        "",
                        T.pcs,
                        T.bills,
                        T.ret,
                        T.retVal,
                        T.sales,
                        T.paid,
                        T.bal,
                    ],
                ];
                const creditExportRows = [
                    ["Date", "Shop", "Route", "Bill total", "Paid", "Balance", "Status"],
                    ...creditList.map((c) => [
                        c.date,
                        c.name,
                        (routeById[c.routeId] || {}).name || "",
                        c.pi.total,
                        c.pi.paid,
                        c.pi.balance,
                        PAY_LABEL[c.pi.status],
                    ]),
                ];
                const returnExportRows = [
                    ["Item", "Returned pcs", "Return value"],
                    ...retRows.map((r) => [r.name, r.pcs, r.val]),
                    ["Total", T.ret, T.retVal],
                ];
                const dailyExportRows = [
                    ["Date", "Ordered pcs", "Sales", "Collected", "Credit", "Returned pcs"],
                    ...dayRows.map((d) => [
                        d.date,
                        d.pcs,
                        d.sales,
                        d.paid,
                        d.bal,
                        d.ret,
                    ]),
                ];
                const rangeSubtitle = `${niceDate(from)} to ${niceDate(to)} · ${rid === "all" ? "All routes" : (routeById[rid] || {}).name || "Selected route"}`;
                function exportRangeExcel() {
                    const error = saveWorkbook(`reports-${label}.xlsx`, [
                        {
                            name: "Summary",
                            rows: [
                                ["Metric", "Value"],
                                ["Sales", T.sales],
                                ["Collected", T.paid],
                                ["Credit outstanding", T.bal],
                                ["Bills", T.bills],
                                ["Ordered pieces", T.pcs],
                                ["Returned pieces", T.ret],
                                ["Return value", T.retVal],
                            ],
                        },
                        { name: "Shops", rows: shopExportRows },
                        { name: "Credit", rows: creditExportRows },
                        { name: "Returns", rows: returnExportRows },
                        { name: "Day by day", rows: dailyExportRows },
                    ]);
                    if (error) toast(error);
                }
                function exportRangePdf() {
                    const error = printDocument(
                        "Income, credit and returns",
                        rangeSubtitle,
                        [
                            {
                                heading: "Summary",
                                columns: ["Metric", "Value"],
                                rows: [
                                    ["Sales", rs(T.sales)],
                                    ["Collected", rs(T.paid)],
                                    ["Credit outstanding", rs(T.bal)],
                                    ["Bills", T.bills],
                                    ["Ordered pieces", T.pcs],
                                    ["Returned pieces", T.ret],
                                    ["Return value", rs(T.retVal)],
                                ],
                            },
                            { heading: "Shop by shop", columns: shopExportRows[0], rows: shopExportRows.slice(1) },
                            { heading: "Credit bills", columns: creditExportRows[0], rows: creditExportRows.slice(1) },
                            { heading: "Returned items", columns: returnExportRows[0], rows: returnExportRows.slice(1) },
                            { heading: "Day by day", columns: dailyExportRows[0], rows: dailyExportRows.slice(1) },
                        ],
                    );
                    if (error) toast(error);
                }
                async function doCollect(c) {
                    if (!(Number(collect.amt) > 0)) {
                        toast("Enter the amount collected");
                        return;
                    }
                    try {
                        await recordPayment(
                            c.date,
                            c.routeId,
                            c.sid,
                            c.bill,
                            "collect",
                            collect.amt,
                        );
                        toast(
                            `Collected ${rs(Number(collect.amt))} from ${c.name}`,
                        );
                        setCollect(null);
                    } catch (e) {
                        toast("Could not save the payment");
                    }
                }

                return html`<${React.Fragment}>
    <div class="panel" style=${{ gap: "10px" }}>
      <div class="toolbar">
        <label class="label" for="rfrom">From</label><input id="rfrom" class="sel" type="date" value=${from} onChange=${(e) => e.target.value && setFrom(e.target.value)}/>
        <label class="label" for="rto">To</label><input id="rto" class="sel" type="date" value=${to} onChange=${(e) => e.target.value && setTo(e.target.value)}/>
        <select id="rroute" class="sel" aria-label="Route" value=${rid} onChange=${(e) => setRid(e.target.value)}>
          <option value="all">All routes</option>${routes.map((r) => html`<option key=${r.id} value=${r.id}>${r.name}</option>`)}</select>
      </div>
      <div class="chips">${[
          ["today", "Today"],
          ["yesterday", "Yesterday"],
          ["week", "This week"],
          ["month", "This month"],
          ["last", "Last month"],
      ].map(([k, l]) => {
          const [a, b] = quickRange(k);
          return html`<button
              key=${k}
              class="chip"
              style=${{
                  minHeight: "32px",
                  padding: "4px 12px",
                  fontSize: "13px",
              }}
              aria-pressed=${from === a && to === b}
              onClick=${() => {
                  setFrom(a);
                  setTo(b);
              }}
          >
              ${l}
          </button>`;
      })}</div>
            <div class="toolbar">
                <button class="btn sm" disabled=${loading || from > to} onClick=${exportRangeExcel}>Download Excel</button>
                <button class="btn sm" disabled=${loading || from > to} onClick=${exportRangePdf}>Print / Save PDF</button>
            </div>
    </div>
    ${
        from > to
            ? html`<div class="notice">
                  The start date is after the end date.
              </div>`
            : loading
              ? html`<div class="empty">
                    Loading ${niceDate(from)} to ${niceDate(to)}…
                </div>`
              : html`<${React.Fragment}>
      <div class="stats">
        <div class="stat"><span class="label">Sales (billed)</span><b style=${{ fontSize: "22px" }}>${rs(T.sales)}</b><span class="small muted">${T.bills} bills</span></div>
        <div class="stat"><span class="label">Income collected</span><b style=${{ fontSize: "22px", color: "var(--accent)" }}>${rs(T.paid)}</b></div>
        <div class="stat"><span class="label">Credit outstanding</span><b style=${{ fontSize: "22px", color: T.bal > 0 ? "var(--warn)" : "inherit" }}>${rs(T.bal)}</b><span class="small muted">${creditList.length} bills</span></div>
        <div class="stat"><span class="label">Returns</span><b>${T.ret} <span class="muted" style=${{ fontSize: "15px" }}>pcs</span></b><span class="small muted">${rs(T.retVal)}</span></div>
        <div class="stat"><span class="label">Ordered</span><b>${T.pcs.toLocaleString()} <span class="muted" style=${{ fontSize: "15px" }}>pcs</span></b></div>
      </div>
      ${
          !rows.length
              ? html`<div class="empty">
                    No orders or bills between ${niceDate(from)} and
                    ${niceDate(to)}.
                </div>`
              : html`<${React.Fragment}>
      <div class="head"><h2>Shop by shop</h2><button class="btn sm" onClick=${dlShops}>Download CSV</button></div>
      <div class="tablewrap"><table>
        <thead><tr><th>Shop</th><th>Route</th><th>Days</th><th>Ordered</th><th>Returned</th><th>Sales</th><th>Collected</th><th>Credit</th></tr></thead>
        <tbody>${rows.map(
            (r) =>
                html`<tr key=${r.sid}>
                    <td><b>${r.name}</b></td>
                    <td>${(routeById[r.routeId] || {}).name || ""}</td>
                    <td>${r.days.size}</td>
                    <td>${r.pcs}</td>
                    <td class=${r.ret ? "" : "zero"}>${r.ret || "–"}</td>
                    <td>${r.sales.toLocaleString("en-LK")}</td>
                    <td>${r.paid.toLocaleString("en-LK")}</td>
                    <td
                        style=${{
                            color: r.bal > 0 ? "var(--warn)" : "",
                            fontWeight: r.bal > 0 ? 700 : 400,
                        }}
                    >
                        ${r.bal ? r.bal.toLocaleString("en-LK") : "–"}
                    </td>
                </tr>`,
        )}</tbody>
        <tfoot><tr><td>Total</td><td></td><td></td><td>${T.pcs}</td><td>${T.ret}</td><td>${rs(T.sales)}</td><td>${rs(T.paid)}</td><td>${rs(T.bal)}</td></tr></tfoot>
      </table></div>

      <div class="head"><h2>Credit bills to collect</h2>${creditList.length > 0 && html`<button class="btn sm" onClick=${dlCredit}>Download CSV</button>`}</div>
      ${
          creditList.length
              ? html`<div class="list">
                    ${creditList.map(
                        (c) =>
                            html`<div
                                class="row"
                                key=${c.key}
                                style=${{ cursor: "default", flexWrap: "wrap" }}
                            >
                                <span class="main"
                                    ><div class="name">${c.name}</div>
                                    <div class="sub">
                                        ${niceDate(c.date)} ·
                                        ${(routeById[c.routeId] || {}).name ||
                                        ""}
                                        · bill ${rs(c.pi.total)}, paid
                                        ${rs(c.pi.paid)}
                                    </div></span
                                >
                                <span
                                    style=${{
                                        fontWeight: 700,
                                        color: "var(--warn)",
                                    }}
                                    >${rs(c.pi.balance)}</span
                                ><${PayPill} status=${c.pi.status} />
                                ${collect && collect.key === c.key
                                    ? html`<span class="toolbar"
                                          ><input
                                              class="mini wide"
                                              id=${"col-" +
                                              c.key.replace(/[^a-z0-9]/gi, "")}
                                              type="number"
                                              inputmode="decimal"
                                              min="0"
                                              placeholder=${String(
                                                  c.pi.balance,
                                              )}
                                              value=${collect.amt}
                                              onChange=${(e) =>
                                                  setCollect({
                                                      ...collect,
                                                      amt: e.target.value,
                                                  })}
                                              autofocus
                                          />
                                          <button
                                              class="btn sm primary"
                                              onClick=${() => doCollect(c)}
                                          >
                                              Save</button
                                          ><button
                                              class="btn sm"
                                              onClick=${() => setCollect(null)}
                                          >
                                              Cancel
                                          </button></span
                                      >`
                                    : acc.can("bills_payments")
                                      ? html`<button
                                            class="btn sm"
                                            onClick=${() =>
                                                setCollect({
                                                    key: c.key,
                                                    amt: String(c.pi.balance),
                                                })}
                                        >
                                            Collect
                                        </button>`
                                      : null}
                            </div>`,
                    )}
                </div>`
              : html`<div class="empty">
                    No credit outstanding in this period.
                </div>`
      }

      <div class="two" style=${{ alignItems: "start" }}>
        <div style=${{ display: "flex", flexDirection: "column", gap: "10px", minWidth: 0 }}><h2 style=${{ fontSize: "18px" }}>Returned items</h2>
          ${
              retRows.length
                  ? html`<div class="tablewrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Pcs</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${retRows.map(
                                    (r) =>
                                        html`<tr key=${r.name}>
                                            <td>${r.name}</td>
                                            <td>${r.pcs}</td>
                                            <td>
                                                ${r.val.toLocaleString("en-LK")}
                                            </td>
                                        </tr>`,
                                )}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>Total</td>
                                    <td>${T.ret}</td>
                                    <td>${rs(T.retVal)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>`
                  : html`<div class="empty">No returns.</div>`
          }</div>
        <div style=${{ display: "flex", flexDirection: "column", gap: "10px", minWidth: 0 }}><h2 style=${{ fontSize: "18px" }}>Day by day</h2>
          <div class="tablewrap"><table><thead><tr><th>Date</th><th>Pcs</th><th>Sales</th><th>Collected</th><th>Credit</th></tr></thead>
            <tbody>${dayRows.map(
                (d) =>
                    html`<tr key=${d.date}>
                        <td>${niceDate(d.date)}</td>
                        <td>${d.pcs}</td>
                        <td>${d.sales.toLocaleString("en-LK")}</td>
                        <td>${d.paid.toLocaleString("en-LK")}</td>
                        <td>${d.bal ? d.bal.toLocaleString("en-LK") : "–"}</td>
                    </tr>`,
            )}</tbody></table></div></div>
      </div>
      </${React.Fragment}>`
      }
    </${React.Fragment}>`
    }
  </${React.Fragment}>`;
            }

            function ReportsView({
                routes,
                shops,
                items,
                itemList,
                days,
                date,
                acc,
                limit,
                toast,
            }) {
                const [view, setView] = useState(ls.get("bh-rview", "all"));
                useEffect(() => ls.set("bh-rview", view), [view]);
                const shopById = useMemo(
                    () => Object.fromEntries(shops.map((s) => [s.id, s])),
                    [shops],
                );

                // per route: item -> qty; per shop: q
                const byRoute = {};
                routes.forEach(
                    (r) => (byRoute[r.id] = { items: {}, shops: {} }),
                );
                days.forEach((d) => {
                    const R =
                        byRoute[d.routeId] ||
                        (byRoute[d.routeId] = { items: {}, shops: {} });
                    Object.entries(d.shops || {}).forEach(([sid, o]) => {
                        if (qtyOf(o) <= 0) return;
                        R.shops[sid] = o;
                        Object.entries(o.q || {}).forEach(([k, v]) => {
                            v = Number(v) || 0;
                            if (v > 0) R.items[k] = (R.items[k] || 0) + v;
                        });
                    });
                });
                const grand = {};
                Object.values(byRoute).forEach((R) =>
                    Object.entries(R.items).forEach(
                        ([k, v]) => (grand[k] = (grand[k] || 0) + v),
                    ),
                );
                const usedItems = itemList
                    .filter((it) => grand[it.id] > 0)
                    .concat(
                        Object.keys(grand)
                            .filter((k) => !items[k])
                            .map((k) => ({ id: k, name: k, price: 0 })),
                    );
                const totalPcs = sum(Object.values(grand));
                let totalValue = 0;
                Object.values(byRoute).forEach((R) =>
                    Object.entries(R.shops).forEach(([sid, o]) =>
                        Object.entries(o.q || {}).forEach(([k, v]) => {
                            totalValue +=
                                (Number(v) || 0) *
                                priceOf(shopById[sid], items, k);
                        }),
                    ),
                );
                const shopsDone = sum(
                    Object.values(byRoute).map(
                        (R) => Object.keys(R.shops).length,
                    ),
                );

                const tabs = [
                    ["range", "Income, credit & returns"],
                    ["all", "Day: all routes"],
                    ["route", "Day: route sheets"],
                ];
                const cur = ["all", "route"].includes(view) ? view : "range";
                const chips = html`<div
                    class="chips"
                    role="group"
                    aria-label="Report"
                >
                    ${tabs.map(
                        ([k, l]) =>
                            html`<button
                                key=${k}
                                class="chip"
                                aria-pressed=${cur === k}
                                onClick=${() => setView(k)}
                            >
                                ${l}
                            </button>`,
                    )}
                </div>`;
                if (cur === "range")
                    return html`<${React.Fragment}>${chips}<${RangeReport} routes=${routes} shops=${shops} items=${items} acc=${acc} limit=${limit} toast=${toast}/></${React.Fragment}>`;
                return html`<${React.Fragment}>
    ${chips}
    <div class="small muted">Showing ${niceDate(date)}. Change the day at the top.</div>
    <div class="stats">
      <div class="stat"><span class="label">Total pieces</span><b>${totalPcs.toLocaleString()}</b></div>
      <div class="stat"><span class="label">Shops entered</span><b>${shopsDone}<span class="muted" style=${{ fontSize: "16px" }}> / ${shops.length}</span></b></div>
      <div class="stat"><span class="label">Items ordered</span><b>${usedItems.length}</b></div>
      ${totalValue > 0 && html`<div class="stat"><span class="label">Order value</span><b style=${{ fontSize: "22px" }}>${rs(totalValue)}</b></div>`}
    </div>
    ${cur === "all" && html`<${AllRoutes} routes=${routes} byRoute=${byRoute} grand=${grand} usedItems=${usedItems} date=${date} toast=${toast} />`}
    ${cur === "route" && html`<${RouteSheet} routes=${routes} shops=${shops} byRoute=${byRoute} items=${items} itemList=${itemList} date=${date} toast=${toast} />`}
  </${React.Fragment}>`;
            }

            function AllRoutes({
                routes,
                byRoute,
                grand,
                usedItems,
                date,
                toast,
            }) {
                if (!usedItems.length)
                    return html`<div class="empty">
                        No orders entered for ${niceDate(date)} yet. Totals for
                        every item across all routes appear here as orders come
                        in.
                    </div>`;
                const exportRows = [
                        ["Item", ...routes.map((r) => r.name), "Total"],
                        ...usedItems.map((it) => [
                            it.name,
                            ...routes.map(
                                (r) => byRoute[r.id].items[it.id] || 0,
                            ),
                            grand[it.id],
                        ]),
                        [
                            "Total",
                            ...routes.map((r) =>
                                sum(Object.values(byRoute[r.id].items)),
                            ),
                            sum(Object.values(grand)),
                        ],
                    ];
                async function dl() {
                    const err = await saveFile(`all-routes-${date}.csv`, toCsv(exportRows));
                    if (err) toast(err);
                }
                function exportExcel() {
                    const error = saveWorkbook(`all-routes-${date}.xlsx`, [
                        { name: "All routes", rows: exportRows },
                    ]);
                    if (error) toast(error);
                }
                function exportPdf() {
                    const error = printDocument(
                        "All items, all routes",
                        niceDate(date),
                        [{
                            heading: "Route totals",
                            columns: exportRows[0],
                            rows: exportRows.slice(1),
                        }],
                    );
                    if (error) toast(error);
                }
                return html`<${React.Fragment}>
    <div class="head"><h2>All items, all routes</h2><div class="toolbar"><button class="btn sm" onClick=${dl}>CSV</button><button class="btn sm" onClick=${exportExcel}>Excel</button><button class="btn sm" onClick=${exportPdf}>Print / Save PDF</button></div></div>
    <div class="tablewrap"><table>
      <thead><tr><th>Item</th>${routes.map((r) => html`<th key=${r.id}>${r.name}</th>`)}<th>Total</th></tr></thead>
      <tbody>${usedItems.map(
          (it) =>
              html`<tr key=${it.id}>
                  <td><b>${it.name}</b></td>
                  ${routes.map((r) => {
                      const v = byRoute[r.id].items[it.id] || 0;
                      return html`<td key=${r.id} class=${v ? "" : "zero"}>
                          ${v || "–"}
                      </td>`;
                  })}
                  <td class="grand">${grand[it.id]}</td>
              </tr>`,
      )}</tbody>
      <tfoot><tr><td>Total pcs</td>${routes.map((r) => html`<td key=${r.id}>${sum(Object.values(byRoute[r.id].items))}</td>`)}<td>${sum(Object.values(grand))}</td></tr></tfoot>
    </table></div></${React.Fragment}>`;
            }

            function RouteSheet({
                routes,
                shops,
                byRoute,
                items,
                itemList,
                date,
                toast,
            }) {
                const [rid, setRid] = useState(
                    ls.get("bh-route", routes[0] && routes[0].id),
                );
                const route = routes.find((r) => r.id === rid) || routes[0];
                if (!route)
                    return html`<div class="empty">No routes yet.</div>`;
                const R = byRoute[route.id] || { items: {}, shops: {} };
                const rShops = shops.filter((s) => s.routeId === route.id);
                const missing = rShops.filter((s) => !R.shops[s.id]);
                const used = itemList
                    .filter((it) => R.items[it.id] > 0)
                    .concat(
                        Object.keys(R.items)
                            .filter((k) => R.items[k] > 0 && !items[k])
                            .map((k) => ({ id: k, name: k, price: 0 })),
                    );
                const done = rShops.filter((s) => R.shops[s.id]);
                const totalsRows = [
                    ["Item", "Qty"],
                    ...used.map((item) => [item.name, R.items[item.id]]),
                    ["Total pcs", sum(Object.values(R.items))],
                ];
                const shopRows = [
                    ["Shop", ...used.map((item) => item.name), "Total pcs"],
                    ...done.map((shop) => {
                        const quantities = R.shops[shop.id].q || {};
                        return [
                            shop.name,
                            ...used.map((item) => Number(quantities[item.id]) || 0),
                            qtyOf(R.shops[shop.id]),
                        ];
                    }),
                    [
                        "Route total",
                        ...used.map((item) => R.items[item.id]),
                        sum(Object.values(R.items)),
                    ],
                ];
                async function dl() {
                    const err = await saveFile(
                        `${slug(route.name)}-${date}.csv`,
                        toCsv(shopRows),
                    );
                    if (err) toast(err);
                }
                function exportExcel() {
                    const error = saveWorkbook(`${slug(route.name)}-${date}.xlsx`, [
                        { name: "Route totals", rows: totalsRows },
                        { name: "Shop by shop", rows: shopRows },
                        {
                            name: "Not entered",
                            rows: [["Shop"], ...missing.map((shop) => [shop.name])],
                        },
                    ]);
                    if (error) toast(error);
                }
                function exportPdf() {
                    const error = printDocument(
                        `${route.name} load sheet`,
                        `${niceDate(date)}${route.driver ? ` · Driver: ${route.driver}` : ""}`,
                        [
                            { heading: "Load for the van", columns: totalsRows[0], rows: totalsRows.slice(1) },
                            { heading: "Shop by shop", columns: shopRows[0], rows: shopRows.slice(1) },
                            ...(missing.length
                                ? [{ heading: "Not entered", columns: ["Shop"], rows: missing.map((shop) => [shop.name]) }]
                                : []),
                        ],
                    );
                    if (error) toast(error);
                }
                function exportShopByShopPdf() {
                    const error = printShopByShopDocument(
                        `${route.name} load sheet`,
                        `${niceDate(date)}${route.driver ? ` · Driver: ${route.driver}` : ""}`,
                        done.map((shop) => {
                            const quantities =
                                (R.shops[shop.id] && R.shops[shop.id].q) || {};
                            return {
                                name: shop.name,
                                total: qtyOf(R.shops[shop.id]),
                                items: Object.entries(quantities)
                                    .filter(([, v]) => Number(v) > 0)
                                    .map(([k, v]) => ({
                                        name: (items[k] || {}).name || k,
                                        qty: Number(v),
                                    })),
                            };
                        }),
                    );
                    if (error) toast(error);
                }
                return html`<${React.Fragment}>
    <${RoutePicker} routes=${routes} value=${route.id} onChange=${setRid}/>
    <div class="head"><div><div class="label">${route.driver ? "Driver: " + route.driver : "Route"}</div><h2>${route.name} load sheet</h2></div>
      <div class="toolbar"><button class="btn sm" onClick=${dl} disabled=${!used.length}>CSV</button><button class="btn sm" onClick=${exportExcel} disabled=${!used.length}>Excel</button><button class="btn sm" onClick=${exportPdf} disabled=${!used.length}>Print / Save PDF</button></div></div>
    ${
        missing.length > 0 &&
        html`<div>
            <div class="label" style=${{ marginBottom: "6px" }}>
                Not entered yet (${missing.length})
            </div>
            <div class="missing">
                ${missing.map((s) => html`<span key=${s.id}>${s.name}</span>`)}
            </div>
        </div>`
    }
    ${
        used.length
            ? html`<${React.Fragment}>
      <div class="label">Load for the van</div>
      <div class="tablewrap"><table><thead><tr><th>Item</th><th>Qty</th></tr></thead>
        <tbody>${used.map(
            (it) =>
                html`<tr key=${it.id}>
                    <td><b>${it.name}</b></td>
                    <td class="grand">${R.items[it.id]}</td>
                </tr>`,
        )}</tbody>
        <tfoot><tr><td>Total pcs</td><td>${sum(Object.values(R.items))}</td></tr></tfoot></table></div>
      <div class="label">Shop by shop</div>
      <div class="cards">${done.map((s) => {
              const q = R.shops[s.id].q || {};
              return html`<div class="card" key=${s.id}>
                  <h3>
                      <span>${s.name}</span
                      ><span class="pill done"
                          >${qtyOf(R.shops[s.id])} pcs</span
                      >
                  </h3>
                  ${Object.entries(q)
                      .filter(([k, v]) => v > 0)
                      .map(
                          ([k, v]) =>
                              html`<div class="kv" key=${k}>
                                  <span>${(items[k] || {}).name || k}</span
                                  ><span>${v}</span>
                              </div>`,
                      )}
              </div>`;
          })}</div>
      <div class="toolbar"><button class="btn sm" onClick=${exportShopByShopPdf} disabled=${!done.length}>Print / Save PDF</button></div>
    </${React.Fragment}>`
            : html`<div class="empty">
                  No orders for ${route.name} on ${niceDate(date)} yet.
              </div>`
    }
  </${React.Fragment}>`;
            }

            /*  BILLS  */
            function BillsView({
                routes,
                shops,
                items,
                days,
                bills,
                date,
                acc,
                toast,
            }) {
                const canGen = acc.can("bills_generate"),
                    canPrice = acc.can("bills_prices"),
                    canRet = acc.can("bills_returns"),
                    canPay = acc.can("bills_payments");
                const [sel, setSel] = useState(ls.get("bh-broute", "all"));
                const [busy, setBusy] = useState(false);
                const [editing, setEditing] = useState(null); // {rid,sid,lines}
                useEffect(() => ls.set("bh-broute", sel), [sel]);
                const shopById = useMemo(
                    () => Object.fromEntries(shops.map((s) => [s.id, s])),
                    [shops],
                );
                const showRoutes =
                    sel === "all" ? routes : routes.filter((r) => r.id === sel);

                // per route: ordered shops, bills and their state
                const data = showRoutes.map((r) => {
                    const day = days.find((d) => d.routeId === r.id);
                    const bdoc = bills.find((b) => b.routeId === r.id);
                    const ordered = Object.entries(
                        (day && day.shops) || {},
                    ).filter(([sid, o]) => qtyOf(o) > 0);
                    const order = (sid) =>
                        (shopById[sid] && shopById[sid].order) || 999;
                    ordered.sort((a, b) => order(a[0]) - order(b[0]));
                    const rows = ordered.map(([sid, o]) => {
                        const b = bdoc && bdoc.shops ? bdoc.shops[sid] : null;
                        const has = b && b.lines && b.lines.length;
                        return {
                            sid,
                            order: o,
                            bill: has ? b : null,
                            stale: has && billSig(b) !== orderSig(o),
                        };
                    });
                    // bills for shops whose order was later removed
                    Object.entries((bdoc && bdoc.shops) || {}).forEach(
                        ([sid, b]) => {
                            if (
                                b.lines &&
                                b.lines.length &&
                                !rows.find((x) => x.sid === sid)
                            )
                                rows.push({
                                    sid,
                                    order: null,
                                    bill: b,
                                    stale: true,
                                });
                        },
                    );
                    return { route: r, rows };
                });
                const all = data.flatMap((d) => d.rows);
                const billed = all.filter((x) => x.bill).length,
                    waiting = all.filter((x) => !x.bill).length,
                    stale = all.filter((x) => x.bill && x.stale).length;
                const amount = sum(
                    all
                        .filter((x) => x.bill)
                        .map((x) => billTotal(x.bill.lines)),
                );
                const retPcs = sum(
                    all
                        .filter((x) => x.bill)
                        .flatMap((x) =>
                            x.bill.lines.map((l) => Number(l.ret) || 0),
                        ),
                );
                const pays = all
                    .filter((x) => x.bill)
                    .map((x) => payInfo(x.bill));
                const collected = sum(pays.map((p) => p.paid)),
                    credit = sum(pays.map((p) => p.balance));

                async function generate(onlyMissing) {
                    setBusy(true);
                    try {
                        let n = 0;
                        for (const { route, rows } of data) {
                            const bdoc = bills.find(
                                (b) => b.routeId === route.id,
                            );
                            const map = {};
                            rows.forEach((x) => {
                                if (onlyMissing && x.bill && !x.stale) return;
                                const shop = shopById[x.sid];
                                if (!x.order) {
                                    map[x.sid] = { lines: [], at: Date.now() };
                                    return;
                                }
                                map[x.sid] = {
                                    lines: buildLines(
                                        shop,
                                        x.order,
                                        items,
                                        x.bill && x.bill.lines,
                                    ),
                                    shopName: shop ? shop.name : x.sid,
                                    at: Date.now(),
                                };
                                n++;
                            });
                            if (Object.keys(map).length)
                                await Store.merge(`bills/${date}_${route.id}`, {
                                    date,
                                    routeId: route.id,
                                    shops: map,
                                });
                        }
                        toast(
                            n
                                ? `Generated ${n} bill${n > 1 ? "s" : ""}`
                                : "All bills are up to date",
                        );
                    } catch (e) {
                        toast("Could not generate bills, check connection");
                    }
                    setBusy(false);
                }
                async function saveEdit() {
                    const { rid, sid, lines } = editing;
                    const clean = lines.map((l) => ({
                        ...l,
                        ret: Math.min(
                            Math.max(0, parseInt(l.ret || "0", 10) || 0),
                            Number(l.qty) || 0,
                        ),
                        price: Number(l.price) || 0,
                    }));
                    try {
                        await Store.merge(`bills/${date}_${rid}`, {
                            date,
                            routeId: rid,
                            shops: {
                                [sid]: { lines: clean, editedAt: Date.now() },
                            },
                        });
                        toast("Bill updated");
                        setEditing(null);
                    } catch (e) {
                        toast("Could not save the bill");
                    }
                }
                function billText(route, sid, b) {
                    const name =
                        (shopById[sid] && shopById[sid].name) ||
                        b.shopName ||
                        sid;
                    const L = b.lines.map((l) => {
                        const net = lineNet(l);
                        return `${l.name}: ${l.qty}${l.ret ? ` - ${l.ret} ret = ${net}` : ""} x ${l.price} = ${net * l.price}`;
                    });
                    const pi = payInfo(b);
                    return [
                        `BILL — ${name}`,
                        `${niceDate(date)} · ${route.name}`,
                        "",
                        ...L,
                        "",
                        `Total: ${rs(pi.total)}`,
                        `Paid: ${rs(pi.paid)}`,
                        `Balance: ${rs(pi.balance)}`,
                    ].join("\n");
                }
                const billExportRows = [
                    [
                        "Date",
                        "Route",
                        "Shop",
                        "Item",
                        "Qty",
                        "Return",
                        "Net",
                        "Price",
                        "Amount",
                        "Bill total",
                        "Paid",
                        "Balance",
                        "Payment",
                    ],
                    ...data.flatMap(({ route, rows }) =>
                        rows.flatMap((entry) => {
                            if (!entry.bill) return [];
                            const name =
                                (shopById[entry.sid] && shopById[entry.sid].name) ||
                                entry.bill.shopName ||
                                entry.sid;
                            const info = payInfo(entry.bill);
                            return entry.bill.lines.map((line, index) => [
                                date,
                                route.name,
                                name,
                                line.name,
                                line.qty,
                                line.ret || 0,
                                lineNet(line),
                                line.price,
                                lineNet(line) * line.price,
                                index ? "" : info.total,
                                index ? "" : info.paid,
                                index ? "" : info.balance,
                                index ? "" : PAY_LABEL[info.status],
                            ]);
                        }),
                    ),
                ];
                const billsFilename = `bills-${sel === "all" ? "all-routes" : slug((routes.find((r) => r.id === sel) || {}).name || "route")}-${date}`;
                function exportBillsExcel() {
                    const error = saveWorkbook(`${billsFilename}.xlsx`, [
                        {
                            name: "Summary",
                            rows: [
                                ["Metric", "Value"],
                                ["Bills made", billed],
                                ["Bill total", amount],
                                ["Collected", collected],
                                ["Credit balance", credit],
                                ["Returned pieces", retPcs],
                            ],
                        },
                        { name: "Bill lines", rows: billExportRows },
                    ]);
                    if (error) toast(error);
                }
                function exportBillsPdf() {
                    const billPages = [];
                    const subtitle = `${niceDate(date)} · ${sel === "all" ? "All routes" : (routes.find((r) => r.id === sel) || {}).name || "Selected route"}`;
                    data.forEach(({ route, rows }) =>
                        rows.forEach((entry) => {
                            if (!entry.bill) return;
                            const name =
                                (shopById[entry.sid] && shopById[entry.sid].name) ||
                                entry.bill.shopName ||
                                entry.sid;
                            const info = payInfo(entry.bill);
                            const returned = sum(
                                entry.bill.lines.map(
                                    (line) => Number(line.ret) || 0,
                                ),
                            );
                            billPages.push({
                                itemCount: entry.bill.lines.length,
                                sections: [
                                    {
                                        heading: `${name} · ${route.name}`,
                                        columns: ["Item", "Qty", "Returned", "Net", "Unit price", "Amount"],
                                        rows: [
                                            ...entry.bill.lines.map((line) => [
                                                line.name,
                                                line.qty,
                                                line.ret || 0,
                                                lineNet(line),
                                                rs(line.price),
                                                rs(lineNet(line) * line.price),
                                            ]),
                                            ["Total", sum(entry.bill.lines.map((line) => Number(line.qty) || 0)), returned, "", "", rs(info.total)],
                                        ],
                                    },
                                    {
                                        heading: `Payment · ${name}`,
                                        columns: ["Paid", "Balance", "Status"],
                                        rows: [[rs(info.paid), rs(info.balance), PAY_LABEL[info.status]]],
                                    },
                                ],
                            });
                        }),
                    );
                    const error = printBillsDocument(
                        "Bills",
                        subtitle,
                        billPages,
                    );
                    if (error) toast(error);
                }
                function printSingleBill(route, sid, bill, lines) {
                    const name =
                        (shopById[sid] && shopById[sid].name) ||
                        bill.shopName ||
                        sid;
                    const printableBill = { ...bill, lines };
                    const info = payInfo(printableBill);
                    const error = printBillsDocument(
                        `Bill · ${name}`,
                        `${niceDate(date)} · ${route.name}`,
                        [
                            {
                                itemCount: lines.length,
                                sections: [
                                    {
                                        heading: "Items",
                                        columns: ["Item", "Qty", "Returned", "Net", "Unit price", "Amount"],
                                        rows: [
                                            ...lines.map((line) => [
                                                line.name,
                                                line.qty,
                                                line.ret || 0,
                                                lineNet(line),
                                                rs(line.price),
                                                rs(lineNet(line) * line.price),
                                            ]),
                                            ["Total", sum(lines.map((line) => Number(line.qty) || 0)), sum(lines.map((line) => Number(line.ret) || 0)), "", "", rs(info.total)],
                                        ],
                                    },
                                    {
                                        heading: "Payment",
                                        columns: ["Total", "Paid", "Balance", "Status"],
                                        rows: [[rs(info.total), rs(info.paid), rs(info.balance), PAY_LABEL[info.status]]],
                                    },
                                ],
                            },
                        ],
                    );
                    if (error) toast(error);
                }
                async function dl() {
                    const err = await saveFile(
                        `${billsFilename}.csv`,
                        toCsv(billExportRows),
                    );
                    if (err) toast(err);
                }

                if (!routes.length)
                    return html`<div class="empty">No routes yet.</div>`;
                return html`<${React.Fragment}>
    <div class="chips" role="group" aria-label="Route">
      <button class="chip" aria-pressed=${sel === "all"} onClick=${() => setSel("all")}>All routes</button>
      ${routes.map((r) => html`<button key=${r.id} class="chip" aria-pressed=${sel === r.id} onClick=${() => setSel(r.id)}>${r.name}</button>`)}
    </div>
    <div class="stats">
      <div class="stat"><span class="label">Bills made</span><b>${billed}<span class="muted" style=${{ fontSize: "16px" }}> / ${all.length}</span></b></div>
      <div class="stat"><span class="label">Bill total</span><b style=${{ fontSize: "22px" }}>${rs(amount)}</b></div>
      <div class="stat"><span class="label">Collected</span><b style=${{ fontSize: "22px", color: "var(--accent)" }}>${rs(collected)}</b></div>
      <div class="stat"><span class="label">Credit / due</span><b style=${{ fontSize: "22px", color: credit > 0 ? "var(--warn)" : "inherit" }}>${rs(credit)}</b></div>
      <div class="stat"><span class="label">Returned pcs</span><b>${retPcs}</b></div>
    </div>
    <div class="toolbar">
      ${
          canGen &&
          html`<button
              class="btn primary"
              disabled=${busy || !all.length || (!waiting && !stale)}
              onClick=${() => generate(true)}
          >
              ${waiting || stale
                  ? `Generate bills (${waiting + stale})`
                  : "All bills generated"}
          </button>`
      }
      ${canGen && billed > 0 && html`<button class="btn" disabled=${busy} onClick=${() => generate(false)}>Regenerate all with current prices</button>`}
    <button class="btn" disabled=${!billed} onClick=${dl}>CSV</button>
    <button class="btn" disabled=${!billed} onClick=${exportBillsExcel}>Download Excel</button>
    <button class="btn" disabled=${!billed} onClick=${exportBillsPdf}>Print / Save PDF</button>
    </div>
    ${stale > 0 && html`<div class="notice">${stale} bill${stale > 1 ? "s were" : " was"} made before the order changed. ${canGen ? "Generate bills to update them; returns already entered are kept." : "Ask the admin to update them."}</div>`}
    ${!all.length && html`<div class="empty">No orders for ${niceDate(date)} yet. Take orders first, then generate the bills here.</div>`}
    ${data
        .filter((d) => d.rows.length)
        .map(
            ({ route, rows }) =>
                html`<section
                    key=${route.id}
                    style=${{
                        display: "flex",
                        flexDirection: "column",
                        gap: "10px",
                    }}
                >
                    <div class="head">
                        <div>
                            <div class="label">
                                ${route.driver
                                    ? "Driver: " + route.driver
                                    : "Route"}
                            </div>
                            <h2>${route.name}</h2>
                        </div>
                        <span class="small muted"
                            >${rows.filter((x) => x.bill).length} bills ·
                            ${rs(
                                sum(
                                    rows
                                        .filter((x) => x.bill)
                                        .map((x) => billTotal(x.bill.lines)),
                                ),
                            )}</span
                        >
                    </div>
                    ${rows.filter((x) => !x.bill).length > 0 &&
                    html`<div>
                        <div class="label" style=${{ marginBottom: "6px" }}>
                            Ordered, bill not generated
                        </div>
                        <div class="missing">
                            ${rows
                                .filter((x) => !x.bill)
                                .map(
                                    (x) =>
                                        html`<span key=${x.sid}
                                            >${(shopById[x.sid] || {}).name ||
                                            x.sid}</span
                                        >`,
                                )}
                        </div>
                    </div>`}
                    <div class="cards bills">
                        ${rows
                            .filter((x) => x.bill)
                            .map((x) => {
                                const name =
                                    (shopById[x.sid] && shopById[x.sid].name) ||
                                    x.bill.shopName ||
                                    x.sid;
                                const ed =
                                    editing &&
                                    editing.sid === x.sid &&
                                    editing.rid === route.id;
                                const lines = ed ? editing.lines : x.bill.lines;
                                const setL = (i, p) =>
                                    setEditing({
                                        ...editing,
                                        lines: editing.lines.map((l, j) =>
                                            j === i ? { ...l, ...p } : l,
                                        ),
                                    });
                                const tot = sum(
                                    lines.map(
                                        (l) =>
                                            Math.max(
                                                0,
                                                (Number(l.qty) || 0) -
                                                    (parseInt(
                                                        l.ret || "0",
                                                        10,
                                                    ) || 0),
                                            ) * (Number(l.price) || 0),
                                    ),
                                );
                                const printThisBill = () =>
                                    printSingleBill(route, x.sid, x.bill, lines);
                                return html`<div class="bill" key=${x.sid}>
                                    <div class="bill-h">
                                        <div>
                                            <div class="label">Bill</div>
                                            <h3>${name}</h3>
                                            <div class="small muted">
                                                ${route.name} ·
                                                ${niceDate(date)}
                                            </div>
                                        </div>
                                        <div
                                            style=${{
                                                textAlign: "right",
                                                display: "flex",
                                                flexDirection: "column",
                                                gap: "4px",
                                                alignItems: "flex-end",
                                            }}
                                        >
                                            ${x.stale
                                                ? html`<span
                                                      class="pill"
                                                      style=${{
                                                          background:
                                                              "var(--warn-soft)",
                                                          color: "var(--warn)",
                                                      }}
                                                      >Order changed</span
                                                  >`
                                                : null}
                                            <${PayPill}
                                                status=${payInfo(x.bill).status}
                                            />
                                            ${x.bill.editedAt &&
                                            html`<span class="small muted"
                                                >Returns updated</span
                                            >`}
                                        </div>
                                    </div>
                                    <div style=${{ overflowX: "auto" }}>
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Qty</th>
                                                    <th>Ret</th>
                                                    <th>Net</th>
                                                    <th>Price</th>
                                                    <th>Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${lines.map((l, i) => {
                                                    const net = Math.max(
                                                        0,
                                                        (Number(l.qty) || 0) -
                                                            (parseInt(
                                                                l.ret || "0",
                                                                10,
                                                            ) || 0),
                                                    );
                                                    return html`<tr key=${l.id}>
                                                        <td>${l.name}</td>
                                                        <td>${l.qty}</td>
                                                        <td>
                                                            ${ed && canRet
                                                                ? html`<input
                                                                      class="mini"
                                                                      id=${"r-" +
                                                                      x.sid +
                                                                      "-" +
                                                                      l.id}
                                                                      type="number"
                                                                      inputmode="numeric"
                                                                      min="0"
                                                                      max=${l.qty}
                                                                      value=${l.ret ||
                                                                      ""}
                                                                      placeholder="0"
                                                                      onFocus=${(
                                                                          e,
                                                                      ) =>
                                                                          e.target.select()}
                                                                      onChange=${(
                                                                          e,
                                                                      ) =>
                                                                          setL(
                                                                              i,
                                                                              {
                                                                                  ret: e.target.value.replace(
                                                                                      /[^0-9]/g,
                                                                                      "",
                                                                                  ),
                                                                              },
                                                                          )}
                                                                  />`
                                                                : l.ret
                                                                  ? html`<span
                                                                        style=${{
                                                                            color: "var(--danger)",
                                                                            fontWeight: 600,
                                                                        }}
                                                                        >−${l.ret}</span
                                                                    >`
                                                                  : html`<span
                                                                        class="muted"
                                                                        >–</span
                                                                    >`}
                                                        </td>
                                                        <td><b>${net}</b></td>
                                                        <td>
                                                            ${ed && canPrice
                                                                ? html`<input
                                                                      class="mini"
                                                                      id=${"p-" +
                                                                      x.sid +
                                                                      "-" +
                                                                      l.id}
                                                                      type="number"
                                                                      inputmode="decimal"
                                                                      min="0"
                                                                      step="0.5"
                                                                      value=${l.price}
                                                                      onChange=${(
                                                                          e,
                                                                      ) =>
                                                                          setL(
                                                                              i,
                                                                              {
                                                                                  price: e
                                                                                      .target
                                                                                      .value,
                                                                              },
                                                                          )}
                                                                  />`
                                                                : l.price ||
                                                                  html`<span
                                                                      class="muted"
                                                                      >–</span
                                                                  >`}
                                                        </td>
                                                        <td>
                                                            ${(
                                                                net *
                                                                (Number(
                                                                    l.price,
                                                                ) || 0)
                                                            ).toLocaleString(
                                                                "en-LK",
                                                            )}
                                                        </td>
                                                    </tr>`;
                                                })}
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td>Total</td>
                                                    <td>
                                                        ${sum(
                                                            lines.map(
                                                                (l) =>
                                                                    Number(
                                                                        l.qty,
                                                                    ) || 0,
                                                            ),
                                                        )}
                                                    </td>
                                                    <td>
                                                        ${sum(
                                                            lines.map(
                                                                (l) =>
                                                                    parseInt(
                                                                        l.ret ||
                                                                            "0",
                                                                        10,
                                                                    ) || 0,
                                                            ),
                                                        ) || ""}
                                                    </td>
                                                    <td></td>
                                                    <td></td>
                                                    <td>${rs(tot)}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    ${!ed &&
                                    html`<${PayBox}
                                        bill=${x.bill}
                                        date=${date}
                                        rid=${route.id}
                                        sid=${x.sid}
                                        canPay=${canPay}
                                        toast=${toast}
                                    />`}
                                    <div class="toolbar">
                                        ${ed
                                            ? html`<button
                                                      class="btn primary sm"
                                                      onClick=${saveEdit}
                                                  >
                                                      Save bill</button
                                                  ><button
                                                      class="btn sm"
                                                      onClick=${() =>
                                                          setEditing(null)}
                                                  >
                                                      Cancel
                                                  </button>`
                                            : html`${(canRet || canPrice) &&
                                                  html`<button
                                                      class="btn sm"
                                                      onClick=${() =>
                                                          setEditing({
                                                              rid: route.id,
                                                              sid: x.sid,
                                                              lines: x.bill.lines.map(
                                                                  (l) => ({
                                                                      ...l,
                                                                      ret: l.ret
                                                                          ? String(
                                                                                l.ret,
                                                                            )
                                                                          : "",
                                                                  }),
                                                              ),
                                                          })}
                                                  >
                                                      ${canRet && canPrice
                                                          ? "Enter returns / edit prices"
                                                          : canRet
                                                            ? "Enter returns"
                                                            : "Edit prices"}
                                                  </button>`}
                                                  <button
                                                      class="btn sm ghost"
                                                      onClick=${async () =>
                                                          toast(
                                                              (await copyText(
                                                                  billText(
                                                                      route,
                                                                      x.sid,
                                                                      x.bill,
                                                                  ),
                                                              ))
                                                                  ? "Bill copied, paste it in WhatsApp or SMS"
                                                                  : "Copy is blocked here, select the bill text instead",
                                                          )}
                                                  >
                                                      Copy bill
                                                  </button>`}
                                        <button class="btn sm" onClick=${printThisBill}>
                                            Print / Save PDF
                                        </button>
                                    </div>
                                </div>`;
                            })}
                    </div>
                </section>`,
        )}
  </${React.Fragment}>`;
            }

            function PayBox({ bill, date, rid, sid, canPay, toast }) {
                const pi = payInfo(bill);
                const [mode, setMode] = useState(null); // 'partial' | 'collect'
                const [amt, setAmt] = useState("");
                const [busy, setBusy] = useState(false);
                async function go(kind, a) {
                    if (
                        (kind === "partial" || kind === "collect") &&
                        !(Number(a) > 0)
                    ) {
                        toast("Enter the amount received");
                        return;
                    }
                    setBusy(true);
                    try {
                        await recordPayment(date, rid, sid, bill, kind, a);
                        toast(
                            kind === "cash"
                                ? "Marked as paid in full"
                                : kind === "credit"
                                  ? "Marked as credit"
                                  : "Payment saved",
                        );
                        setMode(null);
                        setAmt("");
                    } catch (e) {
                        toast("Could not save the payment");
                    }
                    setBusy(false);
                }
                return html`<div class="paybox">
                    <div class="payline">
                        <span>Paid <b>${rs(pi.paid)}</b></span
                        ><span
                            >Balance
                            <b
                                style=${{
                                    color:
                                        pi.balance > 0
                                            ? "var(--warn)"
                                            : "var(--accent)",
                                }}
                                >${rs(pi.balance)}</b
                            ></span
                        >
                    </div>
                    ${!canPay
                        ? null
                        : mode
                          ? html`<div class="toolbar">
                                <label
                                    class="small muted"
                                    for=${"amt-" + rid + sid}
                                    >${mode === "collect"
                                        ? "Amount collected now"
                                        : "Amount received"}
                                    Rs.</label
                                >
                                <input
                                    class="mini wide"
                                    id=${"amt-" + rid + sid}
                                    type="number"
                                    inputmode="decimal"
                                    min="0"
                                    value=${amt}
                                    onChange=${(e) => setAmt(e.target.value)}
                                    autofocus
                                />
                                <button
                                    class="btn sm primary"
                                    disabled=${busy}
                                    onClick=${() => go(mode, amt)}
                                >
                                    Save
                                </button>
                                <button
                                    class="btn sm"
                                    onClick=${() => setMode(null)}
                                >
                                    Cancel
                                </button>
                            </div>`
                          : html`<div
                                class="seg"
                                role="group"
                                aria-label="Payment"
                            >
                                <button
                                    class="btn sm"
                                    aria-pressed=${pi.status === "paid"}
                                    disabled=${busy}
                                    onClick=${() => go("cash")}
                                >
                                    Cash · full
                                </button>
                                <button
                                    class="btn sm"
                                    aria-pressed=${pi.status === "partial"}
                                    disabled=${busy}
                                    onClick=${() => {
                                        setMode("partial");
                                        setAmt(
                                            pi.status === "partial"
                                                ? String(pi.paid)
                                                : "",
                                        );
                                    }}
                                >
                                    Partial
                                </button>
                                <button
                                    class="btn sm"
                                    aria-pressed=${pi.status === "credit"}
                                    disabled=${busy}
                                    onClick=${() => go("credit")}
                                >
                                    Credit
                                </button>
                                ${pi.balance > 0 &&
                                pi.paid > 0 &&
                                html`<button
                                    class="btn sm ghost"
                                    onClick=${() => {
                                        setMode("collect");
                                        setAmt("");
                                    }}
                                >
                                    + Collect
                                </button>`}
                            </div>`}
                </div>`;
            }

            /*  ADMIN  */
            /*  USERS & PERMISSIONS  */
            const PERMS = [
                ["orders", "Take orders", "Orders"],
                ["bills_view", "See bills", "Bills"],
                ["bills_generate", "Generate bills", "Bills"],
                ["bills_returns", "Enter returns", "Bills"],
                ["bills_payments", "Record payments & collect credit", "Bills"],
                ["bills_prices", "Change prices on a bill", "Bills"],
                ["reports", "See reports (income, credit, returns)", "Reports"],
                ["shops", "Manage shops & shop prices", "Admin"],
                ["routes", "Manage routes & drivers", "Admin"],
                ["items", "Manage items & default prices", "Admin"],
                ["users", "Manage users & permissions", "Admin"],
            ];
            const ADMIN_PERMS = ["shops", "routes", "items", "users"];
            const allPerms = (v) =>
                Object.fromEntries(PERMS.map(([k]) => [k, v]));
            const ROLES = {
                admin: { label: "Admin", perms: allPerms(true) },
                manager: {
                    label: "Manager",
                    perms: { ...allPerms(true), users: false },
                },
                salesman: {
                    label: "Salesman",
                    perms: {
                        ...allPerms(false),
                        orders: true,
                        bills_view: true,
                        bills_returns: true,
                        bills_payments: true,
                    },
                },
                viewer: {
                    label: "Viewer",
                    perms: {
                        ...allPerms(false),
                        bills_view: true,
                        reports: true,
                    },
                },
            };
            const roleOf = (perms) => {
                for (const [k, r] of Object.entries(ROLES))
                    if (PERMS.every(([p]) => !!perms[p] === !!r.perms[p]))
                        return k;
                return "custom";
            };
            const roleLabel = (k) =>
                k === "custom"
                    ? "Custom"
                    : k === "owner"
                      ? "Owner"
                      : (ROLES[k] || {}).label || k;

            function AccessScreen({ me, users, requests, toast }) {
                const doc = users.find((u) => u.id === me.id);
                const asked = requests.find((r) => r.id === me.id);
                const [busy, setBusy] = useState(false);
                async function ask() {
                    if (!me.id) {
                        toast("Sign in to Claude to request access");
                        return;
                    }
                    setBusy(true);
                    try {
                        await Store.set("requests/" + me.id, {
                            at: Date.now(),
                        });
                        toast("Request sent to the admin");
                    } catch (e) {
                        toast("Could not send the request");
                    }
                    setBusy(false);
                }
                return html`<div class="panel" style=${{ maxWidth: "520px" }}>
                    <h2 style=${{ fontSize: "20px" }}>
                        ${doc && doc.active === false
                            ? "Your access is turned off"
                            : "You need access"}
                    </h2>
                    <p class="muted" style=${{ margin: 0 }}>
                        ${doc && doc.active === false
                            ? "An admin turned off your account for this bakery. Ask them to turn it back on."
                            : "An admin has to add you before you can take orders or see bills. Send a request and they will choose what you can do."}
                    </p>
                    <div class="toolbar">
                        <span>Signed in as <b>${me.name || "you"}</b></span>
                    </div>
                    ${!(doc && doc.active === false) &&
                    (asked
                        ? html`<div
                              class="notice"
                              style=${{
                                  background: "var(--accent-soft)",
                                  color: "var(--accent)",
                              }}
                          >
                              Request sent. You'll get in as soon as the admin
                              approves it. Keep this page open or reload later.
                          </div>`
                        : html`<div>
                              <button
                                  class="btn primary"
                                  disabled=${busy}
                                  onClick=${ask}
                              >
                                  Request access
                              </button>
                          </div>`)}
                </div>`;
            }

            function PermEditor({ form, setForm, routes, shared, n0 = 0 }) {
                const role = roleOf(form.perms);
                const setP = (k, v) =>
                    setForm({ ...form, perms: { ...form.perms, [k]: v } });
                const rlist = form.routes === "all" ? [] : form.routes;
                return html`<${React.Fragment}>
      <div class="stephead"><span class="stepn">${n0 + 1}</span><span class="label">Role</span></div>
      <div class="chips">${Object.entries(ROLES).map(([k, r]) => html`<button key=${k} class="chip" aria-pressed=${role === k} onClick=${() => setForm({ ...form, perms: { ...r.perms } })}>${r.label}</button>`)}
        ${role === "custom" && html`<span class="chip" aria-pressed="true">Custom</span>`}</div>
      <div class="stephead"><span class="stepn">${n0 + 2}</span><span class="label">Permissions</span></div>
      ${["Orders", "Bills", "Reports", "Admin"].map(
          (g) =>
              html`<div key=${g} class="permgroup">
                  <div class="label">${g}</div>
                  ${PERMS.filter((p) => p[2] === g).map(
                      ([k, l]) =>
                          html`<label key=${k} class="perm"
                              ><input
                                  type="checkbox"
                                  id=${"perm-" + k}
                                  checked=${!!form.perms[k]}
                                  onChange=${(e) => setP(k, e.target.checked)}
                              /><span>${l}</span></label
                          >`,
                  )}
              </div>`,
      )}
      ${shared && ADMIN_PERMS.some((k) => form.perms[k]) && html`<div class="notice">Admin permissions also need “Can edit” access to this app. Give it from the Share menu, or their changes to shops, prices and users will be refused.</div>`}
      <div class="stephead"><span class="stepn">${n0 + 3}</span><span class="label">Routes they can see</span></div>
      <div class="chips">
        <button class="chip" aria-pressed=${form.routes === "all"} onClick=${() => setForm({ ...form, routes: "all" })}>All routes</button>
        ${routes.map(
            (r) =>
                html`<button
                    key=${r.id}
                    class="chip"
                    aria-pressed=${form.routes !== "all" &&
                    rlist.includes(r.id)}
                    onClick=${() =>
                        setForm({
                            ...form,
                            routes: rlist.includes(r.id)
                                ? rlist.filter((x) => x !== r.id)
                                : [...rlist, r.id],
                        })}
                >
                    ${r.name}
                </button>`,
        )}</div>
      ${form.routes !== "all" && !rlist.length && html`<div class="small" style=${{ color: "var(--danger)" }}>Pick at least one route, or choose All routes.</div>`}
      <label class="perm"><input type="checkbox" id="uactive" checked=${form.active} onChange=${(e) => setForm({ ...form, active: e.target.checked })}/><span><b>Account active</b>. Untick to block this person without deleting them.</span></label>
  </${React.Fragment}>`;
            }

            function UsersAdmin({
                routes,
                users,
                requests,
                userNs,
                me,
                editor,
                toast,
            }) {
                const [names, setNames] = useState({});
                const [q, setQ] = useState("");
                const [hits, setHits] = useState([]);
                const [form, setForm] = useState(null);
                const [del, setDel] = useState(null);
                const ids = [
                    ...users.map((u) => u.id),
                    ...requests.map((r) => r.id),
                ];
                useEffect(() => {
                    let on = true;
                    if (userNs && ids.length)
                        userNs.profiles(ids).then((p) => {
                            if (on) setNames(p);
                        });
                    return () => {
                        on = false;
                    };
                }, [ids.join(",")]);
                const nm = (id) =>
                    (names[id] && names[id].name) ||
                    (form && form.id === id && form.name) ||
                    "Someone";
                async function search(v) {
                    setQ(v);
                    if (!userNs) return;
                    const r = await userNs.search(v);
                    setHits(r || []);
                }
                const open = (id, name) => {
                    const u = users.find((x) => x.id === id);
                    setForm(
                        u
                            ? {
                                  id,
                                  name,
                                  perms: {
                                      ...allPerms(false),
                                      ...(u.perms || {}),
                                  },
                                  routes: u.routes || "all",
                                  active: u.active !== false,
                                  isNew: false,
                              }
                            : {
                                  id,
                                  name,
                                  perms: { ...ROLES.salesman.perms },
                                  routes: "all",
                                  active: true,
                                  isNew: true,
                              },
                    );
                    setQ("");
                    setHits([]);
                };
                async function save() {
                    try {
                        await Store.set("users/" + form.id, {
                            perms: form.perms,
                            routes: form.routes,
                            active: form.active,
                            updatedAt: Date.now(),
                        });
                        if (requests.find((r) => r.id === form.id))
                            await Store.del("requests/" + form.id).catch(
                                () => {},
                            );
                        toast(
                            `${nm(form.id)} saved as ${roleLabel(roleOf(form.perms))}`,
                        );
                        setForm(null);
                    } catch (e) {
                        toast(
                            "Could not save. Only people with edit access can change users.",
                        );
                    }
                }
                if (!userNs)
                    return html`<div class="empty">
                        People can't be looked up in this view. Open the app
                        from Claude to manage users.
                    </div>`;

                if (form) {
                    const role = roleOf(form.perms);
                    const setP = (k, v) =>
                        setForm({ ...form, perms: { ...form.perms, [k]: v } });
                    const rlist = form.routes === "all" ? [] : form.routes;
                    return html`<div class="panel">
                        <div class="head">
                            <div>
                                <div class="label">
                                    ${form.isNew ? "Add user" : "Edit user"}
                                </div>
                                <h2 style=${{ fontSize: "20px" }}>
                                    ${nm(form.id)}
                                </h2>
                            </div>
                            <button
                                class="btn sm"
                                onClick=${() => setForm(null)}
                            >
                                Back
                            </button>
                        </div>
                        <${PermEditor}
                            form=${form}
                            setForm=${setForm}
                            routes=${routes}
                            shared=${true}
                        />
                        <div class="toolbar">
                            <button
                                class="btn primary"
                                disabled=${form.routes !== "all" &&
                                !rlist.length}
                                onClick=${save}
                            >
                                Save user
                            </button>
                        </div>
                    </div>`;
                }

                return html`<${React.Fragment}>
    ${!editor && html`<div class="notice">You can see users, but saving changes needs “Can edit” access to this app.</div>`}
    ${
        requests.length > 0 &&
        html`<div class="panel">
            <h2 style=${{ fontSize: "18px" }}>
                Waiting for access (${requests.length})
            </h2>
            <div class="list">
                ${requests.map(
                    (r) =>
                        html`<div
                            class="row"
                            key=${r.id}
                            style=${{ cursor: "default", flexWrap: "wrap" }}
                        >
                            <span class="main"
                                ><div class="name">${nm(r.id)}</div>
                                <div class="sub">
                                    Asked
                                    ${r.at
                                        ? new Date(r.at).toLocaleString(
                                              "en-GB",
                                              {
                                                  day: "numeric",
                                                  month: "short",
                                                  hour: "2-digit",
                                                  minute: "2-digit",
                                              },
                                          )
                                        : ""}
                                </div></span
                            >
                            <button
                                class="btn sm primary"
                                onClick=${() => open(r.id, nm(r.id))}
                            >
                                Set up
                            </button>
                            <button
                                class="btn sm ghost"
                                style=${{ color: "var(--danger)" }}
                                onClick=${() =>
                                    Store.del("requests/" + r.id).then(
                                        () => toast("Request dismissed"),
                                        () => toast("Could not dismiss"),
                                    )}
                            >
                                Dismiss
                            </button>
                        </div>`,
                )}
            </div>
        </div>`
    }
    <div class="panel" style=${{ gap: "8px" }}><h2 style=${{ fontSize: "18px" }}>Add a person</h2>
      <div class="small muted">Search people in your Claude organization. They also need the app shared with them from the Share menu.</div>
      <input class="sel" id="usearch" placeholder="Type a name or email" value=${q} onFocus=${() => search(q)} onInput=${(e) => search(e.target.value)}/>
      ${
          hits.length > 0 &&
          html`<div class="list">
              ${hits.map(
                  (h) =>
                      html`<button
                          key=${h.id}
                          class="row"
                          onClick=${() => open(h.id, h.name)}
                      >
                          <img
                              src=${h.avatarUrl}
                              alt=""
                              width="28"
                              height="28"
                              style=${{ borderRadius: "50%" }}
                          />
                          <span class="main"
                              ><div class="name">
                                  ${h.name}${h.isMe ? " (you)" : ""}
                              </div>
                              <div class="sub">
                                  ${users.find((u) => u.id === h.id)
                                      ? "Already a user · edit"
                                      : "Add as user"}
                              </div></span
                          ><${Chev} />
                      </button>`,
              )}
          </div>`
      }
      ${q && !hits.length && html`<div class="small muted">No one found. If they're new, ask them to open the app and press “Request access”.</div>`}
    </div>
    <div class="list">
      ${
          me.isOwner &&
          html`<div class="row" style=${{ cursor: "default" }}>
              <span class="main"
                  ><div class="name">${me.name || "You"} (you)</div>
                  <div class="sub">Owner · full access to everything</div></span
              ><span class="pill done">Owner</span>
          </div>`
      }
      ${users.map((u) => {
          const r = roleOf(u.perms || {});
          const rl =
              u.routes === "all" || !u.routes
                  ? "All routes"
                  : u.routes
                        .map(
                            (id) =>
                                (routes.find((x) => x.id === id) || {}).name,
                        )
                        .filter(Boolean)
                        .join(", ") || "No routes";
          return html`<div
              class="row"
              key=${u.id}
              style=${{ cursor: "default", flexWrap: "wrap" }}
          >
              <span class="main"
                  ><div class="name">
                      ${nm(u.id)}${u.id === me.id ? " (you)" : ""}
                  </div>
                  <div class="sub">${roleLabel(r)} · ${rl}</div></span
              >
              ${u.active === false
                  ? html`<span class="pill pay-credit">Blocked</span>`
                  : html`<span class="pill done">${roleLabel(r)}</span>`}
              ${del === u.id
                  ? html`<span class="confirm"
                        >Remove ${nm(u.id)}?
                        <button
                            class="btn sm danger"
                            onClick=${() =>
                                Store.del("users/" + u.id).then(
                                    () => {
                                        toast("User removed");
                                        setDel(null);
                                    },
                                    () => toast("Could not remove"),
                                )}
                        >
                            Remove</button
                        ><button class="btn sm" onClick=${() => setDel(null)}>
                            Cancel
                        </button></span
                    >`
                  : html`<button
                            class="btn sm"
                            onClick=${() => open(u.id, nm(u.id))}
                        >
                            Edit</button
                        ><button
                            class="btn sm ghost"
                            style=${{ color: "var(--danger)" }}
                            onClick=${() => setDel(u.id)}
                        >
                            Remove
                        </button>`}
          </div>`;
      })}
      ${!users.length && !me.isOwner && html`<div class="row" style=${{ cursor: "default" }}><span class="muted">No users yet.</span></div>`}
    </div>
  </${React.Fragment}>`;
            }

            /*  LOGIN & USER MANAGEMENT (SQL / Laravel API)  */
            function AuthCard({ title, sub, children }) {
                return html`<div class="auth">
                    <div class="auth-card">
                        <div class="auth-brand">
                            <${Logo} />
                        </div>
                        <h2>${title}</h2>
                        ${sub && html`<p class="muted">${sub}</p>`}${children}
                    </div>
                </div>`;
            }
            function SetupAdmin({ onDone, onSyncSession }) {
                const [f, setF] = useState({
                    name: "",
                    username: "admin",
                    pass: "",
                    pass2: "",
                });
                const [err, setErr] = useState("");
                const [busy, setBusy] = useState(false);
                async function submit(e) {
                    e.preventDefault();
                    setErr("");
                    if (!f.name.trim() || !f.username.trim())
                        return setErr("Enter your name and a username.");
                    if (f.pass.length < minPass())
                        return setErr(
                            `Use a password of at least ${minPass()} characters.`,
                        );
                    if (f.pass !== f.pass2)
                        return setErr("The two passwords are different.");
                    setBusy(true);
                    try {
                        const user = await Store.server.setup({
                            name: f.name.trim(),
                            username: f.username.trim().toLowerCase(),
                            password: f.pass,
                            password_confirmation: f.pass2,
                        });
                        Store.setSession({ user, needsSetup: false });
                        onDone(user);
                    } catch (e) {
                        await Store.init().catch(() => {});
                        if (onSyncSession) onSyncSession();
                        setErr(e.message);
                        setBusy(false);
                    }
                }
                const set = (p) => setF({ ...f, ...p });
                return html`<${AuthCard} title="Create the admin account" sub="First time here. This account can manage shops, prices, bills and users.">
    <form class="form" onSubmit=${submit}>
      <div class="field"><label class="label" for="a-name">Your name</label><input id="a-name" value=${f.name} onChange=${(e) => set({ name: e.target.value })} autoFocus/></div>
      <div class="field"><label class="label" for="a-user">Username</label><input id="a-user" autoCapitalize="none" value=${f.username} onChange=${(e) => set({ username: e.target.value })}/></div>
      <div class="field"><label class="label" for="a-pass">Password</label><input id="a-pass" type="password" value=${f.pass} onChange=${(e) => set({ pass: e.target.value })}/></div>
      <div class="field"><label class="label" for="a-pass2">Confirm password</label><input id="a-pass2" type="password" value=${f.pass2} onChange=${(e) => set({ pass2: e.target.value })}/></div>
      ${err && html`<div class="auth-err" role="alert">${err}</div>`}
      <button class="btn primary" type="submit" disabled=${busy}>Create admin & sign in</button>
    </form></${AuthCard}>`;
            }
            function LoginScreen({ onDone, onSyncSession }) {
                const [u, setU] = useState("");
                const [p, setP] = useState("");
                const [rem, setRem] = useState(true);
                const [err, setErr] = useState("");
                const [busy, setBusy] = useState(false);
                async function submit(e) {
                    e.preventDefault();
                    setErr("");
                    setBusy(true);
                    try {
                        const user = await Store.server.login(
                            u.trim().toLowerCase(),
                            p,
                            rem,
                        );
                        Store.setSession({ user, needsSetup: false });
                        setBusy(false);
                        onDone(user);
                    } catch (e) {
                        await Store.init().catch(() => {});
                        if (onSyncSession) onSyncSession();
                        setBusy(false);
                        setErr(e.message);
                    }
                }
                return html`<${AuthCard} title="Sign in" sub="Use the username and password the admin gave you.">
    <form class="form" onSubmit=${submit}>
      <div class="field"><label class="label" for="l-user">Username</label><input id="l-user" autoCapitalize="none" autoComplete="username" value=${u} onChange=${(e) => setU(e.target.value)} autoFocus/></div>
      <div class="field"><label class="label" for="l-pass">Password</label><input id="l-pass" type="password" autoComplete="current-password" value=${p} onChange=${(e) => setP(e.target.value)}/></div>
      <label class="perm"><input type="checkbox" id="l-rem" checked=${rem} onChange=${(e) => setRem(e.target.checked)}/><span>Keep me signed in on this device</span></label>
      ${err && html`<div class="auth-err" role="alert">${err}</div>`}
      <button class="btn primary" type="submit" disabled=${busy || !u || !p}>Sign in</button>
    </form></${AuthCard}>`;
            }

            function LocalUsersAdmin({ routes, users, me, toast }) {
                const [form, setForm] = useState(null);
                const [del, setDel] = useState(null);
                const accts = users.filter((u) => u.username);
                const open = (u) =>
                    setForm(
                        u
                            ? {
                                  ...u,
                                  perms: {
                                      ...allPerms(false),
                                      ...(u.perms || {}),
                                  },
                                  routes: u.routes || "all",
                                  active: u.active !== false,
                                  pass: "",
                                  isNew: false,
                              }
                            : {
                                  id: newId("u"),
                                  name: "",
                                  username: "",
                                  pass: "",
                                  perms: { ...ROLES.salesman.perms },
                                  routes: "all",
                                  active: true,
                                  isNew: true,
                              },
                    );
                async function save() {
                    const uname = (form.username || "").trim().toLowerCase();
                    if (!form.name.trim() || !uname)
                        return toast("Enter a name and a username");
                    if (
                        accts.find(
                            (a) => a.username === uname && a.id !== form.id,
                        )
                    )
                        return toast("That username is taken");
                    if (form.isNew && form.pass.length < minPass())
                        return toast(
                            `Give a password of at least ${minPass()} characters`,
                        );
                    if (!form.isNew && form.pass && form.pass.length < minPass())
                        return toast(
                            `New password needs at least ${minPass()} characters`,
                        );
                    try {
                        await Store.set("users/" + form.id, {
                            name: form.name.trim(),
                            username: uname,
                            ...(form.pass ? { password: form.pass } : {}),
                            perms: form.perms,
                            routes: form.routes,
                            active: form.active,
                        });
                        toast(`${form.name.trim()} saved`);
                        setForm(null);
                    } catch (e) {
                        toast(e.message || "Could not save the user");
                    }
                }
                if (form) {
                    const rlist = form.routes === "all" ? [] : form.routes;
                    const isOwner = !!(
                        users.find((u) => u.id === form.id) || {}
                    ).owner;
                    return html`<div class="panel">
                        <div class="head">
                            <div>
                                <div class="label">
                                    ${form.isNew ? "New user" : "Edit user"}
                                </div>
                                <h2 style=${{ fontSize: "20px" }}>
                                    ${form.name || "New user"}
                                </h2>
                            </div>
                            <button
                                class="btn sm"
                                onClick=${() => setForm(null)}
                            >
                                Back
                            </button>
                        </div>
                        <div class="stephead">
                            <span class="stepn">1</span
                            ><span class="label">Login details</span>
                        </div>
                        <div class="two">
                            <div class="field">
                                <label class="label" for="u-name">Name</label
                                ><input
                                    id="u-name"
                                    value=${form.name}
                                    onChange=${(e) =>
                                        setForm({
                                            ...form,
                                            name: e.target.value,
                                        })}
                                />
                            </div>
                            <div class="field">
                                <label class="label" for="u-user"
                                    >Username</label
                                ><input
                                    id="u-user"
                                    autocapitalize="none"
                                    value=${form.username}
                                    onChange=${(e) =>
                                        setForm({
                                            ...form,
                                            username: e.target.value,
                                        })}
                                />
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" for="u-pass"
                                >${form.isNew
                                    ? "Password"
                                    : "New password (leave empty to keep)"}</label
                            ><input
                                id="u-pass"
                                type="password"
                                autocomplete="new-password"
                                value=${form.pass}
                                onChange=${(e) =>
                                    setForm({ ...form, pass: e.target.value })}
                            />
                        </div>
                        ${isOwner
                            ? html`<div
                                  class="notice"
                                  style=${{
                                      background: "var(--accent-soft)",
                                      color: "var(--accent)",
                                  }}
                              >
                                  Main admin account. It always has full access.
                              </div>`
                            : html`<${PermEditor}
                                  form=${form}
                                  setForm=${setForm}
                                  routes=${routes}
                                  shared=${false}
                                  n0=${1}
                              />`}
                        <div class="toolbar">
                            <button
                                class="btn primary"
                                disabled=${form.routes !== "all" &&
                                !rlist.length}
                                onClick=${save}
                            >
                                Save user
                            </button>
                        </div>
                    </div>`;
                }
                return html`<${React.Fragment}>
    <div class="head"><div><h2 style=${{ fontSize: "18px" }}>Users</h2><div class="small muted">Each person signs in with their own username and password.</div></div>
      <button class="btn primary" onClick=${() => open(null)}>+ New user</button></div>
    <div class="list">${accts.map((u) => {
        const r = u.owner ? "owner" : roleOf(u.perms || {});
        const rl =
            u.routes === "all" || !u.routes
                ? "All routes"
                : u.routes
                      .map((id) => (routes.find((x) => x.id === id) || {}).name)
                      .filter(Boolean)
                      .join(", ") || "No routes";
        return html`<div
            class="row"
            key=${u.id}
            style=${{ cursor: "default", flexWrap: "wrap" }}
        >
            <span class="main"
                ><div class="name">
                    ${u.name}${u.id === me.id ? " (you)" : ""}
                </div>
                <div class="sub">
                    @${u.username} · ${roleLabel(r)} · ${rl}
                </div></span
            >
            ${u.active === false
                ? html`<span class="pill pay-credit">Blocked</span>`
                : html`<span class="pill done">${roleLabel(r)}</span>`}
            ${del === u.id
                ? html`<span class="confirm"
                      >Remove ${u.name}?
                      <button
                          class="btn sm danger"
                          onClick=${() =>
                              Store.del("users/" + u.id).then(
                                  () => {
                                      toast("User removed");
                                      setDel(null);
                                  },
                                  (e) => toast(e.message || "Could not remove"),
                              )}
                      >
                          Remove</button
                      ><button class="btn sm" onClick=${() => setDel(null)}>
                          Cancel
                      </button></span
                  >`
                : html`<button class="btn sm" onClick=${() => open(u)}>
                          Edit</button
                      >${!u.owner &&
                      u.id !== me.id &&
                      html`<button
                          class="btn sm ghost"
                          style=${{ color: "var(--danger)" }}
                          onClick=${() => setDel(u.id)}
                      >
                          Remove
                      </button>`}`}
        </div>`;
    })}</div>
  </${React.Fragment}>`;
            }

            /*  DASHBOARD  */
            const addDays = (s, n) => {
                const d = new Date(s + "T00:00:00");
                d.setDate(d.getDate() + n);
                return dstr(d);
            };
            const kRs = (n) =>
                n >= 100000
                    ? (n / 1000).toFixed(0) + "k"
                    : n >= 1000
                      ? (n / 1000)
                            .toFixed(n >= 10000 ? 0 : 1)
                            .replace(/\.0$/, "") + "k"
                      : String(Math.round(n));
            function Dashboard({
                routes,
                shops,
                days,
                bills,
                date,
                acc,
                limit,
                me,
                go,
            }) {
                const [hist, setHist] = useState(null);
                const [hover, setHover] = useState(null);
                const from = addDays(date, -29);
                useEffect(
                    () =>
                        Store.watch(
                            "bills",
                            setHist,
                            [
                                ["date", ">=", from],
                                ["date", "<=", date],
                            ],
                            () => setHist([]),
                        ),
                    [from, date],
                );
                const money = acc.can("reports") || acc.can("bills_view");
                const H = (hist || []).filter(
                    (b) => !limit || limit.includes(b.routeId),
                );

                const perRoute = routes.map((r) => {
                    const rs_ = shops.filter((s) => s.routeId === r.id);
                    const d = days.find((x) => x.routeId === r.id);
                    const b = bills.find((x) => x.routeId === r.id);
                    const entered = rs_.filter(
                        (s) => d && d.shops && qtyOf(d.shops[s.id]) > 0,
                    ).length;
                    const pcs = sum(
                        Object.values((d && d.shops) || {}).map(qtyOf),
                    );
                    const bl = Object.values((b && b.shops) || {})
                        .filter((x) => x.lines && x.lines.length)
                        .map(payInfo);
                    return {
                        r,
                        total: rs_.length,
                        entered,
                        pcs,
                        billed: bl.length,
                        sales: sum(bl.map((x) => x.total)),
                        paid: sum(bl.map((x) => x.paid)),
                    };
                });
                const T = perRoute.reduce(
                    (t, x) => ({
                        total: t.total + x.total,
                        entered: t.entered + x.entered,
                        pcs: t.pcs + x.pcs,
                        billed: t.billed + x.billed,
                        sales: t.sales + x.sales,
                        paid: t.paid + x.paid,
                    }),
                    {
                        total: 0,
                        entered: 0,
                        pcs: 0,
                        billed: 0,
                        sales: 0,
                        paid: 0,
                    },
                );
                const retToday = sum(
                    bills.flatMap((b) =>
                        Object.values(b.shops || {}).flatMap((x) =>
                            (x.lines || []).map((l) => Number(l.ret) || 0),
                        ),
                    ),
                );

                // last 7 days: collected vs unpaid
                const week = Array.from({ length: 7 }, (_, i) =>
                    addDays(date, i - 6),
                ).map((d) => {
                    const bl = H.filter((b) => b.date === d).flatMap((b) =>
                        Object.values(b.shops || {})
                            .filter((x) => x.lines && x.lines.length)
                            .map(payInfo),
                    );
                    return {
                        d,
                        sales: sum(bl.map((x) => x.total)),
                        paid: sum(bl.map((x) => x.paid)),
                        due: sum(bl.map((x) => x.balance)),
                    };
                });
                const maxV = Math.max(1, ...week.map((w) => w.sales));
                const step = Math.pow(10, Math.floor(Math.log10(maxV)));
                const top = Math.ceil(maxV / step) * step;
                // credit outstanding (30 days) by shop
                const credit = {};
                H.forEach((b) =>
                    Object.entries(b.shops || {}).forEach(([sid, x]) => {
                        if (!x.lines || !x.lines.length) return;
                        const pi = payInfo(x);
                        if (pi.balance <= 0) return;
                        const c =
                            credit[sid] ||
                            (credit[sid] = {
                                sid,
                                name:
                                    (shops.find((s) => s.id === sid) || {})
                                        .name ||
                                    x.shopName ||
                                    sid,
                                bal: 0,
                                bills: 0,
                                oldest: b.date,
                            });
                        c.bal += pi.balance;
                        c.bills++;
                        if (b.date < c.oldest) c.oldest = b.date;
                    }),
                );
                const creditList = Object.values(credit).sort(
                    (a, b) => b.bal - a.bal,
                );
                const creditTotal = sum(creditList.map((c) => c.bal));

                const hr = new Date().getHours();
                const hello =
                    hr < 12
                        ? "Good morning"
                        : hr < 17
                          ? "Good afternoon"
                          : "Good evening";
                const W = 640,
                    Hh = 200,
                    pl = 44,
                    pb = 26,
                    pt = 10,
                    bw = (W - pl - 10) / 7;
                const y = (v) => pt + (Hh - pt - pb) * (1 - v / top);
                const hv = hover != null ? week[hover] : week[6];

                return html`<${React.Fragment}>
        <section class="dashboard-hero">
        <div class="dash-hello"><div><div class="label">${niceDate(date)}</div><h2>${hello}${me.name ? ", " + me.name.split(" ")[0] : ""}</h2><p>Your bakery operations at a glance.</p></div>
      <div class="toolbar">
        ${acc.can("orders") && html`<button class="btn primary" onClick=${() => go("orders")}>Take orders</button>`}
        ${(acc.can("bills_generate") || acc.can("bills_view")) && html`<button class="btn" onClick=${() => go("bills")}>Bills</button>`}
        ${acc.can("reports") && html`<button class="btn" onClick=${() => go("reports")}>Reports</button>`}
      </div></div>

    <div class="stats">
      <div class="stat"><span class="label">Shops ordered</span><b>${T.entered}<span class="muted" style=${{ fontSize: "16px" }}> / ${T.total}</span></b>
        <div class="progress" style=${{ marginTop: "8px" }}><i style=${{ width: (T.total ? (T.entered / T.total) * 100 : 0) + "%" }}></i></div></div>
      <div class="stat"><span class="label">Pieces ordered</span><b>${T.pcs.toLocaleString()}</b></div>
      <div class="stat"><span class="label">Bills made</span><b>${T.billed}<span class="muted" style=${{ fontSize: "16px" }}> / ${T.entered}</span></b></div>
      ${money && html`<div class="stat"><span class="label">Today's sales</span><b style=${{ fontSize: "22px" }}>${rs(T.sales)}</b></div>`}
      ${money && html`<div class="stat"><span class="label">Collected today</span><b style=${{ fontSize: "22px", color: "var(--accent)" }}>${rs(T.paid)}</b></div>`}
      ${money && html`<div class="stat"><span class="label">Credit (30 days)</span><b style=${{ fontSize: "22px", color: creditTotal > 0 ? "var(--warn)" : "inherit" }}>${rs(creditTotal)}</b><span class="small muted">${creditList.length} shop${creditList.length === 1 ? "" : "s"}</span></div>`}
      <div class="stat"><span class="label">Returns today</span><b>${retToday}<span class="muted" style=${{ fontSize: "15px" }}> pcs</span></b></div>
    </div>
        </section>

    <div class="dash-grid">
      <section class="panel" style=${{ gap: "10px" }}>
        <div class="head"><h2 style=${{ fontSize: "18px" }}>Routes today</h2><span class="small muted">Tap a route to take orders</span></div>
        <div class="list">${perRoute.map((x) => {
            const pct = x.total ? x.entered / x.total : 0;
            const st =
                x.total && x.entered === x.total
                    ? ["done", "All shops in"]
                    : x.entered
                      ? ["todo", `${x.total - x.entered} to go`]
                      : ["todo", "Not started"];
            return html`<button
                class="row"
                key=${x.r.id}
                onClick=${() => {
                    ls.set("bh-route", x.r.id);
                    go(acc.can("orders") ? "orders" : "bills");
                }}
            >
                <span class="main"
                    ><div class="name">
                        ${x.r.name}${x.r.driver && x.r.driver !== x.r.name
                            ? html` <span
                                  class="muted"
                                  style=${{ fontWeight: 400 }}
                                  >· ${x.r.driver}</span
                              >`
                            : null}
                    </div>
                    <div class="progress" style=${{ margin: "6px 0 4px" }}>
                        <i style=${{ width: pct * 100 + "%" }}></i>
                    </div>
                    <div class="sub">
                        ${x.entered}/${x.total} shops · ${x.pcs} pcs ·
                        ${x.billed}
                        bills${money && x.sales ? ` · ${rs(x.sales)}` : ""}
                    </div></span
                >
                <span class=${"pill " + st[0]}>${st[1]}</span><${Chev} />
            </button>`;
        })}</div>
      </section>

      ${
          money &&
          html`<section class="panel" style=${{ gap: "10px" }}>
              <div class="head">
                  <h2 style=${{ fontSize: "18px" }}>Last 7 days</h2>
                  <div class="legend">
                      <span
                          ><i style=${{ background: "var(--accent)" }}></i
                          >Collected</span
                      ><span
                          ><i style=${{ background: "var(--chart-due)" }}></i
                          >Unpaid</span
                      >
                  </div>
              </div>
              ${hist === null
                  ? html`<div class="empty">Loading…</div>`
                  : html`<${React.Fragment}>
        <div class="readout"><b>${niceDate(hv.d)}</b> · Sales ${rs(hv.sales)} · Collected ${rs(hv.paid)} · Unpaid ${rs(hv.due)}</div>
        <svg viewBox=${`0 0 ${W} ${Hh}`} class="chart" role="img" aria-label="Sales for the last 7 days, split into collected and unpaid" onMouseLeave=${() => setHover(null)}>
          ${[0, 0.5, 1].map((f) => html`<g key=${f}><line x1=${pl} x2=${W - 6} y1=${y(top * f)} y2=${y(top * f)} class="grid" /><text x=${pl - 8} y=${y(top * f) + 4} text-anchor="end" class="axis">${kRs(top * f)}</text></g>`)}
          ${week.map((w, i) => {
              const x = pl + i * bw + bw * 0.22,
                  bwid = bw * 0.56;
              const yp = y(w.paid),
                  yd = y(w.sales);
              return html`<g
                  key=${w.d}
                  onMouseEnter=${() => setHover(i)}
                  onClick=${() => setHover(i)}
              >
                  <rect
                      x=${pl + i * bw}
                      y=${pt}
                      width=${bw}
                      height=${Hh - pt - pb}
                      fill="transparent"
                  />
                  ${w.paid > 0 &&
                  html`<rect
                      x=${x}
                      y=${yp}
                      width=${bwid}
                      height=${Math.max(0, y(0) - yp)}
                      rx="3"
                      class="bar-paid"
                      opacity=${hover == null || hover === i ? 1 : 0.55}
                  />`}
                  ${w.due > 0 &&
                  html`<rect
                      x=${x}
                      y=${yd}
                      width=${bwid}
                      height=${Math.max(0, yp - yd - 2)}
                      rx="3"
                      class="bar-due"
                      opacity=${hover == null || hover === i ? 1 : 0.55}
                  />`}
                  <text
                      x=${x + bwid / 2}
                      y=${Hh - 8}
                      text-anchor="middle"
                      class=${"axis" +
                      ((hover == null ? i === 6 : hover === i)
                          ? " strong"
                          : "")}
                      >${new Date(w.d + "T00:00:00").toLocaleDateString(
                          "en-GB",
                          { weekday: "short" },
                      )}</text
                  ></g
              >`;
          })}
          <line x1=${pl} x2=${W - 6} y1=${y(0)} y2=${y(0)} class="base"/>
        </svg></${React.Fragment}>`}
          </section>`
      }

      ${
          money &&
          html`<section class="panel" style=${{ gap: "10px" }}>
              <div class="head">
                  <h2 style=${{ fontSize: "18px" }}>Credit to collect</h2>
                  ${acc.can("reports") &&
                  html`<button
                      class="btn sm ghost"
                      onClick=${() => go("reports")}
                  >
                      All credit →
                  </button>`}
              </div>
              ${creditList.length
                  ? html`<div class="list">
                        ${creditList.slice(0, 6).map(
                            (c) =>
                                html`<div
                                    class="row"
                                    key=${c.sid}
                                    style=${{ cursor: "default" }}
                                >
                                    <span class="main"
                                        ><div class="name">${c.name}</div>
                                        <div class="sub">
                                            ${c.bills}
                                            bill${c.bills > 1 ? "s" : ""} ·
                                            since ${niceDate(c.oldest)}
                                        </div></span
                                    >
                                    <b style=${{ color: "var(--warn)" }}
                                        >${rs(c.bal)}</b
                                    >
                                </div>`,
                        )}
                    </div>`
                  : html`<div class="empty">
                        No unpaid bills in the last 30 days.
                    </div>`}
          </section>`
      }
    </div>
  </${React.Fragment}>`;
            }

            function AdminView({
                routes,
                shops,
                itemList,
                items,
                acc,
                users,
                requests,
                userNs,
                me,
                toast,
            }) {
                const tabs = [
                    ["shops", "Shops"],
                    ["routes", "Routes & drivers"],
                    ["items", "Items & prices"],
                    [
                        "users",
                        "Users" +
                            (requests.length && acc.can("users")
                                ? ` (${requests.length})`
                                : ""),
                    ],
                    ["data", "Backup & import"],
                ].filter(([k]) =>
                    k === "data" ? ownAccounts() && me.isOwner : acc.can(k),
                );
                const [view, setView] = useState(ls.get("bh-aview", "shops"));
                useEffect(() => ls.set("bh-aview", view), [view]);
                const cur = tabs.find((t) => t[0] === view)
                    ? view
                    : (tabs[0] || [])[0];
                return html`<${React.Fragment}>
    <div class="chips" role="group" aria-label="Admin section">${tabs.map(([k, l]) => html`<button key=${k} class="chip" aria-pressed=${cur === k} onClick=${() => setView(k)}>${l}</button>`)}</div>
    ${cur === "shops" && html`<${ShopsAdmin} routes=${routes} shops=${shops} itemList=${itemList} items=${items} toast=${toast} />`}
    ${cur === "routes" && html`<${RoutesAdmin} routes=${routes} shops=${shops} toast=${toast} />`}
    ${cur === "items" && html`<${ItemsAdmin} itemList=${itemList} toast=${toast} />`}
    ${cur === "data" && html`<${DataAdmin} toast=${toast} />`}
    ${cur === "users" && (ownAccounts() ? html`<${LocalUsersAdmin} routes=${routes} users=${users} me=${me} toast=${toast} />` : html`<${UsersAdmin} routes=${routes} users=${users} requests=${requests} userNs=${userNs} me=${me} editor=${acc.editor} toast=${toast} />`)}
  </${React.Fragment}>`;
            }

            /*  BACKUP & IMPORT (owner) 
   Safely migrates any legacy browser localStorage data or backup JSON files into the SQL database. */
            const BACKUP_FORMAT = "janahitha-bakehouse-backup";
            const backupCounts = (d) => ({
                Routes: Object.keys((d && d.routes) || {}).length,
                Shops: Object.keys((d && d.shops) || {}).length,
                Items: (
                    (d && d.config && d.config.catalog && d.config.catalog.items) ||
                    []
                ).length,
                Users: Object.values((d && d.users) || {}).filter(
                    (u) => u && u.username,
                ).length,
                "Order days": Object.keys((d && d.days) || {}).length,
                "Bill days": Object.keys((d && d.bills) || {}).length,
            });
            const hasBackupData = (d) =>
                !!d && Object.values(backupCounts(d)).some((n) => n > 0);
            function readLegacyBrowserData() {
                const raw = ls.get("bh-local", null);
                const data = isObj(raw) ? { ...raw } : {};
                ["routes", "shops", "users", "days", "bills", "config"].forEach((col) => {
                    const legacy = ls.get("bh-" + col, null);
                    if (isObj(legacy) && !isObj(data[col])) {
                        data[col] = legacy;
                    }
                });
                const legacyItems = ls.get("bh-items", null);
                if (
                    Array.isArray(legacyItems) &&
                    legacyItems.length &&
                    !(data.config && data.config.catalog && data.config.catalog.items)
                ) {
                    data.config = {
                        ...(data.config || {}),
                        catalog: { items: legacyItems },
                    };
                }
                return hasBackupData(data) ? data : null;
            }
            function DataAdmin({ toast }) {
                const [browserData, setBrowserData] = useState(() =>
                    readLegacyBrowserData(),
                );
                const [file, setFile] = useState(null);
                const [busy, setBusy] = useState(false);
                const [result, setResult] = useState(null);
                const [sure, setSure] = useState(false);

                async function download(d) {
                    const e = await saveFile(
                        `bakehouse-backup-${todayStr()}.json`,
                        JSON.stringify(
                            {
                                format: BACKUP_FORMAT,
                                version: 1,
                                exportedAt: new Date().toISOString(),
                                origin: location.origin,
                                data: d,
                            },
                            null,
                            2,
                        ),
                    );
                    if (e) {
                        toast(e);
                        return false;
                    }
                    toast("Backup downloaded");
                    return true;
                }
                async function pick(e) {
                    const f = e.target.files && e.target.files[0];
                    setResult(null);
                    if (!f) return setFile(null);
                    try {
                        const json = JSON.parse(await f.text());
                        const d =
                            json && json.format === BACKUP_FORMAT ? json.data : json;
                        if (!hasBackupData(d)) throw new Error("empty");
                        setFile({ name: f.name, data: d });
                    } catch {
                        setFile(null);
                        toast("That file is not a Bake House backup");
                    }
                }
                async function runImport(d, fromBrowser) {
                    setBusy(true);
                    try {
                        const r = await Store.server.importData(d);
                        setResult({ ...r, fromBrowser });
                        toast(
                            r.verification.ok
                                ? "Import finished and checked"
                                : "Import finished, but some records did not check out",
                        );
                    } catch (e) {
                        toast(e.message || "Import failed, nothing was changed");
                    }
                    setBusy(false);
                }
                function clearBrowser() {
                    try {
                        [
                            "bh-local",
                            "bh-routes",
                            "bh-shops",
                            "bh-items",
                            "bh-users",
                            "bh-days",
                            "bh-bills",
                            "bh-config",
                        ].forEach((k) => localStorage.removeItem(k));
                    } catch {}
                    setSure(false);
                    setBrowserData(null);
                    toast("This browser's old data was removed");
                }
                const CountTable = ({ d }) =>
                    html`<div class="tablewrap">
                        <table>
                            <tbody>
                                ${Object.entries(backupCounts(d)).map(
                                    ([k, n]) =>
                                        html`<tr key=${k}>
                                            <td>${k}</td>
                                            <td>${n}</td>
                                        </tr>`,
                                )}
                            </tbody>
                        </table>
                    </div>`;
                const removeBox = (canRemove, why) =>
                    html`<div class="panel">
                        <h2 style=${{ fontSize: "18px" }}>Remove the old browser data</h2>
                        <div class="small muted">${why}</div>
                        <label class="perm"
                            ><input
                                type="checkbox"
                                checked=${sure}
                                disabled=${!canRemove}
                                onChange=${(e) => setSure(e.target.checked)}
                            /><span
                                >I have a backup file, and my data is on the server.</span
                            ></label
                        >
                        <div>
                            <button
                                class="btn danger"
                                disabled=${!canRemove || !sure}
                                onClick=${clearBrowser}
                            >
                                Remove data from this browser
                            </button>
                        </div>
                    </div>`;

                return html`<${React.Fragment}>
                    ${hasBackupData(browserData) &&
                    html`<div class="panel">
                        <h2 style=${{ fontSize: "18px" }}>Data saved in this browser</h2>
                        <div class="small muted">
                            This browser still has data from the offline version of the app. Import it to store it in the SQL database. The server automatically archives a backup copy before importing.
                        </div>
                        <${CountTable} d=${browserData} />
                        <div class="toolbar">
                            <button
                                class="btn"
                                disabled=${busy}
                                onClick=${() => download(browserData)}
                            >
                                Download backup
                            </button>
                            <button
                                class="btn primary"
                                disabled=${busy}
                                onClick=${() => runImport(browserData, true)}
                            >
                                ${busy ? "Importing…" : "Import into the database"}
                            </button>
                        </div>
                    </div>`}
                    <div class="panel">
                        <h2 style=${{ fontSize: "18px" }}>Import a backup file</h2>
                        <div class="small muted">
                            Choose a backup downloaded from the offline app (Admin → Backup & import). Importing the same file again does not create duplicates, and accounts that already exist on the server are kept as they are.
                        </div>
                        <input
                            type="file"
                            accept=".json,application/json"
                            aria-label="Backup file"
                            onChange=${pick}
                        />
                        ${file &&
                        html`<${CountTable} d=${file.data} />
                            <div>
                                <button
                                    class="btn primary"
                                    disabled=${busy}
                                    onClick=${() => runImport(file.data, false)}
                                >
                                    ${busy ? "Importing…" : `Import ${file.name}`}
                                </button>
                            </div>`}
                    </div>
                    ${result &&
                    html`<div class="panel">
                        <div class="head">
                            <h2 style=${{ fontSize: "18px" }}>Import result</h2>
                            <span
                                class=${"pill " +
                                (result.verification.ok ? "done" : "pay-credit")}
                                >${result.verification.ok
                                    ? "All records checked"
                                    : "Some records are missing"}</span
                            >
                        </div>
                        <div class="tablewrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>In backup</th>
                                        <th>Found in database</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${Object.entries(result.verification.checks).map(
                                        ([k, c]) =>
                                            html`<tr key=${k}>
                                                <td>${k}</td>
                                                <td>${c.expected}</td>
                                                <td>${c.found}</td>
                                            </tr>`,
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <div class="small muted">
                            ${Object.entries(result.summary)
                                .map(
                                    ([k, v]) =>
                                        k +
                                        ": " +
                                        Object.entries(v)
                                            .map(([a, n]) => `${n} ${a.replace(/_/g, " ")}`)
                                            .join(", "),
                                )
                                .join(" · ")}
                        </div>
                        ${result.warnings.length > 0 &&
                        html`<div class="notice">
                            ${result.warnings.slice(0, 20).map(
                                (w, i) => html`<div key=${i}>${w}</div>`,
                            )}
                            ${result.warnings.length > 20 &&
                            html`<div>…and ${result.warnings.length - 20} more.</div>`}
                        </div>`}
                    </div>`}
                    ${result &&
                    result.fromBrowser &&
                    hasBackupData(browserData) &&
                    removeBox(
                        result.verification.ok,
                        result.verification.ok
                            ? "The import was checked against the database. You can now remove the old copy from this browser."
                            : "The import did not check out, so the browser data is kept.",
                    )}
                </${React.Fragment}>`;
            }

            function RoutesAdmin({ routes, shops, toast }) {
                const [name, setName] = useState("");
                const [driver, setDriver] = useState("");
                const [edit, setEdit] = useState(null);
                const [del, setDel] = useState(null);
                async function add(e) {
                    e.preventDefault();
                    if (!name.trim()) return;
                    const id = newId("r");
                    await Store.set("routes/" + id, {
                        name: name.trim(),
                        driver: driver.trim(),
                        order: routes.length + 1,
                    }).then(
                        () => {
                            toast("Route added");
                            setName("");
                            setDriver("");
                        },
                        (e) => toast((e && e.message) || "Could not save, editors only"),
                    );
                }
                async function saveEdit() {
                    await Store.merge("routes/" + edit.id, {
                        name: edit.name.trim(),
                        driver: edit.driver.trim(),
                    }).then(
                        () => {
                            toast("Route saved");
                            setEdit(null);
                        },
                        (e) => toast((e && e.message) || "Could not save"),
                    );
                }
                return html`<${React.Fragment}>
    <form class="panel" onSubmit=${add}><h2 style=${{ fontSize: "18px" }}>Add a route</h2>
      <div class="two"><div class="field"><label class="label" for="rn">Route name</label><input id="rn" value=${name} onChange=${(e) => setName(e.target.value)} placeholder="e.g. Maradana"/></div>
      <div class="field"><label class="label" for="rd">Driver / salesman</label><input id="rd" value=${driver} onChange=${(e) => setDriver(e.target.value)} placeholder="e.g. Sujith"/></div></div>
      <div><button class="btn primary" type="submit">Add route</button></div></form>
    <div class="list">${routes.map((r) => {
        const n = shops.filter((s) => s.routeId === r.id).length;
        if (edit && edit.id === r.id)
            return html`<div
                class="row"
                key=${r.id}
                style=${{ cursor: "default", flexWrap: "wrap" }}
            >
                <input
                    class="sel"
                    id=${"en-" + r.id}
                    value=${edit.name}
                    onChange=${(e) =>
                        setEdit({ ...edit, name: e.target.value })}
                    aria-label="Route name"
                />
                <input
                    class="sel"
                    id=${"ed-" + r.id}
                    value=${edit.driver}
                    onChange=${(e) =>
                        setEdit({ ...edit, driver: e.target.value })}
                    aria-label="Driver"
                    placeholder="Driver"
                />
                <button class="btn sm primary" onClick=${saveEdit}>Save</button
                ><button class="btn sm" onClick=${() => setEdit(null)}>
                    Cancel
                </button>
            </div>`;
        return html`<div
            class="row"
            key=${r.id}
            style=${{ cursor: "default", flexWrap: "wrap" }}
        >
            <span class="main"
                ><div class="name">${r.name}</div>
                <div class="sub">
                    ${r.driver ? "Driver: " + r.driver + " · " : ""}${n} shops
                </div></span
            >
            ${del === r.id
                ? html`<span class="confirm"
                      >${n
                          ? `Move its ${n} shops first.`
                          : "Delete this route?"}
                      ${!n &&
                      html`<button
                          class="btn sm danger"
                          onClick=${() =>
                              Store.del("routes/" + r.id).then(
                                  () => {
                                      toast("Route deleted");
                                      setDel(null);
                                  },
                                  (e) => toast(e.message || "Could not delete"),
                              )}
                      >
                          Delete
                      </button>`}<button
                          class="btn sm"
                          onClick=${() => setDel(null)}
                      >
                          Cancel
                      </button></span
                  >`
                : html`<button
                          class="btn sm"
                          onClick=${() =>
                              setEdit({
                                  id: r.id,
                                  name: r.name,
                                  driver: r.driver || "",
                              })}
                      >
                          Edit</button
                      ><button
                          class="btn sm ghost"
                          style=${{ color: "var(--danger)" }}
                          onClick=${() => setDel(r.id)}
                      >
                          Delete
                      </button>`}
        </div>`;
    })}</div>
  </${React.Fragment}>`;
            }

            function ShopsAdmin({ routes, shops, itemList, items, toast }) {
                const [filter, setFilter] = useState(
                    ls.get("bh-route", routes[0] && routes[0].id),
                );
                const [form, setForm] = useState(null);
                const list = shops.filter((s) => s.routeId === filter);
                // all shops in route order, for "select shop" and "next shop"
                const ordered = routes.flatMap((r) =>
                    shops.filter((s) => s.routeId === r.id),
                );
                const openShop = (s) =>
                    setForm({
                        ...s,
                        items: [...(s.items || [])],
                        prices: { ...(s.prices || {}) },
                    });
                if (!routes.length)
                    return html`<div class="empty">
                        Add a route first, then shops can be placed on it.
                    </div>`;
                if (form)
                    return html`<${ShopForm}
                        key=${form.id || "new"}
                        form=${form}
                        setForm=${setForm}
                        routes=${routes}
                        itemList=${itemList}
                        items=${items}
                        shops=${shops}
                        ordered=${ordered}
                        openShop=${openShop}
                        toast=${toast}
                    />`;
                return html`<${React.Fragment}>
    <div class="panel" style=${{ gap: "8px" }}>
      <h2 style=${{ fontSize: "18px" }}>Shop setup</h2>
      <div class="small muted">1. Select a shop  2. Tick the items it buys  3. Set its prices. Then use “Save & next shop” to go through every shop.</div>
      <div class="toolbar">
        <select id="pickshop" class="sel" aria-label="Select shop" value="" onChange=${(
            e,
        ) => {
            const s = shops.find((x) => x.id === e.target.value);
            if (s) openShop(s);
        }}>
          <option value="">Select a shop…</option>
          ${routes.map((r) => html`<optgroup key=${r.id} label=${r.name}>${shops.filter((s) => s.routeId === r.id).map((s) => html`<option key=${s.id} value=${s.id}>${s.name}</option>`)}</optgroup>`)}
        </select>
        ${ordered.length > 0 && html`<button class="btn" onClick=${() => openShop(ordered[0])}>Start from first shop</button>`}
        <button class="btn primary" onClick=${() => setForm({ name: "", routeId: filter || routes[0].id, items: [], prices: {} })}>+ New shop</button>
      </div>
    </div>
    <${RoutePicker} routes=${routes} value=${filter} onChange=${setFilter}/>
    ${
        list.length
            ? html`<div class="list">
                  ${list.map((s, i) => {
                      const np = (s.items || []).filter(
                          (k) => (s.prices || {})[k] == null,
                      ).length;
                      return html`<button
                          key=${s.id}
                          class="row"
                          onClick=${() => openShop(s)}
                      >
                          <span class="idx">${i + 1}</span
                          ><span class="main"
                              ><div class="name">${s.name}</div>
                              <div class="sub">
                                  ${(s.items || []).length} items ·
                                  ${(s.items || [])
                                      .map((k) => (items[k] || {}).name || k)
                                      .join(", ") || "No items yet"}
                              </div></span
                          >
                          ${(s.items || []).length === 0
                              ? html`<span class="pill todo">No items</span>`
                              : np > 0
                                ? html`<span class="pill pay-partial"
                                      >${np} default prices</span
                                  >`
                                : html`<span class="pill done"
                                      >Prices set</span
                                  >`}
                          <${Chev} />
                      </button>`;
                  })}
              </div>`
            : html`<div class="empty">No shops on this route yet.</div>`
    }
  </${React.Fragment}>`;
            }

            function ShopForm({
                form,
                setForm,
                routes,
                itemList,
                items,
                shops,
                ordered,
                openShop,
                toast,
            }) {
                const [confirmDel, setConfirmDel] = useState(false);
                const [confirmAll, setConfirmAll] = useState(false);
                const [busy, setBusy] = useState(false);
                const set = (p) => setForm({ ...form, ...p });
                const move = (i, d) => {
                    const a = [...form.items];
                    const j = i + d;
                    if (j < 0 || j >= a.length) return;
                    [a[i], a[j]] = [a[j], a[i]];
                    set({ items: a });
                };
                const toggle = (k) =>
                    set({
                        items: form.items.includes(k)
                            ? form.items.filter((x) => x !== k)
                            : [...form.items, k],
                    });
                const idx = form.id
                    ? ordered.findIndex((s) => s.id === form.id)
                    : -1;
                const next =
                    idx >= 0 && idx < ordered.length - 1
                        ? ordered[idx + 1]
                        : null;
                const cleanPrices = () => {
                    const p = {};
                    form.items.forEach((k) => {
                        const v = (form.prices || {})[k];
                        if (v != null && v !== "" && !isNaN(Number(v)))
                            p[k] = Number(v);
                    });
                    return p;
                };
                async function save(goNext) {
                    if (!form.name.trim()) {
                        toast("Give the shop a name");
                        return;
                    }
                    const id = form.id || newId("s");
                    const order = form.id
                        ? form.order || 0
                        : Math.max(
                              0,
                              ...shops
                                  .filter((s) => s.routeId === form.routeId)
                                  .map((s) => s.order || 0),
                          ) + 1;
                    setBusy(true);
                    try {
                        await Store.set("shops/" + id, {
                            name: form.name.trim(),
                            routeId: form.routeId,
                            items: form.items,
                            prices: cleanPrices(),
                            order,
                        });
                        toast(`${form.name.trim()} saved`);
                        if (goNext && next) openShop(next);
                        else setForm(null);
                    } catch (e) {
                        toast(e.message || "Could not save, editors only");
                    }
                    setBusy(false);
                }
                function copyFrom(id) {
                    const s = shops.find((x) => x.id === id);
                    if (!s) return;
                    set({
                        items: [...(s.items || [])],
                        prices: { ...(s.prices || {}) },
                    });
                    toast(`Copied items & prices from ${s.name}`);
                }
                async function applyAll() {
                    const p = cleanPrices();
                    const keys = Object.keys(p);
                    if (!keys.length) {
                        toast("Set some prices first");
                        return;
                    }
                    setBusy(true);
                    let n = 0;
                    try {
                        if (form.id) {
                            await Store.merge("shops/" + form.id, {
                                prices: p,
                            });
                        }
                        for (const s of shops) {
                            if (s.id === form.id) continue;
                            const upd = {};
                            keys.forEach((k) => {
                                if ((s.items || []).includes(k)) upd[k] = p[k];
                            });
                            if (Object.keys(upd).length) {
                                await Store.merge("shops/" + s.id, {
                                    prices: upd,
                                });
                                n++;
                            }
                        }
                        toast(`Prices copied to ${n} other shops`);
                        setConfirmAll(false);
                    } catch (e) {
                        toast(e.message || "Could not update every shop, try again");
                    }
                    setBusy(false);
                }
                return html`<form
                    class="panel"
                    onSubmit=${(e) => {
                        e.preventDefault();
                        save(false);
                    }}
                >
                    <div class="head">
                        <div>
                            <div class="label">
                                ${idx >= 0
                                    ? `Shop ${idx + 1} of ${ordered.length}`
                                    : "New shop"}
                            </div>
                            <h2 style=${{ fontSize: "20px" }}>
                                ${form.name || "New shop"}
                            </h2>
                        </div>
                        <div class="toolbar">
                            <select
                                id="jump"
                                class="sel"
                                aria-label="Go to shop"
                                value=${form.id || ""}
                                onChange=${(e) => {
                                    const s = shops.find(
                                        (x) => x.id === e.target.value,
                                    );
                                    if (s) openShop(s);
                                }}
                            >
                                ${!form.id &&
                                html`<option value="">New shop</option>`}
                                ${routes.map(
                                    (r) =>
                                        html`<optgroup
                                            key=${r.id}
                                            label=${r.name}
                                        >
                                            ${shops
                                                .filter(
                                                    (s) => s.routeId === r.id,
                                                )
                                                .map(
                                                    (s) =>
                                                        html`<option
                                                            key=${s.id}
                                                            value=${s.id}
                                                        >
                                                            ${s.name}
                                                        </option>`,
                                                )}
                                        </optgroup>`,
                                )}
                            </select>
                            <button
                                type="button"
                                class="btn sm"
                                onClick=${() => setForm(null)}
                            >
                                Back to list
                            </button>
                        </div>
                    </div>

                    <div class="stephead">
                        <span class="stepn">1</span
                        ><span class="label">Shop</span>
                    </div>
                    <div class="two">
                        <div class="field">
                            <label class="label" for="sn">Shop name</label
                            ><input
                                id="sn"
                                value=${form.name}
                                onChange=${(e) => set({ name: e.target.value })}
                                placeholder="e.g. Police Park"
                            />
                        </div>
                        <div class="field">
                            <label class="label" for="sr">Route</label
                            ><select
                                id="sr"
                                value=${form.routeId}
                                onChange=${(e) =>
                                    set({ routeId: e.target.value })}
                            >
                                ${routes.map(
                                    (r) =>
                                        html`<option key=${r.id} value=${r.id}>
                                            ${r.name}
                                        </option>`,
                                )}
                            </select>
                        </div>
                    </div>

                    <div class="stephead">
                        <span class="stepn">2</span
                        ><span class="label"
                            >Tick the items this shop buys
                            (${form.items.length})</span
                        >
                        <select
                            id="copyfrom"
                            class="sel"
                            style=${{ marginLeft: "auto" }}
                            aria-label="Copy items and prices from another shop"
                            value=""
                            onChange=${(e) => copyFrom(e.target.value)}
                        >
                            <option value="">Copy from another shop…</option>
                            ${routes.map(
                                (r) =>
                                    html`<optgroup key=${r.id} label=${r.name}>
                                        ${shops
                                            .filter(
                                                (s) =>
                                                    s.routeId === r.id &&
                                                    s.id !== form.id,
                                            )
                                            .map(
                                                (s) =>
                                                    html`<option
                                                        key=${s.id}
                                                        value=${s.id}
                                                    >
                                                        ${s.name}
                                                    </option>`,
                                            )}
                                    </optgroup>`,
                            )}
                        </select>
                    </div>
                    <div class="tick">
                        ${itemList.map(
                            (it) =>
                                html`<button
                                    type="button"
                                    key=${it.id}
                                    aria-pressed=${form.items.includes(it.id)}
                                    onClick=${() => toggle(it.id)}
                                >
                                    <span class="box"
                                        >${form.items.includes(it.id)
                                            ? "✓"
                                            : ""}</span
                                    >${it.name}
                                </button>`,
                        )}
                    </div>

                    <div class="stephead">
                        <span class="stepn">3</span
                        ><span class="label">Set prices and bill order</span>
                    </div>
                    <span class="small muted"
                        >Price per piece for this shop. Empty uses the default
                        price (shown faded).</span
                    >
                    ${form.items.length
                        ? html`<div class="picked">
                              ${form.items.map(
                                  (k, i) =>
                                      html`<div key=${k}>
                                          <span
                                              >${(items[k] || {}).name ||
                                              k}</span
                                          >
                                          <label
                                              class="small muted"
                                              for=${"sp-" + k}
                                              >Rs.</label
                                          >
                                          <input
                                              class="mini wide"
                                              id=${"sp-" + k}
                                              type="number"
                                              inputmode="decimal"
                                              min="0"
                                              step="0.5"
                                              placeholder=${String(
                                                  Number(
                                                      (items[k] || {}).price,
                                                  ) || 0,
                                              )}
                                              value=${(form.prices || {})[k] ??
                                              ""}
                                              onChange=${(e) =>
                                                  set({
                                                      prices: {
                                                          ...(form.prices ||
                                                              {}),
                                                          [k]: e.target.value,
                                                      },
                                                  })}
                                          />
                                          <button
                                              type="button"
                                              class="ib"
                                              aria-label="Move up"
                                              onClick=${() => move(i, -1)}
                                          >
                                              ↑
                                          </button>
                                          <button
                                              type="button"
                                              class="ib"
                                              aria-label="Move down"
                                              onClick=${() => move(i, 1)}
                                          >
                                              ↓
                                          </button>
                                      </div>`,
                              )}
                          </div>`
                        : html`<div class="small muted">
                              Tick items in step 2 first.
                          </div>`}

                    <div class="toolbar">
                        <button
                            class="btn primary"
                            type="button"
                            disabled=${busy}
                            onClick=${() => save(true)}
                        >
                            ${next ? `Save & next: ${next.name}` : "Save shop"}
                        </button>
                        ${next &&
                        html`<button class="btn" type="submit" disabled=${busy}>
                            Save
                        </button>`}
                        ${form.id &&
                        (confirmAll
                            ? html`<span
                                  class="confirm"
                                  style=${{
                                      background: "var(--warn-soft)",
                                      color: "var(--warn)",
                                  }}
                                  >Use these prices at every other shop that
                                  buys these items?
                                  <button
                                      type="button"
                                      class="btn sm primary"
                                      disabled=${busy}
                                      onClick=${applyAll}
                                  >
                                      Yes, apply</button
                                  ><button
                                      type="button"
                                      class="btn sm"
                                      onClick=${() => setConfirmAll(false)}
                                  >
                                      Cancel
                                  </button></span
                              >`
                            : html`<button
                                  type="button"
                                  class="btn ghost"
                                  onClick=${() => setConfirmAll(true)}
                              >
                                  Apply these prices to all shops
                              </button>`)}
                    </div>
                    ${form.id &&
                    html`<div class="toolbar">
                        ${confirmDel
                            ? html`<span class="confirm"
                                  >Delete ${form.name}? Past orders and bills
                                  stay in reports.
                                  <button
                                      type="button"
                                      class="btn sm danger"
                                      onClick=${() =>
                                          Store.del("shops/" + form.id).then(
                                              () => {
                                                  toast("Shop deleted");
                                                  setForm(null);
                                              },
                                              (e) =>
                                                  toast(
                                                      e.message ||
                                                          "Could not delete",
                                                  ),
                                          )}
                                  >
                                      Delete</button
                                  ><button
                                      type="button"
                                      class="btn sm"
                                      onClick=${() => setConfirmDel(false)}
                                  >
                                      Cancel
                                  </button></span
                              >`
                            : html`<button
                                  type="button"
                                  class="btn sm ghost"
                                  style=${{ color: "var(--danger)" }}
                                  onClick=${() => setConfirmDel(true)}
                              >
                                  Delete shop
                              </button>`}
                    </div>`}
                </form>`;
            }

            function ItemsAdmin({ itemList, toast }) {
                const [rows, setRows] = useState(
                    itemList.map((i) => ({ ...i })),
                );
                const [name, setName] = useState("");
                const [dirty, setDirty] = useState(false);
                useEffect(() => {
                    if (!dirty) setRows(itemList.map((i) => ({ ...i })));
                }, [itemList]);
                const upd = (id, p) => {
                    setRows((r) =>
                        r.map((x) => (x.id === id ? { ...x, price: p } : x)),
                    );
                    setDirty(true);
                };
                function add(e) {
                    e.preventDefault();
                    const n = name.trim();
                    if (!n) return;
                    let id = slug(n);
                    while (rows.find((r) => r.id === id)) id += "_2";
                    setRows([...rows, { id, name: n, price: 0 }]);
                    setName("");
                    setDirty(true);
                }
                async function save() {
                    try {
                        await Store.set("config/catalog", {
                            items: rows.map((r) => ({
                                id: r.id,
                                name: r.name,
                                price: Number(r.price) || 0,
                            })),
                        });
                        setDirty(false);
                        toast("Items & prices saved");
                    } catch (e) {
                        toast(e.message || "Could not save, editors only");
                    }
                }
                return html`<div class="panel">
                    <div class="head">
                        <div>
                            <h2 style=${{ fontSize: "18px" }}>
                                Items & prices
                            </h2>
                            <div class="small muted">
                                Default price per piece in rupees. A shop's own
                                fixed price (Admin → Shops) overrides it on that
                                shop's bills.
                            </div>
                        </div>
                        <button
                            class="btn primary"
                            disabled=${!dirty}
                            onClick=${save}
                        >
                            ${dirty ? "Save changes" : "Saved"}
                        </button>
                    </div>
                    <div class="pricegrid">
                        ${rows.map(
                            (r) =>
                                html`<label key=${r.id}
                                    ><span>${r.name}</span
                                    ><span
                                        class="small muted"
                                        style=${{ flex: "none" }}
                                        >Rs.</span
                                    >
                                    <input
                                        id=${"p-" + r.id}
                                        type="number"
                                        inputmode="decimal"
                                        min="0"
                                        step="0.5"
                                        value=${r.price || ""}
                                        placeholder="0"
                                        onChange=${(e) =>
                                            upd(r.id, e.target.value)}
                                /></label>`,
                        )}
                    </div>
                    <form class="toolbar" onSubmit=${add}>
                        <input
                            class="sel"
                            id="newitem"
                            value=${name}
                            onChange=${(e) => setName(e.target.value)}
                            placeholder="New item name"
                        /><button class="btn" type="submit">Add item</button>
                    </form>
                </div>`;
            }

            /*  APP  */
            function App() {
                const [ready, setReady] = useState(false);
                const [mode, setMode] = useState("loading");
                const [me] = useState({
                    id: null,
                    name: "",
                    isOwner: false,
                    canEdit: false,
                });
                const [userNs] = useState(null);
                const [routes, setRoutes] = useState([]);
                const [shops, setShops] = useState([]);
                const [itemList, setItemList] = useState([]);
                const [users, setUsers] = useState([]);
                const [requests, setRequests] = useState([]);
                const [usersLoaded, setUsersLoaded] = useState(false);
                const [loaded, setLoaded] = useState(false);
                const [date, setDate] = useState(todayStr());
                const [days, setDays] = useState([]);
                const [bills, setBills] = useState([]);
                const [tab, setTab] = useState("home");
                const [pageSearch, setPageSearch] = useState("");
                const [sid, setSid] = useState(null);
                const [needsSetup, setNeedsSetup] = useState(false);
                const [msg, setMsg] = useState(null);
                const [err, setErr] = useState(null);
                const tRef = useRef();
                const toast = (m) => {
                    setMsg(m);
                    clearTimeout(tRef.current);
                    tRef.current = setTimeout(() => setMsg(null), 2600);
                };

                const resetData = () => {
                    setRoutes([]);
                    setShops([]);
                    setItemList([]);
                    setUsers([]);
                    setRequests([]);
                    setDays([]);
                    setBills([]);
                    setLoaded(false);
                    setUsersLoaded(false);
                };
                const syncSessionState = () => {
                    setNeedsSetup(!!(Store.session && Store.session.needsSetup));
                    setSid(
                        Store.session && Store.session.user
                            ? Store.session.user.id
                            : null,
                    );
                };
                /* server mode: data loads only while signed in, and again for each account */
                const serverSid = mode === "server" ? sid || "" : null;

                useEffect(() => {
                    (async () => {
                        let m;
                        try {
                            m = await Store.init();
                        } catch (e) {
                            setErr(
                                "Could not reach the server. Check your connection and reload the page.",
                            );
                            return;
                        }
                        setMode(m);
                        syncSessionState();
                        Store.server.onSignedOut(() => setSid(null));
                        setReady(true);
                    })();
                }, []);

                useEffect(() => {
                    if (!ready) return;
                    if (mode === "server") resetData();
                    if (serverSid === "") return;
                    const onErr = () =>
                        setErr(
                            "Lost connection to the shared data. Reload the page to reconnect.",
                        );
                    const u1 = Store.watch(
                        "routes",
                        (r) =>
                            setRoutes(
                                [...r].sort(
                                    (a, b) =>
                                        (a.order || 0) - (b.order || 0) ||
                                        String(a.name).localeCompare(b.name),
                                ),
                            ),
                        null,
                        onErr,
                    );
                    const u2 = Store.watch(
                        "shops",
                        (s) => {
                            setShops(
                                [...s].sort(
                                    (a, b) =>
                                        (a.order || 0) - (b.order || 0) ||
                                        String(a.name).localeCompare(b.name),
                                ),
                            );
                            setLoaded(true);
                        },
                        null,
                        onErr,
                    );
                    const u3 = Store.watch(
                        "config",
                        (c) => {
                            const cat = c.find((x) => x.id === "catalog");
                            setItemList(cat && cat.items ? cat.items : []);
                        },
                        null,
                        onErr,
                    );
                    const u4 = Store.watch(
                        "users",
                        (u) => {
                            setUsers(u);
                            setUsersLoaded(true);
                        },
                        null,
                        () => setUsersLoaded(true),
                    );
                    const u5 = Store.watch(
                        "requests",
                        setRequests,
                        null,
                        () => {},
                    );
                    return () => {
                        u1();
                        u2();
                        u3();
                        u4();
                        u5();
                    };
                }, [ready, serverSid]);
                useEffect(() => {
                    if (!ready || serverSid === "") return;
                    return Store.watch(
                        "days",
                        setDays,
                        ["date", "==", date],
                        () => {},
                    );
                }, [ready, date, serverSid]);
                useEffect(() => {
                    if (!ready || serverSid === "") return;
                    return Store.watch(
                        "bills",
                        setBills,
                        ["date", "==", date],
                        () => {},
                    );
                }, [ready, date, serverSid]);

                const ownAcc = true;
                const localUser =
                    users.find((u) => u.id === sid && u.username) ||
                    (Store.session &&
                    Store.session.user &&
                    Store.session.user.id === sid
                        ? Store.session.user
                        : null);
                const meV = localUser
                    ? {
                          id: localUser.id,
                          name: localUser.name,
                          isOwner: !!localUser.owner,
                          canEdit: true,
                      }
                    : me;
                const acc = useMemo(() => {
                    const full = {
                        role: "owner",
                        perms: allPerms(true),
                        routes: null,
                    };
                    const u = localUser;
                    if (!u || u.active === false) return null;
                    const a = u.owner
                        ? full
                        : {
                              role: roleOf(u.perms || {}),
                              perms: u.perms || {},
                              routes:
                                  u.routes === "all" || !u.routes
                                      ? null
                                      : u.routes,
                          };
                    return {
                        ...a,
                        editor: true,
                        can: (k) => !!a.perms[k],
                    };
                }, [mode, me, users, sid]);

                const items = useMemo(
                    () => Object.fromEntries(itemList.map((i) => [i.id, i])),
                    [itemList],
                );
                const limit = acc && acc.routes;
                const vRoutes = limit
                    ? routes.filter((r) => limit.includes(r.id))
                    : routes;
                const vShops = limit
                    ? shops.filter((s) => limit.includes(s.routeId))
                    : shops;
                const vDays = limit
                    ? days.filter((d) => limit.includes(d.routeId))
                    : days;
                const vBills = limit
                    ? bills.filter((d) => limit.includes(d.routeId))
                    : bills;
                const tabs = !acc
                    ? []
                    : [
                          ["home", "Dashboard"],
                          ...(acc.can("orders") ? [["orders", "Orders"]] : []),
                          ...(acc.can("bills_view") || acc.can("bills_generate")
                              ? [["bills", "Bills"]]
                              : []),
                          ...(acc.can("reports")
                              ? [["reports", "Reports"]]
                              : []),
                          ...(ADMIN_PERMS.some((k) => acc.can(k))
                              ? [
                                    [
                                        "admin",
                                        "Admin" +
                                            (acc.can("users") && requests.length
                                                ? " •"
                                                : ""),
                                    ],
                                ]
                              : []),
                      ];
                const curTab = tabs.find((t) => t[0] === tab)
                    ? tab
                    : (tabs[0] || [])[0];
                const goToSearchResult = () => {
                    const query = pageSearch.trim().toLowerCase();
                    const match = tabs.find(([, label]) =>
                        label.replace(" •", "").toLowerCase().includes(query),
                    );
                    if (match) setTab(match[0]);
                    setPageSearch("");
                };
                const navGroups = [
                    {
                        label: "Workspace",
                        items: tabs.filter(([id]) => id === "home"),
                    },
                    {
                        label: "Daily work",
                        items: tabs.filter(([id]) =>
                            ["orders", "bills", "reports"].includes(id),
                        ),
                    },
                    {
                        label: "Management",
                        items: tabs.filter(([id]) => id === "admin"),
                    },
                ].filter((group) => group.items.length);
                const waiting =
                    !ready || (serverSid !== "" && (!loaded || !usersLoaded));
                const needLogin = ownAcc && !acc;
                const hasAccounts = !needsSetup;
                const signedIn = (userOrId) => {
                    const u =
                        userOrId && typeof userOrId === "object"
                            ? userOrId
                            : null;
                    const id = u ? u.id : userOrId;
                    if (u) {
                        Store.setSession({ user: u, needsSetup: false });
                        setUsers((prev) =>
                            prev.some((x) => x.id === u.id)
                                ? prev
                                : [...prev, u],
                        );
                    }
                    setNeedsSetup(false);
                    setSid(id);
                    setTab("home");
                };
                const signOut = () => {
                    setTab("home");
                    Store.server
                        .logout()
                        .catch(() => {})
                        .then(() => Store.init())
                        .catch(() => {})
                        .then(() => syncSessionState());
                };
                const hasPendingBrowserMigration =
                    acc && meV.isOwner && hasBackupData(readLegacyBrowserData());

                return html`<div class=${"workspace" + (tabs.length ? " has-sidenav" : "")}>
    ${
        !needLogin &&
        html`<header class="top">
            <div class="top-in">
                <div class="top-tools">
                    <label class="page-search">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                        <input type="search" list="page-options" aria-label="Search pages" placeholder="Search pages" value=${pageSearch} onInput=${(e) => setPageSearch(e.target.value)} onKeyDown=${(e) => e.key === "Enter" && goToSearchResult()} />
                        <datalist id="page-options">${tabs.map(([, label]) => html`<option value=${label.replace(" •", "")}></option>`)}</datalist>
                    </label>
                    ${acc && acc.can("orders") && html`<button class="btn quick-add" type="button" onClick=${() => setTab("orders")}><span aria-hidden="true">+</span> Quick Add</button>`}
                    ${!needLogin &&
                    html`<div class="date">
                        <label class="label" for="day">Day</label>
                        <input
                            id="day"
                            type="date"
                            value=${date}
                            onChange=${(e) =>
                                e.target.value && setDate(e.target.value)}
                        />
                        ${date !== todayStr() &&
                        html`<button
                            class="btn sm ghost"
                            onClick=${() => setDate(todayStr())}
                        >
                            Today
                        </button>`}
                        ${acc && ownAcc && html`<button class="btn sm ghost top-signout" type="button" onClick=${signOut}>Sign out</button>`}
                    </div>`}
                </div>
            </div>
        </header>`
    }
    <div class=${"app-layout" + (tabs.length ? " has-sidenav" : "")}>
            ${tabs.length > 0 && html`<${Sidenav} groups=${navGroups} active=${curTab} onSelect=${setTab} me=${meV} acc=${acc} signOut=${signOut} />`}
    <main>
      ${hasPendingBrowserMigration &&
      curTab !== "admin" &&
      html`<div class="notice" style=${{ display: "flex", alignItems: "center", justifyContent: "space-between", gap: "12px", flexWrap: "wrap" }}>
          <span>Browser localStorage data was found on this device. Migrate it into the SQL database in Admin → Backup & import.</span>
          <button class="btn sm primary" type="button" onClick=${() => { ls.set("bh-aview", "data"); setTab("admin"); }}>Migrate browser data</button>
      </div>`}
      ${err && html`<div class="notice">${err}</div>`}
            ${!waiting && acc && html`<div class="content-title"><h2>${(tabs.find(([id]) => id === curTab) || [null, "Dashboard"])[1].replace(" •", "")}</h2><span>${niceDate(date)}</span></div>`}
      ${
          waiting
              ? html`<div class="empty">Loading routes and shops…</div>`
              : needLogin
                ? hasAccounts
                    ? html`<${LoginScreen} users=${users} onDone=${signedIn} onSyncSession=${syncSessionState} />`
                    : html`<${SetupAdmin} onDone=${signedIn} onSyncSession=${syncSessionState} />`
                : !acc
                  ? html`<${AccessScreen}
                        me=${me}
                        users=${users}
                        requests=${requests}
                        toast=${toast}
                    />`
                  : curTab === "home"
                    ? html`<${Dashboard}
                          routes=${vRoutes}
                          shops=${vShops}
                          days=${vDays}
                          bills=${vBills}
                          date=${date}
                          acc=${acc}
                          limit=${limit}
                          me=${meV}
                          go=${setTab}
                      />`
                    : !curTab
                      ? html`<div class="empty">
                            Your account has no permissions turned on yet. Ask
                            the admin.
                        </div>`
                      : curTab === "orders"
                        ? html`<${OrdersView}
                              routes=${vRoutes}
                              shops=${vShops}
                              items=${items}
                              days=${vDays}
                              date=${date}
                              toast=${toast}
                          />`
                        : curTab === "bills"
                          ? html`<${BillsView}
                                routes=${vRoutes}
                                shops=${vShops}
                                items=${items}
                                days=${vDays}
                                bills=${vBills}
                                date=${date}
                                acc=${acc}
                                toast=${toast}
                            />`
                          : curTab === "reports"
                            ? html`<${ReportsView}
                                  routes=${vRoutes}
                                  shops=${vShops}
                                  items=${items}
                                  itemList=${itemList}
                                  days=${vDays}
                                  date=${date}
                                  acc=${acc}
                                  limit=${limit}
                                  toast=${toast}
                              />`
                            : html`<${AdminView}
                                  routes=${routes}
                                  shops=${shops}
                                  itemList=${itemList}
                                  items=${items}
                                  acc=${acc}
                                  users=${users}
                                  requests=${requests}
                                  userNs=${userNs}
                                  me=${meV}
                                  toast=${toast}
                              />`
      }
    </main>
        </div>
    <footer class=${"site-footer" + (tabs.length ? " has-sidenav" : "")}>
        <div class="site-footer-in">
            <div class="footer-brand">
                <strong>Janahitha Bake House</strong>
                <span>Treats you can trust</span>
            </div>
            <span>© ${new Date().getFullYear()} Janahitha Bake House</span>
        </div>
    </footer>
    ${msg && html`<div class="toast" role="status">${msg}</div>`}
    </div>`;
            }

            ReactDOM.createRoot(document.getElementById("root")).render(
                html`<${App} />`,
            );
