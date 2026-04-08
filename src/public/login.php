<?php
require_once __DIR__ . '/../lib/util_all.php';
$pageName = "Login";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $pageName; ?></title>
    <?php require_once __DIR__ . '/../lib/html_header/all.php'; ?>
</head>
<body>
<div class="container-fluid mt-4">
    <div class="row mt-4">
        <div class="col-3 mx-auto">
            <div class="card">
                <div class="card-body">

                    <form id="loginForm" action="lib/login.php">
                        <div class="form-floating mb-3" id="inputUsername">
                            <input type="text" class="form-control" id="username" name="username">
                            <label for="username">Username</label>
                        </div>

                        <div class="form-floating mb-3" id="inputPassword">
                            <input type="password" class="form-control" id="password" name="password">
                            <label for="password">Password</label>
                        </div>

                        <div class="form-floating mb-3" id="inputTotp" style="display: none">
                            <input type="number" class="form-control" id="totp" name="totp">
                            <label for="totp">2FA Code</label>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" type="submit">Login</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
    <?php require_once __DIR__ . '/../lib/html_footer/all.php'; ?>
</div>
</body>
<script>
    let totpSecret
    $("#loginForm").on("submit", function(e){
        e.preventDefault();
        let form = $(this);
        let actionUrl = form.attr('action');

        $.ajax({
            type: "POST",
            url: actionUrl,
            data: form.serialize(),
            dataType: "JSON",
            success: function(data){
                if(data.error === "2fa_required"){
                    toastr.warning(data.message)
                    $("#inputTotp").show();
                    $("#inputUsername").hide();
                    $("#inputPassword").hide();
                } else {
                    window.location.href = "/"
                }
            },
            error: function(xhr, status, error){
                let response = xhr.responseJSON
                if(response.error === "2fa_missing"){
                    toastr.warning(response.message)
                    totpSecret = response.secret
                    $("#2fa_qrcode").attr("src", response.qr)
                    $("#2fa_secret").text(totpSecret)
                    $('#totpEnable').modal('show');
                } else {

                    toastr.error(response.message)
                }
            }
        });
    })

    $("#totpVerificationSubmit").on("click", function(e){
        e.preventDefault();
        console.log(totpSecret)
        let totpCode = $("#totpVerificationTextbox").val();
        $.ajax({
            type: "POST",
            url: "lib/login.php",
            data: {totp: totpCode, totpSecret: totpSecret, username: $("#username").val(), password: $("#password").val()},
            dataType: "JSON",
            success: function(data){
                window.location.href = "/"
            }
        })
    })

</script>
</html>