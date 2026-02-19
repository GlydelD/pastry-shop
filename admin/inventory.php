<?php
$page_title = 'Inventory Management';
require_once '../includes/config.php';
require_once '../includes/check_admin_session.php';
require_once 'header.php';

// Fetch all pastries
$query = "SELECT * FROM pastries ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>

<div class="container" style="padding-top: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>Inventory Management</h1>
        <button class="btn" onclick="openModal('addModal')">Add New Pastry</button>
    </div>

    <div style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: var(--cream); border-bottom: 2px solid var(--honey);">
                    <th style="padding: 1rem; text-align: left;">ID</th>
                    <th style="padding: 1rem; text-align: left;">Image</th>
                    <th style="padding: 1rem; text-align: left;">Name</th>
                    <th style="padding: 1rem; text-align: left;">Category</th>
                    <th style="padding: 1rem; text-align: left;">Price</th>
                    <th style="padding: 1rem; text-align: left;">Stock</th>
                    <th style="padding: 1rem; text-align: left;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($pastry = mysqli_fetch_assoc($result)): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 1rem;">#
                            <?php echo $pastry['id']; ?>
                        </td>
                        <td style="padding: 1rem;">
                            <img src="../<?php echo htmlspecialchars($pastry['image_url']); ?>"
                                alt="<?php echo htmlspecialchars($pastry['name']); ?>"
                                style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                        </td>
                        <td style="padding: 1rem; font-weight: bold;">
                            <?php echo htmlspecialchars($pastry['name']); ?>
                        </td>
                        <td style="padding: 1rem;">
                            <?php echo htmlspecialchars($pastry['category']); ?>
                        </td>
                        <td style="padding: 1rem;">₱
                            <?php echo number_format($pastry['price'], 2); ?>
                        </td>
                        <td style="padding: 1rem;">
                            <?php if ($pastry['stock_quantity'] <= 5): ?>
                                <span style="color: var(--raspberry); font-weight: bold;">
                                    <?php echo $pastry['stock_quantity']; ?>
                                </span>
                            <?php else: ?>
                                <?php echo $pastry['stock_quantity']; ?>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem;">
                            <button class="btn btn-sm btn-secondary"
                                onclick='editPastry(<?php echo json_encode($pastry); ?>)'>Edit</button>
                            <button class="btn btn-sm"
                                style="background-color: var(--raspberry); color: white; border: none;"
                                onclick="deletePastry(<?php echo $pastry['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div
        style="background: white; width: 90%; max-width: 500px; margin: 2rem auto; padding: 2rem; border-radius: 8px; position: relative;">
        <h2>Add New Pastry</h2>
        <span onclick="closeModal('addModal')"
            style="position: absolute; top: 1rem; right: 1rem; cursor: pointer; font-size: 1.5rem;">&times;</span>

        <form id="addForm" onsubmit="event.preventDefault(); submitForm('add');">
            <div style="margin-bottom: 1rem;">
                <label>Name</label>
                <input type="text" name="name" required style="width: 100%; padding: 0.5rem; margin-top: 0.25rem;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label>Description</label>
                <textarea name="description" required
                    style="width: 100%; padding: 0.5rem; margin-top: 0.25rem;"></textarea>
            </div>
            <div style="margin-bottom: 1rem;">
                <label>Price (₱)</label>
                <input type="number" step="0.01" name="price" required
                    style="width: 100%; padding: 0.5rem; margin-top: 0.25rem;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label>Category</label>
                <select name="category" required style="width: 100%; padding: 0.5rem; margin-top: 0.25rem;">
                    <option value="Croissants">Croissants</option>
                    <option value="Cakes">Cakes</option>
                    <option value="Breads">Breads</option>
                    <option value="Danish">Danish</option>
                    <option value="Savory">Savory</option>
                </select>
            </div>
            <div style="margin-bottom: 1rem;">
                <label>Stock Quantity</label>
                <input type="number" name="stock_quantity" required
                    style="width: 100%; padding: 0.5rem; margin-top: 0.25rem;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label>Image</label>
                <input type="file" name="image" accept="image/*" required
                    style="width: 100%; padding: 0.5rem; margin-top: 0.25rem;">
            </div>
            <button type="submit" class="btn" style="width: 100%;">Add Pastry</button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div
        style="background: white; width: 90%; max-width: 500px; margin: 2rem auto; padding: 2rem; border-radius: 8px; position: relative;">
        <h2>Edit Pastry</h2>
        <span onclick="closeModal('editModal')"
            style="position: absolute; top: 1rem; right: 1rem; cursor: pointer; font-size: 1.5rem;">&times;</span>

        <form id="editForm" onsubmit="event.preventDefault(); submitForm('update');">
            <input type="hidden" name="id" id="edit_id">
            <div style="margin-bottom: 1rem;">
                <label>Name</label>
                <input type="text" name="name" id="edit_name" required
                    style="width: 100%; padding: 0.5rem; margin-top: 0.25rem;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label>Description</label>
                <textarea name="description" id="edit_description" required
                    style="width: 100%; padding: 0.5rem; margin-top: 0.25rem;"></textarea>
            </div>
            <div style="margin-bottom: 1rem;">
                <label>Price (₱)</label>
                <input type="number" step="0.01" name="price" id="edit_price" required
                    style="width: 100%; padding: 0.5rem; margin-top: 0.25rem;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label>Category</label>
                <select name="category" id="edit_category" required
                    style="width: 100%; padding: 0.5rem; margin-top: 0.25rem;">
                    <option value="Croissants">Croissants</option>
                    <option value="Cakes">Cakes</option>
                    <option value="Breads">Breads</option>
                    <option value="Danish">Danish</option>
                    <option value="Savory">Savory</option>
                </select>
            </div>
            <div style="margin-bottom: 1rem;">
                <label>Stock Quantity</label>
                <input type="number" name="stock_quantity" id="edit_stock_quantity" required
                    style="width: 100%; padding: 0.5rem; margin-top: 0.25rem;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label>Image (Leave empty to keep current)</label>
                <input type="file" name="image" accept="image/*"
                    style="width: 100%; padding: 0.5rem; margin-top: 0.25rem;">
            </div>
            <button type="submit" class="btn" style="width: 100%;">Update Pastry</button>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById(id).style.display = 'block';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    function editPastry(pastry) {
        document.getElementById('edit_id').value = pastry.id;
        document.getElementById('edit_name').value = pastry.name;
        document.getElementById('edit_description').value = pastry.description;
        document.getElementById('edit_price').value = pastry.price;
        document.getElementById('edit_category').value = pastry.category;
        document.getElementById('edit_stock_quantity').value = pastry.stock_quantity;
        openModal('editModal');
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
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        }
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
            });
    }
</script>

<?php require_once '../includes/footer.php'; ?>