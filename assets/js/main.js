/**
 * TutoresOn-Line — JavaScript de Interfaz y Conexión Frontend/Backend
 */

// Función global para filtrar materias desde los clics de las tarjetas
window.filtrarMateria = function(materia) {
  const searchInput = document.getElementById('landing-search-input');
  if (searchInput) {
    searchInput.value = materia;
    searchInput.dispatchEvent(new Event('input'));
  }
  const tutoresSec = document.getElementById('tutores');
  if (tutoresSec) {
    tutoresSec.scrollIntoView({ behavior: 'smooth' });
  }
};

document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);

  // ──────────────────────────────────────────────────────────
  // 1. Alternar Visibilidad de Contraseñas (Ver / Ocultar)
  // ──────────────────────────────────────────────────────────
  const togglePassBtns = document.querySelectorAll('.password-toggle-btn');
  togglePassBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-target') || 'password';
      const input = document.getElementById(targetId);
      if (input) {
        if (input.type === 'password') {
          input.type = 'text';
          btn.innerHTML = `
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
              <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>`;
        } else {
          input.type = 'password';
          btn.innerHTML = `
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>`;
        }
      }
    });
  });

  // ──────────────────────────────────────────────────────────
  // 2. Rellenar credenciales demo en login.html
  // ──────────────────────────────────────────────────────────
  const fillBtns = document.querySelectorAll('.demo-fill-btn');
  fillBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const email = btn.getAttribute('data-email');
      const pass = btn.getAttribute('data-pass');
      const emailInput = document.getElementById('email');
      const passInput = document.getElementById('password');
      if (emailInput && passInput) {
        emailInput.value = email;
        passInput.value = pass;
        emailInput.focus();
      }
    });
  });

  // ──────────────────────────────────────────────────────────
  // 3. Manejo de errores en login.html y registro.html
  // ──────────────────────────────────────────────────────────
  const alertBox = document.getElementById('auth-alert');
  const alertMsg = document.getElementById('auth-alert-message');
  const errorCode = urlParams.get('error');

  if (alertBox && alertMsg && errorCode) {
    const errorMap = {
      'empty_fields': 'Por favor completa todos los campos del formulario.',
      'user_not_found': 'No existe ninguna cuenta registrada con este correo electrónico.',
      'invalid_password': 'La contraseña ingresada es incorrecta. Inténtalo de nuevo.',
      'invalid_email': 'El formato del correo electrónico ingresado no es válido.',
      'short_password': 'La contraseña debe tener al menos 4 caracteres.',
      'passwords_mismatch': 'Las contraseñas no coinciden. Por favor verifícalas.',
      'email_exists': 'Este correo electrónico ya está registrado. Inicia sesión en su lugar.'
    };

    alertMsg.textContent = errorMap[errorCode] || 'Ha ocurrido un error al procesar tu solicitud.';
    alertBox.style.display = 'flex';

    // Rellenar campos si vinieron en la URL
    const emailParam = urlParams.get('email');
    const nameParam = urlParams.get('name');
    const roleParam = urlParams.get('role');

    const emailInput = document.getElementById('email');
    if (emailInput && emailParam) emailInput.value = emailParam;

    const nameInput = document.getElementById('name');
    if (nameInput && nameParam) nameInput.value = nameParam;

    if (roleParam) {
      const roleRadio = document.querySelector(`input[name="role"][value="${roleParam}"]`);
      if (roleRadio) {
        roleRadio.checked = true;
        roleRadio.dispatchEvent(new Event('change'));
      }
    }
  }

  // ──────────────────────────────────────────────────────────
  // 4. Selector de rol en registro.html
  // ──────────────────────────────────────────────────────────
  const roleInputs = document.querySelectorAll('input[name="role"]');
  const specialtyGroup = document.getElementById('specialty-group');

  function updateRoleView() {
    const selected = document.querySelector('input[name="role"]:checked');
    if (selected && specialtyGroup) {
      specialtyGroup.style.display = (selected.value === 'tutor') ? 'block' : 'none';
    }
  }

  roleInputs.forEach(input => input.addEventListener('change', updateRoleView));
  updateRoleView();

  // ──────────────────────────────────────────────────────────
  // 5. Buscador y filtro en vivo de tutores (index.html)
  // ──────────────────────────────────────────────────────────
  const searchInput = document.getElementById('landing-search-input');
  const subjectSelect = document.getElementById('landing-subject-filter');
  const tutorCards = document.querySelectorAll('.tutor-card');

  function filterTutors() {
    const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
    const selectedSubject = (subjectSelect ? subjectSelect.value : '').toLowerCase();

    tutorCards.forEach(card => {
      const name = (card.getAttribute('data-name') || '').toLowerCase();
      const subject = (card.getAttribute('data-subject') || '').toLowerCase();
      const tags = (card.getAttribute('data-tags') || '').toLowerCase();

      const matchesQuery = !query || name.includes(query) || subject.includes(query) || tags.includes(query);
      const matchesSubject = !selectedSubject || selectedSubject === 'todos' || subject.includes(selectedSubject);

      if (matchesQuery && matchesSubject) {
        card.style.display = 'flex';
      } else {
        card.style.display = 'none';
      }
    });
  }

  if (searchInput) searchInput.addEventListener('input', filterTutors);
  if (subjectSelect) subjectSelect.addEventListener('change', filterTutors);

  // ──────────────────────────────────────────────────────────
  // 6. Mensajes Toast en index.html (?msg=...)
  // ──────────────────────────────────────────────────────────
  const toastBox = document.getElementById('toast-box');
  const toastMsg = document.getElementById('toast-message');
  const toastIcon = document.getElementById('toast-icon');
  const msgParam = urlParams.get('msg');

  if (toastBox && toastMsg && msgParam) {
    const nameParam = urlParams.get('name') || 'Usuario';
    const toastMap = {
      'login_success': { text: `¡Bienvenido/a de nuevo, ${nameParam}! Sesión iniciada correctamente.`, icon: '✓', color: '#10B981' },
      'register_success': { text: `¡Cuenta creada exitosamente! Bienvenido/a a TutoresOn-Line, ${nameParam}.`, icon: '✓', color: '#10B981' },
      'logout_success': { text: 'Has cerrado sesión correctamente. ¡Hasta pronto!', icon: 'ℹ', color: '#60A5FA' }
    };

    if (toastMap[msgParam]) {
      toastMsg.textContent = toastMap[msgParam].text;
      if (toastIcon) {
        toastIcon.textContent = toastMap[msgParam].icon;
        toastIcon.style.color = toastMap[msgParam].color;
      }
      toastBox.style.display = 'block';

      setTimeout(() => {
        toastBox.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        toastBox.style.opacity = '0';
        toastBox.style.transform = 'translateY(20px)';
        setTimeout(() => toastBox.style.display = 'none', 400);
      }, 4500);
    }
  }

  // ──────────────────────────────────────────────────────────
  // 7. Verificación de sesión PHP activa (en index.html)
  // ──────────────────────────────────────────────────────────
  const navGuest = document.getElementById('nav-guest-actions');
  const navUser = document.getElementById('nav-user-actions');
  const navUserName = document.getElementById('nav-user-name');
  const navUserRole = document.getElementById('nav-user-role');
  const navUserAvatar = document.getElementById('nav-user-avatar');

  const welcomeBanner = document.getElementById('user-welcome-banner');
  const bannerUserName = document.getElementById('banner-user-name');
  const bannerUserRole = document.getElementById('banner-user-role');
  const bannerUserAvatar = document.getElementById('banner-user-avatar');

  function renderLoggedUser(user) {
    if (navGuest) navGuest.style.display = 'none';
    if (navUser) navUser.style.display = 'flex';

    const initial = (user.name || 'U').charAt(0).toUpperCase();

    if (navUserName) navUserName.textContent = user.name;
    if (navUserRole) navUserRole.textContent = user.role_label || user.role;
    if (navUserAvatar) navUserAvatar.textContent = initial;

    if (welcomeBanner) {
      welcomeBanner.style.display = 'flex';
      if (bannerUserName) bannerUserName.textContent = user.name;
      if (bannerUserRole) bannerUserRole.textContent = user.role_label || user.role;
      if (bannerUserAvatar) bannerUserAvatar.textContent = initial;
    }

    // Actualizar botones de contacto en las tarjetas de tutores
    const contactBtns = document.querySelectorAll('.tutor-contact-btn');
    contactBtns.forEach(btn => {
      btn.href = 'javascript:void(0)';
      btn.textContent = 'Contactar tutor';
      btn.className = 'btn btn-primary btn-sm tutor-contact-btn';
      btn.onclick = () => alert('¡Excelente! Tu mensaje ha sido enviado al tutor. Se pondrá en contacto contigo a la brevedad.');
    });
  }

  // Comprobar sesión llamando a check_session.php
  fetch('check_session.php')
    .then(res => res.json())
    .then(data => {
      if (data && data.logged_in && data.user) {
        renderLoggedUser(data.user);
      }
    })
    .catch(() => {
      // Si se abre directamente como archivo estático o sin servidor PHP activo,
      // revisar parámetros URL de respaldo
      const nameParam = urlParams.get('name');
      const roleParam = urlParams.get('role');
      if (msgParam === 'login_success' || msgParam === 'register_success') {
        renderLoggedUser({
          name: nameParam || 'Usuario Demo',
          role: roleParam || 'Estudiante',
          role_label: roleParam || 'Estudiante'
        });
      }
    });
});
