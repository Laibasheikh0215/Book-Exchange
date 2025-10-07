<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Requests - Community Book Exchange</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .request-card {
            border-left: 4px solid;
            margin-bottom: 15px;
            transition: transform 0.2s;
        }
        .request-card:hover {
            transform: translateY(-2px);
        }
        .request-card.Pending { border-left-color: #ffc107; }
        .request-card.Approved { border-left-color: #198754; }
        .request-card.Rejected { border-left-color: #dc3545; }
        .request-card.Completed { border-left-color: #6c757d; }
        
        .badge-pending { background-color: #ffc107; }
        .badge-approved { background-color: #198754; }
        .badge-rejected { background-color: #dc3545; }
        .badge-completed { background-color: #6c757d; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4" style="background-color: #4e73df;">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-book-exchange me-2"></i>BookSwap
            </a>
            <!-- Add your navigation items here -->
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Book Requests</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestModal">
                <i class="fas fa-plus me-1"></i> New Request
            </button>
        </div>
        
        <ul class="nav nav-tabs" id="requestsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="incoming-tab" data-bs-toggle="tab" data-bs-target="#incoming" type="button" role="tab">
                    Incoming Requests <span class="badge bg-warning" id="incoming-count">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="outgoing-tab" data-bs-toggle="tab" data-bs-target="#outgoing" type="button" role="tab">
                    Outgoing Requests <span class="badge bg-primary" id="outgoing-count">0</span>
                </button>
            </li>
        </ul>

        <div class="tab-content mt-3" id="requestsTabContent">
            <div class="tab-pane fade show active" id="incoming" role="tabpanel">
                <div id="incoming-requests">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="outgoing" role="tabpanel">
                <div id="outgoing-requests">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createRequestForm">
                        <div class="mb-3">
                            <label class="form-label">Book ID</label>
                            <input type="number" class="form-control" name="book_id" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Request Type</label>
                            <select class="form-select" name="request_type" required>
                                <option value="">Select type</option>
                                <option value="Borrow">Borrow</option>
                                <option value="Swap">Swap</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" name="message" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Proposed Return Date (for borrowing)</label>
                            <input type="date" class="form-control" name="proposed_return_date">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="createRequest()">Send Request</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const API_BASE = 'http://localhost/book-exchange/backend/api';
        let currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;

        async function loadRequests() {
            try {
                const response = await fetch(`${API_BASE}/requests/my_requests.php`);
                const data = await response.json();
                
                if (data.success) {
                    displayIncomingRequests(data.incoming_requests);
                    displayOutgoingRequests(data.outgoing_requests);
                } else {
                    showError('Failed to load requests');
                }
                
            } catch (error) {
                console.error('Error loading requests:', error);
                showError('Error loading requests');
            }
        }

        function displayIncomingRequests(requests) {
            const container = document.getElementById('incoming-requests');
            document.getElementById('incoming-count').textContent = requests.length;
            
            if (requests.length === 0) {
                container.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-inbox me-2"></i>
                        No incoming requests
                    </div>
                `;
                return;
            }

            container.innerHTML = requests.map(request => `
                <div class="card request-card ${request.status}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="card-title">${request.book_title}</h5>
                            <span class="badge badge-${request.status.toLowerCase()}">
                                ${request.status}
                            </span>
                        </div>
                        <h6 class="card-subtitle mb-2 text-muted">by ${request.book_author}</h6>
                        
                        <p><strong>Requester:</strong> ${request.requester_name}</p>
                        <p><strong>Type:</strong> ${request.request_type}</p>
                        
                        ${request.message ? `<p><strong>Message:</strong> ${request.message}</p>` : ''}
                        
                        ${request.proposed_return_date ? `
                            <p><strong>Proposed Return:</strong> ${new Date(request.proposed_return_date).toLocaleDateString()}</p>
                        ` : ''}
                        
                        <p class="text-muted small">
                            Requested on: ${new Date(request.created_at).toLocaleString()}
                        </p>
                        
                        ${request.status === 'Pending' ? `
                        <div class="mt-3">
                            <button class="btn btn-success btn-sm me-2" onclick="updateRequest(${request.id}, 'Approved')">
                                <i class="fas fa-check me-1"></i> Approve
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="updateRequest(${request.id}, 'Rejected')">
                                <i class="fas fa-times me-1"></i> Reject
                            </button>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        }

        function displayOutgoingRequests(requests) {
            const container = document.getElementById('outgoing-requests');
            document.getElementById('outgoing-count').textContent = requests.length;
            
            if (requests.length === 0) {
                container.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-paper-plane me-2"></i>
                        No outgoing requests
                    </div>
                `;
                return;
            }

            container.innerHTML = requests.map(request => `
                <div class="card request-card ${request.status}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="card-title">${request.book_title}</h5>
                            <span class="badge badge-${request.status.toLowerCase()}">
                                ${request.status}
                            </span>
                        </div>
                        <h6 class="card-subtitle mb-2 text-muted">by ${request.book_author}</h6>
                        
                        <p><strong>Owner:</strong> ${request.owner_name}</p>
                        <p><strong>Type:</strong> ${request.request_type}</p>
                        
                        ${request.message ? `<p><strong>Your Message:</strong> ${request.message}</p>` : ''}
                        
                        ${request.proposed_return_date ? `
                            <p><strong>Proposed Return:</strong> ${new Date(request.proposed_return_date).toLocaleDateString()}</p>
                        ` : ''}
                        
                        <p class="text-muted small">
                            Requested on: ${new Date(request.created_at).toLocaleString()}
                        </p>
                        
                        ${request.status === 'Pending' ? `
                        <button class="btn btn-warning btn-sm" onclick="cancelRequest(${request.id})">
                            <i class="fas fa-ban me-1"></i> Cancel Request
                        </button>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        }

        async function createRequest() {
            const form = document.getElementById('createRequestForm');
            const formData = new FormData(form);
            
            const requestData = {
                book_id: parseInt(formData.get('book_id')),
                request_type: formData.get('request_type'),
                message: formData.get('message'),
                proposed_return_date: formData.get('proposed_return_date') || null
            };

            try {
                const response = await fetch(`${API_BASE}/requests/create.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    alert('Request sent successfully!');
                    $('#requestModal').modal('hide');
                    form.reset();
                    loadRequests();
                } else {
                    alert('Error: ' + result.message);
                }
                
            } catch (error) {
                console.error('Error creating request:', error);
                alert('Error sending request');
            }
        }

        async function updateRequest(requestId, status) {
            if (!confirm(`Are you sure you want to ${status.toLowerCase()} this request?`)) {
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/requests/update.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        request_id: requestId,
                        status: status
                    })
                });
                
                const result = await response.json();
                alert(result.message);
                loadRequests();
                
            } catch (error) {
                console.error('Error updating request:', error);
                alert('Error updating request');
            }
        }

        function showError(message) {
            const container = document.getElementById('incoming-requests');
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${message}
                </div>
            `;
        }

        // Load requests on page load
        document.addEventListener('DOMContentLoaded', loadRequests);
    </script>
</body>
</html>