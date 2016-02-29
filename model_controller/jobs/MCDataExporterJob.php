<?php

class MCDataExporterJob extends ajumamoro\Ajuma
{
    public function run()
    {
        $this->go();
    }
    
    public function go()
    {
        $fieldNames = array();
        $headers = array();
        foreach($this->fields as $field)
        {
            $fieldNames[] = $field->getName();
            $headers[] = $field->getLabel();
        }
        
        $reportClass = strtoupper($this->format) . 'Report';
        $report = new $reportClass();
        
        $title = new TextContent($this->label);
        $title->style["size"] = 12;
        $title->style["bold"] = true;
        
        $this->model->setQueryResolve(false);
        $data = $this->model->get(array("fields"=>$fieldNames));
        
        if($_GET['template'] !== 'yes')
        {
            foreach($data as $j => $row)
            {
                for($i = 0; $i < count($row); $i++)
                {
                    $this->fields[$i]->setValue($row[$fieldNames[$i]]);
                    $data[$j][$fieldNames[$i]] = strip_tags($this->fields[$i]->getDisplayValue());
                }
            }
        }
        
        $table = new TableContent($headers,$data);
        $table->style["decoration"] = true;

        $report->add($title,$table);
        $report->output();
        die();
    }
    
}