<?php
relRequire('controller/Controller.php');

relRequire('view/Presenter.php');
relRequire("view/Form.php");
relRequire('view/GenericViews.php');

relRequire('model/HomeModel.php');
relRequire('model/ShopModel.php');
relRequire("model/User.php");
relRequire('model/UserAccessModel.php');
relRequire("model/GenericModel.php");
/*
 * Copyright (C) 2015 Fabio Colella
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
 * Handles the pages, the checks, sanitizes the input and more.
 *
 * @author Fabio Colella.
 */
class BasePageController extends Controller
{
    /**
     * The minimum length for the password
     * 
     * @var string
     */
    const PASSWORD_MIN_LEN = 6;
    const USER_MIN_LEN = 5;
    const FIELD_MIN_LEN = 1;
    
    /**
     * Array of errors to be reported.
     * 
     * @var array
     */
    private $error;
    
    /**
     * @var Presenter View of the page.
     */
    private $presenter;
    
    public function __construct(&$request)
    {
        parent::__construct($request);
        $this->error = array();
        $this->presenter = new Presenter($this->getTitle(), $this->getLinks());
    }
    
    
    /***********************************************
     * Page handling functions.                    *
     ***********************************************/   
    /**
     * Renders the home page.
     * 
     * @param array $request
     */
    public function loadPageHome() 
    {               
        $model = new ShopModel();
        $data = $model->getData();
        if ($data)
        {
            $content = (new GenericView)->getHomeContent($data);
            $this->presenter->setContent($content);
        }
        else
        {
            $this->error[] = "There has been an error retrieving the data.";
        }
        
        if (DBGMODE) {$this->concatErrorArray($model->getError());}
        $this->presenter->setError($this->error);
        $this->presenter->render();
    }
    
    /**
     * Handles the signup process.
     */
    public function loadPageSignup()
    {
        
        if($this->isLoggedIn())
        {
            $this->error[] = "You're already logged in and signed up!";
            $this->presenter->setContent((new Form())->getLoginConfirmation($this->username));
            $this->presenter->setError($this->error);
            $this->presenter->setRedir();
            $this->presenter->render();
            return;
        }
        
        /* Setup the fields to be used and initialize a User object */
        $this->setSignupFields();
        
        $model = new UserAccessModel();
        
        /* Check if the fields follow the necessary rules */
        if (!$this->checkFieldsSignUp($model))
        {
            if (DBGMODE) {$this->concatErrorArray($model->getError());}
            $this->presenter->setContent((new Form())->getSignupForm($this->user));
            $this->presenter->setError($this->error);
        }
        else if($model->addUserToDatabase($this->user))
        {
            if (DBGMODE) {$this->concatErrorArray($model->getError());}
            $this->presenter->setContent((new Form())->getSignupConfirmation($this->user));
            $this->presenter->setError($this->error);
            $this->presenter->setRedir();
        }
        else
        {
            if (DBGMODE) {$this->concatErrorArray($model->getError());}
            $this->presenter->setContent((new Form())->getSignupForm($this->user));
            $this->presenter->setError($this->error);
        }
                
        $this->presenter->render();
    }
    
