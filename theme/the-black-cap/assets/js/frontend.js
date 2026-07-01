/* The Black Cap – frontend interactions */

/* ── Nav orbit toggle ───────────────────────────────────────────── */
(function () {
  const orbit = document.getElementById('tbc-logo-orbit');
  const btn   = document.getElementById('tbc-logo-btn');
  if (!orbit || !btn) return;

  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    const open = orbit.classList.toggle('isOpen');
    btn.setAttribute('aria-expanded', String(open));
  });

  document.addEventListener('click', function (e) {
    if (!orbit.contains(e.target)) {
      orbit.classList.remove('isOpen');
      btn.setAttribute('aria-expanded', 'false');
    }
  });

  orbit.querySelectorAll('.rainbowItem').forEach(function (el) {
    el.addEventListener('click', function (e) {
      const href = el.getAttribute('href') || '';
      if (href.startsWith('#')) {
        e.preventDefault();
        const target = document.querySelector(href);
        if (target) target.scrollIntoView({ behavior: 'smooth' });
      }
      orbit.classList.remove('isOpen');
      btn.setAttribute('aria-expanded', 'false');
    });
  });
})();

/* ── Story photo parallax ───────────────────────────────────────── */
(function () {
  const row = document.getElementById('tbc-photo-row');
  if (!row) return;

  var photos = Array.from(row.children).map(function (el) {
    return {
      el:     el,
      driftX: parseFloat(el.dataset.driftX || 0),
      driftY: parseFloat(el.dataset.driftY || 0),
    };
  });

  function update() {
    var rect     = row.getBoundingClientRect();
    var progress = (window.innerHeight / 2 - (rect.top + rect.height / 2)) / window.innerHeight;
    var mobile   = window.innerWidth <= 700;
    photos.forEach(function (p) {
      p.el.style.transform = mobile
        ? 'translateX(' + (p.driftX * progress * 0.12) + 'rem)'
        : 'translate(' + (p.driftX * progress) + 'rem, ' + (p.driftY * progress) + 'rem)';
    });
  }

  window.addEventListener('scroll', update, { passive: true });
  update();
})();

/* ── FrameGallery ───────────────────────────────────────────────── */
(function () {
  var RED_SELECTOR = '[fill="#FF0000"],[fill="#ff0000"],[fill="#f00"],[fill="red"]';
  var lightbox = null;

  function openLightbox(photo) {
    closeLightbox();
    lightbox = document.createElement('div');
    lightbox.className = 'lightboxOverlay';

    var closeBtn = document.createElement('button');
    closeBtn.className  = 'lightboxClose';
    closeBtn.setAttribute('aria-label', 'Close');
    closeBtn.textContent = '✕';
    closeBtn.addEventListener('click', closeLightbox);

    var img = document.createElement('img');
    img.className = 'lightboxImg';
    img.src = photo;
    img.alt = '';
    img.addEventListener('click', function (e) { e.stopPropagation(); });

    lightbox.appendChild(closeBtn);
    lightbox.appendChild(img);
    lightbox.addEventListener('click', closeLightbox);

    document.body.appendChild(lightbox);
    document.addEventListener('keydown', onKey);
  }

  function closeLightbox() {
    if (lightbox) { lightbox.remove(); lightbox = null; }
    document.removeEventListener('keydown', onKey);
  }

  function onKey(e) { if (e.key === 'Escape') closeLightbox(); }

  function initFrame(container) {
    var svgUrl = container.dataset.svg;
    var photos = [];
    try { photos = JSON.parse(container.dataset.photos || '[]'); } catch (_) {}
    if (!svgUrl || !photos.length) return;

    var uid = Math.random().toString(36).slice(2, 7);

    fetch(svgUrl)
      .then(function (r) { return r.text(); })
      .then(function (text) {
        var doc   = new DOMParser().parseFromString(text, 'image/svg+xml');
        var svgEl = doc.documentElement;

        Array.from(svgEl.querySelectorAll(RED_SELECTOR)).forEach(function (el, i) {
          el.setAttribute('data-fg-index', String(i));
        });

        container.innerHTML = new XMLSerializer().serializeToString(svgEl);

        var live = container.querySelector('svg');
        if (!live) return;

        var defs = live.querySelector('defs');
        if (!defs) {
          defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
          live.insertBefore(defs, live.firstChild);
        }

        live.querySelectorAll('[data-fg-index]').forEach(function (el) {
          var i      = parseInt(el.getAttribute('data-fg-index'), 10);
          var bbox   = el.getBBox();
          var clipId = 'fg-' + uid + '-' + i;
          var photo  = photos[i % photos.length];
          if (!photo) { el.remove(); return; }

          var cp = document.createElementNS('http://www.w3.org/2000/svg', 'clipPath');
          cp.setAttribute('id', clipId);
          cp.setAttribute('clipPathUnits', 'userSpaceOnUse');
          var shape = el.cloneNode(true);
          shape.removeAttribute('fill');
          shape.removeAttribute('data-fg-index');
          cp.appendChild(shape);
          defs.appendChild(cp);

          var img = document.createElementNS('http://www.w3.org/2000/svg', 'image');
          img.setAttribute('href', photo);
          img.setAttribute('x', String(bbox.x));
          img.setAttribute('y', String(bbox.y));
          img.setAttribute('width', String(bbox.width));
          img.setAttribute('height', String(bbox.height));
          img.setAttribute('preserveAspectRatio', 'xMidYMid slice');
          img.setAttribute('clip-path', 'url(#' + clipId + ')');
          img.style.cursor = 'pointer';
          img.addEventListener('click', (function (p) {
            return function () { openLightbox(p); };
          })(photo));

          el.replaceWith(img);
        });
      })
      .catch(function (err) { console.error('[FrameGallery]', err); });
  }

  document.querySelectorAll('.frameGallery[data-svg]').forEach(initFrame);
})();

