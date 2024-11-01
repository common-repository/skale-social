
var WPW = WPW || {};
jQuery(document).ready(function($){
    ON = true;
    WPW.body = $("body");
    WPW.window = $(window);

    //----------    FRONTEND

    // add bg to stickybar on frontend
    $("#skaledigital-sharebar-sticky").each(function(){
        var node = $(this);
        var color = node.attr("data-color");
        var bg = $("<div class='sticky-bar-bg'></div>");
        $(".sds-share-icons-bar", node).prepend(bg);
        bg.css("background-color", color);
    });


    //ADD POPUP WINDOW
	$('.sds-share-icons-bar a').on("click", function(event){
		event.preventDefault();

        // these share options don't need to have a popup
        if (jQuery(this).data('service') == 'pinterest') {
            // just redirect
            window.location.href = jQuery(this).attr("href");
        } else {
            // prepare popup window
            var width  = 575,
                height = 520,
                left   = (jQuery(window).width()  - width)  / 2,
                top    = (jQuery(window).height() - height) / 2,
                opts   = 'status=1' +
                    ',width='  + width  +
                    ',height=' + height +
                    ',top='    + top    +
                    ',left='   + left;

            // open the share url in a smaller window
            window.open(jQuery(this).attr("href"), 'share', opts);
            return false;
        }

        return false;
	});



    //----------    ADMIN

    var sdsOptionsPage = $(".sds-options-area");

    if(!sdsOptionsPage.length){return;}


    var menuNode = $("<div class='sds-quick-menu'>Quick Menu: </div>");

    function gotoArea(box){
        $("html, body").animate({
            scrollTop: box.offset().top - 100
         }, 500 );
    }

    //move the checkbox before labels
    $(".sds-input-box").each(function(iIndex){
        var box = $(this);
         var iNode = $("input[type='checkbox']", box);
         box.prepend(iNode);

         if(iIndex == 0){
             box.after(menuNode);
         }




         //check for menu item
         var menuButton = $(".sds-menu-item", box);
         if(menuButton.length){

             menuNode.append(menuButton);
             if($(".sds-menu-item", menuNode).length){
                 menuNode.append(" | ");
             }


             menuButton.on("click", function(e){
                 gotoArea(box);
                 e.preventDefault();

                 return false;
             });
         }


    });

    //enable the colorpickers
    $(".sds-colorpicker").wpColorPicker();

    var toggleModule = $(".social-icons-toggle").first();



    //THE TOGGLE BAR  - lets you select and sort the social services
    toggleModule.each(function(){
        var mNode = $(this);
        var btnNodes = $(".sds-social-button", mNode);
        var saveField = $(".option-share-buttons-list input");

        function initialRead(){
            var initVal = saveField.val();
            var socialIDs = initVal.split(" ");

            for (i = 0; i < socialIDs.length; i++){
                var socialID = socialIDs[i];

                if(socialID.length > 2 ){
                    var fBtn = $("[data-service='"+socialID+"']", mNode);
                    fBtn.addClass("enabled-social-button");
                    mNode.append(fBtn);
                }
            }

            btnNodes.each(function(){
                var btnNode = $(this);
                if(!btnNode.hasClass("enabled-social-button")){
                    mNode.append(btnNode);
                }
            });
        }

        function readToggle(){
            saveField.val("");
            var newVal = "";
            btnNodes = $(".sds-social-button", mNode);
            btnNodes.each(function(){
                var btnNode = $(this);
                if(btnNode.hasClass("enabled-social-button")){
                    newVal += " " + btnNode.attr("data-service");
                }
            });

            saveField.val(newVal);
        }
        initialRead();

        mNode.sortable({
          stop: function( event, ui ) {readToggle()}
        });
        mNode.disableSelection();



        btnNodes.each(function(){
            var btnNode = $(this);
            btnNode.click(function(e){
                e.preventDefault();
                btnNode.toggleClass("enabled-social-button");
                readToggle();
                return false;
            });
        });


    });

    // TEST API TOKEN - button
    $(".option-sds_api_token-box").each(function(){
        var apiBox = $(this);
        var testBtn = $('<span class="sds-test-api-token" title="Test API key"><i class="fa fa-certificate sds-green-result" aria-hidden="true"></i> <span></span></span>');
        apiBox.append(testBtn);
        var apiURL = $("#sds-api-url").val();
        var tokenInput = $("#sds_api_token");
        var resultIcon = $(".sds-green-result", apiBox);
        var msgField = $(".sds-test-api-token span");
        //

        apiURL = apiURL.replace("__API_TOKEN__", tokenInput.val());
        apiURL = apiURL.replace("__TARGET_URL__", "http://skaledigital.com/");
        apiURL = apiURL + "&callback="+Math.random();


        var approvedAPIKEY = 0;

        var apiPanel = $("<div class='sds-api-panel'></div>");
        var activeAction = 0;
        var activeCreation = 0;
        apiBox.append(apiPanel);
        var actionInfo = $('<div class="sds-action-info"></div>');

        var btnDelete = $('<span class="sds-btn-delete-all-links button"><i class="fa fa-times" aria-hidden="true"></i> Delete current cached Skale shortlinks</span>');
        var deleteInfo = $('<span class="sds-delete-info"></span>');

        var btnGenerateLinks = $('<span class="sds-btn-generate-links button"><i class="fa fa-refresh" aria-hidden="true"></i> Generate Skale Links</span>');
        var btnForceGenerateLinks = $('<span class="sds-btn-force-generate-links"><input type="checkbox" name="sds-force-regenarate" id="sds-force-regenarate"> Force Regenerate All</span>');
        var generatePreInfo = $('<span class="sds-info">Generate or Regenerate all shortlinks using this API TOKEN</br>Can increase site speed for your site visitors.</span>');
        var generateInfo = $('<span class="sds-generate-info"></span>');


        apiPanel.append(btnGenerateLinks);
        apiPanel.append(btnForceGenerateLinks);
        apiPanel.append(generatePreInfo);
        apiPanel.append(btnDelete);
        btnGenerateLinks.append(generateInfo);
        apiPanel.append(actionInfo);

        btnForceGenerateLinks = $("#sds-force-regenarate");

        var doneNr = 0;
        var nrToBeDone = 0;

        var clearTimer = 0;
        function clear(){
            msgField.html("<span style='opacity:0.4'>TEST API KEY</span>");
            resultIcon.css("color", "");
        }

        var testingToken = 0;
        function testToken(){
            if(testingToken){ return false; }
            testingToken = 1;

            var data = {
    			'action': 'sds-test-token',
    			'api_key': $("#sds_api_token").val()
    		};

            resultIcon.addClass("fa-spin");

            jQuery.post(ajaxurl, data, function(response) {
                testingToken = 0;
                msgField.html(response);
                if($(response).attr("data-ok") == "1"){
                    if(apiBox.data("generator-test") == 1){
                        apiBox.trigger("GENERATOR.OK");
                        apiBox.data("generator-test", 0);
                    } else {
                        showMessage("API KEY is OK!<br/>In case you just updated the API KEY, click the button '<strong>Generate Skale Links</strong>' to generate new Skale shortlinks. <br/> This may improve the performance for your site visitors, because they will have the urls inside buttons already generated.");
                    }
                    resultIcon.css("color", "green");
                } else {

                    if(apiBox.data("generator-test") == 1){
                        apiBox.trigger("GENERATOR.BAD");
                        apiBox.data("generator-test", 0);
                    }

                    resultIcon.css("color", "orange");
                }
                resultIcon.removeClass("fa-spin");
    		});
            clearTimeout(clearTimer);
            clearTimer = setTimeout(function(){
                clear();
            }, 5000);
        }

        testBtn.on("click", function(){
            showMessage("<br/>");
            testToken();
        });

        tokenInput.on("keyup", testToken);
        tokenInput.on("change", testToken);
        tokenInput.on("propertychange", testToken);
        tokenInput.on("paste", testToken);
        tokenInput.on("mouseup", testToken);
        tokenInput.on("focus", testToken);

        //////

        function showMessage(msg, otherTarget){
            actionInfo.append("<br/>" + msg);
            actionInfo.scrollTop(actionInfo[0].scrollHeight);
            if(otherTarget){
                otherTarget.html(msg);
            }
        }


        function createLinks(){
            if(!activeCreation){
                btnGenerateLinks.removeClass("running-action sds-green");
                $(".fa", btnGenerateLinks).removeClass("fa-spin");
                return false;
            }

            $.post(ajaxurl, {
                "action":   'get_posts_to_generate_links',
                "remaining": doneNr,
                "apikey":   approvedAPIKEY
                },
                function(data) {
                    doneNr = parseInt($(data).attr("data-done"), 10);
                    var remaining = parseInt($(data).attr("data-remaining"), 10);

                    if(remaining > 0){
                        showMessage( "Processed " + (nrToBeDone - remaining) + " / " + nrToBeDone, generateInfo);
                        if(!activeCreation){
                            showMessage( "Processed Stopped", generateInfo);
                        }
                        createLinks();
                    } else {
                        showMessage( "Processed " + (nrToBeDone - remaining) + " / " + nrToBeDone);
                        showMessage("Links Generated!", generateInfo);
                        activeCreation = 0;
                        btnGenerateLinks.removeClass("running-action sds-green");
                        $(".fa", btnGenerateLinks).removeClass("fa-spin");
                    }
			});
        }

        // GENERATE LINKS
        function initCreateLinks(){
            generateInfo.html("Reading the posts number.");
            var force = btnForceGenerateLinks.is(":checked");
            if(force){ force = "on"; }


            $.post(ajaxurl, { action: 'count_linkable_skale_posts', force_regen:force}, function(data) {
                showMessage("Processing " + data + " posts for link generation...", generateInfo);
                if(parseInt(data, 10) > 0){
                    doneNr = 0;
                    nrToBeDone = parseInt(data, 10);
                    createLinks();
                } else {
                    $(".fa", btnGenerateLinks).removeClass("fa-spin");
                }
			});

            return;
        }

        function toggleLinkGeneration(){
            if(btnGenerateLinks.hasClass("running-action")){
                activeCreation = 0;
            } else {
                if(!confirm("Generate Skale Shortlinks?")){ return; }
                activeCreation = 1;
                initCreateLinks();
            }
            btnGenerateLinks.toggleClass("running-action");
        }

        //start generator only after we test api key
        apiBox.on("GENERATOR.OK", function(){
            approvedAPIKEY = tokenInput.val();
            showMessage("API KEY: OK", generateInfo);
            initCreateLinks();
        });

        //bad api key
        apiBox.on("GENERATOR.BAD", function(){
            showMessage("NO VALID API KEY FOUND", generateInfo);
        });

        //start generating stuff with api key test
        btnGenerateLinks.on("click", function(){
            showMessage("<br/>");
            approvedAPIKEY = 0;
            if(btnGenerateLinks.hasClass("running-action")){
                activeCreation = 0;
                showMessage("Canceling process... ", generateInfo);
                return false;
            } else {
                activeCreation = 1;
            }
            $(".fa", btnGenerateLinks).addClass("fa-spin");
            btnGenerateLinks.addClass("running-action sds-green");
            showMessage("Testing API KEY..", generateInfo);
            apiBox.data("generator-test", 1);
            testToken();
        });

        // DELETE ACTION
        function deleteLinks(data){
            if(!activeAction){
                //cancel action
                $(".fa", btnDelete).removeClass("fa-spin");
                btnDelete.removeClass("running-action sds-red");
                showMessage("Links Deleted!", deleteInfo);
                return;
            }

            btnDelete.append(deleteInfo);
            btnDelete.addClass("sds-red");
            $(".fa", btnDelete).addClass("fa-spin");

            if(data){
                showMessage("Items Remaining: " + data, deleteInfo);
            } else {
                showMessage("Deleting Links...", deleteInfo);
            }

            $.post(ajaxurl, { action: 'delete_all_sds_links'}, function(data) {
				if(parseInt(data, 10) > 0){
					deleteLinks(data);
				} else {
					$(".fa", btnDelete).removeClass("fa-spin");
                    btnDelete.removeClass("running-action sds-red")
                    showMessage("DONE ;]", deleteInfo);
				}
			});

        }

        btnDelete.on("click", function(){
            showMessage("<br/>");
            if(btnDelete.hasClass("running-action")){
                activeAction = 0;
            } else {
                if(!confirm("Delete current Skale Shortlinks?")){ return; }
                activeAction = 1;
                deleteLinks();
            }
            btnDelete.toggleClass("running-action");
        });


    });


});
