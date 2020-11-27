<?
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'rpg-ability_stat-boost_middle' => function($objects){
        extract($objects);
        $options->return_early = true;
        if (!empty($this_skill->skill_results['flag_'.$this_skill->skill_token.'_triggered'])){ return false; }
        else { $this_skill->skill_results['flag_'.$this_skill->skill_token.'_triggered'] = true; }
        $options->extra_text = $this_robot->print_name().'\'s '.$this_skill->print_name().' prevents stat changes! ';
        $options->extra_text .= '<br /> '.$this_robot->print_name().'\'s '.$options->stat_type.' was not raised!';
        $this_skill->target_options_update(array('frame' => 'defend', 'success' => array(9, 0, 0, 10, $options->extra_text)));
        $this_robot->trigger_target($this_robot, $this_skill, array('prevent_default_text' => true));
        return false;
    },
    'rpg-ability_stat-break_middle' => function($objects){
        extract($objects);
        $options->return_early = true;
        if (!empty($this_skill->skill_results['flag_'.$this_skill->skill_token.'_triggered'])){ return false; }
        else { $this_skill->skill_results['flag_'.$this_skill->skill_token.'_triggered'] = true; }
        $options->extra_text = $this_robot->print_name().'\'s '.$this_skill->print_name().' protects against stat changes! ';
        $options->extra_text .= '<br /> '.$this_robot->print_name().'\'s '.$options->stat_type.' was not lowered!';
        $this_skill->target_options_update(array('frame' => 'defend', 'success' => array(9, 0, 0, 10, $options->extra_text)));
        $this_robot->trigger_target($this_robot, $this_skill, array('prevent_default_text' => true));
        return false;
    }
);
?>
