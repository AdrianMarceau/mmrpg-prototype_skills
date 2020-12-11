<?
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'skill_function_onload' => function($objects){
        extract($objects);
        $this_skill->priority = -10;
        return true;
    },
    'rpg-ability_stat-boost_before' => function($objects){
        extract($objects);
        if ($this_robot !== $recipient_robot){ return; }
        $options->return_early = true;
        if (empty($options->boost_amount)){ return false; }
        if (!empty($this_skill->skill_results['flag_'.$this_skill->skill_token.'_triggered'])){ return false; }
        else { $this_skill->skill_results['flag_'.$this_skill->skill_token.'_triggered'] = true; }
        if ($this_robot->has_item('reverse-module')){ $options->boost_amount *= -1; }
        $options->extra_text = $this_robot->print_name().'\'s '.$this_skill->print_name().' prevents stat changes! ';
        $options->extra_text .= '<br /> '.$this_robot->print_name().'\'s '.$options->stat_type.' was not '.($options->boost_amount > 0 ? 'raised' : 'lowered').'!';
        $this_skill->target_options_update(array('frame' => 'defend', 'success' => array(9, 0, 0, 10, $options->extra_text)));
        $this_robot->trigger_target($this_robot, $this_skill, array('prevent_default_text' => true));
        $options->boost_amount = 0;
        $options->extra_text = '';
        return false;
    },
    'rpg-ability_stat-break_before' => function($objects){
        extract($objects);
        if ($this_robot !== $recipient_robot){ return; }
        $options->return_early = true;
        if (empty($options->break_amount)){ return false; }
        if (!empty($this_skill->skill_results['flag_'.$this_skill->skill_token.'_triggered'])){ return false; }
        else { $this_skill->skill_results['flag_'.$this_skill->skill_token.'_triggered'] = true; }
        if ($this_robot->has_item('reverse-module')){ $options->break_amount *= -1; }
        $options->extra_text = $this_robot->print_name().'\'s '.$this_skill->print_name().' protects against stat changes! ';
        $options->extra_text .= '<br /> '.$this_robot->print_name().'\'s '.$options->stat_type.' was not '.($options->break_amount > 0 ? 'lowered' : 'raised').'!';
        $this_skill->target_options_update(array('frame' => 'defend', 'success' => array(9, 0, 0, 10, $options->extra_text)));
        $this_robot->trigger_target($this_robot, $this_skill, array('prevent_default_text' => true));
        $options->break_amount = 0;
        $options->extra_text = '';
        return false;
    }
);
?>
