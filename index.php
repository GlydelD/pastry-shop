<?php
$page_title = 'Home';
require_once 'includes/config.php';

// Smart Redirects: Redirect logged-in users to their dashboards
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/admin_dashboard.php');
    exit;
}
if (isset($_SESSION['customer_id'])) {
    header('Location: customer_dashboard.php');
    exit;
}

require_once 'includes/header.php';

// Pagination setup
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Get total number of pastries
$total_query = "SELECT COUNT(*) as total FROM pastries";
$total_result = mysqli_query($conn, $total_query);
$total_pastries = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_pastries / $items_per_page);

// Fetch pastries for current page
$query = "SELECT * FROM pastries ORDER BY created_at DESC LIMIT $offset, $items_per_page";
$result = mysqli_query($conn, $query);
?>

<section class="hero">
    <div class="container">
        <h1>Freshly Baked Daily</h1>
        <p>Artisan pastries crafted with passion, tradition, and the finest ingredients</p>
        <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
            <?php if (isset($_SESSION['customer_id'])): ?>
                <a href="customer_dashboard.php" class="btn">View Dashboard</a>
            <?php else: ?>
                <a href="login.php" class="btn">Login</a>
                <a href="signup.php" class="btn btn-secondary">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</section>
 
 <!-- Featured Pastries Section -->
 <?php
 // Fetch featured pastries
 $featured_query = "SELECT * FROM pastries WHERE is_featured = 1 LIMIT 3";
 $featured_result = mysqli_query($conn, $featured_query);
 ?>
 
 <?php if (mysqli_num_rows($featured_result) > 0): ?>
 <section style="padding: 4rem 0; background: #fffcf5;">
     <div class="container">
         <h2 style="text-align: center; margin-bottom: 3rem; font-size: 2.5rem; color: var(--deep-brown);">Fresh From the Oven</h2>
         <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
             <?php while ($featured = mysqli_fetch_assoc($featured_result)): ?>
                 <div class="pastry-card" style="display: flex; flex-direction: column; height: 100%; border: 2px solid var(--honey);">
                     <div style="position: relative; height: 250px; overflow: hidden;">
                         <img src="<?php echo htmlspecialchars($featured['image_url']); ?>" 
                              alt="<?php echo htmlspecialchars($featured['name']); ?>" 
                              style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;">
                         <div style="position: absolute; top: 1rem; right: 1rem; background: var(--raspberry); color: white; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; font-size: 0.9rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                             Featured
                         </div>
                     </div>
                     <div class="pastry-info" style="flex: 1; display: flex; flex-direction: column;">
                         <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($featured['name']); ?></h3>
                         <p style="color: #666; margin-bottom: 1.5rem; flex: 1;"><?php echo htmlspecialchars($featured['description']); ?></p>
                         
                         <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto;">
                             <div style="display: flex; flex-direction: column;">
                                 <span class="pastry-price" style="font-size: 1.4rem;">₱<?php echo number_format($featured['price'], 2); ?></span>
                                 <div style="margin-top: 0.2rem;">
                                     <?php if ($featured['stock_quantity'] <= 0): ?>
                                         <span class="badge" style="background: #ccc; color: #555; font-size: 0.8rem; padding: 0.2rem 0.5rem;">Out of Stock</span>
                                     <?php elseif ($featured['stock_quantity'] < 10): ?>
                                         <span class="badge" style="background: #fff3cd; color: #856404; border: 1px solid #ffeeba; font-size: 0.8rem; padding: 0.2rem 0.5rem;">Only <?php echo $featured['stock_quantity']; ?> left!</span>
                                     <?php else: ?>
                                         <span class="badge" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; font-size: 0.8rem; padding: 0.2rem 0.5rem;">In Stock: <?php echo $featured['stock_quantity']; ?></span>
                                     <?php endif; ?>
                                 </div>
                             </div>
                             <div style="display: flex; gap: 0.5rem;">
                                 <?php if (isset($_SESSION['customer_id'])): ?>
                                     <button class="btn" onclick="addToCart(<?php echo $featured['id']; ?>)"
                                         <?php echo $featured['stock_quantity'] <= 0 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>>
                                         Add to Cart
                                     </button>
                                 <?php else: ?>
                                     <button class="btn" onclick="alert('Please login to add to cart'); window.location.href='login.php'"
                                         <?php echo $featured['stock_quantity'] <= 0 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>>
                                         Add to Cart
                                     </button>
                                 <?php endif; ?>
                             </div>
                         </div>
                     </div>
                 </div>
             <?php endwhile; ?>
         </div>
     </div>
 </section>
 <?php endif; ?>

