// Navbar scroll effect
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
  navbar.classList.toggle('scrolled', window.scrollY > 40);
}, { passive: true });

// Mobile nav toggle
const toggle = document.getElementById('navToggle');
const links  = document.getElementById('navLinks');
toggle.addEventListener('click', () => links.classList.toggle('open'));
links.querySelectorAll('a').forEach(a => a.addEventListener('click', () => links.classList.remove('open')));

// Contact form – AJAX submit
const form   = document.getElementById('contactForm');
const status = document.getElementById('form-status');
if (form) {
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.textContent = 'Sending…';
    status.className = 'form-status';
    try {
      const res = await fetch('contact.php', {
        method: 'POST',
        body: new FormData(form),
      });
      const data = await res.json();
      if (data.ok) {
        status.className = 'form-status success';
        status.textContent = 'Thank you — your message has been sent. We will be in touch shortly.';
        form.reset();
      } else {
        throw new Error(data.error || 'Server error');
      }
    } catch (err) {
      status.className = 'form-status error';
      status.textContent = 'Something went wrong. Please email us directly at pawel.bilinski.1@city.ac.uk';
    } finally {
      btn.disabled = false;
      btn.textContent = 'Send Message';
    }
  });
}
