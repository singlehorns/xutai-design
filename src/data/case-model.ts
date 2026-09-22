import type { Service, ServiceId } from "./services";
import type { Solution, SolutionId } from "./solutions";

export type CaseContentStatus = "complete" | "partial" | "legacy";
export type CaseCategory = string;
export interface CaseCategoryOption { key: CaseCategory; label: string; }
export interface CaseRelatedLink { href: string; label: string; }

export interface CaseImage {
  src: string;
  alt: string;
  caption?: string;
}

/** Source-independent case contract. Unconfirmed facts remain absent. */
export interface CaseData {
  source?: "local" | "wordpress";
  /** Sanitized editor HTML. Undefined means the legacy image/section layout. */
  contentHtml?: string;
  /** Null explicitly disables the related link; undefined uses legacy data. */
  relatedLink?: CaseRelatedLink | null;
  id: string;
  slug: string;
  title: string;
  shortTitle: string;
  summary: string;
  clientName?: string;
  clientType?: string;
  industry?: string;
  year?: string;
  projectPeriod?: string;
  background?: string;
  challenge?: string;
  approach?: string;
  roles: string[];
  responsibilities: string[];
  deliverables: string[];
  serviceIds: ServiceId[];
  solutionIds: SolutionId[];
  tools: string[];
  technologies: string[];
  coverImage?: CaseImage;
  gallery: CaseImage[];
  externalUrl?: string;
  outcomes: string[];
  featured: boolean;
  displayOrder: number;
  contentStatus: CaseContentStatus;
}

/** Presentation data adds links, categories and missing-data notes without CMS types. */
export interface CaseViewModel extends CaseData {
  url: string;
  categories: CaseCategory[];
  categoryTerms: CaseCategoryOption[];
  categoryLabel: string;
  services: Service[];
  solutions: Solution[];
  referenceLinks: string[];
  pendingNotes: string[];
  statusLabel: string;
}
