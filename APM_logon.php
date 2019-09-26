<?
// input fields configuration
$fields_settings = array(
    1 => ["type" => "text", "name" => "username", "varname" =>"username", "rw" => "1", "caption" => '%[logon_field_1]', "selectvalues" => "", "selectcust" => "%[selectvalues_1]"],
    2 => ["type" => "password", "name" => "password", "varname" =>"password", "rw" => "1", "caption" => '%[logon_field_2]', "selectvalues" => "", "selectcust" => "%[selectvalues_2]"],
    3 => ["type" => "none", "name" => "field3", "varname" => "field3", "rw" => "1", "caption" => '%[logon_field_3]', "selectvalues" => "", "selectcusts" => "", "selectcust" => "%[selectvalues_3]"],
    4 => ["type" => "none", "name" => "field4", "varname" => "field4", "rw" => "1", "caption" => '%[logon_field_4]', "selectvalues" => "", "selectcusts" => "", "selectcust" => "%[selectvalues_4]"],
    5 => ["type" => "none", "name" => "field5", "varname" => "field5", "rw" => "1", "caption" => '%[logon_field_5]', "selectvalues" => "", "selectcusts" => "", "selectcust" => "%[selectvalues_5]"]
);

// amount of fields
$field_num = 5;

// define(TNONE, ["type" => "none"]);
$TNONE = ["type" => "none"];

for( $i=1; $i <= $field_num; $i++ ){
    //
    foreach( $fields_settings[$i] as $fieldPostfix => $defaultValue ){
        //
        if( $fieldPostfix == "caption" ){
            continue;
        }

        if( isset( $_GET[ "f{$i}_{$fieldPostfix}" ] ) ){
            $fields_settings[$i][$fieldPostfix] =  $_GET[ "f{$i}_{$fieldPostfix}" ];
        }
    }
    //varname fix
    //Enable auto-population only for read-only text field
    $fields_settings[$i]["value"] = ( $fields_settings[$i]["rw"] == 1 || empty( $fields_settings[$i]["varname"] ) ?
        '' : '%{session.logon.last.'.$fields_settings[$i]["varname"].'}' );
}

$formHeader = '%[form_header]';

// add error message for CAPTCHA in case
if( isset( $_GET[ 'captcha_err_msg' ] ) || isset( $_GET[ 'captcha_only' ] )){
    $GLOBALS["dont_print_error_string"] = 1;
    include_customized_page("errormap", "errormap.inc");
    $formHeader = $GLOBALS["error_string"];
}

// this code is for challenge/response
// varname _F5_challenge is reserved post var name for challenge password
if ($challenge == 1) {
    // standard challenge
    $fields_settings = [
        1 => ["type" => "password", "name" => "_F5_challenge", "varname" => "password", "rw" => "1", "caption" => ""],
        $TNONE, $TNONE, $TNONE, $TNONE
    ];

    if ($errorcode == 0) {
        $vParam = $_GET['v'];
        if (!empty($vParam) && ($vParam == "v2")) {
            $formHeader = "%{subsession.logon.page.password.desc}";
        } else {
            $formHeader = "%{session.logon.page.password.desc}";
        }
    } else {
        $GLOBALS["dont_print_error_string"] = 1;
        include_customized_page("errormap", "errormap.inc");
        $formHeader = $GLOBALS["error_string"];

        // replace 5005, 5006, 5007 with No/Yes select pin change
        if(in_array($errorcode, [5005, 5006, 5007])){
            $fields_settings[1] = array_merge($fields_settings[1], ["type" => "select", "selectvalues" => "n;y", "selectcust" => "n=>%[no];y=>%[yes]"]);
        }else if($GLOBALS["set_new_password"] ){
            // hardcoded ad change password
            $fields_settings[1]["caption"] = "%[new_password]";
            $fields_settings[2] = ["type" => "password", "name" => "_F5_verify_password",  "varname" => "verify_password", "rw" => "1", "caption" => "%[verify_password]"];
        }

        // we should get error twice for common case and SoftToken case
        // because we don't know now is SoftToken extension is used
        $restoreErr = $errorcode;
        $errorcodeRemap = [5004 => 7004, 5013 => 7013, 5014 => 7014, 5016 => 7016];
        if(array_key_exists ($errorcode, $errorcodeRemap)){
            $errorcode = $errorcodeRemap[$errorcode];
        }

        $GLOBALS["dont_print_error_string"] = 1;
        include_customized_page("errormap", "errormap.inc");
        $formHeaderSoftToken = $GLOBALS["error_string"];
        $errorcode = $restoreErr;
    }

    // See if this is CitrixReceiver
    if ($_GET["ctype"] == "CitrixReceiver") {
        $formHeader = preg_replace("@</?\w+[^>]*>@", " ", $formHeader);
?>
<div id="dialogueStr"><?=$formHeader?></div>
<?
        exit;
    }
}

