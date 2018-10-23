<?php

class ImageUploader extends Field
{
    public function __construct($label="", $name="", $description="", $value= "")
    {
        Field::__construct($name, $value);
        Element::__construct($label, $description);
        $this->addAttribute("type","file");
        $this->hasFile = true;
    }
    
    public function render()
    {
        $this->setAttribute("id",$this->getId());
        $this->addAttribute("name",$this->getName());
        $attributes = $this->getAttributes();
        
        $this->addAttribute("name",$this->getName());
        $ret .= "<input $attributes type='file' class='fapi-fileupload' style='width:{$this->width}'/>";
            
        $ret .= "<script>
            $('#{$this->getId()}').change(function(){
                readFapiImageUploadURL(this);
            });

            function readFapiImageUploadURL(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();

                    reader.onload = function (e) {
                        $('#imager').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }
        </script>";
        
        return $ret;
    }
}