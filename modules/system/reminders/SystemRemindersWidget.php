<?php
class SystemRemindersWidget extends Widget
{
    public $label = "System Reminders";
    private static $reminders = array();
    
    public static function addReminder($reminder)
    {
        if(is_string($reminder))
        {
            $reminder = array('reminder' => $reminder);
        }
        SystemRemindersWidget::$reminders[] = $reminder;
    }
    
    public function render()
    {
    	if(count(SystemRemindersWidget::$reminders) > 0)
    	{
            //$reminders = "<ul><li>" . implode("</li><li>", SystemRemindersWidget::$reminders) . "</li></ul>";
            $reminders = "<ul>";
            foreach(self::$reminders as $reminder)
            {
                $colour = $reminder['colour'] == '' ? '#e0e0e0' : $reminder['colour'];
                
                if(isset($reminder['timestamp']))
                {
                    $time = ucfirst(self::sentenceTime($reminder['timestamp'], array('elaborate_with'=>'ago')));
                    $time .= " - " . date("jS F, Y g:i A", $reminder['timestamp']);
                    $time = "<div style='color:#707070; margin-bottom:10px'>$time</div>";
                }
                else
                {
                    $time = '';
                }
                
                $reminders .= "<li style='border-left:10px solid $colour; border-bottom:1px solid $colour; margin-bottom:1px'> {$time} {$reminder['reminder']}</li>";
            }
            $reminders .= "</ul>";
            $numReminders = count(SystemRemindersWidget::$reminders);
            if($numReminders > 1) $plural = 's';
            return "<div id='reminders-widget'>
                    <h3> You have $numReminders reminder$plural</h3>
                    <div id='reminders-list'>".$reminders."</div>
                </div>";
    	}
    	else
    	{
    		return false;
    	}        
    }
    
    public static function sentenceTime($time, $options = null)
    {
        $elapsed = time() - $time;

        if($elapsed < 10)
        {
            $englishDate = 'now';
        }
        elseif($elapsed >= 10 && $elapsed < 60)
        {
            $englishDate = "$elapsed seconds";
        }
        elseif($elapsed >= 60 && $elapsed < 3600)
        {
            $minutes = floor($elapsed / 60);
            $englishDate = "$minutes minutes";
        }
        elseif($elapsed >= 3600 && $elapsed < 86400)
        {
            $hours = floor($elapsed / 3600);
            $englishDate = "$hours hour" . ($hours > 1 ? 's' : '');
        }
        elseif($elapsed >= 86400 && $elapsed < 172800)
        {
            $englishDate = "yesterday";
        }
        elseif($elapsed >= 172800 && $elapsed < 604800)
        {
            $days = floor($elapsed / 86400);
            $englishDate = "$days days";
        }
        elseif($elapsed >= 604800 && $elapsed < 2419200)
        {
            $weeks = floor($elapsed / 604800);
            $englishDate = "$weeks weeks";
        }
        elseif($elapsed >= 2419200 && $elapsed < 31536000)
        {
            $months = floor($elapsed / 2419200);
            $englishDate = "$months months";
        }
        elseif($elapsed >= 31536000)
        {
            $years = floor($elapsed / 31536000);
            $englishDate = "$years years";
        }

        switch($options['elaborate_with'])
        {
            case 'ago':
                if($englishDate != 'now' && $englishDate != 'yesterday')
                {
                    $englishDate .= ' ago';
                }
                break;
        }

        return $englishDate;
    }    
    
}
