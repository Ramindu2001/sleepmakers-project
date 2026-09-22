<?php
//Cross-site request forgery protection: one random token per session, sent with every form
//and AJAX call that changes something and compared in constant time, so another site cannot
//submit those forms on behalf of a signed-in user.
function csrf_token()
{
    if(empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']))
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }//first use in this session

    return $_SESSION['csrf_token'];
}//csrf token

function csrf_validate($token)
{
    return is_string($token)
        && isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token']) && $_SESSION['csrf_token'] !== ''
        && hash_equals($_SESSION['csrf_token'], $token);
}//csrf validate
