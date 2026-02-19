<?php
$page_title = 'Inventory Management';
require_once '../includes/config.php';
require_once '../includes/check_admin_session.php';
require_once 'header.php';

// Handle Search and Filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$category_filter = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Temp migration logic - consolidate all categories to Savory/Sweet Pastries
mysqli_query($conn, "UPDATE pastries SET category = 'Savory Pastries' WHERE category = 'Savory'");
mysqli_query($conn, "UPDATE pastries SET category = 'Sweet Pastries' WHERE category NOT IN ('Savory Pastries', 'Sweet Pastries')");

$query = "SELECT * FROM pastries WHERE 1=1";

if ($search) {
    $query .= " AND (name LIKE '%$search%' OR description LIKE '%$search%')";
}

if ($category_filter) {
    $query .= " AND category = '$category_filter'";
}

switch ($sort) {
    case 'price_high':
        $query .= " ORDER BY price DESC";
        break;
    case 'price_low':
        $query .= " ORDER BY price ASC";
        break;
    case 'stock_low':
        $query .= " ORDER BY stock_quantity ASC";
        break;
    default: // newest
        $query .= " ORDER BY created_at DESC";
        break;
}

$result = mysqli_query($conn, $query);

// Get categories for filter (Hardcoded for clarity)
$allowed_categories = ['Savory Pastries', 'Sweet Pastries'];
?>

