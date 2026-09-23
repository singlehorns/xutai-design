import sanitizeHtml from "sanitize-html";
import type { CaseCategoryOption, CaseContentStatus, CaseData, CaseImage } from "./case-model";
import type { ServiceId } from "./services";

export interface WordPressCase extends CaseData { categoryTerms: CaseCategoryOption[]; }
export interface WordPressCatalog { cases: WordPressCase[]; categories: CaseCategoryOption[]; }
type Fetcher = typeof fetch;
const serviceIds = new Set(["brand", "print", "web", "social", "motion"]);
const reservedSlugs = new Set(["index", "page", "api", "feed"]);
const object = (value: unknown): value is Record<string, unknown> => Boolean(value) && typeof value === "object" && !Array.isArray(value);
const fail = (message: string): never => { throw new Error(`WordPress 作品資料：${message}`); };

function text(value: unknown, field: string, max = 500, allowEmpty = false): string {
  if (typeof value !== "string" || value.length > max || (!allowEmpty && !value.trim())) return fail(`${field} 格式不正確`);
  return value.trim();
}

export function httpUrl(value: unknown, field = "網址"): string {
  const raw = text(value, field, 4096);
  let parsed: URL;
  try { parsed = new URL(raw); } catch { return fail(`${field} 必須是完整的 HTTP(S) 網址`); }
  if (!["http:", "https:"].includes(parsed.protocol) || parsed.username || parsed.password) return fail(`${field} 不允許此通訊協定或帳密`);
  return parsed.href;
}

function slug(value: unknown, field: string): string {
  let result = text(value, field, 240);
  try { result = decodeURIComponent(result).normalize("NFC"); } catch { return fail(`${field} 編碼不正確`); }
  if (!/^[\p{L}\p{N}]+(?:[-_][\p{L}\p{N}]+)*$/u.test(result) || reservedSlugs.has(result.toLowerCase()) || result.toLowerCase() === "all") return fail(`${field} 含有無效或保留字元`);
  return result;
}

function category(value: unknown): CaseCategoryOption {
  if (!object(value)) return fail("分類格式不正確");
  return { key: slug(value.key, "分類代稱"), label: text(value.label, "分類名稱", 120) };
}

function image(value: unknown): CaseImage | undefined {
  if (value == null) return undefined;
  if (!object(value)) return fail("封面圖片格式不正確");
  return { src: httpUrl(value.src, "封面圖片"), alt: text(value.alt ?? "", "圖片替代文字", 1000, true), ...(value.caption ? { caption: text(value.caption, "圖片說明", 2000) } : {}) };
}

/** Gutenberg markup is never trusted: scripts, inline CSS, embeds and unsafe URLs are removed. */
export function sanitizeCaseContent(html: string): string {
  return sanitizeHtml(html, {
    allowedTags: ["p", "h2", "h3", "h4", "ul", "ol", "li", "strong", "em", "b", "i", "blockquote", "figure", "figcaption", "img", "a", "br", "hr", "code", "pre", "table", "thead", "tbody", "tr", "th", "td", "div", "span"],
    allowedAttributes: { a: ["href", "title", "target", "rel"], img: ["src", "alt", "width", "height", "loading", "decoding"], "*": ["class"], ol: ["start"], th: ["scope", "colspan", "rowspan"], td: ["colspan", "rowspan"] },
    allowedClasses: { "*": ["wp-block-image", "wp-block-gallery", "has-nested-images", "wp-block-quote", "wp-block-table", "wp-element-caption", "aligncenter", "alignwide", "alignfull"] },
    allowedSchemes: ["http", "https"],
    allowProtocolRelative: false,
    transformTags: {
      a: (_tag, attrs) => {
        let href: string | undefined;
        try { href = httpUrl(attrs.href); } catch { /* Omit an unsafe or nonabsolute link. */ }
        return { tagName: "a", attribs: { ...(href ? { href, target: "_blank", rel: "noopener noreferrer" } : {}), ...(attrs.title ? { title: attrs.title } : {}) } };
      },
      img: (_tag, attrs) => {
        let src: string | undefined;
        try { src = httpUrl(attrs.src); } catch { /* Remove the invalid image below. */ }
        return { tagName: "img", attribs: { ...(src ? { src } : {}), alt: attrs.alt ?? "", loading: "lazy", decoding: "async" } };
      }
    },
    exclusiveFilter: (frame) => frame.tag === "img" && !frame.attribs.src
  });
}

