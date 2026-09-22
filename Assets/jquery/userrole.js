$(document).ready(function () 
    {
        $("#status").change(function () 
            {
                var status=$(this).val();
                var id=$("#role_id").val();
                // alert(status); 
                if (confirm("Are sure you want to proceed with the action?")) 
                {
                    $.ajax({
                        type: 'POST',
                        url: '../AJAX/UserRole/data.php',
                        data: 
                        {
                            id:id,
                            status:status
                        },  
                        success: function(data)  
                        {
                            $('#alert').html(data);
                        }
                    });
                }
                else
                {
                    location.reload(true);
                }
            }
        );
    }
);