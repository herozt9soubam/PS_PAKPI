<?php
// dashboard_template.php - HTML template for dashboard
if (!isset($conn)) {
    die('Database connection not available');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — P.S. Pakpi Enterprises</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Main & Theme CSS -->
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/enterprise-theme.css?v=2026-admin-v2">
    <link rel="stylesheet" href="../assets/admin-glass.css?v=2026-fix-flex-1">
</head>
<body class="admin-dashboard">
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-header-inner">
            <div class="admin-brand-wrap">
                <button type="button" class="admin-mobile-toggle" id="adminSidebarToggle" aria-label="Toggle navigation menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <a href="dashboard.php" class="admin-brand">
                    <div class="admin-logo">
                        <span class="logo-mark">PSP</span>
                    </div>
                    <div class="admin-brand-text">
                        <h1>P.S. Pakpi Enterprises</h1>
                        <p class="admin-badge-sub">Enterprise Management Console</p>
                    </div>
                </a>
            </div>
            
            <div class="admin-user-info">
                <a href="../index.php" target="_blank" class="admin-external-link" title="Open Public Website">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>View Site</span>
                </a>
                <div class="admin-user-pill">
                    <div class="admin-avatar">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div class="admin-user-details">
                        <span class="user-welcome"><?php echo htmlspecialchars($_SESSION['admin_fullname'] ?? 'Administrator'); ?></span>
                        <span class="user-role">Super Admin</span>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn" title="Sign out">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </header>

    <div class="admin-main-container">
        <?php echo $message; ?>
        
        <div class="dashboard-grid">
            <!-- Sidebar Drawer Overlay (Mobile) -->
            <div class="sidebar-overlay" id="sidebarOverlay"></div>

            <!-- Sidebar Menu -->
            <aside class="sidebar-container" id="adminSidebar">
                <div class="sidebar-header-mobile">
                    <div class="sidebar-mobile-title">Admin Navigation</div>
                    <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close sidebar">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <ul class="sidebar-menu">
                    <li class="sidebar-section-label">MAIN NAVIGATION</li>
                    <li><a href="dashboard.php" class="<?php echo $action === 'dashboard' ? 'active' : ''; ?>"><i class="fa-solid fa-chart-pie icon"></i> <span>Dashboard</span></a></li>
                    <li><a href="dashboard.php?action=clients" class="<?php echo $action === 'clients' ? 'active' : ''; ?>"><i class="fa-solid fa-users icon"></i> <span>Client Management</span></a></li>
                    <li><a href="dashboard.php?action=services" class="<?php echo $action === 'services' ? 'active' : ''; ?>"><i class="fa-solid fa-layer-group icon"></i> <span>Service Management</span></a></li>
                    <li><a href="dashboard.php?action=inquiries" class="<?php echo $action === 'inquiries' ? 'active' : ''; ?>"><i class="fa-solid fa-envelope-open-text icon"></i> <span>Service Inquiries</span> <?php if (($stats['new_inquiries'] ?? 0) > 0): ?><span class="sidebar-pill"><?php echo $stats['new_inquiries']; ?></span><?php endif; ?></a></li>
                    <li><a href="dashboard.php?action=quotes" class="<?php echo $action === 'quotes' ? 'active' : ''; ?>"><i class="fa-solid fa-receipt icon"></i> <span>Quote Configurations</span></a></li>

                    <li class="sidebar-section-label">CONFIGURATION</li>
                    <li><a href="dashboard.php?action=custom_fields" class="<?php echo $action === 'custom_fields' ? 'active' : ''; ?>"><i class="fa-solid fa-sliders icon"></i> <span>Custom Fields</span></a></li>
                    <li><a href="dashboard.php?action=visibility" class="<?php echo $action === 'visibility' ? 'active' : ''; ?>"><i class="fa-solid fa-eye icon"></i> <span>Field Visibility</span></a></li>
                    <li><a href="reports.php"><i class="fa-solid fa-chart-line icon"></i> <span>Reports & Analytics</span></a></li>

                    <li class="sidebar-section-label">QUICK ACTIONS</li>
                    <li><a href="#" onclick="openModal('addClientModal'); return false;"><i class="fa-solid fa-user-plus icon"></i> <span>Add New Client</span></a></li>
                    <li><a href="#" onclick="openModal('addServiceModal'); return false;"><i class="fa-solid fa-square-plus icon"></i> <span>Add New Service</span></a></li>
                    <li><a href="#" onclick="openQuoteModal(null, 'Add New Quote'); return false;"><i class="fa-solid fa-circle-plus icon"></i> <span>Add Quote Config</span></a></li>
                </ul>
            </aside>

            <!-- Main Content -->
            <div class="dashboard-content">
                <!-- Content View Header / Breadcrumbs Bar -->
                <?php
                $pageTitles = [
                    'dashboard'     => ['title' => 'Dashboard Overview', 'subtitle' => 'System metrics, recent activity & summary', 'icon' => 'fa-chart-pie'],
                    'clients'       => ['title' => 'Client Management', 'subtitle' => 'Manage registered clients, profiles and accounts', 'icon' => 'fa-users'],
                    'services'      => ['title' => 'Service Management', 'subtitle' => 'Manage service offerings, pricing and details', 'icon' => 'fa-layer-group'],
                    'inquiries'     => ['title' => 'Service Inquiries', 'subtitle' => 'Review customer inquiries and follow-ups', 'icon' => 'fa-envelope-open-text'],
                    'quotes'        => ['title' => 'Quote Configurations', 'subtitle' => 'Configure and customize quote packages', 'icon' => 'fa-receipt'],
                    'custom_fields' => ['title' => 'Custom Fields', 'subtitle' => 'Configure custom parameters and metadata fields', 'icon' => 'fa-sliders'],
                    'visibility'    => ['title' => 'Field Visibility', 'subtitle' => 'Control which fields are displayed to clients', 'icon' => 'fa-eye']
                ];
                $currentPage = $pageTitles[$action] ?? ['title' => 'Admin Console', 'subtitle' => 'Manage system data and configurations', 'icon' => 'fa-gauge'];
                ?>
                <div class="dashboard-page-header">
                    <div class="dashboard-page-title-wrap">
                        <div class="dashboard-page-icon">
                            <i class="fa-solid <?php echo $currentPage['icon']; ?>"></i>
                        </div>
                        <div>
                            <div class="dashboard-breadcrumb">
                                <span>Administration</span>
                                <i class="fa-solid fa-chevron-right breadcrumb-separator"></i>
                                <span class="breadcrumb-current"><?php echo htmlspecialchars($currentPage['title']); ?></span>
                            </div>
                            <h2 class="dashboard-page-title"><?php echo htmlspecialchars($currentPage['title']); ?></h2>
                            <p class="dashboard-page-subtitle"><?php echo htmlspecialchars($currentPage['subtitle']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Tab -->
                <div id="dashboardTab" class="tab-content <?php echo $action === 'dashboard' ? 'active' : ''; ?>">
                    <!-- Quick Stats -->
                    <div class="quick-stats">
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['total_clients']; ?></div>
                            <div class="quick-stat-label">Total Clients</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['active_clients']; ?></div>
                            <div class="quick-stat-label">Active Clients</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['total_services']; ?></div>
                            <div class="quick-stat-label">Total Services</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['total_quotes']; ?></div>
                            <div class="quick-stat-label">Total Quotes</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['new_inquiries']; ?></div>
                            <div class="quick-stat-label">New Inquiries</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['pending_inquiries']; ?></div>
                            <div class="quick-stat-label">Pending</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['contacted_inquiries']; ?></div>
                            <div class="quick-stat-label">Contacted</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['resolved_inquiries']; ?></div>
                            <div class="quick-stat-label">Resolved</div>
                        </div>
                        <!-- New stat for reminders -->
                        <div class="quick-stat-card reminder-card">
                            <div class="quick-stat-number"><?php echo $stats['upcoming_followups']; ?></div>
                            <div class="quick-stat-label">Upcoming Follow-ups</div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <h3>Quick Actions</h3>
                        <div class="action-buttons">
                            <button class="btn btn-primary" onclick="window.location.href='dashboard.php?action=clients'">
                                <i class="fa-solid fa-users"></i> <span>Manage Clients</span>
                            </button>
                            <button class="btn btn-success" onclick="window.location.href='dashboard.php?action=services'">
                                <i class="fa-solid fa-layer-group"></i> <span>Manage Services</span>
                            </button>
                            <button class="btn btn-quote-config" onclick="openQuoteModal(null, 'Add New Quote')">
                                <i class="fa-solid fa-circle-plus"></i> <span>Add Quote</span>
                            </button>
                            <button class="btn btn-secondary" onclick="window.location.href='dashboard.php?action=inquiries'">
                                <i class="fa-solid fa-envelope-open-text"></i> <span>View Inquiries</span>
                            </button>
                        </div>
                    </div>

                    <!-- Reminder Card -->
                    <div class="card reminder-card">
                        <div class="card-header">
                            <h3><i class="fa-solid fa-clock icon"></i> Upcoming Follow-ups</h3>
                            <a href="dashboard.php?action=inquiries">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($upcoming_followups)): ?>
                                <p class="text-muted">No upcoming follow-ups.</p>
                            <?php else: ?>
                                <?php foreach ($upcoming_followups as $followup): ?>
                                    <div class="reminder-item">
                                        <div>
                                            <strong><?php echo htmlspecialchars($followup['name'] ?? ''); ?></strong>
                                            <div class="text-muted small">
                                                <?php echo htmlspecialchars($followup['service_title'] ?? $followup['service_name'] ?? 'General'); ?>
                                            </div>
                                        </div>
                                        <div class="reminder-actions">
                                            <span class="reminder-date"><?php echo date('d M', strtotime($followup['follow_up_date'])); ?></span>
                                            <a href="dashboard.php?action=inquiries&view_inquiry=<?php echo $followup['id']; ?>" class="btn btn-info btn-sm">View</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Two-column layout -->
                    <div class="dashboard-grid-2col">
                        <!-- Recent Clients -->
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fa-solid fa-users icon"></i> Recent Clients</h3>
                                <a href="dashboard.php?action=clients">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_clients)): ?>
                                    <p class="text-muted">No clients found</p>
                                <?php else: ?>
                                    <?php foreach ($recent_clients as $client): ?>
                                        <div class="list-item">
                                            <div class="list-item-content">
                                                <strong><?php echo htmlspecialchars($client['full_name'] ?? ''); ?></strong>
                                                <div class="text-muted small"><?php echo htmlspecialchars($client['email'] ?? ''); ?></div>
                                            </div>
                                            <span class="status-badge status-<?php echo $client['status'] ?? ''; ?>">
                                                <?php echo ucfirst($client['status'] ?? ''); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Recent Services -->
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fa-solid fa-layer-group icon"></i> Recent Services</h3>
                                <a href="dashboard.php?action=services">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_services)): ?>
                                    <p class="text-muted">No services found</p>
                                <?php else: ?>
                                    <?php foreach ($recent_services as $service): 
                                        $service_quotes = function_exists('getQuotesByServiceId') ? getQuotesByServiceId($conn, $service['id']) : [];
                                        $quote_count = count($service_quotes);
                                    ?>
                                        <div class="list-item">
                                            <div class="list-item-content">
                                                <strong><?php echo htmlspecialchars($service['title'] ?? ''); ?></strong>
                                                <?php if ($quote_count > 0): ?>
                                                    <span class="quote-indicator"><i class="fa-solid fa-receipt"></i> <?php echo $quote_count; ?></span>
                                                <?php endif; ?>
                                                <?php if (empty($service['is_visible_to_clients'])): ?>
                                                    <span class="status-badge status-inactive small">Hidden</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($service['is_featured'])): ?>
                                                <span class="featured-badge"><i class="fa-solid fa-star"></i></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Recent Inquiries -->
                        <div class="card full-width">
                            <div class="card-header">
                                <h3><i class="fa-solid fa-envelope-open-text icon"></i> Recent Inquiries</h3>
                                <a href="dashboard.php?action=inquiries">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_inquiries)): ?>
                                    <p class="text-muted">No inquiries found</p>
                                <?php else: ?>
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Service</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_inquiries as $inquiry): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($inquiry['name'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($inquiry['service_title'] ?? $inquiry['service_name'] ?? 'General'); ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $inquiry['status'] ?? ''; ?>">
                                                            <?php echo ucfirst($inquiry['status'] ?? ''); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo isset($inquiry['created_at']) ? date('d M Y', strtotime($inquiry['created_at'])) : 'N/A'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clients Tab -->
                <div id="clientsTab" class="tab-content <?php echo $action === 'clients' ? 'active' : ''; ?>">
                    <div class="quick-stats">
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['total_clients']; ?></div>
                            <div class="quick-stat-label">Total Clients</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['active_clients']; ?></div>
                            <div class="quick-stat-label">Active Clients</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['pending_clients']; ?></div>
                            <div class="quick-stat-label">Pending Clients</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['inactive_clients']; ?></div>
                            <div class="quick-stat-label">Inactive Clients</div>
                        </div>
                    </div>

                    <div class="action-toolbar">
                        <div class="search-box">
                            <form method="GET" action="">
                                <input type="hidden" name="action" value="clients">
                                <input type="text" name="search" placeholder="🔍 Search clients by name, email, or ID..." value="<?php echo htmlspecialchars($search); ?>">
                            </form>
                        </div>
                        <div class="filters">
                            <select class="filter-select" onchange="window.location.href='dashboard.php?action=clients&status='+this.value+'&search=<?php echo urlencode($search); ?>'">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                            <button class="btn btn-primary" onclick="openModal('addClientModal')">
                                <i class="fa-solid fa-user-plus"></i> <span>Add Client</span>
                            </button>
                        </div>
                    </div>

                    <div class="table-container">
                        <?php if (empty($clients)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon"><i class="fa-solid fa-users-slash"></i></div>
                                <h3>No clients found</h3>
                                <p><?php echo !empty($search) ? 'Try adjusting your search criteria' : 'Add your first client to get started'; ?></p>
                                <button class="btn btn-primary mt-20" onclick="openModal('addClientModal')">
                                    <i class="fa-solid fa-user-plus"></i> <span>Add Your First Client</span>
                                </button>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Client ID</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Company</th>
                                        <th>Status</th>
                                        <th>Registration Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clients as $client): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($client['client_id'] ?? ''); ?></strong></td>
                                            <td><?php echo htmlspecialchars($client['full_name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($client['email'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($client['company_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $client['status'] ?? ''; ?>">
                                                    <?php echo ucfirst($client['status'] ?? ''); ?>
                                                </span>
                                            </td>
                                            <td><?php echo isset($client['registration_date']) ? date('d M Y', strtotime($client['registration_date'])) : 'N/A'; ?></td>
                                            <td>
                                                <div class="action-buttons-small">
                                                    <a href="dashboard.php?action=clients&view_client=<?php echo $client['id']; ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-eye"></i> View</a>
                                                    <a href="dashboard.php?action=clients&edit_client=<?php echo $client['id']; ?>" class="btn btn-success btn-sm"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                                    <form method="POST" class="inline-form">
                                                        <input type="hidden" name="reset_id" value="<?php echo $client['id']; ?>">
                                                        <button type="submit" name="reset_password" class="btn btn-warning btn-sm" onclick="return confirm('Reset password to default (welcome123)?')"><i class="fa-solid fa-key"></i> Reset PW</button>
                                                    </form>
                                                    <form method="POST" class="inline-form">
                                                        <input type="hidden" name="delete_id" value="<?php echo $client['id']; ?>">
                                                        <button type="submit" name="delete_client" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this client?')"><i class="fa-solid fa-trash"></i> Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Services Tab -->
                <div id="servicesTab" class="tab-content <?php echo $action === 'services' ? 'active' : ''; ?>">
                    <div class="quick-stats">
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['total_services']; ?></div>
                            <div class="quick-stat-label">Total Services</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['featured_services']; ?></div>
                            <div class="quick-stat-label">Featured Services</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['total_quotes']; ?></div>
                            <div class="quick-stat-label">Total Quotes</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['featured_quotes']; ?></div>
                            <div class="quick-stat-label">Featured Quotes</div>
                        </div>
                    </div>

                    <div class="action-toolbar">
                        <div class="search-box">
                            <form method="GET" action="">
                                <input type="hidden" name="action" value="services">
                                <input type="text" name="search" placeholder="🔍 Search services by name or description..." value="<?php echo htmlspecialchars($search); ?>">
                            </form>
                        </div>
                        <div class="filters">
                            <button class="btn btn-success" onclick="openModal('addServiceModal')">
                                <i class="fa-solid fa-square-plus"></i> <span>Add Service</span>
                            </button>
                        </div>
                    </div>

                    <div class="services-list">
                        <?php if (empty($services)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon"><i class="fa-solid fa-layer-group"></i></div>
                                <h3>No services found</h3>
                                <p><?php echo !empty($search) ? 'Try adjusting your search criteria' : 'Add your first service to get started'; ?></p>
                                <button class="btn btn-success mt-20" onclick="openModal('addServiceModal')">
                                    <i class="fa-solid fa-square-plus"></i> <span>Add Your First Service</span>
                                </button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($services as $service): 
                                $service_quotes = function_exists('getQuotesByServiceId') ? getQuotesByServiceId($conn, $service['id']) : [];
                                $quote_count = count($service_quotes);
                            ?>
                                <div class="service-card <?php echo !empty($service['is_featured']) ? 'featured' : ''; ?>" data-service-id="<?php echo $service['id']; ?>">
                                    <div class="service-header">
                                        <div class="service-title">
                                            <?php echo htmlspecialchars($service['title'] ?? ''); ?>
                                            <?php if ($quote_count > 0): ?>
                                                <span class="quote-indicator"><i class="fa-solid fa-receipt"></i> <?php echo $quote_count; ?> Package<?php echo $quote_count > 1 ? 's' : ''; ?></span>
                                            <?php endif; ?>
                                            <?php if (empty($service['is_visible_to_clients'])): ?>
                                                <span class="status-badge status-inactive">Hidden</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($service['is_featured'])): ?>
                                            <span class="featured-badge"><i class="fa-solid fa-star"></i> Featured</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="service-description">
                                        <?php echo htmlspecialchars(substr($service['description'] ?? '', 0, 150)); ?>...
                                    </div>
                                    
                                    <?php if (!empty($service['image_url'])): ?>
                                    <div class="service-image-container">
                                        <?php if (strpos($service['image_url'], 'data:image') === 0): ?>
                                            <img src="<?php echo htmlspecialchars($service['image_url']); ?>" alt="Service Image" class="service-image-preview">
                                        <?php else: ?>
                                            <a href="<?php echo htmlspecialchars($service['image_url']); ?>" target="_blank">View Image</a>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="service-meta">
                                        <div class="meta-item">
                                            <i class="fa-regular fa-clock"></i>
                                            <span><?php echo htmlspecialchars($service['duration'] ?? ''); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fa-solid fa-tag"></i>
                                            <span><?php echo htmlspecialchars($service['price_range'] ?? ''); ?></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Multiple Quotes Section -->
									<!-- Multiple Quotes Section - Modern Cards -->
									<?php if (!empty($service_quotes)): ?>
									<div class="modern-quote-section">
										<h4 class="modern-quote-title"><i class="fa-solid fa-boxes-stacked"></i> Available Packages</h4>
										<div class="modern-quote-grid">
											<?php 
											$current_category = '';
											foreach ($service_quotes as $sq): 
												if ($sq['quote_category'] && $sq['quote_category'] != $current_category):
													$current_category = $sq['quote_category'];
											?>
												<div class="quote-category-header">
													<h5><?php echo htmlspecialchars($current_category); ?></h5>
												</div>
											<?php endif; ?>
											<div class="modern-quote-card">
												<div class="quote-card-header">
													<div class="quote-title">
														<strong><?php echo htmlspecialchars($sq['quote_title']); ?></strong>
														<?php if (!empty($sq['quote_code'])): ?>
															<span class="quote-code">(<?php echo htmlspecialchars($sq['quote_code']); ?>)</span>
														<?php endif; ?>
													</div>
													<?php if (!empty($sq['is_featured_quote'])): ?>
														<span class="featured-tag"><i class="fa-solid fa-star"></i> Featured</span>
													<?php endif; ?>
												</div>
												<div class="quote-card-body">
													<p class="quote-description"><?php echo htmlspecialchars($sq['quote_description']); ?></p>
													<div class="quote-price">
														<?php 
														switch($sq['price_type'] ?? '') {
															case 'fixed':
																echo formatPrice($sq['fixed_price']);
																break;
															case 'range':
																echo formatPrice($sq['min_price']) . ' – ' . formatPrice($sq['max_price']);
																break;
															case 'starting_at':
																echo 'From ' . formatPrice($sq['min_price']);
																break;
															default:
																echo 'Custom';
														}
														?>
													</div>
												</div>
												<div class="quote-card-actions">
													<a href="print_quotation.php?id=<?php echo $sq['id']; ?>" target="_blank" class="btn btn-info btn-sm" title="Print Quotation"><i class="fa-solid fa-print"></i> Print</a>
													<button type="button" class="btn btn-primary btn-sm" onclick="openQuoteModal(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars(addslashes($service['title'])); ?>', <?php echo htmlspecialchars(json_encode($sq)); ?>)"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
													<form method="POST" class="inline-form">
														<input type="hidden" name="delete_quote_id" value="<?php echo $sq['id']; ?>">
														<button type="submit" name="delete_quote" class="btn btn-danger btn-sm" onclick="return confirm('Delete this quote?')"><i class="fa-solid fa-trash"></i> Delete</button>
													</form>
												</div>
											</div>
											<?php endforeach; ?>
										</div>
									</div>
                                    <?php endif; ?>
                                    
                                  <div class="service-actions">
									<a href="dashboard.php?action=services&view_service=<?php echo $service['id']; ?>" class="btn btn-info btn-sm"><i class="fa-solid fa-eye"></i> View</a>
									<a href="dashboard.php?action=services&edit_service=<?php echo $service['id']; ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
									
									<button type="button" class="btn btn-success btn-sm" onclick="openQuoteModal(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars(addslashes($service['title'])); ?>')">
										<i class="fa-solid fa-circle-plus"></i> Add Quote
									</button>
									
									<form method="POST" class="inline-form">
										<input type="hidden" name="delete_service_id" value="<?php echo $service['id']; ?>">
										<button type="submit" name="delete_service" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this service?')"><i class="fa-solid fa-trash"></i> Delete</button>
									</form>
								</div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Inquiries Tab -->
                <div id="inquiriesTab" class="tab-content <?php echo $action === 'inquiries' ? 'active' : ''; ?>">
                    <div class="quick-stats">
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['total_inquiries']; ?></div>
                            <div class="quick-stat-label">Total Inquiries</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['new_inquiries']; ?></div>
                            <div class="quick-stat-label">New</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['pending_inquiries']; ?></div>
                            <div class="quick-stat-label">Pending</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['contacted_inquiries']; ?></div>
                            <div class="quick-stat-label">Contacted</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['resolved_inquiries']; ?></div>
                            <div class="quick-stat-label">Resolved</div>
                        </div>
                    </div>

                    <div class="action-toolbar">
                        <div class="search-box">
                            <form method="GET" action="">
                                <input type="hidden" name="action" value="inquiries">
                                <input type="text" name="search" placeholder="🔍 Search inquiries by name, email, phone, or organization..." value="<?php echo htmlspecialchars($search); ?>">
                            </form>
                        </div>
                        <div class="filters">
                            <select class="filter-select" onchange="window.location.href='dashboard.php?action=inquiries&status='+this.value+'&search=<?php echo urlencode($search); ?>'">
                                <option value="">All Status</option>
                                <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>New</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="contacted" <?php echo $status_filter === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-container">
                        <?php if (empty($inquiries)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon"><i class="fa-solid fa-inbox"></i></div>
                                <h3>No inquiries found</h3>
                                <p><?php echo !empty($search) ? 'Try adjusting your search criteria' : 'No service inquiries yet'; ?></p>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Service</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inquiries as $inquiry): ?>
                                        <tr>
                                            <td><strong>#<?php echo $inquiry['id']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($inquiry['name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($inquiry['email'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($inquiry['phone'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if (!empty($inquiry['service_id'])): ?>
                                                    <a href="dashboard.php?action=services&view_service=<?php echo $inquiry['service_id']; ?>">
                                                        <?php echo htmlspecialchars($inquiry['service_title'] ?? $inquiry['service_name'] ?? ''); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($inquiry['service_name'] ?? 'General Inquiry'); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $inquiry['status'] ?? ''; ?>">
                                                    <?php echo ucfirst($inquiry['status'] ?? ''); ?>
                                                </span>
                                            </td>
                                            <td><?php echo isset($inquiry['created_at']) ? date('d M Y', strtotime($inquiry['created_at'])) : 'N/A'; ?></td>
                                            <td>
                                                <div class="action-buttons-small">
                                                    <a href="dashboard.php?action=inquiries&view_inquiry=<?php echo $inquiry['id']; ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-eye"></i> View</a>
													<a href="mailto:<?php echo htmlspecialchars($inquiry['email']); ?>?subject=Regarding your inquiry for <?php echo urlencode($inquiry['service_name'] ?? 'our services'); ?>" class="btn btn-info btn-sm" title="Send Email"><i class="fa-solid fa-envelope"></i> Email</a>
                                                    
                                                    <form method="POST" class="inline-form">
                                                        <input type="hidden" name="inquiry_id" value="<?php echo $inquiry['id']; ?>">
                                                        <select name="update_inquiry_status" onchange="this.form.submit()" class="status-select">
                                                            <option value="">Change</option>
                                                            <option value="new">New</option>
                                                            <option value="pending">Pending</option>
                                                            <option value="contacted">Contacted</option>
                                                            <option value="resolved">Resolved</option>
                                                        </select>
                                                    </form>
                                                    
                                                    <form method="POST" class="inline-form">
                                                        <input type="hidden" name="delete_inquiry_id" value="<?php echo $inquiry['id']; ?>">
                                                        <button type="submit" name="delete_inquiry" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this inquiry?')"><i class="fa-solid fa-trash"></i> Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quotes Tab -->
                <div id="quotesTab" class="tab-content <?php echo $action === 'quotes' ? 'active' : ''; ?>">
                    <div class="quick-stats">
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['total_quotes'] ?? 0; ?></div>
                            <div class="quick-stat-label">Total Quotes</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['visible_quotes'] ?? 0; ?></div>
                            <div class="quick-stat-label">Client Visible</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['featured_quotes'] ?? 0; ?></div>
                            <div class="quick-stat-label">Featured</div>
                        </div>
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['quotes_with_inquiries'] ?? 0; ?></div>
                            <div class="quick-stat-label">With Inquiries</div>
                        </div>
                    </div>

                    <div class="action-toolbar">
                        <div class="search-box">
                            <form method="GET" action="">
                                <input type="hidden" name="action" value="quotes">
                                <input type="text" name="search" placeholder="🔍 Search quotes by title or service..." value="<?php echo htmlspecialchars($search); ?>">
                            </form>
                        </div>
                        <div class="filters">
                            <button class="btn btn-quote-config" onclick="openQuoteModal(null, 'Add New Quote')">
                                <i class="fa-solid fa-circle-plus"></i> Add Quote Config
                            </button>
                        </div>
                    </div>

                    <div class="table-container">
                        <?php if (empty($all_quotes)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">💰</div>
                                <h3>No quotes found</h3>
                                <p><?php echo !empty($search) ? 'Try adjusting your search criteria' : 'Create pricing packages and quotes for your services'; ?></p>
                                <button class="btn btn-quote-config mt-20" onclick="openQuoteModal(null, 'Add New Quote')">
                                    <i class="fa-solid fa-circle-plus"></i> Add First Quote
                                </button>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Quote / Package</th>
                                        <th>Service</th>
                                        <th>Category / Code</th>
                                        <th>Pricing</th>
                                        <th>Duration</th>
                                        <th>Visibility</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_quotes as $q): ?>
                                        <tr>
                                            <td><strong>#<?php echo $q['id']; ?></strong></td>
                                            <td>
                                                <div class="table-title-wrap">
                                                    <strong><?php echo htmlspecialchars($q['quote_title']); ?></strong>
                                                    <?php if (!empty($q['is_featured_quote'])): ?>
                                                        <span class="featured-badge" title="Featured Quote">★ Featured</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-muted small"><?php echo htmlspecialchars(substr($q['quote_description'] ?? '', 0, 85)) . (strlen($q['quote_description'] ?? '') > 85 ? '...' : ''); ?></div>
                                            </td>
                                            <td>
                                                <?php if (!empty($q['service_id'])): ?>
                                                    <a href="dashboard.php?action=services&view_service=<?php echo $q['service_id']; ?>" class="service-link">
                                                        <?php echo htmlspecialchars($q['service_title'] ?? 'Service #' . $q['service_id']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Unassigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($q['quote_category'])): ?>
                                                    <span class="category-badge"><?php echo htmlspecialchars($q['quote_category']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($q['quote_code'])): ?>
                                                    <div class="code-badge"><?php echo htmlspecialchars($q['quote_code']); ?></div>
                                                <?php endif; ?>
                                                <?php if (empty($q['quote_category']) && empty($q['quote_code'])): ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="price-display">
                                                    <?php
                                                    switch ($q['price_type']) {
                                                        case 'fixed':
                                                            echo function_exists('formatPrice') ? formatPrice($q['fixed_price']) : '₹' . number_format($q['fixed_price'] ?? 0, 2);
                                                            break;
                                                        case 'range':
                                                            echo (function_exists('formatPrice') ? formatPrice($q['min_price']) : '₹' . number_format($q['min_price'] ?? 0, 2)) . ' - ' . 
                                                                 (function_exists('formatPrice') ? formatPrice($q['max_price']) : '₹' . number_format($q['max_price'] ?? 0, 2));
                                                            break;
                                                        case 'starting_at':
                                                            echo 'From ' . (function_exists('formatPrice') ? formatPrice($q['min_price']) : '₹' . number_format($q['min_price'] ?? 0, 2));
                                                            break;
                                                        default:
                                                            echo 'Custom Quote';
                                                    }
                                                    ?>
                                                </div>
                                                <?php if (!empty($q['has_discount']) && !empty($q['discount_percentage'])): ?>
                                                    <span class="discount-badge"><?php echo floatval($q['discount_percentage']); ?>% OFF</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="duration-badge">
                                                    <?php echo htmlspecialchars($q['min_duration'] ?? '1'); ?> - <?php echo htmlspecialchars($q['max_duration'] ?? '12'); ?> mos
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" class="inline-form">
                                                    <input type="hidden" name="quote_id" value="<?php echo $q['id']; ?>">
                                                    <input type="hidden" name="is_visible" value="<?php echo !empty($q['is_visible_to_clients']) ? 0 : 1; ?>">
                                                    <button type="submit" name="toggle_quote_visibility" class="status-badge <?php echo !empty($q['is_visible_to_clients']) ? 'status-active' : 'status-inactive'; ?>" style="border:none; cursor:pointer;" title="Click to toggle client visibility">
                                                        <?php echo !empty($q['is_visible_to_clients']) ? 'Visible' : 'Hidden'; ?>
                                                    </button>
                                                </form>
                                            </td>
                                            <td>
                                                <div class="action-buttons-small">
                                                    <a href="print_quotation.php?id=<?php echo $q['id']; ?>" target="_blank" class="btn btn-info btn-sm" title="Print Quotation Form">
                                                        <i class="fa-solid fa-print"></i> Print
                                                    </a>
                                                    <button type="button" class="btn btn-quote-config btn-sm" onclick="openQuoteModal(<?php echo $q['service_id'] ?? 0; ?>, '<?php echo htmlspecialchars(addslashes($q['service_title'] ?? 'Quote')); ?>', <?php echo htmlspecialchars(json_encode($q)); ?>)">
                                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                                    </button>
                                                    <form method="POST" class="inline-form">
                                                        <input type="hidden" name="delete_quote_id" value="<?php echo $q['id']; ?>">
                                                        <button type="submit" name="delete_quote" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this quote configuration?')">
                                                            <i class="fa-solid fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination for Quotes -->
                    <?php if (isset($quotes_total_pages) && $quotes_total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="dashboard.php?action=quotes&page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">&laquo; Previous</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $quotes_total_pages; $i++): ?>
                            <a href="dashboard.php?action=quotes&page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $quotes_total_pages): ?>
                            <a href="dashboard.php?action=quotes&page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ========== NEW CUSTOM FIELDS TAB ========== -->
                <div id="custom_fieldsTab" class="tab-content <?php echo $action === 'custom_fields' ? 'active' : ''; ?>">
                    <div class="quick-stats">
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php echo $stats['total_custom_fields'] ?? 0; ?></div>
                            <div class="quick-stat-label">Total Custom Fields</div>
                        </div>
                    </div>

                    <div class="action-toolbar">
                        <div class="filters">
                            <button class="btn btn-success" onclick="openModal('addCustomFieldModal')">
                                <i class="fa-solid fa-square-plus"></i> <span>Add Custom Field</span>
                            </button>
                        </div>
                    </div>

                    <div class="table-container">
                        <?php if (empty($custom_fields)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon"><i class="fa-solid fa-sliders"></i></div>
                                <h3>No custom fields defined</h3>
                                <p>Add your first custom field to customize quotes.</p>
                                <button class="btn btn-success mt-20" onclick="openModal('addCustomFieldModal')">
                                    <i class="fa-solid fa-square-plus"></i> <span>Add Custom Field</span>
                                </button>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Field Name</th>
                                        <th>Type</th>
                                        <th>Options (JSON)</th>
                                        <th>Required</th>
                                        <th>Sort Order</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($custom_fields as $field): ?>
                                        <tr>
                                            <td>#<?php echo $field['id']; ?></td>
                                            <td><?php echo htmlspecialchars($field['field_name']); ?></td>
                                            <td><?php echo htmlspecialchars($field['field_type']); ?></td>
                                            <td><pre style="max-width:200px; overflow:auto;"><?php echo htmlspecialchars($field['field_options']); ?></pre></td>
                                            <td><?php echo $field['required'] ? 'Yes' : 'No'; ?></td>
                                            <td><?php echo $field['sort_order']; ?></td>
                                            <td>
                                                <div class="action-buttons-small">
                                                    <button class="btn btn-primary btn-sm" onclick="openEditCustomFieldModal(<?php echo htmlspecialchars(json_encode($field)); ?>)">
                                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                                    </button>
                                                    <form method="POST" class="inline-form">
                                                        <input type="hidden" name="delete_field_id" value="<?php echo $field['id']; ?>">
                                                        <button type="submit" name="delete_custom_field" class="btn btn-danger btn-sm" onclick="return confirm('Delete this custom field? This will remove all associated values from quotes.')">
                                                            <i class="fa-solid fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if (isset($custom_fields_total_pages) && $custom_fields_total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="dashboard.php?action=custom_fields&page=<?php echo $page - 1; ?>">&laquo; Previous</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $custom_fields_total_pages; $i++): ?>
                            <a href="dashboard.php?action=custom_fields&page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $custom_fields_total_pages): ?>
                            <a href="dashboard.php?action=custom_fields&page=<?php echo $page + 1; ?>">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
				<!-- ========== NEW FIELD VISIBILITY TAB ========== -->
                <div id="visibilityTab" class="tab-content <?php echo $action === 'visibility' ? 'active' : ''; ?>">
                    <div class="quick-stats">
                        <div class="quick-stat-card">
                            <div class="quick-stat-number"><?php 
                                $total_fields = 0;
                                foreach ($field_visibility as $section => $fields) {
                                    $total_fields += count($fields);
                                }
                                echo $total_fields;
                            ?></div>
                            <div class="quick-stat-label">Total Fields</div>
                        </div>
                    </div>

                    <div class="action-toolbar">
                        <h3>Control which fields are visible to clients</h3>
                    </div>

                    <form method="POST" action="" id="visibilityForm">
                        <?php foreach ($field_visibility as $section => $fields): ?>
                            <div class="card" style="margin-bottom: 20px;">
                                <div class="card-header">
                                    <h3><?php echo ucfirst($section); ?> Fields</h3>
                                </div>
                                <div class="card-body">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Field</th>
                                                <th>Visible to Clients</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($fields as $field): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($field['field_label']); ?></td>
                                                    <td>
                                                        <label class="switch">
                                                            <input type="checkbox" name="visibility[<?php echo $field['field_key']; ?>]" value="1" <?php echo $field['is_visible'] ? 'checked' : ''; ?>>
                                                            <span class="slider round"></span>
                                                        </label>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_visibility" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk"></i> <span>Save Visibility Settings</span>
                            </button>
                        </div>
                    </form>
                </div>

<!-- Pagination -->
                <?php if (($action === 'clients' || $action === 'services') && $total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="dashboard.php?action=<?php echo $action; ?>&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="dashboard.php?action=<?php echo $action; ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="dashboard.php?action=<?php echo $action; ?>&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($action === 'inquiries' && $inquiry_total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="dashboard.php?action=inquiries&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $inquiry_total_pages; $i++): ?>
                        <a href="dashboard.php?action=inquiries&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $inquiry_total_pages): ?>
                        <a href="dashboard.php?action=inquiries&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <!-- ========== MODALS ========== -->
    <!-- Add Client Modal -->
    <div id="addClientModal" class="modal-overlay" style="display: none;">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fa-solid fa-user-plus icon"></i> Add New Client</h3>
                <button class="modal-close" onclick="closeModal('addClientModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="addClientForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone">
                        </div>
                        <div class="form-group">
                            <label for="company_name">Company Name</label>
                            <input type="text" id="company_name" name="company_name">
                        </div>
                        <div class="form-group full-width">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="registration_date">Registration Date</label>
                            <input type="date" id="registration_date" name="registration_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="pending">Pending</option>
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_client" class="btn btn-primary">
                            <i class="fa-solid fa-user-plus"></i> <span>Add Client</span>
                        </button>
                        <button type="button" class="btn" onclick="closeModal('addClientModal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Service Modal -->
    <div id="addServiceModal" class="modal-overlay" style="display: none;">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h3><i class="fa-solid fa-square-plus icon"></i> Add New Service</h3>
                <button class="modal-close" onclick="closeModal('addServiceModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="addServiceForm" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="title">Service Title *</label>
                            <input type="text" id="title" name="title" required placeholder="e.g., Construction Works">
                        </div>
                        <div class="form-group full-width">
                            <label for="description">Short Description </label>
                            <textarea id="description" name="description" rows="2" required placeholder="Brief description for listings"></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label for="detailed_description">Detailed Description</label>
                            <textarea id="detailed_description" name="detailed_description" rows="4" placeholder="Complete service description with all details"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="duration_min">Min Duration (months) *</label>
                            <input type="number" id="duration_min" name="duration_min" min="1" value="3" required>
                        </div>
                        <div class="form-group">
                            <label for="duration_max">Max Duration (months) *</label>
                            <input type="number" id="duration_max" name="duration_max" min="1" value="24" required>
                        </div>
                        <div class="form-group">
                            <label for="price_range_min">Min Price (₹)</label>
                            <input type="number" id="price_range_min" name="price_range_min" min="10" step="10" placeholder="e.g., 5000">
                        </div>
                        <div class="form-group">
                            <label for="price_range_max">Max Price (₹)</label>
                            <input type="number" id="price_range_max" name="price_range_max" min="10" step="10" placeholder="e.g., 50000">
                        </div>
                        <div class="form-group full-width">
                            <label for="features">Features (comma-separated)</label>
                            <input type="text" id="features" name="features" placeholder="Quality Materials, Expert Team, On-time Delivery">
                            <small class="form-help">Enter features separated by commas</small>
                        </div>
                        
                        <!-- Image Upload Section -->
                        <div class="form-group full-width">
                            <label>Service Image (800x600 pixels recommended)</label>
                            <div class="image-upload-container">
                                <div class="image-preview" id="imagePreview">
                                    <div class="image-preview-placeholder">
                                        <span class="icon">📷</span>
                                        <div>No image selected</div>
                                    </div>
                                </div>
                                <div class="upload-controls">
                                    <label class="upload-button">
                                        <span class="icon">📁</span> Choose Image
                                        <input type="file" id="service_image" name="service_image" accept="image/*" onchange="previewImage(this)">
                                    </label>
                                    <div class="image-info">Max size: 2MB • Formats: JPG, PNG, GIF, WebP</div>
                                    <div class="or-divider">OR</div>
                                    <input type="url" id="image_url" name="image_url" placeholder="Enter image URL instead..." class="url-input">
                                    <small class="form-help">Use either file upload or URL, not both</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="is_featured">Featured Service</label>
                            <select id="is_featured" name="is_featured">
                                <option value="false">No</option>
                                <option value="true">Yes (★ Featured)</option>
                            </select>
                        </div>

                        <!-- Permission Fields -->
                        <div class="form-group permissions-group">
                            <div class="form-checkbox">
                                <label>
                                    <input type="checkbox" id="is_visible_to_clients" name="is_visible_to_clients" value="1" checked>
                                    <span>Visible to clients</span>
                                </label>
                            </div>
                            <div class="form-checkbox">
                                <label>
                                    <input type="checkbox" id="show_price_to_clients" name="show_price_to_clients" value="1" checked>
                                    <span>Show price to clients</span>
                                </label>
                            </div>
                            <div class="form-checkbox">
                                <label>
                                    <input type="checkbox" id="show_description_to_clients" name="show_description_to_clients" value="1" checked>
                                    <span>Show full description to clients</span>
                                </label>
                            </div>
                            <div class="form-checkbox">
                                <label>
                                    <input type="checkbox" id="allow_client_quotes" name="allow_client_quotes" value="1" checked>
                                    <span>Allow clients to request quotes</span>
                                </label>
                            </div>
                            <div class="form-checkbox">
                                <label>
                                    <input type="checkbox" id="requires_admin_approval" name="requires_admin_approval" value="1">
                                    <span>Require admin approval for quotes</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="admin_notes">Admin Notes (internal)</label>
                            <textarea id="admin_notes" name="admin_notes" rows="3" placeholder="Internal notes about this service"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="meta_title">Meta Title (SEO)</label>
                            <input type="text" id="meta_title" name="meta_title" placeholder="SEO title">
                        </div>
                        <div class="form-group">
                            <label for="meta_description">Meta Description</label>
                            <textarea id="meta_description" name="meta_description" rows="2" placeholder="SEO description"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="meta_keywords">Meta Keywords (comma-separated)</label>
                            <input type="text" id="meta_keywords" name="meta_keywords" placeholder="keyword1, keyword2">
                        </div>
                        <div class="form-group">
                            <label for="sort_order">Sort Order</label>
                            <input type="number" id="sort_order" name="sort_order" min="0" value="0">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_service" class="btn btn-success">
                            <i class="fa-solid fa-plus"></i> Add Service
                        </button>
                        <button type="button" class="btn" onclick="closeModal('addServiceModal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Quote Modal (modified to include custom fields) -->
    <div id="quoteModal" class="modal-overlay" style="display: none;">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h3><i class="fa-solid fa-file-invoice-dollar" style="color: var(--brass-accent); margin-right: 8px;"></i> <span id="quoteModalTitle">Configure Service Quote</span></h3>
                <button class="modal-close" onclick="closeModal('quoteModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="quoteForm">
                    <input type="hidden" name="service_id" id="quote_service_id" value="">
                    <input type="hidden" name="id" id="quote_id" value="">
                    
                    <!-- Service selection dropdown -->
                    <div class="form-group" id="service_select_row" style="display: none;">
                        <label for="quote_service_select">Select Service *</label>
                        <select id="quote_service_select" class="form-control" onchange="document.getElementById('quote_service_id').value=this.value">
                            <option value="">-- Choose a service --</option>
                            <?php foreach ($all_services as $srv): ?>
                                <option value="<?php echo $srv['id']; ?>"><?php echo htmlspecialchars($srv['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Quote Category & Code -->
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="quote_category">Category / Item Type</label>
                            <input type="text" id="quote_category" name="quote_category" placeholder="e.g., Cakes, Pastries, Breads, Custom Orders" value="">
                            <small class="form-help">Group related quotes together</small>
                        </div>
                        <div class="form-group half">
                            <label for="quote_code">Quote Code / SKU</label>
                            <input type="text" id="quote_code" name="quote_code" placeholder="e.g., CAKE-001, BREAD-STD" value="">
                            <small class="form-help">Optional unique identifier</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="sort_order">Display Order</label>
                            <input type="number" id="sort_order" name="sort_order" min="0" value="0">
                            <small class="form-help">Lower numbers appear first</small>
                        </div>
                    </div>
                    
                    <!-- Basic Information -->
                    <div class="form-group">
                        <label for="quote_title">Quote Title </label>
                        <input type="text" id="quote_title" name="quote_title" required placeholder="e.g., Wedding Cake Package">
                    </div>
                    
                    <div class="form-group">
                        <label for="quote_description">Short Description </label>
                        <textarea id="quote_description" name="quote_description" rows="2" required placeholder="Brief description"></textarea>
                    </div>
                    
                    <!-- Pricing Structure -->
                    <div class="pricing-config">
                        <h4><i class="fa-solid fa-coins" style="color: var(--brass-accent); margin-right: 6px;"></i> Pricing Configuration</h4>
                        
                        <div class="form-group">
                            <label for="price_type">Pricing Type </label>
                            <select id="price_type" name="price_type" required onchange="togglePriceFields()">
                                <option value="fixed">Fixed Price</option>
                                <option value="range">Price Range (Min - Max)</option>
                                <option value="starting_at">Starting At</option>
                                <option value="custom">Custom (Per Project)</option>
                            </select>
                        </div>
                        
                        <div id="fixed_price_field" class="form-group">
                            <label for="fixed_price">Fixed Price (₹) *</label>
                            <input type="number" id="fixed_price" name="fixed_price" step="0.01" min="0">
                        </div>
                        
                        <div id="range_price_fields" style="display: none;">
                            <div class="form-row">
                                <div class="form-group half">
                                    <label for="min_price">Minimum Price (₹)</label>
                                    <input type="number" id="min_price" name="min_price" step="0.01" min="0">
                                </div>
                                <div class="form-group half">
                                    <label for="max_price">Maximum Price (₹)</label>
                                    <input type="number" id="max_price" name="max_price" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="duration_type">Duration Type *</label>
                            <select id="duration_type" name="duration_type">
                                <option value="fixed_range">Fixed Range (e.g., 3-6 months)</option>
                                <option value="per_unit">Per Month/Week</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group half">
                                <label for="min_duration">Min Duration (months)</label>
                                <input type="number" id="min_duration" name="min_duration" min="1" value="1">
                            </div>
                            <div class="form-group half">
                                <label for="max_duration">Max Duration (months)</label>
                                <input type="number" id="max_duration" name="max_duration" min="1" value="12">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Inclusions & Exclusions -->
                    <div class="inclusions-exclusions">
                        <h4><i class="fa-solid fa-list-check" style="color: var(--color-success); margin-right: 6px;"></i> Inclusions & Exclusions</h4>
                        
                        <div class="form-row">
                            <div class="form-group half">
                                <label for="inclusions">What's Included? (One per line)</label>
                                <textarea id="inclusions" name="inclusions" rows="5" placeholder="Project Management&#10;Quality Materials&#10;Professional Installation&#10;Warranty"></textarea>
                            </div>
                            <div class="form-group half">
                                <label for="exclusions">What's Not Included? (One per line)</label>
                                <textarea id="exclusions" name="exclusions" rows="5" placeholder="Permits & Licenses&#10;Travel Expenses&#10;Taxes"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Requirements -->
                    <div class="requirements">
                        <h4><i class="fa-solid fa-clipboard-list" style="color: var(--primary-blue); margin-right: 6px;"></i> Requirements</h4>
                        
                        <div class="form-row">
                            <div class="form-group half">
                                <label for="prerequisites">Prerequisites (One per line)</label>
                                <textarea id="prerequisites" name="prerequisites" rows="4" placeholder="Site visit required&#10;Existing foundation&#10;Zoning approval"></textarea>
                            </div>
                            <div class="form-group half">
                                <label for="documents_required">Required Documents (One per line)</label>
                                <textarea id="documents_required" name="documents_required" rows="4" placeholder="Site plan&#10;Building permit&#10;Proof of ownership"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Discount Options -->
                    <div class="discount-config">
                        <div class="form-checkbox">
                            <label>
                                <input type="checkbox" id="has_discount" name="has_discount" value="1" onchange="toggleDiscountFields()">
                                <span><i class="fa-solid fa-percent" style="margin-right: 6px;"></i> Apply Discount</span>
                            </label>
                        </div>
                        
                        <div id="discount_fields" style="display: none;">
                            <div class="form-row">
                                <div class="form-group half">
                                    <label for="discount_percentage">Discount Percentage (%)</label>
                                    <input type="number" id="discount_percentage" name="discount_percentage" min="0" max="100" step="0.1">
                                </div>
                                <div class="form-group half">
                                    <label for="discount_description">Discount Description</label>
                                    <input type="text" id="discount_description" name="discount_description" placeholder="e.g., Early bird discount, Volume discount">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Featured Option -->
                    <div class="featured-quote">
                        <div class="form-checkbox">
                            <label>
                                <input type="checkbox" id="is_featured_quote" name="is_featured_quote" value="1">
                                <span><i class="fa-solid fa-star" style="color: var(--brass-accent); margin-right: 6px;"></i> Mark as Featured Quote (Shown prominently)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Visible to Clients Option -->
                    <div class="featured-quote">
                        <div class="form-checkbox">
                            <label>
                                <input type="checkbox" id="is_visible_to_clients" name="is_visible_to_clients" value="1" checked>
                                <span><i class="fa-solid fa-eye" style="margin-right: 6px;"></i> Visible to clients (show this quote on the service page)</span>
                            </label>
                        </div>
                    </div>

                    <!-- ===== CUSTOM FIELDS SECTION ===== -->
                    <?php
                    $all_custom_fields = getCustomFields($conn);
                    if (!empty($all_custom_fields)): ?>
                    <div class="custom-fields-section">
                        <h4><i class="fa-solid fa-sliders" style="margin-right: 6px;"></i> Custom Fields</h4>
                        <div id="customFieldsContainer">
                            <?php foreach ($all_custom_fields as $cf):
                                $field_id = $cf['id'];
                                $field_name = htmlspecialchars($cf['field_name']);
                                $field_type = $cf['field_type'];
                                $required = $cf['required'] ? 'required' : '';
                                $input_html = '';
                                if ($field_type === 'text') {
                                    $input_html = "<input type='text' name='custom_fields[$field_id]' id='custom_$field_id' class='form-control' placeholder='Enter $field_name' $required>";
                                } elseif ($field_type === 'number') {
                                    $input_html = "<input type='number' name='custom_fields[$field_id]' id='custom_$field_id' class='form-control' step='any' placeholder='Enter $field_name' $required>";
                                } elseif ($field_type === 'price_range') {
                                    $input_html = "<input type='text' name='custom_fields[$field_id]' id='custom_$field_id' class='form-control' placeholder='e.g., 500-1000' $required>";
                                } elseif ($field_type === 'duration') {
                                    $input_html = "<input type='text' name='custom_fields[$field_id]' id='custom_$field_id' class='form-control' placeholder='e.g., 3 days' $required>";
                                } elseif ($field_type === 'date_range') {
                                    $input_html = "<input type='text' name='custom_fields[$field_id]' id='custom_$field_id' class='form-control' placeholder='YYYY-MM-DD to YYYY-MM-DD' $required>";
                                }
                                ?>
                                <div class="form-group custom-field-group" data-field-id="<?php echo $field_id; ?>">
                                    <label for="custom_<?php echo $field_id; ?>"><?php echo $field_name; ?></label>
                                    <?php echo $input_html; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-actions">
                        <button type="submit" name="save_quote" id="saveQuoteBtn" class="btn btn-success">
                            <i class="fa-solid fa-floppy-disk"></i> Save Quote Configuration
                        </button>
                        <button type="button" class="btn" onclick="closeModal('quoteModal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add/Edit Custom Field Modal -->
    <div id="addCustomFieldModal" class="modal-overlay" style="display: none;">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fa-solid fa-table-list" style="margin-right: 8px;"></i> <span id="customFieldModalTitle">Add Custom Field</span></h3>
                <button class="modal-close" onclick="closeModal('addCustomFieldModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="customFieldForm">
                    <input type="hidden" name="field_id" id="custom_field_id" value="">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="field_name">Field Name *</label>
                            <input type="text" id="field_name" name="field_name" required placeholder="e.g., Number of Servings">
                        </div>
                        <div class="form-group">
                            <label for="field_type">Field Type *</label>
                            <select id="field_type" name="field_type" required>
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                                <option value="price_range">Price Range</option>
                                <option value="duration">Duration</option>
                                <option value="date_range">Date Range</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="field_options">Options (JSON)</label>
                            <textarea id="field_options" name="field_options" rows="3" placeholder='{"min":0,"max":1000}'></textarea>
                            <small class="form-help">Valid JSON for additional parameters (e.g., min/max for number).</small>
                        </div>
                        <div class="form-group">
                            <label for="sort_order">Sort Order</label>
                            <input type="number" id="sort_order" name="sort_order" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <div class="form-checkbox">
                                <label>
                                    <input type="checkbox" id="required" name="required" value="1">
                                    <span>Required field</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_custom_field" id="saveCustomFieldBtn" class="btn btn-success"><i class="fa-solid fa-plus"></i> Add Field</button>
                        <button type="button" class="btn" onclick="closeModal('addCustomFieldModal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Client Details Modal -->
    <?php if (!empty($view_client)): ?>
    <div id="viewClientModal" class="modal-overlay" style="display: flex;">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fa-solid fa-id-card" style="margin-right: 8px;"></i> Client Details</h3>
                <a href="dashboard.php?action=clients" class="modal-close">&times;</a>
            </div>
            <div class="modal-body">
                <div class="client-details">
                    <div class="details-grid">
                        <div class="detail-item">
                            <span class="detail-label">Client ID</span>
                            <span class="detail-value"><?php echo htmlspecialchars($view_client['client_id'] ?? ''); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Full Name</span>
                            <span class="detail-value"><?php echo htmlspecialchars($view_client['full_name'] ?? ''); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email</span>
                            <span class="detail-value"><?php echo htmlspecialchars($view_client['email'] ?? ''); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Username</span>
                            <span class="detail-value"><?php echo htmlspecialchars($view_client['username'] ?? ''); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone</span>
                            <span class="detail-value"><?php echo htmlspecialchars($view_client['phone'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Company</span>
                            <span class="detail-value"><?php echo htmlspecialchars($view_client['company_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="status-badge status-<?php echo $view_client['status'] ?? ''; ?>">
                                <?php echo ucfirst($view_client['status'] ?? ''); ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Registration Date</span>
                            <span class="detail-value"><?php echo isset($view_client['registration_date']) ? date('d M Y', strtotime($view_client['registration_date'])) : 'N/A'; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Created By</span>
                            <span class="detail-value"><?php echo htmlspecialchars($view_client['created_by_name'] ?? 'System'); ?></span>
                        </div>
                        <div class="detail-item full-width">
                            <span class="detail-label">Address</span>
                            <span class="detail-value"><?php echo nl2br(htmlspecialchars($view_client['address'] ?? 'Not provided')); ?></span>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="dashboard.php?action=clients&edit_client=<?php echo $view_client['id']; ?>" class="btn btn-success"><i class="fa-solid fa-pen-to-square"></i> Edit Client</a>
                    <a href="dashboard.php?action=clients" class="btn">Close</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- View Service Details Modal -->
    <?php if (!empty($view_service)): ?>
    <div id="viewServiceModal" class="modal-overlay" style="display: flex;">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h3><i class="fa-solid fa-layer-group" style="margin-right: 8px;"></i> Service Details</h3>
                <a href="dashboard.php?action=services" class="modal-close">&times;</a>
            </div>
            <div class="modal-body">
                <div class="service-details">
                    <div class="details-grid">
                        <div class="detail-item">
                            <span class="detail-label">Service ID</span>
                            <span class="detail-value">#<?php echo htmlspecialchars($view_service['id']); ?></span>
                        </div>
                        <div class="detail-item full-width">
                            <span class="detail-label">Title</span>
                            <span class="detail-value"><?php echo htmlspecialchars($view_service['title']); ?></span>
                        </div>
                        <div class="detail-item full-width">
                            <span class="detail-label">Short Description</span>
                            <span class="detail-value"><?php echo htmlspecialchars($view_service['description']); ?></span>
                        </div>
                        <div class="detail-item full-width">
                            <span class="detail-label">Detailed Description</span>
                            <span class="detail-value"><?php echo nl2br(htmlspecialchars($view_service['detailed_description'] ?? '')); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Duration</span>
                            <span class="detail-value"><?php echo htmlspecialchars($view_service['duration']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Price Range</span>
                            <span class="detail-value"><?php echo htmlspecialchars($view_service['price_range']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Featured Status</span>
                            <span class="detail-value">
                                <?php if (!empty($view_service['is_featured'])): ?>
                                    <span class="featured-badge"><i class="fa-solid fa-star"></i> Featured Service</span>
                                <?php else: ?>
                                    <span class="normal-badge">Standard Service</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <!-- Permission Fields -->
                        <div class="detail-item full-width">
                            <span class="detail-label">Client Permissions</span>
                            <span class="detail-value">
                                <ul class="permissions-list">
                                    <li>Visible to clients: <span class="<?php echo !empty($view_service['is_visible_to_clients']) ? 'status-active' : 'status-inactive'; ?>"><?php echo !empty($view_service['is_visible_to_clients']) ? 'Yes' : 'No'; ?></span></li>
                                    <li>Show price to clients: <span class="<?php echo !empty($view_service['show_price_to_clients']) ? 'status-active' : 'status-inactive'; ?>"><?php echo !empty($view_service['show_price_to_clients']) ? 'Yes' : 'No'; ?></span></li>
                                    <li>Show description to clients: <span class="<?php echo !empty($view_service['show_description_to_clients']) ? 'status-active' : 'status-inactive'; ?>"><?php echo !empty($view_service['show_description_to_clients']) ? 'Yes' : 'No'; ?></span></li>
                                    <li>Allow client quotes: <span class="<?php echo !empty($view_service['allow_client_quotes']) ? 'status-active' : 'status-inactive'; ?>"><?php echo !empty($view_service['allow_client_quotes']) ? 'Yes' : 'No'; ?></span></li>
                                    <li>Requires admin approval: <span class="<?php echo !empty($view_service['requires_admin_approval']) ? 'status-active' : 'status-inactive'; ?>"><?php echo !empty($view_service['requires_admin_approval']) ? 'Yes' : 'No'; ?></span></li>
                                </ul>
                            </span>
                        </div>
                        
                        <?php if (!empty($view_service['features'])): ?>
                        <div class="detail-item full-width">
                            <span class="detail-label">Features</span>
                            <span class="detail-value">
                                <ul class="features-list">
                                    <?php 
                                    $features = function_exists('pgArrayToArray') ? pgArrayToArray($view_service['features']) : [];
                                    foreach ($features as $feature): ?>
                                        <li><?php echo htmlspecialchars($feature); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Multiple Quotes Section -->
                        <?php 
                        $service_quotes = function_exists('getQuotesByServiceId') ? getQuotesByServiceId($conn, $view_service['id']) : [];
                        if (!empty($service_quotes)): 
                        ?>
                        <div class="detail-item full-width">
                            <span class="detail-label quote-section-title"><i class="fa-solid fa-file-invoice-dollar" style="color: var(--brass-accent); margin-right: 6px;"></i> Quote Configurations (<?php echo count($service_quotes); ?>)</span>
                            <div class="detail-value quote-list-view">
                                <?php 
                                $current_category = '';
                                foreach ($service_quotes as $sq): 
                                    if ($sq['quote_category'] && $sq['quote_category'] != $current_category):
                                        $current_category = $sq['quote_category'];
                                ?>
                                    <h4 class="quote-category-title"><?php echo htmlspecialchars($current_category); ?></h4>
                                <?php endif; ?>
                                <div class="quote-item-view">
                                    <div class="quote-item-header">
                                        <strong><?php echo htmlspecialchars($sq['quote_title']); ?></strong>
                                        <?php if (!empty($sq['is_featured_quote'])): ?>
                                            <span class="featured-badge small"><i class="fa-solid fa-star"></i> Featured</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($sq['quote_code'])): ?>
                                        <div class="quote-code">Code: <?php echo htmlspecialchars($sq['quote_code']); ?></div>
                                    <?php endif; ?>
                                    <p class="quote-description"><?php echo htmlspecialchars($sq['quote_description']); ?></p>
                                    
                                    <div class="quote-pricing">
                                        <strong>Pricing:</strong> 
                                        <?php 
                                        switch($sq['price_type'] ?? '') {
                                            case 'fixed':
                                                 echo (function_exists('formatPrice') ? formatPrice($sq['fixed_price']) : '₹' . number_format($sq['fixed_price'] ?? 0, 2));
                                                break;
                                            case 'range':
                                                echo (function_exists('formatPrice') ? formatPrice($sq['min_price']) : '₹' . number_format($sq['min_price'] ?? 0, 2)) . ' - ' . 
                                                     (function_exists('formatPrice') ? formatPrice($sq['max_price']) : '₹' . number_format($sq['max_price'] ?? 0, 2));
                                                break;
                                            case 'starting_at':
                                                echo 'From ' . (function_exists('formatPrice') ? formatPrice($sq['min_price']) : '₹' . number_format($sq['min_price'] ?? 0, 2));
                                                break;
                                            default:
                                                echo 'Custom';
                                        }
                                        ?>
                                    </div>
                                    
                                    <div class="quote-actions">
                                        <a href="print_quotation.php?id=<?php echo $sq['id']; ?>" target="_blank" class="btn btn-info btn-sm"><i class="fa-solid fa-print"></i> Print</a>
                                        <button type="button" class="btn btn-quote-config btn-sm" onclick="openQuoteModal(<?php echo $view_service['id']; ?>, '<?php echo htmlspecialchars(addslashes($view_service['title'])); ?>', <?php echo htmlspecialchars(json_encode($sq)); ?>)"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($view_service['image_url'])): ?>
                        <div class="detail-item full-width">
                            <span class="detail-label">Image</span>
                            <span class="detail-value">
                                <?php if (strpos($view_service['image_url'], 'data:image') === 0): ?>
                                    <div class="image-preview-large">
                                        <img src="<?php echo htmlspecialchars($view_service['image_url']); ?>" alt="Service Image">
                                    </div>
                                <?php else: ?>
                                    <a href="<?php echo htmlspecialchars($view_service['image_url']); ?>" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Image</a>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="dashboard.php?action=services&edit_service=<?php echo $view_service['id']; ?>" class="btn btn-success"><i class="fa-solid fa-pen-to-square"></i> Edit Service</a>
                    <button type="button" class="btn btn-quote-config" onclick="openQuoteModal(<?php echo $view_service['id']; ?>, '<?php echo htmlspecialchars(addslashes($view_service['title'])); ?>')">
                        <i class="fa-solid fa-plus"></i> Add New Quote
                    </button>
                    <a href="dashboard.php?action=services" class="btn">Close</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- View Inquiry Details Modal -->
    <?php if (!empty($view_inquiry)): ?>
    <div id="viewInquiryModal" class="modal-overlay" style="display: flex;">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h3><i class="fa-solid fa-envelope-open-text" style="margin-right: 8px;"></i> Inquiry Details</h3>
                <a href="dashboard.php?action=inquiries" class="modal-close">&times;</a>
            </div>
            <div class="modal-body">
                <div class="inquiry-details">
                    <div class="details-grid">
                        <div class="detail-item">
                            <span class="detail-label">ID</span>
                            <span class="detail-value">#<?php echo htmlspecialchars($view_inquiry['id']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Name</span>
                            <span class="detail-value"><?php echo htmlspecialchars($view_inquiry['name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email</span>
                            <span class="detail-value">
                                <a href="mailto:<?php echo htmlspecialchars($view_inquiry['email']); ?>">
                                    <?php echo htmlspecialchars($view_inquiry['email']); ?>
                                </a>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone</span>
                            <span class="detail-value"><?php echo htmlspecialchars($view_inquiry['phone'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Organization</span>
                            <span class="detail-value"><?php echo htmlspecialchars($view_inquiry['organization'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Service</span>
                            <span class="detail-value">
                                <?php if (!empty($view_inquiry['service_id'])): ?>
                                    <a href="dashboard.php?action=services&view_service=<?php echo $view_inquiry['service_id']; ?>">
                                        <?php echo htmlspecialchars($view_inquiry['service_title'] ?? $view_inquiry['service_name']); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($view_inquiry['service_name'] ?? 'General Inquiry'); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="status-badge status-<?php echo $view_inquiry['status']; ?>">
                                <?php echo ucfirst($view_inquiry['status']); ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Date</span>
                            <span class="detail-value"><?php echo isset($view_inquiry['created_at']) ? date('d M Y H:i', strtotime($view_inquiry['created_at'])) : 'N/A'; ?></span>
                        </div>
                        <div class="detail-item full-width">
                            <span class="detail-label">Message</span>
                            <div class="detail-value message-box"><?php echo nl2br(htmlspecialchars($view_inquiry['message'])); ?></div>
                        </div>
                        
                        <?php if (!empty($view_inquiry['admin_notes'])): ?>
                        <div class="detail-item full-width">
                            <span class="detail-label">Admin Notes</span>
                            <div class="detail-value notes-box"><?php echo nl2br(htmlspecialchars($view_inquiry['admin_notes'])); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($view_inquiry['follow_up_date'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">Follow-up Date</span>
                            <span class="detail-value"><?php echo date('d M Y', strtotime($view_inquiry['follow_up_date'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Add Note Form -->
                    <div class="add-note-form">
                        <h4>Add Note / Update Status</h4>
                        
                        <form method="POST" action="dashboard.php?action=inquiries&view_inquiry=<?php echo $view_inquiry['id']; ?>">
                            <input type="hidden" name="inquiry_id" value="<?php echo $view_inquiry['id']; ?>">
                            
                            <div class="form-group">
                                <label for="admin_notes">Admin Notes</label>
                                <textarea id="admin_notes" name="admin_notes" rows="4" placeholder="Add your private notes about this inquiry..."><?php echo htmlspecialchars($view_inquiry['admin_notes'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group half">
                                    <label for="follow_up_date">Follow-up Date</label>
                                    <input type="date" id="follow_up_date" name="follow_up_date" value="<?php echo htmlspecialchars($view_inquiry['follow_up_date'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group half">
                                    <label for="update_status">Update Status</label>
                                    <select id="update_status" name="update_status">
                                        <option value="">-- Keep Current Status --</option>
                                        <option value="new" <?php echo $view_inquiry['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                        <option value="pending" <?php echo $view_inquiry['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="contacted" <?php echo $view_inquiry['status'] === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                        <option value="resolved" <?php echo $view_inquiry['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="save_inquiry_notes" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Notes & Updates</button>
                                <a href="dashboard.php?action=inquiries" class="btn">Close</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

      <!-- Edit Client Modal -->
    <?php if (!empty($edit_client)): ?>
    <div id="editClientModal" class="modal-overlay" style="display: flex;">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fa-solid fa-user-pen" style="margin-right: 8px;"></i> Edit Client</h3>
                <a href="dashboard.php?action=clients" class="modal-close">&times;</a>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="client_id" value="<?php echo $edit_client['id']; ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="edit_full_name">Full Name *</label>
                            <input type="text" id="edit_full_name" name="full_name" value="<?php echo htmlspecialchars($edit_client['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email Address *</label>
                            <input type="email" id="edit_email" name="email" value="<?php echo htmlspecialchars($edit_client['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_phone">Phone Number</label>
                            <input type="tel" id="edit_phone" name="phone" value="<?php echo htmlspecialchars($edit_client['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="edit_company_name">Company Name</label>
                            <input type="text" id="edit_company_name" name="company_name" value="<?php echo htmlspecialchars($edit_client['company_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group full-width">
                            <label for="edit_address">Address</label>
                            <textarea id="edit_address" name="address" rows="3"><?php echo htmlspecialchars($edit_client['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit_registration_date">Registration Date</label>
                            <input type="date" id="edit_registration_date" name="registration_date" value="<?php echo $edit_client['registration_date'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="edit_status">Status</label>
                            <select id="edit_status" name="status">
                                <option value="pending" <?php echo $edit_client['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="active" <?php echo $edit_client['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $edit_client['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="update_client" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Update Client</button>
                        <a href="dashboard.php?action=clients" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Edit Service Modal -->
    <?php if (!empty($edit_service)): ?>
    <div id="editServiceModal" class="modal-overlay" style="display: flex;">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h3><i class="fa-solid fa-pen-to-square" style="margin-right: 8px;"></i> Edit Service</h3>
                <a href="dashboard.php?action=services" class="modal-close">&times;</a>
            </div>
            <div class="modal-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="service_id" value="<?php echo $edit_service['id']; ?>">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="edit_title">Service Title *</label>
                            <input type="text" id="edit_title" name="title" value="<?php echo htmlspecialchars($edit_service['title']); ?>" required>
                        </div>
                        <div class="form-group full-width">
                            <label for="edit_description">Short Description </label>
                            <textarea id="edit_description" name="description" rows="2" required><?php echo htmlspecialchars($edit_service['description']); ?></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label for="edit_detailed_description">Detailed Description</label>
                            <textarea id="edit_detailed_description" name="detailed_description" rows="4"><?php echo htmlspecialchars($edit_service['detailed_description'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit_duration_min">Min Duration (months) *</label>
                            <input type="number" id="edit_duration_min" name="duration_min" value="<?php echo $edit_service['duration_min'] ?? 3; ?>" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_duration_max">Max Duration (months) *</label>
                            <input type="number" id="edit_duration_max" name="duration_max" value="<?php echo $edit_service['duration_max'] ?? 24; ?>" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_price_range_min">Min Price (₹)</label>
                            <input type="number" id="edit_price_range_min" name="price_range_min" value="<?php echo $edit_service['price_range_min'] ?? 0; ?>" min="10" step="10">
                        </div>
                        <div class="form-group">
                            <label for="edit_price_range_max">Max Price (₹)</label>
                            <input type="number" id="edit_price_range_max" name="price_range_max" value="<?php echo $edit_service['price_range_max'] ?? 0; ?>" min="10" step="10">
                        </div>
                        <div class="form-group full-width">
                            <label for="edit_features">Features (comma-separated)</label>
                            <input type="text" id="edit_features" name="features" value="<?php echo htmlspecialchars($edit_service['features_list'] ?? ''); ?>">
                        </div>
                        
                        <!-- Image Upload Section -->
                        <div class="form-group full-width">
                            <label>Service Image</label>
                            <div class="image-upload-container">
                                <div class="image-preview" id="editImagePreview">
                                    <?php if (!empty($edit_service['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($edit_service['image_url']); ?>" alt="Current Image" class="image-preview-img">
                                    <?php else: ?>
                                        <div class="image-preview-placeholder">
                                            <i class="fa-solid fa-image" style="font-size: 28px; color: var(--text-tertiary); margin-bottom: 6px;"></i>
                                            <div>No image selected</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="upload-controls">
                                    <?php if (!empty($edit_service['image_url'])): ?>
                                    <div class="current-image-info">
                                        <strong>Current Image:</strong>
                                        <?php if (strpos($edit_service['image_url'], 'data:image') === 0): ?>
                                            <span>Base64 Image (stored in database)</span>
                                        <?php else: ?>
                                            <a href="<?php echo htmlspecialchars($edit_service['image_url']); ?>" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Image</a>
                                        <?php endif; ?>
                                        <div class="remove-image-checkbox">
                                            <input type="checkbox" id="remove_current_image" name="remove_image" value="1">
                                            <label for="remove_current_image">Remove current image</label>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="upload-section">
                                        <label class="upload-button">
                                            <i class="fa-solid fa-cloud-arrow-up"></i> Upload New Image
                                            <input type="file" id="edit_service_image" name="service_image" accept="image/*" onchange="previewEditImage(this)">
                                        </label>
                                        <div class="image-info">Max size: 2MB • Formats: JPG, PNG, GIF, WebP</div>
                                    </div>
                                    
                                    <div class="or-divider">OR</div>
                                    
                                    <div class="url-section">
                                        <label>Enter Image URL:</label>
                                        <input type="url" id="edit_image_url" name="image_url" value="<?php echo !empty($edit_service['image_url']) && strpos($edit_service['image_url'], 'data:image') !== 0 ? htmlspecialchars($edit_service['image_url']) : ''; ?>" placeholder="https://example.com/image.jpg" class="url-input">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_is_featured">Featured Service</label>
                            <select id="edit_is_featured" name="is_featured">
                                <option value="false" <?php echo empty($edit_service['is_featured']) ? 'selected' : ''; ?>>No</option>
                                <option value="true" <?php echo !empty($edit_service['is_featured']) ? 'selected' : ''; ?>>Yes (★ Featured)</option>
                            </select>
                        </div>

                        <!-- Permission Fields (pre-filled) -->
                        <div class="form-group permissions-group">
                            <div class="form-checkbox">
                                <label>
                                    <input type="checkbox" id="edit_is_visible_to_clients" name="is_visible_to_clients" value="1" <?php echo !empty($edit_service['is_visible_to_clients']) ? 'checked' : ''; ?>>
                                    <span>Visible to clients</span>
                                </label>
                            </div>
                            <div class="form-checkbox">
                                <label>
                                    <input type="checkbox" id="edit_show_price_to_clients" name="show_price_to_clients" value="1" <?php echo !empty($edit_service['show_price_to_clients']) ? 'checked' : ''; ?>>
                                    <span>Show price to clients</span>
                                </label>
                            </div>
                            <div class="form-checkbox">
                                <label>
                                    <input type="checkbox" id="edit_show_description_to_clients" name="show_description_to_clients" value="1" <?php echo !empty($edit_service['show_description_to_clients']) ? 'checked' : ''; ?>>
                                    <span>Show full description to clients</span>
                                </label>
                            </div>
                            <div class="form-checkbox">
                                <label>
                                    <input type="checkbox" id="edit_allow_client_quotes" name="allow_client_quotes" value="1" <?php echo !empty($edit_service['allow_client_quotes']) ? 'checked' : ''; ?>>
                                    <span>Allow clients to request quotes</span>
                                </label>
                            </div>
                            <div class="form-checkbox">
                                <label>
                                    <input type="checkbox" id="edit_requires_admin_approval" name="requires_admin_approval" value="1" <?php echo !empty($edit_service['requires_admin_approval']) ? 'checked' : ''; ?>>
                                    <span>Require admin approval for quotes</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="edit_admin_notes">Admin Notes (internal)</label>
                            <textarea id="edit_admin_notes" name="admin_notes" rows="3"><?php echo htmlspecialchars($edit_service['admin_notes'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="edit_meta_title">Meta Title (SEO)</label>
                            <input type="text" id="edit_meta_title" name="meta_title" value="<?php echo htmlspecialchars($edit_service['meta_title'] ?? ''); ?>" placeholder="SEO title">
                        </div>
                        <div class="form-group">
                            <label for="edit_meta_description">Meta Description</label>
                            <textarea id="edit_meta_description" name="meta_description" rows="2" placeholder="SEO description"><?php echo htmlspecialchars($edit_service['meta_description'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit_meta_keywords">Meta Keywords (comma-separated)</label>
                            <input type="text" id="edit_meta_keywords" name="meta_keywords" value="<?php echo htmlspecialchars($edit_service['meta_keywords'] ?? ''); ?>" placeholder="keyword1, keyword2">
                        </div>
                        <div class="form-group">
                            <label for="edit_sort_order">Sort Order</label>
                            <input type="number" id="edit_sort_order" name="sort_order" min="0" value="<?php echo intval($edit_service['sort_order'] ?? 0); ?>">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="update_service" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Update Service</button>
                        <a href="dashboard.php?action=services" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // ========== TAB MANAGEMENT ==========
        function showTab(tabName, event) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + 'Tab').classList.add('active');
            
            // Add active class to clicked button
            if (event && event.target) {
                event.target.classList.add('active');
            }
            
            // Update URL
            const url = new URL(window.location.href);
            if (tabName !== 'dashboard') {
                url.searchParams.set('action', tabName);
            } else {
                url.searchParams.delete('action');
            }
            url.searchParams.delete('view_client');
            url.searchParams.delete('edit_client');
            url.searchParams.delete('view_service');
            url.searchParams.delete('edit_service');
            url.searchParams.delete('view_inquiry');
            window.history.pushState({}, '', url);
            
            // Reload page to show correct content
            window.location.href = url.toString();
        }

        // ========== MODAL FUNCTIONS ==========
        function openModal(modalId) {
            const modalEl = document.getElementById(modalId);
            if (!modalEl) return;
            modalEl.classList.add('active');
            modalEl.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Focus inside modal for accessibility
            const firstInput = modalEl.querySelector('input, select, textarea, button:not(.modal-close)');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 50);
            }
        }

        function closeModal(modalId) {
            const modalEl = document.getElementById(modalId);
            if (!modalEl) return;
            modalEl.classList.remove('active');
            modalEl.style.display = 'none';
            
            // Check if any other modal is still visible
            const anyOpen = document.querySelectorAll('.modal-overlay.active, .modal-overlay[style*="display: flex"]');
            if (anyOpen.length === 0) {
                document.body.style.overflow = 'auto';
            }
        }

        // Close modal when clicking outside overlay
        document.addEventListener('click', function(event) {
            if (event.target && event.target.classList && event.target.classList.contains('modal-overlay')) {
                event.target.classList.remove('active');
                event.target.style.display = 'none';
                
                const anyOpen = document.querySelectorAll('.modal-overlay.active, .modal-overlay[style*="display: flex"]');
                if (anyOpen.length === 0) {
                    document.body.style.overflow = 'auto';
                }
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const openModals = document.querySelectorAll('.modal-overlay.active, .modal-overlay[style*="display: flex"]');
                openModals.forEach(function(m) {
                    m.classList.remove('active');
                    m.style.display = 'none';
                });
                document.body.style.overflow = 'auto';
            }
        });

        // ========== QUOTE FUNCTIONS ==========
        function openQuoteModal(serviceId, serviceName, quoteData = null) {
            document.getElementById('quote_service_id').value = serviceId || '';
            document.getElementById('quoteModalTitle').innerHTML = quoteData ? 'Edit Quote for: ' + serviceName : 'Add New Quote for: ' + (serviceName || 'a Service');
            
            // Show/hide service select row
            const serviceSelectRow = document.getElementById('service_select_row');
            if (serviceId) {
                serviceSelectRow.style.display = 'none';
            } else {
                serviceSelectRow.style.display = 'block';
                document.getElementById('quote_service_select').value = '';
            }
            
            // Reset form
            document.getElementById('quoteForm').reset();
            document.getElementById('quote_id').value = '';
            
            // Reset price fields visibility
            document.getElementById('fixed_price_field').style.display = 'block';
            document.getElementById('range_price_fields').style.display = 'none';
            document.getElementById('discount_fields').style.display = 'none';
            
            // Reset all custom fields
            document.querySelectorAll('#customFieldsContainer input').forEach(input => {
                input.value = '';
            });
            
            // If editing existing quote
            if (quoteData && quoteData !== 'null') {
                document.getElementById('quote_id').value = quoteData.id || '';
                document.getElementById('quote_title').value = quoteData.quote_title || '';
                document.getElementById('quote_description').value = quoteData.quote_description || '';
                document.getElementById('price_type').value = quoteData.price_type || 'fixed';
                document.getElementById('quote_category').value = quoteData.quote_category || '';
                document.getElementById('quote_code').value = quoteData.quote_code || '';
                document.getElementById('sort_order').value = quoteData.sort_order || '0';
                togglePriceFields();
                
                // Set price values
                if (quoteData.price_type === 'fixed') {
                    document.getElementById('fixed_price').value = quoteData.fixed_price || '';
                } else {
                    document.getElementById('min_price').value = quoteData.min_price || '';
                    document.getElementById('max_price').value = quoteData.max_price || '';
                }
                
                // Set duration
                document.getElementById('duration_type').value = quoteData.duration_type || 'fixed_range';
                document.getElementById('min_duration').value = quoteData.min_duration || '1';
                document.getElementById('max_duration').value = quoteData.max_duration || '12';
                
                // Set inclusions/exclusions
                if (quoteData.inclusions && quoteData.inclusions !== '{}') {
                    try {
                        let inclusions = JSON.parse(quoteData.inclusions);
                        if (Array.isArray(inclusions)) {
                            document.getElementById('inclusions').value = inclusions.join('\n');
                        }
                    } catch (e) {
                        let inclusions = String(quoteData.inclusions).replace(/[{}"]/g, '').split(',');
                        document.getElementById('inclusions').value = inclusions.join('\n');
                    }
                }
                
                if (quoteData.exclusions && quoteData.exclusions !== '{}') {
                    try {
                        let exclusions = JSON.parse(quoteData.exclusions);
                        if (Array.isArray(exclusions)) {
                            document.getElementById('exclusions').value = exclusions.join('\n');
                        }
                    } catch (e) {
                        let exclusions = String(quoteData.exclusions).replace(/[{}"]/g, '').split(',');
                        document.getElementById('exclusions').value = exclusions.join('\n');
                    }
                }
                
                if (quoteData.prerequisites && quoteData.prerequisites !== '{}') {
                    try {
                        let prerequisites = JSON.parse(quoteData.prerequisites);
                        if (Array.isArray(prerequisites)) {
                            document.getElementById('prerequisites').value = prerequisites.join('\n');
                        }
                    } catch (e) {
                        let prerequisites = String(quoteData.prerequisites).replace(/[{}"]/g, '').split(',');
                        document.getElementById('prerequisites').value = prerequisites.join('\n');
                    }
                }
                
                if (quoteData.documents_required && quoteData.documents_required !== '{}') {
                    try {
                        let documents = JSON.parse(quoteData.documents_required);
                        if (Array.isArray(documents)) {
                            document.getElementById('documents_required').value = documents.join('\n');
                        }
                    } catch (e) {
                        let documents = String(quoteData.documents_required).replace(/[{}"]/g, '').split(',');
                        document.getElementById('documents_required').value = documents.join('\n');
                    }
                }
                
                // Set discount
                document.getElementById('has_discount').checked = quoteData.has_discount == 't' || quoteData.has_discount == '1' || quoteData.has_discount === true;
                toggleDiscountFields();
                if (document.getElementById('has_discount').checked) {
                    document.getElementById('discount_percentage').value = quoteData.discount_percentage || '';
                    document.getElementById('discount_description').value = quoteData.discount_description || '';
                }
                
                // Set featured
                document.getElementById('is_featured_quote').checked = quoteData.is_featured_quote == 't' || quoteData.is_featured_quote == '1' || quoteData.is_featured_quote === true;
                
                // Set visible to clients
                document.getElementById('is_visible_to_clients').checked = quoteData.is_visible_to_clients == 't' || quoteData.is_visible_to_clients == '1' || quoteData.is_visible_to_clients === true;
                
                // Populate custom field values
                if (quoteData.custom_values) {
                    quoteData.custom_values.forEach(function(cv) {
                        const input = document.getElementById('custom_' + cv.field_id);
                        if (input) {
                            input.value = cv.field_value;
                        }
                    });
                }
            }
            
            openModal('quoteModal');
        }

        // Make sure the service select updates the hidden field
        document.getElementById('quote_service_select')?.addEventListener('change', function() {
            document.getElementById('quote_service_id').value = this.value;
        });

        function togglePriceFields() {
            const type = document.getElementById('price_type').value;
            document.getElementById('fixed_price_field').style.display = type === 'fixed' ? 'block' : 'none';
            document.getElementById('range_price_fields').style.display = (type === 'range' || type === 'starting_at') ? 'block' : 'none';
        }
        
        function toggleDiscountFields() {
            document.getElementById('discount_fields').style.display = 
                document.getElementById('has_discount').checked ? 'block' : 'none';
        }

        // ========== CUSTOM FIELD FUNCTIONS ==========
        function openEditCustomFieldModal(fieldData) {
            document.getElementById('custom_field_id').value = fieldData.id;
            document.getElementById('field_name').value = fieldData.field_name;
            document.getElementById('field_type').value = fieldData.field_type;
            document.getElementById('field_options').value = fieldData.field_options || '';
            document.getElementById('sort_order').value = fieldData.sort_order || 0;
            document.getElementById('required').checked = fieldData.required == 't' || fieldData.required == '1' || fieldData.required === true;
            document.getElementById('customFieldModalTitle').innerText = 'Edit Custom Field';
            document.getElementById('saveCustomFieldBtn').name = 'update_custom_field';
            openModal('addCustomFieldModal');
        }

        // Reset custom field modal when opened for add
        document.getElementById('addCustomFieldModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                document.getElementById('customFieldForm').reset();
                document.getElementById('custom_field_id').value = '';
                document.getElementById('customFieldModalTitle').innerText = 'Add Custom Field';
                document.getElementById('saveCustomFieldBtn').name = 'add_custom_field';
            }
        });
        // ========== IMAGE PREVIEW ==========
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const urlInput = document.getElementById('image_url');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" class="preview-image">';
                    if (urlInput) urlInput.value = '';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function previewEditImage(input) {
            const preview = document.getElementById('editImagePreview');
            const urlInput = document.getElementById('edit_image_url');
            const removeCheckbox = document.getElementById('remove_current_image');
            
            if (removeCheckbox) {
                removeCheckbox.checked = false;
            }
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" class="preview-image">';
                    if (urlInput) urlInput.value = '';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Handle remove image checkbox
        const removeCheckbox = document.getElementById('remove_current_image');
        if (removeCheckbox) {
            removeCheckbox.addEventListener('change', function() {
                const urlInput = document.getElementById('edit_image_url');
                const fileInput = document.getElementById('edit_service_image');
                const preview = document.getElementById('editImagePreview');
                
                if (this.checked) {
                    if (urlInput) {
                        urlInput.value = '';
                        urlInput.disabled = true;
                    }
                    if (fileInput) {
                        fileInput.value = '';
                        fileInput.disabled = true;
                    }
                    preview.innerHTML = '<div class="image-preview-placeholder"><span class="icon">📷</span><div>Image will be removed</div></div>';
                } else {
                    if (urlInput) urlInput.disabled = false;
                    if (fileInput) fileInput.disabled = false;
                    <?php if (!empty($edit_service['image_url'])): ?>
                    preview.innerHTML = '<img src="<?php echo htmlspecialchars($edit_service['image_url']); ?>" alt="Current Image" class="preview-image">';
                    <?php endif; ?>
                }
            });
        }

        // Clear URL input when file is selected
        const editServiceImage = document.getElementById('edit_service_image');
        if (editServiceImage) {
            editServiceImage.addEventListener('change', function() {
                const urlInput = document.getElementById('edit_image_url');
                const removeCheckbox = document.getElementById('remove_current_image');
                if (urlInput) {
                    urlInput.value = '';
                    urlInput.disabled = false;
                }
                if (removeCheckbox) {
                    removeCheckbox.checked = false;
                    removeCheckbox.disabled = false;
                }
            });
        }

        // Clear file input when URL is entered
        const editImageUrl = document.getElementById('edit_image_url');
        if (editImageUrl) {
            editImageUrl.addEventListener('input', function() {
                const fileInput = document.getElementById('edit_service_image');
                const removeCheckbox = document.getElementById('remove_current_image');
                if (fileInput && this.value.trim() !== '') {
                    fileInput.value = '';
                    fileInput.disabled = false;
                }
                if (removeCheckbox) {
                    removeCheckbox.checked = false;
                    removeCheckbox.disabled = false;
                }
            });
        }

        // ========== FORM VALIDATION ==========
        document.getElementById('addClientForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            if (password.length < 6) {
                alert('Password must be at least 6 characters long');
                e.preventDefault();
            }
        });

        document.getElementById('addServiceForm')?.addEventListener('submit', function(e) {
            const duration_min = parseInt(document.getElementById('duration_min').value);
            const duration_max = parseInt(document.getElementById('duration_max').value);
            
            if (duration_min > duration_max) {
                alert('Minimum duration cannot be greater than maximum duration');
                e.preventDefault();
                return false;
            }
            
            const price_min = parseFloat(document.getElementById('price_range_min').value) || 0;
            const price_max = parseFloat(document.getElementById('price_range_max').value) || 0;
            
            if (price_min > price_max && price_max > 0) {
                alert('Minimum price cannot be greater than maximum price');
                e.preventDefault();
                return false;
            }
            
            const fileInput = document.getElementById('service_image');
            if (fileInput.files[0]) {
                const maxSize = 10 * 1024 * 1024;
                if (fileInput.files[0].size > maxSize) {
                    alert('File size must be less than 10MB');
                    e.preventDefault();
                    return false;
                }
            }
        });

        // ========== AUTO-HIDE MESSAGES ==========
        setTimeout(function() {
            document.querySelectorAll('.success-message, .error-message').forEach(function(message) {
                message.style.transition = 'opacity 0.5s';
                message.style.opacity = '0';
                setTimeout(function() {
                    message.style.display = 'none';
                }, 500);
            });
        }, 5000);

        // ========== AUTO-SUBMIT SEARCH ==========
        document.querySelectorAll('.search-box input').forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    this.form.submit();
                }
            });
        });

        // ========== CHECK URL PARAMETERS ON LOAD ==========
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile Sidebar Toggle
            const toggleBtn = document.getElementById('adminSidebarToggle');
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const closeBtn = document.getElementById('sidebarCloseBtn');

            function openSidebar() {
                if (sidebar) sidebar.classList.add('mobile-open');
                if (overlay) overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                if (sidebar) sidebar.classList.remove('mobile-open');
                if (overlay) overlay.classList.remove('active');
                document.body.style.overflow = 'auto';
            }

            if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
            if (overlay) overlay.addEventListener('click', closeSidebar);

            // Close on escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeSidebar();
                }
            });

            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.get('view_client')) {
                openModal('viewClientModal');
            }
            if (urlParams.get('edit_client')) {
                openModal('editClientModal');
            }
            if (urlParams.get('view_service')) {
                openModal('viewServiceModal');
            }
            if (urlParams.get('edit_service')) {
                openModal('editServiceModal');
            }
            if (urlParams.get('view_inquiry')) {
                openModal('viewInquiryModal');
            }

            // ========== SCROLL FLY-IN / FLY-OUT ANIMATIONS (INTERSECTION OBSERVER) ==========
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            
            // Mark target elements for scroll animations
            const animCandidates = document.querySelectorAll('.dashboard-page-header, .quick-stat-card, .card, .service-card, .table-container, .quick-actions');
            
            animCandidates.forEach((el, index) => {
                if (prefersReducedMotion) {
                    el.classList.add('in-view');
                    return;
                }
                
                el.classList.add('animate-on-scroll');
                
                // Assign subtle direction & staggered delay
                if (el.classList.contains('quick-stat-card')) {
                    el.classList.add('fade-up');
                    const staggerNum = (index % 8) + 1;
                    el.classList.add('stagger-' + staggerNum);
                } else if (el.classList.contains('card') || el.classList.contains('service-card')) {
                    el.classList.add(index % 2 === 0 ? 'fade-left' : 'fade-right');
                } else {
                    el.classList.add('fade-up');
                }
            });

            if (!prefersReducedMotion && 'IntersectionObserver' in window) {
                const observerOptions = {
                    root: null,
                    rootMargin: '0px 0px -40px 0px',
                    threshold: 0.12
                };

                const scrollObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('in-view');
                            entry.target.classList.remove('out-view');
                        } else {
                            // Subtle exit state if scrolled far away
                            if (entry.boundingClientRect.top > 0) {
                                entry.target.classList.remove('in-view');
                                entry.target.classList.add('out-view');
                            }
                        }
                    });
                }, observerOptions);

                document.querySelectorAll('.animate-on-scroll').forEach(el => {
                    scrollObserver.observe(el);
                });
            } else if (!prefersReducedMotion) {
                // Fallback for browsers without IntersectionObserver
                document.querySelectorAll('.animate-on-scroll').forEach(el => {
                    el.classList.add('in-view');
                });
            }
        });
    </script>
</body>
</html>
<?php 
if (isset($conn)) {
    $conn = null;
}
?>
