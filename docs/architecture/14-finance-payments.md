# Phase 9 — Finance & Payments

Status: **built, running against the live database.** Implements
[05-finance-payments.md](05-finance-payments.md) (README §26–§31, §39). Decisions 17–21
were unanswered, so the documented defaults were taken — see §7.

This phase also closes the two manual bridges Phases 6 and 7 deliberately left open.

---

## 1. What was built

| Area | File(s) |
|---|---|
| Schema | migrations [`041`](../../database/migrations/041_create_number_sequences.sql)–[`051`](../../database/migrations/051_fix_webhook_idempotency_key.sql) |
| Money | [`app/core/Money.php`](../../app/core/Money.php) |
| Document numbering | [`app/core/DocumentNumber.php`](../../app/core/DocumentNumber.php) |
| Gateway abstraction | [`PaymentGatewayInterface`](../../app/core/PaymentGatewayInterface.php), [`Paystack`](../../app/core/PaystackGateway.php), [`Flutterwave`](../../app/core/FlutterwaveGateway.php), [`Manual`](../../app/core/ManualGateway.php) |
| Orchestration | [`app/core/PaymentService.php`](../../app/core/PaymentService.php) |
| Reconciliation | [`app/core/Reconciliation.php`](../../app/core/Reconciliation.php) |
| Models | [`Invoice`](../../app/models/Invoice.php), [`Payment`](../../app/models/Payment.php), [`Refund`](../../app/models/Refund.php), [`Expense`](../../app/models/Expense.php), [`FinanceReport`](../../app/models/FinanceReport.php) |
| UI | [`FinanceController`](../../app/controllers/FinanceController.php) + 8 views under `app/views/finance/` |
| Webhook receiver | [`public/webhook.php`](../../public/webhook.php) |
| Gateway return page | [`public/payment-return.php`](../../public/payment-return.php) |

## 2. The rule the whole phase turns on

§5: *"The browser callback never marks a payment successful. It is user-controllable — a
student can hit the success URL without paying."*

`public/payment-return.php` therefore writes **no** status of its own. It asks the
gateway server-side and reports the answer; if the gateway cannot confirm, it shows
"confirming…" and lets the webhook settle it. Verified by visiting the success URL for
an unpaid payment:

```
before: initiated
payment-return.php HTTP 200 — page says "Confirming your payment"
after:  initiated
```

Only a signed webhook or an explicit server-side `verify()` moves money.

## 3. Two real bugs found by testing, both fixed

### 3.1 Invoice numbering deadlocked under concurrency

`SELECT COUNT(*)+1` was never on the table, but the first implementation —
`INSERT IGNORE` then `SELECT … FOR UPDATE`, exactly as §3 describes — deadlocks: the
insert takes a shared gap lock and the select then tries to upgrade it. A load test of
six concurrent processes issuing eight invoices each **lost 15 of 48 attempts** to
deadlock. It never produced a duplicate number, but a dropped invoice is not acceptable
either.

Replaced with MySQL's atomic counter idiom (one statement claims the number and advances
the counter, `LAST_INSERT_ID()` carries the value back). Re-tested: **48 of 48 created,
48 distinct numbers, zero deadlocks.** `Database::transaction()` was also added, which
retries on deadlock/lock-timeout — anything touching money should go through it.

### 3.2 Webhook idempotency could be weaponised to block payments

§5 says to insert the event row first so the unique constraint rejects duplicates, then
act. Implemented literally, that is exploitable: an attacker sends an **unsigned**
request carrying a guessed `event_id`, it claims the unique slot, and the genuine signed
webhook that follows is discarded as a duplicate. **The invoice is then never credited.**

Observed directly in testing — an unsigned request made the subsequent correctly-signed
one return "Duplicate event ignored" with the payment left at `initiated`.

Fixed in migration [`051`](../../database/migrations/051_fix_webhook_idempotency_key.sql):
deduplication now applies only to **validly signed** events, via a generated `dedupe_key`
that is NULL when the signature failed (NULLs never collide in a unique index). Invalid
deliveries are still recorded — they are a security signal — but cannot displace a real
one. Re-tested end to end:

| Delivery | Result |
|---|---|
| No signature | 401, recorded, **does not block** |
| Wrong signature | 401, recorded, **does not block** |
| Valid signature | 200 Applied — payment successful, receipt `RCP-…`, invoice `paid` |
| Valid signature, retried | 200 Duplicate ignored — still exactly **one** successful payment |

## 4. Separation of duties, enforced in the service

§6: *"The submitter can never be the verifier. Enforced in PaymentService, not the UI."*

