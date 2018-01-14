<!DOCTYPE html>
<html>
    <head>
        <style type="text/css">
        .image-size {
        max-width: 50%;
        height: auto;
        width: auto\9
        }
        </style>
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/css/materialize.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/js/materialize.min.js"></script>
    </head>
    <body>
        <div class="container">
            <center>
            <img class="image-size" src="http://168.235.96.252:8080/logo/logo.png" align="Middle">
            <h2>Confirm Your Travlendar Account</h2>
            <p class="flow-text">Thanks for creating a Travlendar account. We are happy you found us.<br>
            To confirm your account, please click the button below.</p>
            <a href="http://168.235.96.252:8080/v1/activate?token={{$token}}">
                <button class="btn waves-effect waves-light pulse" style="background-color: rgb(135, 207, 32)">
                <i class="material-icons right">keyboard_arrow_right</i>
                Confirm
                </button>
            </a>
            </center>
        </div>
    </body>
</html>