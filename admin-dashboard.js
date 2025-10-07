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
    // Auto-refresh every 30 seconds
    startAutoRefresh() {
        setInterval(() => {
            this.loadDashboardStats();
            this.loadRecentData();
        }, 30000); // 30 seconds
    }

    // Init mein add karein
    init() {
        this.checkAuth();
        this.setupEventListeners();
        this.loadDashboardStats();
        this.loadRecentData();
        this.startAutoRefresh(); // Add this line
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

    // Load disputes
    async loadDisputes() {
        try {
            const response = await fetch(`${this.ADMIN_API_BASE}/disputes.php`);
            const data = await response.json();

            if (data.success) {
                this.renderDisputesTable(data.disputes);
            }
        } catch (error) {
            console.error('Error loading disputes:', error);
        }
    }

    renderDisputesTable(disputes) {
        const tbody = document.getElementById('disputesTableBody');
        if (!tbody) return;

        tbody.innerHTML = '';

        disputes.forEach(dispute => {
            const priorityClass = `priority-${dispute.priority}`;
            const statusClass = `status-${dispute.status.replace('-', '_')}`;

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${dispute.id}</td>
                <td>
                    <strong>${dispute.title}</strong>
                    <br><small class="text-muted">${dispute.description.substring(0, 50)}...</small>
                </td>
                <td>${dispute.complainant_name}</td>
                <td>${dispute.respondent_name}</td>
                <td>
                    <span class="badge badge-priority ${priorityClass}">
                        ${dispute.priority.charAt(0).toUpperCase() + dispute.priority.slice(1)}
                    </span>
                </td>
                <td>
                    <span class="badge status-badge ${statusClass}">
                        ${dispute.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                    </span>
                </td>
                <td>${new Date(dispute.created_at).toLocaleDateString()}</td>
                <td>${dispute.assigned_admin_name || 'Unassigned'}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" onclick="adminDashboard.viewDispute(${dispute.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-success" onclick="adminDashboard.resolveDispute(${dispute.id})">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });
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

// Load all transactions with enhanced features
async loadTransactions() {
    try {
        console.log('Loading transactions with monitoring...');
        
        const response = await fetch(`${this.ADMIN_API_BASE}/transactions.php`);
        const data = await response.json();
        
        console.log('Transactions monitoring data:', data);
        
        if (data.success) {
            this.renderTransactionsTable(data.transactions);
            this.updateTransactionStats(data.transactions);
        } else {
            this.showToast('Error loading transactions', 'error');
        }
    } catch (error) {
        console.error('Error loading transactions:', error);
        this.showToast('Error loading transactions', 'error');
    }
}

// Update transaction statistics
updateTransactionStats(transactions) {
    const stats = {
        total: transactions.length,
        pending: transactions.filter(t => t.status === 'Pending').length,
        approved: transactions.filter(t => t.status === 'Approved').length,
        completed: transactions.filter(t => t.status === 'Completed').length,
        rejected: transactions.filter(t => t.status === 'Rejected').length,
        cancelled: transactions.filter(t => t.status === 'Cancelled').length
    };

    // Update stats in dashboard if needed
    console.log('Transaction Stats:', stats);
}

// Enhanced transactions table rendering
renderTransactionsTable(transactions) {
    const tbody = document.getElementById('transactionsTableBody');
    tbody.innerHTML = '';

    if (transactions.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="11" class="text-center py-4">
                    <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                    <h5>No Transactions Found</h5>
                    <p class="text-muted">No book exchange transactions recorded yet.</p>
                </td>
            </tr>
        `;
        return;
    }

    transactions.forEach(transaction => {
        const statusClass = this.getTransactionStatusClass(transaction.status);
        const typeClass = transaction.request_type === 'Borrow' ? 'text-primary' : 'text-info';
        const hasSwap = transaction.swap_book_id !== null;

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${transaction.id}</td>
            <td>
                <strong>${transaction.book_title}</strong>
                <br><small class="text-muted">by ${transaction.book_author}</small>
            </td>
            <td>
                <div>
                    <strong>${transaction.requester_name}</strong>
                    <br><small class="text-muted">${transaction.requester_email}</small>
                </div>
            </td>
            <td>
                <div>
                    <strong>${transaction.owner_name}</strong>
                    <br><small class="text-muted">${transaction.owner_email}</small>
                </div>
            </td>
            <td>
                <span class="${typeClass}">
                    <i class="fas ${transaction.request_type === 'Borrow' ? 'fa-hand-holding' : 'fa-exchange-alt'} me-1"></i>
                    ${transaction.request_type}
                    ${hasSwap ? '<br><small class="text-muted">With Swap</small>' : ''}
                </span>
            </td>
            <td>
                <span class="badge ${statusClass}">${transaction.status}</span>
            </td>
            <td>
                <small class="text-muted" title="${transaction.message}">
                    ${transaction.message ? transaction.message.substring(0, 30) + '...' : 'No message'}
                </small>
            </td>
            <td>
                ${transaction.proposed_return_date ? new Date(transaction.proposed_return_date).toLocaleDateString() : 'N/A'}
            </td>
            <td>${new Date(transaction.created_at).toLocaleDateString()}</td>
            <td>
                <span class="badge bg-secondary">${transaction.log_count} logs</span>
            </td>
            <td>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary" onclick="adminDashboard.viewTransactionDetails(${transaction.id})"
                            title="View Details & Logs">
                        <i class="fas fa-search"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-info" onclick="adminDashboard.viewTransactionLogs(${transaction.id})"
                            title="View Activity Logs">
                        <i class="fas fa-history"></i>
                    </button>
                    ${transaction.status === 'Pending' || transaction.status === 'Approved' ? `
                        <button class="btn btn-sm btn-outline-danger" onclick="adminDashboard.forceCancelTransaction(${transaction.id})"
                                title="Force Cancel">
                            <i class="fas fa-ban"></i>
                        </button>
                    ` : ''}
                    ${transaction.status === 'Approved' ? `
                        <button class="btn btn-sm btn-outline-success" onclick="adminDashboard.markTransactionCompleted(${transaction.id})"
                                title="Mark Completed">
                            <i class="fas fa-check-double"></i>
                        </button>
                    ` : ''}
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

getTransactionStatusClass(status) {
    const classes = {
        'Pending': 'bg-warning',
        'Approved': 'bg-success', 
        'Rejected': 'bg-danger',
        'Completed': 'bg-secondary',
        'Cancelled': 'bg-dark'
    };
    return classes[status] || 'bg-secondary';
}

// View transaction details
async viewTransactionDetails(transactionId) {
    try {
        const response = await fetch(`${this.ADMIN_API_BASE}/transactions.php`);
        const data = await response.json();
        
        if (data.success) {
            const transaction = data.transactions.find(t => t.id == transactionId);
            if (transaction) {
                this.showTransactionModal(transaction);
            }
        }
    } catch (error) {
        console.error('Error loading transaction details:', error);
    }
}

// Show transaction details modal
showTransactionModal(transaction) {
    const modalHtml = `
        <div class="modal fade" id="transactionModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Transaction #${transaction.id} Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Book Information</h6>
                                <p><strong>Title:</strong> ${transaction.book_title}</p>
                                <p><strong>Author:</strong> ${transaction.book_author}</p>
                                <p><strong>Request Type:</strong> ${transaction.request_type}</p>
                                ${transaction.swap_book_title ? `
                                    <p><strong>Swap Book:</strong> ${transaction.swap_book_title}</p>
                                ` : ''}
                            </div>
                            <div class="col-md-6">
                                <h6>Parties Involved</h6>
                                <p><strong>Requester:</strong> ${transaction.requester_name} (${transaction.requester_email})</p>
                                <p><strong>Owner:</strong> ${transaction.owner_name} (${transaction.owner_email})</p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Transaction Details</h6>
                                <p><strong>Status:</strong> <span class="badge ${this.getTransactionStatusClass(transaction.status)}">${transaction.status}</span></p>
                                <p><strong>Message:</strong> ${transaction.message || 'No message'}</p>
                                ${transaction.proposed_return_date ? `
                                    <p><strong>Proposed Return:</strong> ${new Date(transaction.proposed_return_date).toLocaleDateString()}</p>
                                ` : ''}
                                <p><strong>Created:</strong> ${new Date(transaction.created_at).toLocaleString()}</p>
                                <p><strong>Last Updated:</strong> ${new Date(transaction.updated_at).toLocaleString()}</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="adminDashboard.viewTransactionLogs(${transaction.id})">
                            <i class="fas fa-history me-1"></i>View Activity Logs
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('transactionModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
    modal.show();
}

// View transaction logs
async viewTransactionLogs(transactionId) {
    try {
        const response = await fetch(`${this.ADMIN_API_BASE}/transaction_logs.php?transaction_id=${transactionId}`);
        const data = await response.json();
        
        if (data.success) {
            this.showTransactionLogsModal(transactionId, data.logs);
        }
    } catch (error) {
        console.error('Error loading transaction logs:', error);
        this.showToast('Error loading transaction logs', 'error');
    }
}

// Show transaction logs modal
showTransactionLogsModal(transactionId, logs) {
    const logsHtml = logs.map(log => `
        <div class="border-bottom pb-2 mb-2">
            <div class="d-flex justify-content-between">
                <div>
                    <strong class="text-capitalize">${log.action.replace('_', ' ')}</strong>
                    <br><small class="text-muted">${log.description}</small>
                </div>
                <div class="text-end">
                    <small class="text-muted">${new Date(log.created_at).toLocaleString()}</small>
                    <br><small class="badge bg-info">${log.user_type}</small>
                    ${log.performed_by_name ? `<br><small>By: ${log.performed_by_name}</small>` : ''}
                </div>
            </div>
        </div>
    `).join('');

    const modalHtml = `
        <div class="modal fade" id="transactionLogsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Activity Logs - Transaction #${transactionId}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${logs.length > 0 ? logsHtml : '<p class="text-muted">No activity logs found for this transaction.</p>'}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('transactionLogsModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('transactionLogsModal'));
    modal.show();
}

// Admin actions
async forceCancelTransaction(transactionId) {
    if (confirm('Are you sure you want to force cancel this transaction? This action cannot be undone.')) {
        try {
            const response = await fetch(`${this.ADMIN_API_BASE}/transactions.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'force_cancel',
                    transaction_id: transactionId,
                    admin_id: this.adminData.admin_id
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showToast('Transaction cancelled successfully', 'success');
                this.loadTransactions(); // Refresh the list
            } else {
                this.showToast('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error cancelling transaction:', error);
            this.showToast('Error cancelling transaction', 'error');
        }
    }
}

async markTransactionCompleted(transactionId) {
    if (confirm('Are you sure you want to mark this transaction as completed?')) {
        try {
            const response = await fetch(`${this.ADMIN_API_BASE}/transactions.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'mark_completed',
                    transaction_id: transactionId,
                    admin_id: this.adminData.admin_id
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showToast('Transaction marked as completed', 'success');
                this.loadTransactions(); // Refresh the list
            } else {
                this.showToast('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error completing transaction:', error);
            this.showToast('Error completing transaction', 'error');
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

    // View dispute
    viewDispute(disputeId) {
        this.showToast(`Viewing dispute ID: ${disputeId} - Feature coming soon`, 'info');
    }

    // Resolve dispute
    resolveDispute(disputeId) {
        this.showToast(`Resolving dispute ID: ${disputeId} - Feature coming soon`, 'info');
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


