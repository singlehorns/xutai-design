import type { CollectionEntry } from "astro:content";
import type { CaseCategory, CaseCategoryOption, CaseData, CaseImage, CaseViewModel } from "./case-model";
import { getServices } from "./services";
import { getSolutions } from "./solutions";
import { legacyServiceGroups } from "./service-groups";

export const caseCategories: readonly { key: CaseCategory; label: string }[] = [
  { key: "brand", label: "品牌與視覺" },
  { key: "print", label: "印刷與輸出" },
  { key: "web", label: "網站" },
  { key: "social", label: "社群與廣告" },
  { key: "motion", label: "影音" }
];

const pendingPattern = /待補|待確認|待提供|未確認/;
const confirmed = (value?: string): string | undefined => value?.trim() && !pendingPattern.test(value) ? value.trim() : undefined;
const confirmedList = (values: readonly string[] = []): string[] => values.map(confirmed).filter((value): value is string => Boolean(value));

function image(source: string | { src: string; alt?: string; caption?: string } | undefined, title: string): CaseImage | undefined {
  if (!source) return undefined;
  return typeof source === "string" ? { src: source, alt: `${title} 作品圖片` } : { src: source.src, alt: source.alt || `${title} 作品圖片`, caption: source.caption };
}

/** The only layer aware of Astro collection fields and the legacy Markdown body. */
export function adaptLocalCase(entry: CollectionEntry<"works">): CaseViewModel {
  const source = entry.data;
  const coverImage = image(source.coverImage ?? source.cover, source.title);
  const gallery = (source.gallery ?? source.images ?? [])
    .map((item) => image(item, source.title))
    .filter((item): item is CaseImage => Boolean(item));
  const caseData: CaseData = {
    id: source.id ?? entry.id,
    slug: entry.slug,
    title: source.title,
    shortTitle: source.shortTitle ?? source.title,
    summary: source.summary,
    clientName: confirmed(source.clientName ?? source.client),
    clientType: confirmed(source.clientType),
    industry: confirmed(source.industry),
    year: confirmed(source.year),
    projectPeriod: confirmed(source.projectPeriod),
    background: confirmed(source.background),
    challenge: confirmed(source.challenge),
    approach: confirmed(source.approach),
    roles: confirmedList(source.roles ?? source.role),
    responsibilities: confirmedList(source.responsibilities ?? source.responsibility),
    deliverables: confirmedList(source.deliverables),
    serviceIds: [...source.serviceIds],
    solutionIds: [...source.solutionIds],
    tools: confirmedList(source.tools),
    technologies: confirmedList(source.technologies),
    coverImage,
    gallery: gallery.length ? gallery : coverImage ? [coverImage] : [],
    externalUrl: source.externalUrl,
    outcomes: confirmedList(source.outcomes),
    featured: source.featured,
    displayOrder: source.displayOrder ?? source.order ?? 0,
    contentStatus: source.contentStatus ?? "legacy"
  };
  const rawNotes = [...(source.responsibilities ?? source.responsibility), ...(source.roles ?? source.role)]
    .filter((value) => pendingPattern.test(value));
  const missing: [boolean, string][] = [
    [!caseData.year, "專案年份待補"],
    [!caseData.clientName, "客戶／品牌資料待補"],
    [!caseData.background, "專案背景待補"],
    [!caseData.deliverables.length, "實際交付項目待補"],
    [!caseData.tools.length, "製作工具待補"],
    [!caseData.gallery.length, "專案圖片待補"]
  ];
  const referenceLinks = [...entry.body.matchAll(/^\s*-\s+(https?:\/\/[^\s]+)\s*$/gm)].map((match) => match[1]);
  return createCaseViewModel(caseData, {
    legacyCategories: source.categories,
    referenceLinks,
    pendingNotes: [...rawNotes, ...missing.filter(([isMissing]) => isMissing).map(([, label]) => label)]
  });
}

/** Any future API adapter can pass the same plain CaseData into this presenter. */
export function createCaseViewModel(data: CaseData, extra: { categories?: readonly CaseCategoryOption[]; legacyCategories?: readonly string[]; referenceLinks?: string[]; pendingNotes?: string[] } = {}): CaseViewModel {
  const services = getServices().filter((service) => data.serviceIds.includes(service.id));
  const solutions = getSolutions().filter((solution) => data.solutionIds.includes(solution.id));
  const legacyMap: Record<string, CaseCategory | undefined> = { visual: "brand", brand: "brand", print: "print", web: "web", admin: "web", social: "social", motion: "motion" };
  const categoryKeys = new Set<CaseCategory>([
    ...data.serviceIds,
    ...(extra.legacyCategories ?? []).map((category) => legacyMap[category]).filter((category): category is CaseCategory => Boolean(category))
  ]);
  const categories = extra.categories ?? caseCategories.filter((category) => categoryKeys.has(category.key));
  return {
    ...data,
    serviceGroups: data.serviceGroups ?? legacyServiceGroups(data.serviceIds),
    url: `/works/${data.slug}/`,
    categories: categories.map((category) => category.key),
    categoryTerms: [...categories],
    categoryLabel: categories.map((category) => category.label).join(" / ") || (data.source !== "wordpress" || (data.slug === "photography-sample" && data.contentStatus === "legacy") ? "影像紀錄" : "未分類"),
    services,
    solutions,
    referenceLinks: [...new Set(extra.referenceLinks ?? [])],
    pendingNotes: [...new Set(extra.pendingNotes ?? [])],
    statusLabel: data.contentStatus === "complete" ? "案例資料完整" : data.contentStatus === "partial" ? "部分專案資料待補" : "舊作紀錄"
  };
}
