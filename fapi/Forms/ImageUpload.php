<?php

class ImageUpload extends Field
{
    protected $script;
    protected $width = '200px';
    protected $height = '200px';
    protected $alt = 'your image';

    public function __construct($label="", $name="", $description="", $value="", $destinationFile="")
    {
        Field::__construct($name, $value);
        Element::__construct($label, $description);
        $this->hasFile = true;
    }
    
    public function setWidth($width)
    {
        $this->width = $width;
    }

    public function setHeight($height)
    {
        $this->height = $height;
    }

    public function render()
    {
        $this->setAttribute("id",$this->getId());
        $this->addAttribute("name",$this->getName());
        $style = "width:{$this->width};height:{$this->height};right:0;left:0;";

        $ret .= "<div>
            <div><img id='{$this->getId()}_image' src='#' alt='{$this->alt}' style='$style'><div> 
            <div><input type='file' id='{$this->getId()}_file' class= 'fapi-fileupload' style='width:{$this->width};bottom:0'/><div>
        </div>";
            
        $ret .= "<script>
            $('#{$this->getId()}_file').change(function(){
                readFapiImageUploadURL(this);
            });

            function readFapiImageUploadURL(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();

                    reader.onload = function (e) {
                        $('#{$this->getId()}_image').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }
        </script>";
        
        return $ret;
    }
}