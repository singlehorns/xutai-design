import { getSolutions, type SolutionId } from "./solutions";

export type CooperationMode = "project" | "monthly" | "hourly";

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

export const onSiteMeetingNote = "必要時可依專案討論現場會議。";
const meetingPolicy = "以線上溝通與定期進度確認為主，會議安排依專案需求討論。";
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
    title: "固定月度合作",
    shortTitle: "月度合作",
    summary: "為持續性的設計與網站需求預留合作產能，建立固定的需求整理與製作節奏。",
    description: "適合每個月都有設計、網站、行銷素材或品牌維護需求的企業。透過固定窗口與預留產能，雙方持續整理工作、確認優先順序並安排製作，累積對品牌的理解，也讓日常需求有穩定的協作脈絡。",
    suitableFor: ["每個月都有持續的設計或網站需求", "不想每一張圖重新找外包", "希望固定由同一窗口處理", "希望品牌視覺逐漸保持一致", "同時存在設計與網站需求"],
    exampleNeeds: ["社群素材", "活動視覺", "印刷物", "網站內容更新", "WordPress 維護", "Landing Page", "商品素材", "廣告素材", "短影音", "不定期品牌設計需求"],
    scope: ["預留合作產能，持續處理約定的設計與網站工作。", "以月度為節奏，整理當期工作與下一期需求。", "當期需求依優先順序安排，新增工作先確認可承接範圍。"],
    workflow: ["每月需求整理", "確認優先順序", "排定工作", "持續製作", "固定確認", "當期工作整理", "進入下一期"],
    scheduling: "持續整理當期需求與優先順序，依預留產能安排工作並確認進度。",
    meetingPolicy,
    feedbackPolicy: "透過線上回饋與固定進度確認，彙整調整事項並納入後續工作安排。",
    hoursPolicy: "以預留合作產能與持續工作節奏安排，具體可承接範圍於合作前確認。",
    pricingNote,
    featured: true,
    displayOrder: 2
  },
  {
    id: "hourly",
    slug: "hourly",
    mode: "hourly",
    title: "彈性包時支援",
    shortTitle: "包時支援",
    summary: "預先安排一組可使用的設計與網站支援產能，依不定期出現的需求彈性使用。",
    description: "需求不固定，但偶爾需要設計、網站或素材修改時，可以先討論可支援的工作範圍。每次提出需求後，再確認內容、評估工作量、安排優先順序並執行，記錄使用狀況；下次也可依範圍切換成其他需要的工作。",
    suitableFor: ["需求不固定，但偶爾需要設計或網站協助", "不定期出現素材修改或尺寸延伸工作", "希望依當次需要切換不同類型的支援"],
    exampleNeeds: ["DM 修改", "網站內容調整", "商品圖", "社群尺寸延伸", "WordPress 更新", "海報修改", "短影音剪輯"],
    scope: ["預先安排可使用的設計與網站支援產能。", "依實際需求提出工作，使用期限另行確認。", "每次需求先檢查合作範圍與工作量，再安排執行。"],
    workflow: ["提出需求", "確認是否在合作範圍", "評估工作量", "安排優先順序", "執行", "記錄使用狀況"],
    scheduling: "依當次需求、工作量與優先順序確認可安排的時段，執行後整理使用狀況。",
    meetingPolicy,
    feedbackPolicy: "在線上確認當次需求與修改內容，彙整回饋後評估調整範圍。",
    hoursPolicy: "使用時數、有效期限、最小使用單位與加購方式，於合作前依實際需求確認。",
    pricingNote,
    featured: true,
    displayOrder: 3
  }
];

export const commonWorkingMethods = ["以遠端執行為主", "固定聯絡窗口", "需求與優先順序整理", "工作排程", "定期進度確認", "線上回饋", "完成交付或持續支援"] as const;

export const commonWorkflow = [
  { title: "提出需求", description: "分享想完成的事、使用情境與目前已有的資料。" },
  { title: "整理工作範圍", description: "一起確認製作項目、交付內容與需要準備的資料。" },
  { title: "確認優先順序與排程", description: "整理先後順序與工作安排，讓進度有共同依據。" },
  { title: "設計 / 製作", description: "由我依確認的內容與排程進行設計或網站工作。" },
  { title: "線上確認與回饋", description: "定期確認進度，彙整線上意見與需要調整的內容。" },
  { title: "完成交付", description: "確認完成項目、交付檔案或網站內容，並整理成果。" },
  { title: "整理下一階段需求", description: "持續型合作在當期工作完成後，整理下一階段的需求與優先順序。" }
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
  { solutionId: "ongoing-brand-support", modes: ["monthly", "hourly"], followUpModes: [], note: "持續需求可討論預留產能；不定期工作可討論彈性安排。實際模式仍依工作內容確認。" }
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
