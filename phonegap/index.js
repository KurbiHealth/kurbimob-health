this["JST"] = this["JST"] || {};

this["JST"]["kurbyApp"] = Handlebars.template(function (Handlebars,depth0,helpers,partials,data) {
  this.compilerInfo = [4,'>= 1.0.0'];
helpers = this.merge(helpers, Handlebars.helpers); data = data || {};
  


  return "\r\n<div data-role=\"page\" id=\"loginPage\">\r\n\r\n    <div class=\"uiContainer\">\r\n    <div data-role=\"header\" id=\"navBar\">\r\n        <p class=\"navText\">kurbi</p>\r\n    </div>\r\n    \r\n        <div id=\"lLogoContainer\">\r\n        <img src=\"kurbi_logo.png\" id=\"lLogo\">\r\n        </div>\r\n        <div id=\"lFormContainer\">\r\n            <input id=\"lUsername\" class=\"iBox\" data-role=\"none\" placeholder=\"name@emailaddress.com\"></input>\r\n            <input id=\"lPassword\" class=\"iBox\" data-role=\"none\" type=\"password\" placeholder=\"password\"></input>\r\n            <div id=\"lLogin\" class=\"bButton cPurple\">login</div>\r\n        </div>\r\n        \r\n    </div>\r\n    \r\n    <div id=\"lBackground_one\" class=\"solidGray\">\r\n    </div>\r\n<div class=\"curtain\"> </div>\r\n</div>\r\n\r\n\r\n<div data-role=\"page\" id=\"registerPage\">\r\n\r\n\r\n\r\n    <div class=\"uiContainer\">\r\n    \r\n    <div data-role=\"header\" id=\"navBar\">\r\n        <p class=\"navText\">kurbi</p>\r\n    </div>\r\n\r\n        <h1 class=\"heading\">Add New Patient</h1>\r\n        <div id=\"rFormContainer\">\r\n            <div class=\"flex-container\">\r\n                <input id=\"rFirstName\" class=\"iBox\" data-role=\"none\" type=\"text\" placeholder=\"First Name\"></input>\r\n                <input id=\"rLastName\" class=\"iBox\" data-role=\"none\" type=\"text\" placeholder=\"Last Name\"></input>\r\n            </div>\r\n            <input id=\"rEmail\" class=\"iBox\" data-role=\"none\" placeholder=\"name@emailaddress.com\"></input>\r\n            <input id=\"rAddress\" class=\"iBox\" data-role=\"none\" placeholder=\"123 Enter Address Here Apt.4\"></input>\r\n            <div class=\"flex-container\">\r\n                <input id=\"rState\" class=\"iBox\" data-role=\"none\" type=\"text\" placeholder=\"AZ\"></input>\r\n                <input id=\"rCity\" class=\"iBox\" data-role=\"none\" placeholder=\"Enter City Here\"></input>\r\n                <input id=\"rZipcode\" class=\"iBox\" data-role=\"none\" placeholder=\"19806\"></input>\r\n                \r\n            </div>\r\n            <div id=\"rSubmit\" class=\"bButton cPurple\">Send Invite</div>\r\n        </div>\r\n    </div>\r\n\r\n    <div id=\"rBackground_one\" class=\"solidGray\"></div>  \r\n<div class=\"curtain\"> </div>\r\n</div>\r\n\r\n";
  });
