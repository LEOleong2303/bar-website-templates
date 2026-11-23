# Bar Photo Menu — sample template

A mobile-first photo menu for a small bar, restaurant or café: a sticky
category tab bar on top, one full-screen menu page per swipe, black and
champagne-gold styling. Guests tap a category and scroll the pages.

> This is a **sample template**. The page images come from a real bar menu —
> replace them with pages of your own drinks before using it for real customers.
>
> **Live demo:** https://leoleong2303.github.io/bar-website-templates/bar-photo-menu/

## Files

| File | What it is |
| --- | --- |
| `index.html` | Tab bar + menu pages (all styling and logic inline) |
| `assets/page-01.jpg` … `page-24.jpg` | The menu pages, 1080×1920 (9:16), 2×2 grid |
| `single-file.html` | Same menu as one self-contained file (pages embedded) — currently still the previous 25-page set; regenerate it from the new pages before deploying it |
| `assets/` | Only the page images — no other dependencies |

The 24 sample pages mirror a typical bar menu: cover, cocktails (7 pages),
house-mix specials (2), zero-proof (4), sparkling specials (1), beers (2),
draught (2), whisky (3), wine (1), shisha (1). Categories never mix on a
page, so some pages have empty cells — that is intentional.

## Deploy

Static hosting only — no server, no database, no build step. Upload the
folder to any static host (or open `single-file.html` directly if you only
want one file to deploy).

## Customize

- **Your photos:** replace each `assets/page-XX.jpg` with your own page
  render or drink photo at the same 1080×1920 size and 2×2 layout. Keep the
  file names — `index.html` references them by name.
- **Categories:** edit the tab buttons in `index.html` (labels + `data-tab`)
  and the matching `<div class="panel">` blocks. Each panel lists its pages
  in order.
- **Shop name / address:** search `index.html` for `SAMPLE BAR` and the
  sample address — title, meta description, cover alt text and footer.
- **Colours:** the palette is in the CSS `:root` block (`--gold`, `--bg`, …).
- **Currency:** prices are baked into the sample page images; your own pages
  carry whatever currency you use.
- **Single-file version:** `single-file.html` embeds the same 24 pages as
  base64, so it works from a single file with no `assets/` folder. Regenerate
  it by replacing the embedded images, or just deploy the folder version.
  (It still embeds the previous 25-page set until regenerated.)

## Notes

- The sample pages are a real bar menu (drink names and prices baked into
  the images). Replace them with pages of your own drinks.
- Photos load lazily except the cover, so the first paint stays fast.

## License

MIT — see `LICENSE`.

---

# 酒吧相片餐牌 — 範例模板

一間細酒吧、餐廳或者 café 用嘅手機相片餐牌：頂部係固定分類 tab 欄，每頁一個全屏餐牌頁，黑底香檳金配色。客人撳分類，碌去睇。

> 呢個係**範例模板**，入面嘅頁面嚟自一份真實酒吧餐牌。正式用之前，請換返自己飲品嘅相。

## 檔案

| 檔案 | 用途 |
| --- | --- |
| `index.html` | Tab 欄 + 餐牌頁（樣式同邏輯全部 inline） |
| `assets/page-01.jpg` … `page-24.jpg` | 餐牌頁，1080×1920（9:16），2×2 排版 |
| `single-file.html` | 同一個餐牌嘅單一檔案版（頁面全部包埋入面）——而家仲係舊嘅 25 頁版，部署之前要先用新頁重新包埋佢 |
| `assets/` | 只係餐牌頁圖片，冇其他依賴 |

24 頁範例跟一間典型酒吧餐牌：封面、雞尾酒（7 頁）、基酒特調（2 頁）、無酒精特飲（4 頁）、氣泡特飲（1 頁）、啤酒（2 頁）、生啤（2 頁）、威士忌（3 頁）、紅酒（1 頁）、水煙（1 頁）。分類唔會喺同一頁混合，所以有啲頁面會有空格——係刻意嘅。

## 部署

純靜態寄存就得——唔需要伺服器、數據庫或者 build。將成個資料夾上載去任何靜態寄存；如果只想得一個檔案，直接用 `single-file.html`。

## 自訂

- **你嘅相：**將每張 `assets/page-XX.jpg` 換返自己嘅餐牌頁或者飲品相，尺寸保持 1080×1920、2×2 排版。檔名唔好改——`index.html` 係用檔名引用佢哋。
- **分類：**改 `index.html` 入面嘅 tab 按鈕（文字 + `data-tab`）同對應嘅 `<div class="panel">` 區塊，每個區塊按順序列出嗰個分類嘅頁面。
- **鋪頭名 / 地址：**喺 `index.html` 搵 `SAMPLE BAR` 同範例地址——標題、meta 描述、封面 alt 文字同頁尾。
- **顏色：**CSS `:root` 入面（`--gold`、`--bg`……）。
- **貨幣：**價錢已經燒入範例頁面圖片；你自己嘅頁面用返你嘅貨幣。
- **單一檔案版：**`single-file.html` 將 24 頁以 base64 包埋入面，得一個檔案就跑到，唔需要 `assets/` 資料夾。換相之後重新包埋佢，或者直接用資料夾版。（佢而家仲係舊嘅 25 頁版，未重新包過。）

## 注意事項

- 範例頁面係一份真實酒吧餐牌（飲品名同價錢已經燒入圖片）。請換返自己餐牌嘅頁面。
- 除封面之外，相片全部 lazy load，所以首次顯示夠快。
