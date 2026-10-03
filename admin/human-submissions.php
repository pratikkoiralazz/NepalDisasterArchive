<?php
require_once __DIR__ . '/_header.php';
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
    $storytellerName = $submission['is_anonymous'] ? 'Anonymous' : ($submission['storyteller_name'] ?: 'Anonymous');
    $find = $pdo->prepare(
        "SELECT id FROM stories
         WHERE story_type='HUMAN' AND status='PUBLISHED'
           AND (slug=? OR slug LIKE ? OR (title=? AND content=? AND storyteller_name=?))
         ORDER BY CASE WHEN slug=? THEN 0 WHEN slug LIKE ? THEN 1 ELSE 2 END
         LIMIT 1 FOR UPDATE"
    );
    $find->execute([
        $slug,
        $slugPattern,
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

if (isset($_GET['reject'])) {
    db()->prepare("UPDATE story_submissions SET status='REJECTED',reviewed_by=?,reviewed_at=NOW() WHERE id=? AND status='SUBMITTED'")
        ->execute([user()['id'], (int)$_GET['reject']]);
    log_admin_activity('reject_story_submission', (string)$_GET['reject']);
    flash('success', 'Submission rejected.');
    redirect('/admin/human-submissions.php');
}

if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    try {
        $pdo = db();
        $pdo->beginTransaction();
        $s = $pdo->prepare("SELECT * FROM story_submissions WHERE id=? AND status IN ('SUBMITTED','APPROVED') FOR UPDATE");
        $s->execute([$id]);
        $submission = $s->fetch();
        if (!$submission) {
            throw new RuntimeException('Submission is no longer pending.');
        }
        $existing = $pdo->prepare("SELECT id FROM stories WHERE slug=? OR (title=? AND story_type='HUMAN' AND status='PUBLISHED' AND content=?) LIMIT 1");
        $existing->execute([
            slugify(trim((string)($submission['title'] ?? ''))) . '-' . $id,
            trim((string)($submission['title'] ?? '')),
            $submission['story_content'] ?? '',
        ]);
        if (!$existing->fetch()) {
            publish_human_story($pdo, $submission, user()['id']);
        }
        $pdo->prepare("UPDATE story_submissions SET status='APPROVED',reviewed_by=?,reviewed_at=NOW() WHERE id=?")
            ->execute([user()['id'], $id]);
        $pdo->commit();
        log_admin_activity('approve_story_submission', (string)$id);
        flash('success', 'Human story published.');
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', 'Could not approve this submission.');
    }
    redirect('/admin/human-submissions.php');
}

$rows = db()->query('SELECT * FROM story_submissions ORDER BY FIELD(status,"SUBMITTED","APPROVED","REJECTED"),created_at DESC')->fetchAll();
foreach ($rows as $row) {
    if ($row['status'] !== 'APPROVED') {
        continue;
    }
    $storyExists = db()->prepare("SELECT id FROM stories WHERE story_type='HUMAN' AND status='PUBLISHED' AND ((slug=? AND slug IS NOT NULL) OR (title=? AND content=?)) LIMIT 1");
    $slug = slugify(trim((string)($row['title'] ?? ''))) . '-' . (int)$row['id'];
    $storyExists->execute([$slug, trim((string)($row['title'] ?? '')), $row['story_content'] ?? '']);
    if ($storyExists->fetch()) {
        continue;
    }
    try {
        $pdo = db();
        $pdo->beginTransaction();
        publish_human_story($pdo, $row, (int)user()['id']);
        $pdo->prepare("UPDATE story_submissions SET status='APPROVED', reviewed_by=?, reviewed_at=NOW() WHERE id=?")
            ->execute([user()['id'], (int)$row['id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}

$sections = [
    'SUBMITTED' => 'Pending review',
    'APPROVED' => 'Approved stories',
    'REJECTED' => 'Rejected stories',
];
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
.delete-bar{display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:#fff;border:1px solid #ddd8d0;border-radius:10px;padding:14px 18px}
.delete-bar p{margin:0;color:#667;font-size:14px}
@media(max-width:600px){.submission-card summary{padding:16px}.submission-content{padding:16px}.submission-select{padding:0 16px 12px}}
</style>
<div class="content">
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
                                <details>
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
                                        <?php if ($row['status'] === 'SUBMITTED'): ?>
                                            <div class="submission-actions">
                                                <a class="btn" data-confirm="Publish this human story?" href="?approve=<?= (int)$row['id'] ?>">Approve &amp; publish</a>
                                                <a class="btn danger" data-confirm="Reject this submission?" href="?reject=<?= (int)$row['id'] ?>">Reject</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </details>
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
<?php require_once __DIR__ . '/_footer.php';
