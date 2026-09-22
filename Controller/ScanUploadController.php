<?php
//The scanner upload dialog's endpoint (Assets/jquery/scan_upload.js) - see
//docs/superpowers/specs/2026-09-22-scanner-upload-design.md, section 10.
//POST only, from a signed-in user still allowed in the session's shop, with the page's CSRF
//token. Every answer is JSON: {ok, message, preview?, confirm?, result?}.
include "../Includes/includes.php";
require_once "../Includes/csrf.php";
require_once "../Includes/scan_upload.php";

header('Content-Type: application/json; charset=utf-8');

function scan_respond($status, array $body)
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}//respond

//the user's choices in the dialog, in the shape the models expect
function scan_decisions()
{
    $list = function($key) {
        return (isset($_POST[$key]) && is_array($_POST[$key])) ? $_POST[$key] : [];
    };
    return [
        'leave_out' => array_values(array_filter($list('leave_out'), 'is_string')),
        'prices' => $list('prices'),
        'dates' => $list('dates'),
        'rack_id' => isset($_POST['rack_id']) ? (int)$_POST['rack_id'] : 0,
        'confirm_duplicate' => !empty($_POST['confirm_duplicate']),
        'confirm_short' => !empty($_POST['confirm_short']),
    ];
}//decisions

//the preview as the page may see it: without what only apply() needs (batch parts, line ids)
function scan_public_preview(?array $preview)
{
    if($preview === null)
    {
        return null;
    }
    unset($preview['groups']);
    foreach($preview['lines'] as &$line)
    {
        unset($line['parts']);
    }
    unset($line);
    return $preview;
}//public preview

if(!isset($_SESSION['user_id'], $_SESSION['shop_id']) || !(new ShopAccess())->canAccessShop($_SESSION['user_id'], $_SESSION['shop_id']))
{
    scan_respond(403, ['ok' => false, 'message' => 'Please sign in to the shop again.']);
}//not signed in to this shop

if($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null))
{
    scan_respond(400, ['ok' => false, 'message' => 'Your session expired. Please reload the page and try again.']);
}//not a post from our page

$user_id = (int)$_SESSION['user_id'];
$shop_id = (int)$_SESSION['shop_id'];
$doc_id = isset($_POST['doc_id']) ? (int)$_POST['doc_id'] : 0;
$raw = (isset($_POST['raw']) && is_string($_POST['raw'])) ? $_POST['raw'] : '';
$action = isset($_POST['action']) ? $_POST['action'] : '';
$context = isset($_POST['context']) ? $_POST['context'] : '';
$decisions = scan_decisions();

try
{
    if($context === 'grn')
    {
        $model = new GrnScan();
        $check = function() use ($model, $doc_id, $shop_id, $user_id, $raw, $decisions) { return $model->preview($doc_id, $shop_id, $user_id, $raw, $decisions); };
        $apply = function() use ($model, $doc_id, $shop_id, $user_id, $raw, $decisions) { return $model->apply($doc_id, $shop_id, $user_id, $raw, $decisions); };
    }
    elseif($context === 'transfer_send')
    {
        $model = new TransferScan();
        $check = function() use ($model, $doc_id, $shop_id, $user_id, $raw, $decisions) { return $model->sendPreview($doc_id, $shop_id, $user_id, $raw, $decisions); };
        $apply = function() use ($model, $doc_id, $shop_id, $user_id, $raw, $decisions) { return $model->sendApply($doc_id, $shop_id, $user_id, $raw, $decisions); };
    }
    elseif($context === 'transfer_receive')
    {
        $model = new TransferScan();
        $check = function() use ($model, $doc_id, $shop_id, $user_id, $raw, $decisions) { return $model->receivePreview($doc_id, $shop_id, $user_id, $raw, $decisions); };
        $apply = function() use ($model, $doc_id, $shop_id, $user_id, $raw, $decisions) { return $model->receiveApply($doc_id, $shop_id, $user_id, $raw, $decisions); };
    }
    else
    {
        scan_respond(400, ['ok' => false, 'message' => 'Unknown upload.']);
    }//contexts of later tasks are added above this line

    if($action === 'check')
    {
        scan_respond(200, ['ok' => true, 'message' => '', 'preview' => scan_public_preview($check())]);
    }
    if($action === 'apply')
    {
        $done = $apply();
        scan_respond(200, ['ok' => true, 'message' => $done['message'], 'result' => $done['result']]);
    }
    scan_respond(400, ['ok' => false, 'message' => 'Unknown action.']);
}
catch(ScanRefused $e)
{
    $body = ['ok' => false, 'message' => $e->getMessage()];
    if($e->preview !== null)
    {
        $body['preview'] = scan_public_preview($e->preview);
    }
    if($e->confirm !== null)
    {
        $body['confirm'] = $e->confirm;
    }
    scan_respond($e->status, $body);
}
catch(Throwable $e)
{
    error_log('ScanUploadController: ' . $e->getMessage());
    scan_respond(500, ['ok' => false, 'message' => 'Something went wrong. Nothing was saved.']);
}//catch