?><!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge" />
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<title>%{session.server.network.name}</title>
<link rel="stylesheet" type="text/css" HREF="/public/include/css/apm.css">
<script language="JavaScript" src="/public/include/js/session_check.js?v=13" ></script>
<script language="JavaScript" src="/public/include/js/agent_common.js" ></script>
<script language="JavaScript" src="/public/include/js/web_host.js" ></script>
<script language="javascript">
<!--
if(!String.prototype.trim){ String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ""); }; }

<? include_customized_page("logout", "session_expired.js"); ?>

var globalRestartOnSubmit = false;

function sessionTimedOut()
{
    // restart session automatically upon "submit" for edge-like clients if logon form is compatibile
    if (externalWebHost.hasWebLogonResetSession() && logonFormCompatible) {
       globalRestartOnSubmit = true;
    }
    else {
       // display session expired msg for non-compatible forms or
       // non-edge clients
       sessionTimeout.showSplashLayer("MessageDIV", SessionExpired_CustomizedScreenGet());
    }
}

/**
 * Place the focus and init the form fields
 */

if(self != top) { top.location = self.location; }
window.onerror=function(){ return function(){ return; } }

//--------------------------------------------------------------------
var doAutoSubmit = true;

var globalFormId = 'auth_form';
var globalTableId = 'credentials_table';
var globalSubmitTrId = 'submit_row';

var globalSavePasswordCheckbox = null;
var logonFormCompatible = false;

var softTokenInput = null;
var softTokenFieldId = "%{session.logon.page.softToken.fieldId}";
var softTokenState = "%{session.securid.last.state}";
var softTokenNewPIN = "%{session.securid.last.pin.value}";
var softTokenPINAutoPopulate = false;
if( !softTokenState ) {
    softTokenState = "SECURID_AUTH_STATE_START";<?//First hit at logon page?>
}

<?
if($challenge == 1){ ?>
    var challengeMode = true;
<? } else { ?>
    var challengeMode = false;
<?
}
?>

function getInputField(fieldId)
{
    var form = document.getElementById( globalFormId );
    if( form == null ){
        return null;
    }

    if(fieldId == null || fieldId == ""){
        return null;
    }

    var inputs = form.getElementsByTagName("input");
    for( var i=0; i<inputs.length; i++ ){
        if(  inputs[i].name == fieldId){
            return inputs[i];
        }
    }
    return null;
}

function getSoftTokenInput()
{
    return getInputField(softTokenFieldId);
}

function getUsernameInput()
{
    return getInputField("username");
}

function getPasswordInput()
{
    return getInputField("password");
}

function edgeClientSoftTokenSupport()
{
    try {
        return externalWebHost.hasWebLogonSoftTokenSupport();
    } catch(e) {}
    return false;
}

function getSoftTokenPrompt()
{
    if ( softTokenFieldId != "" && edgeClientSoftTokenSupport()) {
        var div = document.getElementById("formHeaderSoftToken");
        if (div)  {
            return div.innerHTML;
        }
    }
    return  null;
}

function setSoftTokenChallengeResponse(challengeResponse)
{
    var challengeElements = document.getElementsByName('_F5_challenge');
    if((challengeElements.length == 0) || (null == challengeElements[0])) {
        return;
    }

    var challengeElement = challengeElements[0];
    if(challengeElement.tagName.toLowerCase() == 'select') {
        var challengeOptions = challengeElement.options;
        if(null == challengeOptions){
            return;
        }
        for (var i = 0, optionsLength = challengeOptions.length; i < optionsLength; i++) {
            if (challengeOptions[i].value == challengeResponse) {
                challengeElement.selectedIndex = i;
                break;
            }
        }
    }else if(challengeElement.tagName.toLowerCase() == 'input') {
        challengeElement.value = challengeResponse;
    }
}

