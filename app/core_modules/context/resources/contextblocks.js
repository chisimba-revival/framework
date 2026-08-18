
    // Flag Variable - Update message or not
    var doUpdateMessage = false;


    var leftBlock = false;
    var rightBlock = false;
    var middleBlock = false;


    var inEditMode = false;

    // Var Current Entered Code
    var currentCode;

    function blockControlLabel(value)
    {
        return jQuery('<div></div>').text(String(value)).html();
    }

    function optionalBlockControlLabel(variableName)
    {
        if (typeof window[variableName] === 'undefined') {
            return '';
        }
        return blockControlLabel(window[variableName]);
    }

    function blockOptionsMarkup()
    {
        return '<div class="blockoptions" role="group" aria-label="'+optionalBlockControlLabel('blockControlsLabel')+'">'
            + '<button type="button" class="block-control moveup" aria-label="'+optionalBlockControlLabel('moveUpLabel')+'" title="'+optionalBlockControlLabel('moveUpLabel')+'">'+upIcon+'</button>'
            + '<button type="button" class="block-control movedown" aria-label="'+optionalBlockControlLabel('moveDownLabel')+'" title="'+optionalBlockControlLabel('moveDownLabel')+'">'+downIcon+'</button>'
            + '<button type="button" class="block-control deleteblock" aria-label="'+optionalBlockControlLabel('removeBlockLabel')+'" title="'+optionalBlockControlLabel('removeBlockLabel')+'">'+deleteIcon+'</button>'
            + '</div>';
    }

    // Action to be taken once page has loaded
    jQuery(document).ready(function(){


        jQuery(".block").prepend(blockOptionsMarkup());

        if (inEditMode) {
            jQuery("#leftaddblock").show();
            jQuery("#rightaddblock").show();
            jQuery("#middleaddblock").show();
            jQuery(".blockoptions").show();
        } else {
            jQuery("#leftaddblock").hide();
            jQuery("#rightaddblock").hide();
            jQuery("#middleaddblock").hide();
            jQuery(".blockoptions").hide();
        }


       setUpSide('left');
        setUpSide('right');
       setUpSide('middle');




        jQuery(document).on('click', '.moveup', function(event) {
            event.preventDefault();
            moveBlock(jQuery(this).parent().parent().attr('id'), 'up');
        });

        jQuery(document).on('click', '.movedown', function(event) {
            event.preventDefault();
            moveBlock(jQuery(this).parent().parent().attr('id'), 'down');
        });

        jQuery(document).on('click', '.deleteblock', function(event) {
            event.preventDefault();
            if (confirm(deleteConfirm)) {
                removeBlock(jQuery(this).parent().parent().attr('id'));
            }
        });





        // adjustLayout();


    });

    function setUpSide(side)
    {
        jQuery("#dd"+side+"blocks").bind('change', function() {
            getPreview(jQuery("#dd"+side+"blocks").attr('value'), side);
        });

       // jQuery("#"+side+"button").hide();
        jQuery("#"+side+"button").bind('click', function() {
            addBlock(window[side+'Block'], side)
        });

        updateSideControls(side);
    }

    function updateSideControls(side)
    {
        var blocks = jQuery("#"+side+"blocks");
        blocks.find(".moveup, .movedown").show();
        blocks.children(":first-child").find(".moveup").hide();
        blocks.children(":last-child").find(".movedown").hide();
    }

    function updateAllSideControls()
    {
        updateSideControls('left');
        updateSideControls('right');
        updateSideControls('middle');
    }


    function moveBlock(blockId, direction)
    {
        jQuery.ajax({
            type: "GET",
            url: "index.php",
            data: "module="+theModule+"&action=moveblock&blockid="+blockId+'&direction='+direction,
            success: function(msg){

                if (msg == 'ok') {
                    if (direction == 'up') {

                        var div = jQuery('#'+blockId).insertBefore(jQuery('#'+blockId).prev());
                    } else {
                        var div = jQuery('#'+blockId).insertAfter(jQuery('#'+blockId).next());
                    }
                    updateAllSideControls();

                } else {
                    alert(unableMoveBlock);
                }

                // adjustLayout();
            }
        });


    }

    function removeBlock(blockId)
    {
        jQuery.ajax({
            type: "GET",
            url: "index.php",
            data: "module="+theModule+"&action=removeblock&blockid="+blockId,
            success: function(msg){

                if (msg == 'ok') {
                    jQuery('#'+blockId).remove();
                    updateAllSideControls();
                } else {
                    alert(unableDeleteBlock);
                }

                // adjustLayout();
            }
        });
    }

    function addBlock(blockid, side)
    {

        // DO Ajax
        jQuery.ajax({
            type: "GET",
            url: "index.php",
            data: "module="+theModule+"&action=addblock&blockid="+blockid+"&side="+side,
            success: function(msg){

                if (msg == '') {
                    alert(unableAddBlock);
                } else {
                    jQuery("#"+side+"previewcontent .block").attr('id', msg);

                    // First Add Up/Down/Delete
                    jQuery("#"+side+"previewcontent .block").prepend(blockOptionsMarkup());

                    // Then Attach
                    jQuery("#"+side+"previewcontent .block").appendTo("#"+side+"blocks");
                    jQuery("#"+side+"button").hide();
                    updateSideControls(side);


                }

                // adjustLayout();
            }
        });
    }

    function getPreview(blockid, side)
    {
        jQuery("#"+side+"button").hide();
        // adjustLayout();

        if (blockid=="") {
            jQuery("#"+side+"previewcontent").hide();
            jQuery("#"+side+"button").hide();
            // adjustLayout();
        } else {


            // DO Ajax
            jQuery.ajax({
                type: "GET",
                url: "index.php",
                data: "module="+theModule+"&action=renderblock&blockid="+blockid+"&side="+side,
                success: function(msg){

                    jQuery("#"+side+"previewcontent").show();
                    jQuery("#"+side+"previewcontent").html(msg);

                    if (side == 'left') {leftBlock = blockid; }
                    if (side == 'right') {rightBlock = blockid; }
                    if (side == 'middle') {middleBlock = blockid; }


                    if (msg != "") {
                        jQuery("#"+side+"button").show();
                    }

                    // adjustLayout();
                }
            });


            /*
            // DO Ajax
            jQuery.get("index.php", { module: theModule, action: "renderblock", blockid: blockid, side: side },  function(msg){

                    jQuery("#"+side+"previewcontent").show();
                    jQuery("#"+side+"previewcontent").html(msg);

                    if (side == 'left') {leftBlock = blockid; }
                    if (side == 'right') {rightBlock = blockid; }
                    if (side == 'middle') {middleBlock = blockid; }


                    if (msg != "") {
                        jQuery("#"+side+"button").show();
                    }

                    adjustLayout();
                }
            );*/
        }

    }

    function switchEditMode()
    {
        if (inEditMode) {
            jQuery("#leftaddblock").hide();
            jQuery("#rightaddblock").hide();
            jQuery("#middleaddblock").hide();
            jQuery("#editmodeswitchbutton").text(turnEditingOn);
            jQuery("#modeswitch_wrapper").removeClass("editing_on");
            jQuery("#modeswitch_wrapper").addClass("editing_off");
            jQuery(".blockoptions").hide();
            jQuery(".block").removeClass('highlightblock');

            inEditMode = false;
        } else {

            jQuery("#leftaddblock").show();
            jQuery("#rightaddblock").show();
            jQuery("#middleaddblock").show();
            jQuery("#editmodeswitchbutton").text(turnEditingOff);
            jQuery("#modeswitch_wrapper").removeClass("editing_off");
            jQuery("#modeswitch_wrapper").addClass("editing_on");
            jQuery(".blockoptions").show();
            jQuery(".block").addClass('highlightblock');
            inEditMode = true;
        }

        // adjustLayout();
    }

