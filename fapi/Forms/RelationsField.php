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
    protected $subTag;

    private $filter;
    private $bind = [];
    
    public function __construct($label, $name, $modelName, $mainField, $subField, $mainTag = null, $subTag = null)
    {
        $this->setLabel($label);
        
        $this->mainSelectionList = new SelectionList();
        $this->subSelectionList = new SelectionList();
        $this->setName($name);
        
        $this->model = Model::load($modelName);
        $this->keyField = $this->model->keyField;
        $this->subSort = $subTag ? "$subTag asc" : "$subField asc";
        $this->mainSort = $mainTag ? "$mainTag asc" : "$mainField asc";
        
        $this->mainTag = $mainTag ? $mainTag : $mainField;
        $this->subTag = $subTag ? $subTag : $subField;
        $this->mainField = $mainField;
        $this->subField = $subField;
        
        $info = $this->model->get([
            "fields" => [$mainField, $this->mainTag],
            "distinct" => true,
            "sort_field" => $this->mainSort
        ], Model::MODE_ARRAY);
        
        foreach ($info as $inf) {
            $tag = $this->mainTag ? $inf[1] : $inf[0];
            $this->mainSelectionList->addOption($inf[0], $tag);
        }
        
    }

    public function setValue($value)
    {
        parent::setValue($value);
        $this->subSelectionList = new SelectionList();
        
        if($value == "") {
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
    
    public function setName($name)
    {
        parent::setName($name);        
        $this->mainSelectionList->setId($name."_main");
        $this->subSelectionList->setName($name); 
        return $this;
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
        $sort = explode(" ", $this->subSort);
        $key = str_replace(array(".","[]"),array("_",""), $this->name);
        $this->mainSelectionList->addAttribute("onchange","fapi_change_{$key}()");        
        $object = [
            "model" => $this->model->package,
            "format" => "json",
            "fields" => [$this->mainField, $this->subField],
            "sortField" => $sort[0],
            "and_conditions"=>$this->filter,
            'and_bound_data' => $this->bind,
            "sort" => $sort[1] ? $sort[1] : 'desc'
        ];

        $path = Application::$prefix."/system/api/query";
        $params = "object=".urlencode(base64_encode(serialize($object)))."&";
        $params .= "conditions=".urlencode("{$this->model->getDatabase()}.{$this->mainTag}==");

        return $this->mainSelectionList->render().
               "<br/>".
                $this->subSelectionList->render()
                ."<script type='text/javascript'>
                    function fapi_change_{$key}() {
                        document.getElementById('{$this->name}').innerHTML='<option></option>';
                        $.ajax({
                            type:'GET',
                            url:'$path',
                            dataType: 'json',
                            data: '$params'+escape(document.getElementById('{$this->name}_main').value)+',',
                            success: function (responses) {
                                var list = document.getElementById('{$this->name}');
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