function OnSubmitEdgeRSASoftToken()
{
    var support = edgeClientSoftTokenSupport();
    if( true != support ) {
        return;
    }
    var form = document.getElementById( globalFormId );
    if( form == null ){
        return;
    }
    var inputs = form.getElementsByTagName("input");

    var hiddenInput = document.createElement("input");
    hiddenInput.setAttribute("type", "hidden");
    <? // softTokenError - reserved name for rsa soft token error session variable ?>
    hiddenInput.setAttribute("name", "softTokenError");
    //append to form element that you want .
    form.appendChild(hiddenInput);

    try{
        if(softTokenInput != null) { //normal mode
            // if the client cannot request soft-token PIN,
            // ask user to enter it in the logon page and pass the value to the client
            // Otherwise, the client already has the PIN
            if(!softTokenPINAutoPopulate) {
                externalWebHost.setWebLogonSoftTokenPIN(softTokenInput.value);
            }
            var passcode = externalWebHost.getWebLogonSoftTokenPasscode();
            if( passcode ) {
                softTokenInput.value = passcode;
            }
        } else if( challengeMode ) { //challenge mode
            // If the client can provide response to the challenge, use it instead of user input
            if(externalWebHost.hasWeblogonSoftTokenChallengeResponse()){
                var challengeResponse = externalWebHost.getWeblogonSoftTokenChallengeResponse();
                if(null != challengeResponse){
                     setSoftTokenChallengeResponse(challengeResponse);
                }
            }else{
                // Request user input by default
                externalWebHost.setWebLogonSoftTokenPIN(inputs[0].value);

                var passcode = externalWebHost.getWebLogonSoftTokenPasscode();
                if( passcode ) {
                    inputs[0].value = passcode;
                }
            }

        }
        hiddenInput.value = externalWebHost.getWebLogonSoftTokenError();
    } catch(e) { }

    return;
}

// check whether logon form is compatible with the client for auto-population and auto-submission
// only "username", "password" and soft token field (the name is stored in softTokenFieldId ) are supported
// fields can be configured in either order, but can only be text, password or checkbox types
// form is not considered compatible if it contains any additional fields not supported by the client
// The form is compatible even if it contains a subset of the supported fields
// In this case the client will auto-populate only those fields
function getFormCompatibility()
{
    var form = document.getElementById( globalFormId );
    if( form == null ){
        return false;
    }
    // check if form suites
    var inputs = [];
    var inputsTemp = form.getElementsByTagName("input");
    // filter submit, reset, hidden and little green men
    for( var i=0; i<inputsTemp.length; i++ ){
        if( inputsTemp[i].type == "text" || inputsTemp[i].type == "password"){
            inputs[ inputs.length ] = inputsTemp[i];
        }
    }

    var softTokenSupported = edgeClientSoftTokenSupport() && (null != softTokenInput);
    // Check if there are any custom fields that are not supported by Edge Client for auto-population and auto-submission
    for( var i=0; i<inputs.length; i++ ){
       if((inputs[i].type == "text" && inputs[i].name == "username") ||
          (inputs[i].type == "password" && inputs[i].name == "password") ||
          (softTokenSupported && inputs[i].type == "password" && inputs[i].name == softTokenInput.name)){
               continue;
       }else {
          return false;
       }
    }
    return true;
}

function setOrigUriLink()
{
    var params = parseQueryParams(window.location.search.substr(1));
    if (!params.hasOwnProperty('ORIG_URI')) {
        return;
    }

    var credTable = document.getElementById('credentials_table');
    if (credTable == null) {
        return;
    }
    var tBody = credTable.tBodies[0];
    if (tBody == null) {
        return;
    }

    var trTag = document.createElement("TR");
    tBody.insertBefore(trTag, tBody.children[tBody.children.length - 1]);

    var tdTag = document.createElement("TD");
    tdTag.setAttribute("class", "credentials_table_unified_cell");
    tdTag.setAttribute("colspan", "2");
    trTag.appendChild(tdTag);

    var tdText = document.createTextNode("<? print('%[logon_original_url]' );?>");
    tdTag.appendChild(tdText);

    var origUri = atob(decodeURIComponent(params['ORIG_URI']));
    var tdLink = document.createElement("A");
    tdLink.innerHTML = origUri;
    tdLink.setAttribute("href", origUri);
    tdTag.appendChild(tdLink);

}

