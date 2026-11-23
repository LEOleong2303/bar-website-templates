# Bar Ordering System — sample template

A complete, lightweight ordering system for a small bar, restaurant or café.
Guests order from their phones; the bar sees orders live on a tablet.
No framework, no database, no build step — plain PHP 8.2 + JSON files,
built to run on cheap shared hosting.

> This is a **sample template** with placeholder data. Replace the shop name,
> menu, prices and photos with your own before using it for real customers.
>
> **Live demo:** https://leoleong2303.github.io/bar-website-templates/bar-ordering-system/ — demo mode runs without PHP; orders stay in your browser. Bar admin PIN `1234`.

## What it does

- **Guest page (`index.html`)** — browse the menu by category, add drinks to a
  cart, pick a seat (bar counter or table number), add a note, submit.
  Payment stays offline: guests pay at the bar as usual.
- **Bar screen (same page, “Bar orders” view)** — new orders appear within
  5 seconds with a sound alert; mark orders “preparing” → “done”.
- **Admin page (`admin.html`)** — edit drink names, prices, categories,
  photos and options, and the guest page updates immediately. No redeploy needed.
- **Optional Telegram notifications** — every new order can also be pushed to
  your phone via a Telegram bot.
- **PIN protection** — the bar screen and admin page share one 4-digit PIN,
  stored only as a SHA-256 hash (see `config.php`).

## Files

| File | What it is |
| --- | --- |
| `index.html` | Guest ordering page + bar screen |
| `admin.html` | Menu editor (PIN required) |
| `api.php` | JSON API: menu, orders, login, Telegram push |
| `config.php` | PIN hash + Telegram settings — **edit this first** |
| `data/menu.json` | The whole menu (names, prices, photos, ingredients, options) |
| `data/orders.json` | Live orders (runtime data, starts empty) |
| `assets/cover.jpg` | Cover photo shown on the guest page |
| `assets/img/*.jpg` | One photo per drink |
| `.htaccess` / `data/.htaccess` | Block direct web access to `config.php` and `data/` |

## Deploy (shared hosting, ~10 minutes)

1. Upload everything to your hosting account's `public_html/` (or a subdomain
   folder). PHP 8.2 is enough; no extensions beyond stock PHP are needed.
2. **Change the PIN first.** The default PIN is `1234`. Compute a new hash:
   `echo -n "YOUR-NEW-PIN" | sha256sum`, and paste the hash into `config.php`
   as `$BAR_PIN_HASH`. Never write the plain PIN in any file.
3. Open the site, place a test order from your phone, then open the bar view
   on another device, enter the PIN, and confirm the order shows up.
4. (Optional) Telegram: create a bot with `@BotFather`, start a chat with it,
   get your chat ID from `@userinfobot`, and fill in
   `$TELEGRAM_BOT_TOKEN` / `$TELEGRAM_CHAT_ID` in `config.php`. Leave both
   empty to skip notifications entirely.

## Customize

- **Menu:** edit `data/menu.json` directly, or use `admin.html` (easier).
  Each item has `id`, `name`, `price` (number, `null` = price on request),
  `category`, `category_name`, `img`, and optional `ing` (ingredient line)
  and `opts` (a choice list — see Options below).
- **Categories:** the `CATS` list near the top of the `<script>` in both
  `index.html` and `admin.html` must match the `category` values in
  `menu.json`.
- **Photos:** put your drink photos in `assets/img/` (3:4 works best) and
  point `img` at them. In `admin.html`, the photo picker lists every
  `assets/img/*.jpg` file — update the `PHOTOS` list if you add or rename
  files by hand.
- **Shop name / address / seat labels:** search `index.html` and `admin.html`
  for `SAMPLE BAR` and the sample address; they appear in the title, header,
  cover alt text and footer.
- **Colours:** the palette lives in the CSS `:root` block at the top of each
  HTML file (`--gold`, `--bg`, …).
