/*
Template Name: Admin Template
Author: Wrappixel

File: js
*/
// ==============================================================
// Auto select left navbar
// ==============================================================
$('body').keyup(function(e){
  if(e.keyCode == 192) // press `
  {
    window.open('../Public/wholesale-invoice.php', '_blank');
  }
  if(e.keyCode == 113) // press F2
  {
    $("#main-wrapper").toggleClass("mini-sidebar");
    if ($("#main-wrapper").hasClass("mini-sidebar")) {
      $(".sidebartoggler").prop("checked", !0);
      $("#main-wrapper").attr("data-sidebartype", "mini-sidebar");
    } else {
      $(".sidebartoggler").prop("checked", !1);
      $("#main-wrapper").attr("data-sidebartype", "full");
    }
    
    $("#main-wrapper").toggleClass("show-sidebar");
  }
});
$(function () {
    "use strict";
    var url = window.location + "";
    var path = url.replace(
      window.location.protocol + "//" + window.location.host + "/",
      ""
    );
    var element = $("ul#sidebarnav a").filter(function () {
      return this.href === url || this.href === path; // || url.href.indexOf(this.href) === 0;
    });
    element.parentsUntil(".sidebar-nav").each(function (index) {
      if ($(this).is("li") && $(this).children("a").length !== 0) {
        $(this).children("a").addClass("active");
        $(this).parent("ul#sidebarnav").length === 0
          ? $(this).addClass("active")
          : $(this).addClass("selected");
      } else if (!$(this).is("ul") && $(this).children("a").length === 0) {
        $(this).addClass("selected");
      } else if ($(this).is("ul")) {
        $(this).addClass("in");
      }
    });
  
    element.addClass("active");
    $("#sidebarnav a").on("click", function (e) {
      if ($(this).hasClass("has-arrow")) {
        e.preventDefault();
      }

      var $nextUl = $(this).next("ul");

      // If this link has a submenu
      if ($nextUl.length > 0) {
        if ($nextUl.hasClass("in") || $nextUl.hasClass("show")) {
          // Already open → close it
          $nextUl.removeClass("in show");
          $(this).removeClass("active");
        } else {
          // Close sibling menus at the same level
          var $parentUl = $(this).parents("ul:first");
          $parentUl.find("> li > a").not(this).removeClass("active");
          $parentUl.find("> li > ul").removeClass("in show");

          // Open this menu
          $nextUl.addClass("in");
          $(this).addClass("active");
        }
      }
    });
  });