function OnLoad()
{
    var header = document.getElementById("credentials_table_header");
    var softTokenHeaderStr = getSoftTokenPrompt();
    if ( softTokenHeaderStr ) {
        header.innerHTML = softTokenHeaderStr;
    }
    setFormAttributeByQueryParams("auth_form", "action", "/subsession_logon_submit.php3");
    setFormAttributeByQueryParams("v2_original_url", "href", "/subsession_logon_submit.php3");
    setOrigUriLink();

    // check if form suites
    var form = document.getElementById( globalFormId );
    if( form == null ){
        return;
    }
    // check if form suites
    var inputs = form.getElementsByTagName("input");
    // filter submit, reset, hidden and little green men
    for( var i=0; i<inputs.length; i++ ){
        if( ( inputs[i].type == "text" || inputs[i].type == "password" ) && inputs[i].value == "" ){
            inputs[i].focus();
            if (inputs[i].type == "password") {
                window.setTimeout( function(elem){ return function(){ elem.blur(); elem.focus(); } }(inputs[i]) , 266 );
            }
            return;
        }
    }
}

function disableSubmit(form)
{
    // disable!
    var inputs = form.getElementsByTagName( "input" );
    for( var i=0; i<inputs.length; i++ ){
        if( inputs[i].type == "submit" || inputs[i].type == "reset" ){
            inputs[i].disabled = true;
        }
    }

    return true;
}

//This function is called from Edge Client. Update setWeblogonCallbacks call if renamed
function challengeAutoSubmit()
{
    if(!challengeMode) {
       return false;
    }

    var form = document.getElementById( globalFormId );
    if( form == null ){
       return false;
    }

    disableSubmit(form);

    form.onsubmit();
    form.submit();
    return true;
}

//This function is called from Edge Client. Update setWeblogonCallbacks call if renamed
function weblogonAutoSubmit()
{
      if(!logonFormCompatible){
          return false;
      }

      var form = document.getElementById( globalFormId );
      if( form == null ){
          return false;
      }

      // autosubmit check
      if(externalWebHost.hasWebLogonAutoLogon() && externalWebHost.getWebLogonAutoLogon() && doAutoSubmit){

         disableSubmit(form);

         form.onsubmit();
         form.submit();
         return true;
      }else{
        return false;
      }
}


// support for autologon from Client API
function checkExternalAddCheckbox()
{
    var checkbox_txt = '%[save_password]';

    if( !logonFormCompatible ){
        try {
            if (externalWebHost.hasWebLogonNotifyUser()){
                externalWebHost.webLogonNotifyUser();
            }
        } catch(e){};
        return;
    }

    // find form
    var form = document.getElementById( globalFormId );
    if( form == null ){
        return;
    }

    // find table
    var table = document.getElementById( globalTableId );
    if( table == null ){
        return;
    }

    // find tr
    var submitTr = document.getElementById( globalSubmitTrId );
    if( submitTr == null ){
        return;
    }

    try {
        if(externalWebHost.isAvailable()){
            // push values
            var usernameInput = getUsernameInput();
            if( null != usernameInput && externalWebHost.hasWebLogonUserName() && usernameInput.value == ""){
                usernameInput.value = externalWebHost.getWebLogonUserName();
            }

            var passwordInput = getPasswordInput();
            <? // Allow password caching only if
               // RSA integration is not used for password field and
               // password field is not missing
            ?>
            var allowSavingPassword = ((null != passwordInput) && (passwordInput.name != softTokenFieldId));
<?
// only query ClientAPI for cached password value if we didn't encounter an error from previous logon attempt and if we are not in auth. challenge mode (RSA SecurID)
if ((0 == $challenge) && (0 == $errorcode ))
{

?>
            //don't populate RSA SecurID token field with the cached password
            if(allowSavingPassword) {
                if( externalWebHost.hasWebLogonPassword() && (passwordInput.value == "")){
                     passwordInput.value =  externalWebHost.getWebLogonPassword();
                }
            }else{
                doAutoSubmit = false;
            }
<? } else { ?>

            doAutoSubmit = false;
<? } ?>

            // push data to cells
            if(allowSavingPassword && externalWebHost.isWebLogonSavePasswordAvailable()){
                // right - text

                // create cells
                var newTr = table.insertRow( submitTr.rowIndex );
                var leftTd = newTr.insertCell( 0 );
                var rightTd = newTr.insertCell( 1 );

                leftTd.className = "credentials_table_label_cell";
                rightTd.className = "credentials_table_field_cell credentials_table_field_checkbox_fix";

                rightTd.innerHTML = checkbox_txt;
                // left - checkbox
                globalSavePasswordCheckbox = document.createElement("input");
                globalSavePasswordCheckbox.type = "checkbox";
                globalSavePasswordCheckbox.className = "credentials_input_checkbox";
                globalSavePasswordCheckbox.value = 1;
                globalSavePasswordCheckbox = leftTd.appendChild( globalSavePasswordCheckbox );
                globalSavePasswordCheckbox.checked = externalWebHost.getWebLogonSavePasswordChecked();
                if( globalSavePasswordCheckbox.autocomplete ) {
                    globalSavePasswordCheckbox.autocomplete = "off";
                }
            }

            // autosubmit if possible
            if(weblogonAutoSubmit()){
               return;
            }
        }
    } catch (e) { }
}

