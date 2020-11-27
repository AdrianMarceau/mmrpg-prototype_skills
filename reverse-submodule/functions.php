<?
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'rpg-ability_stat-boost_before' => function($objects){
        extract($objects);
        if (!$options->is_fixed_amount){
            $options->return_early = true;
            if ($this_robot->has_item('guard-module')){ $options->boost_amount *= -1; return false; }
            if (!empty($options->extra_text)){ $options->extra_text .= ' <br /> '; }
            $options->extra_text .= $this_robot->print_name().'\'s '.$this_skill->print_name().' inverts stat changes! ';
            $return_value = rpg_ability::ability_function_stat_break($this_robot, $options->stat_type, $options->boost_amount, $this_ability, $options->success_frame, $options->failure_frame, $options->extra_text, true);
            $options->boost_amount *= -1;
            return $return_value;
        } else {
            return true;
        }
    },
    'rpg-ability_stat-break_before' => function($objects){
        extract($objects);
        if (!$options->is_fixed_amount){
            $options->return_early = true;
            if ($this_robot->has_item('guard-module')){ $options->break_amount *= -1; return false; }
            if (!empty($options->extra_text)){ $options->extra_text .= ' <br /> '; }
            $options->extra_text .= $this_robot->print_name().'\'s '.$this_skill->print_name().' inverts stat changes! ';
            $return_value = rpg_ability::ability_function_stat_boost($this_robot, $options->stat_type, $options->break_amount, $this_ability, $options->success_frame, $options->failure_frame, $options->extra_text, true);
            $options->break_amount *= -1;
            return $return_value;
        } else {
            return true;
        }
    }
);
?>