    /**
     * 
     * Handles the login process.
     */
    public function loadPageLogin()
    {
                
        if($this->isLoggedIn())
        {
            $this->error[] = "You're already logged in!";
            $this->presenter->setContent((new Form())->getLoginConfirmation($this->username));
            $this->presenter->setError($this->error);
            $this->presenter->setRedir();
            $this->presenter->render();
            return;
        }
        
        /* No field is set, probably coming here for first time */
        if(!isset($this->request["username"]) && !isset($this->request["password"]))
        {
            $this->presenter->setContent((new Form())->getLoginForm("", $this->error));
            $this->presenter->render();
            return;
        }
        
        /* Only username or password have not been set, warning. */
        if (!isset($this->request["username"]) || !isset($this->request["password"]))
        {
            $this->error[] = "Please, mind that every field is mandatory.";
            $this->presenter->setContent((new Form())->getLoginForm("", $this->error));
            $this->presenter->render();
            return;
        }
        
        $username = $this->safeInput($this->request["username"]);
        $password = $this->safeInput($this->request["password"]);
        $model = new UserAccessModel();
        
        /* The user filled the fields but either the username or the password is wrong */
        if(!$model->checkLoginData($username, $password))
        {
            if (DBGMODE) {$this->concatErrorArray($model->getError());}
            $this->error[] = "Sorry, username or password are wrong.";
            $this->presenter->setError($this->error);
            $this->presenter->setContent((new Form())->getLoginForm($username, $this->error));
            $this->presenter->render();
        }
        else /* The user logged correctly and a session gets opened. */
        {
            $_SESSION["username"] = $username;
            $user = $model->getUser($username);
            if (DBGMODE) {$this->concatErrorArray($model->getError());}
            if($user->get("isAdmin"))
            {
                $_SESSION["isAdmin"] = true;
            }
            $this->presenter->setContent((new Form())->getLoginConfirmation($username));
            $this->presenter->setError($this->error);
            $this->presenter->setRedir();
            $this->presenter->render();
        }        
    }
    
    /**
     * Lets a user log out.
     */
    public function loadPageLogout() 
    {
         
        if($this->isLoggedIn())
        {

            $this->presenter->setContent((new Form())->getLogout($this->getSessionUsername()));
            $this->closeSession();
            $this->presenter->setRedir();
            $this->presenter->render();
        }
        else
        {
            $this->error[] = "You're not logged in yet. You're now redirected to"
              . "the login page.";
            $this->loadPageLogin();
        }
    }
    
    
    /**
     * Page to add a new fisherman shop.
     */
    public function loadPageAddshop() 
    {
        if(!$this->isLoggedIn())
        {
            $this->error[] = "You need to log in to visit this page!";
            $this->loadPageLogin();
            return;
        }
        
        $this->presenter->setTitle("Add a shop");
        
        $data = $this->setAddshopData();

        /** A field is set, we can assume the user already filled the form. **/
        if(isset($this->request["address"]))
        {
            $model = new ShopModel();
            $fieldsAreOk = $this->checkFieldsAddshop($data);
            /** Case everything went well. **/
            if($fieldsAreOk && $model->addShopToDatabase($data))
            {
                if (DBGMODE) {$this->concatErrorArray($model->getError());}
                $this->presenter->setError();
                $this->presenter->setContent((new Form)->getAddshopConfirmation($data));
                $this->presenter->setRedir();
                $this->presenter->render();
                return;
            }
            /** There's been a database error. **/
            else if($fieldsAreOk && !$model->addShopToDatabase($data))
            {
                if (DBGMODE) {$this->concatErrorArray($model->getError());}
                $this->error[] = "Sorry, something went wrong and the process could not be completed.";
                $this->presenter->setError();
                $this->presenter->setContent((new Form)->getAddshopConfirmation($data));
                $this->presenter->setRedir();
                $this->presenter->render();
                return;
            }
            /** The user filled the fields disrespecting some rule. **/
            else
            {
                if (DBGMODE) {$this->concatErrorArray($model->getError());}
                $this->presenter->setError($this->error);
                $this->presenter->setContent((new Form)->getAddshopForm($data));
                $this->presenter->render();
                return;
            }
            
        }
        /** No field is set, we can assume the user is coming there for the first time. **/
        else
        {
            $this->presenter->setContent((new Form)->getAddshopForm($data));
            $this->presenter->setError($this->error);
            $this->presenter->render();
        }
    }
    
    /**
     * Creates an informative page.
     */
    public function loadPageHelp() 
    {    
        $this->presenter->setContent((new GenericView)->getInfo());
        
        $this->presenter->setError($this->error);
        $this->presenter->render();
    }
    