function OnSubmit()
{
    // find form
    var form = document.getElementById( globalFormId );
    if( form == null ){
        return;
    }
    try{
      if( externalWebHost.isAvailable() ){
         // pass weblogon credentials back to the client
         if( logonFormCompatible ){
              var usernameInput = getUsernameInput();
              if( null != usernameInput && externalWebHost.hasWebLogonUserName() ){
                  externalWebHost.setWebLogonUserName(usernameInput.value);
              }

              var passwordInput = getPasswordInput();
              if( null != passwordInput && externalWebHost.hasWebLogonPassword() ){
                  externalWebHost.setWebLogonPassword(passwordInput.value);
              }
              // pass user decision to save the password back to the client
              if( externalWebHost.hasWebLogonSavePasswordChecked() ){
                  externalWebHost.setWebLogonSavePasswordChecked((null != globalSavePasswordCheckbox) && globalSavePasswordCheckbox.checked);
              }
          }
          if (softTokenFieldId != "") {
              OnSubmitEdgeRSASoftToken();
          }
      }
    } catch(e) { }

    return;
}

function verifyNewPassword()
{
    var form = document.getElementById( globalFormId );
    if( form == null ){
        return true;
    }

    var inputs = form.getElementsByTagName("input");
    if( inputs.length >= 2 && inputs[0].name == "_F5_challenge" && inputs[0].type == "password" && inputs[1].name == "_F5_verify_password" && inputs[1].type == "password" ){
        if( inputs[0].value != inputs[1].value ){
            alert("%[wrong_match]");
            inputs[0].focus();
            return false;
        } else {
            // Not sending the second field.
            inputs[1].disabled = true;

            try{
                  if( externalWebHost.hasWebLogonPassword() ){
                      externalWebHost.setWebLogonPassword(inputs[0].value);
                  }
            } catch(e) { }
        }
    }
    return true;
}

function masterSubmit(form)
{
    if( !verifyNewPassword() /* || ... */){
        return false;
    }

    OnSubmit(); // this required by edge

    if (globalRestartOnSubmit) {
        try {
            var usernameInput = getUsernameInput();
            var passwordInput = getPasswordInput();
            externalWebHost.setWebLogonAutoLogon((usernameInput != null ) && ("" != usernameInput.value) && (null != passwordInput) && ("" != passwordInput.value));
        }
        catch (e) {}
        // restart session
        externalWebHost.webLogonResetSession();
        return false;
    }

    disableSubmit(form);
    return true;
}
//-->
</script>
</head>

<body onload="OnLoad()">

<?
include_customized_page("general_ui", "header.inc");
include_customized_page("general_ui", "javascript_disabled.inc");
?>

<table id="main_table" class="logon_page">
<tr>
    <? if( $GLOBALS["page_layout"] == "form_right" ){
        ?><td id="main_table_image_cell"><img src="<? print('%[front_image]'); ?>"></td><?
    } ?>
    <td id="main_table_info_cell">
    <form id="auth_form" name="e1" method="post" onsubmit="javascript: return masterSubmit(this);" autocomplete="off">
    <table id="credentials_table">
    <tr>
        <td colspan=2 id="credentials_table_header" ><? echo $formHeader; ?></td>
    </tr>
    <tr>
        <td colspan=2 id="credentials_table_postheader" ><? if ($retry == 1) { include_customized_page("errormap", "errormap.inc"); } ?></td>
    </tr>
<?

