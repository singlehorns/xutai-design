import { getCollection } from "astro:content";
import { adaptLocalCase, caseCategories, createCaseViewModel } from "./case-adapter";
import type { CaseCategoryOption, CaseViewModel } from "./case-model";
import { loadWordPressCatalog } from "./wordpress-source";

export interface CaseCatalog { cases: CaseViewModel[]; categories: CaseCategoryOption[]; }
let catalogPromise: Promise<CaseCatalog> | undefined;

async function loadCatalog(): Promise<CaseCatalog> {
  const wordpressUrl = import.meta.env.WORDPRESS_URL?.trim();
  if (wordpressUrl) {
    const catalog = await loadWordPressCatalog(wordpressUrl);
    return { categories: catalog.categories, cases: catalog.cases.map(({ categoryTerms, ...data }) => createCaseViewModel(data, { categories: categoryTerms })) };
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
