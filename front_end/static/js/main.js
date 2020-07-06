$( function () {

	const options = $( '.option' );
	const ranks = $( '.rank' );
	const get_result_button = $( '#get_result_button' );
	const arrow = $( '.arrow' );
	const checkbox = $( '.checkbox' );
	const root = $( '#root' );
	const payload_field = $( '#payload_field' );


	// Sending results
	get_result_button.click( function () {


		//Get tree
		const tree = get_children(root);


		//Get ranks
		const ranks_values = {};

		$.each(ranks,function(key,element){

			const el = $(element);
			let rank = el.attr('id');
			rank = rank.replace('rank_','');

			ranks_values[rank] = el.is(':checked');

		});


		//Get options
		const options_values = [];

		$.each(options,function(key,element){
			options_values.push($(element).is( ':checked' ))
		});


		//Send data
		const payload = JSON.stringify([tree,ranks_values,options_values]);

		payload_field.attr('value',payload);


	} );

	function get_children(element){

		const tree = {};

		const children = element.find('> li');

		$.each(children,function(key,el){

			const child = $(el);

			const child_name = child.attr('data-name');

			if(child.hasClass('mixed')){
				const ul = child.find('> ul');
				if(ul.length===1)
					tree[child_name] = get_children(ul);
			}
			else if(child.hasClass('checked'))
				tree[child_name] = 'true';

		});

		return tree;

	}


	// Folding
	arrow.click( function () {

		const el = $( this );
		const list = el.parent().find( '> ul' );

		el.toggleClass( 'rotated' );
		list.toggleClass( 'collapsed' );

	} );


	// Checkboxes
	checkbox.click( function(){

		const el = $(this);
		const li = el.parent();

		const children = li.find( 'li' );
		const is_mixed = li.hasClass( 'mixed' );
		const is_checked = li.hasClass( 'checked' );

		if(is_mixed || !is_checked){
			li.addClass('checked').removeClass('mixed');
			children.addClass('checked').removeClass('mixed');
		}
		else {
			li.removeClass('checked').removeClass('mixed');
			children.removeClass('checked').removeClass('mixed');
		}

		notify_parent(li);

	} );

	function notify_parent(caller){

		const el = caller.parent();
		const li = el.parent();

		if(!li.is('li'))
			return;//parent is already root

		const children = el.find('li');

		const was_checked = li.hasClass('checked');
		const was_mixed = li.hasClass('mixed');

		let all_unchecked = true;
		let all_checked = true;
		$.each(children,function(key,element){

			const el = $(element);

			if(el.hasClass('mixed')){
				all_unchecked = false;
				all_checked = false;
				return false;
			}

			if(el.hasClass('checked'))
				all_unchecked = false;
			else
				all_checked = false;

		});

		let is_checked = false;
		let is_mixed = false;

		if(all_unchecked && all_checked)//there are 0 children
			return;
		if(all_unchecked)//make unchecked
			li.removeClass('checked mixed');
		else if(all_checked){//make checked
			li.addClass( 'checked' ).removeClass( 'mixed' );
			is_checked = true;
		}
		else {//make mixed
			li.addClass('mixed').removeClass('checked');
			is_mixed = true;
		}

		if(was_checked!==is_checked || was_mixed!==is_mixed)//notify parent if made any changes
			notify_parent(li);

	}

} );