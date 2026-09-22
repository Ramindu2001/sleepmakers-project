$(document).ready(function(){
  $("#openDelivery").click(function(){
    $("#delivery_modal").modal("toggle");
  })
    if ($(window).width()>900) 
    {
        $('#headerCollapse2').css("display","none");
        $('#headerCollapse3').css("display","block");
        $('#header-shortcuts').addClass("d-flex");
        $('#header-shortcuts').css("display","flex !important");
    }
    else
    {
        $('#headerCollapse2').css("display","block");
        $('#headerCollapse3').css("display","none");
        $('#header-shortcuts').removeClass("d-flex");
        $('#header-shortcuts').css("display","none");

    }
    $('#headerCollapse3').click(function(){
      if ($(window).width()>900) {
        $('.left-sidebar').css("margin-left","-270px");
        $('#headerCollapse2').css("display","block");
        $('#headerCollapse3').css("display","none");
      }
        
     
    });
    $('#headerCollapse2').click(function(){
      if ($(window).width()>900) {
        $('.left-sidebar').css("margin-left","0");
        $('#headerCollapse2').css("display","none");
        $('#headerCollapse3').css("display","block");
      }

    });
    $("#ue-password").click(function(){
      var password=$("#uepassword");
      var cpassword=$("#ue-cpassword");
      if(password.prop("type")=="text")
      {
          password.prop("type","password");
      }
      else
      {
          password.prop("type","text");
      }
      if(cpassword.prop("type")=="text")
      {
          cpassword.prop("type","password");
      }
      else
      {
          cpassword.prop("type","text");
      }
  });
  $('#uepassword').keyup(function(){
    var password=$("#uepassword").val();
    var cpassword=$("#ue-cpassword").val();
    var pno=$("#epno");
    var pyes=$("#epyes");
    if(password==cpassword)
    {
        pno.css("display","none");
        pyes.css("display","block");
        $('#echng-pwd').removeAttr("disabled");
    }
    else
    {
        pno.css("display","block");
        pyes.css("display","none");
        $('#echng-pwd').attr("disabled","disabled");
    }

});
$('#uepassword').keydown(function(){
    var password=$("#uepassword").val();
    var cpassword=$("#ue-cpassword").val();
    var pno=$("#uepno");
    var pyes=$("#uepyes");
    if(password==cpassword)
    {
        pno.css("display","none");
        pyes.css("display","block");
        $('#echng-pwd').removeAttr("disabled");
    }
    else
    {
        pno.css("display","block");
        pyes.css("display","none");
        $('#echng-pwd').attr("disabled","disabled");
    }

});
  $('#ue-cpassword').keyup(function(){
    var password=$("#uepassword").val();
    var cpassword=$("#ue-cpassword").val();
    var pno=$("#epno");
    var pyes=$("#epyes");
    if(password==cpassword)
    {
        pno.css("display","none");
        pyes.css("display","block");
        $('#echng-pwd').removeAttr("disabled");
    }
    else
    {
        pno.css("display","block");
        pyes.css("display","none");
        $('#echng-pwd').attr("disabled","disabled");
    }

});
$('#ue-cpassword').keydown(function(){
    var password=$("#uepassword").val();
    var cpassword=$("#ue-cpassword").val();
    var pno=$("#uepno");
    var pyes=$("#uepyes");
    if(password==cpassword)
    {
        pno.css("display","none");
        pyes.css("display","block");
        $('#echng-pwd').removeAttr("disabled");
    }
    else
    {
        pno.css("display","block");
        pyes.css("display","none");
        $('#echng-pwd').attr("disabled","disabled");
    }

});
    $('#sidebarCollapse').click(function(){
      if ($(window).width()>900) {
        $('.left-sidebar').css("margin-left","0");
        $('#headerCollapse2').css("display","none");
        $('#headerCollapse3').css("display","block");
      }
      else
      {
        // $('.left-sidebar').css("margin-left","-270px");
        $('#headerCollapse2').css("display","none");
        $('#headerCollapse3').css("display","block");
        
      }

    });
    $('body').on('click', '#user-password-change', function(){
      $('#user_pasword_change_modal').modal("toggle");
    });
    //open GRN modal
    $('body').on('click', '#add_grn', function(){
      $('#grn_modal').fadeIn();

      //get grn no
      $.get("../AJAX/GRN/getGRNNo.php", 
      function(data){
        $("#grn_no").val(data);
      });//get grn no

      var d = new Date();
      var strDate = d.getFullYear() + "/" + (d.getMonth()+1) + "/" + d.getDate();
      $("#grn_date").val(strDate);

    });//grn open

    $('body').on('click', '#close_grn_modal', function(){
      $('#grn_modal').fadeOut();
    });

    $('body').on('click', '#btn_close_counter', function(){
      // $('#close-bill-counter').fadeIn();
      $('#close-bill-counter').modal("toggle");
    });

    // $('body').on('click', '#btn_close_counter', function(){
    //   // $('#close-bill-counter').fadeIn();
    //   $('#close-bill-counter').modal("hide");
    // });

    $('body').on('click', '#btn_new_counter', function(){
      $("#start_amount").focus();
      $('#open-bill-counter').modal("toggle");
    });

    $('body').on('click', '#btn_close_bill_counter', function(){
      // $('body #close-bill-counter').modal("hide");
      // $('#open-bill-counter').modal("hide");
      $('#close-bill-counter').modal("hide");
    });

    $("#btn_upload").click(function(){
      alert("upload");
    });

    $('body').on('click', '#close_SysModule_modal', function(){
      $('#SysModule_modal').fadeOut(function() {
        // Fade out completed, now redirect
        window.location.href = '../Public/SystemModulesList.php'; });
    });

    $('body').on('click', '#close_SysFe_modal', function(){
      $('#SysFeature_modal').fadeOut(function() {
        // Fade out completed, now redirect
        window.location.href = '../Public/SystemFeaturesList.php'; });
    });
    

  //======================== denomination ========================//
  var counter_total = 0;
  var rs_5000 = 0;
  var rs_1000 = 0;
  var rs_500 = 0;
  var rs_100 = 0;
  var rs_50 = 0;
  var rs_20 = 0;
  var rs_10 = 0;
  var rs_5 = 0;
  var rs_2 = 0;
  var rs_1 = 0;

  $("#rs_5000").keyup(function(){
    var val_5000 = $(this).val() * 5000;
    $("p#val_5000").text(val_5000);
    getNoteTotal();
  });

  $("#rs_1000").keyup(function(){
    var val_1000 = $(this).val() * 1000;
    $("p#val_1000").text(val_1000);
    getNoteTotal();
  });

  $("#rs_500").keyup(function(){
    var val_500 = $(this).val() * 500;
    $("p#val_500").text(val_500);
    getNoteTotal();
  });

  $("#rs_100").keyup(function(){
    var val_100 = $(this).val() * 100;
    $("p#val_100").text(val_100);
    getNoteTotal();
  });

  $("#rs_50").keyup(function(){
    var val_50 = $(this).val() * 50;
    $("p#val_50").text(val_50);
    getNoteTotal();
  });

  $("#rs_20").keyup(function(){
    var val_20 = $(this).val() * 20;
    $("p#val_20").text(val_20);
    getNoteTotal();
  });

  $("#rs_10").keyup(function(){
    var val_10 = $(this).val() * 10;
    $("p#val_10").text(val_10);
    getNoteTotal();
  });

  $("#rs_5").keyup(function(){
    var val_5 = $(this).val() * 5;
    $("p#val_5").text(val_5);
    getNoteTotal();
  });

  $("#rs_2").keyup(function(){
    var val_2 = $(this).val() * 2;
    $("p#val_2").text(val_2);
    getNoteTotal();
  });

  $("#rs_1").keyup(function(){
    var val_1 = $(this).val() * 1;
    $("p#val_1").text(val_1);
    getNoteTotal();
  });

  //---------------- Counter close
  
  var counter_close_total = 0;
  var rs_5000_c = 0;
  var rs_1000_c = 0;
  var rs_500_c = 0;
  var rs_100_c = 0;
  var rs_50_c = 0;
  var rs_20_c = 0;
  var rs_10_c = 0;
  var rs_5_c = 0;
  var rs_2_c = 0;
  var rs_1_c = 0;

  $("#rs_5000_c").keyup(function(){
    var val_5000_c = $(this).val() * 5000;
    $("p#val_5000_c").text(val_5000_c);
    getCloseTotal();
  });

  $("#rs_1000_c").keyup(function(){
    var val_1000_c = $(this).val() * 1000;
    $("p#val_1000_c").text(val_1000_c);
    getCloseTotal();
  });

  $("#rs_500_c").keyup(function(){
    var val_500_c = $(this).val() * 500;
    $("p#val_500_c").text(val_500_c);
    getCloseTotal();
  });

  $("#rs_100_c").keyup(function(){
    var val_100_c = $(this).val() * 100;
    $("p#val_100_c").text(val_100_c);
    getCloseTotal();
  });

  $("#rs_50_c").keyup(function(){
    var val_50_c = $(this).val() * 50;
    $("p#val_50_c").text(val_50_c);
    getCloseTotal();
  });

  $("#rs_20_c").keyup(function(){
    var val_20_c = $(this).val() * 20;
    $("p#val_20_c").text(val_20_c);
    getCloseTotal();
  });

  $("#rs_10_c").keyup(function(){
    var val_10_c = $(this).val() * 10;
    $("p#val_10_c").text(val_10_c);
    getCloseTotal();
  });

  $("#rs_5_c").keyup(function(){
    var val_5_c = $(this).val() * 5;
    $("p#val_5_c").text(val_5_c);
    getCloseTotal();
  });

  $("#rs_2_c").keyup(function(){
    var val_2_c = $(this).val() * 2;
    $("p#val_2_c").text(val_2_c);
    getCloseTotal();
  });

  $("#rs_1_c").keyup(function(){
    var val_1_c = $(this).val() * 1;
    $("p#val_1_c").text(val_1_c);
    getCloseTotal();
  });

  //============== Function =============//
  function getNoteTotal()
  {
    var rs_5000 = $("#rs_5000").val() * 5000;
    var rs_1000 = $("#rs_1000").val() * 1000;
    var rs_500 = $("#rs_500").val() * 500;
    var rs_100 = $("#rs_100").val() * 100;
    var rs_50 = $("#rs_50").val() * 50;
    var rs_20 = $("#rs_20").val() * 20;
    var rs_10 = $("#rs_10").val() * 10;
    var rs_5 = $("#rs_5").val() * 5;
    var rs_2 = $("#rs_2").val() * 2;
    var rs_1 = $("#rs_1").val() * 1;

    counter_total = rs_5000 + rs_1000 + rs_500 + rs_100 + rs_50 + rs_20 + rs_10 + rs_5 + rs_5 + rs_2 + rs_1;
    
    // alert("counter " + counter_total);

    $("#start_amount").val(counter_total);
  }//get note total

  function getCloseTotal()
  { 
    var rs_5000_c = $("#rs_5000_c").val() * 5000;
    var rs_1000_c = $("#rs_1000_c").val() * 1000;
    var rs_500_c = $("#rs_500_c").val() * 500;
    var rs_100_c = $("#rs_100_c").val() * 100;
    var rs_50_c = $("#rs_50_c").val() * 50;
    var rs_20_c = $("#rs_20_c").val() * 20;
    var rs_10_c = $("#rs_10_c").val() * 10;
    var rs_5_c = $("#rs_5_c").val() * 5;
    var rs_2_c = $("#rs_2_c").val() * 2;
    var rs_1_c = $("#rs_1_c").val() * 1;

    counter_total = rs_5000_c + rs_1000_c + rs_500_c + rs_100_c + rs_50_c + rs_20_c + rs_10_c + rs_5_c + rs_5_c + rs_2_c + rs_1_c;
    
    // alert("counter " + counter_total);

    $("#end_amount").val(counter_total);
  }//get counter close total
   
  });