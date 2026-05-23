/**
 * Lifeline Bank - Premium System Javascript controls
 * Manages active states, notifications, charts, and interactive confirmation modals
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Sidebar Active State Sync
    const currentPath = window.location.pathname.split('/').pop() || 'index.php';
    const menuLinks = document.querySelectorAll('.menu-item-link');
    
    menuLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        // Match exact or related files (e.g., donor-add.php should highlight donors.php)
        if (linkPath === currentPath) {
            link.classList.add('active');
        } else if (
            (currentPath.includes('donor') && linkPath === 'donors.php') ||
            (currentPath.includes('request') && linkPath === 'requests.php')
        ) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });

    // 2. Mobile Responsive Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarPanel = document.getElementById('sidebarPanel');
    const sidebarClose = document.getElementById('sidebarClose');

    if (sidebarToggle && sidebarPanel) {
        sidebarToggle.addEventListener('click', function () {
            sidebarPanel.classList.add('show');
        });
    }

    if (sidebarClose && sidebarPanel) {
        sidebarClose.addEventListener('click', function () {
            sidebarPanel.classList.remove('show');
        });
    }

    // 3. Autoclose Toast Notifications
    const toastAlerts = document.querySelectorAll('.alert-toast');
    toastAlerts.forEach(toast => {
        // Automatically fade out after 4.5 seconds
        setTimeout(() => {
            closeToast(toast);
        }, 4500);

        // Allow closing on close button click
        const closeBtn = toast.querySelector('.alert-toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                closeToast(toast);
            });
        }
    });

    function closeToast(toast) {
        toast.style.transition = 'all 0.3s ease';
        toast.style.transform = 'translateY(-20px) scale(0.9)';
        toast.style.opacity = '0';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }

    // 4. Premium Double-Confirmation Prompt for Delete Actions
    // Dynamically hooks onto any delete-btn link
    const deleteButtons = document.querySelectorAll('.delete-trigger');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const targetUrl = this.getAttribute('href');
            const itemName = this.getAttribute('data-item') || 'this record';
            
            showDeleteConfirmModal(itemName, targetUrl);
        });
    });

    function showDeleteConfirmModal(itemName, targetUrl) {
        // Create modal element dynamically if it doesn't exist
        let modalEl = document.getElementById('premiumConfirmModal');
        if (!modalEl) {
            const modalHtml = `
                <div class="modal fade" id="premiumConfirmModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content modal-content-premium">
                            <div class="modal-header modal-header-premium">
                                <h5 class="modal-title text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i> Confirm Action</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4 text-center">
                                <div class="mb-3 text-danger" style="font-size: 40px;">
                                    <i class="fa-solid fa-trash-can-arrow-up"></i>
                                </div>
                                <h4>Are you absolutely sure?</h4>
                                <p class="text-muted mt-2">You are about to delete <strong>${itemName}</strong>. This operational action is permanent and cannot be undone.</p>
                            </div>
                            <div class="modal-footer modal-footer-premium justify-content-center">
                                <button type="button" class="btn btn-premium-secondary px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                                <a id="modalConfirmDeleteBtn" href="#" class="btn btn-premium-primary px-4 py-2">Confirm Delete</a>
                            </div>
                        </div>
                    </div>
                </div>`;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            modalEl = document.getElementById('premiumConfirmModal');
        }

        // Set name and href
        const confirmBtn = modalEl.querySelector('#modalConfirmDeleteBtn');
        confirmBtn.setAttribute('href', targetUrl);
        
        // Show modal using Bootstrap native javascript api
        const bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
    }
});

/**
 * Initialize dynamic blood stock visual charts on dashboard
 * @param {Array} labels Blood groups (e.g. ['A+', 'B+', ...])
 * @param {Array} data Units available (e.g. [15, 20, ...])
 */
function initBloodStockChart(labels, data) {
    const ctx = document.getElementById('bloodStockChart');
    if (!ctx) return;

    // Premium styling config
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Blood Stock (Units)',
                data: data,
                backgroundColor: [
                    'rgba(220, 53, 69, 0.75)',  // Vivid crimson for blood indicators
                    'rgba(220, 53, 69, 0.75)',
                    'rgba(220, 53, 69, 0.75)',
                    'rgba(220, 53, 69, 0.75)',
                    'rgba(220, 53, 69, 0.75)',
                    'rgba(220, 53, 69, 0.75)',
                    'rgba(220, 53, 69, 0.75)',
                    'rgba(220, 53, 69, 0.75)'
                ],
                borderColor: [
                    '#dc3545',
                    '#dc3545',
                    '#dc3545',
                    '#dc3545',
                    '#dc3545',
                    '#dc3545',
                    '#dc3545',
                    '#dc3545'
                ],
                borderWidth: 1.5,
                borderRadius: 6,
                hoverBackgroundColor: 'rgba(255, 71, 87, 0.95)',
                hoverBorderColor: '#ff4757',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // Hide since it's obvious
                },
                tooltip: {
                    padding: 12,
                    backgroundColor: '#151b23',
                    titleFont: { size: 14, weight: 'bold', family: 'Outfit' },
                    bodyFont: { size: 13, family: 'Outfit' },
                    borderColor: '#30363d',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return `${context.parsed.y} Units Available`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#8b949e',
                        font: { family: 'Outfit', size: 12 },
                        stepSize: 5
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#f0f6fc',
                        font: { family: 'Outfit', size: 13, weight: 'bold' }
                    }
                }
            }
        }
    });
}