    /**
     * Handles the profile page and the admin panel to remove the shops.
     */
    public function loadPageProfile()
    {
        /** The user is not logged. **/
        if (!$this->isLoggedIn())
        {
            $this->error[] = "You're not logged in!";
            $this->loadPageLogin();
        }
        /** The user is logged and is an admin. **/
        else if($this->isAdmin())
        {
            $model = new UserAccessModel;
            $user = $model->getUser($this->username);
            if (DBGMODE) {$this->concatErrorArray($model->getError());}
            
            /** User data have been collected and can be displayed **/
            if ($user)
            {
                $shopModel = new ShopModel();
                $data = $shopModel->getData();
                if (DBGMODE) {$this->concatErrorArray($shopModel->getError());}
                $this->presenter->setContent((new GenericView)->getAdminView($user, $data));
            }
            /** The user data could not be loaded. **/
            else
            {
                $this->error[] = "Something naughty happened retrieving the data!";
            }
        }
        /** The user is not admin. **/
        else
        {
            $model = new UserAccessModel;
            $user = $model->getUser($this->username);
            if (DBGMODE) {$this->concatErrorArray($model->getError());}
            
            /** User data have been collected and can be displayed **/
            if ($user)
            {
                $this->presenter->setContent((new GenericView)->getProfileView($user));
            }
            /** The user data could not be loaded. **/
            else
            {
                $this->error[] = "Something naughty happened retrieving the data!";
            }
                       
        }
        
        $this->presenter->setError($this->error);
        $this->presenter->render(); 
    }
    
    /** 
     * Asks a confirmation before deleting a shop definitely.
     */
    public function loadPageRemoveShop()
    {
        /** The user is not logged in. **/
        if (!$this->isLoggedIn())
        {
            $this->error[] = "You're not logged in!";
            $this->loadPageLogin();
        }
        /** The user tried cheating and won't receive gifts for Christmas. **/
        else if(!$this->isAdmin())
        {
            $this->error[] = "You're now on Santa's naugthy list.";
            $this->loadPageErr403();
        }
        /** The admin is ready to delete the shop. **/
        else if(isset ($this->request["shop_name"]) && isset ($this->request["id"]) && isset ($this->request["isSure"]))
        {
            $shop_name = $this->safeInput($this->request["shop_name"]);
            $id = $this->safeInput($this->request["id"]);
            $isSure = $this->safeInput($this->request["isSure"]);
            $model = new ShopModel();
            if ($isSure == "true")
            {
                $result = $model->removeShop($shop_name, $id);
            }
            else
            {
                $result = false;
            }
            
            if ($result)
            {
                $this->presenter->setContent((new GenericView)->getRemoveShopConfirmation($shop_name));
            }
            else
            {
                $this->error[] = "Sorry, could not find the shop. Please, contact the administrator.";
            }
            
            $this->presenter->setRedir("index", 10);
            if (DBGMODE) {$this->concatErrorArray($model->getError());}
            $this->presenter->setError($this->error);

            $this->presenter->render();
        }
        /** The admin is asked for confirmation before deleting the shop. **/
        else if(isset ($this->request["shop_name"]) && isset ($this->request["id"]))
        {
            $shop_name = $this->safeInput($this->request["shop_name"]);
            $id = $this->safeInput($this->request["id"]);
            $this->presenter->setContent((new GenericView)->getRemoveShopCertainty($shop_name, $id));
            $this->presenter->render();
        }
   
    }
    
    /**
     * Handles an Ajax request to search a shop.
     */
    public function loadPageAjaxSearchShop()
    {
        $model = new ShopModel();
        if (isset($this->request["searchstring"]))
        {
            $search = $this->safeInput($this->request["searchstring"]);
            $data = $model->getData($search);
        }
        else
        {
            $data = $model->getData();
        }
        $json = json_encode($data);
        $this->presenter->setContent($json);
        $this->presenter->json();
    }
    
