<?php
/**
 * A special field for displaying data from models for selection.
 * @author James Ekow Abaka Ainooson <jainooson@gmail.com>
 */
class ModelField extends SelectionList
{
    /**
     * @var Model
     */
    private $model;
    private $valueField;
    private $info;
    
    protected $conditions;


    /**
     * Creates a new ModelField. The example below creates a ModelField which
     * lists client accounts for selection.
     * 
     * @code
     * $clients = new ModelField("brokerage.setup.clients.client_id", "account");
     * @endcode
     * @param $path The full path to the field in the module which is to be returned by this field.
     * @param $value The name of the field from the model whose value should be displayed in the list.
     */
    public function __construct($path,$value, $sort = null)
    {
        global $redirectedPackage;
        
        $this->info = Model::resolvePath($path);
        $this->model = Model::load((substr($this->info["model"],0,1) == "." ? $redirectedPackage: "") . $this->info["model"]);
        $this->valueField = $value;
        $this->sortField = $sort ? $sort : $value;
        $field = $this->model->getFields(array($value));
        
        $this->params['fields'] = [$this->info["field"],$this->valueField];
        $this->params['sort_field'] = $this->sortField;

        $this->setLabel($field[0]["label"]);
        $this->setDescription($field[0]["description"]);
        $this->setName($this->info["field"]);
    }
    
    public function setConditions($filter, $bind)
    {
        $this->params['filter'] = $filter;
        $this->params['bind'] = $bind;
        return $this;
    }
    
    public function render()
    {        
        $data = $this->model->get($this->params, Model::MODE_ARRAY);

        foreach($data as $datum)
        {
            if($datum[1] == ""){
                $this->addOption($datum[0]);
            } else {
                $this->addOption($datum[1],$datum[0]);
            }
        }         
        
        return parent::render();
    }
}
