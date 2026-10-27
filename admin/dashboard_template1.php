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
    <title>Admin Dashboard - P.S Pakpi Enterprises</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="admin-dashboard">
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="admin-brand">
                <div class="admin-logo">PSP</div>
                <div class="admin-brand-text">
                    <h1>P.S Pakpi Enterprises</h1>
                    <p>Admin Dashboard</p>
                </div>
            </div>
            <div class="admin-user-info">
                <span class="user-welcome">Welcome, <?php echo htmlspecialchars($_SESSION['admin_fullname'] ?? 'Administrator'); ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="admin-main-container">
        <?php echo $message; ?>
        
        <div class="dashboard-grid">
            <!-- Sidebar Menu -->
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="<?php echo $action === 'dashboard' ? 'active' : ''; ?>"><span class="icon">📊</span> Dashboard</a></li>
                <li><a href="dashboard.php?action=clients" class="<?php echo $action === 'clients' ? 'active' : ''; ?>"><span class="icon">👥</span> Client Management</a></li>
                <li><a href="dashboard.php?action=services" class="<?php echo $action === 'services' ? 'active' : ''; ?>"><span class="icon">⚙️</span> Service Management</a></li>
                <li><a href="#" onclick="openModal('addClientModal'); return false;"><span class="icon">➕</span> Add New Client</a></li>
                <li><a href="#" onclick="openModal('addServiceModal'); return false;"><span class="icon">➕</span> Add New Service</a></li>
                <li><a href="dashboard.php?action=inquiries" class="<?php echo $action === 'inquiries' ? 'active' : ''; ?>"><span class="icon">📩</span> Service Inquiries</a></li>
                <l<li><a href="#" onclick="openQuoteModal(null, 'Add New Quote'); return false;"><span class="icon">💰</span> Add Quote Config</a></li>
                <li><a href="reports.php"><span class="icon">📈</span> Reports</a></li>
            </ul>

            <!-- Main Content -->
            <div class="dashboard-content">
                <!-- Tab Navigation -->
                <div class="tab-navigation">
                    <button class="tab-button <?php echo $action === 'dashboard' ? 'active' : ''; ?>" onclick="showTab('dashboard', event)">Dashboard</button>
                    <button class="tab-button <?php echo $action === 'clients' ? 'active' : ''; ?>" onclick="showTab('clients', event)">Clients</button>
                    <button class="tab-button <?php echo $action === 'services' ? 'active' : ''; ?>" onclick="showTab('services', event)">Services</button>
                    <button class="tab-button <?php echo $action === 'inquiries' ? 'active' : ''; ?>" onclick="showTab('inquiries', event)">Inquiries</button>
                </div>

                <!-- Dashboard Tab -->
                <div id="dashboardTab" class="tab-content <?php echo $action === 'dashboard' ? 'active' : ''; ?>">
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

                    <div class="quick-actions">
                        <h3>Quick Actions</h3>
                        <div class="action-buttons">
                            <button class="btn btn-primary" onclick="window.location.href='dashboard.php?action=clients'">
                                <span class="icon">👥</span> Manage Clients
                            </button>
                            <button class="btn btn-success" onclick="window.location.href='dashboard.php?action=services'">
                                <span class="icon">⚙️</span> Manage Services
                            </button>
							<button class="btn btn-quote-config" onclick="openQuoteModal(null, 'Add New Quote')">
								<span class="icon">💰</span> Add Quote
							</button>
                            <button class="btn btn-secondary" onclick="window.location.href='dashboard.php?action=inquiries'">
                                <span class="icon">📩</span> View Inquiries
                            </button>
                        </div>
                    </div>

                    <!-- Reminder Card -->
                    <div class="card reminder-card">
                        <div class="card-header">
                            <h3><span class="icon">⏰</span> Upcoming Follow-ups</h3>
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

                    <div class="dashboard-grid-2col">
                        <!-- Recent Clients -->
                        <div class="card">
                            <div class="card-header">
                                <h3><span class="icon">👥</span> Recent Clients</h3>
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
                                <h3><span class="icon">⚙️</span> Recent Services</h3>
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
                                                    <span class="quote-indicator"><?php echo $quote_count; ?> 💰</span>
                                                <?php endif; ?>
                                                <?php if (empty($service['is_visible_to_clients'])): ?>
                                                    <span class="status-badge status-inactive small">Hidden</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($service['is_featured'])): ?>
                                                <span class="featured-badge">★</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Recent Inquiries -->
                        <div class="card full-width">
                            <div class="card-header">
                                <h3><span class="icon">📩</span> Recent Inquiries</h3>
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
                                <span class="icon">➕</span> Add Client
                            </button>
                        </div>
                    </div>

                    <div class="table-container">
                        <?php if (empty($clients)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">📭</div>
                                <h3>No clients found</h3>
                                <p><?php echo !empty($search) ? 'Try adjusting your search criteria' : 'Add your first client to get started'; ?></p>
                                <button class="btn btn-primary mt-20" onclick="openModal('addClientModal')">
                                    <span class="icon">➕</span> Add Your First Client
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
                                                    <a href="dashboard.php?action=clients&view_client=<?php echo $client['id']; ?>" class="btn btn-primary btn-sm"><span class="icon">👁️</span> View</a>
                                                    <a href="dashboard.php?action=clients&edit_client=<?php echo $client['id']; ?>" class="btn btn-success btn-sm"><span class="icon">✏️</span> Edit</a>
                                                    <form method="POST" class="inline-form">
                                                        <input type="hidden" name="reset_id" value="<?php echo $client['id']; ?>">
                                                        <button type="submit" name="reset_password" class="btn btn-warning btn-sm" onclick="return confirm('Reset password to default (welcome123)?')"><span class="icon">🔑</span> Reset PW</button>
                                                    </form>
                                                    <form method="POST" class="inline-form">
                                                        <input type="hidden" name="delete_id" value="<?php echo $client['id']; ?>">
                                                        <button type="submit" name="delete_client" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this client?')"><span class="icon">🗑️</span> Delete</button>
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
                                <span class="icon">➕</span> Add Service
                            </button>
                        </div>
                    </div>

                    <div class="services-list">
                        <?php if (empty($services)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">⚙️</div>
                                <h3>No services found</h3>
                                <p><?php echo !empty($search) ? 'Try adjusting your search criteria' : 'Add your first service to get started'; ?></p>
                                <button class="btn btn-success mt-20" onclick="openModal('addServiceModal')">
                                    <span class="icon">➕</span> Add Your First Service
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
                                                <span class="quote-indicator"><?php echo $quote_count; ?> Quote<?php echo $quote_count > 1 ? 's' : ''; ?> 💰</span>
                                            <?php endif; ?>
                                            <?php if (empty($service['is_visible_to_clients'])): ?>
                                                <span class="status-badge status-inactive">Hidden</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($service['is_featured'])): ?>
                                            <span class="featured-badge">★ Featured</span>
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
                                            <span class="icon">⏱️</span>
                                            <span><?php echo htmlspecialchars($service['duration'] ?? ''); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <span class="icon">💰</span>
                                            <span><?php echo htmlspecialchars($service['price_range'] ?? ''); ?></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Multiple Quotes Section -->
									<!-- Multiple Quotes Section - Modern Cards -->
									<?php if (!empty($service_quotes)): ?>
									<div class="modern-quote-section">
										<h4 class="modern-quote-title">📋 Available Packages</h4>
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
														<span class="featured-tag">★ Featured</span>
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
													<a href="print_quotation.php?id=<?php echo $sq['id']; ?>" target="_blank" class="btn btn-info btn-sm" title="Print Quotation">🖨️ Print</a>
													<button type="button" class="btn btn-primary btn-sm" onclick="openQuoteModal(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars(addslashes($service['title'])); ?>', <?php echo htmlspecialchars(json_encode($sq)); ?>)">✏️ Edit</button>
													<form method="POST" class="inline-form">
														<input type="hidden" name="delete_quote_id" value="<?php echo $sq['id']; ?>">
														<button type="submit" name="delete_quote" class="btn btn-danger btn-sm" onclick="return confirm('Delete this quote?')">🗑️ Delete</button>
													</form>
												</div>
											</div>
											<?php endforeach; ?>
										</div>
									</div>
                                    <?php endif; ?>
                                    
                                  <div class="service-actions">
									<a href="dashboard.php?action=services&view_service=<?php echo $service['id']; ?>" class="btn btn-info btn-sm">👁️ View</a>
									<a href="dashboard.php?action=services&edit_service=<?php echo $service['id']; ?>" class="btn btn-primary btn-sm">✏️ Edit</a>
									
									<button type="button" class="btn btn-success btn-sm" onclick="openQuoteModal(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars(addslashes($service['title'])); ?>')">
										💰 Add Quote
									</button>
									
									<form method="POST" class="inline-form">
										<input type="hidden" name="delete_service_id" value="<?php echo $service['id']; ?>">
										<button type="submit" name="delete_service" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this service?')">🗑️ Delete</button>
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
                                <div class="empty-state-icon">📭</div>
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
                                                    <a href="dashboard.php?action=inquiries&view_inquiry=<?php echo $inquiry['id']; ?>" class="btn btn-primary btn-sm"><span class="icon">👁️</span> View</a>
													<a href="mailto:<?php echo htmlspecialchars($inquiry['email']); ?>?subject=Regarding your inquiry for <?php echo urlencode($inquiry['service_name'] ?? 'our services'); ?>" class="btn btn-info btn-sm" title="Send Email">📧 Email</a>
                                                    
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
                                                        <button type="submit" name="delete_inquiry" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this inquiry?')">🗑️ Delete</button>
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

    <!-- Add Client Modal -->
    <div id="addClientModal" class="modal-overlay" style="display: none;">
        <div class="modal">
            <div class="modal-header">
                <h3><span class="icon">➕</span> Add New Client</h3>
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
                            <span class="icon">➕</span> Add Client
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
                <h3><span class="icon">➕</span> Add New Service</h3>
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
                            <label for="description">Short Description *</label>
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
                            <span class="icon">➕</span> Add Service
                        </button>
                        <button type="button" class="btn" onclick="closeModal('addServiceModal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quote Modal (updated with service dropdown) -->
<!-- Quote Modal (updated with validation) -->
    <div id="quoteModal" class="modal-overlay" style="display: none;">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h3><span class="sidebar-icon">💰</span> <span id="quoteModalTitle">Configure Service Quote</span></h3>
                <button class="modal-close" onclick="closeModal('quoteModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="quoteForm">
                    <input type="hidden" name="service_id" id="quote_service_id" value="">
                    <input type="hidden" name="id" id="quote_id" value="">
                    
                    <!-- Service selection dropdown -->
                    <div class="form-group" id="service_select_row" style="display: none;">
                        <label for="quote_service_select">Select Service *</label>
                        <!-- FIX: removed name="service_id" to avoid overwriting hidden input -->
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
                        <label for="quote_title">Quote Title *</label>
                        <input type="text" id="quote_title" name="quote_title" required placeholder="e.g., Wedding Cake Package">
                    </div>
                    
                    <div class="form-group">
                        <label for="quote_description">Short Description *</label>
                        <textarea id="quote_description" name="quote_description" rows="2" required placeholder="Brief description"></textarea>
                    </div>
                    
                    <!-- Pricing Structure -->
                    <div class="pricing-config">
                        <h4>💰 Pricing Configuration</h4>
                        
                        <div class="form-group">
                            <label for="price_type">Pricing Type *</label>
                            <select id="price_type" name="price_type" required onchange="togglePriceFields()">
                                <option value="fixed">Fixed Price</option>
                                <option value="range">Price Range (Min - Max)</option>
                                <option value="starting_at">Starting At</option>
                                <option value="custom">Custom (Per Project)</option>
                            </select>
                        </div>
                        
                        <div id="fixed_price_field" class="form-group">
                            <label for="fixed_price">Fixed Price ($) *</label>
                            <input type="number" id="fixed_price" name="fixed_price" step="0.01" min="0">
                        </div>
                        
                        <div id="range_price_fields" style="display: none;">
                            <div class="form-row">
                                <div class="form-group half">
                                    <label for="min_price">Minimum Price ($)</label>
                                    <input type="number" id="min_price" name="min_price" step="0.01" min="0">
                                </div>
                                <div class="form-group half">
                                    <label for="max_price">Maximum Price ($)</label>
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
                        <h4>✓ Inclusions & Exclusions</h4>
                        
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
                        <h4>📋 Requirements</h4>
                        
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
                                <span>💸 Apply Discount</span>
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
                                <span>⭐ Mark as Featured Quote (Shown prominently)</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="save_quote" id="saveQuoteBtn" class="btn btn-success">
                            <span class="sidebar-icon">💰</span> Save Quote Configuration
                        </button>
                        <button type="button" class="btn" onclick="closeModal('quoteModal')">Cancel</button>
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
                <h3><span class="icon">👁️</span> Client Details</h3>
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
                    <a href="dashboard.php?action=clients&edit_client=<?php echo $view_client['id']; ?>" class="btn btn-success"><span class="icon">✏️</span> Edit Client</a>
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
                <h3><span class="icon">👁️</span> Service Details</h3>
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
                                    <span class="featured-badge">★ Featured Service</span>
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
                            <span class="detail-label quote-section-title">💰 Quote Configurations (<?php echo count($service_quotes); ?>)</span>
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
                                            <span class="featured-badge small">★ Featured</span>
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
                                        <a href="print_quotation.php?id=<?php echo $sq['id']; ?>" target="_blank" class="btn btn-info btn-sm">🖨️ Print</a>
                                        <button type="button" class="btn btn-quote-config btn-sm" onclick="openQuoteModal(<?php echo $view_service['id']; ?>, '<?php echo htmlspecialchars(addslashes($view_service['title'])); ?>', <?php echo htmlspecialchars(json_encode($sq)); ?>)">✏️ Edit</button>
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
                                    <a href="<?php echo htmlspecialchars($view_service['image_url']); ?>" target="_blank">View Image</a>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="dashboard.php?action=services&edit_service=<?php echo $view_service['id']; ?>" class="btn btn-success"><span class="icon">✏️</span> Edit Service</a>
                    <button type="button" class="btn btn-quote-config" onclick="openQuoteModal(<?php echo $view_service['id']; ?>, '<?php echo htmlspecialchars(addslashes($view_service['title'])); ?>')">
                        <span class="icon">💰</span> Add New Quote
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
                <h3><span class="icon">📩</span> Inquiry Details</h3>
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
                                <button type="submit" name="save_inquiry_notes" class="btn btn-primary"><span class="icon">💾</span> Save Notes & Updates</button>
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
                <h3><span class="icon">✏️</span> Edit Client</h3>
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
                        <button type="submit" name="update_client" class="btn btn-primary"><span class="icon">✏️</span> Update Client</button>
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
                <h3><span class="icon">✏️</span> Edit Service</h3>
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
                            <label for="edit_description">Short Description *</label>
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
                                            <span class="icon">📷</span>
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
                                            <a href="<?php echo htmlspecialchars($edit_service['image_url']); ?>" target="_blank">View Image</a>
                                        <?php endif; ?>
                                        <div class="remove-image-checkbox">
                                            <input type="checkbox" id="remove_current_image" name="remove_image" value="1">
                                            <label for="remove_current_image">Remove current image</label>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="upload-section">
                                        <label class="upload-button">
                                            <span class="icon">📁</span> Upload New Image
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
                        <button type="submit" name="update_service" class="btn btn-primary"><span class="icon">✏️</span> Update Service</button>
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
            document.getElementById(modalId).style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        };

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
                const maxSize = 2 * 1024 * 1024;
                if (fileInput.files[0].size > maxSize) {
                    alert('File size must be less than 2MB');
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
        });
    </script>
</body>

</html>
<?php 
if (isset($conn)) {
    $conn = null;
}
?>