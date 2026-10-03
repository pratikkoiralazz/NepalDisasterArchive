<?php
require_once __DIR__ . '/../config/auth.php';
require_admin();
$page_title = 'Human Story Submissions';

function human_submission_story_title(array $submission): string
{
    $title = trim((string)($submission['title'] ?? ''));
    if ($title === '') {
        $title = trim((string)($submission['storyteller_name'] ?? ''));
    }
    if ($title === '') {
        $title = 'Human Story ' . (int)($submission['id'] ?? 0);
    }
    return $title;
}

function publish_human_story(PDO $pdo, array $submission, int $adminId): void
{
    $title = human_submission_story_title($submission);
    $baseSlug = slugify($title);
    $slug = $baseSlug . '-' . (int)($submission['id'] ?? 0);
    $slugCheck = $pdo->prepare('SELECT id FROM stories WHERE slug=? LIMIT 1');
    $slugCheck->execute([$slug]);
    if ($slugCheck->fetch()) {
        $slug = $baseSlug . '-' . time() . '-' . (int)($submission['id'] ?? 0);
    }

    $year = $submission['event_date'] ? date('Y', strtotime((string)$submission['event_date'])) : null;
    $summary = mb_substr(preg_replace('/\s+/', ' ', strip_tags((string)($submission['story_content'] ?? ''))), 0, 220);
    $storytellerName = $submission['is_anonymous'] ? 'Anonymous' : ($submission['storyteller_name'] ?: 'Anonymous');
    $insert = $pdo->prepare('INSERT INTO stories(title,slug,story_type,event_date,year_label,location,image_path,summary,content,storyteller_name,storyteller_role,status,created_by,updated_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $insert->execute([
        $title,
        $slug,
        'HUMAN',
        $submission['event_date'] ?: null,
        $year,
        $submission['location'] ?: null,
        $submission['image_path'] ?: null,
        $summary,
        $submission['story_content'] ?? '',
        $storytellerName,
        $submission['storyteller_role'] ?: null,
        'PUBLISHED',
        $adminId,
        $adminId,
    ]);
}

