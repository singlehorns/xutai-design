import test from "node:test";
import assert from "node:assert/strict";
import { loadWordPressCatalog, sanitizeCaseContent, wordpressEndpoint } from "../src/data/wordpress-source.ts";
import { serviceGroups, legacyServiceGroups } from "../src/data/service-groups.ts";

const terms = [{ key: "web", label: "網站" }, { key: "new-category", label: "新分類" }];
const sample = (overrides = {}) => ({ slug: "sample-work", title: "作品名稱", summary: "簡短描述。", categories: [terms[0]], contentHtml: '<p>設計說明</p><figure class="wp-block-image"><img src="https://cms.example.com/a.jpg" alt="作品"></figure>', coverImage: { src: "https://cms.example.com/a.jpg", alt: "作品" }, relatedLink: null, featured: true, displayOrder: 1, serviceIds: ["web"], contentStatus: "complete", ...overrides });
const json = (data, pages) => new Response(JSON.stringify(data), { headers: { "content-type": "application/json; charset=UTF-8", ...(pages !== undefined ? { "X-WP-TotalPages": String(pages) } : {}) } });
const fixture = (items = [sample()], categories = terms) => async (request) => new URL(request).pathname.endsWith("/categories") ? json(categories) : json(items, items.length ? 1 : 0);

test("published catalog retains empty categories, authoritative renamed labels and one optional link", async () => {
  const renamed = [{ key: "web", label: "網站設計" }, terms[1]];
  const result = await loadWordPressCatalog("https://cms.example.com", fixture([sample({ categories: [renamed[0]], relatedLink: { href: "https://example.com/", label: "查看網站" } })], renamed));
  assert.deepEqual(result.categories, renamed);
  assert.deepEqual(result.cases[0].categoryTerms, [renamed[0]]);
  assert.equal(result.cases[0].relatedLink.href, "https://example.com/");
  assert.equal((await loadWordPressCatalog("https://cms.example.com", fixture())).cases[0].relatedLink, null);
});

test("WordPress can publish an untitled work without losing its route, categories or content", async () => {
  for (const title of ["", " \n\t "]) {
    const input = sample({ slug: "33", title, summary: "新作品的簡短描述。", relatedLink: { href: "https://example.com/project", label: "查看網站" } });
    const existing = Array.from({ length: 9 }, (_, index) => sample({ slug: `existing-${index + 1}`, displayOrder: index + 1 }));
    const result = await loadWordPressCatalog("https://cms.example.com", fixture([...existing, input]));
    assert.equal(result.cases.length, 10);
    const untitled = result.cases.find((item) => item.slug === "33");
    assert.equal(untitled.title, "未命名作品");
    assert.equal(untitled.shortTitle, "未命名作品");
    assert.equal(untitled.summary, input.summary);
    assert.deepEqual(untitled.categoryTerms, input.categories);
    assert.deepEqual(untitled.coverImage, input.coverImage);
    assert.deepEqual(untitled.relatedLink, input.relatedLink);
    assert.match(untitled.contentHtml, /設計說明/);
    assert.match(untitled.contentHtml, /https:\/\/cms.example.com\/a.jpg/);
  }
});

test("collects multiple pages, sorts display order, and normalizes percent-encoded Chinese names", async () => {
  const categories = [{ key: "%e8%a8%ad%e8%a8%88", label: "設計" }];
  const requests = [];
  const result = await loadWordPressCatalog("https://cms.example.com/?rest_route=/", async (request) => {
    const url = new URL(request); requests.push(url);
    if (url.searchParams.get("rest_route").endsWith("/categories")) return json(categories);
    const page = url.searchParams.get("page");
    return json([sample({ slug: page === "1" ? "%e4%bd%9c%e5%93%81" : "second", displayOrder: page === "1" ? 2 : 1, categories })], 2);
  });
  assert.deepEqual(result.cases.map((item) => item.slug), ["second", "作品"]);
  assert.equal(result.categories[0].key, "設計");
  assert.equal(requests.length, 3);
});

test("empty WordPress site is valid and does not invent local cases", async () => {
  const result = await loadWordPressCatalog("https://cms.example.com", fixture([], []));
  assert.deepEqual(result, { cases: [], categories: [] });
});

test("fixed service filters do not grow when a work has brand, print, web and ICON tags", async () => {
  const tags = [{ key: "brand", label: "品牌與視覺" }, { key: "print", label: "印刷與輸出" }, { key: "web", label: "網站" }, { key: "icon", label: "ICON" }];
  const result = await loadWordPressCatalog("https://cms.example.com", fixture([sample({ slug: "33", title: "作品一號", serviceIds: ["social"], categories: tags })], tags));
  assert.deepEqual(serviceGroups.map(({ key }) => key), ["web", "event", "social", "print"]);
  assert.deepEqual(result.cases[0].serviceGroups, ["social"]);
  assert.deepEqual(result.cases[0].categoryTerms, tags);
  assert.equal(result.cases[0].serviceGroups.includes("web"), false);
  assert.equal(serviceGroups.some(({ key }) => key === "icon" || key === "brand"), false);
});

test("explicit event and empty service groups override the old plugin fallback", async () => {
  const result = await loadWordPressCatalog("https://cms.example.com", fixture([
    sample({ slug: "event-work", serviceIds: ["brand", "web", "motion"], serviceGroups: ["event"], categories: [] }),
    sample({ slug: "cleared-work", serviceIds: ["web"], serviceGroups: [], categories: [] }),
    sample({ slug: "old-brand-work", serviceIds: ["brand", "motion"], categories: [] })
  ]));
  assert.deepEqual(result.cases.find((work) => work.slug === "event-work").serviceGroups, ["event"]);
  assert.deepEqual(result.cases.find((work) => work.slug === "cleared-work").serviceGroups, []);
  assert.deepEqual(result.cases.find((work) => work.slug === "old-brand-work").serviceGroups, []);
  assert.deepEqual(result.cases.find((work) => work.slug === "event-work").serviceIds, ["brand", "web", "motion"]);
  assert.deepEqual(legacyServiceGroups(["brand", "motion", "print"]), ["print"]);
});

