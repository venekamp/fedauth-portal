<?php
  require_once('/var/simplesamlphp/lib/_autoload.php');

  $as = new SimpleSAML_Auth_Simple('SURFconext');
  $as->requireAuth();
  $attributes = $as->getAttributes();

  $username = $attributes['urn:mace:dir:attribute-def:uid'][0];

  $output = array();
  $rt = 0;
  exec("sudo ./user_local.py ".$username , $output, $rt );

  $output = array();
  $rt = 0;
  exec("sudo ./get_secret.py ".$username , $output, $rt );
  $secret = $output[0];
?>

<!DOCTYPE html public "Gerard Braad"> 
<html manifest="cache.manifest">
    <head>
        <title>GAuth</title>
        <meta charset="utf-8">
        <meta name="description" content="GAuth Authenticator">
        <meta name="HandheldFriendly" content="True">
        <meta http-equiv="cleartype" content="on">
        <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1">
        <link rel="stylesheet" href="css/jquery.mobile-1.4.5.min.css" />
        <link rel="stylesheet" href="css/jquery.mobile-custom.min.css" />
        <link rel="stylesheet" href="css/styling.css" />
        <!-- purposely at the top -->

<script type="text/javascript">
    <?php echo 'var uid_str = "' . $username . '";'; ?>
    <?php echo 'var otp_secret = "' . $secret . '";'; ?>
</script>

        <script src="lib/jquery-2.1.3.min.js"></script>
        <script src="js/init.js"></script>
        <script src="lib/jquery.mobile-1.4.5.min.js"></script>
        <script src="lib/jssha-1.31.min.js"></script>
        <script src="js/gauth.js"></script>
        <script src="js/main.js"></script>
        <link rel="shortcut icon" type="image/x-icon" href="./favicon.ico" />
        <link rel="apple-touch-icon" href="img/icon_60.png" />
        <link rel="apple-touch-icon" sizes="120x120" href="img/icon_120.png">
        <link rel="apple-touch-icon" sizes="152x152" href="img/icon_152.png">
    </head>
    <body>
        <section data-role="panel" id="panel" data-position="left" data-theme="a" data-display="overlay">
            <ul data-role="listview">
                <li><a id="panelheader" href="#main" data-rel="close">&nbsp;</a></li>
                <li data-l10n-id="menu-keys" data-icon="lock"><a href="#main" data-rel="close">Keys</a></li>
                <li data-l10n-id="menu-settings" data-icon="gear"><a href="#settings" data-rel="close">Settings</a></li>
            </ul>
        </section>

        <section data-role="page" id="main" data-theme="a">
            <header data-role="header">
                <h1>
		            Welcome <?php echo $attributes['urn:mace:dir:attribute-def:displayName'][0]; ?>
                </h1>
            </header>

		    <p>Your assigned username is: <b><?php echo $attributes['urn:mace:dir:attribute-def:uid'][0]; ?></b></p>

<div data-role="tabs" id="tabs">
  <div data-role="navbar">
    <ul><li><a href="#otp">One Time Password</a></li><li><a href="#asp">Application Specific Password</a></li></ul>
  </div>

  <div id="otp">

                <ul data-role="listview" data-inset="true" data-theme="a" data-split-theme="a" data-split-icon="delete" id="accounts">
                    <li id="accountsHeader" data-l10n-id="title-keys" data-role="list-divider">One-time password<span class="ui-li-count" id='updatingIn'>..</span></li>
                </ul>

                <a id="addButton" data-l10n-id="keys-add" href="#add" data-role="button" class="ui-btn ui-icon-plus ui-btn-icon-left">Add</a>
	        <p>This password is valid for 1 single authentication use</p>
            </div>

  <div id="asp" >

	<p>Password: <b><span id="aspresult"></span></b></p>
	<input type="button" id="loadasp" data-inline="true" value="Generate password"/>
	<p>This password is valid until a new one is generated or your session has expired</p>
  </div>

        </section>

        <section data-role="page" id="add" data-theme="a">
            <header data-role="header">
                <h1 data-l10n-id="title-add">Add account</h1>
                <a href="#panel" class="header-icon" data-l10n-id="header-menu" data-role="button" data-iconpos="notext" data-icon="bars" data-iconpos="notext">Menu</a>
            </header>
            <div data-role="content">
                <p>
                    <form>
                        <label data-l10n-id="add-name" for="keyAccount">Account name:</label>
                        <input type="text" name="keyAccount" id="keyAccount" value="" autocorrect="off" autocapitalize="off" />
                        <label data-l10n-id="add-secret" for="keySecret">Secret key:</label>
                        <input type="text" name="keySecret" id="keySecret" value="" autocorrect="off" autocapitalize="off" />
                    </form>
                </p>
                <p>
                    <div data-role="controlgroup" data-type="horizontal">
                        <a id="addKeyButton" data-l10n-id="keys-add" data-role="button" class="ui-btn ui-icon-plus ui-btn-icon-left">Add</a>
                        <a id="addKeyCancel" data-l10n-id="add-cancel" href="#main" data-role="button" data-rel="back">Cancel</a>
                    </div>
                </p>
            </div>
        </section>

        <section data-role="page" id="settings" data-theme="a">
            <header data-role="header">
                <h1 data-l10n-id="title-settings">Settings</h1>
                <a data-l10n-id="header-menu" href="#panel" class="header-icon" data-role="button" data-iconpos="notext" data-icon="bars" data-iconpos="notext">Menu</a>
            </header>
            <div data-role="content">
                <p>
                    <a id="logout" data-l10n-id="logout" data-role="button" data-theme="a">Logout</a>
                </p>
            </div>
        </section>

    </body>
</html>
