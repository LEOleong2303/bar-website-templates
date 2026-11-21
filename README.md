# Bar Website Templates

Website templates for small bars, restaurants and cafés — taken from a real working bar site and generalized so any small food-and-drink business can use them. No frameworks, no database, no build step.

小酒吧 / 小餐廳用的網站模板，全部來自一間真實營業緊嘅酒吧，已經清走晒店名、地址、價錢同相片，換成範例資料。唔使框架、唔使資料庫、唔使 build。

## What's inside / 入面有咩

| Folder | What it is | Tech |
| --- | --- | --- |
| `bar-ordering-system/` | Guest ordering + bar admin screen: cart, table/bar-seat picker, order status, PIN-locked admin, Telegram notifications for new orders | PHP 8.2 + JSON files, runs on cheap shared hosting |
| `bar-photo-menu/` | Mobile photo menu with category tabs; also ships as one self-contained `single-file.html` (all images embedded) | Static HTML, any static host |
| `daily-activities-page/` | Guest-facing daily activities / promo page (tonight's feature, happy hour, weekly rhythm) | Static HTML, any static host |

Each folder has its own README (English + 中文) with setup and customization steps. 每個 folder 入面都有自己嘅 README（英文 + 中文），寫明點安裝同點改。

## Photos / 相片

The sample photos (JPG / WebP under each `assets/` folder) are **not** in this first commit — GitHub's file tools here only accept text files, so the images are uploaded separately via the GitHub web UI (drag the `assets/` folders in). Until then, image slots show placeholders. Swap in your own photos; the folder READMEs say which files map to which drinks.

範例相片（每個 `assets/` 入面嘅 JPG / WebP）**唔喺第一次 commit 入面**——呢度 push 檔案只接受文字檔，相片要另外經 GitHub 網頁 drag 入去。未上傳之前，相位會顯示 placeholder。記得換返自己舖頭啲相，邊個檔對邊款飲品，每個 folder 嘅 README 有寫。

## License

MIT — see `LICENSE` in each folder. 全部 MIT license。
