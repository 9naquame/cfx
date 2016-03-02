<?php
/**
 * Description of WyfWrapper
 *
 * @author ekow
 */
class WyfWrapper
{
    private $table; 
    
    public function __construct()
    {
        $this->table = new WyfTable();
    }
    
    public function getWyfTable()
    {
        return $this->table;
    }
    
    public function getProperty($property)
    {
        switch($property)
        {
            case "table": return $this->table;
            default: throw new Exception("WYF Wrapper failed to matcch an unknown property [$property] please consider refactoring code.");
        }
    }
}

class WyfTable
{
    private $operations = [];
    
    public function addOperation($link,$label=null,$action=null)
    {
        $this->operations[] = [
            'link' => $link,
            'label' => $label,
            'action' => $action
        ];
    }
    
    public function getOperations()
    {
        return $this->operations;
    }
}