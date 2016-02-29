<?php

class MCNotesManager
{
    private $id;
    
    public function setId($id)
    {
        $this->id = $id;
    }
    
    public function deleteNote($id)
    {
        $model = Model::load('system.notes');
        $model->delete('note_id', $id);
        Application::redirect("{$this->path}/notes/{$this->id}");        
    }
    
    protected function postNewNote()
    {
        $noteAttachments = Model::load('system.note_attachments');
        
        $model = Model::load('system.notes');
        $model->datastore->beginTransaction();
        $data = array(
            'note' => $_POST['note'],
            'note_time' => time(),
            'item_id' => $this->id,
            'user_id' => $_SESSION['user_id'],
            'item_type' => $this->model->package
        );
        $model->setData($data);
        $id = $model->save();


        for($i = 1; $i < 5; $i++)
        {
            $file = $_FILES["attachment_$i"];
            if($file['error'] == 0)
            {
                $noteAttachments->setData(array(
                    'note_id' => $id,
                    'description' => $file['name'],
                    'object_id' => PgFileStore::addFile($file['tmp_name']),
                ));
                $noteAttachments->save();
            }
        }            
        $model->datastore->endTransaction();

        Application::redirect("{$this->urlPath}/notes/{$params[0]}");        
    }
    
    public function manage()
    {
            
        if(isset($_POST['is_form_sent']))
        {
            $this->postNewNote();
            return;
        }
        
        $notes = SQLDBDataStore::getMulti(
            array(
                'fields' => array(
                    'system.notes.note_id',
                    'system.notes.note',
                    'system.notes.note_time',
                    'system.users.first_name',
                    'system.users.last_name'
                ),
                'conditions' => Model::condition(array(
                        'item_type' => $this->model->package,
                        'item_id' => $params[0]
                    )
                )
            )
        );
        
        foreach($notes as $i => $note)
        {
            $attachments = $noteAttachments->getWithField2('note_id', $note['note_id']);
            foreach($attachments as $j => $attachment)
            {
                $attachments[$j]['path'] = PgFileStore::getFilePath($attachment['object_id'], $attachment['description']);
            }
            $notes[$i]['attachments'] = $attachments;
        }
        
        $form = Element::create('Form')->add(
            Element::create('TextArea', 'Note', 'note'), 
            Element::create('FieldSet', 'Add Attachments')->add(
                Element::create('UploadField', 'Attachment', 'attachment_1'),
                Element::create('UploadField', 'Attachment', 'attachment_2'),
                Element::create('UploadField', 'Attachment', 'attachment_3'),
                Element::create('UploadField', 'Attachment', 'attachment_4')
            )->setId('attachments')->setCollapsible(true)
        )->setRenderer('default');
        
        return $this->arbitraryTemplate(
            'lib/controllers/notes.tpl', 
            array(
                'form' => $form->render(),
                'notes' => $notes,
                'route' => $this->path,
                'id' => $params[0]
            )
        );
        
    }
}