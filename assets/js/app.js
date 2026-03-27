/**
 * App.js — Global utilities
 */
const App = {
    baseUrl: document.querySelector('meta[name="base-url"]')?.content ?? '',

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

// Buy now — direct checkout (no cart)
async function buyNow(productId, serverId, price, onePerUser = false) {
    let qty = 1;

    // ถ้าไม่ใช่ one_per_user ให้กรอกจำนวนก่อน
    if (!onePerUser) {
        const { value, isConfirmed } = await Swal.fire({
            title: 'ระบุจำนวนที่ต้องการ',
            input: 'number',
            inputValue: 1,
            inputAttributes: { min: 1, max: 99, step: 1 },
            showCancelButton: true,
            confirmButtonText: 'ถัดไป',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: 'var(--color-primary)',
            inputValidator: (v) => (!v || v < 1) ? 'กรุณาระบุจำนวน' : null,
        });
        if (!isConfirmed) return;
        qty = Math.max(1, parseInt(value) || 1);
    }

    const total = new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2 }).format(price * qty);
    const { isConfirmed } = await App.confirm(
        'ยืนยันการซื้อ',
        `จำนวน ${qty} ชิ้น — รวม ${total} ฿`,
        'question'
    );
    if (!isConfirmed) return;

    try {
        const res = await App.post('api/shop/buynow', { product_id: productId, server_id: serverId, quantity: qty });
        if (res.success) {
            App.toast(res.message || 'ซื้อสำเร็จ!');
            setTimeout(() => { location.href = res.redirect || '/orders'; }, 1500);
        } else {
            App.toast(res.message || 'เกิดข้อผิดพลาด', 'error');
        }
    } catch (e) {
        App.toast('เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
    }
}
