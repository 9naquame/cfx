<?php

class ImageField extends Field
{
    protected $alt = 'Upload image';

    public function __construct($label="", $name="", $value="", $width = '200px', $height = '200px')
    {
        Field::__construct($name, $value);
        Element::__construct($label);
        $this->addAttribute("type","img");
        $this->height = $height;
        $this->width = $width;
    }
    
    public function setWidth($width)
    {
        $this->width = $width;
    }

    public function setHeight($height)
    {
        $this->height = $height;
    }
    
    public function setAlt($alt)
    {
        $this->height = $alt;
    }

    public function render()
    {
        $this->setAttribute("id",$this->getId());
        $this->addAttribute("name",$this->getName());
        $style = "width:{$this->width};height:{$this->height};right:0;left:0;";
        $ret .= "<img id='{$this->getId()}' src='{$this->getValue()}' alt='{$this->alt}' style='$style'><div> ";
        return $ret;
    }
}