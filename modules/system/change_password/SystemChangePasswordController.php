<?php

/**
 * Description of ChangePasswordController
 *
 * @author ekow
 */
class SystemChangePasswordController extends Controller
{
    public function getContents()
    {
        $this->label = 'Change Your Password';
        $this->description = 'Please use this form to change your current password';
        $form = Element::create('Form')->add(
            Element::create('PasswordField', 'Current Password', 'current_password')->setRequired(true)->setEncrypted(false),
            Element::create('PasswordField', 'New Password', 'new_password')->setRequired(true)->setEncrypted(false),
            Element::create('PasswordField', 'Retype New Password', 'repeat_new_password')->setRequired(true)->setEncrypted(false)
        );
        $form->addAttribute('style', 'width:500px');
        $form->setCallback($this->getClassName() . '::callback', $this);
        return $form->render();
    }
    
    public static function callback($data, $form, $that) {
        $users = Model::load('system.users')->setQueryResolve(false);
        $user = reset($users->getWithField('user_id', $_SESSION['user_id']));
        
        if($user['password'] == md5($data['current_password'])) {
            if($data['new_password'] == $data['repeat_new_password']) {
                $user['password'] = md5($data['new_password']);
                $users->setData($user);
                $users->update('user_id', $user['user_id']);
                Application::redirect($that->path, 'Password succesfully changed.');
            } else {
                $form->addError('Please enter both passwords correctly');
            }
        } else {
            $form->addError('Please enter your current password correctly');
        }
    }
}
