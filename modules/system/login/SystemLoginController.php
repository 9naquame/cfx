<?php
/**
 * A Controller which validates a user and logs her in. This controller allows
 * first time users to create a new password when they log in. 
 *
 * @author james
 *
 */
class SystemLoginController extends Controller
{
    /**
     * A method which allows the user to change their password if they are
     * logging in for the forst time.
     * @return unknown_type
     */
    public function change_password()
    {
        Application::addStylesheet("css/login.css");        
        Application::$template = "login.tpl";
        Application::setTitle("Change Password");

        $form = new Form();
        $form->setRenderer("default");
        $password = new PasswordField("Password", "password");
        $password->setEncrypted(false);
        $form->add($password);

        $passwordRetype = new PasswordField("Retype Password", "password2");
        $passwordRetype->setEncrypted(false);
        $form->add($passwordRetype);
        $form->setValidatorCallback($this->getClassName() . "::change_password_callback");
        $form->setShowClear(false);
        $form = $form->render();

        return "<h2>Change Password</h2>"
            . "<p>It appears that this is the first time you are logging in. "
            . "Please change your password.</p> $form";
    }

    /**
     * The default page which shows the login form.
     * @see lib/controllers/Controller#getContents()
     */
    public function getContents()
    {
        Application::addStylesheet("css/login.css");
        Application::$template = "login.tpl";
        Application::setTitle("Login");
        
        if ($_SESSION["logged_in"])
        {
        	Application::redirect("/");
        };
        $form = new Form();
        $form->setRenderer("default");
        $username = new TextField("Username","username");
        $form->add($username);
        $password = new PasswordField("Password","password");
        $password->setEncrypted(false);
        $form->add($password);
        $form->setSubmitValue("Login");
        $form->setValidatorCallback("{$this->getClassName()}::callback");
        $form->setShowClear(false);
        
        return $form->render();
    }

    /**
     * A fapi callback function called when passwords are changed it is normally
     * used to check if the user entered the correct passwords.
     * @param $data
     * @param $errors
     * @param $form
     * @return unknown_type
     */
    public static function change_password_callback($data, $form)
    {
        $home = Application::getLink("/");
        if ($data["password"] == $data["password2"])
        {
            $users = Model::load("system.users");
            $userData = $users->getWithField("user_id", $_SESSION["user_id"]);
            $userData[0]["password"] = md5($data["password"]);
            $userData[0]["user_status"] = 1;
            $users->setData($userData[0]);
            $users->update("user_id", $_SESSION["user_id"]);
            unset($_SESSION["user_mode"]);
            User::log("Password changed after first log in");
            Application::redirect($home);
        }
        else
        {
            $form->addError("Passwords entered do not match");
        }
        return true;
    }
    
    /**
     * A callback function which checks the validity of passwords on the form.
     * It checks to ensure that the right user is logging in with the right
     * password.
     * 
     * @param $data
     * @param $form
     * @param $callback_pass
     * @return unknown_type
     */
    public static function callback($data, $form, $callback_pass = null)
    {
        $config = Application::$config;
        $users = Model::load("system.users");                
        if ($config['allow_ldap_auth'] === true) {
            $counter = 0;
            $ldap = $config['ldap'];
            $count = count(explode('.', $ldap['domain']));
            $handle = ldap_connect($ldap['host'], $ldap['port']) or die("Could not connect to {$ldap['host']}");
            ldap_set_option($handle, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($handle, LDAP_OPT_REFERRALS, 0);

            if (trim($ldap['prefix'])) {
                $user = trim($ldap['prefix']).$data['username'];
            } else if (!strpos($data['username'], '@')) {
                $user = "{$data['username']}@{$ldap['domain']}";
            } else {
                $user = $data['username'];
            } foreach(explode('.', $ldap['domain']) as $dc) {
                $domain .= "dc={$dc}" . (++$counter == $count ? '' : ',');
            }
            
            $password = $data['password'];
            $bind = ldap_bind($handle, $user, $password);
            $bind ? $data["username"] = "{$data['username']}@{$ldap['domain']}" : null;

            $ldapError = ldap_error($handle);
        } 
        
        $userData = $users->get([
            'filter' => $bind ? 'email = ?' : 'user_name = ?',
            'bind' => [$data['username']]
        ], Model::MODE_ASSOC, false, false);
        
        if (count($userData) == 0) {
            $form->addError($ldapError ? $ldapError : "Please check your username or password");
            return true;
        } else if($userData[0]["role_id"] == null) {
            $form->addError("Sorry! your account has no role attached!"); 
            return true;
        } else if(User::getPermission("can_log_in_to_web", $userData[0]["role_id"])) {
            $home = Application::getLink("/");
            
            /* Verify the password of the user or check if the user is logging in
             * for the first time.
             */
            if  ($userData[0]["user_status"] == 2 && $bind) {
                $userData[0]["user_status"] = 1;
                User::log("Logged in for first time through LDAP");
            } if ($userData[0]["password"] == md5($data["password"]) || $userData[0]["user_status"] == 2 || $bind) {
                switch ($userData[0]["user_status"]) {
                    case "0":
                        $form->addError("Your account is currently inactive");
                        $form->addError("Please contact the system administrator.");
                        return true;
                        break;
                    
                    case "1":
                        $_SESSION["logged_in"] = true;
                        $_SESSION["user_id"] = $userData[0]["user_id"];
                        $_SESSION["user_name"] = $userData[0]["user_name"];
                        $_SESSION["user_firstname"] = $userData[0]["first_name"];
                        $_SESSION["user_lastname"] = $userData[0]["last_name"];
                        $_SESSION["read_only"] = $userData[0]['read_only'];
                        $_SESSION["role_id"] = $userData[0]["role_id"];
                        $_SESSION['branch_id'] = $userData[0]['branch_id'];
                        $_SESSION["department_id"] = $userData[0]['department_id'];
                        Sessions::bindUser($userData[0]['user_id']);
                        User::log("Logged in");
                        Application::redirect($home);
                        break;
    
                    case "2":
                        $_SESSION["logged_in"] = true;
                        $_SESSION["user_id"] = $userData[0]["user_id"];
                        $_SESSION["user_name"] = $userData[0]["user_name"];
                        $_SESSION["role_id"] = $userData[0]["role_id"];
                        $_SESSION["department_id"] = $userData[0]['department_id'];
                        $_SESSION["user_firstname"] = $userData[0]["first_name"];
                        $_SESSION["user_lastname"] = $userData[0]["last_name"];
                        $_SESSION['branch_id'] = $userData[0]['branch_id'];
                        $_SESSION["user_mode"] = "2";
                        Sessions::bindUser($userData[0]['user_id']);
                        User::log("Logged in for first time");
                        Application::redirect($home);
                        break;
                }
            } else {
                $form->addError("Please check your username or password");
                return true;
            }
        } else {
            $form->addError("You are not allowed to log in from this terminal");
            return true;
        }
    }
}
