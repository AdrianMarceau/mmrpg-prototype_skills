<?
$functions = array(
    'skill_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Return true on success
        return true;

    },
    'rpg-robot_apply-stat-bonuses_after' => function($objects){
        //error_log('rpg-robot_apply-stat-bonuses_after() by '.$objects['this_robot']->robot_string);

        // Extract all objects into the current scope
        extract($objects);

        // Add an invisible attachment preventing this robot from damaged by neutral-type abilities
        $this_attachment_token = $this_robot->robot_token.'_'.$this_skill->skill_token;
        $is_shielded = $this_robot->has_attachment($this_attachment_token) ? true : false;
        if (!$is_shielded){

            // Define this ability's attachment token
            $this_attachment_info = array(
                'class' => 'ability',
                'ability_token' => 'ability',
                'ability_image' => false,
                'ability_frame' => 0,
                'ability_frame_animate' => array(0),
                'ability_frame_offset' => array('x' => 0, 'y' => 0, 'z' => 0),
                'attachment_token' => $this_attachment_token,
                'attachment_damage_input_breaker_none' => 0
                );

            // Attach this auto attachment to the curent robot
            $this_robot->set_attachment($this_attachment_token, $this_attachment_info);
            //error_log('applying the '.$this_skill->skill_token.' to '.$this_robot->robot_string);

        }

        // Return true on success
        return true;

    },
    'rpg-ability_trigger-damage_before' => function($objects){
        //error_log('rpg-ability_trigger-damage_before() by '.$objects['options']->damage_initiator->robot_string.' w/ '.$objects['this_ability']->ability_token);

        // Extract all objects into the current scope
        extract($objects);
        //error_log('$this_robot->robot_string = '.$this_robot->robot_string);
        //error_log('$target_robot->robot_string = '.$target_robot->robot_string);
        //error_log('$options->damage_initiator->robot_string = '.$options->damage_initiator->robot_string);
        //error_log('$options->damage_target->robot_string = '.$options->damage_target->robot_string);

        // If the ability being used is not by this robot, it's not relevant
        if ($options->damage_target !== $this_robot){ return false; }
        //error_log('WE ARE THE TARGET!  Check if we should block damage...');

        // If the damage is going to be shielded, make sure we display the skill name
        if (empty($this_ability->ability_type)
           && !$this_robot->get_flag('skill_overlay_shown')){
            // Print a message showing that this effect is taking place
            $this_robot->set_frame('taunt');
            $this_battle->queue_sound_effect('small-buff-received');
            $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
                $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
                'The skill grants '.$this_robot->get_pronoun('object').' total immunity to Neutral type damage!',
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
    
    }
);
?>
