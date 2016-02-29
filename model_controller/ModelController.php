<?php
/*
 * WYF Framework
 * Copyright (c) 2011 James Ekow Abaka Ainooson
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

/**
 * A controller for interacting with the data in models. This controller is loaded
 * automatically when the path passed to the Controller::load method points to
 * a module which contains only a model definition. This controller provides
 * an interface through which the user can add, edit, delete and also perform
 * other operations on the data store in the model.
 *
 * Extra configuration could be provided through an app.xml file which would be
 * found in the same module path as the model that this controller is loading.
 * This XML file is used to describe what fields this controller should display
 * in the table view list. It also specifies which fields should be displayed
 * in the form.
 *
 * A custom form class could also be provided for this controller. This form
 * class should be a subclass of the Form class. The name of the file in which
 * this class is found should be modelnameForm.php (where modelname represents
 * the actual name of the model). For exampld of your model is called users then
 * the custom form that this controller can pick up should be called usersForm.
 * 
 * In cases where extra functionality and operations are to be added, the
 * ModelController class could be extended.
 *
 * @author James Ekow Abaka Ainooson <jainooson@gmail.com>
 * 
 */
class ModelController extends Controller
{
    /**
     * An instance of the model that this controller is linked to.
     * @var Model
     */
    protected $model;

    /**
     * The name of the model that this controller is linked to.
     * @var string
     */
    public $modelName;

    /**
     * The URL path through which this controller's model can be accessed.
     * @var string
     */
    public $urlPath;

    /**
     * The local pathon the computer through which this controllers model can be
     * accessed.
     * @var string
     */
    protected $localPath;
    
    /**
     * The controller action to be performed.
     * @var string
     */
    protected $action;
    
    /**
     * Conditions which should be applied to the query used in generating the
     * list of items in the model.
     * @var string
     */
    public $listConditions;
    
    /**
     * An array which contains a list of the names of all the fields in the model
     * which are used by this controller.
     * @var array
     */
    public $fieldNames = array();
    
    /**
     * The name of the callback method which should be called after any of the 
     * forms are submitted. This method is the heart of the model controller and it
     * determines how the data is routed around the controller. The method
     * pointed to must be a static method and it should be defined as follows.
     * 
     * @code
     * public static function callback($data,&$form,$c,$redirect=true,&$id=null)
     * {
     * }
     * @endcode
     * 
     * @var string
     * @see ModelController::callback()
     */
    protected $callbackMethod = "ModelController::callback";
    
    /**
     * An array which shows which of the fields of the model should be displayed
     * on the list view provided by the ModelController.
     * @var array
     */
    public $listFields = array();
    
    /**
     * A prefix to be used for the permission.
     * @var string
     */
    protected $permissionPrefix;
    
    /**
     * Should this model controller show the add operation.
     * @var boolean
     */
    protected $hasAddOperation = true;
    
    /**
     * Should this model controller show the edit operation.
     * @var boolean
     */
    protected $hasEditOperation = true;
    
    /**
     * Should this model controller show the delete operation.
     * @var boolean
     */
    protected $hasDeleteOperation = true;
    
    /**
     * Enforce the add operation. This overrides the permissions on the system.
     * 
     * @var boolean
     */
    protected $forceAddOperation = false;
    protected $forceEditOperation = false;
    protected $forceDeleteOperation = false;
    protected $historyModels = array();
    
    /**
     *
     * @var MCListView
     */
    protected $listView;
    
    /**
     * Constructor for the ModelController.
     * @param $model An instance of the Model class which represents the model
     *               to be used.
     */
    public function __construct($model = "")
    {
        $this->modelName = ($this->modelName == "" ? $model : $this->modelName);
        $this->model = Model::load($this->modelName);
        $this->name = $this->model->name;
        $this->urlPath = Application::$prefix."/".str_replace(".","/",$this->modelName);
        $this->permissionPrefix = $this->permissionPrefix == '' ? str_replace(".", "_", $this->modelName) : $this->permissionPrefix;
        $this->label = $this->model->label;
        $this->description = $this->model->description;
        Application::setTitle($this->label);
        $this->_showInMenu = $this->model->showInMenu === "false" ? false : true;
    }
    
