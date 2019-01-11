<?php
/**
 * Works just like the ModelField but presents the user with a suggestion of
 * correct values as the user types in. Provides an interface which is much like
 * a search box.
 * 
 * @author James Ekow Abaka Ainooson <jainooson@gmail.com>
 * @ingroup Forms
 */

class ModelSearchField extends Field
{
    protected $searchFields = array();
    protected $model;
    protected $storedField;
    public $boldFirst;
    private $storedFieldSet = false;
    private $andConditions;
    private $andBoundData = [];
    private $onChangeAttribute;
    private $useSearch = false;
    
    public function __construct($path=null,$value=null, $boldFirst = true)
    {
        if($path!=null)
        {
            $info = Model::resolvePath($path);
            if ($value=="" || $value == $info["field"]) {
                $value = $info["field"];
                $this->fieldName = $value;
            }
            $this->model = model::load($info["model"]);
            $field = $this->model->getFields(array($value));

            $this->setLabel($field[0]["label"]);
            $this->setDescription($field[0]["description"]);
            $this->setName($info["field"]);

            $this->addSearchField($value);
            $this->storedField = $info["field"];
        }
        
        $this->boldFirst = $boldFirst;
    }
    
    public function useSearch($boolean)
    {
        $this->useSearch = $boolean;
        return $this;
    }
    
    public function setAndConditions($andConditions, $bindData = [])
    {
        $this->andConditions = $andConditions;
        $this->andBoundData = $bindData;
        return $this;
    }
    
    public function setStoredField($field)
    {
    	$this->storedField = $field;
    	return $this;
    }
    
    /**
     * 
     * @param $model
     * @param $value
     * @return ModelSearchField
     */
    public function setModel($model,$value="")
    {
        $this->model = $model;
        $this->storedField = $value==""?$this->model->getKeyField():$value;
        return $this;
    }
    
    public function addSearchField($field)
    {
        $this->searchFields[] = $field;
        return $this;
    }
    
    public function onChangeJsFunction($params) 
    {
        $this->onChangeAttribute = $params;
        return $this;
    }
    
    public function render()
    {
        global $redirectedPackage;
        global $packageSchema;
        
        $name = $this->getName();
        $hidden = new HiddenField($name,$this->getValue());
        $id = $this->getId();
        $hidden->addAttribute("id", $id);        
        $ret = $hidden->render();
                
        if($this->storedFieldSet === false)
        {
            $this->addSearchField($this->storedField);
            $this->storedFieldSet = true;
        }
        
        $object = array
        (
            "model"=>$this->model->package,
            "format"=>"json",
            "fields"=>$this->searchFields,
            "limit"=>20,
            "conditions"=>"",
            "and_conditions"=>$this->andConditions,
            'and_bound_data' => $this->andBoundData,
            'redirected_package' => $redirectedPackage,
            'package_schema' => $packageSchema
        );
        $jsonSearchFields = array_reverse($this->searchFields);
        $object = base64_encode(serialize($object));
        $path = Application::$prefix."/system/api/query?object=$object";
        $fields = urlencode(json_encode($jsonSearchFields));
        
        $text = new TextField();
        $text->addAttribute("onkeyup","fapiUpdateSearchField('$id','$path','$fields',this,".($this->boldFirst?"true":"false").",'{$this->onChangeAttribute}')");
        $text->addAttribute("autocomplete","off");
        
        foreach($this->attributes as $attribute)
        {
            $text->addAttributeObject($attribute);
        }
        
        if($this->getValue()!="")
        {
            if ($this->fieldName) {
                $data = $this->model->get(['filter' => "{$this->fieldName} = ?", 'bind' => [trim($this->getValue())]]);
            } else {
                $data = $this->model[$this->getValue()];
            }
            for($i=1;$i<count($jsonSearchFields);$i++)
            {
                $val .= $data[0][$jsonSearchFields[$i]]." ";
            }
            if ($this->useSearch) {
                $text->setValue($this->getValue());
            } 
            $text->setValue($val);
        } else {
            $text->setValue('');
        }

        $text->setId($id."_search_entry");
        $useSearch = $this->useSearch ? 'true' : 'false';  
        $ret .= $text->render();
        $ret .= "<div class='fapi-popup' id='{$id}_search_area'></div>";
        $ret .= "<script type='text/javascript'>
            $(document).on('keydown blur change', function(event){
                if ($('#{$text->getId()}').val() === '') {
                    $('#{$id}').val('');
                } else if ($useSearch === true){
                    $('#{$id}').val($('#{$text->getId()}').val());
                }
            });
        </script>";
        return $ret;
    }
    
    public function setWithDisplayValue($value) 
    {
        $conditions = array();
        foreach($this->searchFields as $searchField)
        {
            $conditions[] = "{$searchField} = '{$value}'";
        }
        $conditions = implode(" OR ", $conditions);
        
        $item = $this->model->get(
            array(
                'fields' => array($this->getName()),
                'conditions' => $conditions
            )
        );
        
        $this->setValue($item[0][$this->getName()]);
    }

    public function getDisplayValue()
    {
        $jsonSearchFields = array_reverse($this->searchFields);
        if ($this->getValue() != '') {
            if ($this->fieldName) {
                $data = $this->model->get(['filter' => "{$this->fieldName} = ?", 'bind' => [trim($this->getValue())]]);
            } else {
                $data = $this->model[$this->getValue()];
            }
        } else {
            $data = [];
        }

        $val = "<b>".$data[0][$jsonSearchFields[0]]."</b> ";
        for($i=1;$i<count($jsonSearchFields);$i++)
        {
            $val .= $data[0][$jsonSearchFields[$i]]." ";
        } 
        if ($this->useSearch) {
            $val = $this->getValue();
        }
        return $val;
    }
}
