export const handwritingFontStack = [
  '"Caveat"',
  '"Snell Roundhand"',
  '"Apple Chancery"',
  '"Segoe Script"',
  '"Lucida Handwriting"',
  '"Brush Script MT"',
  "cursive",
].join(", ");

export const handwritingPresets = {
  subtle: {
    size: "clamp(2.6rem, 6vw, 5.2rem)",
    duration: 1900,
    delay: 180,
    strokeWidth: 0.65,
    color: "var(--home-ink, #111111)",
    fillOpacity: 0.68,
  },
  standard: {
    size: "clamp(3.2rem, 7.5vw, 6.8rem)",
    duration: 1550,
    delay: 100,
    strokeWidth: 0.75,
    color: "var(--home-ink, #111111)",
    fillOpacity: 0.78,
  },
  expressive: {
    size: "clamp(3.8rem, 10vw, 8rem)",
    duration: 1250,
    delay: 60,
    strokeWidth: 1.05,
    color: "var(--home-ink, #111111)",
    fillOpacity: 0.9,
  },
} as const;

export type HandwritingPresetName = keyof typeof handwritingPresets;