if( !isset( $_GET[ 'captcha_only' ] ) ) {

//------------------------------------------------------------
foreach( $fields_settings as $id=>$field_settings )
{
    // skip nne
    if( $field_settings["type"]== "none" ){ continue; }
    // determine disabled
    $disabled = ( $field_settings["rw"] == 0 ? "disabled" : "" );
    $_disabled = ( $disabled == "" ? "" : "_{$disabled}" );

    $options = explode( ';', $field_settings["selectvalues"] );
    $fieldStr = "";
    $labelStr = "<label for='input_{$id}' id='label_input_{$id}'>{$field_settings["caption"]}</label>";

    switch( $field_settings["type"] ){
        case "select":
            foreach( $options as &$o ){
                $fieldStr .= "<option value=\"{$o}\" ".( $field_settings["value"] == $o ? 'selected' : '' ).">{$o}</option>";
            }
            $fieldStr = "<select name='{$field_settings["name"]}' id='input_{$id}' class='credentials_input_{$field_settings["type"]}{$_disabled}' {$disabled}>{$fieldStr}</select>";
        break;
        case "radio":
            foreach( $options as $k=>&$o ){
                $fieldStr .= "<div class='radio-div'><input type='radio' value=\"{$o}\" name='{$field_settings["name"]}' id='input_{$id}_{$k}' ".( $field_settings["value"] == $o ? "checked" : "" )." {$disabled}/><label for='input_{$id}_{$k}' id='label_input_{$id}_{$k}' class='radio-label' style='display: inline'>{$o}</label></div>";
            }
        break;
        case "checkbox":
        case "text":
        case "password":
            $fieldStr = "<input type='{$field_settings["type"]}' name='{$field_settings["name"]}' class='credentials_input_{$field_settings["type"]}{$_disabled}' value='{$field_settings["value"]}' id='input_{$id}' autocomplete='off' autocapitalize='off' {$disabled}/>";
        break;
    }

    if( $field_settings["type"] == "hidden" ){
?><tr style="height: 0px"><td colspan=2><? print( $fieldStr ); ?></td></tr><?
        continue;
    }

    if( $GLOBALS["label_position"] == "above" ){
?>
    <tr>
        <td colspan=2 class="credentials_table_unified_cell" ><? print( $labelStr ); print( $fieldStr ); ?></td>
    </tr>
<?
    }else{
?>
    <tr>
        <td class="credentials_table_label_cell" ><? print( $labelStr ); ?></td>
        <td class="credentials_table_field_cell"><? print( $fieldStr ); ?></td>
    </tr>
<?
    }
}
}
if(isset($_GET['captcha_site_key'])){
    $captchaTheme = !isset($_GET['captcha_theme']) ? "light" : $_GET['captcha_theme'];
    $captchaType = !isset($_GET['captcha_type']) ? "image" : $_GET['captcha_type'];
    $captchaSize = !isset($_GET['captcha_size']) ? "compact" : $_GET['captcha_size'];
?>
    <tr>
    <td colspan=2 class="credentials_table_unified_cell">
    <script src="https://<?=$_GET['captcha_challenge_url']?>" async defer></script>
    <div class="g-recaptcha" data-sitekey="<?=$_GET['captcha_site_key']?>"
        data-theme="<?=$captchaTheme?>" data-type="<?=$captchaType?>" data-size="<?=$captchaSize?>"></div>
    <noscript>
    <div>
        <div style="width: 302px; height: 422px; position: relative;">
            <div style="width: 302px; height: 422px; position: absolute;">
                <iframe src="<?=$_GET['captcha_noscript_url']?>?k=<?=$_GET['captcha_site_key']?>"
                    frameborder="0" scrolling="no" style="width: 302px; height:422px; border-style: none;">
                </iframe>
            </div>
        </div>
        <div style="width: 300px; height: 60px; border-style: none; bottom: 12px; left: 25px; margin: 0px; padding: 0px; right: 25px; background: #f9f9f9; border: 1px solid #c1c1c1; border-radius: 3px;">
            <textarea id="g-recaptcha-response" name="g-recaptcha-response" class="g-recaptcha-response"
                style="width: 250px; height: 40px; border: 1px solid #c1c1c1; margin: 10px 25px; padding: 0px; resize: none;" />
        </div>
    </div>
    </noscript>
    </td>
    </tr>
<?
}

