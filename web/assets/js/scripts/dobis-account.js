require(["jquery", "dropdown"], function($) {
    $(function() {
		var madeChanges = false;


    	// submit button outside of <form>
    	$('.btn--submit').click(function(e){
    		madeChanges = false; // turn this off for the submission
    		$('form.form--account-dobis').submit();
    		e.preventDefault();
    		return false;
    	});

    	// warn user before leaving the page
    	$('.form--account-dobis').on('change', ':input', function (event){
    		madeChanges = true;
    	});

    	function unloadPage(){
    		if (madeChanges){
    			return "Leaving the page now will discard your changes."
    		}
    	}

    	window.onbeforeunload = unloadPage;


    });
});