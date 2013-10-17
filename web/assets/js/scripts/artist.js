require(["jquery", "tweet_links"], function($) {
    $(function() {

    	$('.img-btn--label').click(function(e){
    		if ($(this).hasClass('img-btn--label-active')){
    			return false;
    		}

    		var releaseSlug = $(this).data('release-slug'),
    			artistSlug	= $(this).data('artist-slug');

    		$('.releases__active-release').animate({'opacity':0}, 400, function(){
    			$.post('/ajax/'+artistSlug+'/'+releaseSlug, function(data){
    				$('.releases__active-release').html(data);
    				$('.releases__active-release').animate({'opacity':1}, 400);
    			});
    		});

    		$('.img-btn--label-active').removeClass('img-btn--label-active');

    		$(this).addClass('img-btn--label-active');

    		e.preventDefault();
    		return false;
    	});


        $('.more-link__link').on('click', function(e){
            $('.more-link-wrapper').hide();
            $('.table--songkick-more').removeClass('table--songkick-more');

            e.preventDefault();
            return false;
        })

    });
});
