<?php
// sidebar.php - Left-aligned service menu

// Include database connection
include "db.php";

try {
    // Fetch all services for the menu
    $menuStmt = $conn->prepare("SELECT id, title FROM services ORDER BY id");
    $menuStmt->execute();
    $menuServices = $menuStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $menuServices = [];
}
?>

<!-- Left Sidebar Menu -->
<aside class="sidebar-menu">
    <h3>Our Services</h3>
    
    <?php if (empty($menuServices)): ?>
        <p style="color: #666; font-style: italic; padding: 10px;">
            No services available.
        </p>
    <?php else: ?>
        <ul class="menu-services-list">
            <?php 
            $currentServiceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            foreach ($menuServices as $menuService): 
                $isActive = ($menuService['id'] == $currentServiceId);
            ?>
                <li class="menu-service-item">
                    <a href="service_detail.php?id=<?php echo $menuService['id']; ?>" 
                       class="menu-service-link <?php echo $isActive ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($menuService['title']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    
    <!-- Back to all services link -->
    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee;">
        <a href="index.php" 
           style="display: inline-flex; align-items: center; color: #1f3fd7; text-decoration: none; font-weight: bold;">
            <span style="margin-right: 8px;">←</span> View All Services
        </a>
    </div>
</aside>