//------------------------------------------------------------
    if( $challenge == 1 && ($errorcode == 5022 || $errorcode == 5023) && $GLOBALS["set_new_password"] ){
?>
    <tr>
<?
        if( $GLOBALS["label_position"] == "above" ){
?>
        <td class="credentials_table_unified_cell"><label for='dont_change_password_checkbox' id='label_input_dont_change_password_checkbox'>%[dont_change_password]</label><input type=checkbox id="dont_change_password_checkbox" value="" onclick="javascript: dontChangePasswordClick();"></td>
<?
        }else{
?>
        <td class="credentials_table_label_cell" ><label for='dont_change_password_checkbox' id='label_input_dont_change_password_checkbox'>%[dont_change_password]</label></td>
        <td class="credentials_table_field_cell"><input type=checkbox id="dont_change_password_checkbox" value="" onclick="javascript: dontChangePasswordClick();"></td>
<?
        }
?>
    </tr>
<?
    }
//------------------------------------------------------------

?>
    <tr id="submit_row">
<?
        if( $GLOBALS["label_position"] == "above" ){
?>
        <td class="credentials_table_unified_cell"><input type=submit class="credentials_input_submit" value="%[logon]"></td>
<?
        }else{
?>
        <td class="credentials_table_label_cell" ></td>
        <td class="credentials_table_field_cell"><input type=submit class="credentials_input_submit" value="%[logon]"></td>
<?
        }
?>
    </tr>
<?
    $vParam = $_GET['v'];
    if (!empty($vParam) && ($vParam == "v2")) {
?>
    <tr>
        <td class="credentials_table_unified_cell" colspan="2">
        <a id="v2_original_url" href="/">%[logon_original_url]</a>
        </td>
    </tr>
<?
    }
