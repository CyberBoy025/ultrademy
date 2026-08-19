# Phase 10 — Communication

Status: **built, running against the live database.** Covers README §24 (chat and groups)
and §37 (notification engine), implementing
[06-api-notifications.md](06-api-notifications.md) §2.

This is also the phase that pays back the notification TODOs left in Phases 6, 7 and 9.

---

## 1. What was built

| Area | File(s) |
|---|---|
| Schema | migrations [`052`](../../database/migrations/052_create_notifications.sql)–[`057`](../../database/migrations/057_create_announcements.sql) |
| Notification engine | [`app/core/Notify.php`](../../app/core/Notify.php) |
| Chat models | [`Conversation`](../../app/models/Conversation.php), [`Message`](../../app/models/Message.php) |
| Chat, groups, moderation | [`ChatController`](../../app/controllers/ChatController.php) + `chat/index`, `chat/show` |
| Inbox, preferences, announcements | [`NotificationController`](../../app/controllers/NotificationController.php) + 3 views |
| Email queue drain | [`database/notifications-cron.php`](../../database/notifications-cron.php) |

## 2. Notification rules, each one tested

06-api-notifications.md §2 sets four rules. All four are implemented and were verified
directly rather than assumed:

| Rule | Verified behaviour |
|---|---|
| Every notification lands **in-app** regardless of preference | opting out of `learning` email still produced the in-app row |
| Preferences gate email per category | opt-out produced `in_app` only |
| **Security, payment and admission cannot be switched off** | `setPreference('payment','email',false)` is refused; the email row was still created |
| Floods collapse into a digest | 12 sends of one type → **5 rows, `digest_count` 8**, instead of 12 rows and 12 emails |

The digest rule is the one that matters operationally: §2 warns that "a bulk attendance
mark sends 200 emails". Marking a register of 200 absent students now produces at most
five rows per student with a counter, not two hundred.

## 3. Closing the earlier TODOs

Phases 6, 7 and 9 each deliberately left a comment saying a notification belonged there
but the table did not exist yet. Those are now real calls, following §2's trigger table:

| Event | Who is told | Category |
|---|---|---|
| Application submitted | applicant **and** the preferred centre's manager | admission |
| Application approved / rejected | applicant | admission *(locked)* |
| Admitted / enrolment created | applicant | admission |
| Bank transfer verified / rejected | payer | payment *(locked)* |
| Invoice paid in full | payer | payment |
| Assignment graded | student | learning |
| Subscription expired / ended | subscriber | payment |
| New chat message | other participants, minus anyone who muted | general |
| Message removed by a moderator | the sender | general |
| Announcement published | the chosen audience | operations |

Admission and payment being *locked* categories is the point: an applicant cannot
accidentally switch off the message that tells them the outcome of their application, and
a payer cannot switch off the confirmation that their money arrived.

## 4. Chat, and who you are allowed to talk to

The part worth scrutinising is not sending messages — it is **who appears in the
recipient list**. `ChatController::contactsFor()` deliberately does not return "all
users":

- **Learners** see people in their own cohorts, plus the instructors teaching those
  cohorts.
- **Staff** see people at the centres their role is scoped to (global roles see everyone).

Verified with a real student: her classmate and her instructor were reachable; an
unrelated cashier was **not**. Posting the cashier's user id directly is refused by the
controller as well, not just absent from the dropdown — otherwise §42's "a student must
never be able to access another student's private information" would be one guessed id
away.

Direct threads are keyed on the sorted participant pair (`direct_key`), so clicking
"message" twice reuses the thread instead of forking it — verified: two requests, one
conversation row.

**Cohort groups** (README §24's "programme → cohort → members") are created on demand and
kept in step: enrolled students plus the cohort's instructors. Running it twice does not
create a second group — verified.

## 5. The metered entitlement finally does something

Phase 6 built `Entitlements::requireWithinLimit()` and documented that nothing existed to
meter yet. `chat_groups` is that thing: Basic none, Standard 2, Premium 10, Advanced
unlimited.

Tested against a Standard subscriber:

```
group 1 -> HTTP 302  (now in 1 group)
group 2 -> HTTP 302  (now in 2 groups)
group 3 -> HTTP 402  "You've reached your limit … allows 2"
```

Being over the limit never removes anyone from a group they are already in — the check is
on joining, not on membership. Staff bypass both chat entitlements because `comms` is a
staff-implicit module (Decision 16); an instructor should not buy a package to answer a
student.

## 6. Moderation keeps the evidence

02-data-model.md §9 requires soft deletion: *"a hard delete destroys the evidence."*

`Message::moderate()` sets `deleted_at`, `deleted_by` and a reason, and leaves the body
intact. Verified:

- Participants see *"Message removed by Chidi Nwosu."* — a tombstone, so a reply to the
  removed message still makes sense in context.
- Moderators additionally see the reason **and the original text**.
- The sender is notified that their message was removed, and why.
- A moderator can open a thread they are not a participant in (with a banner saying so
  and no ability to post); anyone else gets 403.

## 7. Email is queued but not sent — deliberately

There is **no mail transport configured**, and rather than call PHP's `mail()` — which
silently "succeeds" on a machine with no MTA and would make the queue *look* delivered —
`notifications-cron.php` reports the backlog and leaves it alone:

```
2 email notification(s) queued, but no mail transport is configured.
Nothing was sent and nothing was marked sent.
```

With a transport name set but no implementation, failures are recorded against each row
(`attempts`, `last_error`, `failed_at` after 3 tries) rather than hidden — §2: "a silently
dropped admission notification is a real-world problem."

Choosing a provider is an open decision. The seam is one function in that file.

## 8. Deliberately not built

- **SMS** — Decision 22 defers the provider. The `sms` channel exists in the enum and in
  preferences so adding one is data plus a transport, but nothing sends SMS, and the
  quiet-hours rule (§2) is therefore not implemented either.
- **Real-time delivery.** Messages appear on page load; there is no WebSocket or polling.
  For a training platform's messaging volume that is honest rather than limiting, but it
  is not "chat" in the instant-messaging sense.
- **Read receipts per message.** `last_read_at` is per participant per conversation, which
  is enough for unread counts but not for "seen by" on individual messages.
- **Attachment scanning.** Chat attachments go through the same `Upload` allow-list and
  content-sniffing as everything else, but there is no virus scanning.
- **The §1 API.** `06-api-notifications.md` also specifies `/api/v1/...` for future mobile
  apps. That is §41 work and belongs with its own phase, not folded in here.

## 9. Open questions

1. **Mail provider** — needed before any notification actually reaches someone off-platform.
2. Should learners be able to message *any* instructor at their centre, or only ones
   teaching them? Currently the latter, which is the tighter reading of §42.
3. Should cohort groups be created automatically when a cohort starts running, rather than
   on demand from a button?
4. Decision 23 (students opting out of attendance notices) is satisfied by the `operations`
   category being unlocked — worth confirming that is the intended granularity.
