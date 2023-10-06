<?
$functions = array(
    'skill_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Return true on success
        return true;

    },
    'rpg-robot_check-skills_end-of-turn' => function($objects){

        // Extract objects into the global scope
        extract($objects);
        
        // Restore this robot's weapon energy by up to half it's base value
        $restore_amount = ceil($this_robot->robot_base_weapons / 2);
        $new_amount = $this_robot->robot_weapons + $restore_amount;
        if ($new_amount > $this_robot->robot_base_weapons) { $new_amount = $this_robot->robot_base_weapons; }
        $this_robot->set_frame('summon');
        $this_robot->set_weapons($new_amount);
        $this_battle->events_create(false, false, '', '');
        /* $this_battle->events_create(false, false, '', '', array(
            'this_skill' => $this_skill,
            'canvas_show_this_skill_overlay' => false,
            'canvas_show_this_skill_underlay' => false,
            'event_flag_camera_action' => true,
            'event_flag_camera_side' => $this_robot->player->player_side,
            'event_flag_camera_focus' => $this_robot->robot_position,
            'event_flag_camera_depth' => $this_robot->robot_key
            )); */
        $this_robot->reset_frame();

        // Return true on success
        return true;
        
    }
);
?>
