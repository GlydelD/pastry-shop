// Add fade-in animation to elements
document.addEventListener('DOMContentLoaded', function () {
    // Animate elements on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe all pastry cards
    document.querySelectorAll('.pastry-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = `all 0.6s ease ${index * 0.1}s`;
        observer.observe(card);
    });

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const requiredInputs = form.querySelectorAll('[required]');
            let isValid = true;

            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = 'var(--raspberry)';
                } else {
                    input.style.borderColor = 'var(--butter)';
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
    });

    // Add smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Image preview for file inputs
    const imageInputs = document.querySelectorAll('input[type="url"]');
    imageInputs.forEach(input => {
        if (input.name === 'image_url') {
            input.addEventListener('blur', function () {
                const url = this.value;
                if (url) {
                    const preview = this.parentElement.querySelector('img');
                    if (preview) {
                        preview.src = url;
                    }
                }
            });
        }
    });

    // Add loading animation to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        if (button.type === 'submit') {
            button.addEventListener('click', function (e) {
                // Only show processing if form is valid
                const form = this.closest('form');
                if (form && form.checkValidity()) {
                    // Use setTimeout to allow the form submission to trigger before disabling
                    setTimeout(() => {
                        this.textContent = 'Processing...';
                        this.disabled = true;
                    }, 0);
                    // Don't re-enable - let the form submit and redirect
                }
            });
        }
    });
});

// Add parallax effect to hero section
window.addEventListener('scroll', function () {
    const hero = document.querySelector('.hero');
    if (hero) {
        const scrolled = window.pageYOffset;
        hero.style.transform = `translateY(${scrolled * 0.5}px)`;
    }
});

// Delete confirmation with style
function confirmDelete(name) {
    return confirm(`Are you sure you want to delete "${name}"?\n\nThis action cannot be undone.`);
}
