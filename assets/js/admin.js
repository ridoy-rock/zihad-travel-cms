/**
 * Zihad Travel CMS — admin UI framework.
 *
 * Native JavaScript, no dependencies. Powers the tabbed editor
 * (WAI-ARIA tabs pattern), media picker, gallery, and repeater
 * components. The media modal uses wp.media when available.
 *
 * @package ZihadTravelCMS
 */

( function () {
	'use strict';

	/* ------------------------------------------------------------------
	 * Tabs (WAI-ARIA tabs pattern, keyboard navigable).
	 * ---------------------------------------------------------------- */

	function initTabs( editor ) {
		var tabs = Array.prototype.slice.call( editor.querySelectorAll( '[role="tab"]' ) );
		var storageKey = 'ztc-active-tab-' + ( editor.getAttribute( 'data-ztc-editor' ) || 'editor' );

		function activate( tab, focus ) {
			tabs.forEach( function ( other ) {
				var selected = other === tab;
				other.setAttribute( 'aria-selected', selected ? 'true' : 'false' );
				other.setAttribute( 'tabindex', selected ? '0' : '-1' );

				var panel = document.getElementById( other.getAttribute( 'aria-controls' ) );
				if ( panel ) {
					panel.hidden = ! selected;
				}
			} );

			if ( focus ) {
				tab.focus();
			}

			try {
				window.localStorage.setItem( storageKey, tab.id );
			} catch ( e ) {
				// Storage unavailable (private mode) — non-fatal.
			}
		}

		tabs.forEach( function ( tab, index ) {
			tab.addEventListener( 'click', function () {
				activate( tab, false );
			} );

			tab.addEventListener( 'keydown', function ( event ) {
				var target = null;

				switch ( event.key ) {
					case 'ArrowRight':
					case 'ArrowDown':
						target = tabs[ ( index + 1 ) % tabs.length ];
						break;
					case 'ArrowLeft':
					case 'ArrowUp':
						target = tabs[ ( index - 1 + tabs.length ) % tabs.length ];
						break;
					case 'Home':
						target = tabs[ 0 ];
						break;
					case 'End':
						target = tabs[ tabs.length - 1 ];
						break;
					default:
						return;
				}

				event.preventDefault();
				activate( target, true );
			} );
		} );

		// Restore the last active tab for this editor.
		var savedId = null;
		try {
			savedId = window.localStorage.getItem( storageKey );
		} catch ( e ) {
			savedId = null;
		}

		var saved = savedId ? document.getElementById( savedId ) : null;
		if ( saved && tabs.indexOf( saved ) > -1 ) {
			activate( saved, false );
		}
	}

	/* ------------------------------------------------------------------
	 * Media picker (single attachment).
	 * ---------------------------------------------------------------- */

	function openMediaModal( options, onSelect ) {
		if ( ! window.wp || ! window.wp.media ) {
			return;
		}

		var frame = window.wp.media( {
			title: options.title || '',
			button: { text: options.button || '' },
			multiple: !! options.multiple,
			library: 'image' === options.type ? { type: 'image' } : {}
		} );

		frame.on( 'select', function () {
			onSelect( frame.state().get( 'selection' ).toJSON() );
		} );

		frame.open();
	}

	function mediaPreviewHtml( attachment ) {
		if ( 'image' === attachment.type ) {
			var thumb =
				attachment.sizes && attachment.sizes.medium
					? attachment.sizes.medium.url
					: attachment.sizes && attachment.sizes.thumbnail
						? attachment.sizes.thumbnail.url
						: attachment.url;

			var img = document.createElement( 'img' );
			img.className = 'ztc-media__image';
			img.src = thumb;
			img.alt = '';

			return img.outerHTML;
		}

		var name = document.createElement( 'span' );
		name.className = 'ztc-media__file';
		name.textContent = attachment.title || attachment.filename || '';

		return name.outerHTML;
	}

	function handleMediaSelect( wrapper ) {
		openMediaModal(
			{
				title: wrapper.getAttribute( 'data-modal-title' ),
				button: wrapper.getAttribute( 'data-modal-button' ),
				type: wrapper.getAttribute( 'data-media-type' ),
				multiple: false
			},
			function ( selection ) {
				var attachment = selection[ 0 ];
				if ( ! attachment ) {
					return;
				}

				wrapper.querySelector( '[data-ztc-media-input]' ).value = attachment.id;
				wrapper.querySelector( '[data-ztc-media-preview]' ).innerHTML = mediaPreviewHtml( attachment );
				wrapper.querySelector( '[data-ztc-media-remove]' ).hidden = false;
			}
		);
	}

	function handleMediaRemove( wrapper, button ) {
		wrapper.querySelector( '[data-ztc-media-input]' ).value = '0';
		wrapper.querySelector( '[data-ztc-media-preview]' ).innerHTML = '';
		button.hidden = true;
	}

	/* ------------------------------------------------------------------
	 * Gallery (multiple attachments, ordered).
	 * ---------------------------------------------------------------- */

	function syncGallery( gallery ) {
		var ids = Array.prototype.map.call(
			gallery.querySelectorAll( '[data-ztc-gallery-item]' ),
			function ( item ) {
				return item.getAttribute( 'data-id' );
			}
		);

		gallery.querySelector( '[data-ztc-gallery-input]' ).value = ids.join( ',' );
	}

	function galleryItemNode( gallery, attachment ) {
		var reference = gallery.querySelector( '[data-ztc-gallery-item]' );
		var item = document.createElement( 'li' );

		item.className = 'ztc-gallery__item';
		item.setAttribute( 'data-ztc-gallery-item', '' );
		item.setAttribute( 'data-id', attachment.id );

		if ( reference ) {
			// Clone the server-rendered controls so labels stay translated.
			item.innerHTML = reference.innerHTML;
			var oldImg = item.querySelector( 'img' );
			if ( oldImg ) {
				oldImg.remove();
			}
		} else {
			item.innerHTML =
				'<span class="ztc-gallery__actions">' +
				'<button type="button" class="button-link" data-ztc-move="up"><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span></button>' +
				'<button type="button" class="button-link" data-ztc-move="down"><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></button>' +
				'<button type="button" class="button-link button-link-delete" data-ztc-gallery-remove><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>' +
				'</span>';
		}

		var img = document.createElement( 'img' );
		img.className = 'ztc-gallery__thumb';
		img.alt = '';
		img.src =
			attachment.sizes && attachment.sizes.thumbnail
				? attachment.sizes.thumbnail.url
				: attachment.url;
		item.insertBefore( img, item.firstChild );

		return item;
	}

	function handleGalleryAdd( gallery ) {
		openMediaModal(
			{
				title: gallery.getAttribute( 'data-modal-title' ),
				button: gallery.getAttribute( 'data-modal-button' ),
				type: 'image',
				multiple: true
			},
			function ( selection ) {
				var list = gallery.querySelector( '[data-ztc-gallery-items]' );

				selection.forEach( function ( attachment ) {
					list.appendChild( galleryItemNode( gallery, attachment ) );
				} );

				syncGallery( gallery );
			}
		);
	}

	/* ------------------------------------------------------------------
	 * Repeater (structured rows: add / remove / reorder).
	 * ---------------------------------------------------------------- */

	function renumberRepeater( repeater ) {
		var rows = repeater.querySelectorAll( ':scope > [data-ztc-repeater-rows] > [data-ztc-repeater-row]' );

		Array.prototype.forEach.call( rows, function ( row, index ) {
			var number = row.querySelector( '[data-ztc-repeater-number]' );
			if ( number ) {
				number.textContent = String( index + 1 );
			}

			row.querySelectorAll( 'input, textarea, select' ).forEach( function ( input ) {
				input.name = input.name.replace( /\]\[(?:\d+|__i__)\]\[/, '][' + index + '][' );
				if ( input.id ) {
					input.id = input.id.replace( /-(?:\d+|__i__)-/, '-' + index + '-' );
				}
			} );

			row.querySelectorAll( 'label[for]' ).forEach( function ( label ) {
				label.htmlFor = label.htmlFor.replace( /-(?:\d+|__i__)-/, '-' + index + '-' );
			} );
		} );
	}

	function handleRepeaterAdd( repeater ) {
		var template = repeater.querySelector( ':scope > [data-ztc-repeater-template]' );
		var rowsWrap = repeater.querySelector( ':scope > [data-ztc-repeater-rows]' );

		if ( ! template || ! rowsWrap ) {
			return;
		}

		rowsWrap.appendChild( template.content.cloneNode( true ) );
		renumberRepeater( repeater );

		var added = rowsWrap.lastElementChild;
		var firstInput = added ? added.querySelector( 'input, textarea, select' ) : null;
		if ( firstInput ) {
			firstInput.focus();
		}
	}

	function moveElement( element, direction ) {
		if ( ! element ) {
			return;
		}

		if ( 'up' === direction && element.previousElementSibling ) {
			element.parentNode.insertBefore( element, element.previousElementSibling );
		} else if ( 'down' === direction && element.nextElementSibling ) {
			element.parentNode.insertBefore( element.nextElementSibling, element );
		}
	}

	/* ------------------------------------------------------------------
	 * Wire-up: init tabs, delegate component events.
	 * ---------------------------------------------------------------- */

	function ready( fn ) {
		if ( 'loading' === document.readyState ) {
			document.addEventListener( 'DOMContentLoaded', fn );
		} else {
			fn();
		}
	}

	ready( function () {
		document.querySelectorAll( '[data-ztc-editor]' ).forEach( initTabs );

		document.addEventListener( 'click', function ( event ) {
			var button = event.target.closest ? event.target.closest( 'button' ) : null;
			if ( ! button ) {
				return;
			}

			var media = button.closest( '[data-ztc-media]' );
			if ( media && button.hasAttribute( 'data-ztc-media-select' ) ) {
				handleMediaSelect( media );
				return;
			}
			if ( media && button.hasAttribute( 'data-ztc-media-remove' ) ) {
				handleMediaRemove( media, button );
				return;
			}

			var gallery = button.closest( '[data-ztc-gallery]' );
			if ( gallery && button.hasAttribute( 'data-ztc-gallery-add' ) ) {
				handleGalleryAdd( gallery );
				return;
			}
			if ( gallery && button.hasAttribute( 'data-ztc-gallery-remove' ) ) {
				button.closest( '[data-ztc-gallery-item]' ).remove();
				syncGallery( gallery );
				return;
			}
			if ( gallery && button.hasAttribute( 'data-ztc-move' ) ) {
				moveElement( button.closest( '[data-ztc-gallery-item]' ), button.getAttribute( 'data-ztc-move' ) );
				syncGallery( gallery );
				return;
			}

			var repeater = button.closest( '[data-ztc-repeater]' );
			if ( repeater && button.hasAttribute( 'data-ztc-repeater-add' ) ) {
				handleRepeaterAdd( repeater );
				return;
			}
			if ( repeater && button.hasAttribute( 'data-ztc-repeater-remove' ) ) {
				button.closest( '[data-ztc-repeater-row]' ).remove();
				renumberRepeater( repeater );
				return;
			}
			if ( repeater && button.hasAttribute( 'data-ztc-move' ) ) {
				moveElement( button.closest( '[data-ztc-repeater-row]' ), button.getAttribute( 'data-ztc-move' ) );
				renumberRepeater( repeater );
			}
		} );
	} );
} )();