    public function setRedirectParameters($redirect) 
    {
        parent::setRedirectParameters($redirect);
        $this->permissionPrefix = str_replace(".", "_", $redirect['package_path']) . str_replace(".", "_", $this->modelName);
        $urlBase = ($redirect['package_path'] != '' ? $redirect['package_path'] : '') . $this->modelName;
        $this->urlPath = Application::$prefix."/".str_replace(".","/",$urlBase);
        $this->localPath = "app/modules/".str_replace(".","/",$urlBase);
    }
    
    /**
     * 
     * @param MCListView $listView
     */
    protected function setupListView()
    {
        
    }
    
    /**
     * Default controller action. This is the default action which is executed
     * when no action is specified for a given call.
     * 
     * @see lib/controllers/Controller::getContents()
     */
    public function getContents()
    {
        $this->listView = new MCListView(
            array(
                'model' => $this->model, 
                'url_path' => $this->urlPath, 
                'list_fields' =>$this->listFields,
                'permission_prefix' => $this->permissionPrefix
            )
        );
        
        $this->setupListView();
        return '<div id="table-wrapper">' . $this->listView->render() . '</div>';
    }

    /**
     * Returns the form that this controller uses to manipulate the data stored
     * in its model. As stated earlier the form is either automatically generated
     * or it is loaded from an existing file which is located in the same
     * directory as the model and bears the model's name.
     *
     * @return Form
     */
    protected function getForm()
    {
        // Load a local form if it exists.
        if($this->redirected)
        {
            $formName = $this->redirectedPackageName . Application::camelize($this->mainRedirectedPackage) . "Form";
            $formPath = $this->redirectPath . "/" . str_replace(".", "/", $this->mainRedirectedPackage) . "/" . $formName . ".php";
        }
        else
        {
            $formName = Application::camelize($this->model->package) . "Form";
            $formPath = $this->localPath . "/" . $formName . ".php";
        }
        
        if(class_exists($formName))
        {
            $form = new $formName();
        }
        else if(is_file($formPath))
        {
            include_once $formPath;
            $form = new $formName();
        }
        else if (is_file($this->localPath."/".$this->name."Form.php"))
        {
            include_once $this->localPath."/".$this->name."Form.php";
            $formclass = $this->name."Form";
            $form = new $formclass();
        }
        else
        {
            $form = new MCDefaultForm($this->model);
        }
        return $form;
    }
    
    /**
     * Controller action method for adding new items to the model database.
     * @return String
     */
    public function add()
    {
    	if(!User::getPermission($this->permissionPrefix."_can_add")) return;

        $form = $this->getForm();
        $this->label = "New ".$this->label;
        $form->setCallback(
            $this->callbackMethod,
            array(
                "action"=>"add",
                "instance"=>$this,
                "success_message"=>"Added new ".$this->model->name,
                "form"=>$form
            )
        );
        
        return $form->render();
    }
    
    protected function setFormErrors($form, $errors)
    {
        $fields = array_keys($errors);
        foreach($fields as $field)
        {
            foreach($errors[$field] as $error)
            {
                try{
                    $element = $form->getElementByName($field);
                }
                catch(Exception $e)
                {
                    $element = $form->getElementById(str_replace(".", "_", $field));
                }
                $element->addError($error);
            }
        }

        foreach($errors as $fieldName => $error)
        {
            $form->addError($error);
        }        
    }

    /**
     * The callback used by the form class. This callback is only called when
     * the add or edit controller actions are performed. 
     * 
     * @param array $data The data from the form
     * @param Form $form an instance of the form
     * @param mixed $c Specific data from the form, this normally includes an instance of the controller
     * 
     * @see ModelController::$callbackFunction
     * @return boolean
     */
    public static function callback($data, $form, $c)
    {
        $return = $c["instance"]->model->setData($data, $c['key_field'], $c['key_value']);
        if($return===true)
        {
            if($c['action'] == 'add')
            {
                $id = $c["instance"]->model->save();
            }
            else
            {
                $id = $c["instance"]->model->update($c["key_field"],$c["key_value"]);
            }
            User::log($c["success_message"]);
            Application::redirect($c["instance"]->urlPath."?notification=".urlencode($c["success_message"]));
        }
        else
        {
            self::setFormErrors($form, $return['errors']);
        }        
    }

