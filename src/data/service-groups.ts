/** Fixed Works filters mirror the four services currently offered on the Services page. */
export const serviceGroups = [
  { key: "web", label: "網站建置・維護管理" },
  { key: "event", label: "活動・教育推廣" },
  { key: "social", label: "社群・廣告推廣" },
  { key: "print", label: "商家印刷・製作" }
] as const;

export type ServiceGroupId = typeof serviceGroups[number]["key"];
export const isServiceGroupId = (value: unknown): value is ServiceGroupId => serviceGroups.some((group) => group.key === value);

/** No guessed mapping for the retired brand/motion service IDs. */
export const legacyServiceGroups = (ids: readonly string[]): ServiceGroupId[] => serviceGroups.filter((group) => group.key !== "event" && ids.includes(group.key)).map((group) => group.key);