    /**
     * Handles the 404 error.
     * @param request $request
     */
    public function loadPageErr404()
    {
        $title = "Error 404 - Page not found";
        $this->error[] = "Sorry, the page you're looking for "
          . "does not exist or has been moved.";
        $this->presenter->setError($this->error);
        $this->presenter->setCustomHeader("HTTP/1.0 404 Not Found");
        /** Let's make a laugh of that. **/
        $this->presenter->setContent("<img id='err404'src='https://media3.giphy.com/media/tj2MwoqitZLtm/giphy.gif'>");
        $this->presenter->setRedir("index", 10);
        $this->presenter->render();
    }
    
    /**
     * Handles the 403 error.
     * @param request $request
     */
    public function loadPageErr403()
    {
        $title = "Error 403 - Forbidden";
        $this->error[] = "You're attempting to access an unauthorized "
          . "area. If you think you should be able to access this area "
          . "contact your administrator.";
        $this->presenter->setError($this->error);
        $this->presenter->setCustomHeader("HTTP/1.0 403 Forbidden");
        $this->presenter->render();
    }
    
    
    /***********************************
     * Helper functions.               *
     ***********************************/

    /**
     * Setup the User object feeding its properties with the form fields.
     */
    public function setSignupFields()
    {
        $this->user = new User();
        foreach ($this->user->fieldList() as $field)
        {
            if (isset($this->request[$field]))
            {
                $this->user->set($field, $this->safeInput($this->request[$field]));
            }
            else
            {
                $this->user->set($field, null);
            }
        }
        
        /** Additonal control to obtain the birthdate. **/
        if(isset($this->request["year"]) && isset($this->request["month"]) && isset($this->request["day"]))
        {
            $birthdate = $this->getDate($this->safeInput($this->request["year"]), 
                                        $this->safeInput($this->request["month"]), 
                                        $this->safeInput($this->request["day"]));
        }
        else
        {
            $birthdate = null;
        }
        $this->user->set("birthdate", $birthdate);
    }
    
    /**
     * Marks a field which require attention.
     * 
     * @param string $field the field to mark.
     * @return string field with class warning.
     */
    public function setWarning($field)
    {
        return $field . '" class="warning';
    }
    
    /**
     * Checks the fields one by one for the sign up process.
     * 
     * If adding new fields this class needs to be edited.
     * 
     * @param UserAccessModel $model a model to access the user table on the DB.
     * @return boolean true if the test is passed.
     */
    public function checkFieldsSignUp(UserAccessModel $model)
    {
        /**First check is the fields exist**/
        $isValid = false;
        
        foreach ($this->user->fieldList() as $field)
        {
            if (null !== $this->user->get($field))
            {
                $isValid = true;
            }
        }
        
        /** Skip other checks if none of the fields is set**/
        if (!$isValid) return false;
       
        /**Then if they're valid**/
        $current = "username";
        /** Usernames are lowercase only, but uppercase can be accepted and converted to lowercase. **/
        $this->user->set($current, strtolower($this->user->get($current)));
        if (!$this->checkCharDigit($current, $this->user->get($current), BasePageController::USER_MIN_LEN) ||
          !$model->checkFieldNotExists($current, $this->user->get($current)))
        {
            $this->user->set($current, $this->setWarning($this->user->get($current)));
            $this->error[] = "This $current already exists or is not valid, please "
              . "use a different one.";
            $isValid = false;
        }
        
        $current = "firstname";
        if (!$this->checkCharSpaces($current, $this->user->get($current)))
        {
            $this->user->set($current, $this->setWarning($this->user->get($current)));
            $isValid = false;
        }
        
        $current = "secondname";
        if (!$this->checkCharSpaces($current, $this->user->get($current)))
        {
            $this->user->set($current, $this->setWarning($this->user->get($current)));
            $isValid = false;
        }
        
        $current = "password";
        if (!$this->checkPassword($current, $this->user->get($current)))
        {
            $this->user->set($current, $this->setWarning($this->user->get($current)));
            $isValid = false;
        }
        
        $current = "email";
        if (!$this->checkEmail($current, $this->user->get($current)) ||
          !$model->checkFieldNotExists($current, $this->user->get($current)))
        {
            $this->user->set($current, $this->setWarning($this->user->get($current)));
            $this->error[] = "This $current already exists or is not valid, please "
              . "use a different one.";
            $isValid = false;
        }

        $current = "birthdate";
        if (!$this->checkDate($current, $this->user->get($current)))
        {
            $this->user->set($current, $this->setWarning($this->user->get($current)));
            $isValid = false;
        }
     
        return $isValid;
    }
    
    
    /**
     * Returns a standard date in the YYYY-MM-DD format.
     * 
     * @param type $year the year
     * @param type $month the month
     * @param type $day the day
     * @return string date in the YYYY-MM-DD format.
     */
    function getDate($year="", $month="", $day="")
    {
        return "$year-$month-$day";
    }
    
