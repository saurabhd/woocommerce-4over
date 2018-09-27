// JavaScript Document

//Call this method to disable the dropdown.
var disableTheDropdown = function (dropdownId, msg){
	"use strict";
	//set default message
	msg = msg || "Loading options";
	
	//add disabled option
	jQuery("#" + dropdownId ).html('<option selected="true" disabled="disabled" value="loading">' + msg + '</option>');
	
	//remove original selectable option
	jQuery('#' + dropdownId + ' option[value="disabled"]').remove();
};

//Let's get this party started!
jQuery( document ).ready( function getAllCats() {	
	
	"use strict";
	
	disableTheDropdown('category-uuid-selector1');

	var index = 1;

	makeRequest('category', index);	
});


//param ID should be UUID (optional)
//param type can be category, product, option, base_price 
var makeRequest = function(type, index, uuid){
	
	"use strict";
	// Set AJAX variables
	var data = {
		//Name of action to trigger
		'action': 'rpep_get_4over_list',

		//Variables to pass to AJAX function
		'type': type,	
		'uuid': uuid		
	};
	
	//Make AJAX request and load results in dropdown
	jQuery.post(ajaxurl, data, function(response) {
				
		//Parse JSON Object in window object for us in ext function as well
		window.results = JSON.parse(response);
		window[type+'_child'] = window.results.next; //This way window.category_child === 'product'
		
		//alert(JSON.stringify(window.results, null, 2));
		//Loop through results and populate the dropdown

		jQuery('#' + type + '-uuid-selector' + index).html('<option selected="true" disabled="disabled">Select ' + type + '</option>');
		console.log('#' + type + '-uuid-selector' + index);
		
		jQuery(window.results.entities).each(function(){
			//For each object add to dropdown with same uuid
			jQuery('#' + type + '-uuid-selector' + index).append('<option value="' + this.rpep_uuid + '">' + this.rpep_title + '</option>');
		});

		//Once loaded, change the Dropdown selection prompt one last time
		jQuery('#' + type + '-uuid-selector' + index + 'option[value="loading"]').remove();
		//jQuery('#' + type + '-uuid-selector' + index).prepend('<option disabled="disabled">Select ' + type + '</option>');
	});
	
};


jQuery(document).on('change', '.category-uuid-selector', function() { 
		
		//alert(jQuery('option:selected',this).text());

		var selectedId = jQuery(this).val();
		var index = jQuery(this).attr('rel');

		disableTheDropdown('product-uuid-selector' + index);

		makeRequest('product', index, selectedId);
	
	});


jQuery( document ).ready(function() {


 var counter = 2;
		
    jQuery("#addButton").click(function () {
			
		var newTextBoxDiv = jQuery(document.createElement('div'))
		     .attr("id", 'TextBoxDiv' + counter);
	                
		newTextBoxDiv.after().html('<br><label for="meta-box-dropdown">Select Category</label> ' + 
	                '<select name="category-uuid-selector[]" class="category-uuid-selector" id="category-uuid-selector' + counter + '" rel="' + counter + '">' + 
	                    '<option>Loading options</option>' + 
	                '</select><br><br><label for="meta-box-dropdown">Select Product</label> <select name="product-uuid-selector[]" class="product-uuid-selector" id="product-uuid-selector' + counter + '" rel="' + counter + '">' + 
	                    '<option>Loading options</option></select></br></br>');
	            
		newTextBoxDiv.appendTo("#TextBoxesGroup");

		makeRequest('category', counter);
					
		counter++;

     });

     jQuery("#removeButton").click(function () {
		if(counter==1){
	          alert("No more textbox to remove");
	          return false;
	       }   
	        
		counter--;
			
        jQuery("#TextBoxDiv" + counter).remove();
			
     });

});
