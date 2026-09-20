import React, { useState, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import './styles.css';
import FrameGallery from './FrameGallery';

// Strokes lifted from /xmas/tree.svg (viewBox 0 0 105 144), each kept as its
// own path so it can flicker on a randomised neon loop independently.
// The three green paths form the tree's own outline, not a light string —
// they stay solidly lit (animating them caused visible cross-fade overlap
// where their strokes touch). Everything else is an ornament/light and
// flickers on its own randomised loop.
const festiveTreeStrokes = [
  { d: 'M70.665 85L81.665 103.5C82.665 105.5 83.165 113.5 75.165 114.5C67.165 115.5 65.165 112 52.165 112C41.165 112 36.165 114.5 31.165 114.5C26.165 114.5 21.165 109 23.665 103.5C25.665 99.1 31.831 89.3333 34.6641 85', color: '#5CFA00', static: true },
  { d: 'M42.1634 59L31.6634 77.5C30.83 79.5 30.2634 83.8 34.6634 85C40.1634 86.5 45.6641 84 52.1641 84C58.6641 84 68.1641 86 70.6641 85.5C73.1641 85 74.6641 80.5 73.1641 77.5C71.9641 75.1 65.6641 64.1667 62.6641 59', color: '#5CFA00', static: true },
  { d: 'M47.6594 39.5C44.8261 44.1667 39.0594 53.8 38.6594 55C38.1594 56.5 39.6594 59.5 41.6594 59.5C43.6594 59.5 48.1602 58.5 52.1602 58.5C56.1602 58.5 59.6602 59.5 63.1602 59.5C65.9602 59.5 65.9935 56.5 65.6602 55L57.1602 39.5', color: '#5CFA00', static: true },
  // Tree-topper star — stays solidly lit like the outline, not part of the
  // blinking light string. (When it flickered down to low opacity, the
  // always-on green outline showed straight through it, reading as the
  // star sitting "behind" the tree.)
  { d: 'M51.6094 38.8698L47.6641 41.5L46.7348 42.1195C45.985 42.6194 45.0085 41.9502 45.204 41.0705L46.5339 35.0857C46.614 34.7252 46.4889 34.3499 46.2085 34.1095L41.4127 29.9988C40.7486 29.4296 41.0837 28.3423 41.953 28.2457L48.0806 27.5648C48.4409 27.5248 48.7512 27.293 48.8919 26.959L51.6804 20.3362C52.0325 19.5 53.2267 19.5272 53.5404 20.3786L55.9632 26.9548C56.0874 27.2919 56.3828 27.5365 56.7372 27.5955L62.9286 28.6274C63.7858 28.7703 64.0654 29.8632 63.3821 30.4001L58.6301 34.1339C58.3427 34.3596 58.2014 34.7241 58.2615 35.0846L59.2846 41.2232C59.4285 42.0867 58.4719 42.7052 57.7435 42.2196L52.7188 38.8698C52.3829 38.6459 51.9453 38.6459 51.6094 38.8698Z', color: '#FFE101', static: true },
  { d: 'M29.5 106.5C33.1667 105.333 42.7 103 51.5 103C60.3 103 71.1667 105.333 75.5 106.5', color: '#00EFEF', delay: '1.8s', duration: '6.5s' },
  { d: 'M35 95C37.8696 94.3333 45.3304 93 52.2174 93C59.1043 93 67.6087 94.3333 71 95', color: '#EF00C7', delay: '0.7s', duration: '5.8s' },
  { d: 'M38 78C40.3116 77.3333 46.3217 76 51.8696 76C57.4174 76 64.2681 77.3333 67 78', color: '#FF9532', delay: '3.1s', duration: '7.1s' },
  { d: 'M42 69C43.6739 68.6667 48.0261 68 52.0435 68C56.0609 68 61.0217 68.6667 63 69', color: '#0F9BFF', delay: '2s', duration: '4.9s' },
  { d: 'M47 52C47.7971 51.6667 49.8696 51 51.7826 51C53.6957 51 56.058 51.6667 57 52', color: '#FF1317', delay: '0.9s', duration: '6.1s' },
];

function FestiveTree() {
  return (
    <svg
      className="festiveTree"
      viewBox="0 0 105 144"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      aria-label="Neon Christmas tree"
    >
      {festiveTreeStrokes.map((stroke, i) => (
        <path
          key={i}
          d={stroke.d}
          fill={stroke.d.endsWith('Z') ? stroke.color : 'none'}
          stroke={stroke.color}
          strokeWidth="3"
          strokeLinecap="round"
          className={`festiveTreeStroke${stroke.static ? ' festiveTreeStroke--static' : ''}`}
          style={{
            '--glow': stroke.color,
            animationDelay: stroke.delay,
            animationDuration: stroke.duration,
          }}
        />
      ))}
    </svg>
  );
}

const navItems = [
  {
    id: 'story',
    label: 'The Cap Story',
    color: '#D65CFF',
    x: '-16rem',
    y: '-10.4rem',
    mobileX: '-7.2rem',
    mobileY: '-6.2rem',
    sectionId: 'story',
  },
  {
    id: 'whats-on',
    label: "What's On",
    color: '#3F7CFF',
    x: '-9.8rem',
    y: '-14.6rem',
    mobileX: '-4.1rem',
    mobileY: '-9.7rem',
    sectionId: 'whats-on',
  },
  {
    id: 'menu',
    label: 'Menu',
    color: '#64F4FF',
    x: '0rem',
    y: '-16.8rem',
    mobileX: '0rem',
    mobileY: '-13.2rem',
    sectionId: 'menu',
  },
  {
    id: 'rooms',
    label: 'Our Rooms',
    color: '#79FF5A',
    x: '9.8rem',
    y: '-14.6rem',
    mobileX: '4.1rem',
    mobileY: '-9.7rem',
    sectionId: 'our-rooms',
  },
  {
    id: 'tables',
    label: 'Book a Table',
    color: '#FFF84A',
    x: '16rem',
    y: '-10.4rem',
    mobileX: '7.2rem',
    mobileY: '-6.2rem',
    href: '#',
  },
];

const instagramPosts = [
  'DX_5LwekcVa',
  'DYzKkfHMC7z',
  'DYxiCs7x4rm',
  'DYxhePkxa1n',
  'DYxgzTsRa8A',
  'DYxgfJNR2pn',
  'DYfNVZ_De71',
  'DYfNPIKFP5e',
];

// TikTok video IDs from @theblackcapuk — populate via TikTok Display API
// GET https://open.tiktokapis.com/v2/video/list/ (requires OAuth app at developers.tiktok.com)
// Each entry is the numeric video ID from the video URL
const highlights = [
  '7644927884900961558',
  '7642689026490912003',
  '7640829274840190240',
  '7640504644887776544',
  '7640442725908712737',
  '7640087100393606433',
  '7639762417546824992',
  '7639360399963360545',
];

const menuItems = [
  { category: 'Draught', items: [
    { name: 'Guinness',               price: '£6.50' },
    { name: 'Camden Hells Lager',     price: '£6.00' },
    { name: 'Camden Pale Ale',        price: '£6.20' },
    { name: 'Meantime London Lager',  price: '£6.20' },
  ]},
  { category: 'Wine', items: [
    { name: 'House glass',            price: '£7.00' },
    { name: 'House carafe',           price: '£22.00' },
    { name: 'Prosecco',               price: '£9.00' },
  ]},
  { category: 'Cocktails', items: [
    { name: 'Negroni',                price: '£12.00' },
    { name: 'Aperol Spritz',          price: '£11.00' },
    { name: 'Espresso Martini',       price: '£12.00' },
    { name: 'Pornstar Martini',       price: '£12.00' },
  ]},
  { category: 'Spirits & Mixers', items: [
    { name: 'Single & mixer',         price: 'from £8' },
    { name: 'Double & mixer',         price: 'from £11' },
    { name: 'Shot',                   price: 'from £4' },
  ]},
  { category: 'Soft Drinks & Low/No', items: [
    { name: 'Soft drinks',            price: 'from £3.50' },
    { name: 'Low & no-alcohol',       price: 'from £4.50' },
  ]},
];

const photos = [
  { id: 1, src: '/story/1.webp', scale: 1.3,  driftX:  1.2, driftY: -12.5 },
  { id: 3, src: '/story/3.webp', scale: 2.2,  driftX:  11.0, driftY: 3.0 },
    { id: 4, src: '/story/4.webp', scale: 2.45, driftX: -5.4, driftY: -9.0 },
  { id: 2, src: '/story/2.webp', scale: 1.1,  driftX: -11.6, driftY: 14.5 },
];

function App() {
  const [navOpen, setNavOpen] = useState(false);
  const [activeSection, setActiveSection] = useState(null);
  const photoRowRef = useRef(null);

  useEffect(() => {
    const update = () => {
      const row = photoRowRef.current;
      if (!row) return;
      const rect = row.getBoundingClientRect();
      const progress = (window.innerHeight / 2 - (rect.top + rect.height / 2)) / window.innerHeight;
      const mobile = window.innerWidth <= 700;
      Array.from(row.children).forEach((el, i) => {
        const photo = photos[i];
        if (!photo) return;
        el.style.transform = mobile
          ? `translateX(${photo.driftX * progress * 0.12}rem)`
          : `translate(${photo.driftX * progress}rem, ${photo.driftY * progress}rem)`;
      });
    };
    window.addEventListener('scroll', update, { passive: true });
    update();
    return () => window.removeEventListener('scroll', update);
  }, []);

  const activeLabel =
    navItems.find((item) => item.id === activeSection)?.label || 'Menu';

  function chooseSection(item) {
    setNavOpen(false);
    if (item.href) {
      window.open(item.href, '_blank', 'noopener,noreferrer');
      return;
    }
    document.getElementById(item.sectionId)?.scrollIntoView({ behavior: 'smooth' });
  }

  return (
    <main className="page">
      <section className="hero" aria-labelledby="hero-title">

        <div className={`logoOrbit ${navOpen ? 'isOpen' : ''}`}>
          <button
            className="logoButton"
            type="button"
            aria-expanded={navOpen}
            aria-controls="rainbow-menu"
            onClick={() => setNavOpen((open) => !open)}
          >
            <object
              className="flame"
              data="/simple_flame_animated.svg"
              type="image/svg+xml"
              aria-label="Animated neon Black Cap logo"
            >
              Animated neon Black Cap logo
            </object>
          </button>

          <nav className="rainbowMenu" id="rainbow-menu" aria-label="Main pages">
            {navItems.map((item, index) => (
              <button
                className="rainbowItem"
                data-index={index}
                key={item.id}
                style={{
                  '--item-color': item.color,
                  '--item-index': index,
                  '--item-x': item.x,
                  '--item-y': item.y,
                  '--mobile-item-x': item.mobileX,
                  '--mobile-item-y': item.mobileY,
                }}
                type="button"
                onClick={() => chooseSection(item)}
              >
                {item.label}
              </button>
            ))}
          </nav>
        </div>

        <div className="venueInfo" aria-label="Venue details">
          <a href="https://maps.google.com/?q=171+Camden+High+Street+London+NW1+7JY">
            171 Camden High Street, London NW1 7JY
          </a>
          <a href="tel:+442074282721">020 7428 2721</a>
          <a href="mailto:Sassy@blackcapcamden.co.uk">
            Sassy@blackcapcamden.co.uk
          </a>
        </div>
      </section>

      <section className="festive" id="festive">
        <div className="festiveScene">
          <div className="festiveTreeBlock">
            <FestiveTree />
          </div>
          <div className="festiveRight">
            <h2 className="festiveTitle">Festive</h2>
            <p className="festiveSubtitle">Celebration at The Black Cap</p>
            <p className="festiveCopy">
              This Christmas, we&rsquo;re turning up the glitter, glamour and festive
              fabulousness at The Black Cap, Camden!
            </p>
            <p className="festiveCopy">
              Get ready for a Christmas celebration that&rsquo;s bigger, campier and
              merrier than ever, with festive sparkle, Christmas cheer and enough
              tinsel to make Santa jealous.
            </p>
            <p className="festiveCopy">
              Whether you&rsquo;re planning a Christmas party, festive get-together,
              Christmas drinks or the ultimate Christmas night out, this is where your
              Christmas celebrations come to life.
            </p>
          </div>
        </div>
      </section>

      <section className="festiveColumns" id="festive-info">
        <div className="festiveColumnsGrid">
          <div className="festiveColumn">
            <img src="/xmas/cane.svg" className="festiveCane" style={{ '--cane-rotate': '-32deg' }} alt="" />
            <h3 className="festiveColumnTitle">Two Bars, Twice the Christmas Magic</h3>
            <p className="festiveColumnCopy">
              With two fabulous bars, there&rsquo;s plenty of room to get merry, raise
              a glass and celebrate in true Christmas style.
            </p>
            <p className="festiveColumnCopy">
              <span className="festiveBarName">Lily&rsquo;s Bar</span> brings the
              festive sparkle, with Christmas drinks, seasonal cheer and plenty of
              opportunities to get into the Christmas spirit.
            </p>
            <p className="festiveColumnCopy">
              <span className="festiveBarName">The Shufflewick Bar</span> offers a
              cosier festive escape, perfect for gathering your favourite people,
              sipping Christmas drinks and keeping the celebrations going.
            </p>
          </div>

          <div className="festiveColumn">
            <img src="/xmas/cane.svg" className="festiveCane" style={{ '--cane-rotate': '24deg' }} alt="" />
            <h3 className="festiveColumnTitle">Perfect for Your Christmas Celebration</h3>
            <ul className="festiveList">
              <li>Christmas parties</li>
              <li>Festive drinks</li>
              <li>Christmas group bookings</li>
              <li>Christmas gatherings</li>
              <li>Christmas nights out</li>
              <li>Festive celebrations</li>
              <li>End-of-year Christmas celebrations</li>
            </ul>
            <p className="festiveColumnCopy">
              So grab your Christmas crew, dust off your most outrageous festive
              outfit, pile on the glitter and get ready to celebrate Christmas in the
              most fabulous way possible.
            </p>
          </div>

          <div className="festiveColumn">
            <img src="/xmas/cane.svg" className="festiveCane" style={{ '--cane-rotate': '-16deg' }} alt="" />
            <h3 className="festiveColumnTitle">Merry, Camp &amp; Fabulous</h3>
            <p className="festiveColumnCopy">
              Because this Christmas, we&rsquo;re not just making it merry.
            </p>
            <p className="festiveColumnCopy festiveColumnCopyStrong">
              We&rsquo;re making it MERRY, CAMP AND FABULOUS!
            </p>
            <ul className="festiveShoutList">
              <li><span>Tinsel?</span> YES.</li>
              <li><span>Glitter?</span> OBVIOUSLY.</li>
              <li><span>Christmas spirit?</span> ABSOLUTELY.</li>
              <li><span>Being subtle?</span> NEVER.</li>
            </ul>
          </div>
        </div>
      </section>

      <section className="festiveClose" id="festive-contact">
        <img src="/xmas/santa.svg" className="festiveSanta" alt="" />
        <p className="festiveCloseHeadline">
          Make this Christmas extra.
          <br />
          Make it fabulous.
          <br />
          Make it Black Cap.
        </p>
        <p className="festiveCloseCopy">
          For Christmas booking enquiries, contact us here:{' '}
          <a href="mailto:Sassy@blackcapcamden.co.uk" className="festiveCloseEmail">
            Sassy@blackcapcamden.co.uk
          </a>
        </p>
      </section>

      <section className="whatsOn" id="whats-on">
        <div className="whatsOnInner">
          <h2 className="whatsOnTitle">What&rsquo;s On</h2>
        </div>
        <div className="posterSlider">
          <div className="posterTrack">
            {instagramPosts.length > 0
              ? instagramPosts.map((shortcode) => (
                  <div key={shortcode} className="instaWrapper">
                    <iframe
                      className="instaEmbed"
                      src={`https://www.instagram.com/p/${shortcode}/embed/captioned/?utm_source=ig_embed`}
                      scrolling="no"
                      allowTransparency="true"
                      title="Instagram post"
                    />
                  </div>
                ))
              : Array.from({ length: 5 }, (_, i) => (
                  <div key={i} className="posterCard" />
                ))}
          </div>
        </div>
      </section>

      <section className="content" id="story">
        <div className="legendaryScene">
          <div className="reginaBlock">
            <img src="/regina.svg" className="reginaSvg" alt="Regina Fong" />
          </div>
          <div className="legendaryRight">
            <h2 className="legendaryTitle">Legendary</h2>
            <p className="legendaryCopy">
              The Black Cap has been Camden&rsquo;s queer heartbeat since 1967. Two bars,
              a legendary terrace, and a performance room that&rsquo;s seen more feather boas
              than the law should allow. Shufflewick&rsquo;s is where the pints happen.
              The stage is where the magic does.
            </p>
            <div className="photoRow" ref={photoRowRef}>
              {photos.map(({ id, src, scale }) => (
                <div key={id} className="photoPlaceholder" style={{ '--h-scale': scale }}>
                  <img src={src} alt="" className="photoImg" />
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      <section className="highlights" id="highlights">
        <div className="highlightsInner">
          <h2 className="highlightsTitle">The Highlights</h2>
        </div>
        <div className="highlightSlider">
          <div className="highlightTrack">
            {highlights.length > 0 ? highlights.map((videoId) => (
              <div key={videoId} className="tiktokSlide">
                <iframe
                  className="tiktokEmbed"
                  src={`https://www.tiktok.com/embed/v2/${videoId}?theme=dark`}
                  allowFullScreen
                  allow="encrypted-media"
                  title="The Black Cap on TikTok"
                />
              </div>
            )) : Array.from({ length: 5 }, (_, i) => (
              <div key={i} className="highlightCard" />
            ))}
          </div>
        </div>
      </section>

      <section className="menuSection" id="menu">
        <div className="menuScene">
          <div className="menuSvgBlock">
            <img src="/neon-menu.svg" className="menuSvg" alt="" />
          </div>
          <div className="menuRight">
            <h2 className="menuHeadline">The Menu</h2>
            <div className="menuList">
              {menuItems.map(({ category, items }) => (
                <div key={category} className="menuCategory">
                  <p className="menuCategoryName">{category}</p>
                  {items.map(({ name, price }) => (
                    <div key={name} className="menuItem">
                      <span className="menuItemName">{name}</span>
                      <span className="menuItemPrice">{price}</span>
                    </div>
                  ))}
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      <section className="roomsSection" id="our-rooms">
        <h2 className="roomsHeadline">Our Rooms</h2>
        <div className="framesGrid">
          <FrameGallery svg="/frames/Frame 1.svg" photos={['/story/1.webp']} />
          <FrameGallery svg="/frames/Frame 2.svg" photos={['/story/2.webp']} />
          <FrameGallery svg="/frames/Frame 3.svg" photos={['/story/3.webp']} />
          <FrameGallery svg="/frames/Frame 4.svg" photos={['/story/4.webp']} />
          <FrameGallery svg="/frames/Frame 5.svg" photos={['/story/1.webp']} />
          <FrameGallery svg="/frames/Frame 6.svg" photos={['/story/2.webp', '/story/3.webp', '/story/4.webp']} className="frameWide" />
          <FrameGallery svg="/frames/Frame 7.svg" photos={['/story/2.webp']} />
          <FrameGallery svg="/frames/Frame 8.svg" photos={['/story/1.webp', '/story/3.webp', '/story/4.webp']} className="frameWide" />
        </div>
        <a href="#book" className="neonButton">See Availability</a>
      </section>

      <footer className="siteFooter">
        <nav className="footerLinks" aria-label="Legal">
          <a href="/privacy">Privacy Policy</a>
          <a href="/cookies">Cookie Policy</a>
          <a href="/terms">Terms &amp; Conditions</a>
          <a href="/accessibility">Accessibility</a>
        </nav>
        <p className="footerCopy">
          &copy; {new Date().getFullYear()} The Black Cap, 171 Camden High Street, London NW1 7JY.
          Registered in England &amp; Wales.
        </p>
      </footer>
    </main>
  );
}

createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);
