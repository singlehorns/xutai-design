import { getSolutions, type SolutionId } from "./solutions";

export type CooperationMode = "project" | "monthly";

export interface Cooperation {
  id: CooperationMode;
  slug: string;
  mode: CooperationMode;
  title: string;
  shortTitle: string;
  summary: string;
  description: string;
  suitableFor: readonly string[];
  exampleNeeds: readonly string[];
  /** Scope basis, cooperation cycle, then flexibility. Shared by details and comparison. */
  scope: readonly [basis: string, cycle: string, flexibility: string];
  workflow: readonly string[];
  scheduling: string;
  meetingPolicy: string;
  feedbackPolicy: string;
  hoursPolicy: string;
  pricingNote: string;
  featured: boolean;
  displayOrder: number;
}

export const onSiteMeetingNote = "月度／半遠端合作可依需求討論固定現場協作日；實際頻率、地點與工作內容於合作前確認。";
const meetingPolicy = "以線上溝通與定期進度確認為主，會議或現場協作安排依專案需求討論。";
const pricingNote = "依實際需求與範圍評估。";

const cooperations: readonly Cooperation[] = [
  {
    id: "project",
    slug: "project",
    mode: "project",
    title: "單次專案",
    shortTitle: "單次專案",
    summary: "以需求範圍、交付成果與排程為基礎，協助在確認的期間內完成一項專案。",
    description: "當需求與交付範圍相對明確，可以先整理專案目標、需要的素材與預定完成時間，再確認製作範圍、交付內容、報價與排程。我會依確認的方向製作，透過階段回饋完成調整與交付。",
    suitableFor: ["有明確專案目標", "有預定完成時間", "需要一次完成多種素材", "暫時沒有持續性的日常需求"],
    exampleNeeds: ["Logo / 品牌識別", "活動視覺", "教育招生宣傳", "印刷文宣", "展場素材", "WordPress 網站", "企業形象網站", "Landing Page", "商品上市素材", "短影音", "整合型品牌 / 活動專案"],
    scope: ["以確認的需求範圍與交付成果為基礎。", "依專案排程合作至完成交付。", "新增或調整項目先確認範圍與排程。"],
    workflow: ["需求討論", "確認製作範圍", "確認交付內容", "提出報價與排程", "製作", "回饋調整", "完成交付"],
    scheduling: "依專案目標與交付期限安排階段工作，調整需求時同步確認排程。",
    meetingPolicy,
    feedbackPolicy: "在線上彙整各階段回饋，確認調整內容後製作；新增範圍另行討論。",
    hoursPolicy: "以確認的需求範圍與交付成果作為合作評估基礎。",
    pricingNote,
    featured: true,
    displayOrder: 1
  },
  {
    id: "monthly",
    slug: "monthly",
    mode: "monthly",
    title: "月度／半遠端合作",
    shortTitle: "月度合作",
    summary: "為持續性的設計與網站需求預留合作產能，可搭配固定遠端執行與部分現場協作。",
    description: "適合每個月都有設計、網站、行銷素材或品牌維護需求，且希望固定窗口更深入理解內部節奏的企業。合作可採月度支援，依需求安排遠端製作、線上會議與部分到貴公司現場協作；每月也可依合作目標整理 KPI／工作指標、完成項目與後續優先順序，讓長期支援有可追蹤的依據。",
    suitableFor: ["每個月都有持續的設計或網站需求", "希望固定窗口半遠端協作", "需要部分時間到公司現場整理需求", "希望以 KPI／工作指標追蹤月度成果", "同時存在設計、網站與行銷素材需求"],
    exampleNeeds: ["社群素材", "活動視覺", "印刷物", "網站內容更新", "WordPress 維護", "Landing Page", "商品素材", "廣告素材", "短影音", "月度 KPI／工作指標整理", "半遠端現場協作"],
    scope: ["預留月度合作產能，持續處理約定的設計、網站與內容支援。", "以月度為節奏，可安排遠端執行、線上會議與部分現場協作。", "每月整理 KPI／工作指標、完成項目與下一階段優先順序。"],
    workflow: ["月度目標確認", "KPI／工作指標設定", "需求與優先順序整理", "遠端製作與現場協作", "固定進度確認", "月度成果整理", "進入下一期"],
    scheduling: "依月度目標、工作量與現場協作需求安排排程，遠端製作與到貴公司協作的比例於合作前確認。",
    meetingPolicy: "以線上溝通與定期進度確認為主，可依月度合作需求安排部分現場協作或到貴公司工作日。",
    feedbackPolicy: "透過線上回饋、固定進度確認與月度 KPI／工作指標整理，彙整調整事項並納入後續工作安排。",
    hoursPolicy: "以預留月度產能與半遠端協作節奏安排，現場時段、遠端工作量與可承接範圍於合作前確認。",
    pricingNote,
    featured: true,
    displayOrder: 2
  }
];

export const commonWorkingMethods = ["遠端執行與半遠端協作", "固定聯絡窗口", "需求與優先順序整理", "工作排程", "定期進度確認", "KPI／工作指標整理", "完成交付或持續支援"] as const;

export const commonWorkflow = [
  { title: "提出需求", description: "分享想完成的事、使用情境與目前已有的資料。" },
  { title: "整理範圍與排程", description: "一起確認製作項目、交付內容、優先順序與工作安排。" },
  { title: "設計製作與確認", description: "依確認的內容進行設計或網站工作，並透過線上回饋確認調整。" },
  { title: "完成交付或延續支援", description: "整理完成項目與交付內容；若是月度合作，再確認下一階段需求。" }
] as const;

export function getCooperations(): Cooperation[] {
  return [...cooperations].sort((a, b) => a.displayOrder - b.displayOrder);
}

export function getFeaturedCooperations(): Cooperation[] {
  return getCooperations().filter((cooperation) => cooperation.featured);
}

/** No separate comparison copy: each value comes from the detailed model. */
export function getCooperationComparison() {
  return getCooperations().map((cooperation) => ({
    cooperation,
    suitableFor: cooperation.suitableFor[0],
    scope: cooperation.scope[0],
    scheduling: cooperation.scheduling,
    cycle: cooperation.scope[1],
    flexibility: cooperation.scope[2]
  }));
}

const solutionRelations: readonly {
  solutionId: SolutionId;
  modes: readonly CooperationMode[];
  followUpModes: readonly CooperationMode[];
  note: string;
}[] = [
  { solutionId: "product-launch", modes: ["project"], followUpModes: ["monthly"], note: "可先以明確專案完成上市素材；上市後若有持續需求，再討論固定的合作節奏。" },
  { solutionId: "ongoing-brand-support", modes: ["monthly"], followUpModes: [], note: "持續需求可討論月度／半遠端合作，依需求安排遠端製作、部分現場協作與 KPI／工作指標整理。實際模式仍依工作內容確認。" }
];

export function getCooperationSolutionExamples() {
  const solutions = getSolutions();
  return solutionRelations.map((relation) => {
    const solution = solutions.find((item) => item.id === relation.solutionId);
    if (!solution) throw new Error("Missing related solution: " + relation.solutionId);
    return {
      solution,
      modes: getCooperations().filter((item) => relation.modes.includes(item.mode)),
      followUpModes: getCooperations().filter((item) => relation.followUpModes.includes(item.mode)),
      note: relation.note
    };
  });
}