/* ── Event card modal ───────────────────────────────────────────── */
(function () {
  'use strict';

  var modal = null;

  var MODAL_HTML = [
    '<div class="eventModal" id="tbc-event-modal" role="dialog" aria-modal="true" aria-label="Event details" hidden>',
    '  <div class="eventModal__backdrop"></div>',
    '  <div class="eventModal__panel">',
    '    <button class="eventModal__close" aria-label="Close">&#x2715;</button>',
    '    <div class="eventModal__img"><img src="" alt="" loading="eager"></div>',
    '    <div class="eventModal__body">',
    '      <time class="eventModal__date"></time>',
    '      <h2 class="eventModal__title"></h2>',
    '      <p class="eventModal__desc"></p>',
    '      <a class="eventModal__tickets" href="#" target="_blank" rel="noopener noreferrer">Get Tickets</a>',
    '      <div class="eventModal__share">',
    '        <a class="shareBtn shareBtn--whatsapp" href="#" target="_blank" rel="noopener noreferrer">WhatsApp</a>',
    '        <a class="shareBtn shareBtn--messenger" href="#" target="_blank" rel="noopener noreferrer">Messenger</a>',
    '        <a class="shareBtn shareBtn--email" href="#">Email</a>',
    '      </div>',
    '    </div>',
    '  </div>',
    '</div>',
  ].join('\n');

  function ensureModal() {
    if (modal) return;
    document.body.insertAdjacentHTML('beforeend', MODAL_HTML);
    modal = document.getElementById('tbc-event-modal');
    modal.querySelector('.eventModal__backdrop').addEventListener('click', closeModal);
    modal.querySelector('.eventModal__close').addEventListener('click', closeModal);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal && !modal.hidden) closeModal();
    });
  }

  function openModal(card) {
    ensureModal();

    var title = card.dataset.title || '';
    var desc  = card.dataset.desc  || '';
    var url   = card.dataset.url   || '#';
    var img   = card.dataset.img   || '';
    var date  = card.dataset.date  || '';

    modal.querySelector('.eventModal__title').textContent = title;
    modal.querySelector('.eventModal__desc').textContent  = desc;

    var dateEl = modal.querySelector('.eventModal__date');
    dateEl.textContent = date;
    dateEl.hidden = !date;

    var imgWrap = modal.querySelector('.eventModal__img');
    var imgEl   = imgWrap.querySelector('img');
    if (img) {
      imgEl.src = img;
      imgEl.alt = title;
      imgWrap.hidden = false;
    } else {
      imgWrap.hidden = true;
    }

    modal.querySelector('.eventModal__tickets').href = url;

    var enc = encodeURIComponent;
    modal.querySelector('.shareBtn--whatsapp').href  =
      'https://wa.me/?text=' + enc(title + '\n' + url);
    modal.querySelector('.shareBtn--messenger').href =
      'fb-messenger://share/?link=' + enc(url);
    modal.querySelector('.shareBtn--email').href     =
      'mailto:?subject=' + enc(title) + '&body=' + enc(desc.slice(0, 300) + '\n\n' + url);

    modal.hidden = false;
    document.documentElement.style.overflow = 'hidden';
    modal.querySelector('.eventModal__close').focus();
  }

  function closeModal() {
    if (!modal) return;
    modal.hidden = true;
    document.documentElement.style.overflow = '';
  }

  function initCards() {
    document.querySelectorAll('.eventCard').forEach(function (card) {
      card.addEventListener('click', function (e) {
        if (e.target.closest('.eventCard__tickets')) return;
        openModal(card);
      });
      card.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          openModal(card);
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCards);
  } else {
    initCards();
  }
})();
