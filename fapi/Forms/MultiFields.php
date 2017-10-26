<?php
/**
 * An extension of the MultiElements class which works only with single fields
 * instead of complex containers.
 * 
 * @author ekow
 * @ingroup Forms
 */
class MultiFields extends MultiElements
{
    public function __construct($template = null)
    {
        parent::__construct($template);
    }
    
    public function setTemplate($template)
    {
        $this->template = $template;
        $buttons = new ButtonBar();
        $buttons->setId("multi-form-buttons");
        $buttons->addButton("Clear");
        $buttons->buttons[0]->setId("clear_{$this->index}_--index--");
        $buttons->buttons[0]->addAttribute("onclick","fapiMultiFormRemove('{$this->index}', '--index--')");
        $this->template->setName($this->template->getName()."[]");
        $this->templateName = $template->getName();
        $template->setId("multiform-content---index--");
        return $this;        
    }
}
