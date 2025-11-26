document.addEventListener('DOMContentLoaded', function () {
    const toggleYes = document.querySelector('.twofactor-toggle input[value="1"]');
    const toggleNo  = document.querySelector('.twofactor-toggle input[value="0"]');

    if (!toggleYes || !toggleNo) return;

    const sendRequest = (enabled) => {
        fetch('/s/mautic-twofactor/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'enabled=' + (enabled ? '1' : '0')
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Mautic.showNotification(data.message, 'success');
            } else {
                Mautic.showNotification('Something went wrong!', 'error');
            }
        })
        .catch(() => {
            Mautic.showNotification('Error while saving 2FA preference', 'error');
        });
    };

    toggleYes.addEventListener('click', () => sendRequest(true));
    toggleNo.addEventListener('click', () => sendRequest(false));
});
