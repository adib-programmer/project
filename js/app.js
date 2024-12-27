// JavaScript for handling notifications and interactions
document.addEventListener('DOMContentLoaded', () => {
    const notificationElements = document.querySelectorAll('.notification');
    notificationElements.forEach(notification => {
        notification.addEventListener('click', () => {
            notification.style.display = 'none';
        });
    });
});
