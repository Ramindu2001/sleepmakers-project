<?php
//Enter a shop after a successful shop login (Controller/shopController.php).
//
//$session is $_SESSION, passed in so this can be tested. When a different person than the one
//signed in logs into the shop, nothing of the previous user's session carries over - it is as
//if they had signed out and in. Returns true in that case, so the caller can also clear the
//previous user's remember-me cookies.
function shop_session_enter(array &$session, array $user, $shop_id)
{
    $switched = !isset($session['user_id']) || (int)$session['user_id'] !== (int)$user['USID'];
    if($switched)
    {
        $csrf_token = isset($session['csrf_token']) ? $session['csrf_token'] : null;
        $session = array();
        if($csrf_token !== null)
        {
            $session['csrf_token'] = $csrf_token;
        }//the open forms stay valid

        unset($user['UserPwd'], $user['PwdChange']);
        $session['user_id'] = (int)$user['USID'];
        $session['user'] = array($user);
    }//another user takes over

    $session['shop_id'] = (int)$shop_id;
    $session['loading'] = 1;   //home.php shows the loading screen once
    $session['toast'] = 1;     //and its "Hi. Shop: ..." greeting
    return $switched;
}//shop session enter
