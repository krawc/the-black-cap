/* The Black Cap – frontend interactions */

/* ── Nav orbit toggle ───────────────────────────────────────────── */
(function () {
  const orbit = document.getElementById('tbc-logo-orbit');
  const btn   = document.getElementById('tbc-logo-btn');
  const cue   = document.getElementById('tbc-menu-cue');
  if (!orbit || !btn) return;

  var openedAt = 0;

  function toggleOrbit(e) {
    e.stopPropagation();
    const open = orbit.classList.toggle('isOpen');
    btn.setAttribute('aria-expanded', String(open));
    if (open) openedAt = Date.now();
  }

  btn.addEventListener('click', toggleOrbit);
  if (cue) cue.addEventListener('click', toggleOrbit);

  document.addEventListener('click', function (e) {
    if (!orbit.contains(e.target) && e.target !== cue) {
      orbit.classList.remove('isOpen');
      btn.setAttribute('aria-expanded', 'false');
    }
  });

  orbit.querySelectorAll('.rainbowItem').forEach(function (el) {
    el.addEventListener('click', function (e) {
      // Ignore clicks that land within 380 ms of the menu opening —
      // they're the same touch gesture that opened it.
      if (Date.now() - openedAt < 380) {
        e.preventDefault();
        return;
      }
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

/* ── Story photos: parallax or scroll-fade ──────────────────────── */
(function () {
  var row = document.getElementById('tbc-photo-row');
  if (!row) return;

  if (row.dataset.storyMode === 'fade') {
    /* Fade-in on scroll via IntersectionObserver */
    var obs = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });

    Array.from(row.children).forEach(function (el) { obs.observe(el); });
  } else {
    /* Parallax drift */
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
  }
})();

/* ── FrameGallery + Room lightbox ───────────────────────────────── */
(function () {
  var RED_SELECTOR = '[fill="#FF0000"],[fill="#ff0000"],[fill="#f00"],[fill="red"]';
  var MEWS_ID      = '8a6e6542-af3c-4a36-b19e-b36b00a8c958';

  /* ── Cursor tooltip ─────────────────────────────────────────────── */
  var tooltip = document.createElement('div');
  tooltip.className = 'roomTooltip';
  tooltip.setAttribute('aria-hidden', 'true');
  tooltip.hidden = true;
  document.body.appendChild(tooltip);

  document.addEventListener('mousemove', function (e) {
    if (!tooltip.hidden) {
      tooltip.style.left = (e.clientX + 16) + 'px';
      tooltip.style.top  = (e.clientY - 10) + 'px';
    }
  });

  /* ── Room lightbox ──────────────────────────────────────────────── */
  var rlbEl  = null;
  var rlbIdx = 0;
  var rlbPhotos = [];
  var rlbImgEl  = null;
  var rlbCounterEl = null;

  function setRlbPhoto(i) {
    rlbIdx = (i + rlbPhotos.length) % rlbPhotos.length;
    rlbImgEl.src = rlbPhotos[rlbIdx];
    if (rlbCounterEl) rlbCounterEl.textContent = (rlbIdx + 1) + ' / ' + rlbPhotos.length;
  }

  function openRoomLightbox(photos, name, desc) {
    closeRoomLightbox();
    rlbPhotos = photos;
    rlbIdx    = 0;

    var lb = document.createElement('div');
    lb.className = 'roomLightbox';
    lb.setAttribute('role', 'dialog');
    lb.setAttribute('aria-modal', 'true');

    var bd = document.createElement('div');
    bd.className = 'roomLightbox__bd';
    bd.addEventListener('click', closeRoomLightbox);
    lb.appendChild(bd);

    var panel = document.createElement('div');
    panel.className = 'roomLightbox__panel';
    lb.appendChild(panel);

    /* close button */
    var closeBtn = document.createElement('button');
    closeBtn.className = 'roomLightbox__close';
    closeBtn.setAttribute('aria-label', 'Close');
    closeBtn.textContent = '✕';
    closeBtn.addEventListener('click', closeRoomLightbox);
    panel.appendChild(closeBtn);

    /* image column */
    var imgWrap = document.createElement('div');
    imgWrap.className = 'roomLightbox__img-wrap';
    panel.appendChild(imgWrap);

    rlbImgEl = document.createElement('img');
    rlbImgEl.className = 'roomLightbox__img';
    rlbImgEl.alt = name;
    rlbImgEl.src = photos[0] || '';
    imgWrap.appendChild(rlbImgEl);

    if (photos.length > 1) {
      rlbCounterEl = document.createElement('span');
      rlbCounterEl.className = 'roomLightbox__counter';
      imgWrap.appendChild(rlbCounterEl);

      var prev = document.createElement('button');
      prev.className = 'roomLightbox__prev';
      prev.setAttribute('aria-label', 'Previous photo');
      prev.innerHTML = '&#8249;';
      prev.addEventListener('click', function () { setRlbPhoto(rlbIdx - 1); });
      imgWrap.appendChild(prev);

      var next = document.createElement('button');
      next.className = 'roomLightbox__next';
      next.setAttribute('aria-label', 'Next photo');
      next.innerHTML = '&#8250;';
      next.addEventListener('click', function () { setRlbPhoto(rlbIdx + 1); });
      imgWrap.appendChild(next);
    }

    /* info column */
    var info = document.createElement('div');
    info.className = 'roomLightbox__info';
    panel.appendChild(info);

    var titleEl = document.createElement('h2');
    titleEl.className = 'roomLightbox__title';
    titleEl.textContent = name;
    info.appendChild(titleEl);

    if (desc) {
      var descEl = document.createElement('p');
      descEl.className = 'roomLightbox__desc';

      var isMobile = window.innerWidth <= 640;
      var preview  = null;
      if (isMobile) {
        // Match up to and including the second sentence-ending punctuation
        var m = desc.match(/^.+?[.!?](?:\s|$).+?[.!?](?=\s|$)/su);
        if (m && m[0].length < desc.trim().length) {
          preview = m[0].trimEnd();
        }
      }

      if (preview) {
        var previewSpan = document.createElement('span');
        previewSpan.textContent = preview + ' ';
        descEl.appendChild(previewSpan);

        var readMore = document.createElement('button');
        readMore.className = 'roomLightbox__readMore';
        readMore.textContent = 'Read More…';
        readMore.addEventListener('click', function () {
          descEl.textContent = desc;
        });
        descEl.appendChild(readMore);
      } else {
        descEl.textContent = desc;
      }

      info.appendChild(descEl);
    }

    var cta = document.createElement('button');
    cta.className = 'neonButton roomLightbox__cta';
    cta.textContent = 'View Availability';
    cta.addEventListener('click', function () {
      closeRoomLightbox();
      if (window.MewsApi && window.MewsApi.open) {
        window.MewsApi.open();
      } else if (window.Mews && window.Mews.D) {
        // Site snippet initialised Mews without a callback, so capture the
        // API now and open. window.MewsApi is cached for subsequent clicks.
        Mews.D(['8a6e6542-af3c-4a36-b19e-b36b00a8c958'], function (api) {
          window.MewsApi = api;
          api.open();
        });
      }
    });
    info.appendChild(cta);

    document.body.appendChild(lb);
    document.addEventListener('keydown', onRlbKey);
    rlbEl = lb;
    setRlbPhoto(0);
  }

  function closeRoomLightbox() {
    if (rlbEl) { rlbEl.remove(); rlbEl = null; }
    rlbImgEl = null;
    rlbCounterEl = null;
    document.removeEventListener('keydown', onRlbKey);
  }

  function onRlbKey(e) {
    if (e.key === 'Escape') { closeRoomLightbox(); return; }
    if (e.key === 'ArrowLeft')  setRlbPhoto(rlbIdx - 1);
    if (e.key === 'ArrowRight') setRlbPhoto(rlbIdx + 1);
  }

  /* ── SVG frame init ─────────────────────────────────────────────── */
  function initFrame(container) {
    var svgUrl   = container.dataset.svg;
    var photos   = [];
    var roomName = container.dataset.roomName || '';
    var roomDesc = container.dataset.roomDesc || '';
    try { photos = JSON.parse(container.dataset.photos || '[]'); } catch (_) {}
    if (!svgUrl || !photos.length) return;

    container.style.cursor = 'pointer';

    container.addEventListener('mouseenter', function () {
      if (!roomName) return;
      tooltip.textContent = roomName;
      tooltip.hidden = false;
    });
    container.addEventListener('mouseleave', function () {
      tooltip.hidden = true;
    });
    container.addEventListener('click', function () {
      openRoomLightbox(photos, roomName, roomDesc);
    });

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

          var imgEl = document.createElementNS('http://www.w3.org/2000/svg', 'image');
          imgEl.setAttribute('href', photo);
          imgEl.setAttribute('x', String(bbox.x));
          imgEl.setAttribute('y', String(bbox.y));
          imgEl.setAttribute('width', String(bbox.width));
          imgEl.setAttribute('height', String(bbox.height));
          imgEl.setAttribute('preserveAspectRatio', 'xMidYMid slice');
          imgEl.setAttribute('clip-path', 'url(#' + clipId + ')');

          el.replaceWith(imgEl);
        });
      })
      .catch(function (err) { console.error('[FrameGallery]', err); });
  }

  document.querySelectorAll('.frameGallery[data-svg]').forEach(initFrame);

  /* ── Mobile room slide cards ────────────────────────────────────── */
  document.querySelectorAll('.roomSlideCard').forEach(function (card) {
    var photos   = [];
    var roomName = card.dataset.roomName || '';
    var roomDesc = card.dataset.roomDesc || '';
    try { photos = JSON.parse(card.dataset.photos || '[]'); } catch (_) {}
    card.style.cursor = 'pointer';
    card.addEventListener('click', function () {
      openRoomLightbox(photos, roomName, roomDesc);
    });
  });
})();

