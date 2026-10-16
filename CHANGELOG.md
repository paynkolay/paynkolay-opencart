# Changelog

## v1.2.0 (2026-10)

Security release. Fixes the critical finding of the October 2026 security review
(callback not bound to the order and amount) in the OpenCart 3.x and
4.x packages. Upgrading is strongly recommended.

### Removed

- **OpenCart 2.0 / 2.1 / 2.2 and 2.3 are no longer supported.** These OpenCart
  versions are end of life; their packages, Docker test stores and old builds
  were removed. Stores on OpenCart 2.x must upgrade OpenCart to 3.x or 4.x.

### Security

- **Callback is now bound to the order, amount and currency.** The payment
  callback (`successUrl` / `failUrl`) used to take the order id from the unsigned
  `CLIENT_REFERENCE_CODE` field and confirm it without checking what was paid, so
  a small payment could confirm an expensive order. Now:
  - When the payment form is rendered, the plugin stores the `clientRefCode`,
    order id, amount and currency it sends in a new table
    `<prefix>nkolaypos_payment` (created automatically on first use).
  - `CLIENT_REFERENCE_CODE` is only used to look up that row. The signed
    `AUTHORIZATION_AMOUNT` must be at least the stored amount and the current order
    total (compared in integer minor units; installment commission may raise it).
  - With a v2 hash the signed `CURRENCY_CODE` must match the order currency.
    Without a v2 hash only TRY orders are accepted.
  - The order must still be unpaid (status 0 / the store's default pending status)
    and use this payment method.
  - The gateway `REFERENCE_CODE` is single-use: it is stored with the attempt and a
    callback reusing it for another order is rejected. Repeating the same, already
    processed callback is treated as success without changing the order again.
  - The `REFERENCE_CODE` is written into the order history comment.
- **Response hash verification fixed.** The SDK read `hashDatav2` while the gateway
  posts `hashDataV2`, and its v2 formula left out `CURRENCY_CODE`. It now accepts
  both spellings, uses the documented formula
  `base64(SHA-512(MERCHANT_NO|REFERENCE_CODE|AUTH_CODE|RESPONSE_CODE|USE_3D|RND|INSTALLMENT|AUTHORIZATION_AMOUNT|CURRENCY_CODE|secret))`,
  and never falls back to the v1 `hashData` when a v2 field is present. Callbacks
  are rejected when the secret key is not configured.
- **AUTH_CODE is checked.** A payment counts as captured only when
  `RESPONSE_CODE` is 2 and `AUTH_CODE` is not empty, `0` or `00`. For 3D Secure
  with AUTO_COMPLETE off, `CompletePayment` is still called and its result is
  checked the same way; with AUTO_COMPLETE on, it is no longer called.
- **No gateway text shown to customers.** Failed or rejected callbacks show a fixed
  message; the reason is written to the OpenCart error log. A failed or forged
  callback never changes the order status.
- **Test mode is off by default** in the settings form.

### Merchant actions

- Install the package for your OpenCart version over the old one. The database
  user must be allowed to `CREATE TABLE` (it normally is).
- Payments started before the upgrade have no stored record and their callback is
  rejected; check orders paid around the upgrade in the paynkolay panel.
- Make sure **Test Mode** is off on the live store. If a store ever ran in live
  mode with the documented test secret, set your own live keys.
- Rejected callbacks are listed in System > Maintenance > Error Logs as
  `paynkolay callback rejected: ...`.
