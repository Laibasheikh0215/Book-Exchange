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

        // User and Book filters
        document.addEventListener('click', (e) => {
            if (e.target.hasAttribute('data-filter') &&
                (e.target.closest('#users-section') || e.target.closest('#books-section'))) {
                const filter = e.target.getAttribute('data-filter');

                // Update active state
                e.target.closest('.btn-group').querySelectorAll('[data-filter]').forEach(btn => {
                    btn.classList.remove('active');
                });
                e.target.classList.add('active');

                // Load data with filter
                if (e.target.closest('#users-section')) {
                    this.loadUsers(filter);
                } else if (e.target.closest('#books-section')) {
                    this.loadBooks(filter);
                }
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

    // Initializes and loads data for the requested admin panel section
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

    //  Fetches and displays dashboard statistics from the admin API
    //  Updates UI with total counts for users, books, and transactions
    async loadDashboardStats() {
        try {
            console.log('🔄 Loading dashboard stats...');

            const response = await fetch(`${this.ADMIN_API_BASE}/stats.php`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('📊 Stats data received:', data);

            if (data.success) {
                // Update the statistics boxes
                document.getElementById('totalUsers').textContent = data.stats.total_users;
                document.getElementById('totalBooks').textContent = data.stats.total_books;
                document.getElementById('totalTransactions').textContent = data.stats.total_transactions;

                // IMPORTANT: Real-time dispute count load karo
                await this.loadRealTimeDisputeCount();

                console.log('✅ Stats updated successfully');
            } else {
                console.error('❌ Stats API returned error:', data.message);
                this.setDefaultStats();
            }
        } catch (error) {
            console.error('❌ Error loading stats:', error);
            this.setDefaultStats();
        }
    }

    // Load real-time dispute count and update dashboard
    async loadRealTimeDisputeCount() {
        try {
            console.log('🔄 Loading real-time dispute count...');

            const response = await fetch(`${this.ADMIN_API_BASE}/disputes.php`);
            const data = await response.json();

            console.log('📋 Disputes API response:', data);

            if (data.success && data.disputes) {
                // Count open and under_review disputes
                const openDisputes = data.disputes.filter(dispute =>
                    dispute.status === 'open' || dispute.status === 'under_review'
                ).length;

                console.log('🔢 Calculated open disputes:', openDisputes);
                console.log('📋 All disputes:', data.disputes.map(d => ({ id: d.id, status: d.status })));

                // Update dashboard count
                const openDisputesElement = document.getElementById('openDisputes');
                if (openDisputesElement) {
                    openDisputesElement.textContent = openDisputes;
                    console.log('✅ Dashboard dispute count updated to:', openDisputes);
                } else {
                    console.error('❌ openDisputes element not found!');
                }
            } else {
                console.error('❌ Disputes API error:', data);
                document.getElementById('openDisputes').textContent = '0';
            }
        } catch (error) {
            console.error('❌ Error loading real-time dispute count:', error);
            document.getElementById('openDisputes').textContent = '0';
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

        if (!users || users.length === 0) {
            container.innerHTML = '<p class="text-muted">No users found</p>';
            return;
        }

        users.forEach(user => {
            const userElement = document.createElement('div');
            userElement.className = 'd-flex justify-content-between align-items-center border-bottom pb-2 mb-2';
            userElement.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                         style="width: 40px; height: 40px;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">${this.safeString(user.name)}</h6>
                        <small class="text-muted">${this.safeString(user.email)}</small>
                    </div>
                </div>
                <small class="text-muted">${user.joined_date ? new Date(user.joined_date).toLocaleDateString() : 'N/A'}</small>
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

        if (!disputes || disputes.length === 0) {
            container.innerHTML = '<p class="text-muted">No recent disputes</p>';
            return;
        }

        disputes.forEach(dispute => {
            const statusClass = `status-${(dispute.status || 'open').replace('-', '_')}`;
            const disputeElement = document.createElement('div');
            disputeElement.className = 'border-bottom pb-2 mb-2';
            disputeElement.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">${this.safeString(dispute.title)}</h6>
                        <p class="mb-1 text-muted small">${this.safeString(dispute.description?.substring(0, 60) || 'No description')}...</p>
                        <small class="text-muted">
                            Between ${this.safeString(dispute.complainant_name)} and ${this.safeString(dispute.respondent_name)}
                        </small>
                    </div>
                    <span class="badge status-badge ${statusClass}">
                        ${this.formatStatus(dispute.status)}
                    </span>
                </div>
            `;
            container.appendChild(disputeElement);
        });
    }

    // Load all users with filter
    async loadUsers(filter = 'all') {
        try {
            const url = filter === 'all'
                ? `${this.ADMIN_API_BASE}/users.php`
                : `${this.ADMIN_API_BASE}/users.php?filter=${filter}`;

            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                this.renderUsersTable(data.users);
                this.updateUserStats(data.users);

                // Safely update users count
                const usersCountElement = document.getElementById('usersCount');
                if (usersCountElement) {
                    usersCountElement.textContent = data.users.length;
                }
            } else {
                this.showToast('Error loading users: ' + (data.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Error loading users:', error);
            this.showToast('Error loading users', 'error');
        }
    }

    // Update user statistics 
    updateUserStats(users) {
        if (!users || !Array.isArray(users)) {
            console.error('Invalid users data for stats:', users);
            return;
        }

        const stats = {
            total: users.length,
            active: users.filter(u => (u.status || 'active') === 'active').length,
            verified: users.filter(u => u.is_verified).length,
            newToday: users.filter(u => {
                try {
                    const today = new Date().toDateString();
                    const userDate = new Date(u.joined_date).toDateString();
                    return today === userDate;
                } catch (e) {
                    return false;
                }
            }).length
        };

        console.log('📊 User stats calculated:', stats);

        // SAFELY update stats cards - check if elements exist first
        const totalUsersElement = document.getElementById('totalUsersCount');
        const activeUsersElement = document.getElementById('activeUsers');
        const verifiedUsersElement = document.getElementById('verifiedUsers');
        const newUsersElement = document.getElementById('newUsersToday');

        if (totalUsersElement) totalUsersElement.textContent = stats.total;
        if (activeUsersElement) activeUsersElement.textContent = stats.active;
        if (verifiedUsersElement) verifiedUsersElement.textContent = stats.verified;
        if (newUsersElement) newUsersElement.textContent = stats.newToday;
    }

    // Render users table with safe data handling
    renderUsersTable(users) {
        const tbody = document.getElementById('usersTableBody');
        tbody.innerHTML = '';

        if (!users || users.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5>No Users Found</h5>
                        <p class="text-muted">No users match the current filter.</p>
                    </td>
                </tr>
            `;
            return;
        }

        users.forEach(user => {
            // Safe data handling with defaults
            const userStatus = user.status || 'active';
            const statusClass = userStatus === 'active' ? 'status-active' : 'status-inactive';
            const avatarUrl = user.avatar || '';
            const bookCount = user.book_count || 0;
            const joinedDate = user.joined_date ? new Date(user.joined_date).toLocaleDateString() : 'N/A';

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${user.id || 'N/A'}</td>
                <td>
                    <div class="d-flex align-items-center">
                        ${avatarUrl ?
                    `<img src="${avatarUrl}" class="user-avatar me-2" alt="${this.safeString(user.name)}" onerror="this.style.display='none'">` :
                    `<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2 user-avatar">
                                <i class="fas fa-user"></i>
                            </div>`
                }
                        <div>
                            <strong>${this.safeString(user.name)}</strong>
                            <br><small class="text-muted">${this.safeString(user.email)}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div>
                        <strong>Email:</strong> ${this.safeString(user.email)}<br>
                        ${user.phone ? `<strong>Phone:</strong> ${this.safeString(user.phone)}` : ''}
                    </div>
                </td>
                <td>
                    <div>
                        ${user.city ? `<strong>City:</strong> ${this.safeString(user.city)}<br>` : ''}
                        ${user.address ? `<small class="text-muted">${this.safeString(user.address.substring(0, 30))}...</small>` : ''}
                    </div>
                </td>
                <td>
                    <span class="badge bg-primary fs-6">${bookCount}</span>
                </td>
                <td>
                    <span class="badge status-badge ${statusClass}">
                        ${this.formatStatus(userStatus)}
                    </span>
                </td>
                <td>${joinedDate}</td>
                <td>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    // Load all books with filter
    async loadBooks(filter = 'all') {
        try {
            const url = filter === 'all'
                ? `${this.ADMIN_API_BASE}/books.php`
                : `${this.ADMIN_API_BASE}/books.php?filter=${filter}`;

            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                this.renderBooksTable(data.books);
                this.updateBookStats(data.books);
                document.getElementById('booksCount').textContent = data.books.length;
            } else {
                this.showToast('Error loading books: ' + (data.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Error loading books:', error);
            this.showToast('Error loading books', 'error');
        }
    }

    // Update book statistics
    updateBookStats(books) {
        if (!books || !Array.isArray(books)) {
            console.error('Invalid books data for stats:', books);
            return;
        }

        const stats = {
            total: books.length,
            available: books.filter(b => (b.status || 'Available') === 'Available').length,
            lent: books.filter(b => (b.status || 'Available') === 'Lent Out').length,
            reserved: books.filter(b => (b.status || 'Available') === 'Reserved').length
        };

        console.log('📊 Book stats calculated:', stats);

        // SAFELY update stats cards - check if elements exist first
        const totalBooksElement = document.getElementById('totalBooksCount');
        const availableBooksElement = document.getElementById('availableBooks');
        const lentBooksElement = document.getElementById('lentBooks');
        const reservedBooksElement = document.getElementById('reservedBooks');

        if (totalBooksElement) totalBooksElement.textContent = stats.total;
        if (availableBooksElement) availableBooksElement.textContent = stats.available;
        if (lentBooksElement) lentBooksElement.textContent = stats.lent;
        if (reservedBooksElement) reservedBooksElement.textContent = stats.reserved;
    }

    // Render books table with safe data handling
    renderBooksTable(books) {
        const tbody = document.getElementById('booksTableBody');
        tbody.innerHTML = '';

        if (!books || books.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <i class="fas fa-book fa-3x text-muted mb-3"></i>
                        <h5>No Books Found</h5>
                        <p class="text-muted">No books match the current filter.</p>
                    </td>
                </tr>
            `;
            return;
        }

        books.forEach(book => {
            // Safe data handling with defaults
            const bookStatus = book.status || 'Available';
            const statusClass = bookStatus === 'Available' ? 'status-available' :
                bookStatus === 'Lent Out' ? 'status-lent' : 'status-reserved';

            const conditionClass = (book.condition || 'Good') === 'New' ? 'text-success' :
                book.condition === 'Like New' ? 'text-primary' :
                    book.condition === 'Very Good' ? 'text-info' :
                        book.condition === 'Good' ? 'text-warning' : 'text-muted';

            const coverUrl = book.cover_image || '';
            const addedDate = book.created_at ? new Date(book.created_at).toLocaleDateString() : 'N/A';

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    ${coverUrl ?
                    `<img src="${coverUrl}" class="book-cover" alt="${this.safeString(book.title)}" onerror="this.style.display='none'">` :
                    `<div class="rounded bg-light d-flex align-items-center justify-content-center book-cover">
                            <i class="fas fa-book text-muted"></i>
                        </div>`
                }
                </td>
                <td>
                    <div>
                        <strong>${this.safeString(book.title)}</strong><br>
                        <small class="text-muted">by ${this.safeString(book.author)}</small><br>
                        ${book.genre ? `<span class="badge bg-light text-dark">${this.safeString(book.genre)}</span>` : ''}
                        ${book.isbn ? `<br><small class="text-muted">ISBN: ${this.safeString(book.isbn)}</small>` : ''}
                    </div>
                </td>
                <td>
                    <div>
                        <strong>${this.safeString(book.user?.name)}</strong><br>
                        <small class="text-muted">${this.safeString(book.user?.email)}</small><br>
                        <small class="text-muted">${this.safeString(book.user?.city || 'N/A')}</small>
                    </div>
                </td>
                <td>
                    <span class="${conditionClass}">
                        <i class="fas fa-book me-1"></i>${this.safeString(book.condition)}
                    </span>
                </td>
                <td>
                    <span class="badge status-badge ${statusClass}">${this.safeString(bookStatus)}</span>
                </td>
                <td>${addedDate}</td>
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

    // Utility function to safely handle strings
    safeString(str) {
        if (str === null || str === undefined) return 'N/A';
        return String(str);
    }

    // Utility function to format status strings
    formatStatus(status) {
        if (!status) return 'Unknown';
        return status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
    }

    // dispute management system
    async loadDisputes(filter = 'all') {
        try {
            console.log('🔄 Loading disputes with filter:', filter);

            const url = filter === 'all'
                ? `${this.ADMIN_API_BASE}/disputes.php`
                : `${this.ADMIN_API_BASE}/disputes.php?filter=${filter}`;

            const response = await fetch(url);
            const data = await response.json();

            console.log('📋 Disputes data received:', data);

            if (data.success) {
                this.renderDisputesTable(data.disputes);
                this.updateDisputeStats(data.disputes);
                // Update dashboard dispute count
                this.updateDashboardDisputeCount(data.disputes);
            } else {
                this.showToast('Error loading disputes', 'error');
            }
        } catch (error) {
            console.error('Error loading disputes:', error);
            this.showToast('Error loading disputes', 'error');
        }
    }

    // dispute statistics
    updateDisputeStats(disputes) {
        if (!disputes || !Array.isArray(disputes)) {
            console.error('Invalid disputes data for stats:', disputes);
            return;
        }

        const stats = {
            total: disputes.length,
            open: disputes.filter(d => (d.status || 'open') === 'open').length,
            under_review: disputes.filter(d => (d.status || 'open') === 'under_review').length,
            resolved: disputes.filter(d => (d.status || 'open') === 'resolved').length,
            urgent: disputes.filter(d => (d.priority || 'medium') === 'urgent').length
        };

        console.log('📊 Dispute stats calculated:', stats);

        // Update stats cards if they exist
        if (document.getElementById('totalDisputes')) {
            document.getElementById('totalDisputes').textContent = stats.total;
            document.getElementById('openDisputesCount').textContent = stats.open;
            document.getElementById('underReviewDisputes').textContent = stats.under_review;
            document.getElementById('resolvedDisputes').textContent = stats.resolved;
            document.getElementById('urgentDisputes').textContent = stats.urgent;
        }
    }

    // Update dispute count in dashboard
    updateDashboardDisputeCount(disputes) {
        if (!disputes || !Array.isArray(disputes)) {
            console.error('Invalid disputes data for dashboard count:', disputes);
            return;
        }

        const openDisputes = disputes.filter(dispute =>
            (dispute.status || 'open') === 'open' || (dispute.status || 'open') === 'under_review'
        ).length;

        console.log('🔢 Updating dashboard dispute count to:', openDisputes);

        // Update in dashboard stats card
        const openDisputesElement = document.getElementById('openDisputes');
        if (openDisputesElement) {
            openDisputesElement.textContent = openDisputes;
            console.log('✅ Dashboard dispute count updated successfully');
        } else {
            console.error('❌ openDisputes element not found!');
        }
    }

    // disputes table rendering with safe data handling
    renderDisputesTable(disputes) {
        const tbody = document.getElementById('disputesTableBody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (!disputes || disputes.length === 0) {
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
            const priority = dispute.priority || 'medium';
            const status = dispute.status || 'open';
            const priorityClass = `priority-${priority}`;
            const statusClass = `status-${status.replace('-', '_')}`;
            const isAssignedToMe = dispute.assigned_admin_id == this.adminData.admin_id;

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${dispute.id || 'N/A'}</td>
                <td>
                    <div>
                        <strong class="d-block">${this.safeString(dispute.title)}</strong>
                        <small class="text-muted">${this.safeString(dispute.description?.substring(0, 60) || 'No description')}...</small>
                    </div>
                </td>
                <td>
                    <div>
                        <small><strong>Complainant:</strong> ${this.safeString(dispute.complainant_name)}</small><br>
                        <small><strong>Respondent:</strong> ${this.safeString(dispute.respondent_name)}</small>
                        ${dispute.transaction_id ? `<br><small class="text-muted">Transaction #${dispute.transaction_id}</small>` : ''}
                    </div>
                </td>
                <td>
                    <span class="badge bg-light text-dark">${this.safeString(dispute.category)}</span>
                </td>
                <td>
                    <span class="badge badge-priority ${priorityClass}">
                        <i class="fas ${priority === 'urgent' ? 'fa-fire' :
                    priority === 'high' ? 'fa-exclamation-circle' :
                        priority === 'medium' ? 'fa-info-circle' : 'fa-arrow-down'} me-1"></i>
                        ${this.formatStatus(priority)}
                    </span>
                </td>
                <td>
                    <span class="badge status-badge ${statusClass}">
                        ${this.formatStatus(status)}
                    </span>
                </td>
                <td>
                    <small>${dispute.created_at ? new Date(dispute.created_at).toLocaleDateString() : 'N/A'}</small><br>
                    <small class="text-muted">${dispute.created_at ? new Date(dispute.created_at).toLocaleTimeString() : ''}</small>
                </td>
                <td>
                    <div>
                        ${dispute.assigned_admin_name ? `
                            <strong>${this.safeString(dispute.assigned_admin_name)}</strong>
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
                        ${status === 'open' && isAssignedToMe ? `
                            <button class="btn btn-sm btn-outline-info" 
                                    onclick="adminDashboard.startReview(${dispute.id})"
                                    title="Start Review">
                                <i class="fas fa-play"></i>
                            </button>
                        ` : ''}
                        ${status === 'under_review' && isAssignedToMe ? `
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

    // View transaction details
    viewTransaction(transactionId) {
        this.showToast(`Viewing transaction ID: ${transactionId} - Feature coming soon`, 'info');
    }

    // Export functions
    async exportUsers() {
        this.showToast('Exporting users data...', 'info');
        // Implementation for exporting users
    }

    async exportBooks() {
        this.showToast('Exporting books data...', 'info');
        // Implementation for exporting books
    }

    async exportDisputes() {
        this.showToast('Exporting disputes data...', 'info');
        // Implementation for exporting disputes
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

        // ==================== TRANSACTION MANAGEMENT ====================

    // Load transactions for admin
    async loadTransactions(filter = 'all') {
        try {
            console.log('🔄 Loading admin transactions...');
            
            const tbody = document.getElementById('transactionsTableBody');
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><div class="spinner-border"></div><p class="mt-2">Loading transactions...</p></td></tr>';

            const apiUrl = 'http://localhost/project/backend/api/transactions/all_transactions.php';
            console.log('🌐 Fetching from:', apiUrl);
            
            const response = await fetch(apiUrl);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('📋 Transactions data received:', data);

            if (data.success) {
                let transactions = data.transactions || [];
                console.log(`📊 Found ${transactions.length} transactions`);
                
                // Apply filter if needed
                if (filter !== 'all') {
                    transactions = transactions.filter(t => t.status === filter);
                    console.log(`📊 Filtered to ${transactions.length} transactions with status: ${filter}`);
                }
                
                // Format data to match expected structure
                transactions = transactions.map(t => ({
                    ...t,
                    book_cover: t.image_path || t.book_cover || '', // Handle different field names
                    payment_method: t.payment_method || 'Cash',
                    amount: parseFloat(t.amount || 0)
                }));
                
                this.renderTransactionsTable(transactions);
                this.updateTransactionStats(transactions);
                
                // Update count
                const countElement = document.getElementById('transactionsCount');
                if (countElement) {
                    countElement.textContent = transactions.length;
                }
                
            } else {
                const errorMsg = 'Error loading transactions: ' + (data.message || 'Unknown error');
                console.error('❌', errorMsg);
                this.showToast(errorMsg, 'error');
                this.renderNoTransactions();
            }
        } catch (error) {
            console.error('❌ Error loading transactions:', error);
            this.showToast('Error loading transactions: ' + error.message, 'error');
            this.renderNoTransactions();
        }
    }

    // Render transactions table
    renderTransactionsTable(transactions) {
        const tbody = document.getElementById('transactionsTableBody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (!transactions || transactions.length === 0) {
            this.renderNoTransactions();
            return;
        }

        transactions.sort((a, b) => new Date(b.created_at || b.transaction_date) - new Date(a.created_at || a.transaction_date));

        transactions.forEach(transaction => {
            const status = transaction.status || 'pending';
            const statusClass = `status-${status}`;
            const amount = parseFloat(transaction.amount || 0);
            const amountDisplay = amount > 0 ? `₨${amount.toFixed(2)}` : '<span class="text-muted">Free</span>';
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>#${transaction.id}</strong></td>
                <td>
                    <div class="d-flex align-items-center">
                        ${transaction.book_cover ? 
                            `<img src="${transaction.book_cover}" class="book-cover me-2" alt="${transaction.book_title}">` : 
                            `<div class="book-cover bg-light d-flex align-items-center justify-content-center me-2">
                                <i class="fas fa-book text-muted"></i>
                            </div>`
                        }
                        <div>
                            <strong>${this.safeString(transaction.book_title)}</strong><br>
                            <small class="text-muted">Book ID: ${transaction.book_id}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div>
                        <small><strong>Borrower:</strong> ${this.safeString(transaction.borrower_name)}</small><br>
                        <small><strong>Lender:</strong> ${this.safeString(transaction.lender_name)}</small>
                    </div>
                </td>
                <td>
                    <div>
                        <strong class="fs-5">${amountDisplay}</strong><br>
                        <small class="text-muted">${transaction.request_type || 'Borrow'}</small>
                    </div>
                </td>
                <td>
                    <span class="badge bg-light text-dark">${transaction.payment_method || 'Cash'}</span>
                </td>
                <td>
                    <span class="badge status-badge ${statusClass}">
                        ${status.charAt(0).toUpperCase() + status.slice(1)}
                    </span>
                </td>
                <td>
                    <small>${new Date(transaction.created_at || transaction.transaction_date).toLocaleDateString()}</small>
                </td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick="adminDashboard.viewTransactionDetails(${transaction.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    // Show no transactions message
    renderNoTransactions() {
        const tbody = document.getElementById('transactionsTableBody');
        if (!tbody) return;
        
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4">
                    <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                    <h5>No Transactions Found</h5>
                    <p class="text-muted">No transactions have been made yet.</p>
                </td>
            </tr>
        `;
    }

    // Update transaction statistics
    updateTransactionStats(transactions) {
        if (!transactions || !Array.isArray(transactions)) return;

        const stats = {
            total: transactions.length,
            completed: transactions.filter(t => t.status === 'completed').length,
            pending: transactions.filter(t => t.status === 'pending').length
        };

        if (document.getElementById('totalTransactions')) {
            document.getElementById('totalTransactions').textContent = stats.total;
        }
        
        if (document.getElementById('totalTransactionsCount')) {
            document.getElementById('totalTransactionsCount').textContent = stats.total;
            document.getElementById('completedTransactions').textContent = stats.completed;
            document.getElementById('ongoingTransactions').textContent = stats.pending;
        }
    }

    // View transaction details
    async viewTransactionDetails(transactionId) {
        try {
            const response = await fetch(`http://localhost/project/backend/api/transactions/get.php?id=${transactionId}`);
            const data = await response.json();
            
            if (data.success) {
                this.showTransactionModal(data.transaction);
            } else {
                this.showToast('Error loading transaction details', 'error');
            }
        } catch (error) {
            console.error('Error loading transaction details:', error);
            this.showToast('Error loading transaction details', 'error');
        }
    }

    // Export transactions
    exportTransactions() {
        window.open('http://localhost/project/backend/api/transactions/export.php', '_blank');
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
