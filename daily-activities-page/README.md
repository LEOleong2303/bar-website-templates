# Daily Activities Page — sample template

A guest-facing “what’s on tonight” page for a small bar, restaurant or café:
hero banner, tonight’s feature, the evening’s timeline, everyday offers, a
week rhythm grid, and visit / order links. One self-contained `index.html`
plus a hero image — no server, no database, no build step.

> This is a **sample template** with placeholder content. Replace the shop
> name, address, links and tonight’s feature with your own before publishing.

## Files

| File | What it is |
| --- | --- |
| `index.html` | The whole page (styling and logic inline) |
| `assets/hero.webp` | Hero banner image (AI-generated sample bar interior) |

## What it shows

- **Hero** — shop name, tonight’s headline and tags. The date pill always
  shows **today** (filled in by JavaScript), so the page never goes stale on
  the date.
- **Tonight feature** — one highlighted event with a promo strip (the sample
  is a football night with three matches and a tonight-only discount).
- **Tonight’s flow** — a timeline from Happy Hour to close.
- **Everyday offers** — the standing deals that apply daily.
- **Week rhythm** — a 7-day grid; today’s cell is highlighted automatically.
- **Visit / order** — address, order and menu buttons.

## Deploy

Static hosting only. Upload the folder to any static host.

## Customize — the daily routine

This page is meant to be edited often. The things you will change:

1. **Tonight’s feature** — the `.tonight` section: headline, promo strip,
   and the feature cards (matches, tastings, live music — whatever tonight
   is). Yesterday’s feature must come out; never leave an expired discount
   showing.
2. **Timeline** — the `.timeline` rows for tonight’s running order.
3. **“Tonight” reminder box** — the short checklist in the info section.
4. **Date** — you don’t need to touch it; the date pill is automatic.

Things you change once:

- **Shop name / address:** search `index.html` for `SAMPLE BAR` and the
  sample address — title, hero, info box and footer.
- **Links:** the order and menu buttons point to placeholders
  (`https://your-ordering-site.example/` and
  `https://your-menu-link.example/`). Point them at your real ordering page
  and menu.
- **Everyday offers / week grid:** edit the `.grid` cards and `.week` days
  to match your standing deals and weekly rhythm.
- **Hero image:** replace `assets/hero.webp` with a photo of your own venue.
- **Colours:** the palette is in the CSS `:root` block (`--gold`, `--bg`, …).
  The page also adapts to light mode automatically.

## Notes

- The football fixtures in the sample are a worked example of a “tonight
  feature”, not live data. Replace them with your own event.
- Prices in the sample are examples — update them with your real offers, and
  remove any discount the moment it expires.

## License

MIT — see `LICENSE`.

---

# 每日活動頁 — 範例模板

一間細酒吧、餐廳或者 café 俾客人睇嘅「今晚有咩玩」頁面：hero 橫幅、今晚精選、當晚流程、每日固定優惠、一週節奏、同埋到店 / 點餐連結。得一個 `index.html` 加一張 hero 相——唔需要伺服器、數據庫或者 build。

> 呢個係**範例模板**，入面全部係佔位內容。公開之前，請換返自己鋪頭嘅名、地址、連結同今晚精選。

## 檔案

| 檔案 | 用途 |
| --- | --- |
| `index.html` | 成頁嘢（樣式同邏輯全部 inline） |
| `assets/hero.webp` | Hero 橫幅圖片（AI 生成嘅範例酒吧內景） |

## 頁面內容

- **Hero**——鋪頭名、今晚標題同標籤。日期 pill 永遠顯示**今日**（JavaScript 自動填），唔會出現過期日期。
- **今晚精選**——一個重點活動加優惠橫條（範例係睇波夜，三場波加今晚限定折扣）。
- **今晚流程**——由 Happy Hour 到收工嘅時間表。
- **每日固定優惠**——日日都適用嘅優惠。
- **一週節奏**——七日格仔，今日嗰格會自動highlight。
- **到店 / 點餐**——地址、點餐同睇餐牌按鈕。

## 部署

純靜態寄存就得。將成個資料夾上載去任何靜態寄存。

## 自訂——每日要做嘅嘢

呢頁係設計嚟成日改嘅。每日要換嘅：

1. **今晚精選**——`.tonight` 嗰區：標題、優惠橫條、同精選卡片（波、試飲、live 音樂——今晚係咩就寫咩）。琴日嘅精選一定要拎走，唔好留低過期折扣俾客人睇到。
2. **時間表**——`.timeline` 嗰幾行，今晚嘅流程。
3. **「今晚提提你」**——info 區入面嘅 checklist。
4. **日期**——唔使理，日期 pill 係自動嘅。

一次過改好就得嘅嘢：

- **鋪頭名 / 地址：**喺 `index.html` 搵 `SAMPLE BAR` 同範例地址——標題、hero、info box 同頁尾。
- **連結：**點餐同睇餐牌按鈕而家指住佔位連結（`https://your-ordering-site.example/` 同 `https://your-menu-link.example/`），改返你真正嘅點餐頁同餐牌連結。
- **每日優惠 / 一週節奏：**改 `.grid` 卡片同 `.week` 格仔，配合你嘅固定優惠同一週主題。
- **Hero 圖片：**將 `assets/hero.webp` 換返自己鋪頭嘅相。
- **顏色：**CSS `:root` 入面（`--gold`、`--bg`……），頁面會自動適應淺色模式。

## 注意事項

- 範例入面嘅足球賽程只係「今晚精選」嘅寫法示範，唔係實時資料。換返你自己嘅活動。
- 範例價錢都係例子——用返你真正嘅優惠價，優惠一過期即刻拎走。
