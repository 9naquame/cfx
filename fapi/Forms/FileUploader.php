<?php

class FileUploader extends Field
{
    private $width = '200px';
    private $height = '200px';
            
    public function __construct($label="", $name="", $description="", $value= "")
    {
        Field::__construct($name, $value);
        Element::__construct($label, $description);
        $this->addAttribute("type","file");
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
    
    public function getValue()
    {
        parent::getValue();
        if (is_numeric($this->value)) {
            $value = PgFileStore::getData($this->value);
            return $value;
        } else {
            return $this->value;
        }
    }
    
    public function getDisplayValue()
    {
        parent::getDisplayValue();
        return $this->render();
    }

    public function render()
    {        
        $id = $this->getId();
        $name = $this->getName();
        $hidden = new HiddenField($name,$this->getValue());

        $this->addAttribute("name",$this->getName());
        $image = new ImageField('', "{$this->getName()}_image", '', $this->getValue());
        
        $ret = $hidden->setId($id)->render(); 
        $ret .= $image->setId("{$id}_image")->render();
        
        if ($this->getShowField()) {
            $ret .= "<input name='{$name}_file' id='{$id}_file' type='file' class='fapi-fileupload' style='width:{$this->width}'/>";

            $ret .= "<script>
                $('#{$id}_file').change(function(){
                    {$id}_readFapiImageUploadURL(this);
                });

                function {$id}_readFapiImageUploadURL(input) {
                    if (input.files && input.files[0]) {
                        var reader = new FileReader();

                        reader.onload = function (e) {
                            $('#{$id}_image').attr('src', e.target.result);
                            $('#{$id}').val(e.target.result);
                        }
                        reader.readAsDataURL(input.files[0]);
                    }
                }
            </script>";
        }
        
        return $ret;
    }
}