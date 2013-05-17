<?php
/**
 * This file demonstrates using Atlassian Crowd for Single Sign On.
 * Grabbed from https://gist.github.com/AsaAyers/1116177
 */
$crowd_app_name = 'appname';
$crowd_app_password = 'apppassword';
$crowd_url = 'http://localhost:8095/crowd/services/SecurityServer?wsdl';

// http://pear.php.net/package/Services_Atlassian_Crowd
require_once('Services/Atlassian/Crowd.php');
$username = NULL;

$crowd = new Services_Atlassian_Crowd(array(
    'app_name' => $crowd_app_name,
    'app_credential' => $crowd_app_password,
    'service_url' => $crowd_url,
));

$crowd->authenticateApplication();

$is_authenticated = FALSE;
if (!empty($_COOKIE['crowd_token_key']))
{
    // If the user already had a crowd token, we need to verify that it's still valid
    $is_authenticated = $crowd->isValidPrincipalToken(
        $_COOKIE['crowd_token_key'],
        $_SERVER['HTTP_USER_AGENT'],
        $_SERVER['REMOTE_ADDR']
    );
}
if (!$is_authenticated)
{
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="Crowd Login"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Forbidden.';
        exit;
    }

    try
    {
        $_COOKIE['crowd_token_key'] = $crowd->authenticatePrincipal(
            $_SERVER['PHP_AUTH_USER'],
            $_SERVER['PHP_AUTH_PW'],
            $_SERVER['HTTP_USER_AGENT'],
            $_SERVER['REMOTE_ADDR']
        );

        setcookie('crowd_token_key', $_COOKIE['crowd_token_key'], time() + 3600);

        $is_authenticated = TRUE;
    }
    catch (Services_Atlassian_Crowd_Exception $e)
    {
        // I have no idea why, but instead of throwing an
        // invalid username or password exception, we get
        // an exception with the username provided if either is wrong.
        if ($e->getMessage() == $_SERVER['PHP_AUTH_USER'])
        {
            // todo: prompt for login again
        }
        throw $e;
    }
}

if ($is_authenticated)
{
    $principal = $crowd->findPrincipalByToken($_COOKIE['crowd_token_key']);
    // Even though the user may have supplied a username, it's not case sensitive
    // and this will make sure the username is always consistent whether they signed
    // in using another application or they used http authentication.
    $username = $principal->name;
}

if (empty($username))
{
    header('HTTP/1.0 401 Unauthorized');
    echo 'Forbidden.';
    exit;
}

echo "Welcome $username, you do have access to this application.";
?>
