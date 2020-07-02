$( function () {

	const include_generic_names = $( '#include_generic_names' );
	const include_scientific_names = $( '#include_scientific_names' );
	const include_authors = $( '#include_authors' );
	const get_result_button = $( '#get_result_button' );
	const arrow = $( '.arrow' );
	const checkbox = $( '.checkbox' );
	const root = $( '#root' );
	const payload_field = $( '#payload_field' );

	get_result_button.click( function () {

		const include_generic_names_value = include_generic_names.is( ':checked' );
		const include_scientific_names_value = include_scientific_names.is( ':checked' );
		const include_authors_value = include_authors.is( ':checked' );

		let tree = {};

		const kingdom_data = root.find( '> li' );

		//kingdom > phylum > class > order
		$.each( kingdom_data, function ( key, value ) {

			const kingdom = $( value );
			const is_mixed = kingdom.hasClass( 'mixed' );
			const is_checked = kingdom.hasClass( 'checked' );
			const kingdom_name = kingdom.attr( 'data-name' );

			if ( is_mixed ) {

				const phylum_data = kingdom.find( '> ul > li' );

				if ( phylum_data.length === 0 )
					tree[ kingdom_name ] = 'true';
				else {

					tree[ kingdom_name ] = {};

					$.each( phylum_data, function ( key, value ) {

						const phylum = $( value );
						const is_mixed = phylum.hasClass( 'mixed' );
						const is_checked = phylum.hasClass( 'checked' );
						const phylum_name = phylum.attr( 'data-name' );

						if ( is_mixed ) {

							const class_data = phylum.find( '> ul > li' );

							if ( phylum_data.length === 0 )
								tree[ kingdom_name ][ phylum_name ] = 'true';
							else {

								tree[ kingdom_name ][ phylum_name ] = {};

								$.each( class_data, function ( key, value ) {

									const class_ = $( value );
									const is_mixed = class_.hasClass( 'mixed' );
									const is_checked = class_.hasClass( 'checked' );
									const class_name = class_.attr( 'data-name' );

									if ( is_mixed ) {

										const order_data = class_.find( '> ul > li' );

										if ( order_data.length === 0 )
											tree[ kingdom_name ][ phylum_name ][ class_name ] = 'true';
										else {

											tree[ kingdom_name ][ phylum_name ][ class_name ] = {};

											$.each( order_data, function ( key, value ) {

												const order = $( value );
												const is_checked = order.hasClass( 'checked' );
												const order_name = order.attr( 'data-name' );

												if ( is_checked )
													tree[ kingdom_name ][ phylum_name ][ class_name ][ order_name ] = 'true';

											} );

										}

									} else if ( is_checked )
										tree[ kingdom_name ][ phylum_name ][ class_name ] = 'true';

								} );

							}

						} else if ( is_checked )
							tree[ kingdom_name ][ phylum_name ] = 'true';

					} );

				}

			} else if ( is_checked )
				tree[ kingdom_name ] = 'true';

		} );

		const payload = '['+JSON.stringify(tree)+','+
			include_generic_names_value+','+
			include_scientific_names_value+','+
			include_authors_value+']';

		payload_field.attr('value',payload);


	} );

	arrow.click( function () {

		const el = $( this );
		const list = el.parent().find( '> ul' );

		el.toggleClass( 'rotated' );
		list.toggleClass( 'collapsed' );

	} );

	checkbox.click( function () {

		const el = $( this );
		const li = el.parent();
		const checkboxes = li.find( 'li' );
		const parent_2 = li.parent();
		const parent_checkbox = parent_2.parent();
		const parent_checkboxes = li.parents('li');
		const neighbors = parent_2.find( '> li' );

		const is_mixed = li.hasClass( 'mixed' );
		const is_checked = li.hasClass( 'checked' );
		const is_parent_checked = parent_checkbox.hasClass( 'checked' );

		if ( ! is_checked || is_mixed ) {

			li.addClass( 'checked' ).removeClass( 'mixed' );
			checkboxes.addClass( 'checked' ).removeClass( 'mixed' );

			if ( ! is_parent_checked )
				parent_checkboxes.addClass( 'mixed' );
			else {

				let all_checked = true;
				$.each( neighbors, function ( key, value ) {

					const el = $( value );
					if ( el.hasClass( 'mixed' ) || ! el.hasClass( 'checked' ) ) {
						all_checked = false;
						return false;
					}

				} );

				if ( all_checked )
					parent_checkboxes.removeClass( 'mixed' );

			}

		} else {

			li.removeClass( 'checked' ).removeClass( 'mixed' );
			checkboxes.removeClass( 'checked' ).removeClass( 'mixed' );

			if ( is_parent_checked )
				parent_checkboxes.addClass( 'mixed' );
			else {

				let all_unchecked = true;
				$.each( neighbors, function ( key, value ) {

					const el = $( value );
					if ( el.hasClass( 'mixed' ) || el.hasClass( 'checked' ) ) {
						all_unchecked = false;
						return false;
					}

				} );

				if ( all_unchecked )
					parent_checkboxes.removeClass( 'mixed' );

			}

		}

	} );

} );