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

const drinks = [
  { name: 'Draught beer', detail: 'Fresh pints, Camden-ready pricing', price: 'TBC' },
  { name: 'House wine', detail: 'Red, white, rose, sparkling', price: 'TBC' },
  { name: 'Signature spritz', detail: 'Bright, sharp, built for the terrace', price: 'TBC' },
  { name: 'Classic cocktails', detail: 'The usual favourites, properly poured', price: 'TBC' },
  { name: 'No and low', detail: 'Alcohol-free beers, spritzes, softs', price: 'TBC' },
];

const openingTimes = [
  { space: "Shufflewick's", times: 'Sun-Thu 12pm-12am, Fri-Sat 12pm-2am' },
  { space: "Lily's Bar", times: 'Show nights and late evenings' },
  { space: 'Regina Fong Terrace', times: 'Open weather permitting' },
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

      <section className="content" aria-labelledby="section-title">
        <div className="sectionHeader">
          <p className="eyebrow">First baseline focus</p>
          <h2 id="section-title">{activeLabel}</h2>
        </div>

        <div className="featureGrid">
          <section className="menuPanel" aria-labelledby="menu-title">
            <div className="panelTop">
              <p className="eyebrow">Drinks menu</p>
              <h3 id="menu-title">Prices people can check before the weekend.</h3>
            </div>

            <div className="drinkList">
              {drinks.map((drink) => (
                <article className="drinkItem" key={drink.name}>
                  <div>
                    <h4>{drink.name}</h4>
                    <p>{drink.detail}</p>
                  </div>
                  <strong>{drink.price}</strong>
                </article>
              ))}
            </div>
          </section>

          <aside className="sideStack" aria-label="Venue snapshot">
            <section className="miniPanel">
              <p className="eyebrow">Opening times</p>
              {openingTimes.map((item) => (
                <div className="timesRow" key={item.space}>
                  <strong>{item.space}</strong>
                  <span>{item.times}</span>
                </div>
              ))}
            </section>

            <section className="miniPanel rainbowEdge">
              <p className="eyebrow">Coming next</p>
              <h3>Shows, rooms, table bookings, and the short story of why The Cap matters.</h3>
              <p>
                Built to receive posters, room photography, booking links, and short
                history clips as soon as management signs them off.
              </p>
            </section>
          </aside>
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
