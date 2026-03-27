/**
 * Real-time polling — Fetch API every 3 seconds
 */
(function() {
    const BASE_URL = document.querySelector('meta[name="base-url"]')?.content ?? '';

    async function poll() {
        try {
            const res = await fetch(`${BASE_URL}/api/realtime/status`, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'Fetch' }
            });
            if (!res.ok) return;
            const data = await res.json();

            // Update online count
            const onlineEls = document.querySelectorAll('[data-online-count]');
            onlineEls.forEach(el => el.textContent = data.online_count ?? '-');
            const footerOnline = document.getElementById('footer-online');
            if (footerOnline) footerOnline.textContent = data.online_count ?? '-';

            // Update notification badge
            if (data.unread_notifications !== undefined) {
                const badges = document.querySelectorAll('[data-notif-count]');
                badges.forEach(el => {
                    el.textContent = data.unread_notifications;
                    el.classList.toggle('hidden', data.unread_notifications === 0);
                });
            }

            // Update cart count
            if (data.cart_count !== undefined) {
                const cartEl = document.getElementById('cart-count');
                if (cartEl) {
                    cartEl.textContent = data.cart_count;
                    cartEl.classList.toggle('hidden', data.cart_count === 0);
                }
            }

            // Update balance
            if (data.balance !== undefined) {
                const balEls = document.querySelectorAll('[data-balance]');
                balEls.forEach(el => el.textContent = data.balance);
            }

            // Show delivery notification
            if (data.recent_delivery) {
                showToast(data.recent_delivery.message, 'success');
            }

        } catch (e) {
            // Silently fail
        }
    }

    // Poll every 3 seconds
    setInterval(poll, 3000);
    poll(); // Initial poll

    // Toast notification
    window.showToast = function(message, icon = 'info') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: icon,
                title: message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        }
    };
})();
