<?
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'rpg-robot_check-skills_update-saboteurs' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Turn ON the priority-blocking feature of this skill
        // by adding this robot's ID to the player's anti_recovery list
        $anti_recovery_robots = $this_player->get_value('anti_recovery_robots');
        if (empty($anti_recovery_robots)){ $anti_recovery_robots = array(); }
        if (!in_array($this_robot->robot_id, $anti_recovery_robots)){ $anti_recovery_robots[] = $this_robot->robot_id; }
        $this_player->set_value('anti_recovery_robots', $anti_recovery_robots);

        // Print a message showing that this effect is taking place
        $this_robot->set_frame('taunt');
        $this_battle->queue_sound_effect('scan-start');
        $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
            $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
            'Recovery abilities are useless as long as this robot remains active!',
            array(
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

        // Return true on success
        return true;

    },
    'rpg-robot_trigger-disabled_after' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If this robot is not the target, then we can return early
        if ($this_robot !== $options->disabled_target){ return false; }

        // Turn OFF the priority-blocking feature of this skill
        // by removing this robot's ID to the player's anti_recovery list
        $anti_recovery_robots = $this_player->get_value('anti_recovery_robots');
        if (empty($anti_recovery_robots)){ $anti_recovery_robots = array(); }
        if (in_array($this_robot->robot_id, $anti_recovery_robots)){ $anti_recovery_robots = array_diff($anti_recovery_robots, array($this_robot->robot_id)); }
        $this_player->set_value('anti_recovery_robots', $anti_recovery_robots);

        // Return true on success
        return true;

    },
);
$functions['rpg-robot_check-skills_battle-start'] = function($objects) use ($functions){
    return $functions['rpg-robot_check-skills_update-saboteurs']($objects, true);
};
$functions['rpg-robot_check-skills_turn-start'] = function($objects) use ($functions){
    return $functions['rpg-robot_check-skills_update-saboteurs']($objects, true);
};
$functions['rpg-battle_switch-in_after'] = function($objects) use ($functions){
    return $functions['rpg-robot_check-skills_update-saboteurs']($objects, false);
};
?>