    /**
     * Helper function to check if strings contain only chars and digits.
     *
     * @param string $field the name of the field.
     * @param string $value the value of the field.
     * @param int $length (optional) checks also the lenghts of the value.
     * @return boolean true if the test is passed.
     */
    public function checkCharDigit($field, $value, $length = -1)
    {
        $flag = true;
          
        if (!preg_match("/^[a-zA-Z][a-zA-Z0-9]*$/",$value)) 
        {
            $this->error[] = "Only letters and numbers are allowed in $field."
              . " (Numbers not at beginning).";
            $flag = false;
        }
        
        if ($length != -1 && !(strlen($value) >= $length))
        {
            $this->error[] = "The $field field should be long at least $length.";
            $flag = false;
        }
        
        return $flag;
    }
        
    /**
     * Helper function to check if strings contain only chars and spaces.
     * @param string $field the name of the field.
     * @param string $value the value of the field.
     * @param int $length (optional) checks also the lenghts of the value.
     * @return boolean true if the test is passed.
     */
    public function checkCharSpaces($field, $value, $length = -1)
    {
        if (!preg_match("/^[a-zA-Z][a-zA-Z0-9 ]*$/",$value)) 
        {
            $this->error[] = "Only letters and spaces are allowed in $field.";
            return false;
        }
        
        if ($length != -1 && !(strlen($value) >= $length))
        {
            $this->error[] = "The $field field should be long at least $length.";
            $flag = false;
        }
        return true;
    }
    
    /**
     * Helper function to check if strings contain only certain symbols.
     * 
     * @param string $field the name of the field.
     * @param string $value the value of the field.
     * @param string $symbols A list of symbols allowed, default: "a-zA-Z0-9 ',.-°".
     * @param int $length (optional) checks also the lenghts of the value.
     * @return boolean true if the test is passed.
     */
    public function checkComposedStrings($field, $value, $symbols = "a-zA-Z0-9 ',.-°", $length = -1)
    {
        if (!preg_match("/[$symbols]*$/",$value)) 
        {
            $this->error[] = "Only the symbols '$symbols' are allowed in $field.";
            return false;
        }
        
        if ($length != -1 && !(strlen($value) >= $length))
        {
            $this->error[] = "The $field field should be be long at least $length.";
            $flag = false;
        }
        return true;
    }
    
    /**
     * Helper function to check if strings contain only decimal numbers.
     * 
     * @param string $field the name of the field.
     * @param string $value the value of the field.
     * @return boolean true if the test is passed.
     */
    public function checkDecimal($field, $value)
    {
        if (!preg_match("/^[0-9]*[.]+[0-9]*$/", $value)) 
        {
            $this->error[] = "You should write a decimal number in $field.";
            return false;
        }
        return true;
    }
    
    /**
     * Helper function to check if the string is strong enough to be used as a password.
     * 
     * @param string $field the name of the field.
     * @param string $value the value of the field.
     * @return boolean true if the test is passed.
     */
    public function checkPassword($field, $value)
    {
        if (!preg_match("/[0-9]+/",$value) || !preg_match("/[A-Z]+/",$value) 
          || !preg_match("/[a-z]+/",$value) || !(strlen($value)>= BasePageController::PASSWORD_MIN_LEN)) 
        {
            $this->error[] = ucfirst($field) . " must be at least "
              . BasePageController::PASSWORD_MIN_LEN ." chars long and have at least: "
              . "one digit, "
              . "one upper case letter "
              . "and one lower case letter.";
            return false;
        }
        return true;
    }
    
