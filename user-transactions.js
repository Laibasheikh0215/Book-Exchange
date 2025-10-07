class UserTransactions {
    constructor() {
        this.currentUser = JSON.parse(localStorage.getItem('user'));
        this.init();
    }

    init() {
        if (!this.currentUser || !this.currentUser.loggedIn) {
            window.location.href = 'index.html';
            return;
        }

        this.setupEventListeners();
        this.loadOutgoingRequests();
        this.loadAvailableBooks();
        this.setMinReturnDate();
    }

    setupEventListeners() {
        // Request type change
        document.querySelectorAll('input[name="requestType"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.handleRequestTypeChange(e.target.value);
            });
        });

        // Book selection change
        document.getElementById('bookSelect').addEventListener('change', (e) => {
            this.loadBookDetails(e.target.value);
        });

        // Form submission
        document.getElementById('newRequestForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitNewRequest();
        });

        // Tab change
        document.getElementById('transactionTabs').addEventListener('shown.bs.tab', (e) => {
            if (e.target.getAttribute('href') === '#incoming') {
                this.loadIncomingRequests();
            }
        });
    }

    setMinReturnDate() {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        document.getElementById('returnDate').min = tomorrow.toISOString().split('T')[0];
    }

    handleRequestTypeChange(type) {
        const swapSection = document.getElementById('swapBookSection');
        const returnSection = document.getElementById('returnDateSection');
        
        if (type === 'Swap') {
            swapSection.style.display = 'block';
            returnSection.style.display = 'none';
            this.loadUserBooksForSwap();
        } else {
            swapSection.style.display = 'none';
            returnSection.style.display = 'block';
        }
    }

    async loadAvailableBooks() {
        try {
            const response = await fetch(`http://localhost/project/backend/api/books/get_available.php`);
            const data = await response.json();
            
            const bookSelect = document.getElementById('bookSelect');
            bookSelect.innerHTML = '<option value="">Choose a book...</option>';
            
            if (data.success && data.books) {
                data.books.forEach(book => {
                    // Don't show user's own books
                    if (book.user_id !== this.currentUser.user_id) {
                        const option = document.createElement('option');
                        option.value = book.id;
                        option.textContent = `${book.title} by ${book.author} (${book.user_name})`;
                        option.setAttribute('data-owner', book.user_id);
                        bookSelect.appendChild(option);
                    }
                });
            }
        } catch (error) {
            console.error('Error loading books:', error);
        }
    }

    async loadUserBooksForSwap() {
        try {
            const response = await fetch(`http://localhost/project/backend/api/books/user_books.php?user_id=${this.currentUser.user_id}`);
            const data = await response.json();
            
            const swapSelect = document.getElementById('swapBookSelect');
            swapSelect.innerHTML = '<option value="">Choose your book to swap...</option>';
            
            if (data.success && data.books) {
                data.books.forEach(book => {
                    if (book.status === 'Available') {
                        const option = document.createElement('option');
                        option.value = book.id;
                        option.textContent = `${book.title} by ${book.author}`;
                        swapSelect.appendChild(option);
                    }
                });
            }
        } catch (error) {
            console.error('Error loading user books:', error);
        }
    }

    async loadBookDetails(bookId) {
        if (!bookId) {
            document.getElementById('bookDetails').innerHTML = '<p class="text-muted">Select a book to see details</p>';
            return;
        }

        try {
            const response = await fetch(`http://localhost/project/backend/api/books/get_one.php?id=${bookId}`);
            const data = await response.json();
            
            if (data.success && data.book) {
                const book = data.book;
                document.getElementById('bookDetails').innerHTML = `
                    <h6>${book.title}</h6>
                    <p class="text-muted">by ${book.author}</p>
                    <p><strong>Genre:</strong> ${book.genre || 'N/A'}</p>
                    <p><strong>Condition:</strong> ${book.condition}</p>
                    <p><strong>Owner:</strong> ${book.user_name}</p>
                    <p class="small text-muted">${book.description || 'No description available'}</p>
                `;
            }
        } catch (error) {
            console.error('Error loading book details:', error);
        }
    }

    async loadOutgoingRequests() {
        try {
            const response = await fetch(`http://localhost/project/backend/api/transactions/outgoing.php?user_id=${this.currentUser.user_id}`);
            const data = await response.json();
            
            this.renderOutgoingRequests(data.requests || []);
        } catch (error) {
            console.error('Error loading outgoing requests:', error);
        }
    }

    async loadIncomingRequests() {
        try {
            const response = await fetch(`http://localhost/project/backend/api/transactions/incoming.php?user_id=${this.currentUser.user_id}`);
            const data = await response.json();
            
            this.renderIncomingRequests(data.requests || []);
        } catch (error) {
            console.error('Error loading incoming requests:', error);
        }
    }

    renderOutgoingRequests(requests) {
        const container = document.getElementById('outgoingRequests');
        
        if (requests.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-paper-plane fa-3x text-muted mb-3"></i>
                    <h5>No Outgoing Requests</h5>
                    <p class="text-muted">You haven't sent any book requests yet.</p>
                    <a href="#new" class="btn btn-primary" data-bs-toggle="tab">Make Your First Request</a>
                </div>
            `;
            return;
        }

        container.innerHTML = requests.map(request => `
            <div class="card transaction-card status-${request.status.toLowerCase()} mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="card-title">${request.book_title}</h5>
                            <p class="card-text">
                                <strong>Owner:</strong> ${request.owner_name}<br>
                                <strong>Type:</strong> ${request.request_type}<br>
                                <strong>Message:</strong> ${request.message}<br>
                                <strong>Date:</strong> ${new Date(request.created_at).toLocaleDateString()}
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-${this.getStatusColor(request.status)}">${request.status}</span>
                            ${request.status === 'Pending' ? `
                                <button class="btn btn-sm btn-outline-danger mt-2" onclick="userTransactions.cancelRequest(${request.id})">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    renderIncomingRequests(requests) {
        const container = document.getElementById('incomingRequests');
        
        if (requests.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5>No Incoming Requests</h5>
                    <p class="text-muted">No one has requested your books yet.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = requests.map(request => `
            <div class="card transaction-card status-${request.status.toLowerCase()} mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="card-title">${request.book_title}</h5>
                            <p class="card-text">
                                <strong>Requester:</strong> ${request.requester_name}<br>
                                <strong>Type:</strong> ${request.request_type}<br>
                                <strong>Message:</strong> ${request.message}<br>
                                <strong>Date:</strong> ${new Date(request.created_at).toLocaleDateString()}
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-${this.getStatusColor(request.status)} mb-2">${request.status}</span>
                            ${request.status === 'Pending' ? `
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-success" onclick="userTransactions.approveRequest(${request.id})">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="userTransactions.rejectRequest(${request.id})">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    getStatusColor(status) {
        const colors = {
            'Pending': 'warning',
            'Approved': 'success',
            'Rejected': 'danger',
            'Completed': 'secondary',
            'Cancelled': 'dark'
        };
        return colors[status] || 'secondary';
    }

    async submitNewRequest() {
        const form = document.getElementById('newRequestForm');
        const formData = new FormData(form);
        
        const requestData = {
            book_id: document.getElementById('bookSelect').value,
            request_type: document.querySelector('input[name="requestType"]:checked').value,
            message: document.getElementById('message').value,
            requester_id: this.currentUser.user_id,
            owner_id: document.getElementById('bookSelect').selectedOptions[0].getAttribute('data-owner')
        };

        if (requestData.request_type === 'Borrow') {
            requestData.proposed_return_date = document.getElementById('returnDate').value;
        } else if (requestData.request_type === 'Swap') {
            requestData.swap_book_id = document.getElementById('swapBookSelect').value;
        }

        try {
            const response = await fetch('http://localhost/project/backend/api/transactions/create.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });

            const data = await response.json();

            if (data.success) {
                alert('Request sent successfully!');
                form.reset();
                this.loadOutgoingRequests();
                // Switch to outgoing tab
                document.querySelector('[href="#outgoing"]').click();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error sending request:', error);
            alert('Error sending request. Please try again.');
        }
    }

    async cancelRequest(requestId) {
        if (confirm('Are you sure you want to cancel this request?')) {
            try {
                const response = await fetch('http://localhost/project/backend/api/transactions/cancel.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        request_id: requestId,
                        user_id: this.currentUser.user_id
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.loadOutgoingRequests();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error cancelling request:', error);
                alert('Error cancelling request.');
            }
        }
    }

    async approveRequest(requestId) {
        if (confirm('Are you sure you want to approve this request?')) {
            try {
                const response = await fetch('http://localhost/project/backend/api/transactions/approve.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        request_id: requestId,
                        user_id: this.currentUser.user_id
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.loadIncomingRequests();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error approving request:', error);
                alert('Error approving request.');
            }
        }
    }

    async rejectRequest(requestId) {
        if (confirm('Are you sure you want to reject this request?')) {
            try {
                const response = await fetch('http://localhost/project/backend/api/transactions/reject.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        request_id: requestId,
                        user_id: this.currentUser.user_id
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.loadIncomingRequests();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error rejecting request:', error);
                alert('Error rejecting request.');
            }
        }
    }
}

// Global functions
function logout() {
    localStorage.removeItem('user');
    window.location.href = 'index.html';
}

// Initialize
const userTransactions = new UserTransactions();