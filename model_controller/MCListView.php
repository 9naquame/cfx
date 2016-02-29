<?php
class MCListView
{
    /**
     * An instance of the Table class that is stored in here for the purpose
     * of displaying and also manipulating the model's data.
     * @var MultiModelTable
     */
    private $table;

    /**
     * An instance of the Toolbar class. This toolbar is put on top of the list
     * which is used to display the model.
     * @var Toolbar
     */
    private $toolbar;
    
    private $model;
    
    private $listFields;
    private $hasAddOperation = true;
    private $hasDeleteOperation = true;
    private $hasEditOperation = true;
    private $urlPath;
    private $permissionPrefix;
    private $listConditions;
    private $newLabel = 'New';

    public function __construct($params)
    {
        $this->toolbar = new Toolbar();
        $this->urlPath = $params['url_path'];
        $this->table = new MultiModelTable($this->urlPath . "/");
        $this->table->useAjax = true;  
        $this->model = $params['model'];
        $this->listFields = $params['list_fields'];
        $this->permissionPrefix = $params['permission_prefix'];
    }
    
    public function setNewLabel($newLabel)
    {
        $this->newLabel = $newLabel;
    }
    
    public function setListFields($listFields)
    {
        $this->listFields = $listFields;
    }
    
    public function setListConditions($listConditions)
    {
        $this->listConditions = $listConditions;
    }
    
    public function addOperation($link, $label, $action = null)
    {
        $this->table->addOperation($link, $label, $action);
    }
    
    public function addConfirmableOperation($link, $label, $message)
    {
        $this->table->addOperation($link, $label, "javascript:wyf.confirmRedirect('{$message}', '{$this->urlPath}/%path%/%key%')");
    }
    
    public function addBulkOperation($link, $label, $confirm = false)
    {
        $this->toolbar->addLinkButton(
            $label,
            $confirm === false ?
                "{$this->urlPath}/{$link}" :
                "javascript:wyf.tapi.confirmBulkOperation(\"$confirm\",\"{$this->urlPath}/{$link}\")"
        );
    }
    
    public function setModel($model)
    {
        $this->model = $model;
    }
    
    private function setupCRUDOperations()
    {
        if($this->hasAddOperation && (User::getPermission($this->permissionPrefix . "_can_add") || $this->forceAddOperation))
        {
            $this->toolbar->addLinkButton($this->newLabel, $this->urlPath . "/add");
        }  
        if($this->hasEditOperation && (User::getPermission($this->permissionPrefix."_can_edit") || $this->forceEditOperation))
        {
            $this->table->addOperation("edit","Edit");
        }
        if($this->hasDeleteOperation && (User::getPermission($this->permissionPrefix."_can_delete") || $this->forceDeleteOperation))
        {
            $this->table->addOperation("delete","Delete","javascript:wyf.confirmRedirect('Are you sure you want to delete','{$this->urlPath}/%path%/%key%')");
            $this->addBulkOperation("bulkdelete", "Delete Selected", "Are you sure you want to delete ");
        }        
    }

    private function setupImportExportOperations()
    {
        if(User::getPermission($this->permissionPrefix."_can_export"))
        {
            $exportButton = new MenuButton("Export");
            $exportButton->addMenuItem("PDF", "#","wyf.openWindow('".$this->urlPath."/export/pdf')");
            $exportButton->addMenuItem("CSV Data", "#","wyf.openWindow('".$this->urlPath."/export/csv')");
            $exportButton->addMenuItem("HTML", "#","wyf.openWindow('".$this->urlPath."/export/html')");
            $exportButton->addMenuItem("Excel", "#","wyf.openWindow('".$this->urlPath."/export/xls')");
            $this->toolbar->add($exportButton);
        }    
        if($this->hasAddOperation && User::getPermission($this->permissionPrefix."_can_import"))
        {
            $this->toolbar->addLinkButton("Import",$this->urlPath."/import");
        }          
    }

    /**
     * Sets up the list that is shown by default when the Model controller is
     * used. This list normall has the toolbar on top and the table below.
     * This method performs checks to ensure that the user has permissions
     * to access a particular operation before it renders the operation.
     */
    protected function setupList()
    {
        $this->setupCRUDOperations();
        $this->setupImportExportOperations();
        
        $this->toolbar->addLinkButton("Search","#")->setLinkAttributes(
            "onclick=\"wyf.tapi.showSearchArea('{$this->table->name}')\""
        );
            
        if(User::getPermission($this->permissionPrefix."_can_view"))
        {
            $this->table->addOperation("view","View");
        }
        
        if(User::getPermission($this->permissionPrefix."_can_audit"))
        {
            $this->table->addOperation("audit","History");
        }          
        
        if(User::getPermission($this->permissionPrefix."_can_view_notes"))
        {
            $this->table->addOperation("notes","Notes");
        }          
    }
    
    private function getDefaultFieldNames()
    {
        $fieldNames = array();
        $keyField = $this->model->getKeyField();
        $fieldNames[$keyField] = "{$this->model->package}.{$keyField}";
        $fields = $this->model->getFields();

        foreach($fields as $i => $field)
        {
            if($field["reference"] == "")
            {
                $fieldNames[$i] = $this->model->package.".".$field["name"];
            }
            else
            {
                $modelInfo = Model::resolvePath($field["reference"]);
                $fieldNames[$i] = $modelInfo["model"] . "." . $field["referenceValue"];
            }
        }   
        
        return $fieldNames;
    }
    
    public function render()    
    {
        if(count($this->listFields) > 0)
        {
            $fieldNames = $this->listFields;
        }
        else
        {
            $fieldNames = $this->getDefaultFieldNames();
        }
        
        foreach($fieldNames as $i => $fieldName)
        {
            $fieldNames[$i] = substr($fieldName, 0, 1) == "." ? $this->redirectedPackage . $fieldName : $fieldName;
        }
        
        $this->setupList();
        $params["fields"] = $fieldNames;
        $params["page"] = 0;
        $params["sort_field"] = array(
            array(
                "field" =>  end(explode('.', reset($fieldNames))),
                "type"  =>  "DESC"
            )
        );
        $params['hardConditions'] = $this->listConditions;
        
        $this->table->setParams($params);
        return $this->toolbar->render().$this->table->render();
    }
    
    public function setHasAddOperation($hasAddOperation)
    {
        $this->hasAddOperation = $hasAddOperation;
    }
    
    public function setHasEditOperation($hasEditOperation)
    {
        $this->hasEditOperation = $hasEditOperation;
    }
    
    public function setHasDeleteOperation($hasDeleteOperation)
    {
        $this->hasDeleteOperation = $hasDeleteOperation;
    }
}