function work(value: unknown, categories: Map<string, CaseCategoryOption>): WordPressCase {
  if (!object(value)) return fail("作品格式不正確");
  const workSlug = slug(value.slug, "作品代稱");
  // WordPress permits published posts without a title. Keep their route/content visible.
  const title = text(value.title, "作品標題", 500, true) || "未命名作品";
  if (!Array.isArray(value.categories)) return fail(`${workSlug} 缺少分類陣列`);
  const terms = value.categories.map(category).map((term) => {
    const authoritative = categories.get(term.key);
    if (!authoritative || authoritative.label !== term.label) return fail(`${workSlug} 分類與分類清單不一致`);
    return authoritative;
  });
  if (new Set(terms.map((term) => term.key)).size !== terms.length) return fail(`${workSlug} 有重複分類`);
  if (!Array.isArray(value.serviceIds) || value.serviceIds.some((id) => typeof id !== "string" || !serviceIds.has(id))) return fail(`${workSlug} 服務對應不正確`);
  if (typeof value.featured !== "boolean" || !Number.isSafeInteger(value.displayOrder)) return fail(`${workSlug} 精選或排序格式不正確`);
  if (!["complete", "partial", "legacy"].includes(String(value.contentStatus))) return fail(`${workSlug} 內容狀態不正確`);
  const relatedLink = value.relatedLink;
  if (relatedLink !== null && !object(relatedLink)) return fail(`${workSlug} 相關連結格式不正確`);
  const contentHtml = sanitizeCaseContent(text(value.contentHtml, "作品內容", 2_000_000, true));
  return {
    source: "wordpress", id: `wordpress:${workSlug}`, slug: workSlug, title, shortTitle: title,
    summary: text(value.summary, "簡短描述", 4000, true), contentHtml,
    categoryTerms: terms, coverImage: image(value.coverImage), gallery: [],
    relatedLink: relatedLink ? { href: httpUrl(relatedLink.href, "相關連結"), label: text(relatedLink.label, "相關連結文字", 120) } : null,
    featured: value.featured, displayOrder: value.displayOrder as number, serviceIds: value.serviceIds as ServiceId[],
    contentStatus: value.contentStatus as CaseContentStatus,
    ...(value.clientName ? { clientName: text(value.clientName, "客戶名稱") } : {}),
    ...(value.year ? { year: text(value.year, "年份", 120) } : {}),
    roles: [], responsibilities: [], deliverables: [], solutionIds: [], tools: [], technologies: [], outcomes: []
  };
}

/** Accept a site URL, a /wp-json/ root, or a ?rest_route=/ root (plain permalinks). */
export function wordpressEndpoint(base: string, resource: string): URL {
  const url = new URL(httpUrl(base, "WORDPRESS_URL"));
  if (url.hash) return fail("WORDPRESS_URL 不可包含 fragment");
  const route = `/t2-portfolio/v1/${resource}`;
  if (url.searchParams.has("rest_route")) {
    if (url.searchParams.get("rest_route") !== "/") return fail("rest_route 請設定為 /");
    url.searchParams.set("rest_route", route);
  } else {
    if (url.search) return fail("WORDPRESS_URL 含有不支援的查詢參數");
    const path = url.pathname.replace(/\/+$/, "");
    url.pathname = `${path.endsWith("/wp-json") ? path : `${path}/wp-json`}${route}`;
  }
  return url;
}

async function read(url: URL, fetcher: Fetcher) {
  let response: Response;
  try { response = await fetcher(url, { headers: { Accept: "application/json" }, signal: AbortSignal.timeout(15000), redirect: "error" }); }
  catch { return fail(`無法讀取 ${url.pathname}，請確認後台網址、SSL 與連線`); }
  if (!response.ok) return fail(`後台回應 HTTP ${response.status}`);
  if (!response.headers.get("content-type")?.includes("application/json")) return fail("後台未回傳 JSON，請確認外掛已啟用");
  let data: unknown;
  try { data = await response.json(); } catch { return fail("後台 JSON 無法解析"); }
  if (!Array.isArray(data)) return fail("後台必須回傳陣列");
  return { response, data };
}

export async function loadWordPressCatalog(base: string, fetcher: Fetcher = fetch): Promise<WordPressCatalog> {
  const { data: categoryData } = await read(wordpressEndpoint(base, "categories"), fetcher);
  const categories = categoryData.map(category);
  const categoryMap = new Map(categories.map((item) => [item.key, item]));
  if (categoryMap.size !== categories.length) return fail("分類代稱重複");
  const cases: WordPressCase[] = [];
  let pages = 1;
  for (let page = 1; page <= pages; page++) {
    const url = wordpressEndpoint(base, "works");
    url.searchParams.set("page", String(page));
    url.searchParams.set("per_page", "100");
    const { response, data } = await read(url, fetcher);
    const rawPages = response.headers.get("X-WP-TotalPages");
    if (rawPages === null || !/^\d+$/.test(rawPages)) return fail("後台缺少正確分頁資訊");
    const totalPages = Math.max(1, Number(rawPages));
    if (!Number.isSafeInteger(totalPages) || totalPages > 100) return fail("後台頁數超過支援範圍");
    if (page === 1) pages = totalPages;
    else if (pages !== totalPages) return fail("建置期間作品數量改變，請重新建置");
    if (data.length > 100 || (page < pages && data.length === 0)) return fail("後台分頁內容不正確");
    cases.push(...data.map((item) => work(item, categoryMap)));
  }
  if (new Set(cases.map((item) => item.slug.toLowerCase())).size !== cases.length) return fail("作品代稱重複，無法產生唯一作品頁");
  return { cases: cases.sort((a, b) => a.displayOrder - b.displayOrder || a.slug.localeCompare(b.slug)), categories };
}
