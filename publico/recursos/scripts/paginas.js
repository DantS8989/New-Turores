// Carga las pantallas que requieren sesión y sus datos desde la API.

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  const app = window.TutoresApp;
  const page = document.body.dataset.page;
  if (!app || !page) return;

  const $ = (id) => document.getElementById(id);
  const show = (el, on, display) => { if (el) el.style.display = on ? (display || 'block') : 'none'; };

  /** Devuelve la respuesta JSON junto con su código HTTP. */
  async function api(url) {
    const res = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
    let data = null;
    try { data = await res.json(); } catch (e) {}
    return { status: res.status, data };
  }

  function goLogin(nextUrl) {
    window.location.replace('login.html?next=' + encodeURIComponent(nextUrl));
  }

  /** Comprueba la sesión; si no hay, envía al inicio de sesión. */
  async function requireSession(nextUrl) {
    const session = await app.session;
    if (!session || !session.logged_in) {
      goLogin(nextUrl);
      return null;
    }
    return session;
  }

  function showAlert(text) {
    const box = $('auth-alert');
    const msg = $('auth-alert-message');
    if (box && msg) {
      msg.textContent = text;
      box.style.display = 'flex';
      box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  function formatDate(value) {
    const d = new Date(String(value).replace(' ', 'T'));
    return isNaN(d) ? '' : d.toLocaleString('es-CO', { dateStyle: 'medium', timeStyle: 'short' });
  }

  // Mi perfil.
  async function initProfile() {
    if (!(await requireSession('perfil.html'))) return;
    const { status, data } = await api('api/perfil.php');
    if (status === 401) return goLogin('perfil.html');
    if (!data || !data.profile) return showAlert('No se pudo cargar tu perfil. Inténtalo de nuevo.');

    const p = data.profile;
    $('name').value = p.name || '';
    $('account-email').value = p.email || '';
    $('bio').value = p.bio || '';
    $('profile-role').textContent = p.role_label || p.role;
    app.paintAvatar($('profile-avatar'), p.name, p.foto);
    show($('remove-photo-row'), !!app.photoUrl(p.foto), 'flex');

    if (p.role === 'tutor') {
      show($('tutor-fields'), true);
      $('specialty').value = p.specialty || '';
      $('tarifa_hora').value = p.tarifa_hora !== null ? String(Math.round(p.tarifa_hora * 100) / 100) : '';
      $('whatsapp').value = p.whatsapp || '';
      $('email_contacto').value = p.email_contacto || '';
      const link = $('public-profile-link');
      link.href = 'tutor.html?id=' + encodeURIComponent(p.id);
      show(link, true, 'inline-block');
    }

    // Evita enviar archivos que el servidor rechazará por tamaño.
    $('foto').addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file && file.size > 2 * 1024 * 1024) {
        e.target.value = '';
        showAlert('La foto supera los 2 MB. Elige una más liviana.');
      }
    });
  }

  // Perfil público de un tutor.
  async function initTutor() {
    const id = new URLSearchParams(window.location.search).get('id') || '';
    const loading = $('tutor-loading');

    if (!/^\d{1,10}$/.test(id)) {
      show(loading, false);
      show($('tutor-missing'), true);
      return;
    }

    const target = 'tutor.html?id=' + id;
    if (!(await requireSession(target))) return;

    const { status, data } = await api('api/tutor_perfil.php?id=' + encodeURIComponent(id));
    show(loading, false);

    if (status === 401) return goLogin(target);
    if (status !== 200 || !data || !data.tutor) {
      show($('tutor-missing'), true);
      return;
    }

    const t = data.tutor;
    document.title = t.name + ' — TutoresOn-Line';
    $('tutor-name').textContent = t.name;
    $('tutor-specialty').textContent = t.specialty || 'Tutor';
    $('tutor-price').textContent = t.tarifa_hora !== null ? app.formatPrice(t.tarifa_hora) : 'Precio a convenir';
    app.paintAvatar($('tutor-avatar'), t.name, t.foto);

    if (t.bio) {
      $('tutor-bio').textContent = t.bio;
      show($('tutor-bio-block'), true);
    }

    // Los datos de contacto solo llegan a usuarios autenticados.
    const wa = $('tutor-whatsapp');
    const mail = $('tutor-email');
    const digits = (t.whatsapp || '').replace(/\D/g, '');
    if (digits.length >= 7 && digits.length <= 15) {
      wa.href = 'https://wa.me/' + digits;
      wa.textContent = 'WhatsApp: ' + t.whatsapp;
      show(wa, true, 'inline-flex');
    }
    if (t.email_contacto && /^[^\s@<>"]+@[^\s@<>"]+\.[^\s@<>"]+$/.test(t.email_contacto)) {
      mail.href = 'mailto:' + t.email_contacto;
      mail.textContent = t.email_contacto;
      show(mail, true, 'inline-flex');
    }
    show($('tutor-contact-block'), wa.style.display !== 'none' || mail.style.display !== 'none');

    if (data.is_self) {
      show($('tutor-self-note'), true, 'flex');
    } else {
      $('message-to').value = String(t.id);
      show($('message-form'), true);
    }
    show($('tutor-card'), true);
  }

  // Mensajes recibidos y enviados.
  function buildMessage(msg, kind, csrfToken) {
    const item = document.createElement('article');
    item.className = 'msg-item';

    const head = document.createElement('div');
    head.className = 'msg-head';
    const avatar = document.createElement('div');
    avatar.className = 'user-avatar-circle';
    app.paintAvatar(avatar, msg.other.name, msg.other.foto);
    const who = document.createElement('div');
    const name = document.createElement('strong');
    name.textContent = (kind === 'received' ? 'De: ' : 'Para: ') + msg.other.name;
    const date = document.createElement('span');
    date.className = 'msg-date';
    date.textContent = formatDate(msg.date);
    who.append(name, date);
    head.append(avatar, who);

    const body = document.createElement('p');
    body.className = 'msg-body';
    body.textContent = msg.body;
    item.append(head, body);

    if (kind === 'received') {
      const details = document.createElement('details');
      details.className = 'msg-reply';
      const summary = document.createElement('summary');
      summary.textContent = 'Responder';

      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'api/mensaje_enviar.php';
      const hidden = (n, v) => {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = n; input.value = v;
        return input;
      };
      const textarea = document.createElement('textarea');
      textarea.name = 'mensaje';
      textarea.className = 'form-control';
      textarea.rows = 3;
      textarea.maxLength = 1000;
      textarea.required = true;
      textarea.placeholder = 'Escribe tu respuesta…';
      const button = document.createElement('button');
      button.type = 'submit';
      button.className = 'btn btn-primary btn-sm';
      button.textContent = 'Enviar respuesta';
      form.append(
        hidden('csrf_token', csrfToken),
        hidden('destinatario_id', String(msg.other.id)),
        hidden('origen', 'mensajes'),
        textarea,
        button
      );
      details.append(summary, form);
      item.append(details);
    }
    return item;
  }

  async function initMessages() {
    const session = await requireSession('mensajes.html');
    if (!session) return;
    const { status, data } = await api('api/mensajes.php');
    if (status === 401) return goLogin('mensajes.html');
    if (!data) return showAlert('No se pudieron cargar tus mensajes. Inténtalo de nuevo.');

    const lists = { received: $('messages-received'), sent: $('messages-sent') };
    const emptyText = {
      received: 'Todavía no has recibido mensajes.',
      sent: 'Todavía no has enviado mensajes. Entra al perfil de un tutor para escribirle.'
    };
    lists.received.replaceChildren(...(data.received || []).map((m) => buildMessage(m, 'received', session.csrf_token)));
    lists.sent.replaceChildren(...(data.sent || []).map((m) => buildMessage(m, 'sent', session.csrf_token)));

    let active = 'received';
    function render() {
      document.querySelectorAll('.tab-btn').forEach((b) => b.classList.toggle('active', b.dataset.tab === active));
      Object.keys(lists).forEach((k) => show(lists[k], k === active));
      const empty = lists[active].children.length === 0;
      $('messages-empty').textContent = emptyText[active];
      show($('messages-empty'), empty);
    }
    document.querySelectorAll('.tab-btn').forEach((b) => b.addEventListener('click', () => {
      active = b.dataset.tab;
      render();
    }));
    render();
  }

  const starters = { perfil: initProfile, tutor: initTutor, mensajes: initMessages };
  if (starters[page]) starters[page]().catch(() => showAlert('Ocurrió un error al cargar la página.'));
});
