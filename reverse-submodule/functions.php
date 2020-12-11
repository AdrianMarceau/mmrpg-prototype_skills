<?
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'rpg-ability_stat-boost_before' => function($objects){
        extract($objects);
        if ($this_robot !== $recipient_robot){ return; }
        if (!$options->is_fixed_amount){
            $options->return_early = true;
            $options->boost_amount *= -1;
            if ($this_robot->has_item('guard-module')){ return false; }
            if (!empty($options->extra_text)){ $options->extra_text .= ' <br /> '; }
            $options->extra_text .= $this_robot->print_name().'\'s '.$this_skill->print_name().' inverts stat changes! ';
            rpg_ability::ability_function_stat_break($this_robot, $options->stat_type, ($options->boost_amount * -1), $this_skill, array(
                'success_frame' => $options->success_frame,
                'failure_frame' => $options->failure_frame,
                'extra_text' => $options->extra_text,
                'is_redirect' => true
                ));
            $options->extra_text = '';
        } else {
            return true;
        }
    },
    'rpg-ability_stat-break_before' => function($objects){
        extract($objects);
        if ($this_robot !== $recipient_robot){ return; }
        if (!$options->is_fixed_amount){
            $options->return_early = true;
            $options->break_amount *= -1;
            if ($this_robot->has_item('guard-module')){ return false; }
            if (!empty($options->extra_text)){ $options->extra_text .= ' <br /> '; }
            $options->extra_text .= $this_robot->print_name().'\'s '.$this_skill->print_name().' inverts stat changes! ';
            rpg_ability::ability_function_stat_boost($this_robot, $options->stat_type, ($options->break_amount * -1), $this_skill, array(
                'success_frame' => $options->success_frame,
                'failure_frame' => $options->failure_frame,
                'extra_text' => $options->extra_text,
                'is_redirect' => true
                ));
            $options->extra_text = '';
        } else {
            return true;
        }
    }
);
?>
