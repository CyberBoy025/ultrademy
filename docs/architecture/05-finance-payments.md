# 05 — Finance, Payments & Audit

Phase 1 deliverable. Covers §26–§31 (payments, manual transfer, accounting, cashier,
financial structure, centre-based finance) and §39 (audit logging).

---

## 1. Money handling rules

Non-negotiable, because these are the mistakes that are expensive to undo:

1. **Integer minor units.** ₦15,000.50 is stored as `1500050`. No floats anywhere in
   the money path — not in PHP, not in MySQL, not in JSON.
2. **Currency travels with amount.** Every money column has a sibling `currency`.
   Even if UltrAdemy only ever sells in NGN, retrofitting this later means touching
   every financial table.
3. **Ledger rows are immutable.** A payment is never edited. Corrections are new rows —
   a reversal, a refund, an adjustment.
4. **Invoice total is derived from lines and stored.** Stored so a historical invoice
   still totals correctly after a price change; derived so it can be verified.
5. **Nothing that moves money is a single-actor operation.** Record and verify are
   different permissions held by different roles (§29).

---

## 2. Financial spine (§30)

```
USER
  │
  ├─ owes ─────────▶ INVOICE ──── lines ──▶ INVOICE_LINES
  │                     │
  │                     ├─ payable_type/payable_id ──▶ enrolment | subscription
  │                     │                              | application_fee
  │                     │
  │                     └─ settled by ──▶ PAYMENT
  │                                          │
  │                                          ├─ gateway: paystack | flutterwave
  │                                          ├─ manual:  bank_transfer | cash
  │                                          │
  │                                          ├─ verification ──▶ verified_by/at
  │                                          ├─ produces ──────▶ RECEIPT
  │                                          └─ may be ────────▶ REFUND
  │
  └─ centre attribution on invoice + payment ──▶ centre reports (§31)
```

An invoice may be settled by several payments (instalments); a payment belongs to
exactly one invoice. `invoices.status` moves `issued → part_paid → paid` as payments
land.

---

## 3. Invoice lifecycle

```
 DRAFT ──issue──▶ ISSUED ──partial payment──▶ PART_PAID ──balance──▶ PAID
                    │                              │
                    ├──due_on passes──▶ OVERDUE ───┘
                    │
                    └──void──▶ VOID  (audited, requires finance.invoice.void)
```

An invoice is never deleted. Voiding writes a reason and an audit entry. A paid invoice
cannot be voided — it must be refunded, which is a different permission held by a
different role.

**Numbering:** `INV-{YY}{MM}-{sequence}`, sequence per month, generated inside the same
transaction as the insert with a row lock. Gaps are acceptable; duplicates are not.

---

## 4. Gateway abstraction (§26)

The brief requires Paystack and Flutterwave and asks that more can be added. One
interface, one implementation per provider, zero provider names in business code.

```php
interface PaymentGateway
{
    public function initialise(Invoice $invoice, User $payer): GatewaySession;
    public function verify(string $gatewayReference): GatewayResult;
    public function parseWebhook(Request $request): WebhookEvent;
    public function verifySignature(Request $request): bool;
}
```

```
PaymentService
      │
      ├─ resolves gateway by  payments.method
      │
      ├─ PaystackGateway     implements PaymentGateway
      ├─ FlutterwaveGateway  implements PaymentGateway
      └─ ManualGateway       implements PaymentGateway  (bank transfer / cash)
```

Adding a provider is a new class plus a settings row. `PaymentService` does not change.

Treating manual payment as a gateway implementation is what keeps §27's flow from
becoming a special case threaded through the code — it has the same lifecycle, it just
has a human where the API call would be.

---

## 5. Online payment flow

```
Student clicks Pay
      │
      ▼
PaymentService::initialise()
      │  create payment (status = initiated, reference = ULP-…)
      │  call gateway → returns authorisation URL
      ▼
Redirect to gateway
      │
      ├──────────── user pays ────────────┐
      │                                    │
      ▼                                    ▼
Callback (browser)                    Webhook (server)
  UNTRUSTED — display only              TRUSTED — source of truth
  never marks paid                      verifies signature
      │                                  verifies amount + currency
      │                                  marks payment successful
      │                                  updates invoice status
      │                                  issues receipt
      │                                  fires PaymentSucceeded
      ▼                                    │
"Confirming your payment…" ◀───────────────┘
```

**The browser callback never marks a payment successful.** It is user-controllable — a
student can hit the success URL without paying. Only the signed webhook, or an explicit
server-side `verify()` call, changes payment status. This single rule prevents the most
common payment fraud in systems of this kind.

If the webhook is delayed, a scheduled reconciliation job polls `verify()` for payments
stuck in `initiated` for more than 10 minutes.

### Webhook idempotency

```
webhook_events (
    id, provider, event_id UNIQUE(provider, event_id),
    payload json, signature_valid bool,
    received_at, processed_at, error
)
```

Gateways retry. Without the unique constraint, a retried `charge.success` credits the
invoice twice. Processing order: insert the event row (unique constraint rejects
duplicates), *then* act. Signature failures are stored with `signature_valid = false`
and never processed — they are also a security signal worth alerting on.

---

## 6. Manual bank transfer (§27)

The flow with the most fraud surface, so it gets the most control.

