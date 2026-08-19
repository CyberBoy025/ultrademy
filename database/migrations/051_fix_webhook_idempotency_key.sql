-- SECURITY FIX.
--
-- 05-finance-payments.md §5 says to insert the event row first (so the unique constraint
-- rejects duplicates) and then act. Implemented literally, that lets an ATTACKER block a
-- payment: send an unsigned request carrying a guessed event_id, it claims the unique
-- slot, and the genuine signed webhook that follows is discarded as a duplicate. The
-- invoice is then never credited.
--
-- Fix: only VALIDLY SIGNED events take part in deduplication. `dedupe_key` is NULL when
-- the signature failed, and NULLs never collide in a UNIQUE index — so bogus deliveries
-- are still recorded (they are a security signal worth keeping) but cannot displace a
-- real one.

ALTER TABLE webhook_events DROP INDEX uq_webhook_event;

ALTER TABLE webhook_events
  ADD COLUMN dedupe_key VARCHAR(160) AS (IF(signature_valid = 1, event_id, NULL)) STORED,
  ADD UNIQUE KEY uq_webhook_signed_event (provider, dedupe_key),
  ADD KEY ix_webhook_event_lookup (provider, event_id);
