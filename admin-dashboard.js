// Admin Dashboard JavaScript
class AdminDashboard {
    constructor() {
        this.adminData = null;
        this.ADMIN_API_BASE = 'http://localhost/project/backend/api/admin';
        this.init();
    }

    init() {
        this.checkAuth();
        this.setupEventListeners();
        this.loadDashboardStats();
        this.loadRecentData();
        this.startAutoRefresh();
    }

    checkAuth() {
        const adminData = JSON.parse(localStorage.getItem('admin'));
        if (!adminData || !adminData.loggedIn) {
            window.location.href = 'admin-login.html';
            return;
        }
        this.adminData = adminData;
        document.getElementById('adminUsername').textContent = adminData.username;
    }

    setupEventListeners() {
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Navigation
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = e.target.getAttribute('data-section');
                this.showSection(section);

                // Update active state
                document.querySelectorAll('.sidebar .nav-link').forEach(l => l.classList.remove('active'));
                e.target.classList.add('active');
            });
        });

        // Dispute filters
        document.addEventListener('click', (e) => {
            if (e.target.hasAttribute('data-filter')) {
                const filter = e.target.getAttribute('data-filter');
                
                // Update active state
                document.querySelectorAll('[data-filter]').forEach(btn => {
                    btn.classList.remove('active');
                });
                e.target.classList.add('active');
                
                // Load disputes with filter
                this.loadDisputes(filter);
            }
        });
    }

    showSection(sectionName) {
        // Hide all sections
        document.querySelectorAll('.content-section').forEach(section => {
            section.style.display = 'none';
        });

        // Show selected section
        const targetSection = document.getElementById(`${sectionName}-section`);
        if (targetSection) {
            targetSection.style.display = 'block';

            // Load section-specific data
            this.loadSectionData(sectionName);
        }
    }

    loadSectionData(sectionName) {
        switch (sectionName) {
            case 'users':
                this.loadUsers();
                break;
            case 'books':
                this.loadBooks();
                break;
            case 'disputes':
                this.loadDisputes();
                break;
            case 'transactions':
                this.loadTransactions();
                break;
            case 'dashboard':
                this.loadDashboardStats();
                this.loadRecentData();
                break;
        }
    }

    async loadDashboardStats() {
        try {
            console.log('Loading dashboard stats...');

            const response = await fetch(`${this.ADMIN_API_BASE}/stats.php`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Stats data received:', data);

            if (data.success) {
                // Update the statistics boxes
                document.getElementById('totalUsers').textContent = data.stats.total_users;
                document.getElementById('totalBooks').textContent = data.stats.total_books;
                document.getElementById('totalTransactions').textContent = data.stats.total_transactions;
                document.getElementById('openDisputes').textContent = data.stats.open_disputes;

                console.log('Stats updated successfully');
            } else {
                console.error('Stats API returned error:', data.message);
                this.setDefaultStats();
            }
        } catch (error) {
            console.error('Error loading stats:', error);
            this.setDefaultStats();
        }
    }

    setDefaultStats() {
        // Set default values if API fails
        document.getElementById('totalUsers').textContent = '0';
        document.getElementById('totalBooks').textContent = '0';
        document.getElementById('totalTransactions').textContent = '0';
        document.getElementById('openDisputes').textContent = '0';
    }

    startAutoRefresh() {
        setInterval(() => {
            this.loadDashboardStats();
            this.loadRecentData();
        }, 30000);
    }

    async loadRecentData() {
        await this.loadRecentUsers();
        await this.loadRecentDisputes();
    }

    async loadRecentUsers() {
        try {
            const response = await fetch(`${this.ADMIN_API_BASE}/users.php?limit=5`);
            const data = await response.json();

            if (data.success) {
                this.renderRecentUsers(data.users);
            }
        } catch (error) {
            console.error('Error loading recent users:', error);
        }
    }

    renderRecentUsers(users) {
        const container = document.getElementById('recentUsers');
        container.innerHTML = '';

        if (users.length === 0) {
            container.innerHTML = '<p class="text-muted">No users found</p>';
            return;
        }

        users.forEach(user => {
            const userElement = document.createElement('div');
            userElement.className = 'd-flex justify-content-between align-items-center border-bottom pb-2 mb-2';
            userElement.innerHTML = `
                <div class="d-flex align-items-center">
                    <img src="${user.profile_picture}" 
                         class="rounded-circle me-3" width="40" height="40"
                         onerror="this.src='assets/default-avatar.png'">
                    <div>
                        <h6 class="mb-0">${user.name}</h6>
                        <small class="text-muted">${user.email}</small>
                    </div>
                </div>
                <small class="text-muted">${new Date(user.joined_date).toLocaleDateString()}</small>
            `;
            container.appendChild(userElement);
        });
    }

    async loadRecentDisputes() {
        try {
            const response = await fetch(`${this.ADMIN_API_BASE}/disputes.php?limit=5`);
            const data = await response.json();

            if (data.success) {
                this.renderRecentDisputes(data.disputes);
            }
        } catch (error) {
            console.error('Error loading recent disputes:', error);
        }
    }

    renderRecentDisputes(disputes) {
        const container = document.getElementById('recentDisputes');
        container.innerHTML = '';

        if (disputes.length === 0) {
            container.innerHTML = '<p class="text-muted">No recent disputes</p>';
            return;
        }

        disputes.forEach(dispute => {
            const statusClass = `status-${dispute.status.replace('-', '_')}`;
            const disputeElement = document.createElement('div');
            disputeElement.className = 'border-bottom pb-2 mb-2';
            disputeElement.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">${dispute.title}</h6>
                        <p class="mb-1 text-muted small">${dispute.description.substring(0, 60)}...</p>
                        <small class="text-muted">
                            Between ${dispute.complainant_name} and ${dispute.respondent_name}
                        </small>
                    </div>
                    <span class="badge status-badge ${statusClass}">
                        ${dispute.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                    </span>
                </div>
            `;
            container.appendChild(disputeElement);
        });
    }

    // Load all users
    async loadUsers() {
        try {
            const response = await fetch(`${this.ADMIN_API_BASE}/users.php`);
            const data = await response.json();

            if (data.success) {
                this.renderUsersTable(data.users);
                document.getElementById('usersCount').textContent = data.users.length;
            }
        } catch (error) {
            console.error('Error loading users:', error);
            this.showToast('Error loading users', 'error');
        }
    }

    // Render users table
    renderUsersTable(users) {
        const tbody = document.getElementById('usersTableBody');
        tbody.innerHTML = '';

        if (users.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5>No Users Found</h5>
                        <p class="text-muted">No users have registered yet.</p>
                    </td>
                </tr>
            `;
            return;
        }

        users.forEach(user => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${user.id}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <img src="${user.profile_picture}" 
                             class="rounded-circle me-2" width="40" height="40" 
                             onerror="this.src='assets/default-avatar.png'"
                             style="object-fit: cover;">
                        <div>
                            <strong>${user.name}</strong>
                            <br><small class="text-muted">${user.email}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div>
                        <strong>Email:</strong> ${user.email}<br>
                    </div>
                </td>
                <td>
                    <div>
                        ${user.city ? `<strong>City:</strong> ${user.city}<br>` : ''}
                        ${user.address ? `<small class="text-muted">${user.address.substring(0, 30)}...</small>` : ''}
                    </div>
                </td>
                <td>
                    <span class="badge bg-primary fs-6">${user.book_count}</span>
                </td>
                <td>${new Date(user.joined_date).toLocaleDateString()}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" onclick="adminDashboard.viewUser(${user.id})" 
                                title="View User Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning" onclick="adminDashboard.editUser(${user.id})"
                                title="Edit User">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="adminDashboard.deleteUser(${user.id})"
                                title="Delete User">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    // Load all books
    async loadBooks() {
        try {
            const response = await fetch(`${this.ADMIN_API_BASE}/books.php`);
            const data = await response.json();

            if (data.success) {
                this.renderBooksTable(data.books);
                document.getElementById('booksCount').textContent = data.books.length;
            }
        } catch (error) {
            console.error('Error loading books:', error);
            this.showToast('Error loading books', 'error');
        }
    }

    // Render books table
    renderBooksTable(books) {
        const tbody = document.getElementById('booksTableBody');
        tbody.innerHTML = '';

        if (books.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <i class="fas fa-book fa-3x text-muted mb-3"></i>
                        <h5>No Books Found</h5>
                        <p class="text-muted">No books have been uploaded by users yet.</p>
                    </td>
                </tr>
            `;
            return;
        }

        books.forEach(book => {
            const statusClass = book.status === 'Available' ? 'bg-success' :
                book.status === 'Lent Out' ? 'bg-warning' : 'bg-secondary';

            const conditionClass = book.condition === 'New' ? 'text-success' :
                book.condition === 'Like New' ? 'text-primary' :
                    book.condition === 'Very Good' ? 'text-info' :
                        book.condition === 'Good' ? 'text-warning' : 'text-muted';

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <img src="${book.image_path}" 
                         class="rounded" width="50" height="70" 
                         style="object-fit: cover;"
                         onerror="this.src='assets/default-book.png'">
                </td>
                <td>
                    <div>
                        <strong>${book.title}</strong><br>
                        <small class="text-muted">by ${book.author}</small><br>
                        ${book.genre ? `<span class="badge bg-light text-dark">${book.genre}</span>` : ''}
                        ${book.isbn ? `<br><small class="text-muted">ISBN: ${book.isbn}</small>` : ''}
                    </div>
                </td>
                <td>
                    <div>
                        <strong>${book.user.name}</strong><br>
                        <small class="text-muted">${book.user.email}</small><br>
                        <small class="text-muted">${book.user.city || 'N/A'}</small>
                    </div>
                </td>
                <td>
                    <span class="${conditionClass}">
                        <i class="fas fa-book me-1"></i>${book.condition}
                    </span>
                </td>
                <td>
                    <span class="badge ${statusClass}">${book.status}</span>
                </td>
                <td>${new Date(book.created_at).toLocaleDateString()}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" onclick="adminDashboard.viewBook(${book.id})"
                                title="View Book Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="adminDashboard.deleteBook(${book.id})"
                                title="Delete Book">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    // Enhanced dispute management system
    async loadDisputes(filter = 'all') {
        try {
            console.log('Loading disputes with filter:', filter);
            
            const url = filter === 'all' 
                ? `${this.ADMIN_API_BASE}/disputes.php` 
                : `${this.ADMIN_API_BASE}/disputes.php?filter=${filter}`;
            
            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                this.renderDisputesTable(data.disputes);
                this.updateDisputeStats(data.disputes);
            } else {
                this.showToast('Error loading disputes', 'error');
            }
        } catch (error) {
            console.error('Error loading disputes:', error);
            this.showToast('Error loading disputes', 'error');
        }
    }

    // Update dispute statistics
    updateDisputeStats(disputes) {
        const stats = {
            total: disputes.length,
            open: disputes.filter(d => d.status === 'open').length,
            under_review: disputes.filter(d => d.status === 'under_review').length,
            resolved: disputes.filter(d => d.status === 'resolved').length,
            urgent: disputes.filter(d => d.priority === 'urgent').length
        };

        // Update stats cards if they exist
        if (document.getElementById('totalDisputes')) {
            document.getElementById('totalDisputes').textContent = stats.total;
            document.getElementById('openDisputesCount').textContent = stats.open;
            document.getElementById('underReviewDisputes').textContent = stats.under_review;
            document.getElementById('resolvedDisputes').textContent = stats.resolved;
            document.getElementById('urgentDisputes').textContent = stats.urgent;
        }
    }

    // Enhanced disputes table rendering
    renderDisputesTable(disputes) {
        const tbody = document.getElementById('disputesTableBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';

        if (disputes.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-3x text-muted mb-3"></i>
                        <h5>No Disputes Found</h5>
                        <p class="text-muted">No disputes match the current filter.</p>
                    </td>
                </tr>
            `;
            return;
        }

        disputes.forEach(dispute => {
            const priorityClass = `priority-${dispute.priority}`;
            const statusClass = `status-${dispute.status.replace('-', '_')}`;
            const isAssignedToMe = dispute.assigned_admin_id == this.adminData.admin_id;

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${dispute.id}</td>
                <td>
                    <div>
                        <strong class="d-block">${dispute.title}</strong>
                        <small class="text-muted">${dispute.description.substring(0, 60)}...</small>
                    </div>
                </td>
                <td>
                    <div>
                        <small><strong>Complainant:</strong> ${dispute.complainant_name}</small><br>
                        <small><strong>Respondent:</strong> ${dispute.respondent_name}</small>
                        ${dispute.transaction_id ? `<br><small class="text-muted">Transaction #${dispute.transaction_id}</small>` : ''}
                    </div>
                </td>
                <td>
                    <span class="badge bg-light text-dark">${dispute.category}</span>
                </td>
                <td>
                    <span class="badge badge-priority ${priorityClass}">
                        <i class="fas ${dispute.priority === 'urgent' ? 'fa-fire' : 
                                       dispute.priority === 'high' ? 'fa-exclamation-circle' : 
                                       dispute.priority === 'medium' ? 'fa-info-circle' : 'fa-arrow-down'} me-1"></i>
                        ${dispute.priority.charAt(0).toUpperCase() + dispute.priority.slice(1)}
                    </span>
                </td>
                <td>
                    <span class="badge status-badge ${statusClass}">
                        ${dispute.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                    </span>
                </td>
                <td>
                    <small>${new Date(dispute.created_at).toLocaleDateString()}</small><br>
                    <small class="text-muted">${new Date(dispute.created_at).toLocaleTimeString()}</small>
                </td>
                <td>
                    <div>
                        ${dispute.assigned_admin_name ? `
                            <strong>${dispute.assigned_admin_name}</strong>
                            ${isAssignedToMe ? '<span class="badge bg-info ms-1">You</span>' : ''}
                        ` : `
                            <span class="text-muted">Unassigned</span>
                        `}
                    </div>
                </td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick="adminDashboard.viewDispute(${dispute.id})"
                                title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${!dispute.assigned_admin_id ? `
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="adminDashboard.assignDisputeToMe(${dispute.id})"
                                    title="Assign to Me">
                                <i class="fas fa-user-check"></i>
                            </button>
                        ` : ''}
                        ${dispute.status === 'open' && isAssignedToMe ? `
                            <button class="btn btn-sm btn-outline-info" 
                                    onclick="adminDashboard.startReview(${dispute.id})"
                                    title="Start Review">
                                <i class="fas fa-play"></i>
                            </button>
                        ` : ''}
                        ${dispute.status === 'under_review' && isAssignedToMe ? `
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="adminDashboard.showResolveForm(${dispute.id})"
                                    title="Resolve">
                                <i class="fas fa-check"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    // View dispute details
    async viewDispute(disputeId) {
        try {
            const response = await fetch(`${this.ADMIN_API_BASE}/disputes.php?id=${disputeId}`);
            const data = await response.json();

            if (data.success) {
                this.showDisputeModal(data.dispute);
            } else {
                this.showToast('Error loading dispute details', 'error');
            }
        } catch (error) {
            console.error('Error loading dispute details:', error);
            this.showToast('Error loading dispute details', 'error');
        }
    }

    // Show dispute details modal
    showDisputeModal(dispute) {
        const priorityClass = `priority-${dispute.priority}`;
        const statusClass = `status-${dispute.status.replace('-', '_')}`;

        const modalHtml = `
            <div class="modal fade" id="disputeModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Dispute #${dispute.id}: ${dispute.title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Basic Information</h6>
                                    <p><strong>Category:</strong> ${dispute.category}</p>
                                    <p><strong>Priority:</strong> 
                                        <span class="badge badge-priority ${priorityClass}">
                                            ${dispute.priority.charAt(0).toUpperCase() + dispute.priority.slice(1)}
                                        </span>
                                    </p>
                                    <p><strong>Status:</strong> 
                                        <span class="badge status-badge ${statusClass}">
                                            ${dispute.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Parties Involved</h6>
                                    <p><strong>Complainant:</strong> ${dispute.complainant_name} (${dispute.complainant_email})</p>
                                    <p><strong>Respondent:</strong> ${dispute.respondent_name} (${dispute.respondent_email})</p>
                                    ${dispute.transaction_id ? `
                                        <p><strong>Transaction ID:</strong> #${dispute.transaction_id}</p>
                                    ` : ''}
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6>Description</h6>
                                    <div class="border p-3 bg-light rounded">
                                        ${dispute.description}
                                    </div>
                                </div>
                            </div>

                            ${dispute.evidence ? `
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6>Evidence/Attachments</h6>
                                    <div class="border p-3 bg-light rounded">
                                        ${dispute.evidence}
                                    </div>
                                </div>
                            </div>
                            ` : ''}

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <h6>Timeline</h6>
                                    <p><strong>Created:</strong> ${new Date(dispute.created_at).toLocaleString()}</p>
                                    <p><strong>Last Updated:</strong> ${new Date(dispute.updated_at).toLocaleString()}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Assignment</h6>
                                    <p><strong>Assigned To:</strong> ${dispute.assigned_admin_name || 'Unassigned'}</p>
                                    ${dispute.resolution_notes ? `
                                        <p><strong>Resolution Notes:</strong> ${dispute.resolution_notes}</p>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            ${!dispute.assigned_admin_id ? `
                                <button type="button" class="btn btn-primary" onclick="adminDashboard.assignDisputeToMe(${dispute.id})">
                                    <i class="fas fa-user-check me-1"></i>Assign to Me
                                </button>
                            ` : ''}
                            ${dispute.status === 'open' && dispute.assigned_admin_id == this.adminData.admin_id ? `
                                <button type="button" class="btn btn-info" onclick="adminDashboard.startReview(${dispute.id})">
                                    <i class="fas fa-play me-1"></i>Start Review
                                </button>
                            ` : ''}
                            ${dispute.status === 'under_review' && dispute.assigned_admin_id == this.adminData.admin_id ? `
                                <button type="button" class="btn btn-success" onclick="adminDashboard.showResolveForm(${dispute.id})">
                                    <i class="fas fa-check me-1"></i>Resolve Dispute
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('disputeModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('disputeModal'));
        modal.show();
    }

    // Assign dispute to current admin
    async assignDisputeToMe(disputeId) {
        try {
            const response = await fetch(`${this.ADMIN_API_BASE}/disputes.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'assign',
                    dispute_id: disputeId,
                    admin_id: this.adminData.admin_id
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showToast('Dispute assigned to you successfully', 'success');
                this.loadDisputes();
                // Close modal if open
                const modal = bootstrap.Modal.getInstance(document.getElementById('disputeModal'));
                if (modal) modal.hide();
            } else {
                this.showToast('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error assigning dispute:', error);
            this.showToast('Error assigning dispute', 'error');
        }
    }

    // Start reviewing a dispute
    async startReview(disputeId) {
        try {
            const response = await fetch(`${this.ADMIN_API_BASE}/disputes.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'start_review',
                    dispute_id: disputeId,
                    admin_id: this.adminData.admin_id
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showToast('Dispute review started', 'success');
                this.loadDisputes();
                // Close modal if open
                const modal = bootstrap.Modal.getInstance(document.getElementById('disputeModal'));
                if (modal) modal.hide();
            } else {
                this.showToast('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error starting review:', error);
            this.showToast('Error starting review', 'error');
        }
    }

    // Show resolve dispute form
    showResolveForm(disputeId) {
        const resolveFormHtml = `
            <div class="modal fade" id="resolveDisputeModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Resolve Dispute #${disputeId}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="resolveDisputeForm">
                                <div class="mb-3">
                                    <label for="resolution" class="form-label">Resolution</label>
                                    <select class="form-select" id="resolution" required>
                                        <option value="">Select resolution</option>
                                        <option value="resolved_in_favor_complainant">Resolved in favor of complainant</option>
                                        <option value="resolved_in_favor_respondent">Resolved in favor of respondent</option>
                                        <option value="mutual_agreement">Mutual agreement</option>
                                        <option value="dismissed">Dismissed</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="resolutionNotes" class="form-label">Resolution Notes</label>
                                    <textarea class="form-control" id="resolutionNotes" rows="4" 
                                              placeholder="Describe the resolution and any actions taken..." required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="penalty" class="form-label">Penalty (if any)</label>
                                    <select class="form-select" id="penalty">
                                        <option value="none">No penalty</option>
                                        <option value="warning">Warning</option>
                                        <option value="temporary_suspension">Temporary suspension</option>
                                        <option value="permanent_ban">Permanent ban</option>
                                    </select>
                                </div>
                                <div class="mb-3" id="penaltyDetails" style="display: none;">
                                    <label for="penaltyDescription" class="form-label">Penalty Details</label>
                                    <textarea class="form-control" id="penaltyDescription" rows="2" 
                                              placeholder="Describe the penalty and duration..."></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-success" onclick="adminDashboard.resolveDispute(${disputeId})">
                                <i class="fas fa-check me-1"></i>Resolve Dispute
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('resolveDisputeModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        document.body.insertAdjacentHTML('beforeend', resolveFormHtml);
        const modal = new bootstrap.Modal(document.getElementById('resolveDisputeModal'));
        modal.show();

        // Show/hide penalty details based on selection
        document.getElementById('penalty').addEventListener('change', function() {
            const penaltyDetails = document.getElementById('penaltyDetails');
            penaltyDetails.style.display = this.value !== 'none' ? 'block' : 'none';
        });
    }

    // Resolve dispute with details
    async resolveDispute(disputeId) {
        const form = document.getElementById('resolveDisputeForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const resolutionData = {
            action: 'resolve',
            dispute_id: disputeId,
            admin_id: this.adminData.admin_id,
            resolution: document.getElementById('resolution').value,
            resolution_notes: document.getElementById('resolutionNotes').value,
            penalty: document.getElementById('penalty').value,
            penalty_description: document.getElementById('penaltyDescription').value || ''
        };

        try {
            const response = await fetch(`${this.ADMIN_API_BASE}/disputes.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(resolutionData)
            });

            const data = await response.json();

            if (data.success) {
                this.showToast('Dispute resolved successfully', 'success');
                this.loadDisputes();
                // Close modals
                const resolveModal = bootstrap.Modal.getInstance(document.getElementById('resolveDisputeModal'));
                if (resolveModal) resolveModal.hide();
                const disputeModal = bootstrap.Modal.getInstance(document.getElementById('disputeModal'));
                if (disputeModal) disputeModal.hide();
            } else {
                this.showToast('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error resolving dispute:', error);
            this.showToast('Error resolving dispute', 'error');
        }
    }

    // Export disputes
    async exportDisputes() {
        try {
            const response = await fetch(`${this.ADMIN_API_BASE}/disputes.php?export=1`);
            const blob = await response.blob();
            
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `disputes_export_${new Date().toISOString().split('T')[0]}.csv`;
            
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            this.showToast('Disputes exported successfully', 'success');
        } catch (error) {
            console.error('Error exporting disputes:', error);
            this.showToast('Error exporting disputes', 'error');
        }
    }

    // Delete user function
    async deleteUser(userId) {
        if (confirm('Are you sure you want to delete this user? This will also delete all their books!')) {
            try {
                const response = await fetch(`${this.ADMIN_API_BASE}/users.php`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ user_id: userId })
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('User deleted successfully', 'success');
                    this.loadUsers(); // Refresh the list
                    this.loadDashboardStats(); // Refresh stats
                } else {
                    this.showToast(data.message || 'Error deleting user', 'error');
                }
            } catch (error) {
                console.error('Error deleting user:', error);
                this.showToast('Error deleting user', 'error');
            }
        }
    }

    // Delete book function
    async deleteBook(bookId) {
        if (confirm('Are you sure you want to delete this book?')) {
            try {
                const response = await fetch(`${this.ADMIN_API_BASE}/books.php`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ book_id: bookId })
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('Book deleted successfully', 'success');
                    this.loadBooks(); // Refresh the list
                    this.loadDashboardStats(); // Refresh stats
                } else {
                    this.showToast(data.message || 'Error deleting book', 'error');
                }
            } catch (error) {
                console.error('Error deleting book:', error);
                this.showToast('Error deleting book', 'error');
            }
        }
    }

    // View user details
    viewUser(userId) {
        this.showToast(`Viewing user ID: ${userId} - Feature coming soon`, 'info');
    }

    // View book details  
    viewBook(bookId) {
        this.showToast(`Viewing book ID: ${bookId} - Feature coming soon`, 'info');
    }

    // Edit user
    editUser(userId) {
        this.showToast(`Editing user ID: ${userId} - Feature coming soon`, 'info');
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
        toast.style.zIndex = '1000';
        toast.innerHTML = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    logout() {
        localStorage.removeItem('admin');
        window.location.href = 'admin-login.html';
    }
}

// Initialize admin dashboard
const adminDashboard = new AdminDashboard();

// Global functions
function logout() {
    adminDashboard.logout();
}

function refreshDashboard() {
    adminDashboard.loadDashboardStats();
    adminDashboard.loadRecentData();
    adminDashboard.showToast('Dashboard refreshed!', 'success');
}