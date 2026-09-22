$(document).ready(function(){
    setTimeout(function(){
        $("#toast").fadeIn();
        $("#toast").addClass("show");
      }, 500);
      setTimeout(function(){
        $("#toast").addClass("show showing");
      }, 3000);
      setTimeout(function(){
        $("#toast").removeClass("show showing");
        $("#toast").addClass("hide");
        $("#toast").fadeOut();
      }, 4000);
});