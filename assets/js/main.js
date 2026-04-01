// ========================================
// Navbar
// ========================================

const navbar = document.querySelector('.navbar');
const mobileMenuToggle = document.getElementById('mobileMenuToggle');
const navMenu = document.getElementById('navMenu');

// Scroll effect
window.addEventListener('scroll', () => {
    if (window.pageYOffset > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// Mobile menu toggle
if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener('click', () => {
        navMenu.classList.toggle('active');
        mobileMenuToggle.classList.toggle('active');
    });
}

// Close mobile menu on link click
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        navMenu.classList.remove('active');
        mobileMenuToggle.classList.remove('active');
    });
});

// ========================================
// Active Navigation Link
// ========================================

function setActiveNavLink() {
    const sections = document.querySelectorAll('section[id]');
    const scrollPosition = window.scrollY + 100;

    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.offsetHeight;
        const sectionId = section.getAttribute('id');

        if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + sectionId) {
                    link.classList.add('active');
                }
            });
        }
    });
}

window.addEventListener('scroll', setActiveNavLink);

// ========================================
// Smooth Scroll
// ========================================

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        const targetSection = document.querySelector(targetId);
        if (targetSection) {
            e.preventDefault();
            const navbarHeight = navbar ? navbar.offsetHeight : 72;
            const targetPosition = targetSection.offsetTop - navbarHeight;
            window.scrollTo({ top: targetPosition, behavior: 'smooth' });
        }
    });
});

// ========================================
// Scroll-triggered Reveal Animations
// ========================================

document.addEventListener('DOMContentLoaded', () => {
    const revealTargets = document.querySelectorAll(
        '.diff-card, .service-card, .solution-card, .team-card, .method-step, .method-connector, .section-header, .contact-form, .contact-info, .cta-content'
    );

    revealTargets.forEach((el, index) => {
        el.classList.add('reveal');
        el.style.transitionDelay = (index % 4) * 0.08 + 's';
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -40px 0px'
    });

    revealTargets.forEach(el => observer.observe(el));
});

// ========================================
// Contact Form
// ========================================

function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    const group = field.closest('.form-group');
    group.classList.add('error');
    const errorEl = document.createElement('p');
    errorEl.className = 'field-error';
    errorEl.textContent = message;
    group.appendChild(errorEl);
}

function showFormAlert(form, message, type) {
    const alert = document.createElement('div');
    alert.className = 'form-alert form-alert--' + type;
    alert.textContent = message;
    const submitBtn = form.querySelector('[type="submit"]');
    form.insertBefore(alert, submitBtn);
}

const contactForm = document.getElementById('contactForm');

if (contactForm) {
    contactForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Clear previous feedback
        contactForm.querySelectorAll('.form-alert').forEach(el => el.remove());
        contactForm.querySelectorAll('.field-error').forEach(el => el.remove());
        contactForm.querySelectorAll('.form-group.error').forEach(g => g.classList.remove('error'));

        const nombre  = document.getElementById('nombre').value.trim();
        const email   = document.getElementById('email').value.trim();
        const mensaje = document.getElementById('mensaje').value.trim();

        // Client-side validation
        let valid = true;
        if (nombre.length < 2) {
            showFieldError('nombre', 'Por favor ingresa tu nombre completo.');
            valid = false;
        }
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showFieldError('email', 'Por favor ingresa un correo electrónico válido.');
            valid = false;
        }
        if (mensaje.length < 10) {
            showFieldError('mensaje', 'Por favor describe tu reto o consulta (mínimo 10 caracteres).');
            valid = false;
        }
        if (!valid) return;

        // Loading state
        const submitBtn = contactForm.querySelector('[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando...';

        try {
            const formData = new FormData(contactForm);
            const response = await fetch('assets/php/contact.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showFormAlert(contactForm, data.message, 'success');
                contactForm.reset();
            } else {
                showFormAlert(contactForm, data.message, 'error');
            }
        } catch {
            showFormAlert(contactForm, 'Ocurrió un error de conexión. Por favor escríbenos directamente a info@koqoi.com', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
}
