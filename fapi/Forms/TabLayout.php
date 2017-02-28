<?php
/**
 * A special container for containing Tab elements. This makes it possible
 * to layout form elements in a tab fashion. The TabLayout container takes
 * the tab as the elements it contains.
 * @ingroup Forms
 */
class TabLayout extends Container
{
    protected $tabs = array();

    /**
     * Adds a tab to the tab layout.
     * @param $tab The tab to be added to the tab layout.
     */
    public function add()
    {
        $tabs = func_get_args();
        foreach($tabs as $tab)
        {
            $this->tabs[] = $tab->getLegend();
            $this->elements[] = $tab;
            $tab->addAttribute("id","fapi-tab-".strval(count($this->tabs)-1));
            $tab->parent = $this;

            if(count($this->tabs)==1)
            {
                $tab->addCSSClass("fapi-tab-seleted");
            }
            else
            {
                $tab->addCSSClass("fapi-tab-unselected");
            }
        }
        return $this;
    }

    public function validate()
    {
        $retval = true;
        foreach($this->elements as $element)
        {
            if($element->validate()==false)
            {
                $retval=false;
                $element->addCSSClass("fapi-tab-error");
                $this->error = true;
                array_push($this->errors,"There were some errors on the ".$element->getLegend()." tab");
            }
        }
        return $retval;
    }

    /**
     * Renders all the tabs.
     */
    public function render()
    {
        $ret = "<div class='fapi-tab-layout'><ul class='fapi-tab-list ".$this->getCSSClasses()."'>";
        for($i=0; $i<count($this->tabs); $i++)
        {
            $ret .= "<li id='fapi-tab-top-$i' onclick='fapiSwitchTabTo($i)' class='".($i==0?"fapi-tab-selected":"fapi-tab-unselected")."'>".$this->tabs[$i]."</li>";
        }
        $ret .= "</ul><div class='fapi-tabs-wrapper'>";
        foreach($this->elements as $element)
        {
            $ret .= $element->render();
        }
        
        $ret .= "</div></div>";
        
        $ret .= "<style>
            /************************ 
            TAB LAYOUT 
            *************************/ 
            ul.fapi-tab-list { 
                margin: 0px; 
                padding: 0px; 
                list-style: none; 
                cursor:pointer; 
            } 
            
            li.fapi-tab-selected { 
                background-color: #F1F1F1; 
                font-weight: bold;
                padding-top: 10px; 
                -moz-border-radius-topleft: 4px; 
                -moz-border-radius-topright: 4px; 
                -webkit-border-radius-topleft: 4px;	
                -webkit-border-radius-topright: 4px;	
            } 
            
            li.fapi-tab-unselected { 
                background-color: #FAFAFA; 
                -moz-border-radius-topleft: 4px; 
                -moz-border-radius-topright: 4px; 
                -webkit-border-radius-topleft: 4px;	
                -webkit-border-radius-topright: 4px; 
                background-image:url(../imgs/tabbg.gif); 
            } 
            
            ul.fapi-tab-list li {
                float: left; 
                margin-right: 5px; 
                padding: 15px; 
            } 
            
            ul.fapi-tab-list li a { 
                text-decoration: none; 
                color: black; 
                font-weight: bold; 
                padding: 5px; 
                padding-left: 10px; 
                padding-right: 10px; 
            } 
            
            div.fapi-tab-unselected { 
                display: none; 
            } 
            
            div.fapi-tab { 
                background-color: #F1F1F1; 
                border:1px #404040; 
                clear: both; 
                padding: 10px; 
            } 
        </style>";
        return $ret;
    }
}