/**
 * Import/Export screen: media-library file picker, batched import loop
 * with progress bar + error log, and export downloads via REST.
 */
( function () {
	'use strict';

	var config = window.ztcAdmin || {};

	function restFetch( path, options ) {
		options = options || {};
		options.headers = Object.assign(
			{ 'Content-Type': 'application/json', 'X-WP-Nonce': config.restNonce || '' },
			options.headers || {}
		);

		return fetch( config.restUrl + path, options ).then( function ( response ) {
			return response.json().then( function ( data ) {
				if ( ! response.ok ) {
					throw new Error( data.message || 'Request failed' );
				}

				return data;
			} );
		} );
	}

	function initImport( root ) {
		var mediaInput = root.querySelector( '[data-ztc-import-media-id]' );
		var filename = root.querySelector( '[data-ztc-import-filename]' );
		var startButton = root.querySelector( '[data-ztc-import-start]' );
		var progressWrap = root.querySelector( '[data-ztc-import-progress]' );
		var progressBar = root.querySelector( '[data-ztc-progress-bar]' );
		var progressTrack = progressWrap ? progressWrap.querySelector( '[role="progressbar"]' ) : null;
		var statusText = root.querySelector( '[data-ztc-progress-status]' );
		var errorsWrap = root.querySelector( '[data-ztc-import-errors]' );
		var errorList = root.querySelector( '[data-ztc-import-error-list]' );

		root.querySelector( '[data-ztc-import-file]' ).addEventListener( 'click', function () {
			if ( ! window.wp || ! window.wp.media ) {
				return;
			}

			var frame = window.wp.media( { title: 'Select import file', multiple: false } );

			frame.on( 'select', function () {
				var file = frame.state().get( 'selection' ).first().toJSON();
				mediaInput.value = file.id;
				filename.textContent = file.filename || file.title || '';
				startButton.disabled = false;
			} );

			frame.open();
		} );

		function renderProgress( job ) {
			progressWrap.hidden = false;
			progressBar.style.width = job.progress + '%';

			if ( progressTrack ) {
				progressTrack.setAttribute( 'aria-valuenow', String( Math.round( job.progress ) ) );
			}

			statusText.textContent =
				job.status + ' — ' + job.processed + '/' + job.total +
				' (created ' + job.created + ', updated ' + job.updated +
				', skipped ' + job.skipped + ', failed ' + job.failed + ')';

			var errors = Object.keys( job.errors || {} );
			errorsWrap.hidden = 0 === errors.length;
			errorList.innerHTML = '';
			errors.forEach( function ( key ) {
				var item = document.createElement( 'li' );
				item.textContent = key + ': ' + job.errors[ key ];
				errorList.appendChild( item );
			} );
		}

		function processLoop( jobId ) {
			restFetch( '/import/process', {
				method: 'POST',
				body: JSON.stringify( { job_id: jobId, batch: 20 } )
			} )
				.then( function ( job ) {
					renderProgress( job );

					if ( ! job.finished ) {
						processLoop( jobId );
					} else {
						startButton.disabled = false;
					}
				} )
				.catch( function ( error ) {
					statusText.textContent = error.message;
					startButton.disabled = false;
				} );
		}

		startButton.addEventListener( 'click', function () {
			startButton.disabled = true;

			restFetch( '/import/start', {
				method: 'POST',
				body: JSON.stringify( {
					type: root.querySelector( '[data-ztc-import-type]' ).value,
					media_id: parseInt( mediaInput.value, 10 ),
					mode: root.querySelector( '[data-ztc-import-mode]' ).value,
					rollback_on_failure: root.querySelector( '[data-ztc-import-rollback]' ).checked
				} )
			} )
				.then( function ( job ) {
					renderProgress( job );
					processLoop( job.id );
				} )
				.catch( function ( error ) {
					statusText.textContent = error.message;
					progressWrap.hidden = false;
					startButton.disabled = false;
				} );
		} );
	}

	function initExport( root ) {
		root.querySelector( '[data-ztc-export-download]' ).addEventListener( 'click', function () {
			var type = root.querySelector( '[data-ztc-export-type]' ).value;
			var format = root.querySelector( '[data-ztc-export-format]' ).value;

			restFetch( '/export?type=' + encodeURIComponent( type ) + '&format=' + encodeURIComponent( format ) )
				.then( function ( result ) {
					var blob = new Blob( [ result.body ], { type: result.mime } );
					var link = document.createElement( 'a' );
					link.href = URL.createObjectURL( blob );
					link.download = result.filename;
					document.body.appendChild( link );
					link.click();
					link.remove();
					URL.revokeObjectURL( link.href );
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

	function initDemo( root ) {
		var progressWrap = root.querySelector( '[data-ztc-demo-progress]' );
		var progressBar = progressWrap.querySelector( '[data-ztc-progress-bar]' );
		var statusText = progressWrap.querySelector( '[data-ztc-progress-status]' );
		var generateButton = root.querySelector( '[data-ztc-demo-generate]' );
		var installButton = root.querySelector( '[data-ztc-demo-install]' );

		function status( text, percent ) {
			progressWrap.hidden = false;
			statusText.textContent = text;

			if ( 'number' === typeof percent ) {
				progressBar.style.width = percent + '%';
			}
		}

		function setBusy( busy ) {
			generateButton.disabled = busy;
			installButton.disabled = busy;
		}

		generateButton.addEventListener( 'click', function () {
			setBusy( true );
			status( 'Generating…', 0 );

			restFetch( '/demo/generate', {
				method: 'POST',
				body: JSON.stringify( { locale: root.querySelector( '[data-ztc-demo-locale]' ).value } )
			} )
				.then( function ( result ) {
					status(
						'Generated ' + result.counts.country + ' countries, ' +
						result.counts.visa + ' visas, ' + result.counts.tour + ' tours.',
						100
					);
				} )
				.catch( function ( error ) {
					status( error.message );
				} )
				.finally( function () {
					setBusy( false );
				} );
		} );

		installButton.addEventListener( 'click', function () {
			var types = [ 'country', 'visa', 'tour' ];
			setBusy( true );

			function installType( index ) {
				if ( index >= types.length ) {
					status( 'Demo data installed.', 100 );
					setBusy( false );
					return;
				}

				restFetch( '/demo/start', {
					method: 'POST',
					body: JSON.stringify( { type: types[ index ] } )
				} )
					.then( function ( job ) {
						// Drive the importer's own process loop.
						function step( currentJob ) {
							status(
								'Importing ' + types[ index ] + ' — ' + currentJob.processed + '/' + currentJob.total,
								currentJob.progress
							);

							if ( currentJob.finished ) {
								installType( index + 1 );
								return;
							}

							restFetch( '/import/process', {
								method: 'POST',
								body: JSON.stringify( { job_id: currentJob.id, batch: 10 } )
							} ).then( step ).catch( function ( error ) {
								status( error.message );
								setBusy( false );
							} );
						}

						step( job );
					} )
					.catch( function ( error ) {
						status( error.message );
						setBusy( false );
					} );
			}

			installType( 0 );
		} );
	}

	ready( function () {
		document.querySelectorAll( '[data-ztc-import]' ).forEach( initImport );
		document.querySelectorAll( '[data-ztc-export]' ).forEach( initExport );
		document.querySelectorAll( '[data-ztc-demo]' ).forEach( initDemo );
	} );
} )();