    protected function getModelData($id)
    {
        $data = $this->model->get(
            array(
                "conditions"=>$this->model->getKeyField()."='$id'"
            ),
            SQLDatabaseModel::MODE_ASSOC,
            true,
            false
        );
        return $data[0];
    }

    /**
     * Action method for editing items already in the database.
     * @param $params array An array of parameters that the system uses.
     * @return string
     */
    public function edit($params)
    {
    	if(!User::getPermission($this->permissionPrefix."_can_edit")) return;
        $form = $this->getForm();
        $form->setData($this->getModelData($params[0]));
        $this->label = "Edit ".$this->label;
        $form->setCallback(
            $this->callbackMethod,
            array(
                "action"=>"edit",
                "instance"=>$this,
                "success_message"=>"Edited ".$this->model->name,
                "key_field"=>$this->model->getKeyField(),
                "key_value"=>$params[0],
                "form"=>$form
            )
        );
        return $form->render();
    }

    /**
     * Display the items already in the database for detailed viewing.
     * @param $params An array of parameters that the system uses.
     * @return string
     */
    public function view($params)
    {
        $form = $this->getForm();
        $form->setShowField(false);
        $data = $this->model->get(
            array(
                "conditions"=>$this->model->getKeyField()."='".$params[0]."'"
            ),
            SQLDatabaseModel::MODE_ASSOC,
            true,
            false
        );
        $form->setData($data[0]);
        $this->label = "View ".$this->label;
        return $form->render(); //ModelController::frameText(400,$form->render());
    }

    /**
     * Export the data in the model into a particular format. Formats depend on
     * the formats available in the reports api.
     * @param $params
     * @return unknown_type
     * @see Report
     */
    public function export($params)
    {
        $exporter = new MCDataExporterJob();
        $exporter->fields = $this->getForm()->getFields(); 
        $exporter->format = $params[0];
        $exporter->model = $this->model;
        $exporter->label = $this->label;
        $exporter->run();
    }

    /**
     * Provides all the necessary forms needed to start an update.
     * @param $params
     * @return string
     */
    public function import()
    {
        $this->label = "Import " . $this->label;
        $form = new Form();
        $form->add(
            Element::create("UploadField","File","file","Select the file you want to upload."),
            Element::create("Checkbox","Break on errors","break_on_errors","","1")->setValue("1")
        );
        $form->addAttribute("style","width:50%");
        $form->setCallback($this->getClassName() . '::importCallback', $this);
        $form->setSubmitValue('Import Data');

        return $this->arbitraryTemplate(
            Application::getWyfHome('utils/model_controller/templates/import.tpl'), 
            array(
                'form' => $form->render(),
                'template' => "{$this->path}/export/csv?template=yes"
            )
        );
    }
    
    public function importReport($status)
    {
        self::getTemplateEngine()->assign('import_status', $status['statuses']);
        self::getTemplateEngine()->assign('import_headers', $status['headers']);
        self::getTemplateEngine()->assign('import_message', $status['message']);
    }
    
