jQuery( function()
{
	$("ul#users li:not(.edit)").click( function()
	{
		$(this).children( "form" ).css( "display", "block" );
	} );

	$("ul#users li:not(.edit) input[type=reset]").click( function( e )
	{
		$(this).parents( "form:first" ).css( "display", "none" );
		e.stopPropagation();
	} );

	$("ul#users li.edit a.add, ul#bills li.edit a.add").click( function()
	{
		$(this).next( "form" ).css( "display", "block" );
		$(this).css( "display", "none" );
		return false;
	} );

	$("ul#users li.edit form input[type=reset], ul#bills li.edit form input[type=reset]").click( function()
	{
		var form = $(this).parents( "form:first" );
		form.prev( "a" ).css( "display", "block" );
		form.css( "display", "none" );
	} );

	$("form#new-bill p.edit input.add").click( function()
	{
		var categoryExists = false;
		var category = $("input#new-category").val().trim();
		$("input#new-category").val( "" );

		$("form#new-bill p:not(.edit) span.category").each( function( i, el )
		{
			var categoryName = $(this).text().trim();

			if( categoryName.toLowerCase() == category.toLowerCase() )
			{
				categoryExists = true;
				return false;
			}
		} );

		if( categoryExists == false && category != "" )
		{
			var p = $("<p/>");
			p.append( $("<span />").addClass( "category" ).text( category ) );
			var input = $("<input/>");
			input.attr( "name", "category["+ category +"]" );
			input.attr( "type", "number" );
			input.attr( "pattern", "[0-9]+([\.,][0-9]+)?" );
			input.attr( "required", "true" );
			input.attr( "value", "0" );

			p.append( $("<span />").addClass( "amount" ).append( input ) );
			p.insertBefore( $("form#new-bill p.edit") );
		}
	} );

	$("input#substract-amount").click( function()
	{
		var currentAmount = $("input#new-bill-amount").val().trim().replace( /,/, "." );
		var amount = $("input#amount-to-substract").val().trim().replace( /,/, "." );

		if( currentAmount != "" )
		{
			if( amount != "" && amount > 0 )
			{
				currentAmount -= amount;

				$("input#new-bill-amount").val( currentAmount.toFixed( 2 ) );
				$("input#amount-to-substract").val( "" );
			}
		}
	} );
} );
