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
