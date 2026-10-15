import { getCollection } from "astro:content";
import { adaptLocalCase, caseCategories, createCaseViewModel } from "./case-adapter";
import type { CaseCategoryOption, CaseViewModel } from "./case-model";
import { loadWordPressCatalog } from "./wordpress-source";
import builtSnapshot from "./built-works-snapshot.json";

export interface CaseCatalog { cases: CaseViewModel[]; categories: CaseCategoryOption[]; }
let catalogPromise: Promise<CaseCatalog> | undefined;

async function loadCatalog(): Promise<CaseCatalog> {
  const wordpressUrl = import.meta.env.WORDPRESS_URL?.trim();
  if (wordpressUrl) {
    try {
      const catalog = await loadWordPressCatalog(wordpressUrl);
      return { categories: catalog.categories, cases: catalog.cases.map(({ categoryTerms, ...data }) => createCaseViewModel(data, { categories: categoryTerms })) };
    } catch (error) {
      console.error("WordPress 作品資料無法載入，改用本地作品資料：", error);
    }
  }
  // Versioned recovery of published public case pages. Unlike dist/, this is
  // available in CI when WordPress is unreachable or not configured.
  if (builtSnapshot.length) {
    const featuredSlugs = new Set(["久發網資訊工會", "職前訓練課程-招生廣告meta", "aaaaaa", "gm-集美精油官網"]);
    const categories = [
      { key: "brand", label: "品牌與視覺" }, { key: "print", label: "印刷與輸出" },
      { key: "web", label: "網站" }, { key: "social", label: "社群與廣告" }, { key: "motion", label: "影音" }
    ];
    const cases = builtSnapshot.map((item) => {
      const terms = categories.filter((category) => item.categories.includes(category.label));
      const imageList = item.images.map((image) => ({ ...image, alt: image.alt || `${item.title} 作品圖片` }));
      return createCaseViewModel({
        source: "local", id: `snapshot:${item.slug}`, slug: item.slug, title: item.title, shortTitle: item.title,
        summary: item.summary, roles: [], responsibilities: [], deliverables: [],
        serviceIds: terms.map((term) => term.key) as import("./services").ServiceId[],
        solutionIds: [], tools: [], technologies: [], coverImage: imageList[0], gallery: imageList.slice(1),
        outcomes: [], featured: featuredSlugs.has(item.slug), displayOrder: item.displayOrder, contentStatus: "legacy"
      }, { categories: item.categories.map((label) => categories.find((term) => term.label === label) ?? { key: label, label }) });
    });
    return { cases, categories: getAvailableCaseCategories(cases) };
  }
  const cases = (await getCollection("works"))
    .filter((entry) => entry.data.visible)
    .map(adaptLocalCase)
    .sort((a, b) => a.displayOrder - b.displayOrder);
  return { cases, categories: getAvailableCaseCategories(cases) };
}

/** One snapshot per production build; development reads fresh published WordPress content. */
export async function getCaseCatalog(): Promise<CaseCatalog> {
  if (import.meta.env.DEV) return loadCatalog();
  return catalogPromise ??= loadCatalog();
}

export async function getCases(): Promise<CaseViewModel[]> {
  return (await getCaseCatalog()).cases;
}

export async function getFeaturedCases(limit = 4): Promise<CaseViewModel[]> {
  return (await getCases()).filter((item) => item.featured).slice(0, limit);
}

export function getAvailableCaseCategories(cases: readonly CaseViewModel[]) {
  return caseCategories.filter((category) => cases.some((item) => item.categories.includes(category.key)));
}