    /**
     * Helper function to check if the string is a valid email address.
     * 
     * @param string $field the name of the field.
     * @param string $value the value of the field.
     * @return boolean true if the test is passed.
     */
    public function checkEmail($field, $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL))
        {
            $this->error[] = "The $field is not valid.";
            return false;
        }
        return true;
    }
    
    /**
     * Checks that the date is in an appropriate format (YYYY-MM-DD).
     * 
     * @param string $field the name of the field.
     * @param string $value the value of the field.
     * @return boolean true if the test is passed.
     */
    public function checkDate($field, $value)
    {
        $date = explode("-", $value);
        if (!preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2}/", $value) ||
          !checkdate($date[1], $date[2], $date[0]))
        {
            $this->error[] = "The $field is not valid or does not correspond "
              . "to the required date format: "
              . "YYYY-MM-DD.";
            return false;
        }
        return true;
    }
    
    
    /**
     * Merges the passed error array with the current error array.
     * 
     * @param array $error
     */
    public function concatErrorArray($error)
    {
        $this->error = array_merge($this->error, $error);
    }
    
    
    /**
     * Returns an associative array of strings containing the data sent 
     * found in the request array.
     * 
     * @return array
     */
    public function setAddshopData() 
    {
        $data = array();
        $data["address"] = "";
        $data["shop_name"] = "";
        $data["city"] = "";
        //$data["typeOfShop"] = "";
        $data["VATNumber"] = "";
        $data["latitude"] = "";
        $data["longitude"] = "";
        $data["owner"] = "";
        
        foreach ($data as $key => $value) 
        {
            if (isset($this->request[$key]))
            {
                $data[$key] = $this->safeInput($this->request[$key]);
            }
        }
        
        return $data;
    }
    
    /**
     * Checks the fields one by one to add a shop.
     * 
     * If adding new fields this class needs to be edited.
     * 
     * @param array $data associative array where the name of the field is the key.
     * @return boolean true if the test is passed.
     */
    public function checkFieldsAddshop($data)
    {
        $isValid = true;
                
        $current = "address";
        if ($current == "")
        {
           $this->error[] = "Field $current cannot be empty.";
           $data[$current] = $this->setWarning($data[$current]);
           $isValid = false;
        }
        else if (!$this->checkComposedStrings($current, $data[$current]))
        {
           $data[$current] = $this->setWarning($data[$current]);
           $isValid = false;
        }
        
        $current = "city";
        if ($current == "")
        {
           $this->error[] = "Field $current cannot be empty.";
           $data[$current] = $this->setWarning($data[$current]);
           $isValid = false;
        }
        else if (!$this->checkComposedStrings($current, $data[$current]))
        {
           $data[$current] = $this->setWarning($data[$current]);
           $isValid = false;
        }
        
        $current = "shop_name";
        if ($current == "")
        {
           $this->error[] = "Field $current cannot be empty.";
           $data[$current] = $this->setWarning($data[$current]);
           $isValid = false;
        }
        
        $current = "VATNumber";
        if ($data[$current] != "" && !$this->checkCharSpaces($current, $data[$current]))
        {
           $this->error[] = "Field $current is not correct.";
           $data[$current] = $this->setWarning($data[$current]);
           $isValid = false;
        }
        
        $current = "latitude";
        if ($data[$current] != "" && !$this->checkDecimal($current, $data[$current]))
        {
           $this->error[] = "Field $current is not a decimal value.";
           $data[$current] = $this->setWarning($data[$current]);
           $isValid = false;
        }
        
        $current = "longitude";
        if ($data[$current] != "" && !$this->checkDecimal($current, $data[$current]))
        {
           $this->error[] = "Field $current is not a decimal value.";
           $data[$current] = $this->setWarning($data[$current]);
           $isValid = false;
        }
        
        return $isValid;
        
    }
    
}
