<?php

class MCDefaultForm extends Form
{
    public function __construct(Model $model)
    {
        parent::__construct();
        $fields = $model->getFields();
        
        foreach($fields as $name => $field)
        {
            if($field['key'] == 'primary') continue;
            if($field['reference'] == '')
            {
                $method = "get{$field['type']}field";
                $element = $this->$method($field);
            }
            else
            {
                $element = new ModelField($field["reference"],$field["referenceValue"]);
            }
            
            $element->setRequired($field['required']);
            $element->setUnique($field['unique']);
            
            $this->add($element);
        }
        
        $this->addAttribute('style', 'width:50%');
    }
    
    private function getNumericField($field)
    {
        return $this->getStringField($field)->setAsNumeric();
    }
    
    private function getIntegerField($field)
    {
        return $this->getNumericField($field);
    }
    
    private function getDoubleField($field)
    {
        return $this->getNumericField($field);
    }
    
    private function getDateField($field)
    {
        return $this->getElement('DateField', $field);
    }
    
    private function getStringField($field)
    {
        return $this->getElement('TextField', $field);
    }
    
    private function getTextField($field)
    {
        return $this->getElement('TextArea', $field);
    }
    
    private function getBooleanField($field)
    {
        return $this->getElement('Checkbox', $field)->setCheckedValue('1');
    }
    
    private function getElement($element, $field)
    {
        return Element::create($element, $field['label'], $field['name'], $field['description']);
    }
}