Proving this needed care: hiding the button is not a control. The test granted a student
`finance.payment.verify` (confirmed by the verification queue returning 200), obtained a
valid CSRF token, and POSTed the approval for her own payment. The request reached the
service and was rejected there — *"You cannot verify your own payment"* — with the
payment unchanged.

The §8 matrix is reproduced exactly in the permission seed, and the route sweep shows it:

| Route | cashier | accountant | management |
|---|---|---|---|
| Invoices / Payments | 200 | 200 | 200 |
| **Verify transfers** | **403** | 200 | **403** |
| **Expenses** | **403** | 200 | 403 |
| **Refunds** | **403** | 200 (raise) | 200 (approve) |
| **Reports** | **403** | 200 | 200 |

A cashier takes money and issues receipts. They cannot confirm money that never arrived,
reverse a charge, or hide one by voiding. Refunds are raised by finance and approved by
management — the accountant who raised one got **403** trying to approve it.

## 5. Gateway abstraction

One interface, one class per provider, no provider name in business code. Manual payment
implements the same interface — it has the same lifecycle, just with a human where the
API call would be, which keeps §27's bank-transfer flow from becoming a special case.

**The unit trap, encoded deliberately:** Paystack works in **minor** units (kobo, matching
our storage), Flutterwave in **major** units (naira as a decimal). Passing stored kobo
straight to Flutterwave would charge 100× the intended amount. Every conversion in
`FlutterwaveGateway` is explicit and commented.

**What is and is not tested.** The security-critical logic is fully exercised locally,
because signatures can be generated without credentials: Paystack's HMAC-SHA512 over the
raw body is tested above. What is **not** tested is live API traffic — `initialise()` and
`verify()` need real API keys and a network call. Those methods are written against the
documented request/response shapes but have **never executed against the real APIs**, and
should be treated as unverified until someone runs a sandbox transaction. Credentials
live in `settings` (so they can be rotated without a deploy); an empty key means that
provider is simply not offered to payers.

## 6. Centre attribution and the bridges

`centre_id` is nullable on invoices, payments and expenses. **NULL means online/global
and is reported as its own line**, never folded into a physical centre — §31 is explicit.
Scoped roles do not see NULL-centre rows, consistent with Decision 8.

The two manual bridges are now closed, verified end to end:

```
BRIDGE 1  subscription invoice paid
          subscription pending → active; online_learning entitlement no → yes

BRIDGE 2  enrolment invoice paid
          enrolment pending_payment → active
```

Both fire from `PaymentService::fulfil()` when an invoice reaches `paid`, whatever the
payment method — cash at the desk and a card online take the same path.

## 7. Decisions 17–21 — defaults taken

| # | Decision | Built as |
|---|---|---|
| 17 | Instalment plans | **Supported** — an invoice accepts several payments; status moves `issued → part_paid → paid` |
| 18 | Late-payment penalty | **None** — `Invoice::markOverdue()` flags overdue but charges nothing |
| 19 | Bank details: settings or per centre | **Global settings** (`bank_name`, `bank_account_name`, `bank_account_number`) |
| 20 | Sequentially numbered receipts | **Yes** — `RCP-YYMM-nnnn`, one per payment, enforced by a unique key |
| 21 | Financial audit retention | 7 years assumed — **still needs legal confirmation** |

## 8. Known gaps

1. **Live gateway calls are untested** (§5 above). Sandbox keys and one real transaction
   per provider should be run before launch.
2. **Reconciliation is mostly local.** It checks stuck payments, missing receipts and
   invoice-status drift with no credentials at all, and reports unconfigured providers as
   `gateway_unavailable` rather than silently skipping them — an empty exception list
   never means "all good" by default. Fetching gateway settlement files is not built.
3. **No PDF receipts or invoices.** Both are on-screen only; a printable/emailable
   document needs a PDF library and a template decision.
4. **No instalment *schedule*.** Partial payments work, but a plan with dated instalments
   and reminders is not modelled — README §17's "due schedule" is one due date today.
5. **No refund execution.** Approving a refund marks the payment `reversed` and records
   the correcting row; actually returning money via the gateway API is not implemented.
6. **Overdue sweeping is manual.** `Invoice::markOverdue()` exists but nothing calls it on
   a schedule yet — it belongs in the same cron as `expire-subscriptions.php`.

## 9. Demo state

`super@ultrademy.com` sees everything; `ifeoma.chukwu@ultrademy.com` (accountant) has the
full finance desk; `tunde.bakare@ultrademy.com` (cashier) demonstrates the restricted
view. Bank details and gateway keys are seeded **empty** on purpose — an administrator
must enter real values in Settings, and until they do, only bank transfer is offered.
