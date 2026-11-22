# Inventorise to FNA link: message format (version 1)

Inventorise sends one HTTPS POST per change to `https://orderd.in/api/inventorise`.

## Signing
Headers:
- `X-Timestamp`: seconds since 1970. FNA refuses it if it is more than 5 minutes away from its own clock.
- `X-Signature`: hex HMAC-SHA256 of `"<timestamp>.<raw body>"`, using the shared secret.

FNA keeps the secret in `.env` as `INVENTORISE_SECRET` and the receiving company as `INVENTORISE_MERCHANT_ID`.
Inventorise keeps the same secret in its own `.env`. Never put the secret in chat or in git.

## Body (JSON)
| field | meaning |
|---|---|
| `type` | `purchase_order`, `sales_order`, `purchase_bill` or `sales_invoice` |
| `ref` | Inventorise's own number (PO-0420, #141262, a bill or invoice id). The same `type` and `ref` sent again updates the same record. |
| `status` | the order's status in Inventorise (orders only) |
| `date`, `expected`, `due_date` | `YYYY-MM-DD` |
| `order_ref` | on a bill or invoice: the `ref` of its order |
| `number` | the supplier's bill number, or our invoice number |
| `notes` | free text |
| `place_of_supply` | sales invoice: the state supplied to |
| `party.key` | stable id, for example `supplier:12` or `customer:40` |
| `party.name`, `party.gstin`, `party.pan`, `party.email`, `party.phone`, `party.state` | the supplier or customer |
| `lines[]` | `description`, `sku`, `hsn`, `qty`, `unit_price` (before GST), `gst_rate` (a fraction: 0.1800 means 18%) |

## What FNA does
- Order: stored as an Order. It posts nothing.
- Bill or invoice: stored as a draft bill or invoice, linked to its order. A person checks and posts it. If it is already posted, a later change is not applied.
- Party matching: a party Inventorise's id was matched to before; else the same GSTIN (the name may differ); else, when no GSTIN is sent, the same name. If the name + GSTIN combination is not in FNA, the supplier or customer is created automatically. A message with no party name waits on the "To confirm" screen.

Replies: `200 {"status":"applied"|"held"|"error","note":...}`, `403` not signed correctly, `422` fields missing.
Nothing is sent back to Inventorise. Payments there come from Xero.
