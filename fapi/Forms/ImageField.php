<?php

class ImageField extends Field
{
    protected $absolute;
    protected $alt = 'Upload image';

    public function __construct($label="", $name="", $value="",  $default="", $width = '200px', $height = '200px')
    {
        Field::__construct($name, $value);
        Element::__construct($label);
        
        $this->addAttribute("type","img");
        $this->default = $default;
        $this->height = $height;
        $this->width = $width;
    }
    
    public function setAbsolute($absolute)
    {
        $this->absolute = $absolute;
        return $this;
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
        $this->alt = $alt;
    }
    
    public function setDefault($default)
    {
        $this->default = $default;
    }
    
    public function getImage()
    {
        return $this->absolute ? "/{$this->getValue()}" : $this->getValue();
    }

    public function render()
    {
        $this->setAttribute("id",$this->getId());
        $this->addAttribute("name",$this->getName());
        $style = "width:{$this->width};height:{$this->height};right:0;left:0;";
        $file = file_exists($this->getValue()) ? $this->getImage() : $this->default;

        $src = $file ? "src={$file}" : '';
        $alt = $this->alt ? "{$this->alt}" : '';
        $ret .= "<img id='{$this->getId()}' {$src} {$alt} style='$style'><div> ";

        return $ret;
    }
}