function delete_published_human_story(PDO $pdo, array $submission): void
{
    $id = (int)$submission['id'];
    $title = human_submission_story_title($submission);
    $baseSlug = slugify($title);
    $slug = $baseSlug . '-' . $id;
    $slugPattern = $baseSlug . '-%-' . $id;
    $submissionSlug = '%-' . $id;
    $storytellerName = $submission['is_anonymous'] ? 'Anonymous' : ($submission['storyteller_name'] ?: 'Anonymous');
    $find = $pdo->prepare(
        "SELECT id FROM stories
         WHERE story_type='HUMAN' AND status='PUBLISHED'
           AND (slug=? OR slug LIKE ? OR slug LIKE ? OR (title=? AND content=? AND storyteller_name=?))
         ORDER BY CASE WHEN slug=? THEN 0 WHEN slug LIKE ? THEN 1 ELSE 2 END
         LIMIT 1 FOR UPDATE"
    );
    $find->execute([
        $slug,
        $slugPattern,
        $submissionSlug,
        $title,
        $submission['story_content'] ?? '',
        $storytellerName,
        $slug,
        $slugPattern,
    ]);
    $story = $find->fetch();
    if ($story) {
        $pdo->prepare('DELETE FROM stories WHERE id=?')->execute([(int)$story['id']]);
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['save_submission'])) {
    verify_csrf();
    $submissionId = filter_var($_POST['save_submission'], FILTER_VALIDATE_INT);
    if ($submissionId === false || $submissionId < 1) {
        flash('error', 'Select a valid submission to edit.');
        redirect('/admin/human-submissions.php');
    }

    $title = trim((string)($_POST['title'] ?? ''));
    $content = trim((string)($_POST['story_content'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $eventDate = trim((string)($_POST['event_date'] ?? ''));
    if ($title === '' || $content === '') {
        flash('error', 'Story title and content are required.');
        redirect('/admin/human-submissions.php');
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Enter a valid email address or leave it blank.');
        redirect('/admin/human-submissions.php');
    }
    if ($eventDate !== '' && !DateTime::createFromFormat('Y-m-d', $eventDate)) {
        flash('error', 'Enter a valid event date.');
        redirect('/admin/human-submissions.php');
    }
    $storytellerName = trim((string)($_POST['storyteller_name'] ?? '')) ?: null;
    $storytellerRole = trim((string)($_POST['storyteller_role'] ?? '')) ?: null;
    $location = trim((string)($_POST['location'] ?? '')) ?: null;
    $isAnonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $save = $pdo->prepare(
            "UPDATE story_submissions SET title=?,storyteller_name=?,storyteller_role=?,email=?,location=?,event_date=?,story_content=?,is_anonymous=? WHERE id=?"
        );
        $save->execute([
            $title, $storytellerName, $storytellerRole, $email ?: null,
            $location, $eventDate ?: null, $content, $isAnonymous, (int)$submissionId,
        ]);
        $publishedStory = $pdo->prepare(
            "SELECT id FROM stories WHERE story_type='HUMAN' AND status='PUBLISHED'
             AND (slug LIKE ? OR (title=? AND content=?)) LIMIT 1 FOR UPDATE"
        );
        $publishedStory->execute(['%-'.(int)$submissionId, $title, $content]);
        $publishedStoryId = $publishedStory->fetchColumn();
        if ($publishedStoryId !== false) {
            $summary = mb_substr(preg_replace('/\s+/', ' ', strip_tags($content)), 0, 220);
            $pdo->prepare(
                'UPDATE stories SET title=?,event_date=?,year_label=?,location=?,summary=?,content=?,storyteller_name=?,storyteller_role=?,updated_by=? WHERE id=?'
            )->execute([
                $title,
                $eventDate ?: null,
                $eventDate !== '' ? date('Y', strtotime($eventDate)) : null,
                $location,
                $summary,
                $content,
                $isAnonymous ? 'Anonymous' : ($storytellerName ?: 'Anonymous'),
                $storytellerRole,
                user()['id'],
                (int)$publishedStoryId,
            ]);
        }
        $pdo->commit();
        log_admin_activity('edit_story_submission', (string)$submissionId);
        flash('success', 'Submission updated.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', 'Could not save the submission changes.');
    }
    redirect('/admin/human-submissions.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
    && (isset($_POST['approve']) || isset($_POST['publish']) || isset($_POST['reject']))) {
    verify_csrf();
    $action = isset($_POST['approve']) ? 'approve' : (isset($_POST['publish']) ? 'publish' : 'reject');
    $submissionId = filter_var($_POST[$action], FILTER_VALIDATE_INT);
    if ($submissionId === false || $submissionId < 1) {
        flash('error', 'Select a valid submission to review.');
        redirect('/admin/human-submissions.php');
    }

    $pdo = db();
    if ($action === 'approve' || $action === 'reject') {
        $fromStatuses = $action === 'approve' ? "status='SUBMITTED'" : "status IN ('SUBMITTED','APPROVED')";
        $newStatus = $action === 'approve' ? 'APPROVED' : 'REJECTED';
        $review = $pdo->prepare(
            "UPDATE story_submissions SET status=?,reviewed_by=?,reviewed_at=NOW() WHERE id=? AND ".$fromStatuses
        );
        $review->execute([$newStatus, user()['id'], (int)$submissionId]);
        if ($review->rowCount() === 1) {
            log_admin_activity($action.'_story_submission', (string)$submissionId);
            flash('success', $action === 'approve' ? 'Submission approved. Publish it when ready.' : 'Submission rejected.');
        } else {
            flash('error', 'This submission has already been reviewed or published.');
        }
        redirect('/admin/human-submissions.php');
    }

    try {
        $pdo->beginTransaction();
        $submissionQuery = $pdo->prepare("SELECT * FROM story_submissions WHERE id=? AND status='APPROVED' FOR UPDATE");
        $submissionQuery->execute([(int)$submissionId]);
        $submission = $submissionQuery->fetch();
        if (!$submission) {
            throw new RuntimeException('Only approved submissions can be published.');
        }
        $title = human_submission_story_title($submission);
        $slug = slugify($title) . '-' . (int)$submission['id'];
        $existing = $pdo->prepare(
            "SELECT id FROM stories
             WHERE story_type='HUMAN' AND status='PUBLISHED'
               AND (slug=? OR (title=? AND content=?))
             LIMIT 1"
        );
        $existing->execute([$slug, $title, $submission['story_content'] ?? '']);
        if (!$existing->fetch()) {
            publish_human_story($pdo, $submission, (int)user()['id']);
        }
        $pdo->commit();
        log_admin_activity('publish_story_submission', (string)$submissionId);
        flash('success', 'Human story published.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', 'Could not publish this submission. It may already have been published.');
    }
    redirect('/admin/human-submissions.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['delete_submission'])) {
    verify_csrf();
    $submissionId = filter_var($_POST['delete_submission'], FILTER_VALIDATE_INT);
    if ($submissionId === false || $submissionId < 1) {
        flash('error', 'Select a valid submission to delete.');
        redirect('/admin/human-submissions.php');
    }
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $find = $pdo->prepare('SELECT * FROM story_submissions WHERE id=? FOR UPDATE');
        $find->execute([(int)$submissionId]);
        $submission = $find->fetch();
        if (!$submission) {
            throw new RuntimeException('Submission not found.');
        }
        if ($submission['status'] === 'APPROVED') {
            delete_published_human_story($pdo, $submission);
        }
        $pdo->prepare('DELETE FROM story_submissions WHERE id=?')->execute([(int)$submissionId]);
        $pdo->commit();
        log_admin_activity('delete_story_submission', (string)$submissionId);
        flash('success', 'Submission deleted.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', 'Could not delete this submission.');
    }
    redirect('/admin/human-submissions.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['delete_selected'])) {
    verify_csrf();
    $selectedIds = [];
    $postedIds = $_POST['submission_ids'] ?? [];
    foreach (is_array($postedIds) ? $postedIds : [] as $selectedId) {
        if (!is_scalar($selectedId)) {
            continue;
        }
        $id = filter_var($selectedId, FILTER_VALIDATE_INT);
        if ($id !== false && $id > 0) {
            $selectedIds[] = (int)$id;
        }
    }
    $selectedIds = array_values(array_unique($selectedIds));
    if (!$selectedIds) {
        flash('error', 'Select at least one approved or rejected submission to delete.');
        redirect('/admin/human-submissions.php');
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
        $selected = $pdo->prepare(
            "SELECT * FROM story_submissions
             WHERE id IN ($placeholders) AND status IN ('APPROVED','REJECTED')
             FOR UPDATE"
        );
        $selected->execute($selectedIds);
        $submissions = $selected->fetchAll();
        $deletedCount = 0;
        foreach ($submissions as $submission) {
            if ($submission['status'] === 'APPROVED') {
                delete_published_human_story($pdo, $submission);
            }
            $delete = $pdo->prepare("DELETE FROM story_submissions WHERE id=? AND status IN ('APPROVED','REJECTED')");
            $delete->execute([(int)$submission['id']]);
            if ($delete->rowCount() > 0) {
                log_admin_activity('delete_story_submission', (string)$submission['id']);
                $deletedCount++;
            }
        }
        $pdo->commit();
        if ($deletedCount > 0) {
            flash('success', $deletedCount . ' submission' . ($deletedCount === 1 ? '' : 's') . ' deleted.');
        } else {
            flash('error', 'No approved or rejected submissions were available to delete.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', 'Could not delete the selected submissions.');
    }
    redirect('/admin/human-submissions.php');
}

$rows = db()->query('SELECT * FROM story_submissions ORDER BY FIELD(status,"SUBMITTED","APPROVED","REJECTED"),created_at DESC')->fetchAll();
$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
$editing = null;
if ($editId !== false && $editId !== null && $editId > 0) {
    $editQuery = db()->prepare('SELECT * FROM story_submissions WHERE id=?');
    $editQuery->execute([(int)$editId]);
    $editing = $editQuery->fetch() ?: null;
}
$publishedStories = [];
foreach ($rows as $row) {
    if ($row['status'] !== 'APPROVED') {
        continue;
    }
    $title = human_submission_story_title($row);
    $slug = slugify($title) . '-' . (int)$row['id'];
    $storyQuery = db()->prepare(
        "SELECT slug FROM stories WHERE story_type='HUMAN' AND status='PUBLISHED'
     AND (slug=? OR slug LIKE ? OR (title=? AND content=?)) LIMIT 1"
    );
    $storyQuery->execute([$slug, '%-'.(int)$row['id'], $title, $row['story_content'] ?? '']);
    $publishedSlug = $storyQuery->fetchColumn();
    if ($publishedSlug !== false) {
        $publishedStories[(int)$row['id']] = (string)$publishedSlug;
    }
}

$sections = [
    'SUBMITTED' => 'Pending review',
    'APPROVED' => 'Approved stories',
    'REJECTED' => 'Rejected stories',
];
require_once __DIR__ . '/_header.php';
?><style>
.submission-list{display:grid;gap:28px}
.submission-section h2{font:700 24px Georgia,serif;margin:0 0 12px}
.submission-section-list{display:grid;gap:12px}
.submission-card{background:#fff;border:1px solid #ddd8d0;border-radius:12px;overflow:hidden}
.submission-card summary{cursor:pointer;list-style:none;padding:20px 24px;display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.submission-card summary::-webkit-details-marker{display:none}
.submission-card summary:after{content:"＋";margin-left:auto;color:#8d2424;font-size:20px}
.submission-card details[open] summary:after{content:"−"}
.submission-title{font:700 21px Georgia,serif;flex:1 1 250px}
.submission-summary-meta{color:#667;font-size:13px}
.submission-status{display:inline-block;padding:6px 10px;border-radius:999px;background:#eee;font-size:12px;font-weight:700}
.submission-status.submitted{background:#f7e4c2;color:#764d00}
.submission-status.approved{background:#d9eedf;color:#17602c}
.submission-status.rejected{background:#f3d9d7;color:#8a211c}
.submission-select{display:flex;align-items:center;gap:7px;padding:0 24px 14px;color:#667;font-size:13px}
.submission-select input{margin:0}
.submission-content{border-top:1px solid #eee;padding:22px 24px}
.submission-meta{display:flex;flex-wrap:wrap;gap:8px 18px;color:#667;font-size:14px;margin-bottom:18px}
.submission-body{border-top:1px solid #eee;padding-top:18px;font:17px/1.8 Georgia,serif;white-space:pre-line}
.submission-photo{display:block;max-width:100%;max-height:420px;object-fit:contain;margin:18px 0;border:1px solid #ddd}
.submission-actions{display:flex;gap:10px;flex-wrap:wrap;border-top:1px solid #eee;margin-top:20px;padding-top:18px}
.submission-card-top{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px 20px;border-bottom:1px solid #eee}
.submission-card-top details{min-width:0;flex:1}
.submission-card-top details summary{padding:0}
.submission-card-top details[open] summary{padding-bottom:14px}
.submission-card-tools{display:flex;align-items:center;justify-content:flex-end;gap:8px;flex:0 0 auto}
.submission-card-tools .btn{padding:8px 11px;font-size:13px}
.submission-card-tools form{margin:0}
.submission-actions .btn.publish{background:#176b35}
.submission-state{font-size:13px;font-weight:700;color:#176b35}
.submission-edit{margin-bottom:24px}
.submission-edit h2{margin-top:0}
.submission-edit .formgrid textarea{min-height:220px}
.delete-bar{display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:#fff;border:1px solid #ddd8d0;border-radius:10px;padding:14px 18px}
.delete-bar p{margin:0;color:#667;font-size:14px}
@media(max-width:650px){.submission-card-top{align-items:flex-start;flex-direction:column}.submission-card-tools{width:100%;justify-content:flex-start}.submission-card summary{padding:16px}.submission-content{padding:16px}.submission-select{padding:0 16px 12px}}
</style>
<div class="content">
    <?php if ($editing): ?>
        <form method="post" class="form submission-edit">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="save_submission" value="<?= (int)$editing['id'] ?>">
            <h2>Edit submission</h2>
            <div class="formgrid">
                <label class="field full">Story title *<input name="title" value="<?= e($editing['title']) ?>" required></label>
                <label class="field">Contributor name<input name="storyteller_name" value="<?= e($editing['storyteller_name']) ?>"></label>
                <label class="field">Contributor role<input name="storyteller_role" value="<?= e($editing['storyteller_role']) ?>"></label>
                <label class="field">Email<input type="email" name="email" value="<?= e($editing['email']) ?>"></label>
                <label class="field">Location<input name="location" value="<?= e($editing['location']) ?>"></label>
                <label class="field">Event date<input type="date" name="event_date" value="<?= e($editing['event_date']) ?>"></label>
                <label class="field"><span>Privacy</span><span><input type="checkbox" name="is_anonymous" value="1" <?= $editing['is_anonymous'] ? 'checked' : '' ?>> Publish anonymously</span></label>
                <label class="field full">Story content *<textarea name="story_content" required><?= e($editing['story_content']) ?></textarea></label>
            </div>
            <div class="actions"><button type="submit">Save changes</button><a class="btn secondary" href="<?= e(BASE_URL) ?>/admin/human-submissions">Cancel</a></div>
        </form>
    <?php elseif (isset($_GET['edit'])): ?>
        <div class="flash error">Submission not found.</div>
    <?php endif; ?>
    <form method="post" class="submission-list">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="delete_selected" value="1">
        <div class="delete-bar">
            <button class="btn danger" type="submit" data-confirm="Delete the selected approved or rejected submissions? Approved stories will also be removed from the public archive.">Delete selected</button>
            <p>Choose approved or rejected stories below. Pending submissions cannot be deleted here.</p>
        </div>
        <?php foreach ($sections as $status => $sectionTitle):
            $sectionRows = array_values(array_filter($rows, static fn($row) => $row['status'] === $status));
        ?>
            <section class="submission-section">
                <h2><?= e($sectionTitle) ?> (<?= count($sectionRows) ?>)</h2>
                <?php if ($sectionRows): ?>
                    <div class="submission-section-list">
                        <?php foreach ($sectionRows as $row): ?>
                            <article class="submission-card">
                                <?php if ($status === 'APPROVED' || $status === 'REJECTED'): ?>
                                    <label class="submission-select">
                                        <input type="checkbox" name="submission_ids[]" value="<?= (int)$row['id'] ?>">
                                        Select for deletion
                                    </label>
                                <?php endif; ?>
                                <div class="submission-card-top">
                                    <details id="submission-<?= (int)$row['id'] ?>">
                                        <summary>
                                            <span class="submission-status <?= strtolower(e($row['status'])) ?>"><?= e($row['status']) ?></span>
                                            <span class="submission-title"><?= e($row['title']) ?></span>
                                            <span class="submission-summary-meta">
                                                <?= e($row['is_anonymous'] ? 'Anonymous' : ($row['storyteller_name'] ?: 'No name')) ?>
                                                · Submitted <?= e($row['created_at']) ?>
                                            </span>
                                        </summary>
                                    <div class="submission-content">
                                        <div class="submission-meta">
                                            <span><strong>Contributor:</strong> <?= e($row['is_anonymous'] ? 'Anonymous' : ($row['storyteller_name'] ?: 'No name')) ?></span>
                                            <?php if ($row['storyteller_role']): ?><span><strong>Role:</strong> <?= e($row['storyteller_role']) ?></span><?php endif; ?>
                                            <?php if ($row['email']): ?><span><strong>Email:</strong> <?= e($row['email']) ?></span><?php endif; ?>
                                            <?php if ($row['location']): ?><span><strong>Location:</strong> <?= e($row['location']) ?></span><?php endif; ?>
                                            <?php if ($row['event_date']): ?><span><strong>Event date:</strong> <?= e($row['event_date']) ?></span><?php endif; ?>
                                            <span><strong>Submitted:</strong> <?= e($row['created_at']) ?></span>
                                        </div>
                                        <?php if ($row['image_path']): ?>
                                            <img class="submission-photo" src="<?= BASE_URL ?>/<?= e($row['image_path']) ?>" alt="Photo submitted with <?= e($row['title']) ?>">
                                        <?php endif; ?>
                                        <div class="submission-body"><?= e($row['story_content']) ?></div>
                                    </div>
                                    </details>
                                    <div class="submission-card-tools">
                                        <button type="button" class="btn secondary" data-view-submission="<?= (int)$row['id'] ?>">View</button>
                                        <a class="btn secondary" href="<?= e(BASE_URL) ?>/admin/human-submissions?edit=<?= (int)$row['id'] ?>">Edit</a>
                                        <?php if (isset($publishedStories[(int)$row['id']])): ?>
                                            <a class="btn secondary" href="<?= e(BASE_URL.'/story/'.rawurlencode($publishedStories[(int)$row['id']])) ?>" target="_blank" rel="noopener noreferrer">View public</a>
                                        <?php endif; ?>
                                        <button class="btn danger" type="submit" name="delete_submission" value="<?= (int)$row['id'] ?>" data-confirm="Delete this submission<?=isset($publishedStories[(int)$row['id']])?' and its published story':''?>?">Delete</button>
                                    </div>
                                </div>
                                <?php if ($status === 'SUBMITTED' || ($status === 'APPROVED' && !isset($publishedStories[(int)$row['id']]))) : ?>
                                    <div class="submission-actions">
                                        <?php if ($status === 'SUBMITTED'): ?>
                                            <button class="btn" type="submit" name="approve" value="<?= (int)$row['id'] ?>" data-confirm="Approve this submission? It will not be published yet.">Approve</button>
                                        <?php else: ?>
                                            <button class="btn publish" type="submit" name="publish" value="<?= (int)$row['id'] ?>" data-confirm="Publish this approved human story?">Publish</button>
                                        <?php endif; ?>
                                        <button class="btn danger" type="submit" name="reject" value="<?= (int)$row['id'] ?>" data-confirm="Reject this submission?">Reject</button>
                                    </div>
                                <?php elseif (isset($publishedStories[(int)$row['id']])): ?>
                                    <div class="submission-actions"><span class="submission-state">Published in public archive</span></div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card">No <?= strtolower(e($sectionTitle)) ?>.</div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </form>
</div>
<script>
document.querySelectorAll('[data-view-submission]').forEach(function (button) {
    button.addEventListener('click', function () {
        var details = document.getElementById('submission-' + button.dataset.viewSubmission);
        if (!details) return;
        details.open = !details.open;
        if (details.open) details.scrollIntoView({behavior: 'smooth', block: 'nearest'});
    });
});
</script>
