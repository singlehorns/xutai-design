import { getCollection } from "astro:content";
import { adaptLocalCase, caseCategories } from "./case-adapter";
import type { CaseViewModel } from "./case-model";

export async function getCases(): Promise<CaseViewModel[]> {
  return (await getCollection("works"))
    .filter((entry) => entry.data.visible)
    .map(adaptLocalCase)
    .sort((a, b) => a.displayOrder - b.displayOrder);
}

export async function getFeaturedCases(limit = 4): Promise<CaseViewModel[]> {
  return (await getCases()).filter((item) => item.featured).slice(0, limit);
}

export function getAvailableCaseCategories(cases: readonly CaseViewModel[]) {
  return caseCategories.filter((category) => cases.some((item) => item.categories.includes(category.key)));
}
