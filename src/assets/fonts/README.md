# Handwriting font

- Family: Caveat, normal 400, Latin subset (Google Fonts v23).
- Downloaded: 2026-09-23.
- Source: https://fonts.gstatic.com/s/caveat/v23/WnznHAc5bAfYB2QRah7pcpNvOx-pjfJ9eIWpYQ.woff2
- Upstream: https://github.com/google/fonts/tree/main/ofl/caveat
- License: SIL Open Font License 1.1; bundled at `public/fonts/Caveat-OFL.txt`.

The shared family order is defined in `src/data/handwriting-presets.ts`.
`src/styles/handwriting.css` embeds this font, and `BaseHead.astro` preloads
the same bundled asset. No locally installed Caveat is used as the source.