<div class="container" style="padding-top: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>Inventory Management</h1>
        <button onclick="openModal('addModal')" class="btn"
            style="border-radius: 12px; padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 0.5rem; background: var(--honey); border: none;">
            <span style="font-size: 1.2rem;">+</span> Add New Pastry
        </button>
    </div>

    <!-- Search and Filter Section -->
    <form method="GET" class="admin-controls">
        <div class="admin-search-group">
            <div class="admin-search-input-wrapper">
                <input type="text" name="search" placeholder="Search pastries..." class="admin-search-input"
                    value="<?php echo htmlspecialchars($search); ?>">
                <?php if ($search): ?>
                    <a href="inventory.php"
                        style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); text-decoration: none; color: #999; font-size: 1.2rem;">&times;</a>
                <?php endif; ?>
            </div>
            <div class="admin-search-hint">Press Enter to search</div>
        </div>

        <select name="category" onchange="this.form.submit()" class="admin-filter-select">
            <option value="">All Categories</option>
            <?php foreach ($allowed_categories as $cat): ?>
                <option value="<?php echo $cat; ?>" <?php echo ($category_filter == $cat) ? 'selected' : ''; ?>>
                    <?php echo $cat; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="sort" onchange="this.form.submit()" class="admin-filter-select">
            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
            <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
            <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
            <option value="stock_low" <?php echo $sort == 'stock_low' ? 'selected' : ''; ?>>Low Stock First</option>
        </select>

        <?php if ($search || $category_filter || $sort != 'newest'): ?>
            <a href="inventory.php" class="admin-btn-clear">
                <span>&times;</span> Clear Filters
            </a>
        <?php endif; ?>
    </form>

    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($pastry = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>
                            <img src="../<?php echo htmlspecialchars($pastry['image_url']); ?>"
                                alt="<?php echo htmlspecialchars($pastry['name']); ?>"
                                style="width: 60px; height: 60px; object-fit: cover; border-radius: 12px; border: 2px solid var(--butter);">
                        </td>
                        <td style="font-weight: 600; color: var(--deep-brown);">
                            <?php echo htmlspecialchars($pastry['name']); ?>
                        </td>
                        <td>
                            <span
                                style="background: var(--butter); color: var(--deep-brown); padding: 0.3rem 0.8rem; border-radius: 8px; font-size: 0.85rem;">
                                <?php echo htmlspecialchars($pastry['category']); ?>
                            </span>
                        </td>
                        <td style="font-weight: 700;">₱<?php echo number_format($pastry['price'], 2); ?></td>
                        <td>
                            <span
                                style="font-weight: 600; <?php echo $pastry['stock_quantity'] < 10 ? 'color: var(--raspberry);' : ''; ?>">
                                <?php echo $pastry['stock_quantity']; ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                <button class="btn btn-sm btn-secondary"
                                    onclick="editPastry(<?php echo htmlspecialchars(json_encode($pastry)); ?>)">Edit</button>
                                <button class="btn btn-sm btn-danger"
                                    onclick="deletePastry(<?php echo $pastry['id']; ?>)">Delete</button>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($result) == 0): ?>
                    <tr>
                        <td colspan="6" style="padding: 3rem; text-align: center; color: #999; font-style: italic;">
                            No pastries found matching your criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; backdrop-filter: blur(4px);">
    <div
        style="background: var(--card-bg); color: var(--deep-brown); width: 90%; max-width: 500px; margin: 2rem auto; padding: 2.5rem; border-radius: 20px; position: relative; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
        <h2 style="font-family: 'Playfair Display', serif; margin-bottom: 1.5rem;">Add New Pastry</h2>
        <span onclick="closeModal('addModal')"
            style="position: absolute; top: 1.25rem; right: 1.5rem; cursor: pointer; font-size: 1.5rem; color: var(--warm-brown);">&times;</span>

        <form id="addForm" onsubmit="event.preventDefault(); submitForm('add');">
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Name</label>
                <input type="text" name="name" required
                    style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1.5px solid var(--butter); background: var(--cream); color: var(--deep-brown);">
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Description</label>
                <textarea name="description" required
                    style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1.5px solid var(--butter); background: var(--cream); color: var(--deep-brown); min-height: 100px;"></textarea>
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Price (₱)</label>
                <input type="number" step="0.01" name="price" required
                    style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1.5px solid var(--butter); background: var(--cream); color: var(--deep-brown);">
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Category</label>
                <select name="category" required
                    style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1.5px solid var(--butter); background: var(--cream); color: var(--deep-brown);">
                    <option value="Savory Pastries">Savory Pastries</option>
                    <option value="Sweet Pastries">Sweet Pastries</option>
                </select>
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Stock Quantity</label>
                <input type="number" name="stock_quantity" required
                    style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1.5px solid var(--butter); background: var(--cream); color: var(--deep-brown);">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Image</label>
                <input type="file" name="image" accept="image/*" required
                    style="width: 100%; color: var(--deep-brown);">
            </div>
            <button type="submit" class="btn" style="width: 100%;">Add Pastry</button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; backdrop-filter: blur(4px);">
    <div
        style="background: var(--card-bg); color: var(--deep-brown); width: 90%; max-width: 500px; margin: 2rem auto; padding: 2.5rem; border-radius: 20px; position: relative; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
        <h2 style="font-family: 'Playfair Display', serif; margin-bottom: 1.5rem;">Edit Pastry</h2>
        <span onclick="closeModal('editModal')"
            style="position: absolute; top: 1.25rem; right: 1.5rem; cursor: pointer; font-size: 1.5rem; color: var(--warm-brown);">&times;</span>

        <form id="editForm" onsubmit="event.preventDefault(); submitForm('update');">
            <input type="hidden" name="id" id="edit_id">
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Name</label>
                <input type="text" name="name" id="edit_name" required
                    style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1.5px solid var(--butter); background: var(--cream); color: var(--deep-brown);">
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Description</label>
                <textarea name="description" id="edit_description" required
                    style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1.5px solid var(--butter); background: var(--cream); color: var(--deep-brown); min-height: 100px;"></textarea>
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Price (₱)</label>
                <input type="number" step="0.01" name="price" id="edit_price" required
                    style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1.5px solid var(--butter); background: var(--cream); color: var(--deep-brown);">
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Category</label>
                <select name="category" id="edit_category" required
                    style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1.5px solid var(--butter); background: var(--cream); color: var(--deep-brown);">
                    <option value="Savory Pastries">Savory Pastries</option>
                    <option value="Sweet Pastries">Sweet Pastries</option>
                </select>
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Stock Quantity</label>
                <input type="number" name="stock_quantity" id="edit_stock" required
                    style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1.5px solid var(--butter); background: var(--cream); color: var(--deep-brown);">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Image (Leave blank to keep
                    current)</label>
                <input type="file" name="image" accept="image/*" style="width: 100%; color: var(--deep-brown);">
            </div>
            <button type="submit" class="btn" style="width: 100%;">Update Pastry</button>
        </form>
    </div>
</div>

<script>
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        if (modalId === 'addModal') document.getElementById('addForm').reset();
        if (modalId === 'editModal') document.getElementById('editForm').reset();
    }

    function editPastry(pastry) {
        document.getElementById('edit_id').value = pastry.id;
        document.getElementById('edit_name').value = pastry.name;
        document.getElementById('edit_description').value = pastry.description;
        document.getElementById('edit_price').value = pastry.price;
        document.getElementById('edit_category').value = pastry.category;
        document.getElementById('edit_stock').value = pastry.stock_quantity;
        openModal('editModal');
    }

    function submitForm(action) {
        const formId = action === 'add' ? 'addForm' : 'editForm';
        const form = document.getElementById(formId);
        const formData = new FormData(form);
        formData.append('action', action);

        fetch('inventory_actions.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
    }

    function deletePastry(id) {
        if (confirm('Are you sure you want to delete this pastry?')) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            fetch('inventory_actions.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
        }
    }

    // Close modal when clicking outside
    window.onclick = function (event) {
        if (event.target.className === 'modal') {
            event.target.style.display = 'none';
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>
</body>

</html>