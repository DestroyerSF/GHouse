require(["jquery", "dropdown"], function($) {
    $(function() {
		var madeChanges = false;


    	// submit button outside of <form>
    	$('.btn--submit').click(function(e){
    		madeChanges = false; // turn this off for the submission
    		$('form.form--artist-dobis').submit();
    		e.preventDefault();
    		return false;
    	});

    	// warn user before leaving the page
    	$('.form--artist-dobis').on('change', ':input', function (event){
    		madeChanges = true;
    	});

    	function unloadPage(){
    		if (madeChanges){
    			return "Leaving the page now will discard your changes."
    		}
    	}

    	window.onbeforeunload = unloadPage;


    	// update URL preview on name change
    	$('.form__input[rel="slugify"]').on('change', ':input', function (event) {
    		var text = $(this).val(),
    			input = $(this),
    			type = $(this).parent('.input__control').parent('.form__input').data('type');

    		$.post('/dobismaster/ajax/slugify', { 'text': text }, function(data){
	    		if (type == 'release')
	    		{
	    			input.next('.input__help').find('.ajax--release').html(data);
	    		}
	    		else if (type == 'artist')
	    		{
	    			$('.ajax--artist').each(function(){
	    				$(this).html(data);
	    			});
	    		}
	    		
    		})
    		
    	});


    	// add new release
    	$('.add-release').on('click', function(event){
    		var state = $(this).data('state');


    		$('.no-release').hide();

    		$.post('/dobismaster/ajax/new-release/'+state, function(data){
    			$('.add-release').after(data);
    		});

    		$(this).data('state', Number(state+1));

    		event.preventDefault();
    		return false;
    	});


    	// remove release
    	$('.form--artist-dobis').on('click', '.btn--remove-release', function (event){
    		var type = $(this).data('type');

    		if (type == "new")
    		{
    			$(this).hide();
    			$(this).parent('.release-dobis').find('.inner-contents').html('<p class="lead">Release deleted.</p>');
    		}
    		else if (type == "existing")
    		{
    			madeChanges = true;

    			var release_id = $(this).data('release-id');
    			console.log($('#dobis_form_releases_existing_releases_'+ release_id +'_remove').attr('name'));
    			$('#dobis_form_releases_existing_releases_'+ release_id +'_remove').attr('value', '1');

    			$(this).hide();
    			$(this).parent('.release-dobis').find('.inner-contents').hide().after('<p class="lead">Release deleted. Please save changes.</p>');
    		}

    		event.preventDefault();
    		return false;
    	});


        // remove artist picture
        $('.form--artist-dobis').on('click', '.btn--remove-picture', function(event){
            $('#dobis_form_artist_new_picture').val(null);

            if (!$(this).hasClass('js-no-picture'))
            {
                $('#dobis_form_artist_remove_picture').val('true');
                $('.js-artist-image-preview').addClass('hide');
                madeChanges = true;
            }

            $(this).addClass('hide');

            event.preventDefault();
            return false;
        });

        $('.form--artist-dobis').on('change', ':input[type=file]', function(event){
            if ($(this).val() != null)
            {
                $('#dobis_form_artist_remove_picture').val('false');
                $('.btn--remove-picture').removeClass('hide');
            }
        })

    });
});