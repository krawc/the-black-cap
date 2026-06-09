import { useState, useEffect, useLayoutEffect, useRef, memo } from 'react';

const RED_SELECTOR = '[fill="#FF0000"],[fill="#ff0000"],[fill="#f00"],[fill="red"]';

function Lightbox({ photo, onClose }) {
  useEffect(() => {
    const onKey = e => { if (e.key === 'Escape') onClose(); };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [onClose]);

  return (
    <div className="lightboxOverlay" onClick={onClose}>
      <button className="lightboxClose" aria-label="Close" onClick={onClose}>✕</button>
      <img
        className="lightboxImg"
        src={photo}
        alt=""
        onClick={e => e.stopPropagation()}
      />
    </div>
  );
}

// Memoised so it never re-renders due to lightbox state changes in the parent —
// that would re-apply dangerouslySetInnerHTML and wipe the phase-2 DOM work.
const FrameSVG = memo(function FrameSVG({ svg, photos, className, onPhotoClick }) {
  const containerRef = useRef(null);
  const uidRef = useRef(Math.random().toString(36).slice(2, 7));
  const photosRef = useRef(photos);
  photosRef.current = photos;

  const [phase1Markup, setPhase1Markup] = useState('');

  useEffect(() => {
    if (!svg) return;
    let cancelled = false;

    fetch(svg)
      .then(r => r.text())
      .then(text => {
        if (cancelled) return;
        const doc = new DOMParser().parseFromString(text, 'image/svg+xml');
        const svgEl = doc.documentElement;
        [...svgEl.querySelectorAll(RED_SELECTOR)].forEach((el, i) => {
          el.setAttribute('data-fg-index', String(i));
        });
        setPhase1Markup(new XMLSerializer().serializeToString(svgEl));
      })
      .catch(err => console.error('[FrameGallery]', err));

    return () => { cancelled = true; };
  }, [svg]);

  useLayoutEffect(() => {
    if (!phase1Markup || !containerRef.current) return;

    const svgEl = containerRef.current.querySelector('svg');
    if (!svgEl) return;

    const uid = uidRef.current;
    const photos = photosRef.current;

    let defs = svgEl.querySelector('defs');
    if (!defs) {
      defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
      svgEl.insertBefore(defs, svgEl.firstChild);
    }

    defs.querySelectorAll(`[id^="fg-${uid}-"]`).forEach(el => el.remove());

    svgEl.querySelectorAll('[data-fg-index]').forEach(el => {
      const i = parseInt(el.getAttribute('data-fg-index'), 10);
      const bbox = el.getBBox();
      const clipId = `fg-${uid}-${i}`;
      const photo = photos[i % photos.length];

      const cp = document.createElementNS('http://www.w3.org/2000/svg', 'clipPath');
      cp.setAttribute('id', clipId);
      cp.setAttribute('clipPathUnits', 'userSpaceOnUse');
      const shape = el.cloneNode(true);
      shape.removeAttribute('fill');
      shape.removeAttribute('data-fg-index');
      cp.appendChild(shape);
      defs.appendChild(cp);

      const img = document.createElementNS('http://www.w3.org/2000/svg', 'image');
      img.setAttribute('href', photo);
      img.setAttribute('x', String(bbox.x));
      img.setAttribute('y', String(bbox.y));
      img.setAttribute('width', String(bbox.width));
      img.setAttribute('height', String(bbox.height));
      img.setAttribute('preserveAspectRatio', 'xMidYMid slice');
      img.setAttribute('clip-path', `url(#${clipId})`);
      img.style.cursor = 'pointer';
      img.addEventListener('click', () => onPhotoClick(photo));

      el.replaceWith(img);
    });
  }, [phase1Markup]);

  return (
    <div
      ref={containerRef}
      className={`frameGallery${className ? ` ${className}` : ''}`}
      dangerouslySetInnerHTML={{ __html: phase1Markup }}
    />
  );
});

export default function FrameGallery({ svg, photos = [], className = '' }) {
  const [lightboxPhoto, setLightboxPhoto] = useState(null);

  return (
    <>
      <FrameSVG
        svg={svg}
        photos={photos}
        className={className}
        onPhotoClick={setLightboxPhoto}
      />
      {lightboxPhoto && (
        <Lightbox photo={lightboxPhoto} onClose={() => setLightboxPhoto(null)} />
      )}
    </>
  );
}
