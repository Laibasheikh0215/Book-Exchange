// For Exclusively suggested section
function swapPic(eId, newPic) {
    document.getElementById(eId).src = newPic;
}

// for night book section
// Example JavaScript (Hover effect)
document.querySelector('.book-photo').addEventListener('mouseenter', function () {
    this.style.transform = 'scale(1.1)';
    this.style.transition = '0.3s';
});
document.querySelector('.book-photo').addEventListener('mouseleave', function () {
    this.style.transform = 'scale(1)';
});

//   for best investment
// Trigger animation on scroll
const elements = document.querySelectorAll('.fade-in-up');
window.addEventListener('scroll', () => {
    elements.forEach(el => {
        const rect = el.getBoundingClientRect();
        if (rect.top < window.innerHeight - 50) {
            el.style.animationPlayState = 'running';
        }
    });
});



