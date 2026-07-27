        </div>
        <!-- End of Main Content -->

        <!-- Footer -->
        <footer class="sticky-footer bg-white mt-auto">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Copyright &copy; <?php echo date('Y'); ?> ARS JUNCTION</span>
                </div>
            </div>
        </footer>
        <!-- End of Footer -->
    </div>
    <!-- End of Content Wrapper -->
</div>
<!-- End of Page Wrapper -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal-->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <a class="btn btn-primary" href="logout.php">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Admin JS -->
<script src="../js/admin.js"></script>

<script>
// Web Audio Synth for Chimes (requiring no assets/files)
function playAdminNotificationSound() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        // High double alert chime for Admin (D5 then A5)
        playBeep(audioCtx, 587.33, 0.15, 'triangle'); 
        setTimeout(() => playBeep(audioCtx, 880, 0.25, 'sine'), 150);
    } catch (e) {
        console.warn('AudioContext failed:', e);
    }
}

function playBeep(ctx, frequency, duration, type) {
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = type;
    osc.frequency.value = frequency;
    gain.gain.setValueAtTime(0.12, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.00001, ctx.currentTime + duration);
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start();
    osc.stop(ctx.currentTime + duration);
}

document.addEventListener('DOMContentLoaded', function() {
    let lastPendingCount = null;
    let lastLatestOrderId = null;

    function pollAdminOrders() {
        fetch('../api/poll_notifications.php?role=admin')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // If it's the first poll, initialize variables without playing sound
                if (lastLatestOrderId === null) {
                    lastLatestOrderId = data.latest_order_id;
                    lastPendingCount = data.pending_count;
                    return;
                }

                // If a new order has appeared
                if (data.latest_order_id > lastLatestOrderId || data.pending_count > lastPendingCount) {
                    playAdminNotificationSound();
                    
                    // Show visual alert in admin panel if helper exists, otherwise default alert
                    if (typeof showToast === 'function') {
                        showToast('New Pending Order Received! Please check the Orders tab.', 'warning');
                    } else {
                        // Dynamically insert a temporary toast banner
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-warning border-primary alert-dismissible fade show position-fixed top-0 end-0 m-3 shadow-lg';
                        alertDiv.style.zIndex = '9999';
                        alertDiv.innerHTML = `
                            <strong><i class="fas fa-shopping-cart text-primary me-2"></i>New Order!</strong> A new customer order #${data.latest_order_id} has been placed.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        `;
                        document.body.appendChild(alertDiv);
                        setTimeout(() => {
                            $(alertDiv).alert('close');
                        }, 5000);
                    }

                    // Auto-refresh the dashboard table if we are on dashboard.php or orders.php
                    const currentFile = window.location.pathname.split("/").pop();
                    if (currentFile === 'dashboard.php' || currentFile === 'orders.php') {
                        setTimeout(() => {
                            window.location.reload();
                        }, 2500); // Reload after 2.5 seconds so they can see the banner first
                    }
                }

                lastLatestOrderId = data.latest_order_id;
                lastPendingCount = data.pending_count;
            }
        })
        .catch(err => console.error('Error polling admin notifications:', err));
    }

    // Start polling every 5 seconds
    setInterval(pollAdminOrders, 5000);
    // Initial check
    setTimeout(pollAdminOrders, 1000);
});
</script>

<?php if (isset($extra_js)): echo $extra_js; endif; ?>

</body>
</html>
