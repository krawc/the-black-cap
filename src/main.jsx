import React from 'react';
import { createRoot } from 'react-dom/client';
import './styles.css';

function App() {
  return (
    <main className="stage" aria-label="Animated flame mark">
      <object
        className="flame"
        data="/simple_flame_animated.svg"
        type="image/svg+xml"
        aria-label="Animated neon flame"
      >
        Animated neon flame
      </object>
    </main>
  );
}

createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);
