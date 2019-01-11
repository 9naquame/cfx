<?php

class FileUploader extends Field
{
    private $width = '200px';
    private $height = '200px';
            
    public function __construct($label="", $name="", $description="", $value= "", $download = "download")
    {
        Field::__construct($name, $value);
        Element::__construct($label, $description);
        $this->addAttribute("type","file");
        $this->hasFile = true;

        $this->download = $download;
    }
    
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }
    
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
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
        $name = $this->getName();
        $id = str_replace('-', '_', $this->getId());
        $hidden = new HiddenField($name,$this->getValue());

        $this->addAttribute("name",$this->getName());
        $image = new ImageField('', "{$this->getName()}_image", '', $this->getValue(), $this->width, $this->height);
        
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
        } else {
            $download =Application::labelize($this->download);
            $ret .= "<a href='{$this->getValue()}' download='{$this->download}'>Download {$download}</a>";
        }
        
        return $ret;
    }
}