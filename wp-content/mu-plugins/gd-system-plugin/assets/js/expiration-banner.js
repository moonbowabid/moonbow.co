( function() {
	var renewLink = document.getElementById( 'wpaas-expiration-renew' );

	if ( ! renewLink ) {
		return;
	}

	renewLink.addEventListener( 'click', function() {
		var dataLayer = window._expDataLayer || [];
		dataLayer.push( {
			schema: 'add_event',
			version: 'v1',
			data: {
				eid: 'expiration-banner.admin.renew-link.click',
				type: 'click'
			}
		} );
	} );
} )();