```
STUDENT                          CASHIER / ACCOUNTANT
   │
   ├─ selects invoice
   ├─ sees bank details (from settings, not hard-coded)
   ├─ pays into the bank
   ├─ submits reference + uploads proof
   │      payment.status = pending_verification
   │                                    │
   │                                    ├─ sees queue of pending verifications
   │                                    ├─ checks bank statement
   │                                    │
   │                          ┌─────────┴─────────┐
   │                          ▼                   ▼
   │                      APPROVE              REJECT
   │                 status=successful     status=failed
   │                 verified_by/at set    reason recorded
   │                 receipt issued        student notified
   │                          │                   │
   └──────── notified ◀───────┴───────────────────┘
```

Controls:

- The submitter can never be the verifier. Enforced in `PaymentService`, not the UI —
  if `verified_by === payment.user_id`, throw.
- `finance.payment.verify` is **not** granted to `cashier` (§29). A cashier records
  cash taken at the desk; they do not confirm bank transfers.
- Proof upload is validated by MIME type and re-encoded, not trusted by extension (§42
  secure file uploads).
- Every state change writes an audit row with old and new values. §27: *"Every manual
  payment must have an audit trail."*
- Duplicate reference detection: warn if the same bank reference was already submitted.

---

## 7. Centre attribution (§31)

`centre_id` is nullable on `invoices`, `payments` and `expenses`.

| Transaction | `centre_id` |
|---|---|
| Enrolment at Gwagwalada | Gwagwalada |
| Enrolment in an online cohort | `NULL` |
| Platform subscription | `NULL` |
| Cash taken at the Kubwa desk | Kubwa |
| Kubwa generator diesel | Kubwa |
| Head-office salaries | `NULL` |

`NULL` means **global/online**, and reports surface it as its own line — never folded
into a physical centre. §31 is explicit that online transactions must not be forced into
an incorrect physical location.

Attribution is set at creation from the enrolment's cohort and is immutable afterwards.
A student transferring centres does not retroactively move last term's revenue.

Reports this enables: revenue by centre, expenses by centre, outstanding by centre,
centre P&L, and Gwagwalada vs Kubwa vs online comparison (§16).

---

## 8. Cashier vs accountant, concretely (§28, §29)

| Capability | Cashier | Accountant |
|---|---|---|
| Record a cash payment | ✓ (own centre) | ✓ |
| Issue a receipt | ✓ (own centre) | ✓ |
| View a student's payment history | ✓ (own centre) | ✓ |
| Daily till reconciliation | ✓ | ✓ |
| **Verify a bank transfer** | — | ✓ |
| **Void an invoice** | — | ✓ |
| **Create a refund** | — | ✓ |
| **Approve a refund** | — | — (management) |
| Record an expense | — | ✓ |
| Financial reports | — | ✓ |
| Bank reconciliation | — | ✓ |

Read the bold rows as the separation-of-duties spine: the person handling cash cannot
also confirm money that never arrived, reverse a charge, or hide it by voiding.

---

## 9. Audit architecture (§39)

Insert-only table, written by model observers rather than by hand at each call site —
manual calls get forgotten exactly where they matter most.

```
audit_logs
  actor_user_id     nullable (null = system/scheduler)
  action            'payment.verified', 'application.approved', …
  auditable_type    'App\Models\Payment'
  auditable_id      1234
  old_values        json — only the changed keys
  new_values        json — only the changed keys
  ip_address, user_agent
  centre_id         nullable, denormalised for scoped filtering
  created_at
```

**Always audited:**

- every financial transaction and status change
- payment verification and rejection
- invoice void, refund create and approve
- application approve, reject, assign
- enrolment create, transfer, withdraw
- role grant and revoke, permission change
- subscription create, cancel, entitlement override
- user suspend, reactivate, email change
- programme approve and publish
- message moderation, group membership change
- settings changes

**Never audited:** page views, logins that succeed (rate-limited elsewhere), read
queries. Auditing reads at this volume buries the signal.

**Sensitive value handling:** `old_values`/`new_values` never store password hashes,
tokens, or full payment card data. Fields on a redaction list are recorded as
`"[redacted]"` so the *fact* of the change is captured without the content.

**Retention:** financial audit rows kept 7 years (assumption — needs legal
confirmation). Others 2 years, then archived to cold storage. `audit_logs` will be the
biggest table in the system; plan monthly partitioning before it passes ~10M rows.

---

## 10. Reconciliation

A nightly job, plus an on-demand run for accountants:

1. Fetch gateway settlements for the period.
2. Match against `payments` by `gateway_reference`.
3. Flag: gateway-side payment with no local record; local `successful` payment absent
   gateway-side; amount mismatches.
4. Write a `reconciliation_runs` row with counts and exceptions.
5. Exceptions land in an accountant queue — never auto-corrected.

Auto-correcting a mismatch is how a bug quietly rewrites the books. A human decides.

---

## 11. Open decisions

| # | Decision | Default taken |
|---|---|---|
| 2 | Multi-currency, or NGN only | NGN only; schema carries `currency` |
| 3 | Application fee — charged, or only on admission | On admission only |
| 17 | Instalment plans for programme fees | Supported — invoice with a due schedule |
| 18 | Late-payment penalty | None |
| 19 | Who owns bank account details — settings, or per centre | Global settings |
| 20 | Are receipts sequentially numbered for tax purposes | Yes, `RCP-{YY}{MM}-{seq}` |
| 21 | Audit retention for financial records | 7 years — **needs legal confirmation** |
