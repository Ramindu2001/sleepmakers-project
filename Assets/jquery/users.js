let propic = document.getElementById("ilogo");
    let input = document.getElementById("propic");
    input.onchange = function() {
        propic.src = URL.createObjectURL(input.files[0]);
        propic.style.borderRadius = "50%";
        propic.style.height = "150px";
        propic.style.width = "150px";
        propic.style.boxShadow = "7px 5px 9px #000000b5";
    }
let epropic = document.getElementById("eilogo");
    let einput = document.getElementById("epropic");
    einput.onchange = function() {
        epropic.src = URL.createObjectURL(einput.files[0]);
        epropic.style.borderRadius = "50%";
        epropic.style.height = "150px";
        epropic.style.width = "150px";
        epropic.style.boxShadow = "7px 5px 9px #000000b5";
    }
$(document).ready(function(){
    $("#btn-add-user").click(function(){
        $("#add_user_modal").fadeIn();

    });
    $("#username").on("keyup", function () 
    {
        var username=$(this).val();
        $.ajax({
            url:'../AJAX/user/checkusername.php',
                method:'post',
                data:{
                    username: username
                    },
                success:function(response)
                {
                    
                }
            });    
    })
    $("body").on("click","#btn-edit-password", function(){
        $("#pasword_change_modal").fadeIn();
        var uid = $(this).closest('tr').find("#uid").val();
        var epwd=$('#e-pwd-change-id');
        epwd.val(uid);

    });
    $("body").on("click","#btn-user-edit", function(){
        $("#edit_user_modal").fadeIn();
        var uid = $(this).closest('tr').find("#uid").val();
        var uname = $(this).closest('tr').find("#uname").val();
        var uemail = $(this).closest('tr').find("#uemail").val();
        var ucontact = $(this).closest('tr').find("#ucontact").val();
        var urole_id = $(this).closest('tr').find("#urole_id").val();
        var paylimit = $(this).closest('tr').find("#paylimit").val();
        // paylimit = paylimit.toFixed(2);
        var urole = $(this).closest('tr').find("#urole_id").val();
        var upropic = $(this).closest('tr').find("#upropic").val();
        var ustat = $(this).closest('tr').find("#ustat").val();
        var euserid=$('#euserid');
        var eusername=$('#eusername');
        var euserEmail=$('#euserEmail');
        var euserContact=$('#euserContact');
        var edituserRole=$('#euserRole');
        var eprofile=$('#eprofile');
        var epaylimit=$('#epaylimit');
        var status=$('#status');
        $('#eilogo').attr("src","../Assets/Images/user_profile/"+upropic);
        euserid.val(uid);
        eusername.val(uname);
        euserEmail.val(uemail);
        euserContact.val(ucontact);
        edituserRole.val(urole);
        epaylimit.val(paylimit);
        eprofile.val(upropic);
        if (ustat==1) 
        {
          status.attr("checked","checked");  
        }
        else
        {
            status.removeAttr("checked");
        }

    });
    $("body").on("click","#btn-close",function(){
        $("#add_user_modal").fadeOut();
        $("#edit_user_modal").fadeOut();
        $("#pasword_change_modal").fadeOut();

    });
    $('#form-user').submit(function(){
        preventDefault();
        $("input").attr('required').each(function(){
            var required = $(this).val();
            if(required=="")
            {
                $(this).css("border-color","#ff0000");
                console.log(required);
            }
            else
            {
                
                $(this).css("border-color","green");
                console.log(required);
            }
        });
    });
    $('#cpassword').keyup(function(){
        var password=$("#password").val();
        var cpassword=$("#cpassword").val();
        var pno=$("#pno");
        var pyes=$("#pyes");
        if(password==cpassword)
        {
            pno.css("display","none");
            pyes.css("display","block");
        }
        else
        {
            pno.css("display","block");
            pyes.css("display","none");
        }

    });
    $('#cpassword').keydown(function(){
        var password=$("#password").val();
        var cpassword=$("#cpassword").val();
        var pno=$("#pno");
        var pyes=$("#pyes");
        if(password==cpassword)
        {
            pno.css("display","none");
            pyes.css("display","block");
        }
        else
        {
            pno.css("display","block");
            pyes.css("display","none");
        }

    });
    $('#e-cpassword').keyup(function(){
        var password=$("#epassword").val();
        var cpassword=$("#e-cpassword").val();
        var pno=$("#epno");
        var pyes=$("#epyes");
        if(password==cpassword)
        {
            pno.css("display","none");
            pyes.css("display","block");
            $('#chng-pwd').removeAttr("disabled");
        }
        else
        {
            pno.css("display","block");
            pyes.css("display","none");
            $('#chng-pwd').attr("disabled","disabled");
        }

    });
    $('#e-cpassword').keydown(function(){
        var password=$("#epassword").val();
        var cpassword=$("#e-cpassword").val();
        var pno=$("#epno");
        var pyes=$("#epyes");
        if(password==cpassword)
        {
            pno.css("display","none");
            pyes.css("display","block");
            $('#chng-pwd').removeAttr("disabled");
        }
        else
        {
            pno.css("display","block");
            pyes.css("display","none");
            $('#chng-pwd').attr("disabled","disabled");
        }

    });
    $('#epassword').keyup(function(){
        var password=$("#epassword").val();
        var cpassword=$("#e-cpassword").val();
        var pno=$("#epno");
        var pyes=$("#epyes");
        if(password==cpassword)
        {
            pno.css("display","none");
            pyes.css("display","block");
            $('#chng-pwd').removeAttr("disabled");
        }
        else
        {
            pno.css("display","block");
            pyes.css("display","none");
            $('#chng-pwd').attr("disabled","disabled");
        }

    });
    $('#epassword').keydown(function(){
        var password=$("#epassword").val();
        var cpassword=$("#e-cpassword").val();
        var pno=$("#epno");
        var pyes=$("#epyes");
        if(password==cpassword)
        {
            pno.css("display","none");
            pyes.css("display","block");
            $('#chng-pwd').removeAttr("disabled");
        }
        else
        {
            pno.css("display","block");
            pyes.css("display","none");
            $('#chng-pwd').attr("disabled","disabled");
        }

    });
    $("#s-password").click(function(){
        var password=$("#password");
        var cpassword=$("#cpassword");
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
    })
    $("#e-password").click(function(){
        var password=$("#epassword");
        var cpassword=$("#e-cpassword");
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
});