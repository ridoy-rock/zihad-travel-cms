/**
 * Zihad Travel CMS — frontend engine.
 *
 * Native JavaScript, no dependencies. Progressive enhancement for the
 * search/filter form: without JS the form submits as a normal keyword
 * search; with JS results update in place through the public REST
 * endpoint (GET ztc/v1/search — cacheable, nonce-free).
 *
 * @package ZihadTravelCMS
 */

( function () {
	'use strict';

	var config = window.ztcFrontend || {};

	function debounce( fn, wait ) {
		var timer = null;

		return function () {
			var args = arguments;
			window.clearTimeout( timer );
			timer = window.setTimeout( function () {
				fn.apply( null, args );
			}, wait );
		};
	}

	function setupSearch( form ) {
		if ( ! config.restUrl || ! window.fetch ) {
			return; // No-JS fallback: the form submits normally.
		}

		var results = document.querySelector( '[data-ztc-results]' );
		var count = document.querySelector( '[data-ztc-count]' );
		var empty = document.querySelector( '[data-ztc-empty]' );
		var pagination = document.querySelector( '.ztc-archive__pagination' );

		if ( ! results ) {
			return;
		}

		var colClass = results.getAttribute( 'data-ztc-col-class' ) || 'col-12 col-sm-6 col-lg-4';

		function params() {
			var query = new URLSearchParams( new FormData( form ) );
			query.set( 'type', form.getAttribute( 'data-ztc-type' ) || 'tour' );

			// Drop empty params for cleaner, more cacheable URLs.
			Array.prototype.slice.call( query.keys() ).forEach( function ( key ) {
				if ( '' === query.get( key ) || '0' === query.get( key ) && ( 'min_price' === key || 'max_price' === key ) ) {
					query.delete( key );
				}
			} );

			return query;
		}

		function render( data ) {
			results.innerHTML = '';

			data.items.forEach( function ( item ) {
				var column = document.createElement( 'div' );
				column.className = colClass;
				column.innerHTML = item.html;
				results.appendChild( column );
			} );

			if ( empty ) {
				empty.hidden = data.items.length > 0;
			}

			if ( count ) {
				count.textContent = count.textContent.replace( /^[\d.,\s]+/, data.total + ' ' );
			}

			if ( pagination ) {
				// Server pagination no longer matches filtered results.
				pagination.hidden = true;
			}
		}

		function fetchResults() {
			results.setAttribute( 'aria-busy', 'true' );
			results.classList.add( 'ztc-grid--loading' );

			fetch( config.restUrl + '/search?' + params().toString() )
				.then( function ( response ) {
					return response.json();
				} )
				.then( render )
				.catch( function () {
					// Network failure: fall back to a normal submit next time.
				} )
				.finally( function () {
					results.setAttribute( 'aria-busy', 'false' );
					results.classList.remove( 'ztc-grid--loading' );
				} );
		}

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			fetchResults();
		} );

		var debounced = debounce( fetchResults, 350 );

		form.querySelectorAll( 'input[type="search"], input[type="number"]' ).forEach( function ( input ) {
			input.addEventListener( 'input', debounced );
		} );

		form.querySelectorAll( 'select' ).forEach( function ( select ) {
			select.addEventListener( 'change', fetchResults );
		} );
	}

	function setupInquiry( form ) {
		if ( ! config.restUrl || ! window.fetch ) {
			return; // No-JS fallback: the form posts to admin-post.php.
		}

		var wrapper = form.closest( '.ztc-inquiry' ) || form.parentElement;
		var message = wrapper.querySelector( '[data-ztc-inquiry-message]' );
		var button = form.querySelector( 'button[type="submit"]' );

		function show( text, isError ) {
			if ( ! message ) {
				return;
			}

			message.hidden = false;
			message.innerHTML = '';

			var paragraph = document.createElement( 'p' );
			paragraph.className = isError ? 'ztc-inquiry__error' : 'ztc-inquiry__success';
			paragraph.textContent = text;
			message.appendChild( paragraph );
		}

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var payload = {};
			new FormData( form ).forEach( function ( value, key ) {
				payload[ key ] = value;
			} );

			button.disabled = true;
			form.setAttribute( 'aria-busy', 'true' );

			fetch( config.restUrl + '/inquiry', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify( payload )
			} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( result ) {
					if ( result.success ) {
						show( form.getAttribute( 'data-ztc-success' ) || result.message, false );
						form.hidden = true;
						return;
					}

					var errors = result.errors || {};
					var first = Object.keys( errors )[ 0 ];
					show( first ? errors[ first ] : result.message, true );
				} )
				.catch( function () {
					// Network/parse failure: fall back to the no-JS path
					// (the native submit() skips this submit listener).
					form.submit();
				} )
				.finally( function () {
					button.disabled = false;
					form.setAttribute( 'aria-busy', 'false' );
				} );
		} );
	}

	function ready( fn ) {
		if ( 'loading' === document.readyState ) {
			document.addEventListener( 'DOMContentLoaded', fn );
		} else {
			fn();
		}
	}

	ready( function () {
		document.querySelectorAll( '[data-ztc-search]' ).forEach( setupSearch );
		document.querySelectorAll( '[data-ztc-inquiry]' ).forEach( setupInquiry );
	} );
} )();
