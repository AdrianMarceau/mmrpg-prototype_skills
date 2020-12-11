<?
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'skill_function_onload' => function($objects){
        extract($objects);
        $this_skill->priority = -5;
        return true;
    },
    'rpg-ability_stat-boost_before' => function($objects){
        extract($objects);
        if ($this_robot !== $initiator_robot){ return; }
        if (!$options->is_fixed_amount){
            $trigger_text = $this_robot->print_name().'\'s '.$this_skill->print_name().' doubles stat boosts! ';
            if (!empty($options->extra_text)){ $options->extra_text .= ' <br /> '; }
            $options->extra_text .= $trigger_text;
            $invert_boost = $options->boost_amount < 0 ? true : false;
            $options->boost_amount *= 2;
            if ($invert_boost){ $options->boost_amount *= -1; }
        }
        return true;
    }
);
?>
