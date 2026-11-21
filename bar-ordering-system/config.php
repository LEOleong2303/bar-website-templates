<?php
// Bar Ordering System — settings / 點餐系統設定
//
// Bar / admin PIN is stored ONLY as a SHA-256 hash, never as plain text.
// The default PIN is "1234". CHANGE IT on first use:
//   echo -n "YOUR-NEW-PIN" | sha256sum
// and paste the resulting hash below. Never write the plain PIN in any file.
$BAR_PIN_HASH = '03ac674216f3e15c761ee1a5e255f067953623c8b388b4459e13f978d7c846f4'; // SHA-256 of "1234" — change me!

// Telegram new-order notifications (optional). Orders work fine without them;
// they only push a message to your phone when a guest orders.
// Fill in BOTH values to enable, leave both empty to disable.
// TELEGRAM_BOT_TOKEN: create a bot with @BotFather (/newbot) and paste its token here.
// TELEGRAM_CHAT_ID: your Telegram chat ID (ask @userinfobot, or open
//   https://api.telegram.org/bot<token>/getUpdates and look for chat.id).
// The token is a secret. Never share it, and never commit a real one.
// If it leaks, revoke it in @BotFather with /revoke.
$TELEGRAM_BOT_TOKEN = '';
$TELEGRAM_CHAT_ID = '';

// Opening this file directly in a browser shows nothing.
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    http_response_code(403);
    exit;
}
