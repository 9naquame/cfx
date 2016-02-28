<?php

class SystemApi2Controller extends Controller
{
    public function __construct()
    {
        
        if($_SESSION["logged_in"]==false && $_GET["q"] != "system/api2/auth")
        {
            http_response_code(403);
            $this->error(array('You are unauthorized'));
        }
        ini_set('html_errors', 'Off');
        ini_set('display_errors', 'Off');        
    }
    
    public function getContents()
    {
        
    }
    
    private function output($data)
    {
        Application::$template = false;
        header("Content-Type: application/json");        
        echo json_encode($data);
        die();
    }
    
    private function error($errors)
    {
        Application::$template = false;
        header("Content-Type: application/json");        
        echo json_encode(array('error' => $errors));
        die();
    }
    
    public function call_function($params)
    {
        $className = array_shift($params);
        $methodName = array_shift($params);
        try{
            $method = new ReflectionMethod($className, $methodName);
            $ret = $method->invokeArgs(null, $params);
            http_response_code(200);
            $this->output($ret);
        }
        catch(Exception $e)
        {
            http_response_code(404);
            $this->error("Invalid method called $className::$methodName. {$e->getMessage()}");
        }
    }
    
    public function get_multi()
    {
        $params = json_decode($_REQUEST["params"], true);
        $data = SQLDBDataStore::getMulti($params);
        if(count($data) > 0)
        {
            http_response_code(200);
            $this->output($data);
        }
        elseif(count($data) == 0)
        {
            http_response_code(404);
            $this->error('Empty set returned');
        }
    }
    
    
    public function rest($params)
    {
        if(is_numeric(end($params)))
        {
            $id = array_pop($params);
        }
        
        $format = null;
        
        //Determine the data format
        $lastItem = explode('.', end($params));
        if(count($lastItem) == 2)
        {
            $format = end($lastItem);
            $lastItem = reset($lastItem);
            array_pop($params);
            array_push($params, $lastItem);
        }
        $modelName = implode('.', $params);
        
        if($modelName == 'system.users')
        {
            http_response_code(403);
            $this->error('You are not allowed access to this model');
        }
        
        try{
            $model = Model::load($modelName);
        }
        catch(ModelException $e)
        {
            $this->error("Failed to load model $modelName");
        }
        
        $model->setQueryResolve(false);
        $model->setQueryExplicitRelations(false);
        
        switch($_SERVER['REQUEST_METHOD'])
        {
            case 'GET':
                $conditions = array();
                if($id != '')
                {
                    $conditions[$model->getKeyField()] = $id;
                }
                $response = $model->get(
                    array(
                        "filter" => $model->getKeyField(). " = ?",
                        "bind"      => [$id]
                    )
                );
                $this->output($response);
                break;
                
            case 'PUT':
                parse_str(file_get_contents("php://input"), $data);
                $validate = $model->setData($data);
                
                //if($validate === true)
                //{
                    try{
                        $model->update($model->getKeyField(), $id);
                        http_response_code(201);
                        $this->output($id);
                    }
                    catch(ModelException $e)
                    {
                        http_response_code(400);
                        $this->error($e->getMessage());
                    }
                    catch(Exception $e)
                    {
                        http_response_code(400);
                        $this->error($e->getMessage());
                    }
                /*}
                else
                {
                    http_response_code(400);
                    print json_encode($validate);
                }*/                 
                break;
                
            case 'POST':
                if($format == 'json')
                {
                    $data = json_decode(file_get_contents("php://input"), true);
                }
                else 
                {
                    $data = $_POST;
                }
                
                if(reset(array_keys($data)) === 0)
                {
                    $ids = array();
                    $errors = array();
                    foreach($data as $record)
                    {
                        $validate = $model->setData($record);
                        if($validate === true)
                        {
                            try
                            {
                                $ids[] = $model->save();
                            }
                            catch(Exception $e)
                            {
                                $errors[] = $e->getMessage();
                            }
                        }
                        else
                        {
                            $errors[] = $validate;
                        }
                    }
                    
                    if(count($errors) > 0)
                    {
                        http_response_code(400);
                        $this->output($errors);
                    }
                    else
                    {
                        http_response_code(201);
                        $this->output($ids);
                    }
                }
                else 
                {
                    $validate = $model->setData($data);
                    if($validate === true)
                    {
                        try{
                            $id = $model->save();
                            http_response_code(201);
                            $this->output($id);
                        }
                        catch(ModelException $e)
                        {
                            http_response_code(400);
                            $this->error($e->getMessage());
                        }
                        catch(Exception $e)
                        {
                            http_response_code(400);
                            $this->error($e->getMessage());
                            
                        }
                    }
                    else
                    {
                        http_response_code(400);
                        $this->output($validate);
                    }                
                }
                
                break;
        }
    }
    
    public function auth()
    {
        $user = Model::load("system.users");
        $userData = $user->get(
            array(
                "filter"  => "user_name = ?",
                "bind"    => [$_REQUEST['username']]
                )
            );

        /* Verify the password of the user or check if the user is logging in
         * for the first time.
         */
        if ($userData[0]["password"] == md5($_REQUEST["password"]) || $userData[0]["user_status"] == 2 )
        {
            switch ($userData[0]["user_status"])
            {
                case "0":
                    http_response_code(403);
                    $this->error('This account has been disabled');
                    break;
                
                case "2":
                    http_response_code(403);
                    $this->error('Please login through the web ui to setup your account');
                    break;

                case "1":
                    http_response_code(200);
                    $_SESSION["logged_in"] = true;
                    $_SESSION["user_id"] = $userData[0]["user_id"];
                    $_SESSION["user_name"] = $userData[0]["user_name"];
                    $_SESSION["user_firstname"] = $userData[0]["first_name"];
                    $_SESSION["user_lastname"] = $userData[0]["last_name"];
                    $_SESSION["role_id"] = $userData[0]["role_id"];
                    $_SESSION['branch_id'] = $userData[0]['branch_id'];
                    Sessions::bindUser($userData[0]['user_id']);
                    User::log("Logged in through API");
                    $this->output(
                        array(
                            'session_id' => session_id()
                        )
                    );                    
                    break;
            }
        }
        else
        {
            http_response_code(403);
            $this->error('Invalid username or password');
        }        
    }
}
