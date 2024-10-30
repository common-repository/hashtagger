jQuery(document).ready(function($) {
  
  $( '.pp-hashtagger-admin-notice' ).on( 'click', '.notice-dismiss', function ( event ) {
    event.preventDefault();
		data = {
			action: 'pp_hashtagger_dismiss_admin_notice',
			pp_hashtagger_dismiss_admin_notice: $( this ).parent().attr( 'id' ),
      securekey : pp_hashtagger_security.securekey
		};
		$.post( ajaxurl, data );
		return false;
	});
 
});