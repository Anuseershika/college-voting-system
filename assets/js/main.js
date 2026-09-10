/**
 * College Online Voting System - Javascript Helpers
 * Features: Mobile menu toggler, photo upload preview, form validators, confirmation alerts
 */

document.addEventListener('DOMContentLoaded', () => {
    // --- 1. Mobile Menu Toggler ---
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', () => {
            navMenu.classList.toggle('open');
            // Toggle hamburger icon if using text/icon
            const isOpen = navMenu.classList.contains('open');
            menuToggle.innerHTML = isOpen ? '✕' : '☰';
        });
    }

    // --- 2. Photo Upload Preview ---
    const photoInput = document.getElementById('photo-input');
    const photoPreview = document.getElementById('photo-preview');

    if (photoInput && photoPreview) {
        photoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Check if file is an image
                if (!file.type.startsWith('image/')) {
                    alert('Please select an image file (PNG, JPG, JPEG, WEBP).');
                    this.value = '';
                    photoPreview.innerHTML = 'No Image';
                    photoPreview.style.backgroundImage = 'none';
                    return;
                }
                
                // Check size (max 2MB = 2 * 1024 * 1024 bytes)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size exceeds 2MB limit.');
                    this.value = '';
                    photoPreview.innerHTML = 'Too Large';
                    photoPreview.style.backgroundImage = 'none';
                    return;
                }

                const reader = new FileReader();
                reader.addEventListener('load', function() {
                    photoPreview.innerHTML = '';
                    photoPreview.style.backgroundImage = `url(${this.result})`;
                    photoPreview.style.backgroundSize = 'cover';
                    photoPreview.style.backgroundPosition = 'center';
                });
                reader.readAsDataURL(file);
            } else {
                photoPreview.innerHTML = 'No Image';
                photoPreview.style.backgroundImage = 'none';
            }
        });
    }

    // --- 3. Client-Side Form Validation ---
    const registerForms = document.querySelectorAll('form[data-validate]');
    registerForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const password = form.querySelector('input[name="password"]');
            const confirmPassword = form.querySelector('input[name="confirm_password"]');
            const studentId = form.querySelector('input[name="student_id"]');
            const email = form.querySelector('input[name="email"]');
            
            // Password Match Validation
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match. Please verify your passwords.');
                confirmPassword.focus();
                return false;
            }

            // Password Length Validation
            if (password && password.value.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                password.focus();
                return false;
            }

            // Student ID validation (basic format, alphanumeric)
            if (studentId) {
                const idVal = studentId.value.trim();
                if (idVal.length < 3) {
                    e.preventDefault();
                    alert('Please enter a valid Student ID (at least 3 characters).');
                    studentId.focus();
                    return false;
                }
            }

            // Email validation (simple college email check if needed, or basic RFC)
            if (email) {
                const emailVal = email.value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailVal)) {
                    e.preventDefault();
                    alert('Please enter a valid email address.');
                    email.focus();
                    return false;
                }
            }
        });
    });

    // --- 4. Voting Confirmation ---
    const voteForms = document.querySelectorAll('form.vote-form');
    voteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const candidateName = this.getAttribute('data-candidate');
            const confirmVote = confirm(`Are you sure you want to cast your vote for ${candidateName}?\n\nThis action cannot be undone.`);
            if (!confirmVote) {
                e.preventDefault();
            }
        });
    });

    // --- 5. Manifesto Toggle (Read More / Read Less) ---
    const toggleButtons = document.querySelectorAll('.toggle-manifesto-btn');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const cardBody = this.closest('.candidate-body');
            const manifesto = cardBody.querySelector('.candidate-manifesto-text');
            const fullText = manifesto.getAttribute('data-full-manifesto');
            const shortText = manifesto.getAttribute('data-short-manifesto');
            const isExpanded = btn.getAttribute('data-expanded') === 'true';

            if (isExpanded) {
                manifesto.textContent = shortText;
                btn.textContent = 'Read Manifesto';
                btn.setAttribute('data-expanded', 'false');
            } else {
                manifesto.textContent = fullText;
                btn.textContent = 'Hide Manifesto';
                btn.setAttribute('data-expanded', 'true');
            }
        });
    });
});