test("tags may use names reserved for routes and never become links or filters", async () => {
  const tags = [{ key: "all", label: "ALL" }, { key: "index", label: "INDEX" }, { key: "%e9%a2%a8%e6%a0%bc", label: "風格" }];
  const result = await loadWordPressCatalog("https://cms.example.com", fixture([sample({ categories: tags, serviceGroups: [] })], tags));
  assert.deepEqual(result.cases[0].categoryTerms.map((tag) => tag.key), ["all", "index", "風格"]);
  assert.deepEqual(result.cases[0].serviceGroups, []);
  assert.equal(result.cases[0].categoryTerms.some((tag) => "href" in tag), false);
});

test("unknown, malformed and duplicate service groups fail instead of creating new filters", async () => {
  for (const value of [["ICON"], ["brand"], ["web", "web"], null, "social", { event: true }]) {
    await assert.rejects(loadWordPressCatalog("https://cms.example.com", fixture([sample({ serviceGroups: value })])), /服務大項/);
  }
});

test("supports site subdirectory, wp-json root and plain permalink API roots", () => {
  assert.equal(wordpressEndpoint("https://cms.example.com/blog/", "works").href, "https://cms.example.com/blog/wp-json/t2-portfolio/v1/works");
  assert.equal(wordpressEndpoint("https://cms.example.com/blog/wp-json/", "works").href, "https://cms.example.com/blog/wp-json/t2-portfolio/v1/works");
  assert.equal(wordpressEndpoint("https://cms.example.com/?rest_route=/", "works").searchParams.get("rest_route"), "/t2-portfolio/v1/works");
  assert.throws(() => wordpressEndpoint("https://user:pass@cms.example.com", "works"));
  assert.throws(() => wordpressEndpoint("https://cms.example.com/?foo=bar", "works"));
});

test("HTML sanitizer removes executable HTML, unsafe URLs, inline CSS and embeds but preserves content", () => {
  const html = sanitizeCaseContent('<script>alert(1)</script><style>body{display:none}</style><p onclick="alert(1)" style="color:red">文字<strong>重點</strong></p><img src="javascript:alert(1)" onerror="alert(1)"><img src="data:image/svg+xml,test"><iframe src="https://evil.example"></iframe><svg onload="alert(1)"></svg><a href="javascript:alert(1)">連結</a><a href="https://good.example">合法</a><img src="https://good.example/image.jpg" alt="照片">');
  assert.doesNotMatch(html, /script|style|onclick|onerror|iframe|svg|javascript:|data:image/);
  assert.match(html, /<strong>重點<\/strong>/);
  assert.match(html, /rel="noopener noreferrer"/);
  assert.match(html, /src="https:\/\/good.example\/image.jpg"/);
});

for (const [name, update] of [
  ["reserved slug", { slug: "index" }],
  ["traversal slug", { slug: "%2e%2e%2fsecret" }],
  ["unsafe related link", { relatedLink: { href: "javascript:alert(1)", label: "link" } }],
  ["credential URL", { coverImage: { src: "https://user:pass@example.com/a.jpg", alt: "" } }],
  ["category mismatch", { categories: [{ key: "web", label: "Changed midway" }] }],
  ["unknown category", { categories: [{ key: "missing", label: "新" }] }],
  ["incorrect type", { featured: "false" }],
  ["missing title", { title: undefined }],
  ["null title", { title: null }],
  ["non-string title", { title: 33 }],
  ["missing explicit link", { relatedLink: undefined }]
]) test(`rejects ${name} instead of building incorrect routes or content`, async () => {
  await assert.rejects(loadWordPressCatalog("https://cms.example.com", fixture([sample(update)])), /WordPress/);
});

test("duplicate case slugs and duplicate category keys fail instead of overwriting pages", async () => {
  await assert.rejects(loadWordPressCatalog("https://cms.example.com", fixture([sample(), sample()])), /重複/);
  await assert.rejects(loadWordPressCatalog("https://cms.example.com", fixture([], [terms[0], terms[0]])), /重複/);
});

test("configured unavailable, timeout, non-JSON and malformed endpoints fail without local fallback", async () => {
  for (const fetcher of [
    async () => { throw new Error("unreachable"); },
    async () => { throw new DOMException("timed out", "TimeoutError"); },
    async () => new Response("Unavailable", { status: 503 }),
    async () => new Response("<html>login page</html>", { headers: { "content-type": "text/html" } }),
    async () => new Response("{broken", { headers: { "content-type": "application/json" } }),
    async () => json({ error: "not an array" })
  ]) await assert.rejects(loadWordPressCatalog("https://cms.example.com", fetcher), /WordPress/);
});

test("bad pagination and changing page counts fail rather than lose cases", async () => {
  for (const pages of [undefined, "bad", 101]) {
    await assert.rejects(loadWordPressCatalog("https://cms.example.com", async (request) => new URL(request).pathname.endsWith("/categories") ? json(terms) : json([sample()], pages)), /WordPress/);
  }
  await assert.rejects(loadWordPressCatalog("https://cms.example.com", async (request) => {
    const url = new URL(request);
    return url.pathname.endsWith("/categories") ? json(terms) : json([sample()], url.searchParams.get("page") === "1" ? 2 : 3);
  }), /改變/);
});
