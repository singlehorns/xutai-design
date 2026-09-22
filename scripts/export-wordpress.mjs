import { readFile, readdir, realpath, mkdir, copyFile, writeFile, stat } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { parse } from "yaml";

// Only approved public work Markdown and public/images/works are read.
// No original portfolio files, resumes, backups, credentials or local docs are exported.
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const output = path.resolve(root, process.argv[2] ?? `backups/wordpress-export-${new Date().toISOString().replace(/[:.]/g, "-")}`);
if (!output.startsWith(path.join(root, "backups") + path.sep)) throw new Error("Export destination must be inside this project's ignored backups directory.");
try { await stat(output); throw new Error("Export destination already exists; choose a new folder to preserve earlier exports."); }
catch (error) { if (error.code !== "ENOENT") throw error; }
const mediaRoot = await realpath(path.join(root, "public/images/works"));
const categoryLabels = { brand: "品牌與視覺", print: "印刷與輸出", web: "網站", social: "社群與廣告", motion: "影音" };
const legacyMap = { visual: "brand", brand: "brand", print: "print", web: "web", admin: "web", social: "social", motion: "motion" };
const serviceSlugs = { brand: "brand-visual-design", print: "print-production", web: "web-wordpress", social: "social-digital-support", motion: "video-motion-content" };
const serviceLabels = { brand: "品牌與視覺設計", print: "印刷與實體輸出", web: "網站與 WordPress", social: "社群與數位行銷支援", motion: "影片與動態內容" };
const pending = /待補|待確認|待提供|未確認/;
const confirmed = (value) => typeof value === "string" && value.trim() && !pending.test(value) ? value.trim() : undefined;
const escape = (value) => String(value).replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#39;");
const paragraph = (value) => `<!-- wp:paragraph --><p>${escape(value)}</p><!-- /wp:paragraph -->`;
const heading = (value) => `<!-- wp:heading --><h2 class="wp-block-heading">${escape(value)}</h2><!-- /wp:heading -->`;
const allMedia = new Map();
const works = [];
for (const file of (await readdir(path.join(root, "src/content/works"))).filter((file) => file.endsWith(".md")).sort()) {
  const raw = await readFile(path.join(root, "src/content/works", file), "utf8");
  const match = raw.match(/^---\r?\n([\s\S]*?)\r?\n---\r?\n([\s\S]*)$/);
  if (!match) throw new Error(`Invalid frontmatter: ${file}`);
  const data = parse(match[1]);
  if (data.visible === false) continue;
  const slug = file.slice(0, -3);
  const sources = [data.coverImage ?? data.cover, ...(data.gallery ?? data.images ?? [])].filter(Boolean);
  const images = [];
  for (const source of sources) {
    const image = typeof source === "string" ? { src: source, alt: `${data.title} 作品圖片` } : source;
    if (!/^\/images\/works\/[^/\\]+\.(?:jpg|jpeg|png|webp|gif)$/i.test(image.src)) throw new Error(`Non-public or unsupported work image: ${image.src}`);
    const filename = path.basename(image.src);
    const absolute = await realpath(path.join(mediaRoot, filename));
    if (!absolute.startsWith(mediaRoot + path.sep)) throw new Error(`Image outside public works: ${filename}`);
    allMedia.set(filename, absolute);
    if (!images.some((item) => item.filename === filename)) images.push({ filename, alt: image.alt || `${data.title} 作品圖片`, ...(image.caption ? { caption: image.caption } : {}) });
  }
  const html = [];
  for (const [key, label] of [["background", "專案背景"], ["challenge", "面對的課題"], ["approach", "執行方式"]]) {
    if (confirmed(data[key])) html.push(heading(label), paragraph(data[key]));
  }
  const deliverables = (data.deliverables ?? []).map(confirmed).filter(Boolean);
  if (deliverables.length) html.push(heading("實際交付內容"), ...deliverables.map(paragraph));
  if (images.length) {
    html.push(heading("作品畫面"));
    for (const image of images) html.push(`<!-- wp:image {"sizeSlug":"full","linkDestination":"none"} --><figure class="wp-block-image size-full"><img src="t2-media://${image.filename}" alt="${escape(image.alt)}"/>${image.caption ? `<figcaption class="wp-element-caption">${escape(image.caption)}</figcaption>` : ""}</figure><!-- /wp:image -->`);
  }
  const outcomes = (data.outcomes ?? []).map(confirmed).filter(Boolean);
  if (outcomes.length) html.push(heading("專案成果"), ...outcomes.map(paragraph));
  const categories = [...new Set([...(data.serviceIds ?? []), ...(data.categories ?? []).map((key) => legacyMap[key]).filter(Boolean)])].filter((key) => categoryLabels[key]);
  const firstService = Object.keys(serviceSlugs).find((key) => (data.serviceIds ?? []).includes(key));
  const referenceUrl = [...match[2].matchAll(/^\s*-\s+(https?:\/\/[^\s]+)\s*$/gm)][0]?.[1];
  const relatedLink = data.externalUrl ? { href: data.externalUrl, label: "前往網站" } : referenceUrl ? { href: referenceUrl, label: "查看相關作品" } : firstService ? { href: `https://singlehorns.github.io/xutai-design/services/#${serviceSlugs[firstService]}`, label: serviceLabels[firstService] } : null;
  const summary = data.summary.match(/^.*?[。！？.!?]/)?.[0] ?? data.summary;
  works.push({ slug, title: data.title, summary, contentHtml: html.join("\n\n"), categories, images, cover: images[0]?.filename ?? null, relatedLink, featured: Boolean(data.featured), displayOrder: data.displayOrder ?? data.order ?? 0, serviceIds: data.serviceIds ?? [], contentStatus: data.contentStatus ?? "legacy", ...(confirmed(data.clientName ?? data.client) ? { clientName: confirmed(data.clientName ?? data.client) } : {}), ...(confirmed(data.year) ? { year: confirmed(data.year) } : {}) });
}
works.sort((a, b) => a.displayOrder - b.displayOrder);
const categories = Object.entries(categoryLabels).filter(([key]) => works.some((item) => item.categories.includes(key))).map(([key, label]) => ({ key, label }));
await mkdir(path.join(output, "images"), { recursive: true });
for (const [filename, source] of allMedia) await copyFile(source, path.join(output, "images", filename));
await writeFile(path.join(output, "manifest.json"), JSON.stringify({ version: 1, categories, works }, null, 2) + "\n", "utf8");
console.log(`Exported ${works.length} works, ${categories.length} categories, ${allMedia.size} approved public images to ${output}`);
