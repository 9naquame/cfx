<?php

class NestedModelController extends ModelController
{
    public $_showInMenu = false;
    protected $parentItemId;
    private $methodName;
    private $parentNameField;
    private $entity;

    /**
     *
     * @var ModelController
     */
    protected $parentController;
    
    public function getLabel()
    {
        $entity = reset($this->parentController->model[$this->parentItemId]);
        return $this->entity . ($this->entity == '' ? '' : ' of ') .  "{$this->parentController->label} ({$entity[$this->parentNameField]})";
    }
    
    public function setLabel($label)
    {
        $this->parentController->setLabel($label);
    }
    
    public function setupListView()
    {
        $this->listView->setListConditions(
            "{$this->parentController->model->getKeyField()} = '{$this->parentItemId}'"
        );
    }
    
    public function setMethodName($methodName)
    {
        $this->methodName = $methodName;
    }
    
    public function setParent(ModelController $parent)
    {
        $this->parentController = $parent;
    }
    
    public function setParentItemId($parentItemId)
    {
        $this->parentItemId = $parentItemId;
    }
    
    public function setParentNameField($parentNameField)
    {
        $this->parentNameField = $parentNameField;
    }
    
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }
    
    public function getParentItemId()
    {
        return $this->parentItemId;
    }
    
    public function getParentController()
    {
        return $this->parentController;
    }
    
    public function getForm()
    {
        $form = parent::getForm();
        $form->add(
            Element::create('HiddenField', $this->parentController->model->getKeyField(), $this->parentItemId)
        );        
        return $form;
    }  
}

