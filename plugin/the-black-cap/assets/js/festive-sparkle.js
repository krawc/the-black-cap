/**
 * Shimmering sparkle canvas background for the Festive (Christmas) block.
 * Paints behind all three festive sections (tree intro / info columns /
 * closing strip), which share one wrapper — see .festiveWrapper and
 * .festiveSparkleCanvas in frontend.css, and the markup in
 * src/blocks/festive/render.php.
 */
( function () {
	var canvas = document.getElementById( 'sparkle-canvas' );
	if ( ! canvas ) return;

	var ctx = canvas.getContext( '2d' );
	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	var particles = [];
	var frameCount = 0;
	var rafId = null;
	var isVisible = false;

	function hsbToRgb( h, s, b ) {
		s /= 100;
		b /= 100;
		var k = function ( n ) { return ( n + h / 60 ) % 6; };
		var f = function ( n ) { return b * ( 1 - s * Math.max( 0, Math.min( k( n ), 4 - k( n ), 1 ) ) ); };
		return [
			Math.round( 255 * f( 5 ) ),
			Math.round( 255 * f( 3 ) ),
			Math.round( 255 * f( 1 ) ),
		];
	}

	function randomGaussian( mean, stdDev ) {
		var u1 = Math.random();
		var u2 = Math.random();
		var z0 = Math.sqrt( -2 * Math.log( u1 ) ) * Math.cos( 2 * Math.PI * u2 );
		return mean + z0 * stdDev;
	}

	// Elegant, desaturated palette: Dusty Rose, Champagne Gold, Soft
	// Teal/Mint, Deep Plum/Burgundy, Lavender Mauve.
	var SUBTLE_PALETTE = [
		{ h: 340, s: 40, b: 85 },
		{ h: 42,  s: 45, b: 90 },
		{ h: 165, s: 35, b: 80 },
		{ h: 320, s: 50, b: 65 },
		{ h: 280, s: 25, b: 85 },
	];

	function EdgeParticle() {
		this.size = 1.2 + Math.random() * 2.8;
		this.pulseSpeed = 0.003 + Math.random() * 0.007;
		this.pulseOffset = Math.random() * Math.PI * 2;

		var col = SUBTLE_PALETTE[ Math.floor( Math.random() * SUBTLE_PALETTE.length ) ];
		this.hue = col.h;
		this.sat = col.s;
		this.baseBri = col.b;

		this.initPosition();
	}

	EdgeParticle.prototype.initPosition = function () {
		var isLeft = Math.random() > 0.5;
		var u = Math.random();
		var pad = this.size + 1;

		var usableWidth = canvas.width - pad * 2;
		var usableHeight = canvas.height - pad * 2;
		if ( usableWidth <= 0 || usableHeight <= 0 ) return;

		if ( isLeft ) {
			this.x = pad + Math.pow( u, 3 ) * ( usableWidth * 0.45 );
		} else {
			this.x = canvas.width - pad - Math.pow( u, 3 ) * ( usableWidth * 0.45 );
		}

		var yPos = randomGaussian( canvas.height * 0.5, canvas.height * 0.22 );
		this.y = Math.max( pad, Math.min( canvas.height - pad, yPos ) );

		var distFromCenter = Math.abs( this.x - canvas.width / 2 ) / ( canvas.width / 2 );
		var heroProbability = Math.pow( distFromCenter, 2 ) * 0.18;
		this.isHero = Math.random() < heroProbability;

		if ( this.isHero ) {
			this.minAlpha = 0.5;
			this.maxAlpha = 1.0;
		} else {
			this.minAlpha = 0.12;
			this.maxAlpha = 0.35;
		}
	};

	EdgeParticle.prototype.update = function () {
		var pulse = Math.sin( frameCount * this.pulseSpeed + this.pulseOffset );
		var currentAlpha = this.minAlpha + ( ( pulse + 1 ) / 2 ) * ( this.maxAlpha - this.minAlpha );
		var currentSat = Math.max( 5, this.sat - ( ( pulse + 1 ) / 2 ) * ( this.isHero ? 20 : 8 ) );
		var rgb = hsbToRgb( this.hue, currentSat, this.baseBri );
		this.colorStr = 'rgba(' + rgb[ 0 ] + ',' + rgb[ 1 ] + ',' + rgb[ 2 ] + ',' + currentAlpha.toFixed( 2 ) + ')';
	};

	EdgeParticle.prototype.draw = function () {
		ctx.fillStyle = this.colorStr;
		ctx.beginPath();
		ctx.arc( this.x, this.y, this.size, 0, Math.PI * 2 );
		ctx.fill();
	};

	// Particle count = half the canvas's own width, which spans the
	// full-bleed festive wrapper (i.e. effectively half the screen width).
	// Re-synced whenever the canvas resizes.
	function targetCount() {
		return Math.max( 0, Math.round( canvas.width / 2 ) );
	}

	function syncParticleCount() {
		var target = targetCount();
		if ( particles.length < target ) {
			for ( var i = particles.length; i < target; i++ ) {
				particles.push( new EdgeParticle() );
			}
		} else if ( particles.length > target ) {
			particles.length = target;
		}
	}

	// Hard ceiling in CSS px, well beyond any real layout — the wrapper's
	// height is normal-flow content (three stacked sections) so it should
	// never approach this, but it stops a runaway ResizeObserver feedback
	// loop dead regardless of what triggers it.
	var MAX_DIMENSION = 8000;

	function clampDimension( value ) {
		if ( ! isFinite( value ) || value <= 0 ) return 0;
		return Math.min( Math.round( value ), MAX_DIMENSION );
	}

	function resizeToParent() {
		var parent = canvas.parentElement;
		if ( ! parent ) return;

		var nextWidth = clampDimension( parent.clientWidth );
		var nextHeight = clampDimension( parent.clientHeight );

		// Setting canvas.width/height — even to the same value — resets the
		// drawing buffer and can retrigger layout/resize notifications in
		// some browsers. Skipping no-op resizes breaks any such loop before
		// it can compound.
		if ( nextWidth === canvas.width && nextHeight === canvas.height ) return;

		canvas.width = nextWidth;
		canvas.height = nextHeight;

		particles.forEach( function ( p ) { p.initPosition(); } );
		syncParticleCount();

		if ( reduceMotion ) drawFrame();
	}

	function drawFrame() {
		frameCount++;

		ctx.fillStyle = '#000';
		ctx.fillRect( 0, 0, canvas.width, canvas.height );

		for ( var i = 0; i < particles.length; i++ ) {
			particles[ i ].update();
			particles[ i ].draw();
		}
	}

	function animate() {
		if ( ! isVisible ) return;
		drawFrame();
		rafId = requestAnimationFrame( animate );
	}

	function start() {
		if ( rafId !== null ) return;
		animate();
	}

	function stop() {
		if ( rafId !== null ) {
			cancelAnimationFrame( rafId );
			rafId = null;
		}
	}

	function init() {
		resizeToParent();

		var parent = canvas.parentElement;
		if ( parent && window.ResizeObserver ) {
			// Deferring to a rAF coalesces rapid-fire notifications and
			// avoids doing layout work synchronously inside the observer
			// callback — Chrome's own guidance for sidestepping
			// ResizeObserver loop warnings.
			var resizePending = false;
			var resizeObserver = new ResizeObserver( function () {
				if ( resizePending ) return;
				resizePending = true;
				window.requestAnimationFrame( function () {
					resizePending = false;
					resizeToParent();
				} );
			} );
			resizeObserver.observe( parent );
		} else {
			window.addEventListener( 'resize', resizeToParent );
		}

		if ( reduceMotion ) {
			// One static frame instead of a perpetual animation loop.
			drawFrame();
			return;
		}

		// Only run the rAF loop while the festive section is actually
		// scrolled into view — an always-on canvas redraw is exactly the
		// kind of thing that tanks battery/perf on mobile Safari otherwise
		// (see the showcase-carousel Safari fix for the same class of bug).
		if ( parent && window.IntersectionObserver ) {
			var io = new IntersectionObserver( function ( entries ) {
				isVisible = entries[ 0 ].isIntersecting;
				if ( isVisible ) {
					start();
				} else {
					stop();
				}
			}, { threshold: 0 } );
			io.observe( parent );
		} else {
			isVisible = true;
			start();
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
