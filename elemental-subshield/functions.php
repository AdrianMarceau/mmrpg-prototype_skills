<?
$functions = array(
    'skill_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Return true on success
        return true;

    },
    'rpg-ability_trigger-damage_before' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If the damage is going to be shielded, make sure we display the skill name
        if (!empty($this_ability->ability_type)
           && !$this_robot->get_flag('skill_overlay_shown')){
            // Print a message showing that this effect is taking place
            $this_robot->set_frame('taunt');
            $this_battle->queue_sound_effect('small-buff-received');
            $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
                $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
                'The skill grants '.$this_robot->get_pronoun('object').' total immunity to elemental damage!',
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
            $this_robot->set_flag('skill_overlay_shown', true);
        }

        // Return true on success
        return true;
    
    },
    'rpg-robot_check-skills_battle-start' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Add an invisible attachment preventing this robot from damaged by elemental-type abilities
        $this_attachment_token = $this_robot->robot_token.'_elemental-armor';
        $is_shielded = $this_robot->has_attachment($this_attachment_token) ? true : false;
        if (!$is_shielded
           && $this_battle->counters['battle_turn'] === 0){

            // Define this ability's attachment token
            $types = rpg_type::get_index();
            $this_attachment_info = array(
                'class' => 'ability',
                'ability_token' => 'ability',
                'ability_image' => false,
                'ability_frame' => 0,
                'ability_frame_animate' => array(0),
                'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => 0)
                );
            foreach ($types AS $type){
                if ($type['type_class'] !== 'normal'){ continue; }
                $this_attachment_info['attachment_damage_input_breaker_'.$type['type_token']] = 0;
            }
            //error_log('$this_attachment_info = '.print_r($this_attachment_info, true));

            // Attach this auto attachment to the curent robot
            $this_robot->set_attachment($this_attachment_token, $this_attachment_info);

        }

        // Return true on success
        return true;

    }
);
?>
