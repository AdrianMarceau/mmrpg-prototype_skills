<?
$functions = array(
    'skill_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Return true on success
        return true;

    },
    'rpg-robot_trigger-ability_before' => function($objects){

        // Extract objects into the global scope
        extract($objects);
        //error_log('skill // '.$this_robot->robot_token.' rpg-robot_trigger-ability_before()');

        // Otherwise, collect this robot's new core type and the last abilities used by it
        $this_core_type = $this_robot->robot_core;
        $this_triggered_abilities = !empty($this_robot->history['triggered_abilities']) ? $this_robot->history['triggered_abilities'] : array();
        $this_triggered_abilities = array_reverse($this_triggered_abilities);

        // If the robot has been reset to default, we gotta remove any changes
        $next_core_shift_target = array();
        if (empty($this_triggered_abilities)){

            // Pull the last ability used by this robot, which is the one that changed it type
            $this_master_token = $this_robot->robot_token;
            $this_master_info = !empty($this_master_token) ? rpg_robot::get_index_info($this_master_token) : null;
            $next_core_shift_target = $this_master_info;

        }
        // Otherwise we gotta update their weaknesses, resistances, etc. based on last-used ability
        else {

            // Pull the last ability used by this robot, which is the one that changed it type
            $last_ability_token = reset($this_triggered_abilities);
            $last_ability_info = rpg_ability::get_index_info($last_ability_token);
            $last_ability_master = !empty($last_ability_info['ability_master']) ? $last_ability_info['ability_master'] : null;
            $last_ability_master_info = !empty($last_ability_master) ? rpg_robot::get_index_info($last_ability_master) : null;
            $next_core_shift_target = $last_ability_master_info;

        }

        // Check to see if it's necessary to display the change message
        $this_core_shift_required = false;
        if (!empty($next_core_shift_target)
            && (empty($this_robot->values['last_core_shift_target'])
                || $this_robot->values['last_core_shift_target'] !== $next_core_shift_target['robot_token'])){
            $this_core_shift_required = true;
        }

        // If we were able to pull original owner robot for this ability, clone their weaknesses, resistances, affinities, and immunities to this robot
        if ($this_core_shift_required){

            // Update this robot with its new weaknesses, resistances, affinities, and immunities
            $this_skill->set_value('skill_source_robot', $next_core_shift_target['robot_token']);
            $this_robot->set_value('last_core_shift_target', $next_core_shift_target['robot_token']);
            $this_robot->set_weaknesses($next_core_shift_target['robot_weaknesses']);
            $this_robot->set_resistances($next_core_shift_target['robot_resistances']);
            $this_robot->set_affinities($next_core_shift_target['robot_affinities']);
            $this_robot->set_immunities($next_core_shift_target['robot_immunities']);
            $this_robot->set_base_weaknesses($next_core_shift_target['robot_weaknesses']);
            $this_robot->set_base_resistances($next_core_shift_target['robot_resistances']);
            $this_robot->set_base_affinities($next_core_shift_target['robot_affinities']);
            $this_robot->set_base_immunities($next_core_shift_target['robot_immunities']);

            // Generate the event message header and body text
            $next_master_name_span = rpg_type::print_span($next_core_shift_target['robot_core'], $next_core_shift_target['robot_name']);
            $this_event_header = $this_robot->robot_name.'\'s '.$this_skill->skill_name;
            $this_event_body = $this_robot->print_name_s().' triggers '.$this_robot->get_pronoun('possessive2').' '.$this_skill->print_name().'! ';
            $this_event_body .= '<br /> Memories of '.$next_master_name_span.' reconfigure '.$this_robot->get_pronoun('possessive2').' program data!';
            $this_event_options = array(
                'this_skill' => $this_skill,
                'canvas_show_this_skill' => true,
                'event_flag_camera_action' => true,
                'event_flag_camera_side' => $this_robot->player->player_side,
                'event_flag_camera_focus' => $this_robot->robot_position,
                'event_flag_camera_depth' => $this_robot->robot_key
                );

            // Set up the scene and then create the events
            $this_robot->set_frame('summon');
            $this_battle->queue_sound_effect('hyper-summon-sound');
            $this_battle->events_create($this_robot, false, $this_event_header, $this_event_body, $this_event_options);
            $this_robot->set_frame('defend');
            $this_battle->events_create($this_robot, false, '', '', $this_event_options);
            $this_robot->reset_frame();

        }

        // Return true on success
        return true;

    }
);
?>
