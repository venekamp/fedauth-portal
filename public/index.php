<?php
$FEDAUTH_ROOT =
    isset($_SERVER['FEDAUTH_ROOT'])
        ? $_SERVER[['FEDAUTH_ROOT']
        : '../fedauth-php';

if (chdir($FEDAUTH_ROOT) === FALSE) {
        echo(
            '<pre>'
            .  "Sorry, something went wrong."
            .'</pre>'
        );
        http_response_code(500);
} else {
    include('index.php');
}