/* ── Event card modal ───────────────────────────────────────────── */
(function () {
  'use strict';

  var modal = null;

  var ICONS = window.TBC_ICONS || {};

  var MODAL_HTML = [
    '<div class="eventModal" id="tbc-event-modal" role="dialog" aria-modal="true" aria-label="Event details" hidden>',
    '  <div class="eventModal__backdrop"></div>',
    '  <div class="eventModal__panel">',
    '    <button class="eventModal__close" aria-label="Close">&#x2715;</button>',
    '    <div class="eventModal__img"><img src="" alt="" loading="eager"></div>',
    '    <div class="eventModal__body">',
    '      <time class="eventModal__date"></time>',
    '      <h3 class="eventModal__title"></h3>',
    '      <p class="eventModal__desc"></p>',
    '      <div class="eventModal__actions">',
    '        <a class="eventModal__tickets" href="#" target="_blank" rel="noopener noreferrer">Get Tickets</a>',
    '        <a class="shareBtn shareBtn--whatsapp" href="#" target="_blank" rel="noopener noreferrer" aria-label="Share on WhatsApp"></a>',
    '        <a class="shareBtn shareBtn--messenger" href="#" target="_blank" rel="noopener noreferrer" aria-label="Share on Messenger"></a>',
    '        <a class="shareBtn shareBtn--email" href="#" aria-label="Share via Email"></a>',
    '      </div>',
    '    </div>',
    '  </div>',
    '</div>',
  ].join('\n');

  function ensureModal() {
    if (modal) return;
    document.body.insertAdjacentHTML('beforeend', MODAL_HTML);
    modal = document.getElementById('tbc-event-modal');

    // Inject SVG icons once
    modal.querySelector('.shareBtn--whatsapp').innerHTML = ICONS.whatsapp;
    modal.querySelector('.shareBtn--messenger').innerHTML = ICONS.messenger;
    modal.querySelector('.shareBtn--email').innerHTML    = ICONS.email;

    modal.querySelector('.eventModal__backdrop').addEventListener('click', closeModal);
    modal.querySelector('.eventModal__close').addEventListener('click', closeModal);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal && !modal.hidden) closeModal();
    });
  }

  function openModal(card) {
    ensureModal();

    var title       = card.dataset.title     || '';
    var desc        = card.dataset.desc      || '';
    var url         = card.dataset.url       || '#';
    var img         = card.dataset.img       || '';
    var date        = card.dataset.date      || '';
    var isPast      = card.dataset.past      === '1';
    var isRecurring = card.dataset.recurring === '1';
    var allDates    = [];
    if ( isRecurring ) {
      try { allDates = JSON.parse( card.dataset.dates || '[]' ); } catch (_) {}
    }

    modal.querySelector('.eventModal__title').textContent = title;
    modal.querySelector('.eventModal__desc').textContent  = desc;

    var dateEl = modal.querySelector('.eventModal__date');
    if ( isRecurring && allDates.length ) {
      dateEl.innerHTML = allDates
        .map( function (d) { return '<span class="eventModal__dates-item">' + d + '</span>'; } )
        .join('');
      dateEl.hidden = false;
    } else {
      dateEl.textContent = date;
      dateEl.hidden = !date;
    }

    // Image with preloader — hide first, fade in once loaded
    var imgWrap = modal.querySelector('.eventModal__img');
    var imgEl   = imgWrap.querySelector('img');

    imgEl.style.opacity = '0';
    imgEl.src = '';                                // clear previous immediately
    imgWrap.classList.remove('eventModal__img--loading');

    if (img) {
      imgWrap.hidden = false;
      imgWrap.classList.add('eventModal__img--loading');

      imgEl.onload = function () {
        imgWrap.classList.remove('eventModal__img--loading');
        imgEl.style.opacity = '1';
      };
      imgEl.onerror = function () {
        imgWrap.classList.remove('eventModal__img--loading');
        imgWrap.hidden = true;
      };

      imgEl.alt = title;
      imgEl.src = img;
    } else {
      imgWrap.hidden = true;
    }

    // Tickets button
    var ticketsEl = modal.querySelector('.eventModal__tickets');
    ticketsEl.href        = url;
    ticketsEl.textContent = isPast ? 'View Event' : 'Get Tickets';

    // Share buttons — hidden for past events
    modal.querySelectorAll('.shareBtn').forEach(function (el) {
      el.hidden = isPast;
    });

    if (!isPast) {
      var enc = encodeURIComponent;
      modal.querySelector('.shareBtn--whatsapp').href =
        'https://wa.me/?text=' + enc(title + '\n' + url);
      modal.querySelector('.shareBtn--messenger').href =
        'fb-messenger://share/?link=' + enc(url);
      modal.querySelector('.shareBtn--email').href =
        'mailto:?subject=' + enc(title) + '&body=' + enc(desc.slice(0, 300) + '\n\n' + url);
    }

    modal.hidden = false;
    document.documentElement.style.overflow = 'hidden';
    modal.querySelector('.eventModal__close').focus();
  }

  function closeModal() {
    if (!modal) return;
    modal.hidden = true;
    document.documentElement.style.overflow = '';
    // Clear image so there's no flash on next open
    var imgEl = modal.querySelector('.eventModal__img img');
    if (imgEl) { imgEl.src = ''; imgEl.style.opacity = '0'; }
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

/* ── Venue Hire ─────────────────────────────────────────────────── */
(function () {
  'use strict';

  /* ── Lightbox ── */
  var venueLb       = null;
  var venueLbPhotos = [];
  var venueLbIdx    = 0;

  var LB_HTML = [
    '<div class="venueLb" id="tbc-venue-lb" role="dialog" aria-modal="true" hidden>',
    '  <div class="venueLb__bd"></div>',
    '  <button class="venueLb__close" aria-label="Close">&#x2715;</button>',
    '  <div class="venueLb__inner">',
    '    <img class="venueLb__img" src="" alt="">',
    '    <button class="venueLb__prev" aria-label="Previous photo">&#x2039;</button>',
    '    <button class="venueLb__next" aria-label="Next photo">&#x203a;</button>',
    '    <span class="venueLb__counter"></span>',
    '  </div>',
    '</div>',
  ].join('');

  function ensureLb() {
    if (venueLb) return;
    document.body.insertAdjacentHTML('beforeend', LB_HTML);
    venueLb = document.getElementById('tbc-venue-lb');

    venueLb.querySelector('.venueLb__bd').addEventListener('click', closeLb);
    venueLb.querySelector('.venueLb__close').addEventListener('click', closeLb);
    venueLb.querySelector('.venueLb__prev').addEventListener('click', function () { showSlide(venueLbIdx - 1); });
    venueLb.querySelector('.venueLb__next').addEventListener('click', function () { showSlide(venueLbIdx + 1); });

    document.addEventListener('keydown', function (e) {
      if (!venueLb || venueLb.hidden) return;
      if (e.key === 'Escape')     { closeLb(); return; }
      if (e.key === 'ArrowLeft')  showSlide(venueLbIdx - 1);
      if (e.key === 'ArrowRight') showSlide(venueLbIdx + 1);
    });
  }

  function showSlide(idx) {
    if (!venueLbPhotos.length) return;
    venueLbIdx    = ((idx % venueLbPhotos.length) + venueLbPhotos.length) % venueLbPhotos.length;
    var total     = venueLbPhotos.length;
    var single    = total === 1;
    var imgEl     = venueLb.querySelector('.venueLb__img');

    imgEl.style.opacity = '0';
    imgEl.src = '';
    imgEl.onload  = function () { imgEl.style.opacity = '1'; };
    imgEl.onerror = function () { imgEl.style.opacity = '1'; };
    imgEl.src = venueLbPhotos[venueLbIdx];

    venueLb.querySelector('.venueLb__counter').textContent = single ? '' : (venueLbIdx + 1) + ' / ' + total;
    venueLb.querySelector('.venueLb__prev').hidden = single;
    venueLb.querySelector('.venueLb__next').hidden = single;
  }

  function openLb(photos, startIdx) {
    ensureLb();
    venueLbPhotos = photos;
    venueLb.hidden = false;
    document.documentElement.style.overflow = 'hidden';
    showSlide(startIdx);
    venueLb.querySelector('.venueLb__close').focus();
  }

  function closeLb() {
    if (!venueLb) return;
    venueLb.hidden = true;
    document.documentElement.style.overflow = '';
    var imgEl = venueLb.querySelector('.venueLb__img');
    imgEl.src = '';
    imgEl.style.opacity = '0';
  }

  /* ── Main init ── */
  function initVenueHire() {
    document.querySelectorAll('.venueHireLayout').forEach(function (layout) {
      var venues = {};
      try { venues = JSON.parse(layout.dataset.venues || '{}'); } catch (_) {}

      var shapes    = layout.querySelectorAll('.venueShape');
      var hitZones  = layout.querySelectorAll('.venueHitZone');
      var nameEl    = layout.querySelector('.venueHirePanel__name');
      var descEl    = layout.querySelector('.venueHirePanel__desc');
      var thumbGrid = layout.querySelector('.venueThumbGrid');

      if (!shapes.length) return;

      function renderThumbs(photos) {
        if (!thumbGrid) return;
        thumbGrid.innerHTML = '';
        (photos || []).forEach(function (url, i) {
          var img = document.createElement('img');
          img.className = 'venueThumb';
          img.src       = url;
          img.alt       = '';
          img.tabIndex  = 0;
          img.addEventListener('click', function () { openLb(photos, i); });
          img.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openLb(photos, i); }
          });
          thumbGrid.appendChild(img);
        });
      }

      function activate(idx) {
        shapes.forEach(function (s) { s.classList.remove('venueShape--active'); });
        if (shapes[idx]) shapes[idx].classList.add('venueShape--active');
        var v = venues[idx] || {};
        if (nameEl) nameEl.textContent = v.title || '';
        if (descEl) descEl.textContent = v.desc  || '';
        renderThumbs(v.photos || []);
      }

      shapes.forEach(function (shape, i) {
        shape.addEventListener('mouseenter', function () { activate(i); });
        shape.addEventListener('click',      function () { activate(i); });
        shape.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); activate(i); }
        });
      });

      hitZones.forEach(function (hz) {
        var i = parseInt(hz.dataset.venueIndex, 10);
        hz.addEventListener('mouseenter', function () { activate(i); });
        hz.addEventListener('click',      function () { activate(i); });
      });

      var panel   = layout.querySelector('.venueHirePanel');
      var initIdx = panel ? (parseInt(panel.dataset.activeIdx, 10) || 0) : 0;
      activate(initIdx);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initVenueHire);
  } else {
    initVenueHire();
  }
})();

