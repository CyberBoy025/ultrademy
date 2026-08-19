<?php
declare(strict_types=1);

/**
 * Chat and groups (README §24).
 *
 * ENTITLEMENTS. This is the first place the metered side of the Phase 6 engine does real
 * work:
 *
 *   chat_direct — on/off. Required to open or post in a one-to-one thread.
 *   chat_groups — METERED. The limit is how many group conversations a person may be in
 *                 (Standard 2, Premium 10, Advanced unlimited). Enforced with
 *                 requireWithinLimit() when joining or creating one; being over the
 *                 limit never removes anyone from a group they are already in.
 *
 * Staff bypass both, because `comms` is a staff-implicit module (Decision 16) — an
 * instructor should not buy a package to answer a student.
 */
final class ChatController
{
    public static function index(): void
    {
        $userId = (int) Auth::id();
        $main = View::render('chat/index', [
            'conversations' => Conversation::forUser($userId),
            'canDirect' => Entitlements::can('chat_direct'),
            'groupLimit' => Entitlements::limitFor('chat_groups'),
            'groupCount' => Conversation::groupCountFor($userId),
            'canGroups' => Entitlements::can('chat_groups'),
            'canModerate' => Auth::can('comms.conversation.moderate'),
            'contacts' => self::contactsFor($userId),
        ]);
        View::shell('chat', 'Messages', $main);
    }

    public static function show(): void
    {
        $userId = (int) Auth::id();
        $id = (int) ($_GET['id'] ?? 0);
        $conversation = Conversation::find($id);
        if (!$conversation) {
            http_response_code(404);
            echo 'Conversation not found.';
            return;
        }

        $isParticipant = Conversation::isParticipant($id, $userId);
        $canModerate = Auth::can('comms.conversation.moderate');
        if (!$isParticipant && !$canModerate) {
            http_response_code(403);
            require dirname(__DIR__) . '/views/errors/403.php';
            exit;
        }

        // Reading a thread you are in requires the matching entitlement; a moderator
        // reviewing a reported thread does not need to have bought chat.
        if ($isParticipant && !$canModerate) {
            Entitlements::requireFeature($conversation['type'] === 'direct' ? 'chat_direct' : 'chat_groups');
        }

        if ($isParticipant) {
            Conversation::markRead($id, $userId);
        }

        $main = View::render('chat/show', [
            'conversation' => $conversation,
            'title' => Conversation::titleFor($conversation, $userId),
            'messages' => Message::forConversation($id),
            'participants' => Conversation::participants($id),
            'isParticipant' => $isParticipant,
            'canModerate' => $canModerate,
            'isMuted' => Conversation::isMuted($id, $userId),
            'myId' => $userId,
        ]);
        View::shell('chat', $conversation['title'] ?? 'Messages', $main);
    }

    /** Opens (or reuses) a one-to-one thread. */
    public static function startDirect(): void
    {
        Csrf::requireValid();
        Entitlements::requireFeature('chat_direct');

        $userId = (int) Auth::id();
        $otherId = (int) ($_POST['user_id'] ?? 0);
        if ($otherId === $userId || !User::find($otherId)) {
            Session::flash('error', 'Choose someone else to message.');
            header('Location: app.php?r=chat');
            exit;
        }
        if (!in_array($otherId, array_column(self::contactsFor($userId), 'id'), true)) {
            // You may only start a thread with someone you actually share a class,
            // cohort or staff relationship with — not any user id you can guess.
            Session::flash('error', 'You can only message people you share a class or centre with.');
            header('Location: app.php?r=chat');
            exit;
        }

        $id = Conversation::findOrCreateDirect($userId, $otherId);
        header('Location: app.php?r=chat.show&id=' . $id);
        exit;
    }

    public static function createGroup(): void
    {
        Csrf::requireValid();
        $userId = (int) Auth::id();

        // Metered: joining another group must fit inside the package's ceiling.
        Entitlements::requireWithinLimit('chat_groups', Conversation::groupCountFor($userId));

        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            Session::flash('error', 'Give the group a name.');
            header('Location: app.php?r=chat');
            exit;
        }
        $memberIds = array_map('intval', $_POST['member_ids'] ?? []);
        $allowed = array_column(self::contactsFor($userId), 'id');
        $memberIds = array_values(array_intersect($memberIds, $allowed));

