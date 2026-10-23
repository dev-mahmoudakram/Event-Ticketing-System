# Payment

Scope: Online payment via the link emailed after approval. On success, Ticket moves Payment
Pending → Ticket Issued, generating the QR code and Workshop Booking Key, then emailing the
issued ticket.

Status: 🟡 Built on Kashier hosted payment sessions — needs live keys and a first live payment.

## Flow

1. Admin approves → `PaymentLinkSender` opens a Kashier session (24h, 5 failed attempts, every
   method enabled on the merchant account) and emails its `sessionUrl` directly. Kashier failing
   or the email failing rolls the approval back. A ticket with nothing to pay (100% coupon) is
   issued straight away instead.
2. "Resend payment link" on the Ticket Requests screen (Awaiting payment tab): checks open links
   with Kashier first, then abandons them and emails a new 24h link. Automatic resend: not yet.
3. Kashier's webhook (`payments.kashier.webhook`, CSRF-exempt) and the customer's return
   (`payments.kashier.return`) are both signature-checked, then `TicketPaymentConfirmer` asks
   Kashier for the session and issues the ticket only if it is PAID with the same order
   reference, amount and currency. One conditional update means only one caller issues.
4. `payments:sync` (scheduled every 15 minutes) settles links whose webhook never came.

## Security

- Only Kashier-issued `https://payments.kashier.io/` URLs are emailed or linked.
- Return and webhook URLs are built from APP_URL, never the request's Host header.
- The return page never redirects; it renders our own view.
- Amounts always come from our database; Kashier's figures are only compared against it.
- Keys live encrypted in `payment_settings`, editable by Admins only, never shown back.

## Checklist

- [x] Payment gateway selection (Kashier)
- [x] Payment link flow (Payment Pending → Ticket Issued) with `ticket_payments` records
- [x] Admin Payment Settings (mode, merchant ID, keys)
- [x] Admin resend of the payment link
- [x] QR code + Workshop Booking Key generation on payment success
- [x] Issued-ticket email
- [ ] Go live: live keys, scheduler cron on the server, one small real payment end to end
- [ ] Refunds from the dashboard (later)
- [ ] Automatic resend of expired links (later)