- **Currency:** prices are plain numbers; the `¥` symbol is rendered in the
  pages. Change it in `index.html` / `admin.html` if your currency differs.
- **Timezone:** `api.php` sets the timezone for order timestamps
  (`date_default_timezone_set`); change it to yours.

## Drink options （選項）

Some drinks offer a choice — hot or iced, single can / 6-pack / 12-pack,
or a glass / 1-litre pour. Each option is a label plus a price:

- Guests pick an option on the drink card. The cart, receipt, bar screen
  and Telegram message all show the chosen option, e.g.
  “Classic Lager (1L) ×1”.
- In `data/menu.json` an item with options carries an `opts` array, e.g.
  `"opts": [{"label": "Single", "price": 28}, {"label": "6-pack", "price": 160}]`.
  An item with no `opts` has no choice and sells at its base price.
- In `admin.html` the Options field takes `Label price / Label price`, e.g.
  `Single 28 / 6-pack 160 / 12-pack 302`. Leave it empty for no options.
- Changing the base price does **not** update the option prices — edit
  them in the same field.
- Sample pricing in this template: cans 6-pack = single ×6 at 5% off,
  12-pack = single ×12 at 10% off; 1-litre draught = two glasses for the
  two house lagers (no discount), 5% off the volume-proportional price for
  the rest. All prices are whole numbers.

## Notes & limits

- Orders are stored as a JSON file with a file lock — fine for one small
  venue, not for high-volume or multi-branch use.
- There is **no online payment** in this template by design.
- `data/orders.json` is runtime data. It is git-ignored; the shipped file is
  just an empty sample (`[]`).
- Most mixed-drink photos are AI-generated samples; the bottled / canned /
  draught product photos are clearly-marked placeholders — replace them with
  photos of your own drinks.

## License

MIT — see `LICENSE`.

---

# 酒吧點餐系統 — 範例模板

一間細酒吧、餐廳或者 café 用的輕量點餐系統：客人用手機落單，吧台用平板即時接單。
冇框架、冇數據庫、唔使 build——PHP 8.2 + JSON 檔案，平價共享主機就跑得起。

> 呢個係**範例模板**，入面全部係示例資料。正式用之前，請換返自己鋪頭嘅名、餐牌、價錢同相片。

## 功能

- **客人頁（`index.html`）**——分類睇餐牌、加飲品落購物車、揀吧位或者枱號、加備註、提交。錢照舊喺吧枱俾，呢度唔接網上付款。
- **吧台畫面（同一頁，「吧台訂單」）**——5 秒內收到新單，有提示聲；可以將訂單標做「製作中」→「完成」。
- **管理頁（`admin.html`）**——改飲品名、價錢、分類、相片、選項，儲存即刻生效，客人畫面即刻見到，唔使重新上載。
- **Telegram 新單通知（可選）**——每張新單都可以推去你手機。
- **PIN 保護**——吧台畫面同管理頁共用一個 4 位 PIN，只存 SHA-256 雜湊值，唔存明文（見 `config.php`）。

## 檔案

| 檔案 | 用途 |
| --- | --- |
| `index.html` | 客人點餐頁 + 吧台畫面 |
| `admin.html` | 餐牌編輯器（要入 PIN） |
| `api.php` | JSON API：餐牌、訂單、登入、Telegram 推送 |
| `config.php` | PIN 雜湊值 + Telegram 設定——**最先改呢個** |
| `data/menu.json` | 成個餐牌（名、價錢、相片、成份、選項） |
| `data/orders.json` | 即時訂單（運行時資料，初始係空） |
| `assets/cover.jpg` | 客人頁頂部封面相 |
| `assets/img/*.jpg` | 每款飲品一張相 |
| `.htaccess` / `data/.htaccess` | 禁止經網頁直接開 `config.php` 同 `data/` |

## 部署（共享主機，約 10 分鐘）