(function(){
var debug = true;
var submitUrlUser = "http://mobiledev.gokurbi.com/sign_in/ajax_user";
var submitUrlForm = "http://mobiledev.gokurbi.com/invitation/ajax_post_form";
var token = "";


$("body").append(JST.kurbyApp());

$(document).on("pagecreate", "#loginPage", function(){
	if(debug) console.log("page2 creaetd");
	$("#lLogin").on("touchstart",addHighlight);
	$("#lLogin").on("touchstart",loginSubmit);
	$("#lLogin").on("touchend",removeHighlight);
	
	
});

$(document).on("pagecreate", "#registerPage", function(){
	if(debug) console.log("page3 creaetd");
	$("#rSubmit").on("touchstart",addHighlight);
	$("#rSubmit").on("touchstart",registerSubmit);
	$("#rSubmit").on("touchend",removeHighlight);
	
	
});


function removeHighlight(){
	if(debug) console.log("removing highlight");
	$(this).removeClass("highlight");
}

function addHighlight(){
	if(debug) console.log("adding highlight");
	$(this).addClass("highlight");
}

function setupCurtain(n){
	var maxHeight = $(".curtain").height();
	var blockHeight = maxHeight/n;
	for(var i = 0; i < n; i++){
		var $elem = $("<div>");
		$elem.css({"background-color":"#ffffff", "height":blockHeight, "width":"100%", "position":"absolute"});
		$elem.css({"top":blockHeight*i});
		$elem.css({"z-index":"100"});
		$(".curtain").append($elem);
		$elem.hide();
	}
	$(".curtain").append();
}


function wipeScreen(duration){
	var numElements = $(".curtain").children().length;
	var dt = duration/numElements;
	$(".curtain").children('div').each(function(j){
		i = numElements - j;
		if(debug) console.log($(this).height());
		$(this).fadeIn(dt*(i+1));
	});
	
	$(".curtain").children('div').each(function(j){
		i = numElements - j;
		if(debug) console.log($(this).height());
		$(this).fadeOut(dt*(i+1));
	});
	}



function loginSubmit(){
	if(debug) console.log("loginSubmit called");
	var dSend = new Object();
	dSend.email = $("#lUsername").val();
	dSend.password = $("#lPassword").val();
	
	if(debug){
		console.log("username: " + dSend.email);
		console.log("password: " + dSend.password);
	}
	
	if(dSend.email && dSend.password){
		$.ajax({
			type: "post",
			dataType: "json",
			data: dSend,
			url:submitUrlUser,
			complete: function(jqXHR, textStatus){
				if(debug) console.log("complete");
			},
			
			error: function(jqXHR, textStatus, errorThrown){
				if(debug) console.log("error");
				if(debug) console.log(textStatus);
				if(debug) console.log(jqXHR.responseText);
			},
			
			success: function(response){
				if(response.status === "success"){
					if(debug) console.log(response.token);
					token = response.token;
					$('body').pagecontainer('change',"#registerPage");
				}
				else{
					toast(response.status);
				}
//				var x = document.cookie;


			},
			
			
			
		});
		
	}
	else if(!dSend.email){
		toast("enter username");
	}else if(!dSend.password){
		toast("enter password");
	}
	
	
}


function registerSubmit(){
	if(debug) console.log("registerSubmit called");
	var dSend = new Object();
	
	dSend.first_name = $("#rFirstName").val();
	dSend.last_name = $("#rLastName").val();
	dSend.address = $("#rAddress").val();
	dSend.state = $("#rState").val();
	dSend.city = $("#rCity").val();
	dSend.zip = $("#rZipcode").val();
	dSend.email = $("#rEmail").val();
	dSend.token = token;
	
	if(dSend.email && dSend.first_name && dSend.last_name && dSend.address && dSend.state && dSend.city && dSend.zip){
		$.ajax({
			type: "post",
			dataType: "json",
			data: dSend,
			url:submitUrlForm,
			complete: function(jqXHR, textStatus){
				if(debug) console.log("complete");
			},
			
			error: function(jqXHR, textStatus, errorThrown){
				if(debug) console.log("error");
				if(debug) console.log(textStatus);
				if(debug) console.log(jqXHR.responseText);
			},
			
			success: function(response){
				if(response.status === "success"){
					toast("success");
				}
				else{
					toast(response.status);
				}
//				var x = document.cookie;


			},
			
			
			
		});
		
	}
	else {
		for(x in dSend) if(!dSend[x]) {
			toast("missing: " + x.replace("_"," "));
			break;
		}
	}
	
}

var toast=function(msg){
	$("<div class='ui-loader ui-overlay-shadow ui-body-e ui-corner-all'><h5>"+msg+"</h5></div>")
	.css({ display: "block", 
		opacity: 0.90, 
		position: "fixed",
		padding: "3px",
		"text-align": "center",
		width: "270px",
		left: ($(window).width() - 284)/2,
		top: $(window).height()*3/4 })
	.appendTo( $.mobile.pageContainer ).delay( 1500 )
	.fadeOut( 400, function(){
		$(this).remove();
	});
};



}());


