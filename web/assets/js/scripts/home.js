require(["jquery", "tweet_links"], function($) {
    $(function() {
    	$('[data-toggle="label-filter"]').click(function(event) {
    		var selectedLabel = $(this).data('label-slug');

    		if ($(this).hasClass('on')) // if it's already selected
    		{
    			$(this).removeClass('on');

    			$('.img-btn--label.off').removeClass('off');
    			$('.artists-grid__artist.off').removeClass('off');
    		}
    		else
    		{
    			$('.img-btn--label.on').removeClass('on');
    			$(this).addClass('on').removeClass('off');

    			$('.img-btn--label').each(function(){
    				if (!$(this).hasClass('off') && !$(this).hasClass('on'))
	    			{
	    				$(this).addClass('off');
	    			}
    			});

                // hide all artist buttons that aren't already hidden...
                $('.artists-grid__artist:not(.off)').addClass('off');

                // and show those that belong to the label
                $('.artists-grid__artist').each(function() {
                    var labelsArray = $(this).data('label-slug').split('|');

                    if ($.inArray(selectedLabel, labelsArray) != -1)
                    {
                        $(this).removeClass('off');
                    }
                });
    			
    		}

    		event.preventDefault();
    		return false;
    	});

		$('.img-btn--artist').click(function(event) {

			if ($(this).hasClass('off'))
			{
				event.preventDefault();
				return false;
			}
		});
    });
});
