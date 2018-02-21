<?php

class RelationsField extends Field
{
    protected $mainSelectionList;
    protected $subSelectionList;
    protected $model;
    
    protected $keyField;
    protected $mainSort;
    protected $subSort;
    
    protected $mainField;
    protected $subField;
    protected $mainTag;

    private $filter;
    private $bind = [];
    
    public function __construct($label, $name, $modelName, $mainField, $subField, $mainTag = null, $mainSort = null, $subSort = null)
    {
        $this->setName($name);
        $this->setLabel($label);
        $this->subField = $subField;
        $this->mainField = $mainField;
        $this->model = Model::load($modelName);
        $this->keyField = $this->model->keyField;
        $this->mainSelectionList = new SelectionList();
        $this->mainTag = $mainTag ? $mainTag : $mainField;
        $this->subSort = $subSort ? $subSort : "$subField asc";
        $this->mainSort = $mainSort ? $mainSort : $this->mainTag;
    }
    
    public function setValue($value)
    {
        parent::setValue($value);
        $this->subSelectionList = new SelectionList();
        
        if ($value == "") {
            $this->mainSelectionList->setValue(null);
            $this->subSelectionList->setValue(null);
            return;
        }
        
        $mainValue = $this->model->get([
            "fields" => [$this->mainTag],
            "filter" => "{$this->keyField} = ?",
            "bind" => [$value]
        ], Model::MODE_ARRAY, false, false);
            
        $this->mainSelectionList->setValue($mainValue[0][0]);
        
        $subValues = $this->model->get([
            "fields" => [$this->subField, $this->keyField],
            "filter" => "{$this->mainTag} = ?",
            "bind" => [$mainValue[0][0]],
            "sort_field" => $this->subSort
        ], Model::MODE_ARRAY, false, false);
            
        foreach($subValues as $subValue) {
            $this->subSelectionList->addOption($subValue[0], $subValue[1]);
        }
        
        $this->subSelectionList->setValue($value);
    }
    
    public function setConditions($filter, $bindData = [])
    {
        $this->filter = $filter;
        $this->bind = $bindData;
        return $this;
    }
    
    public function setWithDisplayValue($value) 
    {
//        $parts = explode("//", $value);
//        $mainId = $this->mainModel->getKeyField();
//        
//        $mainField = Model::resolvePath($this->mainModelField);
//        $mainField = $mainField['field'];
//        
//        $subField = Model::resolvePath($this->subModelField);
//        $subField = $subField['field'];
//        
//        $possibleMainItem = reset($this->mainModel->getWithField2("trim($mainField)", trim($parts[0])));
//        if($possibleMainItem === false)
//        {
//            parent::setValue(null);
//            return;
//        }
//        
//        $possibleSubItem = reset($this->subModel->get(
//            array(
//                    'filter' => "$mainId = ? and trim($subField) = ?",
//                    'bind' => [$possibleMainItem[$mainId],trim($this->mainModel->escape($parts[1]))]
//                )
//            , Model::MODE_ASSOC, false, false
//        ));
//        
//        parent::setValue($possibleSubItem[$this->getName()]);
    }

//    public function getDisplayValue()
//    {
//        $value = $this->getValue();
//        if($value=="") return;
//        
//        $data = reset(SQLDBDataStore::getMulti(
//            array(
//                'fields' => array(
//                    $this->mainModelField,
//                    $this->subModelField
//                ),
//                'filter' => "{$this->subModel->database}.{$this->name} = ?",
//                'bind' => [$value]
//            )
//        ));
//        
//        return array_shift($data) ." // ". array_shift($data);
//    }

    public function render()
    {
        $info = $this->model->get([
            "fields" => [$this->mainField, $this->mainTag],
            "distinct" => true,
            "filter" => $this->filter,
            "bind" => $this->bind,
            "sort_field" => $this->mainSort
        ], Model::MODE_ARRAY);
        
        foreach ($info as $inf) {
            $tag = $inf[1] ? $inf[1] : $inf[0];
            $this->mainSelectionList->addOption($inf[0], $tag);
        }
        
        $sort = explode(" ", $this->subSort);
        $key = str_replace(array(".","[]"),array("_",""), $this->name);
        $this->mainSelectionList->addAttribute("onchange","fapi_change_{$key}(this)");
        
        $object = [
            "model" => $this->model->package,
            "format" => "json",
            "fields" => [$this->subField, $this->keyField],
            "sortField" => $sort[0],
            "and_conditions"=>$this->filter,
            'and_bound_data' => $this->bind,
            "sort" => $sort[1] ? $sort[1] : 'desc'
        ];
        
        $this->subSelectionList->setName($this->name);
        $path = Application::$prefix."/system/api/query";
        $params = "object=".urlencode(base64_encode(serialize($object)))."&";
        $params .= "conditions=".urlencode("{$this->model->getDatabase()}.{$this->mainTag}==");

        return $this->mainSelectionList->render().
               "<br/>".
                $this->subSelectionList->render()
                ."<script type='text/javascript'>
                    function fapi_change_{$key}(element) {
                        var list = element.nextSibling.nextSibling;
                        list.innerHTML='<option></option>';
                        
                        $.ajax({
                            type:'GET',
                            url:'$path',
                            dataType: 'json',
                            data: '$params'+escape(element.value)+',',
                            success: function (responses) {
                                var n = list.length;
                                var i;
                                
                                for (i = 0; i < n; i++) {
                                    list.remove(0);
                                } try {
                                    list.add(new Option('',''), null);
                                } catch(e) {
                                    list.add(new Option('',''));
                                } for (i = 0; i < responses.length; i++) {
                                    try {
                                        list.add(new Option(responses[i].{$this->subField}, responses[i].{$this->keyField}), null);
                                    } catch(e) {
                                        list.add(new Option(responses[i].{$this->subField}, responses[i].{$this->keyField}));
                                    }
                                }   
                            }
                        });
                    }
                  </script>";
    }
}