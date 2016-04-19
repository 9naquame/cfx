<?php
/**
 * Description of WyfWrapper
 *
 * @author ekow
 */
class WyfWrapper
{
    private $table; 
    private $toolbar;
    
    public function __construct()
    {
        $this->table = new WyfTable();
        $this->toolbar = new WyfToolbar();
    }
    
    public function getWyfTable()
    {
        return $this->table;
    }
    
    public function getWyfToolbar()
    {
        return $this->toolbar;
    }
    
    public function getProperty($property)
    {
        switch($property)
        {
            case "table": return $this->table;
            case "toolbar": return $this->toolbar;
            default: 
                throw new Exception("WYF Wrapper failed to match an unknown property [$property] please consider refactoring code.");
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

class WyfToolbar
{
    private $linkButtons = [];
    
    public function addLinkButton($label, $link)
    {
        $this->linkButtons[] = ['label' => $label, 'link' => $link];
    }
    
    public function getLinkButtons()
    {
        return $this->linkButtons;
    }
}