/* ── Timeline lightbox ──────────────────────────────────────────── */
(function () {
  'use strict';

  var lb        = null;
  var lbData    = [];
  var lbIdx     = 0;

  var LB_HTML = [
    '<div class="tlLightbox" id="tbc-tl-lb" role="dialog" aria-modal="true" aria-label="Photo" hidden>',
    '  <div class="tlLightbox__bd"></div>',
    '  <div class="tlLightbox__panel">',
    '    <button class="tlLightbox__close" aria-label="Close">&#x2715;</button>',
    '    <div class="tlLightbox__img-wrap">',
    '      <img class="tlLightbox__img" src="" alt="">',
    '      <button class="tlLightbox__prev" aria-label="Previous photo">&#x2039;</button>',
    '      <button class="tlLightbox__next" aria-label="Next photo">&#x203a;</button>',
    '      <span class="tlLightbox__counter"></span>',
    '    </div>',
    '    <div class="tlLightbox__info">',
    '      <span class="tlLightbox__years"></span>',
    '      <h3 class="tlLightbox__title"></h3>',
    '      <p class="tlLightbox__desc"></p>',
    '    </div>',
    '  </div>',
    '</div>',
  ].join('');

  function ensureLb() {
    if (lb) return;
    document.body.insertAdjacentHTML('beforeend', LB_HTML);
    lb = document.getElementById('tbc-tl-lb');

    lb.querySelector('.tlLightbox__bd').addEventListener('click', closeLb);
    lb.querySelector('.tlLightbox__close').addEventListener('click', closeLb);
    lb.querySelector('.tlLightbox__prev').addEventListener('click', function () { showSlide(lbIdx - 1); });
    lb.querySelector('.tlLightbox__next').addEventListener('click', function () { showSlide(lbIdx + 1); });

    document.addEventListener('keydown', function (e) {
      if (!lb || lb.hidden) return;
      if (e.key === 'Escape')      { closeLb(); return; }
      if (e.key === 'ArrowLeft')   showSlide(lbIdx - 1);
      if (e.key === 'ArrowRight')  showSlide(lbIdx + 1);
    });
  }

  function showSlide(idx) {
    if (!lbData.length) return;
    lbIdx = ((idx % lbData.length) + lbData.length) % lbData.length;
    var item   = lbData[lbIdx];
    var total  = lbData.length;
    var imgEl  = lb.querySelector('.tlLightbox__img');
    var single = total === 1;

    imgEl.style.opacity = '0';
    imgEl.src = '';
    imgEl.onload  = function () { imgEl.style.opacity = '1'; };
    imgEl.onerror = function () { imgEl.style.opacity = '1'; };
    imgEl.alt = item.alt || '';
    imgEl.src = item.url;

    lb.querySelector('.tlLightbox__years').textContent = item.years || '';
    lb.querySelector('.tlLightbox__title').textContent = item.title || '';
    lb.querySelector('.tlLightbox__desc').textContent  = item.desc  || '';
    lb.querySelector('.tlLightbox__counter').textContent = single
      ? '' : (lbIdx + 1) + ' / ' + total;

    lb.querySelector('.tlLightbox__prev').hidden = single;
    lb.querySelector('.tlLightbox__next').hidden = single;
  }

  function openAt(data, idx) {
    ensureLb();
    lbData = data;
    lb.hidden = false;
    document.documentElement.style.overflow = 'hidden';
    showSlide(idx);
    lb.querySelector('.tlLightbox__close').focus();
  }

  function closeLb() {
    if (!lb) return;
    lb.hidden = true;
    document.documentElement.style.overflow = '';
    var imgEl = lb.querySelector('.tlLightbox__img');
    imgEl.src = '';
    imgEl.style.opacity = '0';
  }

  function initTimelines() {
    document.querySelectorAll('.timelineSection').forEach(function (section) {
      // ── Intro fade-in via IntersectionObserver ──
      var intro = section.querySelector('.timelineIntro');
      if (intro && 'IntersectionObserver' in window) {
        new IntersectionObserver(function (entries, obs) {
          entries.forEach(function (e) {
            if (e.isIntersecting) {
              intro.classList.add('isVisible');
              obs.unobserve(intro);
            }
          });
        }, { threshold: 0.2 }).observe(intro);
      } else if (intro) {
        intro.classList.add('isVisible'); // fallback: always visible
      }

      // ── Lightbox ──
      var dataEl = section.querySelector('.tbc-timeline-data');
      if (!dataEl) return;

      var data;
      try { data = JSON.parse(dataEl.textContent); } catch (_) { return; }
      if (!Array.isArray(data) || !data.length) return;

      section.querySelectorAll('.timelineThumb').forEach(function (btn) {
        btn.addEventListener('click', function () {
          openAt(data, parseInt(btn.dataset.index, 10) || 0);
        });
      });

      section.querySelectorAll('.timelineItem__card[data-tl-index]').forEach(function (card) {
        var idx = parseInt(card.dataset.tlIndex, 10) || 0;
        card.addEventListener('click', function () { openAt(data, idx); });
        card.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openAt(data, idx); }
        });
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTimelines);
  } else {
    initTimelines();
  }
})();