        $id = Conversation::createGroup($title, $memberIds);
        Audit::log('conversation.created', 'conversations', $id, null, ['title' => $title, 'members' => count($memberIds)]);

        Notify::sendMany($memberIds, 'chat.added_to_group', 'general',
            'Added to ' . $title, Auth::name() . ' added you to a group conversation.',
            'app.php?r=chat.show&id=' . $id);

        Session::flash('success', 'Group created.');
        header('Location: app.php?r=chat.show&id=' . $id);
        exit;
    }

    /** Creates/refreshes the cohort group from README §24 and drops the user into it. */
    public static function cohortGroup(): void
    {
        Auth::requirePermission('operations.cohort.manage');
        Csrf::requireValid();
        $cohortId = (int) $_POST['cohort_id'];
        $id = Conversation::ensureCohortGroup($cohortId);
        Audit::log('conversation.cohort_group_synced', 'conversations', $id, null, ['cohort_id' => $cohortId]);
        Session::flash('success', 'Cohort group is up to date.');
        header('Location: app.php?r=chat.show&id=' . $id);
        exit;
    }

    public static function post(): void
    {
        Csrf::requireValid();
        $userId = (int) Auth::id();
        $conversationId = (int) ($_POST['conversation_id'] ?? 0);
        $conversation = Conversation::find($conversationId);

        if (!$conversation || !Conversation::isParticipant($conversationId, $userId)) {
            http_response_code(403);
            echo 'You are not in this conversation.';
            return;
        }
        Entitlements::requireFeature($conversation['type'] === 'direct' ? 'chat_direct' : 'chat_groups');

        $result = Message::post($conversationId, $userId, (string) ($_POST['body'] ?? ''), $_FILES['attachment'] ?? []);
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
            header('Location: app.php?r=chat.show&id=' . $conversationId);
            exit;
        }

        // Everyone else in the thread who has not muted it. Notify::send() collapses this
        // into a digest if a thread gets busy.
        $title = Conversation::titleFor($conversation, $userId);
        Notify::sendMany(
            Message::recipients($conversationId, $userId),
            'chat.message',
            'general',
            'New message from ' . Auth::name(),
            mb_substr(trim((string) ($_POST['body'] ?? '')), 0, 140) ?: 'Sent an attachment',
            'app.php?r=chat.show&id=' . $conversationId
        );

        header('Location: app.php?r=chat.show&id=' . $conversationId);
        exit;
    }

    public static function downloadAttachment(): void
    {
        $message = Message::find((int) ($_GET['id'] ?? 0));
        if (!$message || !$message['attachment_stored_name']) {
            http_response_code(404);
            echo 'Not found.';
            return;
        }
        $userId = (int) Auth::id();
        if (!Conversation::isParticipant((int) $message['conversation_id'], $userId) && !Auth::can('comms.conversation.moderate')) {
            http_response_code(403);
            echo 'Not permitted.';
            return;
        }
        if ($message['deleted_at'] !== null && !Auth::can('comms.conversation.moderate')) {
            http_response_code(403);
            echo 'This message was removed.';
            return;
        }
        Upload::stream(Conversation::SUBDIR, $message['attachment_stored_name'], $message['attachment_mime'], $message['attachment_original_name']);
    }

    public static function moderate(): void
    {
        Auth::requirePermission('comms.conversation.moderate');
        Csrf::requireValid();
        $messageId = (int) $_POST['id'];
        $message = Message::find($messageId);
        if (!$message) {
            http_response_code(404);
            echo 'Message not found.';
            return;
        }
        $reason = trim((string) ($_POST['reason'] ?? '')) ?: 'Removed by a moderator';
        Message::moderate($messageId, (int) Auth::id(), $reason);
        Audit::log('message.moderated', 'messages', $messageId, null, ['reason' => $reason]);

        if ($message['sender_id'] !== null) {
            Notify::send((int) $message['sender_id'], 'chat.message_removed', 'general',
                'A message you sent was removed', $reason, 'app.php?r=chat.show&id=' . $message['conversation_id']);
        }
        Session::flash('success', 'Message removed and the sender notified.');
        header('Location: app.php?r=chat.show&id=' . $message['conversation_id']);
        exit;
    }

    public static function mute(): void
    {
        Csrf::requireValid();
        $conversationId = (int) $_POST['conversation_id'];
        $userId = (int) Auth::id();
        if (!Conversation::isParticipant($conversationId, $userId)) {
            http_response_code(403);
            echo 'Not permitted.';
            return;
        }
        Conversation::setMuted($conversationId, $userId, ($_POST['muted'] ?? '1') === '1');
        header('Location: app.php?r=chat.show&id=' . $conversationId);
        exit;
    }

    public static function leave(): void
    {
        Csrf::requireValid();
        $conversationId = (int) $_POST['conversation_id'];
        Conversation::removeParticipant($conversationId, (int) Auth::id());
        Session::flash('success', 'You left the conversation.');
        header('Location: app.php?r=chat');
        exit;
    }

    /**
     * Who a person may start a conversation with.
     *
     * Deliberately NOT "every user". A student may message people in their cohorts and
     * the instructors teaching them; staff may message anyone at their centre(s). This is
     * the §42 rule — a student must never be able to reach another student's details just
     * by guessing an id.
     *
     * @return array<int,array{id:int,name:string,email:string,relation:string}>
     */
    public static function contactsFor(int $userId): array
    {
        if (Auth::isStaff($userId)) {
            $scope = Auth::scopeCentres('staff.member.view_any');
            if ($scope === null) {
                $rows = Database::all(
                    "SELECT DISTINCT u.id, CONCAT(pr.first_name,' ',pr.last_name) AS name, u.email, 'platform' AS relation
                     FROM users u LEFT JOIN user_profiles pr ON pr.user_id = u.id
                     WHERE u.status = 'active' AND u.id <> :u ORDER BY name",
                    ['u' => $userId]
                );
            } else {
                if (empty($scope)) {
                    return [];
                }
                $ph = implode(',', array_fill(0, count($scope), '?'));
                $rows = Database::query(
                    "SELECT DISTINCT u.id, CONCAT(pr.first_name,' ',pr.last_name) AS name, u.email, 'centre' AS relation
                     FROM users u
                     LEFT JOIN user_profiles pr ON pr.user_id = u.id
                     LEFT JOIN enrolments e ON e.user_id = u.id
                     LEFT JOIN staff_centres sc ON sc.user_id = u.id
                     WHERE u.status = 'active' AND u.id <> ?
                       AND (e.centre_id IN ($ph) OR sc.centre_id IN ($ph))
                     ORDER BY name",
                    array_merge([$userId], array_values($scope), array_values($scope))
                )->fetchAll();
            }
        } else {
            // Learners: people in their cohorts, plus the instructors of those cohorts.
            $rows = Database::all(
                "SELECT DISTINCT u.id, CONCAT(pr.first_name,' ',pr.last_name) AS name, u.email, 'cohort' AS relation
                 FROM enrolments mine
                 JOIN enrolments theirs ON theirs.cohort_id = mine.cohort_id
                 JOIN users u ON u.id = theirs.user_id
                 LEFT JOIN user_profiles pr ON pr.user_id = u.id
                 WHERE mine.user_id = :u AND u.id <> :u2 AND u.status = 'active'
                 UNION
                 SELECT DISTINCT u2.id, CONCAT(pr2.first_name,' ',pr2.last_name) AS name, u2.email, 'instructor' AS relation
                 FROM enrolments mine2
                 JOIN class_groups cg ON cg.cohort_id = mine2.cohort_id
                 JOIN users u2 ON u2.id = cg.instructor_user_id
                 LEFT JOIN user_profiles pr2 ON pr2.user_id = u2.id
                 WHERE mine2.user_id = :u3 AND u2.status = 'active'",
                ['u' => $userId, 'u2' => $userId, 'u3' => $userId]
            );
        }

        return array_map(static fn(array $r): array => [
            'id' => (int) $r['id'],
            'name' => trim((string) $r['name']) ?: (string) $r['email'],
            'email' => (string) $r['email'],
            'relation' => (string) $r['relation'],
        ], $rows);
    }
}
