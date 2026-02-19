<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/check_admin_session.php';

$page = isset($_GET['user_page']) ? (int) $_GET['user_page'] : 1;
$users_per_page = 10;
$offset = ($page - 1) * $users_per_page;
$search = isset($_GET['user_search']) ? mysqli_real_escape_string($conn, $_GET['user_search']) : '';
$user_sort = isset($_GET['user_sort']) ? $_GET['user_sort'] : 'newest';

// Build users query
$users_query = "SELECT id, username, full_name, email, profile_picture, created_at FROM customers";
$count_query = "SELECT COUNT(*) as total FROM customers";

$where_clause = "";
if ($search) {
    $where_clause = " WHERE username LIKE '%$search%' OR full_name LIKE '%$search%' OR email LIKE '%$search%'";
}

$sort_sql = "ORDER BY created_at DESC";
switch ($user_sort) {
    case 'oldest':
        $sort_sql = "ORDER BY created_at ASC";
        break;
    case 'az':
        $sort_sql = "ORDER BY username ASC";
        break;
    case 'za':
        $sort_sql = "ORDER BY username DESC";
        break;
    default:
        $sort_sql = "ORDER BY created_at DESC";
        break;
}

$users_query .= "$where_clause $sort_sql LIMIT $users_per_page OFFSET $offset";
$count_query .= $where_clause;

$users_result = mysqli_query($conn, $users_query);
$count_result = mysqli_query($conn, $count_query);
$total_users = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_users / $users_per_page);

ob_start();
if (mysqli_num_rows($users_result) > 0):
    while ($user = mysqli_fetch_assoc($users_result)): ?>
        <tr>
            <td style="font-weight: 600;">
                <div style="display: flex; align-items: center; gap: 0.8rem;">
                    <div
                        style="width: 32px; height: 32px; border-radius: 50%; overflow: hidden; background: #f0f0f0; border: 1.5px solid var(--butter); flex-shrink: 0;">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img src="../<?php echo htmlspecialchars($user['profile_picture']); ?>" alt=""
                                style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div
                                style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                                👤</div>
                        <?php endif; ?>
                    </div>
                    <span>
                        <?php echo htmlspecialchars($user['username']); ?>
                    </span>
                </div>
            </td>
            <td>
                <?php echo htmlspecialchars($user['full_name']); ?>
            </td>
            <td>
                <?php echo htmlspecialchars($user['email']); ?>
            </td>
            <td style="color: #666;">
                <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
            </td>
        </tr>
    <?php endwhile;
else: ?>
    <tr>
        <td colspan="4" style="text-align: center; padding: 2rem;">
            <?php if ($search): ?>
                No users found matching "
                <?php echo htmlspecialchars($search); ?>"
            <?php else: ?>
                No users registered yet
            <?php endif; ?>
        </td>
    </tr>
<?php endif;
$table_html = ob_get_clean();

// Simple pagination HTML (if needed in the future, currently not in dashboard table but query supports it)
$pagination_html = "";
if ($total_pages > 1) {
    // Current dashboard doesn't have visible pagination for users, 
    // but if we were to add it, we would return it here.
}

echo json_encode([
    'table_html' => $table_html,
    'pagination_html' => $pagination_html,
    'total_users' => $total_users
]);
?>