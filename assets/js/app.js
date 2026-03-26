/**
 * App.js — Global utilities
 */
const App = {
    baseUrl: document.querySelector('meta[name="base-url"]')?.content || '/mcstore',

    async fetchJson(url, options = {}) {
        const defaults = {
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'Fetch',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        };
        const merged = { ...defaults, ...options, headers: { ...defaults.headers, ...(options.headers || {}) }};
        const res = await fetch(this.baseUrl + '/' + url.replace(/^\//, ''), merged);
        return res.json();
    },

    async post(url, data = {}) {
        return this.fetchJson(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    confirm(title, text, icon = 'warning') {
        return Swal.fire({
            title, text, icon,
            showCancelButton: true,
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: 'var(--color-primary)',
        });
    },

    toast(message, icon = 'success') {
        Swal.fire({
            toast: true, position: 'top-end', icon, title: message,
            showConfirmButton: false, timer: 3000, timerProgressBar: true,
        });
    },

    // Copy to clipboard
    async copyText(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.toast('คัดลอกแล้ว!');
        } catch {
            this.toast('คัดลอกไม่สำเร็จ', 'error');
        }
    },

    // Format money
    money(amount) {
        return new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2 }).format(amount) + ' ฿';
    }
};

// Add to cart function
async function addToCart(productId, serverId, qty = 1) {
    const res = await App.post('api/cart/add', { product_id: productId, server_id: serverId, quantity: qty });
    if (res.success) {
        App.toast(res.message || 'เพิ่มลงตะกร้าแล้ว!');
        const cartEl = document.getElementById('cart-count');
        if (cartEl && res.cart_count !== undefined) {
            cartEl.textContent = res.cart_count;
            cartEl.classList.remove('hidden');
        }
    } else {
        App.toast(res.message || 'เกิดข้อผิดพลาด', 'error');
    }
}

// Remove from cart
async function removeFromCart(itemId) {
    const result = await App.confirm('ลบสินค้า?', 'ต้องการลบสินค้านี้ออกจากตะกร้า?');
    if (result.isConfirmed) {
        const res = await App.post('api/cart/remove', { item_id: itemId });
        if (res.success) {
            location.reload();
        } else {
            App.toast(res.message || 'เกิดข้อผิดพลาด', 'error');
        }
    }
}
