import React, { useState, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import './styles.css';

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
        <img src="/frames.svg" className="framesSvg" alt="Room frames" />
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
