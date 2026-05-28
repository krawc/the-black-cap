import React, { useState } from 'react';
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
  },
  {
    id: 'whats-on',
    label: "What's On",
    color: '#3F7CFF',
    x: '-9.8rem',
    y: '-14.6rem',
    mobileX: '-4.1rem',
    mobileY: '-9.7rem',
  },
  {
    id: 'menu',
    label: 'Menu',
    color: '#64F4FF',
    x: '0rem',
    y: '-16.8rem',
    mobileX: '0rem',
    mobileY: '-13.2rem',
  },
  {
    id: 'rooms',
    label: 'Our Rooms',
    color: '#79FF5A',
    x: '9.8rem',
    y: '-14.6rem',
    mobileX: '4.1rem',
    mobileY: '-9.7rem',
  },
  {
    id: 'tables',
    label: 'Book a Table',
    color: '#FFF84A',
    x: '16rem',
    y: '-10.4rem',
    mobileX: '7.2rem',
    mobileY: '-6.2rem',
  },
];

const posters = Array.from({ length: 7 }, (_, i) => ({ id: i + 1 }));

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
  { id: 1, width: 'clamp(12rem,26vw,26rem)', aspectRatio: '3/4',  duration: '10s', delay: '0s',  driftX: '0.4rem',  driftY: '-1.5rem' },
  { id: 2, width: 'clamp(16rem,34vw,34rem)', aspectRatio: '4/3',  duration: '13s', delay: '-5s', driftX: '-0.4rem', driftY: '-2rem'   },
  { id: 3, width: 'clamp(11rem,22vw,22rem)', aspectRatio: '2/3',  duration: '11s', delay: '-3s', driftX: '0.3rem',  driftY: '-1rem'   },
];

function App() {
  const [navOpen, setNavOpen] = useState(false);
  const [activeSection, setActiveSection] = useState(null);

  const activeLabel =
    navItems.find((item) => item.id === activeSection)?.label || 'Menu';

  function chooseSection(id) {
    setActiveSection(id);
    setNavOpen(true);
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
                onClick={() => chooseSection(item.id)}
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

      <section className="content">
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
            <div className="photoRow">
              {photos.map(({ id, width, aspectRatio, duration, delay, driftX, driftY }) => (
                <div
                  key={id}
                  className="photoPlaceholder"
                  style={{
                    width,
                    aspectRatio,
                    animationDuration: duration,
                    animationDelay: delay,
                    '--drift-x': driftX,
                    '--drift-y': driftY,
                  }}
                />
              ))}
            </div>
          </div>
        </div>
      </section>

      <section className="whatsOn">
        <div className="whatsOnInner">
          <h2 className="whatsOnTitle">What&rsquo;s On</h2>
        </div>
        <div className="posterSlider">
          <div className="posterTrack">
            {posters.map(({ id }) => (
              <div key={id} className="posterCard" />
            ))}
          </div>
        </div>
      </section>

      <section className="menuSection">
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
    </main>
  );
}

createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);
