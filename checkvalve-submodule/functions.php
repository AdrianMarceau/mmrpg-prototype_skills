<?
/*
==================================================

THIS SKILL HAS PARAMETERS!

Required:
    - stat (string: attack/defense/speed)

Examples:
    {"stat":"attack"}
        would prevent the robot's attack from ever being lowered

==================================================
*/
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'skill_function_onload' => function($objects){
        extract($objects);
        $this_skill->priority = -10;
        return true;
    },
    'rpg-ability_stat-break_before' => function($objects){
        extract($objects);
        if ($this_robot !== $recipient_robot){ return; }
        if (empty($this_skill->flags['validated'])){ return false; }
        if (empty($options->stat_type)
            || ($this_skill->skill_parameters['stat'] !== 'all'
                && $this_skill->skill_parameters['stat'] !== $options->stat_type)){
            return false;
        }
        $options->return_early = true;
        if (empty($options->break_amount)){ return false; }
        if (!empty($this_skill->skill_results['flag_'.$this_skill->skill_token.'_triggered'])){ return false; }
        else { $this_skill->skill_results['flag_'.$this_skill->skill_token.'_triggered'] = true; }
        if ($this_robot->has_item('reverse-module')){ $options->break_amount *= -1; }
        $options->header_text = $this_robot->robot_name.'\'s '.$this_skill->skill_name;
        $options->extra_text = $this_robot->print_name().'\'s '.$this_skill->print_name().' prevents '.$this_skill->skill_parameters['stat'].' loss! ';
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
    },
    'skill_function_onload' => function($objects){

        // Extract objects into the global scope
        extract($objects);

        // Default to this skill being validated and go from there
        $this_skill->set_flag('validated', true);

        // Validate the "stat" parameter has been set to a valid value
        $allowed_stats = array('attack', 'defense', 'speed', 'all');
        if (!isset($this_skill->skill_parameters['stat'])
            || !in_array($this_skill->skill_parameters['stat'], $allowed_stats)){
            error_log('skill parameter "stat" was not set or was invalid ('.$this_skill->skill_token.':'.__LINE__.')');
            if (isset($this_skill->skill_parameters['stat'])){
                error_log('stat = '.print_r($this_skill->skill_parameters['stat'], true));
            }
            $this_skill->set_flag('validated', false);
            return false;
        }

        // Everything is fine so let's return true
        return true;

    }
);
?>
