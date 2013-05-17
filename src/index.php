<!-- A simple form. -->
<a href="sso.php">Who am I logged in as?</a><br/><br/>

<form action="index.php" method="post">
    <b>Create User</b><br/>
    Username: <input type="text" name="username"></input><br/>
    Password: <input type="password" name="password"></input><br/>
    First Name: <input type="text" name="firstName"></input><br/>
    Last Name: <input type="text" name="lastName"></input><br/>
    Display Name: <input type="text" name="displayName"></input><br/>
    E-mail: <input type="text" name="email"></input><br/>
    <input type="submit" name="createUser" value="Submit"></input><br/>
</form>

<form action="index.php" method="post">
    <b>Modify User</b><br/>
    Username: <input type="text" name="username"></input><br/>
    Password: <input type="password" name="password"></input><br/>
    First Name: <input type="text" name="firstName"></input><br/>
    Last Name: <input type="text" name="lastName"></input><br/>
    Display Name: <input type="text" name="displayName"></input><br/>
    E-mail: <input type="text" name="email"></input><br/>
    <input type="submit" name="modifyUser" value="Submit"></input><br/>
</form>

<form action="index.php" method="post">
    <b>Delete User</b><br/>
    Username: <input type="text" name="username"></input><br/>
    <input type="submit" name="deleteUser" value="Submit"></input><br/>
</form>

<form action="index.php" method="get">
    <b>Retrieve User</b><br/>
    Username: <input type="text" name="username"></input><br/>
    <input type="submit" name="retrieveUser" value="Submit"></input><br/>
</form>

<form action="index.php" method="post">
    <b>Assign Favourite Food</b><br/>
    Username: <input type="text" name="username"></input><br/>
    Food: <input type="text" name="favouriteFood"></input><br/>
    <input type="submit" name="assignFavouriteFood" value="Submit"></input></br>
</form>

<form action="index.php" method="get">
    <b>Retrieve Favourite Food</b><br/>
    Username: <input type="text" name="username"></input><br/>
    <input type="submit" name="retrieveFavouriteFood" value="Submit"></input></br>
</form>

<?php

// Performs a request against the Crowd REST API and returns the result.
function crowd_rest_request($url, $content_type, $method, $method_field = ""){
    $curl = curl_init();

    // Set common parameters.
    curl_setopt($curl, CURLOPT_URL,
                "http://localhost:8095/crowd/rest/usermanagement/latest/" . $url);
    curl_setopt($curl, CURLOPT_USERPWD, "appname:apppassword");
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

    // Set content type.
    if (strtolower($content_type) === 'json'){
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json",
                                                     "Accept: application/json"));
    }
    else if (strtolower($content_type) === 'xml'){
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/xml",
                                                     "Accept: application/xml"));
    }
    else throw new Exception("Bad request -- invalid content type: " . $content_type);

    // Set method.
    if (strtolower($method) === 'get'){}
    else if (strtolower($method) === 'post'){
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $method_field);
    }
    else if (strtolower($method) === 'put'){
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $method_field);
    }
    else if (strtolower($method) === 'delete'){
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
    }
    else throw new Exception("Bad request -- invalid method: " . $method);

    // Perform request.
    $return = curl_exec($curl);

    // If status code is not between 200 and 206, throw an exception.
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($http_status < 200 || $http_status > 206){
        throw new Exception("Bad request -- HTTP status code: " . $http_status);
    }

    curl_close($curl);
    return $return;
}

function user_xml_data(){
    return
    '<?xml version="1.0" encoding="UTF-8"?>
        <user name="' . $_POST['username'] . '" expand="attributes">
        <first-name>' . $_POST['firstName'] . '</first-name>
        <last-name>' . $_POST['lastName'] . '</last-name>
        <display-name>' . $_POST['displayName'] . '</display-name>
        <email>' . $_POST['email'] . '</email>
        <active>true</active>
        <attributes>
            <link rel="self" href="/user/attribute?username=' . $_POST['username'] . '"/>
        </attributes>
        <password>
            <link rel="edit" href="/user/password?username=' . $_POST['username'] . '"/>
            <value>' . $_POST['password'] . '</value>
        </password>
    </user>';
}

function create_user(){
    $xml_data = user_xml_data();
    
    try {
        crowd_rest_request("user", "xml", "post", $xml_data);
        echo ("User " . $_POST['username'] . " created.");
    }
    catch (Exception $e){
        echo ("Could not create user " . $_POST['username'] . ".");
    }
}

function modify_user(){
    $xml_data = user_xml_data();

    try {
        crowd_rest_request("user?username=" . $_POST['username'], "xml", "put", $xml_data);
        echo ("User " . $_POST['username'] . " modified.");
    }
    catch (Exception $e){
        echo ("Could not modify user " . $_POST['username'] . ".");
    }
}

function delete_user(){
    try {
        crowd_rest_request("user?username=" . $_POST['username'], "xml", "delete");
        echo ("User " . $_POST['username'] . " deleted.");
    }
    catch (Exception $e){
        echo ("Could not delete user " . $_POST['username'] . ".");
    }
}

function retrieve_user(){
    try {
        $request = crowd_rest_request("user?username=" . $_GET['username'], "json", "get");
        $json_request = json_decode($request);
        $fields_to_display = array('first-name'   => "First Name",
                                   'last-name'    => "Last Name",
                                   'display-name' => "Display Name",
                                   'email'        => "E-mail");

        foreach ($fields_to_display as $json_field => $readable_field){
            echo ($readable_field . ": " . htmlentities($json_request->{$json_field}) . "<br/>");
        }
    }
    catch (Exception $e){
        echo ("Could not retrieve user " . $_GET['username'] . ".");
    }
}

function assign_favourite_food(){
    $xml_data = 
    '<?xml version="1.0" encoding="UTF-8"?>
     <attributes>
         <attribute name="favouriteFood">
            <values>
                <value>' .
                    $_POST['favouriteFood'] .
                '</value>
            </values>
        </attribute>
    </attributes>';

    try {
        crowd_rest_request("user/attribute?username=" . $_POST['username'],
                           "xml", "post", $xml_data);
        echo ("Favourite food assigned for " . $_POST['username'] . ".");
    }
    catch (Exception $e){
        echo ("Could not assign user " . $_POST['username'] . "'s favourite food.");
    }
}

function retrieve_favourite_food(){
    try {
        $request = crowd_rest_request("user/attribute?username=" . $_GET['username'],
                                      "json", "get");
        $json_request = json_decode($request);

        $found = FALSE;
        foreach ($json_request->{'attributes'} as $attribute){
            if ($attribute->{'MultiValuedAttributeEntity'}->{'name'} === "favouriteFood"){
                $found = TRUE;
                echo ($_GET['username'] . "'s favourite food is " .
                      htmlentities($attribute->{'MultiValuedAttributeEntity'}->{'values'}[0]) . ".");
            }
        }
        if (!$found) throw new Exception("User does not have a favourite food.");
    }
    catch (Exception $e){
        echo ("Could not retrieve user " . $_GET['username'] . "'s favourite food.");
    }
}

// Process form submission.
foreach ($_GET as &$value) $value = htmlentities($value);
foreach ($_POST as &$value) $value = htmlentities($value);

if (isset($_POST['createUser'])) create_user();
else if (isset($_POST['modifyUser'])) modify_user();
else if (isset($_POST['deleteUser'])) delete_user();
else if (isset($_GET['retrieveUser'])) retrieve_user();
else if (isset($_POST['assignFavouriteFood'])) assign_favourite_food();
else if (isset($_GET['retrieveFavouriteFood'])) retrieve_favourite_food();

?>
