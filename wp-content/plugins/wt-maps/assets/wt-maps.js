/**
 * wt-maps: hover labels and links on maps (plan 3.2, step 2a; docs/maps.md).
 *
 * Reads the figure's `data-wt-map` JSON (record ID → name and URL, built by
 * PHP) and the shapes' own `data-place` (places and neighbouring countries,
 * tagged by the build) and `data-area` (contested areas). The SVG
 * stays `role="img"`; its shapes take no keyboard focus. Keyboard and screen
 * reader users have the same places as links in the list under the map, and
 * hovering or focusing one of those links highlights its shape here.
 *
 * Mouse: hover shows the label and outline; click opens the place's page.
 * Touch: the first tap shows the label with a link; a second tap opens it.
 */
( function () {
	'use strict';

	const SVG_NS = 'http://www.w3.org/2000/svg';
	let count = 0; // Figures on the page, for unique filter IDs.

	function init( fig ) {
		let data;
		try {
			data = JSON.parse( fig.dataset.wtMap );
		} catch {
			return;
		}
		const svg = fig.querySelector( 'svg.wt-map__svg' );
		if ( ! svg ) {
			return;
		}
		const places = data.places || {};
		const groups = data.groups || {}; // A continent map: places lead to their sub-region.

		const hot = document.createElementNS( SVG_NS, 'g' );
		hot.setAttribute( 'class', 'wt-map__hot' );
		hot.setAttribute( 'aria-hidden', 'true' );
		svg.appendChild( hot );

		// A group is outlined round its edge only, not along the borders inside
		// it: its shapes are grown and the shapes themselves cut out, by a filter.
		const filterId = 'wt-hot-' + ( ++count );
		const defs = document.createElementNS( SVG_NS, 'defs' );
		defs.innerHTML = '<filter id="' + filterId + '" x="-10%" y="-10%" width="120%" height="120%">'
			+ '<feMorphology in="SourceAlpha" operator="dilate" radius="2.6" result="c"/>'
			+ '<feMorphology in="SourceAlpha" operator="dilate" radius="1.4" result="l"/>'
			+ '<feComposite in="c" in2="SourceAlpha" operator="out" result="co"/>'
			+ '<feComposite in="l" in2="SourceAlpha" operator="out" result="lo"/>'
			+ '<feFlood class="wt-map__hot-casing-flood"/><feComposite in2="co" operator="in" result="cf"/>'
			+ '<feFlood class="wt-map__hot-line-flood"/><feComposite in2="lo" operator="in" result="lf"/>'
			+ '<feMerge><feMergeNode in="cf"/><feMergeNode in="lf"/></feMerge></filter>';
		svg.insertBefore( defs, svg.firstChild );

		// Every shape of a group: its territories and their dots, not the land around.
		function groupEls( g ) {
			return Array.prototype.filter.call( svg.querySelectorAll( '.hls [data-place], .dots [data-place], .markers [data-place]' ), function ( el ) {
				const p = places[ el.dataset.place ];
				return p && p.g === g;
			} );
		}

		const tip = document.createElement( 'div' );
		tip.className = 'wt-map__tip';
		tip.hidden = true;
		// A visual echo of the list under the map, which is what assistive
		// technology reads; its tap link is for pointers only.
		tip.setAttribute( 'aria-hidden', 'true' );
		fig.appendChild( tip );

		let current = null; // The target shown now.
		let pinned = false; // Shown by a tap, waiting for a second tap.
		let lastPointer = 'mouse';

		// What a shape on the map stands for, or null.
		function targetOf( el ) {
			if ( ! el || ! el.closest ) {
				return null;
			}
			const area = el.closest( '[data-area]' );
			if ( area && ! area.classList.contains( 'edge' ) && fig.contains( area ) ) {
				return { key: 'area:' + area.dataset.area, n: area.dataset.area, area: true, els: [ area ] };
			}
			const place = el.closest( '[data-place]' );
			const g = place && places[ place.dataset.place ] ? places[ place.dataset.place ].g : null;
			if ( g && groups[ g ] ) {
				return Object.assign( { key: 'g:' + g, group: true, els: groupEls( g ) }, groups[ g ] );
			}
			if ( place && places[ place.dataset.place ] ) {
				const id = place.dataset.place;
				return Object.assign( { key: id, els: svg.querySelectorAll( '[data-place="' + id + '"]' ) }, places[ id ] );
			}
			return null;
		}

		function label( t, withLink ) {
			tip.textContent = '';
			const name = document.createElement( 'span' );
			name.className = 'wt-map__tip-name';
			name.textContent = t.n;
			tip.appendChild( name );
			let sub = '';
			if ( t.area ) {
				sub = 'Contested area';
			} else if ( t.v ) {
				sub = 'Part of ' + t.v;
			}
			if ( sub ) {
				const s = document.createElement( 'span' );
				s.className = 'wt-map__tip-sub';
				s.textContent = sub;
				tip.appendChild( s );
			}
			if ( withLink && t.u ) {
				const a = document.createElement( 'a' );
				a.href = t.u;
				a.className = 'wt-map__tip-link';
				a.tabIndex = -1;
				a.textContent = 'Open ' + ( t.v || t.n ) + ' →';
				tip.appendChild( a );
			}
		}

		function outline( t ) {
			hot.textContent = '';
			if ( t.group ) {
				const g = document.createElementNS( SVG_NS, 'g' );
				g.setAttribute( 'filter', 'url(#' + filterId + ')' );
				t.els.forEach( function ( el ) {
					const c = el.cloneNode( false );
					c.removeAttribute( 'id' );
					c.removeAttribute( 'class' );
					Object.keys( c.dataset ).forEach( function ( k ) {
						delete c.dataset[ k ];
					} );
					g.appendChild( c );
				} );
				hot.appendChild( g );
				return;
			}
			[ 'casing', 'line' ].forEach( function ( layer ) {
				t.els.forEach( function ( el ) {
					const c = el.cloneNode( false );
					c.removeAttribute( 'id' );
					Object.keys( c.dataset ).forEach( function ( k ) {
						delete c.dataset[ k ];
					} );
					c.setAttribute( 'class', 'wt-map__hot-' + layer + ( el.classList.contains( 'marker' ) ? ' is-ring' : '' ) );
					hot.appendChild( c );
				} );
			} );
		}

		// Place the label above a point in the figure, kept inside it.
		function place( x, y ) {
			const fw = fig.clientWidth;
			tip.style.left = '0px';
			tip.style.top = '0px';
			const w = tip.offsetWidth;
			const h = tip.offsetHeight;
			let left = Math.min( Math.max( x - w / 2, 4 ), fw - w - 4 );
			let top = y - h - 12;
			if ( top < 4 ) {
				top = y + 16; // No room above: below the pointer.
			}
			tip.style.left = left + 'px';
			tip.style.top = top + 'px';
		}

		// Above the shapes' combined box (focus, taps).
		function anchor( t ) {
			const f = fig.getBoundingClientRect();
			let l = Infinity, r = -Infinity, top = Infinity;
			t.els.forEach( function ( el ) {
				const b = el.getBoundingClientRect();
				l = Math.min( l, b.left );
				r = Math.max( r, b.right );
				top = Math.min( top, b.top );
			} );
			const s = svg.getBoundingClientRect();
			place( ( Math.max( l, s.left ) + Math.min( r, s.right ) ) / 2 - f.left, Math.max( top, s.top ) - f.top );
		}

		function show( t, at, withLink ) {
			if ( ! current || current.key !== t.key || withLink !== tip.classList.contains( 'has-link' ) ) {
				label( t, withLink );
				outline( t );
				tip.classList.toggle( 'has-link', !! withLink );
				svg.classList.toggle( 'is-link', !! t.u );
			}
			current = t;
			tip.hidden = false;
			fig.classList.add( 'is-hot' );
			if ( at ) {
				const f = fig.getBoundingClientRect();
				place( at.clientX - f.left, at.clientY - f.top );
			} else {
				anchor( t );
			}
		}

		function hide() {
			current = null;
			pinned = false;
			tip.hidden = true;
			hot.textContent = '';
			svg.classList.remove( 'is-link' );
			fig.classList.remove( 'is-hot' );
		}

		svg.addEventListener( 'pointerdown', function ( e ) {
			lastPointer = e.pointerType;
		} );

		svg.addEventListener( 'pointermove', function ( e ) {
			if ( 'mouse' !== e.pointerType || pinned ) {
				return;
			}
			const t = targetOf( e.target );
			if ( t ) {
				show( t, e, false );
			} else if ( current ) {
				hide();
			}
		} );

		svg.addEventListener( 'pointerleave', function ( e ) {
			if ( 'mouse' === e.pointerType && ! pinned ) {
				hide();
			}
		} );

		svg.addEventListener( 'click', function ( e ) {
			const t = targetOf( e.target );
			if ( 'mouse' === lastPointer ) {
				if ( t && t.u ) {
					if ( e.metaKey || e.ctrlKey ) {
						window.open( t.u, '_blank', 'noopener' );
					} else {
						window.location.href = t.u;
					}
				}
				return;
			}
			// Touch and pen: first tap labels, second tap on the same place opens it.
			if ( ! t ) {
				hide();
				return;
			}
			if ( pinned && current && current.key === t.key && t.u ) {
				window.location.href = t.u;
				return;
			}
			show( t, e, true ); // At the finger, not over the whole set.
			pinned = true;
		} );

		document.addEventListener( 'click', function ( e ) {
			if ( pinned && ! fig.contains( e.target ) ) {
				hide();
			}
		} );

		// The place lists next to the map: hovering or focusing a link
		// highlights its place. Matched by URL, which PHP built from record IDs.
		const byUrl = {};
		Object.keys( places ).forEach( function ( id ) {
			const u = places[ id ].u || ( groups[ places[ id ].g ] || {} ).u;
			if ( u ) {
				( byUrl[ u ] = byUrl[ u ] || [] ).push( id );
			}
		} );
		const scope = fig.closest( '.metadata' ) || fig.parentElement;
		scope.querySelectorAll( 'a[href]' ).forEach( function ( a ) {
			const ids = byUrl[ a.href ];
			if ( ! ids ) {
				return;
			}
			const els = svg.querySelectorAll( ids.map( function ( id ) {
				return '[data-place="' + id + '"]';
			} ).join( ',' ) );
			if ( ! els.length ) {
				return;
			}
			const g = places[ ids[ 0 ] ].g;
			const t = g
				? Object.assign( { key: 'g:' + g, group: true, els: groupEls( g ) }, groups[ g ] )
				: Object.assign( { key: 'list:' + ids.join( ',' ), els: els }, places[ ids[ 0 ] ] );
			const on = function () {
				if ( ! pinned ) {
					show( t, null, false );
				}
			};
			const off = function () {
				if ( ! pinned ) {
					hide();
				}
			};
			a.addEventListener( 'mouseenter', on );
			a.addEventListener( 'focus', on );
			a.addEventListener( 'mouseleave', off );
			a.addEventListener( 'blur', off );
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && current ) {
				hide();
			}
		} );
	}

	function start() {
		document.querySelectorAll( '.wt-map[data-wt-map]' ).forEach( init );
	}
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
