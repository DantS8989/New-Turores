/**
 * TutoresOn-Line — JavaScript de la interfaz
 *
 * - Pide a php/check_session.php el estado de la sesión y el token CSRF
 *   y lo coloca en los formularios.
 * - Pide a php/flash.php el mensaje de una sola vez (errores, bienvenida,
 *   cierre de sesión). Nada de esto viaja por la URL.
 * - Carga los tutores registrados desde php/tutores.php.
 *
 * Todo el texto que viene del servidor se inserta con textContent
 * (nunca con innerHTML), para evitar XSS.
 */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  // ──────────────────────────────────────────────────────────
  // Utilidades
  // ──────────────────────────────────────────────────────────
  const $ = (id) => document.getElementById(id);

  async function getJSON(url) {
    const res = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    return res.json();
  }

  const ERROR_MESSAGES = {
    empty_fields: 'Por favor completa todos los campos del formulario.',
    invalid_credentials: 'Correo o contraseña incorrectos.',
    too_many_attempts: 'Demasiados intentos fallidos. Espera 15 minutos antes de volver a intentarlo.',
    invalid_email: 'El formato del correo electrónico no es válido.',
    short_password: 'La contraseña debe tener al menos 8 caracteres.',
    long_password: 'La contraseña no puede superar los 72 caracteres.',
    passwords_mismatch: 'Las contraseñas no coinciden. Por favor verifícalas.',
    email_exists: 'Este correo electrónico ya está registrado. Inicia sesión en su lugar.',
    field_too_long: 'Alguno de los campos supera la longitud permitida.',
    csrf: 'Tu sesión de seguridad expiró. Recarga la página e inténtalo de nuevo.'
  };

  // ──────────────────────────────────────────────────────────
  // 1. Mostrar / ocultar contraseña
  // ──────────────────────────────────────────────────────────
  const EYE_OPEN =
    '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">' +
    '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
  const EYE_OFF =
    '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">' +
    '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>' +
    '<line x1="1" y1="1" x2="23" y2="23"></line></svg>';

  document.querySelectorAll('.password-toggle-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = $(btn.getAttribute('data-target') || 'password');
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.innerHTML = show ? EYE_OFF : EYE_OPEN; // SVG fijo, sin datos del servidor
    });
  });

  // ──────────────────────────────────────────────────────────
  // 2. Selector de rol (registro.html)
  // ──────────────────────────────────────────────────────────
  const roleInputs = document.querySelectorAll('input[name="role"]');
  const specialtyGroup = $('specialty-group');

  function updateRoleView() {
    const selected = document.querySelector('input[name="role"]:checked');
    if (selected && specialtyGroup) {
      specialtyGroup.style.display = selected.value === 'tutor' ? 'block' : 'none';
    }
  }
  roleInputs.forEach((input) => input.addEventListener('change', updateRoleView));
  updateRoleView();

  // ──────────────────────────────────────────────────────────
  // 3. Alertas de error en login.html / registro.html
  // ──────────────────────────────────────────────────────────
  function showAuthError(data) {
    const box = $('auth-alert');
    const msg = $('auth-alert-message');
    if (!box || !msg) return;

    msg.textContent = ERROR_MESSAGES[data.code] || 'Ha ocurrido un error al procesar tu solicitud.';
    box.style.display = 'flex';

    // Rellenar de nuevo lo que escribió (nunca la contraseña)
    const fill = (id, value) => {
      const el = $(id);
      if (el && typeof value === 'string') el.value = value;
    };
    fill('email', data.email);
    fill('name', data.name);
    fill('specialty', data.specialty);

    if (data.role) {
      const radio = document.querySelector('input[name="role"][value="' + CSS.escape(data.role) + '"]');
      if (radio) {
        radio.checked = true;
        updateRoleView();
      }
    }
  }

  // ──────────────────────────────────────────────────────────
  // 4. Toast (index.html)
  // ──────────────────────────────────────────────────────────
  function showToast(text, icon, color) {
    const box = $('toast-box');
    const msg = $('toast-message');
    const ico = $('toast-icon');
    if (!box || !msg) return;

    msg.textContent = text;
    if (ico) {
      ico.textContent = icon || '✓';
      ico.style.color = color || '#10B981';
    }
    box.style.transition = '';
    box.style.opacity = '1';
    box.style.transform = 'none';
    box.style.display = 'block';

    setTimeout(() => {
      box.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      box.style.opacity = '0';
      box.style.transform = 'translateY(20px)';
      setTimeout(() => { box.style.display = 'none'; }, 400);
    }, 4500);
  }

  function showFlash(flash) {
    if (!flash) return;
    const data = flash.data || {};
    const name = typeof data.name === 'string' && data.name ? data.name : 'Usuario';

    if (flash.type === 'error') {
      showAuthError(data);
    } else if (flash.type === 'login_success') {
      showToast('¡Bienvenido/a de nuevo, ' + name + '! Sesión iniciada correctamente.', '✓', '#10B981');
    } else if (flash.type === 'register_success') {
      showToast('¡Cuenta creada exitosamente! Bienvenido/a a TutoresOn-Line, ' + name + '.', '✓', '#10B981');
    } else if (flash.type === 'logout_success') {
      showToast('Has cerrado sesión correctamente. ¡Hasta pronto!', 'ℹ', '#60A5FA');
    }
  }

  // ──────────────────────────────────────────────────────────
  // 5. Estado de sesión (barra de navegación)
  // ──────────────────────────────────────────────────────────
  const navGuest = $('nav-guest-actions');
  const navUser = $('nav-user-actions');
  let currentUser = null;

  function renderLoggedUser(user) {
    currentUser = user;
    if (navGuest) navGuest.style.display = 'none';
    if (navUser) navUser.style.display = 'flex';

    const initial = (user.name || 'U').charAt(0).toUpperCase();
    if ($('nav-user-name')) $('nav-user-name').textContent = user.name;
    if ($('nav-user-role')) $('nav-user-role').textContent = user.role_label || user.role;
    if ($('nav-user-avatar')) $('nav-user-avatar').textContent = initial;
  }

  function setCsrfTokens(token) {
    document.querySelectorAll('input[name="csrf_token"]').forEach((input) => {
      input.value = token;
    });
  }

  // ──────────────────────────────────────────────────────────
  // 6. Tutores registrados y filtros (index.html)
  // ──────────────────────────────────────────────────────────
  const grid = $('tutors-grid');
  const emptyMsg = $('tutors-empty');
  const searchInput = $('landing-search-input');
  const subjectSelect = $('landing-subject-filter');
  let tutors = [];

  function buildTutorCard(tutor) {
    const card = document.createElement('article');
    card.className = 'tutor-card';
    card.dataset.name = tutor.name;
    card.dataset.subject = tutor.specialty || '';

    const body = document.createElement('div');
    body.className = 'tutor-card-body';

    const header = document.createElement('div');
    header.className = 'tutor-header';

    const avatar = document.createElement('div');
    avatar.className = 'user-avatar-circle';
    avatar.style.width = '56px';
    avatar.style.height = '56px';
    avatar.style.fontSize = '20px';
    avatar.textContent = (tutor.name || 'T').charAt(0).toUpperCase();

    const info = document.createElement('div');
    const name = document.createElement('h3');
    name.className = 'tutor-name';
    name.textContent = tutor.name;
    const subject = document.createElement('p');
    subject.className = 'tutor-subject';
    subject.textContent = tutor.specialty || 'Tutor';
    info.append(name, subject);
    header.append(avatar, info);

    const footer = document.createElement('div');
    footer.className = 'tutor-footer';
    let contact;
    if (currentUser) {
      contact = document.createElement('button');
      contact.type = 'button';
      contact.className = 'btn btn-primary btn-sm tutor-contact-btn';
      contact.addEventListener('click', () => {
        showToast('El contacto directo con tutores estará disponible próximamente.', 'ℹ', '#60A5FA');
      });
    } else {
      contact = document.createElement('a');
      contact.href = 'login.html';
      contact.className = 'btn btn-outline btn-sm tutor-contact-btn';
    }
    contact.textContent = 'Contactar tutor';
    footer.append(contact);

    body.append(header, footer);
    card.append(body);
    return card;
  }

  function filterTutors() {
    if (!grid) return;
    const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
    const subject = (subjectSelect ? subjectSelect.value : '').toLowerCase();
    let visible = 0;

    grid.querySelectorAll('.tutor-card').forEach((card) => {
      const name = (card.dataset.name || '').toLowerCase();
      const spec = (card.dataset.subject || '').toLowerCase();
      const matchesQuery = !query || name.includes(query) || spec.includes(query);
      const matchesSubject = !subject || subject === 'todos' || spec.includes(subject);
      const show = matchesQuery && matchesSubject;
      card.style.display = show ? 'flex' : 'none';
      if (show) visible += 1;
    });

    if (emptyMsg) {
      if (tutors.length === 0) {
        emptyMsg.textContent = 'Aún no hay tutores registrados. ¡Sé el primero en unirte!';
      } else if (visible === 0) {
        emptyMsg.textContent = 'No encontramos tutores con esa búsqueda.';
      }
      emptyMsg.style.display = visible === 0 ? 'block' : 'none';
    }
  }

  function renderTutors() {
    if (!grid) return;
    grid.replaceChildren(...tutors.map(buildTutorCard));
    filterTutors();
  }

  if (searchInput) searchInput.addEventListener('input', filterTutors);
  if (subjectSelect) subjectSelect.addEventListener('change', filterTutors);

  // Clic (o Enter / Espacio) en las tarjetas de materias
  function pickSubject(card) {
    const materia = card.getAttribute('data-materia');
    if (searchInput && materia) {
      searchInput.value = materia;
      filterTutors();
    }
    const section = $('tutores');
    if (section) section.scrollIntoView({ behavior: 'smooth' });
  }
  document.querySelectorAll('.subject-card[data-materia]').forEach((card) => {
    card.addEventListener('click', () => pickSubject(card));
    card.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        pickSubject(card);
      }
    });
  });

  // ──────────────────────────────────────────────────────────
  // 7. Carga inicial (sesión + mensaje flash + tutores)
  // ──────────────────────────────────────────────────────────
  const sessionReq = getJSON('php/check_session.php')
    .then((data) => {
      if (data.csrf_token) setCsrfTokens(data.csrf_token);
      if (data.logged_in && data.user) renderLoggedUser(data.user);
    })
    .catch(() => { /* sin servidor PHP: la página sigue siendo navegable */ });

  getJSON('php/flash.php')
    .then((data) => showFlash(data.flash))
    .catch(() => {});

  if (grid) {
    const tutorsReq = getJSON('php/tutores.php')
      .then((data) => { tutors = Array.isArray(data.tutors) ? data.tutors : []; })
      .catch(() => { tutors = []; });

    // Los botones de contacto dependen de si hay sesión: se dibujan al tener ambas respuestas
    Promise.all([sessionReq, tutorsReq]).then(renderTutors);
  }

});