<section>
    <div class="container">
        <h2>All Pastries</h2>
        <div class="pastry-grid">
            <?php while ($pastry = mysqli_fetch_assoc($result)): ?>
                <div class="pastry-card fade-in-up">
                    <img src="<?php echo htmlspecialchars($pastry['image_url']); ?>"
                        alt="<?php echo htmlspecialchars($pastry['name']); ?>" class="pastry-image" loading="lazy">
                    <div class="pastry-info">
                        <h3><?php echo htmlspecialchars($pastry['name']); ?></h3>
                        <p><?php echo htmlspecialchars($pastry['description']); ?></p>
                        
                        <div style="margin-bottom: 0.5rem;">
                            <?php if ($pastry['stock_quantity'] <= 0): ?>
                                <span class="badge" style="background: #ccc; color: #555;">Out of Stock</span>
                            <?php elseif ($pastry['stock_quantity'] < 10): ?>
                                <span class="badge" style="background: #fff3cd; color: #856404; border: 1px solid #ffeeba;">Only <?php echo $pastry['stock_quantity']; ?> left!</span>
                            <?php else: ?>
                                <span class="badge" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb;">In Stock: <?php echo $pastry['stock_quantity']; ?></span>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="pastry-price">₱<?php echo number_format($pastry['price'], 2); ?></span>
                            <div style="display: flex; gap: 0.5rem;">
                                <?php if (isset($_SESSION['customer_id'])): ?>
                                    <button class="btn" style="padding: 0.6rem 1.5rem; font-size: 0.9rem;"
                                        onclick="addToCart(<?php echo $pastry['id']; ?>)"
                                        <?php echo $pastry['stock_quantity'] <= 0 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>>
                                        Add to Cart
                                    </button>
                                    <form method="POST" action="wishlist.php" style="display: inline;">
                                        <input type="hidden" name="pastry_id" value="<?php echo $pastry['id']; ?>">
                                        <button type="submit" name="add_to_wishlist" class="btn btn-secondary" 
                                                style="padding: 0.6rem 1rem; font-size: 0.9rem;">❤️</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn" style="padding: 0.6rem 1.5rem; font-size: 0.9rem;"
                                        onclick="alert('Please login to add to cart'); window.location.href='login.php'"
                                        <?php echo $pastry['stock_quantity'] <= 0 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>>
                                        Add to Cart
                                    </button>
                                    <button class="btn btn-secondary" style="padding: 0.6rem 1rem; font-size: 0.9rem;"
                                        onclick="alert('Please login to save items'); window.location.href='login.php'">❤️</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination" style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="pagination-btn">« Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="pagination-btn active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>" class="pagination-btn"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="pagination-btn">Next »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section style="background: linear-gradient(135deg, var(--butter) 0%, var(--crust) 100%); margin-top: 4rem;">
    <div class="container" style="text-align: center;">
        <h2>Why Choose Our Pastries?</h2>
        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 2rem;">
            <div style="padding: 2rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        style="color: var(--warm-brown);">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                    </svg>
                </div>
                <h3 style="font-family: 'Playfair Display', serif; font-size: 1.5rem; margin-bottom: 0.5rem;">Fresh
                    Ingredients</h3>
                <p>Only the finest, locally-sourced ingredients make it into our pastries</p>
            </div>
            <div style="padding: 2rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        style="color: var(--warm-brown);">
                        <path
                            d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z" />
                        <line x1="6" y1="17" x2="18" y2="17" />
                    </svg>
                </div>
                <h3 style="font-family: 'Playfair Display', serif; font-size: 1.5rem; margin-bottom: 0.5rem;">Master
                    Bakers</h3>
                <p>Crafted by experienced artisans with decades of expertise</p>
            </div>
            <div style="padding: 2rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor"
                        style="color: var(--raspberry);">
                        <path
                            d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                    </svg>
                </div>
                <h3 style="font-family: 'Playfair Display', serif; font-size: 1.5rem; margin-bottom: 0.5rem;">Made with
                    Love</h3>
                <p>Every pastry is baked with care and attention to detail</p>
            </div>
        </div>
    </div>
</section>

<script>
function addToCart(pastryId) {
    fetch('cart_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=add&pastry_id=' + pastryId
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Added to cart!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
}
</script>

<?php require_once 'includes/footer.php'; ?>