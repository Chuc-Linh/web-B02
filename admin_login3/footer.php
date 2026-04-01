<script>

// logout toàn bộ tab
window.addEventListener('storage', function(event) {
    if (event.key === 'force-logout-timestamp') {
        alert('Bạn đã bị đăng xuất từ tab khác!');
        window.location.replace('login.php');
    }
});

// kiểm tra session mỗi 30s
setInterval(async function() {

    try {

        const res = await fetch('check_session.php', {
            cache: 'no-store',
            credentials: 'same-origin'
        });

        if (!res.ok) return;

        const data = await res.json();

        if (!data.logged_in) {
            window.location.replace('login.php');
        }

    } catch (e) {
        console.warn('Check session lỗi:', e);
    }

}, 30000);

</script>