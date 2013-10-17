require(["jquery", "dropdown"], function($) {
    $(function() {
		var madeChanges = false;


    	// submit button outside of <form>
    	$('.btn--submit').click(function(e){
    		madeChanges = false; // turn this off for the submission
    		$('form.form--booking-agents-dobis').submit();
    		e.preventDefault();
    		return false;
    	});

    	// warn user before leaving the page
    	$('.form--booking-agents-dobis').on('change', ':input', function (event){
    		madeChanges = true;
    	});

    	function unloadPage(){
    		if (madeChanges){
    			return "Leaving the page now will discard your changes."
    		}
    	}

    	window.onbeforeunload = unloadPage;


    	// add new agent
    	$('.add-booking-agent').on('click', function(event){
    		var state = $(this).data('state');


    		$('.no-release').hide();

    		$.post('/dobismaster/ajax/new-booking-agent/'+state, function(data){
    			$('.add-booking-agent').after(data);
                    if ($('.booking-agents-dobis--no-agents'))
                    {
                        $('.booking-agents-dobis--no-agents').hide();
                    }                
    		});

    		$(this).data('state', Number(state+1));

    		event.preventDefault();
    		return false;
    	});


    	// remove agent
    	$('.form--booking-agents-dobis').on('click', '.btn--remove-booking-agent', function (event){
    		var type = $(this).data('type');

    		if (type == "new")
    		{
    			$(this).hide();
    			$(this).parent('.booking-agents-dobis').find('.inner-contents').html('<p class="lead">Booking agent deleted.</p>');
    		}
    		else if (type == "existing")
    		{
    			madeChanges = true;

    			var booking_agent_id = $(this).data('booking-agent-id');
    			$('#booking_agents_form_existing_booking_agents_'+ booking_agent_id +'_remove').attr('value', '1');

    			$(this).hide();
    			$(this).parent('.booking-agents-dobis').find('.inner-contents').hide().after('<p class="lead">Booking agent deleted. Please save changes.</p>');
    		}

    		event.preventDefault();
    		return false;
    	});


    });
});