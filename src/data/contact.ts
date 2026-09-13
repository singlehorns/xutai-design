import { getCooperations } from "./cooperations";
import { getServices } from "./services";
import { site } from "./site";

export type ContactFormStatus = "preparing";

export const contactContent = {
  title: "先從需求開始。",
  intro:
    "有新的品牌、活動、網站或持續性的設計需求，可以先簡單整理目前想完成的事、預計時程與已有資料。正式聯絡方式確認後，會作為合作詢問入口公開。",
  ctaLabel: "討論合作需求",
  privacyNote: "聯絡資訊僅用於回覆與合作討論；目前不建立資料儲存機制，也不要求敏感私人資訊。",
  formStatus: {
    status: "preparing" as ContactFormStatus,
    label: "聯絡表單準備中",
    note: "目前尚未串接表單後端，因此不提供假送出流程。正式聯絡方式確認後，會在本頁公開。"
  },
  scheduleOptions: ["一個月內", "一至三個月", "三個月以上", "尚不確定"] as const,
  contactChannels: [
    {
      id: "email",
      label: "Email",
      value: site.contactEmail,
      href: site.contactEmail === "待使用者確認" ? "" : `mailto:${site.contactEmail}`,
      available: site.contactEmail !== "待使用者確認",
      note: site.contactEmail === "待使用者確認" ? "Email 尚待確認後公開。" : "請簡單附上需求方向、時程與參考資料。"
    }
  ] as const
} as const;

export function getContactCooperationOptions() {
  return [
    ...getCooperations().map((cooperation) => ({
      id: cooperation.id,
      label: cooperation.title,
      description: cooperation.summary
    })),
    {
      id: "unsure",
      label: "尚不確定，希望先討論",
      description: "還在整理方向時，也可以先描述目前情況，再一起判斷適合的合作方式。"
    }
  ] as const;
}

export function getContactServiceOptions() {
  return [
    ...getServices().map((service) => ({
      id: service.id,
      label: service.title,
      description: service.summary
    })),
    {
      id: "integrated",
      label: "整合需求",
      description: "需求同時包含品牌、印刷、網站、社群或影音內容，需要先整理優先順序。"
    }
  ] as const;
}