?>
    <tr>
        <td colspan=2 id="credentials_table_footer" ></td>
    </tr>
    </table>
    <input type=hidden name="vhost" value="standard">
    </form>
    <script language="javascript"><!--//
        softTokenInput = getSoftTokenInput();
        logonFormCompatible = getFormCompatibility();
        // Pass method names to the client for auto-submit triggered by the client
        externalWebHost.setWeblogonCallbacks(
             "weblogonAutoSubmit();",
             "challengeAutoSubmit();"
        );

        // Check if the softTokenInput exists or in case of RSA challenge mode
        if((softTokenFieldId != "") && (null != softTokenInput || challengeMode ) && edgeClientSoftTokenSupport()){
            externalWebHost.setWebLogonSoftTokenPrompt(getSoftTokenPrompt());
            externalWebHost.setWebLogonSoftTokenState(softTokenState);
        }

        // Check if the client can ask user for soft-token PIN
        if((softTokenFieldId != "") && (softTokenInput != null)
               && edgeClientSoftTokenSupport()
               && externalWebHost.canRequestWeblogonSoftTokenPIN()){
            softTokenInput.readOnly = true;
            softTokenPINAutoPopulate = true;
            // No need to request input of RSA PIN, as the client can show PIN input UI if needed,
            // make the field read-only
            // set bogus value to display
            softTokenInput.value = "********";
        }

        checkExternalAddCheckbox();
        setTimeout(function(){ window.sessionTimeout = new APMSessionTimeout(sessionTimedOut); }, 200);

        function dontChangePasswordClick(){
            var checkbox = document.getElementById("dont_change_password_checkbox");
            var password = document.getElementById("input_1");
            var verify = document.getElementById("input_2");
            if( checkbox.checked ){
                password.value = "";
                password.disabled = true;
                verify.value = "";
                verify.disabled = true;
            }else{
                password.disabled = false;
                verify.disabled = false;
            }
        }

        var finitvalues=[<? for($i=1; $i<=$field_num; $i++){ echo(($i > 1 ? ',' : '')."'{$fields_settings[$i]["value"]}'" ); }?>];
        var sessionLogonCustomizations = [<? for($i=1; $i<=$field_num; $i++){ echo(($i > 1 ? ',' : '')."\"{$fields_settings[$i]['selectcust']}\"" ); }?>];
        var sessionLogonCustomizationPairs = [[], [], [], [], []];
        for( var i=0; i<sessionLogonCustomizations.length; i++ ){
            var pairs = ( sessionLogonCustomizations[i].indexOf(";") == -1 ? [ sessionLogonCustomizations[i] ] : sessionLogonCustomizations[i].trim().split(";") );
            for( var j=0; j<pairs.length; j++ ){
                if( pairs[j].indexOf("=>") != -1 ){
                    var pair = pairs[j].split("=>");
                    sessionLogonCustomizationPairs[i][pair[0]] = pair[1];
                }
            }
        }

        var sessionLogonValuesets = [ "%{session.logon.last.selectvalues_1}", "%{session.logon.last.selectvalues_2}", "%{session.logon.last.selectvalues_3}", "%{session.logon.last.selectvalues_4}", "%{session.logon.last.selectvalues_5}" ];
        for( var i=0; i<sessionLogonValuesets.length; i++ ){
            var inpx = document.getElementById("input_" + (i+1) );
            if( inpx !== null && inpx.tagName.toUpperCase() == "SELECT" ){
                if( sessionLogonValuesets[i].trim() != "" ){
                    inpx.options.length = 0;
                    var options = sessionLogonValuesets[i].trim().split(";");
                    for( var j=0; j<options.length; j++ ){
                        inpx.options.add( new Option( options[j], options[j] ) );
                    }
                }
                if( sessionLogonCustomizations[i].trim() != "" ){
                    for( var j=0; j<inpx.options.length; j++ ){
                        if( typeof sessionLogonCustomizationPairs[i][ inpx.options[j].value ] != "undefined" ){
                            inpx.options[j].text = sessionLogonCustomizationPairs[i][ inpx.options[j].value ];
                        }
                    }
                }
                // set value
                for( var j=0; j<inpx.options.length; j++ ){
                    if( inpx.options[j].value == finitvalues[i] ){
                        inpx.value = finitvalues[i];
                        break;
                    }
                }
            }else if( document.getElementById("input_" + (i+1) + "_0" ) ){ // RADIO
                var initValue = finitvalues[i];
                var radio = null;
                var name = document.getElementById("input_" + (i+1) + "_0" ).name;
                if( sessionLogonValuesets[i].trim() != "" ){
                    var parent = document.getElementById( "label_input_" + (i+1) ).parentNode;
                    while( parent.childNodes.length > 1 ){
                        parent.removeChild( parent.lastChild );
                    }
                    var options = sessionLogonValuesets[i].trim().split(";");
                    for( var j=0; j<options.length; j++ ){
                        var div = parent.appendChild( document.createElement( "div" ) );
                        var elmdef = document.all && navigator.userAgent.match(/MSIE (\d+)/)[1] < 9 ? "<input type='radio'>" : "input";
                        var input = div.appendChild( document.createElement( elmdef ) );
                        if( input.type != 'radio' ){ input.type = 'radio'; }
                        input.id = "input_" + (i+1) + "_" + j;
                        input.name = "name";
                        input.value = options[j];
                        var label = div.appendChild( document.createElement( "label" ) );
                        label.htmlFor = input.id;
                        label.id = "label_" + input.id;
                        label.className = "radio-label";
                        label.style.display = "inline";
                        label.innerHTML = options[j];
                    }
                }
                if( sessionLogonCustomizations[i].trim() != "" ){
                    var j = 0;
                    while( ( radio = document.getElementById("input_" + (i+1) + "_" + j++ ) ) !== null ){ // what number
                        if( typeof sessionLogonCustomizationPairs[i][ radio.value ] != "undefined" ){
                            document.getElementById("label_input_" + (i+1) + "_" + (j-1) ).innerHTML = sessionLogonCustomizationPairs[i][ radio.value ];
                        }
                    }
                }
                var anyChecked = false, j = 0;
                while( initValue != "" && ( radio = document.getElementById("input_" + (i+1) + "_" + j++ ) ) !== null ){
                    radio.checked = ( radio.value == initValue );
                    if( radio.checked ){ anyChecked = true; }
                }
                if( !anyChecked && ( radio = document.getElementById("input_" + (i+1) + "_" + 0 ) ) !== null ){
                    radio.checked = true;
                }
            }else if( inpx !== null && inpx.tagName.toUpperCase() == "INPUT" && inpx.type.toUpperCase() == "CHECKBOX" ){
                if( inpx.disabled && finitvalues[i] != "" ){
                    inpx.checked = true;
                }else if( !inpx.disabled && inpx.value == "" ){
                    inpx.value = "1";
                }
            }
        }
    --></script>
    </td>
    <? if( $GLOBALS["page_layout"] == "form_left" ){
        ?><td id="main_table_image_cell"><img src="<? print('%[front_image]'); ?>"></td><?
    } ?>
</tr>
</table>

<? include_customized_page("general_ui", "footer.inc"); ?>

<? include_once("sam/webtop/renderer/vk.inc");  ?>

<div id="MessageDIV" class="inspectionHostDIVSmall"></div>
<div id="formHeaderSoftToken" style="overflow: hidden; visibility: hidden; height: 0; width: 0;"><? echo $formHeaderSoftToken; ?></div>
</body>
</html>
