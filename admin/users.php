<?php
require_once __DIR__ . '/_header.php';
require_admin();
$page_title = 'Users';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $targetId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $currentUserId = (int)user()['id'];

    if ($action === 'delete') {
        if ($targetId === false || $targetId < 1) {
            flash('error', 'Select a valid user to delete.');
        } elseif ((int)$targetId === $currentUserId) {
            flash('error', 'You cannot delete your own account.');
        } else {
            try {
                $target = db()->prepare('SELECT id,role FROM admins WHERE id=?');
                $target->execute([(int)$targetId]);
                $targetUser = $target->fetch();
                if (!$targetUser) {
                    flash('error', 'That user no longer exists.');
                } elseif ($targetUser['role'] === 'ADMIN'
                    && (int)db()->query("SELECT COUNT(*) FROM admins WHERE role='ADMIN'")->fetchColumn() <= 1) {
                    flash('error', 'The last administrator account cannot be deleted.');
                } else {
                    $delete = db()->prepare('DELETE FROM admins WHERE id=?');
                    $delete->execute([(int)$targetId]);
                    if ($delete->rowCount() !== 1) {
                        flash('error', 'The user could not be deleted.');
                    } else {
                        log_admin_activity('delete_admin_user', (string)$targetId);
                        flash('success', 'User deleted.');
                    }
                }
            } catch (Throwable $e) {
                flash('error', 'Could not delete this user. The account may still be referenced by existing records.');
            }
        }
        redirect('/admin/users.php');
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim(mb_strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || !in_array($role, ['ADMIN', 'EDITOR'], true)
        || ($action === 'create' && mb_strlen($password) < 8)
        || ($action === 'update' && $password !== '' && mb_strlen($password) < 8)) {
        flash('error', 'Enter a valid name, email, and role. Passwords must be at least 8 characters.');
        redirect($action === 'update' && $targetId ? '/admin/users.php?edit=' . (int)$targetId : '/admin/users.php');
    }

    try {
        if ($action === 'create') {
            db()->prepare('INSERT INTO admins(name,email,password_hash,role) VALUES(?,?,?,?)')
                ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            log_admin_activity('create_admin_user', $email);
            flash('success', 'User created.');
        } elseif ($action === 'update' && $targetId !== false && $targetId > 0) {
            $target = db()->prepare('SELECT id,role FROM admins WHERE id=?');
            $target->execute([(int)$targetId]);
            $targetUser = $target->fetch();
            if (!$targetUser) {
                flash('error', 'That user no longer exists.');
            } elseif ((int)$targetId === $currentUserId && $role !== $targetUser['role']) {
                flash('error', 'You cannot change your own account role.');
            } elseif ($targetUser['role'] === 'ADMIN' && $role !== 'ADMIN'
                && (int)db()->query("SELECT COUNT(*) FROM admins WHERE role='ADMIN'")->fetchColumn() <= 1) {
                flash('error', 'The last administrator account cannot be demoted.');
            } else {
                if ($password !== '') {
                    db()->prepare('UPDATE admins SET name=?,email=?,password_hash=?,role=? WHERE id=?')
                        ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, (int)$targetId]);
                } else {
                    db()->prepare('UPDATE admins SET name=?,email=?,role=? WHERE id=?')
                        ->execute([$name, $email, $role, (int)$targetId]);
                }
                log_admin_activity('update_admin_user', (string)$targetId);
                flash('success', 'User updated.');
            }
        } else {
            flash('error', 'Invalid user action.');
        }
    } catch (Throwable $e) {
        flash('error', 'Could not save the user. Check that the email address is not already in use.');
    }
    redirect('/admin/users.php');
}

$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
$editUser = null;
if ($editId !== false && $editId !== null && $editId > 0) {
    $editQuery = db()->prepare('SELECT id,name,email,role FROM admins WHERE id=?');
    $editQuery->execute([(int)$editId]);
    $editUser = $editQuery->fetch() ?: null;
}
$rows = db()->query('SELECT id,name,email,role,created_at FROM admins ORDER BY created_at DESC')->fetchAll();
?>
<div class="content">
    <?php if ($editId !== false && $editId !== null && !$editUser): ?>
        <div class="flash error">That user could not be found.</div>
    <?php endif; ?>
    <form class="form" method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="<?= $editUser ? 'update' : 'create' ?>">
        <?php if ($editUser): ?><input type="hidden" name="id" value="<?= (int)$editUser['id'] ?>"><?php endif; ?>
        <h2><?= $editUser ? 'Edit user' : 'Create editor' ?></h2>
        <div class="formgrid">
            <div class="field"><label for="user-name">Name</label><input id="user-name" name="name" value="<?= e($editUser['name'] ?? '') ?>" required></div>
            <div class="field"><label for="user-email">Email</label><input id="user-email" type="email" name="email" value="<?= e($editUser['email'] ?? '') ?>" required></div>
            <div class="field"><label for="user-password">Password <?= $editUser ? '(leave blank to keep current)' : '' ?></label><input id="user-password" type="password" name="password" minlength="8" <?= $editUser ? '' : 'required' ?> autocomplete="new-password"></div>
            <div class="field"><label for="user-role">Role</label>
                <select id="user-role" name="role" <?= $editUser && (int)$editUser['id'] === (int)user()['id'] ? 'disabled' : '' ?>>
                    <option value="EDITOR" <?= ($editUser['role'] ?? 'EDITOR') === 'EDITOR' ? 'selected' : '' ?>>EDITOR</option>
                    <option value="ADMIN" <?= ($editUser['role'] ?? '') === 'ADMIN' ? 'selected' : '' ?>>ADMIN</option>
                </select>
                <?php if ($editUser && (int)$editUser['id'] === (int)user()['id']): ?>
                    <input type="hidden" name="role" value="<?= e($editUser['role']) ?>">
                    <span class="help">You cannot change your own role.</span>
                <?php endif; ?>
            </div>
        </div>
        <br><button type="submit"><?= $editUser ? 'Save changes' : 'Create user' ?></button>
        <?php if ($editUser): ?> <a class="btn secondary" href="<?= BASE_URL ?>/admin/users">Cancel</a><?php endif; ?>
    </form>
    <div class="tablewrap" style="margin-top:20px">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['email']) ?></td>
                        <td><?= e($row['role']) ?></td>
                        <td><?= e($row['created_at']) ?></td>
                        <td>
                            <a class="btn secondary" href="<?= BASE_URL ?>/admin/users?edit=<?= (int)$row['id'] ?>">Edit</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('Delete this user? This cannot be undone.')">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <button class="btn danger" type="submit" <?= (int)$row['id'] === (int)user()['id'] ? 'disabled title="You cannot delete your own account."' : '' ?>>Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?><tr><td colspan="5">No users found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/_footer.php';