1. 將全部檔案上載去主機嘅 `public_html/`（或者子域名資料夾）。PHP 8.2 就夠，唔需要額外擴充。
2. **先改 PIN。**預設 PIN 係 `1234`。計新 PIN 嘅雜湊值：`echo -n "你嘅新PIN" | sha256sum`，將結果貼入 `config.php` 嘅 `$BAR_PIN_HASH`。唔好將明文 PIN 寫入任何檔案。
3. 用手機開網站落一張測試單，再用另一部機開吧台畫面，入 PIN，確認 5 秒內見到張單。
4. （可選）Telegram：喺 `@BotFather` 開個 bot，同佢講句嘢，喺 `@userinfobot` 攞你嘅 chat ID，填入 `config.php` 嘅 `$TELEGRAM_BOT_TOKEN` / `$TELEGRAM_CHAT_ID`。兩個留空即係唔用通知。

## 自訂

- **餐牌：**直接改 `data/menu.json`，或者用 `admin.html`（易啲）。每款飲品有 `id`、`name`、`price`（數字，`null` = 價錢面議）、`category`、`category_name`、`img`，同可選嘅 `ing`（成份行）同 `opts`（選項，見下面「選項」一節）。
- **分類：**`index.html` 同 `admin.html` 頂部 `<script>` 入面嘅 `CATS` 列表，要同 `menu.json` 嘅 `category` 值一致。
- **相片：**將飲品相放入 `assets/img/`（3:4 最啱），`img` 指住佢哋。`admin.html` 嘅相片選擇器會列出全部 `assets/img/*.jpg`——如果手動加名或者改名，記得更新 `PHOTOS` 列表。
- **鋪頭名 / 地址 / 座位：**喺 `index.html` 同 `admin.html` 搵 `SAMPLE BAR` 同範例地址——佢哋喺標題、頂欄、封面 alt 文字同頁尾。
- **顏色：**兩個 HTML 檔案頂部 CSS `:root` 入面（`--gold`、`--bg`……）。
- **貨幣：**價錢本身係純數字，`¥` 符號喺頁面渲染；唔同貨幣就喺 `index.html` / `admin.html` 改。
- **時區：**`api.php` 用 `date_default_timezone_set` 設定訂單時間時區，改返你嗰區。

## 選項

有啲飲品有得揀——凍或者熱、單罐 / 半打 / 一打，或者一杯 / 1 升。
每個選項係一個標籤加一個價錢：

- 客人喺飲品卡揀選項；購物車、成功畫面、吧台訂單、Telegram 通知都會
  顯示揀咗邊個選項，例如「經典拉格生啤（1升）×1」。
- `data/menu.json` 入面，有選項嘅飲品會有 `opts` 陣列，例如
  `"opts": [{"label": "單罐", "price": 28}, {"label": "半打", "price": 160}]`。
  冇 `opts` 即係冇得揀，用返原價。
- 管理頁嘅「選項」欄用「標籤 價錢 / 標籤 價錢」格式，例如
  `單罐 28 / 半打 160 / 一打 302`；留空即係冇選項。
- 改咗單價，選項價錢唔會自動跟住變，要人手改埋。
- 呢個模板嘅示例價錢：罐裝半打 = 單價 ×6 再 95折，一打 = 單價 ×12
  再 9折；精釀 1 升：兩款拉格生啤兩杯價唔打折，其他按容量比例再 95折。
  全部價錢都係整數。

## 注意事項

- 訂單用 JSON 檔案 + 檔案鎖儲存——一間細鋪夠用，唔適合大流量或者多分店。
- 呢個模板**唔接網上付款**，係刻意嘅。
- `data/orders.json` 係運行時資料，已經喺 `.gitignore` 入面；附送嗰個只係空範例（`[]`）。
- 52 款特調相片係 AI 生成嘅範例；瓶裝 / 罐裝 / 生啤產品相係明顯標示嘅佔位相——請換返自己飲品嘅相。