    public static function importCallback($data, $form, $instance)
    {
        $id = uniqid();
        $uploadfile = "app/temp/{$id}_data";
        if(move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile))
        {
            $importer = new MCDataImporterJob();
            $importer->file = $uploadfile;
            $importer->model = $instance->model->package;
            $importer->fields = $instance->getForm()->getFields();
            $status = $importer->run();
            $instance->importReport($status);
            if(is_string($status))
            {
                $form->addError($status);
            }
        }
        else
        {
            $form->addError('Failed to upload file');
        }
    }

    /**
     * Delete a particular item from the model.
     * @param $params
     * @return unknown_type
     */
    public function delete($params)
    {
    	if(User::getPermission($this->permissionPrefix."_can_delete"))
    	{
            $data = $this->model->getWithField($this->model->getKeyField(),$params[0]);
            $this->model->delete($this->model->getKeyField(),$params[0]);
            User::log("Deleted " . $this->model->name, $data[0]);
            Application::redirect("{$this->urlPath}?notification=Successfully+deleted+".strtolower($this->label));
    	}
    }

    public function audit($params)
    {
        $table = new MultiModelTable(null);
        if(count($this->historyModels) > 0)
        {
            $models = implode("', '", $this->historyModels);
        }
        else
        {
            $models = $this->modelName;
        }
        
        $table->setParams(
            array(
                'fields' => array(
                    'system.audit_trail.audit_trail_id',
                    'system.audit_trail.audit_date',
                    'system.audit_trail.description',
                    'system.users.user_name',
                    'system.users.first_name',
                    'system.users.last_name',
                    'system.users.other_names'
                ),
                'conditions' => "item_id = '{$params[0]}' AND item_type in ('$models')",
                'sort_field' => 'audit_trail_id DESC'
            )
        );
        $table->useAjax = true;
        return $table->render();
        
    }
    
    public function bulkdelete()
    {
        $this->model->delete("{$this->model->getKeyField('primary')} in (" . implode(",", json_decode($_GET['ids'])) . ")");
        Application::redirect($this->urlPath);
    }
    
    public function notes($params)
    {
        $this->label = "Notes on item";        
        $notesManager = new MCNotesManager();
        $notesManager->setId($params[0]);
        
        if($params[1] === 'delete')
        {
            $notesManager->deleteNote($params[2]);
        }
        else
        {
            $notesManager->manage();            
        }
    }

    /**
     * Return a standard set of permissions which allows people within certain
     * roles to access only parts of this model controller.
     *
     * @see lib/controllers/Controller#getPermissions()
     * @return Array
     */
    public function getPermissions()
    {
        return array
        (
            array("label"=>"Can add {$this->label}",    "name"=> $this->permissionPrefix . "_can_add"),
            array("label"=>"Can edit {$this->label}",   "name"=> $this->permissionPrefix . "_can_edit"),
            array("label"=>"Can delete {$this->label}", "name"=> $this->permissionPrefix . "_can_delete"),
            array("label"=>"Can view {$this->label}",   "name"=> $this->permissionPrefix . "_can_view"),
            array("label"=>"Can export {$this->label}", "name"=> $this->permissionPrefix . "_can_export"),
            array("label"=>"Can import {$this->label}", "name"=> $this->permissionPrefix . "_can_import"),
            array("label"=>"Can view audit trail for {$this->label}", "name"=> $this->permissionPrefix . "_can_audit"),
            array("label"=>"Can view notes for {$this->label}", "name"=> $this->permissionPrefix . "_can_view_notes"),
            array("label"=>"Can create notes for {$this->label}", "name"=> $this->permissionPrefix . "_can_create_notes"),
        );
    }
    
    private function useNestedController(NestedModelController $controller, $args)
    {
        $controller->setParent($this);
        $id = array_shift($args);
        $controller->setParentItemId($id);
        $methodName = array_shift($args);
        $controller->setUrlPath("{$this->urlPath}/{$this->actionMethod}/$id");
        if($methodName == '') 
        {
            $methodName = 'getContents';
        }
        else
        {
            $controller->setActionMethod($methodName);
        }
        $this->setLabel($controller->getLabel());
        $method = new ReflectionMethod($controller, $methodName);
        return $method->invoke($controller, $args);
    }
    
    public function nest($controller, $args, $parameters = null)
    {        
        if(is_string($controller))
        {
            global $redirectedPackage;            
            
            $path = explode(".", $controller);
            $path[0] = $path[0] == '' ? $redirectedPackage : $path[0];
            $controller = Controller::load($path, false, $args[1]);
        }
        
        if(is_array($parameters))
        {
            $controller->setParentNameField($parameters['parent_name_field']);
            $controller->setParentItemId($parameters['parent_item_id']);
            $controller->setEntity($parameters['entity']);
        }
        
        return $this->useNestedController($controller, $args);
    }
    
    public function getUrlPath()
    {
        return $this->urlPath;
    }
    
    public function setUrlPath($urlPath)
    {
        $this->urlPath = $urlPath;
    }    
}

