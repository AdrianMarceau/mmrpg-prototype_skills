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
        $options->header_text = $this_robot->robot_name.'\'s '.$this_skill->skill_name;
        $options->extra_text = $this_robot->print_name().'\'s '.$this_skill->print_name().' prevents stat changes! ';
        $options->extra_text .= '<br /> '.$this_robot->print_name().'\'s '.$options->stat_type.' was not '.($options->boost_amount > 0 ? 'raised' : 'lowered').'!';
        $this_robot->set_frame('defend');
        $this_battle->events_create($this_robot, false, $options->header_text, $options->extra_text, array(
                'this_skill' => $this_skill,
                'canvas_show_this_skill_overlay' => false,
                'canvas_show_this_skill_underlay' => true,
                'event_flag_camera_action' => true,
                'event_flag_camera_side' => $this_robot->player->player_side,
                'event_flag_camera_focus' => $this_robot->robot_position,
                'event_flag_camera_depth' => $this_robot->robot_key
                )
            );
        $this_robot->reset_frame();
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
        $options->header_text = $this_robot->robot_name.'\'s '.$this_skill->skill_name;
        $options->extra_text = $this_robot->print_name().'\'s '.$this_skill->print_name().' protects against stat changes! ';
        $options->extra_text .= '<br /> '.$this_robot->print_name().'\'s '.$options->stat_type.' was not '.($options->break_amount > 0 ? 'lowered' : 'raised').'!';
        $this_robot->set_frame('defend');
        $this_battle->events_create($this_robot, false, $options->header_text, $options->extra_text, array(
                'this_skill' => $this_skill,
                'canvas_show_this_skill_overlay' => false,
                'canvas_show_this_skill_underlay' => true,
                'event_flag_camera_action' => true,
                'event_flag_camera_side' => $this_robot->player->player_side,
                'event_flag_camera_focus' => $this_robot->robot_position,
                'event_flag_camera_depth' => $this_robot->robot_key
                )
            );
        $this_robot->reset_frame();
        $options->break_amount = 0;
        $options->extra_text = '';
        return false;
    }
);
?>
