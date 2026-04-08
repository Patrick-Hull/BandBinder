<?php

require_once __DIR__ . "/../lib/util_all.php";

session_destroy();

?>
<!doctype html>
<html lang="en">
<head>
    <link rel="stylesheet" type="text/css" href="../lib/style/b3.css" />
    <style type="text/css">

        td
        {
            font: 12px Helvetica, Arial, sans-serif;
        }

    </style>
    <title>Logout</title>
</head>

<body>
<table cellpadding="0" cellspacing="0" width="100%" height="80%">
    <tr>
        <td align="center" valign="middle">
            <div style="border: 1px solid lightgray; width: 25em;">
                <p>You have successfully logged out</p>
                <p><a href="login.php">Return to login page</a></p>
            </div>
        </td>
    </tr>
</table>
</body>
</html>
