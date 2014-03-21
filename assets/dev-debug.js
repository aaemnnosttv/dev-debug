/**
 * Dev Debug
 */

+function( $ ){
$(document).ready( function()
{
	$('.ddprint').on( 'click', '.toggle-meta', function( e )
	{
		e.preventDefault();
		//console.log( this );
		$self = $(this);
		$meta = $self.closest('.ddprint').find('.meta');
		//console.log( $meta );
		$meta.slideToggle();
	});

	$('.ddprint').on( 'click', '.dump-tabs a', function( e )
	{
		e.preventDefault();
		//console.log( this );
		$self = $(this); // the link
		$dd = $self.closest('.ddprint');
		type = $self.data('dumpType');
		//console.log( type );

		$tabs = $dd.find('.dump-tabs').children();
		//console.log($tabs);

		$activetab = $tabs.filter('.active');
		//console.log({activetab: $activetab});

		$panels = $dd.find('.panels');
		$activepanel = $panels.find('.active');
		showntype = $activepanel.attr('dump-type');
		//console.log( showntype );

		if ( type == $activepanel.attr('dump-type') )
			return;
		
		//console.log('changing to '+type);


		$tabs.removeClass('active');
		$activepanel.removeClass('active');
		$tabs.filter( 'li.'+type ).addClass('active');
		$panels.find('[dump-type='+type+']').addClass('active');
	});

}); // ready
}(window.jQuery);