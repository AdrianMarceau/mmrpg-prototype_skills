<?
$functions = array(
    'skill_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Return true on success
        return true;

    },
    'rpg-robot_check-skills_end-of-turn' => function($objects){
        //error_log('rpg-robot_check-skills_end-of-turn() for '.$objects['this_robot']->robot_string);

        // Extract all objects into the current scope
        extract($objects);

        // Check to see if there's already a Super Block at this position
        $static_attachment_info = array();
        $static_ability_token = 'super-arm';
        $static_ability_object_token = 'super-block';
        $static_attachment_key = $this_robot->get_static_attachment_key();
        $static_attachment_token = 'ability_'.$static_ability_token.'_'.$static_ability_object_token.'_'.$static_attachment_key;
        $is_summoned = isset($this_battle->battle_attachments[$static_attachment_key][$static_attachment_token]) ? true : false;
        //error_log('Checking for super block at '.$static_attachment_key.' with token '.$static_attachment_token);
        //error_log('$this_battle->battle_attachments = '.print_r($this_battle->battle_attachments, true));
        //error_log('$is_summoned = '.(!empty($is_summoned) ? 'true' : 'false'));
        if (!empty($this_battle->battle_attachments[$static_attachment_key][$static_attachment_token])){
            //error_log('There\'s already a super block at this position!');
            return;
        }

        // Define the sprite sheet and animation defaults
        $this_field_token = $this_battle->battle_field->field_background;
        $this_sprite_sheet = 1;
        $this_target_frame = 0;
        $this_impact_frame = 1;
        $this_object_name = 'boulder';

        // Collect the sprite index for this skill from the original ability
        $this_ability = $this_robot->get_ability_object('super-arm');
        $this_sprite_index = rpg_ability::get_static_index($this_ability, 'super-block', 'sprite-index');
        //error_log('$this_sprite_index = '.print_r($this_sprite_index, true));

        // If the field token has a place in the index, update values
        if (isset($this_sprite_index[$this_field_token])){
            $this_sprite_sheet = $this_sprite_index[$this_field_token][0];
            $this_target_frame = $this_sprite_index[$this_field_token][1];
            $this_impact_frame = $this_sprite_index[$this_field_token][2];
            $this_object_name = $this_sprite_index[$this_field_token][3];
        }

        // Upper-case object name while being sensitive to of/the/a/etc.
        $this_object_name = ucwords($this_object_name);
        $this_object_name = str_replace(array(' A ', ' An ', ' Of ', ' The '), array(' a ', ' an ', ' of ', ' the '), $this_object_name);
        $this_object_name_span = rpg_type::print_span('impact_shield', $this_object_name);

        // Define this ability's attachment token
        $static_attachment_duration = 10;
        $static_attachment_info = rpg_ability::get_static_attachment($this_ability, 'super-block', $static_attachment_key, $static_attachment_duration);
        //error_log('$static_attachment_info = '.print_r($static_attachment_info, true));
        $this_attachment = rpg_game::get_ability($this_battle, $this_player, $this_robot, $static_attachment_info);
        $this_attachment->set_image($static_attachment_info['ability_image']);

        // If the ability flag was not set, this ability begins charging
        if (!$is_summoned){

            // Attach this ability attachment to the battle field itself
            //$static_attachment_info['ability_frame_styles'] = 'opacity: 0.5; ';
            $static_attachment_info['ability_frame_styles'] = 'transform: scale(0.5) translate(0, 50%); ';
            $this_battle->battle_attachments[$static_attachment_key][$static_attachment_token] = $static_attachment_info;
            $this_battle->update_session();

            // Print a message showing that this effect is taking place
            $this_robot->set_frame('summon');
            $this_battle->queue_sound_effect('spawn-sound');
            $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
                $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
                ucfirst($this_robot->get_pronoun('subject')).' created '.
                    (preg_match('/^(a|e|i|o|u)/i', $this_object_name) ? 'an ' : 'a ').$this_object_name_span.' '.
                    'at '.$this_robot->get_pronoun('possessive2').' position! ',
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

            // Attach this ability attachment to the battle field itself
            $static_attachment_info['ability_frame_styles'] = '';
            $this_battle->battle_attachments[$static_attachment_key][$static_attachment_token] = $static_attachment_info;
            $this_battle->update_session();

            // Show another frame with the block reaching full size and finishing being summoned
            $this_robot->set_frame('defend');
            $this_battle->queue_sound_effect('smack-sound');
            $this_battle->events_create($this_robot, false, '', '',
                array(
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $this_robot->player->player_side,
                    'event_flag_camera_focus' => $this_robot->robot_position,
                    'event_flag_camera_depth' => $this_robot->robot_key
                    )
                );
            $this_robot->reset_frame();

        }

        // Return true on success
        return true;

    }
);
?>
