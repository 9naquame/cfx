<?php
class SystemUsersController extends ModelController 
{
    public $listFields = array(
        ".users.user_id",
        ".users.user_name",
        ".users.first_name",
        ".users.last_name",
        ".roles.role_name"
    );

    public $modelName = ".users";
     
    public function setupListView()
    {
        $this->listView->addConfirmableOperation(
            'reset_password',
            "Reset Password",
            "Are you sure you want to reset user password?"
        );
        
        $this->listView->addConfirmableOperation(
            "disable_user", 
            "Disable User",
            "Are you sure you want to disable user?"
        );
    }
    
    public function reset_password($params)
    {
        $this->model->queryResolve = false;
        $user = $this->model->getWithField2('user_id', $params[0]);
        $user[0]['user_status'] = '2';
        $this->model->setData($user[0]);
        $this->model->update('user_id', $params[0]);
        Application::redirect($this->urlPath . "?notification=User's password reset");
    }
    
    public function disable_user($params)
    {
        $this->model->queryResolve = false;
        $user = $this->model->getWithField2('user_id', $params[0]);
        $user[0]['user_status'] = '0';
        $this->model->setData($user[0]);
        $this->model->update('user_id', $params[0]);
        Application::redirect($this->urlPath . "?notification=User has been disabled");
    }

}