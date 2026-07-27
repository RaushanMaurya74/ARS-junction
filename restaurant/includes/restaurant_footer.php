        </div>
        <!-- End of Main Content -->
    </div>
    <!-- End of Content Wrapper -->
</div>
<!-- End of Wrapper -->

<!-- Bootstrap Bundle with Popper JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Sidebar Toggle Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const sidebar = document.getElementById("sidebar");
    const toggle = document.getElementById("sidebarToggle");
    if (sidebar && toggle) {
        toggle.addEventListener("click", function(e) {
            e.preventDefault();
            sidebar.classList.toggle("d-none");
        });
    }
});

// Web Audio Synth for Restaurant notifications (Bell chime)
let audioContextInstance = null;

function getAudioContext() {
    if (!audioContextInstance) {
        audioContextInstance = new (window.AudioContext || window.webkitAudioContext)();
    }
    if (audioContextInstance.state === 'suspended') {
        audioContextInstance.resume();
    }
    return audioContextInstance;
}

function playRestaurantNotificationSound() {
    try {
        const audioCtx = getAudioContext();
        // Sweet bell chime (G5, C6, E6 chimes)
        playBeep(audioCtx, 783.99, 0.15, 'sine'); // G5
        setTimeout(() => playBeep(audioCtx, 1046.50, 0.15, 'sine'), 100); // C6
        setTimeout(() => playBeep(audioCtx, 1318.51, 0.25, 'sine'), 200); // E6
    } catch (e) {
        console.warn('AudioContext failed:', e);
    }
}

function playBeep(ctx, frequency, duration, type) {
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = type;
    osc.frequency.value = frequency;
    gain.gain.setValueAtTime(0.1, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.00001, ctx.currentTime + duration);
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start();
    osc.stop(ctx.currentTime + duration);
}

// User interaction gesture helper to resume AudioContext (browser security)
document.addEventListener('click', function() {
    try {
        getAudioContext();
    } catch (e) {}
}, { once: true });

document.addEventListener('DOMContentLoaded', function() {
    // Only start polling if restaurant session variables are likely present
    let lastPendingCount = null;
    let lastLatestOrderId = null;

    function pollRestaurantOrders() {
        fetch('../api/poll_notifications.php?role=restaurant')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (lastLatestOrderId === null) {
                    lastLatestOrderId = data.latest_order_id;
                    lastPendingCount = data.pending_count;
                    return;
                }

                if (data.latest_order_id > lastLatestOrderId || data.pending_count > lastPendingCount) {
                    playRestaurantNotificationSound();

                    // Insert temporary floating bootstrap toast notification
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger border-primary alert-dismissible fade show position-fixed top-0 end-0 m-4 shadow-lg';
                    alertDiv.style.zIndex = '99999';
                    alertDiv.style.minWidth = '300px';
                    alertDiv.innerHTML = `
                        <div class="d-flex align-items-center">
                            <i class="fas fa-bell fa-2x text-danger me-3 animate-bounce"></i>
                            <div>
                                <strong>New Pending Order!</strong><br>
                                Order #${data.latest_order_id} requires your approval.
                            </div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                    document.body.appendChild(alertDiv);
                    
                    // Auto-dismiss alert after 6 seconds
                    setTimeout(() => {
                        alertDiv.classList.remove('show');
                        setTimeout(() => alertDiv.remove(), 500);
                    }, 6000);

                    // Auto-refresh order pages if currently viewed
                    const currentFile = window.location.pathname.split("/").pop();
                    if (currentFile === 'dashboard.php' || currentFile === 'index.php') {
                        setTimeout(() => {
                            window.location.reload();
                        }, 2500);
                    }
                }

                lastLatestOrderId = data.latest_order_id;
                lastPendingCount = data.pending_count;
            }
        })
        .catch(err => console.error('Error polling restaurant notifications:', err));
    }

    // Poll every 5 seconds
    setInterval(pollRestaurantOrders, 5000);
    // Initial poll
    setTimeout(pollRestaurantOrders, 1200);
});
</script>
</body>
</html>
